import React, { useState, useEffect, useRef } from 'react';
import { RefreshCw, Filter, Download, Search } from 'lucide-react';
import { useApi, apiCall } from '../hooks/useApi';

interface LogEntry {
  id: string;
  timestamp: string;
  level: 'INFO' | 'WARN' | 'ERROR' | 'DEBUG';
  source: string;
  message: string;
}

const Logs: React.FC = () => {
  const [selectedLevel, setSelectedLevel] = useState<string>('all');
  const [searchTerm, setSearchTerm] = useState('');
  const [autoRefresh, setAutoRefresh] = useState(true);
  const logsEndRef = useRef<HTMLDivElement>(null);

  const { data: logs, loading, error, refetch } = useApi<LogEntry[]>(
    `logs.php?level=${selectedLevel}&search=${encodeURIComponent(searchTerm)}&limit=100`
  );

  useEffect(() => {
    // Auto-scroll to bottom when new logs are added
    if (logsEndRef.current) {
      logsEndRef.current.scrollIntoView({ behavior: 'smooth' });
    }
  }, [logs]);

  useEffect(() => {
    // Auto-refresh logs every 10 seconds
    if (autoRefresh) {
      const interval = setInterval(() => {
        refetch();
      }, 10000);

      return () => clearInterval(interval);
    }
  }, [autoRefresh, refetch]);

  const handleRefresh = () => {
    refetch();
  };

  const handleDownload = async () => {
    if (!logs) return;
    
    const logContent = logs.map(log => 
      `[${log.timestamp}] [${log.level}] [${log.source}] ${log.message}`
    ).join('\n');
    
    const blob = new Blob([logContent], { type: 'text/plain' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `minecraft-logs-${new Date().toISOString().split('T')[0]}.txt`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
  };

  const getLevelColor = (level: string) => {
    switch (level) {
      case 'INFO': return 'text-blue-600 dark:text-blue-400 bg-blue-100 dark:bg-blue-900/30';
      case 'WARN': return 'text-yellow-600 dark:text-yellow-400 bg-yellow-100 dark:bg-yellow-900/30';
      case 'ERROR': return 'text-red-600 dark:text-red-400 bg-red-100 dark:bg-red-900/30';
      case 'DEBUG': return 'text-gray-600 dark:text-gray-400 bg-gray-100 dark:bg-gray-900/30';
      default: return 'text-gray-600 dark:text-gray-400 bg-gray-100 dark:bg-gray-900/30';
    }
  };

  return (
    <div className="space-y-6">
      {/* Page Header */}
      <div>
        <h2 className="text-2xl font-bold text-gray-900 dark:text-white mb-2">Logs do Servidor</h2>
        <p className="text-gray-600 dark:text-stone-400">
          Monitore a atividade do servidor em tempo real
        </p>
      </div>

      {/* Controls */}
      <div className="bg-white dark:bg-stone-800 rounded-xl shadow-sm border border-gray-200 dark:border-stone-700 p-6">
        <div className="flex flex-col lg:flex-row lg:items-center lg:justify-between space-y-4 lg:space-y-0 lg:space-x-4">
          {/* Search */}
          <div className="relative flex-1 max-w-md">
            <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-4 h-4" />
            <input
              type="text"
              placeholder="Buscar nos logs..."
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
              className="w-full pl-10 pr-4 py-2 border border-gray-300 dark:border-stone-600 rounded-lg focus:ring-2 focus:ring-minecraft-500 focus:border-minecraft-500 dark:bg-stone-700 dark:text-white"
            />
          </div>

          {/* Controls */}
          <div className="flex items-center space-x-3">
            {/* Level Filter */}
            <div className="flex items-center space-x-2">
              <Filter className="w-4 h-4 text-gray-600 dark:text-stone-400" />
              <select
                value={selectedLevel}
                onChange={(e) => setSelectedLevel(e.target.value)}
                className="border border-gray-300 dark:border-stone-600 rounded-lg px-3 py-2 dark:bg-stone-700 dark:text-white focus:ring-2 focus:ring-minecraft-500 focus:border-minecraft-500"
              >
                <option value="all">Todos os Níveis</option>
                <option value="INFO">INFO</option>
                <option value="WARN">WARN</option>
                <option value="ERROR">ERROR</option>
                <option value="DEBUG">DEBUG</option>
              </select>
            </div>

            {/* Auto Refresh Toggle */}
            <label className="flex items-center space-x-2">
              <input
                type="checkbox"
                checked={autoRefresh}
                onChange={(e) => setAutoRefresh(e.target.checked)}
                className="rounded border-gray-300 text-minecraft-600 focus:ring-minecraft-500"
              />
              <span className="text-sm text-gray-600 dark:text-stone-400">Auto-refresh</span>
            </label>

            {/* Action Buttons */}
            <button
              onClick={handleRefresh}
              disabled={loading}
              className="flex items-center space-x-2 px-3 py-2 bg-minecraft-600 hover:bg-minecraft-700 disabled:bg-minecraft-400 text-white rounded-lg transition-colors"
            >
              <RefreshCw className={`w-4 h-4 ${loading ? 'animate-spin' : ''}`} />
              <span>Atualizar</span>
            </button>

            <button
              onClick={handleDownload}
              disabled={!logs || logs.length === 0}
              className="flex items-center space-x-2 px-3 py-2 bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 text-white rounded-lg transition-colors"
            >
              <Download className="w-4 h-4" />
              <span>Download</span>
            </button>
          </div>
        </div>
      </div>

      {/* Logs Display */}
      <div className="bg-white dark:bg-stone-800 rounded-xl shadow-sm border border-gray-200 dark:border-stone-700">
        <div className="p-6">
          <div className="flex items-center justify-between mb-4">
            <h3 className="text-lg font-semibold text-gray-900 dark:text-white">
              Logs em Tempo Real
            </h3>
            <span className="text-sm text-gray-600 dark:text-stone-400">
              {logs?.length || 0} entradas
            </span>
          </div>

          <div className="bg-black rounded-lg p-4 h-96 overflow-y-auto font-mono text-sm">
            {loading && !logs ? (
              <div className="text-gray-400 text-center py-8">
                <div className="animate-spin rounded-full h-6 w-6 border-b-2 border-gray-400 mx-auto mb-2"></div>
                Carregando logs...
              </div>
            ) : error ? (
              <div className="text-red-400 text-center py-8">
                Erro ao carregar logs: {error}
              </div>
            ) : !logs || logs.length === 0 ? (
              <div className="text-gray-400 text-center py-8">
                Nenhum log encontrado com os filtros aplicados
              </div>
            ) : (
              <div className="space-y-1">
                {logs.map((log) => (
                  <div key={log.id} className="flex items-start space-x-3">
                    <span className="text-gray-400 shrink-0">{log.timestamp}</span>
                    <span className={`px-2 py-1 rounded text-xs font-medium shrink-0 ${getLevelColor(log.level)}`}>
                      {log.level}
                    </span>
                    <span className="text-purple-400 shrink-0">[{log.source}]</span>
                    <span className="text-gray-100 break-all">{log.message}</span>
                  </div>
                ))}
                <div ref={logsEndRef} />
              </div>
            )}
          </div>
        </div>
      </div>
    </div>
  );
};

export default Logs;