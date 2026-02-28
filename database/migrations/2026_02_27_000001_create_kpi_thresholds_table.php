<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('kpi_thresholds', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable(); // например "Порог 80%"
            $table->decimal('vat_ratio', 3, 2); // 0.80, 0.60, 0.50 и т.д.
            $table->integer('direct_kpi'); // KPI для прямых сделок
            $table->integer('indirect_kpi'); // KPI для кривых сделок
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('kpi_thresholds');
    }
};