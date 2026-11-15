<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Laravel\Passkeys\Passkey as PasskeysPasskey;

class Passkey extends PasskeysPasskey
{
    use HasUuids;
}
