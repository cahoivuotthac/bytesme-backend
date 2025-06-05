<?php

namespace App\Notifications;

use App\Models\Order;
use App\Notifications\Channels\ExpoPushChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderStatusNotification extends Notification
{
	protected $order;
	protected $status;
	protected $message;
	/**
	 * Create a new notification instance.
	 */
	public function __construct(Order $order, string $status, string $message = null)
	{
		$this->order = $order;
		$this->status = $status;
		$this->message = $message;
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
	 * Get the array representation of the notification.
	 *
	 * @return array<string, mixed>
	 */
	public function toArray(object $notifiable): array
	{
		return [
			'order_id' => $this->order->order_id,
			'status' => $this->status,
			'message' => $this->message,
			// 'order_details' => [
			// 'total_price' => $this->order->order_total_price,
			// 'deliver_time' => $this->order->order_deliver_time ? $this->order->order_deliver_time->toIso8601String() : null,
			// 'deliver_address' => $this->order->order_deliver_address,
			// ],
			'timestamp' => now()->toIso8601String(),
		];
	}

	/**
	 * Get the broadcastable representation of the notification.
	 *
	 * @param  mixed  $notifiable
	 * @return BroadcastMessage
	 */
	public function toBroadcast($notifiable): BroadcastMessage
	{
		return new BroadcastMessage([
			'order_id' => $this->order->order_id,
			'status' => $this->status,
			'message' => $this->message,
			'order_details' => [
				'total_price' => $this->order->order_total_price,
				'deliver_time' => $this->order->order_deliver_time ? $this->order->order_deliver_time->toIso8601String() : null,
				'deliver_address' => $this->order->order_deliver_address,
			],
			'timestamp' => now()->toIso8601String(),
		]);
	}

	public function toExpoPush($notifiable): array
	{
		$title = "Bytesme: Tin từ Bếp Yêu 🍳"; // "Bytesme: News from the Beloved Kitchen 🍳"
		$body = $this->message;

		if (!$body) {
			switch (strtolower($this->status)) {
				case 'online_payment_pending':
					$body = "Bytesme đang chờ thanh toán cho đơn hàng của bạn. Hoàn tất để Bytesme chuẩn bị món ngon nha! 💳✨";
					break;
				case 'pending':
					$body = "Đơn hàng của bạn đang được Bytesme chuẩn bị nha. Xíu nữa thôi! 💖";
					break;
				case 'delivering':
					$body = "Món ngon đang vèo vèo tới nè! Shipper Bytesme sắp gõ cửa rồi, bạn ơi! 🛵💨";
					break;
				case 'delivered':
					$body = "Món ngon đã trao tay! Bytesme chúc bạn có một bữa ăn thật ấm cúng và ngon miệng nha! 🥰🍽️";
					break;
				case 'cancelled':
					$body = "Đơn hàng Bytesme của bạn đã được hủy theo yêu cầu. Bytesme rất tiếc và mong sẽ sớm được phục vụ bạn lần sau! 🙁";
					break;
				default:
					$body = "Đơn hàng Bytesme của bạn có cập nhật mới: {$this->status}. Xem chi tiết ngay bạn nhé! 😉";
			}
		}

		return [
			'title' => $title,
			'body' => $body,
			'data' => [
				'order_id' => $this->order->order_id,
				'status' => $this->status,
				'type' => 'OrderStatusNotification', // Type for client-side routing/handling
			],
			'badge' => $notifiable->unreadNotifications()->count() + 1,
		];
	}


	/**
	 * Get the type of the notification being broadcast.
	 *
	 * @return string
	 */
	public function broadcastType(): string
	{
		return 'OrderStatusNotification';
	}

	public function broadcastAs(): string
	{
		return 'OrderStatusNotification';
	}

	// public function broadcastOn()
	// {
	// 	return new \Illuminate\Broadcasting\Channel('order-status');
	// }
}
