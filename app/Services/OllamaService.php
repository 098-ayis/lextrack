<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class OllamaService
{
    public function generate(string $prompt, ?string $model = null)
    {
        $model ??= config('services.ollama.model');

        $response = Http::timeout(300)
        ->connectTimeout(10)
        ->post(config('services.ollama.url') . '/api/generate', [
            'model' => $model,
            'prompt' => $prompt,
            'stream' => false,
        ]);

        $response->throw();

        return $response->json('response');
    }
}