<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
{
    protected $table = 'orders';
    
    protected $fillable = [
        // Основные идентификаторы
        'order_number',
        'company_code',
        'manager_id',
        'site_id',
        
        // Даты
        'order_date',
        'loading_date',
        'unloading_date',
        
        // Маршрут и груз
        'loading_point',
        'unloading_point',
        'cargo_description',
        
        // Финансы заказчика
        'customer_rate',
        'customer_payment_form',
        'customer_payment_term',
        
        // Финансы перевозчика
        'carrier_rate',
        'carrier_payment_form',
        'carrier_payment_term',
        
        // Дополнительные расходы
        'additional_expenses',
        'insurance',
        'bonus',
        
        // Контрагенты (ID)
        'customer_id',
        'carrier_id',
        'driver_id',
        
        // Расчётные поля
        'kpi_percent',
        'delta',
        'salary_accrued',
        'salary_paid',
        
        // Статусы
        'status',
        'is_active',
        
        // Метаданные
        'metadata',
        'payment_statuses',
        
        // Аудит
        'created_by',
        'updated_by',
        
        // ПОЛЯ ДЛЯ ОПЛАТЫ (блок "Оплата" из Excel)
        'prepayment_customer',
        'prepayment_date',
        'prepayment_status',
        'prepayment_carrier',
        'prepayment_carrier_date',
        'prepayment_carrier_status',
        'final_customer',
        'final_customer_date',
        'final_customer_status',
        'final_carrier',
        'final_carrier_date',
        'final_carrier_status',
        
        // ПОЛЯ ДЛЯ КОНТРАГЕНТОВ (контактная информация)
        'customer_contact',
        'carrier_contact',
        'driver_phone',
        
        // ПОЛЯ ДЛЯ ДОКУМЕНТОВ (блок "Документы" из Excel)
        'track_number_customer',
        'track_status_customer',
        'track_number_carrier',
        'track_status_carrier',
        'invoice_number',
        'upd_number',
        'upd_customer_status',
        'order_customer_status',
        'waybill_number',
        'upd_carrier_status',
        'order_carrier_status',
    ];

    protected $casts = [
        'order_date' => 'date',
        'loading_date' => 'date',
        'unloading_date' => 'date',
        'prepayment_date' => 'date',
        'prepayment_carrier_date' => 'date',
        'final_customer_date' => 'date',
        'final_carrier_date' => 'date',
        
        'customer_rate' => 'decimal:2',
        'carrier_rate' => 'decimal:2',
        'additional_expenses' => 'decimal:2',
        'insurance' => 'decimal:2',
        'bonus' => 'decimal:2',
        'prepayment_customer' => 'decimal:2',
        'prepayment_carrier' => 'decimal:2',
        'final_customer' => 'decimal:2',
        'final_carrier' => 'decimal:2',
        
        'kpi_percent' => 'decimal:2',
        'delta' => 'decimal:2',
        'salary_accrued' => 'decimal:2',
        'salary_paid' => 'decimal:2',
        
        'is_active' => 'boolean',
        'metadata' => 'array',
        'payment_statuses' => 'array'
    ];

    /**
     * Связи
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Contractor::class, 'customer_id');
    }

    public function carrier(): BelongsTo
    {
        return $this->belongsTo(Contractor::class, 'carrier_id');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Генерация номера заявки по маске
     * Для компании «Логистические решения»: ЛР-инициалы менеджера-сквозной номер
     * Для «Автопартнёр»: АП-инициалы менеджера-сквозной номер
     * Для «Квест»: КВ-инициалы менеджера-сквозной номер
     */
    public static function generateNumber($companyCode, $managerInitials)
    {
        $prefix = match($companyCode) {
            'ЛР' => 'ЛР',
            'АП' => 'АП',
            'КВ' => 'КВ',
            default => $companyCode
        };
        
        $lastOrder = self::where('company_code', $companyCode)
            ->whereYear('created_at', now()->year)
            ->orderBy('id', 'desc')
            ->first();
        
        $sequence = $lastOrder ? intval(substr($lastOrder->order_number, -3)) + 1 : 1;
        
        return sprintf('%s-%s-%03d', $prefix, $managerInitials, $sequence);
    }

    /**
     * Расчёт дельты (доход - расходы)
     */
    public function calculateDelta(): float
    {
        $income = $this->customer_rate ?? 0;
        $expenses = ($this->carrier_rate ?? 0) 
                  + ($this->additional_expenses ?? 0) 
                  + ($this->insurance ?? 0) 
                  + ($this->bonus ?? 0);
        
        return $income - $expenses;
    }

    /**
     * Расчёт KPI (упрощённо, будет заменено на логику из Excel)
     */
    public function calculateKpi(): float
    {
        $delta = $this->calculateDelta();
        $income = $this->customer_rate ?? 0;
        
        if ($income == 0) {
            return 0;
        }
        
        return round(($delta / $income) * 100, 2);
    }

    /**
     * Обновление расчётных полей
     */
    public function refreshCalculatedFields(): void
    {
        $this->delta = $this->calculateDelta();
        $this->kpi_percent = $this->calculateKpi();
        $this->salary_accrued = $this->delta * ($this->kpi_percent / 100);
        
        $this->saveQuietly();
    }

    /**
     * Скоупы для фильтрации
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByManager($query, $managerId)
    {
        return $query->where('manager_id', $managerId);
    }

    public function scopeByCompany($query, $companyCode)
    {
        return $query->where('company_code', $companyCode);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('order_date', [$startDate, $endDate]);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Аксессоры для удобного доступа к данным
     */
    public function getCustomerNameAttribute()
    {
        return $this->customer->name ?? $this->attributes['customer_name'] ?? '';
    }

    public function getCarrierNameAttribute()
    {
        return $this->carrier->name ?? $this->attributes['carrier_name'] ?? '';
    }

    public function getDriverFullNameAttribute()
    {
        return $this->driver->full_name ?? $this->attributes['driver_name'] ?? '';
    }

    public function getTotalExpensesAttribute(): float
    {
        return ($this->carrier_rate ?? 0) 
             + ($this->additional_expenses ?? 0) 
             + ($this->insurance ?? 0) 
             + ($this->bonus ?? 0);
    }

    public function getProfitAttribute(): float
    {
        return ($this->customer_rate ?? 0) - $this->total_expenses;
    }

    public function getProfitMarginAttribute(): float
    {
        $income = $this->customer_rate ?? 0;
        if ($income == 0) {
            return 0;
        }
        return round(($this->profit / $income) * 100, 2);
    }
}