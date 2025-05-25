<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Exception;
use App\Models\Voucher;
use App\Models\CartItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use \Carbon\Carbon;


class VoucherController extends Controller
{
	/**
	 * @var CartItem[]
	 */
	public static function isVoucherApplicable($voucher, iterable $cartItems)
	{
		// Check voucher start date and end date
		$startDate = Carbon::createFromFormat('Y-m-d H:i:s', $voucher->voucher_start_date);
		$endDate = Carbon::createFromFormat('Y-m-d H:i:s', $voucher->voucher_end_date);
		$now = now();

		Log::debug('Voucher ID: ', [$voucher->voucher_id]);
		Log::debug('Voucher start date:', [$startDate]);
		Log::debug('Voucher end date:', [$endDate]);
		Log::debug('Current date:', [$now]);

		if ($startDate->greaterThan($now)) {
			if ($voucher->voucher_id === 58) {
				Log::debug('Voucher is not applicable yet (start date in the future)');
			}
			return false;
		} else if ($endDate->lessThan($now)) {
			if ($voucher->voucher_id === 58) {
				Log::debug('Voucher is expired (end date in the past)');
			}
			return false;
		}

		// Calculate order subtotal
		$voucherRules = $voucher->voucherRules()->get();
		$subtotal = 0;
		foreach ($cartItems as $item) {
			$subtotal += $item->cart_items_quantity * $item->cart_items_unitprice;
		}

		// Process other standard rules
		foreach ($voucherRules as $rule) {
			switch ($rule->voucher_rule_type) {
				case 'first_order':
					$orderCount = Auth::user()->orders()->count();
					Log::debug('Order count:', [$orderCount]);
					if ($orderCount > 0) {
						Log::debug('Voucher ' . $voucher->voucher_id . ' is not applicable (not first order)');
						return false;
					}
					break;

				case 'min_bill_price':
					if ($subtotal < (int) $rule->voucher_rule_value) {
						Log::debug('Voucher ' . $voucher->voucher_id . ' is not applicable (not reached minimum bill price)');
						return false;
					}
					break;

				case 'category_restriction':
				case 'category_id':
					$validCategoryIds = array_map('intval', explode(',', $rule->voucher_rule_value));
					$categoryIds = [];

					foreach ($cartItems as $cartItem) {
						if (isset($cartItem->product->category_id)) {
							$categoryIds[] = $cartItem->product->category_id;
						}
					}

					// Check if at least one item from each category is present
					foreach ($validCategoryIds as $validCategoryId) {
						if (!in_array($validCategoryId, $categoryIds)) {
							Log::debug('Voucher ' . $voucher->voucher_id . ' is not applicable (not in required categories)');
							return false;
						}
					}
					break;

				case 'max_distance':
					// Implementation pending - requires delivery distance calculation
					break;

				case 'product_id':
					$requiredProducts = self::parseGiftProductValue($rule->voucher_rule_value);

					// Check if the cart has the required quantities of each product
					foreach ($requiredProducts as $requiredProduct) {
						$productInCart = false;
						$sufficientQty = false;

						// Look for this product in cart items 
						foreach ($cartItems as $item) {
							if ($item->product_id == $requiredProduct['product_id']) {
								$productInCart = true;
								$requiredQty = (int) $requiredProduct['quantity'];
								if (!$requiredProduct['size'] && $item->cart_items_quantity >= $requiredQty) {
									$sufficientQty = true;
									break;
								} else if ($requiredProduct['size'] == $item->cart_items_size && $item->cart_items_quantity >= $requiredQty) {
									$sufficientQty = true;
									break;
								}
							}
						}

						// If the product isn't in cart or doesn't have enough quantity, voucher isn't applicable
						if (!$productInCart || !$sufficientQty) {
							Log::debug('Voucher ' . $voucher->voucher_id . ' is not applicable (product not in cart or insufficient quantity)');
							return false; // Voucher not applicable (early return)
						}
					}
					break;

				case 'remaining_quantity':
					$remainingQuantity = (int) $rule->voucher_rule_value;
					if ($remainingQuantity <= 0) {
						Log::debug('Voucher ' . $voucher->voucher_id . ' is not applicable (no remaining quantity)');
						return false; // No more vouchers available
					}
					break;

				case 'min_items':
					$requiredItemCount = (int) $rule->voucher_rule_value;
					$cartItemCount = count($cartItems);
					if ($cartItemCount < $requiredItemCount) {
						Log::debug('Voucher ' . $voucher->voucher_id . ' is not applicable (not enough items in cart)');
						return false;
					}
					break;
			}
		}

		Log::debug('Voucher ' . $voucher->voucher_id . ' IS APPLICABLE');

		return true;
	}

	// Expose and endpoint for public check of voucher applicability
	public function isVoucherApplicablePublic(Request $request)
	{
		try {
			$request->validate([
				'voucher_code' => 'string|required',
				'selected_item_ids' => 'string|required'
			]);
		} catch (Exception $e) {
			Log::error('Invalid voucher code input:', [$e->getMessage()]);
			return response()->json(['message' => 'Invalid voucher code'], 400);
		}

		$voucherCode = $request->input('voucher_code');
		Log::info('Voucher code:', [$voucherCode]);

		// Fetch the voucher
		$voucher = Voucher::where('voucher_code', $voucherCode)->first();
		if (!$voucher) {
			return response()->json(['message' => 'Voucher not found'], 404);
		}

		$selectedItemIds = array_map('intval', array: explode(',', $request->input('selected_item_ids')));
		Log::info('Selected items ID list:', [$selectedItemIds]);
		// Fetch the cart items for the user
		$cart = Auth::user()->cart()->with([
			'items.product' => function ($query) {
				$query->select('product_id', 'category_id', 'product_discount_percentage');
			}
		])->first();
		// Filter cart items to only those with product_id in selectedItemIds
		$cartItems = [];
		if ($cart) {
			$cartItems = $cart->items->whereIn('product_id', $selectedItemIds)->values();
		}
		Log::info('Cart items:', [json_encode($cartItems)]);

		if (!$voucher) {
			return response()->json(['message' => 'Voucher not found'], 404);
		}

		return response()->json(data: [
			'voucher_code' => $voucher->voucher_code,
			'is_applicable' => self::isVoucherApplicable($voucher, [])
		]);
	}

	/**
	 * Parse gift product value format (e.g., '1:1,5:1')
	 * Returns associative array where key is product_id and value is quantity
	 * 
	 * @param string $value The gift product value string
	 * @return array Associative array of product_id => quantity
	 */
	public static function parseGiftProductValue($value)
	{
		$result = [];
		$productParts = explode(',', $value);

		foreach ($productParts as $part) {
			$values = explode(':', $part);
			$productId = (int) $values[0];
			$quantity = (int) $values[1];
			$size = $values[2] ?? null; // Optional size parameter
			$result[] = [
				'product_id' => $productId,
				'quantity' => $quantity,
				'size' => $size,
			];
		}

		return $result;
	}

	/**
	 * Calculate the discount value for a given voucher
	 * 
	 * @param Voucher $voucher The voucher
	 * @param float $subtotal The cart subtotal
	 * @param float $deliveryFee The delivery fee
	 * @return float The calculated discount value
	 */
	public static function calculateDiscountValue($voucher, float $subtotal = 0, float $deliveryFee = 0)
	{
		// Default discount value
		$discountValue = 0;

		// Step 1: Calculate base discount value based on voucher type and fields
		switch ($voucher->voucher_fields) {
			case 'freeship':
				if ($voucher->voucher_type === 'cash') {
					$discountValue = min((int) $voucher->voucher_value, $deliveryFee);
				} elseif ($voucher->voucher_type === 'percentage') {
					$discountValue = min(((int) $voucher->voucher_value * $deliveryFee) / 100, $deliveryFee);
				}
				break;

			case 'birthday_gift':
			case 'loyal_customer':
			case 'new_customer':
			case 'holiday':
			case 'shop_related':
			default:
				if ($voucher->voucher_type === 'cash') {
					$discountValue = (int) $voucher->voucher_value;
				} elseif ($voucher->voucher_type === 'percentage') {
					$discountValue = ((int) $voucher->voucher_value * $subtotal) / 100;
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
		try {
			$request->validate([
				'selected_item_ids' => 'string|required', // A list of product_id, delimited by commas
				'voucher_code' => 'string|nullable',
				'offset' => 'integer|min:0|nullable',
				'limit' => 'integer|min:1|max:100|nullable'
			]);
		} catch (Exception $e) {
			Log::error('Invalid selected items ID list input:', [$e->getMessage()]);
			return response()->json(['message' => 'Invalid selected items'], 400);
		}

		$selectedItemIds = array_map('intval', explode(',', $request->input('selected_item_ids')));
		Log::info('Selected items ID list:', [$selectedItemIds]);

		$offset = $request->input('offset', 0);
		$limit = $request->input('limit', 10);

		// Fetch vouchers for the user
		if ($request->input('voucher_code')) {
			$vouchers = Voucher::where('voucher_code', $request->input('voucher_code'))
				->with('voucherRules')
				->get();
			$hasMore = false;
		} else {
			$vouchers = Voucher::with('voucherRules')
				->orderBy('voucher_start_date', 'desc') // Newest first
				->offset($offset)
				->limit($limit)
				->get();

			// Determine if there are more vouchers beyond current page
			$hasMore = Voucher::with('voucherRules')
				->orderBy('voucher_start_date', 'desc')
				->offset($offset + $limit)
				->limit(1)
				->exists();
		}

		$cart = Auth::user()->cart()->with([
			'items.product' => function ($query) {
				$query->select('product_id', 'category_id', 'product_discount_percentage');
			}
		])->first();

		// Filter cart items to only those with product_id in selectedItemIds
		$cartItems = [];
		if ($cart) {
			$cartItems = $cart->items->whereIn('product_id', $selectedItemIds)->values();
		}
		Log::info('Cart items:', [json_encode($cartItems)]);

		// Calculate order subtotal
		$subtotal = 0;
		foreach ($cartItems as $item) {
			$discountedUnitprice = $item->discounted_unit_price;
			if ($discountedUnitprice) {
				$subtotal += $discountedUnitprice * $item->cart_items_quantity;
				Log::debug('Discounted unit price found:', [$discountedUnitprice]);
			} else {
				$subtotal += $item->cart_items_unitprice * $item->cart_items_quantity;
			}
		}

		$deliveryFee = 20000; // Default delivery fee
		Log::debug("subtotal: ", [$subtotal]);

		// Fetch vouchers that are applicable to the cart items
		$processedVouchers = $vouchers->map(function ($voucher) use ($cartItems, $subtotal, $deliveryFee) {
			$isApplicable = $this->isVoucherApplicable($voucher, $cartItems);
			$discountValue = $isApplicable ? $this->calculateDiscountValue($voucher, $subtotal, $deliveryFee) : 0;

			// Create a new array with the additional properties
			$voucherArray = $voucher->toArray();
			$voucherArray['is_applicable'] = $isApplicable;
			Log::debug('Voucher ' . $voucherArray['voucher_id'] . ' applicable?: ', [$voucherArray['is_applicable']]);
			$voucherArray['discount_value'] = $discountValue;

			return $voucherArray;
		});

		return response()->json([
			'vouchers' => $processedVouchers,
			'has_more' => $hasMore,
		]);
	}

	public function getVoucherGiftProducts(Request $request)
	{
		try {
			$request->validate([
				'voucher_code' => 'string|required'
			]);
		} catch (Exception $e) {
			Log::error('Invalid voucher code input:', [$e->getMessage()]);
			return response()->json(['message' => $e->getMessage()], 400);
		}

		$voucherCode = $request->input('voucher_code');
		Log::info('Voucher code:', [$voucherCode]);

		// Fetch the voucher
		$voucher = Voucher::where('voucher_code', $voucherCode)->first();

		if (!$voucher) {
			return response()->json(['message' => 'Voucher not found'], 404);
		}

		$giftProducts = self::parseGiftProductValue($voucher->voucher_value);
		$giftProductIds = array_map(function ($product) {
			return $product['product_id'];
		}, $giftProducts);

		// Fetch the products associated with the voucher
		$products = Product::whereIn('product_id', $giftProductIds)
			->with([
				'product_images' => function ($query) {
					$query->select('product_id', 'product_image_url');
				}
			])
			->get(['product_id', 'product_name']);

		$products = $products->map(function ($product) use ($giftProducts) {
			// First, add the gift product quantity and size
			foreach ($giftProducts as $giftProduct) {
				if ($product->product_id == $giftProduct['product_id']) {
					$product->quantity = $giftProduct['quantity'];
					$product->size = $giftProduct['size'] ?? null;
				}
			}

			// Then, set the image from product_images relation
			if ($product->product_images && count($product->product_images) > 0) {
				$chosenImgObj = $product->product_images[0];

				if ($chosenImgObj->product_image_url) {
					$product->product_image = $chosenImgObj->product_image_url;
				} else {
					$product->product_image = "https://cdn.shopify.com/s/files/1/0727/6042/6786/files/ICON-02.png?v=1681843319"; // placeholder image
				}

				// Remove the product_images relation from the response
				unset($product->product_images);
			}

			return $product;
		});

		Log::debug('Gift products:', [$products]);

		return response()->json($products);
	}
}
