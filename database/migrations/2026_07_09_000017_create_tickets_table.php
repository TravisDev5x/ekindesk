<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            // folio: sequential number per tenant (00001, 00002...)
            $table->string('folio', 10)->nullable();
            // source: origin channel of the ticket (email, web, phone, api)
            $table->string('source', 20)->nullable()->default('web');
            // origin_message_id: Message-Id of the email that originated the ticket (threading)
            $table->string('origin_message_id')->nullable();
            $table->string('subject');
            $table->text('description')->nullable();
            $table->foreignId('area_origin_id')->constrained('areas');
            $table->foreignId('area_current_id')->constrained('areas');
            // Nullable at creation, enforced NOT NULL by 2026_07_09_000027 once
            // integrity is verified (mirrors historical backfill/enforcement window).
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->foreignId('site_id')->constrained('sites');
            $table->foreignId('location_id')->nullable()->constrained('locations');
            $table->foreignId('requester_id')->constrained('users');
            $table->foreignId('requester_position_id')->nullable()->constrained('positions');
            $table->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('assigned_at')->nullable();
            $table->foreignId('ticket_type_id')->constrained('ticket_types');
            $table->foreignId('priority_id')->constrained('priorities');
            $table->foreignId('ticket_state_id')->constrained('ticket_states');
            $table->foreignId('impact_level_id')->nullable()->constrained('impact_levels')->nullOnDelete();
            $table->foreignId('urgency_level_id')->nullable()->constrained('urgency_levels')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->timestamp('first_response_at')->nullable();
            $table->timestamps();

            $table->index(['area_current_id', 'ticket_state_id', 'priority_id']);
            $table->index('due_at');
            $table->index('first_response_at');
            $table->index(['client_id', 'ticket_state_id']);
            $table->index(['assigned_user_id', 'ticket_state_id']);
            $table->index(['client_id', 'created_at'], 'tickets_client_created_idx');
            $table->index(['client_id', 'folio'], 'tickets_client_folio_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
