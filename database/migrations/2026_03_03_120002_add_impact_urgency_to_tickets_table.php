<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->foreignId('impact_level_id')->nullable()->after('priority_id')->constrained('impact_levels')->nullOnDelete();
            $table->foreignId('urgency_level_id')->nullable()->after('impact_level_id')->constrained('urgency_levels')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropForeign(['impact_level_id']);
            $table->dropForeign(['urgency_level_id']);
        });
    }
};
