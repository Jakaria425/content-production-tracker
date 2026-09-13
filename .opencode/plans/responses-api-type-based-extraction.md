# Plan: Type-based output extraction for the OpenAI Responses API

## Problem

`app/Services/OpenAIService.php:75` reads `$response->json('output.0.content.0.text')` (fixed position). When the Responses API emits a `reasoning` item before the assistant `message` (reasoning models), `output[0]` has no `content`, so extraction fails and the app saves `failed`/`invalid_response` despite a valid plan at `output[1]`.

## Changes

### 1. `app/Services/OpenAIService.php`

#### Replace the fixed-path extraction (lines 75-87)

Old:

```php
$outputText = $response->json('output.0.content.0.text');

if (! is_string($outputText) || empty($outputText)) {
    return [ /* failed, invalid_response */ ];
}
```

New:

```php
$status = $response->json('status');

if (is_string($status) && $status !== 'completed') {
    return [
        'status' => 'failed',
        'prompt' => $prompt,
        'data' => null,
        'model' => $model,
        'input_tokens' => null,
        'output_tokens' => null,
        'error_code' => 'invalid_response',
    ];
}

$outputText = $this->extractOutputText($response->json('output'));

if ($outputText === null) {
    return [
        'status' => 'failed',
        'prompt' => $prompt,
        'data' => null,
        'model' => $model,
        'input_tokens' => null,
        'output_tokens' => null,
        'error_code' => 'invalid_response',
    ];
}
```

#### Add private helper after `schema()`

```php
/**
 * Locate the assistant output text by item type, skipping reasoning items.
 *
 * @param  array<int, mixed>|null  $output
 */
private function extractOutputText(mixed $output): ?string
{
    if (! is_array($output)) {
        return null;
    }

    foreach ($output as $item) {
        if (! is_array($item) || ($item['type'] ?? null) !== 'message') {
            continue;
        }

        $content = $item['content'] ?? null;

        if (! is_array($content)) {
            continue;
        }

        $text = '';

        foreach ($content as $contentItem) {
            if (! is_array($contentItem) || ($contentItem['type'] ?? null) !== 'output_text') {
                continue;
            }

            $contentItemText = $contentItem['text'] ?? null;

            if (is_string($contentItemText) && $contentItemText !== '') {
                $text .= $contentItemText;
            }
        }

        if ($text !== '') {
            return $text;
        }
    }

    return null;
}
```

Failure-mode handling:

- **Refusal** (message content is `{"type": "refusal"}`): no `output_text` found -> `invalid_response`, refusal text never stored or surfaced
- **Incomplete** (response-level `status` not `completed`, HTTP 200): explicit check -> `invalid_response`; lenient when `status` absent
- **Absent** (no `message` item, empty/non-array output): extraction returns null -> `invalid_response`
- Non-`message` item types (`reasoning`, `web_search_call`, `function_call`, ...) skipped generically
- Multiple `output_text` parts within one message are concatenated (annotation-split text)

### 2. `tests/Feature/ContentGenerationTest.php`

#### Make `openAIResponsesStub()` realistic (lines 45-53)

```php
function openAIResponsesStub(array $plan, ?array $usage = ['input_tokens' => 111, 'output_tokens' => 222]): array
{
    return [
        'id' => 'resp_test',
        'object' => 'response',
        'status' => 'completed',
        'output' => [
            [
                'type' => 'reasoning',
                'id' => 'rs_test',
                'summary' => [],
            ],
            [
                'type' => 'message',
                'id' => 'msg_test',
                'role' => 'assistant',
                'status' => 'completed',
                'content' => [
                    ['type' => 'output_text', 'text' => json_encode($plan), 'annotations' => []],
                ],
            ],
        ],
        'usage' => $usage,
    ];
}
```

All existing success-path tests and the 31-case malformed dataset then exercise reasoning-first extraction implicitly. Signature unchanged; callers unaffected.

#### Add regression test after 'a successful structured response is validated and saved' (line 184)

```php
test('a response with a reasoning item before the assistant message saves the validated plan and usage', function (): void {
    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create(['brief' => 'A brief.']);
    $plan = contentPlanPayload();

    Http::fake([
        'https://api.openai.com/v1/responses' => Http::response([
            'id' => 'resp_regression',
            'object' => 'response',
            'status' => 'completed',
            'output' => [
                [
                    'type' => 'reasoning',
                    'id' => 'rs_regression',
                    'summary' => [['type' => 'summary_text', 'text' => 'Thought about the project brief.']],
                ],
                [
                    'type' => 'message',
                    'id' => 'msg_regression',
                    'role' => 'assistant',
                    'status' => 'completed',
                    'content' => [
                        ['type' => 'output_text', 'text' => json_encode($plan), 'annotations' => []],
                    ],
                ],
            ],
            'usage' => ['input_tokens' => 111, 'output_tokens' => 222],
        ], 200),
    ]);

    $this->actingAs($user)->post(route('projects.generations.store', $project));

    $generation = ContentGeneration::where('project_id', $project->id)->firstOrFail();

    expect($generation->status)->toBe('completed')
        ->and($generation->response)->toEqual($plan)
        ->and($generation->input_tokens)->toBe(111)
        ->and($generation->output_tokens)->toBe(222)
        ->and($generation->error_code)->toBeNull();
});
```

### 3. `tests/Unit/OpenAIServiceTest.php`

#### Add local realistic helper after `beforeEach` (differently named from the feature file to avoid redeclare)

```php
/**
 * @param  array<string, mixed>|null  $usage
 * @return array<string, mixed>
 */
function responsesApiTextStub(string $text, ?array $usage = ['input_tokens' => 111, 'output_tokens' => 222]): array
{
    return [
        'id' => 'resp_unit',
        'object' => 'response',
        'status' => 'completed',
        'output' => [
            [
                'type' => 'reasoning',
                'id' => 'rs_unit',
                'summary' => [],
            ],
            [
                'type' => 'message',
                'id' => 'msg_unit',
                'role' => 'assistant',
                'status' => 'completed',
                'content' => [
                    ['type' => 'output_text', 'text' => $text, 'annotations' => []],
                ],
            ],
        ],
        'usage' => $usage,
    ];
}
```

#### Update these tests to use the helper (their untyped output items would otherwise fail extraction before reaching the path they claim to test)

- `returns invalid_response when output text is not valid json` -> `responsesApiTextStub('not json')`
- `returns invalid_response when the json misses a required key` -> `responsesApiTextStub(json_encode($broken))`
- data-driven `returns invalid_response for malformed schema: :key` (9 cases) -> `responsesApiTextStub(json_encode($plan))`
- `returns completed with parsed data and token usage` -> `responsesApiTextStub(json_encode($plan))` (default usage 111/222 matches assertions)
- `sends a strict json_schema format` -> helper with valid plan text
- `builds the prompt from allowed fields and excludes secrets` -> helper with valid plan text

Unchanged: `missing_configuration` tests (no stub), `provider_error` tests (error bodies, no output).

#### Add 3 failure-mode tests (place after the data-driven test)

```php
it('returns invalid_response when the output has no assistant message', function (): void {
    $project = Project::factory()->create(['brief' => 'A brief.']);

    Http::fake([
        'https://api.openai.com/v1/responses' => Http::response([
            'id' => 'resp_unit',
            'object' => 'response',
            'status' => 'completed',
            'output' => [
                ['type' => 'reasoning', 'id' => 'rs_unit', 'summary' => []],
            ],
            'usage' => ['input_tokens' => 111, 'output_tokens' => 222],
        ], 200),
    ]);

    $result = app(OpenAIService::class)->generate($project);

    expect($result['status'])->toBe('failed')
        ->and($result['error_code'])->toBe('invalid_response')
        ->and($result['data'])->toBeNull();
});

it('returns invalid_response when the assistant message contains a refusal', function (): void {
    $project = Project::factory()->create(['brief' => 'A brief.']);

    Http::fake([
        'https://api.openai.com/v1/responses' => Http::response([
            'id' => 'resp_unit',
            'object' => 'response',
            'status' => 'completed',
            'output' => [
                ['type' => 'reasoning', 'id' => 'rs_unit', 'summary' => []],
                [
                    'type' => 'message',
                    'id' => 'msg_unit',
                    'role' => 'assistant',
                    'status' => 'completed',
                    'content' => [
                        ['type' => 'refusal', 'refusal' => 'I cannot help with that request.'],
                    ],
                ],
            ],
            'usage' => ['input_tokens' => 111, 'output_tokens' => 222],
        ], 200),
    ]);

    $result = app(OpenAIService::class)->generate($project);

    expect($result['status'])->toBe('failed')
        ->and($result['error_code'])->toBe('invalid_response')
        ->and($result['data'])->toBeNull();
});

it('returns invalid_response when the response status is incomplete', function (): void {
    $project = Project::factory()->create(['brief' => 'A brief.']);

    Http::fake([
        'https://api.openai.com/v1/responses' => Http::response([
            'id' => 'resp_unit',
            'object' => 'response',
            'status' => 'incomplete',
            'incomplete_details' => ['reason' => 'max_output_tokens'],
            'output' => [
                ['type' => 'reasoning', 'id' => 'rs_unit', 'summary' => []],
                [
                    'type' => 'message',
                    'id' => 'msg_unit',
                    'role' => 'assistant',
                    'status' => 'incomplete',
                    'content' => [
                        ['type' => 'output_text', 'text' => '{"suggested_title":"tru', 'annotations' => []],
                    ],
                ],
            ],
            'usage' => ['input_tokens' => 111, 'output_tokens' => 222],
        ], 200),
    ]);

    $result = app(OpenAIService::class)->generate($project);

    expect($result['status'])->toBe('failed')
        ->and($result['error_code'])->toBe('invalid_response')
        ->and($result['data'])->toBeNull();
});
```

Stray HTTP prevention: both files already call `Http::preventStrayRequests()` in `beforeEach` — all new tests inherit it.

## Expected test counts after

- Feature `ContentGenerationTest`: 44 + 1 regression = 45
- Unit `OpenAIServiceTest`: 18 + 3 failure modes = 21

## Verification

```bash
php artisan test --filter="ContentGenerationTest" --compact
php artisan test --filter="OpenAIServiceTest" --compact
php artisan test --compact
vendor/bin/pint --dirty --format agent
```

## Notes

- Usage extraction (`usage.input_tokens` / `usage.output_tokens`) stays as-is — top-level, position-stable even on reasoning models (`output_tokens_details.reasoning_tokens` is nested deeper).
- The `insufficient_quota` plan from the earlier discussion remains separate and unimplemented.
