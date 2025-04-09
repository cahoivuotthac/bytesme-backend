<?php

// filepath: /home/minhduc/Coding/pie-backend/routes/api.php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
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