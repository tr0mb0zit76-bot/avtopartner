@extends('cabinet.layouts.app')

@section('title', 'Управление пользователями')

@section('content')
<div class="users-header">
    <h1>Пользователи</h1>
    @if(auth()->user()->isAdmin())
        <a href="{{ route('cabinet.users.create') }}" class="btn-create">➕ Добавить пользователя</a>
    @endif
</div>

@if(session('success'))
    <div class="alert-success">{{ session('success') }}</div>
@endif

@if(session('error'))
    <div class="alert-error">{{ session('error') }}</div>
@endif

<div class="users-table">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Имя</th>
                <th>Email</th>
                <th>Роль</th>
                <th>Сайт</th>
                <th>Статус</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
            @foreach($users as $user)
                <tr>
                    <td>{{ $user->id }}</td>
                    <td>{{ $user->name }}</td>
                    <td>{{ $user->email }}</td>
                    <td>{{ $user->role->display_name ?? '—' }}</td>
                    <td>{{ $user->site->name ?? 'Все' }}</td>
                    <td>
                        @if($user->is_active)
                            <span class="badge-active">Активен</span>
                        @else
                            <span class="badge-inactive">Неактивен</span>
                        @endif
                    </td>
                    <td class="actions">
                        @if(auth()->user()->isAdmin())
                            <a href="{{ route('cabinet.users.edit', $user) }}" class="btn-edit" title="Редактировать">
                                ✏️
                            </a>
                            @if(auth()->id() !== $user->id)
                                <form method="POST" action="{{ route('cabinet.users.destroy', $user) }}" style="display:inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-delete" title="Удалить" onclick="return confirm('Удалить пользователя {{ $user->name }}?')">
                                        🗑️
                                    </button>
                                </form>
                            @endif
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
    
    @if(method_exists($users, 'links'))
        <div class="pagination">
            {{ $users->links() }}
        </div>
    @endif
</div>
@endsection