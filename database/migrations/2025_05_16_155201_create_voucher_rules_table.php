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
		Schema::create('voucher_rules', function (Blueprint $table) {
			$table->id('rule_id');
			$table->string('voucher_rule_type')->nullable();
			$table->string('voucher_rule_value')->nullable();
			$table->unsignedBigInteger('voucher_id');
			$table->foreign('voucher_id')->references('voucher_id')->on('vouchers')->onDelete('cascade');
		});
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void
	{
		Schema::dropIfExists('voucher_rules');
	}
};
