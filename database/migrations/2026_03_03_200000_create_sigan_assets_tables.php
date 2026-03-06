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
        Schema::create('sigan_assets', function (Blueprint $table) {
            $table->id();
            $table->string('tipo', 32); // ti | mobiliario
            $table->string('subtipo', 64)->nullable(); // laptop, pc, silla, ups
            $table->string('nombre');
            $table->string('numero_serie')->nullable();
            $table->enum('estado', ['activo', 'scrap', 'baja', 'mantenimiento'])->default('activo');
            $table->string('ubicacion')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });

        Schema::create('sigan_asset_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained('sigan_assets')->cascadeOnDelete();
            $table->string('tipo_componente', 64); // ram, ssd, disco, etc.
            $table->string('descripcion')->nullable();
            $table->enum('estado', ['instalado', 'extraido'])->default('instalado');
            $table->foreignId('usado_en_asset_id')->nullable()->constrained('sigan_assets')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('sigan_maintenance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained('sigan_assets')->cascadeOnDelete();
            $table->enum('tipo', ['interno', 'externo']);
            $table->string('proveedor')->nullable();
            $table->date('fecha');
            $table->text('descripcion')->nullable();
            $table->string('resultado', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sigan_maintenance');
        Schema::dropIfExists('sigan_asset_components');
        Schema::dropIfExists('sigan_assets');
    }
};
