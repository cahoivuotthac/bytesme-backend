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
		Schema::create('user_addresses', function (Blueprint $table) {
			$table->id('user_address_id');

			// Address details
			$table->string('urban_name');
			$table->string('suburb_name');
			$table->string('quarter_name')->nullable();
			$table->string('full_address');
			$table->boolean('is_default_address')->default(false);

			// Foreing key
			$table->foreignId('user_id')->constrained('users', 'user_id')->onDelete('cascade');
			$table->timestamps();
		});
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void
	{
		Schema::dropIfExists('user_addresses');
	}
};
