<?php

namespace Tests\Feature;

use App\Providers\AppServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PathPrefixTest extends TestCase
{
    use RefreshDatabase;

    public function test_base_path_is_empty_when_app_url_has_no_path(): void
    {
        $this->get('/auth/signin')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('basePath', ''));
    }

    public function test_base_path_is_shared_with_the_frontend(): void
    {
        config(['app.base_path' => '/admin']);

        $this->get('/auth/signin')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('basePath', '/admin'));
    }

    public function test_generated_urls_follow_the_base_path_of_app_url(): void
    {
        config([
            'app.url' => 'https://app.example.com/admin',
            'app.base_path' => '/admin',
        ]);

        try {
            (new AppServiceProvider($this->app))->boot();

            $this->assertSame(
                'https://app.example.com/admin/dashboard',
                route('dashboard'),
            );

            $this->assertSame(
                'https://app.example.com/admin/build/app.js',
                asset('build/app.js'),
            );
        } finally {
            // Force root/scheme bersifat statis; kembalikan agar tidak bocor
            // ke test lain dalam proses yang sama.
            URL::forceRootUrl(null);
            URL::forceScheme(null);
        }
    }
}
