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
            'name' => config('seeder.user.name'),
            'email' => config('seeder.user.email'),
            'password' => Hash::make((string) config('seeder.user.password')),
        ]);

        Project::factory()
            ->count(10)
            ->for($user)
            ->create();
    }
}
