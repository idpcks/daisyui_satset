<?php

namespace App\Features;

use Laravel\Fortify\Features;
use Laravel\Pennant\Attributes\Name;

/**
 * Ketersediaan passkey (login maupun pengelolaan dari halaman Pengaturan).
 *
 * Config Fortify tetap menjadi saklar utama: mematikan fitur di sana juga
 * menghapus rutenya. Flag ini memberi saklar runtime tanpa deploy ulang.
 */
#[Name('passkeys-login')]
class Passkeys
{
    /**
     * Resolve the feature's initial value.
     */
    public function resolve(mixed $scope): mixed
    {
        return Features::enabled(Features::passkeys());
    }
}
