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
		Schema::create('vouchers', function (Blueprint $table) {
			$table->id('voucher_id');
			$table->string('voucher_name', 50);
			$table->string('voucher_code', 50)->unique();
			$table->string('voucher_description', 100);
			$table->text('voucher_fields');
			$table->timestamp('voucher_start_date');
			$table->timestamp('voucher_end_date');
			$table->enum('voucher_type', ['percentage', 'cash', 'gift_product']);
			$table->text('voucher_value');
			$table->timestamps();
		});
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void
	{
		Schema::dropIfExists('vouchers');
	}
};
