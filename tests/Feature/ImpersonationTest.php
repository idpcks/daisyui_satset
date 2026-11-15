<?php

namespace Tests\Feature;

use App\Http\Controllers\ImpersonationController;
use App\Models\Activity;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ImpersonationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('superadmin', 'web');
        Role::findOrCreate('admin', 'web');
        Role::findOrCreate('operator', 'web');

        Role::findByName('admin', 'web')
            ->givePermissionTo(Permission::findOrCreate('user.manage', 'web'));
    }

    public function test_guests_cannot_start_impersonation(): void
    {
        $target = User::factory()->create();

        $this->post("/users/{$target->id}/impersonate")->assertRedirect('/login');
    }

    public function test_admin_with_user_manage_cannot_start_impersonation(): void
    {
        $actor = $this->admin();
        $target = User::factory()->create();

        $this->actingAs($actor)
            ->post("/users/{$target->id}/impersonate")
            ->assertForbidden();

        $this->assertAuthenticatedAs($actor);
        $this->assertDatabaseMissing('activity_log', ['event' => 'impersonation_started']);
    }

    public function test_superadmin_can_impersonate_an_active_user_and_audit_it(): void
    {
        $actor = $this->superadmin();
        $target = User::factory()->create(['name' => 'Operator Satu', 'username' => 'operator1']);
        $target->assignRole('operator');

        $this->actingAs($actor)
            ->post("/users/{$target->id}/impersonate")
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('success')
            ->assertSessionHas(ImpersonationController::SESSION_IMPERSONATOR_ID, $actor->id);

        $this->assertAuthenticatedAs($target);

        $activity = Activity::query()
            ->where('event', 'impersonation_started')
            ->firstOrFail();

        $this->assertSame('impersonation', $activity->log_name);
        $this->assertSame($actor->id, $activity->causer_id);
        $this->assertSame($target->id, $activity->subject_id);
        $this->assertSame($actor->username, $activity->properties->get('impersonator_username'));
        $this->assertSame($target->username, $activity->properties->get('impersonated_username'));
    }

    public function test_superadmin_cannot_impersonate_self_another_superadmin_or_inactive_user(): void
    {
        $actor = $this->superadmin();

        $otherSuperadmin = User::factory()->create();
        $otherSuperadmin->assignRole('superadmin');

        $inactive = User::factory()->create(['is_active' => false]);
        $inactive->assignRole('operator');

        $this->actingAs($actor)
            ->post("/users/{$actor->id}/impersonate")
            ->assertSessionHas('error');

        $this->actingAs($actor)
            ->post("/users/{$otherSuperadmin->id}/impersonate")
            ->assertSessionHas('error');

        $this->actingAs($actor)
            ->post("/users/{$inactive->id}/impersonate")
            ->assertSessionHas('error');

        $this->assertAuthenticatedAs($actor);
        $this->assertDatabaseMissing('activity_log', ['event' => 'impersonation_started']);
    }

    public function test_impersonation_can_be_stopped_and_returns_to_the_original_superadmin(): void
    {
        $actor = $this->superadmin();
        $target = User::factory()->create(['name' => 'Operator Satu']);
        $target->assignRole('operator');

        $this->actingAs($actor)
            ->post("/users/{$target->id}/impersonate")
            ->assertRedirect(route('dashboard'));

        $this->delete('/impersonation')
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('success')
            ->assertSessionMissing(ImpersonationController::SESSION_IMPERSONATOR_ID)
            ->assertSessionMissing(ImpersonationController::SESSION_STARTED_AT);

        $this->assertAuthenticatedAs($actor);

        $activity = Activity::query()
            ->where('event', 'impersonation_stopped')
            ->firstOrFail();

        $this->assertSame($actor->id, $activity->causer_id);
        $this->assertSame($target->id, $activity->subject_id);
        $this->assertNotNull($activity->properties->get('duration_seconds'));
        $this->assertNotNull($activity->properties->get('stopped_at'));
    }

    public function test_stopping_without_an_active_impersonation_session_is_rejected(): void
    {
        $actor = $this->superadmin();

        $this->actingAs($actor)
            ->delete('/impersonation')
            ->assertSessionHas('error');

        $this->assertAuthenticatedAs($actor);
    }

    public function test_only_superadmin_can_view_the_audit_log(): void
    {
        $actor = $this->superadmin();
        $target = User::factory()->create(['name' => 'Operator Satu', 'username' => 'operator1']);
        $target->assignRole('operator');

        activity('impersonation')
            ->event('impersonation_started')
            ->causedBy($actor)
            ->performedOn($target)
            ->withProperties([
                'impersonator_name' => $actor->name,
                'impersonator_username' => $actor->username,
                'impersonated_name' => $target->name,
                'impersonated_username' => $target->username,
                'ip_address' => '127.0.0.1',
            ])
            ->log('Mulai impersonation.');

        $this->actingAs($actor)
            ->get('/audit')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('audit/Index')
                ->has('logs.data', 1)
                ->where('logs.data.0.event', 'impersonation_started')
                ->where('logs.data.0.impersonator_username', $actor->username)
                ->where('logs.data.0.impersonated_username', $target->username)
            );

        $this->actingAs($this->admin())
            ->get('/audit')
            ->assertForbidden();
    }

    private function superadmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('superadmin');

        return $user;
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        return $user;
    }
}
