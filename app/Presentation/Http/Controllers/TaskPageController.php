<?php

namespace App\Presentation\Http\Controllers;

use App\Domain\Task\UseCases\GetTasksUseCase;
use App\Presentation\Http\Resources\TaskResource;
use App\Presentation\Http\TaskOptions;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TaskPageController
{
    public function __invoke(Request $request, GetTasksUseCase $getTasks): View
    {
        return view('tasks.index', [
            'tasks' => TaskResource::collection($getTasks->execute($request->user()->id))->resolve($request),
            'options' => TaskOptions::toArray(),
        ]);
    }
}
