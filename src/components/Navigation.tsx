import React from 'react';
import { Monitor, FileText, Terminal, Settings } from 'lucide-react';
import { TabType } from '../App';

interface NavigationProps {
  activeTab: TabType;
  setActiveTab: (tab: TabType) => void;
}

const Navigation: React.FC<NavigationProps> = ({ activeTab, setActiveTab }) => {
  const navItems = [
    { id: 'dashboard' as TabType, label: 'Dashboard', icon: Monitor },
    { id: 'logs' as TabType, label: 'Logs', icon: FileText },
    { id: 'console' as TabType, label: 'Console', icon: Terminal },
    { id: 'settings' as TabType, label: 'Settings', icon: Settings },
  ];

  return (
    <nav className="w-64 bg-white dark:bg-stone-800 shadow-sm border-r border-gray-200 dark:border-stone-700 min-h-screen">
      <div className="p-4">
        <ul className="space-y-2">
          {navItems.map(({ id, label, icon: Icon }) => (
            <li key={id}>
              <button
                onClick={() => setActiveTab(id)}
                className={`w-full flex items-center space-x-3 px-4 py-3 rounded-lg text-left transition-all duration-200 ${
                  activeTab === id
                    ? 'bg-minecraft-100 dark:bg-minecraft-900/30 text-minecraft-700 dark:text-minecraft-400 border-l-4 border-minecraft-600'
                    : 'text-gray-700 dark:text-stone-300 hover:bg-gray-100 dark:hover:bg-stone-700'
                }`}
              >
                <Icon className="w-5 h-5" />
                <span className="font-medium">{label}</span>
              </button>
            </li>
          ))}
        </ul>
      </div>
    </nav>
  );
};

export default Navigation;