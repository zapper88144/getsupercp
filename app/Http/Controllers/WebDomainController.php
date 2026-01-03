<?php

namespace App\Http\Controllers;

use App\Models\WebDomain;
use App\Services\RustDaemonClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class WebDomainController extends Controller
{
    public function __construct(
        protected RustDaemonClient $daemon
    ) {}

    public function index()
    {
        return Inertia::render('WebDomains/Index', [
            'domains' => WebDomain::where('user_id', Auth::id())->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'domain' => 'required|string|unique:web_domains,domain',
            'php_version' => 'required|string|in:8.1,8.2,8.3,8.4',
        ]);

        $user = $request->user();
        $rootPath = "/home/{$user->name}/web/{$validated['domain']}/public";

        $domain = WebDomain::create([
            'user_id' => $user->id,
            'domain' => $validated['domain'],
            'root_path' => $rootPath,
            'php_version' => $validated['php_version'],
        ]);

        // Call Rust daemon to create system configs
        try {
            $this->daemon->call('create_vhost', [
                'domain' => $domain->domain,
                'user' => $user->name,
                'root' => $domain->root_path,
                'php_version' => $domain->php_version,
                'has_ssl' => false,
                'ssl_certificate_path' => null,
                'ssl_key_path' => null,
            ]);
        } catch (\Exception $e) {
            // In a real app, we might want to roll back or mark as "failed"
            return back()->withErrors(['daemon' => 'Failed to communicate with system daemon.']);
        }

        return redirect()->route('web-domains.index');
    }

    public function update(Request $request, WebDomain $webDomain)
    {
        $this->authorize('update', $webDomain);

        $validated = $request->validate([
            'php_version' => 'required|string|in:8.1,8.2,8.3,8.4',
            'is_active' => 'required|boolean',
        ]);

        $webDomain->update($validated);

        // Sync with daemon
        try {
            if ($webDomain->is_active) {
                $this->daemon->call('create_vhost', [
                    'domain' => $webDomain->domain,
                    'user' => $request->user()->name,
                    'root' => $webDomain->root_path,
                    'php_version' => $webDomain->php_version,
                    'has_ssl' => $webDomain->has_ssl,
                    'ssl_certificate_path' => $webDomain->ssl_certificate_path,
                    'ssl_key_path' => $webDomain->ssl_key_path,
                ]);
            } else {
                $this->daemon->call('delete_vhost', [
                    'domain' => $webDomain->domain,
                    'user' => $request->user()->name,
                ]);
            }
        } catch (\Exception $e) {
            return back()->withErrors(['daemon' => 'Failed to sync changes with system daemon.']);
        }

        return redirect()->route('web-domains.index');
    }

    public function toggleSsl(Request $request, WebDomain $webDomain)
    {
        $this->authorize('update', $webDomain);

        $webDomain->update([
            'has_ssl' => ! $webDomain->has_ssl,
            // In a real app, we would generate these paths or use Let's Encrypt
            'ssl_certificate_path' => $webDomain->has_ssl ? null : "/etc/letsencrypt/live/{$webDomain->domain}/fullchain.pem",
            'ssl_key_path' => $webDomain->has_ssl ? null : "/etc/letsencrypt/live/{$webDomain->domain}/privkey.pem",
        ]);

        // Sync with daemon
        try {
            $this->daemon->call('create_vhost', [
                'domain' => $webDomain->domain,
                'user' => $request->user()->name,
                'root' => $webDomain->root_path,
                'php_version' => $webDomain->php_version,
                'has_ssl' => $webDomain->has_ssl,
                'ssl_certificate_path' => $webDomain->ssl_certificate_path,
                'ssl_key_path' => $webDomain->ssl_key_path,
            ]);
        } catch (\Exception $e) {
            return back()->withErrors(['daemon' => 'Failed to sync SSL changes with system daemon.']);
        }

        return redirect()->route('web-domains.index');
    }

    public function requestSsl(Request $request, WebDomain $webDomain)
    {
        $this->authorize('update', $webDomain);

        try {
            $this->daemon->call('request_ssl_cert', [
                'domain' => $webDomain->domain,
            ]);

            $certificatePath = "/etc/letsencrypt/live/{$webDomain->domain}/fullchain.pem";
            $keyPath = "/etc/letsencrypt/live/{$webDomain->domain}/privkey.pem";
            $expiresAt = null;

            // Extract expiration date from the certificate if it exists
            if (file_exists($certificatePath)) {
                $certData = openssl_x509_parse(file_get_contents($certificatePath));
                if ($certData && isset($certData['validTo_time_t'])) {
                    $expiresAt = \Carbon\Carbon::createFromTimestamp($certData['validTo_time_t']);
                }
            }

            $webDomain->update([
                'has_ssl' => true,
                'ssl_certificate_path' => $certificatePath,
                'ssl_key_path' => $keyPath,
                'ssl_expires_at' => $expiresAt,
            ]);

            // Update vhost to use the new cert
            $this->daemon->call('create_vhost', [
                'domain' => $webDomain->domain,
                'user' => $request->user()->name,
                'root' => $webDomain->root_path,
                'php_version' => $webDomain->php_version,
                'has_ssl' => true,
                'ssl_certificate_path' => $webDomain->ssl_certificate_path,
                'ssl_key_path' => $webDomain->ssl_key_path,
            ]);
        } catch (\Exception $e) {
            return back()->withErrors(['daemon' => 'Failed to request SSL certificate: '.$e->getMessage()]);
        }

        return redirect()->route('web-domains.index');
    }

    public function destroy(WebDomain $webDomain)
    {
        $this->authorize('delete', $webDomain);

        $user = Auth::user();

        try {
            $this->daemon->call('delete_vhost', [
                'domain' => $webDomain->domain,
                'user' => $user->name,
            ]);
        } catch (\Exception $e) {
            // Log error but continue deletion from DB?
            // Or prevent deletion if daemon fails?
        }

        $webDomain->delete();

        return redirect()->route('web-domains.index');
    }
}
