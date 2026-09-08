<?php

use App\Enums\ProjectContentType;
use App\Models\ContentGeneration;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;

const TEST_API_KEY = 'sk-test-NEVER-LEAK';
const TEST_MODEL = 'test-model-from-config';
const SAFE_MESSAGE = 'The content plan could not be generated. Please try again later.';

beforeEach(function (): void {
    config([
        'services.openai.api_key' => TEST_API_KEY,
        'services.openai.model' => TEST_MODEL,
        'inertia.ssr.enabled' => false,
    ]);

    Http::preventStrayRequests();
});

/**
 * @return array<string, mixed>
 */
function contentPlanPayload(): array
{
    return [
        'suggested_title' => 'The Suggested Title',
        'content_brief' => 'The content brief.',
        'outline' => [
            ['heading' => 'Intro', 'purpose' => 'Hook the reader'],
        ],
        'key_points' => ['Point one'],
        'production_tasks' => ['Draft the outline'],
        'risks_or_missing_information' => ['Missing audience data'],
    ];
}

/**
 * @param  array<string, mixed>  $plan
 * @return array<string, mixed>
 */
function openAIResponsesStub(array $plan, ?array $usage = ['input_tokens' => 111, 'output_tokens' => 222]): array
{
    return [
        'output' => [
            ['content' => [['type' => 'output_text', 'text' => json_encode($plan)]]],
        ],
        'usage' => $usage,
    ];
}

function fakeSuccessfulGeneration(): void
{
    Http::fake([
        'https://api.openai.com/v1/responses' => Http::response(openAIResponsesStub(contentPlanPayload()), 200),
    ]);
}

test('a guest cannot generate a content plan', function (): void {
    $project = Project::factory()->create(['brief' => 'A brief.']);

    $this->post(route('projects.generations.store', $project))
        ->assertRedirect(route('login'));

    Http::assertNothingSent();
    expect(ContentGeneration::count())->toBe(0);
});

test('a user can generate a plan for their own project', function (): void {
    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create(['brief' => 'A brief.']);

    fakeSuccessfulGeneration();

    $this->actingAs($user)
        ->post(route('projects.generations.store', $project))
        ->assertRedirect();

    $this->assertDatabaseHas('content_generations', [
        'project_id' => $project->id,
        'status' => 'completed',
    ]);
});

test('a user cannot generate a plan for another user project', function (): void {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $project = Project::factory()->for($owner)->create(['brief' => 'A brief.']);

    $this->actingAs($intruder)
        ->post(route('projects.generations.store', $project))
        ->assertForbidden();

    Http::assertNothingSent();
    expect(ContentGeneration::count())->toBe(0);
});

test('invalid project input prevents the provider request', function (): void {
    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create(['brief' => null]);

    $this->actingAs($user)
        ->post(route('projects.generations.store', $project))
        ->assertRedirect()
        ->assertInertiaFlash('toast', ['type' => 'error', 'message' => SAFE_MESSAGE]);

    Http::assertNothingSent();
    expect(ContentGeneration::count())->toBe(0);
});

test('the outgoing request uses the configured model', function (): void {
    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create(['brief' => 'A brief.']);

    fakeSuccessfulGeneration();

    $this->actingAs($user)->post(route('projects.generations.store', $project));

    Http::assertSent(fn ($request): bool => $request->url() === 'https://api.openai.com/v1/responses'
        && $request['model'] === TEST_MODEL);
});

test('the outgoing prompt includes the allowed project data', function (): void {
    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create([
        'title' => 'Launch Newsletter',
        'content_type' => ProjectContentType::Newsletter,
        'brief' => 'Weekly product update',
        'notes' => 'Include screenshots',
    ]);

    fakeSuccessfulGeneration();

    $this->actingAs($user)->post(route('projects.generations.store', $project));

    Http::assertSent(function ($request) use ($project): bool {
        $prompt = $request['input'][0]['content'] ?? '';

        return str_contains($prompt, $project->title)
            && str_contains($prompt, $project->content_type->value)
            && str_contains($prompt, $project->brief)
            && str_contains($prompt, $project->notes);
    });
});

test('the outgoing prompt excludes unrelated user data and secrets', function (): void {
    $user = User::factory()->create([
        'email' => 'secret-user@example.test',
        'password' => 'super-secret-pw',
    ]);
    $project = Project::factory()->for($user)->create(['brief' => 'A brief.']);

    fakeSuccessfulGeneration();

    $this->actingAs($user)->post(route('projects.generations.store', $project));

    Http::assertSent(function ($request): bool {
        $prompt = $request['input'][0]['content'] ?? '';

        return ! str_contains($prompt, 'secret-user@example.test')
            && ! str_contains($prompt, 'super-secret-pw')
            && ! str_contains($prompt, TEST_API_KEY);
    });
});

test('a successful structured response is validated and saved', function (): void {
    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create(['brief' => 'A brief.']);
    $plan = contentPlanPayload();

    Http::fake([
        'https://api.openai.com/v1/responses' => Http::response(openAIResponsesStub($plan), 200),
    ]);

    $this->actingAs($user)->post(route('projects.generations.store', $project));

    $generation = ContentGeneration::where('project_id', $project->id)->firstOrFail();

        expect($generation->status)->toBe('completed')
        ->and($generation->response)->toEqual($plan);
});

test('token usage is saved when present', function (): void {
    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create(['brief' => 'A brief.']);

    fakeSuccessfulGeneration();

    $this->actingAs($user)->post(route('projects.generations.store', $project));

    $this->assertDatabaseHas('content_generations', [
        'project_id' => $project->id,
        'input_tokens' => 111,
        'output_tokens' => 222,
    ]);
});

test('a provider http failure saves a failed generation and shows a safe message', function (): void {
    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create(['brief' => 'A brief.']);

    Http::fake([
        'https://api.openai.com/v1/responses' => Http::response(
            ['error' => ['message' => 'INSUFFICIENT_CREDIT_SECRET']],
            429,
        ),
    ]);

    $this->actingAs($user)
        ->post(route('projects.generations.store', $project))
        ->assertRedirect()
        ->assertInertiaFlash('toast', ['type' => 'error', 'message' => SAFE_MESSAGE]);

    $this->assertDatabaseHas('content_generations', [
        'project_id' => $project->id,
        'status' => 'failed',
        'error_code' => 'provider_error',
    ]);
});

test('malformed structured output saves a failed generation and shows a safe message', function (): void {
    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create(['brief' => 'A brief.']);

    $broken = ['suggested_title' => 'X', 'content_brief' => 'Y', 'outline' => [], 'key_points' => [], 'production_tasks' => []];

    Http::fake([
        'https://api.openai.com/v1/responses' => Http::response(
            ['output' => [['content' => [['type' => 'output_text', 'text' => json_encode($broken)]]]]],
            200,
        ),
    ]);

    $this->actingAs($user)
        ->post(route('projects.generations.store', $project))
        ->assertRedirect()
        ->assertInertiaFlash('toast', ['type' => 'error', 'message' => SAFE_MESSAGE]);

    $this->assertDatabaseHas('content_generations', [
        'project_id' => $project->id,
        'status' => 'failed',
        'error_code' => 'invalid_response',
    ]);
});

test('the api key and provider error body do not appear in the browser response', function (): void {
    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create(['brief' => 'A brief.']);

    Http::fake([
        'https://api.openai.com/v1/responses' => Http::response(
            ['error' => ['message' => 'INSUFFICIENT_CREDIT_SECRET']],
            429,
        ),
    ]);

    $response = $this->actingAs($user)->post(route('projects.generations.store', $project));

    $response->assertRedirect();

    $content = $response->getContent();
    expect($content)->not->toContain(TEST_API_KEY)
        ->and($content)->not->toContain('INSUFFICIENT_CREDIT_SECRET');

    $response->assertInertiaFlash('toast', ['type' => 'error', 'message' => SAFE_MESSAGE]);

    $generation = ContentGeneration::where('project_id', $project->id)->firstOrFail();
    expect($generation->prompt)->not->toContain(TEST_API_KEY);
});

test('a project with a completed generation reports has_content_plan true', function (): void {
    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create(['brief' => 'A brief.']);
    ContentGeneration::factory()->for($project)->create();

    $this->actingAs($user)
        ->get(route('projects.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Projects')
            ->where('projects.0.has_content_plan', true),
        );
});

test('a project with only a failed generation reports has_content_plan false', function (): void {
    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create(['brief' => 'A brief.']);
    ContentGeneration::factory()->for($project)->failed()->create();

    $this->actingAs($user)
        ->get(route('projects.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Projects')
            ->where('projects.0.has_content_plan', false),
        );
});
