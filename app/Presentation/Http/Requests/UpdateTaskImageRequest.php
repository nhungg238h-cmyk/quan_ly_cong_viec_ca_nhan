<?php

namespace App\Presentation\Http\Requests;

use App\Presentation\Http\Requests\Concerns\ResolvesImageUpload;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTaskImageRequest extends FormRequest
{
    use ResolvesImageUpload;

    public const IMAGE_MESSAGES = [
        'image.required' => 'Vui lòng chọn ảnh.',
        'image.image' => 'File phải là ảnh (jpg, png, gif, webp).',
        'image.mimes' => 'File phải là ảnh (jpg, png, gif, webp).',
        'image.max' => 'Ảnh không được lớn hơn 5MB.',
        'image.uploaded' => 'Tải ảnh lên thất bại, ảnh có thể quá lớn.',
    ];

    public function rules(): array
    {
        return [
            'image' => ['required', ...self::IMAGE_RULES],
        ];
    }

    public function messages(): array
    {
        return self::IMAGE_MESSAGES;
    }
}
