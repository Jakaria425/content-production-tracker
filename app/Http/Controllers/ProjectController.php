<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    /**
     * Display a listing of the authenticated user's projects.
     */
    public function index(Request $request): Response
    {
        $projects = $request->user()->projects()
            ->latest()
            ->get()
            ->map(fn (Project $project): array => [
                'id' => $project->id,
                'title' => $project->title,
                'content_type' => $project->content_type->label(),
                'status' => $project->status->label(),
                'due_date' => $project->due_date?->toDateString(),
            ])
            ->values();

        return Inertia::render('Projects', [
            'projects' => $projects,
        ]);
    }
}
