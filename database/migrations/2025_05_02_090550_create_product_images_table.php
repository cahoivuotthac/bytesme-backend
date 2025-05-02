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
		Schema::create('product_images', function (Blueprint $table) {
			$table->id('product_image_id');
			$table->unsignedBigInteger('product_id')->nullable();
			$table->string('product_image_name')->nullable();
			$table->string('product_image')->nullable();
			$table->string('product_image_url')->nullable();
			$table->integer('image_type')->nullable();
			$table->timestamps();

			// Add foreign key constraint
			$table->foreign('product_id')
				->references('product_id')
				->on('products')
				->onDelete('cascade');
		});
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void
	{
		Schema::dropIfExists('product_images');
	}
};