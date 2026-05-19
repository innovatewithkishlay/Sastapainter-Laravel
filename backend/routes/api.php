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
        
        // Protected Admin Routes (Matches Node admin.js)
        Route::group(['prefix' => 'admin', 'middleware' => ['admin']], function () {
            Route::get('/stats', [\App\Http\Controllers\AdminController::class, 'getDashboardStats']);
            
            Route::get('/users', [\App\Http\Controllers\AdminController::class, 'getUsers']);
            Route::delete('/users/{id}', [\App\Http\Controllers\AdminController::class, 'deleteUser']);
            
            Route::get('/bookings', [\App\Http\Controllers\AdminController::class, 'getBookings']);
            Route::put('/bookings/{id}/status', [\App\Http\Controllers\AdminController::class, 'updateBookingStatus']);
            Route::put('/bookings/{id}/assign', [\App\Http\Controllers\AdminController::class, 'assignPainter']);
            
            Route::get('/site-visits', [\App\Http\Controllers\AdminController::class, 'getSiteVisits']);
            Route::put('/site-visits/{id}/status', [\App\Http\Controllers\AdminController::class, 'updateSiteVisitStatus']);
            
            Route::get('/painters', [\App\Http\Controllers\AdminController::class, 'getPainters']);
            Route::post('/painters', [\App\Http\Controllers\AdminController::class, 'addPainter']);
            Route::delete('/painters/{id}', [\App\Http\Controllers\AdminController::class, 'deletePainter']);
        });
    });
});
