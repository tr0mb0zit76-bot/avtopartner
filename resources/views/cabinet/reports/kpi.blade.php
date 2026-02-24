@extends('cabinet.layouts.app')

@section('title', 'KPI менеджеров')

@section('content')
<div class="report-header">
    <h1>KPI менеджеров</h1>
    <div class="report-actions">
        <select id="periodSelect">
            <option value="month">За месяц</option>
            <option value="quarter">За квартал</option>
            <option value="year">За год</option>
        </select>
        <button class="btn-generate">Сформировать</button>
        <button class="btn-export">📥 Экспорт</button>
    </div>
</div>

<div class="report-table-container">
    <p class="placeholder">Здесь будет таблица с KPI</p>
</div>
@endsection