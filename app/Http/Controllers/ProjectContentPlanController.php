<?php

namespace App\Http\Controllers;

use App\Models\ContentGeneration;
use App\Models\Project;
use App\Services\OpenAIService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProjectContentPlanController extends Controller
{
    private const SAFE_FAILURE_MESSAGE = 'The content plan could not be generated. Please try again later.';

    public function __construct(private OpenAIService $openAIService) {}

    public function store(Request $request, Project $project): RedirectResponse
    {
        if ($request->user()->id !== $project->user_id) {
            abort(403);
        }

        if (empty($project->title) || empty($project->content_type) || empty($project->brief)) {
            return $this->failWithoutGeneration();
        }

        $result = $this->openAIService->generate($project);

        ContentGeneration::create([
            'project_id' => $project->id,
            'status' => $result['status'],
            'prompt' => $result['prompt'],
            'response' => $result['status'] === 'completed' ? $result['data'] : null,
            'model' => $result['model'],
            'input_tokens' => $result['input_tokens'],
            'output_tokens' => $result['output_tokens'],
            'error_code' => $result['status'] === 'failed' ? $result['error_code'] : null,
        ]);

        if ($result['status'] === 'failed') {
            return $this->failWithoutGeneration();
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Content plan generated successfully.',
        ]);

        return back();
    }

    private function failWithoutGeneration(): RedirectResponse
    {
        Inertia::flash('toast', [
            'type' => 'error',
            'message' => self::SAFE_FAILURE_MESSAGE,
        ]);

        return back();
    }
}
