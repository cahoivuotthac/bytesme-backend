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
		// We'll send this notification via the database and broadcast channels
		return ['database', 'broadcast'];
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
		return [
			'body' => $this->message,
			'title' => 'Order updated',
			// 'sound' => 'default',
			// 'priority' => 'high',
			'data' => [
				'order_id' => $this->order->order_id,
				'status' => $this->status,
			],
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
