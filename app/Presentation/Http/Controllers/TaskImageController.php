<?php

namespace App\Presentation\Http\Controllers;

use App\Domain\Shared\Services\ImageStorageInterface;
use App\Domain\Task\UseCases\GetTaskUseCase;
use App\Presentation\Http\Concerns\ServesPrivateImages;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Trả file ảnh của công việc - chỉ chủ sở hữu mới xem được.
 */
class TaskImageController
{
    use ServesPrivateImages;

    public function __invoke(
        int $id,
        Request $request,
        GetTaskUseCase $getTask,
        ImageStorageInterface $images,
    ): BinaryFileResponse {
        $task = $getTask->execute($request->user()->id, $id);

        return $this->privateImage($request, $task->hasImage() ? $images->absolutePath($task->imagePath()) : null);
    }
}
