<?php
// Sistema de launcher web - executa tudo pelo navegador
// IMPORTANTE: Este arquivo NÃO requer autenticação pois é usado para INICIAR o sistema

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
            echo json_encode(['error' => 'Ação inválida']);
    }
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $status = checkDevServerStatus();
    echo json_encode($status);
}

function startDevServer() {
    $projectPath = dirname(dirname(__FILE__));
    
    try {
        // Verificar se Node.js está instalado
        exec('node --version 2>&1', $nodeOutput, $nodeReturn);
        if ($nodeReturn !== 0) {
            return [
                'success' => false, 
                'message' => 'Node.js não encontrado. Instale o Node.js primeiro.',
                'install_url' => 'https://nodejs.org/',
                'node_output' => implode('\n', $nodeOutput)
            ];
        }
        
        $nodeVersion = trim($nodeOutput[0] ?? 'desconhecida');
        
        // Verificar se npm está disponível
        exec('npm --version 2>&1', $npmOutput, $npmReturn);
        if ($npmReturn !== 0) {
            return [
                'success' => false, 
                'message' => 'NPM não encontrado. Reinstale o Node.js.',
                'install_url' => 'https://nodejs.org/',
                'npm_output' => implode('\n', $npmOutput)
            ];
        }
        
        $npmVersion = trim($npmOutput[0] ?? 'desconhecida');
        
        // Verificar se package.json existe
        if (!file_exists($projectPath . '/package.json')) {
            return [
                'success' => false, 
                'message' => 'Arquivo package.json não encontrado no projeto.',
                'project_path' => $projectPath
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
            $installCommand = "cd \"$projectPath\" && npm install";
            exec($installCommand . ' 2>&1', $installOutput, $installReturn);
            
            if ($installReturn !== 0) {
                return [
                    'success' => false, 
                    'message' => 'Erro ao instalar dependências. Verifique se o Node.js está instalado corretamente.',
                    'install_output' => implode('\n', $installOutput),
                    'install_command' => $installCommand
                ];
            }
        }
        
        // Iniciar servidor de desenvolvimento
        $devCommand = "cd \"$projectPath\" && npm run dev";
        
        // No Windows, usar start /B para executar em background
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $command = "start /B cmd /c \"$devCommand\" > nul 2>&1";
        } else {
            $command = "$devCommand > /dev/null 2>&1 &";
        }
        
        pclose(popen($command, 'r'));
        
        // Aguardar um pouco para o servidor iniciar
        sleep(5);
        
        // Verificar se iniciou com sucesso (tentar várias vezes)
        $attempts = 0;
        $maxAttempts = 10;
        
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
            'command_used' => $command
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
        
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // Windows: Parar processos Node.js que estão usando a porta 5173
            exec('netstat -ano | findstr :5173', $netstatOutput);
            
            foreach ($netstatOutput as $line) {
                if (strpos($line, 'LISTENING') !== false) {
                    preg_match('/\s+(\d+)$/', $line, $matches);
                    if (isset($matches[1])) {
                        $pid = $matches[1];
                        exec("taskkill /F /PID $pid 2>&1", $killOutput);
                        $killed = true;
                    }
                }
            }
            
            // Também tentar matar processos node.exe relacionados ao Vite
            exec('tasklist /FI "IMAGENAME eq node.exe" /FO CSV 2>&1', $processes);
            foreach ($processes as $process) {
                if (strpos($process, 'node.exe') !== false) {
                    exec('taskkill /F /IM node.exe 2>&1');
                    $killed = true;
                    break;
                }
            }
        } else {
            // Linux/Mac: Matar processo na porta 5173
            exec('lsof -ti:5173', $pids);
            foreach ($pids as $pid) {
                if (is_numeric($pid)) {
                    exec("kill -9 $pid");
                    $killed = true;
                }
            }
        }
        
        if ($killed) {
            // Aguardar um pouco para o processo terminar
            sleep(2);
            
            return [
                'success' => true, 
                'message' => 'Servidor de desenvolvimento parado com sucesso!'
            ];
        } else {
            return [
                'success' => false, 
                'message' => 'Nenhum servidor de desenvolvimento encontrado rodando na porta 5173.'
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
        $socket = @fsockopen('localhost', 5173, $errno, $errstr, 2);
        if ($socket) {
            fclose($socket);
            
            // Verificar se é realmente o Vite fazendo uma requisição HTTP
            $context = stream_context_create([
                'http' => [
                    'timeout' => 3,
                    'ignore_errors' => true
                ]
            ]);
            
            $response = @file_get_contents('http://localhost:5173', false, $context);
            
            if ($response !== false) {
                return [
                    'running' => true,
                    'url' => 'http://localhost:5173',
                    'message' => 'Servidor de desenvolvimento está rodando e respondendo'
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
        
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // Windows
            exec("start \"\" \"$url\"");
        } elseif (PHP_OS === 'Darwin') {
            // macOS
            exec("open \"$url\"");
        } else {
            // Linux
            exec("xdg-open \"$url\"");
        }
        
        return [
            'success' => true,
            'message' => 'Navegador aberto com sucesso!',
            'url' => $url
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Erro ao abrir navegador: ' . $e->getMessage()
        ];
    }
}
?>