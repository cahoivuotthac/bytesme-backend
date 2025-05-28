<?php

namespace App\Http\Controllers;

use App\Models\ExpoPushToken;
use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Exception;

class UserController extends Controller
{
	public function updateAddress(Request $request)
	{
		$user = Auth::user();
		try {
			$validatedData = $request->validate([
				'urban_name' => 'required|string|max:255',
				'urban_code' => 'required|numeric',
				'suburb_name' => 'required|string|max:255',
				'suburb_code' => 'required|numeric',
				'quarter_name' => 'nullable|string|max:255',
				'quarter_code' => 'nullable|numeric',
				'full_address' => 'string|required|max:255',
				'user_address_id' => 'required|numeric|exists:user_addresses,user_address_id',
				'is_default_address' => 'boolean',
			]);
		} catch (Exception $e) {
			Log::error("Validation failed: " . $e->getMessage());
			return response()->json([
				'success' => false,
				'message' => 'Validation failed',
				'error' => $e->getMessage()
			], 400);
		}

		$urbanName = $validatedData['urban_name'];
		$urbanCode = $validatedData['urban_code'];
		$suburbName = $validatedData['suburb_name'];
		$suburbCode = $validatedData['suburb_code'];
		$quarterName = $validatedData['quarter_name'] ?? null;
		$quarterCode = $validatedData['quarter_code'] ?? null;
		$fullAddress = $validatedData['full_address'] ?? null;
		$isDefaultAddress = $validatedData['is_default_address'] ?? false;
		$userAddressId = $validatedData['user_address_id'];

		try {
			DB::beginTransaction();
			$userAddress = UserAddress::findOrFail($userAddressId);
			$userAddress->urban_name = $urbanName;
			$userAddress->urban_code = $urbanCode;
			$userAddress->suburb_name = $suburbName;
			$userAddress->suburb_code = $suburbCode;
			$userAddress->quarter_name = $quarterName;
			$userAddress->quarter_code = $quarterCode;
			$userAddress->full_address = $fullAddress;
			$userAddress->is_default_address = $isDefaultAddress;
			$userAddress->save();
			if ($isDefaultAddress) {
				// Set all other addresses to non-default
				UserAddress::where('user_id', $user->user_id)
					->where('user_address_id', '!=', $userAddressId)
					->update(['is_default_address' => false]);
			}

			DB::commit();
		} catch (Exception $e) {
			Log::error("Failed to update user address: " . $e->getMessage());
			return response()->json([
				'success' => false,
				'message' => 'Failed to update user address',
				'error' => $e->getMessage()
			], 500);
		}

		return response()->json(
			[
				'success' => true,
				'message' => 'Address updated successfully',
				'data' => [
					'urbanName' => $urbanName,
					'suburbName' => $suburbName,
					'quarterName' => $quarterName,
					'user' => $user
				]
			]
		);
	}

	public function addAddress(Request $request)
	{
		$user = Auth::user();

		// Validate input
		try {
			$validatedData = $request->validate(
				[
					'urban_name' => 'required|string|max:255',
					'urban_code' => 'required|numeric',
					'suburb_name' => 'required|string|max:255',
					'suburb_code' => 'required|numeric',
					'quarter_name' => 'nullable|string|max:255',
					'quarter_code' => 'nullable|numeric',
					'full_address' => 'string|required|max:255',
					'is_default_address' => 'boolean',
				]
			);
		} catch (Exception $e) {
			Log::error("Validation failed: " . $e->getMessage());
			return response()->json([
				'success' => false,
				'message' => 'Bad input data',
				'error' => $e->getMessage()
			], 400);
		}

		$urbanName = $validatedData['urban_name'];
		$urbanCode = $validatedData['urban_code'];
		$suburbName = $validatedData['suburb_name'];
		$suburbCode = $validatedData['suburb_code'];
		$quarterName = $validatedData['quarter_name'] ?? null;
		$quarterCode = $validatedData['quarter_code'] ?? null;
		$fullAddress = $validatedData['full_address'] ?? null;
		$isDefaultAddress = $request->input('is_default_address', false);
		$userId = $user->user_id;

		$otherAddressesQuery = UserAddress::where('user_id', $userId);

		try {
			DB::beginTransaction();
			if ($isDefaultAddress) {
				// Set all other addresses to non-default
				$otherAddressesQuery->update(['is_default_address' => false]);
			} else if ($otherAddressesQuery->count() === 0) {
				// If no other addresses exist, set this one as default
				$isDefaultAddress = true;
			}
			UserAddress::create([
				'urban_name' => $urbanName,
				'urban_code' => $urbanCode,
				'suburb_name' => $suburbName,
				'suburb_code' => $suburbCode,
				'quarter_name' => $quarterName,
				'quarter_code' => $quarterCode,
				'full_address' => $fullAddress,
				'is_default_address' => $isDefaultAddress,
				'user_id' => $userId
			]);
			DB::commit();

			return response()->json([
				'message' => 'successful',
			]);
		} catch (Exception $e) {
			DB::rollBack();
			Log::error("Failed to add user address: " . $e->getMessage());
			return response()->json([
				'success' => false,
				'message' => 'Failed to add user address',
				'error' => $e->getMessage()
			], 500);
		}
	}

	public function removeAddress(Request $request)
	{
		$user = Auth::user();

		// Validate input
		try {
			$validatedData = $request->validate(
				[
					'user_address_id' => 'required|numeric|exists:user_addresses,user_address_id',
				]
			);
		} catch (Exception $e) {
			Log::error("Validation failed: " . $e->getMessage());
			return response()->json([
				'success' => false,
				'message' => 'Bad input data',
				'error' => $e->getMessage()
			], 400);
		}

		try {
			DB::beginTransaction();
			$userAddress = UserAddress::where('user_address_id', $validatedData['user_address_id'])->first();
			if ($userAddress->is_default_address) {
				// If the address being deleted is the default address, set another address as default
				$nextDefaultAddress = UserAddress::where('user_id', $user->user_id)
					->where('user_address_id', '!=', $validatedData['user_address_id'])
					->first();
				if ($nextDefaultAddress) {
					$nextDefaultAddress->is_default_address = true;
					$nextDefaultAddress->save();
				}
			}
			$userAddress->delete();
			DB::commit();

			return response()->json([
				'success' => true,
				'message' => 'Address removed successfully'
			]);
		} catch (Exception $e) {
			DB::rollBack();
			Log::error("Failed to remove user address: " . $e->getMessage());
			return response()->json([
				'success' => false,
				'message' => 'Failed to remove user address',
				'error' => $e->getMessage()
			], 500);
		}
	}

	public function getUserAddresses(Request $request)
	{
		$userId = Auth::user()->user_id;

		$addresses = UserAddress::where('user_id', $userId)->get();

		return response()->json([
			'addresses' => $addresses,
		]);
	}

	public function updateProfile(Request $request)
	{
		$user = Auth::user();

		// Validate input
		try {
			$validatedData = $request->validate(
				[
					'name' => 'required|string|max:255',
					'email' => 'required|email|max:255',
					'phone_number' => 'required|string|max:11',
				]
			);
		} catch (Exception $e) {
			Log::error("Validation failed: " . $e->getMessage());
			return response()->json([
				'success' => false,
				'message' => 'Bad input data',
				'error' => $e->getMessage()
			], 400);
		}
		$name = $validatedData['name'];
		$email = $validatedData['email'];
		$phoneNumber = $validatedData['phone_number'];

		// Update user profile
		try {
			$user->name = $name;
			$user->email = $email;
			$user->phone_number = $phoneNumber;

			$user->save();
			return response()->json([
				'success' => true,
				'message' => 'Profile updated successfully',
				'user' => $user
			]);
		} catch (Exception $e) {
			Log::error("Failed to update user profile: " . $e->getMessage());
			return response()->json([
				'success' => false,
				'message' => 'Failed to update user profile',
				'error' => $e->getMessage()
			], 500);
		}
	}

	public function updateAvatar(Request $request)
	{
		$user = Auth::user();

		// Validate input
		try {
			$request->validate(
				[
					'avatar' => 'string',
				]
			);
		} catch (Exception $e) {
			Log::error("Validation failed: " . $e->getMessage());
			return response()->json([
				'success' => false,
				'message' => 'Bad input data',
				'error' => $e->getMessage()
			], 400);
		}

		try {
			if ($request->has('avatar')) {
				$avatar = $request->input('avatar');
				$user->avatar = $avatar;
			} else {
				$user->avatar = null; // remove avatar
			}

			$user->save();
			return response()->json([
				'success' => true,
				'message' => 'Avatar updated successfully',
				'user' => $user
			]);
		} catch (Exception $e) {
			Log::error("Failed to update user avatar: " . $e->getMessage());
			return response()->json([
				'success' => false,
				'message' => 'Failed to update user avatar',
				'error' => $e->getMessage()
			], 500);
		}
	}

	public function updatePassword(Request $request)
	{
		$user = Auth::user();

		// Validate input
		try {
			$validatedData = $request->validate(
				[
					'current_password' => 'required|string|min:8',
					'new_password' => 'required|string|min:8',
				]
			);
		} catch (Exception $e) {
			Log::error("Validation failed: " . $e->getMessage());
			return response()->json([
				'success' => false,
				'message' => 'Bad input data',
				'error' => $e->getMessage()
			], 400);
		}

		if (!password_verify($validatedData['current_password'], $user->password)) {
			return response()->json([
				'success' => false,
				'message' => 'Old password is incorrect'
			], 422);
		}

		try {
			$user->password = bcrypt($validatedData['new_password']);
			$user->save();
			return response()->json([
				'success' => true,
				'message' => 'Password updated successfully'
			]);
		} catch (Exception $e) {
			Log::error("Failed to update user password: " . $e->getMessage());
			return response()->json([
				'success' => false,
				'message' => 'Failed to update user password',
				'error' => $e->getMessage()
			], 500);
		}
	}

	public function addPushToken()
	{
		try {
			request()->validate([
				'push_token' => 'required|string|max:255',
			]);
		} catch (Exception $e) {
			Log::error("Validation failed: " . $e->getMessage());
			return response()->json([
				'message' => 'Bad input data: ' . $e->getMessage(),
			], 400);
		}

		$user = Auth::user();
		$expoPushToken = request()->input('push_token');

		if (!$expoPushToken) {
			return response()->json([
				'success' => false,
				'message' => 'Expo push token is required'
			], 400);
		}

		try {
			$existingToken = $user->expo_push_tokens()
				->where('expo_push_token', $expoPushToken)
				->first();
			if ($existingToken) {
				// $existingToken->push_token = $expoPushToken;
				// $existingToken->save();
				Log::debug("Existing push token already exist, no need update" . $expoPushToken);
				return response()->json([
					'message' => 'Expo push token already exists, no update needed'
				]);
			} else {
				ExpoPushToken::create([
					'user_id' => $user->user_id,
					'expo_push_token' => $expoPushToken,
				]);
				Log::debug("Created new expo push token: " . $expoPushToken);
			}

			return response()->json([
				'message' => 'Expo push token updated successfully'
			]);
		} catch (Exception $e) {
			Log::error("Failed to update expo push token: " . $e->getMessage());
			return response()->json([
				'message' => 'Failed to update expo push token',
			], 500);
		}
	}

	public function 
}
