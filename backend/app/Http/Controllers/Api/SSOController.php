<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LoginToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SSOController extends Controller
{
    /**
     * Generate SSO token for current user
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function generateToken(Request $request)
    {
        $user = $request->user();

        if (!$user->platform_url) {
            return response()->json([
                'success' => false,
                'message' => 'Sua conta ainda está sendo configurada. Aguarde alguns instantes.',
            ], 400);
        }

        // Gerar token SSO com 5 minutos de validade
        $loginToken = LoginToken::generateFor($user, 5);

        Log::info('SSO token generated', [
            'user_id' => $user->id,
            'token_id' => $loginToken->id,
            'expires_at' => $loginToken->expires_at,
        ]);

        // URL para fazer SSO no Nextcloud
        $ssoUrl = rtrim($user->platform_url, '/') . '/index.php/apps/libresign_sso/login?token=' . $loginToken->token;

        return response()->json([
            'success' => true,
            'sso_url' => $ssoUrl,
            'expires_at' => $loginToken->expires_at->toISOString(),
        ]);
    }

    /**
     * Validate SSO token (called by Nextcloud)
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function validateToken(Request $request)
    {
        $token = $request->input('token');

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Token não fornecido',
            ], 400);
        }

        $user = LoginToken::validateAndUse($token);

        if (!$user) {
            Log::warning('Invalid SSO token attempt', [
                'token' => substr($token, 0, 10) . '...',
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Token inválido ou expirado',
            ], 401);
        }

        Log::info('SSO token validated successfully', [
            'user_id' => $user->id,
            'email' => $user->email,
        ]);

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'name' => $user->name,
                'nextcloud_user_id' => $user->nextcloud_user_id,
            ],
        ]);
    }
}
