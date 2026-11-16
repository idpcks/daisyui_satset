<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserSession;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserSessionPruneTest extends TestCase
{
    use RefreshDatabase;

    public function test_prune_command_removes_only_expired_sessions(): void
    {
        $user = User::factory()->create();

        $expiredSession = UserSession::factory()->for($user)->create([
            'last_activity' => now()->subMinutes((int) config('session.lifetime') + 1),
        ]);

        $activeSession = UserSession::factory()->for($user)->create([
            'last_activity' => now()->subMinutes(5),
        ]);

        $this->artisan('user-sessions:prune')
            ->expectsOutput('1 baris sesi kedaluwarsa dihapus.')
            ->assertSuccessful();

        $this->assertDatabaseMissing('user_sessions', ['id' => $expiredSession->id]);
        $this->assertDatabaseHas('user_sessions', ['id' => $activeSession->id]);
    }

    public function test_prune_command_is_scheduled_hourly(): void
    {
        // routes/console.php baru dimuat saat kernel console di-bootstrap.
        $this->app->make(Kernel::class)->bootstrap();

        $events = collect($this->app->make(Schedule::class)->events())
            ->filter(fn (Event $event): bool => str_contains((string) $event->command, 'user-sessions:prune'));

        $this->assertCount(1, $events);
        $this->assertSame('0 * * * *', $events->first()->expression);
    }
}
