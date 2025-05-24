<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Log;

/**
 * Class Product
 *
 * @property string $product_id
 * @property int|null $type
 * @property string|null $code
 * @property string|null $name
 * @property string|null $short_description
 * @property string|null $detailed_description
 * @property array|null $product_sizes_prices
 * @property int|null $price
 * @property int|null $total_orders
 * @property int|null $total_ratings
 * @property float|null $overall_stars
 * @property string $product_unit_price
 * @property int|null $is_returnable
 * @property int|null $category_id
 * @property array $sizes
 * @property array $prices
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property Collection|OrderItem[] $order_items
 * @property Collection|Attribute[] $attributes
 * @property Collection|ProductCategory[] $product_categories
 * @property Collection|OrderFeedback[] $product_feedbacks
 * @property Wishlist $wishlist
 *
 * @package App\Models
 */
class Product extends Model
{
	protected $table = 'products';
	protected $primaryKey = 'product_id';
	public $incrementing = false;

	protected $casts = [
		'product_type' => 'int',
		'product_total_orders' => 'int',
		'product_total_ratings' => 'int',
		'product_overall_stars' => 'float',
		'category_id' => 'int',
		'product_stock_quantity' => 'int',
		'product_discount_percentage' => 'float',
		'product_unit_price' => 'json',
		'product_band' => 'string',
	];

	protected $fillable = [
		'product_type',
		'product_code',
		'product_name',
		'product_description',
		'product_sizes_prices',
		'product_discount_percentage',
		'product_total_orders',
		'product_total_ratings',
		'product_overall_stars',
		'product_stock_quantity',
		'product_band',
		'category_id',
	];

	public function order_items()
	{
		return $this->hasMany(OrderItem::class, 'product_id', 'product_id');
	}

	public function attributes()
	{
		return $this->belongsToMany(Attribute::class, 'product_attributes', 'product_id', 'attribute_id')
			->withPivot('value')
			->withTimestamps();
	}

	// public function categories()
	// {
	// 	return $this->belongsToMany(Category::class, 'product_categories', 'product_id', 'category_id');
	// }

	public function category()
	{
		return $this->belongsTo(Category::class, 'category_id', 'category_id');
	}

	public function product_feedbacks()
	{
		return $this->hasManyThrough(
			OrderFeedback::class,
			OrderItem::class,
			'product_id',      // Foreign key on OrderItem table...
			'order_id',        // Foreign key on OrderFeedback table...
			'product_id',      // Local key on Product table...
			'order_id'         // Local key on OrderItem table...
		);
	}

	public function wishlist()
	{
		return $this->hasMany(Wishlist::class, 'product_id', 'product_id');
	}

	public function product_images()
	{
		return $this->hasMany(ProductImage::class, 'product_id', 'product_id');
	}

	public function productImages()
	{
		return $this->hasMany(ProductImage::class, 'product_id', 'product_id');
	}

	public function getSoldQuantityAttribute()
	{
		return $this->order_items->sum('quantity');
	}

	public function sizes(): Attribute
	{
		return Attribute::make(
			get: function (mixed $value, array $attributes) {
				$sizesPrices = json_decode($attributes['product_unit_price'], true);
				$sizes = explode('|', $sizesPrices['product_sizes']);
				return $sizes;
			}
		);
	}

	public function prices(): Attribute
	{
		return Attribute::make(
			get: function (mixed $value, array $attributes) {
				$sizesPrices = json_decode($attributes['product_unit_price'], true);
				Log::debug('SizesPrices in prices function: ', $sizesPrices);
				$prices = explode('|', $sizesPrices['product_prices']);
				Log::debug("Splitted prices: ", $prices);
				$prices = array_map('intval', $prices);
				return $prices;
			}
		);
	}


	public function getClaimsDurationDays()
	{
		$categories = $this->categories()->pluck('name')->toArray();
		foreach ($categories as $category) {
			$firstLetter = explode(" ", $category)[0];
			return strtolower($firstLetter) === "cây"
				? config('constants.refund_return_policy.duration.plants')
				: config('constants.refund_return_policy.duration.others');
		}
	}
}
