<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
use Log;
use Exception;
use App\Models\Voucher;
use App\Models\CartItem;


class VoucherController extends Controller
{
	/**
	 * @var CartItem[]
	 */
	protected function isVoucherApplicable($voucher, iterable $cartItems)
	{
		// Check if the voucher is applicable to the cart items
		$voucherRules = $voucher->voucherRules()->get();
		$subtotal = 0;
		foreach ($cartItems as $item) {
			$subtotal += $item->cart_items_quantity * $item->cart_items_unitprice;
		}

		foreach ($voucherRules as $rule) {
			switch ($rule->voucher_rule_type) {
				case 'first_order':
					$orderCount = Auth::user()->orders()->count();
					Log::debug('Order count:', [$orderCount]);
					if ($orderCount > 0) {
						return false;
					}
					break;
				case 'min_bill_price':
					if ($subtotal < (int) $rule->voucher_rule_value) {
						return false;
					}
					break;
				case 'category_id':
					$validCategoryIds = array_map('intval', explode(',', $rule->voucher_rule_value));
					$categoryIds = array_map(function ($cartItem) {
						return $cartItem->product->category_id;
					}, $cartItems);
					foreach ($categoryIds as $categoryId) {
						if (!in_array($categoryId, $validCategoryIds)) {
							return false;
						}
					}
					break;
				// case 'day_restriction':
				// 	if (date('N') != $rule->voucher_rule_value) {
				// 		return false;
				// 	}
				// 	break;
				case 'max_distance':
					// if ($cartItems->distance > $rule->voucher_rule_value) {
					// 	return false;
					// }
					// Implement later
					break;
				case 'product_id':
					$validProductIds = array_map('intval', explode(',', $rule->voucher_rule_value));
					$productIds = array_map(function ($cartItem) {
						return $cartItem->product_id;
					}, $cartItems);
					foreach ($productIds as $productId) {
						if (!in_array($productId, $validProductIds)) {
							return false;
						}
					}
					break;
				// Check remaining quantity if applicable
				case 'remaining_quantity':
					$remainingQuantity = (int) $rule->voucher_rule_value;
					if ($remainingQuantity <= 0) {
						return false; // No more vouchers available
					}
					break;
			}
		}

		return true;
	}

	/**
	 * Calculate the discount value for a given voucher
	 * 
	 * @param Voucher $voucher The voucher
	 * @param float $subtotal The cart subtotal
	 * @param float $deliveryFee The delivery fee
	 * @return float The calculated discount value
	 */
	protected function calculateDiscountValue($voucher, float $subtotal = 0, float $deliveryFee = 0)
	{
		// Default discount value
		$discountValue = 0;

		// Step 1: Calculate base discount value based on voucher type and fields
		switch ($voucher->voucher_fields) {
			case 'freeship':
				if ($voucher->voucher_type === 'cash') {
					$discountValue = min($voucher->voucher_value, $deliveryFee);
				} elseif ($voucher->voucher_type === 'percentage') {
					$discountValue = min(($voucher->voucher_value * $deliveryFee) / 100, $deliveryFee);
				}
				break;

			case 'birthday_gift':
			case 'loyal_customer':
			case 'new_customer':
			case 'holiday':
			case 'shop_related':
			default:
				if ($voucher->voucher_type === 'cash') {
					$discountValue = $voucher->voucher_value;
				} elseif ($voucher->voucher_type === 'percentage') {
					$discountValue = ($voucher->voucher_value * $subtotal) / 100;
				} elseif ($voucher->voucher_type === 'gift_product') {
					// For gift products, we'd typically return 0 for the discount value
					// The gift itself would be handled separately in the order process
					// $discountValue = 0;

					// If you want to include the value of the gift product:
					// $giftRule = $voucher->voucherRules()->where('voucher_rule_type', 'gift_product')->first();
					// if ($giftRule) {
					// You could look up the product value here
					// $productId = $giftRule->voucher_rule_value;
					// $product = Product::find($productId);
					// $discountValue = $product ? $product->price : 0;
					// }
					// }
				}
				break;
		}

		// Step 2: Apply any maximum discount rules
		$maxDiscountRule = $voucher->voucherRules()->where('voucher_rule_type', 'max_discount')->first();
		if ($maxDiscountRule) {
			$discountValue = min($discountValue, (float) $maxDiscountRule->voucher_rule_value);
		}

		return floor($discountValue); // Round to 2 decimal places
	}

	public function getVouchers(Request $request)
	{
		// Fetch vouchers for the user
		$vouchers = Voucher::offset($request->input('offset', default: 0))
			->limit($request->input('limit', 10))
			->get();

		$cart = Auth::user()->cart()->with([
			'items.product' => function ($query) {
				$query->select('product_id', 'category_id');
			}
		])->first();
		$cartItems = $cart ? $cart->items : [];
		Log::info('Cart items:', [json_encode($cartItems)]);

		$subtotal = 0;
		foreach ($cartItems as $item) {
			$subtotal += $item->cart_items_quantity * $item->cart_items_unitprice;
		}
		$deliveryFee = 30000; // Default delivery fee
		Log::debug("subtotal: ", [$subtotal]);

		$processedVouchers = $vouchers->map(function ($voucher) use ($cartItems, $subtotal, $deliveryFee) {
			$isApplicable = $this->isVoucherApplicable($voucher, $cartItems);
			$discountValue = $isApplicable ? $this->calculateDiscountValue($voucher, $subtotal, $deliveryFee) : 0;

			// Create a new array with the additional properties
			$voucherArray = $voucher->toArray();
			$voucherArray['is_applicable'] = $isApplicable;
			$voucherArray['discount_value'] = $discountValue;

			return $voucherArray;
		});


		return response()->json($processedVouchers);
	}
}
