<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            if (! Schema::hasColumn('clients', 'inbound_email')) {
                $table->string('inbound_email')->nullable()->after('billing_email');
            }
            if (! Schema::hasColumn('clients', 'mode')) {
                $table->enum('mode', ['internal', 'msp', 'hybrid'])
                    ->default('internal')
                    ->after('inbound_email');
            }
            if (! Schema::hasColumn('clients', 'ai_classification_enabled')) {
                $table->boolean('ai_classification_enabled')
                    ->default(true)
                    ->after('mode');
            }
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            foreach (['ai_classification_enabled', 'mode', 'inbound_email'] as $col) {
                if (Schema::hasColumn('clients', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
