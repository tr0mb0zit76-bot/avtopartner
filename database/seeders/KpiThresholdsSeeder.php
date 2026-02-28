<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class KpiThresholdsSeeder extends Seeder
{
    public function run(): void
    {
        $thresholds = [
            // Прямые сделки
            ['deal_type' => 'direct', 'vat_ratio_from' => 0.8, 'vat_ratio_to' => 1.0, 'kpi_percent' => 3, 'sort_order' => 1, 'is_active' => true],
            ['deal_type' => 'direct', 'vat_ratio_from' => 0.6, 'vat_ratio_to' => 0.79, 'kpi_percent' => 4, 'sort_order' => 2, 'is_active' => true],
            ['deal_type' => 'direct', 'vat_ratio_from' => 0.5, 'vat_ratio_to' => 0.59, 'kpi_percent' => 5, 'sort_order' => 3, 'is_active' => true],
            ['deal_type' => 'direct', 'vat_ratio_from' => 0.4, 'vat_ratio_to' => 0.49, 'kpi_percent' => 6, 'sort_order' => 4, 'is_active' => true],
            ['deal_type' => 'direct', 'vat_ratio_from' => 0.3, 'vat_ratio_to' => 0.39, 'kpi_percent' => 7, 'sort_order' => 5, 'is_active' => true],
            
            // Кривые сделки
            ['deal_type' => 'indirect', 'vat_ratio_from' => 0.8, 'vat_ratio_to' => 1.0, 'kpi_percent' => 7, 'sort_order' => 1, 'is_active' => true],
            ['deal_type' => 'indirect', 'vat_ratio_from' => 0.6, 'vat_ratio_to' => 0.79, 'kpi_percent' => 8, 'sort_order' => 2, 'is_active' => true],
            ['deal_type' => 'indirect', 'vat_ratio_from' => 0.5, 'vat_ratio_to' => 0.59, 'kpi_percent' => 9, 'sort_order' => 3, 'is_active' => true],
            ['deal_type' => 'indirect', 'vat_ratio_from' => 0.4, 'vat_ratio_to' => 0.49, 'kpi_percent' => 10, 'sort_order' => 4, 'is_active' => true],
            ['deal_type' => 'indirect', 'vat_ratio_from' => 0.3, 'vat_ratio_to' => 0.39, 'kpi_percent' => 11, 'sort_order' => 5, 'is_active' => true],
        ];
        
        DB::table('kpi_thresholds')->insert($thresholds);
    }
}