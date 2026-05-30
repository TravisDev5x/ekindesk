<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ticket_histories', function (Blueprint $table) {
            $table->index(
                ['ticket_id', 'ticket_state_id', 'created_at'],
                'idx_history_ticket_state_date'
            );
        });
    }

    public function down(): void
    {
        Schema::table('ticket_histories', function (Blueprint $table) {
            $table->dropIndex('idx_history_ticket_state_date');
        });
    }
};
