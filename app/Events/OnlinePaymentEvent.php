<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class OnlinePaymentEvent implements ShouldBroadcastNow
{
	use Dispatchable, InteractsWithSockets, SerializesModels;

	public $orderId;
	public $paymentStatus; // success, failed
	public $paymentMethod; // momo, vnpay, etc. (providers)

	public function __construct($orderId, $paymentStatus, $paymentMethod)
	{
		$this->orderId = $orderId;
		$this->paymentStatus = $paymentStatus ?? 'pending';
		$this->paymentMethod = $paymentMethod;
		Log::info("OnlinePaymentEvent created for order {$orderId}, status: {$paymentStatus}, method: {$paymentMethod}");
	}

	public function broadcastOn()
	{
		$channel = new Channel("online-payment");
		Log::info("OnlinePaymentEvent event will be broadcasted to channel: {$channel->name}");
		return $channel;
	}

	public function broadcastAs()
	{
		return 'OnlinePaymentEvent';
	}
}