<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Địa chỉ, wishlist, voucher, flash sale, đơn hàng, thanh toán, đổi trả, đánh giá, thông báo, lịch sử xem.
 */
class CreateShopTables extends Migration
{
    public function up()
    {
        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('receiver_name');
            $table->string('phone', 20);
            $table->string('province', 100);
            $table->string('district', 100);
            $table->string('ward', 100);
            $table->string('detail', 255);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        Schema::create('wishlists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('book_id')->constrained('books')->onDelete('cascade');
            $table->timestamps();
            $table->unique(['user_id', 'book_id']);
        });

        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->string('type', 10); // percent | fixed
            $table->unsignedInteger('value');
            $table->unsignedInteger('max_discount')->nullable();
            $table->unsignedInteger('min_order')->default(0);
            $table->unsignedInteger('usage_limit')->nullable();
            $table->unsignedInteger('used_count')->default(0);
            $table->unsignedInteger('per_user_limit')->default(1);
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('flash_sales', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('flash_sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('flash_sale_id')->constrained('flash_sales')->onDelete('cascade');
            $table->foreignId('variant_id')->constrained('book_variants')->onDelete('cascade');
            $table->unsignedInteger('sale_price');
            $table->unsignedInteger('quantity');      // số suất flash sale
            $table->unsignedInteger('sold')->default(0);
            $table->timestamps();
            $table->unique(['flash_sale_id', 'variant_id']);
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->foreignId('user_id')->constrained('users');
            $table->string('receiver_name');
            $table->string('phone', 20);
            $table->string('address', 500);
            $table->string('note', 255)->nullable();
            $table->unsignedInteger('subtotal');
            $table->unsignedInteger('discount')->default(0);
            $table->unsignedInteger('shipping_fee')->default(0);
            $table->unsignedInteger('total');
            $table->foreignId('voucher_id')->nullable()->constrained('vouchers')->onDelete('set null');
            $table->string('voucher_code', 50)->nullable();
            $table->string('payment_method', 20)->default('cod'); // cod | online
            $table->string('payment_status', 20)->default('unpaid'); // unpaid | paid | failed | refunded
            $table->string('status', 20)->default('pending'); // pending | confirmed | shipping | delivered | cancelled | returned
            $table->string('shipping_carrier', 50)->nullable();
            $table->string('tracking_code', 50)->nullable();
            $table->foreignId('shipper_id')->nullable()->constrained('users')->onDelete('set null');
            $table->unsignedTinyInteger('fraud_score')->default(0); // 0-100: điểm rủi ro bất thường
            $table->string('fraud_flags', 255)->nullable();
            $table->string('cancel_reason', 255)->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->foreignId('book_id')->nullable()->constrained('books')->onDelete('set null');
            $table->foreignId('variant_id')->nullable()->constrained('book_variants')->onDelete('set null');
            $table->foreignId('flash_sale_item_id')->nullable()->constrained('flash_sale_items')->onDelete('set null');
            $table->string('book_title');
            $table->string('variant_name', 100);
            $table->unsignedInteger('price');
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('total');
            $table->timestamps();
        });

        Schema::create('order_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('status', 20);
            $table->string('note', 255)->nullable();
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->string('method', 20);
            $table->unsignedInteger('amount');
            $table->string('transaction_code', 50)->nullable();
            $table->string('status', 20); // pending | success | failed
            $table->string('bank_code', 20)->nullable();
            $table->timestamps();
        });

        Schema::create('return_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('type', 20); // return (trả hàng hoàn tiền) | exchange (đổi hàng)
            $table->text('reason');
            $table->string('status', 20)->default('pending'); // pending | approved | rejected | completed
            $table->unsignedInteger('refund_amount')->default(0);
            $table->string('admin_note', 255)->nullable();
            $table->timestamps();
        });

        Schema::create('return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('return_request_id')->constrained('return_requests')->onDelete('cascade');
            $table->foreignId('order_item_id')->constrained('order_items')->onDelete('cascade');
            $table->unsignedInteger('quantity');
        });

        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained('books')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->unsignedTinyInteger('rating');
            $table->text('comment')->nullable();
            $table->boolean('is_visible')->default(true);
            $table->string('sentiment', 10)->nullable(); // positive | neutral | negative
            $table->float('sentiment_score')->default(0);
            $table->text('aspects')->nullable(); // JSON: khía cạnh được nhắc tới (nội dung, giấy, giao hàng...)
            $table->timestamps();
            $table->unique(['book_id', 'user_id']);
        });

        Schema::create('user_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('title', 255);
            $table->text('content');
            $table->string('link', 255)->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamps();
        });

        // Lịch sử xem sách – dùng cho gợi ý
        Schema::create('book_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('book_id')->constrained('books')->onDelete('cascade');
            $table->timestamps();
            $table->unique(['user_id', 'book_id']);
        });
    }

    public function down()
    {
        foreach (['book_views', 'user_notifications', 'reviews', 'return_items', 'return_requests', 'payments',
                  'order_status_histories', 'order_items', 'orders', 'flash_sale_items', 'flash_sales',
                  'vouchers', 'wishlists', 'addresses'] as $t) {
            Schema::dropIfExists($t);
        }
    }
}
