<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use \App\Models\Wishlist;
use Illuminate\Support\Facades\Log;

class WishlistController extends Controller
{
	public function addToWishlist(Request $request)
	{
		try {
			$user = $request->user();
			$productId = $request->input('product_id');


			Log::info("Request input: ", ['data' => $request->input()]);

			if (!$user) {
				return response()->json(['message' => 'Unauthorized'], 401);
			}

			if (!$productId) {
				return response()->json(['error' => 'Product ID is required'], 400);
			}

			$item = Wishlist::where([
				'user_id' => $user->user_id,
				'product_id' => $productId,
			])->first();

			if ($item) {
				return response()->json(['message' => 'Product already in wishlist'], 409);
			} else {
				$item = new Wishlist();
				$item->user_id = $user->user_id;
				$item->product_id = $productId;
				$item->save();
				return response()->json([
					'message' => 'Product added to wishlist successfully',
					'item' => $item,
				], 201);
			}

		} catch (\Exception $e) {
			Log::error('Error adding to wishlist: ' . $e->getMessage());
			return response()->json(['message' => 'An error occurred while adding to wishlist'], 500);
		}
	}

	public function removeFromWishlist(Request $request)
	{
		try {
			$user = $request->user();
			$productId = $request->input('product_id');

			if (!$user) {
				return response()->json(['message' => 'Unauthorized'], 401);
			}

			if (!$productId) {
				return response()->json(['error' => 'Product ID is required'], 400);
			}

			$item = Wishlist::where([
				'user_id' => $user->user_id,
				'product_id' => $productId,
			])->first();

			if ($item) {
				$item->delete();
				return response()->json(['message' => 'Product removed from wishlist successfully']);
			} else {
				return response()->json(['message' => 'Product not found in wishlist'], 400);
			}

		} catch (\Exception $e) {
			Log::error('Error removing from wishlist: ' . $e->getMessage());
			return response()->json(['message' => 'An error occurred while removing from wishlist'], 500);
		}
	}

	public function getWishlist(Request $request)
	{
		try {
			$user = $request->user();

			if (!$user) {
				return response()->json(['message' => 'Unauthorized'], 401);
			}

			// Join with products table to get product details
			$wishlistItems = Wishlist::where('user_id', $user->user_id)
				->with([
					'product' => function ($query) {
						$query->with('product_images'); // Include product images
					}
				])
				->get();

			return response()->json([
				'success' => true,
				'wishlist' => $wishlistItems
			]);

		} catch (\Exception $e) {
			Log::error('Error retrieving wishlist: ' . $e->getMessage());
			return response()->json([
				'success' => false,
				'message' => 'An error occurred while retrieving wishlist'
			], 500);
		}
	}

}
