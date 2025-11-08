<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Helpers\PlanHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    /**
     * Criar um novo pedido
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'plan_type' => 'required|in:basico,profissional,empresarial',
            'billing_name' => 'required|string|max:255',
            'billing_document' => 'required|string|max:20',
            'billing_email' => 'required|email|max:255',
            'billing_phone' => 'required|string|max:20',
            'billing_address' => 'required|string|max:255',
            'billing_city' => 'required|string|max:100',
            'billing_state' => 'required|string|max:2',
            'billing_zip' => 'required|string|max:10',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erro de validação',
                'errors' => $validator->errors()
            ], 422);
        }

        // Obtém preço do plano
        $price = PlanHelper::getPrice($request->plan_type);

        $order = Order::create([
            'user_id' => $request->user()->id,
            'plan_type' => $request->plan_type,
            'amount' => $price,
            'status' => 'pending',
            'billing_name' => $request->billing_name,
            'billing_document' => $request->billing_document,
            'billing_email' => $request->billing_email,
            'billing_phone' => $request->billing_phone,
            'billing_address' => $request->billing_address,
            'billing_city' => $request->billing_city,
            'billing_state' => $request->billing_state,
            'billing_zip' => $request->billing_zip,
        ]);

        // Atualiza plano do usuário (em produção, só após confirmação de pagamento)
        // Por enquanto, atualizamos imediatamente para demonstração
        $user = $request->user();
        $user->update([
            'plan_type' => $request->plan_type,
            'document_limit' => PlanHelper::getDocumentsLimit($request->plan_type),
            'subscription_ends_at' => now()->addDays(30), // Assinatura mensal
        ]);

        return response()->json([
            'message' => 'Pedido criado com sucesso',
            'order' => $order
        ], 201);
    }

    /**
     * Listar pedidos do usuário autenticado
     */
    public function index(Request $request)
    {
        $orders = $request->user()->orders()->orderBy('created_at', 'desc')->get();
        return response()->json($orders);
    }

    /**
     * Obter detalhes de um pedido
     */
    public function show(Request $request, $id)
    {
        $order = $request->user()->orders()->findOrFail($id);
        return response()->json($order);
    }
}
