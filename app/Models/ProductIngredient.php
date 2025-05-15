<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class ProductIngredient extends Model
{
	protected $table = 'product_ingredients';
	public $incrementing = false;

	protected $casts = [
		'product_id' => 'int',
		'ingredient_id' => 'int',
	];

	protected $fillable = [
		'product_id',
		'ingredient_id',
		'amount_used',
	];

	public function ingredient()
	{
		return $this->belongsTo(Ingredient::class);
	}

	public function product()
	{
		return $this->belongsTo(Product::class);
	}
}
