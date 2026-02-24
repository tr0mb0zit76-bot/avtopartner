<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RolesTableSeeder extends Seeder
{
    public function run(): void
    {
        // Очищаем таблицу перед заполнением (если нужно)
        // DB::table('roles')->truncate();
        
        $roles = [
            [
                'name' => 'admin',
                'display_name' => 'Администратор',
                'description' => 'Полный доступ к системе',
                'permissions' => json_encode(['*']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'supervisor',
                'display_name' => 'Руководитель',
                'description' => 'Управление процессом, просмотр всех заявок, отчёты',
                'permissions' => json_encode([
                    'users.view',
                    'orders.view_all',
                    'orders.edit',
                    'reports.view',
                    'reports.export',
                    'settings.view',
                    'settings.edit'
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'manager',
                'display_name' => 'Менеджер',
                'description' => 'Ведение своих заявок, работа с парсером',
                'permissions' => json_encode([
                    'orders.view_own',
                    'orders.create',
                    'orders.edit_own',
                    'parser.view',
                    'parser.process'
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'accountant',
                'display_name' => 'Бухгалтер',
                'description' => 'Работа с финансами и документами',
                'permissions' => json_encode([
                    'orders.view_all',
                    'orders.edit_finance',
                    'reports.view_finance',
                    'documents.view',
                    'documents.edit'
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'dispatcher',
                'display_name' => 'Диспетчер',
                'description' => 'Распределение заявок, контроль',
                'permissions' => json_encode([
                    'orders.view_all',
                    'orders.assign',
                    'parser.view',
                    'parser.assign'
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($roles as $role) {
            DB::table('roles')->insert($role);
        }
        
        $this->command->info('Roles seeded successfully!');
    }
}
