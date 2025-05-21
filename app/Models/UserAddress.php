<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserAddress extends Model
{
	protected $table = 'user_addresses';
	protected $primaryKey = 'user_address_id';

	// protected $casts = [
	// 	'' => 'int'
	// ];

	protected $fillable = [ //mass-assigned
		'urban_name',
		'urban_code',
		'suburb_name',
		'suburb_code',
		'quarter_name',
		'quarter_code',
		'full_address',
		'is_default_address',
		'user_id',
	];
}
;