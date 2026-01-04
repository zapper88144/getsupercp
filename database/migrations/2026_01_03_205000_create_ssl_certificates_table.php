<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ssl_certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('web_domain_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('domain');
            $table->string('provider')->default('letsencrypt'); // letsencrypt, custom
            $table->string('certificate_path')->nullable();
            $table->string('key_path')->nullable();
            $table->string('ca_bundle_path')->nullable();
            $table->dateTime('issued_at')->nullable();
            $table->dateTime('expires_at')->nullable();
            $table->dateTime('renewal_scheduled_at')->nullable();
            $table->boolean('auto_renewal_enabled')->default(true);
            $table->enum('status', ['pending', 'active', 'expired', 'renewing', 'failed'])->default('pending');
            $table->string('validation_method')->default('dns'); // dns, http, tls-alpn
            $table->integer('renewal_attempts')->default(0);
            $table->longText('last_error')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'status']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ssl_certificates');
    }
};
