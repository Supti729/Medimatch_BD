<?php

namespace Tests\Feature;

use Tests\TestCase;

class TrustedProxyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function forwarded(string $from)
    {
        return $this->withServerVariables(['REMOTE_ADDR' => $from])
            ->withHeaders(['X-Forwarded-Proto' => 'https', 'X-Forwarded-For' => '203.0.113.9'])
            ->get('/login');
    }

    public function test_a_tunnel_on_this_machine_makes_links_use_https(): void
    {
        $this->forwarded('127.0.0.1')->assertOk()->assertSee('action="https://', false);
        $this->assertSame('203.0.113.9', request()->ip());
    }

    public function test_forwarded_headers_from_anyone_else_are_ignored(): void
    {
        $this->forwarded('198.51.100.7')->assertOk()->assertSee('action="http://', false);
        $this->assertSame('198.51.100.7', request()->ip());
    }
}
