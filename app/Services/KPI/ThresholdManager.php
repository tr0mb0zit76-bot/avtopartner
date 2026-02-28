<?php

namespace App\Services\KPI;

use App\Models\KpiThreshold;
use Illuminate\Support\Facades\Log;

class ThresholdManager
{
    /**
     * Получение KPI для сделки (аналог XLOOKUP + ПОИСКПОЗ + ИНДЕКС в Excel)
     */
    public function getKpiForDeal(string $dealType, float $directRatio): int
    {
        // Получаем все пороги из БД, отсортированные по убыванию
        $thresholds = KpiThreshold::where('is_active', true)
            ->orderBy('threshold_from', 'desc')
            ->get();
        
        if ($thresholds->isEmpty()) {
            Log::warning('No thresholds found in database');
            return 5;
        }
        
        // Разделяем на прямые и кривые, сохраняя порядок
        $directThresholds = $thresholds->where('deal_type', 'direct')->values();
        $indirectThresholds = $thresholds->where('deal_type', 'indirect')->values();
        
        // Находим индекс подходящего порога по direct_ratio
        $selectedIndex = null;
        foreach ($directThresholds as $index => $threshold) {
            if ($directRatio >= $threshold->threshold_from) {
                $selectedIndex = $index;
                break;
            }
        }
        
        // Если не нашли, берём последний (самый низкий порог)
        if ($selectedIndex === null) {
            $selectedIndex = $directThresholds->count() - 1;
        }
        
        $result = ($dealType === 'direct') 
            ? $directThresholds[$selectedIndex]->kpi_percent 
            : $indirectThresholds[$selectedIndex]->kpi_percent;
        
        Log::debug('Threshold selection', [
            'direct_ratio' => $directRatio,
            'selected_index' => $selectedIndex,
            'direct_kpi' => $directThresholds[$selectedIndex]->kpi_percent,
            'indirect_kpi' => $indirectThresholds[$selectedIndex]->kpi_percent,
            'deal_type' => $dealType,
            'result' => $result
        ]);
        
        return $result;
    }
}