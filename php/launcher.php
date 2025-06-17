<?php
// Sistema de launcher web - executa tudo pelo navegador
// IMPORTANTE: Este arquivo NÃO requer autenticação pois é usado para INICIAR o sistema

// Configuração de erro mais robusta
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Iniciar buffer de saída para capturar qualquer output indesejado
ob_start();

// Headers primeiro para evitar problemas
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Responder OPTIONS imediatamente
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    ob_clean();
    http_response_code(200);
    echo json_encode(['status' => 'ok']);
    exit;
}

// Função para resposta de erro padronizada
function sendError($message, $code = 500, $details = []) {
    ob_clean(); // Limpar qualquer output anterior
    http_response_code($code);
    echo json_encode([
        'error' => $message,
        'details' => $details,
        'timestamp' => date('Y-m-d H:i:s'),
        'php_version' => PHP_VERSION,
        'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'
    ]);
    exit;
}

// Função para resposta de sucesso padronizada
function sendSuccess($data) {
    ob_clean(); // Limpar qualquer output anterior
    http_response_code(200);
    echo json_encode($data);
    exit;
}

try {
    // Verificar se o sistema foi instalado
    if (!file_exists(__DIR__ . '/installed.lock')) {
        sendError('Sistema não instalado. Execute install.php primeiro.', 400);
    }

    // Processar requisições
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $rawInput = file_get_contents('php://input');
        
        if (empty($rawInput)) {
            sendError('Dados de entrada vazios', 400, ['raw_input' => $rawInput]);
        }
        
        $input = json_decode($rawInput, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            sendError('JSON inválido: ' . json_last_error_msg(), 400, [
                'raw_input' => substr($rawInput, 0, 200),
                'json_error' => json_last_error()
            ]);
        }
        
        $action = $input['action'] ?? '';
        
        if (empty($action)) {
            sendError('Ação não especificada', 400, ['input' => $input]);
        }
        
        switch ($action) {
            case 'start_dev_server':
                $result = startDevServer();
                sendSuccess($result);
                break;
                
            case 'stop_dev_server':
                $result = stopDevServer();
                sendSuccess($result);
                break;
                
            case 'check_dev_status':
                $result = checkDevServerStatus();
                sendSuccess($result);
                break;
                
            case 'open_browser':
                $result = openBrowser();
                sendSuccess($result);
                break;
                
            default:
                sendError('Ação inválida: ' . $action, 400, ['available_actions' => [
                    'start_dev_server', 'stop_dev_server', 'check_dev_status', 'open_browser'
                ]]);
        }
        
    } elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $status = checkDevServerStatus();
        sendSuccess($status);
    } else {
        sendError('Método não permitido: ' . $_SERVER['REQUEST_METHOD'], 405);
    }

} catch (Exception $e) {
    sendError('Erro interno do servidor', 500, [
        'message' => $e->getMessage(),
        'file' => basename($e->getFile()),
        'line' => $e->getLine(),
        'trace' => array_slice($e->getTrace(), 0, 3) // Apenas primeiras 3 linhas do trace
    ]);
} catch (Error $e) {
    sendError('Erro fatal do PHP', 500, [
        'message' => $e->getMessage(),
        'file' => basename($e->getFile()),
        'line' => $e->getLine()
    ]);
}

function startDevServer() {
    $logs = [];
    
    try {
        $logs[] = ['step' => 'init', 'message' => 'Iniciando verificações do sistema...', 'status' => 'info'];
        
        // Determinar caminho do projeto de forma mais robusta
        $projectPath = realpath(dirname(dirname(__FILE__)));
        if (!$projectPath) {
            $projectPath = dirname(dirname(__FILE__));
        }
        
        // Normalizar separadores de diretório para Windows
        $projectPath = str_replace('/', DIRECTORY_SEPARATOR, $projectPath);
        
        $logs[] = ['step' => 'init', 'message' => "Caminho do projeto: {$projectPath}", 'status' => 'info'];
        
        // Verificar se o diretório existe
        if (!is_dir($projectPath)) {
            $logs[] = ['step' => 'init', 'message' => 'Diretório do projeto não encontrado', 'status' => 'error'];
            return [
                'success' => false,
                'message' => 'Diretório do projeto não encontrado: ' . $projectPath,
                'logs' => $logs
            ];
        }
        
        // Verificar se Node.js está instalado - VERSÃO MELHORADA PARA WINDOWS
        $logs[] = ['step' => 'node_check', 'message' => 'Verificando instalação do Node.js...', 'status' => 'info'];
        
        $nodeResult = checkNodeInstallation();
        if (!$nodeResult['success']) {
            $logs[] = ['step' => 'node_check', 'message' => 'Node.js não encontrado no sistema', 'status' => 'error'];
            $logs[] = ['step' => 'node_check', 'message' => 'Detalhes: ' . $nodeResult['error'], 'status' => 'error'];
            return [
                'success' => false, 
                'message' => 'Node.js não encontrado. Instale o Node.js primeiro.',
                'install_url' => 'https://nodejs.org/',
                'logs' => $logs,
                'debug_info' => $nodeResult,
                'manual_command' => 'Baixe e instale o Node.js de https://nodejs.org/'
            ];
        }
        
        $nodeVersion = $nodeResult['version'];
        $logs[] = ['step' => 'node_check', 'message' => "Node.js encontrado: {$nodeVersion}", 'status' => 'success'];
        
        // Verificar se npm está disponível - VERSÃO MELHORADA
        $logs[] = ['step' => 'npm_check', 'message' => 'Verificando instalação do NPM...', 'status' => 'info'];
        
        $npmResult = checkNpmInstallation();
        if (!$npmResult['success']) {
            $logs[] = ['step' => 'npm_check', 'message' => 'NPM não encontrado no sistema', 'status' => 'error'];
            $logs[] = ['step' => 'npm_check', 'message' => 'Detalhes: ' . $npmResult['error'], 'status' => 'error'];
            return [
                'success' => false, 
                'message' => 'NPM não encontrado. Reinstale o Node.js.',
                'install_url' => 'https://nodejs.org/',
                'logs' => $logs,
                'debug_info' => $npmResult
            ];
        }
        
        $npmVersion = $npmResult['version'];
        $logs[] = ['step' => 'npm_check', 'message' => "NPM encontrado: {$npmVersion}", 'status' => 'success'];
        
        // Verificar se package.json existe
        $logs[] = ['step' => 'project_check', 'message' => 'Verificando estrutura do projeto...', 'status' => 'info'];
        $packageJsonPath = $projectPath . DIRECTORY_SEPARATOR . 'package.json';
        
        if (!file_exists($packageJsonPath)) {
            $logs[] = ['step' => 'project_check', 'message' => 'Arquivo package.json não encontrado', 'status' => 'error'];
            
            // Listar arquivos no diretório para debug
            $files = is_dir($projectPath) ? scandir($projectPath) : [];
            $logs[] = ['step' => 'project_check', 'message' => 'Arquivos encontrados: ' . implode(', ', array_slice($files, 0, 10)), 'status' => 'info'];
            
            return [
                'success' => false, 
                'message' => 'Arquivo package.json não encontrado no projeto.',
                'logs' => $logs,
                'project_path' => $projectPath,
                'expected_file' => $packageJsonPath,
                'files_found' => $files
            ];
        }
        
        $logs[] = ['step' => 'project_check', 'message' => 'Estrutura do projeto verificada com sucesso', 'status' => 'success'];
        
        // Verificar se já está rodando
        $logs[] = ['step' => 'status_check', 'message' => 'Verificando se servidor já está rodando...', 'status' => 'info'];
        $status = checkDevServerStatus();
        if ($status['running']) {
            $logs[] = ['step' => 'status_check', 'message' => 'Servidor já está rodando na porta 5173', 'status' => 'success'];
            $logs[] = ['step' => 'complete', 'message' => 'Sistema já estava rodando!', 'status' => 'success'];
            return [
                'success' => true, 
                'message' => 'Servidor de desenvolvimento já está rodando!',
                'url' => 'http://localhost:5173',
                'logs' => $logs,
                'already_running' => true
            ];
        }
        
        $logs[] = ['step' => 'status_check', 'message' => 'Porta 5173 está livre para uso', 'status' => 'success'];
        
        // Limpeza de porta
        $logs[] = ['step' => 'port_cleanup', 'message' => 'Verificando processos antigos na porta 5173...', 'status' => 'info'];
        $cleanupResult = cleanupPort5173();
        if ($cleanupResult['cleaned']) {
            $logs[] = ['step' => 'port_cleanup', 'message' => 'Processos antigos removidos da porta 5173', 'status' => 'success'];
        } else {
            $logs[] = ['step' => 'port_cleanup', 'message' => 'Porta 5173 está limpa', 'status' => 'success'];
        }
        
        // Verificar dependências
        $logs[] = ['step' => 'deps_check', 'message' => 'Verificando dependências do projeto...', 'status' => 'info'];
        $nodeModulesPath = $projectPath . DIRECTORY_SEPARATOR . 'node_modules';
        
        if (!is_dir($nodeModulesPath)) {
            $logs[] = ['step' => 'deps_install', 'message' => 'Dependências não encontradas. Iniciando instalação...', 'status' => 'info'];
            $logs[] = ['step' => 'deps_install', 'message' => 'Executando: npm install (isso pode demorar alguns minutos)', 'status' => 'info'];
            
            $installResult = executeCommandInDirectory($projectPath, 'npm install --no-audit --no-fund', 300);
            
            if (!$installResult['success']) {
                $logs[] = ['step' => 'deps_install', 'message' => 'Erro durante instalação das dependências', 'status' => 'error'];
                $logs[] = ['step' => 'deps_install', 'message' => 'Output: ' . substr($installResult['output'], 0, 200), 'status' => 'error'];
                return [
                    'success' => false, 
                    'message' => 'Erro ao instalar dependências.',
                    'logs' => $logs,
                    'install_output' => $installResult['output'],
                    'manual_command' => "cd \"{$projectPath}\" && npm install"
                ];
            }
            
            $logs[] = ['step' => 'deps_install', 'message' => 'Dependências instaladas com sucesso!', 'status' => 'success'];
        } else {
            $logs[] = ['step' => 'deps_check', 'message' => 'Dependências já estão instaladas', 'status' => 'success'];
        }
        
        // Iniciar servidor
        $logs[] = ['step' => 'server_start', 'message' => 'Iniciando servidor de desenvolvimento...', 'status' => 'info'];
        
        $startResult = startDevServerProcess($projectPath);
        if (!$startResult['success']) {
            $logs[] = ['step' => 'server_start', 'message' => 'Erro ao iniciar processo: ' . $startResult['error'], 'status' => 'error'];
            return [
                'success' => false,
                'message' => 'Erro ao iniciar servidor: ' . $startResult['error'],
                'logs' => $logs,
                'manual_command' => "cd \"{$projectPath}\" && npm run dev",
                'start_details' => $startResult
            ];
        }
        
        $logs[] = ['step' => 'server_start', 'message' => 'Processo iniciado com sucesso', 'status' => 'success'];
        
        // Aguardar inicialização
        $logs[] = ['step' => 'server_wait', 'message' => 'Aguardando servidor inicializar...', 'status' => 'info'];
        sleep(5);
        
        // Verificar se está respondendo
        $logs[] = ['step' => 'server_verify', 'message' => 'Verificando se servidor está respondendo...', 'status' => 'info'];
        
        $attempts = 0;
        $maxAttempts = 20;
        
        while ($attempts < $maxAttempts) {
            $newStatus = checkDevServerStatus();
            if ($newStatus['running']) {
                $logs[] = ['step' => 'server_verify', 'message' => "Servidor respondendo após " . ($attempts + 1) . " tentativas!", 'status' => 'success'];
                $logs[] = ['step' => 'complete', 'message' => 'Sistema iniciado com sucesso!', 'status' => 'success'];
                
                return [
                    'success' => true, 
                    'message' => 'Servidor de desenvolvimento iniciado com sucesso!',
                    'url' => 'http://localhost:5173',
                    'logs' => $logs,
                    'node_version' => $nodeVersion,
                    'npm_version' => $npmVersion,
                    'attempts' => $attempts + 1
                ];
            }
            
            sleep(2);
            $attempts++;
        }
        
        // Se chegou aqui, não conseguiu iniciar
        $logs[] = ['step' => 'server_verify', 'message' => 'Servidor não respondeu após todas as tentativas', 'status' => 'warning'];
        
        return [
            'success' => false, 
            'message' => 'Servidor iniciado mas não está respondendo. Tente executar manualmente.',
            'logs' => $logs,
            'manual_command' => "cd \"{$projectPath}\" && npm run dev",
            'troubleshooting' => [
                'Verifique se não há antivírus bloqueando o Node.js',
                'Tente executar o comando manual no terminal',
                'Verifique se a porta 5173 não está sendo usada',
                'Reinicie o computador se necessário'
            ]
        ];
        
    } catch (Exception $e) {
        $logs[] = ['step' => 'error', 'message' => 'Erro durante execução: ' . $e->getMessage(), 'status' => 'error'];
        return [
            'success' => false, 
            'message' => 'Erro ao iniciar servidor: ' . $e->getMessage(),
            'logs' => $logs,
            'exception_details' => [
                'file' => basename($e->getFile()),
                'line' => $e->getLine()
            ]
        ];
    }
}

// NOVA FUNÇÃO: Verificação robusta do Node.js para Windows
function checkNodeInstallation() {
    $attempts = [
        'node --version',
        'node.exe --version',
        '"C:\\Program Files\\nodejs\\node.exe" --version',
        '"C:\\Program Files (x86)\\nodejs\\node.exe" --version'
    ];
    
    foreach ($attempts as $command) {
        $result = executeCommandWindows($command, 10);
        if ($result['success'] && !empty($result['output']) && preg_match('/^v\d+\.\d+\.\d+/', trim($result['output']))) {
            return [
                'success' => true,
                'version' => trim($result['output']),
                'command_used' => $command
            ];
        }
    }
    
    // Tentar encontrar Node.js no PATH
    $pathResult = executeCommandWindows('where node', 5);
    if ($pathResult['success'] && !empty($pathResult['output'])) {
        $nodePath = trim(explode("\n", $pathResult['output'])[0]);
        $versionResult = executeCommandWindows("\"{$nodePath}\" --version", 5);
        if ($versionResult['success'] && !empty($versionResult['output'])) {
            return [
                'success' => true,
                'version' => trim($versionResult['output']),
                'path' => $nodePath
            ];
        }
    }
    
    return [
        'success' => false,
        'error' => 'Node.js não encontrado em nenhum local padrão',
        'attempts' => $attempts,
        'path_check' => $pathResult ?? null
    ];
}

// NOVA FUNÇÃO: Verificação robusta do NPM para Windows
function checkNpmInstallation() {
    $attempts = [
        'npm --version',
        'npm.cmd --version',
        '"C:\\Program Files\\nodejs\\npm.cmd" --version',
        '"C:\\Program Files (x86)\\nodejs\\npm.cmd" --version'
    ];
    
    foreach ($attempts as $command) {
        $result = executeCommandWindows($command, 10);
        if ($result['success'] && !empty($result['output']) && preg_match('/^\d+\.\d+\.\d+/', trim($result['output']))) {
            return [
                'success' => true,
                'version' => trim($result['output']),
                'command_used' => $command
            ];
        }
    }
    
    // Tentar encontrar NPM no PATH
    $pathResult = executeCommandWindows('where npm', 5);
    if ($pathResult['success'] && !empty($pathResult['output'])) {
        $npmPath = trim(explode("\n", $pathResult['output'])[0]);
        $versionResult = executeCommandWindows("\"{$npmPath}\" --version", 5);
        if ($versionResult['success'] && !empty($versionResult['output'])) {
            return [
                'success' => true,
                'version' => trim($versionResult['output']),
                'path' => $npmPath
            ];
        }
    }
    
    return [
        'success' => false,
        'error' => 'NPM não encontrado em nenhum local padrão',
        'attempts' => $attempts,
        'path_check' => $pathResult ?? null
    ];
}

// NOVA FUNÇÃO: Execução de comandos otimizada para Windows
function executeCommandWindows($command, $timeout = 30) {
    // Usar cmd.exe explicitamente para garantir compatibilidade
    $fullCommand = "cmd /c \"{$command}\" 2>&1";
    
    $descriptorspec = [
        0 => ["pipe", "r"],  // stdin
        1 => ["pipe", "w"],  // stdout
        2 => ["pipe", "w"]   // stderr
    ];
    
    $process = proc_open($fullCommand, $descriptorspec, $pipes, null, null, ['bypass_shell' => false]);
    
    if (is_resource($process)) {
        $start = time();
        $output = '';
        $error = '';
        
        // Fechar stdin
        fclose($pipes[0]);
        
        // Ler output com timeout
        while (time() - $start < $timeout) {
            $status = proc_get_status($process);
            if (!$status['running']) {
                break;
            }
            
            $read = [$pipes[1], $pipes[2]];
            $write = null;
            $except = null;
            
            if (stream_select($read, $write, $except, 1)) {
                if (in_array($pipes[1], $read)) {
                    $output .= fread($pipes[1], 8192);
                }
                if (in_array($pipes[2], $read)) {
                    $error .= fread($pipes[2], 8192);
                }
            }
        }
        
        // Ler qualquer output restante
        $output .= stream_get_contents($pipes[1]);
        $error .= stream_get_contents($pipes[2]);
        
        fclose($pipes[1]);
        fclose($pipes[2]);
        
        $exitCode = proc_close($process);
        
        return [
            'success' => $exitCode === 0,
            'output' => trim($output),
            'error' => trim($error),
            'exit_code' => $exitCode,
            'command' => $fullCommand
        ];
    }
    
    return [
        'success' => false,
        'output' => '',
        'error' => 'Falha ao criar processo',
        'exit_code' => -1,
        'command' => $fullCommand
    ];
}

function executeCommand($command, $timeout = 30) {
    return executeCommandWindows($command, $timeout);
}

function executeCommandInDirectory($directory, $command, $timeout = 60) {
    $fullCommand = "cd /d \"{$directory}\" && {$command}";
    return executeCommandWindows($fullCommand, $timeout);
}

function startDevServerProcess($projectPath) {
    try {
        // Método 1: PowerShell Start-Process (mais confiável)
        $psCommand = "powershell -Command \"Start-Process cmd -ArgumentList '/c', 'cd /d \\\"{$projectPath}\\\" && npm run dev' -WindowStyle Hidden\"";
        
        $result = executeCommandWindows($psCommand, 10);
        
        if ($result['success']) {
            return ['success' => true, 'method' => 'powershell', 'command' => $psCommand];
        }
        
        // Método 2: CMD start (alternativa)
        $cmdCommand = "start /B cmd /c \"cd /d \"{$projectPath}\" && npm run dev\"";
        $result2 = executeCommandWindows($cmdCommand, 5);
        
        if ($result2['success']) {
            return ['success' => true, 'method' => 'cmd', 'command' => $cmdCommand];
        }
        
        // Método 3: Execução direta com popen
        $directCommand = "cd /d \"{$projectPath}\" && npm run dev";
        $handle = popen($directCommand, 'r');
        
        if ($handle) {
            // Não fechar o handle para manter o processo rodando
            return ['success' => true, 'method' => 'popen', 'command' => $directCommand];
        }
        
        return [
            'success' => false, 
            'error' => 'Todos os métodos de inicialização falharam',
            'attempts' => [
                'powershell' => $result,
                'cmd' => $result2,
                'popen' => 'failed'
            ]
        ];
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

function cleanupPort5173() {
    $cleaned = false;
    
    try {
        // Windows: Verificar e matar processos na porta 5173
        $netstatResult = executeCommandWindows('netstat -ano | findstr :5173', 10);
        
        if ($netstatResult['success'] && !empty($netstatResult['output'])) {
            $lines = explode("\n", $netstatResult['output']);
            foreach ($lines as $line) {
                if (strpos($line, 'LISTENING') !== false) {
                    preg_match('/\s+(\d+)$/', $line, $matches);
                    if (isset($matches[1])) {
                        $pid = $matches[1];
                        executeCommandWindows("taskkill /F /PID {$pid}", 5);
                        $cleaned = true;
                    }
                }
            }
        }
    } catch (Exception $e) {
        // Ignorar erros de limpeza
    }
    
    return ['cleaned' => $cleaned];
}

function stopDevServer() {
    $logs = [];
    
    try {
        $killed = false;
        
        $logs[] = ['step' => 'stop_init', 'message' => 'Iniciando processo de parada...', 'status' => 'info'];
        
        // Windows: Parar processos na porta 5173
        $logs[] = ['step' => 'stop_port', 'message' => 'Verificando processos na porta 5173...', 'status' => 'info'];
        $netstatResult = executeCommandWindows('netstat -ano | findstr :5173', 10);
        
        if ($netstatResult['success'] && !empty($netstatResult['output'])) {
            $lines = explode("\n", $netstatResult['output']);
            foreach ($lines as $line) {
                if (strpos($line, 'LISTENING') !== false) {
                    preg_match('/\s+(\d+)$/', $line, $matches);
                    if (isset($matches[1])) {
                        $pid = $matches[1];
                        $logs[] = ['step' => 'stop_process', 'message' => "Finalizando processo PID: {$pid}", 'status' => 'info'];
                        executeCommandWindows("taskkill /F /PID {$pid}", 5);
                        $killed = true;
                    }
                }
            }
        }
        
        // Parar processos node.exe
        $logs[] = ['step' => 'stop_node', 'message' => 'Verificando processos Node.js...', 'status' => 'info'];
        $nodeResult = executeCommandWindows('tasklist /FI "IMAGENAME eq node.exe"', 10);
        if ($nodeResult['success'] && strpos($nodeResult['output'], 'node.exe') !== false) {
            $logs[] = ['step' => 'stop_node', 'message' => 'Finalizando processos Node.js...', 'status' => 'info'];
            executeCommandWindows('taskkill /F /IM node.exe', 5);
            $killed = true;
        }
        
        if ($killed) {
            $logs[] = ['step' => 'stop_complete', 'message' => 'Servidor parado com sucesso!', 'status' => 'success'];
            sleep(2);
            
            return [
                'success' => true, 
                'message' => 'Servidor de desenvolvimento parado com sucesso!',
                'logs' => $logs
            ];
        } else {
            $logs[] = ['step' => 'stop_notfound', 'message' => 'Nenhum processo encontrado', 'status' => 'warning'];
            
            return [
                'success' => false, 
                'message' => 'Nenhum servidor encontrado rodando na porta 5173.',
                'logs' => $logs
            ];
        }
        
    } catch (Exception $e) {
        $logs[] = ['step' => 'stop_error', 'message' => 'Erro: ' . $e->getMessage(), 'status' => 'error'];
        return [
            'success' => false, 
            'message' => 'Erro ao parar servidor: ' . $e->getMessage(),
            'logs' => $logs
        ];
    }
}

function checkDevServerStatus() {
    try {
        // Tentar conectar na porta 5173
        $socket = @fsockopen('localhost', 5173, $errno, $errstr, 3);
        if ($socket) {
            fclose($socket);
            
            // Verificar se responde HTTP
            $context = stream_context_create([
                'http' => [
                    'timeout' => 5,
                    'ignore_errors' => true,
                    'method' => 'GET'
                ]
            ]);
            
            $response = @file_get_contents('http://localhost:5173', false, $context);
            
            if ($response !== false) {
                return [
                    'running' => true,
                    'url' => 'http://localhost:5173',
                    'message' => 'Servidor rodando e respondendo'
                ];
            }
        }
        
        return [
            'running' => false,
            'message' => 'Servidor não está rodando'
        ];
        
    } catch (Exception $e) {
        return [
            'running' => false,
            'message' => 'Erro ao verificar status: ' . $e->getMessage()
        ];
    }
}

function openBrowser() {
    try {
        $url = 'http://localhost:5173';
        $command = "start \"\" \"{$url}\"";
        
        $result = executeCommandWindows($command, 5);
        
        return [
            'success' => true,
            'message' => 'Comando para abrir navegador executado!',
            'url' => $url
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Erro ao abrir navegador: ' . $e->getMessage()
        ];
    }
}

// Finalizar buffer de saída
ob_end_flush();
?>