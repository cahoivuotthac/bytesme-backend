<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\OTP;
use Illuminate\Support\Facades\Log;
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
		$validatedData = $request->validate([
			'phone_number' => 'required|string|min:10|max:15',
		]);

		$phone_number = $validatedData['phone_number'];

		try {
			// Check if OTP is expired (10 minutes validity)
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
		]);

		$phone_number = $validatedData['phone_number'];
		$code = $validatedData['code'];

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

			return response()->json([
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
