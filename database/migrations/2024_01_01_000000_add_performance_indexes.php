<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations - Add performance indexes
     */
    public function up(): void
    {
        // User table indexes - only add for columns that exist
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (Schema::hasColumn('users', 'email')) {
                    try {
                        $table->index('email');
                    } catch (\Exception $e) {
                        // Index may already exist
                    }
                }
                if (Schema::hasColumn('users', 'created_at')) {
                    try {
                        $table->index('created_at');
                    } catch (\Exception $e) {
                        // Index may already exist
                    }
                }
            });
        }

        // Web domains indexes
        if (Schema::hasTable('web_domains')) {
            Schema::table('web_domains', function (Blueprint $table) {
                if (Schema::hasColumn('web_domains', 'user_id')) {
                    try {
                        $table->index('user_id');
                    } catch (\Exception $e) {
                    }
                }
                if (Schema::hasColumn('web_domains', 'domain_name')) {
                    try {
                        $table->index('domain_name');
                    } catch (\Exception $e) {
                    }
                }
                if (Schema::hasColumn('web_domains', 'status')) {
                    try {
                        $table->index('status');
                    } catch (\Exception $e) {
                    }
                }
            });
        }

        // Email accounts indexes
        if (Schema::hasTable('email_accounts')) {
            Schema::table('email_accounts', function (Blueprint $table) {
                if (Schema::hasColumn('email_accounts', 'user_id')) {
                    try {
                        $table->index('user_id');
                    } catch (\Exception $e) {
                    }
                }
                if (Schema::hasColumn('email_accounts', 'domain_id')) {
                    try {
                        $table->index('domain_id');
                    } catch (\Exception $e) {
                    }
                }
                if (Schema::hasColumn('email_accounts', 'email')) {
                    try {
                        $table->index('email');
                    } catch (\Exception $e) {
                    }
                }
            });
        }

        // SSL certificates indexes
        if (Schema::hasTable('ssl_certificates')) {
            Schema::table('ssl_certificates', function (Blueprint $table) {
                if (Schema::hasColumn('ssl_certificates', 'user_id')) {
                    try {
                        $table->index('user_id');
                    } catch (\Exception $e) {
                    }
                }
                if (Schema::hasColumn('ssl_certificates', 'domain_id')) {
                    try {
                        $table->index('domain_id');
                    } catch (\Exception $e) {
                    }
                }
                if (Schema::hasColumn('ssl_certificates', 'expires_at')) {
                    try {
                        $table->index('expires_at');
                    } catch (\Exception $e) {
                    }
                }
            });
        }

        // Databases indexes
        if (Schema::hasTable('databases')) {
            Schema::table('databases', function (Blueprint $table) {
                if (Schema::hasColumn('databases', 'user_id')) {
                    try {
                        $table->index('user_id');
                    } catch (\Exception $e) {
                    }
                }
                if (Schema::hasColumn('databases', 'name')) {
                    try {
                        $table->index('name');
                    } catch (\Exception $e) {
                    }
                }
            });
        }

        // Backups indexes
        if (Schema::hasTable('backups')) {
            Schema::table('backups', function (Blueprint $table) {
                if (Schema::hasColumn('backups', 'user_id')) {
                    try {
                        $table->index('user_id');
                    } catch (\Exception $e) {
                    }
                }
                if (Schema::hasColumn('backups', 'resource_type')) {
                    try {
                        $table->index('resource_type');
                    } catch (\Exception $e) {
                    }
                }
                if (Schema::hasColumn('backups', 'created_at')) {
                    try {
                        $table->index('created_at');
                    } catch (\Exception $e) {
                    }
                }
            });
        }

        // Audit logs indexes
        if (Schema::hasTable('audit_logs')) {
            Schema::table('audit_logs', function (Blueprint $table) {
                if (Schema::hasColumn('audit_logs', 'user_id')) {
                    try {
                        $table->index('user_id');
                    } catch (\Exception $e) {
                    }
                }
                if (Schema::hasColumn('audit_logs', 'action')) {
                    try {
                        $table->index('action');
                    } catch (\Exception $e) {
                    }
                }
                if (Schema::hasColumn('audit_logs', 'created_at')) {
                    try {
                        $table->index('created_at');
                    } catch (\Exception $e) {
                    }
                }
            });
        }

        // DNS records indexes
        if (Schema::hasTable('dns_records')) {
            Schema::table('dns_records', function (Blueprint $table) {
                if (Schema::hasColumn('dns_records', 'zone_id')) {
                    try {
                        $table->index('zone_id');
                    } catch (\Exception $e) {
                    }
                }
                if (Schema::hasColumn('dns_records', 'type')) {
                    try {
                        $table->index('type');
                    } catch (\Exception $e) {
                    }
                }
                if (Schema::hasColumn('dns_records', 'name')) {
                    try {
                        $table->index('name');
                    } catch (\Exception $e) {
                    }
                }
            });
        }

        // Firewall rules indexes
        if (Schema::hasTable('firewall_rules')) {
            Schema::table('firewall_rules', function (Blueprint $table) {
                if (Schema::hasColumn('firewall_rules', 'user_id')) {
                    try {
                        $table->index('user_id');
                    } catch (\Exception $e) {
                    }
                }
                if (Schema::hasColumn('firewall_rules', 'ip_address')) {
                    try {
                        $table->index('ip_address');
                    } catch (\Exception $e) {
                    }
                }
            });
        }

        // Monitoring alerts indexes
        if (Schema::hasTable('monitoring_alerts')) {
            Schema::table('monitoring_alerts', function (Blueprint $table) {
                if (Schema::hasColumn('monitoring_alerts', 'user_id')) {
                    try {
                        $table->index('user_id');
                    } catch (\Exception $e) {
                    }
                }
                if (Schema::hasColumn('monitoring_alerts', 'resource_type')) {
                    try {
                        $table->index('resource_type');
                    } catch (\Exception $e) {
                    }
                }
                if (Schema::hasColumn('monitoring_alerts', 'severity')) {
                    try {
                        $table->index('severity');
                    } catch (\Exception $e) {
                    }
                }
                if (Schema::hasColumn('monitoring_alerts', 'created_at')) {
                    try {
                        $table->index('created_at');
                    } catch (\Exception $e) {
                    }
                }
            });
        }

        // Cron jobs indexes
        if (Schema::hasTable('cron_jobs')) {
            Schema::table('cron_jobs', function (Blueprint $table) {
                if (Schema::hasColumn('cron_jobs', 'user_id')) {
                    try {
                        $table->index('user_id');
                    } catch (\Exception $e) {
                    }
                }
                if (Schema::hasColumn('cron_jobs', 'last_run_at')) {
                    try {
                        $table->index('last_run_at');
                    } catch (\Exception $e) {
                    }
                }
            });
        }

        // FTP users indexes
        if (Schema::hasTable('ftp_users')) {
            Schema::table('ftp_users', function (Blueprint $table) {
                if (Schema::hasColumn('ftp_users', 'user_id')) {
                    try {
                        $table->index('user_id');
                    } catch (\Exception $e) {
                    }
                }
                if (Schema::hasColumn('ftp_users', 'username')) {
                    try {
                        $table->index('username');
                    } catch (\Exception $e) {
                    }
                }
            });
        }
    }

    /**
     * Reverse the migrations
     */
    public function down(): void
    {
        // Note: In production, be careful with dropping indexes
        // This is typically not necessary as indexes are dropped with tables
    }
};
