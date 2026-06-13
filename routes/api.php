<?php

use App\Http\Controllers\API\OngoingEventController;
use App\Http\Controllers\API\StudentAuthController;
use App\Http\Controllers\API\StudentHomeController;
use App\Http\Controllers\API\UpcomingEventController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'student'], function () {

    Route::post('/login', [StudentAuthController::class, 'login']);
    Route::post('forgot-password', [StudentAuthController::class, 'forgotPassword']);
    Route::post('update-password', [StudentAuthController::class, 'updatePassword']);

    Route::middleware('verify.jwt')->group(function () {
        Route::post('home', [StudentHomeController::class, 'index']);
        Route::get('upcoming-events', [UpcomingEventController::class, 'index']);
        Route::get('ongoing-events', [OngoingEventController::class, 'index']);
    });
});
