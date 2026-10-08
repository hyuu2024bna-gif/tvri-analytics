<?php

namespace Tests\Feature;

use App\Models\Platform;
use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MetaDiagnosticTest extends TestCase
{
    use RefreshDatabase;

    public function test_diagnostic_meta_cannot_be_accessed_by_guest(): void
    {
        $response = $this->get('/diagnostic/meta');

        $response->assertRedirect('/login');
    }

    public function test_diagnostic_meta_can_be_accessed_by_authenticated_user(): void
    {
        $user = User::factory()->create();

        Http::fake([
            'https://graph.facebook.com/*' => Http::response(['data' => []], 200),
        ]);

        $response = $this
            ->actingAs($user)
            ->get('/diagnostic/meta');

        $response->assertOk();
        $response->assertViewIs('diagnostic.meta');
        $response->assertSee('Meta Diagnostic');
        $response->assertSee('Application');
        $response->assertSee('OAuth Routes');
        $response->assertSee('Stored Social Accounts');
        $response->assertSee('Permissions');
        $response->assertSee('API Health');
        $response->assertSee('Access Levels');
    }

    public function test_diagnostic_meta_does_not_expose_app_secret_or_access_token(): void
    {
        $user = User::factory()->create();

        $dummySecret = 'super_secret_fb_app_secret_xyz123';
        $dummyToken = 'EAAGm0PXqBZCYBA_dummy_secret_access_token_999';

        config([
            'services.facebook.app_id' => '1598238835233623',
            'services.facebook.app_secret' => $dummySecret,
        ]);

        $platform = Platform::create([
            'nama' => 'Instagram',
            'slug' => 'instagram',
        ]);

        SocialAccount::create([
            'platform_id' => $platform->id,
            'external_account_id' => '17841414598432682',
            'username' => 'wahyu_abdilahh',
            'page_id' => '1327082517151077',
            'access_token' => $dummyToken,
        ]);

        Http::fake([
            'https://graph.facebook.com/*/debug_token*' => Http::response([
                'data' => [
                    'is_valid' => true,
                    'scopes' => ['instagram_basic', 'pages_show_list'],
                    'type' => 'PAGE',
                    'application' => 'TVRI ACEH Analyst',
                ],
            ], 200),
            'https://graph.facebook.com/*' => Http::response(['id' => '17841414598432682'], 200),
        ]);

        $response = $this
            ->actingAs($user)
            ->get('/diagnostic/meta');

        $response->assertOk();

        // Ensure the secret and token NEVER appear in response HTML
        $response->assertDontSee($dummySecret);
        $response->assertDontSee($dummyToken);

        // Instead, safe status markers should be visible
        $response->assertSee('CONFIGURED');
        $response->assertSee('PRESENT');
        $response->assertSee('wahyu_abdilahh');
    }

    public function test_diagnostic_meta_clearly_distinguishes_business_portfolio_access(): void
    {
        $user = User::factory()->create();

        Http::fake([
            'https://graph.facebook.com/*' => Http::response([], 200),
        ]);

        $response = $this
            ->actingAs($user)
            ->get('/diagnostic/meta');

        $response->assertOk();
        $response->assertSee('Cannot determine from application credentials');
    }

    public function test_facebook_card_displays_page_id_and_live_page_name_without_instagram_identity(): void
    {
        $user = User::factory()->create();

        $igPlatform = Platform::create([
            'nama' => 'Instagram',
            'slug' => 'instagram',
        ]);

        Platform::create([
            'nama' => 'Facebook',
            'slug' => 'facebook',
        ]);

        SocialAccount::create([
            'platform_id' => $igPlatform->id,
            'external_account_id' => '17841414598432682',
            'username' => 'wahyu_abdilahh',
            'page_id' => '1327082517151077',
            'access_token' => 'dummy_page_token_abc',
        ]);

        Http::fake([
            'https://graph.facebook.com/*/1327082517151077*' => Http::response([
                'id' => '1327082517151077',
                'name' => 'Test Page KMB TVRI',
            ], 200),
            'https://graph.facebook.com/*' => Http::response([], 200),
        ]);

        $response = $this
            ->actingAs($user)
            ->get('/diagnostic/meta');

        $response->assertOk();

        // Assert data view
        $report = $response->viewData('report');
        $fbData = $report['accounts']['facebook'];

        $this->assertTrue($fbData['is_facebook']);
        $this->assertEquals('YES', $fbData['connected']);
        $this->assertEquals('Test Page KMB TVRI', $fbData['page_name']);
        $this->assertEquals('1327082517151077', $fbData['page_id']);
        $this->assertArrayNotHasKey('username', $fbData);
        $this->assertArrayNotHasKey('external_id', $fbData);

        // Assert HTML view output
        $response->assertSee('Test Page KMB TVRI');
        $response->assertSee('1327082517151077');
        $response->assertSee('Page Name');
        $response->assertSee('Page ID');
    }

    public function test_facebook_card_uses_honest_fallback_when_page_name_api_fails(): void
    {
        $user = User::factory()->create();

        $igPlatform = Platform::create([
            'nama' => 'Instagram',
            'slug' => 'instagram',
        ]);

        Platform::create([
            'nama' => 'Facebook',
            'slug' => 'facebook',
        ]);

        SocialAccount::create([
            'platform_id' => $igPlatform->id,
            'external_account_id' => '17841414598432682',
            'username' => 'wahyu_abdilahh',
            'page_id' => '1327082517151077',
            'access_token' => 'dummy_page_token_abc',
        ]);

        Http::fake([
            'https://graph.facebook.com/*/1327082517151077*' => Http::response(['error' => 'API Unavailable'], 500),
            'https://graph.facebook.com/*' => Http::response([], 200),
        ]);

        $response = $this
            ->actingAs($user)
            ->get('/diagnostic/meta');

        $response->assertOk();

        $report = $response->viewData('report');
        $fbData = $report['accounts']['facebook'];

        $this->assertEquals('Facebook Page (shared Meta connection)', $fbData['page_name']);
        $this->assertEquals('1327082517151077', $fbData['page_id']);
        $response->assertSee('Facebook Page (shared Meta connection)');
    }
}
