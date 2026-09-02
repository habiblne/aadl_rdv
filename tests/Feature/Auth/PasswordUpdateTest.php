<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;

class PasswordUpdateTest extends TestCase
{
    public function test_default_breeze_password_update_route_is_not_exposed(): void
    {
        $this->put('/password')->assertNotFound();
    }
}
