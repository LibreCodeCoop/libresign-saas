interface StatCardProps {
  title: string;
  value: string | number;
  subtitle?: string;
  showProgress?: boolean;
  current?: number;
  limit?: number;
  gradient?: boolean;
}

export default function StatCard({
  title,
  value,
  subtitle,
  showProgress,
  current,
  limit,
  gradient,
}: StatCardProps) {
  const percentage = current && limit ? (current / limit) * 100 : 0;

  return (
    <div
      className={`rounded-xl shadow-lg p-6 ${
        gradient
          ? 'bg-gradient-to-br from-libresign-blue to-blue-700 text-white'
          : 'bg-white'
      }`}
    >
      <h3
        className={`text-sm font-medium ${
          gradient ? 'text-blue-100' : 'text-gray-600'
        } mb-2`}
      >
        {title}
      </h3>
      <p className="text-3xl font-bold mb-1">{value}</p>
      {subtitle && (
        <p
          className={`text-sm ${
            gradient ? 'text-blue-100' : 'text-gray-500'
          }`}
        >
          {subtitle}
        </p>
      )}
      {showProgress && current !== undefined && limit !== undefined && (
        <div className="mt-4">
          <div className="flex justify-between text-sm mb-1">
            <span className={gradient ? 'text-blue-100' : 'text-gray-600'}>
              {current} de {limit}
            </span>
            <span className={gradient ? 'text-blue-100' : 'text-gray-600'}>
              {percentage.toFixed(1)}%
            </span>
          </div>
          <div
            className={`w-full ${
              gradient ? 'bg-blue-900' : 'bg-gray-200'
            } rounded-full h-2`}
          >
            <div
              className={`${
                gradient ? 'bg-white' : 'bg-libresign-green'
              } h-2 rounded-full transition-all duration-500`}
              style={{ width: `${percentage}%` }}
            ></div>
          </div>
        </div>
      )}
    </div>
  );
}
