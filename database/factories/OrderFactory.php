<?php
namespace Database\Factories;

use App\Http\Controllers\OrderController;
use App\Models\Order;
use App\Models\User;
use App\Models\Voucher;
use App\Models\OrderItem;
use Faker\Factory as Faker;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
	protected $model = Order::class;

	public function definition()
	{
		$faker = Faker::create('vi_VN');
		$createdAt = $this->faker->dateTimeBetween('-6 months', 'now');

		return [
			'created_at' => $createdAt,
			'updated_at' => $createdAt,
			'user_id' => fn() => User::query()->exists()
				? User::query()->where('role_type', 0)->inRandomOrder()->first()->user_id
				: User::factory()->create()->user_id,
			'voucher_id' => fn() => $this->faker->boolean(25)
				? Voucher::query()->inRandomOrder()->first()->voucher_id
				: null,
			'order_provisional_price' => 0,
			'order_deliver_cost' => 20000,
			'order_total_price' => 0,
			'order_payment_date' => fn(array $attributes) => $attributes['order_is_paid']
				? $this->faker->dateTimeBetween($createdAt, (clone $createdAt)->modify('+1 hours'))
				: null,
			'order_status' => fn() => $this->faker->randomElement([
				'pending',
				'delivering',
				'delivered',
				'cancelled'
			]),
			'order_deliver_time' => function (array $attributes) use ($createdAt) {
				return isset($attributes['order_status']) && $attributes['order_status'] === 'delivered'
					? $this->faker->dateTimeBetween(
						(clone $createdAt)->modify('+15 minutes'),
						(clone $createdAt)->modify('+1 hour')
					)
					: null;
			},
			'order_payment_method' => fn() => $this->faker->randomElement(['cod', 'momo']),
			'order_is_paid' => fn(array $attributes) => in_array($attributes['order_status'], ['delivering', 'delivered']),
			'order_additional_note' => fn() => $this->faker->optional()->sentence(),
			'order_deliver_address' => $this->faker->address(),
		];
	}

	public function configure()
	{
		return $this->afterCreating(function (Order $order) {
			// Create 1-3 OrderItems for the Order
			$itemCount = rand(1, 3);
			$orderItems = OrderItem::factory()
				->count($itemCount)
				->forOrder($order)
				->create();

			// Update total_orders of Products
			foreach ($orderItems as $orderItem) {
				$orderItem->product->product_total_orders += $orderItem->order_items_quantity;
				$orderItem->product->save();
			}

			// Calculate provisional_price by summing total_price of OrderItems
			$subtotal = $orderItems->sum(
				fn($orderItem) =>
				(int) $orderItem->order_items_unitprice * (int) $orderItem->order_items_quantity
			);

			// Update the Order with the calculated prices
			$order->order_provisional_price = $subtotal;
			$order->order_total_price = $subtotal + $order->order_deliver_cost;

			// Apply voucher/coupon if available
			if (!empty($order->voucher)) {
				// switch ($order->voucher->voucher_type) {
				// 	case 'cash':
				// 		$order->order_total_price -= $order->voucher->value;
				// 		break;
				// 	default:
				// 		$order->total_price *= 1 - $order->voucher->value / 100;
				// 		break;
				// }
				OrderController::applyVoucher(
					$order->voucher,
					$order,
					$orderItems
				);
			}
			$order->save();
		});
	}

	// State modifiers
	public function pending()
	{
		return $this->state(function (array $attributes) {
			return [
				'status' => 'pending',
			];
		});
	}

	public function delivering()
	{
		return $this->state(function (array $attributes) {
			return [
				'status' => 'delivering',
			];
		});
	}

	public function delivered()
	{
		return $this->state(function (array $attributes) {
			return [
				'status' => 'delivered',
				'is_paid' => true
			];
		});
	}
}


