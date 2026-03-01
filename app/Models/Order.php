<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
{
    protected $fillable = [
        'order_number',
        'company_code',
        'manager_id',
        'site_id',
        'order_date',
        'loading_date',
        'unloading_date',
        'loading_point',
        'unloading_point',
        'cargo_description',
        'customer_rate',
        'customer_payment_form',
        'customer_payment_term',
        'carrier_rate',
        'carrier_payment_form',
        'carrier_payment_term',
        'additional_expenses',
        'insurance',
        'bonus',
        'customer_id',
        'carrier_id',
        'driver_id',
        'kpi_percent',
        'delta',
        'salary_accrued',
        'salary_paid',
        'status',
        'is_active',
        'metadata',
        'payment_statuses',
        'created_by',
        'updated_by',
        
        // Поля для оплаты
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
        
        // Поля для контактов
        'customer_contact',
        'carrier_contact',
        'driver_phone',
        
        // Поля для документов
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
        'doc_received_date_customer',
        'doc_received_date_carrier',
    ];

    protected $casts = [
        'order_date' => 'date',
        'loading_date' => 'date',
        'unloading_date' => 'date',
        'prepayment_date' => 'date',
        'prepayment_carrier_date' => 'date',
        'final_customer_date' => 'date',
        'final_carrier_date' => 'date',
        'doc_received_date_customer' => 'date',
        'doc_received_date_carrier' => 'date',
        'customer_rate' => 'decimal:2',
        'carrier_rate' => 'decimal:2',
        'additional_expenses' => 'decimal:2',
        'insurance' => 'decimal:2',
        'bonus' => 'decimal:2',
        'kpi_percent' => 'decimal:2',
        'delta' => 'decimal:2',
        'salary_accrued' => 'decimal:2',
        'salary_paid' => 'decimal:2',
        'prepayment_customer' => 'decimal:2',
        'prepayment_carrier' => 'decimal:2',
        'final_customer' => 'decimal:2',
        'final_carrier' => 'decimal:2',
        'is_active' => 'boolean',
        'metadata' => 'array',
        'payment_statuses' => 'array'
    ];

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
     * Генерация номера заявки по шаблону компании
     */
    public static function generateOrderNumber(string $companyCode, int $managerId): string
    {
        $manager = User::find($managerId);
        if (!$manager) {
            return 'ERR-0001';
        }
        
        // Получаем инициалы
        $nameParts = explode(' ', $manager->name);
        $initials = '';
        if (isset($nameParts[0])) {
            $initials .= mb_substr($nameParts[0], 0, 1);
        }
        if (isset($nameParts[1])) {
            $initials .= mb_substr($nameParts[1], 0, 1);
        }
        $initials = strtoupper($initials);
        
        // Получаем следующий порядковый номер
        $lastOrder = self::where('manager_id', $managerId)
            ->where('company_code', $companyCode)
            ->orderBy('id', 'desc')
            ->first();
        
        $sequence = $lastOrder ? ((int) substr($lastOrder->order_number, -3)) + 1 : 1;
        $sequencePadded = str_pad($sequence, 3, '0', STR_PAD_LEFT);
        
        // Формируем номер по шаблону компании
        return match($companyCode) {
            'ЛР' => "ЛР-{$initials}-{$sequencePadded}",
            'АП' => "{$initials}-АП-{$sequencePadded}",
            'КВ' => "{$sequencePadded}-КВ-{$initials}",
            default => $sequencePadded,
        };
    }
}