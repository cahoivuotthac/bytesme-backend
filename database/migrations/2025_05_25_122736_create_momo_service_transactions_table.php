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
		Schema::create('momo_service.transactions', function (Blueprint $table) {
			$table->string('transaction_id');
			$table->primary(['transaction_id']);
			$table->foreignId('order_id')->constrained('public.orders', 'order_id')->onDelete('cascade');
		});
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void
	{
		Schema::dropIfExists('momo_service.transactions');
	}
};
