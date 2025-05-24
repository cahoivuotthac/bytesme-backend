<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\PasswordResets;
use App\Models\OTP;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Notification;
// use Auth;
use Exception;

class OTPController extends Controller
{
	/**
	 * Generate and store a 4-digit OTP for phone verification
	 * 
	 * @param Request $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function generate(Request $request): \Illuminate\Http\JsonResponse
	{
		// Validate input
		try {
			$validatedData = $request->validate([
				'email' => 'required|string|min:1|max:255',
				'is_password_reset' => 'nullable|boolean',
			]);
		} catch (Exception $e) {
			Log::error("Input validation error", [
				'error' => $e->getMessage(),
				'input' => $request->all()
			]);

			return response()->json([
				'success' => false,
				'message' => 'Invalid input'
			], 400);
		}

		$email = $validatedData['email'];
		$is_password_reset = $validatedData['is_password_reset'] ?? false;

		// Fail with 409 if user already exists whilte not a reset-password intent (meaning they already verified)
		if (
			$is_password_reset === false &&
			User::where('email', $email)->exists()
		) {
			return response()->json([
				'success' => false,
				'message' => 'Email đã được đăng ký'
			], 409);
		}

		try {
			// Ensure caller don't exceed rate limit
			$otp = OTP::where('email', $email)->first();
			if ($otp) {
				$createdTime = new Carbon($otp->updated_at);
				$diffInSeconds = abs(Carbon::now()->diffInSeconds($createdTime));
				if ($diffInSeconds < 30) {
					return response()->json([
						'success' => false,
						'message' => 'Rate limit exceeded. Please wait ' . (30 - $diffInSeconds) . ' seconds before requesting a new OTP.'
					], 429);
				}
			}

			// In case of password reset request, check if user with email exists
			if ($is_password_reset === true) {
				// Check if the user exists
				Log::debug("Generating OTP for password reset, email: " . $email);
				if (User::where('email', $email)->count() < 1)
					return response()->json([
						'success' => false,
						'message' => 'email not linked to any account'
					], 400);
			}

			// Generate a random 4-digit OTP
			$otp_code = mt_rand(1000, 9999);

			// Store or update OTP in database
			OTP::updateOrCreate(
				['email' => $email], // Search criteria
				['code' => $otp_code] // Values to update or create with
			);

			// Log OTP generation (remove in production or use proper logging)
			Log::info("OTP generated", [
				'email' => $email,
				'otp' => $otp_code,
			]);

			/// Send OTP via email channel
			Notification::route('mail', $email)
				->notify(new \App\Notifications\OTPNotification($otp_code));

			return response()->json([
				'success' => true,
				'message' => 'Mã OTP đã được gửi đến địa chỉ email của bạn',
				'debug_otp' => $otp_code, // Remove in production
			]);
		} catch (Exception $e) {
			Log::error("Failed to generate OTP", [
				'error' => $e->getMessage(),
				'email' => $email
			]);

			return response()->json([
				'success' => false,
				'message' => 'Không thể tạo mã OTP. Vui lòng thử lại sau.'
			], 500);
		}
	}

	/**
	 * Verify the OTP code for email
	 * 
	 * @param Request $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function verify(Request $request): \Illuminate\Http\JsonResponse
	{
		// Validate input
		$validatedData = $request->validate([
			'email' => 'required|string|min:1|max:255',
			'code' => 'required|numeric|digits:4',
			'is_password_reset' => 'nullable|boolean',
			'device_name' => 'nullable|string',
		]);

		$email = $validatedData['email'];
		$code = $validatedData['code'];
		$is_password_reset = $validatedData['is_password_reset'] ?? false;

		try {
			// Find OTP record
			$otp = OTP::where('email', $email)->first();

			if (!$otp) {
				return response()->json([
					'success' => false,
					'message' => 'Không tìm thấy mã OTP cho email này'
				], 404);
			}

			// Check if OTP is expired (10 minutes validity)
			$createdTime = new Carbon($otp->updated_at);
			if (Carbon::now()->diffInMinutes($createdTime) > 10) {
				return response()->json([
					'success' => false,
					'message' => 'Mã OTP đã hết hạn. Vui lòng yêu cầu mã mới.'
				], 400);
			}

			// Verify OTP
			if ((string) $otp->code !== (string) $code) {
				return response()->json([
					'success' => false,
					'message' => 'Mã OTP không chính xác'
				], 400);
			}

			// Mark user's email as verified if they exist
			$otp->verified_at = Carbon::now();
			$otp->save();

			// If it's a password reset scenario, send a password reset token
			if ($is_password_reset === true) {
				// Here you would typically send a password reset token
				$user = User::where('email', $email)->first();

				if (!$user) {
					return response()->json([
						'success' => false,
						'message' => 'Không tìm thấy tài khoản với số điện thoại này'
					], 400);
				}

				// Generate password reset token
				$token = Str::random(length: 60);
				$passwordReset = PasswordResets::create([
					'token' => $token,
				]);
				$passwordReset->save();

				Log::info("Password reset token generated", [
					'token' => $token
				]);

				return response()->json([
					'success' => true,
					'message' => 'Mã OTP xác thực thành công. Bạn có thể đặt lại mật khẩu.',
					'reset_token' => $token,
				]);
			}

			// Log user in if user exists
			// $user = User::where('email', '=', $email)->first();
			// if ($user) {
			// 	Log::info("User exists, logging in", [
			// 		'user_id' => $user->user_id,
			// 		'email' => $email
			// 	]);

			// 	// Auth::login($user, remember: true);
			// 	$token = $user->createToken($deviceName)->plainTextToken;
			// 	return response()->json([
			// 		'success' => true,
			// 		'message' => 'Đăng nhập thành công',
			// 		'user' => $user,
			// 		'token' => $token,
			// 	]);
			// }

			return response()->json(data: [
				'success' => true,
				'message' => 'Xác thực thành công'
			]);
		} catch (Exception $e) {
			Log::error("Could not verify OTP", [
				'error' => $e->getMessage(),
				'email' => $email
			]);

			return response()->json([
				'success' => false,
				'message' => 'Có lỗi xảy ra khi xác thực OTP'
			], 500);
		}
	}
}
