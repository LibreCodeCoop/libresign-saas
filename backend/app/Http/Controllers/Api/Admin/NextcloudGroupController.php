<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\NextcloudInstance;
use App\Services\NextcloudService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class NextcloudGroupController extends Controller
{
    /**
     * Lista todos os grupos de uma instância
     */
    public function index(Request $request, string $instanceId)
    {
        $instance = NextcloudInstance::findOrFail($instanceId);
        
        try {
            $nc = new NextcloudService($instance);
            $groups = $nc->listGroups();
            
            return response()->json([
                'instance' => $instance->name,
                'groups' => $groups
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao listar grupos',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cria um novo grupo no Nextcloud
     */
    public function store(Request $request, string $instanceId)
    {
        $validator = Validator::make($request->all(), [
            'group_id' => 'required|string|max:255',
            'quota' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erro de validação',
                'errors' => $validator->errors()
            ], 422);
        }

        $instance = NextcloudInstance::findOrFail($instanceId);

        try {
            $nc = new NextcloudService($instance);
            $result = $nc->createGroup($request->group_id);
            
            // Define quota se especificada
            if ($request->has('quota')) {
                $nc->setGroupQuota($request->group_id, $request->quota);
            }

            return response()->json([
                'message' => 'Grupo criado com sucesso',
                'group_id' => $request->group_id,
                'output' => $result
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao criar grupo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Deleta um grupo do Nextcloud
     */
    public function destroy(Request $request, string $instanceId, string $groupId)
    {
        $instance = NextcloudInstance::findOrFail($instanceId);

        try {
            $nc = new NextcloudService($instance);
            $result = $nc->deleteGroup($groupId);

            return response()->json([
                'message' => 'Grupo removido com sucesso',
                'output' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao remover grupo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Define quota para um grupo
     */
    public function setQuota(Request $request, string $instanceId, string $groupId)
    {
        $validator = Validator::make($request->all(), [
            'quota' => 'required|string|max:255', // Ex: 5GB, 1TB, unlimited
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erro de validação',
                'errors' => $validator->errors()
            ], 422);
        }

        $instance = NextcloudInstance::findOrFail($instanceId);

        try {
            $nc = new NextcloudService($instance);
            $result = $nc->setGroupQuota($groupId, $request->quota);

            return response()->json([
                'message' => 'Quota definida com sucesso',
                'group_id' => $groupId,
                'quota' => $request->quota,
                'output' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao definir quota',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
