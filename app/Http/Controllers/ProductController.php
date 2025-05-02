<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ProductController extends Controller
{
	function getHomePageProducts(Request $request)
	{
		$products = \App\Models\Product::with(['product_categories.category', 'product_ingredients.ingredient'])
			->where('is_active', true)
			->orderBy('created_at', 'desc')
			->take(10)
			->get();

		return response()->json($products);
	}
}
