import React, { useState, useEffect } from 'react';
import { Save, FolderOpen, Server, Key, Shield, AlertTriangle, Eye, EyeOff, CheckCircle } from 'lucide-react';
import { useApi, apiCall } from '../hooks/useApi';

interface ServerSettings {
  server_path: string;
  server_port: string;
  log_path: string;
  rcon_enabled: string;
  rcon_ip: string;
  rcon_port: string;
  rcon_password: string;
  auto_start: string;
  auto_restart: string;
  max_players: string;
  difficulty: string;
}

const Settings: React.FC = () => {
  const [settings, setSettings] = useState<ServerSettings>({
    server_path: '',
    server_port: '25565',
    log_path: '',
    rcon_enabled: '1',
    rcon_ip: '127.0.0.1',
    rcon_port: '25575',
    rcon_password: '',
    auto_start: '0',
    auto_restart: '1',
    max_players: '20',
    difficulty: 'normal'
  });

  const [showPassword, setShowPassword] = useState(false);
  const [hasChanges, setHasChanges] = useState(false);
  const [saving, setSaving] = useState(false);
  const [testing, setTesting] = useState(false);

  const { data: serverSettings, loading, error, refetch } = useApi<ServerSettings>('settings.php');

  useEffect(() => {
    if (serverSettings) {
      setSettings(serverSettings);
    }
  }, [serverSettings]);

  const handleSettingChange = (key: keyof ServerSettings, value: string) => {
    setSettings(prev => ({
      ...prev,
      [key]: value
    }));
    setHasChanges(true);
  };

  const handleSave = async () => {
    setSaving(true);
    try {
      const result = await apiCall('settings.php', {
        method: 'POST',
        body: JSON.stringify(settings)
      });

      if (result.success) {
        setHasChanges(false);
        alert('Configurações salvas com sucesso!');
      } else {
        alert(result.message || 'Erro ao salvar configurações');
      }
    } catch (err) {
      alert('Erro ao salvar configurações: ' + (err instanceof Error ? err.message : 'Erro desconhecido'));
    } finally {
      setSaving(false);
    }
  };

  const handleReset = () => {
    if (serverSettings) {
      setSettings(serverSettings);
      setHasChanges(false);
    }
  };

  const handleTestConnection = async () => {
    if (settings.rcon_enabled !== '1') {
      alert('RCON não está habilitado');
      return;
    }

    setTesting(true);
    try {
      // Test RCON connection
      const result = await apiCall('console.php', {
        method: 'POST',
        body: JSON.stringify({ 
          action: 'execute', 
          command: '/list' 
        })
      });

      if (result.success) {
        alert('Conexão RCON estabelecida com sucesso!');
      } else {
        alert('Falha na conexão RCON: ' + result.response);
      }
    } catch (err) {
      alert('Erro ao testar conexão RCON: ' + (err instanceof Error ? err.message : 'Erro desconhecido'));
    } finally {
      setTesting(false);
    }
  };

  const handleFileSelect = (inputId: string, settingKey: keyof ServerSettings) => {
    // Create a hidden file input
    const fileInput = document.createElement('input');
    fileInput.type = 'file';
    fileInput.style.display = 'none';
    
    // Set appropriate file filters based on the setting
    if (settingKey === 'server_path') {
      fileInput.accept = '.bat,.sh,.exe';
    } else if (settingKey === 'log_path') {
      fileInput.accept = '.log,.txt';
    }
    
    fileInput.onchange = (e) => {
      const target = e.target as HTMLInputElement;
      if (target.files && target.files[0]) {
        const file = target.files[0];
        const filePath = file.webkitRelativePath || file.name;
        
        // For demonstration, we'll use the file name
        // In a real implementation, you'd need to handle the full path
        handleSettingChange(settingKey, filePath);
        
        // Show a helpful message
        alert(`Arquivo selecionado: ${file.name}\n\nNota: Em um ambiente de produção, você precisaria configurar o caminho completo manualmente.`);
      }
      
      // Clean up
      document.body.removeChild(fileInput);
    };
    
    // Add to DOM and trigger click
    document.body.appendChild(fileInput);
    fileInput.click();
  };

  const handleFolderSelect = (settingKey: keyof ServerSettings) => {
    // Create a hidden file input with directory selection
    const folderInput = document.createElement('input');
    folderInput.type = 'file';
    folderInput.style.display = 'none';
    folderInput.setAttribute('webkitdirectory', '');
    folderInput.setAttribute('directory', '');
    
    folderInput.onchange = (e) => {
      const target = e.target as HTMLInputElement;
      if (target.files && target.files[0]) {
        const file = target.files[0];
        const folderPath = file.webkitRelativePath.split('/')[0];
        
        // For demonstration, we'll use the folder name
        handleSettingChange(settingKey, folderPath);
        
        // Show a helpful message
        alert(`Pasta selecionada: ${folderPath}\n\nNota: Configure o caminho completo manualmente nas configurações.`);
      }
      
      // Clean up
      document.body.removeChild(folderInput);
    };
    
    // Add to DOM and trigger click
    document.body.appendChild(folderInput);
    folderInput.click();
  };

  const getCommonPaths = () => {
    return {
      serverPaths: [
        'C:\\Minecraft\\Prominence II RPG\\start.bat',
        'C:\\Users\\%USERNAME%\\Desktop\\Minecraft\\start.bat',
        'D:\\Games\\Minecraft\\Prominence II RPG\\start.bat',
        'C:\\Games\\Minecraft Server\\start.bat'
      ],
      logPaths: [
        'C:\\Minecraft\\Prominence II RPG\\logs\\latest.log',
        'C:\\Users\\%USERNAME%\\Desktop\\Minecraft\\logs\\latest.log',
        'D:\\Games\\Minecraft\\Prominence II RPG\\logs\\latest.log',
        'C:\\Games\\Minecraft Server\\logs\\latest.log'
      ]
    };
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center h-64">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-minecraft-600"></div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="bg-red-100 dark:bg-red-900/30 border border-red-300 dark:border-red-800 rounded-lg p-4">
        <p className="text-red-700 dark:text-red-400">Erro ao carregar configurações: {error}</p>
        <button 
          onClick={refetch}
          className="mt-2 px-3 py-1 bg-red-600 text-white rounded hover:bg-red-700"
        >
          Tentar Novamente
        </button>
      </div>
    );
  }

  const commonPaths = getCommonPaths();

  return (
    <div className="space-y-6">
      {/* Page Header */}
      <div>
        <h2 className="text-2xl font-bold text-gray-900 dark:text-white mb-2">Configurações</h2>
        <p className="text-gray-600 dark:text-stone-400">
          Configure os parâmetros do servidor e sistema de monitoramento
        </p>
      </div>

      {/* Save Banner */}
      {hasChanges && (
        <div className="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
          <div className="flex items-center justify-between">
            <div className="flex items-center space-x-2">
              <AlertTriangle className="w-5 h-5 text-yellow-600 dark:text-yellow-400" />
              <span className="text-yellow-700 dark:text-yellow-300">
                Você tem alterações não salvas
              </span>
            </div>
            <div className="flex space-x-2">
              <button
                onClick={handleReset}
                className="px-3 py-1 text-sm text-yellow-700 dark:text-yellow-300 hover:bg-yellow-100 dark:hover:bg-yellow-900/40 rounded transition-colors"
              >
                Descartar
              </button>
              <button
                onClick={handleSave}
                disabled={saving}
                className="px-3 py-1 text-sm bg-yellow-600 hover:bg-yellow-700 disabled:bg-yellow-400 text-white rounded transition-colors"
              >
                {saving ? 'Salvando...' : 'Salvar Agora'}
              </button>
            </div>
          </div>
        </div>
      )}

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* Server Configuration */}
        <div className="bg-white dark:bg-stone-800 rounded-xl shadow-sm border border-gray-200 dark:border-stone-700 p-6">
          <div className="flex items-center space-x-2 mb-6">
            <Server className="w-5 h-5 text-minecraft-600 dark:text-minecraft-400" />
            <h3 className="text-lg font-semibold text-gray-900 dark:text-white">
              Configurações do Servidor
            </h3>
          </div>

          <div className="space-y-4">
            {/* Server Path */}
            <div>
              <label className="block text-sm font-medium text-gray-700 dark:text-stone-300 mb-2">
                Caminho do Arquivo .bat
              </label>
              <div className="space-y-2">
                <div className="flex space-x-2">
                  <input
                    type="text"
                    value={settings.server_path}
                    onChange={(e) => handleSettingChange('server_path', e.target.value)}
                    className="flex-1 px-3 py-2 border border-gray-300 dark:border-stone-600 rounded-lg focus:ring-2 focus:ring-minecraft-500 focus:border-minecraft-500 dark:bg-stone-700 dark:text-white"
                    placeholder="C:\Minecraft\Prominence II RPG\start.bat"
                  />
                  <button 
                    onClick={() => handleFileSelect('server_path', 'server_path')}
                    className="flex items-center space-x-1 px-3 py-2 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 hover:bg-blue-200 dark:hover:bg-blue-900/50 rounded-lg transition-colors"
                    title="Selecionar arquivo .bat"
                  >
                    <FolderOpen className="w-4 h-4" />
                    <span className="hidden sm:inline">Procurar</span>
                  </button>
                </div>
                
                {/* Quick Select for Common Paths */}
                <div className="text-xs text-gray-600 dark:text-stone-400">
                  <span className="font-medium">Caminhos comuns:</span>
                  <div className="mt-1 space-y-1">
                    {commonPaths.serverPaths.map((path, index) => (
                      <button
                        key={index}
                        onClick={() => handleSettingChange('server_path', path)}
                        className="block w-full text-left px-2 py-1 hover:bg-gray-100 dark:hover:bg-stone-700 rounded text-xs font-mono"
                      >
                        {path}
                      </button>
                    ))}
                  </div>
                </div>
              </div>
            </div>

            {/* Server Port */}
            <div>
              <label className="block text-sm font-medium text-gray-700 dark:text-stone-300 mb-2">
                Porta do Servidor
              </label>
              <input
                type="number"
                value={settings.server_port}
                onChange={(e) => handleSettingChange('server_port', e.target.value)}
                className="w-full px-3 py-2 border border-gray-300 dark:border-stone-600 rounded-lg focus:ring-2 focus:ring-minecraft-500 focus:border-minecraft-500 dark:bg-stone-700 dark:text-white"
              />
            </div>

            {/* Log Path */}
            <div>
              <label className="block text-sm font-medium text-gray-700 dark:text-stone-300 mb-2">
                Caminho do Arquivo de Log
              </label>
              <div className="space-y-2">
                <div className="flex space-x-2">
                  <input
                    type="text"
                    value={settings.log_path}
                    onChange={(e) => handleSettingChange('log_path', e.target.value)}
                    className="flex-1 px-3 py-2 border border-gray-300 dark:border-stone-600 rounded-lg focus:ring-2 focus:ring-minecraft-500 focus:border-minecraft-500 dark:bg-stone-700 dark:text-white"
                    placeholder="C:\Minecraft\Prominence II RPG\logs\latest.log"
                  />
                  <button 
                    onClick={() => handleFileSelect('log_path', 'log_path')}
                    className="flex items-center space-x-1 px-3 py-2 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 hover:bg-blue-200 dark:hover:bg-blue-900/50 rounded-lg transition-colors"
                    title="Selecionar arquivo de log"
                  >
                    <FolderOpen className="w-4 h-4" />
                    <span className="hidden sm:inline">Procurar</span>
                  </button>
                </div>
                
                {/* Quick Select for Common Log Paths */}
                <div className="text-xs text-gray-600 dark:text-stone-400">
                  <span className="font-medium">Caminhos comuns:</span>
                  <div className="mt-1 space-y-1">
                    {commonPaths.logPaths.map((path, index) => (
                      <button
                        key={index}
                        onClick={() => handleSettingChange('log_path', path)}
                        className="block w-full text-left px-2 py-1 hover:bg-gray-100 dark:hover:bg-stone-700 rounded text-xs font-mono"
                      >
                        {path}
                      </button>
                    ))}
                  </div>
                </div>
              </div>
            </div>

            {/* Max Players */}
            <div>
              <label className="block text-sm font-medium text-gray-700 dark:text-stone-300 mb-2">
                Máximo de Jogadores
              </label>
              <input
                type="number"
                value={settings.max_players}
                onChange={(e) => handleSettingChange('max_players', e.target.value)}
                className="w-full px-3 py-2 border border-gray-300 dark:border-stone-600 rounded-lg focus:ring-2 focus:ring-minecraft-500 focus:border-minecraft-500 dark:bg-stone-700 dark:text-white"
              />
            </div>

            {/* Difficulty */}
            <div>
              <label className="block text-sm font-medium text-gray-700 dark:text-stone-300 mb-2">
                Dificuldade
              </label>
              <select
                value={settings.difficulty}
                onChange={(e) => handleSettingChange('difficulty', e.target.value)}
                className="w-full px-3 py-2 border border-gray-300 dark:border-stone-600 rounded-lg focus:ring-2 focus:ring-minecraft-500 focus:border-minecraft-500 dark:bg-stone-700 dark:text-white"
              >
                <option value="peaceful">Pacífico</option>
                <option value="easy">Fácil</option>
                <option value="normal">Normal</option>
                <option value="hard">Difícil</option>
              </select>
            </div>
          </div>
        </div>

        {/* RCON Configuration */}
        <div className="bg-white dark:bg-stone-800 rounded-xl shadow-sm border border-gray-200 dark:border-stone-700 p-6">
          <div className="flex items-center space-x-2 mb-6">
            <Key className="w-5 h-5 text-blue-600 dark:text-blue-400" />
            <h3 className="text-lg font-semibold text-gray-900 dark:text-white">
              Configurações RCON
            </h3>
          </div>

          <div className="space-y-4">
            {/* Enable RCON */}
            <div className="flex items-center space-x-3">
              <input
                type="checkbox"
                id="rconEnabled"
                checked={settings.rcon_enabled === '1'}
                onChange={(e) => handleSettingChange('rcon_enabled', e.target.checked ? '1' : '0')}
                className="rounded border-gray-300 text-minecraft-600 focus:ring-minecraft-500"
              />
              <label htmlFor="rconEnabled" className="text-sm font-medium text-gray-700 dark:text-stone-300">
                Habilitar RCON
              </label>
            </div>

            {settings.rcon_enabled === '1' && (
              <>
                {/* RCON IP */}
                <div>
                  <label className="block text-sm font-medium text-gray-700 dark:text-stone-300 mb-2">
                    IP do RCON
                  </label>
                  <input
                    type="text"
                    value={settings.rcon_ip}
                    onChange={(e) => handleSettingChange('rcon_ip', e.target.value)}
                    className="w-full px-3 py-2 border border-gray-300 dark:border-stone-600 rounded-lg focus:ring-2 focus:ring-minecraft-500 focus:border-minecraft-500 dark:bg-stone-700 dark:text-white"
                  />
                </div>

                {/* RCON Port */}
                <div>
                  <label className="block text-sm font-medium text-gray-700 dark:text-stone-300 mb-2">
                    Porta do RCON
                  </label>
                  <input
                    type="number"
                    value={settings.rcon_port}
                    onChange={(e) => handleSettingChange('rcon_port', e.target.value)}
                    className="w-full px-3 py-2 border border-gray-300 dark:border-stone-600 rounded-lg focus:ring-2 focus:ring-minecraft-500 focus:border-minecraft-500 dark:bg-stone-700 dark:text-white"
                  />
                </div>

                {/* RCON Password */}
                <div>
                  <label className="block text-sm font-medium text-gray-700 dark:text-stone-300 mb-2">
                    Senha do RCON
                  </label>
                  <div className="relative">
                    <input
                      type={showPassword ? 'text' : 'password'}
                      value={settings.rcon_password}
                      onChange={(e) => handleSettingChange('rcon_password', e.target.value)}
                      className="w-full px-3 py-2 pr-10 border border-gray-300 dark:border-stone-600 rounded-lg focus:ring-2 focus:ring-minecraft-500 focus:border-minecraft-500 dark:bg-stone-700 dark:text-white"
                    />
                    <button
                      type="button"
                      onClick={() => setShowPassword(!showPassword)}
                      className="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-stone-300"
                    >
                      {showPassword ? <EyeOff className="w-4 h-4" /> : <Eye className="w-4 h-4" />}
                    </button>
                  </div>
                </div>

                {/* Test Connection */}
                <button
                  onClick={handleTestConnection}
                  disabled={testing}
                  className="w-full flex items-center justify-center space-x-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:bg-blue-400 text-white rounded-lg transition-colors"
                >
                  {testing ? (
                    <div className="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin" />
                  ) : (
                    <Shield className="w-4 h-4" />
                  )}
                  <span>{testing ? 'Testando...' : 'Testar Conexão RCON'}</span>
                </button>
              </>
            )}
          </div>
        </div>

        {/* System Settings */}
        <div className="bg-white dark:bg-stone-800 rounded-xl shadow-sm border border-gray-200 dark:border-stone-700 p-6 lg:col-span-2">
          <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-6">
            Configurações do Sistema
          </h3>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div className="space-y-4">
              <div className="flex items-center space-x-3">
                <input
                  type="checkbox"
                  id="autoStart"
                  checked={settings.auto_start === '1'}
                  onChange={(e) => handleSettingChange('auto_start', e.target.checked ? '1' : '0')}
                  className="rounded border-gray-300 text-minecraft-600 focus:ring-minecraft-500"
                />
                <label htmlFor="autoStart" className="text-sm font-medium text-gray-700 dark:text-stone-300">
                  Iniciar servidor automaticamente
                </label>
              </div>

              <div className="flex items-center space-x-3">
                <input
                  type="checkbox"
                  id="autoRestart"
                  checked={settings.auto_restart === '1'}
                  onChange={(e) => handleSettingChange('auto_restart', e.target.checked ? '1' : '0')}
                  className="rounded border-gray-300 text-minecraft-600 focus:ring-minecraft-500"
                />
                <label htmlFor="autoRestart" className="text-sm font-medium text-gray-700 dark:text-stone-300">
                  Reiniciar automaticamente em caso de crash
                </label>
              </div>
            </div>

            <div className="flex items-center justify-end">
              <button
                onClick={handleSave}
                disabled={!hasChanges || saving}
                className="flex items-center space-x-2 px-6 py-3 bg-minecraft-600 hover:bg-minecraft-700 disabled:bg-gray-400 disabled:cursor-not-allowed text-white rounded-lg transition-colors"
              >
                {saving ? (
                  <div className="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin" />
                ) : (
                  <Save className="w-4 h-4" />
                )}
                <span>{saving ? 'Salvando...' : 'Salvar Configurações'}</span>
              </button>
            </div>
          </div>
        </div>

        {/* File Selection Help */}
        <div className="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-6 lg:col-span-2">
          <div className="flex items-start space-x-3">
            <CheckCircle className="w-6 h-6 text-blue-600 dark:text-blue-400 mt-0.5" />
            <div>
              <h4 className="text-lg font-semibold text-blue-900 dark:text-blue-100 mb-2">
                💡 Dicas para Configuração de Arquivos
              </h4>
              <div className="text-blue-800 dark:text-blue-200 space-y-2 text-sm">
                <p><strong>Seletor de Arquivos:</strong> Use os botões "Procurar" para selecionar arquivos facilmente, ou clique nos caminhos sugeridos.</p>
                <p><strong>Arquivo .bat:</strong> Procure por "start.bat", "run.bat" ou similar na pasta do seu servidor Minecraft.</p>
                <p><strong>Arquivo de Log:</strong> Geralmente está em "logs/latest.log" dentro da pasta do servidor.</p>
                <p><strong>Caminhos Manuais:</strong> Você também pode digitar o caminho completo diretamente nos campos.</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default Settings;