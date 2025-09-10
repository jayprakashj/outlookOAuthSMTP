<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MsMailController;

Route::get('/', function () {
    return view('welcome');
});


Route::get('/connect-ms', [MsMailController::class, 'connect']);
Route::get('/get-token', [MsMailController::class, 'callback']);
