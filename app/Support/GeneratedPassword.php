<?php

namespace App\Support;

use Illuminate\Support\Str;

class GeneratedPassword
{
    /**
     * Buat password acak yang memenuhi standar OWASP untuk password
     * yang dihasilkan sistem (panjang tinggi, entropi tinggi).
     */
    public static function make(int $length = 16): string
    {
        return Str::password($length);
    }
}
