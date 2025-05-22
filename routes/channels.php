<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\Order;
use App\Models\User;


Broadcast::channel('App.Models.User.{userId}', function (User $user, $userId) {
	// return (int) $user->user_id === (int) $userId;
	return true;
});

Broadcast::channel("orders-status", function () {
	return true;
});

Broadcast::channel('online-payment', function() {
	return true;
});

// Channel for receiving updates about a specific user's orders
Broadcast::channel('order.{userId}', function (User $user, $userId) {
	return (int) $user->user_id === (int) $userId;
});

// Channel for receiving updates about a specific order
// Users can only subscribe to their own orders, admins can subscribe to any order
Broadcast::channel('order.{orderId}', function (User $user, $orderId) {
	// Admin can access any order
	if ($user->role_type === 1) {
		return true;
	}

	// Regular user can only access their own orders
	$order = Order::where('order_id', $orderId)->first();
	return $order && (int) $user->user_id === (int) $order->user_id;
}, ['guards' => ['sanctum']]);

Broadcast::channel('test-channel', function () {
	// Always return true - anyone can subscribe
	return true;
});