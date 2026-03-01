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
        $status = $this->determineStatus($order);
        $icon = $this->getStatusIcon($status);
        $label = $this->getStatusLabel($status);
        $messages = $this->getStatusMessages($order);
        
        \Illuminate\Support\Facades\Log::info('Status calculated', [
            'order_id' => $order->id,
            'status' => $status,
            'icon' => $icon,
            'label' => $label,
            'has_loading' => !empty($order->loading_date),
            'has_unloading' => !empty($order->unloading_date),
            'has_docs' => $this->hasAllDocuments($order),
            'paid_by_customer' => $this->isPaidByCustomer($order),
            'paid_to_manager' => $this->isPaidToManager($order)
        ]);
        
        return [
            'status' => $status,
            'icon' => $icon,
            'label' => $label,
            'messages' => $messages,
            'is_in_progress' => $status === 'in_progress',
            'documents_received' => $this->hasAllDocuments($order),
            'is_paid_by_customer' => $this->isPaidByCustomer($order),
            'is_paid_to_manager' => $this->isPaidToManager($order),
            'is_completed' => $status === 'completed',
        ];
    }
    
    /**
     * Определение статуса
     */
    protected function determineStatus(Order $order): string
    {
        $hasLoading = !empty($order->loading_date);
        $hasUnloading = !empty($order->unloading_date);
        $hasAllDocuments = $this->hasAllDocuments($order);
        $isPaidByCustomer = $this->isPaidByCustomer($order);
        $isPaidToManager = $this->isPaidToManager($order);
        
        // 1. НОВАЯ
        if (!$hasLoading && !$hasUnloading) {
            return 'new';
        }
        
        // 2. ВЫПОЛНЯЕТСЯ
        if ($hasLoading && !$hasUnloading) {
            return 'in_progress';
        }
        
        // 3. ДОКУМЕНТЫ (есть выгрузка, но не все документы)
        if ($hasUnloading && !$hasAllDocuments) {
            return 'awaiting_docs';
        }
        
        // 4. ОПЛАТА (все документы есть, но нет оплаты)
        if ($hasAllDocuments && !$isPaidByCustomer) {
            return 'awaiting_payment';
        }
        
        // 5. ЗАВЕРШЕНА (всё есть и оплачено)
        if ($hasUnloading && $hasAllDocuments && $isPaidByCustomer && $isPaidToManager) {
            return 'completed';
        }
        
        // Если ничего не подошло, возвращаем новый (как запасной вариант)
        return 'new';
    }
    
    /**
     * Проверка наличия всех документов
     */
    protected function hasAllDocuments(Order $order): bool
    {
        return !empty($order->upd_customer_status) &&
               !empty($order->order_customer_status) &&
               !empty($order->waybill_number) &&
               !empty($order->upd_carrier_status) &&
               !empty($order->order_carrier_status);
    }
    
    /**
     * Проверка оплаты заказчиком (хотя бы частичной)
     */
    protected function isPaidByCustomer(Order $order): bool
    {
        return ($order->final_customer ?? 0) > 0 || ($order->prepayment_customer ?? 0) > 0;
    }
    
    /**
     * Проверка выплаты менеджеру
     */
    protected function isPaidToManager(Order $order): bool
    {
        return ($order->salary_paid ?? 0) > 0;
    }
    
    /**
     * Получение сообщений о статусе
     */
    protected function getStatusMessages(Order $order): array
    {
        $messages = [];
        
        if (!$this->hasAllDocuments($order)) {
            $missingDocs = [];
            if (empty($order->upd_customer_status)) $missingDocs[] = 'УПД заказчик';
            if (empty($order->order_customer_status)) $missingDocs[] = 'Заявка заказчик';
            if (empty($order->waybill_number)) $missingDocs[] = 'ТН';
            if (empty($order->upd_carrier_status)) $missingDocs[] = 'УПД перевозчик';
            if (empty($order->order_carrier_status)) $missingDocs[] = 'Заявка перевозчик';
            
            if (!empty($missingDocs)) {
                $messages[] = 'Не хватает: ' . implode(', ', $missingDocs);
            }
        }
        
        if (!$this->isPaidByCustomer($order) && !empty($order->unloading_date)) {
            $messages[] = 'Ожидание оплаты клиентом';
        }
        
        if ($this->isPaidByCustomer($order) && !$this->isPaidToManager($order)) {
            $messages[] = 'Клиент оплатил, ожидание выплаты менеджеру';
        }
        
        return $messages;
    }
    
    /**
     * Получение иконки для статуса
     */
    protected function getStatusIcon(string $status): string
    {
        return match($status) {
            'new' => '⏳',
            'in_progress' => '🚛',
            'awaiting_docs' => '📄',
            'awaiting_payment' => '💰',
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
            'awaiting_docs' => 'Документы',
            'awaiting_payment' => 'Оплата',
            'completed' => 'Завершена',
            default => 'Новая',
        };
    }
}