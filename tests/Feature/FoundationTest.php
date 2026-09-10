<?php

namespace Tests\Feature;

use Tests\TestCase;

class FoundationTest extends TestCase
{
    public function test_health_endpoint_reports_ok(): void
    {
        $this->get(route('health'))->assertOk()->assertExactJson(['status' => 'ok']);
    }

    public function test_public_and_login_pages_render(): void
    {
        $this->get(route('home'))->assertSee('What help do you need?');
        $this->get(route('login'))->assertSee('Sign in to Oncall');
    }
}
