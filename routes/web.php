<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\OTPController;

Route::get('/', function () {
	return view('welcome');
});


/**
 * @notice Auth routes
 */
Route::get('/auth', [AuthController::class, 'showLoginForm'])->name('auth.index');
Route::get('/auth/login', [AuthController::class, 'showLoginForm'])->name('auth.showLoginForm');
Route::post('/auth/login', [AuthController::class, 'handleLogin'])->name('auth.login');

// Logout
Route::post('/auth/logout', [AuthController::class, 'handleLogout'])->name('auth.logout');

// Registe
Route::get('/auth/register', action: [AuthController::class, 'showRegistrationForm'])->name('auth.showRegisterForm');
Route::post('/auth/register', [AuthController::class, 'handleRegister'])->name('auth.register');

// OAuth2 social login
Route::post('/auth/login/{social}', [AuthController::class, 'showConsentScreen']);
Route::get('/auth/login/{social}/callback', [AuthController::class, 'handleSocialCallback']);

Route::get('/get-csrf-token', function () {
	return response()->json(['token' => csrf_token()]);
});

Route::post('/auth/otp/gen', [OTPController::class, 'generate']);
Route::post('/auth/otp/verify', [OTPController::class, 'verify']);