<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Admin User
        \App\Models\User::firstOrCreate(
            ['username' => 'raj_maurya_7878'],
            [
                'email' => 'admin@aapkapainter.clone',
                'password' => \Illuminate\Support\Facades\Hash::make('208001@@Raj'),
                'isAdmin' => true,
            ]
        );

        // Seed some services
        $services = [
            [
                'name' => 'Interior Painting',
                'description' => 'Transform your indoor spaces with premium interior painting.',
                'image' => '/images/interior.webp',
                'category' => 'Interior'
            ],
            [
                'name' => 'Exterior Painting',
                'description' => 'Protect and beautify your home exterior.',
                'image' => '/images/exterior.jpg',
                'category' => 'Exterior'
            ],
            [
                'name' => 'Waterproofing',
                'description' => 'Advanced waterproofing solutions for a leak-free home.',
                'image' => '/images/waterproofing-banner.avif',
                'category' => 'Waterproofing'
            ],
            [
                'name' => 'Wood Finishes',
                'description' => 'Enhance the natural beauty of your wooden furniture and doors.',
                'image' => '/images/wood-finishes-banner.avif',
                'category' => 'Wood'
            ]
        ];

        foreach ($services as $service) {
            \App\Models\Service::firstOrCreate(['name' => $service['name']], $service);
        }
    }
}
