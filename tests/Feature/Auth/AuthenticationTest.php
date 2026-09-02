<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    public function test_default_breeze_login_routes_are_not_exposed(): void
    {
        $this->get('/login')->assertNotFound();
        $this->post('/login')->assertNotFound();
        $this->post('/logout')->assertNotFound();
    }
}
