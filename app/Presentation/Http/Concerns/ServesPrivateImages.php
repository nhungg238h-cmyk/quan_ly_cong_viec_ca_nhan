<?php

namespace App\Presentation\Http\Concerns;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

trait ServesPrivateImages
{
    /**
     * Trả file ảnh riêng tư. no-cache: trình duyệt luôn hỏi lại server (kiểm tra quyền)
     * trước khi dùng bản cache, nên sau khi đổi tài khoản trên cùng máy không xem được ảnh của người trước.
     */
    protected function privateImage(Request $request, ?string $path): BinaryFileResponse
    {
        abort_unless($path !== null && is_file($path), 404);

        $response = response()->file($path, ['Cache-Control' => 'private, no-cache']);
        $response->setAutoEtag();
        $response->isNotModified($request);

        return $response;
    }
}
