<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Elimina por completo el módulo TimeDesk y control de asistencias:
 * - schedule_assignments, schedule_days, attendances
 * - employee_profiles (y tablas relacionadas: termination_reasons, employee_statuses, hire_types, recruitment_sources)
 * - schedules
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('schedule_assignments');
        Schema::dropIfExists('schedule_days');
        Schema::dropIfExists('attendances');
        Schema::dropIfExists('employee_profiles');
        Schema::dropIfExists('schedules');
        Schema::dropIfExists('termination_reasons');
        Schema::dropIfExists('employee_statuses');
        Schema::dropIfExists('hire_types');
        Schema::dropIfExists('recruitment_sources');
    }

    public function down(): void
    {
        // No se revierte: el módulo TimeDesk fue eliminado.
    }
};
