'use client';

import { useState, useEffect } from 'react';
import toast from 'react-hot-toast';
import { api } from '@/services/api';

interface ChangePasswordModalProps {
  isOpen: boolean;
  onClose: () => void;
}

interface PasswordRequirements {
  minLength: boolean;
  hasUpperCase: boolean;
  hasLowerCase: boolean;
  hasNumber: boolean;
  hasSpecialChar: boolean;
}

export default function ChangePasswordModal({
  isOpen,
  onClose,
}: ChangePasswordModalProps) {
  const [currentPassword, setCurrentPassword] = useState('');
  const [newPassword, setNewPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');
  const [loading, setLoading] = useState(false);
  const [requirements, setRequirements] = useState<PasswordRequirements>({
    minLength: false,
    hasUpperCase: false,
    hasLowerCase: false,
    hasNumber: false,
    hasSpecialChar: false,
  });

  useEffect(() => {
    if (newPassword) {
      setRequirements({
        minLength: newPassword.length >= 8,
        hasUpperCase: /[A-Z]/.test(newPassword),
        hasLowerCase: /[a-z]/.test(newPassword),
        hasNumber: /[0-9]/.test(newPassword),
        hasSpecialChar: /[!@#$%^&*(),.?":{}|<>]/.test(newPassword),
      });
    }
  }, [newPassword]);

  const isPasswordValid = Object.values(requirements).every((req) => req);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();

    if (!isPasswordValid) {
      toast.error('A senha não atende aos requisitos mínimos');
      return;
    }

    if (newPassword !== confirmPassword) {
      toast.error('As senhas não coincidem');
      return;
    }

    setLoading(true);

    try {
      await api.changePassword({
        current_password: currentPassword,
        new_password: newPassword,
        new_password_confirmation: confirmPassword,
      });
      toast.success('Senha alterada com sucesso!');
      setCurrentPassword('');
      setNewPassword('');
      setConfirmPassword('');
      onClose();
    } catch (error: any) {
      toast.error(error.message || 'Erro ao trocar senha');
    } finally {
      setLoading(false);
    }
  };

  const handleClose = () => {
    setCurrentPassword('');
    setNewPassword('');
    setConfirmPassword('');
    onClose();
  };

  if (!isOpen) return null;

  return (
    <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div className="bg-white rounded-xl shadow-2xl max-w-md w-full p-6">
        <div className="flex justify-between items-center mb-6">
          <h2 className="text-2xl font-bold text-gray-900">Trocar Senha</h2>
          <button
            onClick={handleClose}
            className="text-gray-400 hover:text-gray-600 text-2xl"
          >
            ×
          </button>
        </div>

        <form onSubmit={handleSubmit} className="space-y-4">
          <div>
            <label
              htmlFor="current-password"
              className="block text-sm font-medium text-gray-700 mb-1"
            >
              Senha atual
            </label>
            <input
              id="current-password"
              type="password"
              value={currentPassword}
              onChange={(e) => setCurrentPassword(e.target.value)}
              className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-libresign-blue focus:border-libresign-blue"
              required
            />
          </div>

          <div>
            <label
              htmlFor="new-password"
              className="block text-sm font-medium text-gray-700 mb-1"
            >
              Nova senha
            </label>
            <input
              id="new-password"
              type="password"
              value={newPassword}
              onChange={(e) => setNewPassword(e.target.value)}
              className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-libresign-blue focus:border-libresign-blue"
              required
            />
          </div>

          <div>
            <label
              htmlFor="confirm-password"
              className="block text-sm font-medium text-gray-700 mb-1"
            >
              Confirmar nova senha
            </label>
            <input
              id="confirm-password"
              type="password"
              value={confirmPassword}
              onChange={(e) => setConfirmPassword(e.target.value)}
              className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-libresign-blue focus:border-libresign-blue"
              required
            />
          </div>

          {newPassword && (
            <div className="bg-gray-50 p-4 rounded-lg">
              <p className="text-sm font-medium text-gray-700 mb-2">
                Requisitos da senha:
              </p>
              <ul className="space-y-1 text-sm">
                <li
                  className={`flex items-center ${
                    requirements.minLength ? 'text-green-600' : 'text-gray-500'
                  }`}
                >
                  <span className="mr-2">
                    {requirements.minLength ? '✓' : '○'}
                  </span>
                  Mínimo 8 caracteres
                </li>
                <li
                  className={`flex items-center ${
                    requirements.hasUpperCase
                      ? 'text-green-600'
                      : 'text-gray-500'
                  }`}
                >
                  <span className="mr-2">
                    {requirements.hasUpperCase ? '✓' : '○'}
                  </span>
                  Letra maiúscula
                </li>
                <li
                  className={`flex items-center ${
                    requirements.hasLowerCase
                      ? 'text-green-600'
                      : 'text-gray-500'
                  }`}
                >
                  <span className="mr-2">
                    {requirements.hasLowerCase ? '✓' : '○'}
                  </span>
                  Letra minúscula
                </li>
                <li
                  className={`flex items-center ${
                    requirements.hasNumber ? 'text-green-600' : 'text-gray-500'
                  }`}
                >
                  <span className="mr-2">
                    {requirements.hasNumber ? '✓' : '○'}
                  </span>
                  Número
                </li>
                <li
                  className={`flex items-center ${
                    requirements.hasSpecialChar
                      ? 'text-green-600'
                      : 'text-gray-500'
                  }`}
                >
                  <span className="mr-2">
                    {requirements.hasSpecialChar ? '✓' : '○'}
                  </span>
                  Caractere especial
                </li>
              </ul>
            </div>
          )}

          <div className="flex gap-3 mt-6">
            <button
              type="button"
              onClick={handleClose}
              className="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors font-medium"
            >
              Cancelar
            </button>
            <button
              type="submit"
              disabled={loading || !isPasswordValid}
              className="flex-1 px-4 py-2 bg-libresign-blue text-white rounded-lg hover:bg-blue-700 transition-colors font-medium disabled:opacity-50 disabled:cursor-not-allowed"
            >
              {loading ? 'Salvando...' : 'Salvar'}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}
