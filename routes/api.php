<?php

// filepath: /home/minhduc/Coding/pie-backend/routes/api.php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\OTPController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
	return $request->user();
});

// Authentication routes
// Route::post('/login', [AuthController::class, 'handleLogin']);
// Route::post('/register', [AuthController::class, 'handleRegister']);
// Route::post('/logout', [AuthController::class, 'handleLogout']);

// // OTP verification routes
// Route::post('/otp/generate', [OTPController::class, 'generate']);
// Route::post('/otp/verify', [OTPController::class, 'verify']);

// Order status routes (requires authentication)
// Route::middleware('auth:sanctum')->group(function () {
//     // Get order status
//     Route::get('/orders/{orderId}/status', [OrderController::class, 'getOrderStatus']);
    
//     // Update order status (admin only or owner)
//     Route::put('/orders/{orderId}/status', [OrderController::class, 'updateOrderStatus']);
    
//     // Notification routes
//     Route::prefix('notifications')->group(function () {
//         // Get all notifications for the authenticated user
//         Route::get('/', [NotificationController::class, 'getNotifications']);
        
//         // Get a specific notification by ID
//         Route::get('/{id}', [NotificationController::class, 'getNotificationById']);
        
//         // Mark notifications as read
//         Route::post('/mark-as-read', [NotificationController::class, 'markAsRead']);
        
//         // Delete notifications
//         Route::delete('/', [NotificationController::class, 'deleteNotifications']);
//     });
// });