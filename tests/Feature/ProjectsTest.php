<?php

use App\Models\Project;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function (): void {
    $response = $this->get(route('projects.index'));

    $response->assertRedirect(route('login'));
});

test('authenticated users can open the projects page', function (): void {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get(route('projects.index'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page->component('Projects'));
});

test('the projects page shows only the authenticated user projects', function (): void {
    $user = User::factory()->create();

    $projects = Project::factory()->count(3)->for($user)->create();

    $response = $this
        ->actingAs($user)
        ->get(route('projects.index'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Projects')
        ->has('projects', 3)
        ->where('projects.0.id', $projects[0]->id)
        ->where('projects.1.id', $projects[1]->id)
        ->where('projects.2.id', $projects[2]->id)
        ->where('projects.0.title', $projects[0]->title)
        ->where('projects.0.status', $projects[0]->status->label())
        ->where('projects.0.content_type', $projects[0]->content_type->label()),
    );
});

test('the projects page does not expose another user projects', function (): void {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    Project::factory()->for($user)->create([
        'title' => 'My Project',
    ]);

    Project::factory()->for($otherUser)->create([
        'title' => 'Other Users Project',
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('projects.index'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Projects')
        ->has('projects', 1)
        ->where('projects.0.title', 'My Project'),
    );
});

test('projects are ordered from newest to oldest', function (): void {
    $user = User::factory()->create();

    $old = Project::factory()->for($user)->create([
        'title' => 'Oldest Project',
        'created_at' => now()->subDays(2),
    ]);

    $new = Project::factory()->for($user)->create([
        'title' => 'Newest Project',
        'created_at' => now(),
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('projects.index'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Projects')
        ->has('projects', 2)
        ->where('projects.0.id', $new->id)
        ->where('projects.1.id', $old->id),
    );
});

test('an account without projects sees the empty state', function (): void {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get(route('projects.index'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Projects')
        ->has('projects', 0),
    );
});
