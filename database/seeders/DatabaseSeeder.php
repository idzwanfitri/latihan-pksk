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
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        User::factory()->admin()->create([
            'name' => 'Pentadbir',
            'email' => 'admin@example.com',
        ]);

        User::factory()->pelajar()->create([
            'name' => 'Pelajar Ujian',
            'email' => 'pelajar@example.com',
        ]);

        $this->call([
            QuestionSeeder::class,
        ]);
    }
}
