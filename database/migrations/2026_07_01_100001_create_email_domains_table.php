<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dominios de email por tenant.
 *
 * Cada cliente puede tener uno o más dominios de entrada para recibir tickets
 * por email (ej: soporte@acme.com → ticket en tenant "acme").
 *
 * inbound_address: alias de recepción configurado en Mailgun/SES
 *   ej: tickets+acme@in.tikara.mx
 *
 * verification_token: token para verificar que el cliente controla el dominio
 *   (registro TXT en DNS: tikara-verify=<token>)
 *
 * provider_route_id: ID de la ruta en Mailgun/SES (para poder eliminarla al desactivar)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_domains', function (Blueprint $table) {
            $table->id();
            // client_id es bigint (clients.id es auto-increment bigint, no UUID)
            $table->unsignedBigInteger('client_id');
            $table->string('domain')->unique();
            $table->string('inbound_address');
            $table->boolean('is_verified')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->string('verification_token', 64)->nullable();
            $table->string('provider_route_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('client_id')
                ->references('id')
                ->on('clients')
                ->cascadeOnDelete();

            $table->index(['client_id', 'is_active']);
            $table->index(['domain', 'is_verified']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_domains');
    }
};
