<?php

namespace App\Http\Controllers;

use App\Models\VoucherRule;
use Exception;
use App\Constants;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Voucher;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\VoucherController;
use App\Services\MomoPaymentService;
use App\Notifications\OnlinePaymentNotification;

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
				'language' => 'nullable|string|in:en,vi',
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
		$userAddressId = $validatedInput['user_address_id'];
		$additionalNote = $validatedInput['order_additional_note'] ?? null;
		$voucherCode = $validatedInput['voucher_code'] ?? null;
		$orderDeliverCost = 20000; // hard-code for now

		// Get cart items
		$cart = Auth::user()->cart()->with([
			'items.product' => function ($query) {
				$query->select('product_id', 'category_id', 'product_discount_percentage', 'product_stock_quantity', 'product_name');
			}
		])->first();
		$selectedItemIds = array_map('intval', explode(',', $validatedInput['selected_item_ids']));
		$cartItems = [];
		if ($cart) {
			$cartItems = $cart->items->whereIn('product_id', $selectedItemIds)->values();
		}

		// Check stock quantity before proceeding
		foreach ($cartItems as $item) {
			$product = $item->product;
			if ($product->product_stock_quantity < $item->cart_items_quantity) {
				return response()->json([
					'message' => "Insufficient stock for product {$product->product_name}. Available: {$product->product_stock_quantity}, Requested: {$item->cart_items_quantity}",
					'code' => 'INSUFFICIENT_STOCK',
					'extras' => [
						'product_id' => $product->product_id,
						'product_name' => $product->product_name,
					]
				], 422);
			}
		}

		// Find delivery address
		$addressItem = $user->user_addresses
			->where(
				'user_address_id',
				$userAddressId
			)->first();
		if (!$addressItem || !isset($addressItem['full_address'])) {
			return response()->json(['message' => 'Cannot find delivery address', 'code' => 'ADDRESS_NOT_FOUND'], 422);
		}
		$deliverAddress = $addressItem['full_address'];
		Log::debug('Deliver address:', [$deliverAddress]);

		// Calculate order subtotal
		$subtotal = 0;
		foreach ($cartItems as $item) {
			$unitPrice = $item->discounted_unit_price ?? $item->cart_items_unitprice;
			$itemCost = $unitPrice * $item->cart_items_quantity;
			$subtotal += $itemCost;
		}

		// Build order and order items
		$order = Order::create(
			[
				'user_id' => $user->user_id,
				'voucher_code' => $voucherCode,
				'order_provisional_price' => $subtotal,
				'order_deliver_cost' => $orderDeliverCost,
				'order_total_price' => $subtotal + $orderDeliverCost,
				'order_payment_method' => $paymentMethodId,
				'order_status' => $paymentMethodId === Constants::PAYMENT_METHOD_COD ? 'pending' : 'online_payment_pending',
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
			$voucher_rules = $voucher->voucherRules()->get();
			$this->applyVoucher($voucher, $voucher_rules, $order, $orderItems);
		}


		// Decrease product stock quantities and product total orders (total sold)
		foreach ($cartItems as $item) {
			$product = $item->product;
			$product->product_stock_quantity -= $item->cart_items_quantity;
			$product->product_total_orders += $item->cart_items_quantity;
			$product->save();
		}

		// Finally, handle different flows a bit differently for different payment methods
		$responseData = [
			'message' => 'Order placed',
			'user' => $user,
			'order_id' => $order->order_id,
			'order_total_price' => $order->order_total_price,
			'order_deliver_cost' => $order->order_deliver_cost,
			'payment_method_id' => $paymentMethodId,
			'voucher_code' => $voucherCode,
		];

		switch ($paymentMethodId) {
			case Constants::PAYMENT_METHOD_COD:
				break;
			// Handle Momo payment flow
			case Constants::PAYMENT_METHOD_MOMO:
				$momoService = app(MomoPaymentService::class);
				$paymentCreationInfo = $momoService->createPaymentIntent(
					$order->order_id,
					'', // Order info
					$order->order_total_price, // amount
					$validatedInput['language'] ?? 'vi',
				);
				Log::debug('Momo payment creation response:', [$paymentCreationInfo]);
				if ($paymentCreationInfo['success'] === false) {
					Log::error('Failed to create Momo payment intent: ');
					return response()->json(['message' => $paymentCreationInfo['message']], 500);
				} else {
					$responseData['pay_urls'] = $paymentCreationInfo['payUrls']; // Attach generated payment URLs to response
					// Notify user
					$user->notify(new OnlinePaymentNotification(
						$order->order_id,
						"created", // payment stauts
						Constants::PAYMENT_METHOD_MOMO,
						json_encode($paymentCreationInfo['payUrls'])
					));
				}
				break;
		}

		Log::debug("Order placed");
		$order->save();
		$order->order_items()->saveMany($orderItems);
		return response()->json($responseData);
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

	public static function unapplyVoucher($voucher, $voucher_rules, $order, $orderItems, $saveImmediately = false): void
	{
		// Make changes to voucher rules if necessary (e.g., increment remaining quantity)
		foreach ($voucher_rules as $rule) {
			if ($rule->voucher_rule_type === 'remaining_quantity') {
				$rule->voucher_rule_value += 1; // Increment remaining quantity
			}
		}

		// Handle voucher type gift_product specifically
		// if ($voucher->voucher_type === 'gift_product') {
		// 	Log::info('Removing gift products from order items:', [$orderItems]);

		// 	$giftProducts = VoucherController::parseGiftProductValue($voucher->voucher_value);
		// 	// Remove gift products from order items
		// 	foreach ($giftProducts as $giftProduct) {
		// 		// Find and remove gift products from order items
		// 		foreach ($orderItems as $index => $item) {
		// 			if ($item['product_id'] == $giftProduct['product_id'] && $item['order_items_size'] == $giftProduct['size']) {
		// 				// If quantity matches exactly, remove the item completely
		// 				if ($item['order_items_quantity'] == $giftProduct['quantity']) {
		// 					unset($orderItems[$index]);
		// 				} else {
		// 					// Otherwise, decrease the quantity
		// 					$item['order_items_quantity'] -= $giftProduct['quantity'];
		// 				}

		// 				// Restore stock quantity for gift product
		// 				$product = \App\Models\Product::find($giftProduct['product_id']);
		// 				if ($product) {
		// 					$product->product_stock_quantity += $giftProduct['quantity'];
		// 					$product->save();
		// 				}
		// 				break;
		// 			}
		// 		}
		// 	}

		// Re-index the array after removing items
		// $orderItems = array_values($orderItems);


		// Log::info('Gift products removed from order items:', [$orderItems]);
		// }

		if ($saveImmediately) {
			foreach ($voucher_rules as $rule) {
				$rule->save();
			}
			$order->save();
			Log::debug("Unapplied voucher and saved changes");
		}
		Log::debug("Unapplied voucher");
	}

	public static function applyVoucher($voucher, $voucher_rules, $order, $orderItems, $saveImmediately = false): void
	{
		Log::debug("Order ID in applyVoucher:", [$order->order_id]);
		// Add voucher id to order
		$order->voucher_id = $voucher->voucher_id;

		// Make changes to voucher rules if necessary (e.g., decrement remaining quantity)
		foreach ($voucher_rules as $rule) {
			if ($rule->voucher_rule_type === 'remaining_quantity') {
				$rule->voucher_rule_value -= 1; // Decrement remaining quantity
			}
		}

		// Handle voucher type gift_product specifically
		if ($voucher->voucher_type === 'gift_product') {
			Log::info('Order items before applying gift products:', [$orderItems]);
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
						// Decrease stock quantity for gift product
						$product = \App\Models\Product::find($giftProduct['product_id']);
						if ($product) {
							$product->product_stock_quantity -= $giftProduct['quantity'];
							$product->save();
						}
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
				default:
					$order->order_total_price -= $discount_value;
					break;
			}
		}


		// Save changes if rqeuired
		if ($saveImmediately) {
			$order->order_items()->saveMany($orderItems);
			foreach ($voucher_rules as $rule) {
				$rule->save();
			}
			$order->save();
		}
	}

	public function updateOrderStatus(Request $request)
	{
		try {
			$validatedData = $request->validate([
				'order_id' => 'required|integer',
				'status' => 'required|string|in:pending,delivering,delivered,cancelled',
				'message' => 'nullable|string',
				'lang' => 'nullable|string|in:en,vi',
			]);

			$orderId = $validatedData['order_id'];
			$order = Order::with('voucher')->findOrFail($orderId);

			// Check if the user has permission to update this order
			$user = Auth::user();
			$hasPermission = $user->role_type === 1 || $user->user_id === $order->user_id;
			Log::debug('User ID:', [$user->user_id]);
			if (!$hasPermission) {
				return response()->json(['message' => 'Unauthorized to update this order'], 403);
			}

			if ($validatedData['status'] === 'delivered') {
				// If the order is marked as delivered, set the payment date and mark as paid
				if ($order->order_payment_method === Constants::PAYMENT_METHOD_COD) {
					$order->order_payment_date = now();
					$order->order_deliver_time = now();
					$order->order_is_paid = true;
				}
				$order->save();
			} else if ($validatedData['status'] === 'cancelled') {
				// Cannot cancel an order that is not pending
				if (in_array($order->order_status, ['delivering', 'delivered', 'cancelled'])) {
					return response()->json([
						'message' => 'Cannot cancel order that is not pending',
						'code' => 'TOO_LATE_TO_CANCEL'
					], 422);
				}

				if ($order->order_payment_method === Constants::PAYMENT_METHOD_MOMO) {
					// If the order was paid via Momo, refund logic should be implemented here
					// For now, we just log it
					Log::info('Order cancelled with Momo payment, refund logic not implemented yet');

					// Revert the stock quantities and total_orders for the cancelled order items
					$order_items = $order->order_items()->get();
					$order_items->each(function ($item) {
						$product = $item->product;
						if ($product) {
							$product->product_stock_quantity += $item->order_items_quantity;
							if ($product->product_total_orders >= $item->order_items_quantity) {
								$product->product_total_orders -= $item->order_items_quantity;
							} else {
								// Prevent negative total orders
								$product->product_total_orders = 0;
							}
							$product->save();
						}
					});
					if ($order->voucher) {
						$voucher_rules = $order->voucher->voucherRules()->get();
						$this->unapplyVoucher($order->voucher, $voucher_rules, $order, $order->order_items, true);
					}

					// Refund momo money to customer
					$momoService = app(MomoPaymentService::class);
					$refundResult = $momoService->refundPaymentForOrder(
						$order->order_id,
						$validatedData['language'] ?? 'vi',
					);

					if (!isset($refundResult['success']) || $refundResult['success'] !== true) {
						$errCode = $refundResult['code'] ?? 'unknown';
						Log::error("Failed to refund Momo payment for order {$order->order_id}");
						return response()->json([
							'message' => "Failed to refund Momo payment (error code {$errCode})",
							'code' => 'REFUND_FAILED',
						], 500);
					}

					// Update order is paid status
					$order->order_is_paid = false;
				}
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
				'order_status' => $order->order_status,
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

			$user = Auth::user();
			$orderId = $validated['order_id'];
			$order = Order::with([
				'order_items.product.product_images',
				'voucher'
			])->findOrFail($orderId);

			// Check if the user has permission to view this order
			$isAllowedToView = $user->user_id === $order->user_id || $user->role_type === 1;
			if (!$isAllowedToView) {
				return response()->json(['message' => 'Unauthorized to view this order'], 403);
			}

			// Calculate voucher discount value
			if ($order->voucher && $order->voucher->voucher_type != 'gift_product') {
				$discountValue = VoucherController::calculateDiscountValue(
					$order->voucher,
					$order->order_provisional_price,
					$order->order_deliver_cost
				);
				$order->voucher->setAttribute('discount_value', $discountValue);
			} else if ($order->voucher) {
				$order->voucher->setAttribute('discount_value', 0);
			}

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

	public function getOrderHistory(Request $request)
	{
		try {
			$validated = $request->validate([
				'offset' => 'nullable|integer|min:0',
				'limit' => 'nullable|integer|min:1|max:100'
			]);

			$offset = $validated['offset'] ?? 0;
			$limit = $validated['limit'] ?? 10;
			$user = Auth::user();

			// Get orders with pagination
			$orders = Order::where('user_id', $user->user_id)
				->with(['order_items.product.product_images', 'order_feedbacks'])
				->orderBy('updated_at', 'desc')
				->offset($offset)
				->limit($limit + 1) // Get one extra to check if there are more
				->get();

			// Check if there are more orders
			$hasMore = $orders->count() > $limit;
			if ($hasMore) {
				$orders->pop(); // Remove the extra order
			}

			$orderHistory = $orders->map(function ($order) {
				return [
					'order_id' => $order->order_id,
					'status' => $order->order_status,
					'created_at' => $order->created_at,
					'total_price' => $order->order_total_price,
					'payment_method' => $order->order_payment_method,
					'deliver_address' => $order->order_deliver_address,
					'items_count' => $order->order_items->count(),
					'did_feedback' => $order->order_feedbacks->isNotEmpty(),
					'items' => $order->order_items->map(callback: function ($item) {
						return [
							'product_id' => $item->product_id,
							'product_name' => $item->product->product_name,
							'product_category_id' => $item->product->category_id,
							'quantity' => $item->order_items_quantity,
							'size' => $item->order_items_size,
							'unit_price' => $item->order_items_unitprice,
							'image_url' => $item->product->product_images->first()->product_image_url ?? null,
						];
					})
				];
			});

			return response()->json([
				'orders' => $orderHistory,
				'has_more' => $hasMore,
				'offset' => $offset,
				'limit' => $limit
			]);

		} catch (\Illuminate\Validation\ValidationException $e) {
			return response()->json(['message' => $e->getMessage()], 400);
		} catch (Exception $e) {
			Log::error('Failed to get order history: ' . $e->getMessage());
			return response()->json(['message' => 'Failed to get order history'], 500);
		}
	}
}
