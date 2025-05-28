$(document).ready(function () {
    // Initialize analytics dashboard
    loadUserAnalytics();

    // Handle filters
    $("#applyFilters").click(function () {
        const status = $("#statusFilter").val();
        const role = $("#roleFilter").val();

        const url = new URL(window.location.href);
        if (status) url.searchParams.set("status", status);
        else url.searchParams.delete("status");
        if (role) url.searchParams.set("role", role);
        else url.searchParams.delete("role");

        window.location.href = url.toString();
    });

    // Handle sorting
    $(".sort-icon").click(function () {
        const sort = $(this).data("sort");
        const currentSort = new URLSearchParams(window.location.search).get("sort");
        const currentDirection = new URLSearchParams(window.location.search).get("direction");

        let newDirection = "desc";
        if (currentSort === sort) {
            newDirection = currentDirection === "desc" ? "asc" : "desc";
        }

        const url = new URL(window.location.href);
        url.searchParams.set("sort", sort);
        url.searchParams.set("direction", newDirection);

        window.location.href = url.toString();
    });

    // Handle search
    $("#searchButton").click(function () {
        performSearch();
    });

    $("#searchInput").keypress(function (e) {
        if (e.which == 13) {
            performSearch();
        }
    });

    // Handle view user details
    $(".view-user").click(function () {
        const userId = $(this).data("userId");
        showUserDetails(userId);
    });

    // Update sort icons
    updateSortIcons();
});

function loadUserAnalytics() {
    $.ajax({
        url: '/admin/users/analytics/data',
        method: 'GET',
        success: function(response) {
            if (response.success) {
                renderMetricsCards(response.data.overview);
                renderRoleChart(response.data.role_distribution);
                renderRegistrationChart(response.data.monthly_registrations);
                renderActivityChart(response.data.activity_pattern);
                renderSegmentChart(response.data.customer_segments);
                renderTopSpendersList(response.data.top_spenders);
                renderGeographicList(response.data.geographic_data);
                
                // New feedback analytics
                renderFeedbackRatingChart(response.data.feedback_stats);
                renderImproveTagsChart(response.data.improve_tags);
                renderMonthlyFeedbackChart(response.data.monthly_feedbacks);
                renderLowRatingFeedbacksList(response.data.low_rating_feedbacks);
            } else {
                showAlert('error', 'Không thể tải dữ liệu phân tích');
            }
        },
        error: function() {
            showAlert('error', 'Có lỗi khi tải dữ liệu phân tích');
        }
    });
}

function renderMetricsCards(overview) {
    const metricsHtml = `
        <div class="metric-card">
            <div class="metric-icon users">
                <i class="mdi mdi-account-group"></i>
            </div>
            <div class="metric-value">${overview.total_users}</div>
            <div class="metric-label">Tổng số user</div>
        </div>
        <div class="metric-card">
            <div class="metric-icon new-users">
                <i class="mdi mdi-account-plus"></i>
            </div>
            <div class="metric-value">${overview.new_this_month}</div>
            <div class="metric-label">Đăng ký tháng này</div>
        </div>
        <div class="metric-card">
            <div class="metric-icon active-users">
                <i class="mdi mdi-account-check"></i>
            </div>
            <div class="metric-value">${overview.active_users}</div>
            <div class="metric-label">Đang hoạt động</div>
        </div>
        <div class="metric-card">
            <div class="metric-icon inactive-users">
                <i class="mdi mdi-account-off"></i>
            </div>
            <div class="metric-value">${overview.inactive_users}</div>
            <div class="metric-label">Không hoạt động</div>
        </div>
    `;
    $('#userMetricsGrid').html(metricsHtml);
}

function renderRegistrationChart(monthlyData) {
    const ctx = document.getElementById('registrationChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: monthlyData.map(item => {
                const date = new Date(item.month + '-01');
                return date.toLocaleDateString('vi-VN', { month: 'short', year: 'numeric' });
            }),
            datasets: [{
                label: 'Đăng ký mới',
                data: monthlyData.map(item => item.count),
                borderColor: '#3498db',
                backgroundColor: 'rgba(52, 152, 219, 0.1)',
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

function renderRoleChart(roleData) {
    const ctx = document.getElementById('roleChart').getContext('2d');
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: roleData.map(item => item.role_name),
            datasets: [{
                data: roleData.map(item => item.count),
                backgroundColor: [
                    '#3498db', '#e74c3c', '#f39c12', '#27ae60'
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

function renderActivityChart(activityData) {
    const ctx = document.getElementById('activityChart').getContext('2d');
    
    // Create 24-hour array, filling missing hours with 0
    const hourlyData = Array(24).fill(0);
    activityData.forEach(item => {
        hourlyData[item.hour] = item.order_count;
    });
    
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: Array.from({length: 24}, (_, i) => `${i}:00`),
            datasets: [{
                label: 'Đơn hàng',
                data: hourlyData,
                backgroundColor: 'rgba(52, 152, 219, 0.7)',
                borderColor: '#3498db',
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

function renderSegmentChart(segmentData) {
    const ctx = document.getElementById('segmentChart').getContext('2d');
    new Chart(ctx, {
        type: 'pie',
        data: {
            labels: segmentData.map(item => item.segment),
            datasets: [{
                data: segmentData.map(item => item.count),
                backgroundColor: [
                    '#f39c12', '#e74c3c', '#3498db', '#27ae60', '#95a5a6'
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

function renderTopSpendersList(topSpenders) {
    const spendersHtml = topSpenders.map((spender, index) => `
        <div class="top-spender-item">
            <img src="${spender.avatar || 'https://via.placeholder.com/40'}" alt="${spender.name}" class="top-spender-avatar">
            <div class="top-spender-info">
                <div class="top-spender-name">#${index + 1} ${spender.name}</div>
                <div class="top-spender-email">${spender.email}</div>
            </div>
            <div class="top-spender-stats">
                <div class="top-spender-amount">${new Intl.NumberFormat('vi-VN').format(spender.total_spent)}đ</div>
                <div class="top-spender-orders">${spender.orders_count} đơn hàng</div>
            </div>
        </div>
    `).join('');
    
    $('#topSpendersList').html(spendersHtml);
}

function renderGeographicList(geographicData) {
    const geographicHtml = geographicData.map(item => `
        <div class="geographic-item">
            <div class="geographic-city">${item.city || 'Không xác định'}</div>
            <div class="geographic-count">${item.user_count}</div>
        </div>
    `).join('');
    
    $('#geographicList').html(geographicHtml);
}

function showUserDetails(userId) {
    const modal = new bootstrap.Modal(document.getElementById('userDetailsModal'));
    modal.show();
    
    // Load user details
    $.ajax({
        url: `/admin/users/${userId}/details`,
        method: 'GET',
        success: function(response) {
            if (response.success) {
                renderUserDetailsContent(response.user, response.recent_orders);
            } else {
                $('#userDetailsContent').html('<div class="alert alert-danger">Không thể tải thông tin người dùng</div>');
            }
        },
        error: function() {
            $('#userDetailsContent').html('<div class="alert alert-danger">Có lỗi khi tải thông tin người dùng</div>');
        }
    });
}

function renderUserDetailsContent(user, recentOrders) {
    const ordersHtml = recentOrders.map(order => `
        <tr>
            <td>#${order.order_id}</td>
            <td>${new Date(order.created_at).toLocaleDateString('vi-VN')}</td>
            <td>${new Intl.NumberFormat('vi-VN').format(order.order_total_price)}đ</td>
            <td><span class="badge badge-${order.order_status}">${order.order_status}</span></td>
        </tr>
    `).join('');

    const content = `
        <div class="row">
            <div class="col-md-4">
                <div class="text-center mb-4">
                    <img src="${user.profile_image_url || 'https://via.placeholder.com/150'}" 
                         class="rounded-circle mb-3" width="150" height="150" style="object-fit: cover;">
                    <h4>${user.full_name}</h4>
                    <p class="text-muted">@${user.user_name}</p>
                </div>
                <div class="info-card">
                    <h6>Thông tin cơ bản</h6>
                    <p><strong>Email:</strong> ${user.email}</p>
                    <p><strong>Số điện thoại:</strong> ${user.phone_number || 'N/A'}</p>
                    <p><strong>Ngày đăng ký:</strong> ${new Date(user.created_at).toLocaleDateString('vi-VN')}</p>
                    <p><strong>Vai trò:</strong> ${getRoleName(user.role_type)}</p>
                </div>
            </div>
            <div class="col-md-8">
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="metric-card-small">
                            <div class="metric-value-small">${user.orders_count}</div>
                            <div class="metric-label-small">Tổng đơn hàng</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="metric-card-small">
                            <div class="metric-value-small">${new Intl.NumberFormat('vi-VN').format(user.total_spent || 0)}đ</div>
                            <div class="metric-label-small">Tổng chi tiêu</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="metric-card-small">
                            <div class="metric-value-small">${user.addresses ? user.addresses.length : 0}</div>
                            <div class="metric-label-small">Địa chỉ</div>
                        </div>
                    </div>
                </div>
                
                <h6>Đơn hàng gần đây</h6>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Mã đơn</th>
                                <th>Ngày</th>
                                <th>Tổng tiền</th>
                                <th>Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${ordersHtml || '<tr><td colspan="4" class="text-center">Chưa có đơn hàng nào</td></tr>'}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    `;
    
    $('#userDetailsContent').html(content);
}

function getRoleName(roleType) {
    switch(roleType) {
        case 0: return 'Khách hàng';
        case 1: return 'Admin';
        case 2: return 'Nhân viên';
        default: return 'Không xác định';
    }
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

function updateSortIcons() {
    const currentSort = new URLSearchParams(window.location.search).get("sort");
    const currentDirection = new URLSearchParams(window.location.search).get("direction");

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
}

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

function renderFeedbackRatingChart(feedbackStats) {
    const ctx = document.getElementById('feedbackRatingChart').getContext('2d');
    
    const ratingData = [
        feedbackStats.five_star || 0,
        feedbackStats.four_star || 0,
        feedbackStats.three_star || 0,
        feedbackStats.two_star || 0,
        feedbackStats.one_star || 0
    ];
    
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['5 ⭐', '4 ⭐', '3 ⭐', '2 ⭐', '1 ⭐'],
            datasets: [{
                data: ratingData,
                backgroundColor: [
                    '#28a745',
                    '#8bc34a',
                    '#ffc107',
                    '#ff9800',
                    '#f44336'
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
                        padding: 15,
                        usePointStyle: true
                    }
                }
            }
        }
    });
}

function renderImproveTagsChart(improveTagsData) {
    const ctx = document.getElementById('improveTagsChart').getContext('2d');
    
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: improveTagsData.map(item => item.tag_name),
            datasets: [{
                label: 'Số lượng góp ý',
                data: improveTagsData.map(item => item.count),
                backgroundColor: [
                    'rgba(255, 99, 132, 0.8)',
                    'rgba(54, 162, 235, 0.8)',
                    'rgba(255, 205, 86, 0.8)',
                    'rgba(75, 192, 192, 0.8)',
                    'rgba(153, 102, 255, 0.8)'
                ],
                borderColor: [
                    'rgba(255, 99, 132, 1)',
                    'rgba(54, 162, 235, 1)',
                    'rgba(255, 205, 86, 1)',
                    'rgba(75, 192, 192, 1)',
                    'rgba(153, 102, 255, 1)'
                ],
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

function renderMonthlyFeedbackChart(monthlyData) {
    const ctx = document.getElementById('monthlyFeedbackChart').getContext('2d');
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: monthlyData.map(item => {
                const date = new Date(item.month + '-01');
                return date.toLocaleDateString('vi-VN', { month: 'short', year: 'numeric' });
            }),
            datasets: [{
                label: 'Số phản hồi',
                data: monthlyData.map(item => item.total_feedbacks),
                borderColor: '#435E53',
                backgroundColor: 'rgba(67, 94, 83, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                yAxisID: 'y'
            }, {
                label: 'Điểm trung bình',
                data: monthlyData.map(item => parseFloat(item.avg_rating || 0).toFixed(1)),
                borderColor: '#ffc107',
                backgroundColor: 'rgba(255, 193, 7, 0.1)',
                borderWidth: 3,
                fill: false,
                tension: 0.4,
                yAxisID: 'y1'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            scales: {
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Số phản hồi'
                    }
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    min: 0,
                    max: 5,
                    title: {
                        display: true,
                        text: 'Điểm trung bình'
                    },
                    grid: {
                        drawOnChartArea: false,
                    },
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

function renderLowRatingFeedbacksList(lowRatingData) {
    const feedbacksHtml = lowRatingData.map(feedback => {
        const tagsHtml = feedback.improve_tags.map(tag => {
            const tagName = getTagDisplayName(tag);
            return `<span class="improve-tag improve-tag-${tag}">${tagName}</span>`;
        }).join('');
        
        const stars = '★'.repeat(feedback.rating) + '☆'.repeat(5 - feedback.rating);
        const date = new Date(feedback.created_at).toLocaleDateString('vi-VN');
        
        return `
            <div class="low-rating-item">
                <div class="feedback-header">
                    <div class="feedback-user">${feedback.user_name}</div>
                    <div class="feedback-rating">${stars}</div>
                    <div class="feedback-date">${date}</div>
                </div>
                <div class="feedback-content">${feedback.content || 'Không có nội dung'}</div>
                <div class="feedback-tags">${tagsHtml}</div>
            </div>
        `;
    }).join('');
    
    $('#lowRatingFeedbacksList').html(feedbacksHtml || '<div class="no-data">Không có phản hồi đánh giá thấp</div>');
}

function getTagDisplayName(tag) {
    const tagNames = {
        'flavour': 'Hương vị',
        'act-of-service': 'Dịch vụ',
        'packaging': 'Đóng gói',
        'delivery-time': 'Giao hàng'
    };
    return tagNames[tag] || tag;
}
