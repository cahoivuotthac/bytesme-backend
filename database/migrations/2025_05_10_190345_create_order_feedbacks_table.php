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
		Schema::create('order_feedbacks', function (Blueprint $table) {
			$table->id('order_feedback_id');
			$table->foreignId('user_id')->nullable()->constrained('users', 'user_id')->onDelete('cascade');
			$table->foreignId('order_id')->constrained('orders', 'order_id')->onDelete('cascade');
			$table->integer('num_star');
			$table->string('feedback_content')->nullable();
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
