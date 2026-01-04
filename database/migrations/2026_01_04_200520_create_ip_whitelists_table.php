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
        Schema::create('ip_whitelists', function (Blueprint $table) {
            $table->id();
            $table->ipAddress('ip_address')->unique()->index();
            $table->string('ip_range')->nullable(); // CIDR notation
            $table->string('description')->nullable();
            $table->string('reason'); // cloudflare, admin, trusted, etc.
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->boolean('is_permanent')->default(false);
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ip_whitelists');
    }
};
