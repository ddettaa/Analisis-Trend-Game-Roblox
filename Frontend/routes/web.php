<?php

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DashboardController::class, 'landing']);
Route::get('/dashboard', [DashboardController::class, 'ringkasan']);
Route::get('/dashboard/saturasi', [DashboardController::class, 'saturasi']);
Route::get('/dashboard/viral', [DashboardController::class, 'viral']);
