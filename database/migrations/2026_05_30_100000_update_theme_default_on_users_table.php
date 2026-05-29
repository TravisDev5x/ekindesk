<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('theme')->default('system')->change();
        });

        DB::table('users')
            ->where('theme', 'light')
            ->where('updated_at', '<', '2026-01-01')
            ->update(['theme' => 'system']);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('theme')->default('light')->change();
        });
    }
};
