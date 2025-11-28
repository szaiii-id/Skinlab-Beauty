<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\FcmToken; // <--- WAJIB ADA

class FcmController extends Controller
{
    public function store(Request $request)
    {
        // Validasi input
        $request->validate(['token' => 'required|string']);

        // Simpan ke DB
        FcmToken::updateOrCreate(
            ['user_id' => $request->user()->id, 'token' => $request->token],
            ['device_type' => 'web']
        );

        return response()->json(['message' => 'Token saved']);
    }
}