<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Voucher;
use App\Models\VoucherRule;

class VoucherRuleSeeder extends Seeder
{
	public function run()
	{
		// Enhanced voucher rules that match the simplified voucher_fields approach
		$voucherRules = [
			// Original vouchers rules
			'WELCOME10' => [
				[
					'voucher_rule_type' => 'first_order',
					'voucher_rule_value' => 'true'
				]
			],
			'SAVE50K' => [
				[
					'voucher_rule_type' => 'min_bill_price',
					'voucher_rule_value' => '500000'
				]
			],
			'TETDL2025' => [
				[
					'voucher_rule_type' => 'category_restriction',
					'voucher_rule_value' => '1,2,5' // Cake, Pastry, and Dessert categories
				]
			],
			'NOEL24' => [
				[
					'voucher_rule_type' => 'remaining_quantity',
					'voucher_rule_value' => '150',
				]
			],
			'FREESHIP300' => [
				[
					'voucher_rule_type' => 'min_bill_price',
					'voucher_rule_value' => '300000'
				],
				[
					'voucher_rule_type' => 'max_distance',
					'voucher_rule_value' => '10'
				]
			],
			'BOGO2025' => [
				[
					'voucher_rule_type' => 'product_id',
					'voucher_rule_value' => '3:2'
				],
			],
			'B2G1party' => [
				[
					'voucher_rule_type' => 'min_bill_price',
					'voucher_rule_value' => '200000'
				],
				[
					'voucher_rule_type' => 'product_id',
					'voucher_rule_value' => '1:2,5:2' // 1:2 means buy 2 of product 1, get 1 free, 5:2 means buy 2 of product 5, get 1 free
				]
			],

			// Mock vouchers rules
			'SNHAT-BYTESME' => [
				[
					'voucher_rule_type' => 'min_bill_price',
					'voucher_rule_value' => '150000'
				],
				[
					'voucher_rule_type' => 'max_discount',
					'voucher_rule_value' => '50000'
				]
			],
			'FREESHIP-25K' => [
				[
					'voucher_rule_type' => 'min_bill_price',
					'voucher_rule_value' => '100000'
				],
				[
					'voucher_rule_type' => 'max_distance',
					'voucher_rule_value' => '5'
				]
			],
			'VIP-CUSTOMER20' => [
				[
					'voucher_rule_type' => 'min_bill_price',
					'voucher_rule_value' => '250000'
				],
				[
					'voucher_rule_type' => 'max_discount',
					'voucher_rule_value' => '100000'
				]
			],
			'NEWUSER-30K' => [
				[
					'voucher_rule_type' => 'min_bill_price',
					'voucher_rule_value' => '120000'
				],
				[
					'voucher_rule_type' => 'first_order',
					'voucher_rule_value' => 'true'
				]
			],
			'WEEKEND-10PCT' => [
				[
					'voucher_rule_type' => 'min_bill_price',
					'voucher_rule_value' => '180000'
				],
				[
					'voucher_rule_type' => 'max_discount',
					'voucher_rule_value' => '45000'
				],
				// [
				// 	'voucher_rule_type' => 'day_restriction',
				// 	'voucher_rule_value' => '6,0'  // Weekend only
				// ]
			],
			'JULY-SALE15K' => [
				[
					'voucher_rule_type' => 'remaining_quantity',
					'voucher_rule_value' => '1000'
				]
			],
			'COMBO-SAVE25K' => [
				[
					'voucher_rule_type' => 'min_bill_price',
					'voucher_rule_value' => '150000'
				],
				[
					'voucher_rule_type' => 'min_items',
					'voucher_rule_value' => '2'
				]
			],
			'FREESHIP-EXTRA40' => [
				[
					'voucher_rule_type' => 'min_bill_price',
					'voucher_rule_value' => '250000'
				],
				[
					'voucher_rule_type' => 'max_discount',
					'voucher_rule_value' => '40000'
				]
			],
			'LOYAL-8PCT' => [
				[
					'voucher_rule_type' => 'min_bill_price',
					'voucher_rule_value' => '300000'
				]
			],
			'FLASH35PCT' => [
				[
					'voucher_rule_type' => 'min_bill_price',
					'voucher_rule_value' => '200000'
				],
				[
					'voucher_rule_type' => 'remaining_quantity',
					'voucher_rule_value' => '500'
				]
			],
			'BUNDLE-MEAL' => [
				[
					'voucher_rule_type' => 'min_bill_price',
					'voucher_rule_value' => '150000'
				],
				[
					'voucher_rule_type' => 'category_id',
					'voucher_rule_value' => '2,3' // Must include items from Pastry and Coffee
				]
			],
			// 'HAPPY-HOUR' => [
			// 	[
			// 		'voucher_rule_type' => 'min_bill_price',
			// 		'voucher_rule_value' => '100000'
			// 	],
			// 	[
			// 		'voucher_rule_type' => 'hour_restriction',
			// 		'voucher_rule_value' => '15:00-17:00'
			// 	]
			// ]
		];

		// Create voucher rules for all vouchers
		foreach ($voucherRules as $voucherCode => $rules) {
			if ($voucher = Voucher::where('voucher_code', $voucherCode)->first()) {
				foreach ($rules as $rule) {
					VoucherRule::create([
						'voucher_rule_type' => $rule['voucher_rule_type'],
						'voucher_rule_value' => $rule['voucher_rule_value'],
						'voucher_id' => $voucher->voucher_id
					]);
				}
			}
		}

		$this->command->info('Voucher rules seeded successfully!');
	}
}