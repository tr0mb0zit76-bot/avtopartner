<?php

namespace App\Services\KPI;

use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class PaymentDateCalculator
{
    /**
     * Расчёт всех дат и сумм оплат для заявки
     */
    public function calculate(Order $order): array
    {
        Log::debug('PaymentDateCalculator::calculate started', [
            'order_id' => $order->id,
            'customer_term' => $order->customer_payment_term,
            'carrier_term' => $order->carrier_payment_term,
        ]);
        
        $customerRate = $order->customer_rate ?? 0;
        $carrierRate = $order->carrier_rate ?? 0;
        
        $result = [
            'prepayment_customer' => null,
            'prepayment_date' => null,
            'prepayment_carrier' => null,
            'prepayment_carrier_date' => null,
            'final_customer' => null,
            'final_customer_date' => null,
            'final_carrier' => null,
            'final_carrier_date' => null,
        ];
        
        // Расчёт для заказчика
        if (!empty($order->customer_payment_term)) {
            $customerResult = $this->calculatePartyPayments(
                $order,
                'customer',
                $customerRate,
                $order->customer_payment_term
            );
            
            $result = array_merge($result, $customerResult);
        }
        
        // Расчёт для перевозчика
        if (!empty($order->carrier_payment_term)) {
            $carrierResult = $this->calculatePartyPayments(
                $order,
                'carrier',
                $carrierRate,
                $order->carrier_payment_term
            );
            
            $result = array_merge($result, $carrierResult);
        }
        
        Log::debug('PaymentDateCalculator result', [
            'order_id' => $order->id,
            'result' => $result
        ]);
        
        return $result;
    }
    
    /**
     * Расчёт платежей для одной стороны (заказчик/перевозчик)
     */
    protected function calculatePartyPayments(Order $order, string $party, float $rate, string $term): array
    {
        $result = [
            "prepayment_{$party}" => null,
            "prepayment_{$party}_date" => null,
            "final_{$party}" => null,
            "final_{$party}_date" => null,
        ];
        
        // Ищем все пропорции в строке (например: 50/50, 30/70, 40/60)
        preg_match_all('/(\d+)\/(\d+)/', $term, $proportionMatches);
        
        if (!empty($proportionMatches[0])) {
            // Есть пропорции - значит есть предоплата
            $prepaymentPercent = (int)$proportionMatches[1][0]; // первая цифра
            $finalPercent = (int)$proportionMatches[2][0]; // вторая цифра
            
            // Суммы
            $prepaymentAmount = round($rate * $prepaymentPercent / 100, 2);
            $finalAmount = round($rate * $finalPercent / 100, 2);
            
            // Дата предоплаты (от даты погрузки)
            $prepaymentDate = $this->calculatePrepaymentDate($order, $party);
            
            // Дата постоплаты (из оставшейся части строки)
            $remainingTerm = preg_replace('/\d+\/\d+\s*/', '', $term);
            $finalDate = $this->calculatePostpaymentDate($order, $party, $remainingTerm);
            
            $result["prepayment_{$party}"] = $prepaymentAmount;
            $result["prepayment_{$party}_date"] = $prepaymentDate?->format('Y-m-d');
            $result["final_{$party}"] = $finalAmount;
            $result["final_{$party}_date"] = $finalDate?->format('Y-m-d');
            
            Log::debug("Calculated {$party} payments with proportion", [
                'prepayment_percent' => $prepaymentPercent,
                'final_percent' => $finalPercent,
                'prepayment_amount' => $prepaymentAmount,
                'final_amount' => $finalAmount,
                'prepayment_date' => $prepaymentDate?->format('Y-m-d'),
                'final_date' => $finalDate?->format('Y-m-d'),
                'remaining_term' => $remainingTerm
            ]);
            
        } else {
            // Нет пропорций - только постоплата
            $finalDate = $this->calculatePostpaymentDate($order, $party, $term);
            $result["final_{$party}"] = $rate;
            $result["final_{$party}_date"] = $finalDate?->format('Y-m-d');
            
            Log::debug("Calculated {$party} final payment only", [
                'amount' => $rate,
                'date' => $finalDate?->format('Y-m-d')
            ]);
        }
        
        return $result;
    }
    
    /**
     * Расчёт даты предоплаты
     */
    protected function calculatePrepaymentDate(Order $order, string $party): ?Carbon
    {
        $loadingDate = $order->loading_date ? Carbon::parse($order->loading_date) : null;
        
        if (!$loadingDate) {
            return null;
        }
        
        // Для заказчика +1 день, для перевозчика +2 дня
        $days = $party === 'customer' ? 1 : 2;
        
        return $this->addWorkingDays($loadingDate, $days);
    }
    
    /**
     * Расчёт даты постоплаты на основе условий
     */
    protected function calculatePostpaymentDate(Order $order, string $party, string $term): ?Carbon
    {
        $baseDate = null;
        $days = 0;
        
        // Очищаем строку от лишних пробелов
        $term = trim($term);
        
        if (str_contains($term, 'фттн')) {
            // фттн - от даты выгрузки
            $baseDate = $order->unloading_date ? Carbon::parse($order->unloading_date) : null;
            
            // Извлекаем количество дней
            preg_match('/(\d+)\s*фттн/', $term, $matches);
            $days = isset($matches[1]) ? (int)$matches[1] : 5;
            
            Log::debug('Postpayment фттн', [
                'party' => $party,
                'base_date' => $baseDate?->format('Y-m-d'),
                'days' => $days,
                'term' => $term
            ]);
            
        } elseif (str_contains($term, 'оттн')) {
            // оттн - от даты получения документов
            $docDate = $party === 'customer' 
                ? $order->track_status_customer 
                : $order->track_status_carrier;
            
            $baseDate = $docDate ? Carbon::parse($docDate) : null;
            
            // Извлекаем количество дней
            preg_match('/(\d+)\s*оттн/', $term, $matches);
            $days = isset($matches[1]) ? (int)$matches[1] : 5;
            
            Log::debug('Postpayment оттн', [
                'party' => $party,
                'doc_date' => $docDate,
                'base_date' => $baseDate?->format('Y-m-d'),
                'days' => $days,
                'term' => $term
            ]);
        }
        
        if (!$baseDate) {
            return null;
        }
        
        return $this->addWorkingDays($baseDate, $days);
    }
    
    /**
     * Добавление рабочих дней (без учёта праздников)
     */
    protected function addWorkingDays(Carbon $date, int $days): Carbon
    {
        $result = $date->copy();
        $added = 0;
        
        while ($added < $days) {
            $result->addDay();
            // Пропускаем субботу и воскресенье
            if ($result->isWeekday()) {
                $added++;
            }
        }
        
        Log::debug('addWorkingDays', [
            'start_date' => $date->format('Y-m-d'),
            'days_to_add' => $days,
            'result_date' => $result->format('Y-m-d'),
            'added' => $added
        ]);
        
        return $result;
    }
}