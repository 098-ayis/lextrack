<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RyanChandler\LaravelCloudflareTurnstile\Contracts\ClientInterface;
use RyanChandler\LaravelCloudflareTurnstile\Responses\SiteverifyResponse;
use Throwable;

final class CloudflareTurnstileClient implements ClientInterface
{
    public function __construct(
        private readonly string $secret,
    ) {}

    public function siteverify(string $response): SiteverifyResponse
    {
        try {
            $verification = Http::retry(3, 100)
                ->asForm()
                ->acceptJson()
                ->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                    'secret' => $this->secret,
                    'response' => $response,
                ]);
        } catch (Throwable) {
            return SiteverifyResponse::failure(['internal-error']);
        }

        if (! $verification->successful()) {
            return SiteverifyResponse::failure(['internal-error']);
        }

        if ($verification->json('success') === true) {
            return SiteverifyResponse::success();
        }

        $errorCodes = $verification->json('error-codes', []);

        return SiteverifyResponse::failure(
            is_array($errorCodes) && $errorCodes !== []
                ? $errorCodes
                : ['internal-error'],
        );
    }

    public function dummy(): string
    {
        return self::RESPONSE_DUMMY_TOKEN;
    }
}
