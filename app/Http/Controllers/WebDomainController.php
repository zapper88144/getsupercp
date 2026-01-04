<?php

namespace App\Http\Controllers;

use App\Models\WebDomain;
use App\Services\WebDomainService;
use App\Traits\HandlesDaemonErrors;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class WebDomainController extends Controller
{
    use HandlesDaemonErrors;

    public function __construct(
        protected WebDomainService $webDomainService
    ) {}

    public function index(Request $request)
    {
        $query = WebDomain::where('user_id', Auth::id());

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('domain', 'like', "%{$search}%");
        }

        return Inertia::render('WebDomains/Index', [
            'domains' => $query->paginate(10)->withQueryString(),
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'domain' => 'required|string|unique:web_domains,domain',
            'php_version' => 'required|string|in:8.4', // Only 8.4 is supported in this environment
        ]);

        try {
            $this->webDomainService->create($request->user(), $validated);
        } catch (\Exception $e) {
            return $this->handleDaemonError($e, 'Failed to create web domain on the system.');
        }

        return redirect()->route('web-domains.index');
    }

    public function update(Request $request, WebDomain $webDomain)
    {
        $this->authorize('update', $webDomain);

        $validated = $request->validate([
            'php_version' => 'required|string|in:8.4',
            'is_active' => 'required|boolean',
        ]);

        try {
            $this->webDomainService->update($webDomain, $validated);
        } catch (\Exception $e) {
            return $this->handleDaemonError($e, 'Failed to sync changes with system daemon.');
        }

        return redirect()->route('web-domains.index');
    }

    public function toggleSsl(Request $request, WebDomain $webDomain)
    {
        $this->authorize('update', $webDomain);

        try {
            $this->webDomainService->toggleSsl($webDomain, ! $webDomain->has_ssl);
        } catch (\Exception $e) {
            return $this->handleDaemonError($e, 'Failed to sync SSL changes with system daemon.');
        }

        return redirect()->route('web-domains.index');
    }

    public function requestSsl(Request $request, WebDomain $webDomain)
    {
        $this->authorize('update', $webDomain);

        try {
            $this->webDomainService->renewSsl($webDomain);
        } catch (\Exception $e) {
            return $this->handleDaemonError($e, 'Failed to request SSL certificate: '.$e->getMessage());
        }

        return redirect()->route('web-domains.index');
    }

    public function destroy(WebDomain $webDomain)
    {
        $this->authorize('delete', $webDomain);

        try {
            $this->webDomainService->delete($webDomain);
        } catch (\Exception $e) {
            return $this->handleDaemonError($e, 'Failed to delete vhost from system daemon.');
        }

        return redirect()->route('web-domains.index');
    }
}
