<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('plans', 'type')) {
            return;
        }

        Schema::table('plans', function (Blueprint $table) {
            $table->enum('type', ['inhouse', 'msp', 'both'])->default('msp')->after('slug');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('plans', 'type')) {
            return;
        }

        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
