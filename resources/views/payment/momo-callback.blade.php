<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thanh toán {{ $paymentStatus === 'success' ? 'thành công' : ($paymentStatus === 'failed' ? 'thất bại' : 'đang xử lý') }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #FFB6C1 0%, #98E4D6 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 20px;
            color: #333;
        }
        
        .container {
            max-width: 400px;
            width: 100%;
            text-align: center;
            animation: fadeIn 0.8s ease-in-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .order-header {
            margin-bottom: 30px;
        }
        
        .order-number {
            font-size: 16px;
            color: #666;
            margin-bottom: 10px;
        }
        
        .order-id {
            font-size: 20px;
            font-weight: bold;
            color: #FF8C42;
        }
        
        .main-title {
            font-size: 28px;
            font-weight: bold;
            color: #FF8C42;
            margin: 20px 0 40px 0;
            line-height: 1.3;
        }
        
        .icon-container {
            width: 100px;
            height: 100px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 40px auto;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }
        
        .status-icon {
            font-size: 50px;
        }
        
        .success-icon { color: #4CAF50; }
        .failed-icon { color: #F44336; }
        .pending-icon { color: #FF9800; }
        
        .status-message {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 30px;
            color: #333;
        }
        
        .buttons {
            display: flex;
            flex-direction: column;
            gap: 15px;
            margin-top: 40px;
        }
        
        .btn {
            padding: 16px 24px;
            border-radius: 25px;
            border: none;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
            text-align: center;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #FF8C42 0%, #FF6B42 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(255, 140, 66, 0.3);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 140, 66, 0.4);
        }
        
        .btn-secondary {
            background: linear-gradient(135deg, #E91E63 0%, #D81B60 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(233, 30, 99, 0.3);
        }
        
        .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(233, 30, 99, 0.4);
        }
        
        .btn-tertiary {
            background: white;
            color: #FF8C42;
            border: 2px solid #FF8C42;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        .btn-tertiary:hover {
            background: #FF8C42;
            color: white;
            transform: translateY(-2px);
        }
        
        .info-text {
            background: rgba(255, 255, 255, 0.9);
            padding: 20px;
            border-radius: 15px;
            margin: 30px 0;
            font-size: 14px;
            line-height: 1.6;
            color: #555;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        .dots {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin: 20px 0;
        }
        
        .dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.5);
        }
        
        .dot.active { background: #FF8C42; }
        .dot:nth-child(2) { background: #4CAF50; }
        
        @media (max-width: 480px) {
            .container {
                padding: 0 10px;
            }
            
            .main-title {
                font-size: 24px;
            }
            
            .btn {
                padding: 14px 20px;
                font-size: 15px;
            }
        }
        
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #FF8C42;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-left: 10px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="container">
        @if($orderId)
        <div class="order-header">
            <div class="order-number">Mã đơn #</div>
            <div class="order-id">{{ $orderId }}</div>
        </div>
        @endif
        
        <h1 class="main-title">Thanh toán bằng ví MoMo</h1>
        
        <div class="icon-container">
            @if($paymentStatus === 'success')
                <div class="status-icon success-icon">✓</div>
            @elseif($paymentStatus === 'failed')
                <div class="status-icon failed-icon">✗</div>
            @elseif($paymentStatus === 'timeout')
                <div class="status-icon failed-icon">⏰</div>
            @else
                <div class="status-icon pending-icon">⏳</div>
            @endif
        </div>
        
        <div class="status-message">{{ $statusMessage }}</div>
        
        <div class="dots">
            <div class="dot"></div>
            <div class="dot active"></div>
            <div class="dot"></div>
        </div>
        
        @if($paymentStatus === 'success')
            <div class="info-text">
                Thanh toán của bạn đã được xử lý thành công. Chúng tôi sẽ cập nhật trạng thái đơn hàng sớm nhất có thể.
            </div>
            
            <div class="buttons">
                <a href="javascript:history.back()" class="btn btn-primary">Quay lại ứng dụng</a>
                <a href="#" onclick="checkOrderStatus()" class="btn btn-secondary">Kiểm tra đơn hàng</a>
            </div>
            
        @elseif($paymentStatus === 'failed' || $paymentStatus === 'timeout')
            <div class="info-text">
                @if($paymentStatus === 'timeout')
                    Thanh toán đã hết thời gian. Vui lòng thử lại hoặc chọn phương thức thanh toán khác.
                @else
                    Có lỗi xảy ra trong quá trình thanh toán. Vui lòng thử lại sau hoặc liên hệ hỗ trợ.
                @endif
            </div>
            
            <div class="buttons">
                <a href="javascript:history.back()" class="btn btn-primary">Thử lại thanh toán</a>
                <a href="javascript:history.back()" class="btn btn-tertiary">Quay lại</a>
            </div>
            
        @else
            <div class="info-text">
                Vui lòng đợi trong khi chúng tôi đang xử lý thanh toán của bạn...
                <div class="loading"></div>
            </div>
            
            <div class="buttons">
                <a href="javascript:history.back()" class="btn btn-primary">Quay lại ứng dụng</a>
            </div>
        @endif
    </div>
    
    <script>
        // Auto refresh for pending payments
        @if($paymentStatus === 'pending')
        setTimeout(function() {
            location.reload();
        }, 3000);
        @endif
        
        function checkOrderStatus() {
            // You can implement order status checking here
            // For now, just go back to the app
            if (window.history.length > 1) {
                history.back();
            } else {
                // Fallback - could redirect to your app's deep link
                window.location.href = 'about:blank';
            }
        }
        
        // Add some interactivity
        document.addEventListener('DOMContentLoaded', function() {
            // Add touch feedback for mobile
            const buttons = document.querySelectorAll('.btn');
            buttons.forEach(button => {
                button.addEventListener('touchstart', function() {
                    this.style.transform = 'scale(0.95)';
                });
                button.addEventListener('touchend', function() {
                    this.style.transform = '';
                });
            });
        });
    </script>
</body>
</html>