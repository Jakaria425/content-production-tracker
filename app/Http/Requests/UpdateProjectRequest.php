<?php

namespace App\Http\Requests;

use App\Enums\ProjectContentType;
use App\Enums\ProjectStatus;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return false;
    }

    public function rules(): array
    {
        return [
            'content_type' => ['required', 'string', 'in:'.implode(',', ProjectContentType::values())],
            'status' => ['required', 'string', 'in:'.implode(',', ProjectStatus::values())],
        ];
    }
}
