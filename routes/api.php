<?php

use App\Http\Controllers\HealthController;
use Illuminate\Support\Facades\Route;

Route::post('/health', [HealthController::class, 'ingest']);

Route::get('/health/summary', [HealthController::class, 'summary']);
Route::get('/health/sleep', [HealthController::class, 'sleep']);
Route::get('/health/metrics', [HealthController::class, 'metricsList']);
Route::get('/health/metrics/{name}', [HealthController::class, 'metric']);
Route::get('/health/workouts', [HealthController::class, 'workouts']);
