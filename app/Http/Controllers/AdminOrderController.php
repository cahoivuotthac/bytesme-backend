<?php

namespace App\Http\Controllers;

use App\Constants;
use Illuminate\Http\Request;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AdminOrderController extends Controller
{
	private function generateSalesData($startDate, $endDate, $format)
	{
		$dateRange = [];
		$current = clone $startDate;

		while ($current <= $endDate) {
			$dateRange[$current->format('Y-m-d')] = 0;
			$current->addDay();
		}

		$orders = Order::where('order_is_paid', true)
			->whereBetween('created_at', [$startDate, $endDate])
			->selectRaw('DATE(created_at) as date, SUM(order_total_price) as total')
			->groupBy('date')
			->get();

		foreach ($orders as $order) {
			$dateRange[$order->date] = $order->total;
		}

		$labels = [];
		$values = [];

		foreach ($dateRange as $date => $total) {
			$labels[] = Carbon::parse($date)->format($format);
			$values[] = $total;
		}

		return [
			'labels' => $labels,
			'values' => $values
		];
	}

	private function generateOrderStatusData()
	{
		$statuses = ['pending', 'delivering', 'delivered', 'cancelled'];
		$counts = [];

		foreach ($statuses as $status) {
			$counts[] = Order::where('order_status', $status)->count();
		}

		return $counts;
	}

	private function generateDeliveryTimeData($startDate, $endDate)
	{
		$deliveryTypes = [
			'Tiền ship >= 30k' => Order::where('order_status', 'delivered')
				->whereBetween('created_at', [$startDate, $endDate])
				->where('order_deliver_cost', '>=', 30000)
				->selectRaw('AVG(EXTRACT(EPOCH FROM (order_deliver_time - created_at)) / 60) as avg_minutes')
				->first()->avg_minutes ?? 0,

			'Phí ship < 30k' => Order::where('order_status', 'delivered')
				->whereBetween('created_at', [$startDate, $endDate])
				->where('order_deliver_cost', '<', 30000)
				->selectRaw('AVG(EXTRACT(EPOCH FROM (order_deliver_time - created_at)) / 60) as avg_minutes')
				->first()->avg_minutes ?? 0,

			'Đơn hàng lớn' => Order::where('order_status', 'delivered')
				->whereBetween('created_at', [$startDate, $endDate])
				->where('order_total_price', '>=', 500000)
				->selectRaw('AVG(EXTRACT(EPOCH FROM (order_deliver_time - created_at)) / 60) as avg_minutes')
				->first()->avg_minutes ?? 0,

			'Đơn hàng nhỏ' => Order::where('order_status', 'delivered')
				->whereBetween('created_at', [$startDate, $endDate])
				->where('order_total_price', '<', 500000)
				->selectRaw('AVG(EXTRACT(EPOCH FROM (order_deliver_time - created_at)) / 60) as avg_minutes')
				->first()->avg_minutes ?? 0,
		];

		return [
			'labels' => array_keys($deliveryTypes),
			'values' => array_values($deliveryTypes)
		];
	}

	private function generatePaymentMethodData($startDate, $endDate)
	{
		$codCount = Order::whereBetween('created_at', [$startDate, $endDate])
			->where('order_payment_method', Constants::PAYMENT_METHOD_COD)
			->count();

		$momoCount = Order::whereBetween('created_at', [$startDate, $endDate])
			->where('order_payment_method', Constants::PAYMENT_METHOD_MOMO)
			->count();

		$otherCount = Order::whereBetween('created_at', [$startDate, $endDate])
			->whereNotIn('order_payment_method', [Constants::PAYMENT_METHOD_COD, Constants::PAYMENT_METHOD_MOMO])
			->count();

		return [
			'values' => [$codCount, $momoCount, $otherCount]
		];
	}

	private function generateCancellationRateData($startDate, $endDate, $period)
	{
		$labels = [];
		$rates = [];
		$interval = $period === 'year' ? '1 month' : '1 day';

		$current = clone $startDate;
		while ($current <= $endDate) {
			$nextDate = (clone $current)->modify("+{$interval}");

			$totalOrders = Order::whereBetween('created_at', [$current, $nextDate])
				->count();

			$cancelledOrders = Order::whereBetween('created_at', [$current, $nextDate])
				->where('order_status', 'cancelled')
				->count();

			$rate = $totalOrders > 0 ? ($cancelledOrders / $totalOrders) * 100 : 0;

			$labels[] = $period === 'year' ? $current->format('M Y') : $current->format('d/m');
			$rates[] = round($rate, 1);

			$current = $nextDate;
		}

		return [
			'labels' => $labels,
			'values' => $rates
		];
	}

	public function showOrdersPage(Request $request)
	{
		$query = Order::query();
		$query->with('user:user_id,name');

		if ($request->has('dateFilterType')) {
			if (
				$request->input('dateFilterType') == 'single'
				&& $request->has('single_date')
				&& $request->input('single_date') != ''
			) {
				$query->whereDate('created_at', $request->input('single_date'));
			} elseif (
				$request->input('dateFilterType') == 'range'
				&& $request->has('date_start') && $request->input('date_start') != ''
				&& $request->has('date_end') && $request->input('date_end') != ''
			) {
				$query->whereBetween('created_at', [$request->input('date_start'), $request->input('date_end')]);
			}
		}

		if ($request->has('order_status') && $request->input('order_status') != '') {
			$query->where('order_status', $request->input('order_status'));
		}

		if ($request->has('order_is_paid') && $request->input('order_is_paid') != '') {
			$query->where('order_is_paid', (int) $request->input('order_is_paid'));
		}

		if ($request->has('order_payment_method') && $request->input('order_payment_method') != '') {
			$query->where('order_payment_method', $request->input('order_payment_method'));
		}

		// Add sorting logic with default sort
		$sortColumn = $request->input('sort', 'created_at');
		$direction = $request->input('direction', 'desc');

		if (in_array($sortColumn, ['order_id', 'order_total_price', 'created_at', 'order_deliver_time'])) {
			$query->orderBy($sortColumn, $direction);
		}

		$orders = $query->orderBy('created_at', 'desc')->paginate(25)->withQueryString();

		// Update newestOrders to include order_items and products
		$newestOrders = Order::where('order_status', 'pending')
			->orWhere('order_status', 'delivering')
			->where(function ($query) {
				$query->where('order_payment_method', Constants::PAYMENT_METHOD_COD)
					->orWhere(function ($query) {
						$query->where('order_payment_method', Constants::PAYMENT_METHOD_MOMO)
							->where('order_is_paid', true);
					});
			})
			->with([
				'user:user_id,name',
				'order_items.product.product_images:product_id,product_image_url',
			])
			->orderBy('created_at', 'desc')
			->take(5)
			->get();
		Log::debug('Newest orders: ', ['newestOrders' => $newestOrders]);

		return view('admin.orders.index', compact(
			'orders',
			'newestOrders',
		));
	}

	private function getSummaryMetrics($startDate, $endDate)
	{
		$periodLenDays = $startDate->diffInDays($endDate) + 1; // +1 to include both start and end dates
		$lastPeriodStart = $startDate->copy()->subDays($periodLenDays);
		$lastPeriodEnd = $endDate->copy();

		// Total revenue for current period
		$totalRevenue = Order::where('order_is_paid', true)
			->whereBetween('created_at', [$startDate, $endDate])
			->sum('order_total_price');

		// Revenue for last period
		$lastPeriodRevenue = Order::where('order_is_paid', true)
			->whereBetween('created_at', [$lastPeriodStart, $lastPeriodEnd])
			->sum('order_total_price');

		// Calculate revenue growth
		$revenueGrowth = $lastPeriodRevenue > 0
			? (($totalRevenue - $lastPeriodRevenue) / $lastPeriodRevenue) * 100
			: 0;

		// Total orders for current period
		$totalOrders = Order::whereBetween('created_at', [$startDate, $endDate])->count();

		// Orders for last period
		$lastPeriodOrders = Order::whereBetween('created_at', [$lastPeriodStart, $lastPeriodEnd])->count();

		// Calculate order growth
		$orderGrowth = $lastPeriodOrders > 0
			? (($totalOrders - $lastPeriodOrders) / $lastPeriodOrders) * 100
			: 0;

		// Average delivery time in minutes for current period
		$deliveryTime = Order::where('order_status', 'delivered')
			->whereBetween('created_at', [$startDate, $endDate])
			->whereNotNull('order_deliver_time')
			->selectRaw("AVG(EXTRACT(EPOCH FROM (order_deliver_time - created_at)) / 60) as avg_minutes")
			->first()->avg_minutes ?? 0;

		// Last period's delivery time in minutes
		$lastPeriodDeliveryTime = Order::where('order_status', 'delivered')
			->whereBetween('created_at', [$lastPeriodStart, $lastPeriodEnd])
			->whereNotNull('order_deliver_time')
			->selectRaw("AVG(EXTRACT(EPOCH FROM (order_deliver_time - created_at)) / 60) as avg_minutes")
			->first()->avg_minutes ?? 0;

		// Calculate delivery time improvement (negative is better)
		$deliveryImprovement = $lastPeriodDeliveryTime > 0
			? (($lastPeriodDeliveryTime - $deliveryTime) / $lastPeriodDeliveryTime) * 100
			: 0;

		// Average order value
		$avgOrderValue = $totalOrders > 0
			? $totalRevenue / $totalOrders
			: 0;

		// Last period's average order value
		$lastPeriodAvgOrderValue = $lastPeriodOrders > 0
			? $lastPeriodRevenue / $lastPeriodOrders
			: 0;

		// Calculate average order value growth
		$avgOrderGrowth = $lastPeriodAvgOrderValue > 0
			? (($avgOrderValue - $lastPeriodAvgOrderValue) / $lastPeriodAvgOrderValue) * 100
			: 0;

		Log::debug("delivery improvement: ", ['deliveryImprovement' => $deliveryImprovement]);

		return [
			'totalRevenue' => $totalRevenue,
			'revenueGrowth' => $revenueGrowth,
			'totalOrders' => $totalOrders,
			'orderGrowth' => $orderGrowth,
			'deliveryTime' => round($deliveryTime, 0), // Round to whole minutes
			'deliveryImprovement' => $deliveryImprovement,
			'avgOrderValue' => $avgOrderValue,
			'avgOrderGrowth' => $avgOrderGrowth
		];
	}

	public function edit($id)
	{
		$order = Order::findOrFail($id);
		return view('admin.orders.edit', compact('order'));
	}

	public function updateOrderField(Request $request): JsonResponse
	{
		$validator = Validator::make($request->all(), [
			'order_id' => 'required|integer|exists:orders,order_id',
			'field' => 'required|string|in:order_status,order_is_paid',
			'value' => 'required'
		]);

		if ($validator->fails()) {
			return response()->json(['success' => false, 'message' => 'Invalid data provided.'], 400);
		}

		$order = Order::findOrFail($request->input('order_id'));

		try {
			if ($request->input('field') === 'order_status') {
				$allowedorder_statusValues = ['online_payment_pending', 'pending', 'delivering', 'delivered', 'cancelled'];
				if (!in_array($request->input('value'), $allowedorder_statusValues)) {
					return response()->json(['success' => false, 'message' => 'Invalid order_status value.'], 400);
				}
				$order->order_status = $request->input('value');
				if ($request->input('value') === 'delivered') {
					$order->deliver_time = now();
				}
			} elseif ($request->input('field') === 'order_is_paid') {
				$value = $request->input('value');
				if (!in_array($value, ['0', '1'])) {
					return response()->json(['success' => false, 'message' => 'Invalid order_is_paid value.'], 400);
				}
				$order->order_is_paid = $value;
			}
			$order->save();
		} catch (\Exception $e) {
			Log::error('AdminOrderController@updateOrderField: ' . $e->getMessage());
			return response()->json(['success' => false, 'message' => 'Có lỗi xảy ra khi cập nhật đơn hàng'], 500);
		}

		$notifySuccess = true;
		try {
			$field = $request->input('field');
			$value = $request->input('value');
			switch ($field) {
				case 'order_status':
					if ($value == 'delivering')
						$this->notifyUserOrderFieldChanged(
							$order,
							$request->input('field'),
							$request->input('value')
						);
					break;
				default:
					break;
			}
		} catch (\Exception $e) {
			$notifySuccess = false;
			Log::error('AdminOrderController@updateOrderField: ' . $e->getMessage());
		}

		return response()->json([
			'success' => $notifySuccess,
			'message' => $notifySuccess
				? 'Cập nhật đơn hàng thành công'
				: 'Cập nhật đơn hàng thành công, nhưng không thể gửi thông báo cho người dùng.'
		]);
	}

	public function notifyUserOrderFieldChanged($order, $field, $value)
	{
		// $order->user->notify(new \App\Notifications\OrderDeliveringNotification($order));
	}

	public function getOrderDetails($order_id): JsonResponse
	{
		$order = Order::with(relations: [
			'user',
			'voucher',
			'order_items.product.product_images',
		])->find($order_id);

		if (!$order) {
			return response()->json(['success' => false, 'message' => 'Order not found.'], 404);
		}

		return response()->json(['success' => true, 'order' => $order]);
	}

	public function getStatistics(Request $request): JsonResponse
	{
		try {
			$period = $request->input('period', 'week');
			$now = Carbon::now();

			switch ($period) {
				case 'month':
					$startDate = $now->copy()->subDays(30);
					$groupBy = "TO_CHAR(created_at, 'YYYY-MM-DD')";
					break;
				case 'year':
					$startDate = $now->copy()->subDays(365);
					$groupBy = "EXTRACT(MONTH FROM created_at)";
					break;
				default: // week
					$startDate = $now->copy()->subDays(7);
					$groupBy = "TO_CHAR(created_at, 'YYYY-MM-DD')";
					break;
			}

			// Get sales data
			$salesData = Order::where('created_at', '>=', $startDate)
				->where('order_status', '!=', 'cancelled')
				->groupBy(DB::raw($groupBy))
				->select(
					DB::raw($groupBy . ' as date'),
					DB::raw('SUM(order_total_price) as total')
				)
				->get();

			// Get order order_status distribution
			$order_statusData = Order::where('created_at', '>=', $startDate)
				->groupBy('order_status')
				->select('order_status', DB::raw('COUNT(*) as count'))
				->pluck('count', 'order_status')
				->toArray();
			Log::debug('Order status data: ', $order_statusData);

			// Format sales data for Chart.js
			$labels = [];
			$values = [];
			foreach ($salesData as $data) {
				$labels[] = $period === 'year'
					? Carbon::createFromFormat('m', $data->date)->format('M')
					: Carbon::parse($data->date)->format('d/m');
				$values[] = $data->total;
			}

			// Format order_status data for pie chart
			$order_statusCounts = [
				$order_statusData['pending'] ?? 0,
				$order_statusData['delivering'] ?? 0,
				$order_statusData['delivered'] ?? 0,
				$order_statusData['cancelled'] ?? 0
			];

			switch ($period) {
				case 'week':
					$startDate = Carbon::now()->subDays(6)->startOfDay();
					$format = 'D';
					break;
				case 'month':
					$startDate = Carbon::now()->subDays(29)->startOfDay();
					$format = 'd/m';
					break;
				case 'year':
					$startDate = Carbon::now()->subMonths(11)->startOfDay();
					$format = 'M Y';
					break;
				default:
					$startDate = Carbon::now()->subDays(6)->startOfDay();
					$format = 'd/m';
			}

			$endDate = Carbon::now();

			// Generate delivery time data
			$deliveryTimeData = $this->generateDeliveryTimeData($startDate, $endDate);

			// Generate payment method distribution
			$paymentMethodData = $this->generatePaymentMethodData($startDate, $endDate);

			// Generate cancellation rate data
			$cancellationRateData = $this->generateCancellationRateData($startDate, $endDate, $period);

			$summaryMetrics = $this->getSummaryMetrics($startDate, $endDate);

			return response()->json([
				'salesData' => [
					'labels' => $labels,
					'values' => $values
				],
				'statusData' => $order_statusCounts,
				'deliveryTimeData' => $deliveryTimeData,
				'paymentMethodData' => $paymentMethodData,
				'cancellationRateData' => $cancellationRateData,
				'summaryMetrics' => $summaryMetrics
			]);
		} catch (\Exception $e) {
			Log::error('AdminOrderController@getStatistics: ' . $e->getMessage());
			return response()->json(['success' => false, 'message' => 'Có lỗi xảy ra khi lấy thống kê'], 500);
		}
	}
}