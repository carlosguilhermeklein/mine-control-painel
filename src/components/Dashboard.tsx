import React, { useState, useEffect } from 'react';
import { 
  Play, 
  Square, 
  RotateCcw, 
  Users, 
  Clock, 
  Server, 
  Wifi,
  WifiOff,
  Activity,
  AlertCircle
} from 'lucide-react';
import { useApi, apiCall } from '../hooks/useApi';

interface Player {
  name: string;
  onlineTime: string;
  joinTime: string;
}

interface ServerStatus {
  status: 'online' | 'offline' | 'starting' | 'stopping';
  players: Player[];
  playerCount: number;
  maxPlayers: number;
  serverInfo: {
    ip: string;
    port: string;
    version: string;
    uptime: string;
  };
}

const Dashboard: React.FC = () => {
  const [serverStatus, setServerStatus] = useState<'online' | 'offline' | 'starting' | 'stopping'>('offline');
  const [actionLoading, setActionLoading] = useState<string | null>(null);
  const { data: statusData, loading, error, refetch } = useApi<ServerStatus>('server.php');
  
  useEffect(() => {
    if (statusData) {
      setServerStatus(statusData.status);
    }
  }, [statusData]);

  // Auto-refresh a cada 10 segundos
  useEffect(() => {
    const interval = setInterval(() => {
      if (!actionLoading) {
        refetch();
      }
    }, 10000);
    
    return () => clearInterval(interval);
  }, [refetch, actionLoading]);

  const handleServerAction = async (action: 'start' | 'stop' | 'restart') => {
    try {
      setActionLoading(action);
      
      if (action === 'start') {
        setServerStatus('starting');
      } else if (action === 'stop') {
        setServerStatus('stopping');
      } else if (action === 'restart') {
        setServerStatus('stopping');
      }

      const result = await apiCall('server.php', {
        method: 'POST',
        body: JSON.stringify({ action })
      });

      if (result.success) {
        // Aguardar um pouco e atualizar status
        setTimeout(() => {
          refetch();
        }, action === 'restart' ? 5000 : 2000);
      } else {
        alert(result.message || 'Erro ao executar ação');
        refetch();
      }
    } catch (err) {
      alert('Erro ao executar ação: ' + (err instanceof Error ? err.message : 'Erro desconhecido'));
      refetch();
    } finally {
      setActionLoading(null);
    }
  };

  const getStatusColor = () => {
    switch (serverStatus) {
      case 'online': return 'text-green-600 dark:text-green-400';
      case 'offline': return 'text-red-600 dark:text-red-400';
      case 'starting': return 'text-yellow-600 dark:text-yellow-400';
      case 'stopping': return 'text-orange-600 dark:text-orange-400';
      default: return 'text-gray-600 dark:text-gray-400';
    }
  };

  const getStatusIcon = () => {
    switch (serverStatus) {
      case 'online': return <Wifi className="w-5 h-5" />;
      case 'offline': return <WifiOff className="w-5 h-5" />;
      default: return <Activity className="w-5 h-5 animate-pulse" />;
    }
  };

  const getStatusText = () => {
    switch (serverStatus) {
      case 'online': return 'Online';
      case 'offline': return 'Offline';
      case 'starting': return 'Iniciando...';
      case 'stopping': return 'Parando...';
      default: return 'Desconhecido';
    }
  };

  if (loading && !statusData) {
    return (
      <div className="flex items-center justify-center h-64">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-minecraft-600"></div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="bg-red-100 dark:bg-red-900/30 border border-red-300 dark:border-red-800 rounded-lg p-4">
        <div className="flex items-center space-x-2">
          <AlertCircle className="w-5 h-5 text-red-600 dark:text-red-400" />
          <p className="text-red-700 dark:text-red-400">Erro ao carregar dados: {error}</p>
        </div>
        <button 
          onClick={refetch}
          className="mt-2 px-3 py-1 bg-red-600 text-white rounded hover:bg-red-700 transition-colors"
        >
          Tentar Novamente
        </button>
      </div>
    );
  }

  const players = statusData?.players || [];
  const playerCount = statusData?.playerCount || 0;
  const maxPlayers = statusData?.maxPlayers || 20;
  const serverInfo = statusData?.serverInfo || {
    ip: '127.0.0.1',
    port: '25565',
    version: 'Prominence II RPG v2.8.0',
    uptime: '0m'
  };

  return (
    <div className="space-y-6">
      {/* Page Header */}
      <div>
        <h2 className="text-2xl font-bold text-gray-900 dark:text-white mb-2">Dashboard</h2>
        <p className="text-gray-600 dark:text-stone-400">
          Monitore e controle seu servidor Minecraft
        </p>
      </div>

      {/* Server Status Cards */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        {/* Status Card */}
        <div className="bg-white dark:bg-stone-800 rounded-xl shadow-sm border border-gray-200 dark:border-stone-700 p-6">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm font-medium text-gray-600 dark:text-stone-400">Status</p>
              <p className={`text-lg font-semibold ${getStatusColor()}`}>
                {getStatusText()}
              </p>
            </div>
            <div className={getStatusColor()}>
              {getStatusIcon()}
            </div>
          </div>
        </div>

        {/* Players Online */}
        <div className="bg-white dark:bg-stone-800 rounded-xl shadow-sm border border-gray-200 dark:border-stone-700 p-6">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm font-medium text-gray-600 dark:text-stone-400">Jogadores</p>
              <p className="text-lg font-semibold text-gray-900 dark:text-white">
                {playerCount}/{maxPlayers}
              </p>
            </div>
            <Users className="w-5 h-5 text-blue-600 dark:text-blue-400" />
          </div>
        </div>

        {/* Server IP */}
        <div className="bg-white dark:bg-stone-800 rounded-xl shadow-sm border border-gray-200 dark:border-stone-700 p-6">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm font-medium text-gray-600 dark:text-stone-400">Servidor</p>
              <p className="text-lg font-semibold text-gray-900 dark:text-white">
                {serverInfo.ip}:{serverInfo.port}
              </p>
            </div>
            <Server className="w-5 h-5 text-purple-600 dark:text-purple-400" />
          </div>
        </div>

        {/* Uptime */}
        <div className="bg-white dark:bg-stone-800 rounded-xl shadow-sm border border-gray-200 dark:border-stone-700 p-6">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm font-medium text-gray-600 dark:text-stone-400">Uptime</p>
              <p className="text-lg font-semibold text-gray-900 dark:text-white">
                {serverInfo.uptime}
              </p>
            </div>
            <Clock className="w-5 h-5 text-minecraft-600 dark:text-minecraft-400" />
          </div>
        </div>
      </div>

      {/* Control Buttons */}
      <div className="bg-white dark:bg-stone-800 rounded-xl shadow-sm border border-gray-200 dark:border-stone-700 p-6">
        <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-4">
          Controle do Servidor
        </h3>
        <div className="flex flex-wrap gap-3">
          <button
            onClick={() => handleServerAction('start')}
            disabled={serverStatus === 'online' || serverStatus === 'starting' || actionLoading === 'start'}
            className="flex items-center space-x-2 px-4 py-2 bg-green-600 hover:bg-green-700 disabled:bg-gray-400 disabled:cursor-not-allowed text-white rounded-lg transition-colors duration-200"
          >
            {actionLoading === 'start' ? (
              <div className="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin" />
            ) : (
              <Play className="w-4 h-4" />
            )}
            <span>Iniciar</span>
          </button>
          
          <button
            onClick={() => handleServerAction('stop')}
            disabled={serverStatus === 'offline' || serverStatus === 'stopping' || actionLoading === 'stop'}
            className="flex items-center space-x-2 px-4 py-2 bg-red-600 hover:bg-red-700 disabled:bg-gray-400 disabled:cursor-not-allowed text-white rounded-lg transition-colors duration-200"
          >
            {actionLoading === 'stop' ? (
              <div className="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin" />
            ) : (
              <Square className="w-4 h-4" />
            )}
            <span>Parar</span>
          </button>
          
          <button
            onClick={() => handleServerAction('restart')}
            disabled={serverStatus === 'starting' || serverStatus === 'stopping' || actionLoading === 'restart'}
            className="flex items-center space-x-2 px-4 py-2 bg-orange-600 hover:bg-orange-700 disabled:bg-gray-400 disabled:cursor-not-allowed text-white rounded-lg transition-colors duration-200"
          >
            {actionLoading === 'restart' ? (
              <div className="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin" />
            ) : (
              <RotateCcw className="w-4 h-4" />
            )}
            <span>Reiniciar</span>
          </button>
        </div>
      </div>

      {/* Players List */}
      <div className="bg-white dark:bg-stone-800 rounded-xl shadow-sm border border-gray-200 dark:border-stone-700 p-6">
        <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-4">
          Jogadores Online
        </h3>
        
        {players.length === 0 ? (
          <div className="text-center py-8">
            <Users className="w-12 h-12 text-gray-400 dark:text-stone-500 mx-auto mb-3" />
            <p className="text-gray-600 dark:text-stone-400">
              Nenhum jogador online
            </p>
          </div>
        ) : (
          <div className="space-y-3">
            {players.map((player, index) => (
              <div key={index} className="flex items-center justify-between p-3 bg-gray-50 dark:bg-stone-700 rounded-lg">
                <div className="flex items-center space-x-3">
                  <div className="w-10 h-10 bg-minecraft-600 rounded-lg flex items-center justify-center">
                    <span className="text-white font-medium text-sm">
                      {player.name.charAt(0)}
                    </span>
                  </div>
                  <div>
                    <p className="font-medium text-gray-900 dark:text-white">
                      {player.name}
                    </p>
                    <p className="text-sm text-gray-600 dark:text-stone-400">
                      Online há {player.onlineTime}
                    </p>
                  </div>
                </div>
                <div className="text-right">
                  <p className="text-sm text-gray-600 dark:text-stone-400">
                    Entrou às {player.joinTime}
                  </p>
                </div>
              </div>
            ))}
          </div>
        )}
      </div>

      {/* Server Info */}
      <div className="bg-white dark:bg-stone-800 rounded-xl shadow-sm border border-gray-200 dark:border-stone-700 p-6">
        <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-4">
          Informações do Servidor
        </h3>
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <p className="text-sm font-medium text-gray-600 dark:text-stone-400">Versão</p>
            <p className="text-gray-900 dark:text-white">{serverInfo.version}</p>
          </div>
          <div>
            <p className="text-sm font-medium text-gray-600 dark:text-stone-400">Endereço</p>
            <p className="text-gray-900 dark:text-white">{serverInfo.ip}:{serverInfo.port}</p>
          </div>
        </div>
      </div>
    </div>
  );
};

export default Dashboard;