<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Log;

class CredentialsValidatorService
{
	public function validateAndReturnEmail(string $email, bool $is_login = false): mixed
	{
		// email length check
		if (strlen($email) > 255) {
			throw new Exception("email is too long");
		}

		// email format check
		if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
			throw new Exception("email format is invalid");
		}

		 // not throwing any exception, all good
		return $email;
	}

	public function validateAndReturnName(string $name)
	{
		if (strlen($name) > 255) {
			throw new Exception("name is too long");
		}

		// not throwing any exception, all good
		return $name;
	}

	public function validateAndReturnPassword(string $password)
	{
		// password length check
		if (strlen($password) < 8) {
			throw new Exception(message: "Mật khẩu phải chứa ít nhất 8 ký tự");
		}

		// not throwing any exception, all good
		return $password;
	}
}