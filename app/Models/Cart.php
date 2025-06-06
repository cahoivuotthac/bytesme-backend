<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Cart
 *
 * @property int $cart_id
 * @property int|null $cart_items_count
 * @property CartItem|null $items
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property Collection|User[] $users
 *
 * @package App\Models
 */
class Cart extends Model
{
	protected $table = 'carts';
	protected $primaryKey = 'cart_id';

	public $incrementing = false;

	protected $casts = [
		'cart_id' => 'int',
		'cart_items_count' => 'int'
	];

	protected $fillable = [ //mass-assigned
		'cart_id',
		'cart_items_count'
	];

	public function user()
	{
		return $this->hasMany(User::class, 'cart_id', 'cart_id');
	}
	public function items()
	{
		return $this->hasMany(CartItem::class, 'cart_id', 'cart_id');
	}
}
