<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Plan;
use App\Jobs\CreateNextcloudUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Password::min(8)
                ->mixedCase()
                ->numbers()
                ->symbols()],
            'phone' => ['required', 'string', 'max:20'],
            'company' => ['required', 'string', 'max:255'],
            'role' => ['required', 'string', 'max:255'],
        ]);

        // Buscar plano Trial
        $trialPlan = Plan::where('slug', 'trial')->first();
        
        if (!$trialPlan) {
            return response()->json([
                'message' => 'Plano Trial não configurado. Contate o administrador.',
            ], 500);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
            'company' => $request->company,
            'role' => $request->role,
            'plan_id' => $trialPlan->id,
            'plan_type' => 'trial',
            'trial_ends_at' => now()->addDays($trialPlan->trial_days),
            'document_limit' => $trialPlan->document_limit,
        ]);

        // Dispara criação de usuário Nextcloud em background
        CreateNextcloudUser::dispatch($user);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'company' => $user->company,
                'role' => $user->role,
                'is_admin' => $user->is_admin,
            ],
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['As credenciais fornecidas estão incorretas.'],
            ]);
        }

        // Remover tokens anteriores se não for "lembrar-me"
        if (!$request->remember) {
            $user->tokens()->delete();
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'company' => $user->company,
                'role' => $user->role,
                'is_admin' => $user->is_admin,
            ],
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout realizado com sucesso',
        ]);
    }

    public function user(Request $request)
    {
        $user = $request->user();
        
        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'company' => $user->company,
            'role' => $user->role,
            'is_admin' => $user->is_admin,
            'plan_type' => $user->plan_type,
            'trial_ends_at' => $user->trial_ends_at,
            'subscription_ends_at' => $user->subscription_ends_at,
            'document_limit' => $user->document_limit,
            'documents_signed_this_month' => $user->documents_signed_this_month,
            'days_until_trial_ends' => $user->days_until_trial_ends,
            'is_on_trial' => $user->isOnTrial(),
            'nextcloud_status' => $user->nextcloud_status,
            'nextcloud_instance_id' => $user->nextcloud_instance_id,
            'platform_url' => $user->platform_url,
        ]);
    }
}
