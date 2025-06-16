import React, { useState, useRef, useEffect } from 'react';
import { Send, Terminal, History, Trash2 } from 'lucide-react';
import { useApi, apiCall } from '../hooks/useApi';

interface Command {
  id: number;
  command: string;
  timestamp: string;
  response: string;
  success: boolean;
}

const Console: React.FC = () => {
  const [currentCommand, setCurrentCommand] = useState('');
  const [commandIndex, setCommandIndex] = useState(-1);
  const [recentCommands, setRecentCommands] = useState<string[]>([]);
  const inputRef = useRef<HTMLInputElement>(null);
  const consoleRef = useRef<HTMLDivElement>(null);

  const { data: commandHistory, loading, error, refetch } = useApi<Command[]>('console.php');

  useEffect(() => {
    // Auto-scroll to bottom when new commands are added
    if (consoleRef.current) {
      consoleRef.current.scrollTop = consoleRef.current.scrollHeight;
    }
  }, [commandHistory]);

  useEffect(() => {
    // Extract recent commands from history
    if (commandHistory) {
      const recent = [...new Set(commandHistory.map(cmd => cmd.command))].slice(0, 10);
      setRecentCommands(recent);
    }
  }, [commandHistory]);

  const handleSubmitCommand = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!currentCommand.trim()) return;

    try {
      await apiCall('console.php', {
        method: 'POST',
        body: JSON.stringify({ 
          action: 'execute', 
          command: currentCommand 
        })
      });

      // Add to recent commands if not already there
      if (!recentCommands.includes(currentCommand)) {
        setRecentCommands(prev => [currentCommand, ...prev.slice(0, 9)]);
      }

      setCurrentCommand('');
      setCommandIndex(-1);
      
      // Refresh command history
      refetch();
    } catch (err) {
      alert('Erro ao executar comando: ' + (err instanceof Error ? err.message : 'Erro desconhecido'));
    }
  };

  const handleKeyDown = (e: React.KeyboardEvent) => {
    if (e.key === 'ArrowUp') {
      e.preventDefault();
      if (commandIndex < recentCommands.length - 1) {
        const newIndex = commandIndex + 1;
        setCommandIndex(newIndex);
        setCurrentCommand(recentCommands[newIndex]);
      }
    } else if (e.key === 'ArrowDown') {
      e.preventDefault();
      if (commandIndex > 0) {
        const newIndex = commandIndex - 1;
        setCommandIndex(newIndex);
        setCurrentCommand(recentCommands[newIndex]);
      } else if (commandIndex === 0) {
        setCommandIndex(-1);
        setCurrentCommand('');
      }
    }
  };

  const clearHistory = async () => {
    if (confirm('Tem certeza que deseja limpar o histórico de comandos?')) {
      // You could implement a clear history endpoint here
      refetch();
    }
  };

  const useRecentCommand = (command: string) => {
    setCurrentCommand(command);
    inputRef.current?.focus();
  };

  return (
    <div className="space-y-6">
      {/* Page Header */}
      <div>
        <h2 className="text-2xl font-bold text-gray-900 dark:text-white mb-2">Console Remoto</h2>
        <p className="text-gray-600 dark:text-stone-400">
          Execute comandos diretamente no servidor via RCON
        </p>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Main Console */}
        <div className="lg:col-span-2">
          <div className="bg-white dark:bg-stone-800 rounded-xl shadow-sm border border-gray-200 dark:border-stone-700">
            <div className="p-6">
              <div className="flex items-center justify-between mb-4">
                <div className="flex items-center space-x-2">
                  <Terminal className="w-5 h-5 text-minecraft-600 dark:text-minecraft-400" />
                  <h3 className="text-lg font-semibold text-gray-900 dark:text-white">
                    Terminal do Servidor
                  </h3>
                </div>
                <button
                  onClick={clearHistory}
                  className="flex items-center space-x-2 px-3 py-2 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors"
                >
                  <Trash2 className="w-4 h-4" />
                  <span>Limpar</span>
                </button>
              </div>

              {/* Console Display */}
              <div 
                ref={consoleRef}
                className="bg-black rounded-lg p-4 h-80 overflow-y-auto font-mono text-sm mb-4"
              >
                {loading && !commandHistory ? (
                  <div className="text-gray-400 text-center py-8">
                    <div className="animate-spin rounded-full h-6 w-6 border-b-2 border-gray-400 mx-auto mb-2"></div>
                    Carregando histórico...
                  </div>
                ) : error ? (
                  <div className="text-red-400 text-center py-8">
                    Erro ao carregar histórico: {error}
                  </div>
                ) : !commandHistory || commandHistory.length === 0 ? (
                  <div className="text-gray-400 text-center py-8">
                    Nenhum comando executado ainda
                  </div>
                ) : (
                  <div className="space-y-2">
                    {commandHistory.map((cmd) => (
                      <div key={cmd.id} className="space-y-1">
                        <div className="flex items-center space-x-2">
                          <span className="text-gray-400">[{cmd.timestamp}]</span>
                          <span className="text-green-400">$</span>
                          <span className="text-white">{cmd.command}</span>
                        </div>
                        <div className={`ml-4 ${cmd.success ? 'text-gray-300' : 'text-red-400'}`}>
                          {cmd.response}
                        </div>
                      </div>
                    ))}
                  </div>
                )}
              </div>

              {/* Command Input */}
              <form onSubmit={handleSubmitCommand} className="flex space-x-2">
                <div className="flex-1 relative">
                  <input
                    ref={inputRef}
                    type="text"
                    value={currentCommand}
                    onChange={(e) => setCurrentCommand(e.target.value)}
                    onKeyDown={handleKeyDown}
                    placeholder="Digite um comando... (use ↑↓ para histórico)"
                    className="w-full px-4 py-3 pr-12 border border-gray-300 dark:border-stone-600 rounded-lg focus:ring-2 focus:ring-minecraft-500 focus:border-minecraft-500 dark:bg-stone-700 dark:text-white font-mono"
                  />
                  <Terminal className="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-5 h-5" />
                </div>
                <button
                  type="submit"
                  disabled={!currentCommand.trim()}
                  className="flex items-center space-x-2 px-6 py-3 bg-minecraft-600 hover:bg-minecraft-700 disabled:bg-gray-400 disabled:cursor-not-allowed text-white rounded-lg transition-colors"
                >
                  <Send className="w-4 h-4" />
                  <span>Executar</span>
                </button>
              </form>
            </div>
          </div>
        </div>

        {/* Sidebar */}
        <div className="space-y-6">
          {/* Recent Commands */}
          <div className="bg-white dark:bg-stone-800 rounded-xl shadow-sm border border-gray-200 dark:border-stone-700 p-6">
            <div className="flex items-center space-x-2 mb-4">
              <History className="w-5 h-5 text-blue-600 dark:text-blue-400" />
              <h3 className="text-lg font-semibold text-gray-900 dark:text-white">
                Comandos Recentes
              </h3>
            </div>
            
            <div className="space-y-2">
              {recentCommands.map((command, index) => (
                <button
                  key={index}
                  onClick={() => useRecentCommand(command)}
                  className="w-full text-left px-3 py-2 text-sm bg-gray-50 dark:bg-stone-700 hover:bg-gray-100 dark:hover:bg-stone-600 rounded-lg transition-colors font-mono"
                >
                  {command}
                </button>
              ))}
            </div>
          </div>

          {/* Quick Commands */}
          <div className="bg-white dark:bg-stone-800 rounded-xl shadow-sm border border-gray-200 dark:border-stone-700 p-6">
            <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-4">
              Comandos Rápidos
            </h3>
            
            <div className="space-y-2">
              {[
                { label: 'Listar Jogadores', command: '/list' },
                { label: 'Tempo Dia', command: '/time set day' },
                { label: 'Tempo Noite', command: '/time set night' },
                { label: 'Clima Limpo', command: '/weather clear' },
                { label: 'Parar Chuva', command: '/weather rain' },
                { label: 'Salvar Mundo', command: '/save-all' }
              ].map((item, index) => (
                <button
                  key={index}
                  onClick={() => useRecentCommand(item.command)}
                  className="w-full text-left px-3 py-2 text-sm text-gray-700 dark:text-stone-300 hover:bg-gray-100 dark:hover:bg-stone-700 rounded-lg transition-colors"
                >
                  <div className="font-medium">{item.label}</div>
                  <div className="text-xs text-gray-500 dark:text-stone-500 font-mono">
                    {item.command}
                  </div>
                </button>
              ))}
            </div>
          </div>

          {/* RCON Status */}
          <div className="bg-white dark:bg-stone-800 rounded-xl shadow-sm border border-gray-200 dark:border-stone-700 p-6">
            <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-4">
              Status RCON
            </h3>
            
            <div className="space-y-3">
              <div className="flex items-center justify-between">
                <span className="text-sm text-gray-600 dark:text-stone-400">Conexão</span>
                <span className="px-2 py-1 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 rounded text-sm">
                  Conectado
                </span>
              </div>
              <div className="flex items-center justify-between">
                <span className="text-sm text-gray-600 dark:text-stone-400">IP</span>
                <span className="text-sm text-gray-900 dark:text-white font-mono">127.0.0.1</span>
              </div>
              <div className="flex items-center justify-between">
                <span className="text-sm text-gray-600 dark:text-stone-400">Porta</span>
                <span className="text-sm text-gray-900 dark:text-white font-mono">25575</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default Console;