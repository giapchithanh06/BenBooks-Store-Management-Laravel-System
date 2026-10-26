<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Danh mục (thể loại), tác giả, nhà xuất bản, sách, biến thể sách, kho.
 */
class CreateCatalogTables extends Migration
{
    public function up()
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->onDelete('set null');
            $table->text('description')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('authors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('bio')->nullable();
            $table->timestamps();
        });

        Schema::create('publishers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('address', 255)->nullable();
            $table->string('website', 255)->nullable();
            $table->timestamps();
        });

        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->string('title', 255);
            $table->string('slug')->unique();
            $table->foreignId('category_id')->constrained('categories');
            $table->foreignId('publisher_id')->nullable()->constrained('publishers')->onDelete('set null');
            $table->string('isbn', 20)->nullable();
            $table->unsignedSmallInteger('publish_year')->nullable();
            $table->unsignedInteger('pages')->nullable();
            $table->string('language', 50)->default('Tiếng Việt');
            $table->text('description')->nullable();
            $table->string('tags', 255)->nullable(); // từ khóa – dùng cho tìm kiếm ngữ nghĩa
            $table->string('cover_image')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('view_count')->default(0);
            $table->unsignedInteger('sold_count')->default(0);
            $table->decimal('rating_avg', 3, 2)->default(0);
            $table->unsignedInteger('rating_count')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('author_book', function (Blueprint $table) {
            $table->foreignId('author_id')->constrained('authors')->onDelete('cascade');
            $table->foreignId('book_id')->constrained('books')->onDelete('cascade');
            $table->primary(['author_id', 'book_id']);
        });

        // Biến thể: Bìa mềm / Bìa cứng / Bản đặc biệt ... mỗi biến thể có giá & tồn kho riêng
        Schema::create('book_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained('books')->onDelete('cascade');
            $table->string('name', 100);
            $table->string('sku', 50)->unique();
            $table->unsignedInteger('price');
            $table->unsignedInteger('original_price')->nullable();
            $table->integer('stock')->default(0);
            $table->timestamps();
        });

        // Lịch sử nhập / xuất kho
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('variant_id')->constrained('book_variants')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('type', 20); // import | export | order | cancel | return | adjust
            $table->integer('quantity');  // + nhập, - xuất
            $table->integer('stock_after');
            $table->string('note', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('book_variants');
        Schema::dropIfExists('author_book');
        Schema::dropIfExists('books');
        Schema::dropIfExists('publishers');
        Schema::dropIfExists('authors');
        Schema::dropIfExists('categories');
    }
}
