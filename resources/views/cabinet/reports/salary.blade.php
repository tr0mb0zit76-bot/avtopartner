@extends('cabinet.layouts.app')

@section('title', 'Отчёт по зарплате')

@section('content')
<div class="report-header">
    <h1>Отчёт по зарплате</h1>
    <div class="report-actions">
        <select id="monthSelect">
            <option value="1">Январь</option>
            <option value="2">Февраль</option>
            <option value="3">Март</option>
            <option value="4">Апрель</option>
            <option value="5">Май</option>
            <option value="6">Июнь</option>
            <option value="7">Июль</option>
            <option value="8">Август</option>
            <option value="9">Сентябрь</option>
            <option value="10">Октябрь</option>
            <option value="11">Ноябрь</option>
            <option value="12">Декабрь</option>
        </select>
        <input type="number" id="yearInput" value="{{ date('Y') }}">
        <button class="btn-generate">Сформировать</button>
        <button class="btn-export">📥 Экспорт</button>
    </div>
</div>

<div class="report-table-container">
    <p class="placeholder">Здесь будет таблица с расчётом зарплаты</p>
</div>
@endsection