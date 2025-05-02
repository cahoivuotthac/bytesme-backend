<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Wishlist
 * 
 * @property int $user_id
 * @property string $product_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Product $product
 * @property User $user
 *
 * @package App\Models
 */

class Wishlist extends Model
{
	protected $table = 'wishlists';
	public $incrementing = false;
	protected $primaryKey = ['user_id', 'product_id']; // Define composite primary key
	protected $keyType = 'array'; // Important for composite keys
	public $timestamps = true;

	protected $casts = [
		'user_id' => 'int'
	];

	protected $fillable = [
		'user_id',
		'product_id'
	];

	public function product()
	{
		return $this->belongsTo(Product::class, 'product_id', 'product_id');
	}

	public function user()
	{
		return $this->belongsTo(User::class, 'user_id', 'user_id');
	}

	/**
	 * Set the keys for a save update query.
	 * This is a override method.
	 *
	 * @param  \Illuminate\Database\Eloquent\Builder  $query
	 * @return \Illuminate\Database\Eloquent\Builder
	 */
	protected function setKeysForSaveQuery($query)
	{
		$query->where('user_id', $this->getAttribute('user_id'))
			->where('product_id', $this->getAttribute('product_id'));

		return $query;
	}

	// /**
	//  * Set the keys for a delete query.
	//  *
	//  * @param  \Illuminate\Database\Eloquent\Builder  $query
	//  * @return \Illuminate\Database\Eloquent\Builder
	//  */
	// protected function setKeysForDeleteQuery($query)
	// {
	// 	$query->where('user_id', $this->getAttribute('user_id'))
	// 		->where('product_id', $this->getAttribute('product_id'));

	// 	return $query;
	// }
}
