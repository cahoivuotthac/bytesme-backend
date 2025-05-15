<?php

namespace App\Http\Controllers;

use App\Models\UserAddresses;
use Exception;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Log;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;

class UserController extends Controller
{
	public function updateAddress(Request $request)
	{
		try {
			$request->validate([
				'urbanName' => 'required|string|max:255',
				'suburbName' => 'required|string|max:255',
				'quarterName' => 'required|string|max:255',
			]);
		} catch (Exception $e) {
			Log::error("Validation failed: " . $e->getMessage());
			return response()->json([
				'success' => false,
				'message' => 'Validation failed',
				'error' => $e->getMessage()
			], 422);
		}

		$urbanName = $request->input('urbanName');
		$suburbName = $request->input('suburbName');
		$quarterName = $request->input('quarterName');

		// Call the address_items_from_slug method to get the address items
		$user = User::find($request->user()->user_id);

		if (!$user) {
			Log::error("User not found: " . $request->user()->id);
			return response()->json(['error' => 'User not found'], 400);
		}

		try {
			$user->urban = $urbanName;
			$user->suburb = $suburbName;
			$user->quarter = $quarterName;
			$user->save();
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
		// Validate input
		try {
			$validatedData = $request->validate(
				[
					'urban_name' => 'required|string|max:255',
					'suburb_name' => 'required|string|max:255',
					'quarter_name' => 'nullable|string|max:255',
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
		$suburbName = $validatedData['suburb_name'];
		$quarterName = $validatedData['quarter_name'] ?? null;
		$fullAddress = $validatedData['full_address'] ?? null;
		$isDefaultAddress = $validatedData['is_default_address'] ?? false;
		$userId = Auth::user()->user_id;

		UserAddresses::create([
			'urban_name' => $urbanName,
			'suburb_name' => $suburbName,
			'quarter_name' => $quarterName,
			'full_address' => $fullAddress,
			'is_default_address' => $isDefaultAddress,
			'user_id' => $userId
		]);

		return response()->json([
			'message' => 'successful',
		]);
	}

	public function getUserAddresses(Request $request)
	{
		$userId = Auth::user()->user_id;

		$addresses = UserAddresses::where('user_id', $userId)->get();

		return response()->json([
			'addresses' => $addresses,
		]);
	}
}
