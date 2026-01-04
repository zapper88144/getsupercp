<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_admin',
        'role',
        'status',
        'phone',
        'notes',
        'last_login_at',
        'last_login_ip',
        'two_factor_enabled',
        'suspended_at',
        'suspended_reason',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'two_factor_enabled' => 'boolean',
            'last_login_at' => 'datetime',
            'suspended_at' => 'datetime',
        ];
    }

    public function webDomains(): HasMany
    {
        return $this->hasMany(WebDomain::class);
    }

    public function databases(): HasMany
    {
        return $this->hasMany(Database::class);
    }

    public function ftpUsers(): HasMany
    {
        return $this->hasMany(FtpUser::class);
    }

    public function cronJobs(): HasMany
    {
        return $this->hasMany(CronJob::class);
    }

    public function dnsZones(): HasMany
    {
        return $this->hasMany(DnsZone::class);
    }

    public function emailAccounts(): HasMany
    {
        return $this->hasMany(EmailAccount::class);
    }

    public function backups(): HasMany
    {
        return $this->hasMany(Backup::class);
    }

    public function sslCertificates(): HasMany
    {
        return $this->hasMany(SslCertificate::class);
    }

    public function backupSchedules(): HasMany
    {
        return $this->hasMany(BackupSchedule::class);
    }

    public function monitoringAlerts(): HasMany
    {
        return $this->hasMany(MonitoringAlert::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function twoFactorAuthentication(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(TwoFactorAuthentication::class);
    }

    public function emailServerConfig(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(EmailServerConfig::class);
    }
}
