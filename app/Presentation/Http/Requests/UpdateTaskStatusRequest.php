<?php

namespace App\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTaskStatusRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'completed' => ['required', 'boolean'],
        ];
    }
}
