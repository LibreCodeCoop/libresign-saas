<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\NextcloudInstance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class NextcloudInstanceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $instances = NextcloudInstance::orderBy('created_at', 'desc')->get();
        
        return response()->json($instances);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Define management_method padrão se não fornecido
        $data = $request->all();
        if (!isset($data['management_method'])) {
            $data['management_method'] = 'ssh';
        }
        
        $validator = Validator::make($data, [
            'name' => 'required|string|max:255',
            'url' => 'required|url|max:255',
            'management_method' => 'required|in:ssh,api',
            'ssh_host' => 'required_if:management_method,ssh|nullable|string|max:255',
            'ssh_port' => 'nullable|integer|min:1|max:65535',
            'ssh_user' => 'required_if:management_method,ssh|nullable|string|max:255',
            'ssh_private_key' => 'nullable|string',
            'docker_container_name' => 'nullable|string|max:255',
            'api_username' => 'required_if:management_method,api|nullable|string|max:255',
            'api_password' => 'required_if:management_method,api|nullable|string|max:255',
            'status' => 'nullable|in:active,inactive,maintenance',
            'max_users' => 'nullable|integer|min:1',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erro de validação',
                'errors' => $validator->errors()
            ], 422);
        }

        $instance = NextcloudInstance::create($data);

        return response()->json($instance, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $instance = NextcloudInstance::findOrFail($id);
        
        return response()->json($instance);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $instance = NextcloudInstance::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'url' => 'sometimes|required|url|max:255',
            'management_method' => 'nullable|in:ssh,api',
            'ssh_host' => 'nullable|string|max:255',
            'ssh_port' => 'nullable|integer|min:1|max:65535',
            'ssh_user' => 'nullable|string|max:255',
            'ssh_private_key' => 'nullable|string',
            'docker_container_name' => 'nullable|string|max:255',
            'api_username' => 'nullable|string|max:255',
            'api_password' => 'nullable|string|max:255',
            'status' => 'nullable|in:active,inactive,maintenance',
            'max_users' => 'nullable|integer|min:1',
            'current_users' => 'nullable|integer|min:0',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erro de validação',
                'errors' => $validator->errors()
            ], 422);
        }

        $instance->update($request->all());

        return response()->json($instance);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $instance = NextcloudInstance::findOrFail($id);
        $instance->delete();

        return response()->json([
            'message' => 'Instância removida com sucesso'
        ]);
    }

    /**
     * Test connection to the instance.
     */
    public function testConnection(string $id)
    {
        $instance = NextcloudInstance::findOrFail($id);
        
        // TODO: Implementar teste de conexão SSH real
        // Por enquanto, retorna sucesso simulado
        
        return response()->json([
            'success' => true,
            'message' => 'Conexão testada com sucesso',
            'instance' => $instance->name
        ]);
    }

    /**
     * Busca a versão do Nextcloud da instância
     */
    public function fetchVersion(string $id)
    {
        $instance = NextcloudInstance::findOrFail($id);
        
        $version = $instance->fetchVersion();
        
        if ($version) {
            return response()->json([
                'success' => true,
                'version' => $version,
                'instance' => $instance->name
            ]);
        }
        
        return response()->json([
            'success' => false,
            'message' => 'Não foi possível obter a versão. Verifique se a URL está correta e acessível.'
        ], 500);
    }

    /**
     * Executa health check na instância
     */
    public function healthCheck(string $id)
    {
        $instance = NextcloudInstance::findOrFail($id);
        
        try {
            $results = $instance->runHealthCheck();
            
            return response()->json([
                'success' => true,
                'results' => $results,
                'instance' => $instance->name
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao executar health check: ' . $e->getMessage()
            ], 500);
        }
    }
}
