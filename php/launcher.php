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
        
        // Verificar se Node.js está instalado
        $nodeCheck = shell_exec('node --version 2>&1');
        if (empty($nodeCheck) || strpos($nodeCheck, 'not found') !== false || strpos($nodeCheck, 'não') !== false) {
            return [
                'success' => false, 
                'message' => 'Node.js não encontrado. Instale o Node.js primeiro.',
                'install_url' => 'https://nodejs.org/',
                'node_check' => $nodeCheck,
                'project_path' => $projectPath
            ];
        }
        
        $nodeVersion = trim($nodeCheck);
        
        // Verificar se npm está disponível
        $npmCheck = shell_exec('npm --version 2>&1');
        if (empty($npmCheck) || strpos($npmCheck, 'not found') !== false || strpos($npmCheck, 'não') !== false) {
            return [
                'success' => false, 
                'message' => 'NPM não encontrado. Reinstale o Node.js.',
                'install_url' => 'https://nodejs.org/',
                'npm_check' => $npmCheck
            ];
        }
        
        $npmVersion = trim($npmCheck);
        
        // Verificar se package.json existe
        if (!file_exists($projectPath . '/package.json')) {
            return [
                'success' => false, 
                'message' => 'Arquivo package.json não encontrado no projeto.',
                'project_path' => $projectPath,
                'files_found' => scandir($projectPath)
            ];
        }
        
        // Verificar se já está rodando
        $status = checkDevServerStatus();
        if ($status['running']) {
            return [
                'success' => true, 
                'message' => 'Servidor de desenvolvimento já está rodando!',
                'url' => 'http://localhost:5173',
                'already_running' => true
            ];
        }
        
        // Verificar se node_modules existe, se não, instalar dependências
        if (!is_dir($projectPath . '/node_modules')) {
            // Instalar dependências
            $installCommand = "cd \"$projectPath\" && npm install 2>&1";
            $installOutput = shell_exec($installCommand);
            
            if (strpos($installOutput, 'error') !== false || strpos($installOutput, 'Error') !== false) {
                return [
                    'success' => false, 
                    'message' => 'Erro ao instalar dependências.',
                    'install_output' => $installOutput,
                    'install_command' => $installCommand
                ];
            }
        }
        
        // Iniciar servidor de desenvolvimento
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // Windows: usar start /B para executar em background
            $command = "cd \"$projectPath\" && start /B cmd /c \"npm run dev\" > nul 2>&1";
            pclose(popen($command, 'r'));
        } else {
            // Linux/Mac: usar & para executar em background
            $command = "cd \"$projectPath\" && npm run dev > /dev/null 2>&1 &";
            shell_exec($command);
        }
        
        // Aguardar um pouco para o servidor iniciar
        sleep(3);
        
        // Verificar se iniciou com sucesso (tentar várias vezes)
        $attempts = 0;
        $maxAttempts = 15; // Aumentar tentativas
        
        while ($attempts < $maxAttempts) {
            $newStatus = checkDevServerStatus();
            if ($newStatus['running']) {
                return [
                    'success' => true, 
                    'message' => 'Servidor de desenvolvimento iniciado com sucesso!',
                    'url' => 'http://localhost:5173',
                    'node_version' => $nodeVersion,
                    'npm_version' => $npmVersion,
                    'attempts' => $attempts + 1
                ];
            }
            
            sleep(2);
            $attempts++;
        }
        
        return [
            'success' => false, 
            'message' => 'Servidor iniciado mas não está respondendo na porta 5173. Tente novamente em alguns segundos.',
            'attempts' => $attempts,
            'command_used' => $command,
            'project_path' => $projectPath
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false, 
            'message' => 'Erro ao iniciar servidor: ' . $e->getMessage(),
            'exception' => $e->getTraceAsString()
        ];
    }
}

function stopDevServer() {
    try {
        $killed = false;
        $output = [];
        
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // Windows: Parar processos Node.js que estão usando a porta 5173
            $netstatOutput = shell_exec('netstat -ano | findstr :5173 2>&1');
            
            if ($netstatOutput) {
                $lines = explode("\n", $netstatOutput);
                foreach ($lines as $line) {
                    if (strpos($line, 'LISTENING') !== false) {
                        preg_match('/\s+(\d+)$/', $line, $matches);
                        if (isset($matches[1])) {
                            $pid = $matches[1];
                            $killResult = shell_exec("taskkill /F /PID $pid 2>&1");
                            $output[] = "Killed PID $pid: $killResult";
                            $killed = true;
                        }
                    }
                }
            }
            
            // Também tentar matar processos node.exe relacionados ao Vite
            $nodeProcesses = shell_exec('tasklist /FI "IMAGENAME eq node.exe" /FO CSV 2>&1');
            if ($nodeProcesses && strpos($nodeProcesses, 'node.exe') !== false) {
                $killResult = shell_exec('taskkill /F /IM node.exe 2>&1');
                $output[] = "Killed node.exe processes: $killResult";
                $killed = true;
            }
        } else {
            // Linux/Mac: Matar processo na porta 5173
            $pids = shell_exec('lsof -ti:5173 2>&1');
            if ($pids) {
                $pidList = explode("\n", trim($pids));
                foreach ($pidList as $pid) {
                    if (is_numeric($pid)) {
                        $killResult = shell_exec("kill -9 $pid 2>&1");
                        $output[] = "Killed PID $pid: $killResult";
                        $killed = true;
                    }
                }
            }
        }
        
        if ($killed) {
            // Aguardar um pouco para o processo terminar
            sleep(2);
            
            return [
                'success' => true, 
                'message' => 'Servidor de desenvolvimento parado com sucesso!',
                'output' => $output
            ];
        } else {
            return [
                'success' => false, 
                'message' => 'Nenhum servidor de desenvolvimento encontrado rodando na porta 5173.',
                'output' => $output
            ];
        }
        
    } catch (Exception $e) {
        return [
            'success' => false, 
            'message' => 'Erro ao parar servidor: ' . $e->getMessage()
        ];
    }
}

function checkDevServerStatus() {
    try {
        // Tentar conectar na porta 5173
        $socket = @fsockopen('localhost', 5173, $errno, $errstr, 3);
        if ($socket) {
            fclose($socket);
            
            // Verificar se é realmente o Vite fazendo uma requisição HTTP
            $context = stream_context_create([
                'http' => [
                    'timeout' => 5,
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