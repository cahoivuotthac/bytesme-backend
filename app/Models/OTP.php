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
	protected $primaryKey = 'phone_number';

	protected $casts = [
		'verified_at' => 'string',
	];

	protected $fillable = [ //mass-assigned
		'phone_number',
		'code',
		'verified_at',
	];
}