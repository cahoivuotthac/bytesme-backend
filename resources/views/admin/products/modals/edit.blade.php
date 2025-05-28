<div class="modal fade vintage-modal" id="editProductModal" tabindex="-1">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title vintage-title">
					<i class="mdi mdi-package-variant"></i>
					Chỉnh sửa sản phẩm
				</h5>
				<button type="button" class="btn-close vintage-close" data-bs-dismiss="modal"></button>
			</div>
			<div class="modal-body">
				<form id="editProductForm" method="POST" enctype="multipart/form-data">
					@csrf
					@method('PUT')
					<div class="row">
						<div class="col-md-6 mb-3">
							<div class="form-group">
								<label class="form-label vintage-label">Mã sản phẩm</label>
								<input type="text" class="form-control vintage-input inactive-muted-text" id="editCode"
									readonly>
							</div>
						</div>
						<div class="col-md-6 mb-4">
							<div class="form-group">
								<label class="form-label vintage-label">Tên sản phẩm</label>
								<input type="text" class="form-control vintage-input inactive-muted-text" id="editName"
									name="product_name">
							</div>
						</div>
					</div>

					<div class="mb-4">
						<div class="form-group">
							<label class="form-label vintage-label">Mô tả chi tiết</label>
							<textarea class="form-control vintage-textarea inactive-muted-text" id="editDescription"
								name="product_description" rows="3"></textarea>
						</div>
					</div>

					<div class="row">
						<div class="col-md-4 mb-3">
							<div class="form-group">
								<label class="form-label vintage-label">Danh mục</label>
								<select class="form-select vintage-input" id="editCategorySelect" name="category_id">
									<option value="">Chọn danh mục</option>
								</select>
							</div>
						</div>
						<div class="col-md-4 mb-3">
							<div class="form-group">
								<label class="form-label vintage-label">Giảm giá</label>
								<div class="input-group">
									<input type="number" name="product_discount_percentage"
										class="form-control vintage-input inactive-muted-text" id="editDiscount" min="0"
										max="100" value="0">
									<span class="input-group-text vintage-addon">%</span>
								</div>
							</div>
						</div>
						<div class="col-md-4 mb-3">
							<div class="form-group">
								<label class="form-label vintage-label">Tồn kho tổng</label>
								<input type="number" name="product_stock_quantity"
									class="form-control vintage-input inactive-muted-text" id="editTotalStock">
							</div>
						</div>
					</div>

					<!-- Sizes and Prices Section -->
					<div class="mb-4">
						<div class="form-group">
							<div class="d-flex justify-content-between align-items-center mb-2">
								<label class="form-label vintage-label">Kích thước và giá</label>
								<button type="button" class="btn btn-sm btn-success" id="addSizePriceBtn">
									<i class="mdi mdi-plus"></i> Thêm size
								</button>
							</div>
							<div id="sizes-prices-container">
								<!-- Size/price rows will be populated here -->
							</div>
						</div>
					</div>

					<div class="form-group mt-5">
						<label class="form-label vintage-label" id="imgCounter">Hình ảnh sản phẩm (tối đa 5)</label>
						<input type="file" name="images[]" accept="image/*" multiple
							class="form-control visually-hidden">
						<div class="d-flex flex-wrap border-dashed" id="preview-image-container">
							<!-- Images will be populated here -->
							<!-- Add new image button -->
							<div class="p-2" style="background-color: transparent;">
								<div class="d-flex preview-image justify-content-center align-items-center border border-1 border-dark"
									style="cursor: pointer; max-width: 40px !important;">
									<span class="fs-3">+</span>
								</div>
							</div>
						</div>
					</div>
				</form>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-outline-secondary vintage-btn" data-bs-dismiss="modal">
					<i class="mdi mdi-close"></i> Đóng
				</button>
				<button type="submit" class="btn btn-primary vintage-btn-primary" id="saveProductChanges">
					<i class="mdi mdi-content-save"></i> Lưu thay đổi
				</button>
			</div>
		</div>
	</div>
</div>

<link rel="stylesheet" href="{{ asset('css/admin/products/modals/edit.css') }}">
<script src="{{ asset('js/admin/products/modals/edit.js') }}"></script>