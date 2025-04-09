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
 * @property int|null $code
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
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
	];

	protected $fillable = [ //mass-assigned
		'phone_number',
		'code'

	];
}