<?php

namespace App\Presentation\Http\Requests;

use App\Presentation\Http\Requests\Concerns\BuildsTaskDetails;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTaskRequest extends FormRequest
{
    use BuildsTaskDetails;

    public function rules(): array
    {
        return $this->taskDetailRules();
    }

    public function messages(): array
    {
        return $this->taskDetailMessages();
    }
}
