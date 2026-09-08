<?php

use Illuminate\Support\Facades\File;

test('openai credentials come from environment via config, not hardcoded', function (): void {
    $envExample = File::get(base_path('.env.example'));

    expect($envExample)->toContain('OPENAI_API_KEY=');
    expect($envExample)->toContain('OPENAI_MODEL=');

    // Ensure .env.example does not contain a real-looking OpenAI API key.
    expect(preg_match('/sk-[a-zA-Z0-9_-]{10,}/', $envExample))->toBe(0);
});

test('services config reads openai values from environment', function (): void {
    $servicesContent = File::get(config_path('services.php'));

    expect(str_contains($servicesContent, "env('OPENAI_API_KEY')"))->toBeTrue();
    expect(str_contains($servicesContent, "env('OPENAI_MODEL')"))->toBeTrue();
});

test('application code does not call env for openai credentials', function (): void {
    $openaiService = app_path('Services/OpenAIService.php');

    if (! File::exists($openaiService)) {
        $this->markTestSkipped('OpenAIService has not been created yet.');
    }

    $serviceContent = File::get($openaiService);

    expect(str_contains($serviceContent, "env('OPENAI_API_KEY')"))->toBeFalse();
    expect(str_contains($serviceContent, "env('OPENAI_MODEL')"))->toBeFalse();
    expect(str_contains($serviceContent, "config('services.openai.api_key')"))->toBeTrue();
    expect(str_contains($serviceContent, "config('services.openai.model')"))->toBeTrue();
});
