@extends('cabinet.layouts.app')

@section('title', 'Заявки')

@section('content')
<!-- Подменю модуля заявок -->
<div class="module-submenu">
    <a href="{{ route('cabinet.orders.index') }}" class="submenu-item {{ request()->routeIs('cabinet.orders.index') ? 'active' : '' }}">
        📋 Все заявки
    </a>
    @if(auth()->user()->hasPermission('orders.view_all'))
        <a href="#" class="submenu-item" onclick="alert('В разработке')">
            📊 Сводный реестр
        </a>
    @endif
    <a href="#" class="submenu-item" onclick="alert('В разработке')">
        📅 Календарь оплат
    </a>
</div>

<div class="orders-header">
    <h1>Заявки</h1>
    <div class="orders-actions">
        <button class="btn-create" onclick="createNewOrder()">➕ Новая заявка</button>
        <button class="btn-export" onclick="exportToExcel()">📥 Экспорт</button>
    </div>
</div>

<div class="filters-bar">
    <input type="date" id="filterDateFrom" placeholder="Дата с">
    <input type="date" id="filterDateTo" placeholder="Дата по">
    <select id="filterStatus">
        <option value="">Все статусы</option>
        <option value="new">Новая</option>
        <option value="in_progress">В работе</option>
        <option value="completed">Завершена</option>
    </select>
    <select id="filterManager">
        <option value="">Все менеджеры</option>
        @foreach($managers ?? [] as $manager)
            <option value="{{ $manager->id }}">{{ $manager->name }}</option>
        @endforeach
    </select>
    <button class="btn-filter" onclick="applyFilters()">Применить</button>
</div>

<div class="orders-table-container">
    <div id="orders-handsontable" style="width: 100%; height: 100%;"></div>
</div>

@push('scripts')
<script>
    let hot;
    let ordersData = @json($orders ?? []);
    
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(initHandsontable, 100);
    });
    
    function initHandsontable() {
        const container = document.getElementById('orders-handsontable');
        
        if (!container) {
            console.error('Container not found');
            return;
        }
        
        hot = new Handsontable(container, {
            data: ordersData,
            rowHeaders: true,
            colHeaders: true,
            height: '100%',
            width: '100%',
            licenseKey: 'non-commercial-and-evaluation',
            stretchH: 'none',
            autoWrapRow: true,
            autoWrapCol: true,
            
            columns: [
                // Заявка
                { data: 'order_number', title: 'ID', readOnly: true },
                { data: 'company_code', title: 'Наша компания', type: 'dropdown', source: ['ЛР', 'АП', 'КВ'] },
                { data: 'manager_name', title: 'Менеджер', readOnly: true },
                { data: 'order_date', title: 'Дата заявки', type: 'date', dateFormat: 'YYYY-MM-DD', correctFormat: true },
                
                // Маршрут / груз
                { data: 'loading_point', title: 'Загрузка' },
                { data: 'unloading_point', title: 'Выгрузка' },
                { data: 'cargo_description', title: 'Груз' },
                { data: 'loading_date', title: 'Дата погрузки', type: 'date', dateFormat: 'YYYY-MM-DD', correctFormat: true },
                { data: 'unloading_date', title: 'Дата выгрузки', type: 'date', dateFormat: 'YYYY-MM-DD', correctFormat: true },
                
                // Финансы заказчика
                { data: 'customer_rate', title: 'Ставка заказчика', type: 'numeric', numericFormat: { pattern: '0,0.00' } },
                { data: 'customer_payment_form', title: 'Форма оплаты (зак)', type: 'dropdown', source: ['с НДС', 'без НДС'] },
                { data: 'customer_payment_term', title: 'Условия оплаты (зак)' }, // Убрали dropdown, теперь текстовое поле
                
                // Финансы перевозчика
                { data: 'carrier_rate', title: 'Ставка перевозчика', type: 'numeric', numericFormat: { pattern: '0,0.00' } },
                { data: 'carrier_payment_form', title: 'Форма оплаты (пер)', type: 'dropdown', source: ['с НДС', 'без НДС'] },
                { data: 'carrier_payment_term', title: 'Условия оплаты (пер)' }, // Убрали dropdown, теперь текстовое поле
                
                // Расходы
                { data: 'additional_expenses', title: 'Доп расходы', type: 'numeric', numericFormat: { pattern: '0,0.00' } },
                { data: 'insurance', title: 'Страховка', type: 'numeric', numericFormat: { pattern: '0,0.00' } },
                { data: 'bonus', title: 'Бонус', type: 'numeric', numericFormat: { pattern: '0,0.00' } },
                
                // KPI и дельта
                { data: 'kpi_percent', title: 'KPI %', type: 'numeric', numericFormat: { pattern: '0.00' }, readOnly: true },
                { data: 'delta', title: 'Дельта', type: 'numeric', numericFormat: { pattern: '0,0.00' }, readOnly: true },
                
                // ОПЛАТА
                { data: 'prepayment_customer', title: 'Предоплата заказчик', type: 'numeric', numericFormat: { pattern: '0,0.00' } },
                { data: 'prepayment_date', title: 'Дата предоплаты', type: 'date', dateFormat: 'YYYY-MM-DD', correctFormat: true },
                { data: 'prepayment_status', title: 'Статус предоплаты' },
                { data: 'prepayment_carrier', title: 'Предоплата перевозчик', type: 'numeric', numericFormat: { pattern: '0,0.00' } },
                { data: 'prepayment_carrier_date', title: 'Дата предоплаты (пер)', type: 'date', dateFormat: 'YYYY-MM-DD', correctFormat: true },
                { data: 'prepayment_carrier_status', title: 'Статус предоплаты (пер)' },
                { data: 'final_customer', title: 'Окончательный расчёт (зак)', type: 'numeric', numericFormat: { pattern: '0,0.00' } },
                { data: 'final_customer_date', title: 'Дата окончательного расчёта', type: 'date', dateFormat: 'YYYY-MM-DD', correctFormat: true },
                { data: 'final_customer_status', title: 'Статус окончательного расчёта' },
                { data: 'final_carrier', title: 'Окончательный расчёт (пер)', type: 'numeric', numericFormat: { pattern: '0,0.00' } },
                { data: 'final_carrier_date', title: 'Дата окончательного расчёта (пер)', type: 'date', dateFormat: 'YYYY-MM-DD', correctFormat: true },
                { data: 'final_carrier_status', title: 'Статус окончательного расчёта (пер)' },
                
                // Контрагенты
                { data: 'customer_name', title: 'Заказчик' },
                { data: 'customer_contact', title: 'Контакт заказчика' },
                { data: 'carrier_name', title: 'Перевозчик' },
                { data: 'carrier_contact', title: 'Контакт перевозчика' },
                { data: 'driver_name', title: 'Водитель' },
                { data: 'driver_phone', title: 'Телефон водителя' },
                
                // Зарплата
                { data: 'salary_accrued', title: 'Начислено', type: 'numeric', numericFormat: { pattern: '0,0.00' }, readOnly: true },
                { data: 'salary_paid', title: 'Выплачено', type: 'numeric', numericFormat: { pattern: '0,0.00' } },
                
                // Документы - теперь все текстовые поля для ссылок
                { data: 'track_number_customer', title: 'Трэк-номер заказ' },
                { data: 'track_status_customer', title: 'Статус' },
                { data: 'track_number_carrier', title: 'Трэк-номер перевоз' },
                { data: 'track_status_carrier', title: 'Статус' },
                { data: 'invoice_number', title: '№ счёта' },
                { data: 'upd_number', title: '№ УПД' },
                { data: 'upd_customer_status', title: 'УПД заказчик' }, // Для ссылок на Яндекс.Диск
                { data: 'order_customer_status', title: 'Заявка заказчик' }, // Для ссылок на Яндекс.Диск
                { data: 'waybill_number', title: 'ТН' }, // Для ссылок на Яндекс.Диск
                { data: 'upd_carrier_status', title: 'УПД перевозчик' }, // Для ссылок на Яндекс.Диск
                { data: 'order_carrier_status', title: 'Заявка перевозчик' } // Для ссылок на Яндекс.Диск
            ],
            
            nestedHeaders: [
                [
                    { label: 'ЗАЯВКА', colspan: 4 },
                    { label: 'МАРШРУТ / ГРУЗ', colspan: 5 },
                    { label: 'ФИНАНСЫ ЗАКАЗЧИКА', colspan: 3 },
                    { label: 'ФИНАНСЫ ПЕРЕВОЗЧИКА', colspan: 3 },
                    { label: 'РАСХОДЫ', colspan: 3 },
                    { label: 'KPI', colspan: 2 },
                    { label: 'ОПЛАТА', colspan: 11 },
                    { label: 'КОНТРАГЕНТЫ', colspan: 6 },
                    { label: 'ЗАРПЛАТА', colspan: 2 },
                    { label: 'ДОКУМЕНТЫ', colspan: 11 }
                ],
                [
                    'ID', 'Наша компания', 'Менеджер', 'Дата заявки',
                    'Загрузка', 'Выгрузка', 'Груз', 'Дата погрузки', 'Дата выгрузки',
                    'Ставка', 'Форма оплаты', 'Условия оплаты',
                    'Ставка', 'Форма оплаты', 'Условия оплаты',
                    'Доп расходы', 'Страховка', 'Бонус',
                    'KPI %', 'Дельта',
                    'Заказчик', 'Дата', 'Статус', 'Перевозчик', 'Дата', 'Статус', 'Заказчик', 'Дата', 'Статус', 'Перевозчик', 'Дата', 'Статус',
                    'Заказчик', 'Контакт', 'Перевозчик', 'Контакт', 'Водитель', 'Телефон',
                    'Начислено', 'Выплачено',
                    'Трэк-номер заказ', 'Статус', 'Трэк-номер перевоз', 'Статус', '№ счёта', '№ УПД', 'УПД заказчик', 'Заявка заказчик', 'ТН', 'УПД перевозчик', 'Заявка перевозчик'
                ]
            ],
            
            contextMenu: {
                items: {
                    'row_above': { name: 'Вставить строку выше' },
                    'row_below': { name: 'Вставить строку ниже' },
                    'remove_row': {
                        name: 'Удалить строку',
                        callback: function(key, selection, clickEvent) {
                            const rows = selection[0].start.row;
                            const rowData = hot.getSourceDataAtRow(rows);
                            
                            if (confirm(`Удалить заявку ${rowData.order_number}?`)) {
                                if (rowData.id) {
                                    fetch(`/cabinet/orders/${rowData.id}`, {
                                        method: 'DELETE',
                                        headers: {
                                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                                        }
                                    })
                                    .then(response => response.json())
                                    .then(data => {
                                        if (data.success) {
                                            hot.alter('remove_row', rows, 1);
                                            showNotification('Заявка удалена', 'success');
                                        }
                                    });
                                } else {
                                    hot.alter('remove_row', rows, 1);
                                }
                            }
                        }
                    },
                    'separator': Handsontable.plugins.ContextMenu.SEPARATOR,
                    'copy': { name: 'Копировать' },
                    'cut': { name: 'Вырезать' },
                    'paste': { name: 'Вставить' }
                }
            },
            
            afterChange: function(changes, source) {
                if (source === 'edit' && changes) {
                    const rowIndex = changes[0][0];
                    calculateKPI(rowIndex);
                    saveRow(rowIndex);
                }
            }
        });
    }
    
    function calculateKPI(rowIndex) {
        const rowData = hot.getSourceDataAtRow(rowIndex);
        
        const customerRate = parseFloat(rowData.customer_rate) || 0;
        const carrierRate = parseFloat(rowData.carrier_rate) || 0;
        const additional = parseFloat(rowData.additional_expenses) || 0;
        const insurance = parseFloat(rowData.insurance) || 0;
        const bonus = parseFloat(rowData.bonus) || 0;
        
        const totalExpenses = carrierRate + additional + insurance + bonus;
        const delta = customerRate - totalExpenses;
        const kpiPercent = 5;
        const salaryAccrued = delta * (kpiPercent / 100);
        
        const updatedData = {
            ...rowData,
            delta: delta,
            kpi_percent: kpiPercent,
            salary_accrued: salaryAccrued
        };
        
        hot.setSourceDataAtRow(rowIndex, updatedData);
    }
    
    function saveRow(rowIndex) {
        const rowData = hot.getSourceDataAtRow(rowIndex);
        
        fetch('/cabinet/orders/save', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ data: rowData })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Сохранено', 'success');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Ошибка соединения', 'error');
        });
    }
    
    function createNewOrder() {
        fetch('/cabinet/orders/create', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ company_code: 'ЛР' })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const newRow = {
                    order_number: data.order.order_number,
                    company_code: 'ЛР',
                    manager_name: '{{ auth()->user()->name }}',
                    order_date: new Date().toISOString().split('T')[0],
                    additional_expenses: 0,
                    insurance: 0,
                    bonus: 0,
                    customer_rate: 0,
                    carrier_rate: 0,
                    delta: 0,
                    kpi_percent: 0,
                    salary_accrued: 0,
                    salary_paid: 0
                };
                
                const currentData = hot.getSourceData();
                currentData.unshift(newRow);
                hot.loadData(currentData);
                hot.selectCell(0, 0);
                
                showNotification('Новая заявка создана: ' + data.order.order_number, 'success');
            }
        });
    }
    
    function applyFilters() {
        const dateFrom = document.getElementById('filterDateFrom').value;
        const dateTo = document.getElementById('filterDateTo').value;
        const status = document.getElementById('filterStatus').value;
        const manager = document.getElementById('filterManager').value;
        
        let url = '/cabinet/orders/filter?';
        if (dateFrom) url += `date_from=${dateFrom}&`;
        if (dateTo) url += `date_to=${dateTo}&`;
        if (status) url += `status=${status}&`;
        if (manager) url += `manager=${manager}`;
        
        fetch(url)
            .then(response => response.json())
            .then(data => {
                hot.loadData(data);
                showNotification('Фильтр применён', 'success');
            });
    }
    
    function exportToExcel() {
        const data = hot.getSourceData();
        const headers = hot.getColHeader();
        
        let csv = headers.join(';') + '\n';
        data.forEach(row => {
            const values = Object.values(row).map(val => {
                if (val && typeof val === 'string' && val.includes(';')) {
                    return `"${val}"`;
                }
                return val || '';
            });
            csv += values.join(';') + '\n';
        });
        
        const blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = 'orders_' + new Date().toISOString().slice(0,10) + '.csv';
        link.click();
        
        showNotification('Экспорт завершён', 'success');
    }
    
    function showNotification(message, type) {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.textContent = message;
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            background: ${type === 'success' ? '#4CAF50' : '#f44336'};
            color: white;
            border-radius: 5px;
            z-index: 9999;
            animation: slideIn 0.3s ease;
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => {
                if (document.body.contains(notification)) {
                    document.body.removeChild(notification);
                }
            }, 300);
        }, 3000);
    }
    
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        @keyframes slideOut {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }
    `;
    document.head.appendChild(style);
</script>
@endpush
@endsection