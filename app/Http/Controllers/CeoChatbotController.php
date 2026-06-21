<?php

namespace App\Http\Controllers;

use App\Support\CeoChatbot;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CeoChatbotController extends Controller
{
    public function bootstrap(): JsonResponse
    {
        return response()->json(CeoChatbot::bootstrap());
    }

    public function ask(Request $request): JsonResponse
    {
        $question = trim((string) $request->input('question', ''));

        if ($question === '') {
            return response()->json(['ok' => false, 'message' => 'Pertanyaan kosong.'], 422);
        }

        return response()->json(CeoChatbot::answer($question));
    }
}
