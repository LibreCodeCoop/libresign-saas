<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PlanController;
use App\Http\Controllers\Api\SSOController;
use App\Http\Controllers\Api\Admin\NextcloudInstanceController;
use App\Http\Controllers\Api\Admin\NextcloudUserController;
use App\Http\Controllers\Api\Admin\NextcloudGroupController;
use App\Http\Controllers\Api\Admin\UserMetricsController;
use App\Http\Controllers\Api\Admin\PaymentMethodController;
use App\Http\Controllers\Api\Admin\PaymentMethodTestController;
use Illuminate\Support\Facades\Route;

// Rotas públicas
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/plans', [PlanController::class, 'index']);
Route::get('/plans/{planType}', [PlanController::class, 'show']);

// Métodos de pagamento disponíveis (público)
Route::get('/payment-methods', function () {
    return response()->json(\App\Models\PaymentMethod::where('is_available', true)->get());
});

// SSO - Validação de token (chamado pelo Nextcloud)
Route::post('/sso/validate', [SSOController::class, 'validateToken']);

// Rotas protegidas (requerem autenticação)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    
    // SSO - Gerar token para login único
    Route::post('/sso/generate-token', [SSOController::class, 'generateToken']);
    
    // Pedidos/Checkout
    Route::apiResource('orders', OrderController::class)->only(['index', 'store', 'show']);
    
    // Rotas administrativas
    Route::middleware('admin')->prefix('admin')->group(function () {
        // Métodos de pagamento
        Route::apiResource('payment-methods', PaymentMethodController::class);
        Route::post('payment-methods/{id}/test', [PaymentMethodTestController::class, 'test']);
        
        // Métricas de usuários
        Route::get('users/metrics', [UserMetricsController::class, 'index']);
        Route::post('users/{id}/sync-metrics', [UserMetricsController::class, 'sync']);
        Route::get('instances/{instanceId}/users/stats', [UserMetricsController::class, 'byInstance']);
        
        // Gerenciamento de instâncias Nextcloud
        Route::apiResource('instances', NextcloudInstanceController::class);
        Route::post('instances/{id}/test-connection', [NextcloudInstanceController::class, 'testConnection']);
        Route::post('instances/{id}/fetch-version', [NextcloudInstanceController::class, 'fetchVersion']);
        Route::post('instances/{id}/health-check', [NextcloudInstanceController::class, 'healthCheck']);
        
        // Gerenciamento de usuários Nextcloud
        Route::prefix('instances/{instanceId}/users')->group(function () {
            Route::get('/', [NextcloudUserController::class, 'index']);
            Route::post('/', [NextcloudUserController::class, 'store']);
            Route::get('/{userId}', [NextcloudUserController::class, 'show']);
            Route::delete('/{userId}', [NextcloudUserController::class, 'destroy']);
            Route::post('/{userId}/add-to-group', [NextcloudUserController::class, 'addToGroup']);
            Route::post('/{userId}/remove-from-group', [NextcloudUserController::class, 'removeFromGroup']);
            Route::post('/{userId}/promote-subadmin', [NextcloudUserController::class, 'promoteToSubAdmin']);
            Route::get('/{userId}/last-seen', [NextcloudUserController::class, 'lastSeen']);
            Route::post('/{userId}/resend-welcome', [NextcloudUserController::class, 'resendWelcome']);
        });
        
        // Gerenciamento de grupos Nextcloud
        Route::prefix('instances/{instanceId}/groups')->group(function () {
            Route::get('/', [NextcloudGroupController::class, 'index']);
            Route::post('/', [NextcloudGroupController::class, 'store']);
            Route::delete('/{groupId}', [NextcloudGroupController::class, 'destroy']);
            Route::post('/{groupId}/set-quota', [NextcloudGroupController::class, 'setQuota']);
        });
    });
});
