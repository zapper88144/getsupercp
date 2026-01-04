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
        // PowerDNS Tables (gmysql backend)
        if (! Schema::hasTable('domains')) {
            Schema::create('domains', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->string('master', 128)->nullable();
                $table->integer('last_check')->nullable();
                $table->string('type', 6);
                $table->integer('notified_serial')->nullable();
                $table->string('account', 40)->nullable();
                $table->index('name', 'name_index');
            });
        }

        if (! Schema::hasTable('records')) {
            Schema::create('records', function (Blueprint $table) {
                $table->id();
                $table->integer('domain_id')->nullable();
                $table->string('name')->nullable();
                $table->string('type', 10)->nullable();
                $table->text('content')->nullable();
                $table->integer('ttl')->nullable();
                $table->integer('prio')->nullable();
                $table->boolean('disabled')->default(false);
                $table->string('ordername')->nullable();
                $table->boolean('auth')->default(true);
                $table->index('domain_id', 'nametype_index');
                $table->index(['name', 'type'], 'rec_name_type_index');
                $table->index('ordername', 'ordername_index');
            });
        }

        if (! Schema::hasTable('supermasters')) {
            Schema::create('supermasters', function (Blueprint $table) {
                $table->string('ip', 64);
                $table->string('nameserver', 255);
                $table->string('account', 40)->nullable();
                $table->primary(['ip', 'nameserver']);
            });
        }

        if (! Schema::hasTable('domainmetadata')) {
            Schema::create('domainmetadata', function (Blueprint $table) {
                $table->id();
                $table->integer('domain_id');
                $table->string('kind', 32);
                $table->text('content')->nullable();
                $table->index('domain_id', 'domainmetadata_idx');
            });
        }

        if (! Schema::hasTable('cryptokeys')) {
            Schema::create('cryptokeys', function (Blueprint $table) {
                $table->id();
                $table->integer('domain_id');
                $table->integer('flags');
                $table->boolean('active')->nullable();
                $table->text('content')->nullable();
                $table->index('domain_id', 'cryptokeys_idx');
            });
        }

        if (! Schema::hasTable('tsigkeys')) {
            Schema::create('tsigkeys', function (Blueprint $table) {
                $table->id();
                $table->string('name')->nullable();
                $table->string('algorithm', 50)->nullable();
                $table->string('secret')->nullable();
                $table->unique(['name', 'algorithm'], 'name_algo_index');
            });
        }

        // Postfix / Dovecot Tables
        if (! Schema::hasTable('virtual_domains')) {
            Schema::create('virtual_domains', function (Blueprint $table) {
                $table->id();
                $table->string('name', 100)->unique();
            });
        }

        if (! Schema::hasTable('virtual_users')) {
            Schema::create('virtual_users', function (Blueprint $table) {
                $table->id();
                $table->foreignId('domain_id')->constrained('virtual_domains')->onDelete('cascade');
                $table->string('password', 106);
                $table->string('email', 100)->unique();
            });
        }

        if (! Schema::hasTable('virtual_aliases')) {
            Schema::create('virtual_aliases', function (Blueprint $table) {
                $table->id();
                $table->foreignId('domain_id')->constrained('virtual_domains')->onDelete('cascade');
                $table->string('source', 100);
                $table->string('destination', 100);
            });
        }

        // Pure-FTPd Tables
        if (! Schema::hasTable('ftp_users')) {
            Schema::create('ftp_users', function (Blueprint $table) {
                $table->id();
                $table->string('user', 32)->unique();
                $table->string('password', 128);
                $table->integer('uid')->default(2000); // Default ftp user uid
                $table->integer('gid')->default(2000); // Default ftp user gid
                $table->string('dir', 255);
                $table->integer('quota_files')->default(0);
                $table->integer('quota_size')->default(0); // in MB
                $table->string('ul_bandwidth')->default('0');
                $table->string('dl_bandwidth')->default('0');
                $table->string('ip_access')->default('*');
                $table->boolean('active')->default(true);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ftp_users');
        Schema::dropIfExists('virtual_aliases');
        Schema::dropIfExists('virtual_users');
        Schema::dropIfExists('virtual_domains');
        Schema::dropIfExists('tsigkeys');
        Schema::dropIfExists('cryptokeys');
        Schema::dropIfExists('domainmetadata');
        Schema::dropIfExists('supermasters');
        Schema::dropIfExists('records');
        Schema::dropIfExists('domains');
    }
};
