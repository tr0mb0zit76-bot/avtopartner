@extends('cabinet.layouts.app')

@section('title', 'Отчёты')

@section('content')
<div class="reports-header">
    <h1>Отчёты и аналитика</h1>
</div>

<div class="reports-grid">
    <div class="report-card">
        <h3>📊 Отчёт по зарплате</h3>
        <p>Расчёт зарплаты менеджеров за период</p>
        <a href="{{ route('cabinet.reports.salary') }}" class="btn-report">Открыть</a>
    </div>
    
    <div class="report-card">
        <h3>📈 KPI менеджеров</h3>
        <p>Анализ эффективности по KPI</p>
        <a href="{{ route('cabinet.reports.kpi') }}" class="btn-report">Открыть</a>
    </div>
    
    <div class="report-card">
        <h3>🚛 Заявки по менеджерам</h3>
        <p>Распределение заявок и дельты</p>
        <a href="#" class="btn-report">Открыть</a>
    </div>
    
    <div class="report-card">
        <h3>💰 Финансовый отчёт</h3>
        <p>Доходы, расходы, дельта</p>
        <a href="#" class="btn-report">Открыть</a>
    </div>
</div>
@endsection