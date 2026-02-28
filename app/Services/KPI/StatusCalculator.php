<?php

namespace App\Services\KPI;

use App\Models\Order;

class StatusCalculator
{
    /**
     * Расчёт статуса заявки на основе данных
     */
    public function calculate(Order $order): array
    {
        $statusCode = $this->determineStatus($order);
        
        return [
            'code' => $statusCode,
            'icon' => $this->getStatusIcon($statusCode),
            'label' => $this->getStatusLabel($statusCode),
            'is_in_progress' => $statusCode === 'in_progress',
            'documents_received' => $this->hasDocuments($order),
            'is_paid' => $this->isPaid($order),
            'is_completed' => $statusCode === 'completed',
        ];
    }
    
    /**
     * Определение статуса
     */
    protected function determineStatus(Order $order): string
    {
        $hasLoadingDate = !empty($order->loading_date);
        $hasUnloadingDate = !empty($order->unloading_date);
        
        $allDocumentsFilled = 
            !empty($order->upd_customer_status) &&
            !empty($order->order_customer_status) &&
            !empty($order->waybill_number) &&
            !empty($order->upd_carrier_status) &&
            !empty($order->order_carrier_status);
        
        $customerPaid = ($order->final_customer ?? 0) > 0 || ($order->prepayment_customer ?? 0) > 0;
        $salaryPaid = ($order->salary_paid ?? 0) > 0;
        
        if (!$hasLoadingDate) {
            return 'new';
        }
        
        if ($hasLoadingDate && !$hasUnloadingDate) {
            return 'in_progress';
        }
        
        if ($hasUnloadingDate && !$allDocumentsFilled) {
            return 'documents';
        }
        
        if ($allDocumentsFilled && (!$customerPaid || !$salaryPaid)) {
            return 'payment';
        }
        
        if ($hasUnloadingDate && $allDocumentsFilled && $customerPaid && $salaryPaid) {
            return 'completed';
        }
        
        return 'new';
    }
    
    /**
     * Проверка наличия документов
     */
    protected function hasDocuments(Order $order): bool
    {
        return !empty($order->upd_customer_status) ||
               !empty($order->waybill_number) ||
               !empty($order->track_number_customer) ||
               !empty($order->invoice_number);
    }
    
    /**
     * Проверка оплаты
     */
    protected function isPaid(Order $order): bool
    {
        return ($order->salary_paid ?? 0) > 0;
    }
    
    /**
     * Получение иконки для статуса
     */
    protected function getStatusIcon(string $status): string
    {
        return match($status) {
            'new' => '⏳',
            'in_progress' => '🚛',
            'documents' => '📄',
            'payment' => '💰',
            'completed' => '✅',
            default => '⏳',
        };
    }
    
    /**
     * Получение текстового статуса
     */
    protected function getStatusLabel(string $status): string
    {
        return match($status) {
            'new' => 'Новая',
            'in_progress' => 'Выполняется',
            'documents' => 'Документы',
            'payment' => 'Оплата',
            'completed' => 'Завершена',
            default => 'Новая',
        };
    }
}