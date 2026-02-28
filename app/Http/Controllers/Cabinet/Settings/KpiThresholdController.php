<?php

namespace App\Http\Controllers\Cabinet\Settings;

use App\Http\Controllers\Controller;
use App\Models\KpiThreshold;
use App\Models\SalaryCoefficient;
use App\Models\Order;
use App\Models\User;
use App\Services\KPI\KpiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class KpiThresholdController extends Controller
{
    protected KpiService $kpiService;
    
    public function __construct()
    {
        $this->kpiService = new KpiService();
    }
    
    protected function checkAccess()
    {
        if (!auth()->check()) {
            return redirect()->route('cabinet.login');
        }
        
        if (!auth()->user()->isAdmin() && !auth()->user()->isSupervisor()) {
            abort(403, 'Доступ запрещён');
        }
    }
    
    public function index()
    {
        $this->checkAccess();
        
        // Получаем пороги KPI
        $directThresholds = KpiThreshold::where('deal_type', 'direct')
            ->orderBy('threshold_from', 'desc')
            ->get();
            
        $indirectThresholds = KpiThreshold::where('deal_type', 'indirect')
            ->orderBy('threshold_from', 'desc')
            ->get();
        
        // Получаем всех менеджеров для настройки зарплаты
        $managers = User::whereHas('role', function($q) {
            $q->whereIn('name', ['manager', 'admin', 'supervisor']);
        })->orderBy('name')->get();
        
        // Получаем текущие коэффициенты зарплаты
        $salaryCoefficients = [];
        foreach ($managers as $manager) {
            $coeff = SalaryCoefficient::where('manager_id', $manager->id)
                ->where('is_active', true)
                ->orderBy('effective_from', 'desc')
                ->first();
            $salaryCoefficients[$manager->id] = $coeff ?? null;
        }
        
        Log::info('KPI settings page loaded', [
            'direct_thresholds_count' => $directThresholds->count(),
            'indirect_thresholds_count' => $indirectThresholds->count(),
            'managers_count' => $managers->count()
        ]);
        
        return view('cabinet.settings.kpi', compact(
            'directThresholds', 
            'indirectThresholds',
            'managers',
            'salaryCoefficients'
        ));
    }
    
    public function update(Request $request)
    {
        $this->checkAccess();
        
        Log::info('Updating KPI settings', [
            'has_thresholds' => $request->has('thresholds'),
            'has_salary' => $request->has('salary'),
            'request_data' => $request->except('_token')
        ]);
        
        try {
            DB::beginTransaction();
            
            // Обновляем пороги KPI
            if ($request->has('thresholds')) {
                Log::info('Updating KPI thresholds');
                
                // Очищаем старые пороги
                $deleted = KpiThreshold::query()->delete();
                Log::info('Deleted old thresholds', ['count' => $deleted]);
                
                foreach ($request->thresholds as $index => $threshold) {
                    Log::debug('Processing threshold', [
                        'index' => $index,
                        'from' => $threshold['from'],
                        'to' => $threshold['to']
                    ]);
                    
                    // Прямая сделка
                    if (isset($request->direct[$index]['kpi_percent'])) {
                        $directKpi = $request->direct[$index]['kpi_percent'];
                        KpiThreshold::create([
                            'deal_type' => 'direct',
                            'threshold_from' => $threshold['from'],
                            'threshold_to' => $threshold['to'],
                            'kpi_percent' => $directKpi,
                            'is_active' => true
                        ]);
                        Log::debug('Created direct threshold', [
                            'from' => $threshold['from'],
                            'to' => $threshold['to'],
                            'kpi' => $directKpi
                        ]);
                    }
                    
                    // Кривая сделка
                    if (isset($request->indirect[$index]['kpi_percent'])) {
                        $indirectKpi = $request->indirect[$index]['kpi_percent'];
                        KpiThreshold::create([
                            'deal_type' => 'indirect',
                            'threshold_from' => $threshold['from'],
                            'threshold_to' => $threshold['to'],
                            'kpi_percent' => $indirectKpi,
                            'is_active' => true
                        ]);
                        Log::debug('Created indirect threshold', [
                            'from' => $threshold['from'],
                            'to' => $threshold['to'],
                            'kpi' => $indirectKpi
                        ]);
                    }
                }
            }
            
            // Обновляем коэффициенты зарплаты
            $updatedManagers = [];
            if ($request->has('salary')) {
                Log::info('Updating salary coefficients');
                
                foreach ($request->salary as $managerId => $data) {
                    // Проверяем, что менеджер существует
                    $manager = User::find($managerId);
                    if (!$manager) {
                        Log::warning('Manager not found', ['manager_id' => $managerId]);
                        continue;
                    }
                    
                    $effectiveFrom = $data['effective_from'] ?? now()->format('Y-m-d');
                    $baseSalary = intval($data['base_salary'] ?? 0);
                    $bonusPercent = intval($data['bonus_percent'] ?? 0);
                    
                    Log::info('Saving salary coefficients', [
                        'manager_id' => $managerId,
                        'manager_name' => $manager->name,
                        'base_salary' => $baseSalary,
                        'bonus_percent' => $bonusPercent,
                        'effective_from' => $effectiveFrom
                    ]);
                    
                    // Проверяем, существует ли уже активная запись на эту дату
                    $existing = SalaryCoefficient::where('manager_id', $managerId)
                        ->where('effective_from', $effectiveFrom)
                        ->first();
                    
                    if ($existing) {
                        // Обновляем существующую запись
                        $existing->update([
                            'base_salary' => $baseSalary,
                            'bonus_percent' => $bonusPercent,
                            'is_active' => true
                        ]);
                        Log::info('Updated existing salary coefficient', [
                            'id' => $existing->id,
                            'manager_id' => $managerId
                        ]);
                    } else {
                        // Деактивируем все старые активные записи
                        $deactivated = SalaryCoefficient::where('manager_id', $managerId)
                            ->where('is_active', true)
                            ->update(['is_active' => false]);
                        
                        Log::info('Deactivated old coefficients', [
                            'manager_id' => $managerId,
                            'count' => $deactivated
                        ]);
                        
                        // Создаём новую запись
                        $new = SalaryCoefficient::create([
                            'manager_id' => $managerId,
                            'base_salary' => $baseSalary,
                            'bonus_percent' => $bonusPercent,
                            'effective_from' => $effectiveFrom,
                            'is_active' => true
                        ]);
                        
                        Log::info('Created new salary coefficient', [
                            'id' => $new->id,
                            'manager_id' => $managerId
                        ]);
                    }
                    
                    $updatedManagers[] = $managerId;
                }
            }
            
            DB::commit();
            
            // После успешного сохранения пересчитываем все заявки обновлённых менеджеров
            $recalculatedCount = 0;
            $recalculatedManagers = [];
            
            foreach ($updatedManagers as $managerId) {
                Log::info('Recalculating orders for manager', ['manager_id' => $managerId]);
                
                // Получаем все заявки менеджера
                $orders = Order::where('manager_id', $managerId)
                    ->whereNotNull('customer_payment_form')
                    ->whereNotNull('carrier_payment_form')
                    ->get();
                
                $managerRecalculated = 0;
                foreach ($orders as $order) {
                    // Пересчитываем каждую заявку
                    $result = $this->kpiService->calculateForOrder($order);
                    
                    $order->update([
                        'kpi_percent' => $result['kpi_percent'],
                        'delta' => $result['delta'],
                        'salary_accrued' => $result['salary_accrued']
                    ]);
                    
                    $managerRecalculated++;
                    $recalculatedCount++;
                    
                    Log::debug('Order recalculated after settings update', [
                        'order_id' => $order->id,
                        'kpi_percent' => $result['kpi_percent'],
                        'delta' => $result['delta'],
                        'salary' => $result['salary_accrued']
                    ]);
                }
                
                if ($managerRecalculated > 0) {
                    $recalculatedManagers[] = "{$manager->name} ($managerRecalculated)";
                }
            }
            
            $managersStr = implode(', ', $recalculatedManagers);
            
            return redirect()->route('cabinet.settings.kpi')
                ->with('success', "Настройки успешно обновлены. Пересчитано заявок: $recalculatedCount ($managersStr)");
                
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating settings: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()
                ->with('error', 'Ошибка при обновлении: ' . $e->getMessage());
        }
    }
    
    /**
     * Ручной пересчёт для конкретного менеджера
     */
    public function recalculateManager($managerId)
    {
        $this->checkAccess();
        
        try {
            $manager = User::findOrFail($managerId);
            
            $orders = Order::where('manager_id', $managerId)
                ->whereNotNull('customer_payment_form')
                ->whereNotNull('carrier_payment_form')
                ->get();
            
            $count = 0;
            foreach ($orders as $order) {
                $result = $this->kpiService->calculateForOrder($order);
                
                $order->update([
                    'kpi_percent' => $result['kpi_percent'],
                    'delta' => $result['delta'],
                    'salary_accrued' => $result['salary_accrued']
                ]);
                
                $count++;
            }
            
            Log::info('Manual recalculation completed', [
                'manager_id' => $managerId,
                'manager_name' => $manager->name,
                'recalculated' => $count
            ]);
            
            return response()->json([
                'success' => true,
                'count' => $count,
                'message' => "Пересчитано заявок: $count"
            ]);
            
        } catch (\Exception $e) {
            Log::error('Manual recalculation error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}