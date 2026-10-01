<?php

namespace Tests\Unit;

use App\Services\CloudflareTurnstileClient;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CloudflareTurnstileClientTest extends TestCase
{
    public function test_successful_siteverify_response_is_accepted(): void
    {
        Http::fake([
            'https://challenges.cloudflare.com/turnstile/v0/siteverify' => Http::response([
                'success' => true,
                'error-codes' => [],
            ]),
        ]);

        $result = (new CloudflareTurnstileClient('test-secret'))
            ->siteverify('valid-token');

        $this->assertTrue($result->success);
        $this->assertSame([], $result->errorCodes);
    }

    public function test_failed_siteverify_response_returns_cloudflare_errors(): void
    {
        Http::fake([
            'https://challenges.cloudflare.com/turnstile/v0/siteverify' => Http::response([
                'success' => false,
                'error-codes' => ['invalid-input-response'],
            ]),
        ]);

        $result = (new CloudflareTurnstileClient('test-secret'))
            ->siteverify('invalid-token');

        $this->assertFalse($result->success);
        $this->assertSame(['invalid-input-response'], $result->errorCodes);
    }

    public function test_siteverify_http_failure_fails_closed(): void
    {
        Http::fake([
            'https://challenges.cloudflare.com/turnstile/v0/siteverify' => Http::response([], 503),
        ]);

        $result = (new CloudflareTurnstileClient('test-secret'))
            ->siteverify('token');

        $this->assertFalse($result->success);
        $this->assertSame(['internal-error'], $result->errorCodes);
    }
}
