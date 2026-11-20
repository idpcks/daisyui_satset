<?php

namespace App\Models;

use Database\Factories\UserSessionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'session_id',
    'user_id',
    'ip_address',
    'user_agent',
    'last_activity',
    'created_by',
    'updated_by',
    'deleted_by',
])]
class UserSession extends Model
{
    /** @use HasFactory<UserSessionFactory> */
    use HasFactory, HasUuids;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_activity' => 'datetime',
        ];
    }

    /**
     * Pemilik sesi.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Batasi query ke sesi yang belum melewati masa berlaku session.
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('last_activity', '>=', now()->subMinutes(
            (int) config('session.lifetime')
        ));
    }

    /**
     * Batasi query ke sesi yang sudah melewati masa berlaku session.
     */
    public function scopeExpired(Builder $query): void
    {
        $query->where('last_activity', '<', now()->subMinutes(
            (int) config('session.lifetime')
        ));
    }
}
