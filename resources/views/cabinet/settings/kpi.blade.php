@extends('cabinet.layouts.app')

@section('title', 'Данные для реестра')

@section('content')
<div class="settings-header">
    <h1>Данные для реестра</h1>
    <p class="settings-description">Настройка порогов KPI и коэффициентов зарплаты</p>
</div>

@if(session('success'))
    <div class="alert-success">{{ session('success') }}</div>
@endif

@if(session('error'))
    <div class="alert-error">{{ session('error') }}</div>
@endif

<div class="settings-container">
    <form method="POST" action="{{ route('cabinet.settings.kpi.update') }}" id="settingsForm">
        @csrf
        
        <div class="settings-section">
            <h2>Пороги KPI</h2>
            <div class="thresholds-table-wrapper">
                <table class="thresholds-table">
                    <thead>
                        <tr>
                            <th>Порог (соотношение прямых сделок)</th>
                            <th>Прямая сделка (KPI %)</th>
                            <th>Кривая сделка (KPI %)</th>
                        </tr>
                    </thead>
                    <tbody id="thresholds-body">
                        @php
                            $thresholdValues = [0.8, 0.6, 0.5, 0.4, 0.3];
                            $thresholdLabels = [
                                0.8 => '80% и выше',
                                0.6 => '60% - 79%',
                                0.5 => '50% - 59%',
                                0.4 => '40% - 49%',
                                0.3 => '30% - 39%',
                            ];
                        @endphp
                        
                        @foreach($thresholdValues as $index => $value)
                            @php
                                $directThreshold = $directThresholds->where('threshold_from', $value)->first();
                                $indirectThreshold = $indirectThresholds->where('threshold_from', $value)->first();
                            @endphp
                            <tr>
                                <td>
                                    <div class="threshold-range">
                                        <input type="number" 
                                               name="thresholds[{{ $index }}][from]" 
                                               value="{{ $directThreshold->threshold_from ?? $value }}"
                                               class="threshold-input"
                                               step="0.01"
                                               min="0"
                                               max="1"
                                               placeholder="От"
                                               required>
                                        <span class="range-separator">—</span>
                                        <input type="number" 
                                               name="thresholds[{{ $index }}][to]" 
                                               value="{{ $directThreshold->threshold_to ?? ($index == 0 ? 1.0 : $value + 0.19) }}"
                                               class="threshold-input"
                                               step="0.01"
                                               min="0"
                                               max="1"
                                               placeholder="До"
                                               required>
                                        <span class="threshold-label">{{ $thresholdLabels[$value] ?? '' }}</span>
                                    </div>
                                </td>
                                <td>
                                    <input type="number" 
                                           name="direct[{{ $index }}][kpi_percent]" 
                                           value="{{ $directThreshold->kpi_percent ?? 3 + $index }}"
                                           class="kpi-input"
                                           min="0"
                                           max="100"
                                           required>
                                </td>
                                <td>
                                    <input type="number" 
                                           name="indirect[{{ $index }}][kpi_percent]" 
                                           value="{{ $indirectThreshold->kpi_percent ?? 7 + $index }}"
                                           class="kpi-input"
                                           min="0"
                                           max="100"
                                           required>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="settings-section">
            <h2>Коэффициенты зарплаты менеджеров</h2>
            <p class="section-description">Настройка оклада и процента от дельты для каждого менеджера</p>
            
            <div class="salary-table-wrapper">
                <table class="salary-table">
                    <thead>
                        <tr>
                            <th>Менеджер</th>
                            <th>Оклад (руб.)</th>
                            <th>Процент от дельты (%)</th>
                            <th>Действует с</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($managers as $manager)
                            @php
                                $coeff = $salaryCoefficients[$manager->id] ?? null;
                            @endphp
                            <tr>
                                <td>
                                    <strong>{{ $manager->name }}</strong>
                                    <input type="hidden" name="salary[{{ $manager->id }}][manager_id]" value="{{ $manager->id }}">
                                </td>
                                <td>
                                    <input type="number" 
                                           name="salary[{{ $manager->id }}][base_salary]" 
                                           value="{{ $coeff->base_salary ?? 0 }}"
                                           class="salary-input"
                                           min="0"
                                           step="1000"
                                           placeholder="Оклад">
                                </td>
                                <td>
                                    <input type="number" 
                                           name="salary[{{ $manager->id }}][bonus_percent]" 
                                           value="{{ $coeff->bonus_percent ?? 50 }}"
                                           class="salary-input"
                                           min="0"
                                           max="100"
                                           step="0.1"
                                           placeholder="% от дельты">
                                </td>
                                <td>
                                    <input type="date" 
                                           name="salary[{{ $manager->id }}][effective_from]" 
                                           value="{{ $coeff->effective_from ?? now()->format('Y-m-d') }}"
                                           class="date-input">
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn-save">Сохранить изменения</button>
            <a href="{{ route('cabinet.dashboard') }}" class="btn-cancel">Отмена</a>
        </div>
    </form>
</div>

<style>
.settings-header {
    margin-bottom: 2rem;
}

.settings-header h1 {
    color: var(--text-primary);
    font-size: 2rem;
    margin-bottom: 0.5rem;
}

.settings-description {
    color: var(--text-secondary);
    font-size: 1rem;
}

.settings-container {
    max-width: 1200px;
}

.settings-section {
    background: var(--bg-secondary);
    border-radius: 20px;
    border: 1px solid var(--border-color);
    padding: 2rem;
    margin-bottom: 2rem;
}

.settings-section h2 {
    color: var(--text-primary);
    font-size: 1.5rem;
    margin-bottom: 1.5rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid var(--border-color);
}

.section-description {
    color: var(--text-secondary);
    margin-bottom: 1.5rem;
    font-size: 0.95rem;
}

.thresholds-table,
.salary-table {
    width: 100%;
    border-collapse: collapse;
}

.thresholds-table th,
.salary-table th {
    text-align: left;
    padding: 1rem;
    background: var(--bg-primary);
    color: var(--text-primary);
    font-weight: 600;
    border-bottom: 2px solid var(--border-color);
}

.thresholds-table td,
.salary-table td {
    padding: 1rem;
    border-bottom: 1px solid var(--border-color);
    vertical-align: middle;
}

.threshold-range {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.threshold-input,
.salary-input,
.date-input {
    width: 100px;
    padding: 0.5rem;
    border: 2px solid var(--border-color);
    border-radius: 8px;
    background: var(--bg-primary);
    color: var(--text-primary);
    text-align: center;
    font-size: 0.9rem;
}

.salary-input {
    width: 120px;
}

.date-input {
    width: 140px;
}

.threshold-input:focus,
.salary-input:focus,
.date-input:focus {
    outline: none;
    border-color: var(--accent-color);
}

.range-separator {
    color: var(--text-secondary);
    font-weight: bold;
}

.threshold-label {
    color: var(--text-secondary);
    font-size: 0.9rem;
    margin-left: 0.5rem;
}

.kpi-input {
    width: 80px;
    padding: 0.5rem;
    border: 2px solid var(--border-color);
    border-radius: 8px;
    background: var(--bg-primary);
    color: var(--text-primary);
    text-align: center;
    font-size: 1rem;
}

.kpi-input:focus {
    outline: none;
    border-color: var(--accent-color);
}

.form-actions {
    margin-top: 2rem;
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

.btn-save {
    background: var(--accent-color);
    color: white;
    padding: 0.75rem 2rem;
    border: none;
    border-radius: 10px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-save:hover {
    background: var(--accent-color-dark, #a06a3d);
    transform: translateY(-2px);
}

.btn-cancel {
    background: var(--bg-secondary);
    color: var(--text-primary);
    padding: 0.75rem 2rem;
    border: 1px solid var(--border-color);
    border-radius: 10px;
    text-decoration: none;
    font-size: 1rem;
    transition: all 0.2s;
}

.btn-cancel:hover {
    background: var(--border-color);
}

.alert-success {
    background: #d4edda;
    color: #155724;
    padding: 1rem;
    border-radius: 10px;
    margin-bottom: 1rem;
}

.alert-error {
    background: #f8d7da;
    color: #721c24;
    padding: 1rem;
    border-radius: 10px;
    margin-bottom: 1rem;
}
</style>
@endsection