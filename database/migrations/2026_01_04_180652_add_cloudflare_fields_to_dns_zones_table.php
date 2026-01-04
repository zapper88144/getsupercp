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
        Schema::table('dns_zones', function (Blueprint $table) {
            $table->string('cloudflare_zone_id')->nullable()->after('domain');
            $table->boolean('cloudflare_proxy_enabled')->default(false)->after('cloudflare_zone_id');
            $table->timestamp('cloudflare_last_sync_at')->nullable()->after('cloudflare_proxy_enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dns_zones', function (Blueprint $table) {
            $table->dropColumn(['cloudflare_zone_id', 'cloudflare_proxy_enabled', 'cloudflare_last_sync_at']);
        });
    }
};
