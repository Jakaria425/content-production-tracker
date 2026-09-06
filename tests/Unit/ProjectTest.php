<?php

use App\Enums\ProjectContentType;
use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\User;

test('Project test', function (): void {
    $project = Project::factory()->create();
    expect($project->user)->toBeInstanceOf(User::class);
});

test('factory produces valid content types', function (): void {
    $project = Project::factory()->make();
    expect(in_array($project->content_type->value, ProjectContentType::values()))->toBeTrue();
});

test('factory produces valid statuses', function (): void {
    $project = Project::factory()->make();
    expect(in_array($project->status->value, ProjectStatus::values()))->toBeTrue();
});

test('factory produces projects with enum-casted attributes', function (): void {
    $project = Project::factory()->make();
    expect($project->content_type)->toBeInstanceOf(ProjectContentType::class);
    expect($project->status)->toBeInstanceOf(ProjectStatus::class);
});
