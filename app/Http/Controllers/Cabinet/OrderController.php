<?php

namespace App\Http\Controllers\Cabinet;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use App\Services\KPI\KpiService;
use App\Services\KPI\PeriodCalculator;
use App\Services\KPI\StatusCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    protected KpiService $kpiService;
    protected PeriodCalculator $periodCalculator;
    protected StatusCalculator $statusCalculator;
    
    public function __construct()
    {
        $this->kpiService = new KpiService();
        $this->periodCalculator = new PeriodCalculator();
        $this->statusCalculator = new StatusCalculator();
    }
    
    public function index()
    {
        $user = auth()->user();
        $isAdminOrSupervisor = $user->isAdmin() || $user->isSupervisor();
        
        if ($isAdminOrSupervisor || $user->isDispatcher()) {
            $orders = Order::with(['manager', 'customer', 'carrier', 'driver'])
                ->orderBy('created_at', 'desc')
                ->get();
        } else {
            $orders = Order::with(['manager', 'customer', 'carrier', 'driver'])
                ->where('manager_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->get();
        }
        
        $managers = [];
        if ($isAdminOrSupervisor || $user->isDispatcher()) {
            $managers = User::whereHas('role', function($q) {
                $q->whereIn('name', ['manager', 'admin', 'supervisor']);
            })->get();
        }
        
        $ordersData = $orders->map(function($order) {
            $status = $this->statusCalculator->calculate($order);
            
            return [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'status_icon' => $status['icon'],
                'status_code' => $status['code'],
                'status_label' => $status['label'],
                'company_code' => $order->company_code,
                'manager_name' => $order->manager->name ?? '',
                'manager_id' => $order->manager_id,
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
                'doc_received_date_customer' => $order->doc_received_date_customer?->format('Y-m-d'),
                'track_number_carrier' => $order->track_number_carrier,
                'doc_received_date_carrier' => $order->doc_received_date_carrier?->format('Y-m-d'),
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
            'managers' => $managers,
            'isAdminOrSupervisor' => $isAdminOrSupervisor
        ]);
    }
    
    public function save(Request $request)
    {
        $data = $request->input('data');
        
        if (!$data) {
            return response()->json(['success' => false, 'error' => 'Нет данных'], 400);
        }
        
        try {
            $order = null;
            if (isset($data['id']) && $data['id']) {
                $order = Order::find($data['id']);
            }
            
            // Подготавливаем данные для сохранения
            $prepareData = [];
            $fillable = (new Order())->getFillable();
            
            foreach ($fillable as $field) {
                if (isset($data[$field])) {
                    $prepareData[$field] = $data[$field];
                }
            }
            
            // Добавляем updated_by
            $prepareData['updated_by'] = auth()->id();
            
            if ($order) {
                // Обновляем существующую заявку
                $order->update($prepareData);
            } else {
                // Создаём новую заявку
                $prepareData['order_number'] = $data['order_number'] ?? null;
                $prepareData['manager_id'] = auth()->id();
                $prepareData['site_id'] = session('current_site')->id ?? 1;
                $prepareData['created_by'] = auth()->id();
                $order = Order::create($prepareData);
            }
            
            // Проверяем, изменились ли критичные для KPI поля
            if (!empty($data['customer_payment_form']) && !empty($data['carrier_payment_form'])) {
                $this->kpiService->recalculateManagerPeriod(
                    $order->manager_id,
                    $order->order_date
                );
                
                // Получаем обновлённую заявку
                $order = $order->fresh();
            }
            
            // Получаем актуальный статус
            $status = $this->statusCalculator->calculate($order);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'status_icon' => $status['icon'],
                    'status_code' => $status['code'],
                    'company_code' => $order->company_code,
                    'manager_name' => $order->manager->name ?? '',
                    'order_date' => $order->order_date?->format('Y-m-d'),
                    'delta' => $order->delta,
                    'kpi_percent' => $order->kpi_percent,
                    'salary_accrued' => $order->salary_accrued,
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Order save error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'data' => $data
            ]);
            
            return response()->json([
                'success' => false, 
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    public function filter(Request $request)
    {
        $user = auth()->user();
        $isAdminOrSupervisor = $user->isAdmin() || $user->isSupervisor();
        $isDispatcher = $user->isDispatcher();
        
        $query = Order::with(['manager', 'customer', 'carrier', 'driver']);
        
        if (!$isAdminOrSupervisor && !$isDispatcher) {
            $query->where('manager_id', $user->id);
        }
        
        if ($request->date_from) {
            $query->whereDate('order_date', '>=', $request->date_from);
        }
        if ($request->date_to) {
            $query->whereDate('order_date', '<=', $request->date_to);
        }
        if ($request->status) {
            $status = $request->status;
            
            if ($status === 'new') {
                $query->whereNull('loading_date');
            } elseif ($status === 'in_progress') {
                $query->whereNotNull('loading_date')->whereNull('unloading_date');
            } elseif ($status === 'documents') {
                $query->whereNotNull('unloading_date')
                      ->where(function($q) {
                          $q->whereNull('upd_customer_status')
                            ->orWhereNull('order_customer_status')
                            ->orWhereNull('waybill_number')
                            ->orWhereNull('upd_carrier_status')
                            ->orWhereNull('order_carrier_status');
                      });
            } elseif ($status === 'payment') {
                $query->whereNotNull('upd_customer_status')
                      ->whereNotNull('order_customer_status')
                      ->whereNotNull('waybill_number')
                      ->whereNotNull('upd_carrier_status')
                      ->whereNotNull('order_carrier_status')
                      ->where(function($q) {
                          $q->where('final_customer', '<=', 0)
                            ->where('prepayment_customer', '<=', 0)
                            ->orWhere('salary_paid', '<=', 0);
                      });
            } elseif ($status === 'completed') {
                $query->whereNotNull('unloading_date')
                      ->whereNotNull('upd_customer_status')
                      ->whereNotNull('order_customer_status')
                      ->whereNotNull('waybill_number')
                      ->whereNotNull('upd_carrier_status')
                      ->whereNotNull('order_carrier_status')
                      ->where('final_customer', '>', 0)
                      ->orWhere('prepayment_customer', '>', 0)
                      ->where('salary_paid', '>', 0);
            }
        }
        
        if (($isAdminOrSupervisor || $isDispatcher) && $request->manager) {
            $query->where('manager_id', $request->manager);
        }
        
        $orders = $query->orderBy('created_at', 'desc')->get();
        
        $ordersData = $orders->map(function($order) {
            $status = $this->statusCalculator->calculate($order);
            
            return [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'status_icon' => $status['icon'],
                'status_code' => $status['code'],
                'status_label' => $status['label'],
                'company_code' => $order->company_code,
                'manager_name' => $order->manager->name ?? '',
                'manager_id' => $order->manager_id,
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
                'doc_received_date_customer' => $order->doc_received_date_customer?->format('Y-m-d'),
                'track_number_carrier' => $order->track_number_carrier,
                'doc_received_date_carrier' => $order->doc_received_date_carrier?->format('Y-m-d'),
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
    
    public function getPeriodOrders(Request $request)
    {
        try {
            $user = auth()->user();
            $isAdminOrSupervisor = $user->isAdmin() || $user->isSupervisor();
            $isDispatcher = $user->isDispatcher();
            
            $managerId = $request->input('manager_id');
            $date = $request->input('date', now()->format('Y-m-d'));
            
            $period = $this->periodCalculator->getPeriodForDate($date);
            
            $query = Order::with(['manager', 'customer', 'carrier', 'driver'])
                ->whereBetween('order_date', [$period['start'], $period['end']]);
            
            if ($isAdminOrSupervisor || $isDispatcher) {
                if ($managerId) {
                    $query->where('manager_id', $managerId);
                }
            } else {
                $query->where('manager_id', $user->id);
            }
            
            $orders = $query->orderBy('created_at', 'desc')->get();
            
            $ordersData = $orders->map(function($order) {
                $status = $this->statusCalculator->calculate($order);
                
                return [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'status_icon' => $status['icon'],
                    'status_code' => $status['code'],
                    'status_label' => $status['label'],
                    'company_code' => $order->company_code,
                    'manager_name' => $order->manager->name ?? '',
                    'manager_id' => $order->manager_id,
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
                    'doc_received_date_customer' => $order->doc_received_date_customer?->format('Y-m-d'),
                    'track_number_carrier' => $order->track_number_carrier,
                    'doc_received_date_carrier' => $order->doc_received_date_carrier?->format('Y-m-d'),
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
        } catch (\Exception $e) {
            Log::error('Error in getPeriodOrders: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    
    public function create(Request $request)
    {
        try {
            $order = new Order();
            
            if ($request->company_code) {
                $order->order_number = Order::generateOrderNumber(
                    $request->company_code,
                    auth()->id()
                );
            }
            
            $order->company_code = $request->company_code;
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
            
            $status = $this->statusCalculator->calculate($order);
            
            return response()->json([
                'success' => true, 
                'order' => [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'company_code' => $order->company_code,
                    'manager_name' => auth()->user()->name,
                    'order_date' => $order->order_date->format('Y-m-d'),
                    'status_icon' => $status['icon'],
                    'status_code' => $status['code'],
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Order create error: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
    
    public function generateNumber(Request $request)
    {
        try {
            $companyCode = $request->input('company_code');
            if (!$companyCode) {
                return response()->json(['success' => false, 'error' => 'Компания не выбрана'], 400);
            }
            
            $managerId = $request->input('manager_id', auth()->id());
            $orderNumber = Order::generateOrderNumber($companyCode, $managerId);
            
            return response()->json([
                'success' => true,
                'order_number' => $orderNumber
            ]);
        } catch (\Exception $e) {
            Log::error('Generate number error: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
    
    public function bulkDelete(Request $request)
    {
        try {
            $ids = $request->input('ids', []);
            
            if (empty($ids)) {
                return response()->json(['success' => false, 'error' => 'Нет выбранных записей'], 400);
            }
            
            if (!auth()->user()->isAdmin() && !auth()->user()->isSupervisor()) {
                return response()->json(['success' => false, 'error' => 'Нет прав для удаления'], 403);
            }
            
            Order::whereIn('id', $ids)->delete();
            
            return response()->json(['success' => true, 'deleted_count' => count($ids)]);
        } catch (\Exception $e) {
            Log::error('Bulk delete error: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
    
    public function update(Request $request, Order $order)
    {
        try {
            $order->update($request->all());
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('Update error: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
    
    public function destroy(Order $order)
    {
        try {
            if (!auth()->user()->isAdmin() && !auth()->user()->isSupervisor()) {
                return response()->json(['success' => false, 'error' => 'Нет прав для удаления'], 403);
            }
            
            $order->delete();
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('Destroy error: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}