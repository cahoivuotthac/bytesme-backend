<?php

namespace app\services;

use App\Providers\DBConnService;
use Exception;
use Log;

class CredentialsValidatorService
{
	protected DBConnService $dbConnService;

	public function __construct(DBConnService $dBConnService)
	{
		$this->dbConnService = $dBConnService;
	}

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

		// check email availability
		// if (!$is_login) {
		// 	try {
		// 		$conn = $this->dbConnService->getDBConn();
		// 		$sql = "select email from users where email = ?";
		// 		$pstm = $conn->prepare($sql);
		// 		$pstm->bind_param("s", $email);
		// 		$pstm->execute();
		// 		$result = $pstm->get_result();
		// 		if ($result->num_rows > 0) {
		// 			throw new Exception(message: "email has been taken");
		// 		}
		// 	} catch (Exception $e) {
		// 		Log::error("Error occurred when validating email", [
		// 			'error' => $e->getMessage(),
		// 		]);
		// 	} finally {
		// 		$pstm->close();
		// 	}
		// }

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