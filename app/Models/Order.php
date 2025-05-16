<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
		 'order_deliver_time' => 'datetime',
		 'order_is_paid' => 'bool',
		 'order_status' => 'string',
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

	public function user()
	{
		return $this->belongsTo(User::class, 'user_id', 'user_id');
	}

	public function voucher()
	{
		return $this->belongsTo(Voucher::class , 'voucher_id', 'voucher_id');
	}

	public function order_items(): HasMany
	{
		return $this->hasMany(OrderItem::class, 'order_id', 'order_id');
	}
}
