<?php

namespace Database\Seeders;

use App\Models\Project;
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
        // User::factory(10)->create();

        $user = User::factory()->create([
            'name' => env('SEEDER_USER_NAME'),
            'email' => env('SEEDER_USER_EMAIL'),
            'password' => Hash::make(env('SEEDER_USER_PASSWORD')),
        ]);

        Project::factory()
            ->count(10)
            ->for($user)
            ->create();
    }
}
