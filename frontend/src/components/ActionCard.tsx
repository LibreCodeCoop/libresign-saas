interface ActionCardProps {
  title: string;
  description: string;
  buttonText: string;
  onButtonClick: () => void;
  icon?: string;
}

export default function ActionCard({
  title,
  description,
  buttonText,
  onButtonClick,
  icon,
}: ActionCardProps) {
  return (
    <div className="bg-white rounded-xl shadow-lg p-6">
      <div className="flex items-start justify-between mb-4">
        <div>
          <h3 className="text-lg font-semibold text-gray-900 mb-2">{title}</h3>
          <p className="text-sm text-gray-600">{description}</p>
        </div>
        {icon && <span className="text-3xl">{icon}</span>}
      </div>
      <button
        onClick={onButtonClick}
        className="w-full px-4 py-2 bg-libresign-blue text-white rounded-lg hover:bg-blue-700 transition-colors font-medium"
      >
        {buttonText}
      </button>
    </div>
  );
}
