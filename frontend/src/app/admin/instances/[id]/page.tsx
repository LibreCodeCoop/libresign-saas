'use client';

import { use, useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import { api } from '@/services/api';
import toast from 'react-hot-toast';
import MetricsChart, { SystemMetricsChart, UserActivityChart, formatBytes } from '@/components/admin/MetricsChart';
import LogsViewer from '@/components/admin/LogsViewer';

export default function InstanceDetailsPage({ params }: { params: Promise<{ id: string }> }) {
  const router = useRouter();
  const [loading, setLoading] = useState(true);
  const [instance, setInstance] = useState<any>(null);
  const [metrics, setMetrics] = useState<any>(null);
  const [logs, setLogs] = useState<any[]>([]);
  const [activeTab, setActiveTab] = useState<'overview' | 'metrics' | 'logs'>('overview');
  const [instanceId, setInstanceId] = useState<string | null>(null);

  useEffect(() => {
    params.then((resolvedParams) => {
      setInstanceId(resolvedParams.id);
    });
  }, [params]);

  useEffect(() => {
    if (instanceId) {
      loadInstance();
      loadMetrics();
      loadLogs();
    }
  }, [instanceId]);

  const loadInstance = async () => {
    try {
      const data = await api.getInstance(Number(instanceId));
      setInstance(data);
    } catch (error: any) {
      toast.error(error.message);
      router.push('/admin/instances');
    } finally {
      setLoading(false);
    }
  };

  const loadMetrics = async () => {
    try {
      const data = await api.getInstanceMetrics(Number(instanceId));
      setMetrics(data);
    } catch (error: any) {
      console.error('Erro ao carregar métricas:', error);
      // Define métricas vazias para não quebrar a UI
      setMetrics({ real_time: {}, historical: {} });
    }
  };

  const loadLogs = async () => {
    try {
      const data = await api.getInstanceLogs(Number(instanceId));
      setLogs(data.logs || []);
    } catch (error: any) {
      console.error('Erro ao carregar logs:', error);
      // Define array vazio para não quebrar a UI
      setLogs([]);
    }
  };

  const handleAction = async (action: string) => {
    if (!confirm(`Tem certeza que deseja ${action} esta instância?`)) {
      return;
    }

    try {
      await api.instanceAction(Number(instanceId), action);
      toast.success(`Ação ${action} executada com sucesso`);
      loadInstance();
      loadMetrics();
    } catch (error: any) {
      toast.error(error.message);
    }
  };

  const handleCollectMetrics = async () => {
    try {
      toast.loading('Coletando métricas...', { id: 'collect' });
      const response = await fetch(`${process.env.NEXT_PUBLIC_API_URL}/api/admin/instances/${instanceId}/collect-metrics`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${document.cookie.split('token=')[1]?.split(';')[0]}`,
          'Content-Type': 'application/json',
        },
      });
      
      if (response.ok) {
        toast.success('Métricas coletadas!', { id: 'collect' });
        loadInstance();
        loadMetrics();
      } else {
        toast.error('Erro ao coletar métricas', { id: 'collect' });
      }
    } catch (error: any) {
      toast.error(error.message, { id: 'collect' });
    }
  };

  if (loading) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <div className="text-center">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
          <p className="mt-4 text-gray-600">Carregando instância...</p>
        </div>
      </div>
    );
  }

  if (!instance) {
    return null; // Or a more elaborate placeholder if desired
  }

  const realTime = metrics?.real_time || {};
  const historical = metrics?.historical || {};

  return (
    <div className="min-h-screen bg-gray-50 p-6">
      <div className="max-w-7xl mx-auto">
        {/* Header */}
        <div className="mb-6">
          <button
            onClick={() => router.back()}
            className="mb-4 text-blue-600 hover:text-blue-800"
          >
            ← Voltar
          </button>
          <div className="flex justify-between items-start">
            <div>
              <h1 className="text-3xl font-bold text-gray-900">{instance.name}</h1>
              <p className="text-gray-600 mt-1">{instance.url}</p>
              <div className="flex gap-3 mt-3">
                <StatusBadge status={instance.status} />
                <span className="px-3 py-1 bg-purple-100 text-purple-800 rounded-full text-sm font-medium capitalize">
                  {instance.plan}
                </span>
                {instance.version && (
                  <span className="px-3 py-1 bg-gray-100 text-gray-800 rounded-full text-sm font-medium">
                    v{instance.version}
                  </span>
                )}
              </div>
            </div>
            
            {/* Action Buttons */}
            <div className="flex gap-2">
              <button
                onClick={() => handleAction('start')}
                className="px-3 py-2 bg-green-600 text-white rounded hover:bg-green-700 text-sm"
              >
                ▶️ Iniciar
              </button>
              <button
                onClick={() => handleAction('stop')}
                className="px-3 py-2 bg-red-600 text-white rounded hover:bg-red-700 text-sm"
              >
                ⏹️ Parar
              </button>
              <button
                onClick={() => handleAction('restart')}
                className="px-3 py-2 bg-orange-600 text-white rounded hover:bg-orange-700 text-sm"
              >
                🔄 Reiniciar
              </button>
              <button
                onClick={() => handleAction('backup')}
                className="px-3 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm"
              >
                💾 Backup
              </button>
              <button
                onClick={handleCollectMetrics}
                className="px-3 py-2 bg-purple-600 text-white rounded hover:bg-purple-700 text-sm"
              >
                📊 Coletar Métricas
              </button>
            </div>
          </div>
        </div>

        {/* Tabs */}
        <div className="border-b border-gray-200 mb-6">
          <nav className="flex space-x-8">
            {['overview', 'metrics', 'logs'].map((tab) => (
              <button
                key={tab}
                onClick={() => setActiveTab(tab as any)}
                className={`py-4 px-1 border-b-2 font-medium text-sm ${
                  activeTab === tab
                    ? 'border-blue-500 text-blue-600'
                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                }`}
              >
                {tab === 'overview' ? 'Visão Geral' : tab === 'metrics' ? 'Métricas' : 'Logs'}
              </button>
            ))}
          </nav>
        </div>

        {/* Content */}
        {activeTab === 'overview' && (
          <div className="space-y-6">
            {/* Real-time Metrics */}
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
              <MetricCard title="CPU" value={`${realTime.cpu_usage?.toFixed(1) || 0}%`} color="red" />
              <MetricCard title="Memória" value={`${realTime.memory_usage?.toFixed(1) || 0}%`} color="blue" />
              <MetricCard 
                title="Storage" 
                value={formatBytes(realTime.storage_used || 0)}
                subtitle={`${realTime.storage_percentage || 0}% usado`}
                color="purple" 
              />
              <MetricCard 
                title="Usuários" 
                value={`${instance.current_users || 0} / ${instance.max_users || 0}`}
                subtitle={`${realTime.active_users || 0} ativos agora`}
                color="green" 
              />
            </div>

            {/* Instance Info */}
            <div className="bg-white rounded-lg shadow p-6">
              <h3 className="text-lg font-semibold mb-4">Informações da Instância</h3>
              <div className="grid grid-cols-2 gap-4 text-sm">
                <div>
                  <span className="text-gray-600">ID:</span>
                  <span className="ml-2 font-medium">{instance.id}</span>
                </div>
                <div>
                  <span className="text-gray-600">Método de Gerenciamento:</span>
                  <span className="ml-2 font-medium capitalize">{instance.management_method}</span>
                </div>
                <div>
                  <span className="text-gray-600">Criado em:</span>
                  <span className="ml-2 font-medium">{new Date(instance.created_at).toLocaleString('pt-BR')}</span>
                </div>
                <div>
                  <span className="text-gray-600">Último Backup:</span>
                  <span className="ml-2 font-medium">
                    {instance.last_backup ? new Date(instance.last_backup).toLocaleString('pt-BR') : 'Nunca'}
                  </span>
                </div>
              </div>
            </div>

            {/* Alerts */}
            {instance.alerts && instance.alerts.length > 0 && (
              <div className="bg-red-50 border-l-4 border-red-400 p-4">
                <h3 className="text-sm font-medium text-red-800 mb-2">Alertas Ativos</h3>
                <div className="space-y-1">
                  {instance.alerts.map((alert: any, idx: number) => (
                    <div key={idx} className="text-sm text-red-700">
                      • [{alert.type.toUpperCase()}] {alert.message}
                    </div>
                  ))}
                </div>
              </div>
            )}
          </div>
        )}

        {activeTab === 'metrics' && (
          <div className="space-y-6">
            {historical.response_times && historical.response_times.length > 0 ? (
              <>
                <SystemMetricsChart data={historical.response_times} />
                {historical.storage_growth && historical.storage_growth.length > 0 && (
                  <MetricsChart
                    data={historical.storage_growth}
                    title="Crescimento de Storage"
                    dataKey="value"
                    color="#8B5CF6"
                    valueFormatter={formatBytes}
                  />
                )}
                {historical.user_activity && historical.user_activity.length > 0 && (
                  <UserActivityChart data={historical.user_activity} />
                )}
              </>
            ) : (
              <div className="bg-white rounded-lg shadow p-12 text-center text-gray-600">
                Nenhum dado histórico disponível. Execute "Coletar Métricas" para começar.
              </div>
            )}
          </div>
        )}

        {activeTab === 'logs' && (
          <LogsViewer logs={logs} />
        )}
      </div>
    </div>
  );
}

function StatusBadge({ status }: { status: string }) {
  const config: any = {
    active: { label: 'Ativo', color: 'bg-green-100 text-green-800' },
    inactive: { label: 'Inativo', color: 'bg-gray-100 text-gray-800' },
    error: { label: 'Erro', color: 'bg-red-100 text-red-800' },
    maintenance: { label: 'Manutenção', color: 'bg-yellow-100 text-yellow-800' },
  };

  const { label, color } = config[status] || config.inactive;

  return (
    <span className={`px-3 py-1 rounded-full text-sm font-medium ${color}`}>
      {label}
    </span>
  );
}

function MetricCard({ title, value, subtitle, color }: any) {
  const colorClasses = {
    red: 'bg-red-50 text-red-600',
    blue: 'bg-blue-50 text-blue-600',
    purple: 'bg-purple-50 text-purple-600',
    green: 'bg-green-50 text-green-600',
  };

  return (
    <div className="bg-white rounded-lg shadow p-6">
      <h3 className="text-sm font-medium text-gray-600 mb-2">{title}</h3>
      <p className={`text-2xl font-bold ${colorClasses[color as keyof typeof colorClasses]}`}>
        {value}
      </p>
      {subtitle && <p className="text-sm text-gray-500 mt-1">{subtitle}</p>}
    </div>
  );
}
