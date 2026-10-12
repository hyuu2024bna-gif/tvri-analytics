<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/admin/users');
        $response->assertRedirect('/login');

        $responsePost = $this->post('/admin/users', [
            'name' => 'New User',
            'email' => 'new@tvri.go.id',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);
        $responsePost->assertRedirect('/login');
    }

    public function test_staf_user_is_forbidden(): void
    {
        $staf = User::factory()->create(['role' => 'staf']);

        $response = $this->actingAs($staf)->get('/admin/users');
        $response->assertForbidden();

        $responsePost = $this->actingAs($staf)->post('/admin/users', [
            'name' => 'New User',
            'email' => 'new@tvri.go.id',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);
        $responsePost->assertForbidden();
    }

    public function test_admin_can_view_users_index(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'name' => 'Admin Utama']);
        $otherUser = User::factory()->create(['name' => 'User Kedua', 'email' => 'kedua@tvri.go.id']);

        $response = $this->actingAs($admin)->get('/admin/users');

        $response->assertOk();
        $response->assertSee('Kelola Pengguna');
        $response->assertSee('Admin Utama');
        $response->assertSee('User Kedua');
        $response->assertSee('kedua@tvri.go.id');
    }

    public function test_admin_can_create_new_admin_user(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post('/admin/users', [
            'name' => 'Admin Baru TVRI',
            'email' => 'adminbaru@tvri.go.id',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect('/admin/users');
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('users', [
            'name' => 'Admin Baru TVRI',
            'email' => 'adminbaru@tvri.go.id',
            'role' => 'admin',
        ]);

        $createdUser = User::where('email', 'adminbaru@tvri.go.id')->first();
        $this->assertNotNull($createdUser);
        $this->assertNotNull($createdUser->email_verified_at);
        $this->assertTrue(Hash::check('password123', $createdUser->password));
    }

    public function test_create_user_validation_fails_on_invalid_data(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post('/admin/users', [
            'name' => '',
            'email' => 'invalid-email',
            'password' => 'short',
            'password_confirmation' => 'different',
        ]);

        $response->assertSessionHasErrors(['name', 'email', 'password']);
    }

    public function test_create_user_fails_on_duplicate_email(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'email' => 'existing@tvri.go.id']);

        $response = $this->actingAs($admin)->post('/admin/users', [
            'name' => 'Duplikat User',
            'email' => 'existing@tvri.go.id',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    public function test_admin_can_delete_other_user(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $userToDelete = User::factory()->create(['email' => 'hapus@tvri.go.id']);

        $response = $this->actingAs($admin)->delete("/admin/users/{$userToDelete->id}");

        $response->assertRedirect('/admin/users');
        $response->assertSessionHas('status');
        $this->assertDatabaseMissing('users', ['id' => $userToDelete->id]);
    }

    public function test_admin_cannot_delete_themselves(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->delete("/admin/users/{$admin->id}");

        $response->assertRedirect('/admin/users');
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_navigation_menu_shows_kelola_pengguna_only_for_admin(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $staf = User::factory()->create(['role' => 'staf']);

        $responseAdmin = $this->actingAs($admin)->get('/dashboard');
        $responseAdmin->assertSee('Kelola Pengguna');

        $responseStaf = $this->actingAs($staf)->get('/dashboard');
        $responseStaf->assertDontSee('Kelola Pengguna');
    }
}
