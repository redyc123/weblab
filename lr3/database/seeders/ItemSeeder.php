<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Item;
use App\Models\User;

class ItemSeeder extends Seeder
{
    public function run()
    {
        $users = User::all();
        if ($users->count() == 0) {
            // If no users exist, create a default user
            $user = User::factory()->create([
                'name' => 'Default User',
                'email' => 'default@example.com',
                'password' => bcrypt('password'),
                'username' => 'defaultuser'
            ]);
            $users = collect([$user]);
        }

        $items = [
            [
                'title' => 'Объект 1',
                'description' => 'Описание объекта 1',
                'price' => 100,
                'released_at' => '2020-01-10',
                'category' => 'A',
            ],
            [
                'title' => 'Объект 2',
                'description' => 'Описание объекта 2',
                'price' => 200,
                'released_at' => '2021-05-20',
                'category' => 'B',
            ],
            [
                'title' => 'Объект 3',
                'description' => 'Описание объекта 3',
                'price' => 300,
                'released_at' => '2022-08-15',
                'category' => 'C',
            ],
            [
                'title' => 'Объект 4',
                'description' => 'Описание объекта 4',
                'price' => 400,
                'released_at' => '2023-03-03',
                'category' => 'A',
            ],
            [
                'title' => 'Объект 5',
                'description' => 'Описание объекта 5',
                'price' => 500,
                'released_at' => '2024-11-01',
                'category' => 'B',
            ],
        ];

        foreach ($items as $index => $data) {
            $user = $users->random(); // Assign randomly to any user
            $data['user_id'] = $user->id;
            Item::create($data);
        }
    }
}
