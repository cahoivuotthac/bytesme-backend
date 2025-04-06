<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
	public function run()
	{
		$this->seedAdminUser();
		$this->seedNormalUsers(5);
	}

	protected function seedAdminUser()
	{
		$hasAdminUser = User::where('role_type', 1)->exists();

		if (!$hasAdminUser) {
			User::create([
				'name' => 'Nguyễn Văn Admin',
				'email' => 'admin@example.com',
				'password' => bcrypt('password'),
				'phone_number' => fake()->numerify(fake()->randomElement(['#########', '##########'])),
				'role_type' => 1,
			]);
		}
	}

	protected function seedNormalUsers($count)
	{
		User::factory()->count($count)->create();
	}
}