<?php

use Illuminate\Support\Facades\File;

test('seeder contains no hardcoded production credentials', function (): void {
    $seederPath = database_path('seeders/DatabaseSeeder.php');
    $content = File::get($seederPath);

    expect(str_contains($content, "env('SEEDER_USER_EMAIL'"))->toBeTrue();
    expect(str_contains($content, "env('SEEDER_USER_PASSWORD'"))->toBeTrue();
});
