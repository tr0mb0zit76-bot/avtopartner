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
        
        // Контрагенты
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
        
        // Поля для оплаты (из блока "ОПЛАТА")
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
        
        // Метаданные
        'metadata',
        'payment_statuses',
        
        // Аудит
        'created_by',
        'updated_by'
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
     * Связь с менеджером (пользователем)
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    /**
     * Связь с сайтом
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /**
     * Связь с заказчиком
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Contractor::class, 'customer_id');
    }

    /**
     * Связь с перевозчиком
     */
    public function carrier(): BelongsTo
    {
        return $this->belongsTo(Contractor::class, 'carrier_id');
    }

    /**
     * Связь с водителем
     */
    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    /**
     * Связь с создателем
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Связь с редактором
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Генерация номера заявки в зависимости от компании
     * 
     * Форматы:
     * - ЛР: ЛР-Инициалы-Номер (пример: ЛР-ИИ-001)
     * - АП: АПИнициалыНомер (пример: АПИИ001)
     * - КВ: Номер-Инициалы-КВ (пример: 001-ИИ-КВ)
     */
    public static function generateOrderNumber(string $companyCode, int $managerId): string
    {
        // Получаем менеджера
        $manager = User::find($managerId);
        if (!$manager) {
            return 'ERR-0001';
        }
        
        // Получаем инициалы менеджера (первые буквы имени и фамилии)
        $nameParts = explode(' ', trim($manager->name));
        $initials = '';
        foreach ($nameParts as $part) {
            if (!empty($part)) {
                $initials .= mb_strtoupper(mb_substr($part, 0, 1));
            }
        }
        
        // Если инициалов нет, используем ID
        if (empty($initials)) {
            $initials = 'XX';
        }
        
        // Получаем счётчик заявок менеджера по этой компании за текущий год
        $count = self::where('company_code', $companyCode)
            ->where('manager_id', $managerId)
            ->whereYear('created_at', now()->year)
            ->count();
        
        // Номер с ведущими нулями (3 знака)
        $sequence = str_pad($count + 1, 3, '0', STR_PAD_LEFT);
        
        // Генерация в зависимости от компании
        return match ($companyCode) {
            'ЛР' => sprintf('ЛР-%s-%s', $initials, $sequence),
            'АП' => sprintf('АП%s%s', $initials, $sequence),
            'КВ' => sprintf('%s-%s-КВ', $sequence, $initials),
            default => sprintf('%s-%s-%s', $companyCode, $initials, $sequence),
        };
    }

    /**
     * Парсинг номера заявки для получения информации
     */
    public static function parseOrderNumber(string $orderNumber): array
    {
        $result = [
            'company' => null,
            'initials' => null,
            'number' => null,
            'format' => null
        ];
        
        // Формат ЛР: ЛР-ИИ-001
        if (preg_match('/^ЛР-([А-Я]{2,3})-(\d{3})$/', $orderNumber, $matches)) {
            $result['company'] = 'ЛР';
            $result['initials'] = $matches[1];
            $result['number'] = $matches[2];
            $result['format'] = 'lr';
        }
        // Формат АП: АПИИ001
        elseif (preg_match('/^АП([А-Я]{2,3})(\d{3})$/', $orderNumber, $matches)) {
            $result['company'] = 'АП';
            $result['initials'] = $matches[1];
            $result['number'] = $matches[2];
            $result['format'] = 'ap';
        }
        // Формат КВ: 001-ИИ-КВ
        elseif (preg_match('/^(\d{3})-([А-Я]{2,3})-КВ$/', $orderNumber, $matches)) {
            $result['company'] = 'КВ';
            $result['initials'] = $matches[2];
            $result['number'] = $matches[1];
            $result['format'] = 'kv';
        }
        
        return $result;
    }

    /**
     * Получить инициалы из номера заявки
     */
    public function getInitialsFromNumber(): ?string
    {
        $parsed = self::parseOrderNumber($this->order_number);
        return $parsed['initials'];
    }

    /**
     * Получить порядковый номер из номера заявки
     */
    public function getSequenceFromNumber(): ?string
    {
        $parsed = self::parseOrderNumber($this->order_number);
        return $parsed['number'];
    }

    /**
     * Проверка, принадлежит ли заявка текущему менеджеру
     */
    public function isOwnedBy(int $userId): bool
    {
        return $this->manager_id === $userId;
    }

    /**
     * Получить следующий номер для менеджера (без сохранения)
     */
    public static function previewNextNumber(string $companyCode, int $managerId): string
    {
        return self::generateOrderNumber($companyCode, $managerId);
    }

    /**
     * Переопределяем метод save для автоматической генерации номера при создании
     */
    public static function boot()
    {
        parent::boot();
        
        static::creating(function ($order) {
            // Если номер не задан, генерируем автоматически
            if (empty($order->order_number)) {
                $order->order_number = self::generateOrderNumber(
                    $order->company_code ?? 'ЛР',
                    $order->manager_id ?? auth()->id()
                );
            }
        });
    }
}