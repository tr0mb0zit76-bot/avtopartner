<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salary_coefficients', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('coefficient', 5, 2);
            $table->string('condition_type'); // 'deal_count', 'kpi_average', etc.
            $table->json('condition_value')->nullable();
            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_coefficients');
    }
};