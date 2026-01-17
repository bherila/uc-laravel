<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'alias' => 'Admin User',
                'password' => Hash::make('password'),
                'ax_maxmin' => true,
                'ax_homes' => true,
                'ax_tax' => true,
                'ax_evdb' => true,
                'ax_spgp' => true,
                'ax_uc' => true,
            ]
        );
    }
}