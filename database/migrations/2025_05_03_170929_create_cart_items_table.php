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
		Schema::create('cart_items', function (Blueprint $table) {
			// Key columns
			$table->unsignedBigInteger('cart_id');
			$table->unsignedBigInteger('product_id');
			$table->primary(['cart_id', 'product_id']); // Composite key
			$table->foreign('cart_id')->references('cart_id')->on('carts')->onDelete('cascade');
			$table->foreign('product_id')->references('product_id')->on('products')->onDelete('cascade');

			// Other columns
			$table->integer('cart_items_quantity')->default(0);
			$table->integer('cart_items_unitprice')->default(0);
			$table->string('cart_items_size')->nullable();

			$table->timestamps();
		});
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void
	{
		Schema::dropIfExists('cart_items');
	}
};
