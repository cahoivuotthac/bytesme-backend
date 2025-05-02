<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\Cart;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
	/**
	 * The current password being used by the factory.
	 */
	protected static ?string $password;

	/**
	 * Define the model's default state.
	 *
	 * @return array<string, mixed>
	 */
	public function definition(): array
	{
		return [
			// 'role_type' => $this->faker->numberBetween(0, 1),
			'role_type' => 0,
			'email' => $this->faker->unique()->safeEmail(),
			'name' => $this->faker->name(),
			'password' => static::$password ??= Hash::make('password'),
			'phone_number' => fake()->numerify(fake()->randomElement(['#########', '##########'])),
			'phone_verified_at' => now(),
			'urban' => $this->faker->city(),
			'suburb' => $this->faker->streetName(),
			'quarter' => $this->faker->streetAddress(),
			'address' => $this->faker->address(),
			'gender' => $this->faker->randomElement(['Nam', 'Nữ', 'Khác']),
			'date_of_birth' => $this->faker->date(),
			// 'avatar' => $this->faker->imageUrl(200, 200, 'people'),
			'remember_token' => Str::random(length: 10),
			'cart_id' => null
		];
	}

	/**
	 * Indicate that the model's email address should be unverified.
	 */
	public function unverified(): static
	{
		return $this->state(fn(array $attributes) => [
			'phone_verified_at' => null,
		]);
	}

	public function configure()
	{ // Create a cart for each created user
		return $this->afterCreating(function ($user) {
			$userId = $user->getAttribute('user_id');
			$cart = Cart::create([
				'cart_id' => $userId,
				'items_count' => 0
			]);

			$user->update([
				'cart_id' => $cart->cart_id
			]);

		});
	}
}

