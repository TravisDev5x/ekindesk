<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            // employee_number retained for legacy data, deprecated in favor of invitation flow
            $table->string('employee_number')->nullable()->unique();
            $table->string('first_name', 255)->nullable();
            $table->string('paternal_last_name', 255)->nullable();
            $table->string('maternal_last_name', 255)->nullable();
            // name kept in sync by the User model for backward-compat raw queries
            $table->string('name')->nullable();
            $table->string('email')->nullable()->unique();
            $table->string('google_id')->nullable()->unique();
            $table->string('phone', 10)->nullable();
            $table->string('avatar_path', 2048)->nullable();
            $table->foreignId('campaign_id')->nullable()->constrained('campaigns')->nullOnDelete();
            $table->foreignId('area_id')->nullable()->constrained('areas')->nullOnDelete();
            $table->foreignId('position_id')->nullable()->constrained('positions')->nullOnDelete();
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->boolean('is_operator')->default(false);
            $table->boolean('onboarding_completed')->default(false);
            $table->foreignId('site_id')->constrained('sites');
            $table->foreignId('location_id')->nullable()->constrained('locations');
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip', 45)->nullable();
            $table->string('password');
            $table->boolean('force_password_change')->default(false);
            $table->rememberToken();
            $table->string('status')->default('active');
            $table->string('theme')->default('system');
            $table->string('ui_density')->default('normal');
            $table->string('sidebar_state')->default('expanded');
            $table->boolean('sidebar_hover_preview')->default(false);
            $table->string('sidebar_position', 10)->default('left');
            $table->string('locale', 5)->default('es');
            // Estado de disponibilidad para futuro chat interno.
            // available = disponible, busy = ocupado, disconnected = desconectado.
            $table->string('availability', 20)->default('disconnected');
            $table->timestamps();
            $table->softDeletes();
            $table->text('deletion_reason')->nullable();
            $table->boolean('is_blacklisted')->default(false);

            $table->index('status');
            $table->index(['status', 'is_blacklisted']);
            $table->index(['client_id', 'status']);
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });

        Schema::create('blacklist_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('admin_id')->nullable()->constrained('users');
            $table->string('action');
            $table->text('reason');
            $table->timestamps();
        });

        // Now that `users` exists, wire up the pending FK from clients.operator_user_id
        // (clients was created earlier without this constraint to break the circular
        // clients<->users dependency).
        Schema::table('clients', function (Blueprint $table) {
            $table->foreign('operator_user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropForeign(['operator_user_id']);
        });
        Schema::dropIfExists('blacklist_logs');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
