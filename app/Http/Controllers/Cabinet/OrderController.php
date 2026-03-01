<?php

namespace App\Http\Controllers\Cabinet;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use App\Services\KPI\KpiService;
use App\Services\KPI\PeriodCalculator;
use App\Services\Order\OrderDataFormatter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    protected KpiService $kpiService;
    protected PeriodCalculator $periodCalculator;
    protected OrderDataFormatter $formatter;
    
    public function __construct()
    {
        $this->kpiService = new KpiService();
        $this->periodCalculator = new PeriodCalculator();
        $this->formatter = new OrderDataFormatter();
    }
    
    public function index()
    {
        $user = auth()->user();
        $isAdminOrSupervisor = $user->isAdmin() || $user->isSupervisor();
        
        // Админ, руководитель и диспетчер видят все заявки
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
        
        // Менеджеры для фильтра
        $managers = [];
        if ($isAdminOrSupervisor || $user->isDispatcher()) {
            $managers = User::whereHas('role', function($q) {
                $q->whereIn('name', ['manager', 'admin', 'supervisor']);
            })->get();
        }
        
        return view('cabinet.orders.index', [
            'orders' => $this->formatter->formatCollection($orders),
            'managers' => $managers,
            'isAdminOrSupervisor' => $isAdminOrSupervisor
        ]);
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
            } elseif ($status === 'awaiting_docs') {
                $query->whereNotNull('unloading_date')
                      ->where(function($q) {
                          $q->whereNull('upd_customer_status')
                            ->orWhereNull('order_customer_status')
                            ->orWhereNull('waybill_number')
                            ->orWhereNull('upd_carrier_status')
                            ->orWhereNull('order_carrier_status');
                      });
            } elseif ($status === 'awaiting_payment') {
                $query->whereNotNull('upd_customer_status')
                      ->whereNotNull('order_customer_status')
                      ->whereNotNull('waybill_number')
                      ->whereNotNull('upd_carrier_status')
                      ->whereNotNull('order_carrier_status')
                      ->where(function($q) {
                          $q->where('final_customer_status', '!=', 'оплачено')
                            ->orWhere('salary_paid', '<=', 0);
                      });
            } elseif ($status === 'completed') {
                $query->whereNotNull('unloading_date')
                      ->whereNotNull('upd_customer_status')
                      ->whereNotNull('order_customer_status')
                      ->whereNotNull('waybill_number')
                      ->whereNotNull('upd_carrier_status')
                      ->whereNotNull('order_carrier_status')
                      ->where('final_customer_status', 'оплачено')
                      ->where('salary_paid', '>', 0);
            }
        }
        
        if (($isAdminOrSupervisor || $isDispatcher) && $request->manager) {
            $query->where('manager_id', $request->manager);
        }
        
        $orders = $query->orderBy('created_at', 'desc')->get();
        
        return response()->json($this->formatter->formatCollection($orders));
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
            
            return response()->json($this->formatter->formatCollection($orders));
        } catch (\Exception $e) {
            Log::error('Error in getPeriodOrders: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
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
            
            // Проверяем права: только создатель может менять номер у существующей заявки
            if ($request->has('order_id') && $request->order_id) {
                $order = Order::find($request->order_id);
                if ($order && $order->manager_id !== auth()->id() && !auth()->user()->isAdmin()) {
                    return response()->json([
                        'success' => false, 
                        'error' => 'Только создатель может изменить номер заявки'
                    ], 403);
                }
            }
            
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
        
            $oldData = $order ? $order->toArray() : null;
        
            // Подготавливаем данные
            $prepareData = $this->prepareData($data);
        
            if ($order) {
                $order->update($prepareData);
            } else {
                $prepareData['order_number'] = null;
                $prepareData['manager_id'] = auth()->id();
                $prepareData['site_id'] = session('current_site')->id ?? 1;
                $prepareData['created_by'] = auth()->id();
                $order = Order::create($prepareData);
            }
        
            // Проверяем, изменились ли критичные для KPI поля
            $kpiRelevantChanged = false;
        
            if ($oldData) {
                $kpiRelevantChanged = 
                    ($oldData['customer_payment_form'] ?? '') !== ($data['customer_payment_form'] ?? '') ||
                    ($oldData['carrier_payment_form'] ?? '') !== ($data['carrier_payment_form'] ?? '') ||
                    ($oldData['order_date'] ?? '') !== ($data['order_date'] ?? '');
            } else {
                $kpiRelevantChanged = !empty($data['customer_payment_form']) && !empty($data['carrier_payment_form']);
            }
        
            if ($kpiRelevantChanged && !empty($data['customer_payment_form']) && !empty($data['carrier_payment_form'])) {
                Log::info('KPI relevant fields changed, recalculating period', [
                    'order_id' => $order->id,
                    'order_date' => $order->order_date
                ]);
            
                $this->kpiService->recalculateManagerPeriod(
                    $order->manager_id,
                    $order->order_date
                );
            
                $order = $order->fresh();
            }
        
            return response()->json([
                'success' => true,
                'data' => $this->formatter->format($order),
            ]);
        
        } catch (\Exception $e) {
            Log::error('Order save error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
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
            
            return response()->json([
                'success' => true, 
                'order' => $this->formatter->format($order)
            ]);
        } catch (\Exception $e) {
            Log::error('Order create error: ' . $e->getMessage());
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
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
    
    public function update(Request $request, Order $order)
    {
        try {
            $order->update($request->all());
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
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
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
    
    private function prepareData(array $data): array
    {
        $fillable = (new Order())->getFillable();
        $data['updated_by'] = auth()->id();
        
        return array_filter($data, function($key) use ($fillable) {
            return in_array($key, $fillable) || $key === 'updated_by';
        }, ARRAY_FILTER_USE_KEY);
    }
}