<?php
// Sistema de launcher web - executa tudo pelo navegador
// IMPORTANTE: Este arquivo NÃO requer autenticação pois é usado para INICIAR o sistema

// Habilitar exibição de erros para debug
error_reporting(E_ALL);
ini_set('display_errors', 0); // Não mostrar erros na saída para não quebrar JSON
ini_set('log_errors', 1);

// Buffer de saída para capturar erros
ob_start();

try {
    // Verificar se o sistema foi instalado
    if (!file_exists(__DIR__ . '/installed.lock')) {
        http_response_code(400);
        echo json_encode(['error' => 'Sistema não instalado. Execute install.php primeiro.']);
        exit;
    }

    // Headers para API
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');

    if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
        echo json_encode(['status' => 'ok']);
        exit(0);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? '';
        
        switch ($action) {
            case 'start_dev_server':
                $result = startDevServer();
                echo json_encode($result);
                break;
                
            case 'stop_dev_server':
                $result = stopDevServer();
                echo json_encode($result);
                break;
                
            case 'check_dev_status':
                $result = checkDevServerStatus();
                echo json_encode($result);
                break;
                
            case 'open_browser':
                $result = openBrowser();
                echo json_encode($result);
                break;
                
            default:
                http_response_code(400);
                echo json_encode(['error' => 'Ação inválida: ' . $action]);
        }
        
    } elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $status = checkDevServerStatus();
        echo json_encode($status);
    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Método não permitido']);
    }

} catch (Exception $e) {
    // Limpar buffer de saída em caso de erro
    ob_clean();
    
    http_response_code(500);
    echo json_encode([
        'error' => 'Erro interno do servidor',
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
} catch (Error $e) {
    // Capturar erros fatais do PHP
    ob_clean();
    
    http_response_code(500);
    echo json_encode([
        'error' => 'Erro fatal do PHP',
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}

// Finalizar buffer de saída
ob_end_flush();

function startDevServer() {
    try {
        $projectPath = dirname(dirname(__FILE__));
        $logs = [];
        
        $logs[] = ['step' => 'init', 'message' => 'Iniciando verificações do sistema...', 'status' => 'info'];
        
        // Verificar se Node.js está instalado
        $logs[] = ['step' => 'node_check', 'message' => 'Verificando instalação do Node.js...', 'status' => 'info'];
        $nodeCheck = shell_exec('node --version 2>&1');
        
        if (empty($nodeCheck) || strpos($nodeCheck, 'not found') !== false || strpos($nodeCheck, 'não') !== false) {
            $logs[] = ['step' => 'node_check', 'message' => 'Node.js não encontrado no sistema', 'status' => 'error'];
            return [
                'success' => false, 
                'message' => 'Node.js não encontrado. Instale o Node.js primeiro.',
                'install_url' => 'https://nodejs.org/',
                'logs' => $logs,
                'node_check' => $nodeCheck,
                'project_path' => $projectPath
            ];
        }
        
        $nodeVersion = trim($nodeCheck);
        $logs[] = ['step' => 'node_check', 'message' => "Node.js encontrado: {$nodeVersion}", 'status' => 'success'];
        
        // Verificar se npm está disponível
        $logs[] = ['step' => 'npm_check', 'message' => 'Verificando instalação do NPM...', 'status' => 'info'];
        $npmCheck = shell_exec('npm --version 2>&1');
        
        if (empty($npmCheck) || strpos($npmCheck, 'not found') !== false || strpos($npmCheck, 'não') !== false) {
            $logs[] = ['step' => 'npm_check', 'message' => 'NPM não encontrado no sistema', 'status' => 'error'];
            return [
                'success' => false, 
                'message' => 'NPM não encontrado. Reinstale o Node.js.',
                'install_url' => 'https://nodejs.org/',
                'logs' => $logs,
                'npm_check' => $npmCheck
            ];
        }
        
        $npmVersion = trim($npmCheck);
        $logs[] = ['step' => 'npm_check', 'message' => "NPM encontrado: {$npmVersion}", 'status' => 'success'];
        
        // Verificar se package.json existe
        $logs[] = ['step' => 'project_check', 'message' => 'Verificando estrutura do projeto...', 'status' => 'info'];
        if (!file_exists($projectPath . '/package.json')) {
            $logs[] = ['step' => 'project_check', 'message' => 'Arquivo package.json não encontrado', 'status' => 'error'];
            return [
                'success' => false, 
                'message' => 'Arquivo package.json não encontrado no projeto.',
                'logs' => $logs,
                'project_path' => $projectPath,
                'files_found' => scandir($projectPath)
            ];
        }
        
        $logs[] = ['step' => 'project_check', 'message' => 'Estrutura do projeto verificada com sucesso', 'status' => 'success'];
        
        // Verificar se já está rodando
        $logs[] = ['step' => 'status_check', 'message' => 'Verificando se servidor já está rodando...', 'status' => 'info'];
        $status = checkDevServerStatus();
        if ($status['running']) {
            $logs[] = ['step' => 'status_check', 'message' => 'Servidor já está rodando na porta 5173', 'status' => 'success'];
            return [
                'success' => true, 
                'message' => 'Servidor de desenvolvimento já está rodando!',
                'url' => 'http://localhost:5173',
                'logs' => $logs,
                'already_running' => true
            ];
        }
        
        $logs[] = ['step' => 'status_check', 'message' => 'Porta 5173 está livre para uso', 'status' => 'success'];
        
        // Verificar se node_modules existe, se não, instalar dependências
        $logs[] = ['step' => 'deps_check', 'message' => 'Verificando dependências do projeto...', 'status' => 'info'];
        if (!is_dir($projectPath . '/node_modules')) {
            $logs[] = ['step' => 'deps_install', 'message' => 'Dependências não encontradas. Iniciando instalação...', 'status' => 'info'];
            $logs[] = ['step' => 'deps_install', 'message' => 'Executando: npm install (isso pode demorar alguns minutos)', 'status' => 'info'];
            
            // Instalar dependências
            $installCommand = "cd \"$projectPath\" && npm install 2>&1";
            $installOutput = shell_exec($installCommand);
            
            if (strpos($installOutput, 'error') !== false || strpos($installOutput, 'Error') !== false) {
                $logs[] = ['step' => 'deps_install', 'message' => 'Erro durante instalação das dependências', 'status' => 'error'];
                return [
                    'success' => false, 
                    'message' => 'Erro ao instalar dependências.',
                    'logs' => $logs,
                    'install_output' => $installOutput,
                    'install_command' => $installCommand
                ];
            }
            
            $logs[] = ['step' => 'deps_install', 'message' => 'Dependências instaladas com sucesso!', 'status' => 'success'];
        } else {
            $logs[] = ['step' => 'deps_check', 'message' => 'Dependências já estão instaladas', 'status' => 'success'];
        }
        
        // Verificar se há processos antigos na porta 5173
        $logs[] = ['step' => 'port_cleanup', 'message' => 'Verificando processos antigos na porta 5173...', 'status' => 'info'];
        $cleanupResult = cleanupPort5173();
        if ($cleanupResult['cleaned']) {
            $logs[] = ['step' => 'port_cleanup', 'message' => 'Processos antigos removidos da porta 5173', 'status' => 'success'];
        } else {
            $logs[] = ['step' => 'port_cleanup', 'message' => 'Porta 5173 está limpa', 'status' => 'success'];
        }
        
        // Iniciar servidor de desenvolvimento
        $logs[] = ['step' => 'server_start', 'message' => 'Iniciando servidor de desenvolvimento...', 'status' => 'info'];
        $logs[] = ['step' => 'server_start', 'message' => 'Executando: npm run dev', 'status' => 'info'];
        
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // Windows: usar start /B para executar em background
            $command = "cd \"$projectPath\" && start /B cmd /c \"npm run dev\" > nul 2>&1";
            pclose(popen($command, 'r'));
            $logs[] = ['step' => 'server_start', 'message' => 'Comando executado no Windows (background)', 'status' => 'info'];
        } else {
            // Linux/Mac: usar & para executar em background
            $command = "cd \"$projectPath\" && npm run dev > /dev/null 2>&1 &";
            shell_exec($command);
            $logs[] = ['step' => 'server_start', 'message' => 'Comando executado no Linux/Mac (background)', 'status' => 'info'];
        }
        
        // Aguardar um pouco para o servidor iniciar
        $logs[] = ['step' => 'server_wait', 'message' => 'Aguardando servidor inicializar (5 segundos)...', 'status' => 'info'];
        sleep(5); // Aumentar tempo de espera inicial
        
        // Verificar se iniciou com sucesso (tentar várias vezes)
        $attempts = 0;
        $maxAttempts = 20; // Aumentar tentativas
        
        $logs[] = ['step' => 'server_verify', 'message' => 'Verificando se servidor está respondendo...', 'status' => 'info'];
        
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
            
            $logs[] = ['step' => 'server_verify', 'message' => "Tentativa " . ($attempts + 1) . "/{$maxAttempts} - aguardando resposta...", 'status' => 'info'];
            sleep(3); // Aumentar tempo entre tentativas
            $attempts++;
        }
        
        // Se chegou aqui, o servidor não respondeu
        $logs[] = ['step' => 'server_verify', 'message' => 'Servidor não respondeu após todas as tentativas', 'status' => 'warning'];
        
        // Tentar diagnóstico adicional
        $logs[] = ['step' => 'diagnostics', 'message' => 'Executando diagnósticos adicionais...', 'status' => 'info'];
        $diagnostics = runDiagnostics($projectPath);
        
        foreach ($diagnostics as $diag) {
            $logs[] = $diag;
        }
        
        return [
            'success' => false, 
            'message' => 'Servidor iniciado mas não está respondendo na porta 5173. Verifique os logs para mais detalhes.',
            'logs' => $logs,
            'attempts' => $attempts,
            'command_used' => $command,
            'project_path' => $projectPath,
            'diagnostics' => $diagnostics
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false, 
            'message' => 'Erro ao iniciar servidor: ' . $e->getMessage(),
            'logs' => $logs ?? [],
            'exception' => $e->getTraceAsString()
        ];
    }
}

function cleanupPort5173() {
    $cleaned = false;
    
    try {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // Windows: Verificar e matar processos na porta 5173
            $netstatOutput = shell_exec('netstat -ano | findstr :5173 2>&1');
            
            if ($netstatOutput) {
                $lines = explode("\n", $netstatOutput);
                foreach ($lines as $line) {
                    if (strpos($line, 'LISTENING') !== false) {
                        preg_match('/\s+(\d+)$/', $line, $matches);
                        if (isset($matches[1])) {
                            $pid = $matches[1];
                            shell_exec("taskkill /F /PID $pid 2>&1");
                            $cleaned = true;
                        }
                    }
                }
            }
        } else {
            // Linux/Mac: Matar processo na porta 5173
            $pids = shell_exec('lsof -ti:5173 2>&1');
            if ($pids) {
                $pidList = explode("\n", trim($pids));
                foreach ($pidList as $pid) {
                    if (is_numeric($pid)) {
                        shell_exec("kill -9 $pid 2>&1");
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

function runDiagnostics($projectPath) {
    $diagnostics = [];
    
    try {
        // Verificar se o processo npm está rodando
        $diagnostics[] = ['step' => 'diag_processes', 'message' => 'Verificando processos Node.js ativos...', 'status' => 'info'];
        
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $processes = shell_exec('tasklist /FI "IMAGENAME eq node.exe" 2>&1');
            if (strpos($processes, 'node.exe') !== false) {
                $diagnostics[] = ['step' => 'diag_processes', 'message' => 'Processos Node.js encontrados em execução', 'status' => 'success'];
            } else {
                $diagnostics[] = ['step' => 'diag_processes', 'message' => 'Nenhum processo Node.js encontrado', 'status' => 'warning'];
            }
        } else {
            $processes = shell_exec('pgrep -f node 2>&1');
            if (!empty(trim($processes))) {
                $diagnostics[] = ['step' => 'diag_processes', 'message' => 'Processos Node.js encontrados em execução', 'status' => 'success'];
            } else {
                $diagnostics[] = ['step' => 'diag_processes', 'message' => 'Nenhum processo Node.js encontrado', 'status' => 'warning'];
            }
        }
        
        // Verificar se o arquivo package.json tem o script dev
        $diagnostics[] = ['step' => 'diag_package', 'message' => 'Verificando scripts no package.json...', 'status' => 'info'];
        $packageJson = file_get_contents($projectPath . '/package.json');
        $package = json_decode($packageJson, true);
        
        if (isset($package['scripts']['dev'])) {
            $diagnostics[] = ['step' => 'diag_package', 'message' => 'Script "dev" encontrado: ' . $package['scripts']['dev'], 'status' => 'success'];
        } else {
            $diagnostics[] = ['step' => 'diag_package', 'message' => 'Script "dev" não encontrado no package.json', 'status' => 'error'];
        }
        
        // Verificar se o Vite está instalado
        $diagnostics[] = ['step' => 'diag_vite', 'message' => 'Verificando instalação do Vite...', 'status' => 'info'];
        if (file_exists($projectPath . '/node_modules/.bin/vite') || file_exists($projectPath . '/node_modules/.bin/vite.cmd')) {
            $diagnostics[] = ['step' => 'diag_vite', 'message' => 'Vite encontrado nas dependências', 'status' => 'success'];
        } else {
            $diagnostics[] = ['step' => 'diag_vite', 'message' => 'Vite não encontrado - pode ser necessário reinstalar dependências', 'status' => 'warning'];
        }
        
        // Tentar executar vite diretamente
        $diagnostics[] = ['step' => 'diag_direct', 'message' => 'Tentando executar Vite diretamente...', 'status' => 'info'];
        $viteOutput = shell_exec("cd \"$projectPath\" && npx vite --version 2>&1");
        if (strpos($viteOutput, 'vite/') !== false || strpos($viteOutput, 'error') === false) {
            $diagnostics[] = ['step' => 'diag_direct', 'message' => 'Vite executável: ' . trim($viteOutput), 'status' => 'success'];
        } else {
            $diagnostics[] = ['step' => 'diag_direct', 'message' => 'Erro ao executar Vite: ' . trim($viteOutput), 'status' => 'error'];
        }
        
    } catch (Exception $e) {
        $diagnostics[] = ['step' => 'diag_error', 'message' => 'Erro durante diagnósticos: ' . $e->getMessage(), 'status' => 'error'];
    }
    
    return $diagnostics;
}

function stopDevServer() {
    try {
        $killed = false;
        $output = [];
        $logs = [];
        
        $logs[] = ['step' => 'stop_init', 'message' => 'Iniciando processo de parada do servidor...', 'status' => 'info'];
        
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $logs[] = ['step' => 'stop_windows', 'message' => 'Detectado sistema Windows', 'status' => 'info'];
            
            // Windows: Parar processos Node.js que estão usando a porta 5173
            $logs[] = ['step' => 'stop_port', 'message' => 'Verificando processos na porta 5173...', 'status' => 'info'];
            $netstatOutput = shell_exec('netstat -ano | findstr :5173 2>&1');
            
            if ($netstatOutput) {
                $lines = explode("\n", $netstatOutput);
                foreach ($lines as $line) {
                    if (strpos($line, 'LISTENING') !== false) {
                        preg_match('/\s+(\d+)$/', $line, $matches);
                        if (isset($matches[1])) {
                            $pid = $matches[1];
                            $logs[] = ['step' => 'stop_process', 'message' => "Finalizando processo PID: {$pid}", 'status' => 'info'];
                            $killResult = shell_exec("taskkill /F /PID $pid 2>&1");
                            $output[] = "Killed PID $pid: $killResult";
                            $killed = true;
                        }
                    }
                }
            }
            
            // Também tentar matar processos node.exe relacionados ao Vite
            $logs[] = ['step' => 'stop_node', 'message' => 'Verificando processos Node.js...', 'status' => 'info'];
            $nodeProcesses = shell_exec('tasklist /FI "IMAGENAME eq node.exe" /FO CSV 2>&1');
            if ($nodeProcesses && strpos($nodeProcesses, 'node.exe') !== false) {
                $logs[] = ['step' => 'stop_node', 'message' => 'Finalizando processos Node.js...', 'status' => 'info'];
                $killResult = shell_exec('taskkill /F /IM node.exe 2>&1');
                $output[] = "Killed node.exe processes: $killResult";
                $killed = true;
            }
        } else {
            $logs[] = ['step' => 'stop_unix', 'message' => 'Detectado sistema Unix/Linux', 'status' => 'info'];
            
            // Linux/Mac: Matar processo na porta 5173
            $logs[] = ['step' => 'stop_port', 'message' => 'Verificando processos na porta 5173...', 'status' => 'info'];
            $pids = shell_exec('lsof -ti:5173 2>&1');
            if ($pids) {
                $pidList = explode("\n", trim($pids));
                foreach ($pidList as $pid) {
                    if (is_numeric($pid)) {
                        $logs[] = ['step' => 'stop_process', 'message' => "Finalizando processo PID: {$pid}", 'status' => 'info'];
                        $killResult = shell_exec("kill -9 $pid 2>&1");
                        $output[] = "Killed PID $pid: $killResult";
                        $killed = true;
                    }
                }
            }
        }
        
        if ($killed) {
            $logs[] = ['step' => 'stop_wait', 'message' => 'Aguardando processos finalizarem...', 'status' => 'info'];
            // Aguardar um pouco para o processo terminar
            sleep(2);
            
            $logs[] = ['step' => 'stop_complete', 'message' => 'Servidor parado com sucesso!', 'status' => 'success'];
            
            return [
                'success' => true, 
                'message' => 'Servidor de desenvolvimento parado com sucesso!',
                'logs' => $logs,
                'output' => $output
            ];
        } else {
            $logs[] = ['step' => 'stop_notfound', 'message' => 'Nenhum processo encontrado na porta 5173', 'status' => 'warning'];
            
            return [
                'success' => false, 
                'message' => 'Nenhum servidor de desenvolvimento encontrado rodando na porta 5173.',
                'logs' => $logs,
                'output' => $output
            ];
        }
        
    } catch (Exception $e) {
        return [
            'success' => false, 
            'message' => 'Erro ao parar servidor: ' . $e->getMessage(),
            'logs' => $logs ?? []
        ];
    }
}

function checkDevServerStatus() {
    try {
        // Tentar conectar na porta 5173
        $socket = @fsockopen('localhost', 5173, $errno, $errstr, 5); // Aumentar timeout
        if ($socket) {
            fclose($socket);
            
            // Verificar se é realmente o Vite fazendo uma requisição HTTP
            $context = stream_context_create([
                'http' => [
                    'timeout' => 10, // Aumentar timeout
                    'ignore_errors' => true,
                    'method' => 'GET',
                    'header' => 'User-Agent: Minecraft-Monitor-Launcher/1.0'
                ]
            ]);
            
            $response = @file_get_contents('http://localhost:5173', false, $context);
            
            if ($response !== false) {
                return [
                    'running' => true,
                    'url' => 'http://localhost:5173',
                    'message' => 'Servidor de desenvolvimento está rodando e respondendo',
                    'response_length' => strlen($response)
                ];
            } else {
                return [
                    'running' => false,
                    'message' => 'Porta 5173 está ocupada mas não responde HTTP',
                    'errno' => $errno,
                    'errstr' => $errstr
                ];
            }
        }
        
        return [
            'running' => false,
            'message' => 'Servidor de desenvolvimento não está rodando',
            'errno' => $errno ?? null,
            'errstr' => $errstr ?? null
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
        $command = '';
        
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // Windows
            $command = "start \"\" \"$url\"";
        } elseif (PHP_OS === 'Darwin') {
            // macOS
            $command = "open \"$url\"";
        } else {
            // Linux
            $command = "xdg-open \"$url\"";
        }
        
        $output = shell_exec($command . ' 2>&1');
        
        return [
            'success' => true,
            'message' => 'Comando para abrir navegador executado!',
            'url' => $url,
            'command' => $command,
            'output' => $output
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Erro ao abrir navegador: ' . $e->getMessage()
        ];
    }
}
?>