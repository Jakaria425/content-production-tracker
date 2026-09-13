<?php

use App\Models\ContentGeneration;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function (): void {
    config(['inertia.ssr.enabled' => false]);
    Http::preventStrayRequests();
});

it('passes the per-project content plan to the projects page', function (): void {
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
            ->where('projects.0.content_plan.suggested_title', 'Suggested Title')
            ->where('projects.0.content_plan.content_brief', 'Content Brief')
            ->has('projects.0.content_plan.outline', 1)
            ->where('projects.0.content_plan.outline.0.heading', 'Outline Heading')
            ->where('projects.0.content_plan.outline.0.purpose', 'Outline Purpose')
            ->has('projects.0.content_plan.key_points', 1)
            ->where('projects.0.content_plan.key_points.0', 'Key Point')
            ->has('projects.0.content_plan.production_tasks', 1)
            ->where('projects.0.content_plan.production_tasks.0', 'Production Task')
            ->has('projects.0.content_plan.risks_or_missing_information', 1)
            ->where('projects.0.content_plan.risks_or_missing_information.0', 'Risk'),
        );
});

it('omits content plan when no completed generation exists', function (): void {
    $user = User::factory()->create();
    Project::factory()->for($user)->create(['brief' => 'A brief.']);

    $this->actingAs($user)
        ->get(route('projects.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Projects')
            ->where('projects.0.content_plan', null),
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
            ->where('projects.0.content_plan', null),
        );
});

it('shows each project its own completed plan', function (): void {
    $user = User::factory()->create();
    $projectA = Project::factory()->for($user)->create(['brief' => 'Brief A.']);
    $projectB = Project::factory()->for($user)->create(['brief' => 'Brief B.']);

    $planA = [
        'suggested_title' => 'Plan A',
        'content_brief' => 'Brief A.',
        'outline' => [['heading' => 'Heading A', 'purpose' => 'Purpose A']],
        'key_points' => ['Key A'],
        'production_tasks' => ['Task A'],
        'risks_or_missing_information' => ['Risk A'],
    ];
    $planB = [
        'suggested_title' => 'Plan B',
        'content_brief' => 'Brief B.',
        'outline' => [['heading' => 'Heading B', 'purpose' => 'Purpose B']],
        'key_points' => ['Key B'],
        'production_tasks' => ['Task B'],
        'risks_or_missing_information' => ['Risk B'],
    ];

    ContentGeneration::factory()->for($projectA)->create([
        'status' => 'completed',
        'response' => $planA,
    ]);
    ContentGeneration::factory()->for($projectB)->create([
        'status' => 'completed',
        'response' => $planB,
    ]);

    $this->actingAs($user)
        ->get(route('projects.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Projects')
            ->has('projects', 2)
            ->where('projects.0.content_plan.suggested_title', 'Plan A')
            ->where('projects.1.content_plan.suggested_title', 'Plan B'),
        );

    Http::assertNothingSent();
});
