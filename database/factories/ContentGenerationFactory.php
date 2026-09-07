<?php

namespace Database\Factories;

use App\Models\ContentGeneration;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContentGeneration>
 */
class ContentGenerationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'status' => 'completed',
            'prompt' => fake()->paragraph(),
            'response' => [
                'suggested_title' => fake()->sentence(),
                'content_brief' => fake()->paragraph(),
                'outline' => [
                    ['heading' => fake()->sentence(), 'purpose' => fake()->sentence()],
                ],
                'key_points' => [fake()->sentence()],
                'production_tasks' => [fake()->sentence()],
                'risks_or_missing_information' => [fake()->sentence()],
            ],
            'model' => 'gpt-4o-mini',
            'input_tokens' => fake()->numberBetween(100, 500),
            'output_tokens' => fake()->numberBetween(100, 500),
            'error_code' => null,
        ];
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'response' => null,
            'model' => null,
            'input_tokens' => null,
            'output_tokens' => null,
            'error_code' => 'provider_error',
        ]);
    }
}
