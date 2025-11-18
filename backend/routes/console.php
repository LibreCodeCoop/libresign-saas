<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Models\LoginToken;

// Monitorar instâncias Nextcloud
// Executa a cada 5 minutos
Schedule::command('instances:monitor')->everyFiveMinutes();

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
