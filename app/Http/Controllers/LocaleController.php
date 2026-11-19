<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LocaleController extends Controller
{
    /**
     * Ubah bahasa aktif pengguna dalam sesi.
     */
    public function update(Request $request): RedirectResponse
    {
        $allowed = array_keys((array) config('system.locales'));

        $validated = $request->validate([
            'locale' => ['required', 'string', Rule::in($allowed)],
        ]);

        $request->session()->put('locale', $validated['locale']);

        return back();
    }
}
