<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MomoServiceTransaction extends Model
{
	protected $table = 'momo_service.transactions';
	protected $primaryKey = 'transaction_id';
	public $incrementing = false;
	protected $keyType = 'string';

	protected $fillable = [
		'transaction_id',
		'order_id',
	];

	public $timestamps = false;
}
