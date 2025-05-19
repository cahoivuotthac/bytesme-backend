<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Category;
use Illuminate\Support\Facades\File;

class ProductSeeder extends Seeder
{
	/**
	 * Run the database seeds.
	 */
	public function run(): void
	{
		// Create categories if they don't exist
		// $this->createCategories();

		// Path where product images will be stored (you'll add images here later)
		$imagesPath = public_path('images/products');

		// Create directory if it doesn't exist
		if (!File::exists($imagesPath)) {
			File::makeDirectory($imagesPath, 0755, true);
		}

		// Generate 20 products
		for ($i = 1; $i <= 20; $i++) {
			// Generate product data
			$productType = rand(1, 5); // 1: Cake, 2: Pastry, 3: Coffee, 4: Cold Drinks, 5: Desserts
			$productName = $this->generateProductName($productType);
			$category = Category::inRandomOrder()->first();
			$category_id = $category ? $category->category_id : null;
			$productId = $i;

			// Create product
			$product = Product::create([
				'product_id' => $productId,
				'product_code' => 'P' . str_pad($i, 4, '0', STR_PAD_LEFT),
				'product_name' => $productName,
				'product_description' => $this->generateDescription($productName, $productType),
				'product_unit_price' => [
					'sizes' => 'S|M|L',
					'prices' => implode('|', [
						rand(20000, 150000),
						rand(40000, 250000),
						rand(30000, 200000),
					]), // Price from 20k to 150k VND
				]
				,
				'product_discount_percentage' => rand(0, 25), // Discount from 0% to 25%
				'product_total_orders' => rand(0, 200),
				'product_total_ratings' => rand(0, 80),
				'product_overall_stars' => rand(35, 50) / 10, // Rating from 3.5 to 5.0
				'product_stock_quantity' => rand(10, 100),
				'category_id' => $category_id, // Assuming category_id corresponds to product type
			]);

			// Create 1-4 product images for each product
			$this->createProductImages($product, $imagesPath, rand(1, 4));

			// Output progress for large seeding operations
			if ($i % 5 == 0) {
				$this->command->info("Created $i products");
			}
		}

		$this->command->info('All 20 products have been created successfully!');
	}

	/**
	 * Create product images referencing placeholder files
	 */
	private function createProductImages(Product $product, string $imagesPath, int $count): void
	{
		// For main product image (thumbnail)
		ProductImage::create([
			'product_id' => $product->product_id,
			'product_image_name' => $product->product_name . ' - Main',
			'product_image' => 'placeholder.jpg', // Placeholder until real images are added
			'product_image_url' => '/images/products/placeholder.jpg',
			'image_type' => 1, // 1 for main image/thumbnail
		]);

		// For additional product images
		for ($i = 1; $i < $count; $i++) {
			ProductImage::create([
				'product_id' => $product->product_id,
				'product_image_name' => $product->product_name . ' - Image ' . $i,
				'product_image' => 'placeholder' . $i . '.jpg', // Placeholder until real images are added
				'product_image_url' => '/images/products/placeholder' . $i . '.jpg',
				'image_type' => 2, // 2 for additional images
			]);
		}
	}

	/**
	 * Generate a product name based on type
	 */
	private function generateProductName(int $productType): string
	{
		$names = [
			1 => [ // Cakes
				'Bánh Tiramisu',
				'Bánh Red Velvet',
				'Bánh Chocolate Lava',
				'Bánh Cheesecake',
				'Bánh Crepe',
				'Bánh Opera',
				'Bánh Black Forest',
				'Bánh Matcha',
				'Bánh Yogurt Dâu',
				'Bánh Mousse Chanh Leo',
				'Bánh Caramen',
				'Bánh Vani',
				'Bánh Dâu Tây',
				'Bánh Sô-cô-la',
				'Bánh Caramel',
				'Bánh Socola Trắng',
				'Bánh Chanh',
				'Bánh Hạnh Nhân'
			],
			2 => [ // Pastries
				'Bánh Croissant',
				'Bánh Pain au Chocolat',
				'Bánh Mì Hoa Cúc',
				'Bánh Danish',
				'Bánh Donut',
				'Bánh Éclair',
				'Bánh Macaron',
				'Bánh Bagel',
				'Bánh Scone',
				'Bánh Pretzel',
				'Bánh Cupcake',
				'Bánh Muffin',
				'Bánh Tart',
				'Bánh Brownie',
				'Bánh Cookie',
				'Bánh Bông Lan',
				'Bánh Sandwich',
				'Bánh Gối'
			],
			3 => [ // Coffee
				'Cà Phê Đen',
				'Cà Phê Sữa',
				'Cappuccino',
				'Latte',
				'Espresso',
				'Americano',
				'Mocha',
				'Macchiato',
				'Affogato',
				'Cold Brew',
				'Cà Phê Cốt Dừa',
				'Cà Phê Trứng',
				'Cà Phê Bạc Xỉu',
				'Flat White',
				'Cà Phê Dừa Đá',
				'Cà Phê Caramel',
				'Cà Phê Irish',
				'Cà Phê Đá Xay'
			],
			4 => [ // Cold Drinks
				'Trà Đào',
				'Trà Vải',
				'Trà Chanh',
				'Trà Sữa Trân Châu',
				'Matcha Đá Xay',
				'Sinh Tố Xoài',
				'Sinh Tố Bơ',
				'Sinh Tố Dâu',
				'Nước Ép Cam',
				'Nước Ép Táo',
				'Nước Ép Dưa Hấu',
				'Mojito',
				'Smoothie Việt Quất',
				'Soda Chanh',
				'Soda Việt Quất',
				'Chanh Đá',
				'Nước Dừa Tươi',
				'Yakult Đào',
				'Yakult Chanh Leo'
			],
			5 => [ // Desserts
				'Pudding Caramel',
				'Pudding Trà Xanh',
				'Bánh Flan',
				'Kem Vanilla',
				'Kem Chocolate',
				'Kem Dâu',
				'Chè Thái',
				'Chè Đậu Đen',
				'Chè Bưởi',
				'Chè Khúc Bạch',
				'Sữa Chua Dẻo',
				'Thạch Rau Câu',
				'Caramen Phô Mai',
				'Panna Cotta',
				'Trái Cây Dầm',
				'Chè Trôi Nước',
				'Chè Ba Màu',
				'Chè Hạt Sen'
			]
		];

		$variants = ['Đặc Biệt', 'Premium', 'Homemade', 'Signature', 'Classic', 'Truyền Thống', 'Fusion'];
		$baseNames = $names[$productType];

		// 70% chance to get a base name, 30% chance to get a base name with variant
		if (rand(1, 10) <= 7) {
			return $baseNames[array_rand($baseNames)];
		} else {
			return $baseNames[array_rand($baseNames)] . ' ' . $variants[array_rand($variants)];
		}
	}

	/**
	 * Generate product description
	 */
	private function generateDescription(string $productName, int $productType): string
	{
		$descriptions = [
			1 => [ // Cakes
				"{name} mềm mịn với lớp kem tươi thơm ngon, thích hợp cho mọi dịp đặc biệt.",
				"{name} được làm từ nguyên liệu tươi ngon, mang đến hương vị tinh tế và độc đáo.",
				"{name} là sự kết hợp hoàn hảo giữa bột mì cao cấp và các loại trái cây tươi.",
				"{name} với lớp kem mịn màng cùng hương vị ngọt ngào, là lựa chọn tuyệt vời cho bữa tráng miệng.",
				"{name} nhẹ nhàng, thơm ngon, là món bánh được yêu thích nhất tại tiệm bánh của chúng tôi."
			],
			2 => [ // Pastries
				"{name} giòn rụm, thơm mùi bơ đặc trưng, là món ăn nhẹ hoàn hảo cho buổi sáng.",
				"{name} được nướng theo công thức truyền thống, giữ nguyên hương vị đặc trưng.",
				"{name} với lớp vỏ giòn tan cùng nhân ngọt ngào, là lựa chọn hoàn hảo cho bữa sáng.",
				"{name} mềm mịn bên trong, giòn tan bên ngoài, tạo nên sự kết hợp hương vị tuyệt vời.",
				"{name} được làm từ bơ Pháp cao cấp, mang đến hương vị béo ngậy khó cưỡng."
			],
			3 => [ // Coffee
				"{name} đậm đà, thơm nồng, là thức uống lý tưởng để khởi đầu ngày mới đầy năng lượng.",
				"{name} được pha từ hạt cà phê Arabica cao cấp, mang đến hương vị tinh tế và cân bằng.",
				"{name} với hương thơm quyến rũ và vị đắng thanh, là lựa chọn hoàn hảo cho người yêu cà phê.",
				"{name} là sự kết hợp hoàn hảo giữa espresso đậm đà và sữa mịn màng, tạo nên tách cà phê đúng điệu.",
				"{name} được chế biến từ những hạt cà phê được rang mới mỗi ngày, đảm bảo chất lượng tuyệt hảo."
			],
			4 => [ // Cold Drinks
				"{name} mát lạnh, thơm ngon với vị ngọt từ thiên nhiên, giải khát tức thì trong ngày hè.",
				"{name} được pha chế từ trái cây tươi 100%, giữ nguyên vị ngọt tự nhiên và vitamin.",
				"{name} thanh mát, chua ngọt hài hòa, là thức uống lý tưởng cho những ngày nắng nóng.",
				"{name} với hương vị tươi mát cùng topping phong phú, mang đến cảm giác sảng khoái tức thì.",
				"{name} được pha chế theo công thức độc quyền, tạo nên hương vị độc đáo khó quên."
			],
			5 => [ // Desserts
				"{name} mềm mịn, ngọt ngào, là lựa chọn hoàn hảo cho bữa tráng miệng.",
				"{name} được chế biến từ nguyên liệu tươi ngon, mang đến hương vị tinh tế và độc đáo.",
				"{name} với vị ngọt vừa phải cùng hương thơm quyến rũ, sẽ làm hài lòng mọi khẩu vị.",
				"{name} mát lạnh, thơm ngon, là món tráng miệng lý tưởng sau bữa ăn.",
				"{name} được làm theo công thức gia truyền, mang đến hương vị truyền thống khó quên."
			]
		];

		$details = [
			"Sản phẩm được chế biến từ nguyên liệu tươi ngon nhất mỗi ngày.",
			"Không sử dụng chất bảo quản, đảm bảo an toàn cho sức khỏe.",
			"Thích hợp cho mọi dịp, từ bữa ăn nhẹ đến tiệc tùng sang trọng.",
			"Được khách hàng đánh giá cao về chất lượng và hương vị.",
			"Có thể đặt trước để đảm bảo luôn có sản phẩm tươi ngon nhất.",
			"Đóng gói cẩn thận, giữ nguyên hương vị và hình dáng khi vận chuyển.",
			"Thời gian bảo quản tối ưu để đảm bảo độ tươi ngon của sản phẩm."
		];

		$baseDescriptions = $descriptions[$productType];
		$baseDescription = str_replace('{name}', $productName, $baseDescriptions[array_rand($baseDescriptions)]);

		// Add 2-3 random detail sentences
		$randomDetails = array_rand($details, rand(2, 3));
		if (!is_array($randomDetails)) {
			$randomDetails = [$randomDetails];
		}

		return $baseDescription . ' ' . implode(' ', array_map(fn($key) => $details[$key], $randomDetails));
	}
}
