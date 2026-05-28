<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('client_id')
                ->nullable()
                ->after('sede_id')
                ->constrained('clients')
                ->nullOnDelete();
            $table->boolean('is_operator')->default(false)->after('client_id');
            $table->boolean('onboarding_completed')->default(false)->after('is_operator');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['client_id']);
            $table->dropColumn(['client_id', 'is_operator', 'onboarding_completed']);
        });
    }
};
