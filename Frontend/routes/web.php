<?php

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DashboardController::class, 'ringkasan']);
Route::get('/saturasi', [DashboardController::class, 'saturasi']);
Route::get('/viral', [DashboardController::class, 'viral']);
