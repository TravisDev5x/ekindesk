<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Elimina la tabla legacy ticket_audit_logs (auditoría migrada a audit_logs polimórfica).
     */
    public function up(): void
    {
        Schema::dropIfExists('ticket_audit_logs');
    }

    /**
     * Rollback de emergencia: recrea la estructura básica de ticket_audit_logs.
     */
    public function down(): void
    {
        Schema::create('ticket_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('tickets')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }
};
