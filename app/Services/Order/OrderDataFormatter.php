<?php

namespace App\Services\Order;

use App\Models\Order;
use App\Services\KPI\StatusCalculator;

class OrderDataFormatter
{
    protected StatusCalculator $statusCalculator;
    
    public function __construct()
    {
        $this->statusCalculator = new StatusCalculator();
    }
    
    /**
     * Преобразование заявки в формат для Handsontable
     */
    public function format(Order $order): array
    {
        $statusInfo = $this->statusCalculator->calculate($order);
        
        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'status_icon' => $statusInfo['icon'],
            'status_code' => $statusInfo['status'],
            'status_label' => $statusInfo['label'],
            'status_messages' => $statusInfo['messages'] ?? [],
            'company_code' => $order->company_code,
            'manager_name' => $order->manager->name ?? '',
            'manager_id' => $order->manager_id,
            'order_date' => $order->order_date?->format('Y-m-d'),
            'loading_point' => $order->loading_point,
            'unloading_point' => $order->unloading_point,
            'cargo_description' => $order->cargo_description,
            'loading_date' => $order->loading_date?->format('Y-m-d'),
            'unloading_date' => $order->unloading_date?->format('Y-m-d'),
            'customer_rate' => $order->customer_rate ?? 0,
            'customer_payment_form' => $order->customer_payment_form,
            'customer_payment_term' => $order->customer_payment_term,
            'carrier_rate' => $order->carrier_rate ?? 0,
            'carrier_payment_form' => $order->carrier_payment_form,
            'carrier_payment_term' => $order->carrier_payment_term,
            'additional_expenses' => $order->additional_expenses ?? 0,
            'insurance' => $order->insurance ?? 0,
            'bonus' => $order->bonus ?? 0,
            'kpi_percent' => $order->kpi_percent ?? 0,
            'delta' => $order->delta ?? 0,
            'prepayment_customer' => $order->prepayment_customer ?? 0,
            'prepayment_date' => $order->prepayment_date?->format('Y-m-d'),
            'prepayment_status' => $order->prepayment_status,
            'prepayment_carrier' => $order->prepayment_carrier ?? 0,
            'prepayment_carrier_date' => $order->prepayment_carrier_date?->format('Y-m-d'),
            'prepayment_carrier_status' => $order->prepayment_carrier_status,
            'final_customer' => $order->final_customer ?? 0,
            'final_customer_date' => $order->final_customer_date?->format('Y-m-d'),
            'final_customer_status' => $order->final_customer_status,
            'final_carrier' => $order->final_carrier ?? 0,
            'final_carrier_date' => $order->final_carrier_date?->format('Y-m-d'),
            'final_carrier_status' => $order->final_carrier_status,
            'customer_name' => $order->customer->name ?? '',
            'customer_contact' => $order->customer_contact,
            'carrier_name' => $order->carrier->name ?? '',
            'carrier_contact' => $order->carrier_contact,
            'driver_name' => $order->driver->full_name ?? '',
            'driver_phone' => $order->driver_phone,
            'salary_accrued' => $order->salary_accrued ?? 0,
            'salary_paid' => $order->salary_paid ?? 0,
            'track_number_customer' => $order->track_number_customer,
            'track_status_customer' => $order->track_status_customer,
            'track_number_carrier' => $order->track_number_carrier,
            'track_status_carrier' => $order->track_status_carrier,
            'invoice_number' => $order->invoice_number,
            'upd_number' => $order->upd_number,
            'upd_customer_status' => $order->upd_customer_status,
            'order_customer_status' => $order->order_customer_status,
            'waybill_number' => $order->waybill_number,
            'upd_carrier_status' => $order->upd_carrier_status,
            'order_carrier_status' => $order->order_carrier_status,
        ];
    }
    
    /**
     * Преобразование коллекции заявок
     */
    public function formatCollection($orders): array
    {
        return $orders->map(fn($order) => $this->format($order))->toArray();
    }
}