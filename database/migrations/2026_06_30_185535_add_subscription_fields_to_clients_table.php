<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            // Plan de suscripción que tiene este cliente en la plataforma EkinDesk
            $table->foreignId('plan_id')
                  ->nullable()
                  ->after('is_active')
                  ->constrained('plans')
                  ->nullOnDelete();

            // Cuándo vence su suscripción actual (null = indefinido / sin vencimiento)
            $table->timestamp('subscription_expires_at')
                  ->nullable()
                  ->after('plan_id');

            // Email de facturación (puede diferir del contact_email)
            $table->string('billing_email')->nullable()->after('subscription_expires_at');

            // Cuándo fue cancelada la cuenta (null = activa). No implica borrado de datos.
            $table->timestamp('cancelled_at')->nullable()->after('billing_email');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropForeign(['plan_id']);
            $table->dropColumn(['plan_id', 'subscription_expires_at', 'billing_email', 'cancelled_at']);
        });
    }
};
