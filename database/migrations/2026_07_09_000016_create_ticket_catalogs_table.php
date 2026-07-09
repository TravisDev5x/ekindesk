<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('priorities', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->unsignedTinyInteger('level')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->foreignId('operator_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->index(['client_id', 'operator_user_id']);
        });

        Schema::create('ticket_states', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('code')->unique();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_final')->default(false);
            $table->timestamps();
            $table->foreignId('operator_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->index(['client_id', 'operator_user_id']);
        });

        Schema::create('ticket_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('code')->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->foreignId('operator_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->index(['client_id', 'operator_user_id']);
        });

        Schema::create('area_ticket_type', function (Blueprint $table) {
            $table->id();
            $table->foreignId('area_id')->constrained('areas')->cascadeOnDelete();
            $table->foreignId('ticket_type_id')->constrained('ticket_types')->cascadeOnDelete();
            $table->unique(['area_id', 'ticket_type_id']);
        });

        Schema::create('impact_levels', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedTinyInteger('weight')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->foreignId('operator_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->index(['client_id', 'operator_user_id']);
        });

        Schema::create('urgency_levels', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedTinyInteger('weight')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->foreignId('operator_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->index(['client_id', 'operator_user_id']);
        });

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
        Schema::dropIfExists('urgency_levels');
        Schema::dropIfExists('impact_levels');
        Schema::dropIfExists('area_ticket_type');
        Schema::dropIfExists('ticket_types');
        Schema::dropIfExists('ticket_states');
        Schema::dropIfExists('priorities');
    }
};
