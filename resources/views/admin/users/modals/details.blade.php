<div class="modal fade vintage-modal" id="userDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title vintage-title">
                    <i class="mdi mdi-account-details"></i>
                    Chi tiết khách hàng
                </h5>
                <button type="button" class="btn-close vintage-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="userDetailsContent">
                    <div class="text-center">
                        <div class="loading-spinner"></div>
                        Đang tải thông tin...
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary vintage-btn" data-bs-dismiss="modal">
                    <i class="mdi mdi-close"></i> Đóng
                </button>
            </div>
        </div>
    </div>
</div>

<link rel="stylesheet" href="{{ asset('css/admin/users/modals/details.css') }}">
