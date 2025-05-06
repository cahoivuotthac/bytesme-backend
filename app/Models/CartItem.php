<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class CartItem
 *
 * @property int $cart_id
 * @property int $product_id
 * @property int|null $cart_items_quantity
 * @property int|null $cart_items_unitprice
 * @property string|null $cart_items_size
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property int $original_price
 * @property float $discount_amount
 * @property float $discounted_price
 * @package App\Models
 */
class CartItem extends Model
{
	protected $table = 'cart_items';
	public $incrementing = false;

	protected $casts = [
		'cart_id' => 'int',
		'product_id' => 'int',
		'cart_items_quantity' => 'int',
		'cart_items_unitprice' => 'int',
		'cart_items_size' => 'string',
	];

	protected $fillable = [
		'cart_id',
		'product_id',
		'cart_items_quantity',
		'cart_items_unitprice',
		'cart_items_size',
		// 'original_price',
		// 'discount_amount',
		// 'final_price'
	];

	public function cart(): BelongsTo
	{
		return $this->belongsTo(Cart::class, 'cart_id');
	}

	public function getUnitPriceAttribute()
	{
		return $this->getRelation('product')->price;
	}

	public function product(): BelongsTo
	{
		return $this->belongsTo(Product::class, 'product_id');
	}

	public function getOriginalPriceAttribute()
	{
		return $this->cart_items_unitprice * $this->cart_items_quantity;
	}
	public function getDiscountAmountAttribute()
	{
		return ($this->getRelation('product')->discount_percentage / 100) * $this->original_price;
	}
	public function getDiscountedPriceAttribute()
	{
		return $this->original_price - $this->discount_amount;
	}

	// Override the default save query to handle composite keys
	protected function setKeysForSaveQuery($query)
	{
		return $query->where('cart_id', $this->getAttribute('cart_id'))
			->where('product_id', $this->getAttribute('product_id'));
	}
}
