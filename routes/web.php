<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MsMailController;

Route::get('/', [MsMailController::class, 'index']);
Route::get('/connect-ms', [MsMailController::class, 'connect']);
Route::get('/get-ms-token', [MsMailController::class, 'callback']);
Route::post('/refresh-token', [MsMailController::class, 'refreshToken']);
Route::post('/disconnect-current', [MsMailController::class, 'disconnectCurrentAccount']);
Route::post('/send-test-email', [MsMailController::class, 'sendTestEmail']);
Route::post('/token-status', [MsMailController::class, 'getTokenStatus']);
Route::post('/check-account-status', [MsMailController::class, 'checkAccountStatus']);
