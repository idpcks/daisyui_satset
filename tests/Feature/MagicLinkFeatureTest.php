<?php

namespace Tests\Feature;

use App\Features\MagicLinkLogin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Pennant\Feature;
use Tests\TestCase;

class MagicLinkFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_magic_link_routes_are_available_by_default(): void
    {
        $this->get(route('email-magic-link.request.form'))->assertOk();
    }

    public function test_magic_link_routes_are_unavailable_when_the_feature_is_disabled(): void
    {
        Feature::deactivate(MagicLinkLogin::class);
        Feature::flushCache();

        $this->get(route('email-magic-link.request.form'))->assertNotFound();
        $this->post(route('email-magic-link.request'))->assertNotFound();
        $this->get(route('email-magic-link.code.form'))->assertNotFound();
    }

    public function test_feature_defaults_to_the_package_configuration(): void
    {
        $feature = new MagicLinkLogin;

        $this->assertTrue($feature->resolve(null));

        config(['email-magic-link.enabled' => false]);

        $this->assertFalse($feature->resolve(null));
    }
}
