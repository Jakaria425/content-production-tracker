<?php

use App\Models\ContentGeneration;
use App\Models\Project;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function (): void {
    config(['inertia.ssr.enabled' => false]);
});

it('passes the latest completed plan to the projects page', function (): void {
    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create(['brief' => 'A brief.']);
    $plan = [
        'suggested_title' => 'Suggested Title',
        'content_brief' => 'Content Brief',
        'outline' => [['heading' => 'Outline Heading', 'purpose' => 'Outline Purpose']],
        'key_points' => ['Key Point'],
        'production_tasks' => ['Production Task'],
        'risks_or_missing_information' => ['Risk'],
    ];

    ContentGeneration::factory()->for($project)->create([
        'status' => 'completed',
        'response' => $plan,
    ]);

    $this->actingAs($user)
        ->get(route('projects.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Projects')
            ->where('latestGeneration.project_id', $project->id)
            ->where('latestGeneration.project_title', $project->title)
            ->where('latestGeneration.suggested_title', 'Suggested Title')
            ->where('latestGeneration.content_brief', 'Content Brief')
            ->has('latestGeneration.outline', 1)
            ->where('latestGeneration.outline.0.heading', 'Outline Heading')
            ->where('latestGeneration.outline.0.purpose', 'Outline Purpose')
            ->has('latestGeneration.key_points', 1)
            ->where('latestGeneration.key_points.0', 'Key Point')
            ->has('latestGeneration.production_tasks', 1)
            ->where('latestGeneration.production_tasks.0', 'Production Task')
            ->has('latestGeneration.risks_or_missing_information', 1)
            ->where('latestGeneration.risks_or_missing_information.0', 'Risk'),
        );
});

it('omits latest generation when no completed generation exists', function (): void {
    $user = User::factory()->create();
    Project::factory()->for($user)->create(['brief' => 'A brief.']);

    $this->actingAs($user)
        ->get(route('projects.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Projects')
            ->where('latestGeneration', null),
        );
});

it('ignores another user completed generation', function (): void {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $project = Project::factory()->for($user)->create(['brief' => 'A brief.']);
    $otherProject = Project::factory()->for($otherUser)->create(['brief' => 'Other brief.']);

    ContentGeneration::factory()->for($otherProject)->create([
        'status' => 'completed',
        'response' => ['suggested_title' => 'Other'],
    ]);

    $this->actingAs($user)
        ->get(route('projects.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Projects')
            ->where('latestGeneration', null),
        );
});
