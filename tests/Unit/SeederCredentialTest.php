<?php

use Illuminate\Support\Facades\File;

test('seeder credentials come from environment via config, not hardcoded', function (): void {
    $configContent = File::get(config_path('seeder.php'));

    expect(str_contains($configContent, "env('SEEDER_USER_NAME'"))->toBeTrue();
    expect(str_contains($configContent, "env('SEEDER_USER_EMAIL'"))->toBeTrue();
    expect(str_contains($configContent, "env('SEEDER_USER_PASSWORD'"))->toBeTrue();

    $seederContent = File::get(database_path('seeders/DatabaseSeeder.php'));

    expect(str_contains($seederContent, "config('seeder.user.name')"))->toBeTrue();
    expect(str_contains($seederContent, "config('seeder.user.email')"))->toBeTrue();
    expect(str_contains($seederContent, "config('seeder.user.password')"))->toBeTrue();
});
