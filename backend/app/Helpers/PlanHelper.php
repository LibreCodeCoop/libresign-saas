<?php

namespace App\Helpers;

class PlanHelper
{
    /**
     * Obtém todas as configurações de planos
     */
    public static function all(): array
    {
        return config('plans', []);
    }

    /**
     * Obtém configuração de um plano específico
     */
    public static function get(string $planType): ?array
    {
        return config("plans.{$planType}");
    }

    /**
     * Obtém o storage de um plano
     */
    public static function getStorage(string $planType): string
    {
        return self::get($planType)['storage'] ?? '5GB';
    }

    /**
     * Obtém o limite de documentos de um plano
     */
    public static function getDocumentsLimit(string $planType): int
    {
        return self::get($planType)['documents_limit'] ?? 50;
    }

    /**
     * Obtém o preço de um plano
     */
    public static function getPrice(string $planType): float
    {
        return self::get($planType)['price'] ?? 0;
    }

    /**
     * Obtém o nome do plano
     */
    public static function getName(string $planType): string
    {
        return self::get($planType)['name'] ?? ucfirst($planType);
    }

    /**
     * Obtém as features do plano
     */
    public static function getFeatures(string $planType): array
    {
        return self::get($planType)['features'] ?? [];
    }

    /**
     * Verifica se um plano existe
     */
    public static function exists(string $planType): bool
    {
        return self::get($planType) !== null;
    }

    /**
     * Lista apenas planos pagos (sem trial)
     */
    public static function paidPlans(): array
    {
        return array_filter(self::all(), function ($plan, $key) {
            return $key !== 'trial';
        }, ARRAY_FILTER_USE_BOTH);
    }
}
