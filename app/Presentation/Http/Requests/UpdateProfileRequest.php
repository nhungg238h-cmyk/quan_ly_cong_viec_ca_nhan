<?php

namespace App\Presentation\Http\Requests;

use App\Domain\User\Entities\User;
use App\Presentation\Http\Requests\Concerns\ResolvesImageUpload;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    use ResolvesImageUpload;

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:'.User::MAX_NAME_LENGTH],
            'avatar' => ['nullable', ...self::IMAGE_RULES],
            'remove_avatar' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Vui lòng nhập họ tên.',
            'name.max' => 'Họ tên không được dài quá :max ký tự.',
            'avatar.image' => 'Ảnh đại diện phải là ảnh (jpg, png, gif, webp).',
            'avatar.mimes' => 'Ảnh đại diện phải là ảnh (jpg, png, gif, webp).',
            'avatar.max' => 'Ảnh đại diện không được lớn hơn 5MB.',
            'avatar.uploaded' => 'Tải ảnh lên thất bại, ảnh có thể quá lớn.',
        ];
    }
}
