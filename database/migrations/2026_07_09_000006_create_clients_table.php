<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `operator_user_id` is a plain column here (no FK constraint yet) since `users` doesn't exist
 * until later — the FK is wired up in 2026_07_09_000009_create_users_table.php once both
 * tables exist, breaking the clients<->users circular dependency.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('operator_user_id')->nullable();
            $table->string('name');
            $table->string('code', 20)->nullable();
            $table->string('legal_name')->nullable();
            $table->string('tax_id', 20)->nullable();
            $table->string('industry')->nullable();
            $table->string('contact_name')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone', 20)->nullable();
            $table->string('website')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('portal_slug', 63)->nullable()->unique();
            $table->string('portal_primary_color', 7)->nullable();
            $table->string('portal_welcome_message', 500)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('plan_id')->nullable()->constrained('plans')->nullOnDelete();
            $table->timestamp('subscription_expires_at')->nullable();
            $table->string('billing_email')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('inbound_email')->nullable();
            $table->enum('mode', ['internal', 'msp', 'hybrid'])->default('internal');
            $table->boolean('ai_classification_enabled')->default(true);
            $table->timestamps();

            $table->index('operator_user_id');
            $table->index(['operator_user_id', 'is_active'], 'clients_operator_active_idx');
            $table->unique(['operator_user_id', 'name'], 'clients_operator_name_unique');
            $table->unique(['operator_user_id', 'code'], 'clients_operator_code_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
