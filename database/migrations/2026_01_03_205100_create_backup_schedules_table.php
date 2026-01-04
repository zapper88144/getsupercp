<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('backup_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->enum('frequency', ['daily', 'weekly', 'monthly', 'custom'])->default('daily');
            $table->string('time')->default('02:00'); // HH:MM format
            $table->string('day_of_week')->nullable(); // 0-6 for weekly
            $table->string('day_of_month')->nullable(); // 1-31 for monthly
            $table->enum('backup_type', ['full', 'incremental', 'database_only', 'files_only'])->default('full');
            $table->json('targets')->nullable(); // ['web_domains' => [1,2,3], 'databases' => [1,2]]
            $table->integer('retention_days')->default(30);
            $table->boolean('compress')->default(true);
            $table->boolean('encrypt')->default(false);
            $table->string('encryption_key')->nullable();
            $table->boolean('notify_on_completion')->default(true);
            $table->boolean('notify_on_failure')->default(true);
            $table->boolean('is_enabled')->default(true);
            $table->dateTime('last_run_at')->nullable();
            $table->integer('last_run_duration_seconds')->nullable();
            $table->dateTime('next_run_at')->nullable();
            $table->integer('run_count')->default(0);
            $table->integer('failed_count')->default(0);
            $table->timestamps();
            $table->index(['user_id', 'is_enabled']);
            $table->index('next_run_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('backup_schedules');
    }
};
