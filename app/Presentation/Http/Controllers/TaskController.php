<?php

namespace App\Presentation\Http\Controllers;

use App\Domain\Task\UseCases\ClearCompletedTasksUseCase;
use App\Domain\Task\UseCases\CreateTaskUseCase;
use App\Domain\Task\UseCases\DeleteTaskUseCase;
use App\Domain\Task\UseCases\GetTasksUseCase;
use App\Domain\Task\UseCases\SetTaskCompletionUseCase;
use App\Domain\Task\UseCases\SetTaskImageUseCase;
use App\Domain\Task\UseCases\UpdateTaskUseCase;
use App\Presentation\Http\Requests\StoreTaskRequest;
use App\Presentation\Http\Requests\UpdateTaskImageRequest;
use App\Presentation\Http\Requests\UpdateTaskRequest;
use App\Presentation\Http\Requests\UpdateTaskStatusRequest;
use App\Presentation\Http\Resources\TaskResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * API JSON cho công việc của người dùng đang đăng nhập.
 * Controller chỉ nhận request, gọi use case và trả kết quả.
 */
class TaskController
{
    public function index(Request $request, GetTasksUseCase $getTasks): AnonymousResourceCollection
    {
        return TaskResource::collection($getTasks->execute($request->user()->id));
    }

    public function store(StoreTaskRequest $request, CreateTaskUseCase $createTask): JsonResponse
    {
        $task = $createTask->execute($request->user()->id, $request->taskDetails(), $request->imageUpload());

        return (new TaskResource($task))->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function update(int $id, UpdateTaskRequest $request, UpdateTaskUseCase $updateTask): TaskResource
    {
        return new TaskResource($updateTask->execute($request->user()->id, $id, $request->taskDetails()));
    }

    public function updateStatus(int $id, UpdateTaskStatusRequest $request, SetTaskCompletionUseCase $setCompletion): TaskResource
    {
        return new TaskResource($setCompletion->execute($request->user()->id, $id, $request->boolean('completed')));
    }

    public function updateImage(int $id, UpdateTaskImageRequest $request, SetTaskImageUseCase $setImage): TaskResource
    {
        return new TaskResource($setImage->execute($request->user()->id, $id, $request->imageUpload()));
    }

    public function destroyImage(int $id, Request $request, SetTaskImageUseCase $setImage): TaskResource
    {
        return new TaskResource($setImage->execute($request->user()->id, $id, null));
    }

    public function destroy(int $id, Request $request, DeleteTaskUseCase $deleteTask): Response
    {
        $deleteTask->execute($request->user()->id, $id);

        return response()->noContent();
    }

    public function destroyCompleted(Request $request, ClearCompletedTasksUseCase $clearCompleted): JsonResponse
    {
        return response()->json(['data' => ['deleted' => $clearCompleted->execute($request->user()->id)]]);
    }
}
