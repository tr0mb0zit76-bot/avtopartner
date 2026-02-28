<?php

namespace App\Services\KPI;

use App\Models\Order;
use Carbon\Carbon;

class PeriodCalculator
{
    /**
     * Получение периода по дате (аналог СЧЁТЕСЛИМН в Excel)
     */
    public function getPeriodForDate($date): array
    {
        $date = Carbon::parse($date);
        $day = $date->day;
        
        if ($day <= 15) {
            return [
                'start' => $date->copy()->startOfMonth()->format('Y-m-d'),
                'end' => $date->copy()->day(15)->format('Y-m-d'),
                'name' => 'первая половина ' . $date->format('F Y')
            ];
        } else {
            return [
                'start' => $date->copy()->day(16)->format('Y-m-d'),
                'end' => $date->copy()->endOfMonth()->format('Y-m-d'),
                'name' => 'вторая половина ' . $date->format('F Y')
            ];
        }
    }
    
    /**
     * Получение статистики периода (аналог СУММПРОИЗВ в Excel)
     */
    public function getManagerPeriodStats($managerId, $periodStart, $periodEnd): array
    {
        // СЧЁТЕСЛИМН - считаем количество заявок за период
        $orders = Order::where('manager_id', $managerId)
            ->whereBetween('order_date', [$periodStart, $periodEnd])
            ->whereNotNull('customer_payment_form')
            ->whereNotNull('carrier_payment_form')
            ->get();
        
        $total = $orders->count();
        
        // СУММПРОИЗВ - считаем прямые сделки (где формы оплаты совпадают)
        $direct = 0;
        foreach ($orders as $order) {
            if ($order->customer_payment_form === $order->carrier_payment_form) {
                $direct++;
            }
        }
        
        $directRatio = $total > 0 ? round($direct / $total, 2) : 0;
        
        return [
            'total' => $total,
            'direct' => $direct,
            'indirect' => $total - $direct,
            'direct_ratio' => $directRatio,
            'indirect_ratio' => $total > 0 ? round(($total - $direct) / $total, 2) : 0
        ];
    }
}