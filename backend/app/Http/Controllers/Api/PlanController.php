<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Helpers\PlanHelper;
use Illuminate\Http\Request;

class PlanController extends Controller
{
    /**
     * Lista todos os planos disponíveis
     */
    public function index()
    {
        return response()->json(PlanHelper::all());
    }

    /**
     * Retorna detalhes de um plano específico
     */
    public function show(string $planType)
    {
        $plan = PlanHelper::get($planType);
        
        if (!$plan) {
            return response()->json([
                'message' => 'Plano não encontrado'
            ], 404);
        }
        
        return response()->json($plan);
    }
}
