@extends('cabinet.layouts.app')

@section('title', 'Главная кабинета')

@section('content')
<div class="dashboard">
    <h1>Добро пожаловать, {{ auth()->user()->name }}!</h1>
    <p>Вы успешно вошли в систему.</p>
    
    <!-- Здесь будет контент дашборда -->
</div>
@endsection