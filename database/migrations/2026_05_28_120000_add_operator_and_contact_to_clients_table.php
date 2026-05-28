<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            if (! Schema::hasColumn('clients', 'operator_user_id')) {
                $table->foreignId('operator_user_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('users')
                    ->nullOnDelete();
                $table->index('operator_user_id');
            }
            if (! Schema::hasColumn('clients', 'industry')) {
                $table->string('industry')->nullable()->after('name');
            }
            if (! Schema::hasColumn('clients', 'website')) {
                $table->string('website')->nullable()->after('contact_email');
            }
            if (! Schema::hasColumn('clients', 'logo_path')) {
                $table->string('logo_path')->nullable()->after('website');
            }
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            if (Schema::hasColumn('clients', 'operator_user_id')) {
                $table->dropForeign(['operator_user_id']);
                $table->dropColumn('operator_user_id');
            }
            if (Schema::hasColumn('clients', 'industry')) {
                $table->dropColumn('industry');
            }
            if (Schema::hasColumn('clients', 'website')) {
                $table->dropColumn('website');
            }
            if (Schema::hasColumn('clients', 'logo_path')) {
                $table->dropColumn('logo_path');
            }
        });
    }
};
