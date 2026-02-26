<?php

namespace App\Http\Controllers\Cabinet;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    /**
     * Display a listing of the orders.
     */
    public function index()
    {
        // Получаем все заявки с связанными данными
        $orders = Order::with(['manager', 'customer', 'carrier', 'driver'])
            ->orderBy('created_at', 'desc')
            ->get();
        
        // Получаем менеджеров для фильтра
        $managers = User::whereHas('role', function($q) {
            $q->whereIn('name', ['manager', 'admin', 'supervisor']);
        })->get();
        
        // Преобразуем данные для Handsontable со ВСЕМИ полями
        $ordersData = $orders->map(function($order) {
            return [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'company_code' => $order->company_code,
                'manager_name' => $order->manager->name ?? '',
                'order_date' => $order->order_date?->format('Y-m-d'),
                
                // Маршрут / груз
                'loading_point' => $order->loading_point,
                'unloading_point' => $order->unloading_point,
                'cargo_description' => $order->cargo_description,
                'loading_date' => $order->loading_date?->format('Y-m-d'),
                'unloading_date' => $order->unloading_date?->format('Y-m-d'),
                
                // Финансы заказчика
                'customer_rate' => $order->customer_rate,
                'customer_payment_form' => $order->customer_payment_form,
                'customer_payment_term' => $order->customer_payment_term,
                
                // Финансы перевозчика
                'carrier_rate' => $order->carrier_rate,
                'carrier_payment_form' => $order->carrier_payment_form,
                'carrier_payment_term' => $order->carrier_payment_term,
                
                // Расходы
                'additional_expenses' => $order->additional_expenses,
                'insurance' => $order->insurance,
                'bonus' => $order->bonus,
                
                // KPI и дельта
                'kpi_percent' => $order->kpi_percent,
                'delta' => $order->delta,
                
                // ОПЛАТА - блок как в Excel
                'prepayment_customer' => $order->prepayment_customer,
                'prepayment_date' => $order->prepayment_date?->format('Y-m-d'),
                'prepayment_status' => $order->prepayment_status,
                'prepayment_carrier' => $order->prepayment_carrier,
                'prepayment_carrier_date' => $order->prepayment_carrier_date?->format('Y-m-d'),
                'prepayment_carrier_status' => $order->prepayment_carrier_status,
                'final_customer' => $order->final_customer,
                'final_customer_date' => $order->final_customer_date?->format('Y-m-d'),
                'final_customer_status' => $order->final_customer_status,
                'final_carrier' => $order->final_carrier,
                'final_carrier_date' => $order->final_carrier_date?->format('Y-m-d'),
                'final_carrier_status' => $order->final_carrier_status,
                
                // Контрагенты
                'customer_name' => $order->customer->name ?? '',
                'customer_contact' => $order->customer_contact,
                'carrier_name' => $order->carrier->name ?? '',
                'carrier_contact' => $order->carrier_contact,
                'driver_name' => $order->driver->full_name ?? '',
                'driver_phone' => $order->driver_phone,
                
                // Зарплата
                'salary_accrued' => $order->salary_accrued,
                'salary_paid' => $order->salary_paid,
                
                // Документы
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
        });
        
        return view('cabinet.orders.index', [
            'orders' => $ordersData,
            'managers' => $managers
        ]);
    }
    
    /**
     * Save order data from Handsontable.
     */
    public function save(Request $request)
    {
        $data = $request->input('data');
        
        try {
            // Ищем заявку по номеру или ID
            $order = null;
            if (isset($data['id'])) {
                $order = Order::find($data['id']);
            }
            
            if ($order) {
                // Обновляем существующую
                $order->update($this->prepareData($data));
            } else {
                // Создаём новую
                $data['order_number'] = $data['order_number'] ?? $this->generateOrderNumber();
                $data['manager_id'] = auth()->id();
                $data['site_id'] = session('current_site')->id ?? 1;
                $data['created_by'] = auth()->id();
                Order::create($this->prepareData($data));
            }
            
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Filter orders.
     */
    public function filter(Request $request)
    {
        $query = Order::with(['manager', 'customer', 'carrier', 'driver']);
        
        if ($request->date_from) {
            $query->whereDate('order_date', '>=', $request->date_from);
        }
        if ($request->date_to) {
            $query->whereDate('order_date', '<=', $request->date_to);
        }
        if ($request->status) {
            $query->where('status', $request->status);
        }
        if ($request->manager) {
            $query->where('manager_id', $request->manager);
        }
        
        $orders = $query->orderBy('created_at', 'desc')->get();
        
        $ordersData = $orders->map(function($order) {
            return [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'company_code' => $order->company_code,
                'manager_name' => $order->manager->name ?? '',
                'order_date' => $order->order_date?->format('Y-m-d'),
                'loading_point' => $order->loading_point,
                'unloading_point' => $order->unloading_point,
                'cargo_description' => $order->cargo_description,
                'loading_date' => $order->loading_date?->format('Y-m-d'),
                'unloading_date' => $order->unloading_date?->format('Y-m-d'),
                'customer_rate' => $order->customer_rate,
                'customer_payment_form' => $order->customer_payment_form,
                'customer_payment_term' => $order->customer_payment_term,
                'carrier_rate' => $order->carrier_rate,
                'carrier_payment_form' => $order->carrier_payment_form,
                'carrier_payment_term' => $order->carrier_payment_term,
                'additional_expenses' => $order->additional_expenses,
                'insurance' => $order->insurance,
                'bonus' => $order->bonus,
                'kpi_percent' => $order->kpi_percent,
                'delta' => $order->delta,
                'prepayment_customer' => $order->prepayment_customer,
                'prepayment_date' => $order->prepayment_date?->format('Y-m-d'),
                'prepayment_status' => $order->prepayment_status,
                'prepayment_carrier' => $order->prepayment_carrier,
                'prepayment_carrier_date' => $order->prepayment_carrier_date?->format('Y-m-d'),
                'prepayment_carrier_status' => $order->prepayment_carrier_status,
                'final_customer' => $order->final_customer,
                'final_customer_date' => $order->final_customer_date?->format('Y-m-d'),
                'final_customer_status' => $order->final_customer_status,
                'final_carrier' => $order->final_carrier,
                'final_carrier_date' => $order->final_carrier_date?->format('Y-m-d'),
                'final_carrier_status' => $order->final_carrier_status,
                'customer_name' => $order->customer->name ?? '',
                'customer_contact' => $order->customer_contact,
                'carrier_name' => $order->carrier->name ?? '',
                'carrier_contact' => $order->carrier_contact,
                'driver_name' => $order->driver->full_name ?? '',
                'driver_phone' => $order->driver_phone,
                'salary_accrued' => $order->salary_accrued,
                'salary_paid' => $order->salary_paid,
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
        });
        
        return response()->json($ordersData);
    }
    
    /**
     * Create new order.
     */
    public function create(Request $request)
    {
        try {
            $order = new Order();
            $order->order_number = Order::generateOrderNumber(
                $request->company_code ?? 'ЛР',
                auth()->id()
            );
            $order->company_code = $request->company_code ?? 'ЛР';
            $order->manager_id = auth()->id();
            $order->site_id = session('current_site')->id ?? 1;
            $order->order_date = now();
            $order->additional_expenses = 0;
            $order->insurance = 0;
            $order->bonus = 0;
            $order->status = 'new';
            $order->is_active = true;
            $order->created_by = auth()->id();
            $order->save();
            
            return response()->json([
                'success' => true, 
                'order' => [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'company_code' => $order->company_code,
                    'manager_name' => auth()->user()->name,
                    'order_date' => $order->order_date->format('Y-m-d'),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Update order.
     */
    public function update(Request $request, Order $order)
    {
        try {
            $order->update($this->prepareData($request->all()));
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Delete order.
     */
    public function destroy(Order $order)
    {
        try {
            // Проверка прав
            if (!auth()->user()->isAdmin() && !auth()->user()->isSupervisor()) {
                return response()->json(['success' => false, 'error' => 'Нет прав для удаления'], 403);
            }
            
            $order->delete();
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Prepare data for saving.
     */
    private function prepareData(array $data): array
    {
        // Убираем поля, которых нет в таблице
        $fillable = (new Order())->getFillable();
        
        // Добавляем updated_by
        $data['updated_by'] = auth()->id();
        
        return array_filter($data, function($key) use ($fillable) {
            return in_array($key, $fillable) || $key === 'updated_by';
        }, ARRAY_FILTER_USE_KEY);
    }
    
    /**
     * Generate unique order number.
     */
    private function generateOrderNumber(): string
    {
        $prefix = 'ORD';
        $year = now()->format('y');
        $month = now()->format('m');
        
        $lastOrder = Order::whereYear('created_at', now()->year)
            ->orderBy('id', 'desc')
            ->first();
        
        if ($lastOrder && preg_match('/(\d+)$/', $lastOrder->order_number, $matches)) {
            $sequence = intval($matches[1]) + 1;
        } else {
            $sequence = 1;
        }
        
        return sprintf('%s-%s%s-%04d', $prefix, $year, $month, $sequence);
    }
}