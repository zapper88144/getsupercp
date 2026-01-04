<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDnsZoneRequest;
use App\Jobs\SyncDnsToCloudflare;
use App\Models\DnsZone;
use App\Services\CloudflareService;
use App\Services\DnsService;
use App\Traits\HandlesDaemonErrors;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DnsZoneController extends Controller
{
    use HandlesDaemonErrors;

    public function __construct(
        protected DnsService $dnsService,
        protected CloudflareService $cloudflare
    ) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', DnsZone::class);

        $query = Auth::user()->dnsZones()->withCount('dnsRecords')->latest();

        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where('domain', 'like', "%{$search}%");
        }

        return Inertia::render('Dns/Index', [
            'zones' => $query->paginate(10)->withQueryString(),
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(StoreDnsZoneRequest $request)
    {
        $this->authorize('create', DnsZone::class);

        try {
            $zone = $this->dnsService->createZone(Auth::user(), $request->validated());

            // Create default records
            $defaultIp = config('dns.default_ip', '127.0.0.1');
            $nameservers = config('dns.nameservers', ['ns1.supercp.com.', 'ns2.supercp.com.']);

            $records = [
                ['type' => 'A', 'name' => '@', 'value' => $defaultIp],
                ['type' => 'A', 'name' => 'www', 'value' => $defaultIp],
            ];

            foreach ($nameservers as $ns) {
                $records[] = ['type' => 'NS', 'name' => '@', 'value' => $ns];
            }

            $this->dnsService->syncRecords($zone, $records);

            if ($request->boolean('sync_cloudflare')) {
                SyncDnsToCloudflare::dispatch($zone);
            }
        } catch (\Throwable $e) {
            return $this->handleDaemonError($e, 'DNS zone created locally, but failed to sync with system daemon.', route('dns-zones.index'));
        }

        return redirect()->route('dns-zones.index')->with('success', 'DNS zone created successfully.');
    }

    public function show(DnsZone $dnsZone)
    {
        $this->authorize('view', $dnsZone);

        return Inertia::render('Dns/Show', [
            'zone' => $dnsZone->load('dnsRecords'),
            'default_ip' => config('dns.default_ip', '127.0.0.1'),
            'availableTypes' => ['A', 'AAAA', 'CNAME', 'MX', 'TXT', 'NS'],
        ]);
    }

    public function updateRecords(Request $request, DnsZone $dnsZone)
    {
        $this->authorize('update', $dnsZone);

        $request->validate([
            'records' => ['required', 'array'],
            'records.*.id' => ['nullable', 'exists:dns_records,id'],
            'records.*.type' => ['required', 'string', 'in:A,AAAA,CNAME,MX,TXT,NS'],
            'records.*.name' => ['required', 'string'],
            'records.*.value' => ['required', 'string'],
            'records.*.priority' => ['nullable', 'integer'],
            'records.*.ttl' => ['required', 'integer', 'min:60'],
        ]);

        try {
            $this->dnsService->syncRecords($dnsZone, $request->records);

            if ($dnsZone->cloudflare_zone_id) {
                SyncDnsToCloudflare::dispatch($dnsZone);
            }
        } catch (\Throwable $e) {
            return $this->handleDaemonError($e, 'DNS records updated locally, but failed to sync with system daemon.');
        }

        return redirect()->back()->with('success', 'DNS records updated successfully.');
    }

    public function purgeCloudflareCache(DnsZone $dnsZone)
    {
        $this->authorize('update', $dnsZone);

        if ($this->cloudflare->purgeCache($dnsZone)) {
            return redirect()->back()->with('success', 'Cloudflare cache purged successfully.');
        }

        return redirect()->back()->with('error', 'Failed to purge Cloudflare cache.');
    }

    public function toggleCloudflareProxy(DnsZone $dnsZone)
    {
        $this->authorize('update', $dnsZone);

        $dnsZone->update([
            'cloudflare_proxy_enabled' => ! $dnsZone->cloudflare_proxy_enabled,
        ]);

        SyncDnsToCloudflare::dispatch($dnsZone);

        return redirect()->back()->with('success', 'Cloudflare proxy toggled successfully.');
    }

    public function destroy(DnsZone $dnsZone)
    {
        $this->authorize('delete', $dnsZone);

        try {
            $this->dnsService->deleteZone($dnsZone);
        } catch (\Throwable $e) {
            return $this->handleDaemonError($e, 'Failed to delete DNS zone from system daemon.', route('dns-zones.index'));
        }

        return redirect()->route('dns-zones.index')->with('success', 'DNS zone deleted successfully.');
    }
}
