<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use App\Services\ChatbotService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatbotController extends Controller
{
    public function ask(Request $request, ChatbotService $bot)
    {
        $request->validate(['message' => 'required|string|max:500']);
        $reply = $bot->reply($request->message, Auth::user());

        ChatMessage::create([
            'user_id'    => Auth::id(),
            'session_id' => session()->getId(),
            'question'   => $request->message,
            'answer'     => mb_substr($reply['answer'], 0, 2000),
            'intent'     => $reply['intent'],
        ]);
        return response()->json($reply);
    }
}
