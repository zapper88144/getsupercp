# SuperCP Architecture Improvements & Deep Analysis

This document supplements [CONTROL_WEBPANEL_ANALYSIS.md](CONTROL_WEBPANEL_ANALYSIS.md) with detailed code patterns, architecture improvements, and implementation strategies.

---

## Part 1: Rust Daemon Enhancement Strategy

### Current Architecture Issues

**Problem 1: Error Handling & Recovery**

Current implementation silently fails:
```php
// RustDaemonClient.php
public function call(string $method, array $params = []): array {
    try {
        $fp = stream_socket_client("unix://{$this->socketPath}", ...);
        if (!$fp) {
            return ['error' => ['message' => 'Connection failed']];
        }
        // ... no retry logic
        // ... no logging
        // ... no transaction handling
    } catch (\Exception $e) {
        return ['error' => ['message' => $e->getMessage()]];
    }
}
```

**Solution: Implement Robust Error Handling**

```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use App\Events\DaemonCallStarted;
use App\Events\DaemonCallSucceeded;
use App\Events\DaemonCallFailed;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

class RustDaemonClient
{
    const MAX_RETRIES = 3;
    const RETRY_DELAY_MS = 1000;
    const SOCKET_TIMEOUT_MS = 30000;

    protected string $socketPath;

    public function __construct()
    {
        $this->socketPath = storage_path('framework/sockets/super-daemon.sock');
    }

    /**
     * Call with automatic retry and comprehensive error handling
     */
    public function callWithRetry(
        string $method, 
        array $params = [], 
        int $maxRetries = self::MAX_RETRIES
    ): array {
        $lastException = null;

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                return $this->call($method, $params, $attempt, $maxRetries);
            } catch (DaemonException $e) {
                $lastException = $e;
                
                Log::warning("Daemon call failed, attempt $attempt/$maxRetries", [
                    'method' => $method,
                    'error' => $e->getMessage(),
                    'params' => $params,
                ]);

                if ($attempt < $maxRetries) {
                    // Exponential backoff: 1s, 2s, 4s
                    $delayMs = self::RETRY_DELAY_MS * (2 ** ($attempt - 1));
                    usleep($delayMs * 1000);
                }
            }
        }

        throw $lastException ?? new DaemonException("Max retries exceeded for $method");
    }

    /**
     * Core socket communication with comprehensive logging
     */
    protected function call(
        string $method, 
        array $params = [], 
        int $attempt = 1,
        int $maxAttempts = 1
    ): array {
        Event::dispatch(new DaemonCallStarted($method, $params, $attempt, $maxAttempts));

        try {
            $request = json_encode([
                'jsonrpc' => '2.0',
                'method' => $method,
                'params' => $params,
                'id' => uniqid('daemon-', true),
            ]);

            // Create socket with timeout
            $context = stream_context_create([
                'socket' => [
                    'so_reuseaddr' => 1,
                ]
            ]);

            $fp = @stream_socket_client(
                "unix://{$this->socketPath}",
                $errno,
                $errstr,
                self::SOCKET_TIMEOUT_MS / 1000
            );

            if (!$fp) {
                throw new DaemonConnectionException(
                    "Failed to connect to daemon: [$errno] $errstr"
                );
            }

            // Set read timeout
            stream_set_timeout($fp, self::SOCKET_TIMEOUT_MS / 1000);

            // Send request
            $written = fwrite($fp, $request . "\n");
            if ($written === false) {
                throw new DaemonException("Failed to write to daemon socket");
            }

            // Read response with timeout
            $response = fgets($fp);
            
            // Check for timeout
            $metadata = stream_get_meta_data($fp);
            if ($metadata['timed_out']) {
                throw new DaemonTimeoutException("Daemon response timeout after " . 
                    self::SOCKET_TIMEOUT_MS . "ms");
            }

            if ($response === false) {
                throw new DaemonException("Failed to read from daemon socket");
            }

            fclose($fp);

            $decoded = json_decode($response, true);
            if (!is_array($decoded)) {
                throw new DaemonException("Invalid JSON response from daemon");
            }

            // Check for RPC error
            if (isset($decoded['error'])) {
                Event::dispatch(new DaemonCallFailed($method, $decoded['error']));
                throw new DaemonRpcException(
                    $decoded['error']['message'] ?? 'Unknown error',
                    $decoded['error']['code'] ?? -1
                );
            }

            Event::dispatch(new DaemonCallSucceeded($method, $decoded['result'] ?? null));

            // Log successful call
            $this->logAudit($method, $params, 'success', $decoded);

            return $decoded;

        } catch (\Exception $e) {
            $this->logAudit($method, $params, 'failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Log daemon call to audit trail
     */
    protected function logAudit(
        string $method, 
        array $params, 
        string $status, 
        array $result
    ): void {
        // Don't log sensitive parameters like passwords
        $sanitized = $this->sanitizeParams($params);

        try {
            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => "daemon:$method",
                'status' => $status,
                'ip_address' => request()->ip(),
                'details' => json_encode([
                    'params' => $sanitized,
                    'result' => $result,
                ]),
                'created_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to log daemon audit", ['error' => $e->getMessage()]);
        }
    }

    /**
     * Remove sensitive parameters from logging
     */
    protected function sanitizeParams(array $params): array
    {
        $sensitive = ['password', 'api_key', 'secret', 'token'];
        
        return collect($params)->mapWithKeys(function ($value, $key) use ($sensitive) {
            if (in_array(strtolower($key), $sensitive)) {
                return [$key => '***REDACTED***'];
            }
            return [$key => $value];
        })->toArray();
    }
}

// Custom Exception Classes
class DaemonException extends \Exception {}
class DaemonConnectionException extends DaemonException {}
class DaemonTimeoutException extends DaemonException {}
class DaemonRpcException extends DaemonException {}
```

### Problem 2: Transaction & Rollback Handling

Current issue: If domain creation succeeds in DB but fails in daemon, system is inconsistent.

**Solution: Implement Domain Creation Transaction**

```php
<?php

namespace App\Services;

use App\Models\WebDomain;
use App\Models\DnsZone;
use App\Models\DnsRecord;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\DBTransactionException;

class DomainCreationService
{
    public function __construct(
        protected RustDaemonClient $daemon
    ) {}

    /**
     * Create domain with proper transaction handling
     * 
     * Steps (in order):
     * 1. Validate inputs
     * 2. Create DB records (transactional)
     * 3. Create filesystem structure
     * 4. Create Nginx/PHP-FPM configs
     * 5. Create DNS zone & records
     * 
     * Rollback on any failure
     */
    public function create(User $user, array $data): WebDomain
    {
        $this->validate($data);

        return DB::transaction(function () use ($user, $data) {
            // Step 1: Create domain record with pending status
            $domain = $user->webDomains()->create([
                'domain' => $data['domain'],
                'root_path' => "/home/{$user->name}/web/{$data['domain']}/public",
                'php_version' => $data['php_version'] ?? '8.4',
                'is_active' => false,
                'status' => 'creating', // New field to track state
            ]);

            try {
                // Step 2: Create filesystem structure
                $this->createFilesystem($user, $domain);

                // Step 3: Create DNS zone and records
                $zone = $this->createDnsZone($user, $domain);

                // Step 4: Create vhost configs in daemon
                $response = $this->daemon->callWithRetry('create_vhost', [
                    'domain' => $domain->domain,
                    'user' => $user->name,
                    'root' => $domain->root_path,
                    'php_version' => $domain->php_version,
                    'has_ssl' => false,
                ]);

                if (isset($response['error'])) {
                    throw new \Exception($response['error']['message']);
                }

                // Success: mark as active
                $domain->update([
                    'is_active' => true,
                    'status' => 'active',
                ]);

                event(new DomainCreatedEvent($domain));

                return $domain;

            } catch (\Exception $e) {
                // Mark as failed
                $domain->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                ]);

                // Attempt cleanup (optional, may not succeed)
                try {
                    $this->cleanupAfterFailure($user, $domain);
                } catch (\Exception $cleanupError) {
                    \Log::error("Cleanup failed for domain {$domain->domain}", [
                        'error' => $cleanupError->getMessage(),
                    ]);
                }

                event(new DomainCreationFailedEvent($domain, $e));
                throw new DomainCreationException(
                    "Failed to create domain: " . $e->getMessage(),
                    0,
                    $e
                );
            }
        });
    }

    /**
     * Create filesystem structure
     */
    protected function createFilesystem(User $user, WebDomain $domain): void
    {
        $paths = [
            dirname($domain->root_path),
            $domain->root_path,
            "/home/{$user->name}/web/{$domain->domain}/logs",
        ];

        foreach ($paths as $path) {
            $response = $this->daemon->callWithRetry('create_directory', [
                'path' => $path,
                'owner' => $user->name,
                'permissions' => '0755',
            ]);

            if (isset($response['error'])) {
                throw new \Exception("Failed to create directory: $path - " . 
                    $response['error']['message']);
            }
        }

        // Create default index.php
        $this->daemon->callWithRetry('write_file', [
            'path' => $domain->root_path . '/index.php',
            'content' => $this->getDefaultIndexPhp($domain),
            'owner' => $user->name,
        ]);
    }

    /**
     * Create DNS zone and default records
     */
    protected function createDnsZone(User $user, WebDomain $domain): DnsZone
    {
        $zone = $user->dnsZones()->create([
            'domain' => $domain->domain,
            'status' => 'active',
        ]);

        $defaultIp = config('dns.default_ip', '127.0.0.1');
        $nameservers = config('dns.nameservers', ['ns1.supercp.com.', 'ns2.supercp.com.']);

        // Create A record for domain
        $zone->dnsRecords()->create([
            'name' => '@',
            'type' => 'A',
            'value' => $defaultIp,
            'ttl' => 3600,
        ]);

        // Create CNAME for www subdomain
        $zone->dnsRecords()->create([
            'name' => 'www',
            'type' => 'CNAME',
            'value' => $domain->domain . '.',
            'ttl' => 3600,
        ]);

        // Create NS records
        foreach ($nameservers as $ns) {
            $zone->dnsRecords()->create([
                'name' => '@',
                'type' => 'NS',
                'value' => $ns,
                'ttl' => 3600,
            ]);
        }

        // Sync DNS with daemon
        $this->syncDnsWithDaemon($zone);

        return $zone;
    }

    /**
     * Sync DNS zone with daemon
     */
    protected function syncDnsWithDaemon(DnsZone $zone): void
    {
        $response = $this->daemon->callWithRetry('update_dns_zone', [
            'domain' => $zone->domain,
            'records' => $zone->dnsRecords()->get()->map(fn ($r) => [
                'name' => $r->name,
                'type' => $r->type,
                'value' => $r->value,
                'priority' => $r->priority,
                'ttl' => $r->ttl,
            ])->toArray(),
        ]);

        if (isset($response['error'])) {
            throw new \Exception("DNS sync failed: " . $response['error']['message']);
        }
    }

    /**
     * Cleanup after creation failure
     */
    protected function cleanupAfterFailure(User $user, WebDomain $domain): void
    {
        try {
            $this->daemon->call('delete_vhost', [
                'domain' => $domain->domain,
                'user' => $user->name,
            ]);
        } catch (\Exception $e) {
            // Continue cleanup even if daemon call fails
        }
    }

    protected function getDefaultIndexPhp(WebDomain $domain): string
    {
        return <<<'PHP'
<?php
echo '<div style="font-family: sans-serif; text-align: center; padding-top: 50px;">';
echo '<h1>Welcome to ' . htmlspecialchars('{$domain->domain}') . '</h1>';
echo '<p>Your website is successfully set up and hosted on SuperCP.</p>';
echo '</div>';
phpinfo();
PHP;
    }

    protected function validate(array $data): void
    {
        if (!preg_match('/^[a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?(\.[a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?)*$/', $data['domain'])) {
            throw new \InvalidArgumentException("Invalid domain format");
        }
    }
}

// Custom Exception
class DomainCreationException extends \Exception {}

// Events
class DomainCreatedEvent {}
class DomainCreationFailedEvent {}
```

---

## Part 2: React Component Architecture

### Problem: Code Duplication in List Pages

Both `WebDomains/Index.tsx` and `Databases/Index.tsx` have identical patterns:
- Search/filter
- Add form toggle
- Delete confirmation
- No pagination
- No sorting

**Solution: Create Reusable ListPage Component**

```tsx
// components/ListPage/ListPage.tsx
import React, { useState, useMemo, ReactNode } from 'react';
import { router } from '@inertiajs/react';
import TextInput from '@/Components/TextInput';
import PrimaryButton from '@/Components/PrimaryButton';
import { MagnifyingGlassIcon, PlusIcon, XMarkIcon } from '@heroicons/react/24/outline';
import DataTable from './DataTable';
import Pagination from './Pagination';

export interface Column<T> {
    key: keyof T;
    label: string;
    render?: (item: T, value: any) => ReactNode;
    sortable?: boolean;
    width?: string;
}

interface ListPageProps<T> {
    title: string;
    description?: string;
    items: T[];
    columns: Column<T>[];
    searchFields: (keyof T)[];
    onAdd: () => void;
    onDelete?: (item: T) => Promise<void> | void;
    onEdit?: (item: T) => void;
    renderAddForm?: () => ReactNode;
    actionColumn?: (item: T) => ReactNode;
    pageSize?: number;
    pagination?: {
        current_page: number;
        last_page: number;
        total: number;
        per_page: number;
    };
    onPageChange?: (page: number) => void;
}

export default function ListPage<T extends { id: number }>({
    title,
    description,
    items,
    columns,
    searchFields,
    onAdd,
    onDelete,
    onEdit,
    renderAddForm,
    actionColumn,
    pageSize = 25,
    pagination,
    onPageChange,
}: ListPageProps<T>) {
    const [searchQuery, setSearchQuery] = useState('');
    const [sortField, setSortField] = useState<keyof T | null>(null);
    const [sortOrder, setSortOrder] = useState<'asc' | 'desc'>('asc');

    // Filter items
    const filtered = useMemo(() => {
        return items.filter(item =>
            searchFields.some(field =>
                String(item[field])
                    .toLowerCase()
                    .includes(searchQuery.toLowerCase())
            )
        );
    }, [items, searchQuery, searchFields]);

    // Sort items
    const sorted = useMemo(() => {
        if (!sortField) return filtered;

        return [...filtered].sort((a, b) => {
            const aVal = a[sortField];
            const bVal = b[sortField];

            if (aVal < bVal) return sortOrder === 'asc' ? -1 : 1;
            if (aVal > bVal) return sortOrder === 'asc' ? 1 : -1;
            return 0;
        });
    }, [filtered, sortField, sortOrder]);

    const handleSort = (field: keyof T) => {
        if (sortField === field) {
            setSortOrder(sortOrder === 'asc' ? 'desc' : 'asc');
        } else {
            setSortField(field);
            setSortOrder('asc');
        }
    };

    const handleDelete = async (item: T) => {
        if (confirm(`Are you sure you want to delete "${item.id}"?`)) {
            try {
                if (onDelete) {
                    await onDelete(item);
                }
            } catch (error) {
                alert('Failed to delete item');
            }
        }
    };

    return (
        <div className="py-12">
            <div className="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                {/* Header */}
                <div className="flex justify-between items-start">
                    <div>
                        <h1 className="text-3xl font-bold text-gray-900 dark:text-white">
                            {title}
                        </h1>
                        {description && (
                            <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                {description}
                            </p>
                        )}
                    </div>
                    <PrimaryButton
                        onClick={onAdd}
                        className="flex items-center gap-2"
                    >
                        <PlusIcon className="w-5 h-5" />
                        Add {title}
                    </PrimaryButton>
                </div>

                {/* Add Form */}
                {renderAddForm && (
                    <div className="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        {renderAddForm()}
                    </div>
                )}

                {/* Search Bar */}
                <div className="relative">
                    <MagnifyingGlassIcon className="absolute left-3 top-3 w-5 h-5 text-gray-400" />
                    <TextInput
                        type="text"
                        placeholder={`Search ${title.toLowerCase()}...`}
                        value={searchQuery}
                        onChange={(e) => setSearchQuery(e.target.value)}
                        className="pl-10 w-full"
                    />
                </div>

                {/* Results Info */}
                <div className="text-sm text-gray-600 dark:text-gray-400">
                    Showing {sorted.length} of {items.length} items
                </div>

                {/* Data Table */}
                {filtered.length > 0 ? (
                    <>
                        <DataTable
                            columns={columns}
                            data={sorted}
                            sortField={sortField}
                            sortOrder={sortOrder}
                            onSort={handleSort}
                            onDelete={handleDelete}
                            onEdit={onEdit}
                            actionColumn={actionColumn}
                        />
                        
                        {/* Pagination */}
                        {pagination && onPageChange && (
                            <Pagination
                                currentPage={pagination.current_page}
                                lastPage={pagination.last_page}
                                total={pagination.total}
                                perPage={pagination.per_page}
                                onPageChange={onPageChange}
                            />
                        )}
                    </>
                ) : (
                    <div className="text-center py-12">
                        <p className="text-gray-600 dark:text-gray-400">
                            No {title.toLowerCase()} found
                        </p>
                    </div>
                )}
            </div>
        </div>
    );
}
```

**Usage Example:**

```tsx
// pages/WebDomains/Index.tsx
import ListPage from '@/Components/ListPage/ListPage';
import { Column } from '@/Components/ListPage/ListPage';

interface WebDomain {
    id: number;
    domain: string;
    root_path: string;
    is_active: boolean;
    has_ssl: boolean;
    created_at: string;
}

const columns: Column<WebDomain>[] = [
    {
        key: 'domain',
        label: 'Domain',
        sortable: true,
        render: (item, value) => (
            <Link href={route('web-domains.show', item.id)}>
                <span className="text-blue-600 hover:underline">{value}</span>
            </Link>
        ),
    },
    {
        key: 'is_active',
        label: 'Status',
        render: (item, value) => (
            <span className={value ? 'text-green-600' : 'text-gray-400'}>
                {value ? 'Active' : 'Inactive'}
            </span>
        ),
    },
    {
        key: 'has_ssl',
        label: 'SSL',
        render: (item, value) => (
            <span>{value ? '✓ Secure' : '✗ Not Configured'}</span>
        ),
    },
];

export default function Index({ domains }: { domains: WebDomain[] }) {
    const [isAdding, setIsAdding] = useState(false);

    return (
        <AuthenticatedLayout>
            <ListPage<WebDomain>
                title="Web Domains"
                description="Manage your web domains and hosting"
                items={domains}
                columns={columns}
                searchFields={['domain']}
                onAdd={() => setIsAdding(!isAdding)}
                onDelete={(domain) => router.delete(route('web-domains.destroy', domain.id))}
                renderAddForm={() => isAdding ? <DomainForm /> : null}
            />
        </AuthenticatedLayout>
    );
}
```

---

## Part 3: Database & Resource Tracking

### Implement User Quotas

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserResourceUsage extends Model
{
    protected $fillable = [
        'user_id',
        'domains_count',
        'domains_limit',
        'databases_count',
        'databases_limit',
        'email_accounts_count',
        'email_accounts_limit',
        'storage_used',
        'storage_limit',
        'bandwidth_used',
        'bandwidth_limit',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isNearingDomainLimit(): bool
    {
        return ($this->domains_count / $this->domains_limit) >= 0.8;
    }

    public function isNearingStorageLimit(): bool
    {
        return ($this->storage_used / $this->storage_limit) >= 0.8;
    }

    public function usagePercentage(string $resource): float
    {
        $used = $this->{$resource . '_used'} ?? $this->{$resource . '_count'};
        $limit = $this->{$resource . '_limit'};
        return ($used / $limit) * 100;
    }
}
```

**Usage in Controllers:**

```php
class WebDomainController extends Controller
{
    public function store(Request $request)
    {
        $user = $request->user();

        // Check quota before creation
        if ($user->resourceUsage->domains_count >= $user->resourceUsage->domains_limit) {
            return back()->withErrors([
                'domain' => 'You have reached the maximum number of domains for your account.'
            ]);
        }

        $domain = // ... create domain ...

        // Update quota
        $user->resourceUsage()->increment('domains_count');

        return redirect()->route('web-domains.index');
    }
}
```

---

## Part 4: Testing Strategy

### Unit Test Example: DomainCreationService

```php
<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\DomainCreationService;
use App\Models\User;
use App\Models\WebDomain;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

class DomainCreationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected DomainCreationService $service;
    protected $daemonClientMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->daemonClientMock = Mockery::mock(RustDaemonClient::class);
        $this->service = new DomainCreationService($this->daemonClientMock);
    }

    /** @test */
    public function it_creates_domain_successfully()
    {
        $user = User::factory()->create();
        
        $this->daemonClientMock
            ->shouldReceive('callWithRetry')
            ->times(3)  // create_directory x2, create_vhost
            ->andReturn(['result' => 'success']);

        $domain = $this->service->create($user, [
            'domain' => 'example.com',
            'php_version' => '8.4',
        ]);

        $this->assertEquals('example.com', $domain->domain);
        $this->assertTrue($domain->is_active);
        $this->assertDatabaseHas('web_domains', ['domain' => 'example.com']);
    }

    /** @test */
    public function it_rolls_back_on_daemon_failure()
    {
        $user = User::factory()->create();
        
        $this->daemonClientMock
            ->shouldReceive('callWithRetry')
            ->once()
            ->andThrow(new \Exception('Daemon connection failed'));

        $this->expectException(DomainCreationException::class);
        
        $this->service->create($user, [
            'domain' => 'example.com',
            'php_version' => '8.4',
        ]);

        // Domain should be marked as failed, not deleted
        $this->assertDatabaseHas('web_domains', [
            'domain' => 'example.com',
            'status' => 'failed',
        ]);
    }

    /** @test */
    public function it_validates_domain_format()
    {
        $user = User::factory()->create();

        $this->expectException(\InvalidArgumentException::class);
        
        $this->service->create($user, [
            'domain' => 'invalid_domain!',
            'php_version' => '8.4',
        ]);
    }
}
```

---

## Part 5: Implementation Roadmap (Next 90 Days)

### Week 1-2: Foundation
- [ ] Add comprehensive audit logging
- [ ] Implement 2FA (TOTP)
- [ ] Add breadcrumb navigation
- [ ] Create notification center
- [ ] Improve daemon error handling

### Week 3-4: Core Features
- [ ] SSL certificate management UI
- [ ] Database backup/restore interface
- [ ] File upload improvements (drag-drop)
- [ ] Search/filter on all list pages
- [ ] Pagination for large datasets

### Week 5-8: Advanced Features
- [ ] RBAC implementation
- [ ] Alert system (email/webhook)
- [ ] Service management enhancements
- [ ] Enhanced dashboard with history
- [ ] Email account management UI

### Week 9-12: Polish & Documentation
- [ ] Performance optimization
- [ ] Comprehensive API documentation
- [ ] Knowledge base/help system
- [ ] Mobile optimization
- [ ] Security audit

---

## Conclusion

These architectural improvements position SuperCP for:
1. **Scalability** - Handle thousands of users and operations
2. **Reliability** - Proper error handling and recovery
3. **Maintainability** - Clean code patterns and structure
4. **Security** - Comprehensive audit logging and validation
5. **User Experience** - Modern UI/UX with proper feedback

Focus on the foundation first (error handling, logging, transactions), then move to user-facing features.
