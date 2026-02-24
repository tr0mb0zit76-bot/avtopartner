@extends('cabinet.layouts.app')

@section('title', 'Главная кабинета')

@section('content')
<div class="dashboard">
    <h1>Добро пожаловать в кабинет!</h1>
    <p>Вы успешно вошли в систему.</p>
    
    <form method="POST" action="{{ route('cabinet.logout') }}">
        @csrf
        <button type="submit" class="btn-logout">Выйти</button>
    </form>
</div>
@endsection