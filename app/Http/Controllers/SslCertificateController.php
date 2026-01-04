<?php

namespace App\Http\Controllers;

use App\Models\SslCertificate;
use App\Models\User;
use App\Models\WebDomain;
use App\Services\SslService;
use App\Traits\HandlesDaemonErrors;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SslCertificateController extends Controller
{
    use HandlesDaemonErrors;

    public function __construct(
        protected SslService $sslService
    ) {}

    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = auth()->guard('web')->user();

        $query = $user->sslCertificates()
            ->with('webDomain')
            ->latest();

        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('domain', 'like', "%{$search}%")
                    ->orWhere('provider', 'like', "%{$search}%")
                    ->orWhere('status', 'like', "%{$search}%");
            });
        }

        $certificates = $query->paginate(10)->withQueryString();

        return Inertia::render('Ssl/Index', [
            'certificates' => $certificates,
            'filters' => $request->only(['search']),
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
            'validation_method' => 'required_if:provider,letsencrypt|in:dns,http,tls-alpn',
            'auto_renewal_enabled' => 'boolean',
            'input_type' => 'required_if:provider,custom|in:file,text',
            'certificate' => 'required_if:input_type,file|file',
            'private_key' => 'required_if:input_type,file|file',
            'ca_bundle' => 'nullable|file',
            'certificate_text' => 'required_if:input_type,text|string',
            'private_key_text' => 'required_if:input_type,text|string',
            'ca_bundle_text' => 'nullable|string',
        ]);

        $domain = WebDomain::findOrFail($validated['web_domain_id']);
        $this->authorize('view', $domain);

        /** @var User $user */
        $user = auth()->guard('web')->user();

        try {
            if ($validated['provider'] === 'letsencrypt') {
                $certificate = $this->sslService->requestLetsEncrypt(
                    $domain,
                    $user,
                    $validated['validation_method'] ?? 'http'
                );
            } else {
                $data = [];
                if ($validated['input_type'] === 'file') {
                    $data['certificate_content'] = file_get_contents($request->file('certificate')->getRealPath());
                    $data['key_content'] = file_get_contents($request->file('private_key')->getRealPath());
                    if ($request->hasFile('ca_bundle')) {
                        $data['ca_bundle_content'] = file_get_contents($request->file('ca_bundle')->getRealPath());
                    }
                } else {
                    $data['certificate_content'] = $validated['certificate_text'];
                    $data['key_content'] = $validated['private_key_text'];
                    $data['ca_bundle_content'] = $validated['ca_bundle_text'] ?? null;
                }

                $certificate = $this->sslService->installCustom($domain, $user, $data);
            }

            return redirect()->route('ssl.show', $certificate)
                ->with('success', $validated['provider'] === 'letsencrypt'
                    ? 'SSL certificate requested and activated successfully.'
                    : 'Custom SSL certificate uploaded and activated.');
        } catch (\Exception $e) {
            return $this->handleDaemonError($e, 'Failed to process SSL certificate: '.$e->getMessage());
        }
    }

    public function renew(SslCertificate $certificate)
    {
        $this->authorize('update', $certificate);

        try {
            $this->sslService->renew($certificate);

            return back()->with('success', 'Certificate renewed successfully.');
        } catch (\Exception $e) {
            return $this->handleDaemonError($e, 'Failed to renew certificate: '.$e->getMessage());
        }
    }

    public function destroy(SslCertificate $certificate)
    {
        $this->authorize('delete', $certificate);

        try {
            $this->sslService->delete($certificate);

            return redirect()->route('ssl.index')
                ->with('success', 'SSL certificate deleted.');
        } catch (\Exception $e) {
            return $this->handleDaemonError($e, 'Failed to delete certificate: '.$e->getMessage());
        }
    }
}
