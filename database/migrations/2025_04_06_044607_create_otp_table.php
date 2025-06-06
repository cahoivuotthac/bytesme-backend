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
		Schema::create('otp', function (Blueprint $table) {
			$table->string('email')->unique(); // Unique email
			$table->primary('email');
			$table->string('code');
			$table->timestamp('verified_at')->nullable();
			$table->timestamps();  // created_at and updated_at fields
		});
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void
	{
		Schema::dropIfExists('otp');
	}
};