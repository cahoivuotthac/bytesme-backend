<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Log;

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
		} catch (\Exception $e) {
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
		} catch (\Exception $e) {
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
}
