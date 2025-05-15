<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Message;
use App\Events\MessageSent;

class MessageController extends Controller
{
    public function index(Request $request)
    {
        return Message::where('channel', $request->channel ?? 'general')
                      ->orderBy('created_at')
                      ->get();
    }

    public function store(Request $request)
    {
        $request->validate([
            'username' => 'required',
            'message' => 'required',
            'channel' => 'required',
        ]);

        $message = Message::create([
            'username' => $request->username,
            'message' => $request->message,
            'channel' => $request->channel,
        ]);

        broadcast(new MessageSent($message))->toOthers();

        return response()->json($message, 201);
    }
}
