<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalaryCoefficient extends Model
{
    protected $fillable = [
        'manager_id',
        'base_salary',
        'bonus_percent',
        'effective_from',
        'effective_to',
        'is_active'
    ];

    protected $casts = [
        'base_salary' => 'integer',
        'bonus_percent' => 'integer',
        'effective_from' => 'date',
        'effective_to' => 'date',
        'is_active' => 'boolean'
    ];

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    /**
     * Получить активные коэффициенты для менеджера на дату
     */
    public static function getForManagerOnDate(int $managerId, string $date): ?self
    {
        return self::where('manager_id', $managerId)
            ->where('is_active', true)
            ->where('effective_from', '<=', $date)
            ->where(function($q) use ($date) {
                $q->whereNull('effective_to')
                  ->orWhere('effective_to', '>=', $date);
            })
            ->first();
    }
}