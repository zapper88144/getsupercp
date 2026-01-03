<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDnsZoneRequest;
use App\Models\DnsRecord;
use App\Models\DnsZone;
use App\Services\RustDaemonClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DnsZoneController extends Controller
{
    public function __construct(protected RustDaemonClient $daemon)
    {
    }

    public function index()
    {
        $this->authorize('viewAny', DnsZone::class);

        return Inertia::render('Dns/Index', [
            'zones' => Auth::user()->dnsZones()->withCount('dnsRecords')->get(),
        ]);
    }

    public function store(StoreDnsZoneRequest $request)
    {
        $this->authorize('create', DnsZone::class);

        $zone = Auth::user()->dnsZones()->create($request->validated());

        // Create default records
        $zone->dnsRecords()->createMany([
            ['type' => 'A', 'name' => '@', 'value' => '127.0.0.1'],
            ['type' => 'A', 'name' => 'www', 'value' => '127.0.0.1'],
            ['type' => 'NS', 'name' => '@', 'value' => 'ns1.supercp.com.'],
            ['type' => 'NS', 'name' => '@', 'value' => 'ns2.supercp.com.'],
        ]);

        $this->syncWithDaemon($zone);

        return redirect()->route('dns-zones.index')->with('success', 'DNS zone created successfully.');
    }

    public function show(DnsZone $dnsZone)
    {
        $this->authorize('view', $dnsZone);

        return Inertia::render('Dns/Show', [
            'zone' => $dnsZone->load('dnsRecords'),
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

        $submittedIds = collect($request->records)->pluck('id')->filter()->toArray();
        
        // Delete records not in submitted list
        $dnsZone->dnsRecords()->whereNotIn('id', $submittedIds)->delete();

        foreach ($request->records as $recordData) {
            if (isset($recordData['id'])) {
                $dnsZone->dnsRecords()->where('id', $recordData['id'])->update(collect($recordData)->except('id')->toArray());
            } else {
                $dnsZone->dnsRecords()->create($recordData);
            }
        }

        $this->syncWithDaemon($dnsZone);

        return redirect()->back()->with('success', 'DNS records updated successfully.');
    }

    public function destroy(DnsZone $dnsZone)
    {
        $this->authorize('delete', $dnsZone);

        $this->daemon->call('delete_dns_zone', ['domain' => $dnsZone->domain]);
        $dnsZone->delete();

        return redirect()->route('dns-zones.index')->with('success', 'DNS zone deleted successfully.');
    }

    protected function syncWithDaemon(DnsZone $zone)
    {
        $records = $zone->dnsRecords()->get()->map(function ($record) {
            return [
                'name' => $record->name,
                'type' => $record->type,
                'value' => $record->value,
                'priority' => $record->priority,
                'ttl' => $record->ttl,
            ];
        })->toArray();

        $this->daemon->call('update_dns_zone', [
            'domain' => $zone->domain,
            'records' => $records,
        ]);
    }
}
