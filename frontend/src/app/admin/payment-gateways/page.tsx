'use client';

import { useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import { api } from '@/services/api';
import toast from 'react-hot-toast';

export default function PaymentGatewaysPage() {
  const router = useRouter();
  const [loading, setLoading] = useState(true);
  const [gateways, setGateways] = useState<any[]>([]);
  const [selectedGateway, setSelectedGateway] = useState<any>(null);
  const [showModal, setShowModal] = useState(false);

  useEffect(() => {
    loadGateways();
  }, []);

  const loadGateways = async () => {
    try {
      setLoading(true);
      const data = await api.getPaymentGateways();
      setGateways(data);
    } catch (error: any) {
      toast.error(error.message);
    } finally {
      setLoading(false);
    }
  };

  const handleToggle = async (gateway: any) => {
    try {
      await api.togglePaymentGateway(gateway.id);
      toast.success(`${gateway.name} ${gateway.is_active ? 'desativado' : 'ativado'} com sucesso`);
      loadGateways();
    } catch (error: any) {
      toast.error(error.message);
    }
  };

  const handleEdit = (gateway: any) => {
    setSelectedGateway(gateway);
    setShowModal(true);
  };

  const handleSave = async (data: any) => {
    try {
      await api.updatePaymentGateway(selectedGateway.id, data);
      toast.success('Gateway atualizado com sucesso');
      setShowModal(false);
      setSelectedGateway(null);
      loadGateways();
    } catch (error: any) {
      toast.error(error.message);
    }
  };

  return (
    <div className="min-h-screen bg-gray-50 p-6">
      <div className="max-w-6xl mx-auto">
        {/* Header */}
        <div className="mb-6">
          <div className="flex items-center gap-4 mb-2">
            <button
              onClick={() => router.push('/admin')}
              className="text-gray-600 hover:text-gray-900"
            >
              ← Voltar
            </button>
          </div>
          <h1 className="text-3xl font-bold text-gray-900">Meios de Pagamento</h1>
          <p className="text-gray-600 mt-1">Configure os gateways de pagamento disponíveis</p>
        </div>

        {loading ? (
          <div className="flex items-center justify-center p-12">
            <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
          </div>
        ) : (
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            {gateways.map((gateway) => (
              <GatewayCard
                key={gateway.id}
                gateway={gateway}
                onToggle={() => handleToggle(gateway)}
                onEdit={() => handleEdit(gateway)}
              />
            ))}
          </div>
        )}
      </div>

      {/* Modal de Configuração */}
      {showModal && selectedGateway && (
        <ConfigModal
          gateway={selectedGateway}
          onClose={() => {
            setShowModal(false);
            setSelectedGateway(null);
          }}
          onSave={handleSave}
        />
      )}
    </div>
  );
}

function GatewayCard({ gateway, onToggle, onEdit }: any) {
  const getIcon = (type: string) => {
    const icons: any = {
      pix: '💸',
      boleto: '📄',
      credit_card: '💳',
      paypal: '🅿️',
      stripe: '💎',
    };
    return icons[type] || '💰';
  };

  const getTypeLabel = (type: string) => {
    const labels: any = {
      pix: 'PIX',
      boleto: 'Boleto',
      credit_card: 'Cartão de Crédito',
      paypal: 'PayPal',
      stripe: 'Stripe',
    };
    return labels[type] || type;
  };

  return (
    <div className={`bg-white rounded-lg shadow p-6 border-2 ${gateway.is_active ? 'border-green-500' : 'border-gray-200'}`}>
      <div className="flex items-start justify-between mb-4">
        <div className="flex items-center gap-3">
          <span className="text-4xl">{getIcon(gateway.type)}</span>
          <div>
            <h3 className="text-xl font-bold text-gray-900">{gateway.name}</h3>
            <span className="text-sm text-gray-500">{getTypeLabel(gateway.type)}</span>
          </div>
        </div>
        <label className="relative inline-flex items-center cursor-pointer">
          <input
            type="checkbox"
            checked={gateway.is_active}
            onChange={onToggle}
            className="sr-only peer"
          />
          <div className="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-600"></div>
        </label>
      </div>

      <p className="text-gray-600 text-sm mb-4">{gateway.description}</p>

      <div className="flex items-center justify-between pt-4 border-t">
        <div className="flex items-center gap-2">
          {gateway.is_active ? (
            <span className="px-3 py-1 bg-green-100 text-green-800 rounded-full text-xs font-medium">
              Ativo
            </span>
          ) : (
            <span className="px-3 py-1 bg-gray-100 text-gray-800 rounded-full text-xs font-medium">
              Inativo
            </span>
          )}
        </div>
        <button
          onClick={onEdit}
          className="px-4 py-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors text-sm font-medium"
        >
          ⚙️ Configurar
        </button>
      </div>
    </div>
  );
}

function ConfigModal({ gateway, onClose, onSave }: any) {
  const [formData, setFormData] = useState({
    name: gateway.name,
    description: gateway.description,
    settings: gateway.settings || {},
  });

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    onSave(formData);
  };

  const updateSetting = (key: string, value: string) => {
    setFormData({
      ...formData,
      settings: {
        ...formData.settings,
        [key]: value,
      },
    });
  };

  const renderSettingsFields = () => {
    const type = gateway.type;

    if (type === 'pix' || type === 'boleto') {
      return (
        <>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">
              Client ID
            </label>
            <input
              type="text"
              value={formData.settings.client_id || ''}
              onChange={(e) => updateSetting('client_id', e.target.value)}
              className="w-full px-3 py-2 border rounded-lg"
              placeholder="Seu Client ID do Sicoob"
            />
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">
              Certificado Path
            </label>
            <input
              type="text"
              value={formData.settings.certificate_path || ''}
              onChange={(e) => updateSetting('certificate_path', e.target.value)}
              className="w-full px-3 py-2 border rounded-lg"
              placeholder="/path/to/certificate.pem"
            />
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">
              Código do Beneficiário
            </label>
            <input
              type="text"
              value={formData.settings.beneficiary_code || ''}
              onChange={(e) => updateSetting('beneficiary_code', e.target.value)}
              className="w-full px-3 py-2 border rounded-lg"
            />
          </div>
          {type === 'pix' && (
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">
                Chave PIX
              </label>
              <input
                type="text"
                value={formData.settings.key_pix || ''}
                onChange={(e) => updateSetting('key_pix', e.target.value)}
                className="w-full px-3 py-2 border rounded-lg"
                placeholder="sua-chave@pix.com"
              />
            </div>
          )}
        </>
      );
    }

    if (type === 'credit_card' || gateway.slug === 'stripe') {
      return (
        <>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">
              Publishable Key
            </label>
            <input
              type="text"
              value={formData.settings.publishable_key || ''}
              onChange={(e) => updateSetting('publishable_key', e.target.value)}
              className="w-full px-3 py-2 border rounded-lg"
              placeholder="pk_..."
            />
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">
              Secret Key
            </label>
            <input
              type="password"
              value={formData.settings.secret_key || ''}
              onChange={(e) => updateSetting('secret_key', e.target.value)}
              className="w-full px-3 py-2 border rounded-lg"
              placeholder="sk_..."
            />
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">
              Webhook Secret
            </label>
            <input
              type="password"
              value={formData.settings.webhook_secret || ''}
              onChange={(e) => updateSetting('webhook_secret', e.target.value)}
              className="w-full px-3 py-2 border rounded-lg"
              placeholder="whsec_..."
            />
          </div>
        </>
      );
    }

    if (type === 'paypal') {
      return (
        <>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">
              Client ID
            </label>
            <input
              type="text"
              value={formData.settings.client_id || ''}
              onChange={(e) => updateSetting('client_id', e.target.value)}
              className="w-full px-3 py-2 border rounded-lg"
            />
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">
              Secret
            </label>
            <input
              type="password"
              value={formData.settings.secret || ''}
              onChange={(e) => updateSetting('secret', e.target.value)}
              className="w-full px-3 py-2 border rounded-lg"
            />
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">
              Modo
            </label>
            <select
              value={formData.settings.mode || 'sandbox'}
              onChange={(e) => updateSetting('mode', e.target.value)}
              className="w-full px-3 py-2 border rounded-lg"
            >
              <option value="sandbox">Sandbox (Teste)</option>
              <option value="live">Live (Produção)</option>
            </select>
          </div>
        </>
      );
    }

    return null;
  };

  return (
    <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div className="bg-white rounded-lg max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <div className="p-6">
          <div className="flex items-center justify-between mb-6">
            <h2 className="text-2xl font-bold text-gray-900">
              Configurar {gateway.name}
            </h2>
            <button
              onClick={onClose}
              className="text-gray-400 hover:text-gray-600"
            >
              ✕
            </button>
          </div>

          <form onSubmit={handleSubmit} className="space-y-6">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">
                Nome
              </label>
              <input
                type="text"
                value={formData.name}
                onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                className="w-full px-3 py-2 border rounded-lg"
              />
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">
                Descrição
              </label>
              <textarea
                value={formData.description}
                onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                className="w-full px-3 py-2 border rounded-lg"
                rows={3}
              />
            </div>

            <div className="border-t pt-6">
              <h3 className="text-lg font-semibold mb-4">Configurações</h3>
              <div className="space-y-4">
                {renderSettingsFields()}
              </div>
            </div>

            <div className="flex gap-4 pt-6 border-t">
              <button
                type="button"
                onClick={onClose}
                className="flex-1 px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50"
              >
                Cancelar
              </button>
              <button
                type="submit"
                className="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
              >
                Salvar Configurações
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  );
}
