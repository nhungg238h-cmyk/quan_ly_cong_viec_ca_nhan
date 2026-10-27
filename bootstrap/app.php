<?php

use App\Domain\Task\Exceptions\InvalidTaskException;
use App\Domain\Task\Exceptions\TaskNotFoundException;
use App\Domain\User\Exceptions\UserNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Tin header X-Forwarded-* khi chạy sau proxy/tunnel (vd. Cloudflare) để sinh URL https đúng.
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Ánh xạ lỗi nghiệp vụ của tầng Domain sang mã HTTP.
        $exceptions->render(fn (TaskNotFoundException $e) => response()->json(['message' => $e->getMessage()], 404));
        $exceptions->render(fn (InvalidTaskException $e) => response()->json(['message' => $e->getMessage()], 422));
        $exceptions->render(fn (UserNotFoundException $e) => abort(404));
    })->create();
