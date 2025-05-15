<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Category
 * 
 * @property string $category_id
 * @property string $category_name
 * @property int|null $category_type
 * @property string|null $category_background_url
 * @property string|null $category_description
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Collection|ProductCategory[] $product_categories
 *
 * @package App\Models
 */
class Category extends Model
{
	protected $table = 'categories';
	protected $primaryKey = 'category_id';
	public $incrementing = false;

	protected $casts = [
		'category_name' => 'string',
		'category_background_url' => 'string',
		'category_description' => 'string',
		'category_type' => 'int',
	];

	protected $fillable = [
		'category_name',
		'category_type',
		'category_description',
		'category_background_url',
	];
}
