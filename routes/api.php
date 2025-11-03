<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/test', function () {
    return response()->json([
        'message' => 'Fitness App API is running!',
        'version' => '1.0.0',
        'timestamp' => now()->toDateTimeString(),
    ]);
});

// Protected route example
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
