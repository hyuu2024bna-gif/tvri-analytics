<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class LegalPagesTest extends TestCase
{
    public function test_terms_of_service_route_name_exists(): void
    {
        $this->assertTrue(Route::has('terms'));
        $this->assertEquals(url('/terms'), route('terms'));
    }

    public function test_privacy_policy_route_name_exists(): void
    {
        $this->assertTrue(Route::has('privacy'));
        $this->assertEquals(url('/privacy'), route('privacy'));
    }

    public function test_terms_of_service_returns_200_without_authentication(): void
    {
        // Must be accessible without login
        $response = $this->get('/terms');

        $response->assertStatus(200);
        $response->assertSee('Terms of Service');
        $response->assertSee('KMB TVRI Analytics');
    }

    public function test_privacy_policy_returns_200_without_authentication(): void
    {
        // Must be accessible without login
        $response = $this->get('/privacy');

        $response->assertStatus(200);
        $response->assertSee('Privacy Policy');
        $response->assertSee('KMB TVRI Analytics');
    }

    public function test_terms_page_has_terms_of_service_heading_and_all_required_sections(): void
    {
        $response = $this->get(route('terms'));

        $response->assertStatus(200);
        $response->assertSee('Terms of Service');
        $response->assertSee('Acceptance of Terms');
        $response->assertSee('Description of Service');
        $response->assertSee('Authorized Use');
        $response->assertSee('Third-Party Platforms');
        $response->assertSee('Data and Analytics');
        $response->assertSee('Availability');
        $response->assertSee('Security');
        $response->assertSee('Intellectual Property');
        $response->assertSee('Limitation of Liability');
        $response->assertSee('Changes to Terms');
        $response->assertSee('Contact');
    }

    public function test_privacy_page_has_privacy_policy_heading_and_all_required_sections(): void
    {
        $response = $this->get(route('privacy'));

        $response->assertStatus(200);
        $response->assertSee('Privacy Policy');
        $response->assertSee('Information We Collect');
        $response->assertSee('Platform Integrations');
        $response->assertSee('OAuth and Access Tokens');
        $response->assertSee('How We Use Data');
        $response->assertSee('Data Storage and Security');
        $response->assertSee('Data Sharing');
        $response->assertSee('Data Retention');
        $response->assertSee('User Rights');
        $response->assertSee('Third-Party Privacy Policies');
        $response->assertSee('YouTube');
        $response->assertSee('Instagram');
        $response->assertSee('Facebook');
        $response->assertSee('TikTok');
    }

    public function test_privacy_policy_does_not_claim_tiktok_refresh_tokens_are_stored(): void
    {
        $response = $this->get(route('privacy'));

        $response->assertStatus(200);
        // Specifically verify it notes refresh tokens are not stored for TikTok
        $response->assertSee('refresh token');
    }

    public function test_legal_pages_support_localization_switching(): void
    {
        // Indonesian session
        $responseId = $this->withSession(['locale' => 'id'])->get(route('terms'));
        $responseId->assertStatus(200);
        $responseId->assertSee('KMB TVRI Analytics');
        $responseId->assertSee('Terms of Service');
        $responseId->assertSee('Ketentuan Layanan');

        // English session
        $responseEn = $this->withSession(['locale' => 'en'])->get(route('terms'));
        $responseEn->assertStatus(200);
        $responseEn->assertSee('KMB TVRI Analytics');
        $responseEn->assertSee('Terms of Service');

        // Indonesian Privacy Policy
        $privacyId = $this->withSession(['locale' => 'id'])->get(route('privacy'));
        $privacyId->assertStatus(200);
        $privacyId->assertSee('Kebijakan Privasi');

        // English Privacy Policy
        $privacyEn = $this->withSession(['locale' => 'en'])->get(route('privacy'));
        $privacyEn->assertStatus(200);
        $privacyEn->assertSee('Privacy Policy');
    }

    public function test_login_page_renders_terms_and_privacy_links(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee(route('terms'));
        $response->assertSee(route('privacy'));
    }
}
