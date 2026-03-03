<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('priority_matrix', function (Blueprint $table) {
            $table->id();
            $table->foreignId('impact_level_id')->constrained('impact_levels')->cascadeOnDelete();
            $table->foreignId('urgency_level_id')->constrained('urgency_levels')->cascadeOnDelete();
            $table->foreignId('priority_id')->constrained('priorities')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['impact_level_id', 'urgency_level_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('priority_matrix');
    }
};
