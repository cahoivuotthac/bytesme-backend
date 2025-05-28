document.addEventListener("DOMContentLoaded", function () {
    // Existing flatpickr initialization
    flatpickr("#single_date", {
        dateFormat: "Y-m-d",
    });
    flatpickr("#date_start", {
        dateFormat: "Y-m-d",
    });
    flatpickr("#date_end", {
        dateFormat: "Y-m-d",
    });

    // Initialize default filter period btn
    document.querySelector('[data-period="week"]').classList.add("active");

    // Charts: initialize with default period
    updateCharts("week");

    // Open order modal if delegated by other page
    (function openIntendedModal() {
        const orderId = sessionStorage.getItem("modal.orderId");
        if (!orderId) {
            return;
        }
        loadOrderDetailsPopup(orderId, new Event("click"));
        sessionStorage.removeItem("modal.orderId");
    })();

    // Charts: handle periods filter buttons clicks
    document.querySelectorAll("[data-period]").forEach((button) => {
        button.addEventListener("click", function () {
            const period = this.dataset.period;
            updateCharts(period);

            // Update active button state
            document.querySelectorAll("[data-period]").forEach((btn) => {
                btn.classList.remove("active", "btn-secondary");
                btn.classList.add("btn-outline-secondary");
            });
            this.classList.remove("btn-outline-secondary");
            this.classList.add("active", "btn-secondary");
        });
    });

    // Existing date filter type handling
    document
        .querySelectorAll('input[name="dateFilterType"]')
        .forEach(function (el) {
            el.addEventListener("change", function () {
                if (this.value === "single") {
                    document.getElementById("singleDatePicker").style.display =
                        "block";
                    document.getElementById("rangeDatePicker").style.display =
                        "none";
                } else {
                    document.getElementById("singleDatePicker").style.display =
                        "none";
                    document.getElementById("rangeDatePicker").style.display =
                        "block";
                }
            });
        });

    // Add sorting functionality
    document.querySelectorAll(".sortable").forEach(function (header) {
        header.addEventListener("click", function () {
            const sort = this.dataset.sort;
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
    });

    document.querySelectorAll(".view-details-btn").forEach(function (element) {
        element.addEventListener("click", function (event) {
            event.stopPropagation();
            const orderId = this.dataset.orderId;
            loadOrderDetailsPopup(orderId, event);
        });
    });

    // Handle editable cells
    document.querySelectorAll(".editable-cell").forEach(function (cell) {
        cell.addEventListener("click", function (event) {
            event.stopPropagation();
            const field = this.dataset.field;
            const orderId = this.dataset.orderId;
            let options = {};

            if (field === "order_status") {
                options = window.statusOptions || statusOptions;
            } else if (field === "order_is_paid") {
                options = window.isPaidOptions || isPaidOptions;
            }

            if (Object.keys(options).length === 0) return;

            // Create popup
            const popup = document.createElement("div");
            popup.classList.add("popup-menu");

            const list = document.createElement("ul");

            for (const [value, label] of Object.entries(options)) {
                const item = document.createElement("li");
                item.textContent = label;
                item.dataset.value = value;
                item.addEventListener("click", function () {
                    updateOrderField(orderId, field, value);
                    document.body.removeChild(popup);
                });
                list.appendChild(item);
            }

            popup.appendChild(list);
            document.body.appendChild(popup);

            // Position the popup
            const rect = this.getBoundingClientRect();
            popup.style.top = `${rect.bottom + window.scrollY}px`;
            popup.style.left = `${rect.left + window.scrollX}px`;

            // Close popup when clicking outside
            document.addEventListener(
                "click",
                function handler(event) {
                    if (!popup.contains(event.target)) {
                        document.body.removeChild(popup);
                        document.removeEventListener("click", handler);
                    }
                },
                { once: true }
            );
        });
    });

    // Handle expand/collapse of newest orders
    document
        .querySelectorAll(".newest-order-item .order-header")
        .forEach(function (header) {
            header.addEventListener("click", function (event) {
                // Check if the click originated from the '.order-id.clickable' element
                if (event.target.closest(".order-id.clickable")) {
                    // Do nothing if the click is on the order-id
                    return;
                }

                const orderId = this.dataset.orderId;
                const details = document.getElementById(
                    `order-details-${orderId}`
                );
                const toggleIcon = this.querySelector(".toggle-icon");

                if (details.style.display === "none") {
                    details.style.display = "block";
                    toggleIcon.classList.remove("mdi-chevron-down");
                    toggleIcon.classList.add("mdi-chevron-up");
                } else {
                    details.style.display = "none";
                    toggleIcon.classList.remove("mdi-chevron-up");
                    toggleIcon.classList.add("mdi-chevron-down");
                }
            });
        });

    // Ensure the click handler on '.order-id.clickable' stops propagation
    function loadOrderDetailsPopup(orderId, event) {
        if (event) event.stopPropagation();
        const modal = new bootstrap.Modal(
            document.getElementById("orderDetailsModal")
        );
        const modalContent = document.getElementById("order-details-content");

        // Show loading indicator
        modalContent.innerHTML = `
				<div class="text-center">
					<div class="spinner-border text-primary" role="status">
						<span class="visually-hidden">Đang tải...</span>
					</div>
				</div>
			`;

        // Show the modal
        modal.show();

        // Fetch order details via AJAX
        fetch(`/admin/orders/${orderId}/details`, {
            headers: {
                "X-CSRF-TOKEN": document.querySelector(
                    'meta[name="csrf-token"]'
                ).content,
            },
        })
            .then((response) => response.json())
            .then((data) => {
                if (data.success) {
                    // Render order details
                    const order = data.order;

                    let orderDetailsHtml = `
						<p><strong>Mã đơn hàng:</strong> #${order.order_id}</p>
						<p><strong>Khách hàng:</strong> ${order.user.name}</p>
						<p><strong>Ngày đặt hàng:</strong> ${new Date(
                            order.created_at
                        ).toLocaleString()}</p>
						<p><strong>Tổng tiền:</strong> ${Number(
                            order.order_total_price
                        ).toLocaleString()} ₫</p>
						<p><strong>Phí vận chuyển:</strong> ${Number(
                            order.order_deliver_cost
                        ).toLocaleString()} ₫</p>
					`;

                    // Check for voucher
                    if (order.voucher) {
                        orderDetailsHtml += `
							<p><strong>Mã giảm giá:</strong> ${
                                order.voucher.voucher_name || "Không có"
                            }&nbsp; - &nbsp;${
                            order.voucher.description || ""
                        }</p>
						`;
                    } else {
                        orderDetailsHtml += `<p><strong>Mã giảm giá:</strong> Không có</p>`;
                    }

                    orderDetailsHtml += `
						<p><strong>Ghi chú:</strong> ${order.order_additional_note || "Không có"}</p>
						<hr style="opacity: 0.1; color: grey;">
						<h5>Sản phẩm:</h5>
						<div class="order-items">
					`;

                    order.order_items.forEach((item) => {
                        const product = item.product;
                        const imageUrl =
                            product.product_images.length > 0
                                ? product.product_images[0].product_image_url
                                : "/images/placeholder-plant.jpg";

                        const discountedAmount = Number(
                            item.order_items_discounted_amount || 0
                        );
                        let itemPriceHtml = "";

                        if (discountedAmount > 0) {
                            const originalPrice = (
                                item.order_items_unitprice - discountedAmount
                            ).toLocaleString();
                            itemPriceHtml = `
								<p class="product-quantity-price mb-0">
									Số lượng: ${item.order_items_quantity} x 
									<span class="text-decoration-line-through text-muted">${originalPrice} ₫</span> 
									<span class="text-danger">${item.order_items_unitprice} ₫</span>
								</p>
							`;
                        } else {
                            itemPriceHtml = `
								<p class="product-quantity-price mb-0">
									Số lượng: ${item.order_items_quantity} x ${item.order_items_unitprice} ₫
								</p>
							`;
                        }

                        orderDetailsHtml += `
							<div class="order-item d-flex align-items-center mb-3">
								<img src="${imageUrl}" alt="${product.product_name}" class="product-image me-3">
								<div>
									<p class="product-name mb-1">${product.product_name}</p>
									${itemPriceHtml}
								</div>
							</div>
						`;
                    });

                    orderDetailsHtml += `</div>`;

                    modalContent.innerHTML = orderDetailsHtml;
                } else {
                    modalContent.innerHTML = `<p class="text-danger">Lỗi: ${data.message}</p>`;
                }
            })
            .catch((error) => {
                console.error("Error:", error);
                modalContent.innerHTML = `<p class="text-danger">Đã xảy ra lỗi khi tải chi tiết đơn hàng.</p>`;
            });
    }

    document
        .querySelectorAll(".order-id.clickable")
        .forEach(function (element) {
            element.addEventListener("click", function (event) {
                loadOrderDetailsPopup(this.dataset.orderId, event);
            });
        });
});

function updateCharts(period) {
    fetch(`/admin/orders/statistics?period=${period}`, {
        headers: {
            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')
                .content,
        },
    })
        .then((response) => response.json())
        .then((data) => {
            console.log("Received chart data:", data);

            if (data.salesData) updateSalesChart(data.salesData);
            if (data.statusData) updateOrderStatusChart(data.statusData);
            if (data.deliveryTimeData)
                updateDeliveryTimeChart(data.deliveryTimeData);
            if (data.paymentMethodData)
                updatePaymentMethodChart(data.paymentMethodData);
            if (data.cancellationRateData)
                updateCancellationRateChart(data.cancellationRateData);
            if (data.summaryMetrics) updateSummaryMetrics(data.summaryMetrics);
        })
        .catch((error) => {
            console.error("Error fetching statistics:", error);
            // Show error message on the page
            const chartContainers = document.querySelectorAll(
                ".chart-container, .chart-container-small"
            );
            chartContainers.forEach((container) => {
                container.innerHTML = `<div class="text-center text-danger py-5">
                    <i class="mdi mdi-alert-circle fs-2"></i>
                    <p>Lỗi khi tải dữ liệu biểu đồ</p>
                </div>`;
            });
        });
}

function updateSummaryMetrics(metrics) {
    // Update revenue metric
    const revenueElement = document.getElementById("totalRevenue");
    if (revenueElement) {
        revenueElement.textContent =
            new Intl.NumberFormat("vi-VN").format(metrics.totalRevenue) +
            " VND";
    }

    const revenueGrowthElement = document.getElementById("revenueGrowth");
    if (revenueGrowthElement) {
        const isPositive = metrics.revenueGrowth >= 0;
        revenueGrowthElement.className = `metric-trend ${
            isPositive ? "positive" : "negative"
        }`;
        revenueGrowthElement.innerHTML = `
            <i class="mdi ${
                isPositive ? "mdi-arrow-up" : "mdi-arrow-down"
            }"></i>
            ${Math.abs(metrics.revenueGrowth).toFixed(1)}%
        `;
    }

    // Update orders metric
    const ordersElement = document.getElementById("totalOrders");
    if (ordersElement) {
        ordersElement.textContent = new Intl.NumberFormat("vi-VN").format(
            metrics.totalOrders
        );
    }

    const orderGrowthElement = document.getElementById("orderGrowth");
    if (orderGrowthElement) {
        const isPositive = metrics.orderGrowth >= 0;
        orderGrowthElement.className = `metric-trend ${
            isPositive ? "positive" : "negative"
        }`;
        orderGrowthElement.innerHTML = `
            <i class="mdi ${
                isPositive ? "mdi-arrow-up" : "mdi-arrow-down"
            }"></i>
            ${Math.abs(metrics.orderGrowth).toFixed(1)}%
        `;
    }

    // Update delivery time metric
    const deliveryTimeElement = document.getElementById("deliveryTime");
    if (deliveryTimeElement) {
        const minutes = Math.round(metrics.deliveryTime);
        const hours = Math.floor(minutes / 60);
        const remainingMinutes = minutes % 60;

        if (hours > 0) {
            deliveryTimeElement.textContent = `${hours}h ${remainingMinutes}m`;
        } else {
            deliveryTimeElement.textContent = `${minutes} phút`;
        }
    }

    // Update delivery time improvement metric
    const deliveryImprovementElement = document.getElementById(
        "deliveryImprovement"
    );
    if (deliveryImprovementElement) {
        const isPositive = metrics.deliveryImprovement >= 0;
        deliveryImprovementElement.className = `metric-trend ${
            isPositive ? "positive" : "negative"
        }`;
        deliveryImprovementElement.innerHTML = `
			<i class="mdi ${isPositive ? "mdi-arrow-down" : "mdi-arrow-up"}"></i>
			${Math.abs(metrics.deliveryImprovement).toFixed(1)}%
		`;
    }

    // Update average order value metric
    const avgOrderValueElement = document.getElementById("avgOrderValue");
    if (avgOrderValueElement) {
        avgOrderValueElement.textContent =
            new Intl.NumberFormat("vi-VN").format(metrics.avgOrderValue) +
            " VND";
    }

    const avgOrderGrowthElement = document.getElementById("avgOrderGrowth");
    if (avgOrderGrowthElement) {
        const isPositive = metrics.avgOrderGrowth >= 0;
        avgOrderGrowthElement.className = `metric-trend ${
            isPositive ? "positive" : "negative"
        }`;
        avgOrderGrowthElement.innerHTML = `
            <i class="mdi ${
                isPositive ? "mdi-arrow-up" : "mdi-arrow-down"
            }"></i>
            ${Math.abs(metrics.avgOrderGrowth).toFixed(1)}%
        `;
    }
}

function updateSalesChart(data) {
    const ctx = document.getElementById("salesChart").getContext("2d");
    if (window.salesChart && typeof window.salesChart.destroy === "function")
        window.salesChart.destroy();

    const gradient = ctx.createLinearGradient(0, 0, 0, 400);
    gradient.addColorStop(0, "rgba(79, 70, 229, 0.6)");
    gradient.addColorStop(1, "rgba(79, 70, 229, 0.1)");

    window.salesChart = new Chart(ctx, {
        type: "line",
        data: {
            labels: data.labels,
            datasets: [
                {
                    label: "Doanh thu",
                    data: data.values,
                    borderColor: "#4f46e5",
                    backgroundColor: gradient,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: "#ffffff",
                    pointBorderColor: "#4f46e5",
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false,
                },
                tooltip: {
                    mode: "index",
                    intersect: false,
                    backgroundColor: "#334155",
                    titleColor: "#ffffff",
                    bodyColor: "#ffffff",
                    titleFont: {
                        size: 14,
                        weight: "bold",
                    },
                    bodyFont: {
                        size: 13,
                    },
                    padding: 12,
                    cornerRadius: 8,
                    callbacks: {
                        label: function (context) {
                            let label = context.dataset.label || "";
                            if (label) {
                                label += ": ";
                            }
                            if (context.parsed.y !== null) {
                                label +=
                                    context.parsed.y.toLocaleString("vi-VN") +
                                    "₫";
                            }
                            return label;
                        },
                    },
                },
            },
            scales: {
                x: {
                    grid: {
                        display: false,
                    },
                    ticks: {
                        font: {
                            size: 11,
                        },
                        color: "#64748b",
                    },
                },
                y: {
                    beginAtZero: true,
                    grid: {
                        color: "rgba(0,0,0,0.05)",
                    },
                    ticks: {
                        font: {
                            size: 11,
                        },
                        color: "#64748b",
                        callback: function (value) {
                            return value.toLocaleString("vi-VN") + "₫";
                        },
                    },
                },
            },
            interaction: {
                mode: "nearest",
                axis: "x",
                intersect: false,
            },
        },
    });
}

function updateOrderStatusChart(data) {
    const ctx = document.getElementById("orderStatusChart").getContext("2d");
    if (
        window.orderStatusChart &&
        typeof window.orderStatusChart.destroy === "function"
    )
        window.orderStatusChart.destroy();

    window.orderStatusChart = new Chart(ctx, {
        type: "doughnut",
        data: {
            labels: ["Chờ xử lý", "Đang giao", "Đã giao", "Đã hủy"],
            datasets: [
                {
                    data: data,
                    backgroundColor: [
                        "#fbbf24", // pending - amber
                        "#10b981", // delivering - emerald
                        "#3b82f6", // delivered - blue
                        "#ef4444", // cancelled - red
                    ],
                    borderColor: "#ffffff",
                    borderWidth: 2,
                    hoverOffset: 10,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: "70%",
            plugins: {
                legend: {
                    position: "bottom",
                    labels: {
                        padding: 15,
                        usePointStyle: true,
                        pointStyle: "circle",
                        font: {
                            size: 12,
                        },
                    },
                },
                tooltip: {
                    backgroundColor: "#334155",
                    titleColor: "#ffffff",
                    bodyColor: "#ffffff",
                    padding: 12,
                    cornerRadius: 8,
                    callbacks: {
                        label: function (context) {
                            const label = context.label || "";
                            const value = context.parsed || 0;
                            const total = context.dataset.data.reduce(
                                (acc, val) => acc + val,
                                0
                            );
                            const percentage = Math.round(
                                (value / total) * 100
                            );
                            return `${label}: ${value} (${percentage}%)`;
                        },
                    },
                },
            },
            onClick: (event, elements) => {
                if (elements.length > 0) {
                    const index = elements[0].index;
                    const status = [
                        "pending",
                        "delivering",
                        "delivered",
                        "cancelled",
                    ][index];
                    window.location.href = `/admin/orders?status=${status}`;
                }
            },
        },
    });
}

function updateDeliveryTimeChart(data) {
    const ctx = document.getElementById("deliveryTimeChart").getContext("2d");
    if (
        window.deliveryTimeChart &&
        typeof window.deliveryTimeChart.destroy === "function"
    )
        window.deliveryTimeChart.destroy();

    // Check if data exists and has the expected structure
    if (!data || !data.labels || !data.values) {
        console.error("Invalid delivery time data format:", data);
        // Create an empty chart or show a message
        const noDataMessage = document.createElement("div");
        noDataMessage.className = "text-center text-muted py-5";
        noDataMessage.innerHTML =
            '<i class="mdi mdi-alert-circle-outline fs-2"></i><p>Không có dữ liệu</p>';

        const container = ctx.canvas.parentNode;
        container.innerHTML = "";
        container.appendChild(noDataMessage);
        return;
    }

    window.deliveryTimeChart = new Chart(ctx, {
        type: "bar",
        data: {
            labels: data.labels,
            datasets: [
                {
                    label: "Số phút",
                    data: data.values,
                    backgroundColor: "#0d9488",
                    borderRadius: 8,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: "y",
            plugins: {
                legend: {
                    display: false,
                },
                tooltip: {
                    backgroundColor: "#334155",
                    callbacks: {
                        label: function (context) {
                            const minutes = Math.round(context.parsed.x);
                            const hours = Math.floor(minutes / 60);
                            const remainingMinutes = minutes % 60;

                            if (hours > 0) {
                                return `${hours}h ${remainingMinutes}m (${minutes} phút)`;
                            } else {
                                return `${minutes} phút`;
                            }
                        },
                    },
                },
            },
            scales: {
                x: {
                    beginAtZero: true,
                    grid: {
                        color: "rgba(0,0,0,0.05)",
                    },
                    title: {
                        display: true,
                        text: "Thời gian giao trung bình (phút)",
                        color: "#64748b",
                        font: {
                            size: 12,
                        },
                    },
                    ticks: {
                        callback: function (value) {
                            const minutes = Math.round(value);
                            const hours = Math.floor(minutes / 60);
                            const remainingMinutes = minutes % 60;

                            if (hours > 0) {
                                return `${hours}h ${remainingMinutes}m`;
                            } else {
                                return `${minutes}m`;
                            }
                        },
                    },
                },
                y: {
                    grid: {
                        display: false,
                    },
                },
            },
        },
    });
}

function updatePaymentMethodChart(data) {
    const ctx = document.getElementById("paymentMethodChart").getContext("2d");
    if (
        window.paymentMethodChart &&
        typeof window.paymentMethodChart.destroy === "function"
    )
        window.paymentMethodChart.destroy();

    // Check if data exists and has the expected structure
    if (!data || !data.values) {
        console.error("Invalid payment method data format:", data);
        const noDataMessage = document.createElement("div");
        noDataMessage.className = "text-center text-muted py-5";
        noDataMessage.innerHTML =
            '<i class="mdi mdi-alert-circle-outline fs-2"></i><p>Không có dữ liệu</p>';

        const container = ctx.canvas.parentNode;
        container.innerHTML = "";
        container.appendChild(noDataMessage);
        return;
    }

    window.paymentMethodChart = new Chart(ctx, {
        type: "pie",
        data: {
            labels: ["COD", "Momo", "Khác"],
            datasets: [
                {
                    data: data.values,
                    backgroundColor: ["#64748b", "#7c3aed", "#6366f1"],
                    borderColor: "#ffffff",
                    borderWidth: 2,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: "bottom",
                    labels: {
                        usePointStyle: true,
                        pointStyle: "circle",
                    },
                },
                tooltip: {
                    backgroundColor: "#334155",
                    callbacks: {
                        label: function (context) {
                            const label = context.label || "";
                            const value = context.parsed || 0;
                            const total = context.dataset.data.reduce(
                                (acc, val) => acc + val,
                                0
                            );
                            const percentage = Math.round(
                                (value / total) * 100
                            );
                            return `${label}: ${value} đơn (${percentage}%)`;
                        },
                    },
                },
            },
        },
    });
}

function updateCancellationRateChart(data) {
    const ctx = document
        .getElementById("cancellationRateChart")
        .getContext("2d");
    if (
        window.cancellationRateChart &&
        typeof window.cancellationRateChart.destroy === "function"
    )
        window.cancellationRateChart.destroy();

    // Check if data exists and has the expected structure
    if (!data || !data.labels || !data.values) {
        console.error("Invalid cancellation rate data format:", data);
        const noDataMessage = document.createElement("div");
        noDataMessage.className = "text-center text-muted py-5";
        noDataMessage.innerHTML =
            '<i class="mdi mdi-alert-circle-outline fs-2"></i><p>Không có dữ liệu</p>';

        const container = ctx.canvas.parentNode;
        container.innerHTML = "";
        container.appendChild(noDataMessage);
        return;
    }

    window.cancellationRateChart = new Chart(ctx, {
        type: "line",
        data: {
            labels: data.labels,
            datasets: [
                {
                    label: "Tỉ lệ hủy đơn",
                    data: data.values,
                    borderColor: "#ef4444",
                    backgroundColor: "rgba(239, 68, 68, 0.1)",
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: "#ffffff",
                    pointBorderColor: "#ef4444",
                    pointBorderWidth: 2,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false,
                },
                tooltip: {
                    backgroundColor: "#334155",
                    callbacks: {
                        label: function (context) {
                            return `${context.parsed.y.toFixed(1)}%`;
                        },
                    },
                },
            },
            scales: {
                x: {
                    grid: {
                        display: false,
                    },
                },
                y: {
                    beginAtZero: true,
                    max: 100,
                    grid: {
                        color: "rgba(0,0,0,0.05)",
                    },
                    ticks: {
                        callback: function (value) {
                            return value + "%";
                        },
                    },
                },
            },
        },
    });
}

// Handle change status button click
document.querySelectorAll(".change-status-btn").forEach(function (button) {
    button.addEventListener("click", function (event) {
        event.stopPropagation();
        const orderId = this.dataset.orderId;

        Swal.fire({
            title: "Xác nhận chuyển trạng thái?",
            text: "Đơn hàng sẽ được chuyển sang trạng thái đang giao hàng và khách hàng sẽ nhận được thông báo qua ứng dụng Bytesme",
            icon: "question",
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
                this.classList.add("btn-secondary");
                updateOrderField(orderId, "order_status", "delivering");
            }
        });
    });
});

function updateOrderField(orderId, field, value) {
    fetch("/admin/orders/update-field", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')
                .content,
        },
        body: JSON.stringify({
            order_id: orderId,
            field: field,
            value: value,
        }),
    })
        .then((response) => response.json())
        .then(async (data) => {
            if (data.success) {
                // Reload the page or update the cell content
                showAlert("success", data.message);
                await new Promise((r) => setTimeout(r, 2000));
                window.location.reload();
            } else {
                console.error("Error:", data.message);
                showAlert("error", data.message);
            }
        })
        .catch((error) => {
            console.error("Error:", error);
        });
}
