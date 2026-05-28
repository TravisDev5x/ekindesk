<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY employee_number VARCHAR(255) NULL COMMENT 'employee_number retained for legacy data, deprecated in favor of invitation flow'");
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('employee_number')->nullable()->change();
        });
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE users MODIFY employee_number VARCHAR(255) NOT NULL');
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('employee_number')->nullable(false)->change();
        });
    }
};
