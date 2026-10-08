<?php

namespace Tests\Feature;

use App\Models\Platform;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocalizationTest extends TestCase
{
    use RefreshDatabase;

    private function createPlatforms(): void
    {
        Platform::firstOrCreate(['slug' => 'youtube'], ['nama' => 'YouTube']);
        Platform::firstOrCreate(['slug' => 'instagram'], ['nama' => 'Instagram']);
        Platform::firstOrCreate(['slug' => 'facebook'], ['nama' => 'Facebook']);
        Platform::firstOrCreate(['slug' => 'tiktok'], ['nama' => 'TikTok']);
    }

    public function test_default_locale_is_indonesian(): void
    {
        $this->assertEquals('id', config('app.locale'));
        $this->assertEquals('id', config('app.fallback_locale'));

        $response = $this->get('/login');
        $response->assertStatus(200);
        $this->assertEquals('id', app()->getLocale());
    }

    public function test_can_switch_locale_from_id_to_en(): void
    {
        $response = $this->withSession(['locale' => 'id'])
            ->get(route('locale.switch', 'en'));

        $response->assertRedirect();
        $response->assertSessionHas('locale', 'en');
    }

    public function test_can_switch_locale_from_en_to_id(): void
    {
        $response = $this->withSession(['locale' => 'en'])
            ->get(route('locale.switch', 'id'));

        $response->assertRedirect();
        $response->assertSessionHas('locale', 'id');
    }

    public function test_invalid_locale_is_rejected(): void
    {
        $response = $this->get(route('locale.switch', 'fr'));
        $response->assertStatus(400);

        $response2 = $this->get(route('locale.switch', 'unknown_locale'));
        $response2->assertStatus(400);
    }

    public function test_session_locale_persists_across_page_navigation(): void
    {
        $this->createPlatforms();
        $user = User::factory()->create();

        // 1. Visit with locale=en
        $response1 = $this->actingAs($user)
            ->withSession(['locale' => 'en'])
            ->get(route('dashboard'));

        $response1->assertStatus(200);
        $response1->assertSee('All Platforms');
        $response1->assertSee('Active Content');
        $response1->assertSee('Current Views');

        // 2. Subsequent request preserves 'en' locale from session
        $response2 = $this->actingAs($user)
            ->withSession(['locale' => 'en'])
            ->get(route('profile.edit'));

        $response2->assertStatus(200);
        $this->assertEquals('en', app()->getLocale());
    }

    public function test_dashboard_renders_correctly_in_indonesian(): void
    {
        $this->createPlatforms();
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->withSession(['locale' => 'id'])
            ->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Semua Platform');
        $response->assertSee('Konten Aktif');
        $response->assertSee('Views Saat Ini');
        $response->assertSee('Views Bertambah');
        $response->assertSee('Top 10 Konten');
    }

    public function test_dashboard_renders_correctly_in_english(): void
    {
        $this->createPlatforms();
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->withSession(['locale' => 'en'])
            ->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('All Platforms');
        $response->assertSee('Active Content');
        $response->assertSee('Current Views');
        $response->assertSee('Views Growth');
        $response->assertSee('Top 10 Content');
    }

    public function test_period_buttons_render_correctly_without_raw_translation_keys(): void
    {
        $this->createPlatforms();
        $user = User::factory()->create();

        // Test Indonesian Period Labels
        $responseId = $this->actingAs($user)
            ->withSession(['locale' => 'id'])
            ->get(route('dashboard'));

        $responseId->assertStatus(200);
        $responseId->assertSee('Hari Ini');
        $responseId->assertSee('7 Hari');
        $responseId->assertSee('15 Hari');
        $responseId->assertSee('30 Hari');
        $responseId->assertSee('Semua Waktu');
        $responseId->assertSee('Custom');
        $responseId->assertDontSee('app.dashboard.days');

        // Test English Period Labels
        $responseEn = $this->actingAs($user)
            ->withSession(['locale' => 'en'])
            ->get(route('dashboard'));

        $responseEn->assertStatus(200);
        $responseEn->assertSee('Today');
        $responseEn->assertSee('7 Days');
        $responseEn->assertSee('15 Days');
        $responseEn->assertSee('30 Days');
        $responseEn->assertSee('All Time');
        $responseEn->assertSee('Custom');
        $responseEn->assertDontSee('app.dashboard.days');
    }

    public function test_period_button_active_state_is_consistent_and_bold(): void
    {
        $this->createPlatforms();
        $user = User::factory()->create();

        // Check period 7 (default) — is-active class present, font-weight bold via inline style
        $response7 = $this->actingAs($user)->get(route('dashboard', ['period' => '7']));
        $response7->assertStatus(200);
        // The 7 days button has is-active class
        $this->assertMatchesRegularExpression('/period=7"[^>]*class="[^"]*is-active/', $response7->getContent());
        // The 7 days button has bold font-weight via inline style
        $this->assertMatchesRegularExpression('/period=7"[^>]*style="[^"]*font-weight:\s*800/', $response7->getContent());
        // The 15 days button does NOT have is-active
        $this->assertDoesNotMatchRegularExpression('/period=15"[^>]*class="[^"]*is-active/', $response7->getContent());

        // Check period 15
        $response15 = $this->actingAs($user)->get(route('dashboard', ['period' => '15']));
        $response15->assertStatus(200);
        $this->assertMatchesRegularExpression('/period=15"[^>]*class="[^"]*is-active/', $response15->getContent());
        $this->assertMatchesRegularExpression('/period=15"[^>]*style="[^"]*font-weight:\s*800/', $response15->getContent());
        $this->assertDoesNotMatchRegularExpression('/period=7"[^>]*class="[^"]*is-active/', $response15->getContent());

        // Check period 30
        $response30 = $this->actingAs($user)->get(route('dashboard', ['period' => '30']));
        $response30->assertStatus(200);
        $this->assertMatchesRegularExpression('/period=30"[^>]*class="[^"]*is-active/', $response30->getContent());
        $this->assertMatchesRegularExpression('/period=30"[^>]*style="[^"]*font-weight:\s*800/', $response30->getContent());
        $this->assertDoesNotMatchRegularExpression('/period=7"[^>]*class="[^"]*is-active/', $response30->getContent());

        // Check period today
        $responseToday = $this->actingAs($user)->get(route('dashboard', ['period' => 'today']));
        $responseToday->assertStatus(200);
        $this->assertMatchesRegularExpression('/period=today"[^>]*class="[^"]*is-active/', $responseToday->getContent());
        $this->assertMatchesRegularExpression('/period=today"[^>]*style="[^"]*font-weight:\s*800/', $responseToday->getContent());
        $this->assertDoesNotMatchRegularExpression('/period=7"[^>]*class="[^"]*is-active/', $responseToday->getContent());
    }

    public function test_language_switcher_is_integrated_inside_user_dropdown(): void
    {
        $this->createPlatforms();
        $user = User::factory()->create(['name' => 'Admin KMB']);

        $response = $this->actingAs($user)->get(route('dashboard'));
        $response->assertStatus(200);

        // User name is displayed
        $response->assertSee('Admin KMB');

        // Language switch URLs are present inside the view
        $response->assertSee(route('locale.switch', 'id'));
        $response->assertSee(route('locale.switch', 'en'));

        // Profile and Logout links are present
        $response->assertSee(route('profile.edit'));
        $response->assertSee(route('logout'));
    }

    public function test_login_page_renders_correctly_in_indonesian(): void
    {
        $response = $this->withSession(['locale' => 'id'])
            ->get(route('login'));

        $response->assertStatus(200);
        $response->assertSee('Selamat Datang');
        $response->assertSee('Ingatkan saya');
        $response->assertSee('Masuk');
    }

    public function test_login_page_renders_correctly_in_english(): void
    {
        $response = $this->withSession(['locale' => 'en'])
            ->get(route('login'));

        $response->assertStatus(200);
        $response->assertSee('Welcome Back');
        $response->assertSee('Remember me');
        $response->assertSee('Log In');
    }
}
