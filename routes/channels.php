<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Otorisasi channel privat dan presence didaftarkan di sini. Channel publik
| tidak butuh entri.
|
*/

Broadcast::channel('App.Models.User.{id}', function (User $user, string $id): bool {
    return (string) $user->id === $id;
});
