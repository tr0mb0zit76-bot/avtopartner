<?php

use Illuminate\Support\Facades\Route;

// Главная страница сайта-визитки
Route::get('/', function () {
    return view('pages.index');
});

// Временный роут для кабинета (позже заменим на модуль)
Route::get('/cabinet', function () {
    return 'Здесь будет кабинет версии 2.0';
})->name('cabinet');