<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class OrderStatusEvent implements ShouldBroadcastNow
{
	use Dispatchable, InteractsWithSockets, SerializesModels;

	public $orderId;
	public $newStatus;

	public function __construct($orderId, $newStatus)
	{
		$this->orderId = $orderId;
		$this->newStatus = $newStatus;
		Log::info("OrderStatusUpdatedEvent created for order {$orderId}, status: {$newStatus}");
	}

	public function broadcastOn()
	{
		$channel = new Channel("order-status");
		Log::info("Broadcasting test event to channel: {$channel->name}");
		return $channel;
	}

	public function broadcastAs()
	{
		return 'OrderStatusEvent';
	}
}