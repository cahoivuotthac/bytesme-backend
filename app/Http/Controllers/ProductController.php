<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use App\Models\Product;

class ProductController extends Controller
{
	public function getHomePageProducts(Request $request)
	{
		$request->validate([
			'limit' => 'nullable|integer|min:1|max:100',
		]);

		function processProducts($products)
		{
			return $products->map(function ($product) {
				$product->product_image_url = $product->product_images->first()->product_image_url ?? null;
				unset($product->product_images);

				$product->product_sizes = $product->sizes;
				$product->product_prices = $product->prices;
				unset($product->product_unit_price);
				unset($product->created_at);
				unset($product->updated_at);
				unset($product->product_band);
				return $product;
			});
		}

		$limit = $request->input('limit', 1);
		try {
			// Best selling products
			$bestSellers = Product::with([
				'product_images' => function ($query) {
					$query->where('product_image_type', 1)
						->select('product_id', 'product_image_url')
						->limit(1);
				}
			])
				->orderBy('product_total_orders', 'desc')
				->take($limit)
				->get()
				->makeHidden('product_description');

			$bestSellers = processProducts($bestSellers);

			// Highest rated products 
			$topRated = Product::with([
				'product_images' => function ($query) {
					$query->where('product_image_type', 1)
						->select('product_id', 'product_image_url')
						->limit(1);
				}
			])
				->where('product_overall_stars', '>', 0)
				->orderBy('product_overall_stars', 'desc')
				->take($limit)
				->get()
				->makeHidden('product_description');

			$topRated = processProducts($topRated);

			// Discounted products
			$discounted = Product::with([
				'product_images' => function ($query) {
					$query->where('product_image_type', 1)
						->select('product_id', 'product_image_url')
						->limit(1);
				}
			])
				->where('product_discount_percentage', '>', 0)
				->orderBy('product_discount_percentage', 'desc')
				->take($limit)
				->get()
				->makeHidden('product_description');

			$discounted = processProducts($discounted);

			$newProducts = Product::with([
				'product_images' => function ($query) {
					$query->where('product_image_type', 1)
						->select('product_id', 'product_image_url')
						->limit(1);
				}
			])
				->orderBy('created_at', 'desc')
				->take($limit)
				->get()
				->makeHidden('product_description');

			$newProducts = processProducts($newProducts);

			return response()->json([
				'best_sellers' => $bestSellers,
				'top_rated' => $topRated,
				'discounted' => $discounted,
				'new_products' => $newProducts,
			]);

		} catch (Exception $e) {
			Log::error('getHomePageProducts error: ' . $e->getMessage());
			return response()->json([
				'message' => 'Error fetching homepage products'
			], 500);
		}
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

	public function searchProductsRag(Request $request)
	{
		try {

			$request->validate([
				'query' => 'required|string|max:255',
			]);
		} catch (Exception $e) {
			Log::info('searchProductsRag error: ' . $e->getMessage());
			return response()->json([
				'message' => 'Bad input data: ' . $e->getMessage(),
			], 400);
		}

		$query = $request->input('query');

		// Forward request to Flask API (BytesMe Intelligence service)
		$baseUrl = config('services.bytesme_intelligence.base_url', 'http://localhost:5000');
		$response = Http::withHeaders([
			'Content-Type' => 'application/json',
		])
			->withOptions([
				'stream' => true,
				'timeout' => 120,
				'http_errors' => false, // Prevent exceptions on HTTP errors
			])
			->get("{$baseUrl}/product/search/rag", [
				'query' => $query
			]);

		// Stream response back to client and log the full LLM output
		return response()->stream(function () use ($response) {
			$stream = $response->getBody();

			while (!$stream->eof()) {
				$chunk = $stream->read(1024);
				echo $chunk;
				flush(); // Send output immediately
			}

			$stream->close();
		}, 200, ['Content-Type' => 'text/event-stream']);
	}

	public function searchProductsSemantics(Request $request)
	{
		try {
			$request->validate([
				'query' => 'required|string|max:255',
				'offset' => 'nullable|integer|min:0',
				'limit' => 'nullable|integer|min:1|max:100',
			]);
		} catch (Exception $e) {
			Log::info('searchProductsSemantic bad input data: ' . $e->getMessage());
			return response()->json([
				'message' => 'Bad input data: ' . $e->getMessage(),
			], 400);
		}

		$query = $request->input('query');
		$offset = $request->input('offset', 0);
		$limit = $request->input('limit', 10) + 1;

		// Forward request to Flask API (BytesMe Intelligence service)
		$baseUrl = config('services.bytesme_intelligence.base_url', 'http://localhost:5000');
		try {
			$response = Http::withHeaders([
				'Content-Type' => 'application/json',
			])
				->get("{$baseUrl}/product/search/semantics", [
					'query' => $query,
					'offset' => $offset,
					'limit' => $limit + 1,
				]);
			$rawData = $response->json();

			$processedProducts = array_map(function ($product) {
				unset($product['description']);
				$prices = $product['price'] = explode('|', $product['sizes_prices']['product_prices']);
				$firstPrice = $prices[0] ?? 0;
				$product['price'] = $firstPrice;
				unset($product['sizes_prices']);
				return $product;
			}, $rawData);

			// Deteremine if the user has wishlisted products
			$userId = Auth::user()->user_id ?? null;
			if ($userId) {
				$productIds = array_column($processedProducts, 'product_id');
				$wishlistedIds = \App\Models\Wishlist::where('user_id', $userId)
					->whereIn('product_id', $productIds)
					->pluck('product_id')
					->toArray();

				$processedProducts = array_map(function ($product) use ($wishlistedIds) {
					$product['is_favorited'] = in_array($product['product_id'], $wishlistedIds);
					return $product;
				}, $processedProducts);
			} else {
				$processedProducts = array_map(function ($product) {
					$product['is_favorited'] = false;
					return $product;
				}, $processedProducts);
			}

			return response()->json([
				'products' => $processedProducts,
				'has_more' => count($processedProducts) > $limit,
			]);
		} catch (Exception $e) {
			Log::error('searchProductsSemantics error: ' . $e->getMessage());
			return response()->json([
				'message' => 'Error when performing semantical search on products'
			], 500);
		}
	}

	public function getRelatedProductsCoOccur(Request $request)
	{
		try {
			$request->validate([
				'product_ids' => 'required|string', // comma-separated product IDs
				'limit' => 'nullable|integer|min:1|max:100',
			]);
		} catch (Exception $e) {
			Log::debug('getRelatedProductsCoOccur error: ' . $e->getMessage());
			return response()->json([
				'message' => 'Invalid input format: ' . $e->getMessage(),
			], 400);
		}

		// Parse comma-separated product IDs into array
		$productIds = $request->input('product_ids');
		$limit = $request->input('limit', 5);

		try {
			// Forward request to Flask API (BytesMe Intelligence service)
			$baseUrl = config('services.bytesme_intelligence.base_url', 'http://localhost:5000');
			// Send each product_id as a separate query param
			$response = Http::withHeaders([
				'Content-Type' => 'application/json',
			])
				->get("{$baseUrl}/product/related/co-occur", [
					'product_ids' => $productIds,
					'top_k' => $limit,
				]);

			if ($response->failed()) {
				Log::error('Error fetching related products from co-occurrence API: ' . $response->body());
				return response()->json([
					'message' => 'Error fetching related products'
				], 500);
			}

			$data = $response->json();
			foreach ($data as $key => $productIds) {
				$products = Product::whereIn('product_id', $productIds)
					->pluck('product_name', 'product_id')
					->toArray();

				$data[$key] = array_map(function ($id) use ($products) {
					return [
						'product_id' => $id,
						'product_name' => $products[$id] ?? null,
					];
				}, $productIds);
			}
			Log::debug('getRelatedProductsCoOccur enriched data: ' . json_encode($data));

			return response()->json($data);
		} catch (Exception $e) {
			Log::error('getRelatedProductsCoOccur error: ' . $e->getMessage());
			return response()->json([
				'message' => 'Error when fetching related products by co-occurrence'
			], 500);
		}
	}

	public function getRelatedProductsSemantics(Request $request)
	{
		try {
			$request->validate([
				'product_id' => 'required|integer|exists:products,product_id',
				'limit' => 'nullable|integer|min:1|max:100',
			]);
		} catch (Exception $e) {
			Log::debug('getRelatedProductsSemantics error: ' . $e->getMessage());
			return response()->json([
				'message' => 'Invalid input format: ' . $e->getMessage(),
			], 400);
		}

		$productId = $request->input('product_id');
		$productCode = Product::findOrFail($productId)->product_code;
		$limit = $request->input('limit', 5);

		try {
			// Forward request to Flask API (BytesMe Intelligence service)
			$baseUrl = config('services.bytesme_intelligence.base_url', 'http://localhost:5000');
			$response = Http::withHeaders([
				'Content-Type' => 'application/json',
			])
				->get("{$baseUrl}/product/related/semantics", [
					'product_code' => $productCode,
					'top_k' => $limit,
				]);

			if ($response->failed()) {
				Log::error('Error fetching related products from semantics API: ' . $response->body());
				return response()->json([
					'message' => 'Error fetching related products'
				], 500);
			}

			return response()->json($response->json());
		} catch (Exception $e) {
			Log::error('getRelatedProductsSemantics error: ' . $e->getMessage());
			return response()->json([
				'message' => 'Error when fetching related products by semantics'
			], 500);
		}
	}
}