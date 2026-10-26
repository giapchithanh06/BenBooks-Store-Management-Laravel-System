<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bảng phục vụ nhóm chức năng nâng cao (cột C):
 * hành vi khách hàng, vector index cho semantic search, kho tri thức RAG, lịch sử chatbot, hàng đợi (message queue).
 */
class CreateAiTables extends Migration
{
    public function up()
    {
        // Phân tích hành vi khách hàng: view | search | add_cart | wishlist | purchase
        Schema::create('user_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->string('session_id', 100)->nullable();
            $table->string('action', 20);
            $table->foreignId('book_id')->nullable()->constrained('books')->onDelete('cascade');
            $table->string('keyword')->nullable();
            $table->timestamps();
            $table->index(['action', 'created_at']);
        });

        // Vector TF-IDF của từng sách (Semantic Search + "vector database" đơn giản)
        Schema::create('book_vectors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->unique()->constrained('books')->onDelete('cascade');
            $table->longText('vector'); // JSON {term: weight}
            $table->float('norm')->default(0);
            $table->timestamps();
        });

        // Kho tri thức cho RAG: chính sách, hướng dẫn, FAQ
        Schema::create('knowledge_docs', function (Blueprint $table) {
            $table->id();
            $table->string('title', 255);
            $table->string('category', 50)->default('policy');
            $table->text('content');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->string('session_id', 100)->nullable();
            $table->text('question');
            $table->text('answer');
            $table->string('intent', 30)->nullable();
            $table->timestamps();
        });

        // Message Queue – database driver của Laravel
        if (!Schema::hasTable('jobs')) {
            Schema::create('jobs', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('queue')->index();
                $table->longText('payload');
                $table->unsignedTinyInteger('attempts');
                $table->unsignedInteger('reserved_at')->nullable();
                $table->unsignedInteger('available_at');
                $table->unsignedInteger('created_at');
            });
        }

        if (!Schema::hasTable('failed_jobs')) {
            Schema::create('failed_jobs', function (Blueprint $table) {
                $table->id();
                $table->string('uuid')->unique();
                $table->text('connection');
                $table->text('queue');
                $table->longText('payload');
                $table->longText('exception');
                $table->timestamp('failed_at')->useCurrent();
            });
        }

        // Nhật ký xử lý hàng đợi (để demo message queue trên trang admin)
        Schema::create('queue_logs', function (Blueprint $table) {
            $table->id();
            $table->string('job');
            $table->string('message', 255);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('queue_logs');
        Schema::dropIfExists('jobs');
        Schema::dropIfExists('chat_messages');
        Schema::dropIfExists('knowledge_docs');
        Schema::dropIfExists('book_vectors');
        Schema::dropIfExists('user_activities');
    }
}
