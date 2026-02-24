<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Cabinet\Auth\LoginController;
use App\Http\Controllers\Cabinet\DashboardController;
use App\Http\Controllers\Cabinet\ThemeController;
use App\Http\Controllers\Cabinet\Users\UserController;
use App\Http\Controllers\Cabinet\OrderController;

// Все маршруты кабинета
Route::prefix('cabinet')->name('cabinet.')->group(function () {
    
    // Вход и выход (доступны всем)
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
    
    // Переключение темы (доступно всем)
    Route::post('/theme/switch', [ThemeController::class, 'switch'])->name('theme.switch');
    
    // Защищённые маршруты (только для авторизованных)
    Route::middleware('auth')->group(function () {
        // Главная кабинета
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
        
        // Управление пользователями
        Route::resource('users', UserController::class)->names('users');
        
        // Отчёты
        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('/', [ReportController::class, 'index'])->name('index');
            Route::get('/salary', [ReportController::class, 'salary'])->name('salary');
            Route::get('/kpi', [ReportController::class, 'kpi'])->name('kpi');
        });
        
        // Заявки (Orders)
        Route::prefix('orders')->name('orders.')->group(function () {
            Route::get('/', [OrderController::class, 'index'])->name('index');
            Route::post('/save', [OrderController::class, 'save'])->name('save');
            Route::get('/filter', [OrderController::class, 'filter'])->name('filter');
            Route::post('/create', [OrderController::class, 'create'])->name('create');
            Route::put('/{order}', [OrderController::class, 'update'])->name('update');
            Route::delete('/{order}', [OrderController::class, 'destroy'])->name('destroy');
        });
    });
});

// Алиас для редиректа с /login на /cabinet/login
Route::get('/login', function () {
    return redirect()->route('cabinet.login');
})->name('login');