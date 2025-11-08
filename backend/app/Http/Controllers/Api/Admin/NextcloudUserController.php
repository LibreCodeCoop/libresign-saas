<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\NextcloudInstance;
use App\Services\NextcloudService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class NextcloudUserController extends Controller
{
    /**
     * Lista todos os usuários de uma instância
     */
    public function index(Request $request, string $instanceId)
    {
        $instance = NextcloudInstance::findOrFail($instanceId);
        
        try {
            $nc = new NextcloudService($instance);
            $users = $nc->listUsers();
            
            return response()->json([
                'instance' => $instance->name,
                'users' => $users
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao listar usuários',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cria um novo usuário no Nextcloud
     */
    public function store(Request $request, string $instanceId)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|string|max:255',
            'display_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'password' => 'nullable|string|min:8',
            'groups' => 'nullable|array',
            'groups.*' => 'string',
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
            
            // Criar usuário
            $result = $nc->createUser(
                $request->user_id,
                $request->display_name,
                $request->email,
                $request->password
            );

            // Adicionar aos grupos se especificado
            if ($request->has('groups')) {
                foreach ($request->groups as $group) {
                    try {
                        $nc->addUserToGroup($request->user_id, $group);
                    } catch (\Exception $e) {
                        // Se o grupo não existe, cria
                        $nc->createGroup($group);
                        $nc->addUserToGroup($request->user_id, $group);
                    }
                }
            }

            // Incrementar contador de usuários da instância
            $instance->increment('current_users');

            return response()->json([
                'message' => 'Usuário criado com sucesso',
                'user' => $result
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao criar usuário',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtém informações de um usuário específico
     */
    public function show(Request $request, string $instanceId, string $userId)
    {
        $instance = NextcloudInstance::findOrFail($instanceId);

        try {
            $nc = new NextcloudService($instance);
            $userInfo = $nc->getUserInfo($userId);
            $groups = $nc->listUserGroups($userId);

            return response()->json([
                'user_id' => $userId,
                'info' => $userInfo,
                'groups' => $groups
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao obter informações do usuário',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Deleta um usuário do Nextcloud
     */
    public function destroy(Request $request, string $instanceId, string $userId)
    {
        $instance = NextcloudInstance::findOrFail($instanceId);

        try {
            $nc = new NextcloudService($instance);
            $result = $nc->deleteUser($userId);

            // Decrementar contador de usuários da instância
            if ($instance->current_users > 0) {
                $instance->decrement('current_users');
            }

            return response()->json([
                'message' => 'Usuário removido com sucesso',
                'output' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao remover usuário',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Adiciona usuário a um grupo
     */
    public function addToGroup(Request $request, string $instanceId, string $userId)
    {
        $validator = Validator::make($request->all(), [
            'group_id' => 'required|string|max:255',
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
            $result = $nc->addUserToGroup($userId, $request->group_id);

            return response()->json([
                'message' => 'Usuário adicionado ao grupo',
                'output' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao adicionar usuário ao grupo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove usuário de um grupo
     */
    public function removeFromGroup(Request $request, string $instanceId, string $userId)
    {
        $validator = Validator::make($request->all(), [
            'group_id' => 'required|string|max:255',
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
            $result = $nc->removeUserFromGroup($userId, $request->group_id);

            return response()->json([
                'message' => 'Usuário removido do grupo',
                'output' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao remover usuário do grupo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Promove usuário a sub-admin de um grupo
     */
    public function promoteToSubAdmin(Request $request, string $instanceId, string $userId)
    {
        $validator = Validator::make($request->all(), [
            'group_id' => 'required|string|max:255',
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
            $result = $nc->addSubAdmin($userId, $request->group_id);

            return response()->json([
                'message' => 'Usuário promovido a sub-admin',
                'output' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao promover usuário',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtém último acesso do usuário
     */
    public function lastSeen(Request $request, string $instanceId, string $userId)
    {
        $instance = NextcloudInstance::findOrFail($instanceId);

        try {
            $nc = new NextcloudService($instance);
            $lastSeen = $nc->getUserLastSeen($userId);

            return response()->json([
                'user_id' => $userId,
                'last_seen' => $lastSeen
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao obter último acesso',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reenvia email de boas-vindas
     */
    public function resendWelcome(Request $request, string $instanceId, string $userId)
    {
        $instance = NextcloudInstance::findOrFail($instanceId);

        try {
            $nc = new NextcloudService($instance);
            $result = $nc->resendWelcomeEmail($userId);

            return response()->json([
                'message' => 'Email de boas-vindas reenviado',
                'output' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao reenviar email',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
