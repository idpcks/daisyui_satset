<?php
namespace Tests\Feature;
use Tests\TestCase;
class MagicLinkRedirectTest extends TestCase {
    public function test_magic_link_request_redirects_to_login() {
        $this->get('/magic-link')->assertRedirect(route('login'));
    }
}
