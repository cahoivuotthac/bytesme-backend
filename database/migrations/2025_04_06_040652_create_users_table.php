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
		/**
		 *Php -> MySQL datatype mapping:
		 * string -> varchar
		 * longText -> longBlob
		 * integer -> int
		 * date -> date
		 * timestamp -> timestamp
		 * tinyInteger -> tinyint
		 */
		Schema::create('users', function (Blueprint $table) {
			$table->id('user_id');
			$table->string('name', 50);
			$table->string('email')->unique();
			$table->timestamp('phone_verified_at')->nullable();
			$table->string('password');
			$table->string('phone_number', 10)->unique();
			$table->string('urban', 255)->nullable();
			$table->string('suburb', 255)->nullable();
			$table->string('quarter', 255)->nullable();
			$table->string('address', 255)->nullable();
			$table->integer('cart_id')->nullable()->index();
			$table->longText('avatar')->nullable();
			$table->string('gender', 4)->nullable();
			$table->date('date_of_birth')->nullable();
			$table->tinyInteger('role_type')->unsigned()->default(0)->comment('0: regular user, 1: admin');
			$table->rememberToken();
			$table->timestamps();
		});

		Schema::create('password_reset_tokens', function (Blueprint $table) {
			$table->string('phone_number')->primary();
			$table->string('token');
			$table->timestamp('created_at')->nullable();
		});

		Schema::create('sessions', function (Blueprint $table) {
			$table->string('id')->primary();
			$table->foreignId('user_id')->nullable()->index();
			$table->string('ip_address', 45)->nullable();
			$table->text('user_agent')->nullable();
			$table->longText('payload');
			$table->integer('last_activity')->index();
		});
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void
	{
		Schema::dropIfExists('users');
		Schema::dropIfExists('password_reset_tokens');
		Schema::dropIfExists('sessions');
	}
};
