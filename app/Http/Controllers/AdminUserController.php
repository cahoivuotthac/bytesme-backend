<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AdminUserController extends Controller
{
	public function showUsersPage(Request $request)
	{
		// Get filter parameters
		$status = $request->input('status');
		$role = $request->input('role');
		$sort = $request->input('sort', 'created_at');
		$direction = $request->input('direction', 'desc');

		// Base query with proper joins to calculate totals
		$query = User::leftJoin('orders', 'users.user_id', '=', 'orders.user_id')
			->select([
				'users.*',
				DB::raw('COUNT(DISTINCT orders.order_id) as orders_count'),
				DB::raw('COALESCE(SUM(orders.order_total_price), 0) as total_spent')
			])
			->groupBy([
				'users.user_id',
				'users.name',
				'users.email',
				'users.phone_number',
				'users.avatar',
				'users.role_type',
				'users.created_at',
				'users.updated_at',
			]);

		// Apply status filter
		if ($status) {
			switch ($status) {
				case 'active':
					$query->whereHas('orders', function ($q) {
						$q->where('created_at', '>=', now()->subDays(30));
					});
					break;
				case 'inactive':
					$query->whereDoesntHave('orders', function ($q) {
						$q->where('created_at', '>=', now()->subDays(90));
					});
					break;
				case 'new':
					$query->where('users.created_at', '>=', now()->subDays(7));
					break;
			}
		}

		// Apply role filter
		if ($role !== null && $role !== '') {
			$query->where('users.role_type', $role);
		}

		// Apply search if provided
		if ($request->filled('search')) {
			$searchTerm = $request->input('search');
			$searchType = $request->input('type', 'name');

			$query->where(function ($q) use ($searchTerm, $searchType) {
				switch ($searchType) {
					case 'full_name':
					case 'name':
						$q->where('users.name', 'LIKE', "%{$searchTerm}%");
						break;
					case 'email':
						$q->where('users.email', 'LIKE', "%{$searchTerm}%");
						break;
					case 'phone_number':
						$q->where('users.phone_number', 'LIKE', "%{$searchTerm}%");
						break;
				}
			});
		}

		// Apply sorting
		switch ($sort) {
			case 'name':
				$query->orderBy('users.name', $direction);
				break;
			case 'email':
				$query->orderBy('users.email', $direction);
				break;
			case 'orders':
				$query->orderBy('orders_count', $direction);
				break;
			case 'spent':
				$query->orderBy('total_spent', $direction);
				break;
			default:
				$query->orderBy('users.created_at', $direction);
		}

		// Get paginated results
		$users = $query->paginate(10)->withQueryString();

		return view('admin.users.index', compact('users'));
	}

	public function getUserAnalytics()
	{
		try {
			// Basic user statistics
			$totalUsers = User::count();
			$newUsersThisMonth = User::where('created_at', '>=', now()->startOfMonth())->count();

			// Active users based on recent order activity
			$activeUsers = User::whereHas('orders', function ($q) {
				$q->where('created_at', '>=', now()->subDays(30));
			})->count();
			$inactiveUsers = $totalUsers - $activeUsers;

			// User role distribution
			$roleDistribution = User::select('role_type', DB::raw('count(*) as count'))
				->groupBy('role_type')
				->get()
				->map(function ($item) {
					$roleName = match ($item->role_type) {
						0 => 'Khách hàng',
						1 => 'Admin',
						2 => 'Nhân viên',
						default => 'Không xác định'
					};
					return [
						'role_name' => $roleName,
						'count' => $item->count
					];
				});

			// Monthly user registrations
			$monthlyRegistrations = User::selectRaw("
                TO_CHAR(created_at, 'YYYY-MM') as month,
                COUNT(*) as count
            ")
				->where('created_at', '>=', now()->subMonths(12))
				->groupBy('month')
				->orderBy('month')
				->get();

			// Top spending customers - fix column names and query structure
			$topSpenders = DB::table('users')
				->leftJoin('orders', 'users.user_id', '=', 'orders.user_id')
				->select([
					'users.user_id',
					'users.name',
					'users.email',
					'users.phone_number',
					'users.avatar',
					'users.role_type',
					'users.created_at',
					'users.updated_at',
					DB::raw('COALESCE(SUM(orders.order_total_price), 0) as total_spent'),
					DB::raw('COUNT(orders.order_id) as orders_count')
				])
				->groupBy([
					'users.user_id',
					'users.name',
					'users.email',
					'users.phone_number',
					'users.avatar',
					'users.role_type',
					'users.created_at',
					'users.updated_at'
				])
				->havingRaw('COALESCE(SUM(orders.order_total_price), 0) > 0')
				->orderByRaw('COALESCE(SUM(orders.order_total_price), 0) DESC')
				->limit(10)
				->get()
				->map(function ($user) {
					return [
						'name' => $user->name,
						'email' => $user->email,
						'total_spent' => $user->total_spent ?? 0,
						'orders_count' => $user->orders_count,
						'avatar' => $user->avatar,
						'phone' => $user->phone_number
					];
				});

			// User activity patterns (by hour)
			$activityPattern = DB::table('orders')
				->selectRaw("
                    EXTRACT(HOUR FROM created_at) as hour,
                    COUNT(*) as order_count
                ")
				->where('created_at', '>=', now()->subDays(30))
				->groupBy('hour')
				->orderBy('hour')
				->get();

			// Geographic distribution (by address)
			$geographicData = DB::table('user_addresses')
				->join('users', 'user_addresses.user_id', '=', 'users.user_id')
				->selectRaw("
                    user_addresses.urban_name as city,
                    COUNT(DISTINCT users.user_id) as user_count
                ")
				->groupBy('user_addresses.urban_name')
				->orderBy('user_count', 'desc')
				->limit(10)
				->get();

			// Customer lifetime value segments - fix with subquery approach
			$customerSegments = DB::table(DB::raw('(
				SELECT 
					users.user_id,
					COALESCE(SUM(orders.order_total_price), 0) as total_spent
				FROM users 
				LEFT JOIN orders ON users.user_id = orders.user_id
				WHERE users.role_type = 0
				GROUP BY users.user_id
			) as user_totals'))
				->selectRaw("
					CASE 
						WHEN total_spent >= 5000000 THEN 'VIP'
						WHEN total_spent >= 1000000 THEN 'Premium'
						WHEN total_spent >= 100000 THEN 'Regular'
						WHEN total_spent > 0 THEN 'New'
						ELSE 'Inactive'
					END as segment,
					COUNT(*) as count
				")
				->groupBy(DB::raw("
					CASE 
						WHEN total_spent >= 5000000 THEN 'VIP'
						WHEN total_spent >= 1000000 THEN 'Premium'
						WHEN total_spent >= 100000 THEN 'Regular'
						WHEN total_spent > 0 THEN 'New'
						ELSE 'Inactive'
					END
				"))
				->get();

			// Average order value by user
			$avgOrderStats = DB::table('orders')
				->selectRaw("
                    AVG(order_total_price) as avg_order_value,
                    COUNT(*) as total_orders,
                    COUNT(DISTINCT user_id) as unique_customers
                ")
				->first();

			return response()->json([
				'success' => true,
				'data' => [
					'overview' => [
						'total_users' => $totalUsers,
						'new_this_month' => $newUsersThisMonth,
						'active_users' => $activeUsers,
						'inactive_users' => $inactiveUsers
					],
					'role_distribution' => $roleDistribution,
					'monthly_registrations' => $monthlyRegistrations,
					'top_spenders' => $topSpenders,
					'activity_pattern' => $activityPattern,
					'geographic_data' => $geographicData,
					'customer_segments' => $customerSegments,
					'avg_order_stats' => $avgOrderStats
				]
			]);
		} catch (\Exception $e) {
			Log::error('AdminUserController@getUserAnalytics: ' . $e->getMessage());
			return response()->json([
				'success' => false,
				'message' => 'Failed to fetch user analytics'
			], 500);
		}
	}

	public function updateUserStatus(Request $request, $user_id)
	{
		$request->validate([
			'status' => 'required|in:active,inactive,banned'
		]);

		try {
			$user = User::findOrFail($user_id);

			// Update user status logic here
			// You might want to add a status field to users table

			return response()->json([
				'success' => true,
				'message' => 'User status updated successfully'
			]);
		} catch (\Exception $e) {
			Log::error('AdminUserController@updateUserStatus: ' . $e->getMessage());
			return response()->json([
				'success' => false,
				'message' => 'Failed to update user status'
			], 500);
		}
	}

	public function getUserDetails($user_id)
	{
		try {
			$user = User::with([
				'orders' => function ($query) {
					$query->orderBy('created_at', 'desc');
				}
			])
				->withCount('orders')
				->withSum('orders', 'order_total_price')
				->findOrFail($user_id);

			// Get user addresses
			$addresses = DB::table('user_addresses')
				->where('user_id', $user_id)
				->get();

			// Get recent activity
			$recentOrders = $user->orders()->take(5)->get();

			return response()->json([
				'success' => true,
				'user' => [
					'user_id' => $user->user_id,
					'name' => $user->name,
					'email' => $user->email,
					'phone_number' => $user->phone_number,
					'avatar' => $user->avatar,
					'role_type' => $user->role_type,
					'created_at' => $user->created_at,
					'updated_at' => $user->updated_at,
					'orders_count' => $user->orders_count,
					'total_spent' => $user->orders_sum_order_total_price ?? 0,
					'addresses' => $addresses,
					'recent_orders' => $recentOrders
				]
			]);
		} catch (\Exception $e) {
			Log::error('AdminUserController@getUserDetails: ' . $e->getMessage());
			return response()->json([
				'success' => false,
				'message' => 'Không thể tải thông tin người dùng'
			], 500);
		}
	}
}
