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
		Schema::create('feedback_images', function (Blueprint $table) {
			$table->id('feedback_image_id');
			$table->foreignId('product_feedback_id')->constrained('product_feedbacks', 'product_feedback_id')->onDelete('cascade');
			$table->longText('feedback_image');
			$table->timestamps();
		});
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void
	{
		Schema::dropIfExists('feedback_images');
	}
};
