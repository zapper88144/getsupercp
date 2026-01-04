<?php

namespace App\Services;

use App\Models\TwoFactorAuthentication;
use App\Models\User;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Str;
use PragmaRX\Google2FALaravel\Facade as Google2FA;

class TwoFactorService
{
    public function generateSecret(): string
    {
        return Google2FA::generateSecretKey();
    }

    public function getQrCodeUrl(User $user, string $secret): string
    {
        return Google2FA::getQRCodeUrl(
            config('app.name'),
            $user->email,
            $secret
        );
    }

    public function getQrCodeSvg(string $url): string
    {
        $renderer = new ImageRenderer(
            new RendererStyle(200),
            new SvgImageBackEnd
        );
        $writer = new Writer($renderer);

        return $writer->writeString($url);
    }

    public function verifyCode(string $secret, string $code): bool
    {
        return Google2FA::verifyKey($secret, $code);
    }

    public function enable(User $user, string $secret, array $recoveryCodes): TwoFactorAuthentication
    {
        $user->update(['two_factor_enabled' => true]);

        return TwoFactorAuthentication::updateOrCreate(
            ['user_id' => $user->id],
            [
                'secret' => $secret,
                'recovery_codes' => $recoveryCodes,
                'is_enabled' => true,
                'enabled_at' => now(),
                'method' => 'totp',
            ]
        );
    }

    public function disable(User $user): void
    {
        $user->update(['two_factor_enabled' => false]);

        $user->twoFactorAuthentication()->update([
            'is_enabled' => false,
            'enabled_at' => null,
            'secret' => null,
            'recovery_codes' => null,
        ]);
    }

    public function generateRecoveryCodes(): array
    {
        return collect(range(1, 8))->map(fn () => Str::random(10).'-'.Str::random(10))->toArray();
    }
}
