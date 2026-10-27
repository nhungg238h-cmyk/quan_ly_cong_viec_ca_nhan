<?php

namespace App\Presentation\Http\Requests;

use App\Presentation\Http\Requests\Concerns\BuildsTaskDetails;
use App\Presentation\Http\Requests\Concerns\ResolvesImageUpload;
use Illuminate\Foundation\Http\FormRequest;

class StoreTaskRequest extends FormRequest
{
    use BuildsTaskDetails, ResolvesImageUpload;

    public function rules(): array
    {
        return [
            ...$this->taskDetailRules(),
            'image' => ['nullable', ...self::IMAGE_RULES],
        ];
    }

    public function messages(): array
    {
        return [
            ...$this->taskDetailMessages(),
            ...UpdateTaskImageRequest::IMAGE_MESSAGES,
        ];
    }
}
