<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory; // Add this

/**
 * Class OrderItem
 * 
 * @property int $order_items_id
 * @property int|null $order_id
 * @property int|null $product_id
 * @property int|null $order_items_quantity
 * @property int|null $order_items_discounted_amount
 * @property int|null $order_items_unitprice
 * @property string|null $order_items_size
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Order|null $order
 * @property Product|null $product
 *
 * @package App\Models
 */
class OrderItem extends Model
{
	use HasFactory;

	protected $table = 'order_items';
	protected $primaryKey = 'order_items_id';

	protected $casts = [
		'order_id' => 'int',
		'product_id' => 'int',
		'order_items_quantity' => 'int',
		'order_items_discounted_amount' => 'int',
		'order_items_unitprice' => 'int',
		'order_items_size' => 'string',
	];

	protected $fillable = [
		'order_id',
		'product_id',
		'order_items_quantity',
		'order_items_discounted_amount',
		'order_items_size',
		'order_items_unitprice',
	];

	public function order()
	{
		return $this->belongsTo(Order::class, 'order_id', 'order_id');
	}

	public function product()
	{
		return $this->belongsTo(Product::class, 'product_id', 'product_id');
	}

	// public function return_refund_items()
	// {
	// 	return $this->hasMany(ReturnRefundItem::class, 'order_items_id');
	// }
}
