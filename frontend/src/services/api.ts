import Cookies from 'js-cookie';

const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000';

interface LoginCredentials {
  email: string;
  password: string;
}

interface RegisterData {
  name: string;
  email: string;
  password: string;
  password_confirmation: string;
  company_name: string;
  phone?: string;
  role?: string;
}

interface ChangePasswordData {
  current_password: string;
  new_password: string;
  new_password_confirmation: string;
}

class ApiService {
  private getHeaders(withAuth = false): HeadersInit {
    const headers: HeadersInit = {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    };

    if (withAuth) {
      const token = Cookies.get('token');
      if (token) {
        headers['Authorization'] = `Bearer ${token}`;
      }
    }

    return headers;
  }

  async login(credentials: LoginCredentials) {
    const response = await fetch(`${API_URL}/api/login`, {
      method: 'POST',
      headers: this.getHeaders(),
      body: JSON.stringify(credentials),
    });

    if (!response.ok) {
      const error = await response.json();
      throw new Error(error.message || 'Erro ao fazer login');
    }

    return response.json();
  }

  async register(data: RegisterData) {
    const response = await fetch(`${API_URL}/api/register`, {
      method: 'POST',
      headers: this.getHeaders(),
      body: JSON.stringify({
        ...data,
        company: data.company_name,
      }),
    });

    if (!response.ok) {
      const error = await response.json();
      throw new Error(error.message || 'Erro ao registrar');
    }

    return response.json();
  }

  async getUser() {
    const response = await fetch(`${API_URL}/api/user`, {
      headers: this.getHeaders(true),
    });

    if (!response.ok) {
      throw new Error('Erro ao buscar dados do usuário');
    }

    return response.json();
  }

  async getUserStats() {
    const response = await fetch(`${API_URL}/api/user/stats`, {
      headers: this.getHeaders(true),
    });

    if (!response.ok) {
      // Retorna dados mockados se a API não estiver pronta
      return {
        documents_signed_this_month: 127,
        total_documents: 543,
      };
    }

    return response.json();
  }

  async getUserPlan() {
    const response = await fetch(`${API_URL}/api/user/plan`, {
      headers: this.getHeaders(true),
    });

    if (!response.ok) {
      // Retorna dados mockados se a API não estiver pronta
      return {
        name: 'Profissional',
        price: 149,
        documents_limit: 500,
        renewal_date: '2025-12-08',
      };
    }

    return response.json();
  }

  async changePassword(data: ChangePasswordData) {
    const response = await fetch(`${API_URL}/api/user/change-password`, {
      method: 'POST',
      headers: this.getHeaders(true),
      body: JSON.stringify(data),
    });

    if (!response.ok) {
      const error = await response.json();
      throw new Error(error.message || 'Erro ao trocar senha');
    }

    return response.json();
  }

  async logout() {
    const response = await fetch(`${API_URL}/api/logout`, {
      method: 'POST',
      headers: this.getHeaders(true),
    });

    if (!response.ok) {
      throw new Error('Erro ao fazer logout');
    }

    return response.json();
  }

  // Admin APIs
  async getAdminDashboard() {
    const response = await fetch(`${API_URL}/api/admin/dashboard`, {
      headers: this.getHeaders(true),
    });

    if (!response.ok) {
      throw new Error('Erro ao buscar dashboard admin');
    }

    return response.json();
  }

  async getAdminStats() {
    const response = await fetch(`${API_URL}/api/admin/stats`, {
      headers: this.getHeaders(true),
    });

    if (!response.ok) {
      throw new Error('Erro ao buscar estatísticas');
    }

    return response.json();
  }

  async getInstances(params?: any) {
    // Remove empty params
    const cleanParams: any = {};
    if (params) {
      Object.keys(params).forEach(key => {
        if (params[key] !== '' && params[key] !== null && params[key] !== undefined) {
          cleanParams[key] = params[key];
        }
      });
    }
    
    const queryParams = new URLSearchParams(cleanParams).toString();
    const url = `${API_URL}/api/admin/instances${queryParams ? `?${queryParams}` : ''}`;
    
    console.log('GET Instances URL:', url);
    console.log('Clean params:', cleanParams);
    
    const response = await fetch(url, {
      headers: this.getHeaders(true),
    });

    console.log('Response status:', response.status);

    if (!response.ok) {
      const errorText = await response.text();
      console.error('Error response:', errorText);
      throw new Error('Erro ao buscar instâncias');
    }

    const data = await response.json();
    console.log('Response data:', data);
    return data;
  }

  async getInstance(id: number) {
    const response = await fetch(`${API_URL}/api/admin/instances/${id}`, {
      headers: this.getHeaders(true),
    });

    if (!response.ok) {
      throw new Error('Erro ao buscar instância');
    }

    return response.json();
  }

  async createInstance(data: any) {
    const response = await fetch(`${API_URL}/api/admin/instances`, {
      method: 'POST',
      headers: this.getHeaders(true),
      body: JSON.stringify(data),
    });

    if (!response.ok) {
      const error = await response.json();
      throw new Error(error.message || 'Erro ao criar instância');
    }

    return response.json();
  }

  async updateInstance(id: number, data: any) {
    const response = await fetch(`${API_URL}/api/admin/instances/${id}`, {
      method: 'PUT',
      headers: this.getHeaders(true),
      body: JSON.stringify(data),
    });

    if (!response.ok) {
      const error = await response.json();
      throw new Error(error.message || 'Erro ao atualizar instância');
    }

    return response.json();
  }

  async deleteInstance(id: number) {
    const response = await fetch(`${API_URL}/api/admin/instances/${id}`, {
      method: 'DELETE',
      headers: this.getHeaders(true),
    });

    if (!response.ok) {
      const error = await response.json();
      throw new Error(error.message || 'Erro ao deletar instância');
    }

    return response.json();
  }

  async getInstanceMetrics(id: number) {
    const response = await fetch(`${API_URL}/api/admin/instances/${id}/metrics`, {
      headers: this.getHeaders(true),
    });

    if (!response.ok) {
      const error = await response.json().catch(() => ({ message: response.statusText }));
      throw new Error(error.message || `Erro ao buscar métricas (${response.status})`);
    }

    return response.json();
  }

  async getInstanceLogs(id: number) {
    const response = await fetch(`${API_URL}/api/admin/instances/${id}/logs`, {
      headers: this.getHeaders(true),
    });

    if (!response.ok) {
      throw new Error('Erro ao buscar logs');
    }

    return response.json();
  }

  async instanceAction(id: number, action: string) {
    const response = await fetch(`${API_URL}/api/admin/instances/${id}/action`, {
      method: 'POST',
      headers: this.getHeaders(true),
      body: JSON.stringify({ action }),
    });

    if (!response.ok) {
      const error = await response.json();
      throw new Error(error.message || 'Erro ao executar ação');
    }

    return response.json();
  }

  async batchInstanceAction(instanceIds: number[], action: string) {
    const response = await fetch(`${API_URL}/api/admin/instances/batch-action`, {
      method: 'POST',
      headers: this.getHeaders(true),
      body: JSON.stringify({ instance_ids: instanceIds, action }),
    });

    if (!response.ok) {
      const error = await response.json();
      throw new Error(error.message || 'Erro ao executar ação em lote');
    }

    return response.json();
  }

  async instanceHealthCheck(id: number) {
    const response = await fetch(`${API_URL}/api/admin/instances/${id}/health-check`, {
      method: 'POST',
      headers: this.getHeaders(true),
    });

    if (!response.ok) {
      const error = await response.json();
      throw new Error(error.message || 'Erro ao executar health check');
    }

    return response.json();
  }
}

export const api = new ApiService();
