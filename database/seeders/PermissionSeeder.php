<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Права для администратора
        DB::table('roles')->where('name', 'admin')->update([
            'permissions' => json_encode(['*'])
        ]);
        
        // Права для руководителя
        DB::table('roles')->where('name', 'supervisor')->update([
            'permissions' => json_encode([
                'orders.view_all',
                'orders.edit_all',
                'orders.export',
                'reports.view',
                'reports.export',
                'users.view',
                'settings.view',
                'settings.edit'
            ])
        ]);
        
        // Права для менеджера
        DB::table('roles')->where('name', 'manager')->update([
            'permissions' => json_encode([
                'orders.view_own',
                'orders.create',
                'orders.edit_own',
                'orders.delete_own',
                'orders.export_own'
            ])
        ]);
        
        // Права для бухгалтера
        DB::table('roles')->where('name', 'accountant')->update([
            'permissions' => json_encode([
                'orders.view_all',
                'orders.edit_finance',
                'reports.view_finance',
                'reports.export_finance'
            ])
        ]);
        
        // Права для диспетчера
        DB::table('roles')->where('name', 'dispatcher')->update([
            'permissions' => json_encode([
                'orders.view_all',
                'orders.assign',
                'orders.status_edit'
            ])
        ]);
    }
}
