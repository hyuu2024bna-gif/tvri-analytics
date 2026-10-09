<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DiagnosticEndpointsAccessTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Daftar 7 endpoint diagnostik yang harus dilindungi auth dan admin.
     *
     * @var array<string>
     */
    protected array $diagnosticEndpoints = [
        '/tiktok/test',
        '/tiktok/test-videos',
        '/instagram/test',
        '/instagram/test-media',
        '/facebook/test',
        '/facebook/test-posts',
        '/diagnostic/meta',
    ];

    public function test_guest_cannot_access_any_diagnostic_endpoint(): void
    {
        foreach ($this->diagnosticEndpoints as $endpoint) {
            $response = $this->get($endpoint);

            $response->assertRedirect('/login', "Endpoint {$endpoint} should redirect guest to /login");
        }
    }

    public function test_staf_user_is_forbidden_from_all_diagnostic_endpoints(): void
    {
        $staf = User::factory()->create(['role' => 'staf']);

        foreach ($this->diagnosticEndpoints as $endpoint) {
            $response = $this
                ->actingAs($staf)
                ->get($endpoint);

            $response->assertForbidden("Endpoint {$endpoint} should return 403 Forbidden for staf role");
        }
    }

    public function test_admin_user_is_authorized_to_access_diagnostic_endpoints(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Http::fake([
            'https://graph.facebook.com/*' => Http::response(['data' => []], 200),
            'https://open.tiktokapis.com/*' => Http::response(['data' => []], 200),
        ]);

        foreach ($this->diagnosticEndpoints as $endpoint) {
            $response = $this
                ->actingAs($admin)
                ->get($endpoint);

            // Admin tidak boleh dialihkan ke login (bukan 302 ke /login) dan tidak boleh 403 Forbidden
            $this->assertNotEquals(
                403,
                $response->getStatusCode(),
                "Endpoint {$endpoint} should NOT return 403 Forbidden for admin"
            );
            $this->assertFalse(
                $response->isRedirect('/login'),
                "Endpoint {$endpoint} should NOT redirect admin to /login"
            );
        }
    }

    public function test_retained_oauth_and_status_routes_remain_functional(): void
    {
        // 1. Callbacks are accessible without auth (provider redirects, not login redirect)
        $this->get('/youtube/callback')->assertRedirect(route('dashboard', ['platform' => 'youtube']));
        $this->get('/tiktok/callback')->assertRedirect(route('tiktok.status'));
        $this->get('/instagram/callback')->assertRedirect(route('instagram.status'));

        // 2. Connect and status routes require auth
        $this->get('/youtube/connect')->assertRedirect('/login');
        $this->get('/tiktok/connect')->assertRedirect('/login');
        $this->get('/tiktok/status')->assertRedirect('/login');
        $this->get('/instagram/connect')->assertRedirect('/login');
        $this->get('/instagram/status')->assertRedirect('/login');

        // 3. Authenticated staf can access connection/status pages
        $staf = User::factory()->create(['role' => 'staf']);
        $this->actingAs($staf)->get('/tiktok/status')->assertOk();
        $this->actingAs($staf)->get('/instagram/status')->assertOk();
    }
}
