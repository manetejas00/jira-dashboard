<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\JiraController;

Route::post('login', [UserController::class, 'login']);
Route::post('register', [UserController::class, 'register']);

Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('jira')->controller(JiraController::class)->group(function () {
        Route::get('tasks', 'getTasks');
        Route::post('tasks', 'createTask');
        Route::put('tasks/{taskKey}', 'updateTask');
        Route::get('users', 'getAssignableUsers');
        Route::get('priorities', 'getPriorities');
        Route::get('statuses', 'getStatuses');
        Route::get('boards', 'getBoards');
        Route::get('sprints', 'getSprints');
    });
    Route::post('logout', [UserController::class, 'logout']);
});
