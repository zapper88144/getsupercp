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
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['super-admin', 'admin', 'moderator', 'user'])->default('user')->after('is_admin');
            $table->enum('status', ['active', 'suspended', 'inactive'])->default('active')->after('role');
            $table->string('phone')->nullable()->after('email');
            $table->text('notes')->nullable()->after('password');
            $table->timestamp('last_login_at')->nullable()->after('notes');
            $table->ipAddress('last_login_ip')->nullable()->after('last_login_at');
            $table->boolean('two_factor_enabled')->default(false)->after('last_login_ip');
            $table->dateTime('suspended_at')->nullable()->after('two_factor_enabled');
            $table->string('suspended_reason')->nullable()->after('suspended_at');
            $table->index('role');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role']);
            $table->dropIndex(['status']);
            $table->dropColumn([
                'role',
                'status',
                'phone',
                'notes',
                'last_login_at',
                'last_login_ip',
                'two_factor_enabled',
                'suspended_at',
                'suspended_reason',
            ]);
        });
    }
};
