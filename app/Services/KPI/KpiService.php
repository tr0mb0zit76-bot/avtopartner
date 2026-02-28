<?php

namespace App\Services\KPI;

use App\Models\Order;
use App\Models\SalaryCoefficient;
use Illuminate\Support\Facades\Log;

class KpiService
{
    protected DealTypeClassifier $classifier;
    protected PeriodCalculator $periodCalculator;
    protected ThresholdManager $thresholdManager;
    
    public function __construct()
    {
        $this->classifier = new DealTypeClassifier();
        $this->periodCalculator = new PeriodCalculator();
        $this->thresholdManager = new ThresholdManager();
    }
    
    /**
     * Получение KPI для конкретной заявки
     */
    public function calculateForOrder(Order $order): array
    {
        $orderData = $order->toArray();
        
        // Проверяем, указаны ли формы оплаты
        if (empty($orderData['customer_payment_form']) || empty($orderData['carrier_payment_form'])) {
            return [
                'kpi_percent' => 0,
                'delta' => 0,
                'salary_accrued' => 0,
                'deal_type' => 'unknown',
                'period_info' => []
            ];
        }
        
        $dealType = $this->classifier->classify($orderData);
        $period = $this->periodCalculator->getPeriodForDate($order->order_date);
        
        // Получаем актуальную статистику периода
        $periodStats = $this->periodCalculator->getManagerPeriodStats(
            $order->manager_id,
            $period['start'],
            $period['end']
        );
        
        // Получаем KPI через ThresholdManager
        $kpiPercent = $this->thresholdManager->getKpiForDeal($dealType, $periodStats['direct_ratio']);
        
        // Расчёт финансов
        $customerRate = $order->customer_rate ?? 0;
        $carrierRate = $order->carrier_rate ?? 0;
        $additional = $order->additional_expenses ?? 0;
        $insurance = $order->insurance ?? 0;
        $bonus = $order->bonus ?? 0;
        
        // Расчёт расходов: ставка перевозчика + доп. расходы + страховка + (бонус * 1.3)
        $bonusPart = $bonus * 1.3;
        $totalExpenses = $carrierRate + $additional + $insurance + $bonusPart;
        
        // Дельта по формуле: доход - (доход * KPI / 100) - расходы
        $kpiDeduction = $customerRate * ($kpiPercent / 100);
        $delta = $customerRate - $kpiDeduction - $totalExpenses;
        
        // Получаем коэффициенты зарплаты для менеджера
        $salaryCoeff = SalaryCoefficient::getForManagerOnDate(
            $order->manager_id, 
            $order->order_date->format('Y-m-d')
        );
        
        $bonusPercent = $salaryCoeff ? $salaryCoeff->bonus_percent : 0;
        $baseSalary = $salaryCoeff ? $salaryCoeff->base_salary : 0;
        
        $salaryAccrued = ($delta * $bonusPercent / 100) + $baseSalary;
        
        return [
            'kpi_percent' => $kpiPercent,
            'delta' => round($delta, 2),
            'salary_accrued' => round($salaryAccrued, 2),
            'deal_type' => $dealType,
            'period_info' => $periodStats,
            'salary_details' => [
                'bonus_percent' => $bonusPercent,
                'base_salary' => $baseSalary
            ]
        ];
    }
    
    /**
     * Пересчёт всех заявок менеджера за период
     */
    public function recalculateManagerPeriod(int $managerId, string $date): void
    {
        $period = $this->periodCalculator->getPeriodForDate($date);
        
        $periodStats = $this->periodCalculator->getManagerPeriodStats(
            $managerId,
            $period['start'],
            $period['end']
        );
        
        if ($periodStats['total'] === 0) {
            return;
        }
        
        $salaryCoeff = SalaryCoefficient::getForManagerOnDate($managerId, $date);
        $bonusPercent = $salaryCoeff ? $salaryCoeff->bonus_percent : 0;
        $baseSalary = $salaryCoeff ? $salaryCoeff->base_salary : 0;
        
        $orders = Order::where('manager_id', $managerId)
            ->whereBetween('order_date', [$period['start'], $period['end']])
            ->whereNotNull('customer_payment_form')
            ->whereNotNull('carrier_payment_form')
            ->get();
        
        foreach ($orders as $order) {
            $dealType = $this->classifier->classify($order->toArray());
            $kpiPercent = $this->thresholdManager->getKpiForDeal($dealType, $periodStats['direct_ratio']);
            
            $customerRate = $order->customer_rate ?? 0;
            $carrierRate = $order->carrier_rate ?? 0;
            $additional = $order->additional_expenses ?? 0;
            $insurance = $order->insurance ?? 0;
            $bonus = $order->bonus ?? 0;
            
            $bonusPart = $bonus * 1.3;
            $totalExpenses = $carrierRate + $additional + $insurance + $bonusPart;
            $kpiDeduction = $customerRate * ($kpiPercent / 100);
            $delta = $customerRate - $kpiDeduction - $totalExpenses;
            
            $salaryAccrued = ($delta * $bonusPercent / 100) + $baseSalary;
            
            $order->update([
                'kpi_percent' => $kpiPercent,
                'delta' => round($delta, 2),
                'salary_accrued' => round($salaryAccrued, 2)
            ]);
        }
    }
}