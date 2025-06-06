$(document).ready(function () {
    // Add snow effect
    const snowflakes = 50;
    const snowContainer = $(".snow-overlay");

    for (let i = 0; i < snowflakes; i++) {
        const snow = $('<div class="snowflake">❆</div>');
        snow.css({
            left: `${Math.random() * 100}%`,
            "animation-delay": `${Math.random() * 3}s`,
            "animation-duration": `${Math.random() * 3 + 2}s`,
        });
        snowContainer.append(snow);
    }

    // Show success alert on page load if any
    if (sessionStorage.getItem("success")) {
        showAlert("success", sessionStorage.getItem("success"));
        sessionStorage.removeItem("success");
    } else if (sessionStorage.getItem("error")) {
        showAlert("error", sessionStorage.getItem("error"));
        sessionStorage.removeItem("error");
    }

    // Handle filters
    $("#applyFilters").click(function () {
        const category = $("#categoryFilter").val();
        const stock = $("#stockFilter").val();

        const url = new URL(window.location.href);
        if (category != null) url.searchParams.set("category", category);
        if (stock != null) url.searchParams.set("stock", stock);

        window.location.href = url.toString();
    });

    // Handle sorting
    $(".sort-icon").click(function () {
        const sort = $(this).data("sort");
        const currentSort = new URLSearchParams(window.location.search).get(
            "sort"
        );
        const currentDirection = new URLSearchParams(
            window.location.search
        ).get("direction");

        let newDirection = "desc";
        if (currentSort === sort) {
            newDirection = currentDirection === "desc" ? "asc" : "desc";
        }

        const url = new URL(window.location.href);
        url.searchParams.set("sort", sort);
        url.searchParams.set("direction", newDirection);

        window.location.href = url.toString();
    });

    // Change sort icon directions based on request
    (function updateSortIcons() {
        const currentSort = new URLSearchParams(window.location.search).get(
            "sort"
        );
        const currentDirection = new URLSearchParams(
            window.location.search
        ).get("direction");

        $(".sort-icon").each(function () {
            const sort = $(this).data("sort");
            if (sort === currentSort) {
                $(this)
                    .removeClass("mdi-arrow-up-down")
                    .addClass(
                        currentDirection === "desc"
                            ? "mdi-arrow-down"
                            : "mdi-arrow-up"
                    );
            }
        });
    })();

    // Update initial stock filter's value
    (function updateStockFiltersValue() {
        const currentStock = new URLSearchParams(window.location.search).get(
            "stock"
        );
        $(`#stockFilter > option[value="${currentStock}"]`).prop(
            "selected",
            true
        );
    })();

    // Handle description editing
    $(".editable-cell").click(function () {
        const productId = $(this).data("productId");
        const currentText = $(this).text().trim();

        Swal.fire({
            title: "Cập nhật mô tả",
            input: "textarea",
            inputValue: currentText,
            showCancelButton: true,
            confirmButtonText: "Cập nhật",
            cancelButtonText: "Hủy",
            confirmButtonColor: "#435E53",
            customClass: {
                popup: "edit-description-popup",
            },
        }).then((result) => {
            if (result.isConfirmed) {
                updateProductField(
                    productId,
                    "detailed_description",
                    result.value
                );
            }
        });
    });

    // Handle edit product
    $(".edit-product").click(function () {
        const productId = $(this).data("productId");
        console.log("Edit product clicked, id: ", productId);
        showModal("editProductModal");
        loadProductDetails(productId);
    });

    // Handle add product
    $(".add-product").click(function () {
        showModal("editProductModal");
        getAllCategories()
            .then((allCategories) => {
                console.log("All categories retrieved: ", allCategories);
                loadCategoryOptions(allCategories);
                $("#editProductForm")[0].reset();
                $("#editCode").val("");
                $("#editName").val("");
                $("#editDescription").val("");
                $("#editDiscount").val("0");
                $("#editCategorySelect").val("");
                $("#sizes-prices-container").empty();
                $("#editTotalStock").val("0");
                addSizePriceRow(); // Add at least one row for new products
                $("#preview-image-container")
                    .children(":not(:last-child)")
                    .remove();
                $("#imgCounter").text("Hình ảnh sản phẩm (0/5)");
                
                // Set form for creation mode
                const form = $("#editProductForm");
                form.attr("action", "/admin/products/");
                form.attr("data-mode", "create");
            })
            .catch((error) => {
                console.error("Error loading categories:", error);
                showAlert("error", "Không thể tải danh mục sản phẩm");
            });
    });

    // Handle save product changes after edit in modal
    $("#saveProductChanges").click(function (e) {
        e.preventDefault();
        const form = $("#editProductForm");
        const mode = form.attr("data-mode");
        
        const title = mode === "create" ? "Xác nhận thêm sản phẩm?" : "Xác nhận cập nhật sản phẩm?";
        const text = mode === "create" ? "Sản phẩm mới sẽ được tạo." : "Sản phẩm sẽ được cập nhật với thông tin mới.";
        
        Swal.fire({
            title: title,
            text: text,
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#435E53",
            cancelButtonColor: "#6c757d",
            confirmButtonText: "Xác nhận",
            cancelButtonText: "Hủy",
            customClass: {
                popup: "status-confirm-popup",
            },
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    });

    // Handle search
    $("#searchButton").click(function () {
        performSearch();
    });

    // Handle enter key in search input
    $("#searchInput").keypress(function (e) {
        if (e.which == 13) {
            // Enter key
            performSearch();
        }
    });

    // Initialize search input with URL params
    (function initializeSearch() {
        const searchValue = new URLSearchParams(window.location.search).get(
            "search"
        );
        const searchType = new URLSearchParams(window.location.search).get(
            "type"
        );
        if (searchValue) {
            $("#searchInput").val(searchValue);
        }
        if (searchType) {
            $("#searchType").val(searchType);
        }
    })();

    // Initialize analytics dashboard
    loadProductAnalytics();
});

function updateProductField(productId, field, value) {
    $.ajax({
        url: `/admin/products/${productId}/update-field`,
        method: "POST",
        data: {
            field: field,
            value: value,
            _token: $('meta[name="csrf-token"]').attr("content"),
        },
        success: function (response) {
            if (response.success) {
                showAlert("success", "Cập nhật thành công");
                setTimeout(() => window.location.reload(), 1500);
            } else {
                showAlert("error", "Có lỗi xảy ra");
            }
        },
        error: function () {
            showAlert("error", "Có lỗi xảy ra");
        },
    });
}

function showModal(modalHtmlId) {
    const modal = new bootstrap.Modal(
        document.getElementById(`${modalHtmlId}`)
    );
    modal.show();
}

async function getAllCategories() {
    return new Promise((resolve, reject) => {
        $.ajax({
            url: "/product/all-categories",
            method: "GET",
            success: function (response) {
                console.log("All categories: ", response);
                resolve(response);
            },
            error: function (error) {
                console.error("Error fetching categories:", error);
                reject(error);
            },
        });
    });
}

function loadProductDetails(productId) {
    // First load categories, then load product details
    getAllCategories()
        .then((allCategories) => {
            loadCategoryOptions(allCategories);

            // Now load product details
            return $.ajax({
                url: `/admin/products/${productId}/details`,
                method: "GET",
            });
        })
        .then((response) => {
            if (response.success) {
                console.log("Product details: ", response.product);
                const jModal = $("#editProductModal");
                const product = response.product;
                if (!product) {
                    console.error("Product is empty");
                    return;
                }

                // Populate modal with basic product details
                jModal
                    .find("#editDescription")
                    .val(product.product_description || "");
                jModal.find("#editCode").val(product.product_code || "");
                jModal.find("#editName").val(product.product_name || "");
                jModal
                    .find("#editDiscount")
                    .val(product.product_discount_percentage || 0);

                // Set category dropdown - IMPORTANT!
                jModal
                    .find("#editCategorySelect")
                    .val(product.category_id || "");

                // Load sizes and prices from product_unit_price JSON
                const unitPrice = product.product_unit_price || {};
                const sizes = unitPrice.product_sizes
                    ? unitPrice.product_sizes.split("|")
                    : [];
                const prices = unitPrice.product_prices
                    ? unitPrice.product_prices.split("|")
                    : [];

                loadSizesAndPrices(sizes, prices);

                // Set the existing stock quantity
                jModal
                    .find("#editTotalStock")
                    .val(product.product_stock_quantity || 0);

                // Populate modal's image containers
                console.log("Populating images: ", product.product_images);
                const previewContainer = jModal.find(
                    "#preview-image-container"
                );
                const inputElement = jModal.find('input[name="images[]"]')[0];
                inputElement.value = "";
                console.log("cleared input value");

                // Clear existing images first
                previewContainer.children().not(":last").remove();

                // Display existing images
                if (
                    product.product_images &&
                    product.product_images.length > 0
                ) {
                    for (let i = 0; i < product.product_images.length; i++) {
                        const imageUrl =
                            product.product_images[i].product_image_url;
                        console.log("imageUrl is: " + imageUrl);
                        const imgHtml = `
							<div class="position-relative p-2">
								<img src="${imageUrl}" class="preview-image">
								<button type="button" class="btn-close position-absolute top-0 end-0" data-index="${i}" aria-label="Close"></button>
							</div>
						`;
                        previewContainer.children().last().before(imgHtml);
                    }

                    // Read existing images into <input>
                    const dataTransfer = new DataTransfer();
                    let loadedImages = 0;
                    const totalImages = product.product_images.length;

                    if (totalImages > 0) {
                        product.product_images.forEach((img, index) => {
                            fetch(img.product_image_url)
                                .then((res) => res.blob())
                                .then((blob) => {
                                    const file = new File(
                                        [blob],
                                        `image_${index + 1}.jpg`,
                                        { type: blob.type }
                                    );
                                    dataTransfer.items.add(file);
                                    console.log(
                                        "Data transfer files length ",
                                        dataTransfer.files.length
                                    );

                                    loadedImages++;
                                    if (loadedImages === totalImages) {
                                        inputElement.files = dataTransfer.files;
                                        console.log(
                                            "Input files length: ",
                                            inputElement.files.length
                                        );
                                        $("#imgCounter").text(
                                            `Hình ảnh sản phẩm (${totalImages}/5)`
                                        );
                                        jModal.trigger(
                                            "editProductDetailModalLoaded"
                                        );
                                    }
                                })
                                .catch((err) => {
                                    console.error("Error loading image:", err);
                                    loadedImages++;
                                    if (loadedImages === totalImages) {
                                        jModal.trigger(
                                            "editProductDetailModalLoaded"
                                        );
                                    }
                                });
                        });
                    } else {
                        $("#imgCounter").text("Hình ảnh sản phẩm (0/5)");
                        jModal.trigger("editProductDetailModalLoaded");
                    }
                } else {
                    $("#imgCounter").text("Hình ảnh sản phẩm (0/5)");
                    jModal.trigger("editProductDetailModalLoaded");
                }

                // Set the form action URL dynamically for update mode
                const form = $("#editProductForm");
                form.attr("action", `/admin/products/${productId}/update`);
                form.attr("data-mode", "update");
            } else {
                showAlert("error", "Có lỗi xảy ra khi tải thông tin sản phẩm");
            }
        })
        .catch((error) => {
            console.error("Error loading product details:", error);
            showAlert("error", "Có lỗi xảy ra khi tải thông tin sản phẩm");
        });
}

function loadCategoryOptions(allCategories) {
    const categorySelect = $("#editCategorySelect");
    categorySelect.empty();
    categorySelect.append('<option value="">Chọn danh mục</option>');

    allCategories.forEach((cat) => {
        categorySelect.append(
            `<option value="${cat.category_id}">${cat.category_name}</option>`
        );
    });
}

function loadSizesAndPrices(sizes, prices = []) {
    const container = $("#sizes-prices-container");
    container.empty();

    if (sizes.length === 0) {
        addSizePriceRow();
    } else {
        for (let i = 0; i < sizes.length; i++) {
            addSizePriceRow(sizes[i] || "", prices[i] || "");
        }
    }
}

function addSizePriceRow(sizeName = "", price = "") {
    const container = $("#sizes-prices-container");
    const rowHtml = `
		<div class="row mb-2 size-price-row">
			<div class="col-md-5">
				<input type="text" class="form-control vintage-input" name="sizes[]" 
					   placeholder="Tên size" value="${sizeName}">
			</div>
			<div class="col-md-5">
				<input type="number" class="form-control vintage-input" name="prices[]" 
					   placeholder="Giá" value="${price}">
			</div>
			<div class="col-md-2">
				<button type="button" class="btn btn-danger btn-sm remove-size-price">
					<i class="mdi mdi-minus"></i>
				</button>
			</div>
		</div>
	`;
    container.append(rowHtml);
}

function performSearch() {
    const searchValue = $("#searchInput").val().trim();
    const searchType = $("#searchType").val();

    const url = new URL(window.location.href);
    if (searchValue) {
        url.searchParams.set("search", searchValue);
        url.searchParams.set("type", searchType);
    } else {
        url.searchParams.delete("search");
        url.searchParams.delete("type");
    }

    window.location.href = url.toString();
}

// Handle remove size/price row
$(document).on("click", ".remove-size-price", function () {
    $(this).closest(".size-price-row").remove();
    if ($(".size-price-row").length === 0) {
        addSizePriceRow();
    }
});

function loadProductAnalytics() {
    $.ajax({
        url: '/admin/products/analytics/data',
        method: 'GET',
        success: function(response) {
            if (response.success) {
                renderMetricsCards(response.data.inventory);
                renderCategoryChart(response.data.category_distribution);
                renderTopProductsList(response.data.top_selling);
                renderMonthlyChart(response.data.monthly_additions);
                renderRatingDistribution(response.data.rating_stats);
            } else {
                showAlert('error', 'Không thể tải dữ liệu phân tích');
            }
        },
        error: function() {
            showAlert('error', 'Có lỗi khi tải dữ liệu phân tích');
        }
    });
}

function renderMetricsCards(inventory) {
    const metricsHtml = `
        <div class="metric-card">
            <div class="metric-icon total">
                <i class="mdi mdi-package-variant"></i>
            </div>
            <div class="metric-value">${inventory.total}</div>
            <div class="metric-label">Tổng sản phẩm</div>
        </div>
        <div class="metric-card">
            <div class="metric-icon in-stock">
                <i class="mdi mdi-check-circle"></i>
            </div>
            <div class="metric-value">${inventory.in_stock}</div>
            <div class="metric-label">Còn hàng</div>
        </div>
        <div class="metric-card">
            <div class="metric-icon low-stock">
                <i class="mdi mdi-alert-circle"></i>
            </div>
            <div class="metric-value">${inventory.low_stock}</div>
            <div class="metric-label">Sắp hết hàng</div>
        </div>
        <div class="metric-card">
            <div class="metric-icon out-stock">
                <i class="mdi mdi-close-circle"></i>
            </div>
            <div class="metric-value">${inventory.out_of_stock}</div>
            <div class="metric-label">Hết hàng</div>
        </div>
    `;
    $('#metricsGrid').html(metricsHtml);
}

function renderCategoryChart(categoryData) {
    const ctx = document.getElementById('categoryChart').getContext('2d');
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: categoryData.map(item => item.category_name),
            datasets: [{
                data: categoryData.map(item => item.count),
                backgroundColor: [
                    '#435E53', '#6c94a3', '#28a745', '#ffc107', 
                    '#dc3545', '#6f42c1', '#fd7e14', '#20c997'
                ],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 20,
                        usePointStyle: true
                    }
                }
            }
        }
    });
}

function renderTopProductsList(topProducts) {
    const productsHtml = topProducts.map(product => `
        <div class="top-product-item">
            <img src="${product.image || '/images/placeholder.jpg'}" alt="${product.name}" class="top-product-image">
            <div class="top-product-info">
                <div class="top-product-name">${product.name}</div>
                <div class="top-product-category">${product.category}</div>
            </div>
            <div class="top-product-stats">
                <div class="top-product-orders">${product.total_orders}</div>
                <div class="top-product-stock">Tồn: ${product.stock}</div>
            </div>
        </div>
    `).join('');
    
    $('#topProductsList').html(productsHtml);
}

function renderMonthlyChart(monthlyData) {
    const ctx = document.getElementById('monthlyChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: monthlyData.map(item => {
                const date = new Date(item.month + '-01');
                return date.toLocaleDateString('vi-VN', { month: 'short', year: 'numeric' });
            }),
            datasets: [{
                label: 'Sản phẩm mới',
                data: monthlyData.map(item => item.count),
                borderColor: '#435E53',
                backgroundColor: 'rgba(67, 94, 83, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4
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
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0,0,0,0.05)'
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            }
        }
    });
}

function renderRatingDistribution(ratingStats) {
    const ratings = [
        { label: 'Xuất sắc (4.5-5★)', count: ratingStats.excellent, stars: '★★★★★' },
        { label: 'Tốt (3.5-4.5★)', count: ratingStats.good, stars: '★★★★☆' },
        { label: 'Trung bình (2.5-3.5★)', count: ratingStats.average, stars: '★★★☆☆' },
        { label: 'Kém (1-2.5★)', count: ratingStats.poor, stars: '★★☆☆☆' },
        { label: 'Chưa có đánh giá', count: ratingStats.no_rating, stars: '☆☆☆☆☆' }
    ];

    const total = ratingStats.total_products;
    const ratingHtml = ratings.map(rating => `
        <div class="rating-indicator">
            <div class="rating-label">
                <span class="rating-stars">${rating.stars}</span>
                ${rating.label}
            </div>
            <div class="rating-bar">
                <div class="rating-fill" style="width: ${total > 0 ? (rating.count / total) * 100 : 0}%"></div>
            </div>
            <div class="rating-count">${rating.count}</div>
        </div>
    `).join('');

    $('#ratingDistribution').html(`
        <div style="text-align: center; margin-bottom: 1rem;">
            <div style="font-size: 2rem; font-weight: bold; color: #435E53;">
                ${ratingStats.avg_rating ? parseFloat(ratingStats.avg_rating).toFixed(1) : 'N/A'}
            </div>
            <div style="color: #6c757d;">Điểm trung bình</div>
        </div>
        ${ratingHtml}
    `);
}
