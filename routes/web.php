<?php

use App\Http\Controllers\AddressController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VoucherController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\OTPController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\WishlistController;
use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * @notice Auth routes
 */
Route::prefix('auth')->group(function () {
	Route::post('/signin', [AuthController::class, 'handleSignin']);
	Route::post('/signup', [AuthController::class, 'handleSignup']);
	Route::post('/reset-password', [AuthController::class, 'handleResetPassword']);

	// OTP code - phone number verification
	Route::group(['prefix' => 'otp'], function () {
		Route::post('/gen', [OTPController::class, 'generate']);
		Route::post('/verify', [OTPController::class, 'verify']);
	});

	// Protected auth routes
	Route::middleware('auth:sanctum')->group(function () {
		Route::post('/logout', [AuthController::class, 'handleLogout']);
		Route::get('/user', function (Request $request) {
			return response()->json(data: $request->user());
		});
	});

	// OAuth2 social login
	Route::post('signin/{social}', [AuthController::class, 'showConsentScreen']);
	Route::get('signin/{social}/callback', [AuthController::class, 'handleSocialCallback']);
});

Route::prefix('product')->group(function () {
	Route::get('/homepage-products', [ProductController::class, 'getHomepageProducts']);
	Route::get('/{id}', function ($id) {
		return response()->json(['product_id' => $id]);
	});
});

// Use sanctum auth middleware for user routes
Route::prefix('user')->middleware(['auth:sanctum'])->group(function () {
	Route::group(['prefix' => 'cart'], function () {
		Route::get('/', [UserController::class, 'getCart']);
		Route::post('/add', [UserController::class, 'addToCart']);
		Route::post('/remove', [UserController::class, 'removeFromCart']);
		Route::post('/update', [UserController::class, 'updateCart']);
		Route::post('/checkout', [UserController::class, 'checkout']);
	});

	Route::group(['prefix' => 'wishlist'], function () {
		Route::get('/', [WishlistController::class, 'getWishlist']);
		Route::post('/add', [WishlistController::class, 'addToWishlist']);
		Route::post('/remove', [WishlistController::class, 'removeFromWishlist']);
	});

	Route::group(['prefix' => 'cart'], function () {
		Route::get('/', [CartController::class, 'getCartItems']);
		Route::post('/add', [CartController::class, 'addToCart']);
		Route::post('/remove', [CartController::class, 'removeFromCart']);
		Route::post('/checkout', [CartController::class, 'checkout']);
		Route::post('/update-item-quantity', [CartController::class, 'updateItemQuantity']);
		Route::post('/update-item-size', [CartController::class, 'updateItemSize']);
	});

	Route::group(['prefix' => 'addresses'], function () {
		Route::get('/', [UserController::class, 'getUserAddresses']);
		Route::post('/add', [UserController::class, 'addAddress']);
		Route::post('/remove', [UserController::class, 'removeAddress']);
		Route::post('/set-default', [UserController::class, 'setDefaultAddress']);
	});

	Route::get('/test', function () {
		return response()->json(['message' => 'Test route']);
	});

	Route::post('/update-address', [UserController::class, 'updateAddress']);
});

Route::prefix('order')->middleware(['auth:sanctum'])->group(function () {
	Route::post('/place', [OrderController::class, 'placeOrder']);
	Route::get('/details', [OrderController::class, 'getOrderDetails']);
	Route::get('/', [OrderController::class, 'getOrders']);
	Route::post('/update-status', [OrderController::class, 'updateOrderStatus']);

	// Notification routes
	Route::prefix('notifications')->group(function () {
		// Get all notifications for the authenticated user
		Route::get('/', [NotificationController::class, 'getNotifications']);

		// Get a specific notification by ID
		Route::get('/{id}', [NotificationController::class, 'getNotificationById']);

		// Mark notifications as read
		Route::post('/mark-as-read', [NotificationController::class, 'markAsRead']);

		// Delete notifications
		Route::delete('/', [NotificationController::class, 'deleteNotifications']);
	});
});

Route::prefix('info')->group(function () {
	Route::prefix('address')->group(function (): void {
		route::get('/urban-list', function () {
			// Read the JSON file from the public directory
			$jsonFilePath = public_path('constants/vietnam-address/tinh-tp.json');

			// Check if the file exists
			if (!file_exists($jsonFilePath)) {
				return response()->json([
					'success' => false,
					'message' => 'Province & City list not found'
				], 404);
			}

			// Read and decode the JSON file
			$jsonData = file_get_contents($jsonFilePath);
			$data = json_decode($jsonData, associative: true);

			// Return the data as JSON response
			return response()->json($data);
		});
		Route::get('/suburb-list', function (Request $request) {
			$urbanCode = $request->query('urbanCode');

			if (!$urbanCode) {
				return response()->json([
					'success' => false,
					'message' => 'Province ID is required'
				], 400);
			}

			// Read the JSON file from the public directory
			$jsonFilePath = public_path("constants/vietnam-address/quan-huyen/{$urbanCode}.json");

			// Check if the file exists
			if (!file_exists($jsonFilePath)) {
				return response()->json([
					'success' => false,
					'message' => 'District list not found'
				], 404);
			}

			// Read and decode the JSON file
			$jsonData = file_get_contents($jsonFilePath);
			$data = json_decode($jsonData, associative: true);

			// Return the data as JSON response
			return response()->json($data);
		});

		Route::get('/quarter-list', function (Request $request) {
			$suburbCode = $request->query('suburbCode');

			if (!$suburbCode) {
				return response()->json([
					'success' => false,
					'message' => 'District ID is required'
				], 400);
			}

			// Read the JSON file from the public directory
			$jsonFilePath = public_path('constants/vietnam-address/xa-phuong/' . $suburbCode . '.json');

			// Check if the file exists
			if (!file_exists($jsonFilePath)) {
				return response()->json([
					'success' => false,
					'message' => 'Ward list not found'
				], 404);
			}

			// Read and decode the JSON file
			$jsonData = file_get_contents($jsonFilePath);
			$data = json_decode($jsonData, associative: true);

			// Return the data as JSON response
			return response()->json($data);

		});
		Route::get('/reverse-geocode', [AddressController::class, "reverse_geocode"]);
	});
});

Route::prefix('voucher')->middleware(['auth:sanctum'])->group(function () {
	Route::get('/', [VoucherController::class, 'getVouchers']);
	Route::get('/gift-products', [VoucherController::class, 'getVoucherGiftProducts']);
	// Route::post('/apply', action: [UserController::class, 'applyVoucher']);
	// Route::post('/remove', [UserController::class, 'removeVoucher']);
});

