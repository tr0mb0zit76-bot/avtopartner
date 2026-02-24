<?php

use Illuminate\Support\Facades\Route;

// Главная страница сайта-визитки
Route::get('/', function () {
    return view('pages.index');
});

// Подключаем маршруты кабинета
require base_path('routes/cabinet.php');