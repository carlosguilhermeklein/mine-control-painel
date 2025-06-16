import React, { useState, useEffect } from 'react';
import { Moon, Sun, Shield, LogOut } from 'lucide-react';
import Dashboard from './components/Dashboard';
import Logs from './components/Logs';
import Console from './components/Console';
import Settings from './components/Settings';
import Login from './components/Login';
import Navigation from './components/Navigation';
import { apiCall } from './hooks/useApi';

export type TabType = 'dashboard' | 'logs' | 'console' | 'settings';

function App() {
  const [isDarkMode, setIsDarkMode] = useState(true);
  const [isAuthenticated, setIsAuthenticated] = useState(false);
  const [activeTab, setActiveTab] = useState<TabType>('dashboard');
  const [sessionTimeout, setSessionTimeout] = useState<NodeJS.Timeout | null>(null);
  const [isCheckingAuth, setIsCheckingAuth] = useState(true);

  useEffect(() => {
    if (isDarkMode) {
      document.documentElement.classList.add('dark');
    } else {
      document.documentElement.classList.remove('dark');
    }
  }, [isDarkMode]);

  useEffect(() => {
    // Check authentication status on app load
    checkAuthStatus();
  }, []);

  useEffect(() => {
    if (isAuthenticated) {
      // Auto logout after 15 minutes
      const timeout = setTimeout(() => {
        handleLogout();
      }, 15 * 60 * 1000);
      setSessionTimeout(timeout);
      
      return () => {
        if (timeout) clearTimeout(timeout);
      };
    }
  }, [isAuthenticated]);

  const checkAuthStatus = async () => {
    try {
      const result = await apiCall('auth.php', {
        method: 'POST',
        body: JSON.stringify({ action: 'check' })
      });
      
      setIsAuthenticated(result.authenticated);
    } catch (err) {
      setIsAuthenticated(false);
    } finally {
      setIsCheckingAuth(false);
    }
  };

  const handleLogin = () => {
    setIsAuthenticated(true);
  };

  const handleLogout = async () => {
    try {
      await apiCall('auth.php', {
        method: 'POST',
        body: JSON.stringify({ action: 'logout' })
      });
    } catch (err) {
      // Ignore logout errors
    }
    
    setIsAuthenticated(false);
    setActiveTab('dashboard');
    if (sessionTimeout) {
      clearTimeout(sessionTimeout);
      setSessionTimeout(null);
    }
  };

  const toggleTheme = () => {
    setIsDarkMode(!isDarkMode);
  };

  if (isCheckingAuth) {
    return (
      <div className="min-h-screen bg-gray-50 dark:bg-stone-900 flex items-center justify-center">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-minecraft-600"></div>
      </div>
    );
  }

  if (!isAuthenticated) {
    return <Login onLogin={handleLogin} isDarkMode={isDarkMode} toggleTheme={toggleTheme} />;
  }

  return (
    <div className="min-h-screen bg-gray-50 dark:bg-stone-900 transition-colors duration-300">
      {/* Header */}
      <header className="bg-white dark:bg-stone-800 shadow-sm border-b border-gray-200 dark:border-stone-700">
        <div className="px-6 py-4">
          <div className="flex items-center justify-between">
            <div className="flex items-center space-x-3">
              <Shield className="w-8 h-8 text-minecraft-600 dark:text-minecraft-400" />
              <div>
                <h1 className="text-xl font-bold text-gray-900 dark:text-white">
                  Minecraft Server Monitor
                </h1>
                <p className="text-sm text-gray-600 dark:text-stone-400">
                  Prominence II RPG - Hasturian Era
                </p>
              </div>
            </div>
            
            <div className="flex items-center space-x-4">
              <button
                onClick={toggleTheme}
                className="p-2 rounded-lg bg-gray-100 dark:bg-stone-700 hover:bg-gray-200 dark:hover:bg-stone-600 transition-colors"
                title={isDarkMode ? 'Switch to Light Mode' : 'Switch to Dark Mode'}
              >
                {isDarkMode ? (
                  <Sun className="w-5 h-5 text-yellow-500" />
                ) : (
                  <Moon className="w-5 h-5 text-stone-600" />
                )}
              </button>
              
              <button
                onClick={handleLogout}
                className="flex items-center space-x-2 px-3 py-2 rounded-lg bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 hover:bg-red-200 dark:hover:bg-red-900/50 transition-colors"
              >
                <LogOut className="w-4 h-4" />
                <span>Logout</span>
              </button>
            </div>
          </div>
        </div>
      </header>

      <div className="flex">
        {/* Sidebar Navigation */}
        <Navigation activeTab={activeTab} setActiveTab={setActiveTab} />

        {/* Main Content */}
        <main className="flex-1 p-6">
          <div className="animate-fade-in">
            {activeTab === 'dashboard' && <Dashboard />}
            {activeTab === 'logs' && <Logs />}
            {activeTab === 'console' && <Console />}
            {activeTab === 'settings' && <Settings />}
          </div>
        </main>
      </div>
    </div>
  );
}

export default App;