<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ingredient extends Model
{
	protected $table = 'ingredients';
	protected $primaryKey = 'ingredient_id';
	public $incrementing = false;

	protected $fillable = [
		'ingredient_name',
		'ingredient_description',
	];

	public function product_ingredients()
	{
		return $this->hasMany(ProductIngredient::class);
	}
}
