<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Voucher;
use Carbon\Carbon;

class VoucherSeeder extends Seeder
{
	public function run()
	{
		// Original vouchers with corrected fields
		Voucher::create([
			'voucher_name' => 'Chào mừng khách hàng mới 10%',
			'voucher_code' => 'WELCOME10',
			'voucher_type' => 'percentage',
			'voucher_fields' => 'new_customer',
			'voucher_description' => 'Giảm ngay 10% cho lần mua sắm đầu tiên',
			'voucher_start_date' => now()->subDays(50),
			'voucher_end_date' => now()->addDays(60),
			'voucher_value' => 10,
		]);

		Voucher::create([
			'voucher_name' => 'Tiết kiệm 50K',
			'voucher_code' => 'SAVE50K',
			'voucher_type' => 'cash',
			'voucher_fields' => 'shop_related',
			'voucher_description' => 'Giảm 50.000 ₫ với đơn hàng trên 500.000 ₫',
			'voucher_start_date' => now(),
			'voucher_end_date' => now()->addDays(15),
			'voucher_value' => 50000,
		]);

		Voucher::create([
			'voucher_name' => 'Khuyến mãi Tết Dương Lịch 25%',
			'voucher_code' => 'TETDL2025',
			'voucher_type' => 'percentage',
			'voucher_fields' => 'holiday',
			'voucher_description' => 'Tết Dương Lịch 2025 - Giảm sốc 25% cho toàn bộ đơn hàng',
			'voucher_start_date' => now()->addDays(5),
			'voucher_end_date' => now()->addDays(55),
			'voucher_value' => 25,
		]);

		Voucher::create([
			'voucher_name' => 'Giảm giá Giáng sinh 24%',
			'voucher_code' => 'NOEL24',
			'voucher_type' => 'percentage',
			'voucher_fields' => 'holiday',
			'voucher_description' => 'Noel giảm giá 24% cho mọi đơn hàng!',
			'voucher_start_date' => now()->addDays(5),
			'voucher_end_date' => now()->addDays(35),
			'voucher_value' => 24,
		]);

		Voucher::create([
			'voucher_name' => 'Miễn phí vận chuyển đơn 300K',
			'voucher_code' => 'FREESHIP300',
			'voucher_type' => 'cash',
			'voucher_fields' => 'freeship',
			'voucher_description' => 'Miễn phí vận chuyển cho đơn hàng từ 300.000 ₫',
			'voucher_start_date' => now()->addDays(1),
			'voucher_end_date' => now()->addDays(45),
			'voucher_value' => 30000, // Maximum shipping fee covered
		]);

		Voucher::create([
			'voucher_name' => 'Mua 1 tặng 1 mừng xuân',
			'voucher_code' => 'BOGO2025',
			'voucher_type' => 'gift_product',
			'voucher_fields' => 'holiday',
			'voucher_description' => 'Mua 1 tặng 1 mừng xuân Ất Tỵ',
			'voucher_start_date' => now(),
			'voucher_end_date' => now()->addDays(30),
			'voucher_value' => '3:1' // 3:1 means you get 1 unit of product with product_id = 3 for free
		]);

		Voucher::create([
			'voucher_name' => 'Mua 2 Donut tặng 1',
			'voucher_code' => 'B2G1party',
			'voucher_type' => 'gift_product',
			'voucher_fields' => 'shop_related',
			'voucher_description' => 'Mua 2 Donut bất kỳ, nhận ngay 1 nước ngọt miễn phí',
			'voucher_start_date' => now(),
			'voucher_end_date' => now()->addDays(30),
			'voucher_value' => '1:1,5:1:M' // 1:1:L means you get 1 unit of size 'L' of product with product_id = 1 for free, and 5:1 means you get 1 unit of sizes 'M' of product with product_id = 5 for free
		]);

		// Add mock vouchers with correct fields

		// 1. Mừng sinh nhật
		Voucher::create([
			'voucher_code' => 'SNHAT-BYTESME',
			'voucher_name' => 'Ưu đãi sinh nhật 15%',
			'voucher_description' => 'Giảm 15% tối đa 50K',
			'voucher_fields' => 'birthday_gift',
			'voucher_start_date' => Carbon::parse('2024-05-01T00:00:00Z'),
			'voucher_end_date' => Carbon::parse('2024-06-30T23:59:59Z'),
			'voucher_type' => 'percentage',
			'voucher_value' => 15,
		]);

		// 2. Freeship
		Voucher::create([
			'voucher_code' => 'FREESHIP-25K',
			'voucher_name' => 'Miễn phí vận chuyển 25K',
			'voucher_description' => 'Miễn phí vận chuyển đến 25K',
			'voucher_fields' => 'freeship',
			'voucher_start_date' => Carbon::parse('2024-01-01T00:00:00Z'),
			'voucher_end_date' => Carbon::parse('2024-12-31T23:59:59Z'),
			'voucher_type' => 'cash',
			'voucher_value' => 25000,
		]);

		// 3. Khách hàng VIP
		Voucher::create([
			'voucher_code' => 'VIP-CUSTOMER20',
			'voucher_name' => 'Đặc quyền khách VIP 20%',
			'voucher_description' => 'Giảm 20% tối đa 100K',
			'voucher_fields' => 'loyal_customer',
			'voucher_start_date' => Carbon::parse('2024-04-01T00:00:00Z'),
			'voucher_end_date' => Carbon::parse('2024-07-31T23:59:59Z'),
			'voucher_type' => 'percentage',
			'voucher_value' => 20,
		]);

		// 4. Khách mới
		Voucher::create([
			'voucher_code' => 'NEWUSER-30K',
			'voucher_name' => 'Ưu đãi khách hàng mới 30K',
			'voucher_description' => 'Giảm 30K cho đơn đầu tiên',
			'voucher_fields' => 'new_customer',
			'voucher_start_date' => Carbon::parse('2024-01-01T00:00:00Z'),
			'voucher_end_date' => Carbon::parse('2024-12-31T23:59:59Z'),
			'voucher_type' => 'cash',
			'voucher_value' => 30000,
		]);

		// 5. Cuối tuần vui vẻ
		Voucher::create([
			'voucher_code' => 'WEEKEND-10PCT',
			'voucher_name' => 'Khuyến mãi cuối tuần 10%',
			'voucher_description' => 'Giảm 10% tối đa 45K vào T7, CN',
			'voucher_fields' => 'shop_related',
			'voucher_start_date' => Carbon::parse('2024-06-01T00:00:00Z'),
			'voucher_end_date' => Carbon::parse('2024-08-31T23:59:59Z'),
			'voucher_type' => 'percentage',
			'voucher_value' => 10,
		]);

		// 6. Siêu deal tháng 7
		Voucher::create([
			'voucher_code' => 'JULY-SALE15K',
			'voucher_name' => 'Khuyến mãi tháng 7 giảm 15K',
			'voucher_description' => 'Giảm 15K không điều kiện',
			'voucher_fields' => 'shop_related',
			'voucher_start_date' => Carbon::parse('2024-07-01T00:00:00Z'),
			'voucher_end_date' => Carbon::parse('2024-07-31T23:59:59Z'),
			'voucher_type' => 'cash',
			'voucher_value' => 15000,
		]);

		// 7. Combo tiết kiệm
		Voucher::create([
			'voucher_code' => 'COMBO-SAVE25K',
			'voucher_name' => 'Tiết kiệm combo 25K',
			'voucher_description' => 'Giảm 25K cho combo từ 2 món',
			'voucher_fields' => 'shop_related',
			'voucher_start_date' => Carbon::parse('2024-05-15T00:00:00Z'),
			'voucher_end_date' => Carbon::parse('2024-09-15T23:59:59Z'),
			'voucher_type' => 'cash',
			'voucher_value' => 25000,
		]);

		// 8. Freeship Extra
		Voucher::create([
			'voucher_code' => 'FREESHIP-EXTRA40',
			'voucher_name' => 'Miễn phí vận chuyển cao cấp 40K',
			'voucher_description' => 'Miễn phí vận chuyển đến 40K',
			'voucher_fields' => 'freeship',
			'voucher_start_date' => Carbon::parse('2024-06-01T00:00:00Z'),
			'voucher_end_date' => Carbon::parse('2024-06-30T23:59:59Z'),
			'voucher_type' => 'cash',
			'voucher_value' => 40000,
		]);

		// 9. Thành viên thân thiết
		Voucher::create([
			'voucher_code' => 'LOYAL-8PCT',
			'voucher_name' => 'Ưu đãi thành viên thân thiết 8%',
			'voucher_description' => 'Giảm 8% không giới hạn',
			'voucher_fields' => 'loyal_customer',
			'voucher_start_date' => Carbon::parse('2024-01-01T00:00:00Z'),
			'voucher_end_date' => Carbon::parse('2024-12-31T23:59:59Z'),
			'voucher_type' => 'percentage',
			'voucher_value' => 8,
		]);

		// 10. Flash Sale
		Voucher::create([
			'voucher_code' => 'FLASH35PCT',
			'voucher_name' => 'Flash Sale 35%',
			'voucher_description' => 'Giảm sốc 35% trong 3 giờ',
			'voucher_fields' => 'shop_related',
			'voucher_start_date' => Carbon::parse('2025-05-20T16:00:00Z'), // 3-hour flash sale
			'voucher_end_date' => Carbon::parse('2025-05-20T19:00:00Z'),
			'voucher_type' => 'percentage',
			'voucher_value' => 35,
		]);

		// 11. Bundle discount
		Voucher::create([
			'voucher_code' => 'BUNDLE-MEAL',
			'voucher_name' => 'Combo bữa ăn',
			'voucher_description' => 'Giảm 30K khi mua đủ bộ bữa ăn',
			'voucher_fields' => 'shop_related',
			'voucher_start_date' => Carbon::parse('2025-05-01T00:00:00Z'),
			'voucher_end_date' => Carbon::parse('2025-08-31T23:59:59Z'),
			'voucher_type' => 'cash',
			'voucher_value' => 30000,
		]);
	}
}