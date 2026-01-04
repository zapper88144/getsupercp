<?php

namespace App\Http\Controllers;

use App\Models\SslCertificate;
use App\Models\User;
use App\Models\WebDomain;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SslCertificateController extends Controller
{
    public function index(): Response
    {
        /** @var User $user */
        $user = auth()->guard('web')->user();
        $certificates = $user->sslCertificates()
            ->with('webDomain')
            ->latest()
            ->get();

        return Inertia::render('Ssl/Index', [
            'certificates' => $certificates,
        ]);
    }

    public function show(SslCertificate $certificate)
    {
        $this->authorize('view', $certificate);

        return Inertia::render('Ssl/Show', [
            'certificate' => $certificate->load('webDomain'),
        ]);
    }

    public function create(): Response
    {
        /** @var User $user */
        $user = auth()->guard('web')->user();
        $domains = $user->webDomains()->get();

        return Inertia::render('Ssl/Create', [
            'domains' => $domains,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'web_domain_id' => 'required|exists:web_domains,id',
            'provider' => 'required|in:letsencrypt,custom',
            'validation_method' => 'required|in:dns,http,tls-alpn',
            'auto_renewal_enabled' => 'boolean',
        ]);

        $domain = WebDomain::findOrFail($validated['web_domain_id']);
        $this->authorize('view', $domain);

        /** @var User $user */
        $user = auth()->guard('web')->user();
        $certificate = $user->sslCertificates()->create([
            'web_domain_id' => $validated['web_domain_id'],
            'domain' => $domain->domain,
            'provider' => $validated['provider'],
            'validation_method' => $validated['validation_method'],
            'auto_renewal_enabled' => $validated['auto_renewal_enabled'] ?? true,
            'status' => 'pending',
        ]);

        return redirect()->route('ssl.show', $certificate)
            ->with('success', 'SSL certificate request initiated. Validation in progress.');
    }

    public function renew(SslCertificate $certificate)
    {
        $this->authorize('update', $certificate);

        $certificate->update([
            'status' => 'renewing',
            'renewal_attempts' => $certificate->renewal_attempts + 1,
            'renewal_scheduled_at' => now(),
        ]);

        // Queue renewal job
        // RenewSslCertificateJob::dispatch($certificate);

        return back()->with('success', 'Certificate renewal scheduled.');
    }

    public function destroy(SslCertificate $certificate)
    {
        $this->authorize('delete', $certificate);

        $certificate->delete();

        return redirect()->route('ssl.index')
            ->with('success', 'SSL certificate deleted.');
    }
}
