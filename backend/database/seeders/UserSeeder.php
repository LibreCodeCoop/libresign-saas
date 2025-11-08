<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Criar usuário admin
        User::updateOrCreate(
            ['email' => 'admin@libresign.coop'],
            [
                'name' => 'Administrador',
                'password' => Hash::make('Admin@123'),
                'phone' => '(11) 99999-9999',
                'company' => 'LibreCode Cooperativa',
                'role' => 'Administrador',
                'is_admin' => true,
            ]
        );

        // Criar usuário de teste
        User::updateOrCreate(
            ['email' => 'teste@libresign.coop'],
            [
                'name' => 'Teste LibreSign',
                'password' => Hash::make('Teste@123'),
                'phone' => '(11) 98765-4321',
                'company' => 'LibreCode Cooperativa',
                'role' => 'Desenvolvedor',
                'is_admin' => false,
                'plan_type' => 'trial',
                'trial_ends_at' => now()->addDays(14),
                'document_limit' => 50,
                'documents_signed_this_month' => 12,
                'platform_url' => 'https://demo.libresign.coop', // URL de exemplo
            ]
        );

        echo "\n✓ Usuários criados com sucesso!\n";
        echo "\n=== Credenciais de Admin ===\n";
        echo "Email: admin@libresign.coop\n";
        echo "Senha: Admin@123\n";
        echo "\n=== Credenciais de Teste ===\n";
        echo "Email: teste@libresign.coop\n";
        echo "Senha: Teste@123\n\n";
    }
}
