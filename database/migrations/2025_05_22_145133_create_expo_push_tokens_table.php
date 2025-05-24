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
		Schema::create('expo_push_tokens', function (Blueprint $table) {
			$table->id('push_token_id');
			$table->string('push_token');
			$table->foreignId('user_id')
				->constrained('users', 'user_id')
				->onDelete('cascade');
		});
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void
	{
		Schema::table('users', function (Blueprint $table) {
			//
		});
	}
};
