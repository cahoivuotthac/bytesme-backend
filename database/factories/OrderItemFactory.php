<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderItemFactory extends Factory
{
	protected $model = OrderItem::class;

	public function definition()
	{
		$product = Product::inRandomOrder()->first(); // Get random product from db
		$quantity = $this->faker->numberBetween(1, int2: 5);
		$discountPercentage = (float) ($product->getAttribute('discount_percentage'));

		$sizes = $product->sizes;
		$prices = $product->prices;
		$randomSizeIndex = array_rand($sizes);
		$selectedSize = $sizes[$randomSizeIndex];
		$selectedPrice = $prices[$randomSizeIndex];

		$discountedAmount = ceil($selectedPrice * $discountPercentage / 100);
		$unitPrice = $selectedPrice - $discountedAmount;

		return [
			'order_id' => Order::factory(),
			'product_id' => $product->product_id,
			'order_items_quantity' => $quantity,
			'order_items_size' => $selectedSize,
			'order_items_unitprice' => $unitPrice,
			'order_items_discounted_amount' => $discountedAmount
		];
	}

	// State modifier for specific quantity
	public function quantity(int $quantity)
	{
		return $this->state(function (array $attributes) use ($quantity) {
			$product = Product::find($attributes['product_id']);
			$discount = (float) ($product->getAttribute('discount_percentage') / 100);
			return [
				'quantity' => $quantity,
				'total_price' => $product->price * (1 - $discount) * $quantity
			];
		});
	}

	// State modifier for existing order
	public function forOrder(Order $order)
	{
		return $this->state(function (array $attributes) use ($order) {
			return ['order_id' => $order->order_id];
		});
	}
}