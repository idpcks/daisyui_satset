<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class UserSessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_settings_page_lists_only_active_sessions_owned_by_the_user(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $activeSession = UserSession::factory()->for($user)->create([
            'ip_address' => '203.0.113.10',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0) Chrome/120.0.0.0 Safari/537.36',
            'last_activity' => now()->subMinutes(5),
        ]);

        UserSession::factory()->for($user)->create([
            'last_activity' => now()->subMinutes((int) config('session.lifetime') + 1),
        ]);

        UserSession::factory()->for($otherUser)->create();

        $this->actingAs($user)
            ->get('/settings?tab=keamanan')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('settings/Index')
                ->has('sessions', 2)
                ->where('sessions.0.is_current', true)
                ->where('sessions.1.id', $activeSession->id)
                ->where('sessions.1.ip_address', '203.0.113.10')
                ->where('sessions.1.is_current', false));
    }

    public function test_user_can_log_out_a_specific_session(): void
    {
        $user = User::factory()->create();
        $otherSession = UserSession::factory()->for($user)->create();

        $this->actingAs($user)
            ->from('/settings?tab=keamanan')
            ->delete("/settings/security/sessions/{$otherSession->id}", [
                'password' => 'password',
            ])
            ->assertRedirect('/settings?tab=keamanan')
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('user_sessions', ['id' => $otherSession->id]);
        $this->assertDatabaseHas('user_sessions', ['user_id' => $user->id]);
    }

    public function test_logging_out_a_specific_session_requires_the_current_password(): void
    {
        $user = User::factory()->create();
        $otherSession = UserSession::factory()->for($user)->create();

        $this->actingAs($user)
            ->from('/settings?tab=keamanan')
            ->delete("/settings/security/sessions/{$otherSession->id}", [
                'password' => 'salah',
            ])
            ->assertRedirect('/settings?tab=keamanan')
            ->assertSessionHasErrors('password');

        $this->assertDatabaseHas('user_sessions', ['id' => $otherSession->id]);
    }

    public function test_user_cannot_log_out_another_users_session(): void
    {
        $user = User::factory()->create();
        $otherSession = UserSession::factory()->for(User::factory())->create();

        $this->actingAs($user)
            ->delete("/settings/security/sessions/{$otherSession->id}", [
                'password' => 'password',
            ])
            ->assertNotFound();

        $this->assertDatabaseHas('user_sessions', ['id' => $otherSession->id]);
    }

    public function test_user_cannot_log_out_the_current_session(): void
    {
        $user = User::factory()->create();
        $sessionId = Str::random(40);

        app('session')->driver()->getHandler()->write($sessionId, '');

        $currentSession = UserSession::factory()->for($user)->create([
            'session_id' => $sessionId,
        ]);

        $this->actingAs($user)
            ->withCookie((string) config('session.cookie'), $sessionId)
            ->from('/settings?tab=keamanan')
            ->delete("/settings/security/sessions/{$currentSession->id}", [
                'password' => 'password',
            ])
            ->assertRedirect('/settings?tab=keamanan')
            ->assertSessionHas('error');

        $this->assertDatabaseHas('user_sessions', ['id' => $currentSession->id]);
    }

    public function test_regenerating_the_session_removes_the_previous_registry_row(): void
    {
        Route::middleware('web')->get('/_test/regenerate-session', function (Request $request) {
            $request->session()->regenerate();

            return response('ok');
        });

        $user = User::factory()->create();
        $sessionId = Str::random(40);

        app('session')->driver()->getHandler()->write($sessionId, '');

        $previousSession = UserSession::factory()->for($user)->create([
            'session_id' => $sessionId,
        ]);

        $this->actingAs($user)
            ->withCookie((string) config('session.cookie'), $sessionId)
            ->get('/_test/regenerate-session')
            ->assertOk();

        $this->assertDatabaseMissing('user_sessions', ['id' => $previousSession->id]);
    }

    public function test_logging_out_removes_the_current_device_from_the_registry(): void
    {
        $user = User::factory()->create();
        $sessionId = Str::random(40);

        app('session')->driver()->getHandler()->write($sessionId, '');

        $currentSession = UserSession::factory()->for($user)->create([
            'session_id' => $sessionId,
        ]);

        $this->actingAs($user)
            ->withCookie((string) config('session.cookie'), $sessionId)
            ->post('/logout')
            ->assertRedirect('/');

        $this->assertDatabaseMissing('user_sessions', ['id' => $currentSession->id]);
    }

    public function test_logging_out_other_sessions_removes_their_registry_rows(): void
    {
        $user = User::factory()->create();
        $otherSession = UserSession::factory()->for($user)->create();

        $this->actingAs($user)
            ->delete('/settings/security/other-sessions', [
                'password' => 'password',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('user_sessions', ['id' => $otherSession->id]);
        $this->assertDatabaseHas('user_sessions', ['user_id' => $user->id]);
    }
}
