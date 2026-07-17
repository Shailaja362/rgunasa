<?php

use App\Http\Controllers\API\CashfreeController;
use App\Http\Controllers\API\OngoingEventController;
use App\Http\Controllers\API\RegisterController;
use App\Http\Controllers\API\StudentAuthController;
use App\Http\Controllers\API\StudentController;
use App\Http\Controllers\API\StudentHomeController;
use App\Http\Controllers\API\UpcomingEventController;
use Illuminate\Support\Facades\Route;


Route::group(['prefix' => 'student'], function () {

    Route::post('/login', [StudentAuthController::class, 'login']);
    Route::post('forgot-password', [StudentAuthController::class, 'forgotPassword']);
    Route::post('update-password', [StudentAuthController::class, 'updatePassword']);
    Route::get('/download-certificate', [StudentController::class, 'downloadCertificate'])
        ->name('student.certificate.download');
    Route::middleware('verify.jwt')->group(function () {
        Route::post('home', [StudentHomeController::class, 'index']);
        Route::get('upcoming-events', [UpcomingEventController::class, 'index']);
        Route::get('ongoing-events', [OngoingEventController::class, 'index']);
        Route::get('register-events', [OngoingEventController::class, 'registerEvent']);
        Route::get('completed-events', [OngoingEventController::class, 'completedEvent']);

        // Register Event
        Route::post('register-event', [RegisterController::class, 'registerEvent']);
        Route::post('event-upload-proof', [RegisterController::class, 'eventUploadProof']);

        // profile
        Route::get('details', [StudentController::class, 'details']);
        Route::get('certificate', [StudentController::class, 'viewCertificate']);

        //notification
        Route::get('/notification-list', [StudentController::class, 'notificationList']);
        Route::delete('/notification-delete', [StudentController::class, 'deleteNotification']);

        //read notification
        Route::post('/notification-read-all', [StudentController::class, 'readNotificationAll']);

        Route::post('/create-order', [CashfreeController::class, 'createOrder'])->name('cashfree.order');
        Route::post('/verify-payment', [CashfreeController::class, 'verifyPayment']);
        Route::post('/register-paid-event', [CashfreeController::class, 'registerEvent']);
    });
});
