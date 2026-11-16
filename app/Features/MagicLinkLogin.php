<?php

namespace App\Features;

use Laravel\Pennant\Attributes\Name;

/**
 * Ketersediaan login lewat tautan email (email-magic-link).
 *
 * Config paket tetap menjadi saklar utama: bila dinonaktifkan, rutenya tidak
 * didaftarkan sama sekali. Flag ini memungkinkan penonaktifan runtime atas
 * rute yang sudah terdaftar tanpa mengubah config atau melakukan deploy ulang.
 */
#[Name('magic-link-login')]
class MagicLinkLogin
{
    /**
     * Resolve the feature's initial value.
     */
    public function resolve(mixed $scope): mixed
    {
        return (bool) config('email-magic-link.enabled');
    }
}
