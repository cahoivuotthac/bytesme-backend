<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Order;

class OrderSeeder extends Seeder
{
	public function run()
	{
		Order::factory()->count(1000)->create();
	}
}