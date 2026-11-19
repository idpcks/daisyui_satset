<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class LocaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_supported_locale_is_stored_in_the_session(): void
    {
        $this->post('/locale', ['locale' => 'en'])
            ->assertRedirect()
            ->assertSessionHas('locale', 'en');
    }

    public function test_an_unsupported_locale_is_rejected(): void
    {
        $this->post('/locale', ['locale' => 'fr'])
            ->assertSessionHasErrors('locale')
            ->assertSessionMissing('locale');
    }

    public function test_the_system_locale_is_used_when_the_session_has_no_locale(): void
    {
        Setting::put(['app.locale' => 'en']);

        $this->get('/auth/signin')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('locale', 'en')
                ->where('translations.Simpan', 'Save'));
    }

    public function test_the_session_locale_overrides_the_system_locale(): void
    {
        Setting::put(['app.locale' => 'en']);

        $this->withSession(['locale' => 'id'])
            ->get('/auth/signin')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('locale', 'id')
                ->where('translations.Simpan', 'Simpan'));
    }
}
