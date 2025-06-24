<?php

use App\Http\Controllers\Api\PostController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/posts', [PostController::class, 'index']);
Route::get('/posts/create', [PostController::class, 'create']);

Route::post('/posts', [PostController::class, 'store'])->middleware('auth:sanctum');

Route::get('/posts/{post}', [PostController::class, 'show']);

Route::put('/posts/{post}', [PostController::class, 'update'])->middleware('auth:sanctum');
Route::delete('/posts/{post}', [PostController::class, 'destroy'])->middleware('auth:sanctum');

Route::put('/posts/{post}', [PostController::class, 'update'])->middleware('auth:sanctum');
