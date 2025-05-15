<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            // Cake categories (type 1)
            [
                'category_name' => 'Bánh sinh nhật',
                'category_type' => 1,
                'category_background_url' => '/images/categories/birthday-cake.jpg',
                'category_description' => 'Các loại bánh sinh nhật với nhiều kiểu trang trí và hương vị khác nhau.'
            ],
            [
                'category_name' => 'Bánh mousse',
                'category_type' => 1,
                'category_background_url' => '/images/categories/mousse-cake.jpg',
                'category_description' => 'Bánh mousse mềm mịn với nhiều lớp hương vị khác nhau.'
            ],
            [
                'category_name' => 'Bánh tiramisu',
                'category_type' => 1,
                'category_background_url' => '/images/categories/tiramisu.jpg',
                'category_description' => 'Bánh tiramisu với lớp kem mascarpone mềm mịn và vị cà phê đặc trưng.'
            ],
            [
                'category_name' => 'Bánh kem',
                'category_type' => 1,
                'category_background_url' => '/images/categories/cream-cake.jpg',
                'category_description' => 'Bánh kem mềm mịn với lớp kem tươi bên trên.'
            ],
            [
                'category_name' => 'Bánh cheese',
                'category_type' => 1,
                'category_background_url' => '/images/categories/cheesecake.jpg',
                'category_description' => 'Bánh cheese với vị béo ngậy đặc trưng của phô mai.'
            ],

            // Pastry categories (type 2)
            [
                'category_name' => 'Bánh mì',
                'category_type' => 2,
                'category_background_url' => '/images/categories/bread.jpg',
                'category_description' => 'Các loại bánh mì mềm, thơm, nướng mới mỗi ngày.'
            ],
            [
                'category_name' => 'Bánh ngọt',
                'category_type' => 2,
                'category_background_url' => '/images/categories/pastry.jpg',
                'category_description' => 'Các loại bánh ngọt với nhiều hương vị và kiểu dáng khác nhau.'
            ],
            [
                'category_name' => 'Bánh croissant',
                'category_type' => 2,
                'category_background_url' => '/images/categories/croissant.jpg',
                'category_description' => 'Bánh croissant giòn, xốp với hương vị bơ đặc trưng.'
            ],
            [
                'category_name' => 'Bánh cookies',
                'category_type' => 2,
                'category_background_url' => '/images/categories/cookies.jpg',
                'category_description' => 'Bánh cookies giòn tan với nhiều hương vị đa dạng.'
            ],

            // Coffee categories (type 3)
            [
                'category_name' => 'Cà phê Việt Nam',
                'category_type' => 3,
                'category_background_url' => '/images/categories/vietnamese-coffee.jpg',
                'category_description' => 'Cà phê Việt Nam đậm đà, thơm ngon theo phong cách truyền thống.'
            ],
            [
                'category_name' => 'Cà phê Ý',
                'category_type' => 3,
                'category_background_url' => '/images/categories/italian-coffee.jpg',
                'category_description' => 'Các loại cà phê theo phong cách Ý như Espresso, Cappuccino, Latte.'
            ],
            [
                'category_name' => 'Cà phê đặc biệt',
                'category_type' => 3,
                'category_background_url' => '/images/categories/special-coffee.jpg',
                'category_description' => 'Các loại cà phê đặc biệt với công thức độc đáo.'
            ],

            // Cold drink categories (type 4)
            [
                'category_name' => 'Trà trái cây',
                'category_type' => 4,
                'category_background_url' => '/images/categories/fruit-tea.jpg',
                'category_description' => 'Trà trái cây tươi mát, thanh nhiệt với nhiều hương vị.'
            ],
            [
                'category_name' => 'Sinh tố',
                'category_type' => 4,
                'category_background_url' => '/images/categories/smoothie.jpg',
                'category_description' => 'Sinh tố từ trái cây tươi, bổ dưỡng và thơm ngon.'
            ],
            [
                'category_name' => 'Đá xay',
                'category_type' => 4,
                'category_background_url' => '/images/categories/frappe.jpg',
                'category_description' => 'Đồ uống đá xay mát lạnh với nhiều hương vị khác nhau.'
            ],
            [
                'category_name' => 'Nước ép',
                'category_type' => 4,
                'category_background_url' => '/images/categories/juice.jpg',
                'category_description' => 'Nước ép trái cây tươi nguyên chất, giàu vitamin.'
            ],

            // Dessert categories (type 5)
            [
                'category_name' => 'Pudding',
                'category_type' => 5,
                'category_background_url' => '/images/categories/pudding.jpg',
                'category_description' => 'Pudding mềm mịn với nhiều hương vị hấp dẫn.'
            ],
            [
                'category_name' => 'Kem',
                'category_type' => 5,
                'category_background_url' => '/images/categories/ice-cream.jpg',
                'category_description' => 'Kem tươi mát với nhiều hương vị đặc sắc.'
            ],
            [
                'category_name' => 'Chè',
                'category_type' => 5,
                'category_background_url' => '/images/categories/che.jpg',
                'category_description' => 'Các loại chè truyền thống và hiện đại, giải nhiệt tuyệt vời.'
            ],
        ];

        foreach ($categories as $category) {
            Category::firstOrCreate(
                ['category_name' => $category['category_name']],
                $category
            );
        }

        $this->command->info('Categories seeded successfully!');
    }
}