'use client';

import { useState, useEffect } from 'react';
import { useRouter } from 'next/navigation';
import toast from 'react-hot-toast';
import { api } from '@/services/api';
import Header from '@/components/Header';
import StatCard from '@/components/StatCard';
import ActionCard from '@/components/ActionCard';
import ChangePasswordModal from '@/components/ChangePasswordModal';

interface UserData {
  name: string;
  email: string;
  company_name: string;
  phone?: string;
  role?: string;
  is_admin?: boolean;
}

interface PlanData {
  name: string;
  price: number;
  documents_limit: number;
  renewal_date: string;
}

interface StatsData {
  documents_signed_this_month: number;
  total_documents: number;
}

export default function DashboardPage() {
  const router = useRouter();
  const [user, setUser] = useState<UserData | null>(null);
  const [plan, setPlan] = useState<PlanData | null>(null);
  const [stats, setStats] = useState<StatsData | null>(null);
  const [loading, setLoading] = useState(true);
  const [isPasswordModalOpen, setIsPasswordModalOpen] = useState(false);

  useEffect(() => {
    loadDashboardData();
  }, []);

  const loadDashboardData = async () => {
    setLoading(true);
    try {
      const [userData, planData, statsData] = await Promise.all([
        api.getUser(),
        api.getUserPlan(),
        api.getUserStats(),
      ]);

      setUser(userData);
      setPlan(planData);
      setStats(statsData);
    } catch (error: any) {
      toast.error('Erro ao carregar dados do dashboard');
      if (error.message.includes('Erro ao buscar dados do usuário')) {
        router.push('/login');
      }
    } finally {
      setLoading(false);
    }
  };

  const handleAccessPlatform = () => {
    // TODO: Implementar SSO com plataforma LibreSign
    window.open('https://libresign.example.com', '_blank');
    toast.success('Abrindo plataforma LibreSign...');
  };

  const handleChangePlan = () => {
    // TODO: Implementar página de mudança de plano
    toast.info('Funcionalidade de mudança de plano em desenvolvimento');
  };

  if (loading) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-libresign-blue"></div>
      </div>
    );
  }

  if (!user || !plan || !stats) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <div className="text-center">
          <p className="text-gray-600">Erro ao carregar dados</p>
          <button
            onClick={() => router.push('/login')}
            className="mt-4 px-4 py-2 bg-libresign-blue text-white rounded-lg"
          >
            Voltar ao login
          </button>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-50">
      <Header userName={user.name} companyName={user.company_name} />

      <main className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {/* Cards de Estatísticas */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
          <StatCard
            title="Documentos do Mês"
            value={stats.documents_signed_this_month}
            showProgress={true}
            current={stats.documents_signed_this_month}
            limit={plan.documents_limit}
          />

          <StatCard
            title="Total de Documentos"
            value={stats.total_documents}
            subtitle="Todos os documentos assinados"
          />

          <StatCard
            title="Plano Atual"
            value={plan.name}
            subtitle={`R$ ${plan.price}/mês`}
            gradient={true}
          />
        </div>

        {/* Botão Admin (se for admin) */}
        {user.is_admin && (
          <div className="bg-gradient-to-r from-purple-600 to-blue-600 rounded-xl shadow-lg p-6 mb-8">
            <div className="flex items-center justify-between text-white">
              <div>
                <h3 className="text-xl font-bold mb-2">🔧 Painel Administrativo</h3>
                <p className="text-purple-100">Gerencie instâncias Nextcloud, usuários e métricas do sistema</p>
              </div>
              <button
                onClick={() => router.push('/admin')}
                className="px-6 py-3 bg-white text-purple-600 font-semibold rounded-lg hover:bg-purple-50 transition-colors"
              >
                Acessar Painel Admin
              </button>
            </div>
          </div>
        )}

        {/* Seções de Ação */}
        <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
          <ActionCard
            title="Plataforma de Assinatura"
            description="Acesse a plataforma LibreSign para assinar e gerenciar seus documentos"
            buttonText="Acessar Plataforma"
            onButtonClick={handleAccessPlatform}
            icon="📄"
          />

          <ActionCard
            title="Gerenciar Plano"
            description={`Plano ${plan.name} • Próxima renovação: ${new Date(
              plan.renewal_date
            ).toLocaleDateString('pt-BR')}`}
            buttonText="Alterar Plano"
            onButtonClick={handleChangePlan}
            icon="💳"
          />

          <ActionCard
            title="Segurança"
            description="Mantenha sua conta segura alterando sua senha regularmente"
            buttonText="Trocar Senha"
            onButtonClick={() => setIsPasswordModalOpen(true)}
            icon="🔐"
          />

          <ActionCard
            title="Informações da Conta"
            description={`Email: ${user.email}${
              user.phone ? ` • Tel: ${user.phone}` : ''
            }${user.role ? ` • Cargo: ${user.role}` : ''}`}
            buttonText="Ver Detalhes"
            onButtonClick={() =>
              toast.info('Funcionalidade em desenvolvimento')
            }
            icon="👤"
          />
        </div>

        {/* Informações Adicionais */}
        <div className="bg-white rounded-xl shadow-lg p-6">
          <h3 className="text-lg font-semibold text-gray-900 mb-4">
            Informações do Plano
          </h3>
          <div className="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
            <div>
              <p className="text-gray-600">Limite mensal</p>
              <p className="font-semibold text-gray-900">
                {plan.documents_limit} documentos
              </p>
            </div>
            <div>
              <p className="text-gray-600">Uso atual</p>
              <p className="font-semibold text-gray-900">
                {stats.documents_signed_this_month} documentos
              </p>
            </div>
            <div>
              <p className="text-gray-600">Disponível</p>
              <p className="font-semibold text-gray-900">
                {plan.documents_limit - stats.documents_signed_this_month}{' '}
                documentos
              </p>
            </div>
          </div>
        </div>
      </main>

      <ChangePasswordModal
        isOpen={isPasswordModalOpen}
        onClose={() => setIsPasswordModalOpen(false)}
      />
    </div>
  );
}
