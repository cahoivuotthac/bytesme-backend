@extends("admin.layouts.layout")
@section("title", "Bảng điều khiển Admin Bytesme")

@section('style')
	<style>
		.dashboard-card {
			background: white;
			border-radius: 12px;
			box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
			transition: transform 0.2s ease, box-shadow 0.2s ease;
		}

		.dashboard-card:hover {
			transform: translateY(-2px);
			box-shadow: 0 4px 20px rgba(0, 0, 0, 0.12);
		}

		.metric-card {
			background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
			color: white;
			border-radius: 12px;
			padding: 1.5rem;
			margin-bottom: 1rem;
		}

		.metric-card.revenue {
			background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
		}

		.metric-card.orders {
			background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
		}

		.metric-card.customers {
			background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
		}

		.metric-card.products {
			background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
		}

		.metric-value {
			font-size: 2.5rem;
			font-weight: 700;
			margin-bottom: 0.5rem;
		}

		.metric-label {
			font-size: 1rem;
			opacity: 0.9;
			margin-bottom: 0.25rem;
		}

		.metric-change {
			font-size: 0.875rem;
			opacity: 0.8;
		}

		.chart-container {
			position: relative;
			height: 400px;
			margin: 1rem 0;
		}

		.chart-container-small {
			position: relative;
			height: 300px;
			margin: 1rem 0;
		}

		.table-responsive {
			border-radius: 8px;
			overflow: hidden;
		}

		.status-badge {
			padding: 0.25rem 0.75rem;
			border-radius: 20px;
			font-size: 0.75rem;
			font-weight: 600;
			text-transform: uppercase;
		}

		.status-pending {
			background: #fff3cd;
			color: #856404;
		}

		.status-delivering {
			background: #cce5ff;
			color: #004085;
		}

		.status-delivered {
			background: #d4edda;
			color: #155724;
		}

		.status-cancelled {
			background: #f8d7da;
			color: #721c24;
		}
	</style>
@endsection

@section('body-script')
	<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
	<script>
		document.addEventListener('DOMContentLoaded', function () {
			// Daily Revenue Chart
			const revenueCtx = document.getElementById('revenueChart').getContext('2d');
			const revenueData = {!! json_encode($dailyRevenue->map(function ($item) {
		return ['date' => $item->date, 'total' => $item->total];
	})) !!};

			new Chart(revenueCtx, {
				type: 'line',
				data: {
					labels: revenueData.map(item => new Date(item.date).toLocaleDateString()),
					datasets: [{
						label: 'Doanh thu hàng ngày (VND)',
						data: revenueData.map(item => item.total),
						borderColor: '#11998e',
						backgroundColor: 'rgba(17, 153, 142, 0.1)',
						borderWidth: 3,
						fill: true,
						tension: 0.4
					}]
				},
				options: {
					responsive: true,
					maintainAspectRatio: false,
					plugins: {
						legend: { display: false }
					},
					scales: {
						y: {
							beginAtZero: true,
							ticks: {
								callback: function (value) {
									return new Intl.NumberFormat('vi-VN').format(value) + ' VND';
								}
							}
						}
					}
				}
			});

			// Order Status Chart
			const statusCtx = document.getElementById('statusChart').getContext('2d');
			const statusData = {!! json_encode($orderStatusData) !!};

			new Chart(statusCtx, {
				type: 'doughnut',
				data: {
					labels: statusData.map(item => {
						switch(item.order_status) {
							case 'pending': return 'Chờ xử lý';
							case 'delivering': return 'Đang giao';
							case 'delivered': return 'Đã giao';
							case 'cancelled': return 'Đã hủy';
							default: return item.order_status;
						}
					}),
					datasets: [{
						data: statusData.map(item => item.count),
						backgroundColor: ['#ffc107', '#007bff', '#28a745', '#dc3545', '#6c757d'],
						borderWidth: 0
					}]
				},
				options: {
					responsive: true,
					maintainAspectRatio: false,
					plugins: {
						legend: { position: 'bottom' }
					}
				}
			});

			// Top Products Chart
			const productsCtx = document.getElementById('productsChart').getContext('2d');
			const productsData = {!! json_encode($topProducts) !!};

			new Chart(productsCtx, {
				type: 'bar',
				data: {
					labels: productsData.map(item => item.product_name),
					datasets: [{
						label: 'Số lượng đã bán',
						data: productsData.map(item => item.total_sold),
						backgroundColor: '#4facfe',
						borderRadius: 6
					}]
				},
				options: {
					responsive: true,
					maintainAspectRatio: false,
					plugins: {
						legend: { display: false }
					},
					scales: {
						y: { beginAtZero: true }
					}
				}
			});

			// Category Distribution Spider Chart
			const categoryCtx = document.getElementById('categoryChart').getContext('2d');
			const categoryData = {!! json_encode($categoryDistribution) !!};

			new Chart(categoryCtx, {
				type: 'radar',
				data: {
					labels: categoryData.map(item => item.category_name),
					datasets: [
						{
							label: 'Tổng số lượng đã bán',
							data: categoryData.map(item => item.total_quantity),
							borderColor: '#ff6b6b',
							backgroundColor: 'rgba(255, 107, 107, 0.2)',
							borderWidth: 2,
							pointBackgroundColor: '#ff6b6b'
						},
						{
							label: 'Số đơn hàng',
							data: categoryData.map(item => item.order_count),
							borderColor: '#4ecdc4',
							backgroundColor: 'rgba(78, 205, 196, 0.2)',
							borderWidth: 2,
							pointBackgroundColor: '#4ecdc4'
						}
					]
				},
				options: {
					responsive: true,
					maintainAspectRatio: false,
					plugins: {
						legend: { 
							position: 'bottom',
							labels: { usePointStyle: true }
						}
					},
					scales: {
						r: {
							beginAtZero: true,
							grid: { color: 'rgba(0,0,0,0.1)' },
							pointLabels: { 
								font: { size: 12 },
								color: '#666'
							}
						}
					}
				}
			});

			// Hourly Orders Chart (Peak Hours)
			const hourlyCtx = document.getElementById('hourlyChart').getContext('2d');
			const hourlyData = {!! json_encode($hourlyOrdersComplete) !!};

			new Chart(hourlyCtx, {
				type: 'bar',
				data: {
					labels: hourlyData.map(item => item.hour + ':00'),
					datasets: [
						{
							label: 'Số đơn hàng',
							data: hourlyData.map(item => item.order_count),
							backgroundColor: '#667eea',
							borderRadius: 4,
							yAxisID: 'y'
						},
						{
							label: 'Giá trị đơn hàng TB (VND)',
							data: hourlyData.map(item => item.avg_order_value),
							type: 'line',
							borderColor: '#f093fb',
							backgroundColor: 'transparent',
							borderWidth: 3,
							tension: 0.4,
							yAxisID: 'y1'
						}
					]
				},
				options: {
					responsive: true,
					maintainAspectRatio: false,
					plugins: {
						legend: { 
							position: 'top',
							labels: { usePointStyle: true }
						},
						title: {
							display: true,
							text: 'Phân tích giờ cao điểm (30 ngày qua)'
						}
					},
					scales: {
						x: {
							title: {
								display: true,
								text: 'Giờ trong ngày'
							}
						},
						y: {
							type: 'linear',
							display: true,
							position: 'left',
							title: {
								display: true,
								text: 'Số đơn hàng'
							},
							beginAtZero: true
						},
						y1: {
							type: 'linear',
							display: true,
							position: 'right',
							title: {
								display: true,
								text: 'Giá trị đơn hàng TB (VND)'
							},
							beginAtZero: true,
							grid: { drawOnChartArea: false },
							ticks: {
								callback: function(value) {
									return new Intl.NumberFormat('vi-VN').format(value);
								}
							}
						}
					}
				}
			});
		});
	</script>
@endsection

@section('content')
	<div class="container-fluid">
		<!-- Header -->
		<div class="row mb-4">
			<div class="col-12">
				<h1 class="h3 mb-0 text-gray-800">Bảng điều khiển Bytesme</h1>
				<p class="text-muted">Chào mừng trở lại! Đây là tình hình kinh doanh của Bytesme hôm nay.</p>
			</div>
		</div>

		<!-- Key Metrics -->
		<div class="row mb-4">
			<div class="col-xl-3 col-md-6">
				<div class="metric-card revenue">
					<div class="metric-value">{{ number_format($totalRevenue) }} VND</div>
					<div class="metric-label">Tổng doanh thu</div>
					<div class="metric-change">
						Hôm nay: {{ number_format($todayRevenue) }} VND
					</div>
				</div>
			</div>
			<div class="col-xl-3 col-md-6">
				<div class="metric-card orders">
					<div class="metric-value">{{ number_format($totalOrders) }}</div>
					<div class="metric-label">Tổng đơn hàng</div>
					<div class="metric-change">
						Hôm nay: {{ $todayOrders }} đơn hàng
					</div>
				</div>
			</div>
			<div class="col-xl-3 col-md-6">
				<div class="metric-card customers">
					<div class="metric-value">{{ number_format($totalCustomers) }}</div>
					<div class="metric-label">Tổng khách hàng</div>
					<div class="metric-change">
						Mới hôm nay: {{ $newCustomersToday }}
					</div>
				</div>
			</div>
			<div class="col-xl-3 col-md-6">
				<div class="metric-card products">
					<div class="metric-value">{{ number_format($totalProducts) }}</div>
					<div class="metric-label">Tổng sản phẩm</div>
					<div class="metric-change">
						Đánh giá TB: {{ number_format($averageRating, 1) }}/5
					</div>
				</div>
			</div>
		</div>

		<!-- Revenue Growth Alert -->
		@if($revenueGrowth != 0)
			<div class="row mb-4">
				<div class="col-12">
					<div class="alert alert-{{ $revenueGrowth > 0 ? 'success' : 'warning' }} alert-dismissible fade show">
						<strong>{{ $revenueGrowth > 0 ? 'Tăng trưởng' : 'Giảm' }} doanh thu tháng:</strong>
						{{ $revenueGrowth > 0 ? '+' : '' }}{{ number_format($revenueGrowth, 1) }}% so với tháng trước
						<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
					</div>
				</div>
			</div>
		@endif

		<!-- Charts Row -->
		<div class="row mb-4">
			<!-- Revenue Trend Chart -->
			<div class="col-xl-8 col-lg-7">
				<div class="dashboard-card p-4">
					<h5 class="card-title mb-3">Xu hướng doanh thu (30 ngày qua)</h5>
					<div class="chart-container">
						<canvas id="revenueChart"></canvas>
					</div>
				</div>
			</div>

			<!-- Order Status Distribution -->
			<div class="col-xl-4 col-lg-5">
				<div class="dashboard-card p-4">
					<h5 class="card-title mb-3">Phân bố trạng thái đơn hàng</h5>
					<div class="chart-container">
						<canvas id="statusChart"></canvas>
					</div>
				</div>
			</div>
		</div>

		<!-- Secondary Charts Row -->
		<div class="row mb-4">
			<!-- Top Products -->
			<div class="col-xl-6">
				<div class="dashboard-card p-4">
					<h5 class="card-title mb-3">Sản phẩm bán chạy nhất</h5>
					<div class="chart-container">
						<canvas id="productsChart"></canvas>
					</div>
				</div>
			</div>

			<!-- Recent Orders -->
			<div class="col-xl-6">
				<div class="dashboard-card p-4">
					<h5 class="card-title mb-3">Đơn hàng gần đây</h5>
					<div class="table-responsive">
						<table class="table table-hover mb-0">
							<thead class="table-light">
								<tr>
									<th>Mã đơn hàng</th>
									<th>Khách hàng</th>
									<th>Tổng tiền</th>
									<th>Trạng thái</th>
									<th>Ngày</th>
								</tr>
							</thead>
							<tbody>
								@foreach($recentOrders as $order)
									<tr>
										<td><strong>#{{ $order->order_id }}</strong></td>
										<td>{{ $order->user->user_name ?? 'Khách vãng lai' }}</td>
										<td>{{ number_format($order->order_total_price) }} VND</td>
										<td>
											<span class="status-badge status-{{ $order->order_status }}">
												@switch($order->order_status)
													@case('pending') Chờ xử lý @break
													@case('delivering') Đang giao @break
													@case('delivered') Đã giao @break
													@case('cancelled') Đã hủy @break
													@default {{ ucfirst($order->order_status) }}
												@endswitch
											</span>
										</td>
										<td>{{ $order->created_at->format('d/m H:i') }}</td>
									</tr>
								@endforeach
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>

		<!-- Analytics Charts Row -->
		<div class="row mb-4">
			<!-- Category Distribution Spider Chart -->
			<div class="col-xl-6">
				<div class="dashboard-card p-4">
					<h5 class="card-title mb-3">Hiệu suất theo danh mục sản phẩm</h5>
					<p class="text-muted small mb-3">Phân bố đơn hàng và số lượng theo danh mục sản phẩm</p>
					<div class="chart-container-small">
						<canvas id="categoryChart"></canvas>
					</div>
				</div>
			</div>

			<!-- Peak Hours Analysis -->
			<div class="col-xl-6">
				<div class="dashboard-card p-4">
					<h5 class="card-title mb-3">Phân tích giờ cao điểm</h5>
					<p class="text-muted small mb-3">Xác định các khung giờ bận rộn và giá trị đơn hàng trung bình</p>
					<div class="chart-container-small">
						<canvas id="hourlyChart"></canvas>
					</div>
				</div>
			</div>
		</div>

		<!-- Monthly Summary -->
		<div class="row">
			<div class="col-12">
				<div class="dashboard-card p-4">
					<h5 class="card-title mb-3">Tổng kết tháng</h5>
					<div class="row">
						<div class="col-md-3 text-center">
							<h4 class="text-primary">{{ number_format($monthlyRevenue) }} VND</h4>
							<p class="text-muted mb-0">Doanh thu tháng này</p>
						</div>
						<div class="col-md-3 text-center">
							<h4 class="text-info">{{ $monthlyOrders }}</h4>
							<p class="text-muted mb-0">Đơn hàng tháng này</p>
						</div>
						<div class="col-md-3 text-center">
							<h4 class="text-success">{{ number_format($monthlyRevenue / max($monthlyOrders, 1)) }} VND</h4>
							<p class="text-muted mb-0">Giá trị đơn hàng TB</p>
						</div>
						<div class="col-md-3 text-center">
							<h4 class="text-warning">{{ number_format($averageRating, 1) }}/5</h4>
							<p class="text-muted mb-0">Hài lòng khách hàng</p>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
@endsection