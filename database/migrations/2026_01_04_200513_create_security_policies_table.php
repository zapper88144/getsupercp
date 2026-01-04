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
        Schema::create('security_policies', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->boolean('enable_firewall')->default(true);
            $table->boolean('enable_brute_force_protection')->default(true);
            $table->integer('failed_login_threshold')->default(5);
            $table->integer('lockout_duration_minutes')->default(15);
            $table->boolean('enable_ip_filtering')->default(false);
            $table->boolean('enable_ssl_enforcement')->default(true);
            $table->boolean('enable_cloudflare_security')->default(false);
            $table->string('cloudflare_api_token')->nullable();
            $table->json('security_headers')->nullable();
            $table->json('waf_rules')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('security_policies');
    }
};
