<?php

namespace App\Http\Controllers;

use App\Models\Message;
use Illuminate\Http\Request;
use App\Events\NewMessage;

class MessageController extends Controller
{
    /**
     * Pobierz wiadomości dla określonego kanału
     */
    public function index(Request $request)
    {
        $channel = $request->query('channel', 'general');
        
        // Pobierz wiadomości posortowane od najstarszej do najnowszej
        $messages = Message::where('channel', $channel)
            ->orderBy('created_at', 'asc')
            ->get();
            
        return response()->json($messages);
    }

    /**
     * Zapisz nową wiadomość
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'username' => 'required|string|max:50',
            'message' => 'required|string',
            'channel' => 'required|string|max:50',
        ]);

        $message = Message::create([
            'username' => $validated['username'],
            'message' => $validated['message'],
            'channel' => $validated['channel'],
        ]);

        // Wyzwól event, który powiadomi wszystkich użytkowników o nowej wiadomości
        event(new NewMessage($message));

        return response()->json($message, 201);
    }
}