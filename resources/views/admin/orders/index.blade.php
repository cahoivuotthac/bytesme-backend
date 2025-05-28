@extends('admin.layouts.layout')

@section('title', 'Quản lý đơn hàng')

@section('style')
<link rel="stylesheet" href="{{ asset('css/admin/orders/index.css') }}">
@endsection

@section('lib')
<!-- Flatpickr -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@endsection

@section('head-script')
<script>
	function showAlert(type, message) {
		const Toast = Swal.mixin({
			toast: true,
			position: 'top-end',
			showConfirmButton: false,
			timer: 3000,
			timerProgressBar: true
		});

		Toast.fire({
			icon: type,
			title: message
		});
	}
</script>
@endsection

@section('content')
<!-- Alerts -->
<div class="mt-2 alert alert-success visually-hidden" style="position: fixed; top: 10%; right: 2%; z-index: 1000;"></div>
<div class="mt-2 alert alert-danger visually-hidden" style="position: fixed; top: 10%; right: 2%; z-index: 1000;"></div>
<!-- End Alerts -->

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="page-title fs-5">Quản lý đơn hàng</h3>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard.showDashboardPage') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">Đơn hàng</li>
            </ol>
        </nav>
    </div>
</div>

<div class="content-wrapper">
    <!-- Key Metrics Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="metric-card primary">
                <div class="metric-icon">
                    <i class="mdi mdi-cash-multiple"></i>
                </div>
                <div class="metric-content">
                    <h4 class="metric-value" id="totalRevenue">--</h4>
                    <p class="metric-label">Doanh thu tháng</p>
                    <div class="metric-trend" id="revenueGrowth">
                        <i class="mdi mdi-arrow-up"></i>
                        --%
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="metric-card secondary">
                <div class="metric-icon">
                    <i class="mdi mdi-calendar-check"></i>
                </div>
                <div class="metric-content">
                    <h4 class="metric-value" id="totalOrders">--</h4>
                    <p class="metric-label">Tổng đơn hàng</p>
                    <div class="metric-trend" id="orderGrowth">
                        <i class="mdi mdi-arrow-up"></i>
                        --%
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="metric-card tertiary">
                <div class="metric-icon">
                    <i class="mdi mdi-truck-delivery"></i>
                </div>
                <div class="metric-content">
                    <h4 class="metric-value" id="deliveryTime">--</h4>
                    <p class="metric-label">Thời gian giao (phút)</p>
                    <div class="metric-trend" id="deliveryImprovement">
                        <i class="mdi mdi-arrow-up"></i>
                        --%
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="metric-card quaternary">
                <div class="metric-icon">
                    <i class="mdi mdi-cart-outline"></i>
                </div>
                <div class="metric-content">
                    <h4 class="metric-value" id="avgOrderValue">--</h4>
                    <p class="metric-label">Giá trị đơn TB</p>
                    <div class="metric-trend" id="avgOrderGrowth">
                        <i class="mdi mdi-arrow-up"></i>
                        --%
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="card dashboard-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title">Phân tích doanh thu</h4>
                    <div class="btn-group">
                        <button type="button" class="btn btn-outline-primary btn-sm" data-period="week">7 ngày</button>
                        <button type="button" class="btn btn-outline-primary btn-sm" data-period="month">30 ngày</button>
                        <button type="button" class="btn btn-outline-primary btn-sm" data-period="year">365 ngày</button>
                    </div>
                </div>
                <div class="card-body">
                    <canvas id="salesChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card dashboard-card">
                <div class="card-header">
                    <h4 class="card-title">Trạng thái đơn hàng</h4>
                </div>
                <div class="card-body">
                    <canvas id="orderStatusChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Advanced Analytics Row -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card dashboard-card">
                <div class="card-header">
                    <h4 class="card-title">Thời gian giao hàng</h4>
                </div>
                <div class="card-body">
                    <canvas id="deliveryTimeChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card dashboard-card">
                <div class="card-header">
                    <h4 class="card-title">Phương thức thanh toán</h4>
                </div>
                <div class="card-body">
                    <canvas id="paymentMethodChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card dashboard-card">
                <div class="card-header">
                    <h4 class="card-title">Tỉ lệ hủy đơn hàng</h4>
                </div>
                <div class="card-body">
                    <canvas id="cancellationRateChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="orders-container">
        <div class="row">
            <div class="col-md-12 grid-margin">
                <div class="card newest-orders-card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h4 class="card-title">Đơn hàng mới nhận</h4>
                            <span class="badge badge-pill badge-primary">{{ count($newestOrders) }} đơn chờ xử lý</span>
                        </div>
                    </div>
                    <div class="card-body px-0">
                        <div class="newest-orders-list">
                            @foreach($newestOrders as $order)
                                <div class="newest-order-item">
                                    <div class="d-flex align-items-center order-header" data-order-id="{{ $order->order_id }}">
                                        <div class="order-info row w-50">
                                            <div class="col-3">
                                                <span class="order-id clickable" data-order-id="{{ $order->order_id }}">Đơn #{{ $order->order_id }}</span>
                                            </div>
                                            <div class="col-6">
                                                <span class="customer-name">KH: {{ $order->user->name }}</span>
                                            </div>
                                        </div>
                                        <span class="ms-auto me-3 order-time">{{ \Carbon\Carbon::parse($order->created_at)->format('d/m/Y H:i') }}</span>
                                        <span class="status-badge
                                            @switch($order->order_status)
                                                @case('pending') badge-pending @break
                                                @case('delivering') badge-delivering @break
                                                @case('delivered') badge-delivered @break
                                                @case('cancelled') badge-cancelled @break
                                                @default badge-default
                                            @endswitch
                                        ">
                                            @switch($order->order_status)
                                                @case('pending')
                                                    @if ($order->order_payment_method != Constants::PAYMENT_METHOD_COD && $order->order_is_paid == 0)
                                                        <i class="mdi mdi-clock-outline me-1"></i> Chờ KH tt. online
                                                    @else
                                                    <i class="mdi mdi-alert-box-outline me-1"></i> Đang chờ xử lý
                                                    @endif
                                                    @break
                                                @case ('delivering')
                                                    <i class="mdi mdi-truck-delivery me-1"></i> Đang giao hàng
                                                    @break
                                                @case ('delivered')
                                                    <i class="mdi mdi-check-circle me-1"></i> Đã giao hàng
                                                    @break
                                                @case('cancelled')
                                                    <i class="mdi mdi-close-circle me-1"></i> Đã hủy
                                                    @break
                                                @default
                                                    N\A
                                                @break
                                            @endswitch
                                        </span>
                                        <i class="mdi mdi-chevron-down toggle-icon ms-2"></i>
                                    </div>
                                    <div class="order-details" id="order-details-{{ $order->order_id }}" style="display: none;">
                                        <div class="order-summary mt-3">
                                            <p><strong>Tổng tiền:</strong> {{ number_format($order->order_total_price, 0, ',', '.') }} ₫</p>
                                            <p><strong>Phí vận chuyển:</strong> {{ number_format($order->order_deliver_cost, 0, ',', '.') }} ₫</p>
                                        </div>
                                        <div class="order-items">
                                            @foreach($order->order_items as $item)
                                                <div class="order-item d-flex align-items-center mb-3">
                                                    <img src="{{ $item->product->product_images[0]->product_image_url ?? asset('images/placeholder-plant.jpg') }}" alt="{{ $item->product->name }}" class="product-image me-3">
                                                    <div>
                                                        <p class="product-name mb-1">{{ $item->product->product_name }}</p>
                                                        <p class="product-quantity-price mb-0">Số lượng: {{ $item->order_items_quantity }} x {{ number_format($item->order_items_unitprice, 0, ',', '.') }} ₫</p>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                        <button class="btn btn-primary mt-2 mb-3 change-status-btn rounded" style="font-size: 0.9rem;" data-order-id="{{ $order->order_id }}" data-order-status="{{ $order->order_status }}">
											@if($order->order_status == 'pending')
												<p class="d-flex align-items-center">Cập trạng thái thành đang giao hàng <span class="mdi mdi-truck-delivery ms-2" style="font-size: 1.2rem;"></span></p>
											@elseif($order->order_status == 'delivering')
												<p class="d-flex align-items-center">Xác nhận đã giao hàng <span class="mdi mdi-check-circle ms-2" style="font-size: 1.2rem;"></span></p>
											@endif
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12 grid-margin">
                <div class="card orders-card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h4 class="card-title">Danh sách đơn hàng</h4>
                            <div>
                                <button class="btn btn-outline-primary btn-sm" data-bs-toggle="collapse" data-bs-target="#filterCollapse">
                                    <i class="mdi mdi-filter-variant"></i> Bộ lọc
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="collapse" id="filterCollapse">
                        <form method="GET" action="{{ route('admin.orders.showOrdersPage') }}" class="filter-section">
                            <div class="filter-grid">
                                <div class="form-group">
                                    <div class="date-filter-container">
                                        <div class="btn-group mb-3" role="group">
                                            <input type="radio" class="btn-check" name="dateFilterType" id="singleDate"
                                                value="single" checked>
                                            <label class="btn btn-outline" for="singleDate">Lọc theo ngày</label>
                                            <input type="radio" class="btn-check" name="dateFilterType" id="dateRange"
                                                value="range">
                                            <label class="btn btn-outline" for="dateRange">Lọc khoảng thời gian</label>
                                        </div>

                                        <div id="singleDatePicker" class="date-input-group">
                                            <input type="text" class="form-control-md" id="single_date" name="single_date"
                                                placeholder="Chọn ngày">
                                        </div>

                                        <div id="rangeDatePicker" class="date-input-group" style="display: none;">
                                            <div class="d-flex gap-3">
                                                <input type="text" class="form-control-md" id="date_start" name="date_start"
                                                    placeholder="Chọn ngày bắt đầu">
                                                <input type="text" class="form-control-md" id="date_end" name="date_end"
                                                    placeholder="Chọn ngày kết thúc">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="filter-group-container">
                                    <div class="form-group">
                                        <label for="status" class="vintage-label">Tình trạng đơn hàng</label>
                                        <select class="form-control form-control-lg vintage-select" id="status" name="status">
                                            <option value="">Tất cả</option>
                                            <option value="pending">Đang chờ xử lý</option>
                                            <option value="delivering">Đang giao hàng</option>
                                            <option value="delivered">Đã giao thành công</option>
                                            <option value="cancelled">Khách hàng đã hủy đơn</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="order_is_paid" class="vintage-label">Tình trạng thanh toán</label>
                                        <select class="form-control form-control-lg vintage-select" id="isPaid" name="order_is_paid">
                                            <option value="">Tất cả</option>
                                            <option value="1">Đã thanh toán</option>
                                            <option value="0">Chưa thanh toán</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="order_payment_method" class="vintage-label">Loại hình thanh toán</label>
                                        <select class="form-control form-control-lg vintage-select" id="isPaid" name="order_payment_method">
                                            <option value="">Tất cả</option>
                                            <option value="{{ Constants::PAYMENT_METHOD_COD }}">Thanh toán khi nhận hàng (COD)</option>
                                            <option value="{{ Constants::PAYMENT_METHOD_MOMO }}">Thanh toán ví momo</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <button type="submit" class="btn vintage-btn vintage-btn-primary filter-submit">
                                    <i class="mdi mdi-filter-variant"></i> Áp dụng bộ lọc
                                </button>
                            </div>
                        </form>
                    </div>
                    
                    <div class="card-body">
                        <div class="table-container">
                            <div class="table-responsive">
                                <table class="table vintage-table">
                                    <thead>
                                        <tr>
                                            <th class="sortable" data-sort="order_id">
                                                <i class="mdi mdi-pound"></i> Mã đơn
                                                <i class="sort-icon mdi {{ request('sort') == 'order_id' ? (request('direction', 'asc') == 'asc' ? 'mdi-arrow-up' : 'mdi-arrow-down') : 'mdi-swap-vertical' }}"></i>
                                            </th>
                                            <th>
                                                <i class="mdi mdi-account"></i> Khách hàng
                                            </th>
                                            <th class="sortable" data-sort="order_total_price">
                                                <i class="mdi mdi-cash"></i> Tổng tiền
                                                <i class="sort-icon mdi {{ request('sort') == 'order_total_price' ? (request('direction') == 'asc' ? 'mdi-arrow-up' : 'mdi-arrow-down') : 'mdi-swap-vertical' }}"></i>
                                            </th>
                                            <th class="sortable" data-sort="created_at">
                                                <i class="mdi mdi-calendar"></i> Ngày đặt
                                                @if (!request('sort'))
                                                    <i class="sort-icon mdi mdi-arrow-down"></i>
                                                @else
                                                    <i class="sort-icon mdi {{ request('sort') == 'created_at' ? (request('direction', 'desc') == 'asc' ? 'mdi-arrow-up' : 'mdi-arrow-down') : 'mdi-swap-vertical' }}"></i>
                                                @endif
                                            </th>
                                            <th class="sortable" data-sort="order_deliver_time">
                                                <i class="mdi mdi-truck"></i> Ngày giao
                                                <i class="sort-icon mdi {{ request('sort') == 'order_deliver_time' ? (request('direction') == 'asc' ? 'mdi-arrow-up' : 'mdi-arrow-down') : 'mdi-swap-vertical' }}"></i>
                                            </th>
                                            <th><i class="mdi mdi-information-outline"></i> Trạng thái</th>
                                            <th><i class="mdi mdi-credit-card"></i> PTTT</th>
                                            <th><i class="mdi mdi-check-circle"></i> Đã TT</th>
                                            <th><i class="mdi mdi-dots-vertical"></i> Hành động</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($orders as $order)
                                            <tr>
                                                <td><strong>{{ $order->order_id }}</strong></td>
                                                <td>{{ $order->user->name }}</td>
                                                <td>{{  number_format($order->order_total_price, 0, ',', '.')  }} ₫</td>
                                                <td>{{  \Carbon\Carbon::parse($order->created_at)->format('d/m/Y H:i')  }}</td>
                                                <td>
                                                @if ($order->order_status === 'delivered')
                                                    {{  \Carbon\Carbon::parse($order->order_deliver_time)->format('d/m/Y H:i')  }}
                                                @else
                                                    Chưa giao
                                                @endif
                                                </td>
                                                <td class="editable-cell" data-order-id="{{ $order->order_id }}" data-field="order_status">
                                                    <span class="status-badge 
                                                        @switch($order->order_status)
                                                            @case('pending') badge-pending @break
                                                            @case('delivering') badge-delivering @break
                                                            @case('delivered') badge-delivered @break
                                                            @case('cancelled') badge-cancelled @break
                                                            @default badge-default
                                                        @endswitch
                                                        ">
                                                        @switch($order->order_status)
															@case('online_payment_pending')
																<i class="mdi mdi-credit-card-clock-outline me-1"></i> Chờ tt. online
																@break
                                                            @case('pending')
                                                                <i class="mdi mdi-alert-box-outline me-1"></i> Chờ xử lý
                                                                @break
                                                            @case ('delivering')
                                                                <i class="mdi mdi-truck-delivery me-1"></i> Đang giao
                                                                @break
                                                            @case ('delivered')
                                                                <i class="mdi mdi-check-circle me-1"></i> Đã giao
                                                                @break
                                                            @case('cancelled')
                                                                <i class="mdi mdi-close-circle me-1"></i> Đã hủy
                                                                @break
                                                            @default
                                                                N\A
                                                                @break
                                                        @endswitch
                                                    </span>
                                                </td>
                                                <td>
                                                    @if ($order->order_payment_method == Constants::PAYMENT_METHOD_COD)
                                                        <span class="badge badge-neutral-1">COD</span>
                                                    @else
                                                        <span class="badge badge-neutral-2">Online</span>
                                                    @endif
                                                </td>
                                                <td class="editable-cell" data-order-id="{{ $order->order_id }}" data-field="order_is_paid">
                                                    @if ($order->order_is_paid == true)
                                                        <span class="badge badge-success"><i class="mdi mdi-check-all me-1"></i> Đã TT</span>
                                                    @else
                                                        <span class="badge badge-danger"><i class="mdi mdi-close me-1"></i> Chưa TT</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <div class="dropdown">
                                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                                            <i class="mdi mdi-dots-vertical"></i>
                                                        </button>
                                                        <div class="dropdown-menu">
                                                            <a class="dropdown-item view-details-btn" href="#" data-order-id="{{ $order->order_id }}">
                                                                <i class="mdi mdi-eye"></i> Xem chi tiết
                                                            </a>
                                                            @if($order->order_status == 'pending')
                                                            <a class="dropdown-item change-status-btn" href="#" data-order-id="{{ $order->order_id }}">
                                                                <i class="mdi mdi-truck-delivery"></i> Chuyển trạng thái
                                                            </a>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @include('admin.layouts.pagination', ['paginator' => $orders, 'itemName' => 'Đơn hàng'])
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade vintage-modal" id="orderDetailsModal" tabindex="-1" aria-labelledby="orderDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title vintage-title">Chi tiết đơn hàng</h5>
                <button type="button" class="btn-close vintage-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <div class="modal-body vintage-body">
                <!-- Order details will be loaded here via AJAX -->
                <div id="order-details-content">
                    <!-- Loading indicator -->
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Đang tải...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('body-script')
<script src="{{ asset('js/admin/orders/index.js') }}"></script>
<script>
	// Define options for editable cells
	const statusOptions = {
		'pending': 'Đang chờ xử lý',
		'delivering': 'Đang giao hàng',
		'delivered': 'Đã giao hàng',
		'cancelled': 'Đã hủy'
	};

	const isPaidOptions = {
		'1': 'Đã thanh toán',
		'0': 'Chưa thanh toán'
	};

	// Make these available globally
	window.statusOptions = statusOptions;
	window.isPaidOptions = isPaidOptions;
</script>
@endsection