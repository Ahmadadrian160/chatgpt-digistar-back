<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    use HasFactory;

    // Menambahkan session_id ke dalam fillable property
    protected $fillable = ['session_id', 'user_message', 'bot_response'];
}
