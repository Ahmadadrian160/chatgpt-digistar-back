<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Conversation;
use Illuminate\Support\Facades\Http; // Untuk permintaan HTTP

class ChatController extends Controller
{
    public function chat(Request $request)
    {
        // Kunci API dan detail model dari GroqCloud
        $apiKey = "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"; // Pastikan kunci API disimpan di file .env
        $model = "llama-3.3-70b-versatile"; // Tentukan model yang digunakan
        $userMessage = $request->input('message');
        $sessionId = $request->input('session_id');

        if (!$userMessage) {
            return response()->json(['error' => 'Pesan diperlukan'], 400);
        }

        try {
            // Menyiapkan body untuk permintaan POST
            $requestBody = [
                'model' => $model,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $userMessage,
                    ],
                ],
                // Tambahkan parameter lain sesuai kebutuhan
                // 'n' => 1,
                // 'stream' => false,
                // 'stop' => ['\n'],
            ];

            // Mengirim permintaan ke API GroqCloud
            $response = Http::withHeaders([
                'Authorization' => "Bearer $apiKey",
                'Content-Type' => 'application/json',
            ])->post('https://api.groq.com/openai/v1/chat/completions', $requestBody);

            if ($response->failed()) {
                return response()->json([ 
                    'error' => 'Gagal terhubung ke API GroqCloud',
                    'details' => $response->json()
                ], 500);
            }

            // Mengambil respons dari API
            $botReply = $response->json()['choices'][0]['message']['content'] ?? 'Error: Tidak ada respons';

            // Menyimpan percakapan ke database
            Conversation::create([
                'session_id' => $sessionId,
                'user_message' => $userMessage,
                'bot_response' => $botReply,
            ]);

            return response()->json(['response' => $botReply]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Terjadi kesalahan server', 'details' => $e->getMessage()], 500);
        }
    }

    // Metode untuk mengambil riwayat percakapan berdasarkan session_id
    public function getChatHistory($sessionId)
    {
        // Mengambil riwayat percakapan dari database
        $conversations = Conversation::where('session_id', $sessionId)->get();

        // Memformat data percakapan
        $history = $conversations->flatMap(function ($conversation) {
            return [
                ['role' => 'user', 'content' => $conversation->user_message],
                ['role' => 'bot', 'content' => $conversation->bot_response],
            ];
        });

        return response()->json(['history' => $history]);
    }
    public function getDistinctSessionIds()
    {
        // Mengambil daftar session_id yang unik dari database
        $sessionIds = Conversation::distinct()->pluck('session_id');

        return response()->json(['session_ids' => $sessionIds]);
    }
    
}
