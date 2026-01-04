<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_server_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('smtp_host')->nullable();
            $table->integer('smtp_port')->default(587);
            $table->string('smtp_username')->nullable();
            $table->string('smtp_password')->nullable()->encrypted();
            $table->boolean('smtp_encryption')->default(true); // TLS/SSL
            $table->string('imap_host')->nullable();
            $table->integer('imap_port')->default(993);
            $table->string('imap_username')->nullable();
            $table->string('imap_password')->nullable()->encrypted();
            $table->boolean('imap_encryption')->default(true);
            $table->string('from_email')->nullable();
            $table->string('from_name')->nullable();
            $table->string('spf_record')->nullable();
            $table->string('dkim_public_key')->nullable();
            $table->string('dkim_private_key')->nullable()->encrypted();
            $table->string('dmarc_policy')->nullable(); // none, quarantine, reject
            $table->boolean('is_configured')->default(false);
            $table->dateTime('last_tested_at')->nullable();
            $table->boolean('last_test_passed')->default(false);
            $table->text('last_test_error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_server_configs');
    }
};
