<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\Cabinet\Auth\LoginController;
use App\Http\Controllers\Cabinet\DashboardController;
use App\Http\Controllers\Cabinet\ThemeController;
use App\Http\Controllers\Cabinet\Users\UserController;
use App\Http\Controllers\Cabinet\OrderController;
use App\Http\Controllers\Cabinet\Settings\KpiThresholdController;

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
        
        // Заявки
        Route::prefix('orders')->name('orders.')->group(function () {
            Route::get('/', [OrderController::class, 'index'])->name('index');
            Route::post('/save', [OrderController::class, 'save'])->name('save');
            Route::get('/filter', [OrderController::class, 'filter'])->name('filter');
            Route::post('/create', [OrderController::class, 'create'])->name('create');
            Route::post('/generate-number', [OrderController::class, 'generateNumber'])->name('generate-number');
            Route::post('/bulk-delete', [OrderController::class, 'bulkDelete'])->name('bulk-delete');
            Route::put('/{order}', [OrderController::class, 'update'])->name('update');
            Route::delete('/{order}', [OrderController::class, 'destroy'])->name('destroy');
            // Маршрут для получения заявок за период
            Route::get('/period-orders', [OrderController::class, 'getPeriodOrders'])->name('period-orders');
        });
        
        // Настройки KPI (только для руководителей и администраторов)
        Route::prefix('settings')->name('settings.')->group(function () {
            Route::get('/kpi', [KpiThresholdController::class, 'index'])->name('kpi');
            Route::post('/kpi', [KpiThresholdController::class, 'update'])->name('kpi.update');
            // Маршрут для ручного пересчёта заявок менеджера
            Route::post('/recalculate-manager/{managerId}', [KpiThresholdController::class, 'recalculateManager'])->name('recalculate-manager');
        });
        
        // Дополнительные модули (в разработке)
        Route::get('/reports', function () {
            return view('cabinet.reports.index');
        })->name('reports.index');
    });
});

// Алиас для редиректа с /login на /cabinet/login (для стандартных редиректов Laravel)
Route::get('/login', function () {
    return redirect()->route('cabinet.login');
})->name('login');

// Временный тестовый маршрут (можно удалить позже)
Route::get('/test', function () {
    return 'Test page';
});