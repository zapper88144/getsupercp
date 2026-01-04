<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('action');
            $table->string('model')->nullable();
            $table->unsignedBigInteger('model_id')->nullable();
            $table->json('changes')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->enum('result', ['success', 'failed', 'warning'])->default('success');
            $table->text('description')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'created_at']);
            $table->index('action');
            $table->index('result');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
