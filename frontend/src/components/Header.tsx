'use client';

import { useRouter } from 'next/navigation';
import Cookies from 'js-cookie';
import toast from 'react-hot-toast';
import { api } from '@/services/api';

interface HeaderProps {
  userName: string;
  companyName: string;
}

export default function Header({ userName, companyName }: HeaderProps) {
  const router = useRouter();

  const handleLogout = async () => {
    try {
      await api.logout();
      Cookies.remove('token');
      toast.success('Logout realizado com sucesso!');
      router.push('/login');
    } catch (error) {
      Cookies.remove('token');
      router.push('/login');
    }
  };

  return (
    <header className="bg-white shadow-sm">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
        <div className="flex justify-between items-center">
          <div>
            <h1 className="text-2xl font-bold text-libresign-blue">LibreSign SaaS</h1>
            <p className="text-sm text-gray-600">
              {userName} • {companyName}
            </p>
          </div>
          <button
            onClick={handleLogout}
            className="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors font-medium"
          >
            Sair
          </button>
        </div>
      </div>
    </header>
  );
}
