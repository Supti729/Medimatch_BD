<?php

namespace Tests\Feature\Matching;

use App\Enums\Role;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ConsoleCommandsTest extends TestCase
{
    use RefreshDatabase;

    public function test_ai_status_reports_a_working_service(): void
    {
        config(['services.matching.enabled' => true]);
        Cache::flush();
        Http::fake([
            'matching.test/health' => Http::response(['status' => 'ok', 'semantic_model' => 'e5', 'model_loaded' => true]),
            'matching.test/normalize' => Http::response(['normalized' => 'napa 500 tablet', 'brands' => ['napa'], 'generics' => ['paracetamol'], 'strengths' => [500], 'form' => 'tablet', 'corrections' => []]),
        ]);

        $this->artisan('ai:status')->expectsOutputToContain('Status: online')->expectsOutputToContain('understood as paracetamol')->assertSuccessful();
    }

    public function test_ai_status_fails_when_the_service_is_unreachable(): void
    {
        config(['services.matching.enabled' => true]);
        Http::fake(['matching.test/*' => Http::response('', 503)]);

        $this->artisan('ai:status')->expectsOutputToContain('keyword matching')->assertFailed();
    }

    public function test_make_admin_promotes_a_verified_account_after_confirmation_and_is_audited(): void
    {
        $user = User::factory()->create(['email' => 'lead@example.com']);

        $this->artisan('app:make-admin', ['login' => 'lead@example.com'])->expectsConfirmation("Give {$user->name} (user #{$user->id}) full administrator access?", 'yes')->assertSuccessful();

        $this->assertSame(Role::Admin, $user->fresh()->role);
        $this->assertTrue(AuditLog::where('action', 'user.made_admin')->exists());
    }

    public function test_make_admin_refuses_unknown_or_unverified_accounts(): void
    {
        $unverified = User::factory()->unverified()->create(['email' => 'new@example.com']);

        $this->artisan('app:make-admin', ['login' => 'nobody@example.com'])->assertFailed();
        $this->artisan('app:make-admin', ['login' => 'new@example.com'])->assertFailed();
        $this->assertSame(Role::User, $unverified->fresh()->role);
    }
}
