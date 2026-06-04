<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('user_invitations') && Schema::hasColumn('user_invitations', 'role_id')) {
            Schema::table('user_invitations', function (Blueprint $table) {
                $table->unsignedBigInteger('role_id')->nullable()->change();
            });
        }

        if (Schema::hasTable('users') && ! Schema::hasColumn('users', 'google_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('google_id')->nullable()->unique()->after('email');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'google_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropUnique(['google_id']);
                $table->dropColumn('google_id');
            });
        }

        if (Schema::hasTable('user_invitations') && Schema::hasColumn('user_invitations', 'role_id')) {
            Schema::table('user_invitations', function (Blueprint $table) {
                $table->unsignedBigInteger('role_id')->nullable(false)->change();
            });
        }
    }
};
