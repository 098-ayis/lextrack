<?php

namespace App\Http\Controllers;

use App\Ai\Agents\LexTrackAssistant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Ai\Enums\Lab;

class ChatbotController extends Controller
{
    public function reply(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:1000'],
        ]);

        $response = (new LexTrackAssistant)->prompt(
            $validated['message'],
            provider: Lab::OpenAI,
            model: 'gpt-5-mini',
            timeout: 30,
        );

        return response()->json([
            'reply' => (string) $response,
        ]);
    }
}