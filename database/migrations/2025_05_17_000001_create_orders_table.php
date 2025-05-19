<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id('order_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('voucher_id')->nullable();

            $table->integer('order_provisional_price');
            $table->integer('order_deliver_cost');
            $table->timestamp('order_deliver_time')->nullable();
            $table->string('order_deliver_address', 255);

            $table->integer('order_total_price');
            $table->timestamp('order_payment_date')->nullable();
            $table->string('order_payment_method', 50); // "Banking" or "COD"
            $table->boolean('order_is_paid')->default(false);
            $table->enum('order_status', ['pending', 'delivering', 'delivered', 'cancelled'])->default('pending');
            $table->text('order_additional_note')->nullable();
			$table->text('order_addtional_note')->nullable();

            $table->timestamps();

            $table->foreign('user_id')->references('user_id')->on('users')->onDelete('cascade');
            $table->foreign('voucher_id')->references('voucher_id')->on('vouchers')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};