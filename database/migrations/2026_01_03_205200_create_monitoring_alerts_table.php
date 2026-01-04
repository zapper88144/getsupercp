<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monitoring_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->enum('metric', ['cpu', 'memory', 'disk', 'bandwidth', 'load_average']);
            $table->integer('threshold_percentage');
            $table->enum('comparison', ['>', '>=', '<', '<=', '==', '!='])->default('>=');
            $table->enum('frequency', ['immediate', '5min', '15min', '30min', '1hour'])->default('immediate');
            $table->boolean('notify_email')->default(true);
            $table->boolean('notify_webhook')->default(false);
            $table->string('webhook_url')->nullable();
            $table->boolean('is_enabled')->default(true);
            $table->dateTime('triggered_at')->nullable();
            $table->integer('consecutive_triggers')->default(0);
            $table->dateTime('last_notification_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'is_enabled']);
            $table->index('metric');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monitoring_alerts');
    }
};
