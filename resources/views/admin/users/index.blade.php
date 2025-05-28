@php
    use Illuminate\Support\Str;
@endphp

@extends('admin.layouts.layout')
@section('title', 'Quản lý người dùng')

@section('style')
    <link rel="stylesheet" href="{{ asset('css/admin/users/index.css') }}">
@endsection

@section('lib')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
    @if (session('success'))
        <script>showAlert('success', '{{ session('success') }}')</script>
        {{ session()->forget('success') }}
    @elseif (session('error'))
        <script>showAlert('error', '{{ session('error') }}')</script>
        {{ session()->forget('error') }}
    @endif
    
    <div class="content-wrapper">
        <!-- Christmas decorations -->
        <div class="christmas-decorations">
            <img src="{{ asset('images/pie1.webp') }}" class="holly-left" alt="Holly">
            <img src="{{ asset('images/pie1.webp') }}" class="holly-right" alt="Holly">
        </div>

        <div class="snow-overlay"></div>

        <div class="row">
            <div class="col-12">
                <!-- Festive header -->
                <div class="festive-header mb-4">
                    <h2 class="text-center">
                        <i class="fas fa-users christmas-icon"></i>
                        Quản lý khách hàng
                        <i class="fas fa-user-friends christmas-icon"></i>
                    </h2>
                </div>

                <!-- Analytics Dashboard -->
                <div class="analytics-dashboard">
                    <!-- Metrics Cards -->
                    <div class="metrics-grid" id="userMetricsGrid">
                        <div class="metric-card">
                            <div class="chart-loading">
                                <div class="loading-spinner"></div>
                                Đang tải dữ liệu...
                            </div>
                        </div>
                    </div>

                    <!-- Charts Grid -->
                    <div class="charts-grid">
                        <div class="chart-container">
                            <div class="chart-header">
                                <div>
                                    <h3 class="chart-title">Đăng ký theo tháng</h3>
                                    <p class="chart-subtitle">12 tháng gần nhất</p>
                                </div>
                            </div>
                            <div class="chart-wrapper">
                                <canvas id="registrationChart"></canvas>
                            </div>
                        </div>

                        <div class="chart-container">
                            <div class="chart-header">
                                <div>
                                    <h3 class="chart-title">Phân loại người dùng</h3>
                                    <p class="chart-subtitle">Theo vai trò</p>
                                </div>
                            </div>
                            <div class="chart-wrapper">
                                <canvas id="roleChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <div class="charts-grid">
                        <div class="chart-container">
                            <div class="chart-header">
                                <div>
                                    <h3 class="chart-title">Hoạt động theo giờ</h3>
                                    <p class="chart-subtitle">Đặt hàng trong 30 ngày qua</p>
                                </div>
                            </div>
                            <div class="chart-wrapper">
                                <canvas id="activityChart"></canvas>
                            </div>
                        </div>

                        <div class="chart-container">
                            <div class="chart-header">
                                <div>
                                    <h3 class="chart-title">Phân khúc khách hàng</h3>
                                    <p class="chart-subtitle">Theo giá trị đơn hàng</p>
                                </div>
                            </div>
                            <div class="chart-wrapper">
                                <canvas id="segmentChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <div class="charts-grid">
                        <div class="chart-container">
                            <div class="chart-header">
                                <div>
                                    <h3 class="chart-title">Top khách hàng VIP</h3>
                                    <p class="chart-subtitle">Theo tổng chi tiêu</p>
                                </div>
                            </div>
                            <div id="topSpendersList" class="top-spenders-list">
                                <div class="chart-loading">
                                    <div class="loading-spinner"></div>
                                    Đang tải...
                                </div>
                            </div>
                        </div>

                        <div class="chart-container">
                            <div class="chart-header">
                                <div>
                                    <h3 class="chart-title">Phân bố địa lý</h3>
                                    <p class="chart-subtitle">Top 10 thành phố</p>
                                </div>
                            </div>
                            <div id="geographicList" class="geographic-list">
                                <div class="chart-loading">
                                    <div class="loading-spinner"></div>
                                    Đang tải...
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Search and filter -->
                <div class="card christmas-card mb-3">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="search-box">
                                    <input type="text" id="searchInput" class="form-control"
                                        placeholder="Tìm kiếm theo tên, email, số điện thoại..."
                                        value="{{ request('search') }}">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <select class="form-select" id="searchType">
                                    <option value="full_name" {{ request('type') === 'full_name' ? 'selected' : '' }}>Tên</option>
                                    <option value="email" {{ request('type') === 'email' ? 'selected' : '' }}>Email</option>
                                    <option value="phone_number" {{ request('type') === 'phone_number' ? 'selected' : '' }}>SĐT</option>
                                    <option value="user_name" {{ request('type') === 'user_name' ? 'selected' : '' }}>Username</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button class="btn btn-primary w-100" id="searchButton">
                                    <i class="mdi mdi-magnify"></i> Tìm kiếm
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="card christmas-card mb-3">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4 class="mb-0">Bộ lọc</h4>
                        </div>
                        <div class="row">
                            <div class="col-md-3">
                                <select class="form-select" id="statusFilter">
                                    <option value="">Tất cả trạng thái</option>
                                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Hoạt động</option>
                                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Không hoạt động</option>
                                    <option value="new" {{ request('status') === 'new' ? 'selected' : '' }}>Mới (7 ngày)</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select class="form-select" id="roleFilter">
                                    <option value="">Tất cả vai trò</option>
                                    <option value="0" {{ request('role') === '0' ? 'selected' : '' }}>Khách hàng</option>
                                    <option value="1" {{ request('role') === '1' ? 'selected' : '' }}>Admin</option>
                                    <option value="2" {{ request('role') === '2' ? 'selected' : '' }}>Nhân viên</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <button class="btn btn-filter w-100" id="applyFilters">
                                    <i class="mdi mdi-filter"></i> Áp dụng lọc
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Users table -->
                <div class="card christmas-card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Avatar</th>
                                        <th>Tên <i class="mdi mdi-arrow-up-down sort-icon" data-sort="name"></i></th>
                                        <th>Email <i class="mdi mdi-arrow-up-down sort-icon" data-sort="email"></i></th>
                                        <th>Số điện thoại</th>
                                        <th>Vai trò</th>
                                        <th>Đơn hàng <i class="mdi mdi-arrow-up-down sort-icon" data-sort="orders"></i></th>
                                        <th>Tổng chi tiêu <i class="mdi mdi-arrow-up-down sort-icon" data-sort="spent"></i></th>
                                        <th>Ngày đăng ký</th>
                                        <th>Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($users as $user)
                                        <tr>
                                            <td>
                                                <img src="{{ $user->avatar ?? 'https://cdn.pixabay.com/photo/2018/11/13/22/01/avatar-3814081_640.png' }}" 
                                                     class="user-avatar" alt="Avatar">
                                            </td>
                                            <td>
                                                <div class="user-name">{{ $user->full_name }}</div>
                                                <div class="user-username">{{ $user->user_name }}</div>
                                            </td>
                                            <td>{{ $user->email }}</td>
                                            <td>{{ $user->phone_number ?? 'N/A' }}</td>
                                            <td>
                                                <span class="badge badge-role-{{ $user->role_type }}">
                                                    {{ match($user->role_type) {
                                                        0 => 'Khách hàng',
                                                        1 => 'Admin', 
                                                        2 => 'Nhân viên',
                                                        default => 'Không xác định'
                                                    } }}
                                                </span>
                                            </td>
                                            <td>{{ $user->orders_count }}</td>
                                            <td>{{ number_format($user->total_spent ?? 0) }}đ</td>
                                            <td>{{ $user->created_at->format('d/m/Y') }}</td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary view-user" 
                                                        data-user-id="{{ $user->user_id }}">
                                                    <i class="mdi mdi-eye"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @include('admin.layouts.pagination', ['paginator' => $users, 'itemName' => 'người dùng'])
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('admin.users.modals.details')
@endsection

@section('body-script')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="{{ asset('js/admin/users/index.js') }}"></script>
@endsection
