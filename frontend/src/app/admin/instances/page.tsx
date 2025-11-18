'use client';

import { useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import { api } from '@/services/api';
import toast from 'react-hot-toast';

export default function InstancesListPage() {
  const router = useRouter();
  const [loading, setLoading] = useState(true);
  const [instances, setInstances] = useState<any[]>([]);
  const [pagination, setPagination] = useState<any>({});
  const [selectedIds, setSelectedIds] = useState<number[]>([]);
  
  // Filtros
  const [filters, setFilters] = useState({
    status: '',
    plan: '',
    search: '',
    page: 1,
  });

  useEffect(() => {
    loadInstances();
  }, [filters]);

  const loadInstances = async () => {
    try {
      setLoading(true);
      console.log('Loading instances with filters:', filters);
      const data = await api.getInstances(filters);
      console.log('Received data:', data);
      console.log('Instances data:', data.data);
      setInstances(data.data || []);
      setPagination({
        current_page: data.current_page,
        last_page: data.last_page,
        total: data.total,
        per_page: data.per_page,
      });
    } catch (error: any) {
      console.error('Error loading instances:', error);
      toast.error(error.message);
    } finally {
      setLoading(false);
    }
  };

  const handleBatchAction = async (action: string) => {
    if (selectedIds.length === 0) {
      toast.error('Selecione pelo menos uma instância');
      return;
    }

    if (!confirm(`Tem certeza que deseja ${action} ${selectedIds.length} instância(s)?`)) {
      return;
    }

    try {
      await api.batchInstanceAction(selectedIds, action);
      toast.success(`Ação ${action} executada com sucesso`);
      setSelectedIds([]);
      loadInstances();
    } catch (error: any) {
      toast.error(error.message);
    }
  };

  const toggleSelect = (id: number) => {
    setSelectedIds(prev =>
      prev.includes(id) ? prev.filter(i => i !== id) : [...prev, id]
    );
  };

  const toggleSelectAll = () => {
    if (selectedIds.length === instances.length) {
      setSelectedIds([]);
    } else {
      setSelectedIds(instances.map(i => i.id));
    }
  };

  return (
    <div className="min-h-screen bg-gray-50 p-6">
      <div className="max-w-7xl mx-auto">
        {/* Header */}
        <div className="flex justify-between items-center mb-6">
          <div>
            <h1 className="text-3xl font-bold text-gray-900">Instâncias Nextcloud</h1>
            <p className="text-gray-600 mt-1">Gerenciar todas as instâncias</p>
          </div>
          <button
            onClick={() => router.push('/admin/instances/new')}
            className="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700"
          >
            + Nova Instância
          </button>
        </div>

        {/* Filters */}
        <div className="bg-white rounded-lg shadow p-4 mb-6">
          <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
            <input
              type="text"
              placeholder="Buscar por nome, domínio ou URL..."
              value={filters.search}
              onChange={(e) => setFilters({ ...filters, search: e.target.value, page: 1 })}
              className="px-4 py-2 border rounded-lg"
            />
            <select
              value={filters.status}
              onChange={(e) => setFilters({ ...filters, status: e.target.value, page: 1 })}
              className="px-4 py-2 border rounded-lg"
            >
              <option value="">Todos os Status</option>
              <option value="active">Ativo</option>
              <option value="inactive">Inativo</option>
              <option value="error">Erro</option>
              <option value="maintenance">Manutenção</option>
            </select>
            <select
              value={filters.plan}
              onChange={(e) => setFilters({ ...filters, plan: e.target.value, page: 1 })}
              className="px-4 py-2 border rounded-lg"
            >
              <option value="">Todos os Planos</option>
              <option value="starter">Starter</option>
              <option value="business">Business</option>
              <option value="enterprise">Enterprise</option>
            </select>
            <button
              onClick={() => setFilters({ status: '', plan: '', search: '', page: 1 })}
              className="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300"
            >
              Limpar Filtros
            </button>
          </div>
        </div>

        {/* Batch Actions */}
        {selectedIds.length > 0 && (
          <div className="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
            <div className="flex items-center justify-between">
              <span className="text-blue-800 font-medium">
                {selectedIds.length} instância(s) selecionada(s)
              </span>
              <div className="flex gap-2">
                <button
                  onClick={() => handleBatchAction('start')}
                  className="px-3 py-1 bg-green-600 text-white rounded hover:bg-green-700 text-sm"
                >
                  ▶️ Iniciar
                </button>
                <button
                  onClick={() => handleBatchAction('stop')}
                  className="px-3 py-1 bg-red-600 text-white rounded hover:bg-red-700 text-sm"
                >
                  ⏹️ Parar
                </button>
                <button
                  onClick={() => handleBatchAction('restart')}
                  className="px-3 py-1 bg-orange-600 text-white rounded hover:bg-orange-700 text-sm"
                >
                  🔄 Reiniciar
                </button>
                <button
                  onClick={() => handleBatchAction('backup')}
                  className="px-3 py-1 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm"
                >
                  💾 Backup
                </button>
              </div>
            </div>
          </div>
        )}

        {/* Table */}
        <div className="bg-white rounded-lg shadow overflow-hidden">
          {loading ? (
            <div className="p-12 text-center">
              <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
              <p className="mt-4 text-gray-600">Carregando instâncias...</p>
            </div>
          ) : instances.length === 0 ? (
            <div className="p-12 text-center text-gray-600">
              Nenhuma instância encontrada
            </div>
          ) : (
            <>
              <table className="min-w-full divide-y divide-gray-200">
                <thead className="bg-gray-50">
                  <tr>
                    <th className="px-6 py-3 text-left">
                      <input
                        type="checkbox"
                        checked={selectedIds.length === instances.length}
                        onChange={toggleSelectAll}
                        className="rounded"
                      />
                    </th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                      Nome
                    </th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                      Status
                    </th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                      Plano
                    </th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                      Usuários
                    </th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                      Storage
                    </th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                      CPU/Mem
                    </th>
                    <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                      Ações
                    </th>
                  </tr>
                </thead>
                <tbody className="bg-white divide-y divide-gray-200">
                  {instances.map((instance) => (
                    <tr key={instance.id} className="hover:bg-gray-50">
                      <td className="px-6 py-4">
                        <input
                          type="checkbox"
                          checked={selectedIds.includes(instance.id)}
                          onChange={() => toggleSelect(instance.id)}
                          className="rounded"
                        />
                      </td>
                      <td className="px-6 py-4">
                        <div className="text-sm font-medium text-gray-900">{instance.name}</div>
                        <div className="text-sm text-gray-500">{instance.url}</div>
                      </td>
                      <td className="px-6 py-4">
                        <StatusBadge status={instance.status} />
                      </td>
                      <td className="px-6 py-4 text-sm text-gray-900 capitalize">
                        {instance.plan || 'N/A'}
                      </td>
                      <td className="px-6 py-4 text-sm text-gray-900">
                        {instance.current_users || 0} / {instance.max_users || 0}
                      </td>
                      <td className="px-6 py-4 text-sm text-gray-900">
                        {formatBytes(instance.storage_used || 0)}
                        <span className="text-gray-500"> / {formatBytes(instance.storage_allocated || 0)}</span>
                      </td>
                      <td className="px-6 py-4 text-sm text-gray-900">
                        <div>CPU: {instance.cpu_usage?.toFixed(1) || 0}%</div>
                        <div className="text-gray-500">Mem: {instance.memory_usage?.toFixed(1) || 0}%</div>
                      </td>
                      <td className="px-6 py-4 text-right text-sm font-medium space-x-2">
                        <button
                          onClick={() => router.push(`/admin/instances/${instance.id}`)}
                          className="text-blue-600 hover:text-blue-900"
                        >
                          Ver
                        </button>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>

              {/* Pagination */}
              {pagination.last_page > 1 && (
                <div className="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200">
                  <div className="text-sm text-gray-700">
                    Mostrando {instances.length} de {pagination.total} instâncias
                  </div>
                  <div className="flex gap-2">
                    <button
                      onClick={() => setFilters({ ...filters, page: filters.page - 1 })}
                      disabled={filters.page === 1}
                      className="px-3 py-1 border rounded disabled:opacity-50"
                    >
                      Anterior
                    </button>
                    <span className="px-3 py-1">
                      Página {pagination.current_page} de {pagination.last_page}
                    </span>
                    <button
                      onClick={() => setFilters({ ...filters, page: filters.page + 1 })}
                      disabled={filters.page === pagination.last_page}
                      className="px-3 py-1 border rounded disabled:opacity-50"
                    >
                      Próxima
                    </button>
                  </div>
                </div>
              )}
            </>
          )}
        </div>
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
    <span className={`px-2 py-1 rounded-full text-xs font-medium ${color}`}>
      {label}
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
