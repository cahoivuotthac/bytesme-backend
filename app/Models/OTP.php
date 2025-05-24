<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Cart
 *
 * @property int $phone_number
 * @property string $verified_at
 * @property int|null $code
 *
 * @property Collection|User $user
 *
 * @package App\Models
 */
class OTP extends Model
{
	protected $table = 'otp';
	protected $primaryKey = 'email';

	protected $casts = [
		'verified_at' => 'string',
		'email' => 'string',
	];

	protected $fillable = [ //mass-assigned
		'email',
		'code',
		'verified_at',
	];
}