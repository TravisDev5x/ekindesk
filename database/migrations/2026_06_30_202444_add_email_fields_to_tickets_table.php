<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            // folio: número secuencial por tenant (00001, 00002…)
            if (! Schema::hasColumn('tickets', 'folio')) {
                $table->string('folio', 10)->nullable()->after('id');
                $table->index(['client_id', 'folio'], 'tickets_client_folio_idx');
            }
            // source: canal de origen del ticket (email, web, phone, api)
            if (! Schema::hasColumn('tickets', 'source')) {
                $table->string('source', 20)->nullable()->default('web')->after('folio');
            }
            // origin_message_id: Message-Id del email que originó el ticket (threading)
            if (! Schema::hasColumn('tickets', 'origin_message_id')) {
                $table->string('origin_message_id')->nullable()->after('source');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            if (Schema::hasColumn('tickets', 'folio')) {
                $table->dropIndex('tickets_client_folio_idx');
                $table->dropColumn('folio');
            }
            foreach (['source', 'origin_message_id'] as $col) {
                if (Schema::hasColumn('tickets', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
