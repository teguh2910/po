<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PoController;

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware('manual.auth')->group(function () {
    Route::get('/', fn() => redirect()->route('po.index'));
    Route::get('/po', [PoController::class, 'index'])->name('po.index');
    Route::get('/po/upload', [PoController::class, 'create'])->name('po.create');
    Route::post('/po/upload', [PoController::class, 'store'])->name('po.store');
    // NEW: detail per upload
    Route::get('/po/{poUpload}', [PoController::class, 'show'])->name('po.show');
    Route::delete('/po/{poUpload}', [PoController::class, 'destroy'])->name('po.destroy');

});
