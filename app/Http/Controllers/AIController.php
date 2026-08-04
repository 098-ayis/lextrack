<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\OllamaService;

class AIController extends Controller
{
    protected $ollama;

    public function __construct(OllamaService $ollama)
    {
        $this->ollama = $ollama;
    }

    public function ask(Request $request)
    {
        $response = $this->ollama->generate($request->prompt);

        return response()->json([
            'response' => $response
        ]);
    }
}