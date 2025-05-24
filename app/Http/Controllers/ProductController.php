<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\Product;

class ProductController extends Controller
{
	function getHomePageProducts(Request $request)
	{
		$products = Product::with(['product_categories.category', 'product_ingredients.ingredient'])
			->where('is_active', true)
			->orderBy('created_at', 'desc')
			->take(10)
			->get();

		return response()->json($products);
	}

	public function getProductsByCategory(Request $request)
	{
		Log::info("Fuck you");

		try {
			$request->validate([
				'category_id' => 'required|integer|exists:categories,category_id',
				'offset' => 'nullable|integer|min:0',
				'limit' => 'nullable|integer|min:1|max:100',
				'user_id' => 'nullable|integer|exists:users,user_id'
			]);
		} catch (Exception $e) {
			Log::debug('getProductsByCategory error: ' . $e->getMessage());
			return response()->json([
				'message' => 'Invalid request: ' . $e->getMessage(),
			], 400);
		}

		$offset = $request->input('offset', 0);
		$limit = $request->input('limit', 10);
		$categoryId = (int) $request->input('category_id');

		Log::debug("request input: " . json_encode($request->all()));

		try {
			$userId = Auth::user()->user_id;
			$query = Product::where('category_id', $categoryId)
				->with([
					'product_images' => function ($query) {
						$query->where('product_image_type', 1)
							->select('product_id', 'product_image_url')
							->limit(1);
					}
				])
				->orderBy('product_name', 'asc');

			// Fetch one extra to check if there are more products
			$products = $query->skip($offset)
				->take($limit + 1)
				->get();

			$hasMore = $products->count() > $limit;
			$products = $products->take($limit)->map(function ($product) use ($userId) {
				$prices = $product->prices;
				$product_price = $prices[0];
				$product->product_price = $product_price;
				$product->product_image_url = $product->product_images->first()->product_image_url ?? null;
				unset($product->product_unit_price);
				unset($product->product_images);

				// Add is_wishlisted field
				if ($userId) {
					$product->is_wishlisted = $product->wishlist()
						->where('user_id', $userId)
						->exists();
				} else {
					$product->is_wishlisted = false;
				}

				return $product;
			});

			Log::debug('SQL:', [$query->toSql()]);
			Log::debug('Products:', $products->toArray());
			Log::debug('getProductsByCategory products: ' . json_encode($products));
			return response()->json([
				'products' => $products,
				'has_more' => $hasMore,
			]);
		} catch (Exception $e) {
			Log::debug('getProductsByCategory error: ' . $e->getMessage());
			return response()->json([
				'message' => 'Error fetching products: ' . $e->getMessage(),
			], 500);
		}
	}

	public function getProductDetails(Request $request)
	{
		try {
			$request->validate([
				'product_id' => 'required|integer|exists:products,product_id',
			]);
		} catch (Exception $e) {
			Log::debug('getProductDetail error: ' . $e->getMessage());
			return response()->json([
				'message' => 'Invalid input format: ' . $e->getMessage(),
			], 400);
		}

		$productId = (int) $request->input('product_id');
		$userId = Auth::user()->user_id;

		try {
			$product = Product::with([
				'product_images' => function ($query) {
					$query->where('product_image_type', 1)
						->select('product_id', 'product_image_url');
				},
				'category',
			])
				->where('product_id', $productId)
				->firstOrFail();

			// Attach is_favorited field
			if ($userId) {
				$product->is_favorited = $product->wishlist()
					->where('user_id', $userId)
					->exists();
			} else {
				$product->is_favorited = false;
			}

			$product['prices'] = $product->prices;
			$product['sizes'] = $product->sizes;
			$product['image_urls'] = $product->product_images->pluck('product_image_url');
			$product->makeHidden('product_unit_price');
			unset($product->product_images);

			return response()->json($product);
		} catch (Exception $e) {
			Log::debug('getProductDetails error: ' . $e->getMessage());
			return response()->json([
				'message' => 'Error when fetching product details'
			], 500);
		}
	}

	public function getProductFeedbacks(Request $request)
	{
		try {
			$request->validate([
				'product_id' => 'required|integer|exists:products,product_id',
				'offset' => 'nullable|integer|min:0',
				'limit' => 'nullable|integer|min:1|max:100',
			]);
		} catch (Exception $e) {
			Log::debug('getProductFeedbacks error: ' . $e->getMessage());
			return response()->json([
				'message' => 'Invalid input format: ' . $e->getMessage(),
			], 400);
		}

		$productId = $request->input('product_id');
		try {

			$offset = $request->input('offset', 0);
			$limit = $request->input('limit', 10);

			$product = Product::where('product_id', $productId)
				->with([
					'product_feedbacks' => function ($query) use ($offset, $limit) {
						$query->skip($offset)->take($limit + 1);
						$query->with([
							'feedback_images' => function ($query) {
								$query->select('order_feedback_id', 'feedback_image');
							},
						]);
						$query->with([
							'user' => function ($query) {
								$query->select('user_id', 'name', 'avatar');
							}
						]);
					},
				])
				->first();

			$feedbacks = $product->product_feedbacks;
			$hasMore = $feedbacks->count() > $limit;
			$feedbacks = $feedbacks->take($limit);

			Log::info('Product ' . $productId . ' feedbacks : ' . json_encode($feedbacks));
			return response()->json([
				'product_feedbacks' => $feedbacks,
				'has_more' => $hasMore
			]);
		} catch (Exception $e) {
			Log::error('getProductFeedbacks error: ' . $e->getMessage());
			return response()->json([
				'message' => 'Error when fetching product feedbacks'
			], 500);
		}
	}

	public function getRelatedProducts(Request $request)
	{
		try {
			$request->validate([
				'product_id' => 'required|integer|exists:products,product_id',
				'limit' => 'nullable|integer|min:1|max:100',
			]);
		} catch (Exception $e) {
			Log::debug('getRelatedProducts error: ' . $e->getMessage());
			return response()->json([
				'message' => 'Invalid input format: ' . $e->getMessage(),
			], 400);
		}

		$productId = $request->input('product_id');
		$limit = $request->input('limit', 10);

		try {
			// Get the category of the product
			$product = Product::findOrFail($productId);
			$categoryId = $product->category_id;

			// Get random products from the same category, excluding the current product
			$relatedProducts = Product::where('category_id', $categoryId)
				->where('product_id', '!=', $productId)
				->with([
					'product_images' => function ($query) {
						$query->where('product_image_type', 1)
							->select('product_id', 'product_image_url')
							->limit(1);
					}
				])
				->inRandomOrder()
				->take($limit)
				->get()
				->map(function ($product) {
					$product->product_image_url = $product->product_images->first()->product_image_url ?? null;
					unset($product->product_images);
					return $product;
				});

			return response()->json($relatedProducts);
		} catch (Exception $e) {
			Log::error('getRelatedProducts error: ' . $e->getMessage());
			return response()->json([
				'message' => 'Error when fetching related products'
			], 500);
		}
	}
}