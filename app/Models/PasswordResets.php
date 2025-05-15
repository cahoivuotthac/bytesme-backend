<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Cart
 *
 * @property string $token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property Collection|User[] $users
 *
 * @package App\Models
 */
class PasswordResets extends Model
{
	protected $table = 'password_resets';
	protected $primaryKey = 'token';

	protected $casts = [
		'token' => 'string',
	];

	protected $fillable = [
		'token'
	];
}
