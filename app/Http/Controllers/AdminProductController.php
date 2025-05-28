<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\ProductImage;
use Illuminate\Support\Facades\Log;

class AdminProductController extends Controller
{
	public function showProductsPage(Request $request)
	{
		// Get filter parameters
		$category = $request->input('category');
		$stock = $request->input('stock');
		$sort = $request->input('sort', 'created_at');
		$direction = $request->input('direction', 'desc');

		// Base query
		$query = Product::with(['product_images', 'category']);

		// Apply category filter
		if ($category) {
			$query->where('category_id', $category);
		}

		// Apply stock filter
		if ($stock) {
			switch ($stock) {
				case 'in_stock':
					$query->where('product_stock_quantity', '>', 10);
					break;
				case 'low_stock':
					$query->whereBetween('product_stock_quantity', [1, 10]);
					break;
				case 'out_of_stock':
					$query->where('product_stock_quantity', 0);
					break;
			}
		}

		// Apply search if provided
		if ($request->filled('search')) {
			$searchTerm = $request->input('search');
			$searchType = $request->input('type', 'product_code');

			$query->where(function ($q) use ($searchTerm, $searchType) {
				if (in_array($searchType, ['product_code', 'product_name', 'product_description'])) {
					$q->where($searchType, 'LIKE', "%{$searchTerm}%");
				}
			});
		}

		// Apply sorting
		switch ($sort) {
			case 'code':
				$query->orderBy('product_code', $direction);
				break;
			case 'description':
				$query->orderBy('product_description', $direction);
				break;
			case 'stock':
				$query->orderBy('product_stock_quantity', $direction);
				break;
			case 'sold':
				$query->orderBy('product_total_orders', $direction);
				break;
			case 'rating':
				$query->orderBy('product_overall_stars', $direction);
				break;
			// case 'price':
			// 	$query->orderBy('price', $direction);
			// 	break;
			default:
				$query->orderBy('created_at', $direction);
		}

		// Get paginated results
		$products = $query->paginate(10)->withQueryString();

		// Get all categories for filter dropdown
		$categories = Category::all();

		return view('admin.products.index', compact(
			'products',
			'categories'
		));
	}

	public function getDetails($product_id)
	{
		$product = Product::with(['product_images', 'category'])
			->findOrFail($product_id);

		Log::debug('Product details: ' . json_encode($product));

		return response()->json([
			'success' => true,
			'product' => $product,
		]);
	}

	public function updateField(Request $request, $product_id)
	{
		$request->validate([
			'field' => 'required|string',
			'value' => 'required'
		]);

		$product = Product::findOrFail($product_id);
		$field = $request->input('field');
		$value = $request->input('value');

		// Validate and sanitize the field
		switch ($field) {
			case 'product_description':
				$value = Str::limit($value, 1000); // Limit description length
				break;
			case 'product_stock_quantity':
				$value = max(0, intval($value)); // Ensure non-negative integer
				break;
			case 'price':
				$value = max(0, intval($value)); // Ensure non-negative integer
				break;
			default:
				return response()->json([
					'success' => false,
					'message' => 'Invalid field'
				], 400);
		}

		try {
			$product->update([$field => $value]);

			return response()->json([
				'success' => true,
				'message' => 'Updated successfully'
			]);
		} catch (\Exception $e) {
			return response()->json([
				'success' => false,
				'message' => 'Update failed'
			], 500);
		}
	}

	public function update(Request $request, $product_id)
	{
		Log::debug('AdminProductController@update: update(): ' . json_encode($request->all()));
		try {
			$request->validate([
				'product_name' => 'nullable|string',
				'product_description' => 'nullable|string',
				'product_discount_percentage' => 'numeric',
				'product_stock_quantity' => 'nullable|integer|min:0',
				'category_id' => 'required|exists:categories,category_id',
				'sizes' => 'array',
				'prices' => 'array',
				'sizes.*' => 'string',
				'prices.*' => 'numeric',
			]);
		} catch (\Exception $e) {
			Log::error('AdminProductController@update: validation error: ' . $e->getMessage());
			Log::debug('Request data: ', ['data' => $request->all()]);
			return redirect()->back()->with('error', 'invalid_input: ' . $e->getMessage());
		}

		$product = Product::findOrFail($product_id);
		$data = $request->all();
		Log::debug('Request data ' . json_encode($data));

		DB::beginTransaction();
		try {
			// Prepare sizes, prices, and stocks data as JSON for product_unit_price
			$unitPriceData = [];

			if (isset($data['sizes']) && isset($data['prices'])) {
				$sizes = [];
				$prices = [];

				for ($i = 0; $i < count($data['sizes']); $i++) {
					if (!empty($data['sizes'][$i])) {
						$sizes[] = $data['sizes'][$i];
						$prices[] = $data['prices'][$i];
					}
				}

				$unitPriceData = [
					'product_sizes' => implode('|', $sizes),
					'product_prices' => implode('|', $prices),
				];
			}

			Log::debug("here");

			// Update product
			$product->update([
				'product_id' => $product->product_id, // Keep the same ID
				'category_id' => $data['category_id'],
				'product_name' => $data['product_name'],
				'product_description' => $data['product_description'],
				'product_discount_percentage' => $data['product_discount_percentage'] ?? 0,
				'product_unit_price' => $unitPriceData,
				'product_stock_quantity' => $data['product_stock_quantity'] ?? 0,
			]);

			Log::debug('Updated product: ' . json_encode($product));

			// Update images
			if ($request->hasFile('images')) {
				Log::debug('Request has images');
				$images = $request->file('images');
				$product->product_images()->delete();
				Log::debug('Deleted old images');
				foreach ($images as $image) {
					$path = $image->store('products', 'public');
					ProductImage::create([
						'product_image_id' => ProductImage::max('product_image_id') + 1,
						'product_id' => $product->product_id,
						'product_image_url' => '/storage/' . $path,
						'image_type' => 1
					]);
				}
				Log::debug('Added new images');
			}

			Log::debug('AdminProductController@update: product updated successfully: ' . json_encode($product));

			DB::commit();
			return redirect()->back()->with('success', 'Cập nhật sản phẩm thành công');
		} catch (\Exception $e) {
			Log::error('AdminProductController@update: update error: ' . $e->getMessage());
			DB::rollBack();
			return redirect()->back()->withErrors('Có lỗi khi cập nhật sản phẩm.');
		}
	}

	public function getAllCategories()
	{
		try {
			$categories = Category::all();
			return response()->json($categories);
		} catch (\Exception $e) {
			Log::error('AdminProductController@getAllCategories: ' . $e->getMessage());
			return response()->json([
				'success' => false,
				'message' => 'Failed to fetch categories'
			], 500);
		}
	}

	public function createProduct(Request $request)
	{
		try {
			$request->validate([
				'product_name' => 'required|string|max:255',
				'product_description' => 'nullable|string',
				'product_discount_percentage' => 'nullable|numeric|min:0|max:100',
				'product_stock_quantity' => 'required|integer|min:0',
				'category_id' => 'required|exists:categories,category_id',
				'sizes' => 'array',
				'sizes.*' => 'string',
				'prices' => 'array',
				'prices.*' => 'numeric',
			]);
		} catch (\Exception $e) {
			Log::error('AdminProductController@createProduct: validation error: ' . $e->getMessage());
			return redirect()->back()->with('error', 'invalid_input: ' . $e->getMessage());
		}

		$data = $request->all();
		Log::debug('Create product request data: ' . json_encode($data));

		DB::beginTransaction();
		try {
			// Generate unique product ID
			// $productId = 'PROD_' . strtoupper(Str::random(8));
			$productId = Product::max('product_id') + 1;

			// Prepare sizes and prices data as JSON for product_unit_price
			$unitPriceData = [];
			if (isset($data['sizes']) && isset($data['prices'])) {
				$sizes = [];
				$prices = [];

				for ($i = 0; $i < count($data['sizes']); $i++) {
					if (!empty($data['sizes'][$i])) {
						$sizes[] = $data['sizes'][$i];
						$prices[] = $data['prices'][$i];
					}
				}

				$unitPriceData = [
					'product_sizes' => implode('|', $sizes),
					'product_prices' => implode('|', $prices),
				];
			}

			// Create new product
			$product = Product::create([
				'product_id' => $productId,
				'product_code' => $productId, // Use same as product_id for now
				'product_name' => $data['product_name'],
				'product_description' => $data['product_description'],
				'product_discount_percentage' => $data['product_discount_percentage'] ?? 0,
				'product_stock_quantity' => $data['product_stock_quantity'] ?? 0,
				'category_id' => $data['category_id'],
				'product_unit_price' => $unitPriceData,
			]);

			// Handle images
			if ($request->hasFile('images')) {
				Log::debug('Request has images for new product');
				$images = $request->file('images');
				foreach ($images as $image) {
					$path = $image->store('products', 'public');
					ProductImage::create([
						'product_image_id' => ProductImage::max('product_image_id') + 1,
						'product_id' => $product->product_id,
						'product_image_url' => '/storage/' . $path,
						'image_type' => 1
					]);
				}
				Log::debug('Added images for new product');
			}

			DB::commit();
			return redirect()->back()->with('success', 'Thêm sản phẩm thành công');
		} catch (\Exception $e) {
			Log::error('AdminProductController@createProduct: error: ' . $e->getMessage());
			DB::rollBack();
			return redirect()->back()->with('error', 'Có lỗi khi thêm sản phẩm: ' . $e->getMessage());
		}
	}
}