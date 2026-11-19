<?php

namespace Tests\Feature;

use App\Features\MagicLinkLogin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class MagicLinkFeatureTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Magic link mati secara default: tujuh rutenya publik dan tidak ada
     * halaman yang menautkannya, sementara akun nonaktif tidak diperiksa
     * (eligibility null). Menyalakannya harus keputusan sadar.
     *
     * Path ditulis literal karena saat fitur mati, rutenya tidak didaftarkan
     * sama sekali sehingga route() tidak bisa dipakai.
     */
    public function test_magic_link_routes_are_unavailable_by_default(): void
    {
        $this->get('/magic-link')->assertNotFound();
        $this->post('/magic-link')->assertNotFound();
        $this->get('/magic-link/code')->assertNotFound();
    }

    public function test_magic_link_routes_are_not_registered_at_all(): void
    {
        $this->assertFalse(Route::has('email-magic-link.request.form'));
        $this->assertFalse(Route::has('email-magic-link.request'));
        $this->assertFalse(Route::has('email-magic-link.confirm'));
        $this->assertFalse(Route::has('email-magic-link.consume'));
    }

    /**
     * Saklar tetap berfungsi: mengaktifkan flag menghidupkan kembali rute
     * yang sudah terdaftar. Ini jalur runtime, terpisah dari config paket.
     */
    public function test_feature_defaults_to_the_package_configuration(): void
    {
        $feature = new MagicLinkLogin;

        $this->assertFalse($feature->resolve(null));

        config(['email-magic-link.enabled' => true]);

        $this->assertTrue($feature->resolve(null));
    }
}
