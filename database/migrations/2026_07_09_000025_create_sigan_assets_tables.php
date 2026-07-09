<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Inventario de activos (TI y mobiliario) y mantenimientos para Contact Center.
     */
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->string('type', 32); // it | furniture
            $table->string('subtype', 64)->nullable(); // laptop, pc, chair, ups
            $table->string('name');
            $table->string('serial_number')->nullable();
            $table->enum('status', ['active', 'scrap', 'decommissioned', 'maintenance'])->default('active');
            $table->string('location')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('asset_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
            $table->string('component_type', 64); // ram, ssd, disk, etc.
            $table->string('description')->nullable();
            $table->enum('status', ['installed', 'removed'])->default('installed');
            $table->foreignId('reused_in_asset_id')->nullable()->constrained('assets')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('asset_maintenance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
            $table->enum('type', ['internal', 'external']);
            $table->string('provider')->nullable();
            $table->date('performed_at');
            $table->text('description')->nullable();
            $table->string('result', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_maintenance');
        Schema::dropIfExists('asset_components');
        Schema::dropIfExists('assets');
    }
};
