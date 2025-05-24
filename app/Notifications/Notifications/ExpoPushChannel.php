<?php
namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;

class ExpoPushChannel
{
	public function send($notifiable, Notification $notification)
	{
		$expoToken = $notifiable->expo_push_token;
		if (!$expoToken) {
			return;
		}

		$message = $notification->toExpoPush($notifiable);

		$payload = [
			'to' => $expoToken,
			'sound' => 'default',
			'title' => $message['title'] ?? '',
			'body' => $message['body'] ?? '',
			'data' => $message['data'] ?? [],
		];

		Http::post('https://exp.host/--/api/v2/push/send', $payload);
	}
}