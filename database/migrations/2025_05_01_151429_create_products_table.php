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
		Schema::create('products', function (Blueprint $table) {
			$table->id('product_id');
			$table->string('product_code');
			$table->string('product_name');
			$table->json('product_sizes_prices');
			$table->longText('product_description')->nullable();
			$table->float('product_discount_percentage')->default(0);
			$table->integer('product_total_orders')->default(0);
			$table->integer('product_total_ratings')->default(0);
			$table->float('product_overall_stars')->default(0);
			$table->integer('product_stock_quantity')->default(0);
			$table->timestamps();

			// Foreign key constraints
			$table->foreignId('category_id')->constrained('categories', 'category_id')->onDelete('cascade');
		});
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void
	{
		Schema::dropIfExists('products');
	}
};
