<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('two_factor_authentications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('secret')->encrypted();
            $table->json('recovery_codes')->encrypted()->nullable();
            $table->enum('method', ['totp', 'sms', 'email'])->default('totp');
            $table->string('phone_number')->nullable();
            $table->boolean('is_enabled')->default(false);
            $table->dateTime('enabled_at')->nullable();
            $table->integer('failed_attempts')->default(0);
            $table->dateTime('last_failed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('two_factor_authentications');
    }
};
