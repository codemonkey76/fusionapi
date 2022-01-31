<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CdrController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);

// Protected Routes
Route::middleware('auth:sanctum')->group(function() {
    Route::get('calls', [CdrController::class, 'index']);
    Route::get('call_range', [CdrController::class, 'range']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('active_calls', [ActiveCallController::class, 'index']);
});
