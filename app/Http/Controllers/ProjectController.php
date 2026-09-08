<?php

namespace App\Http\Controllers;

use App\Models\ContentGeneration;
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
            ->with(['contentGenerations' => fn ($query) => $query->where('status', 'completed')->select('id', 'project_id')])
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
            ])
            ->values();

        $latestGeneration = ContentGeneration::query()
            ->with('project')
            ->whereHas('project', fn ($query) => $query->where('user_id', $request->user()->id))
            ->where('status', 'completed')
            ->latest()
            ->first();

        return Inertia::render('Projects', [
            'projects' => $projects,
            'latestGeneration' => $latestGeneration === null ? null : [
                'id' => $latestGeneration->id,
                'project_id' => $latestGeneration->project_id,
                'project_title' => $latestGeneration->project->title,
                'suggested_title' => $latestGeneration->response['suggested_title'] ?? '',
                'content_brief' => $latestGeneration->response['content_brief'] ?? '',
                'outline' => $latestGeneration->response['outline'] ?? [],
                'key_points' => $latestGeneration->response['key_points'] ?? [],
                'production_tasks' => $latestGeneration->response['production_tasks'] ?? [],
                'risks_or_missing_information' => $latestGeneration->response['risks_or_missing_information'] ?? [],
            ],
        ]);
    }
}
