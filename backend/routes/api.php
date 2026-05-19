<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

Route::group(['prefix' => 'v1'], function () {
    
    // Public Auth Routes
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/admin/login', [AuthController::class, 'adminLogin']);
    Route::post('/auth/google', [AuthController::class, 'googleLogin']);

    // Protected Auth Routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/check-auth', [AuthController::class, 'checkAuth']);
        Route::get('/logout', [AuthController::class, 'logout']);
    });
});
