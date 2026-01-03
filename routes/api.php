<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\FacePhotoController;
use App\Http\Controllers\LocationController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

Route::middleware('auth:api')->group(function () {
    Route::get('/user', [AuthController::class, 'me']);

    Route::post('/face-photos', [FacePhotoController::class, 'store']);
    Route::get('/face-photos', [FacePhotoController::class, 'show']);
    Route::post('/update-embed-face', [FacePhotoController::class, 'updateFace']);
    Route::post('/face-photos/encode', [FacePhotoController::class, 'registerFace']);

    // === ATTENDANCE ===
    Route::post('/attendance', [AttendanceController::class, 'store']);
    Route::get('/attendance', [AttendanceController::class, 'history']);

    // ⭐ STATUS TOMBOL MOBILE
    Route::get('/attendance/today-status', [AttendanceController::class, 'todayStatus']);

    Route::get('/locations', [LocationController::class, 'index']);
});
