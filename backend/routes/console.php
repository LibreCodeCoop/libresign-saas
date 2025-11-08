<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Models\LoginToken;

// Agendar sincronização de métricas dos usuários
// Executa diariamente à meia-noite
Schedule::command('users:sync-metrics')->daily();

// Limpar tokens SSO expirados
// Executa diariamente à 1h da manhã
Schedule::call(function () {
    LoginToken::cleanupExpired();
})->dailyAt('01:00');

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
