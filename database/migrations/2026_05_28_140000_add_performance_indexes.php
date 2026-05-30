<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->index('read_at');
            $table->index(['notifiable_id', 'notifiable_type', 'created_at'], 'notifications_notifiable_created_idx');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->index('status');
            $table->index(['status', 'is_blacklisted']);
            $table->index(['client_id', 'status']);
        });

        Schema::table('tickets', function (Blueprint $table) {
            $table->index(['client_id', 'ticket_state_id']);
            $table->index(['assigned_user_id', 'ticket_state_id']);
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex(['read_at']);
            $table->dropIndex('notifications_notifiable_created_idx');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['status', 'is_blacklisted']);
            $table->dropIndex(['client_id', 'status']);
        });

        Schema::table('tickets', function (Blueprint $table) {
            $table->dropIndex(['client_id', 'ticket_state_id']);
            $table->dropIndex(['assigned_user_id', 'ticket_state_id']);
        });
    }
};
