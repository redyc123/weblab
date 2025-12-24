<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    public function index()
    {
        // Get the authenticated user
        $user = Auth::user();

        // Get user's OAuth access tokens (for Passport)
        $tokens = $user->tokens()->orderBy('created_at', 'desc')->get();

        return view('profile.index', compact('user', 'tokens'));
    }

    public function createToken(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'scopes' => 'array'
        ]);

        $user = Auth::user();

        $token = $user->createToken($request->name, $request->scopes ?: []);

        return response()->json([
            'success' => true,
            'token' => $token->accessToken,
            'name' => $token->token->name,
            'created_at' => $token->token->created_at,
            'message' => 'Токен успешно создан'
        ]);
    }

    public function deleteToken(Request $request, $tokenId)
    {
        $user = Auth::user();

        $token = $user->tokens()->where('id', $tokenId)->first();

        if ($token) {
            $token->revoke();
            return response()->json([
                'success' => true,
                'message' => 'Токен успешно удален'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Токен не найден'
        ], 404);
    }
}