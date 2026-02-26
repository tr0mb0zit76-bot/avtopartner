@extends('cabinet.layouts.app')

@section('title', 'Добавление пользователя')

@section('content')
<div class="user-form">
    <h1>Добавление пользователя</h1>
    
    <form method="POST" action="{{ route('cabinet.users.store') }}">
        @include('cabinet.users.partials.form', ['user' => null])
    </form>
    
    <a href="{{ route('cabinet.users.index') }}" class="btn-back">← Назад к списку</a>
</div>
@endsection