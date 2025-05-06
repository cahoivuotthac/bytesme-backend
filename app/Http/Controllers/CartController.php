<?php
namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\CartItem;
use App\Models\Wishlist;
use Exception;
use Illuminate\Support\Facades\Auth;
use App\Models\Cart;
use Illuminate\Support\Facades\Log;

class CartController extends Controller
{
	public function getCartItems()
	{
		$user = Auth::user();
		try {
			$cartId = $user->getAttribute('cart_id');
			Log::debug('Cart ID:', ['cart_id' => $cartId]);
			$user_id = $user->user_id;
			$cartItems = CartItem::with(['product.productImages'])
				->where('cart_id', $cartId)
				->get()
				->map(function ($cartItem) use ($user_id) {
					$cartItem->is_wishlisted = Wishlist::where([
						'user_id' => $user_id,
						'product_id' => $cartItem->product_id
					])->exists();
					return $cartItem;
				});

			$inStockItems = CartItem::with(['product'])
				->whereHas('product', function ($query) {
					$query->where('product_stock_quantity', '>', 0);
				})
				->where('cart_id', $cartId)
				->get();

			$totalDiscountedPrice = $inStockItems->sum('discounted_price');
			$totalQuantity = $inStockItems->sum('quantity');
			$totalPrice = $inStockItems->sum('original_price');
			$totalDiscountAmount = $inStockItems->sum('discount_amount');
			return response()->json([
				'cartItems' => $cartItems,
				'instockCartItems' => $inStockItems,
				'totalPrice' => $totalPrice,
				'totalDiscountAmount' => $totalDiscountAmount,
				'totalDiscountedPrice' => $totalDiscountedPrice,
				'totalQuantity' => $totalQuantity
			]);
		} catch (Exception $e) {
			Log::error('Failed to fetch cart items', [
				'user_id' => $user->user_id,
				'error' => $e->getMessage()
			]);
			return response()->json([
				'cartItems' => [],
				'instockCartItems' => [],
				'totalPrice' => 0,
				'totalDiscountAmount' => 0,
				'totalDiscountedPrice' => 0,
				'totalQuantity' => 0,
				'error' => 'Failed to fetch cart items!'
			], 500);
		}
	}

	public function updatePrice(Request $request)
	{
		try {
			$user = Auth::user();
			$cartId = $user->cart_id;

			$cartItem = CartItem::whereHas('product', function ($query) {
				$query->where('stock_quantity', '>', 0);
			})->where([
						'cart_id' => $cartId,
						'product_id' => $request->product_id
					])->first();

			if ($cartItem) {
				$cartItem->update([
					'quantity' => $request->quantity,
					'original_price' => $request->original_price,
					'discount_amount' => $request->discount_amount,
					// 'final_price' => $request->final_price
				]);

				return response()->json([
					'success' => true,
					'message' => 'Price updated successfully'
				]);
			}

			return response()->json([
				'success' => false,
				'message' => 'Cart item not found'
			], 404);

		} catch (Exception $e) {
			return response()->json([
				'success' => false,
				'message' => 'Failed to update price'
			], 500);
		}
	}

	public function addToCart(Request $request)
	{
		try {
			// Validate input
			try {
				$validatedData = $request->validate([
					'product_id' => 'required|integer',
					'quantity' => 'integer|min:1'
				]);
			} catch (Exception $e) {
				return response()->json([
					'success' => false,
					'message' => 'Invalid input data',
					'errors' => $e->getMessage()
				], 400);
			}

			$user = Auth::user();
			$quantity = $validatedData['quantity'] ?? 1;
			$productId = $validatedData['product_id'];
			Log::debug('Request quantity:', ['quantity' => $quantity]);

			// Validate product exists
			$product = Product::find($productId);
			if (!$product) {
				return response()->json([
					'success' => false,
					'message' => 'Product not found'
				], 404);
			}

			// Get or create cart in a single query
			$cart = Cart::firstOrCreate(
				['cart_id' => $user->cart_id],
			);

			$cartItem = CartItem::with(['product'])
				->where('cart_id', $user->cart_id)
				->where('product_id', $productId)
				->first();

			// Stock validation
			$newQuantity = $cartItem ? $cartItem->cart_items_quantity + $quantity : $quantity;
			Log::debug('Stock quantity:', ['quantity' => $newQuantity]);
			if ($product->getAttribute('product_stock_quantity') < $newQuantity) {
				return response()->json([
					'success' => false,
					'message' => 'Insufficient stock available'
				], 400);
			}

			if ($cartItem) {
				$cartItem->update([
					'cart_items_quantity' => $newQuantity
				]);
				$cartItem->save();
			} else {
				CartItem::create([
					'cart_id' => $cart->cart_id,
					'product_id' => $productId,
					'cart_items_quantity' => $newQuantity
				]);
			}

			// Update the items count
			$cart->cart_items_count = CartItem::where('cart_id', $user->cart_id)
				->sum('cart_items_quantity');
			$cart->save();

			return response()->json([
				'success' => true,
				'message' => 'Item added to cart successfully',
				'items_count' => $cart->cart_items_count
			]);

		} catch (Exception $e) {
			Log::error('Failed to add item to cart', [
				'user_id' => Auth::id(),
				'product_id' => $productId ?? null,
				'error' => $e->getMessage()
			]);
			return response()->json([
				'success' => false,
				'message' => 'Failed to add item to cart'
			], 500);
		}
	}

	public function removeFromCart(Request $request)
	{
		// Validate input
		try {
			$validatedData = $request->validate([
				'product_id' => 'required|integer',
			]);
		} catch (Exception $e) {
			return response()->json([
				'success' => false,
				'message' => 'Invalid input data',
				'errors' => $e->getMessage()
			], 400);
		}

		$productId = $validatedData['product_id'];
		$user = Auth::user();
		$cartId = $user->cart_id;
		try {
			$cartItem = CartItem::where('cart_id', $cartId)
				->where('product_id', $productId)->first();

			if (!$cartItem) {
				return response()->json([
					'success' => false,
					'message' => 'Cart item not found!'
				], 400);
			}

			$cartItem->delete();

			$cart = Cart::find($cartId);
			$cart->cart_items_count = CartItem::where('cart_id', $cartId)->sum('cart_items_quantity');
			$cart->save();

			return response()->json([
				'success' => true,
				'message' => 'Item removed successfully!',
				'items count' => $cart->cart_items_count
			]);

		} catch (Exception $e) {
			Log::error('Failed to remove item from cart', [
				'user_id' => $user->user_id,
				'cart_id' => $cartId,
				'product_id' => $productId,
				'error' => $e->getMessage()
			]);
			return response()->json([
				'success' => false,
				'message' => 'Failed to remove item!'
			], 500);
		}
	}

	public function updateItemQuantity(Request $request)
	{
		try {
			$user = Auth::user();
			$cartId = $user->cart_id;
			$productId = $request->input('product_id');
			$quantity = $request->input('quantity');

			$cartItem = CartItem::where('cart_id', $cartId)
				->where('product_id', $productId)
				->first();

			if ($cartItem) {
				if ($quantity <= 0) {
					return response()->json([
						'success' => false,
						'message' => 'Invalid quantity'
					], 400);
				}

				$cartItem->cart_items_quantity = $quantity;
				$cartItem->save();

				// Update the items count
				$cart = Cart::find($cartId);
				$cart->cart_items_count = CartItem::where('cart_id', $cartId)
					->sum('cart_items_quantity');

				return response()->json([
					'success' => true,
					'message' => 'Cart item quantity updated successfully'
				]);
			}

			return response()->json([
				'success' => false,
				'message' => 'Item is not in cart'
			], 400);

		} catch (Exception $e) {
			return response()->json([
				'success' => false,
				'message' => 'Failed to update cart item quantity'
			], 500);
		}
	}

	public function updateCartItems(Request $request)
	{
		try {
			$user = Auth::user();
			$cartId = $user->cart_id;
			$items = $request->input('items');

			// Store voucher information in session to use later in CheckOut page
			session([
				'voucher_id' => $request->input('voucher_id'),
				'voucher_name' => $request->input('voucher_name'),
				'voucher_discount' => $request->input('voucher_discount')
			]);

			// Validate input format
			if (!is_array($items)) {
				return response()->json([
					'success' => false,
					'message' => 'Invalid input format'
				], 400);
			}

			$inStockItems = [];

			foreach ($items as $item) {
				$productId = $item['product_id'];
				$quantity = $item['quantity'];

				$cartItem = CartItem::where('cart_id', $cartId)
					->where('product_id', $productId)
					->first();

				// Check product stock
				$product = Product::find($productId);
				if ($cartItem && $product && $product->getAttribute('stock_quantity') > 0) {
					$cartItem->quantity = $quantity;
					$cartItem->save();
					$inStockItems[] = $productId;
				}
			}
			$cart = Cart::find($cartId);
			$cart->items_count = count($inStockItems);
			$cart->save();

			return response()->json([
				'success' => true,
				'message' => 'Cart updated successfully',
				'updated_items' => $inStockItems,
				'items_count' => $cart->items_count
			]);

		} catch (Exception $e) {
			return response()->json([
				'success' => false,
				'message' => 'Failed to update cart and cart items'
			], 500);
		}
	}

	public function updateItemSize(Request $request)
	{
		// Validate input
		try {
			$validatedData = $request->validate([
				'product_id' => 'required|integer',
				'size' => 'required|string|max:255'
			]);
		} catch (Exception $e) {
			return response()->json([
				'success' => false,
				'message' => 'Invalid input data',
				'errors' => $e->getMessage()
			], 400);
		}

		try {
			$user = Auth::user();
			$cartId = $user->cart_id;
			$productId = $validatedData['product_id'];
			$size = $validatedData['size'];

			$cartItem = CartItem::with(['product'])->where('cart_id', $cartId)
				->where('product_id', $productId)
				->first();

			if ($cartItem) {
				// Check if the product has size options
				if ($cartItem->product->product_prices_sizes) {
					Log::debug('Product has size options', ['product_id' => $productId]);
					$sizes = $cartItem->product->product_prices_sizes->sizes;
					$selectedIndex = array_search($size, $sizes);
					if ($selectedIndex === false) {
						return response()->json([
							'success' => false,
							'message' => 'Size is not available for this product'
						], 400);
					}
					$selectedPrice = $cartItem->product->product_prices_sizes->prices[$selectedIndex];
					$cartItem->cart_items_unitprice = $selectedPrice;
					$cartItem->cart_items_size = $size;
				}
				$cartItem->cart_items_size = $size;
				$cartItem->save();

				return response()->json([
					'success' => true,
					'message' => 'Cart item size updated successfully'
				]);
			}

			return response()->json([
				'success' => false,
				'message' => 'Item is not in cart'
			], 400);

		} catch (Exception $e) {
			return response()->json([
				'success' => false,
				'message' => 'Failed to update cart item size'
			], 500);
		}
	}
}


