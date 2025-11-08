<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Jobs\SyncUserMetrics;
use Illuminate\Http\Request;

class UserMetricsController extends Controller
{
    public function index(Request $request)
    {
        $query = User::whereNotNull('nextcloud_user_id')->with('nextcloudInstance:id,name,url');

        if ($request->has('plan_type')) {
            $query->where('plan_type', $request->plan_type);
        }

        $users = $query->orderBy('created_at', 'desc')->get();

        return response()->json($users->map(fn($user) => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'company' => $user->company,
            'plan_type' => $user->plan_type,
            'nextcloud_instance' => $user->nextcloudInstance?->name,
            'storage_used' => $user->storage_used_human,
            'storage_quota' => $user->storage_quota_human,
            'storage_percentage' => $user->storage_usage_percentage,
            'documents_signed_this_month' => $user->documents_signed_this_month,
            'last_login_at' => $user->last_login_at,
        ]));
    }

    public function sync(string $id)
    {
        $user = User::findOrFail($id);

        if (!$user->nextcloud_user_id) {
            return response()->json(['message' => 'Usuário não possui conta Nextcloud'], 400);
        }

        SyncUserMetrics::dispatch($user);
        return response()->json(['message' => 'Sincronização agendada']);
    }

    /**
     * Estatísticas de usuários por instância
     */
    public function byInstance(string $instanceId)
    {
        $users = User::where('nextcloud_instance_id', $instanceId)
            ->whereNotNull('nextcloud_user_id')
            ->where('nextcloud_status', 'active')
            ->get();

        return response()->json([
            'users' => $users->map(fn($user) => [
                'user_id' => $user->nextcloud_user_id,
                'display_name' => $user->name,
                'quota' => $user->storage_quota_human,
                'used' => $user->storage_used_human,
                'free' => $this->calculateFree($user),
                'relative' => (int) $user->storage_usage_percentage,
            ])
        ]);
    }

    private function calculateFree($user): string
    {
        if (!$user->storage_quota_bytes || $user->storage_quota_bytes == 0) {
            return 'Ilimitado';
        }
        $freeBytes = $user->storage_quota_bytes - $user->storage_used_bytes;
        return $this->formatBytes(max(0, $freeBytes));
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
