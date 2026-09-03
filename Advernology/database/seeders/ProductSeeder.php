<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            [
                'name'         => '1 Email Account',
                'description'  => 'Migrate a single email address to our platform. Includes setup and support.',
                'price'        => 6.99,
                'emails_count' => 1,
                'sort_order'   => 1,
            ],
            [
                'name'         => 'Drop-back / 1D Package',
                'description'  => 'Single email with drop-back forwarding included. Perfect for business use.',
                'price'        => 9.99,
                'emails_count' => 1,
                'sort_order'   => 2,
            ],
            [
                'name'         => '6 Email Package',
                'description'  => 'Migrate up to 6 email addresses. Great for small families or small businesses.',
                'price'        => 29.99,
                'emails_count' => 6,
                'sort_order'   => 3,
            ],
            [
                'name'         => '25 Email Package',
                'description'  => 'Migrate up to 25 email addresses. Best value for organizations and businesses.',
                'price'        => 59.99,
                'emails_count' => 25,
                'sort_order'   => 4,
            ],
        ];

        foreach ($products as $product) {
            Product::firstOrCreate(['name' => $product['name']], array_merge($product, ['active' => true]));
        }
    }
}
