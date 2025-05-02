<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductIngredient extends Model
{
	protected $table = 'product_ingredients';
	public $incrementing = false;

	public function ingredient()
	{
		return $this->belongsTo(Ingredient::class);
	}

	public function product()
	{
		return $this->belongsTo(Product::class);
	}
}
