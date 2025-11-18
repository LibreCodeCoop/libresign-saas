'use client';

import { useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import { api } from '@/services/api';
import toast from 'react-hot-toast';

export default function AdminDashboard() {
  const router = useRouter();
  const [loading, setLoading] = useState(true);
  const [dashboard, setDashboard] = useState<any>(null);

  useEffect(() => {
    loadDashboard();
  }, []);

  const loadDashboard = async () => {
    try {
      const data = await api.getAdminDashboard();
      setDashboard(data);
    } catch (error: any) {
      toast.error(error.message);
      if (error.message.includes('negado')) {
        router.push('/dashboard');
      }
    } finally {
      setLoading(false);
    }
  };

  if (loading) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <div className="text-center">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-libresign-blue mx-auto"></div>
          <p className="mt-4 text-gray-600">Carregando dashboard...</p>
        </div>
      </div>
    );
  }

  const metrics = dashboard?.metrics || {};
  const alerts = dashboard?.alerts || [];

  return (
    <div className="min-h-screen bg-gray-50 p-6">
      <div className="max-w-7xl mx-auto">
        <div className="mb-8">
          <h1 className="text-3xl font-bold text-gray-900">Painel Administrativo</h1>
          <p className="text-gray-600 mt-2">Gerenciamento de Instâncias Nextcloud</p>
        </div>

        {/* Métricas Globais */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
          <MetricCard
            title="Total de Instâncias"
            value={metrics.total_instances || 0}
            subtitle={`${metrics.running_instances || 0} ativos`}
            color="blue"
            icon="🖥️"
          />
          <MetricCard
            title="Usuários SaaS"
            value={metrics.total_users || 0}
            subtitle={`${metrics.active_users || 0} ativos no Nextcloud`}
            color="green"
            icon="👥"
          />
          <MetricCard
            title="Storage Usuários"
            value={formatBytes(metrics.total_storage_used || 0)}
            subtitle={`de ${formatBytes(metrics.total_storage_quota || 0)} quota`}
            color="purple"
            icon="💾"
          />
          <MetricCard
            title="Uso Médio CPU"
            value={`${metrics.avg_cpu_usage || 0}%`}
            subtitle="Instâncias Nextcloud"
            color="orange"
            icon="⚡"
          />
        </div>

        {/* Alertas Críticos */}
        {alerts.length > 0 && (
          <div className="bg-red-50 border-l-4 border-red-400 p-4 mb-8">
            <div className="flex">
              <div className="ml-3">
                <h3 className="text-sm font-medium text-red-800">
                  {alerts.length} Alerta{alerts.length > 1 ? 's' : ''} Crítico{alerts.length > 1 ? 's' : ''}
                </h3>
                <div className="mt-2 text-sm text-red-700 space-y-1">
                  {alerts.slice(0, 5).map((alert: any, idx: number) => (
                    <div key={idx}>
                      • {alert.instance_name}: {alert.alert.message || 'Alerta'}
                    </div>
                  ))}
                </div>
              </div>
            </div>
          </div>
        )}

        {/* Actions */}
        <div className="flex gap-4 mb-8">
          <button
            onClick={() => router.push('/admin/instances')}
            className="px-4 py-2 bg-libresign-blue text-white rounded-lg hover:bg-blue-700 transition-colors"
          >
            Ver Todas as Instâncias
          </button>
          <button
            onClick={() => router.push('/admin/instances/new')}
            className="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors"
          >
            + Nova Instância
          </button>
          <button
            onClick={loadDashboard}
            className="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors"
          >
            🔄 Atualizar
          </button>
        </div>

        {/* Grid de 2 colunas */}
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
          {/* Usuários por Plano */}
          <div className="bg-white rounded-lg shadow p-6">
            <h2 className="text-xl font-semibold mb-4">📊 Usuários por Plano</h2>
            <div className="space-y-3">
              {dashboard?.users_by_plan?.map((planData: any) => (
                <div key={planData.plan_id} className="p-4 border rounded-lg">
                  <div className="flex justify-between items-center mb-2">
                    <h3 className="font-medium capitalize">{planData.plan_name}</h3>
                    <span className="text-2xl font-bold text-blue-600">{planData.users_count}</span>
                  </div>
                  <div className="text-sm text-gray-600">
                    <div>Storage usado: {formatBytes(planData.storage_used)}</div>
                    <div>Quota total: {formatBytes(planData.storage_quota)}</div>
                  </div>
                  {planData.storage_quota > 0 && (
                    <div className="mt-2">
                      <div className="h-2 bg-gray-200 rounded-full overflow-hidden">
                        <div 
                          className="h-full bg-blue-600"
                          style={{ width: `${Math.min((planData.storage_used / planData.storage_quota) * 100, 100)}%` }}
                        />
                      </div>
                    </div>
                  )}
                </div>
              ))}
            </div>
          </div>

          {/* Usuários por Instância */}
          <div className="bg-white rounded-lg shadow p-6">
            <h2 className="text-xl font-semibold mb-4">🖥️ Usuários por Instância</h2>
            <div className="space-y-3">
              {dashboard?.users_by_instance?.map((instanceData: any) => (
                <div 
                  key={instanceData.instance_id} 
                  className="p-4 border rounded-lg hover:bg-gray-50 cursor-pointer"
                  onClick={() => router.push(`/admin/instances/${instanceData.instance_id}`)}
                >
                  <div className="flex justify-between items-center mb-2">
                    <div>
                      <h3 className="font-medium">{instanceData.instance_name}</h3>
                      <p className="text-sm text-gray-600">{instanceData.instance_url}</p>
                    </div>
                    <span className="text-2xl font-bold text-green-600">{instanceData.users_count}</span>
                  </div>
                  <div className="text-sm text-gray-600">
                    Storage usado: {formatBytes(instanceData.storage_used)}
                  </div>
                </div>
              ))}
            </div>
          </div>
        </div>

        {/* Usuários Recentes */}
        <div className="bg-white rounded-lg shadow p-6 mb-8">
          <h2 className="text-xl font-semibold mb-4">👤 Usuários Recentes</h2>
          <div className="overflow-x-auto">
            <table className="min-w-full divide-y divide-gray-200">
              <thead className="bg-gray-50">
                <tr>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nome</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Plano</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Instância</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Storage</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cadastro</th>
                </tr>
              </thead>
              <tbody className="bg-white divide-y divide-gray-200">
                {dashboard?.recent_users?.map((user: any) => (
                  <tr key={user.id} className="hover:bg-gray-50">
                    <td className="px-6 py-4 text-sm font-medium text-gray-900">{user.name}</td>
                    <td className="px-6 py-4 text-sm text-gray-600">{user.email}</td>
                    <td className="px-6 py-4 text-sm text-gray-900">{user.plan_name}</td>
                    <td className="px-6 py-4 text-sm text-gray-600">{user.instance_name}</td>
                    <td className="px-6 py-4 text-sm text-gray-900">{formatBytes(user.storage_used || 0)}</td>
                    <td className="px-6 py-4">
                      <NextcloudStatusBadge status={user.nextcloud_status} />
                    </td>
                    <td className="px-6 py-4 text-sm text-gray-600">
                      {new Date(user.created_at).toLocaleDateString('pt-BR')}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>

        {/* Instâncias Recentes */}
        <div className="bg-white rounded-lg shadow p-6">
          <h2 className="text-xl font-semibold mb-4">Instâncias Nextcloud</h2>
          <div className="space-y-3">
            {dashboard?.recent_instances?.slice(0, 5).map((instance: any) => (
              <div
                key={instance.id}
                className="flex items-center justify-between p-4 border rounded-lg hover:bg-gray-50 cursor-pointer"
                onClick={() => router.push(`/admin/instances/${instance.id}`)}
              >
                <div>
                  <h3 className="font-medium">{instance.name}</h3>
                  <p className="text-sm text-gray-600">{instance.url}</p>
                </div>
                <StatusBadge status={instance.status} />
              </div>
            ))}
          </div>
        </div>
      </div>
    </div>
  );
}

function MetricCard({ title, value, subtitle, color, icon }: any) {
  const colorClasses = {
    blue: 'bg-blue-50 text-blue-600',
    green: 'bg-green-50 text-green-600',
    orange: 'bg-orange-50 text-orange-600',
    purple: 'bg-purple-50 text-purple-600',
  };

  return (
    <div className="bg-white rounded-lg shadow p-6">
      <div className="flex items-center justify-between mb-2">
        <h3 className="text-sm font-medium text-gray-600">{title}</h3>
        {icon && <span className="text-2xl">{icon}</span>}
      </div>
      <p className={`text-3xl font-bold ${colorClasses[color as keyof typeof colorClasses]}`}>
        {value}
      </p>
      <p className="text-sm text-gray-500 mt-1">{subtitle}</p>
    </div>
  );
}

function StatusBadge({ status }: { status: string }) {
  const statusConfig: any = {
    active: { label: 'Ativo', color: 'bg-green-100 text-green-800' },
    inactive: { label: 'Inativo', color: 'bg-gray-100 text-gray-800' },
    error: { label: 'Erro', color: 'bg-red-100 text-red-800' },
    maintenance: { label: 'Manutenção', color: 'bg-yellow-100 text-yellow-800' },
  };

  const config = statusConfig[status] || statusConfig.inactive;

  return (
    <span className={`px-3 py-1 rounded-full text-xs font-medium ${config.color}`}>
      {config.label}
    </span>
  );
}

function NextcloudStatusBadge({ status }: { status: string }) {
  const statusConfig: any = {
    active: { label: 'Ativo', color: 'bg-green-100 text-green-800' },
    pending: { label: 'Pendente', color: 'bg-yellow-100 text-yellow-800' },
    creating: { label: 'Criando', color: 'bg-blue-100 text-blue-800' },
    failed: { label: 'Falhou', color: 'bg-red-100 text-red-800' },
  };

  const config = statusConfig[status] || { label: 'N/A', color: 'bg-gray-100 text-gray-800' };

  return (
    <span className={`px-2 py-1 rounded-full text-xs font-medium ${config.color}`}>
      {config.label}
    </span>
  );
}

function formatBytes(bytes: number) {
  if (bytes === 0) return '0 B';
  const k = 1024;
  const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
}
