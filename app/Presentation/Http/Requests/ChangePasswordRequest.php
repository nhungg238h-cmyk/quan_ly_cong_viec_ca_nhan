<?php

namespace App\Presentation\Http\Requests;

use App\Domain\User\UseCases\RegisterUserUseCase;
use Illuminate\Foundation\Http\FormRequest;

class ChangePasswordRequest extends FormRequest
{
    /**
     * Lỗi hiển thị riêng cho form đổi mật khẩu trên trang hồ sơ.
     */
    protected $errorBag = 'password';

    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:'.RegisterUserUseCase::MIN_PASSWORD_LENGTH, 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'current_password.required' => 'Vui lòng nhập mật khẩu hiện tại.',
            'password.required' => 'Vui lòng nhập mật khẩu mới.',
            'password.min' => 'Mật khẩu mới phải có ít nhất :min ký tự.',
            'password.confirmed' => 'Mật khẩu nhập lại không khớp.',
        ];
    }
}
