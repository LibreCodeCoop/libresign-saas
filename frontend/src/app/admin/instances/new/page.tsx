'use client';

import { useState } from 'react';
import { useRouter } from 'next/navigation';
import { api } from '@/services/api';
import toast from 'react-hot-toast';

export default function NewInstancePage() {
  const router = useRouter();
  const [loading, setLoading] = useState(false);
  const [formData, setFormData] = useState({
    name: '',
    url: '',
    domain: '',
    api_username: '',
    api_password: '',
    plan: 'starter',
    max_users: 50,
    storage_allocated: 10737418240, // 10GB em bytes
    memory_allocated: 2147483648,   // 2GB em bytes
  });

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    
    if (!formData.url || !formData.api_username || !formData.api_password) {
      toast.error('Por favor, preencha todos os campos obrigatórios');
      return;
    }

    // Validar URL
    try {
      new URL(formData.url);
    } catch {
      toast.error('URL inválida. Use o formato: https://cloud.example.com');
      return;
    }

    setLoading(true);

    try {
      const instanceData = {
        ...formData,
        management_method: 'api',
        // Se domain não foi preenchido, extrair da URL
        domain: formData.domain || new URL(formData.url).hostname,
      };

      const response = await api.createInstance(instanceData);
      
      toast.success('Instância criada com sucesso!');
      router.push(`/admin/instances/${response.instance.id}`);
    } catch (error: any) {
      toast.error(error.message || 'Erro ao criar instância');
    } finally {
      setLoading(false);
    }
  };

  const handleCancel = () => {
    if (confirm('Deseja cancelar? Os dados preenchidos serão perdidos.')) {
      router.back();
    }
  };

  return (
    <div className="min-h-screen bg-gray-50 p-6">
      <div className="max-w-3xl mx-auto">
        {/* Header */}
        <div className="mb-8">
          <button
            onClick={handleCancel}
            className="mb-4 text-blue-600 hover:text-blue-800"
          >
            ← Voltar
          </button>
          <h1 className="text-3xl font-bold text-gray-900">Nova Instância Nextcloud</h1>
          <p className="text-gray-600 mt-2">
            Conecte uma instância Nextcloud existente usando as credenciais da API
          </p>
        </div>

        {/* Form */}
        <form onSubmit={handleSubmit} className="bg-white rounded-lg shadow-lg p-8 space-y-6">
          {/* URL do Nextcloud */}
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">
              URL do Nextcloud *
            </label>
            <input
              type="url"
              required
              value={formData.url}
              onChange={(e) => setFormData({ ...formData, url: e.target.value })}
              placeholder="https://cloud.example.com"
              className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            />
            <p className="mt-1 text-sm text-gray-500">
              URL completa da sua instalação Nextcloud
            </p>
          </div>

          {/* Nome da Instância */}
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">
              Nome da Instância *
            </label>
            <input
              type="text"
              required
              value={formData.name}
              onChange={(e) => setFormData({ ...formData, name: e.target.value })}
              placeholder="Nextcloud Produção"
              className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            />
          </div>

          {/* Domínio (opcional) */}
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">
              Domínio (opcional)
            </label>
            <input
              type="text"
              value={formData.domain}
              onChange={(e) => setFormData({ ...formData, domain: e.target.value })}
              placeholder="cloud.example.com"
              className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            />
            <p className="mt-1 text-sm text-gray-500">
              Será extraído automaticamente da URL se não for fornecido
            </p>
          </div>

          {/* Credenciais da API */}
          <div className="border-t pt-6">
            <h3 className="text-lg font-semibold mb-4">Credenciais da API Nextcloud</h3>
            
            <div className="space-y-4">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Usuário Admin *
                </label>
                <input
                  type="text"
                  required
                  value={formData.api_username}
                  onChange={(e) => setFormData({ ...formData, api_username: e.target.value })}
                  placeholder="admin"
                  className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Senha ou Token da API *
                </label>
                <input
                  type="password"
                  required
                  value={formData.api_password}
                  onChange={(e) => setFormData({ ...formData, api_password: e.target.value })}
                  placeholder="••••••••••••••••"
                  className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
                <p className="mt-1 text-sm text-gray-500">
                  Recomendado usar um token de aplicativo ao invés da senha
                </p>
              </div>
            </div>
          </div>

          {/* Configurações */}
          <div className="border-t pt-6">
            <h3 className="text-lg font-semibold mb-4">Configurações</h3>
            
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Plano
                </label>
                <select
                  value={formData.plan}
                  onChange={(e) => setFormData({ ...formData, plan: e.target.value })}
                  className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                >
                  <option value="starter">Starter</option>
                  <option value="business">Business</option>
                  <option value="enterprise">Enterprise</option>
                </select>
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Máximo de Usuários
                </label>
                <input
                  type="number"
                  min="1"
                  value={formData.max_users}
                  onChange={(e) => setFormData({ ...formData, max_users: parseInt(e.target.value) })}
                  className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Storage Alocado (GB)
                </label>
                <input
                  type="number"
                  min="1"
                  value={formData.storage_allocated / 1073741824} // Converter bytes para GB
                  onChange={(e) => setFormData({ 
                    ...formData, 
                    storage_allocated: parseInt(e.target.value) * 1073741824 
                  })}
                  className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Memória Alocada (GB)
                </label>
                <input
                  type="number"
                  min="1"
                  step="0.5"
                  value={formData.memory_allocated / 1073741824} // Converter bytes para GB
                  onChange={(e) => setFormData({ 
                    ...formData, 
                    memory_allocated: Math.floor(parseFloat(e.target.value) * 1073741824)
                  })}
                  className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
              </div>
            </div>
          </div>

          {/* Info Box */}
          <div className="bg-blue-50 border-l-4 border-blue-400 p-4">
            <div className="flex">
              <div className="ml-3">
                <p className="text-sm text-blue-700">
                  <strong>Como obter as credenciais:</strong><br />
                  1. Acesse seu Nextcloud como administrador<br />
                  2. Vá em Configurações → Segurança → Tokens de dispositivos/app<br />
                  3. Crie um novo token de aplicativo<br />
                  4. Use o nome de usuário e o token gerado aqui
                </p>
              </div>
            </div>
          </div>

          {/* Buttons */}
          <div className="flex gap-4 pt-6 border-t">
            <button
              type="submit"
              disabled={loading}
              className="flex-1 px-6 py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
            >
              {loading ? 'Criando...' : 'Criar Instância'}
            </button>
            <button
              type="button"
              onClick={handleCancel}
              disabled={loading}
              className="px-6 py-3 bg-gray-200 text-gray-700 font-semibold rounded-lg hover:bg-gray-300 disabled:opacity-50 transition-colors"
            >
              Cancelar
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}
