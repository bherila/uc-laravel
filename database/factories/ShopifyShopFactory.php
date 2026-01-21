<?php

namespace Database\Factories;

use App\Models\ShopifyShop;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ShopifyShop>
 */
class ShopifyShopFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = ShopifyShop::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->company();
        $slug = str_replace(' ', '-', strtolower($name));

        return [
            'name' => $name,
            'shop_domain' => $slug . '-' . fake()->unique()->randomNumber(5) . '.myshopify.com',
            'app_name' => 'Test App',
            'admin_api_token' => 'shpat_' . fake()->sha256(),
            'api_version' => '2025-01',
            'api_key' => fake()->sha256(),
            'api_secret_key' => fake()->sha256(),
            'webhook_version' => '2025-01',
            'webhook_secret' => fake()->sha256(),
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the shop is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
