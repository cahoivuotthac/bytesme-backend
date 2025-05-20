<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Voucher
 * 
 * @property int $voucher_id
 * @property string $voucher_name
 * @property string $voucher_description
 * @property string $voucher_fields
 * @property Carbon $voucher_start_date
 * @property Carbon $voucher_end_date
 * @property string $voucher_type
 * @property string $voucher_code
 * @property int $voucher_value
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Collection|Order[] $orders
 *
 * @package App\Models
 */
class Voucher extends Model
{
	protected $table = 'app_data.vouchers';
	protected $primaryKey = 'voucher_id';

	protected $casts = [
		'voucher_id' => 'int',
		'voucher_name' => 'string',
		'voucher_description' => 'string',
		'voucher_fields' => 'json', // Changed to json since it's a text field that likely stores structured data
		'voucher_code' => 'string',
		'voucher_start_date' => 'datetime',
		'voucher_end_date' => 'datetime',
		'voucher_type' => 'string', // Enum type: percentage, cash, gift_product
		'voucher_value' => 'int'
	];

	protected $fillable = [
		'voucher_name',
		'voucher_description',
		'voucher_fields',
		'voucher_code',
		'voucher_start_date',
		'voucher_end_date',
		'voucher_type',
		'voucher_value'
	];

	/**
	 * Get the current status of the voucher based on dates.
	 * 
	 * @return string
	 */
	public function getStatusAttribute()
	{
		$now = Carbon::now();
		if ($now->lt($this->voucher_start_date)) {
			return 'not_yet';
		}
		if ($now->gt($this->voucher_end_date)) {
			return 'expired';
		}
		return 'active';
	}

	/**
	 * Get the orders associated with this voucher.
	 */
	public function orders()
	{
		return $this->hasMany(Order::class, 'voucher_id', 'voucher_id');
	}

	/**
	 * Get the rules associated with this voucher.
	 */
	public function voucherRules()
	{
		return $this->hasMany(VoucherRule::class, 'voucher_id', 'voucher_id');
	}
}
