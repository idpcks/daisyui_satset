<?php

namespace Tests\Feature;

use App\Models\Passkey;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PasskeyManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_settings_page_lists_only_the_users_passkeys_without_credentials(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $passkey = $this->passkeyFor($user, [
            'name' => 'Laptop Kantor',
            'credential_id' => 'credential-own',
        ]);

        $this->passkeyFor($otherUser, [
            'name' => 'Perangkat Lain',
            'credential_id' => 'credential-other',
        ]);

        $this->actingAs($user)
            ->get('/settings?tab=keamanan')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('settings/Index')
                ->has('passkeys', 1)
                ->where('passkeys.0.id', $passkey->id)
                ->where('passkeys.0.name', 'Laptop Kantor')
                ->where('passkeys.0.authenticator', null)
                ->missing('passkeys.0.credential')
                ->missing('passkeys.0.credential_id'));
    }

    public function test_user_can_delete_their_own_passkey_after_confirming_the_password(): void
    {
        $user = User::factory()->create();
        $passkey = $this->passkeyFor($user);

        $this->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->deleteJson("/user/passkeys/{$passkey->id}")
            ->assertOk()
            ->assertJson(['status' => 'passkey-deleted']);

        $this->assertDatabaseMissing('passkeys', ['id' => $passkey->id]);
    }

    public function test_passkey_management_requires_a_confirmed_password(): void
    {
        $user = User::factory()->create();
        $passkey = $this->passkeyFor($user);

        $this->actingAs($user)
            ->deleteJson("/user/passkeys/{$passkey->id}")
            ->assertStatus(423);

        $this->assertDatabaseHas('passkeys', ['id' => $passkey->id]);
    }

    public function test_user_cannot_delete_another_users_passkey(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $passkey = $this->passkeyFor($otherUser);

        $this->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->deleteJson("/user/passkeys/{$passkey->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('passkeys', ['id' => $passkey->id]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function passkeyFor(User $user, array $attributes = []): Passkey
    {
        $passkey = new Passkey([
            'name' => 'Passkey',
            'credential_id' => 'credential-'.$user->id,
            'credential' => [],
            ...$attributes,
        ]);

        $passkey->user_id = $user->getKey();
        $passkey->save();

        return $passkey;
    }
}
