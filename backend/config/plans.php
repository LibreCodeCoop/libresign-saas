<?php

return [
    'trial' => [
        'name' => 'Trial',
        'price' => 0,
        'documents_limit' => 50,
        'storage' => '5GB',
        'users_limit' => 1,
        'duration_days' => 14,
        'features' => [
            'Até 50 documentos/mês',
            '1 usuário',
            '5 GB de armazenamento',
            'Suporte por email',
            'Assinatura digital válida juridicamente',
        ],
    ],

    'basico' => [
        'name' => 'Básico',
        'price' => 49.00,
        'documents_limit' => 200,
        'storage' => '10GB',
        'users_limit' => 5,
        'features' => [
            'Até 200 documentos/mês',
            'Até 5 usuários',
            '10 GB de armazenamento',
            'Suporte por e-mail',
            'Assinatura digital válida juridicamente',
            'Modelos de documentos básicos',
        ],
    ],

    'profissional' => [
        'name' => 'Profissional',
        'price' => 149.00,
        'documents_limit' => 500,
        'storage' => '50GB',
        'users_limit' => 20,
        'featured' => true,
        'features' => [
            'Até 500 documentos/mês',
            'Até 20 usuários',
            '50 GB de armazenamento',
            'Suporte por chat e e-mail',
            'Assinatura digital válida juridicamente',
            'Modelos avançados de documentos',
            'Integração via API',
            'Relatórios avançados',
        ],
    ],

    'empresarial' => [
        'name' => 'Empresarial',
        'price' => 499.00,
        'documents_limit' => 2000,
        'storage' => '200GB',
        'users_limit' => 'unlimited',
        'features' => [
            'Até 2.000 documentos/mês',
            'Usuários ilimitados',
            '200 GB de armazenamento',
            'Suporte prioritário',
            'Assinatura digital válida juridicamente',
            'Modelos personalizados',
            'Integração via API completa',
            'Relatórios avançados e customizáveis',
            'Gerente de conta dedicado',
            'SLA garantido',
        ],
    ],
];
