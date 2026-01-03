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
        Schema::table('web_domains', function (Blueprint $table) {
            $table->boolean('has_ssl')->default(false)->after('php_version');
            $table->string('ssl_certificate_path')->nullable()->after('has_ssl');
            $table->string('ssl_key_path')->nullable()->after('ssl_certificate_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('web_domains', function (Blueprint $table) {
            $table->dropColumn(['has_ssl', 'ssl_certificate_path', 'ssl_key_path']);
        });
    }
};
