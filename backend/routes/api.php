<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\MainController;
use App\Http\Controllers\AdminController;

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
    Route::get('/logout', [AuthController::class, 'logout']);

    // Optional-auth routes: work with OR without a token (matches Node.js checkAuth optional middleware)
    Route::post('/site-visit', [MainController::class, 'postSiteVisit']);
    Route::post('/book', [MainController::class, 'postBooking']);

    // Protected Routes (require valid Sanctum token)
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/check-auth', [AuthController::class, 'checkAuth']);

        // Protected User Routes
        Route::prefix('user')->group(function () {
            Route::get('/my-bookings', [MainController::class, 'getMyBookings']);
            Route::get('/my-bookings/edit/{id}', [MainController::class, 'getEditBooking']);
            Route::post('/my-bookings/edit/{id}', [MainController::class, 'updateBooking']);
            Route::post('/my-bookings/delete/{id}', [MainController::class, 'deleteBooking']);
            Route::post('/reviews', [MainController::class, 'submitReview']);
        });

        // Protected Admin Routes
        Route::prefix('admin')->middleware('admin')->group(function () {
            Route::get('/stats', [AdminController::class, 'getDashboardStats']);

            Route::get('/users', [AdminController::class, 'getUsers']);
            Route::delete('/users/{id}', [AdminController::class, 'deleteUser']);

            Route::get('/bookings', [AdminController::class, 'getBookings']);
            Route::put('/bookings/{id}/status', [AdminController::class, 'updateBookingStatus']);
            Route::put('/bookings/{id}/assign', [AdminController::class, 'assignPainter']);

            Route::get('/site-visits', [AdminController::class, 'getSiteVisits']);
            Route::put('/site-visits/{id}/status', [AdminController::class, 'updateSiteVisitStatus']);

            Route::get('/painters', [AdminController::class, 'getPainters']);
            Route::post('/painters', [AdminController::class, 'addPainter']);
            Route::delete('/painters/{id}', [AdminController::class, 'deletePainter']);

            // Legacy routes (matching Node.js admin.js)
            Route::get('/inquiries', [MainController::class, 'getInquiries']);
            Route::post('/inquiries/{id}/update', [MainController::class, 'updateInquiryStatus']);
        });
    });
});
