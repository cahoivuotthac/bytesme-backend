<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Auth\Authenticatable as AuthenticatableTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Class User
 *
 * @property int $user_id
 * @property int|null $cart_id
 * @property int $role_type
 * @property string|null $email
 * @property string $name
 * @property string|null $password
 * @property string $phone_number
 * @property string $province_city
 * @property string $district
 * @property string|null $commune_ward
 * @property string $address
 * @property string|null $gender
 * @property Carbon|null $date_of_birth
 * @property string|null $avatar
 * @property int|null $card_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property Cart|null $cart
 * @property Collection|Order[] $orders
 * @property Collection|OrderFeedback[] $product_feedbacks
 *
 * @package App\Models
 */
class User extends Authenticatable
{
	use HasFactory, HasApiTokens;
	use AuthenticatableTrait;
	use Notifiable;

	protected $table = 'users';
	protected $primaryKey = 'user_id';
	public $incrementing = true;

	protected $casts = [
		'user_id' => 'int',
		'role_type' => 'int',
		'date_of_birth' => 'datetime',
		'cart_id' => 'int',
		'password' => 'hashed',
		'remember_token' => 'hashed',
		'avatar' => 'string',
	];

	protected $hidden = [
		'password',
		'remember_token',
	];

	protected $fillable = [
		'role_type',
		'email',
		'name',
		'password',
		'remember_token',
		'phone_number',
		// 'gender',
		// 'date_of_birth',
		'avatar',
		'cart_id',
		'phone_verified_at',
	];

	public function cart()
	{
		return $this->belongsTo(Cart::class, 'cart_id', 'cart_id');
	}

	public function orders()
	{
		return $this->hasMany(Order::class, 'user_id', 'user_id');
	}

	public function order_feedbacks()
	{
		return $this->hasMany(OrderFeedback::class, 'user_id', 'user_id');
	}

	// public function return_refund_items()
	// {
	// 	return $this->hasMany(ReturnRefundItem::class);
	// }

	public function wishlist()
	{
		return $this->belongsToMany(
			Product::class,
			'wishlists',
			'user_id',     // Foreign key on the pivot table referencing the user
			'product_id'   // Foreign key on the pivot table referencing the product
		);
	}

	public function user_addresses()
	{
		return $this->hasMany(UserAddress::class, 'user_id', 'user_id');
	}

	public function personal_access_tokens()
	{
		return $this->morphMany(PersonalAccessToken::class, 'tokenable');
	}

	public function expo_push_notifications()
	{
		return $this->personal_access_tokens()
			->pluck('expo_push_token') // Return a Collection already, not a query builder
			->filter() // Remove any null/empty values
			->values() // Reset array keys
			->toArray(); // Convert to plain array
	}
}
