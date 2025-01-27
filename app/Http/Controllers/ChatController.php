<?php


namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Conversation;

class ChatController extends Controller
{
    public function chat(Request $request)
    {
        $apiKey = env('HUGGING_FACE_API_KEY'); // API Key Hugging Face
        $userMessage = $request->input('message');
        $sessionId = $request->input('session_id');

        if (!$userMessage) {
            return response()->json(['error' => 'Message is required'], 400);
        }

        try {
            // Menggunakan Hugging Face API untuk mendapatkan respons dari model
            $response = Http::withHeaders([
                'Authorization' => "Bearer $apiKey",
            ])->post('https://api-inference.huggingface.co/models/gpt2', [
                'inputs' => $userMessage,
            ]);

            if ($response->failed()) {
                return response()->json([ 
                    'error' => 'Failed to connect to Hugging Face API',
                    'details' => $response->json()
                ], 500);
            }

            $botReply = $response->json()[0]['generated_text'] ?? 'Error: No response';

            // Simpan ke database
            Conversation::create([
                'session_id' => $sessionId,
                'user_message' => $userMessage,
                'bot_response' => $botReply,
            ]);

            return response()->json(['response' => $botReply]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Server error', 'details' => $e->getMessage()], 500);
        }
    }

    // Method untuk mendapatkan riwayat percakapan berdasarkan session_id
    public function getChatHistory($sessionId)
    {
        // Ambil riwayat percakapan berdasarkan session_id
        $conversations = Conversation::where('session_id', $sessionId)->get();

        // Format data percakapan untuk dikirimkan sebagai response
        $history = $conversations->flatMap(function ($conversation) {
            return [
                ['role' => 'user', 'content' => $conversation->user_message],
                ['role' => 'bot', 'content' => $conversation->bot_response],
            ];
        });

        return response()->json(['history' => $history]);
    }
}
