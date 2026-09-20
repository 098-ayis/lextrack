<?php

namespace App\Http\Controllers;

use App\Ai\Agents\LexTrackAssistant;
use App\Models\User;
use App\Services\ClientDocumentLookupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Laravel\Ai\Enums\Lab;

class ClientAssistantController extends Controller
{
    public function reply(
        Request $request,
        ClientDocumentLookupService $documents
    ): JsonResponse {

        $user = $request->user();

        // Require an authenticated LexTrack user.
        // The route must also use the correct Client Portal auth guard.
        abort_unless($user instanceof User, 401);

        $data = $request->validate([
            'action' => [
                'required',
                'string',
                Rule::in([
                    'latest_status',
                    'explain_pending',
                ]),
            ],
        ]);

        /*
         * PRIVATE DOCUMENT INQUIRY
         *
         * Laravel retrieves the document directly.
         * No document information is sent to OpenAI.
         */
        if ($data['action'] === 'latest_status') {

            $reply = $documents->latestStatus($user);

            return response()->json([
                'reply' => $reply,
                'source' => 'database',
            ]);
        }

        /*
         * GENERAL KNOWLEDGE INQUIRY
         *
         * Only a predefined general question is sent
         * to the AI agent.
         */
        $response = (new LexTrackAssistant)->prompt(
            'Explain what the Pending document status means in LexTrack.',
            provider: Lab::OpenAI,
            model: 'gpt-5-mini',
            timeout: 30,
        );

        return response()->json([
            'reply' => (string) $response,
            'source' => 'ai',
        ]);
    }
}

