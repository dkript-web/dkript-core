<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Profile;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserProfileTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $otherUser;
    protected Role $role;

    protected function setUp(): void
    {
        parent::setUp();

        $this->role = Role::create([
            'id' => 1,
            'name' => 'Super Administrador',
            'description' => 'Acceso Total',
            'status' => 1,
        ]);

        $this->user = User::create([
            'name' => 'alchemist',
            'email' => 'admin@dkript.com',
            'password' => Hash::make('SecretPass123!'),
            'status' => 1,
        ]);

        Profile::create([
            'user_id' => $this->user->id,
            'role_id' => $this->role->id,
            'first_name' => 'Alchemist',
            'last_name' => 'Admin',
            'phone' => '+525512345678',
        ]);

        $this->otherUser = User::create([
            'name' => 'operator',
            'email' => 'operator@dkript.com',
            'password' => Hash::make('SecretPass123!'),
            'status' => 1,
        ]);

        Profile::create([
            'user_id' => $this->otherUser->id,
            'role_id' => $this->role->id,
            'first_name' => 'Operator',
            'last_name' => 'User',
            'phone' => '+525587654321',
        ]);
    }

    public function test_guest_cannot_update_profile(): void
    {
        $response = $this->putJson(route('user.profile.update'), [
            'name' => 'newname',
            'first_name' => 'Nuevo',
            'last_name' => 'Nombre',
            'email' => 'nuevo@dkript.com',
            'current_password' => 'SecretPass123!',
        ]);

        $response->assertStatus(401);
    }

    public function test_user_can_update_profile_with_valid_current_password(): void
    {
        $response = $this->actingAs($this->user)->putJson(route('user.profile.update'), [
            'name' => 'alchemist_updated',
            'first_name' => 'Hermes',
            'last_name' => 'Trismegisto',
            'email' => 'hermes@dkript.com',
            'phone' => '+525599887766',
            'current_password' => 'SecretPass123!',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'user' => [
                    'name' => 'alchemist_updated',
                    'email' => 'hermes@dkript.com',
                    'first_name' => 'Hermes',
                    'last_name' => 'Trismegisto',
                    'phone' => '+525599887766',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'name' => 'alchemist_updated',
            'email' => 'hermes@dkript.com',
        ]);

        $this->assertDatabaseHas('profiles', [
            'user_id' => $this->user->id,
            'first_name' => 'Hermes',
            'last_name' => 'Trismegisto',
            'phone' => '+525599887766',
        ]);
    }

    public function test_profile_update_fails_if_current_password_is_incorrect(): void
    {
        $response = $this->actingAs($this->user)->putJson(route('user.profile.update'), [
            'name' => 'hacked_name',
            'first_name' => 'Hacked',
            'last_name' => 'User',
            'email' => 'hacked@dkript.com',
            'current_password' => 'WrongPassword999',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ])
            ->assertJsonValidationErrors(['current_password']);

        // Assert database values remain unchanged
        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'name' => 'alchemist',
            'email' => 'admin@dkript.com',
        ]);

        $this->assertDatabaseHas('profiles', [
            'user_id' => $this->user->id,
            'first_name' => 'Alchemist',
            'last_name' => 'Admin',
        ]);
    }

    public function test_profile_update_fails_if_current_password_is_missing(): void
    {
        $response = $this->actingAs($this->user)->putJson(route('user.profile.update'), [
            'name' => 'no_pass_name',
            'first_name' => 'No',
            'last_name' => 'Pass',
            'email' => 'nopass@dkript.com',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['current_password']);
    }

    public function test_user_can_update_password_from_profile_with_valid_current_password(): void
    {
        $response = $this->actingAs($this->user)->putJson(route('user.profile.update'), [
            'name' => 'alchemist',
            'first_name' => 'Alchemist',
            'last_name' => 'Admin',
            'email' => 'admin@dkript.com',
            'phone' => '+525512345678',
            'current_password' => 'SecretPass123!',
            'new_password' => 'BrandNewPass2026!',
            'new_password_confirmation' => 'BrandNewPass2026!',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->user->refresh();
        $this->assertTrue(Hash::check('BrandNewPass2026!', $this->user->password));
        $this->assertFalse(Hash::check('SecretPass123!', $this->user->password));
    }

    public function test_profile_update_fails_if_new_password_confirmation_mismatches(): void
    {
        $response = $this->actingAs($this->user)->putJson(route('user.profile.update'), [
            'name' => 'alchemist',
            'first_name' => 'Alchemist',
            'last_name' => 'Admin',
            'email' => 'admin@dkript.com',
            'current_password' => 'SecretPass123!',
            'new_password' => 'BrandNewPass2026!',
            'new_password_confirmation' => 'MismatchPass999!',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['new_password']);
    }

    public function test_profile_update_fails_if_new_password_lacks_symbol(): void
    {
        $response = $this->actingAs($this->user)->putJson(route('user.profile.update'), [
            'name' => 'alchemist',
            'first_name' => 'Alchemist',
            'last_name' => 'Admin',
            'email' => 'admin@dkript.com',
            'current_password' => 'SecretPass123!',
            'new_password' => 'BrandNewPassword2026',
            'new_password_confirmation' => 'BrandNewPassword2026',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['new_password']);
    }

    public function test_profile_update_fails_if_new_password_lacks_uppercase(): void
    {
        $response = $this->actingAs($this->user)->putJson(route('user.profile.update'), [
            'name' => 'alchemist',
            'first_name' => 'Alchemist',
            'last_name' => 'Admin',
            'email' => 'admin@dkript.com',
            'current_password' => 'SecretPass123!',
            'new_password' => 'brandnewpass2026!',
            'new_password_confirmation' => 'brandnewpass2026!',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['new_password']);
    }

    public function test_profile_update_fails_if_new_password_lacks_lowercase(): void
    {
        $response = $this->actingAs($this->user)->putJson(route('user.profile.update'), [
            'name' => 'alchemist',
            'first_name' => 'Alchemist',
            'last_name' => 'Admin',
            'email' => 'admin@dkript.com',
            'current_password' => 'SecretPass123!',
            'new_password' => 'BRANDNEWPASS2026!',
            'new_password_confirmation' => 'BRANDNEWPASS2026!',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['new_password']);
    }

    public function test_profile_update_fails_if_new_password_lacks_number(): void
    {
        $response = $this->actingAs($this->user)->putJson(route('user.profile.update'), [
            'name' => 'alchemist',
            'first_name' => 'Alchemist',
            'last_name' => 'Admin',
            'email' => 'admin@dkript.com',
            'current_password' => 'SecretPass123!',
            'new_password' => 'BrandNewPassword!',
            'new_password_confirmation' => 'BrandNewPassword!',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['new_password']);
    }

    public function test_profile_update_fails_if_new_password_is_too_short(): void
    {
        $response = $this->actingAs($this->user)->putJson(route('user.profile.update'), [
            'name' => 'alchemist',
            'first_name' => 'Alchemist',
            'last_name' => 'Admin',
            'email' => 'admin@dkript.com',
            'current_password' => 'SecretPass123!',
            'new_password' => 'Pass1!',
            'new_password_confirmation' => 'Pass1!',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['new_password']);
    }

    public function test_profile_email_must_be_unique_except_for_current_user(): void
    {
        // Try using otherUser's email -> Should fail
        $response = $this->actingAs($this->user)->putJson(route('user.profile.update'), [
            'name' => 'alchemist',
            'first_name' => 'Alchemist',
            'last_name' => 'Admin',
            'email' => 'operator@dkript.com',
            'current_password' => 'SecretPass123!',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);

        // Keeping own email -> Should succeed
        $responseSelf = $this->actingAs($this->user)->putJson(route('user.profile.update'), [
            'name' => 'alchemist_same_email',
            'first_name' => 'Alchemist',
            'last_name' => 'Admin',
            'email' => 'admin@dkript.com',
            'current_password' => 'SecretPass123!',
        ]);

        $responseSelf->assertStatus(200);
    }

    public function test_profile_update_generates_audit_log(): void
    {
        $this->actingAs($this->user)->putJson(route('user.profile.update'), [
            'name' => 'audited_user',
            'first_name' => 'Audited',
            'last_name' => 'Profile',
            'email' => 'audited@dkript.com',
            'current_password' => 'SecretPass123!',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->user->id,
            'action' => 'UPDATE',
            'module' => 'USERS',
        ]);

        $log = AuditLog::where('user_id', $this->user->id)
            ->where('action', 'UPDATE')
            ->where('module', 'USERS')
            ->latest('id')
            ->first();

        $this->assertNotNull($log);
        $this->assertStringContainsString('Actualización de datos de perfil', $log->description);
    }

    public function test_profile_modal_renders_reactive_current_password_container_and_correct_labels(): void
    {
        $response = $this->actingAs($this->user)->get(route('dashboard'));
        $response->assertStatus(200);

        // Container exists and is hidden by default
        $response->assertSee('id="containerCurrentPassword"', false);
        $response->assertSee('class="relative sm:col-span-2 hidden transition-all duration-300"', false);

        // Label updated to "Contraseña Actual"
        $response->assertSee('Contraseña Actual', false);

        // Placeholder updated to "Contraseña actual"
        $response->assertSee('placeholder="Contraseña actual"', false);

        // Key icon rendered
        $response->assertSee('bi-key', false);

        // Position: check order in HTML
        $content = $response->getContent();
        $posAccordion = strpos($content, 'id="sectionPasswordChange"');
        $posContainer = strpos($content, 'id="containerCurrentPassword"');
        $posMember = strpos($content, 'Miembro Desde');

        $this->assertNotFalse($posAccordion);
        $this->assertNotFalse($posContainer);
        $this->assertNotFalse($posMember);
        $this->assertTrue($posContainer > $posAccordion, 'containerCurrentPassword should be after sectionPasswordChange');
        $this->assertTrue($posContainer < $posMember, 'containerCurrentPassword should be before Miembro Desde');
    }

    protected function tearDown(): void
    {
        // Clean up test upload files for the test user
        if (isset($this->user)) {
            $testUserDir = public_path("uploads/users/user_{$this->user->id}_alchemist");
            if (File::isDirectory($testUserDir)) {
                File::deleteDirectory($testUserDir);
            }
        }

        parent::tearDown();
    }

    public function test_user_can_upload_avatar_without_current_password(): void
    {
        $file = UploadedFile::fake()->image('test_avatar.png', 200, 200);

        $response = $this->actingAs($this->user)->postJson(route('user.profile.avatar'), [
            'avatar' => $file,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Foto de perfil actualizada exitosamente.',
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'avatar_url',
                    'avatar_path',
                ],
            ]);

        $this->user->refresh();
        $this->assertNotNull($this->user->profile->avatar);
        $this->assertFileExists(public_path($this->user->profile->avatar));
        $this->assertStringContainsString("uploads/users/user_{$this->user->id}_", $this->user->profile->avatar);
        $this->assertStringContainsString("/avatar/avatar_", $this->user->profile->avatar);
    }

    public function test_avatar_upload_replaces_old_avatar_and_deletes_previous_file(): void
    {
        $file1 = UploadedFile::fake()->image('avatar1.png', 200, 200);
        $response1 = $this->actingAs($this->user)->postJson(route('user.profile.avatar'), [
            'avatar' => $file1,
        ]);
        $response1->assertStatus(200);

        $this->user->refresh();
        $oldPath = public_path($this->user->profile->avatar);
        $this->assertFileExists($oldPath);

        // Upload second avatar
        sleep(1);
        $file2 = UploadedFile::fake()->image('avatar2.jpg', 300, 300);
        $response2 = $this->actingAs($this->user)->postJson(route('user.profile.avatar'), [
            'avatar' => $file2,
        ]);
        $response2->assertStatus(200);

        $this->user->refresh();
        $newPath = public_path($this->user->profile->avatar);

        $this->assertFileDoesNotExist($oldPath);
        $this->assertFileExists($newPath);
        $this->assertNotEquals($oldPath, $newPath);
    }

    public function test_avatar_upload_fails_for_non_image_files(): void
    {
        $fakeDoc = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        $response = $this->actingAs($this->user)->postJson(route('user.profile.avatar'), [
            'avatar' => $fakeDoc,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['avatar']);
    }
}


