@extends('cabinet.layouts.app')

@section('title', 'Заявки')

@section('content')
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
        @if(auth()->user()->isAdmin() || auth()->user()->isSupervisor() || auth()->user()->isManager())
            <button class="btn-create" id="createOrderBtn">➕ Новая заявка</button>
        @endif
        <button class="btn-export" id="exportBtn">📥 Экспорт</button>
    </div>
</div>

<div class="filters-bar">
    <input type="date" id="filterDateFrom" placeholder="Дата с">
    <input type="date" id="filterDateTo" placeholder="Дата по">
    <select id="filterStatus">
        <option value="">Все статусы</option>
        <option value="new">Новая</option>
        <option value="in_progress">Выполняется</option>
        <option value="awaiting_docs">Документы</option>
        <option value="awaiting_payment">Оплата</option>
        <option value="completed">Завершена</option>
    </select>
    
    @if(auth()->user()->isAdmin() || auth()->user()->isSupervisor() || auth()->user()->isDispatcher())
        <select id="filterManager">
            <option value="">Все менеджеры</option>
            @foreach($managers ?? [] as $manager)
                <option value="{{ $manager->id }}">{{ $manager->name }}</option>
            @endforeach
        </select>
    @endif
    
    <button class="btn-filter" id="filterBtn">Применить</button>
</div>

<div class="orders-table-container">
    <div id="orders-handsontable" style="width: 100%; height: 100%;"></div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    window.ordersData = @json($orders ?? []);
    window.currentUserId = {{ auth()->id() ?? 0 }};
    window.currentUserName = '{{ auth()->user()->name ?? "Пользователь" }}';
    window.currentUserRole = '{{ auth()->user()->role->name ?? "user" }}';
    window.isAdminOrSupervisor = {{ auth()->user()->isAdmin() || auth()->user()->isSupervisor() ? 'true' : 'false' }};
    window.isManager = {{ auth()->user()->isManager() ? 'true' : 'false' }};
    window.isDispatcher = {{ auth()->user()->isDispatcher() ? 'true' : 'false' }};
    
    formatAllDates(window.ordersData);
    
    setTimeout(initHandsontable, 100);
    
    document.getElementById('createOrderBtn')?.addEventListener('click', createNewOrder);
    document.getElementById('exportBtn')?.addEventListener('click', exportToExcel);
    document.getElementById('filterBtn')?.addEventListener('click', applyFilters);
});

let hot;

function formatAllDates(data) {
    data.forEach(row => {
        if (row.order_date) row.order_date = formatDateForInput(row.order_date);
        if (row.loading_date) row.loading_date = formatDateForInput(row.loading_date);
        if (row.unloading_date) row.unloading_date = formatDateForInput(row.unloading_date);
        if (row.prepayment_date) row.prepayment_date = formatDateForInput(row.prepayment_date);
        if (row.prepayment_carrier_date) row.prepayment_carrier_date = formatDateForInput(row.prepayment_carrier_date);
        if (row.final_customer_date) row.final_customer_date = formatDateForInput(row.final_customer_date);
        if (row.final_carrier_date) row.final_carrier_date = formatDateForInput(row.final_carrier_date);
        if (row.track_status_customer) row.track_status_customer = formatDateForInput(row.track_status_customer);
        if (row.track_status_carrier) row.track_status_carrier = formatDateForInput(row.track_status_carrier);
    });
    return data;
}

function formatDateForInput(dateStr) {
    if (!dateStr) return null;
    if (typeof dateStr === 'string' && dateStr.match(/^\d{4}-\d{2}-\d{2}$/)) {
        return dateStr;
    }
    try {
        const date = new Date(dateStr);
        if (!isNaN(date.getTime())) {
            return date.toISOString().split('T')[0];
        }
    } catch (e) {}
    return null;
}

function dateRenderer(instance, td, row, col, prop, value, cellProperties) {
    Handsontable.renderers.TextRenderer.apply(this, arguments);
    if (value) {
        if (typeof value === 'string' && value.match(/^\d{4}-\d{2}-\d{2}$/)) {
            const parts = value.split('-');
            td.innerHTML = parts[2] + '-' + parts[1] + '-' + parts[0];
        } else {
            td.innerHTML = value;
        }
    }
    return td;
}

function statusIconRenderer(instance, td, row, col, prop, value, cellProperties) {
    Handsontable.renderers.TextRenderer.apply(this, arguments);
    
    if (!value) {
        td.innerHTML = '<span style="font-size: 1.3rem;">⏳</span>';
        return td;
    }
    
    td.innerHTML = '<span style="font-size: 1.3rem;">' + value + '</span>';
    return td;
}

function conditionalRenderer(instance, td, row, col, prop, value, cellProperties) {
    Handsontable.renderers.TextRenderer.apply(this, arguments);
    
    const rowData = instance.getSourceDataAtRow(row);
    
    const hasOttn = (rowData.customer_payment_term && rowData.customer_payment_term.includes('оттн')) ||
                    (rowData.carrier_payment_term && rowData.carrier_payment_term.includes('оттн'));
    
    if (hasOttn && (prop === 'track_status_customer' || prop === 'track_status_carrier')) {
        td.style.backgroundColor = '#fff0f0';
        td.style.border = '1px solid #ff9999';
    }
    
    return td;
}

function initHandsontable() {
    const container = document.getElementById('orders-handsontable');
    if (!container) return;
    
    let columns = [];
    let nestedHeaders = [];
    
    if (window.isDispatcher) {
        columns = [
            { data: 'order_number', title: 'ID', readOnly: false },
            { data: 'status_icon', title: 'Статус', renderer: statusIconRenderer, readOnly: true },
            { data: 'company_code', title: 'Наша компания', readOnly: true },
            { data: 'manager_name', title: 'Менеджер', readOnly: true },
            { data: 'order_date', title: 'Дата заявки', type: 'date', dateFormat: 'YYYY-MM-DD', renderer: dateRenderer, correctFormat: true, readOnly: true },
            
            { data: 'loading_point', title: 'Загрузка', readOnly: true },
            { data: 'unloading_point', title: 'Выгрузка', readOnly: true },
            { data: 'cargo_description', title: 'Груз', readOnly: true },
            { data: 'loading_date', title: 'Дата погрузки', type: 'date', dateFormat: 'YYYY-MM-DD', renderer: dateRenderer, correctFormat: true, readOnly: true },
            { data: 'unloading_date', title: 'Дата выгрузки', type: 'date', dateFormat: 'YYYY-MM-DD', renderer: dateRenderer, correctFormat: true, readOnly: true },
            
            { data: 'customer_name', title: 'Заказчик', readOnly: true },
            { data: 'carrier_name', title: 'Перевозчик', readOnly: true },
            { data: 'driver_name', title: 'Водитель', readOnly: true },
            { data: 'driver_phone', title: 'Телефон', readOnly: true },
            
            { data: 'track_number_customer', title: 'Трэк-номер заказ' },
            { data: 'track_status_customer', title: 'Получено', renderer: conditionalRenderer },
            { data: 'track_number_carrier', title: 'Трэк-номер перевоз' },
            { data: 'track_status_carrier', title: 'Получено', renderer: conditionalRenderer },
            { data: 'invoice_number', title: '№ счёта' },
            { data: 'upd_number', title: '№ УПД' },
            { data: 'upd_customer_status', title: 'УПД заказчик' },
            { data: 'order_customer_status', title: 'Заявка заказчик' },
            { data: 'waybill_number', title: 'ТН' },
            { data: 'upd_carrier_status', title: 'УПД перевозчик' },
            { data: 'order_carrier_status', title: 'Заявка перевозчик' }
        ];
        
        nestedHeaders = [
            [
                { label: 'ЗАЯВКА', colspan: 5 },
                { label: 'МАРШРУТ', colspan: 5 },
                { label: 'КОНТРАГЕНТЫ', colspan: 4 },
                { label: 'ДОКУМЕНТЫ', colspan: 11 }
            ],
            [
                'ID', 'Статус', 'Наша компания', 'Менеджер', 'Дата заявки',
                'Загрузка', 'Выгрузка', 'Груз', 'Дата погрузки', 'Дата выгрузки',
                'Заказчик', 'Перевозчик', 'Водитель', 'Телефон',
                'Трэк-номер заказ', 'Получено', 'Трэк-номер перевоз', 'Получено', '№ счёта', '№ УПД', 'УПД заказчик', 'Заявка заказчик', 'ТН', 'УПД перевозчик', 'Заявка перевозчик'
            ]
        ];
    } else {
        columns = [
            { 
                data: 'order_number', 
                title: 'ID', 
                readOnly: !window.isAdminOrSupervisor && !window.isManager
            },
            { data: 'status_icon', title: 'Статус', renderer: statusIconRenderer, readOnly: true },
            { data: 'company_code', title: 'Наша компания', type: 'dropdown', source: ['ЛР', 'АП', 'КВ'] },
            { data: 'manager_name', title: 'Менеджер', readOnly: true },
            { data: 'order_date', title: 'Дата заявки', type: 'date', dateFormat: 'YYYY-MM-DD', renderer: dateRenderer, correctFormat: true },
            
            { data: 'loading_point', title: 'Загрузка' },
            { data: 'unloading_point', title: 'Выгрузка' },
            { data: 'cargo_description', title: 'Груз' },
            { data: 'loading_date', title: 'Дата погрузки', type: 'date', dateFormat: 'YYYY-MM-DD', renderer: dateRenderer, correctFormat: true },
            { data: 'unloading_date', title: 'Дата выгрузки', type: 'date', dateFormat: 'YYYY-MM-DD', renderer: dateRenderer, correctFormat: true },
            
            { data: 'customer_rate', title: 'Ставка заказчика', type: 'numeric', numericFormat: { pattern: '0,0.00' } },
            { data: 'customer_payment_form', title: 'Форма оплаты (зак)', type: 'dropdown', source: ['с НДС', 'без НДС'] },
            { data: 'customer_payment_term', title: 'Условия оплаты (зак)' },
            
            { data: 'carrier_rate', title: 'Ставка перевозчика', type: 'numeric', numericFormat: { pattern: '0,0.00' } },
            { data: 'carrier_payment_form', title: 'Форма оплаты (пер)', type: 'dropdown', source: ['с НДС', 'без НДС'] },
            { data: 'carrier_payment_term', title: 'Условия оплаты (пер)' },
            
            { data: 'additional_expenses', title: 'Доп расходы', type: 'numeric', numericFormat: { pattern: '0,0.00' } },
            { data: 'insurance', title: 'Страховка', type: 'numeric', numericFormat: { pattern: '0,0.00' } },
            { data: 'bonus', title: 'Бонус', type: 'numeric', numericFormat: { pattern: '0,0.00' } },
            
            { data: 'kpi_percent', title: 'KPI %', type: 'numeric', readOnly: true },
            { data: 'delta', title: 'Дельта', type: 'numeric', readOnly: true, numericFormat: { pattern: '0,0.00' } },
            
            { data: 'prepayment_customer', title: 'Предоплата заказчик', type: 'numeric', numericFormat: { pattern: '0,0.00' } },
            { data: 'prepayment_date', title: 'Дата предоплаты', type: 'date', dateFormat: 'YYYY-MM-DD', renderer: dateRenderer, correctFormat: true },
            { data: 'prepayment_status', title: 'Статус' },
            { data: 'prepayment_carrier', title: 'Предоплата перевозчик', type: 'numeric', numericFormat: { pattern: '0,0.00' } },
            { data: 'prepayment_carrier_date', title: 'Дата предоплаты (пер)', type: 'date', dateFormat: 'YYYY-MM-DD', renderer: dateRenderer, correctFormat: true },
            { data: 'prepayment_carrier_status', title: 'Статус' },
            
            { data: 'final_customer', title: 'Постоплата заказчик', type: 'numeric', numericFormat: { pattern: '0,0.00' } },
            { data: 'final_customer_date', title: 'Дата постоплаты', type: 'date', dateFormat: 'YYYY-MM-DD', renderer: dateRenderer, correctFormat: true },
            { data: 'final_customer_status', title: 'Статус' },
            { data: 'final_carrier', title: 'Постоплата перевозчик', type: 'numeric', numericFormat: { pattern: '0,0.00' } },
            { data: 'final_carrier_date', title: 'Дата постоплаты (пер)', type: 'date', dateFormat: 'YYYY-MM-DD', renderer: dateRenderer, correctFormat: true },
            { data: 'final_carrier_status', title: 'Статус' },
            
            { data: 'customer_name', title: 'Заказчик' },
            { data: 'customer_contact', title: 'Контакт' },
            { data: 'carrier_name', title: 'Перевозчик' },
            { data: 'carrier_contact', title: 'Контакт' },
            { data: 'driver_name', title: 'Водитель' },
            { data: 'driver_phone', title: 'Телефон' },
            
            { data: 'salary_accrued', title: 'Начислено', type: 'numeric', readOnly: true, numericFormat: { pattern: '0,0.00' } },
            { data: 'salary_paid', title: 'Выплачено', type: 'numeric', numericFormat: { pattern: '0,0.00' } },
            
            { data: 'track_number_customer', title: 'Трэк-номер заказ' },
            { data: 'track_status_customer', title: 'Получено', renderer: conditionalRenderer },
            { data: 'track_number_carrier', title: 'Трэк-номер перевоз' },
            { data: 'track_status_carrier', title: 'Получено', renderer: conditionalRenderer },
            { data: 'invoice_number', title: '№ счёта' },
            { data: 'upd_number', title: '№ УПД' },
            { data: 'upd_customer_status', title: 'УПД заказчик' },
            { data: 'order_customer_status', title: 'Заявка заказчик' },
            { data: 'waybill_number', title: 'ТН' },
            { data: 'upd_carrier_status', title: 'УПД перевозчик' },
            { data: 'order_carrier_status', title: 'Заявка перевозчик' }
        ];
        
        nestedHeaders = [
            [
                { label: 'ЗАЯВКА', colspan: 5 },
                { label: 'МАРШРУТ / ГРУЗ', colspan: 5 },
                { label: 'ФИНАНСЫ ЗАКАЗЧИКА', colspan: 3 },
                { label: 'ФИНАНСЫ ПЕРЕВОЗЧИКА', colspan: 3 },
                { label: 'РАСХОДЫ', colspan: 3 },
                { label: 'KPI', colspan: 2 },
                { label: 'ПРЕДОПЛАТА', colspan: 6 },
                { label: 'ПОСТОПЛАТА', colspan: 6 },
                { label: 'КОНТРАГЕНТЫ', colspan: 6 },
                { label: 'ЗАРПЛАТА', colspan: 2 },
                { label: 'ДОКУМЕНТЫ', colspan: 11 }
            ],
            [
                'ID', 'Статус', 'Наша компания', 'Менеджер', 'Дата заявки',
                'Загрузка', 'Выгрузка', 'Груз', 'Дата погрузки', 'Дата выгрузки',
                'Ставка', 'Форма оплаты', 'Условия оплаты',
                'Ставка', 'Форма оплаты', 'Условия оплаты',
                'Доп расходы', 'Страховка', 'Бонус',
                'KPI %', 'Дельта',
                'Заказчик', 'Дата', 'Статус', 'Перевозчик', 'Дата', 'Статус',
                'Заказчик', 'Дата', 'Статус', 'Перевозчик', 'Дата', 'Статус',
                'Заказчик', 'Контакт', 'Перевозчик', 'Контакт', 'Водитель', 'Телефон',
                'Начислено', 'Выплачено',
                'Трэк-номер заказ', 'Получено', 'Трэк-номер перевоз', 'Получено', '№ счёта', '№ УПД', 'УПД заказчик', 'Заявка заказчик', 'ТН', 'УПД перевозчик', 'Заявка перевозчик'
            ]
        ];
    }
    
    hot = new Handsontable(container, {
        data: window.ordersData,
        rowHeaders: true,
        colHeaders: true,
        height: '100%',
        width: '100%',
        licenseKey: 'non-commercial-and-evaluation',
        columns: columns,
        nestedHeaders: nestedHeaders,
        manualColumnResize: true,
        className: 'htRowStripe',
        persistentState: true,
        
        contextMenu: (window.isAdminOrSupervisor || window.isManager) ? {
            items: {
                'row_above': { name: 'Вставить строку выше' },
                'row_below': { name: 'Вставить строку ниже' },
                'remove_row': {
                    name: 'Удалить строку',
                    callback: function(key, selection) {
                        const rows = selection[0].start.row;
                        const rowData = hot.getDataAtRow(rows);
                        
                        if (confirm('Удалить заявку ' + rowData[0] + '?')) {
                            const rowId = hot.getSourceDataAtRow(rows)?.id;
                            
                            if (rowId) {
                                fetch('/cabinet/orders/' + rowId, {
                                    method: 'DELETE',
                                    headers: {
                                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                                    }
                                })
                                .then(response => response.json())
                                .then(data => {
                                    if (data.success) {
                                        hot.alter('remove_row', rows, 1);
                                        refreshCurrentPeriod();
                                        showMessage('Заявка удалена', 'success');
                                    }
                                });
                            } else {
                                hot.alter('remove_row', rows, 1);
                            }
                        }
                    }
                },
                'remove_multiple_rows': {
                    name: '🗑️ Удалить выделенные строки',
                    callback: function(key, selection) {
                        const selectedRows = [];
                        for (let i = 0; i < selection.length; i++) {
                            const startRow = selection[i].start.row;
                            const endRow = selection[i].end.row;
                            for (let row = startRow; row <= endRow; row++) {
                                if (!selectedRows.includes(row)) {
                                    selectedRows.push(row);
                                }
                            }
                        }
                        
                        selectedRows.sort((a, b) => b - a);
                        
                        if (selectedRows.length === 0) return;
                        
                        if (confirm('Удалить ' + selectedRows.length + ' выделенных заявок?')) {
                            const idsToDelete = [];
                            selectedRows.forEach(function(rowIndex) {
                                const rowData = hot.getSourceDataAtRow(rowIndex);
                                if (rowData && rowData.id) {
                                    idsToDelete.push(rowData.id);
                                }
                            });
                            
                            if (idsToDelete.length > 0) {
                                fetch('/cabinet/orders/bulk-delete', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                                    },
                                    body: JSON.stringify({ ids: idsToDelete })
                                })
                                .then(response => response.json())
                                .then(data => {
                                    if (data.success) {
                                        selectedRows.forEach(function(rowIndex) {
                                            hot.alter('remove_row', rowIndex, 1);
                                        });
                                        refreshCurrentPeriod();
                                        showMessage('Удалено ' + selectedRows.length + ' заявок', 'success');
                                    }
                                });
                            } else {
                                selectedRows.forEach(function(rowIndex) {
                                    hot.alter('remove_row', rowIndex, 1);
                                });
                                showMessage('Удалено ' + selectedRows.length + ' заявок', 'success');
                            }
                        }
                    }
                },
                'separator': Handsontable.plugins.ContextMenu.SEPARATOR,
                'copy': { name: 'Копировать' },
                'cut': { name: 'Вырезать' },
                'paste': { name: 'Вставить' }
            }
        } : false,
        
        afterChange: function(changes, source) {
            if (source === 'edit' && changes) {
                const rowIndex = changes[0][0];
                const prop = changes[0][1];
                const oldValue = changes[0][2];
                const newValue = changes[0][3];
                
                const sourceData = hot.getSourceData();
                const rowData = sourceData[rowIndex];
                
                if (prop && rowData) {
                    rowData[prop] = newValue;
                }
                
                if (prop === 'company_code' && oldValue !== newValue) {
                    updateOrderNumber(rowIndex, newValue, oldValue);
                } else {
                    saveRow(rowIndex);
                }
            }
        }
    });
}

function refreshCurrentPeriod() {
    const sourceData = hot.getSourceData();
    if (sourceData.length === 0) return;
    
    const firstOrderDate = sourceData[0]?.order_date;
    if (!firstOrderDate) return;
    
    // Сохраняем текущую позицию курсора
    const selected = hot.getSelected();
    let currentRow = 0, currentCol = 0;
    if (selected && selected.length > 0) {
        currentRow = selected[0][0];
        currentCol = selected[0][1];
    }
    
    let url = '/cabinet/orders/period-orders?date=' + firstOrderDate;
    
    showMessage('Обновление данных...', 'info');
    
    fetch(url)
        .then(r => r.json())
        .then(data => {
            if (Array.isArray(data)) {
                formatAllDates(data);
                hot.loadData(data);
                
                // Пытаемся восстановить позицию курсора
                if (data.length > 0) {
                    // Если сохранённая строка существует, ставим туда
                    if (currentRow < data.length) {
                        setTimeout(() => {
                            hot.selectCell(currentRow, currentCol);
                        }, 50);
                    } else {
                        // Иначе ставим на последнюю строку
                        setTimeout(() => {
                            hot.selectCell(data.length - 1, currentCol);
                        }, 50);
                    }
                }
                
                showMessage('Данные обновлены', 'success');
            }
        })
        .catch(error => {
            console.error('Error refreshing period:', error);
            showMessage('Ошибка обновления', 'error');
        });
}

function updateOrderNumber(rowIndex, newCompanyCode, oldCompanyCode) {
    const sourceData = hot.getSourceData();
    if (!sourceData[rowIndex]) return;
    
    const rowData = sourceData[rowIndex];
    const managerId = rowData.manager_id;
    const orderId = rowData.id;
    
    // Сохраняем текущую позицию курсора
    const selected = hot.getSelected();
    let currentCol = 0;
    if (selected && selected.length > 0) {
        currentCol = selected[0][1];
    }
    
    if (orderId && managerId !== window.currentUserId && !window.isAdminOrSupervisor) {
        showMessage('Только создатель может изменить номер заявки', 'error');
        sourceData[rowIndex].company_code = oldCompanyCode;
        hot.loadData(sourceData);
        // Возвращаем курсор
        setTimeout(() => {
            hot.selectCell(rowIndex, currentCol);
        }, 50);
        return;
    }
    
    if (!managerId) {
        showMessage('ID менеджера не найден', 'error');
        return;
    }
    
    fetch('/cabinet/orders/generate-number', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ 
            company_code: newCompanyCode,
            manager_id: managerId,
            order_id: orderId
        })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            sourceData[rowIndex].order_number = data.order_number;
            sourceData[rowIndex].company_code = newCompanyCode;
            hot.loadData(sourceData);
            
            // Возвращаем курсор
            setTimeout(() => {
                hot.selectCell(rowIndex, currentCol);
            }, 50);
            
            saveRow(rowIndex);
            showMessage('Номер заявки обновлён: ' + data.order_number, 'success');
        } else {
            showMessage('Ошибка: ' + (data.error || 'Неизвестная ошибка'), 'error');
            sourceData[rowIndex].company_code = oldCompanyCode;
            hot.loadData(sourceData);
            
            // Возвращаем курсор
            setTimeout(() => {
                hot.selectCell(rowIndex, currentCol);
            }, 50);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showMessage('Ошибка соединения', 'error');
    });
}

function createNewOrder() {
    if (!window.isAdminOrSupervisor && !window.isManager) {
        showMessage('У вас нет прав для создания заявок', 'error');
        return;
    }
    
    fetch('/cabinet/orders/create', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ 
            company_code: null,
            create_empty: true 
        })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const newRow = {
                id: data.order.id,
                order_number: data.order.order_number,
                status_icon: data.order.status_icon || '⏳',
                status_code: data.order.status_code || 'new',
                company_code: data.order.company_code,
                manager_name: data.order.manager_name,
                order_date: data.order.order_date,
                additional_expenses: 0,
                insurance: 0,
                bonus: 0,
                customer_rate: 0,
                carrier_rate: 0,
                delta: 0,
                kpi_percent: 0,
                salary_accrued: 0,
                salary_paid: 0,
                prepayment_customer: 0,
                prepayment_carrier: 0,
                final_customer: 0,
                final_carrier: 0,
                is_in_progress: false,
                documents_received: false,
                is_paid: false,
                is_completed: false
            };
            
            const sourceData = hot.getSourceData();
            sourceData.push(newRow);
            hot.loadData(sourceData);
            hot.selectCell(sourceData.length - 1, 2);
            showMessage('Новая заявка создана', 'success');
        }
    });
}

function saveRow(rowIndex) {
    const sourceData = hot.getSourceData();
    if (!sourceData[rowIndex]) return;
    
    const rowData = sourceData[rowIndex];
    
    // Сохраняем текущую позицию курсора
    const selected = hot.getSelected();
    let currentCol = 0;
    if (selected && selected.length > 0) {
        currentCol = selected[0][1]; // Индекс текущей колонки
    }
    
    fetch('/cabinet/orders/save', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ data: rowData })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            if (data.data) {
                const updatedRow = data.data;
                
                // Форматируем даты
                if (updatedRow.order_date) updatedRow.order_date = formatDateForInput(updatedRow.order_date);
                if (updatedRow.loading_date) updatedRow.loading_date = formatDateForInput(updatedRow.loading_date);
                if (updatedRow.unloading_date) updatedRow.unloading_date = formatDateForInput(updatedRow.unloading_date);
                if (updatedRow.prepayment_date) updatedRow.prepayment_date = formatDateForInput(updatedRow.prepayment_date);
                if (updatedRow.prepayment_carrier_date) updatedRow.prepayment_carrier_date = formatDateForInput(updatedRow.prepayment_carrier_date);
                if (updatedRow.final_customer_date) updatedRow.final_customer_date = formatDateForInput(updatedRow.final_customer_date);
                if (updatedRow.final_carrier_date) updatedRow.final_carrier_date = formatDateForInput(updatedRow.final_carrier_date);
                if (updatedRow.track_status_customer) updatedRow.track_status_customer = formatDateForInput(updatedRow.track_status_customer);
                if (updatedRow.track_status_carrier) updatedRow.track_status_carrier = formatDateForInput(updatedRow.track_status_carrier);
                
                sourceData[rowIndex] = updatedRow;
                hot.loadData(sourceData);
                
                // Возвращаем курсор на ту же ячейку (та же строка, та же колонка)
                setTimeout(() => {
                    hot.selectCell(rowIndex, currentCol);
                }, 50);
            }
            
            // Фоновое обновление периода
            setTimeout(() => {
                refreshCurrentPeriod();
            }, 500);
            
            showMessage('Сохранено', 'success');
        } else {
            showMessage('Ошибка: ' + (data.error || 'Неизвестная ошибка'), 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showMessage('Ошибка соединения', 'error');
    });
}

function applyFilters() {
    const dateFrom = document.getElementById('filterDateFrom').value;
    const dateTo = document.getElementById('filterDateTo').value;
    const status = document.getElementById('filterStatus').value;
    
    let url = '/cabinet/orders/filter?';
    if (dateFrom) url += 'date_from=' + dateFrom + '&';
    if (dateTo) url += 'date_to=' + dateTo + '&';
    if (status) url += 'status=' + status + '&';
    
    if (window.isAdminOrSupervisor || window.isDispatcher) {
        const managerSelect = document.getElementById('filterManager');
        if (managerSelect) {
            const manager = managerSelect.value;
            if (manager) url += 'manager=' + manager;
        }
    }
    
    fetch(url)
        .then(r => r.json())
        .then(data => {
            formatAllDates(data);
            hot.loadData(data);
            showMessage('Фильтр применён', 'success');
        });
}

function exportToExcel() {
    const data = hot.getSourceData();
    const headers = hot.getColHeader();
    let csv = headers.join(';') + '\n';
    
    data.forEach(row => {
        const values = Object.values(row).map(v => v || '');
        csv += values.join(';') + '\n';
    });
    
    const blob = new Blob(['\uFEFF' + csv], { type: 'text/csv' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = 'orders_' + new Date().toISOString().slice(0,10) + '.csv';
    link.click();
}

function showMessage(msg, type) {
    const div = document.createElement('div');
    div.textContent = msg;
    div.style.cssText = `
        position: fixed; top: 20px; right: 20px; padding: 15px 20px;
        background: ${type === 'success' ? '#4CAF50' : type === 'error' ? '#f44336' : '#17a2b8'};
        color: white; border-radius: 5px; z-index: 9999;
        display: flex;
        align-items: center;
        gap: 10px;
    `;
    
    if (type === 'info') {
        const spinner = document.createElement('span');
        spinner.className = 'loading-spinner';
        div.appendChild(spinner);
    }
    
    document.body.appendChild(div);
    
    if (type !== 'info') {
        setTimeout(() => div.remove(), 3000);
    }
}

const style = document.createElement('style');
style.textContent = `
    .loading-spinner {
        display: inline-block;
        width: 16px;
        height: 16px;
        border: 2px solid rgba(255,255,255,0.3);
        border-radius: 50%;
        border-top-color: white;
        animation: spin 1s ease-in-out infinite;
        margin-right: 8px;
    }
    
    @keyframes spin {
        to { transform: rotate(360deg); }
    }
`;
document.head.appendChild(style);
</script>
@endpush
@endsection