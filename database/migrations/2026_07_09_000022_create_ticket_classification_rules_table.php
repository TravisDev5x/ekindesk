<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_classification_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_id');
            $table->string('name');
            // keywords: array of lowercase keywords to search for in subject+body
            $table->json('keywords');
            $table->unsignedBigInteger('ticket_type_id')->nullable();
            $table->unsignedBigInteger('priority_id')->nullable();
            $table->boolean('is_active')->default(true);
            // Evaluation order — lower number is evaluated first
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('client_id')->references('id')->on('clients')->cascadeOnDelete();
            $table->foreign('ticket_type_id')->references('id')->on('ticket_types')->nullOnDelete();
            $table->foreign('priority_id')->references('id')->on('priorities')->nullOnDelete();

            $table->index(['client_id', 'is_active', 'sort_order'], 'tcr_client_active_order_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_classification_rules');
    }
};
