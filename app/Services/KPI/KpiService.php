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
    protected PaymentDateCalculator $paymentDateCalculator;
    
    public function __construct()
    {
        $this->classifier = new DealTypeClassifier();
        $this->periodCalculator = new PeriodCalculator();
        $this->thresholdManager = new ThresholdManager();
        $this->paymentDateCalculator = new PaymentDateCalculator();
    }
    
    /**
     * Полный расчёт заявки (KPI + зарплата + даты оплат)
     */
    public function calculateForOrder(Order $order): array
    {
        $orderData = $order->toArray();
        
        // Расчёт KPI и зарплаты
        $kpiResult = $this->calculateKpiAndSalary($order);
        
        // Расчёт дат оплат
        $paymentDates = $this->paymentDateCalculator->calculate($order);
        
        return array_merge($kpiResult, [
            'prepayment_customer' => $paymentDates['prepayment_customer'],
            'prepayment_date' => $paymentDates['prepayment_date'],
            'prepayment_carrier' => $paymentDates['prepayment_carrier'],
            'prepayment_carrier_date' => $paymentDates['prepayment_carrier_date'],
            'final_customer' => $paymentDates['final_customer'],
            'final_customer_date' => $paymentDates['final_customer_date'],
            'final_carrier' => $paymentDates['final_carrier'],
            'final_carrier_date' => $paymentDates['final_carrier_date'],
        ]);
    }
    
    /**
     * Расчёт KPI и зарплаты (существующая логика)
     */
    protected function calculateKpiAndSalary(Order $order): array
    {
        $orderData = $order->toArray();
        
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
        
        $periodStats = $this->periodCalculator->getManagerPeriodStats(
            $order->manager_id,
            $period['start'],
            $period['end']
        );
        
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
    
    Log::info('Recalculating manager period', [
        'manager_id' => $managerId,
        'period' => $period,
        'total_orders' => $periodStats['total'],
        'direct_ratio' => $periodStats['direct_ratio']
    ]);
    
    if ($periodStats['total'] === 0) {
        Log::warning('No orders in period, skipping recalculation');
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
    
    Log::info('Found orders to recalculate', ['count' => $orders->count()]);
    
    foreach ($orders as $order) {
        // Пересчитываем KPI и зарплату
        $kpiResult = $this->calculateKpiAndSalary($order);
        
        // Пересчитываем даты оплат
        $paymentDates = $this->paymentDateCalculator->calculate($order);
        
        Log::info('Recalculating order', [
            'order_id' => $order->id,
            'kpi' => $kpiResult['kpi_percent'],
            'delta' => $kpiResult['delta'],
            'salary' => $kpiResult['salary_accrued'],
            'payment_dates' => $paymentDates
        ]);
        
        $order->update(array_merge(
            [
                'kpi_percent' => $kpiResult['kpi_percent'],
                'delta' => $kpiResult['delta'],
                'salary_accrued' => $kpiResult['salary_accrued'],
            ],
            $paymentDates
        ));
    }
}
}