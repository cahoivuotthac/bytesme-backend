<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use App\Notifications\OrderStatusNotification;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Broadcast;

/**
 * Class Order
 *
 * @property int $order_id
 * @property int|null $user_id
 * @property int|null $voucher_id
 * @property int|null $order_provisional_price
 * @property int|null $order_deliver_cost
 * @property int|null $order_total_price
 * @property Carbon|null $order_payment_date
 * @property string|null $order_payment_method
 * @property bool|null $order_is_paid
 * @property string|null $order_status
 * @property string|null $order_additional_note
 * @property Carbon|null $order_deliver_time
 * @property string|null $order_deliver_address
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property User|null $user
 * @property Voucher|null $voucher
 * @property Collection|OrderItem[] $order_items
 *
 * @package App\Models
 */
class Order extends Model
{
	use HasFactory;

	protected $table = 'orders';
	protected $primaryKey = 'order_id';

	protected $casts = [
		'user_id' => 'int',
		'voucher_id' => 'int',
		'order_provisional_price' => 'int',
		'order_deliver_cost' => 'int',
		'order_total_price' => 'int',
		'order_payment_date' => 'datetime',
		'order_payment_method' => 'string',
		'order_deliver_time' => 'datetime',
		'order_is_paid' => 'bool',
		'order_status' => 'string', // online_payment_pending, pending, delivering, delivered, cancelled
		'order_additional_note' => 'string',
		'order_deliver_address' => 'string',
	];

	protected $fillable = [
		'user_id',
		'voucher_id',
		'order_provisional_price',
		'order_deliver_cost',
		'order_total_price',
		'order_payment_date',
		'order_payment_method',
		'order_is_paid',
		'order_status',
		'order_additional_note',
		'order_deliver_time',
		'order_deliver_address',
	];

	/**
	 * The "booted" method of the model.
	 */
	protected static function booted(): void
	{
		// Listen for when an order is updated
		static::updating(function (Order $order) {
			// If the order status has changed, send notification to the user
			if (!$order->isDirty('order_status')) {
				return;
			}

			$newStatus = $order->order_status;
			$oldStatus = $order->getOriginal('order_status');

			// Only notify if there's an actual status change
			if ($newStatus == 'pending') {
				Log::debug("Skipped update noti for order creation event");
				return;
			}

			// Generate a message for the notification
			switch ($newStatus) {
				case 'delivering':
					$message = "Đơn hàng #{$order->order_id} của bạn đã được xác nhận và đang được giao";
					break;
				case 'delivered':
					$message = "Đơn hàng #{$order->order_id} của bạn đã được giao thành công";
					break;
				case 'cancelled':
					$message = "Đơn hàng #{$order->order_id} của bạn đã bị hủy";
					break;
			}

			// Find the user who owns the order and notify them
			$user = User::where('user_id', $order->user_id)->first();

			$user->notify(new OrderStatusNotification(
				$order,
				$newStatus,
				$message
			));

			// Send direct websocket broadcast
			broadcast(new \App\Events\OrderStatusEvent(
				$order->order_id,
				$newStatus
			))->toOthers(); // Except the updater

			// Also notify admins if needed
			$admins = User::where('role_type', 1)->get();
			foreach ($admins as $admin) {
				// Don't notify the admin if they're the order owner
				if ($admin->user_id != $order->user_id) {
					$admin->notify(new OrderStatusNotification($order, $newStatus));
				}
			}

			Log::info("Order #{$order->order_id} status updated from {$oldStatus} to {$newStatus}");
		});

		// Listen for when a new order is created
		static::created(function (Order $order) {
			// Skip notifications if running in seeder context
			if (app()->runningInConsole() && app()->runningUnitTests() === false) {
				// Check if the command is db:seed or migrate:fresh --seed
				$commands = ['db:seed', 'migrate:fresh', 'migrate:refresh', 'migrate:reset', 'migrate:rollback'];
				foreach ($commands as $cmd) {
					if (collect($_SERVER['argv'] ?? [])->contains($cmd)) {
						return;
					}
				}
			}

			// Notify the user about their new order
			$user = User::where('user_id', $order->user_id)->first();
			Log::debug("User #{$user->user_id} found for order #{$order->order_id}");
			if ($user) {
				$user->notify(new OrderStatusNotification(
					$order,
					$order->order_status,
					"Đơn hàng #{$order->order_id} của bạn vừa được tạo"
				));
			}

			// Also notify admins about the new order
			$admins = User::where('role_type', 1)->get();
			foreach ($admins as $admin) {
				// Don't notify the admin if they're the order owner
				if ($admin->user_id != $order->user_id) {
					$admin->notify(new OrderStatusNotification(
						$order,
						$order->order_status,
						"New order #{$order->order_id} has been created by user #{$order->user_id}"
					));
				}
			}
			Log::info("Order #{$order->order_id} created and notifications sent.");
		});
	}

	public function user()
	{
		return $this->belongsTo(User::class, 'user_id', 'user_id');
	}

	public function voucher()
	{
		return $this->belongsTo(Voucher::class, 'voucher_id', 'voucher_id');
	}

	public function order_items(): HasMany
	{
		return $this->hasMany(OrderItem::class, 'order_id', 'order_id');
	}

	/**
	 * Update the order status and send notification
	 * 
	 * @param string $status The new status value
	 * @param string|null $message Optional custom message for the notification
	 * @return bool Whether the update was successful
	 */
	public function updateStatus(string $status, string $message = null): bool
	{
		$oldStatus = $this->order_status;
		$this->order_status = $status;

		$result = $this->save();

		return $result;
	}
}
