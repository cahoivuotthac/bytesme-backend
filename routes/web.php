<?php

// Controllers
use App\Http\Controllers\AddressController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\MomoPaymentController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VoucherController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AdminOrderController;
use App\Http\Controllers\AdminProductController;
use App\Http\Controllers\AdminVoucherController;
use App\Http\Controllers\AdminNotificationController;
use App\Http\Controllers\AdminUserController;

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
	Route::post('/signin/{social}', [AuthController::class, 'getSocialLoginUrl']);
	Route::post('/signin/{social}/callback', [AuthController::class, 'handleSocialCallback']);
});

Route::prefix('user')->middleware('auth:sanctum')->group(function () {
	Route::put('/profile', [UserController::class, 'updateProfile']);
	Route::post('/update-password', [UserController::class, 'updatePassword']);
	Route::post('/update-avatar', [UserController::class, 'updateAvatar']);
});

Route::prefix('product')->middleware('auth:sanctum')->group(function () {
	Route::get('/homepage', [ProductController::class, 'getHomepageProducts']);
	Route::get('/category', [ProductController::class, 'getProductsByCategory']);
	Route::get('/details', [ProductController::class, 'getProductDetails']);
	// Test get products feedback
	Route::get('/feedbacks', [ProductController::class, 'getProductFeedbacks']);
	Route::get('/search/text', [ProductController::class, 'searchProductsText']); // Normal sql text search (fall-back route)
	Route::get('/search/semantics', [ProductController::class, 'searchProductsSemantics']);
	Route::get('/search/rag', [ProductController::class, 'searchProductsRag']);
	Route::get('/related/co-occur', [ProductController::class, 'getRelatedProductsCoOccur']); // Co-occurence based on orders
	Route::get('/related/semantics', [ProductController::class, 'getRelatedProductsSemantics']);
	Route::get('/all-categories', [AdminProductController::class, 'getAllCategories']);
});

// Use sanctum auth middleware for user routes
Route::prefix('user')->middleware(['auth:sanctum'])->group(function () {
	Route::group(['prefix' => 'cart'], function () {
		Route::get('/', [UserController::class, 'getCart']);
		Route::post('/add', [UserController::class, 'addToCart']);
		Route::post('/remove', [UserController::class, 'removeFromCart']);
		Route::post('/update', [UserController::class, 'updateCart']);
		Route::post('/checkout', [UserController::class, 'checkout']);
		Route::post('/replicate-order', [CartController::class, 'replicateOrder']);
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
		Route::post('/update', [UserController::class, 'updateAddress']);
		Route::post('/remove', [UserController::class, 'removeAddress']);
		Route::post('/set-default', [UserController::class, 'setDefaultAddress']);
	});
	Route::post('/update-address', [UserController::class, 'updateAddress']);

	Route::prefix('notification')->group(function () {
		Route::get('/', [NotificationController::class, 'getNotifications']);
		Route::post('/mark-as-read', [NotificationController::class, 'markAsRead']);
		Route::post('/mark-all-as-read', [NotificationController::class, 'markAllAsRead']);
		Route::post('/delete', [NotificationController::class, 'deleteNotifications']);
		// Route::get('/{id}', [NotificationController::class, 'getNotificationById']);
		Route::post('/add-push-token', [UserController::class, 'addPushToken']);
	});

	Route::get('/test', function () {
		return response()->json(['message' => 'Test route']);
	});
});

Route::prefix('order')->middleware(['auth:sanctum'])->group(function () {
	Route::get('/', [OrderController::class, 'getOrders']);
	Route::get('/history', [OrderController::class, 'getOrderHistory']);
	Route::post('/place', [OrderController::class, 'placeOrder']);
	Route::post('/cancel', [OrderController::class, 'cancelOrder']);
	Route::post('/feedback', [FeedbackController::class, 'sendFeedback']);
	Route::get('/details', [OrderController::class, 'getOrderDetails']);
	Route::get('/status', [OrderController::class, 'getOrderStatus']);
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

	Route::prefix('payment')->group(function () {
		Route::prefix('momo')->group(function () {
			Route::post('/create-intent', [MomoPaymentController::class, 'createPaymentIntent'])->withoutMiddleware(['auth:sanctum']);
			// Exclude auth:sanctum for these two routes
			Route::post('/ipn-callback', [MomoPaymentController::class, 'handleIpnCallback'])->withoutMiddleware(['auth:sanctum']);
			Route::get('/redirect-callback', [MomoPaymentController::class, 'handleRedirectCallback']);
		});
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
	Route::get('/is-applicable', [VoucherController::class, 'isVoucherApplicablePublic']);
});


Route::middleware('auth:sanctum')->post('/test-broadcast', function (Request $request) {
	$user = $request->user();
	broadcast(new \App\Events\OrderStatusEvent('Test message at ' . now(), $user->user_id))->toOthers();

	return response()->json([
		'success' => true,
		'message' => 'Test broadcast sent',
		'user_id' => $user->user_id
	]);
});

Route::get('/test-broadcast-public', function () {
	$data = ['message' => 'This is a test broadcast at ' . now(), 'time' => now()->toIso8601String()];

	// Log that we're attempting to broadcast
	Log::info('Attempting to broadcast to public channel', $data);

	// Broadcast to public channel
	broadcast(new \App\Events\OrderStatusEvent($data['message'], 'public-test'));

	return response()->json([
		'success' => true,
		'message' => 'Test broadcast sent to public channel',
		'data' => $data
	]);
});

Route::middleware('auth:sanctum')->post('/test-broadcast-private', function (Request $request) {
	$user = Auth::user();
	$message = "Private test for user {$user->user_id} at " . now();

	Log::info("Attempting private broadcast for user {$user->user_id}");

	// Note: we're NOT using toOthers() here to make testing easier
	broadcast(new \App\Events\OrderStatusEvent($message, $user->user_id));

	return response()->json([
		'success' => true,
		'message' => 'Private test broadcast sent',
		'user_id' => $user->user_id,
		'channel' => "App.Models.User.{$user->user_id}"
	]);
});

// Admin routes

/**
 * @notice Admin routes
 */
Route::prefix('admin')->name('admin.')->group(function () {
	// Public admin routes
	Route::get('/', function () {
		if (!Auth::check()) {
			return redirect()->route('admin.auth.showLoginForm');
		}
		return Auth::user()->role_type === 1
			? redirect()->route('admin.dashboard.showDashboardPage')
			: redirect()->back()->with('error', 'Bạn không có quyền truy cập trang này');
	})->name('index');

	Route::prefix('auth')->name('auth.')->group(function () {
		Route::get('/login', [AuthController::class, 'showAdminLoginForm'])->name('showLoginForm');
		Route::post('/login', [AuthController::class, 'handleAdminLogin'])->name('handleLogin');
	});

	// Admin protected routes
	Route::middleware(['auth:sanctum'])->group(function () {
		// Admin orders route
		// add back admin protection middleware later
		Route::prefix('orders')->name('orders.')->group(function () {
			Route::get('/', [AdminOrderController::class, 'showOrdersPage'])->name('showOrdersPage');
			Route::get('/{order_id}/details', [AdminOrderController::class, 'getOrderDetails'])->name('getOrdersDetails');
			Route::get('/edit', [AdminOrderController::class, 'edit'])->name('edit');
			// Route::get('/orders', [AdminController::class, 'index'])->name('.management');
			Route::post('/update-field', [AdminOrderController::class, 'updateOrderField'])
				->name('updateField');
			Route::get('/statistics', [AdminOrderController::class, 'getStatistics'])->name('statistics');
		});

		// Admin Dashboard route
		Route::prefix('dashboard')->name('dashboard.')->group(function () {
			Route::get('/', [AdminDashboardController::class, 'showDashboardPage'])->name('showDashboardPage');
			Route::get('/sales-data', [AdminDashboardController::class, 'getSalesData'])->name('getSalesData');
			Route::get('/analyze/{metric}', [AdminDashboardController::class, 'analyzeMetric']);
			Route::get('/top-selling', [AdminDashboardController::class, 'topSellingProducts'])->name('topSelling');

			// New chart data routes
			Route::get('/sales-trend', [AdminOrderController::class, 'getSalesTrendData'])->name('salesTrendData');
			Route::get('/order-status', [AdminOrderController::class, 'getOrderStatusData'])->name('orderStatusData');
			Route::get('/popular-hours', [AdminOrderController::class, 'getPopularHoursData'])->name('popularHoursData');
			Route::get('/category-performance', [AdminOrderController::class, 'getCategoryPerformanceData'])->name('categoryPerformanceData');
			Route::get('/metrics', [AdminOrderController::class, 'getDashboardMetrics'])->name('metrics');
			Route::get('/top-products', [AdminOrderController::class, 'getTopProducts'])->name('topProducts');
		});

		// Admin products management routes
		Route::prefix('products')->name('products.')->group(function () {
			Route::get('/', [AdminProductController::class, 'showProductsPage'])->name('index');
			Route::get('/{product_id}/details', [AdminProductController::class, 'getDetails'])->name('details');
			Route::post('/{product_id}/update-field', [AdminProductController::class, 'updateField'])->name('updateField');
			Route::put('/{product_id}/update', [AdminProductController::class, 'update'])->name('update');
			Route::put('/', [AdminProductController::class, 'createProduct'])->name('createProduct');
			Route::get('/analytics/data', [AdminProductController::class, 'getProductAnalytics'])->name('analytics');
		});

		// Admin vouchers routes
		Route::prefix('vouchers')->name('vouchers.')->group(function () {
			Route::get('/', [AdminVoucherController::class, 'showVouchersPage'])->name('showVouchersPage');
			Route::post('/store', [AdminVoucherController::class, 'store'])->name('store');
			Route::get('/{voucher_id}/details', [AdminVoucherController::class, 'getDetails'])->name('details');
			Route::post('/{voucher_id}/update', [AdminVoucherController::class, 'update'])->name('update');
			Route::post('/{voucher_id}/delete', [AdminVoucherController::class, 'delete'])->name('delete');
		});

		// Admin notification routes
		Route::prefix('notifications')->name('notifications.')->group(function () {
			Route::get('/', [AdminNotificationController::class, 'getNotifications'])
				->name('getNotifications');
			Route::post('/{notification_id}/mark-as-read', [AdminNotificationController::class, 'markNotifcationAsRead'])
				->name('markAsRead');
		});

		// Admin users management routes
		Route::prefix('users')->name('users.')->group(function () {
			Route::get('/', [AdminUserController::class, 'showUsersPage'])->name('index');
			Route::get('/analytics/data', [AdminUserController::class, 'getUserAnalytics'])->name('analytics');
			Route::get('/{user_id}/details', [AdminUserController::class, 'getUserDetails'])->name('details');
			Route::post('/{user_id}/update-status', [AdminUserController::class, 'updateUserStatus'])->name('updateStatus');
		});
	});
});