<?php

namespace Tests\Feature;

use Tests\TestCase;

class ProfileTest extends TestCase
{
    public function test_default_breeze_profile_routes_are_not_exposed(): void
    {
        $this->get('/profile')->assertNotFound();
        $this->patch('/profile')->assertNotFound();
        $this->delete('/profile')->assertNotFound();
    }
}
