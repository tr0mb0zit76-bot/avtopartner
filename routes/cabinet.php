<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Cabinet\Auth\LoginController;
use App\Http\Controllers\Cabinet\DashboardController;
use App\Http\Controllers\Cabinet\ThemeController;

// Все маршруты кабинета
Route::prefix('cabinet')->name('cabinet.')->group(function () {
    
    // Вход и выход
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
    
    // Переключение темы
    Route::post('/theme/switch', [ThemeController::class, 'switch'])->name('theme.switch');
    
    // Защищённые маршруты
    Route::middleware('auth')->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    });
});

// ДОБАВЛЯЕМ ЭТОТ АЛИАС В КОНЕЦ ФАЙЛА
// Это заставит Laravel перенаправлять 'login' на 'cabinet.login'
Route::get('/login', function () {
    return redirect()->route('cabinet.login');
})->name('login');