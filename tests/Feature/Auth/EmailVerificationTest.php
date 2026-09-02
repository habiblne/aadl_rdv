<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    public function test_default_breeze_email_verification_routes_are_not_exposed(): void
    {
        $this->get('/verify-email')->assertNotFound();
        $this->post('/email/verification-notification')->assertNotFound();
    }
}
