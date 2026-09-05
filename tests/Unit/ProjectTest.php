<?php

use App\Models\Project;
use App\Models\User;

test('Project test', function () {
    $project = Project::factory()->create();
    expect($project->user)->toBeInstanceOf(User::class);
});
