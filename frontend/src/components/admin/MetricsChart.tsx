'use client';

import { LineChart, Line, AreaChart, Area, XAxis, YAxis, CartesianGrid, Tooltip, Legend, ResponsiveContainer } from 'recharts';

interface MetricsChartProps {
  data: any[];
  title: string;
  dataKey: string;
  color?: string;
  valueFormatter?: (value: number) => string;
  type?: 'line' | 'area';
}

export default function MetricsChart({
  data,
  title,
  dataKey,
  color = '#3B82F6',
  valueFormatter,
  type = 'area'
}: MetricsChartProps) {
  
  // Prepara dados para o gráfico
  const chartData = data.map((item) => ({
    timestamp: new Date(item.timestamp).toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' }),
    value: item.value || item[dataKey] || 0,
  }));

  const Chart = type === 'area' ? AreaChart : LineChart;
  const ChartComponent = type === 'area' ? Area : Line;

  return (
    <div className="bg-white rounded-lg shadow p-6">
      <h3 className="text-lg font-semibold mb-4">{title}</h3>
      <ResponsiveContainer width="100%" height={250}>
        <Chart data={chartData}>
          <CartesianGrid strokeDasharray="3 3" />
          <XAxis 
            dataKey="timestamp" 
            style={{ fontSize: '12px' }}
          />
          <YAxis 
            style={{ fontSize: '12px' }}
            tickFormatter={valueFormatter}
          />
          <Tooltip 
            formatter={(value: any) => valueFormatter ? valueFormatter(value) : value}
            labelStyle={{ color: '#000' }}
          />
          <ChartComponent
            type="monotone"
            dataKey="value"
            stroke={color}
            fill={color}
            fillOpacity={type === 'area' ? 0.3 : 1}
            strokeWidth={2}
          />
        </Chart>
      </ResponsiveContainer>
    </div>
  );
}

// Componente específico para CPU/Memória
export function SystemMetricsChart({ data }: { data: any[] }) {
  const chartData = data.map((item) => ({
    timestamp: new Date(item.timestamp).toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' }),
    cpu: item.cpu_usage || 0,
    memory: item.memory_usage || 0,
  }));

  return (
    <div className="bg-white rounded-lg shadow p-6">
      <h3 className="text-lg font-semibold mb-4">CPU e Memória</h3>
      <ResponsiveContainer width="100%" height={300}>
        <LineChart data={chartData}>
          <CartesianGrid strokeDasharray="3 3" />
          <XAxis dataKey="timestamp" style={{ fontSize: '12px' }} />
          <YAxis style={{ fontSize: '12px' }} tickFormatter={(value) => `${value}%`} />
          <Tooltip formatter={(value: any) => `${value.toFixed(1)}%`} />
          <Legend />
          <Line type="monotone" dataKey="cpu" stroke="#EF4444" name="CPU" strokeWidth={2} />
          <Line type="monotone" dataKey="memory" stroke="#3B82F6" name="Memória" strokeWidth={2} />
        </LineChart>
      </ResponsiveContainer>
    </div>
  );
}

// Componente específico para Usuários
export function UserActivityChart({ data }: { data: any[] }) {
  const chartData = data.map((item) => ({
    timestamp: new Date(item.timestamp).toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' }),
    total: item.total || 0,
    active: item.active || 0,
  }));

  return (
    <div className="bg-white rounded-lg shadow p-6">
      <h3 className="text-lg font-semibold mb-4">Atividade de Usuários</h3>
      <ResponsiveContainer width="100%" height={300}>
        <AreaChart data={chartData}>
          <CartesianGrid strokeDasharray="3 3" />
          <XAxis dataKey="timestamp" style={{ fontSize: '12px' }} />
          <YAxis style={{ fontSize: '12px' }} />
          <Tooltip />
          <Legend />
          <Area type="monotone" dataKey="total" stroke="#8B5CF6" fill="#8B5CF6" fillOpacity={0.3} name="Total" />
          <Area type="monotone" dataKey="active" stroke="#10B981" fill="#10B981" fillOpacity={0.3} name="Ativos" />
        </AreaChart>
      </ResponsiveContainer>
    </div>
  );
}

// Formatadores úteis
export const formatBytes = (bytes: number) => {
  if (bytes === 0) return '0 B';
  const k = 1024;
  const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
};

export const formatPercentage = (value: number) => `${value.toFixed(1)}%`;
