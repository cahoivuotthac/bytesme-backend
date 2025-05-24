<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExpoPushToken extends Model
{
	protected $table = 'expo_push_tokens';

	protected $primaryKey = 'push_token_id';

	public $timestamps = false;

	protected $fillable = [
		'push_token',
		'user_id',
	];

	public function user()
	{
		return $this->belongsTo(User::class, 'user_id', 'user_id');
	}
}
