<?php

namespace App\Services;

use App\Models\Project;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class OpenAIService
{
    /**
     * @return array<string, mixed>
     */
    public function generate(Project $project): array
    {
        $apiKey = config('services.openai.api_key');
        $model = config('services.openai.model');
        $timeout = (int) config('services.openai.timeout', 30);

        if (empty($apiKey) || empty($model)) {
            return [
                'status' => 'failed',
                'prompt' => '',
                'data' => null,
                'model' => null,
                'input_tokens' => null,
                'output_tokens' => null,
                'error_code' => 'missing_configuration',
            ];
        }

        $prompt = $this->buildPrompt($project);

        try {
            $response = Http::withToken($apiKey)
                ->timeout($timeout)
                ->post('https://api.openai.com/v1/responses', [
                    'model' => $model,
                    'input' => [
                        ['role' => 'user', 'content' => $prompt],
                    ],
                    'text' => [
                        'format' => [
                            'type' => 'json_schema',
                            'name' => 'content_plan',
                            'schema' => $this->schema(),
                            'strict' => true,
                        ],
                    ],
                ]);
        } catch (ConnectionException) {
            return [
                'status' => 'failed',
                'prompt' => $prompt,
                'data' => null,
                'model' => $model,
                'input_tokens' => null,
                'output_tokens' => null,
                'error_code' => 'provider_error',
            ];
        }

        if (! $response->successful()) {
            return [
                'status' => 'failed',
                'prompt' => $prompt,
                'data' => null,
                'model' => $model,
                'input_tokens' => null,
                'output_tokens' => null,
                'error_code' => 'provider_error',
            ];
        }

        $outputText = $response->json('output.0.content.0.text');

        if (! is_string($outputText) || empty($outputText)) {
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

        if (! $this->validateResponse($outputText)) {
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

        $data = json_decode($outputText, true);

        return [
            'status' => 'completed',
            'prompt' => $prompt,
            'data' => $data,
            'model' => $model,
            'input_tokens' => $response->json('usage.input_tokens'),
            'output_tokens' => $response->json('usage.output_tokens'),
            'error_code' => null,
        ];
    }

    private function buildPrompt(Project $project): string
    {
        $parts = [
            'Create a practical content-production plan for the following project.',
            "Title: {$project->title}",
            "Content type: {$project->content_type->value}",
            "Brief: {$project->brief}",
        ];

        if (! empty($project->notes)) {
            $parts[] = "Notes: {$project->notes}";
        }

        $parts[] = 'Do not invent missing business facts. Put uncertainties in risks_or_missing_information. Respond strictly using the requested JSON schema.';

        return implode("\n\n", $parts);
    }

    /**
     * @return array<string, mixed>
     */
    private function schema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'suggested_title' => ['type' => 'string'],
                'content_brief' => ['type' => 'string'],
                'outline' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'heading' => ['type' => 'string'],
                            'purpose' => ['type' => 'string'],
                        ],
                        'required' => ['heading', 'purpose'],
                        'additionalProperties' => false,
                    ],
                ],
                'key_points' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                ],
                'production_tasks' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                ],
                'risks_or_missing_information' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                ],
            ],
            'required' => [
                'suggested_title',
                'content_brief',
                'outline',
                'key_points',
                'production_tasks',
                'risks_or_missing_information',
            ],
            'additionalProperties' => false,
        ];
    }

    private function validateResponse(string $json): bool
    {
        $decoded = json_decode($json);

        if (! is_object($decoded)) {
            return false;
        }

        $allowed = [
            'suggested_title',
            'content_brief',
            'outline',
            'key_points',
            'production_tasks',
            'risks_or_missing_information',
        ];

        $actual = array_keys(get_object_vars($decoded));
        if (array_diff($actual, $allowed) !== []) {
            return false;
        }

        if (! is_string($decoded->suggested_title ?? null) || $decoded->suggested_title === '') {
            return false;
        }

        if (! is_string($decoded->content_brief ?? null) || $decoded->content_brief === '') {
            return false;
        }

        if (! $this->validateOutline($decoded->outline ?? null)) {
            return false;
        }

        foreach (['key_points', 'production_tasks', 'risks_or_missing_information'] as $field) {
            if (! $this->validateStringList($decoded->$field ?? null)) {
                return false;
            }
        }

        return true;
    }

    private function validateOutline(mixed $value): bool
    {
        if (! is_array($value)) {
            return false;
        }

        foreach ($value as $item) {
            if (! is_object($item)) {
                return false;
            }

            if (array_diff(array_keys(get_object_vars($item)), ['heading', 'purpose']) !== []) {
                return false;
            }

            if (! is_string($item->heading ?? null) || $item->heading === '') {
                return false;
            }

            if (! is_string($item->purpose ?? null) || $item->purpose === '') {
                return false;
            }
        }

        return true;
    }

    private function validateStringList(mixed $value): bool
    {
        if (! is_array($value)) {
            return false;
        }

        foreach ($value as $item) {
            if (! is_string($item) || $item === '') {
                return false;
            }
        }

        return true;
    }
}
