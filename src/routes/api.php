<?php

use App\Http\Controllers\RecordController;
use Illuminate\Support\Facades\Route;

Route::post('/records', [RecordController::class, 'store'])->name('records.store');
Route::get('/records/aggregate', [RecordController::class, 'aggregate'])->name('records.aggregate');
