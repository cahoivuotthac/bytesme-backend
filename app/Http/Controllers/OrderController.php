<?php

namespace App\Http\Controllers;

use Exception;
use App\Constants;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Voucher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\VoucherController;

class OrderController extends Controller
{
	public function placeOrder(Request $request)
	{
		$user = Auth::user()->with([
			'user_addresses' => function ($query) {
				$query->select('user_id', 'user_address_id', 'full_address');
			}
		])->find(Auth::id());

		// Validate the request data
		try {
			$validatedInput = $request->validate([
				'voucher_code' => 'string',
				'payment_method_id' => 'required|string|max:255',
				'user_address_id' => 'required|integer',
				'order_additional_note' => 'nullable|string|max:255',
				'selected_item_ids' => 'string|required',
			]);

			if (!in_array($validatedInput['payment_method_id'], Constants::ACCEPTED_PAYMENT_METHODS)) {
				throw new Exception('Invalid payment method');
			}
		} catch (Exception $e) {
			Log::error('Input validation error: ' . $e->getMessage());
			return response()->json(['message' => $e->getMessage()], 422);
		}

		// Select validated input
		$paymentMethodId = $validatedInput['payment_method_id'];
		$orderDeliverCost = 20000; // hard-code for now
		$userAddressId = $validatedInput['user_address_id'];
		$additionalNote = $validatedInput['order_additional_note'] ?? null;
		$voucherCode = $validatedInput['voucher_code'] ?? null;

		// Get cart items
		$cart = Auth::user()->cart()->with([
			'items.product' => function ($query) {
				$query->select('product_id', 'category_id', 'product_discount_percentage');
			}
		])->first();
		$selectedItemIds = array_map('intval', explode(',', $validatedInput['selected_item_ids']));
		$cartItems = [];
		if ($cart) {
			$cartItems = $cart->items->whereIn('product_id', $selectedItemIds)->values();
		}

		// Find delivery address
		$deliverAddress = $user->user_addresses->where('user_address_id', $userAddressId)->first()['full_address'];
		if (!$deliverAddress) {
			return response()->json(['message' => 'Cannot find delivery address'], 422);
		}
		Log::debug('Deliver address:', [$deliverAddress]);

		// Calculate order subtotal
		$subtotal = 0;
		foreach ($cartItems as $item) {
			$unitPrice = $item->discounted_unit_price ?? $item->cart_items_unitprice;
			$itemCost = $unitPrice * $item->cart_items_quantity;
			$subtotal += $itemCost;
		}

		// Build order and order items
		$order = new Order(
			[
				'user_id' => $user->user_id,
				'voucher_code' => $voucherCode,
				'order_provisional_price' => $subtotal,
				'order_deliver_cost' => $orderDeliverCost,
				'order_total_price' => $subtotal + $orderDeliverCost, // Placeholder, calculate later
				'order_payment_method' => $paymentMethodId,
				'order_status' => 'pending',
				'order_additional_note' => $additionalNote,
				'order_deliver_address' => $deliverAddress,
				'order_payment_date' => null,
				'order_is_paid' => false,
				'order_deliver_time' => null,
			]
		);
		$orderItems = $cartItems->map(function ($item) use ($order) {
			// Calculate item discount amount (if any, could be 0 if no discount)
			$itemDiscount = ceil(($item->cart_items_quantity * $item->cart_items_unitprice) * $item->product->product_discount_percentage / 100);

			return new OrderItem([
				'order_id' => $order->order_id,
				'product_id' => $item->product_id,
				'order_items_quantity' => $item->cart_items_quantity,
				'order_items_size' => $item->cart_items_size,
				'order_items_unitprice' => $item->discounted_unit_price ?? $item->cart_items_unitprice,
				'order_items_discounted_amount' => $itemDiscount,
			]);
		});

		// Validate voucher is applicable
		if ($voucherCode) {
			$voucher = Voucher
				::where('voucher_code', $voucherCode)
				->with('voucherRules')
				->first();
			Log::info('Voucher:', [$voucher]);
			// Early return if not applicable
			if (!$voucher || !VoucherController::isVoucherApplicable($voucher, $cartItems)) {
				return response()->json(['message' => 'Voucher is not applicable'], 422);
			}

			Log::debug('Voucher is applicable:');
			// Apply voucher to order
			$this->applyVoucher($voucher, $order, $orderItems);
		}

		$order->save();
		$order->order_items()->saveMany($orderItems);

		switch ($paymentMethodId) {
			case Constants::PAYMENT_METHOD_COD:

				break;
		}

		Log::debug("Done");

		return response()->json([
			'message' => 'Order placed',
			'user' => $user,
			'order_id' => $order->order_id,
			'order_total_price' => $order->order_total_price,
			'order_deliver_cost' => $order->order_deliver_cost,
			'payment_method_id' => $paymentMethodId,
			'voucher_code' => $voucherCode,
		]);
	}

	public function cancelOrder(Request $request)
	{
		$validated = $request->validate([
			'order_id' => 'required|integer'
		]);

		$orderId = $validated['order_id'];
		$order = Order::findOrFail($orderId);

		if ($order->order_status !== 'pending') {
			return response()->json(['message' => 'Cannot cancel order'], 422);
		}

		$order->order_status = 'cancelled';
		$order->save();

		return response()->json(['message' => 'Order cancelled successfully']);
	}

	public static function applyVoucher($voucher, $order, $orderItems): void
	{
		// Handle voucher type gift_product specifically
		if ($voucher->voucher_type === 'gift_product') {
			Log::info('Order items before applying gift products:', [$orderItems]);
			Log::debug("Im here");
			Log::debug('Voucher value:', [$voucher->voucher_value]);
			$giftProducts = VoucherController::parseGiftProductValue($voucher->voucher_value);
			Log::info('Parsed gift products:', [$giftProducts]);

			// Add gift products to order items
			foreach ($giftProducts as $giftProduct) {
				$existingItem = false;

				// Check if product already exists in order items
				foreach ($orderItems as &$item) {
					if ($item['product_id'] == $giftProduct['product_id'] && $item['order_items_size'] == $giftProduct['size']) {
						// Update the quantity
						$item['order_items_quantity'] += $giftProduct['quantity'];
						$existingItem = true;
						break;
					}
				}

				// If product doesn't exist in order items, add it
				if (!$existingItem) {
					$orderItems[] = new OrderItem([
						'order_id' => $order->order_id,
						'product_id' => $giftProduct['product_id'],
						'order_items_quantity' => $giftProduct['quantity'],
						'order_items_unitprice' => 0, // Gift products are free
						'order_items_size' => $giftProduct['size'],
						'order_items_discounted_amount' => 0, // No discount for gift products
					]);
				}
			}

			Log::info('Gift products added to order items:', context: [$orderItems]);
		}
		// Handle other voucher types
		else {
			$discount_value = VoucherController::calculateDiscountValue(
				$voucher,
				$order->order_provisional_price,
				$order->order_deliver_cost
			);

			switch ($voucher->voucher_fields) {
				case 'freeship':
					$order->order_deliver_cost -= $discount_value;
					break;
				default:
					$order->order_total_price -= $discount_value;
					break;
			}
		}
	}

	public function updateOrderStatus(Request $request)
	{
		try {
			$validatedData = $request->validate([
				'order_id' => 'required|integer',
				'status' => 'required|string|in:pending,delivering,delivered,cancelled',
				'message' => 'nullable|string'
			]);

			$orderId = $validatedData['order_id'];
			$order = Order::findOrFail($orderId);

			// Check if the user has permission to update this order
			$user = Auth::user();
			$hasPermission = $user->role_type === 1 || $user->user_id === $order->user_id;
			if (!$hasPermission) {
				return response()->json(['message' => 'Unauthorized to update this order'], 403);
			}

			// Update the order status
			$result = $order->updateStatus(
				$validatedData['status'],
				$validatedData['message'] ?? null
			);

			if ($result) {
				return response()->json([
					'success' => true,
					'message' => 'Order status updated successfully',
					'order' => $order
				]);
			} else {
				return response()->json([
					'success' => false,
					'message' => 'Failed to update order status'
				], 500);
			}

		} catch (\Illuminate\Validation\ValidationException $e) {
			return response()->json(['message' => $e->getMessage()], 400);
		} catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
			return response()->json(['message' => 'Order not found'], 404);
		} catch (Exception $e) {
			Log::error('Failed to update order status: ' . $e->getMessage());
			return response()->json(['message' => 'Failed to update order status'], 500);
		}
	}

	public function getOrderStatus(Request $request)
	{
		try {
			$validated = $request->validate([
				'order_id' => 'required|integer'
			]);

			$orderId = $validated['order_id'];
			$order = Order::with(relations: ['order_items.product'])->findOrFail($orderId);

			// Check if the user has permission to view this order
			$user = Auth::user();
			$isAllowedToView = $user->user_id === $order->user_id || $user->role_type === 1;
			if (!$isAllowedToView) {
				return response()->json(['message' => 'Unauthorized to view this order'], 403);
			}

			return response()->json([
				'success' => true,
				'order' => [
					'order_id' => $order->order_id,
					'status' => $order->order_status,
					'created_at' => $order->created_at,
					'updated_at' => $order->updated_at,
					'deliver_time' => $order->order_deliver_time,
					'deliver_address' => $order->order_deliver_address,
					'total_price' => $order->order_total_price,
					'items' => $order->order_items->map(function ($item) {
						return [
							'product_id' => $item->product_id,
							'product_name' => $item->product->product_name,
							'quantity' => $item->order_items_quantity,
							'size' => $item->order_items_size,
							'unit_price' => $item->order_items_unitprice,
							'discount_amount' => $item->order_items_discounted_amount,
						];
					})
				]
			]);

		} catch (\Illuminate\Validation\ValidationException $e) {
			return response()->json(['message' => $e->getMessage()], 400);
		} catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
			return response()->json(['message' => 'Order not found'], 404);
		} catch (Exception $e) {
			Log::error('Failed to get order status: ' . $e->getMessage());
			return response()->json(['message' => 'Failed to get order status'], 500);
		}
	}

	public function getOrderDetails(Request $request)
	{
		try {
			$validated = $request->validate([
				'order_id' => 'required|integer'
			]);

			$orderId = $validated['order_id'];
			$order = Order::with(relations: ['order_items.product'])->findOrFail($orderId);
			$user = Auth::user();

			// Check if the user has permission to view this order
			$isAllowedToView = $user->user_id === $order->user_id || $user->role_type === 1;
			if (!$isAllowedToView) {
				return response()->json(['message' => 'Unauthorized to view this order'], 403);
			}

			$order = Order::with([
				'order_items.product.product_images'
			])->findOrFail($orderId);

			// return response()->json([
			// 	'success' => true,
			// 	'order' => [
			// 		'order_id' => $order->order_id,
			// 		'status' => $order->order_status,
			// 		'created_at' => $order->created_at,
			// 		'updated_at' => $order->updated_at,
			// 		'total_price' => $order->order_total_price,
			// 		'items' => $order->order_items->map(function ($item) {
			// 			return [
			// 				'product_id' => $item->product_id,
			// 				'product_name' => $item->product->product_name,
			// 				'quantity' => $item->order_items_quantity,
			// 				'size' => $item->order_items_size,
			// 				'unit_price' => $item->order_items_unitprice,
			// 				'discount_amount' => $item->order_items_discounted_amount,
			// 			];
			// 		})
			// 	]
			// ]);
			return response()->json($order);

		} catch (\Illuminate\Validation\ValidationException $e) {
			return response()->json(['message' => $e->getMessage()], 400);
		} catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
			return response()->json(['message' => 'Order not found'], 404);
		} catch (Exception $e) {
			Log::error('Failed to get order details: ' . $e->getMessage());
			return response()->json(['message' => 'Failed to get order details'], 500);
		}
	}
}
