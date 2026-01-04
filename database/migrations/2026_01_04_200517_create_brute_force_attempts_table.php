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
        Schema::create('brute_force_attempts', function (Blueprint $table) {
            $table->id();
            $table->ipAddress('ip_address')->index();
            $table->string('service')->index(); // ssh, http, ftp, etc.
            $table->integer('attempt_count')->default(1);
            $table->timestamp('first_attempt_at')->nullable();
            $table->timestamp('last_attempt_at')->nullable();
            $table->timestamp('blocked_until')->nullable();
            $table->boolean('is_blocked')->default(false);
            $table->string('username')->nullable();
            $table->text('reason')->nullable();
            $table->timestamps();
            $table->index(['ip_address', 'service']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('brute_force_attempts');
    }
};
