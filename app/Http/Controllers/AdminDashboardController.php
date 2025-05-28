<?php

namespace App\Http\Controllers;
use App\Models\Order;
use App\Models\User;
use App\Models\OrderItem;
use App\Models\ProductImage;
use App\Models\Product;
use App\Models\OrderFeedback;
use App\Models\Category;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AdminDashboardController extends Controller
{
	public function showDashboardPage()
	{
		// Get current date and time periods
		$today = Carbon::today();
		$thisMonth = Carbon::now()->startOfMonth();
		$lastMonth = Carbon::now()->subMonth()->startOfMonth();
		$thisYear = Carbon::now()->startOfYear();

		// Key metrics
		$totalRevenue = Order::where('order_is_paid', true)->sum('order_total_price');
		$todayRevenue = Order::where('order_is_paid', true)
			->whereDate('created_at', $today)
			->sum('order_total_price');
		$monthlyRevenue = Order::where('order_is_paid', true)
			->where('created_at', '>=', $thisMonth)
			->sum('order_total_price');

		$totalOrders = Order::count();
		$todayOrders = Order::whereDate('created_at', $today)->count();
		$monthlyOrders = Order::where('created_at', '>=', $thisMonth)->count();

		$totalCustomers = User::where('role_type', 0)->count();
		$newCustomersToday = User::where('role_type', 0)
			->whereDate('created_at', $today)
			->count();

		$totalProducts = Product::count();
		$averageRating = OrderFeedback::avg('num_star') ?? 0;

		// Chart data - Daily revenue for last 30 days
		$dailyRevenue = Order::where('order_is_paid', true)
			->where('created_at', '>=', Carbon::now()->subDays(30))
			->selectRaw('DATE(created_at) as date, SUM(order_total_price) as total')
			->groupBy('date')
			->orderBy('date')
			->get();

		// Order status distribution
		$orderStatusData = Order::selectRaw('order_status, COUNT(*) as count')
			->groupBy('order_status')
			->get();

		// Top selling products
		$topProducts = OrderItem::join('products', 'order_items.product_id', '=', 'products.product_id')
			->selectRaw('products.product_name, SUM(order_items.order_items_quantity) as total_sold')
			->groupBy('products.product_id', 'products.product_name')
			->orderByDesc('total_sold')
			->limit(5)
			->get();

		// Monthly comparison
		$currentMonthRevenue = Order::where('order_is_paid', true)
			->whereBetween('created_at', [$thisMonth, Carbon::now()])
			->sum('order_total_price');

		$lastMonthRevenue = Order::where('order_is_paid', true)
			->whereBetween('created_at', [$lastMonth, $thisMonth])
			->sum('order_total_price');

		$revenueGrowth = $lastMonthRevenue > 0
			? (($currentMonthRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100
			: 0;

		// Recent orders
		$recentOrders = Order::with(['user', 'order_items.product'])
			->orderByDesc('created_at')
			->limit(5)
			->get();

		// Product category distribution (for spider chart)
		$categoryDistribution = OrderItem::join('products', 'order_items.product_id', '=', 'products.product_id')
			->join('categories', 'products.category_id', '=', 'categories.category_id')
			->selectRaw('categories.category_name, SUM(order_items.order_items_quantity) as total_quantity, COUNT(DISTINCT order_items.order_id) as order_count')
			->groupBy('categories.category_id', 'categories.category_name')
			->orderByDesc('total_quantity')
			->get();

		// Hourly order patterns (for peak hours identification)
		$hourlyOrders = Order::selectRaw('EXTRACT(HOUR FROM created_at) as hour, COUNT(*) as order_count, AVG(order_total_price) as avg_order_value')
			->where('created_at', '>=', Carbon::now()->subDays(30))
			->groupBy(DB::raw('EXTRACT(HOUR FROM created_at)'))
			->orderBy('hour')
			->get();

		// Fill missing hours with 0 values
		$hourlyOrdersComplete = collect(range(0, 23))->map(function ($hour) use ($hourlyOrders) {
			$existing = $hourlyOrders->firstWhere('hour', $hour);
			return [
				'hour' => $hour,
				'order_count' => $existing ? $existing->order_count : 0,
				'avg_order_value' => $existing ? $existing->avg_order_value : 0
			];
		});

		return view('admin.dashboard.index', compact(
			'totalRevenue',
			'todayRevenue',
			'monthlyRevenue',
			'totalOrders',
			'todayOrders',
			'monthlyOrders',
			'totalCustomers',
			'newCustomersToday',
			'totalProducts',
			'averageRating',
			'dailyRevenue',
			'orderStatusData',
			'topProducts',
			'revenueGrowth',
			'recentOrders',
			'categoryDistribution',
			'hourlyOrdersComplete'
		));
	}
}