<nav class="sidebar sidebar-offcanvas" id="sidebar">
	<ul class="nav">
		<li class="nav-item nav-profile border-bottom">
			<a href="#" class="nav-link flex-column">
				<div class="nav-profile-image">
					<img src="https://imgcdn.stablediffusionweb.com/2024/11/29/ee8eaf23-2480-4bd4-bd6d-a93b0447c662.jpg"
						alt="profile">
				</div>
				<div class="nav-profile-text d-flex ms-0 mb-3 flex-column">
					<span class="fw-semibold mb-1 mt-2 text-center">{{ auth()->user()->full_name }}</span>
					<span class="text-secondary icon-sm text-center">{{ auth()->user()->user_name }}</span>
				</div>
			</a>
		</li>
		<li class="nav-item pt-3">
			<a class="nav-link d-block" href="{{ route('admin.dashboard.showDashboardPage') }}">
				<div class="fw-bold pt-1" style="font-size: 1.2rem; color: white;">🍰 Bytesme</div>
				<div class="small fw-light pt-1" style="color: rgba(255,255,255,0.8);">Admin Panel</div>
			</a>
		</li>
		<li class="pt-2 pb-1">
			<span class="nav-item-head"
				style="color: rgba(255,255,255,0.7); font-weight: 600; letter-spacing: 1px;">QUẢN LÝ</span>
		</li>
		<li class="nav-item">
			<a class="nav-link" href="{{ route('admin.dashboard.showDashboardPage') }}">
				<i class="mdi mdi-view-dashboard menu-icon"></i>
				<span class="menu-title">Dashboard</span>
			</a>
		</li>
		<li class="nav-item">
			<a class="nav-link" href="{{ route('admin.orders.showOrdersPage') }}">
				<i class="menu-icon mdi mdi-cart"></i>
				<span class="menu-title">Đơn hàng</span>
				<div class="menu-sub-title">Quản lý đơn hàng</div>
			</a>
		</li>
		<li class="nav-item">
			<a class="nav-link" href="{{ route('admin.products.index') }}">
				<i class="menu-icon mdi mdi-cupcake"></i>
				<span class="menu-title">Sản phẩm</span>
				<div class="menu-sub-title">Bánh & Đồ uống</div>
			</a>
		</li>
		<li class="nav-item">
			<a class="nav-link" href="/admin/users">
				<i class="menu-icon mdi mdi-account-multiple"></i>
				<span class="menu-title">Khách hàng</span>
				<div class="menu-sub-title">Quản lý người dùng</div>
			</a>
		</li>
		<li class="nav-item">
			<a class="nav-link" href="{{ route('admin.vouchers.showVouchersPage') }}">
				<i class="menu-icon mdi mdi-tag-multiple"></i>
				<span class="menu-title">Khuyến mãi</span>
				<div class="menu-sub-title">Mã giảm giá</div>
			</a>
		</li>
		<li class="pt-2 pb-1">
			<span class="nav-item-head"
				style="color: rgba(255,255,255,0.7); font-weight: 600; letter-spacing: 1px;">BÁNH CÁO</span>
		</li>
		<li class="nav-item">
			<a class="nav-link" href="/admin/analytics">
				<i class="menu-icon mdi mdi-chart-line"></i>
				<span class="menu-title">Phân tích</span>
				<div class="menu-sub-title">Dữ liệu & Báo cáo</div>
			</a>
		</li>
		<li class="nav-item">
			<a class="nav-link" href="/admin/notifications">
				<i class="menu-icon mdi mdi-bell"></i>
				<span class="menu-title">Thông báo</span>
				<div class="menu-sub-title">Tin nhắn hệ thống</div>
			</a>
		</li>
		<li class="nav-item">
			<a class="nav-link" href="/admin/settings">
				<i class="menu-icon mdi mdi-cogs"></i>
				<span class="menu-title">Cài đặt</span>
				<div class="menu-sub-title">Hệ thống</div>
			</a>
		</li>
	</ul>
</nav>

<style>
	.menu-sub-title {
		font-size: 11px;
		color: rgba(255, 255, 255, 0.6);
		margin-top: 2px;
		font-weight: 400;
	}

	.nav-item-head {
		display: block;
		padding: 10px 20px 5px 20px;
		font-size: 11px;
		text-transform: uppercase;
	}
</style>