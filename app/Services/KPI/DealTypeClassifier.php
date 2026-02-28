<?php

namespace App\Services\KPI;

class DealTypeClassifier
{
    public function classify(array $order): string
    {
        $customerForm = $order['customer_payment_form'] ?? '';
        $carrierForm = $order['carrier_payment_form'] ?? '';
        
        // Если формы оплаты не указаны, считаем что тип неизвестен
        if (empty($customerForm) || empty($carrierForm)) {
            return 'unknown';
        }
        
        return ($customerForm === $carrierForm) ? 'direct' : 'indirect';
    }
    
    public function calculateVatRatio(array $order): float
    {
        $customerForm = $order['customer_payment_form'] ?? '';
        $carrierForm = $order['carrier_payment_form'] ?? '';
        
        if (empty($customerForm) || empty($carrierForm)) {
            return 0;
        }
        
        $hasCustomerVat = $customerForm === 'с НДС';
        $hasCarrierVat = $carrierForm === 'с НДС';
        
        if ($hasCustomerVat && $hasCarrierVat) return 1.0;
        if (!$hasCustomerVat && !$hasCarrierVat) return 0.0;
        return 0.5;
    }
}