<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $contentTypes = ['Ebook', 'Blog post', 'Newsletter', 'Social post'];
        $statuses = ['Draft', 'In progress', 'Review', 'Complete'];

        return [
            'user_id' => User::factory(),
            'title' => fake()->sentence(4),
            'content_type' => fake()->randomElement($contentTypes),
            'status' => fake()->randomElement($statuses),
            'due_date' => fake()->optional()->date(),
            'brief' => fake()->optional()->paragraph(),
            'notes' => fake()->optional()->paragraph(),
        ];
    }
}
