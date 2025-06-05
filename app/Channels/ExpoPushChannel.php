<?php
// app/Channels/ExpoPushChannel.php

namespace App\Channels;

use App\Services\PushNotificationService;
use Illuminate\Notifications\Notification;

class ExpoPushChannel
{
	protected $pushService;

	public function __construct(PushNotificationService $pushService)
	{
		$this->pushService = $pushService;
	}

	/**
	 * Send the given notification.
	 */
	public function send($notifiable, Notification $notification)
	{
		if (!method_exists($notification, 'toExpoPush')) {
			return;
		}

		$data = $notification->toExpoPush($notifiable);

		if (!empty($data)) {
			$this->pushService->sendToUser($notifiable, $data);
		}
	}
}