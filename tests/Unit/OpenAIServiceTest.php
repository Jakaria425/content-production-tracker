<?php

use App\Enums\ProjectContentType;
use App\Models\Project;
use App\Models\User;
use App\Services\OpenAIService;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    config([
        'services.openai.api_key' => 'sk-test-NEVER-LEAK',
        'services.openai.model' => 'test-model-from-config',
    ]);

    Http::preventStrayRequests();
});

it('returns missing_configuration when the api key is empty', function (): void {
    config(['services.openai.api_key' => '']);
    $project = Project::factory()->create(['brief' => 'A brief.']);

    $result = app(OpenAIService::class)->generate($project);

    expect($result['status'])->toBe('failed')
        ->and($result['error_code'])->toBe('missing_configuration')
        ->and($result['prompt'])->toBe('');

    Http::assertNothingSent();
});

it('returns missing_configuration when the model is empty', function (): void {
    config(['services.openai.model' => '']);
    $project = Project::factory()->create(['brief' => 'A brief.']);

    $result = app(OpenAIService::class)->generate($project);

    expect($result['status'])->toBe('failed')
        ->and($result['error_code'])->toBe('missing_configuration');

    Http::assertNothingSent();
});

it('returns provider_error on a non-2xx response', function (): void {
    $project = Project::factory()->create(['brief' => 'A brief.']);
    Http::fake([
        'https://api.openai.com/v1/responses' => Http::response(['error' => 'boom'], 500),
    ]);

    $result = app(OpenAIService::class)->generate($project);

    expect($result['status'])->toBe('failed')
        ->and($result['error_code'])->toBe('provider_error')
        ->and($result['model'])->toBe('test-model-from-config')
        ->and($result['prompt'])->not->toBeEmpty();
});

it('returns provider_error on a connection failure', function (): void {
    $project = Project::factory()->create(['brief' => 'A brief.']);
    Http::fake([
        'https://api.openai.com/v1/responses' => Http::failedConnection('cURL error 60'),
    ]);

    $result = app(OpenAIService::class)->generate($project);

    expect($result['status'])->toBe('failed')
        ->and($result['error_code'])->toBe('provider_error');
});

it('returns invalid_response when output text is not valid json', function (): void {
    $project = Project::factory()->create(['brief' => 'A brief.']);
    Http::fake([
        'https://api.openai.com/v1/responses' => Http::response(
            ['output' => [['content' => [['type' => 'output_text', 'text' => 'not json']]]]],
            200,
        ),
    ]);

    $result = app(OpenAIService::class)->generate($project);

    expect($result['status'])->toBe('failed')
        ->and($result['error_code'])->toBe('invalid_response');
});

it('returns invalid_response when the json misses a required key', function (): void {
    $project = Project::factory()->create(['brief' => 'A brief.']);
    $broken = ['suggested_title' => 'X', 'content_brief' => 'Y', 'outline' => [], 'key_points' => [], 'production_tasks' => []];

    Http::fake([
        'https://api.openai.com/v1/responses' => Http::response(
            ['output' => [['content' => [['type' => 'output_text', 'text' => json_encode($broken)]]]]],
            200,
        ),
    ]);

    $result = app(OpenAIService::class)->generate($project);

    expect($result['status'])->toBe('failed')
        ->and($result['error_code'])->toBe('invalid_response');
});

it('returns completed with parsed data and token usage', function (): void {
    $project = Project::factory()->create(['brief' => 'A brief.']);
    $plan = [
        'suggested_title' => 'The Suggested Title',
        'content_brief' => 'The content brief.',
        'outline' => [['heading' => 'Intro', 'purpose' => 'Hook the reader']],
        'key_points' => ['Point one'],
        'production_tasks' => ['Draft the outline'],
        'risks_or_missing_information' => ['Missing audience data'],
    ];

    Http::fake([
        'https://api.openai.com/v1/responses' => Http::response([
            'output' => [
                ['content' => [['type' => 'output_text', 'text' => json_encode($plan)]]],
            ],
            'usage' => ['input_tokens' => 111, 'output_tokens' => 222],
        ], 200),
    ]);

    $result = app(OpenAIService::class)->generate($project);

    expect($result['status'])->toBe('completed')
        ->and($result['data'])->toBe($plan)
        ->and($result['model'])->toBe('test-model-from-config')
        ->and($result['input_tokens'])->toBe(111)
        ->and($result['output_tokens'])->toBe(222)
        ->and($result['error_code'])->toBeNull();
});

it('sends a strict json_schema format', function (): void {
    $project = Project::factory()->create(['brief' => 'A brief.']);
    Http::fake([
        'https://api.openai.com/v1/responses' => Http::response([
            'output' => [
                ['content' => [['type' => 'output_text', 'text' => json_encode([
                    'suggested_title' => 'T',
                    'content_brief' => 'B',
                    'outline' => [['heading' => 'H', 'purpose' => 'P']],
                    'key_points' => ['K'],
                    'production_tasks' => ['T'],
                    'risks_or_missing_information' => ['R'],
                ])]]],
            ],
        ], 200),
    ]);

    app(OpenAIService::class)->generate($project);

    Http::assertSent(fn ($request): bool => $request['text']['format']['type'] === 'json_schema'
        && $request['text']['format']['strict'] === true);
});

it('builds the prompt from allowed fields and excludes secrets', function (): void {
    $user = User::factory()->create([
        'email' => 'secret-user@example.test',
        'password' => 'super-secret-pw',
    ]);
    $project = Project::factory()->for($user)->create([
        'title' => 'Launch Newsletter',
        'content_type' => ProjectContentType::Newsletter,
        'brief' => 'Weekly product update',
        'notes' => 'Include screenshots',
    ]);
    Http::fake([
        'https://api.openai.com/v1/responses' => Http::response([
            'output' => [
                ['content' => [['type' => 'output_text', 'text' => json_encode([
                    'suggested_title' => 'T',
                    'content_brief' => 'B',
                    'outline' => [['heading' => 'H', 'purpose' => 'P']],
                    'key_points' => ['K'],
                    'production_tasks' => ['T'],
                    'risks_or_missing_information' => ['R'],
                ])]]],
            ],
        ], 200),
    ]);

    app(OpenAIService::class)->generate($project);

    Http::assertSent(function ($request) use ($project): bool {
        $prompt = $request['input'][0]['content'] ?? '';

        return str_contains($prompt, $project->title)
            && str_contains($prompt, $project->content_type->value)
            && str_contains($prompt, $project->brief)
            && str_contains($prompt, $project->notes)
            && ! str_contains($prompt, 'secret-user@example.test')
            && ! str_contains($prompt, 'super-secret-pw')
            && ! str_contains($prompt, 'sk-test-NEVER-LEAK');
    });
});
