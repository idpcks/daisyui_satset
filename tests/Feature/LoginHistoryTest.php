<?php

namespace Tests\Feature;

use App\Models\LoginHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class LoginHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_successful_login_is_recorded_with_request_metadata(): void
    {
        $user = User::factory()->create();

        $this->withHeader('User-Agent', 'Mozilla/5.0 (Windows NT 10.0) Chrome/120.0.0.0 Safari/537.36')
            ->post('/auth/signin', [
                'email' => $user->email,
                'password' => 'password',
            ])
            ->assertRedirect('/app');

        $this->assertDatabaseCount('login_histories', 1);
        $this->assertDatabaseHas('login_histories', [
            'user_id' => $user->id,
            'email' => $user->email,
            'successful' => true,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0) Chrome/120.0.0.0 Safari/537.36',
        ]);
    }

    public function test_failed_login_is_recorded_without_storing_the_password(): void
    {
        $user = User::factory()->create();

        $this->from('/auth/signin')
            ->post('/auth/signin', [
                'email' => Str::upper($user->email),
                'password' => 'password-salah',
            ])
            ->assertRedirect('/auth/signin')
            ->assertSessionHasErrors('email');

        $entry = LoginHistory::sole();

        $this->assertDatabaseCount('login_histories', 1);
        $this->assertNull($entry->user_id);
        $this->assertSame($user->email, $entry->email);
        $this->assertFalse($entry->successful);
        $this->assertStringNotContainsString('password-salah', (string) $entry->user_agent);
    }

    public function test_login_history_only_contains_the_current_users_attempts(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $successful = LoginHistory::factory()->create([
            'user_id' => $user->id,
            'email' => $user->email,
            'ip_address' => '203.0.113.10',
            'created_at' => now()->subMinute(),
        ]);

        $failed = LoginHistory::factory()->failed()->create([
            'email' => $user->email,
            'ip_address' => '203.0.113.11',
            'created_at' => now(),
        ]);

        LoginHistory::factory()->create([
            'user_id' => $otherUser->id,
            'email' => $otherUser->email,
        ]);

        $this->actingAs($user)
            ->get('/settings?tab=riwayat')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('settings/Index')
                ->where('tab', 'riwayat')
                ->has('loginHistory', 2)
                ->where('loginHistory.0.id', $failed->id)
                ->where('loginHistory.0.successful', false)
                ->where('loginHistory.0.ip_address', '203.0.113.11')
                ->where('loginHistory.1.id', $successful->id)
                ->where('loginHistory.1.successful', true)
                ->where('loginHistory.1.ip_address', '203.0.113.10'));
    }
}
