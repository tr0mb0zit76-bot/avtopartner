@extends('cabinet.layouts.app')

@section('title', 'Вход в кабинет')

@section('content')
<div class="login-container">
    <div class="login-form">
        <h2>Вход в кабинет</h2>
        <p class="site-name">{{ current_site()->name ?? '' }}</p>
        
        <form method="POST" action="{{ route('cabinet.login') }}">
            @csrf
            
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" name="email" id="email" value="{{ old('email') }}" required autofocus>
                @error('email')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="password">Пароль</label>
                <input type="password" name="password" id="password" required>
                @error('password')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group remember">
                <label>
                    <input type="checkbox" name="remember"> Запомнить меня
                </label>
            </div>

            <button type="submit" class="btn-login">Войти</button>
        </form>
    </div>
</div>
@endsection