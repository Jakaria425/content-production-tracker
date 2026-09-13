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
            ->with(['contentGenerations' => fn ($query) => $query->where('status', 'completed')->latest()])
            ->latest()
            ->get()
            ->map(fn (Project $project): array => [
                'id' => $project->id,
                'title' => $project->title,
                'content_type' => $project->content_type->label(),
                'status' => $project->status->label(),
                'due_date' => $project->due_date?->toDateString(),
                'brief' => $project->brief,
                'has_content_plan' => $project->contentGenerations->isNotEmpty(),
                'content_plan' => $project->contentGenerations->first()?->response,
            ])
            ->values();

        return Inertia::render('Projects', [
            'projects' => $projects,
        ]);
    }
}
