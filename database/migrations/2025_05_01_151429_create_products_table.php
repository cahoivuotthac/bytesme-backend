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
				$table->integer('product_type')->nullable();
				$table->string('product_code')->nullable();
				$table->string('product_name')->nullable();
				$table->text('product_description')->nullable();
				$table->integer('product_unit_price')->nullable();
				$table->float('product_discount_percentage')->nullable();
				$table->integer('product_total_orders')->nullable()->default(0);
				$table->integer('product_total_ratings')->nullable()->default(0);
				$table->float('product_overall_stars')->nullable()->default(0);
				$table->integer('product_stock_quantity')->nullable()->default(0);
			$table->timestamps();
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
