<?php

use App\Models\User;

test('probe environment and csrf', function (): void {
    dump([
        'env' => app()->environment(),
        'runningUnitTests' => app()->runningUnitTests(),
        'runningInConsole' => app()->runningInConsole(),
    ]);

    $user = User::factory()->create();
    $response = $this
        ->actingAs($user)
        ->post(route('teams.store'), ['name' => 'Probe Team']);

    dump([
        'status' => $response->status(),
        'exception' => $response->exception ? get_class($response->exception) : null,
        'body' => substr($response->getContent() ?: '', 0, 300),
    ]);
});
