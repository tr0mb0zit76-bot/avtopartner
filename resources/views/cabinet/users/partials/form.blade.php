@csrf

<div class="form-group">
    <label for="name">Имя</label>
    <input type="text" name="name" id="name" value="{{ old('name', $user->name ?? '') }}" required>
    @error('name')
        <div class="error">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label for="email">Email</label>
    <input type="email" name="email" id="email" value="{{ old('email', $user->email ?? '') }}" required>
    @error('email')
        <div class="error">{{ $message }}</div>
    @enderror
</div>

@if(!isset($user))
<div class="form-group">
    <label for="password">Пароль</label>
    <input type="password" name="password" id="password" required>
    @error('password')
        <div class="error">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label for="password_confirmation">Подтверждение пароля</label>
    <input type="password" name="password_confirmation" id="password_confirmation" required>
</div>
@else
<div class="form-group">
    <label for="password">Новый пароль (оставьте пустым, если не меняете)</label>
    <input type="password" name="password" id="password">
    @error('password')
        <div class="error">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label for="password_confirmation">Подтверждение нового пароля</label>
    <input type="password" name="password_confirmation" id="password_confirmation">
</div>
@endif

<div class="form-group">
    <label for="role_id">Роль</label>
    <select name="role_id" id="role_id" required>
        <option value="">Выберите роль</option>
        @foreach($roles as $role)
            <option value="{{ $role->id }}" {{ old('role_id', $user->role_id ?? '') == $role->id ? 'selected' : '' }}>
                {{ $role->display_name }}
            </option>
        @endforeach
    </select>
    @error('role_id')
        <div class="error">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label for="site_id">Сайт</label>
    <select name="site_id" id="site_id">
        <option value="">Все сайты</option>
        @foreach($sites as $site)
            <option value="{{ $site->id }}" {{ old('site_id', $user->site_id ?? '') == $site->id ? 'selected' : '' }}>
                {{ $site->name }}
            </option>
        @endforeach
    </select>
    @error('site_id')
        <div class="error">{{ $message }}</div>
    @enderror
</div>

<div class="form-group-checkbox">
    <label>
        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $user->is_active ?? true) ? 'checked' : '' }}>
        Активен
    </label>
</div>

<button type="submit" class="btn-submit">Сохранить</button>