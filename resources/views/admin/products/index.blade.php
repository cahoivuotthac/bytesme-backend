@extends('admin.layouts.layout')
@section('title', 'Quản lý sản phẩm - Bytesme')

@section('lib')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@endsection

@section('content')
<!-- Page Header -->
<div class="dashboard-header fade-in-up mb-4">
	<div class="d-flex justify-content-between align-items-center">
		<div>
			<h2 class="dashboard-title">🧁 Quản lý sản phẩm</h2>
			<p class="dashboard-subtitle">Quản lý danh mục bánh ngọt, đồ uống và dessert</p>
		</div>
		<div class="header-actions">
			<button class="btn btn-bytesme-primary" onclick="openAddProductModal()">
				<i class="mdi mdi-plus"></i> Thêm sản phẩm mới
			</button>
		</div>
	</div>
</div>

<!-- Product Statistics Cards -->
<div class="metrics-grid fade-in-up mb-4">
	<div class="metric-card">
		<div class="metric-icon">
			<i class="mdi mdi-package-variant"></i>
		</div>
		<div class="metric-value">{{ $products->total() }}</div>
		<div class="metric-label">Tổng sản phẩm</div>
		<div class="metric-change">
			<i class="mdi mdi-trending-up"></i> Đang kinh doanh
		</div>
	</div>

	<div class="metric-card">
		<div class="metric-icon">
			<i class="mdi mdi-chart-line"></i>
		</div>
		<div class="metric-value">{{ $products->where('total_orders', '>', 0)->count() }}</div>
		<div class="metric-label">Có doanh số</div>
		<div class="metric-change text-success">
			<i class="mdi mdi-check"></i> {{ round(($products->where('total_orders', '>', 0)->count() / max($products->total(), 1)) * 100, 1) }}% tổng SP
		</div>
	</div>

	<div class="metric-card">
		<div class="metric-icon">
			<i class="mdi mdi-alert-circle"></i>
		</div>
		<div class="metric-value">{{ $products->where('stock_quantity', '<', 10)->count() }}</div>
		<div class="metric-label">Sắp hết hàng</div>
		<div class="metric-change text-warning">
			<i class="mdi mdi-alert"></i> Cần nhập thêm
		</div>
	</div>

	<div class="metric-card">
		<div class="metric-icon">
			<i class="mdi mdi-star"></i>
		</div>
		<div class="metric-value">{{ number_format($products->where('overall_stars', '>', 0)->avg('overall_stars') ?? 0, 1) }}</div>
		<div class="metric-label">Đánh giá TB</div>
		<div class="metric-change text-success">
			<i class="mdi mdi-star"></i> Chất lượng tốt
		</div>
	</div>
</div>

<!-- Analytics Charts -->
<div class="row mb-4">
	<div class="col-lg-8 col-md-12 mb-3">
		<div class="chart-container fade-in-up">
			<div class="chart-header">
				<h5 class="chart-title">📊 Top sản phẩm bán chạy</h5>
			</div>
			<canvas id="topProductsChart" height="300"></canvas>
		</div>
	</div>
	
	<div class="col-lg-4 col-md-12 mb-3">
		<div class="chart-container fade-in-up">
			<div class="chart-header">
				<h5 class="chart-title">🏷️ Phân bố theo danh mục</h5>
			</div>
			<canvas id="categoryChart" height="300"></canvas>
		</div>
	</div>
</div>

<!-- Low Stock Alert -->
@php $lowStockProducts = $products->where('stock_quantity', '<', 10)->where('stock_quantity', '>', 0) @endphp
@if($lowStockProducts->count() > 0)
<div class="alert alert-warning fade-in-up mb-4">
	<div class="d-flex align-items-center">
		<i class="mdi mdi-alert-circle me-2 fs-4"></i>
		<div>
			<h6 class="mb-1">⚠️ Cảnh báo tồn kho thấp</h6>
			<p class="mb-0">Có {{ $lowStockProducts->count() }} sản phẩm sắp hết hàng cần nhập thêm</p>
		</div>
	</div>
</div>
@endif

<!-- Search and Filters -->
<div class="card fade-in-up mb-4">
	<div class="card-header">
		<h5>🔍 Tìm kiếm & Bộ lọc</h5>
	</div>
	<div class="card-body">
		<form method="GET" action="{{ route('admin.products.index') }}" id="filterForm">
			<div class="row g-3">
				<div class="col-md-4">
					<label class="form-label">Tìm kiếm</label>
					<div class="input-group">
						<input type="text" class="form-control" name="search" 
							   placeholder="Tìm theo mã, tên sản phẩm..." 
							   value="{{ request('search') }}">
						<select class="form-select" name="type" style="max-width: 150px;">
							<option value="code" {{ request('type') == 'code' ? 'selected' : '' }}>Mã SP</option>
							<option value="name" {{ request('type') == 'name' ? 'selected' : '' }}>Tên SP</option>
							<option value="short_description" {{ request('type') == 'short_description' ? 'selected' : '' }}>Mô tả</option>
						</select>
					</div>
				</div>
				
				<div class="col-md-2">
					<label class="form-label">Danh mục</label>
					<select class="form-select" name="category">
						<option value="">Tất cả</option>
						@foreach($categories as $category)
							<option value="{{ $category->category_id }}" {{ request('category') == $category->category_id ? 'selected' : '' }}>
								{{ $category->name }}
							</option>
						@endforeach
					</select>
				</div>
				
				<div class="col-md-2">
					<label class="form-label">Tồn kho</label>
					<select class="form-select" name="stock">
						<option value="">Tất cả</option>
						<option value="in_stock" {{ request('stock') == 'in_stock' ? 'selected' : '' }}>Còn hàng (>10)</option>
						<option value="low_stock" {{ request('stock') == 'low_stock' ? 'selected' : '' }}>Sắp hết (1-10)</option>
						<option value="out_of_stock" {{ request('stock') == 'out_of_stock' ? 'selected' : '' }}>Hết hàng (0)</option>
					</select>
				</div>
				
				<div class="col-md-2">
					<label class="form-label">Sắp xếp</label>
					<select class="form-select" name="sort">
						<option value="created_at" {{ request('sort') == 'created_at' ? 'selected' : '' }}>Mới nhất</option>
						<option value="code" {{ request('sort') == 'code' ? 'selected' : '' }}>Mã SP</option>
						<option value="name" {{ request('sort') == 'name' ? 'selected' : '' }}>Tên SP</option>
						<option value="stock" {{ request('sort') == 'stock' ? 'selected' : '' }}>Tồn kho</option>
						<option value="sold" {{ request('sort') == 'sold' ? 'selected' : '' }}>Đã bán</option>
						<option value="rating" {{ request('sort') == 'rating' ? 'selected' : '' }}>Đánh giá</option>
						<option value="price" {{ request('sort') == 'price' ? 'selected' : '' }}>Giá</option>
					</select>
				</div>
				
				<div class="col-md-2">
					<label class="form-label">&nbsp;</label>
					<div class="d-flex gap-2">
						<button type="submit" class="btn btn-bytesme-primary">
							<i class="mdi mdi-magnify"></i> Tìm
						</button>
						<a href="{{ route('admin.products.index') }}" class="btn btn-bytesme-secondary">
							<i class="mdi mdi-refresh"></i>
						</a>
					</div>
				</div>
			</div>
		</form>
	</div>
</div>

<!-- Products Table -->
<div class="card fade-in-up">
	<div class="card-header">
		<h5>🧁 Danh sách sản phẩm ({{ $products->total() }} sản phẩm)</h5>
	</div>
	<div class="card-body">
		<div class="table-responsive">
			<table class="table table-hover">
				<thead>
					<tr>
						<th style="width: 80px;">Hình ảnh</th>
						<th>Mã SP</th>
						<th>Tên sản phẩm</th>
						<th>Danh mục</th>
						<th class="text-center">Tồn kho</th>
						<th class="text-center">Đã bán</th>
						<th class="text-center">Đánh giá</th>
						<th class="text-end">Giá</th>
						<th class="text-center">Thao tác</th>
					</tr>
				</thead>
				<tbody>
					@foreach($products as $product)
					<tr>
						<td>
							<div class="product-image-container">
								<img src="{{ $product->product_images->first()?->product_image_url ?? '/images/placeholder.jpg' }}"
									 class="product-thumbnail rounded" 
									 alt="{{ $product->short_description }}"
									 style="width: 60px; height: 60px; object-fit: cover;">
							</div>
						</td>
						<td>
							<div class="fw-bold">{{ $product->code }}</div>
							<small class="text-muted">ID: {{ $product->product_id }}</small>
						</td>
						<td>
							<div class="fw-semibold">{{ $product->short_description }}</div>
							@if($product->detailed_description)
								<small class="text-muted">{{ Str::limit($product->detailed_description, 50) }}</small>
							@endif
						</td>
						<td>
							@foreach($product->categories as $category)
								<span class="badge badge-bytesme-light me-1">{{ $category->name }}</span>
							@endforeach
						</td>
						<td class="text-center">
							<span class="badge 
								@if($product->stock_quantity > 10) bg-success
								@elseif($product->stock_quantity > 0) bg-warning
								@else bg-danger
								@endif
							">
								{{ $product->stock_quantity }}
							</span>
						</td>
						<td class="text-center">
							<div class="fw-bold">{{ $product->total_orders ?? 0 }}</div>
							<small class="text-muted">lượt</small>
						</td>
						<td class="text-center">
							@if($product->overall_stars > 0)
								<div class="rating-display">
									<span class="fw-bold">{{ number_format($product->overall_stars, 1) }}</span>
									<i class="mdi mdi-star text-warning"></i>
								</div>
								<small class="text-muted">({{ $product->total_ratings }} đánh giá)</small>
							@else
								<span class="text-muted">Chưa có</span>
							@endif
						</td>
						<td class="text-end">
							<div class="fw-bold text-success">₫{{ number_format($product->price, 0, ',', '.') }}</div>
							@if($product->discount_percentage > 0)
								<small class="text-danger">-{{ $product->discount_percentage }}%</small>
							@endif
						</td>
						<td class="text-center">
							<div class="btn-group">
								<button class="btn btn-outline-primary btn-sm edit-product-btn" 
										data-product-id="{{ $product->product_id }}">
									<i class="mdi mdi-pencil"></i>
								</button>
								<button class="btn btn-outline-info btn-sm view-product-btn" 
										data-product-id="{{ $product->product_id }}">
									<i class="mdi mdi-eye"></i>
								</button>
								@if($product->stock_quantity < 10)
								<button class="btn btn-outline-warning btn-sm restock-btn" 
										data-product-id="{{ $product->product_id }}">
									<i class="mdi mdi-package-up"></i>
								</button>
								@endif
							</div>
						</td>
					</tr>
					@endforeach
				</tbody>
			</table>
		</div>
		
		<!-- Pagination -->
		<div class="d-flex justify-content-between align-items-center mt-4">
			<div class="text-muted">
				Hiển thị {{ $products->firstItem() ?? 0 }} - {{ $products->lastItem() ?? 0 }} của {{ $products->total() }} sản phẩm
			</div>
			{{ $products->links() }}
		</div>
	</div>
</div>

<!-- Product Details Modal -->
<div class="modal fade" id="productModal" tabindex="-1">
	<div class="modal-dialog modal-xl">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">Chi tiết sản phẩm</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
			</div>
			<div class="modal-body" id="productModalBody">
				<div class="text-center">
					<div class="spinner-border text-primary" role="status">
						<span class="visually-hidden">Đang tải...</span>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

@if(session('success'))
	<script>
		Swal.fire({
			icon: 'success',
			title: 'Thành công!',
			text: '{{ session('success') }}',
			confirmButtonColor: '#FF6B35'
		});
	</script>
@endif

@if(session('error'))
	<script>
		Swal.fire({
			icon: 'error',
			title: 'Lỗi!',
			text: '{{ session('error') }}',
			confirmButtonColor: '#FF6B35'
		});
	</script>
@endif

@endsection

@section('body-script')
<script>
$(document).ready(function() {
	initializeCharts();
	initializeEventHandlers();
});

function initializeCharts() {
	// Top products chart
	const topProductsCtx = document.getElementById('topProductsChart').getContext('2d');
	const topProducts = @json($products->sortByDesc('total_orders')->take(10)->values());
	
	new Chart(topProductsCtx, {
		type: 'bar',
		data: {
			labels: topProducts.map(p => p.short_description.substring(0, 20) + '...'),
			datasets: [{
				label: 'Số lượng đã bán',
				data: topProducts.map(p => p.total_orders || 0),
				backgroundColor: 'rgba(255, 107, 53, 0.8)',
				borderColor: 'rgba(255, 107, 53, 1)',
				borderWidth: 1
			}]
		},
		options: {
			responsive: true,
			maintainAspectRatio: false,
			plugins: {
				legend: {
					display: false
				}
			},
			scales: {
				y: {
					beginAtZero: true
				}
			}
		}
	});

	// Category distribution chart
	const categoryCtx = document.getElementById('categoryChart').getContext('2d');
	const categoryData = @json($categories->mapWithKeys(function($cat) use ($products) {
		return [$cat->name => $products->filter(function($product) use ($cat) {
			return $product->categories->contains('category_id', $cat->category_id);
		})->count()];
	}));
	
	new Chart(categoryCtx, {
		type: 'doughnut',
		data: {
			labels: Object.keys(categoryData),
			datasets: [{
				data: Object.values(categoryData),
				backgroundColor: [
					'#FF6B35', '#F7931E', '#FFB74D', '#FFA726', 
					'#FF9800', '#FF8A65', '#FFAB91', '#FFCC80'
				],
				borderWidth: 0
			}]
		},
		options: {
			responsive: true,
			maintainAspectRatio: false,
			plugins: {
				legend: {
					position: 'bottom'
				}
			}
		}
	});
}

function initializeEventHandlers() {
	// Edit product
	$('.edit-product-btn').click(function() {
		const productId = $(this).data('product-id');
		loadProductDetails(productId, true);
	});

	// View product details
	$('.view-product-btn').click(function() {
		const productId = $(this).data('product-id');
		loadProductDetails(productId, false);
	});

	// Restock product
	$('.restock-btn').click(function() {
		const productId = $(this).data('product-id');
		showRestockModal(productId);
	});
}

function loadProductDetails(productId, editMode = false) {
	$('#productModal').modal('show');
	$('#productModalBody').html('<div class="text-center"><div class="spinner-border text-primary" role="status"></div></div>');
	
	$.get(`/admin/products/${productId}/details`, function(response) {
		if (response.success) {
			const product = response.product;
			let html = `
				<div class="row">
					<div class="col-md-4">
						<div class="product-images">
							${product.product_images.map(img => `
								<img src="${img.product_image_url}" class="img-fluid rounded mb-2" alt="Product Image">
							`).join('')}
						</div>
					</div>
					<div class="col-md-8">
						<h4>${product.short_description}</h4>
						<p class="text-muted">Mã sản phẩm: ${product.code}</p>
						
						<div class="row mb-3">
							<div class="col-md-6">
								<strong>Giá:</strong> ₫${formatNumber(product.price)}
								${product.discount_percentage > 0 ? `<span class="text-danger">(-${product.discount_percentage}%)</span>` : ''}
							</div>
							<div class="col-md-6">
								<strong>Tồn kho:</strong> 
								<span class="badge ${product.stock_quantity > 10 ? 'bg-success' : product.stock_quantity > 0 ? 'bg-warning' : 'bg-danger'}">
									${product.stock_quantity}
								</span>
							</div>
						</div>
						
						<div class="row mb-3">
							<div class="col-md-6">
								<strong>Đã bán:</strong> ${product.total_orders || 0} lượt
							</div>
							<div class="col-md-6">
								<strong>Đánh giá:</strong> 
								${product.overall_stars > 0 ? `${product.overall_stars}/5 ⭐ (${product.total_ratings} đánh giá)` : 'Chưa có đánh giá'}
							</div>
						</div>
						
						<div class="mb-3">
							<strong>Danh mục:</strong>
							${product.categories.map(cat => `<span class="badge badge-bytesme-light me-1">${cat.name}</span>`).join('')}
						</div>
						
						${product.detailed_description ? `
							<div class="mb-3">
								<strong>Mô tả chi tiết:</strong>
								<p>${product.detailed_description}</p>
							</div>
						` : ''}
						
						${editMode ? `
							<div class="d-flex gap-2">
								<button class="btn btn-bytesme-primary" onclick="openEditForm(${product.product_id})">
									<i class="mdi mdi-pencil"></i> Chỉnh sửa
								</button>
								${product.stock_quantity < 10 ? `
									<button class="btn btn-warning" onclick="showRestockModal(${product.product_id})">
										<i class="mdi mdi-package-up"></i> Nhập hàng
									</button>
								` : ''}
							</div>
						` : ''}
					</div>
				</div>
			`;
			
			$('#productModalBody').html(html);
		} else {
			$('#productModalBody').html('<div class="alert alert-danger">Không thể tải thông tin sản phẩm</div>');
		}
	}).fail(function() {
		$('#productModalBody').html('<div class="alert alert-danger">Lỗi kết nối</div>');
	});
}

function showRestockModal(productId) {
	Swal.fire({
		title: 'Nhập hàng',
		text: 'Nhập số lượng cần thêm vào kho:',
		input: 'number',
		inputAttributes: {
			min: 1,
			step: 1
		},
		showCancelButton: true,
		confirmButtonText: 'Cập nhật',
		cancelButtonText: 'Hủy',
		confirmButtonColor: '#FF6B35',
		inputValidator: (value) => {
			if (!value || value < 1) {
				return 'Vui lòng nhập số lượng hợp lệ!';
			}
		}
	}).then((result) => {
		if (result.isConfirmed) {
			updateStock(productId, result.value);
		}
	});
}

function updateStock(productId, quantity) {
	$.post(`/admin/products/${productId}/update-field`, {
		field: 'stock_quantity',
		value: quantity,
		_token: $('meta[name="csrf-token"]').attr('content')
	}).done(function(response) {
		if (response.success) {
			Swal.fire('Thành công!', 'Đã cập nhật số lượng tồn kho', 'success').then(() => {
				location.reload();
			});
		} else {
			Swal.fire('Lỗi!', response.message, 'error');
		}
	}).fail(function() {
		Swal.fire('Lỗi!', 'Không thể cập nhật số lượng tồn kho', 'error');
	});
}

function formatNumber(num) {
	return new Intl.NumberFormat('vi-VN').format(num);
}

function openAddProductModal() {
	// TODO: Implement add product modal
	Swal.fire('Thông báo', 'Chức năng thêm sản phẩm đang được phát triển', 'info');
}

function openEditForm(productId) {
	// TODO: Implement edit form
	Swal.fire('Thông báo', 'Chức năng chỉnh sửa sản phẩm đang được phát triển', 'info');
}
</script>
@endsection