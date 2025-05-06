<?php

namespace App\Http\Controllers;

use DB;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\PasswordResets;
use App\Models\Cart;
use App\Models\OTP;
use Illuminate\Support\Facades\Cache;
use Exception;
use Auth;
use App\Providers\DBConnService;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Log;
use App\Services\CredentialsValidatorService;

class AuthUtils
{
	public static function random_string(
		int $length = 64,
		string $keyspace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'
	): string {
		if ($length < 1) {
			throw new \RangeException("Length must be a positive integer");
		}
		$pieces = [];
		$max = mb_strlen($keyspace, '8bit') - 1;
		for ($i = 0; $i < $length; ++$i) {
			$pieces[] = $keyspace[random_int(0, $max)];
		}
		return implode('', $pieces);
	}

	public static function random_password(): string
	{
		return bcrypt(self::random_string());
	}

	public static function random_name(string $prefix): string
	{
		$prefixLen = strlen($prefix);

		if ($prefixLen > 50) {
			throw new \RangeException("Prefix length must be less than or equal to 50");
		}

		$nameLen = min($prefixLen + 4, 50);
		$randomUsername = $prefix . self::random_string($nameLen - strlen($prefix));
		return $randomUsername;
	}
}

class AuthController extends Controller
{
	protected CredentialsValidatorService $credentialsValidatorService;
	protected DBConnService $dbConnService;

	public function __construct(
		DBConnService $dbConnService,
		CredentialsValidatorService $credentialsValidatorService,
	) {
		$this->dbConnService = $dbConnService;
		$this->credentialsValidatorService = $credentialsValidatorService;
	}

	// Handle login with Sanctum token
	public function handleSignin(Request $request)
	{
		$email = $request->input('email');
		$password = $request->input('password');

		try {
			$email = $this->credentialsValidatorService->validateAndReturnEmail($email, true);
			$password = $this->credentialsValidatorService->validateAndReturnPassword($password);
		} catch (Exception $e) {
			Log::error("An error occurred in func. handleLogin()", [
				'error' => $e->getMessage(),
				'request_data' => $request->all(), // Optional: log request data
			]);
			return response()->json([
				'success' => false,
				'message' => $e->getMessage()
			], 400);
		}

		try {
			// Find user by email
			$user = User::where('email', $email)->first();

			if (!$user || !password_verify($password, $user->password)) {
				return response()->json([
					'success' => false,
					'message' => 'Thông tin đăng nhập sai'
				], 401);
			}

			// Create token with device name
			$deviceName = $request->input('device_name', 'mobile');
			$token = $user->createToken($deviceName)->plainTextToken;

			return response()->json([
				'success' => true,
				'message' => 'Đăng nhập thành công',
				'user' => $user,
				'token' => $token
			]);

		} catch (Exception $e) {
			Log::error("An error occurred in func. handleLogin()", [
				'error' => $e->getMessage(),
				'request_data' => $request->all(),
			]);
			return response()->json([
				'success' => false,
				'message' => 'Có lỗi xảy ra khi đăng nhập'
			], 500);
		}
	}

	// Handle registration with token
	public function handleSignup(Request $request): mixed
	{
		$email = $request->input('email');
		$password = $request->input('password');
		$phone_number = $request->input('phone_number');
		$name = null;

		if (!$phone_number) {
			return response()->json([
				'success' => false,
				'message' => 'Số điện thoại là bắt buộc'
			], 400);
		}

		try {
			$email = $this->credentialsValidatorService->validateAndReturnEmail($email, true);
			$password = $this->credentialsValidatorService->validateAndReturnPassword($password);
		} catch (Exception $e) {
			Log::error("An error occurred in func. handleRegister()", [
				'error' => $e->getMessage(),
				'request_data' => $request->all(), // Optional: log request data
			]);
			return response()->json([
				'success' => false,
				'message' => $e->getMessage()
			], 400);
		}

		// Check if user with this email already exists
		$existingUser = User::where('email', $email)
			->orWhere('phone_number', $phone_number)
			->first();
		if ($existingUser) {
			return response()->json([
				'success' => false,
				'message' => 'Email hoặc số điện thoại đã được đăng ký'
			], 409);
		}

		// Verify if phone number is verified via OTP
		$otp = OTP::where('phone_number', $phone_number)->first();
		if (!$otp || !$otp->verified_at) {
			return response()->json([
				'success' => false,
				'message' => 'Số điện thoại chưa được xác thực'
			], 400);
		}

		// Create user
		$user = null;
		try {
			DB::beginTransaction();
			$cart = Cart::create();
			$user = User::create(attributes: [
				'phone_number' => $phone_number,
				'name' => !empty($name) ? $name : explode('@', $email)[0],
				'email' => $email,
				'password' => $password,
				'role_type' => 0,
				'cart_id' => $cart->cart_id,
			]);
			$otp->delete();
			DB::commit();

			// Generate token for new user
			$deviceName = $request->input('device_name', 'mobile');
			$token = $user->createToken($deviceName)->plainTextToken;

		} catch (Exception $e) {
			DB::rollback();
			if (strpos($e->getMessage(), "1062 Duplicate") !== false) {
				return response()->json([
					'success' => false,
					'message' => 'Email đã được đăng ký'
				], 400);
			}
			Log::error("An error occurred in func. handleRegister()", [
				'error' => $e->getMessage(),
				'request_data' => $request->all(), // Optional: log request data
			]);
			return response()->json([
				'success' => false,
				'message' => 'Có lỗi xảy ra khi đăng ký'
			], 500);
		}

		return response()->json([
			'success' => true,
			'message' => 'Đăng ký tài khoản thành công',
			'user' => $user,
			'token' => $token
		]);
	}

	// Handle logout for token-based auth
	public function handleLogout(Request $request)
	{
		try {
			// Revoke all tokens or just the current token
			if ($request->input('all_devices', false)) {
				// Revoke all tokens
				$request->user()->tokens()->delete();
			} else {
				// Revoke only current token
				$request->user()->currentAccessToken()->delete();
			}

			return response()->json([
				'success' => true,
				'message' => 'Đăng xuất thành công'
			]);
		} catch (Exception $e) {
			Log::error("Logout error: " . $e->getMessage());
			return response()->json([
				'success' => false,
				'message' => 'Có lỗi xảy ra khi đăng xuất'
			], 500);
		}
	}

	public function showConsentScreen($social)
	{
		switch ($social) {
			case 'facebook':
			case 'google':
				// For OAuth with React, we need to return the auth URL instead of redirecting
				$authUrl = Socialite::driver($social)->stateless()->redirect()->getTargetUrl();
				return response()->json([
					'success' => true,
					'auth_url' => $authUrl
				]);
			default:
				return response()->json([
					'success' => false,
					'message' => 'Mạng xã hội này không được hỗ trợ'
				], 400);
		}
	}

	public function handleSocialCallback(Request $request, $social)
	{
		try {
			$socialUser = Socialite::driver($social)->stateless()->user();
			$email = $socialUser->getEmail();

			// Find existing user or create new one
			$user = User::where('email', $email)->first();

			if (!$user) {
				// Create new user
				DB::beginTransaction();

				$name = $socialUser->getName();
				$password = AuthUtils::random_password();
				$emailPrefix = explode('@', $email)[0];
				$randomUsername = AuthUtils::random_name($emailPrefix);

				$cart = Cart::create([
					'items_count' => 0,
				]);

				$user = User::create([
					'email' => $email,
					'name' => $name,
					'password' => $password,
					'role_type' => 0,
					'cart_id' => $cart->cart_id,
				]);

				DB::commit();
			}

			// Generate token for the user
			$deviceName = $request->input('device_name', 'mobile-' . $social);
			$token = $user->createToken($deviceName)->plainTextToken;

			Log::debug('Social login successful for user: ', ['user_id' => $user->user_id]);

			return response()->json([
				'success' => true,
				'message' => 'Đăng nhập thành công',
				'user' => $user,
				'token' => $token
			]);

		} catch (\mysqli_sql_exception $me) {
			DB::rollBack();
			Log::error("MySQL error in social login: " . $me->getMessage());
			return response()->json([
				'success' => false,
				'message' => 'Có lỗi xảy ra khi đăng nhập'
			], 500);
		} catch (Exception $e) {
			Log::error('Social login error: ' . $e->getMessage());
			return response()->json([
				'success' => false,
				'message' => 'Có lỗi xảy ra khi đăng nhập'
			], 500);
		}
	}

	public function handleResetPassword(Request $request)
	{
		$phone_number = $request->input('phone_number');
		$new_password = $request->input('new_password');
		$reset_token = $request->input('reset_token');

		// Check for required inputs
		if (empty($phone_number) || empty($new_password) || empty($reset_token)) {
			return response()->json([
				'success' => false,
				'message' => 'Số điện thoại, mật khẩu mới và mã xác thực là bắt buộc'
			], 400);
		}

		try {
			// Validate password format
			try {
				$new_password = $this->credentialsValidatorService->validateAndReturnPassword(password: $new_password);
			} catch (Exception $validationErr) {
				Log::info("Password validation error (user-error)", [
					'error' => $validationErr,
					'new_password' => $new_password
				]);
				return response()->json([
					'success' => false,
					'message' => $validationErr->getMessage()
				], 400);
			}

			// Verify token in database
			$passwordReset = PasswordResets::where('token', $reset_token)
				->first();
			if (!$passwordReset) {
				Log::debug("Password reset token not found in DB");
				return response()->json([
					'success' => false,
					'message' => 'Mã xác thực không hợp lệ hoặc đã hết hạn'
				], 401);
			}

			// Update user's password
			$user = User::where('phone_number', $phone_number)->first();
			if (!$user) {
				return response()->json([
					'success' => false,
					'message' => 'Không tìm thấy người dùng với số điện thoại này'
				], 404);
			}

			$user->password = $new_password;
			$user->save();

			// Generate a token for automatic login
			$deviceName = $request->input('device_name', 'mobile');
			$token = $user->createToken($deviceName)->plainTextToken;

			// Delete used token
			$passwordReset->delete();

			return response()->json(data: [
				'success' => true,
				'message' => 'Mật khẩu đã được cập nhật thành công',
				'user' => $user,
				'token' => $token
			]);
		} catch (Exception $e) {
			Log::error("Password reset error", [
				'error' => $e->getMessage(),
				'phone_number' => $phone_number
			]);

			return response()->json([
				'success' => false,
				'message' => 'Có lỗi xảy ra khi đặt lại mật khẩu'
			], 500);
		}
	}

	public function showAdminLoginForm()
	{
		if (Auth::check() && Auth::user()->role_type == 1) {
			return redirect()->intended('/admin/dashboard');
		}
		return view('admin.auth.login');
	}

	public function handleAdminLogin(Request $request)
	{
		$request_data = $_POST;
		$password = "";

		try {
			$username = $request_data["username"];
			$password = $this->credentialsValidatorService->validateAndReturnPassword($request_data);

			$conn = $this->dbConnService->getDBConn();
			$sql = "select user_id, full_name, email, password, role_type from users where user_name = ?";
			$pstm = $conn->prepare($sql);
			$pstm->bind_param("s", $username);
			$pstm->execute();
			$result = $pstm->get_result();

			if ($result->num_rows == 0) {
				Log::debug('User not found in database', [
					'email' => $username,
				]);
				throw new Exception("Invalid credentials");
			}

			$row = $result->fetch_assoc();
			Log::debug('User found in database', [
				'user' => $row,
			]);

			// Check if user is admin
			if ($row['role_type'] != 1) {
				throw new Exception("Unauthorized access");
			}

			// Verify password
			if (!password_verify($password, $row['password'])) {
				throw new Exception("Invalid credentials");
			}

			$user = new User();
			$user->user_id = $row['user_id'];
			$user->name = $row['full_name'];
			$user->email = $row['email'];
			$user->password = $row['password'];
			$user->role_type = $row['role_type'];

			Auth::login($user);
			$request->session()->regenerate();

			return redirect()->intended('/admin/dashboard');

		} catch (Exception $e) {
			Log::error("Admin login error", [
				'error' => $e->getMessage(),
				'email' => $username
			]);
			return redirect()->back()->withErrors($e->getMessage());
		}
	}
}
