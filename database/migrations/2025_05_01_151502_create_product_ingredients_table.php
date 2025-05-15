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
		Schema::create('product_ingredients', function (Blueprint $table) {
			$table->unsignedBigInteger('product_id');
			$table->unsignedBigInteger('ingredient_id');

			$table->foreign('product_id')->references('product_id')->on('products')->onDelete('cascade');
			$table->foreign('ingredient_id')->references('ingredient_id')->on('ingredients')->onDelete('cascade');

			$table->string('amount_used');

			$table->primary(['product_id', 'ingredient_id']); // Composite key
			$table->timestamps();
		});
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void
	{
		Schema::dropIfExists('product_ingredients');
	}
};
