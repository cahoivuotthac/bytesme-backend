<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Cookie;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\PasswordResets;
use App\Models\OTP;
use Illuminate\Support\Facades\Log;
use Str;
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
				'phone_number' => 'required|string|min:10|max:15',
				'is_password_reset' => 'nullable|string',
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

		$phone_number = $validatedData['phone_number'];
		$is_password_reset = $validatedData['is_password_reset'] ?? "false";

		try {
			// Ensure caller don't exceed rate limit
			$otp = OTP::where('phone_number', $phone_number)->first();
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

			// In case of password reset request, check if user with phone number exists
			if ($is_password_reset === 'true') {
				// Check if the user exists
				Log::debug("Generating OTP for password reset, phone number: " . $phone_number);
				if (User::where('phone_number', $phone_number)->count() < 1)
					return response()->json([
						'success' => false,
						'message' => 'Số điện thoại này chưa liên kết với tài khoản nào'
					], 400);
			}

			// Generate a random 4-digit OTP
			$otp_code = mt_rand(1000, 9999);

			// Store or update OTP in database
			OTP::updateOrCreate(
				['phone_number' => $phone_number], // Search criteria
				['code' => $otp_code] // Values to update or create with
			);


			// Log OTP generation (remove in production or use proper logging)
			Log::info("OTP generated", [
				'phone_number' => $phone_number,
				'otp' => $otp_code,
			]);

			// Here you would typically send the OTP via SMS
			// For development, we'll just return it in the response
			// In production, you should integrate with an SMS provider

			return response()->json([
				'success' => true,
				'message' => 'Mã OTP đã được gửi đến số điện thoại của bạn',
				'debug_otp' => $otp_code, // Remove in production
			]);

		} catch (Exception $e) {
			Log::error("Failed to generate OTP", [
				'error' => $e->getMessage(),
				'phone_number' => $phone_number
			]);

			return response()->json([
				'success' => false,
				'message' => 'Không thể tạo mã OTP. Vui lòng thử lại sau.'
			], 500);
		}
	}

	/**
	 * Verify the OTP code for a phone number
	 * 
	 * @param Request $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function verify(Request $request): \Illuminate\Http\JsonResponse
	{
		// Validate input
		$validatedData = $request->validate([
			'phone_number' => 'required|string|min:9|max:10',
			'code' => 'required|numeric|digits:4',
			'is_password_reset' => 'nullable|string',
			'device_name' => 'nullable|string',
		]);

		$phone_number = $validatedData['phone_number'];
		$code = $validatedData['code'];
		$is_password_reset = $validatedData['is_password_reset'] ?? "false";
		$deviceName = $validatedData['device_name'] ?? "mobile";

		try {
			// Find OTP record
			$otp = OTP::where('phone_number', $phone_number)->first();

			if (!$otp) {
				return response()->json([
					'success' => false,
					'message' => 'Không tìm thấy mã OTP cho số điện thoại này'
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

			// Mark user's phone as verified if they exist
			$otp->verified_at = Carbon::now();

			$otp->save();

			// If it's a password reset, send a password reset token
			if ($is_password_reset === 'true') {
				// Here you would typically send a password reset token
				$user = User::where('phone_number', $phone_number)->first();

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
			$user = User::where('phone_number', operator: $phone_number)->first();
			if ($user) {
				Log::info("User exists, logging in", [
					'user_id' => $user->user_id,
					'phone_number' => $phone_number
				]);

				// Auth::login($user, remember: true);
				$token = $user->createToken($deviceName)->plainTextToken;
				return response()->json([
					'success' => true,
					'message' => 'Đăng nhập bằng số điện thoại thành công',
					'user' => $user,
					'token' => $token,
				]);
			}

			return response()->json(data: [
				'success' => true,
				'message' => 'Xác thực số điện thoại thành công'
			]);

		} catch (Exception $e) {
			Log::error("Failed to verify OTP", [
				'error' => $e->getMessage(),
				'phone_number' => $phone_number
			]);

			return response()->json([
				'success' => false,
				'message' => 'Có lỗi xảy ra khi xác thực OTP'
			], 500);
		}
	}
}
