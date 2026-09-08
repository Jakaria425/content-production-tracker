<?php

use App\Models\ContentGeneration;
use App\Models\Project;

it('casts the response column as an array', function (): void {
    $project = Project::factory()->create();
    $response = ['suggested_title' => 'Test Title'];

    $generation = ContentGeneration::create([
        'project_id' => $project->id,
        'status' => 'completed',
        'prompt' => 'prompt text',
        'response' => $response,
        'model' => 'gpt-4o-mini',
    ]);

    expect($generation->fresh()->response)->toBe($response);
});

it('persists tracked fields', function (): void {
    $project = Project::factory()->create();

    $generation = ContentGeneration::create([
        'project_id' => $project->id,
        'status' => 'failed',
        'prompt' => 'the prompt',
        'model' => 'gpt-4o-mini',
        'input_tokens' => 100,
        'output_tokens' => 200,
        'error_code' => 'provider_error',
    ]);

    expect($generation->fresh())
        ->status->toBe('failed')
        ->prompt->toBe('the prompt')
        ->model->toBe('gpt-4o-mini')
        ->input_tokens->toBe(100)
        ->output_tokens->toBe(200)
        ->error_code->toBe('provider_error');
});

it('belongs to its project', function (): void {
    $project = Project::factory()->create();
    $generation = ContentGeneration::factory()->for($project)->create();

    expect($generation->project->is($project))->toBeTrue();
});

it('factory default is a completed generation', function (): void {
    $project = Project::factory()->create();
    $generation = ContentGeneration::factory()->for($project)->create();

    expect($generation->status)->toBe('completed')
        ->and($generation->response)->toBeArray()
        ->and($generation->error_code)->toBeNull();
});

it('factory failed state clears response data', function (): void {
    $project = Project::factory()->create();
    $generation = ContentGeneration::factory()->for($project)->failed()->create();

    expect($generation->status)->toBe('failed')
        ->and($generation->response)->toBeNull()
        ->and($generation->model)->toBeNull()
        ->and($generation->input_tokens)->toBeNull()
        ->and($generation->output_tokens)->toBeNull()
        ->and($generation->error_code)->toBe('provider_error');
});

it('deletes generations when the project is deleted', function (): void {
    $project = Project::factory()->create();
    ContentGeneration::factory()->for($project)->create();

    $project->delete();

    expect(ContentGeneration::count())->toBe(0);
});
