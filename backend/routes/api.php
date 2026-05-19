<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\MainController;

Route::group(['prefix' => 'v1'], function () {
    
    // Public Main Routes
    Route::get('/', [MainController::class, 'getHome']);
    Route::get('/services', [MainController::class, 'getServices']);
    Route::get('/reviews/public', [MainController::class, 'getPublicReviews']);
    
    // Public Auth Routes
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/admin/login', [AuthController::class, 'adminLogin']);
    Route::post('/auth/google', [AuthController::class, 'googleLogin']);

    // Protected Auth Routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/check-auth', [AuthController::class, 'checkAuth']);
        Route::get('/logout', [AuthController::class, 'logout']);
        
        // Protected User Routes (Matches Node user.js)
        Route::post('/site-visit', [MainController::class, 'postSiteVisit']);
        Route::post('/book', [MainController::class, 'postBooking']);
        
        Route::group(['prefix' => 'user'], function () {
            Route::get('/my-bookings', [MainController::class, 'getMyBookings']);
            Route::get('/my-bookings/edit/{id}', [MainController::class, 'getEditBooking']);
            Route::post('/my-bookings/edit/{id}', [MainController::class, 'updateBooking']);
            Route::post('/my-bookings/delete/{id}', [MainController::class, 'deleteBooking']);
            Route::post('/reviews', [MainController::class, 'submitReview']);
        });
    });
});
