<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ticket_classification_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_id');
            $table->string('name');
            // keywords: array de palabras clave en minúsculas a buscar en subject+body
            $table->json('keywords');
            $table->unsignedBigInteger('ticket_type_id')->nullable();
            $table->unsignedBigInteger('priority_id')->nullable();
            $table->boolean('is_active')->default(true);
            // Orden de evaluación — menor número se evalúa primero
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('client_id')->references('id')->on('clients')->cascadeOnDelete();
            $table->foreign('ticket_type_id')->references('id')->on('ticket_types')->nullOnDelete();
            $table->foreign('priority_id')->references('id')->on('priorities')->nullOnDelete();

            $table->index(['client_id', 'is_active', 'sort_order'], 'tcr_client_active_order_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_classification_rules');
    }
};
