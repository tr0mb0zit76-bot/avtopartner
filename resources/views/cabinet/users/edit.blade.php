@extends('cabinet.layouts.app')

@section('title', 'Редактирование пользователя')

@section('content')
<div class="user-form">
    <h1>Редактирование пользователя</h1>
    
    <form method="POST" action="{{ route('cabinet.users.update', $user) }}">
        @csrf
        @method('PUT')
        @include('cabinet.users.partials.form')
    </form>
    
    <a href="{{ route('cabinet.users.index') }}" class="btn-back">← Назад к списку</a>
</div>
@endsection
