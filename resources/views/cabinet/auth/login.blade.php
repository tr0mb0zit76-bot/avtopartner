@extends('cabinet.layouts.app')

@section('title', 'Вход в кабинет')

@section('content')
<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <h2>Вход в кабинет</h2>
            <p>{{ current_site()->name ?? 'Автопартнер Поволжье' }}</p>
        </div>
        
        @if($errors->any())
            <div class="auth-error">
                {{ $errors->first() }}
            </div>
        @endif
        
        <form method="POST" action="{{ route('cabinet.login') }}" class="auth-form">
            @csrf
            
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" name="email" id="email" value="{{ old('email') }}" required autofocus>
            </div>

            <div class="form-group">
                <label for="password">Пароль</label>
                <input type="password" name="password" id="password" required>
            </div>

            <div class="form-group">
                <div class="checkbox-group">
                    <input type="checkbox" name="remember" id="remember">
                    <label for="remember">Запомнить меня</label>
                </div>
            </div>

            <button type="submit" class="btn-login">Войти</button>
        </form>
    </div>
</div>
@endsection