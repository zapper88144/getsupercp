<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Services\TwoFactorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class TwoFactorTest extends TestCase
{
    use RefreshDatabase;

    public function test_two_factor_setup_page_can_be_rendered(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/two-factor-setup');

        $response->assertStatus(200);
        $response->assertSee('secret');
    }

    public function test_two_factor_can_be_enabled(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $secret = 'K65G66S66S66S66S';
        session(['2fa_secret' => $secret]);

        $twoFactorService = Mockery::mock(TwoFactorService::class);
        $twoFactorService->shouldReceive('verifyCode')->with($secret, '123456')->andReturn(true);
        $twoFactorService->shouldReceive('generateRecoveryCodes')->andReturn(['code1', 'code2']);
        $twoFactorService->shouldReceive('enable')->once();

        $this->app->instance(TwoFactorService::class, $twoFactorService);

        $response = $this->actingAs($user)->post('/two-factor-setup', [
            'code' => '123456',
        ]);

        $response->assertRedirect('/two-factor-recovery-codes');
        $this->assertEquals(['code1', 'code2'], session('2fa_recovery_codes'));
    }

    public function test_two_factor_challenge_is_enforced(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $user->twoFactorAuthentication()->create([
            'secret' => 'secret',
            'is_enabled' => true,
            'method' => 'totp',
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertRedirect('/two-factor-challenge');
    }

    public function test_two_factor_can_be_verified(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $twoFactor = $user->twoFactorAuthentication()->create([
            'secret' => 'secret',
            'is_enabled' => true,
            'method' => 'totp',
        ]);

        $twoFactorService = Mockery::mock(TwoFactorService::class);
        $twoFactorService->shouldReceive('verifyCode')->with('secret', '123456')->andReturn(true);
        $this->app->instance(TwoFactorService::class, $twoFactorService);

        $response = $this->actingAs($user)->post('/two-factor-challenge', [
            'code' => '123456',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertNotNull(session('2fa_verified_at'));
    }

    public function test_two_factor_can_be_verified_with_recovery_code(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $twoFactor = $user->twoFactorAuthentication()->create([
            'secret' => 'secret',
            'is_enabled' => true,
            'method' => 'totp',
            'recovery_codes' => ['recovery-1', 'recovery-2'],
        ]);

        $response = $this->actingAs($user)->post('/two-factor-challenge', [
            'recovery_code' => 'recovery-1',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertNotNull(session('2fa_verified_at'));
        $this->assertEquals(['recovery-2'], $user->fresh()->twoFactorAuthentication->recovery_codes);
    }
}
