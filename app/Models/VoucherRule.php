<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class VoucherRule
 *
 * @property int $rule_id
 * @property string|null $voucher_rule_type
 * @property string|null $voucher_rule_value
 * @property int $voucher_id
 *
 * @property Voucher $voucher
 *
 * @package App\Models
 */
class VoucherRule extends Model
{
	protected $table = 'voucher_rules';
	protected $primaryKey = 'rule_id';
	public $timestamps = false;

	protected $casts = [
		'voucher_id' => 'int',
		'voucher_rule_value' => 'string',
		'voucher_rule_type' => 'string',
	];

	protected $fillable = [
		'voucher_rule_type',
		'voucher_rule_value',
		'voucher_id'
	];

	public function voucher()
	{
		return $this->belongsTo(Voucher::class, 'voucher_id', 'voucher_id');
	}
}
