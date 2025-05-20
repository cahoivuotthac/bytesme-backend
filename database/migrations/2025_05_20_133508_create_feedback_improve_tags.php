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
		Schema::create('feedback_improve_tags', function (Blueprint $table) {
			$table->id('feedback_improve_tag_id');
			$table->enum('tag', [
				'flavour',
				'packaging',
				'act-of-service',
				'delivery-time',
				'product-quality',
				'other'
			]);
			$table->foreignId('order_feedback_id')
				->constrained('order_feedbacks', 'order_feedback_id')
				->onDelete('cascade');
			$table->timestamps();
		});
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void
	{
		Schema::dropIfExists('feedback_improve_tags');
	}
};
