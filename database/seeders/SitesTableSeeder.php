<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SitesTableSeeder extends Seeder
{
    public function run(): void
    {
        // Проверяем, есть ли уже сайты
        $count = DB::table('sites')->count();
        
        if ($count === 0) {
            // Добавляем сайты только если таблица пуста
            DB::table('sites')->insert([
                [
                    'domain' => 'avtopartner.pro',
                    'name' => 'Автопартнер Поволжье',
                    'theme' => 'avtopartner',
                    'home_url' => 'https://avtopartner.pro',
                    'is_active' => true,
                    'settings' => json_encode(['default_theme' => 'light']),
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'domain' => 'log-sol.ru',
                    'name' => 'Логистические Решения',
                    'theme' => 'logist',
                    'home_url' => 'https://log-sol.ru',
                    'is_active' => true,
                    'settings' => json_encode(['default_theme' => 'light']),
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
            
            $this->command->info('Сайты успешно добавлены!');
        } else {
            $this->command->info('Таблица sites уже содержит данные. Пропускаем...');
        }
    }
}