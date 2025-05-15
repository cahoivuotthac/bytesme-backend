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
		Schema::create('product_feedbacks', function (Blueprint $table) {
			$table->id('product_feedback_id');
			$table->foreignId('product_id')->constrained('products', 'product_id')->onDelete('cascade');
			$table->foreignId('user_id')->constrained('users', 'user_id')->onDelete('cascade');
			$table->integer('num_star');
			$table->string('feedback_content')->nullable();
			$table->json('feedback_tags')->nullable();
			$table->timestamps();
		});
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void
	{
		Schema::dropIfExists('product_feedbacks');
	}
};
