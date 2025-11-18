<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PaymentGateway;
use Illuminate\Http\Request;

class PaymentGatewayController extends Controller
{
    /**
     * Listar todos os gateways
     */
    public function index()
    {
        $gateways = PaymentGateway::ordered()->get();
        
        return response()->json($gateways);
    }

    /**
     * Listar apenas gateways ativos (para checkout)
     */
    public function active()
    {
        $gateways = PaymentGateway::getActiveOrdered();
        
        return response()->json($gateways);
    }

    /**
     * Detalhes de um gateway
     */
    public function show($id)
    {
        $gateway = PaymentGateway::findOrFail($id);
        
        return response()->json($gateway);
    }

    /**
     * Criar novo gateway
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|unique:payment_gateways,slug',
            'type' => 'required|string|in:pix,boleto,credit_card,stripe,paypal',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'settings' => 'nullable|array',
            'metadata' => 'nullable|array',
        ]);

        $gateway = PaymentGateway::create($validated);

        return response()->json([
            'message' => 'Gateway criado com sucesso',
            'gateway' => $gateway,
        ], 201);
    }

    /**
     * Atualizar gateway
     */
    public function update(Request $request, $id)
    {
        $gateway = PaymentGateway::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'slug' => 'sometimes|string|unique:payment_gateways,slug,' . $id,
            'type' => 'sometimes|string|in:pix,boleto,credit_card,stripe,paypal',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'settings' => 'nullable|array',
            'metadata' => 'nullable|array',
        ]);

        $gateway->update($validated);

        return response()->json([
            'message' => 'Gateway atualizado com sucesso',
            'gateway' => $gateway,
        ]);
    }

    /**
     * Ativar/desativar gateway
     */
    public function toggle($id)
    {
        $gateway = PaymentGateway::findOrFail($id);
        $gateway->is_active = !$gateway->is_active;
        $gateway->save();

        return response()->json([
            'message' => 'Status atualizado com sucesso',
            'gateway' => $gateway,
        ]);
    }

    /**
     * Deletar gateway
     */
    public function destroy($id)
    {
        $gateway = PaymentGateway::findOrFail($id);
        $gateway->delete();

        return response()->json([
            'message' => 'Gateway deletado com sucesso',
        ]);
    }

    /**
     * Reordenar gateways
     */
    public function reorder(Request $request)
    {
        $validated = $request->validate([
            'gateways' => 'required|array',
            'gateways.*.id' => 'required|exists:payment_gateways,id',
            'gateways.*.sort_order' => 'required|integer',
        ]);

        foreach ($validated['gateways'] as $item) {
            PaymentGateway::where('id', $item['id'])
                ->update(['sort_order' => $item['sort_order']]);
        }

        return response()->json([
            'message' => 'Ordem atualizada com sucesso',
        ]);
    }
}
