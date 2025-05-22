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
	public $paymentStatus;
	public $paymentMethod;

	/**
	 * Create a new notification instance.
	 */
	public function __construct($orderId, $paymentStatus, $paymentMethod)
	{
		$this->orderId = $orderId;
		$this->paymentStatus = $paymentStatus ?? 'pending';
		$this->paymentMethod = $paymentMethod;
		Log::info("OnlinePaymentEvent created for order {$orderId}, status: {$paymentStatus}, method: {$paymentMethod}");
	}

	/**
	 * Get the notification's delivery channels.
	 *
	 * @return array<int, string>
	 */
	public function via(object $notifiable): array
	{
		return ['database'];
	}

	/**
	 * Get the mail representation of the notification.
	 */
	// public function toMail(object $notifiable): MailMessage
	// {
	// 	return (new MailMessage)
	// 		->line('The introduction to the notification.')
	// 		->action('Notification Action', url('/'))
	// 		->line('Thank you for using our application!');
	// }

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

	public function toBroadcast(object $notifiable): BroadcastMessage
	{
		return (new BroadcastMessage([
			'message' => 'Your payment was successful!',
			'order_id' => 12345,
			'status' => 'Paid',
			'timestamp' => now()->toIso8601String(),
		]));
	}

	public function broadcastType(): string
	{
		return 'OrderStatusNotification';
	}

	public function broadcastAs(): string
	{
		return 'OrderStatusNotification';
	}

}
