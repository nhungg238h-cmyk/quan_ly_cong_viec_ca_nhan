<?php

use App\Presentation\Http\Controllers\Auth\LoginController;
use App\Presentation\Http\Controllers\Auth\RegisterController;
use App\Presentation\Http\Controllers\TaskController;
use App\Presentation\Http\Controllers\TaskImageController;
use App\Presentation\Http\Controllers\TaskPageController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:10,1');
    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->middleware('throttle:10,1');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('/', TaskPageController::class)->name('tasks.page');
    Route::get('/tasks/{id}/image', TaskImageController::class)->whereNumber('id')->name('tasks.image');


    Route::prefix('api/tasks')->name('tasks.')->controller(TaskController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::delete('/completed', 'destroyCompleted')->name('completed.destroy');
        Route::put('/{id}', 'update')->whereNumber('id')->name('update');
        Route::patch('/{id}', 'updateStatus')->whereNumber('id')->name('status');
        Route::delete('/{id}', 'destroy')->whereNumber('id')->name('destroy');
        Route::post('/{id}/image', 'updateImage')->whereNumber('id')->name('image.update');
        Route::delete('/{id}/image', 'destroyImage')->whereNumber('id')->name('image.destroy');
    });
});
