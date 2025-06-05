<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Support\Facades\Log;

class OnlinePaymentNotification extends Notification
{
	// use Queueable;

	public $orderId;
	public $paymentStatus; // success, failed, refunded
	public $paymentMethod; // momo, vnpay, etc. (providers)
	// public $pay_urls;

	/**
	 * Create a new notification instance.
	 */
	public function __construct($orderId, $paymentStatus, $paymentMethod)
	{
		$this->orderId = $orderId;
		$this->paymentStatus = $paymentStatus ?? 'pending';
		$this->paymentMethod = $paymentMethod;
	}

	/**
	 * Get the notification's delivery channels.
	 *
	 * @return array<int, string>
	 */
	public function via(object $notifiable): array
	{
		return ['database', 'expo_push'];
	}

	/**
	 * Get the array representation of the notification. (for db store)
	 *
	 * @return array<string, mixed>
	 */
	public function toArray(object $notifiable): array
	{
		return [
			'order_id' => $this->orderId,
			'payment_status' => $this->paymentStatus,
			'payment_method' => $this->paymentMethod,
			'timestamp' => now()->toIso8601String(),
		];
	}

	public function toExpoPush(object $notifiable): array
	{
		$title = 'Bytesme: Thông báo thanh toán'; // Default title
		$body = "Trạng thái thanh toán cho đơn hàng #{$this->orderId} của bạn đã được cập nhật."; // Default body

		switch ($this->paymentStatus) {
			case 'success':
				$title = 'Bytesme: Thanh toán ngọt ngào thành công! 🍰';
				$body = "Tuyệt vời! Đơn hàng #{$this->orderId} của bạn đã được thanh toán. Bytesme đang chuẩn bị những chiếc bánh xinh xắn, thơm lừng gửi đến bạn ngay đây!";
				break;
			case 'failed':
				$title = 'Bytesme: Thanh toán chưa thành công 🥺';
				$body = "Ôi chao! Thanh toán cho đơn hàng #{$this->orderId} gặp chút xíu trắc trở. Bạn kiểm tra lại thông tin hoặc thử phương thức khác để Bytesme sớm mang bánh ngon đến bạn nhé!";
				break;
			case 'refunded':
				$title = 'Bytesme: Hoàn tiền thành công! 💸';
				$body = "Bytesme đã hoàn tiền thành công cho đơn hàng #{$this->orderId}. Mong sớm được phục vụ bạn những mẻ bánh thơm ngon khác nha! 🥰";
				break;
			case 'pending':
				$title = 'Bytesme: Thanh toán đang chờ xử lý ⏳';
				$body = "Bytesme đang xử lý thanh toán cho đơn hàng #{$this->orderId} của bạn. Chúng mình sẽ thông báo ngay khi có kết quả nhé!";
				break;
			default:
				$title = "Bytesme: Cập nhật trạng thái đơn hàng #{$this->orderId}";
				$body = "Trạng thái thanh toán cho đơn hàng #{$this->orderId} của bạn là '{$this->paymentStatus}'. Bytesme sẽ sớm cập nhật thông tin chi tiết.";
				break;
		}

		return [
			'title' => $title,
			'body' => $body,
			'data' => [
				'order_id' => $this->orderId,
				'payment_status' => $this->paymentStatus,
				'payment_method' => $this->paymentMethod,
				'timestamp' => now()->toIso8601String(),
				'type' => 'OnlinePaymentNotification'
			],
		];
	}
}
