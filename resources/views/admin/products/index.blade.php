@php
	use Illuminate\Support\Str;
@endphp

@extends('admin.layouts.layout')
@section('title', 'Quản lý sản phẩm')

@section('style')
	<link rel="stylesheet" href="{{ asset('css/admin/products/index.css') }}">
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
		<!-- Add Christmas decorations -->
		<div class="christmas-decorations">
			<img src="{{ asset('images/pie1.webp') }}" class="holly-left" alt="Holly">
			<img src="{{ asset('images/pie1.webp') }}" class="holly-right" alt="Holly">
		</div>

		<div class="snow-overlay"></div>

		<div class="row">
			<div class="col-12">
				<!-- Add festive header -->
				<div class="festive-header mb-4">
					<h2 class="text-center">
						<i class="fas fa-gifts christmas-icon"></i>
						Quản lý sản phẩm
						<i class="fas fa-holly-berry christmas-icon"></i>
					</h2>
				</div>

				<!-- Analytics Dashboard -->
				<div class="analytics-dashboard">
					<!-- Metrics Cards -->
					<div class="metrics-grid" id="metricsGrid">
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
									<h3 class="chart-title">Phân bố theo danh mục</h3>
									<p class="chart-subtitle">Số lượng sản phẩm trong mỗi danh mục</p>
								</div>
							</div>
							<div class="chart-wrapper">
								<canvas id="categoryChart"></canvas>
							</div>
						</div>

						<div class="chart-container">
							<div class="chart-header">
								<div>
									<h3 class="chart-title">Sản phẩm bán chạy</h3>
									<p class="chart-subtitle">Top 5 sản phẩm có đơn hàng nhiều nhất</p>
								</div>
							</div>
							<div id="topProductsList" class="top-products-list">
								<div class="chart-loading">
									<div class="loading-spinner"></div>
									Đang tải...
								</div>
							</div>
						</div>
					</div>

					<div class="charts-grid">
						<div class="chart-container">
							<div class="chart-header">
								<div>
									<h3 class="chart-title">Thêm sản phẩm theo tháng</h3>
									<p class="chart-subtitle">12 tháng gần nhất</p>
								</div>
							</div>
							<div class="chart-wrapper">
								<canvas id="monthlyChart"></canvas>
							</div>
						</div>

						<div class="chart-container">
							<div class="chart-header">
								<div>
									<h3 class="chart-title">Phân bố đánh giá</h3>
									<p class="chart-subtitle">Chất lượng sản phẩm theo rating</p>
								</div>
							</div>
							<div id="ratingDistribution">
								<div class="chart-loading">
									<div class="loading-spinner"></div>
									Đang tải...
								</div>
							</div>
						</div>
					</div>
				</div>

				<!-- Existing search and filter cards -->
				<div class="card christmas-card mb-3">
					<div class="card-body">
						<div class="row">
							<div class="col-md-8">
								<div class="search-box">
									<input type="text" id="searchInput" class="form-control"
										placeholder="Tìm kiếm theo mã, tên hoặc mô tả sản phẩm..."
										value="{{ request('search') }}">
								</div>
							</div>
							<div class="col-md-2">
								<select class="form-select" id="searchType">
									<option value="product_code" {{ request('type') === 'product_code' ? 'selected' : '' }}>Mã
										SP</option>
									<option value="product_name" {{ request('type') === 'product_name' ? 'selected' : '' }}>
										Tên SP</option>
									<option value="product_description" {{ request('type') === 'product_description' ? 'selected' : '' }}>Mô tả ngắn</option>
								</select>
							</div>
							<div class="col-md-2">
								<button class="btn btn-primary w-100" id="searchButton">
									<i class="text-light mdi mdi-magnify"></i> Tìm kiếm
								</button>
							</div>
						</div>
					</div>
				</div>

				<div class="card christmas-card mb-3">
					<div class="card-body">
						<div class="d-flex justify-content-between align-items-center mb-3">
							<h4 class="mb-0">Bộ lọc</h4>
							<button class="btn btn-light add-product">
								<i class="mdi mdi-plus"></i> Thêm sản phẩm
							</button>
						</div>
						<div class="row">
							<div class="col-md-4">
								<select class="form-select" id="categoryFilter">
									<option value="">Tất cả danh mục</option>
									@foreach($categories as $category)
										<option value="{{ $category->category_id }}" {{ $category->category_id === (int) request('category') ? "selected" : "" }}>
											{{ $category->category_name }}
										</option>
									@endforeach
								</select>
							</div>
							<div class="col-md-4">
								<select class="form-select" id="stockFilter">
									<option value="">Tình trạng kho</option>
									<option value="in_stock">Còn hàng</option>
									<option value="low_stock">Sắp hết (< 10)</option>
									<option value="out_of_stock">Hết hàng</option>
								</select>
							</div>
							<div class="col-md-4">
								<button class="btn btn-filter w-100" id="applyFilters">
									<i class="mdi mdi-filter"></i> Áp dụng lọc
								</button>
							</div>
						</div>
					</div>
				</div>

				<div class="card christmas-card">
					<div class="card-body">
						<div class="table-responsive">
							<table class="table">
								<thead>
									<tr>
										<th>Hình ảnh</th>
										<th>Mã SP <i class="mdi mdi-arrow-up-down sort-icon" data-sort="code"></i></th>
										<th>Mô tả ngắn</th>
										<th>Mô tả</th>
										<th>Tồn kho <i class="mdi mdi-arrow-up-down sort-icon" data-sort="stock"></i></th>
										<th>Đã bán <i class="mdi mdi-arrow-up-down sort-icon" data-sort="sold"></i></th>
										<th>Đánh giá <i class="mdi mdi-arrow-up-down sort-icon" data-sort="rating"></i></th>
										<th>Giá <i class="mdi mdi-arrow-up-down sort-icon" data-sort="price"></i></th>
										<th>Thao tác</th>
									</tr>
								</thead>
								<tbody>
									@foreach($products as $product)
										<tr>
											<td>
												<img src="{{ $product->product_images->first()?->product_image_url ?? '/images/placeholder.jpg' }}"
													class="product-thumbnail" alt="Product Image">
											</td>
											<td>{{ $product->product_code }}</td>
											<td>{{ $product->product_name }}</td>
											<td class="description-cell editable-cell"
												data-product-id="{{ $product->product_id }}" data-field="description">
												@if (!empty($product->product_description))
													{{ Str::limit($product->product_description, 50) }}
												@else
													<span class="text-muted">N/A</span>
												@endif
											</td>
											<td>{{ $product->product_stock_quantity }}</td>
											<td>{{ $product->total_orders }}</td>
											<td>
												@if($product->product_total_ratings > 0)
													<span class="rating">
														{{ number_format($product->product_overall_stars, 1) }}
														<i class="mdi mdi-star text-warning"></i>
													</span>
												@else
													<span class="text-muted">N/A</span>
												@endif
											</td>
											<td>{{ number_format($product->price) }}đ</td>
											<td>
												<button class="btn btn-sm btn-outline-primary edit-product"
													data-product-id="{{ $product->product_id }}">
													<i class="mdi mdi-pencil"></i>
												</button>
											</td>
										</tr>
									@endforeach
								</tbody>
							</table>
						</div>
						<!-- {{ $products->links('pagination::simple-bootstrap-4') }} -->
						@include('admin.layouts.pagination', ['paginator' => $products, 'itemName' => 'sản phẩm'])
					</div>
				</div>
			</div>
		</div>
	</div>

	@include('admin.products.modals.edit')
@endsection

@section('body-script')
	<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
	<script src="{{ asset('js/admin/products/index.js') }}"></script>
@endsection