<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Удаляем строки с kpi_percent = 8 и 12
        DB::table('kpi_thresholds')
            ->whereIn('kpi_percent', [8, 12])
            ->delete();
    }

    public function down(): void
    {
        // Восстанавливать не будем, так как это данные
    }
};
