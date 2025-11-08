<?php

namespace App\Observers;

use App\Models\User;
use App\Jobs\SyncNextcloudUserQuota;
use Illuminate\Support\Facades\Log;

class UserObserver
{
    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        // Verifica se o plano foi alterado
        if ($user->isDirty('plan_type')) {
            $oldPlan = $user->getOriginal('plan_type');
            $newPlan = $user->plan_type;
            
            Log::info("Plano alterado", [
                'user_id' => $user->id,
                'email' => $user->email,
                'old_plan' => $oldPlan,
                'new_plan' => $newPlan,
            ]);
            
            // Dispara job para sincronizar quota no Nextcloud
            SyncNextcloudUserQuota::dispatch($user, $newPlan);
        }
        
        // Verifica se o limite de documentos foi alterado
        if ($user->isDirty('document_limit')) {
            Log::info("Limite de documentos alterado", [
                'user_id' => $user->id,
                'old_limit' => $user->getOriginal('document_limit'),
                'new_limit' => $user->document_limit,
            ]);
        }
    }
}
