<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-tenant inbound email domains.
 *
 * Each client can have one or more inbound domains to receive tickets by email
 * (e.g. support@acme.com -> ticket in tenant "acme").
 *
 * inbound_address: receiving alias configured in Mailgun/SES
 *   e.g. tickets+acme@in.tikara.mx
 *
 * verification_token: token to verify the client controls the domain
 *   (DNS TXT record: tikara-verify=<token>)
 *
 * provider_route_id: route ID in Mailgun/SES (so it can be removed when deactivated)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_domains', function (Blueprint $table) {
            $table->id();
            // client_id is bigint (clients.id is auto-increment bigint, not UUID)
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
