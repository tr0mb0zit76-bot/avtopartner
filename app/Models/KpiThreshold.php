<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KpiThreshold extends Model
{
    protected $fillable = [
        'deal_type',
        'threshold_from',
        'threshold_to',
        'kpi_percent',
        'is_active'
    ];

    protected $casts = [
        'threshold_from' => 'decimal:2',
        'threshold_to' => 'decimal:2',
        'is_active' => 'boolean'
    ];
}