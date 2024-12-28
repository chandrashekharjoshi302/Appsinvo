<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');


Route::post('/register', [UserController::class, 'register']);
Route::post('/toggle-statuses', [UserController::class, 'toggleStatuses'])->middleware('auth:sanctum');
Route::post('/get-distance', [UserController::class, 'getDistance'])->middleware('auth:sanctum');
Route::post('/list-users', [UserController::class, 'listUsersByDays'])->middleware('auth:sanctum');
