<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
	/**
	 * Seed the application's database.
	 */
	public function run(): void
	{
		// Create mock data
		$this->call([
			UserSeeder::class,
			// VoucherSeeder::class,  // Run VoucherSeeder before VoucherRuleSeeder
			// VoucherRuleSeeder::class,
			// OrderSeeder::class,
		]);
	}
}
