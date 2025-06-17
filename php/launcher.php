<?php
// Sistema de launcher web - VERSÃO SIMPLIFICADA PARA WINDOWS/XAMPP
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Headers primeiro
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Responder OPTIONS
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    echo json_encode(['status' => 'ok']);
    exit;
}

// Verificar se o sistema foi instalado
if (!file_exists(__DIR__ . '/installed.lock')) {
    http_response_code(400);
    echo json_encode(['error' => 'Sistema não instalado. Execute install.php primeiro.']);
    exit;
}

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $rawInput = file_get_contents('php://input');
        
        if (empty($rawInput)) {
            http_response_code(400);
            echo json_encode(['error' => 'Dados de entrada vazios']);
            exit;
        }
        
        $input = json_decode($rawInput, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            http_response_code(400);
            echo json_encode(['error' => 'JSON inválido: ' . json_last_error_msg()]);
            exit;
        }
        
        $action = $input['action'] ?? '';
        
        switch ($action) {
            case 'start_dev_server':
                $result = startDevServerSimple();
                echo json_encode($result);
                break;
                
            case 'stop_dev_server':
                $result = stopDevServerSimple();
                echo json_encode($result);
                break;
                
            case 'check_dev_status':
                $result = checkDevServerStatusSimple();
                echo json_encode($result);
                break;
                
            case 'open_browser':
                $result = openBrowserSimple();
                echo json_encode($result);
                break;
                
            default:
                http_response_code(400);
                echo json_encode(['error' => 'Ação inválida: ' . $action]);
        }
        
    } elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $status = checkDevServerStatusSimple();
        echo json_encode($status);
    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Método não permitido']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Erro interno do servidor',
        'message' => $e->getMessage()
    ]);
}

function ensureNodeAvailable(&$logs) {
    $nodeCheck = exec('node --version 2>&1', $nodeOutput, $nodeReturn);
    if ($nodeReturn === 0 && !empty($nodeCheck)) {
        return $nodeCheck;
    }

    // Tentar caminhos comuns no Windows
    $possible = [
        'C:\\Program Files\\nodejs\\node.exe',
        'C:\\Program Files (x86)\\nodejs\\node.exe'
    ];
    foreach ($possible as $path) {
        if (file_exists($path)) {
            $dir = dirname($path);
            putenv('PATH=' . $dir . PATH_SEPARATOR . getenv('PATH'));
            $nodeCheck = exec('node --version 2>&1', $nodeOutput, $nodeReturn);
            if ($nodeReturn === 0 && !empty($nodeCheck)) {
                $logs[] = ['step' => 'node_check', 'message' => 'Node.js encontrado em ' . $path, 'status' => 'info'];
                return $nodeCheck;
            }
        }
    }

    return null;
}

function startDevServerSimple() {
    $logs = [];
    
    try {
        // Determinar caminho do projeto
        $projectPath = dirname(dirname(__FILE__));
        $logs[] = ['step' => 'init', 'message' => "Caminho do projeto: {$projectPath}", 'status' => 'info'];
        
        // Verificar se Node.js está instalado
        $logs[] = ['step' => 'node_check', 'message' => 'Verificando Node.js...', 'status' => 'info'];
        
        $nodeCheck = ensureNodeAvailable($logs);
        if ($nodeCheck === null) {
            $logs[] = ['step' => 'node_check', 'message' => 'Node.js não encontrado', 'status' => 'error'];
            return [
                'success' => false,
                'message' => 'Node.js não está instalado. Baixe em https://nodejs.org/',
                'logs' => $logs,
                'install_url' => 'https://nodejs.org/'
            ];
        }
        
        $logs[] = ['step' => 'node_check', 'message' => "Node.js encontrado: {$nodeCheck}", 'status' => 'success'];
        
        // Verificar NPM
        $logs[] = ['step' => 'npm_check', 'message' => 'Verificando NPM...', 'status' => 'info'];
        
        $npmCheck = exec('npm --version 2>&1', $npmOutput, $npmReturn);
        if ($npmReturn !== 0 || empty($npmCheck)) {
            $logs[] = ['step' => 'npm_check', 'message' => 'NPM não encontrado', 'status' => 'error'];
            return [
                'success' => false,
                'message' => 'NPM não encontrado. Reinstale o Node.js.',
                'logs' => $logs
            ];
        }
        
        $logs[] = ['step' => 'npm_check', 'message' => "NPM encontrado: {$npmCheck}", 'status' => 'success'];
        
        // Verificar se já está rodando
        $logs[] = ['step' => 'status_check', 'message' => 'Verificando se já está rodando...', 'status' => 'info'];
        
        $socket = @fsockopen('localhost', 5173, $errno, $errstr, 3);
        if ($socket) {
            fclose($socket);
            $logs[] = ['step' => 'status_check', 'message' => 'Servidor já está rodando!', 'status' => 'success'];
            return [
                'success' => true,
                'message' => 'Servidor já está rodando!',
                'url' => 'http://localhost:5173',
                'logs' => $logs,
                'already_running' => true
            ];
        }
        
        $logs[] = ['step' => 'status_check', 'message' => 'Porta 5173 livre', 'status' => 'success'];
        
        // Verificar package.json
        $logs[] = ['step' => 'project_check', 'message' => 'Verificando projeto...', 'status' => 'info'];
        
        if (!file_exists($projectPath . '/package.json')) {
            $logs[] = ['step' => 'project_check', 'message' => 'package.json não encontrado', 'status' => 'error'];
            return [
                'success' => false,
                'message' => 'Arquivo package.json não encontrado no projeto.',
                'logs' => $logs
            ];
        }
        
        $logs[] = ['step' => 'project_check', 'message' => 'Projeto válido', 'status' => 'success'];
        
        // Verificar node_modules
        $logs[] = ['step' => 'deps_check', 'message' => 'Verificando dependências...', 'status' => 'info'];
        
        if (!is_dir($projectPath . '/node_modules')) {
            $logs[] = ['step' => 'deps_install', 'message' => 'Instalando dependências...', 'status' => 'info'];
            
            // Executar npm install
            $oldDir = getcwd();
            chdir($projectPath);
            
            $installOutput = [];
            $installReturn = 0;
            exec('npm install 2>&1', $installOutput, $installReturn);
            
            chdir($oldDir);
            
            if ($installReturn !== 0) {
                $logs[] = ['step' => 'deps_install', 'message' => 'Erro na instalação: ' . implode(' ', $installOutput), 'status' => 'error'];
                return [
                    'success' => false,
                    'message' => 'Erro ao instalar dependências.',
                    'logs' => $logs
                ];
            }
            
            $logs[] = ['step' => 'deps_install', 'message' => 'Dependências instaladas!', 'status' => 'success'];
        } else {
            $logs[] = ['step' => 'deps_check', 'message' => 'Dependências já instaladas', 'status' => 'success'];
        }
        
        // Iniciar servidor
        $logs[] = ['step' => 'server_start', 'message' => 'Iniciando servidor...', 'status' => 'info'];
        
        // Usar start para executar em background no Windows
        $command = "start /B cmd /c \"cd /d \"{$projectPath}\" && npm run dev\"";
        
        $startOutput = [];
        $startReturn = 0;
        exec($command, $startOutput, $startReturn);
        
        $logs[] = ['step' => 'server_start', 'message' => 'Comando executado', 'status' => 'success'];
        
        // Aguardar um pouco
        $logs[] = ['step' => 'server_wait', 'message' => 'Aguardando inicialização...', 'status' => 'info'];
        sleep(5);
        
        // Verificar se está respondendo
        $logs[] = ['step' => 'server_verify', 'message' => 'Verificando resposta...', 'status' => 'info'];
        
        $attempts = 0;
        $maxAttempts = 10;
        
        while ($attempts < $maxAttempts) {
            $socket = @fsockopen('localhost', 5173, $errno, $errstr, 2);
            if ($socket) {
                fclose($socket);
                $logs[] = ['step' => 'server_verify', 'message' => 'Servidor respondendo!', 'status' => 'success'];
                $logs[] = ['step' => 'complete', 'message' => 'Sistema iniciado!', 'status' => 'success'];
                
                return [
                    'success' => true,
                    'message' => 'Servidor iniciado com sucesso!',
                    'url' => 'http://localhost:5173',
                    'logs' => $logs
                ];
            }
            
            sleep(2);
            $attempts++;
        }
        
        $logs[] = ['step' => 'server_verify', 'message' => 'Servidor não respondeu', 'status' => 'warning'];
        
        return [
            'success' => false,
            'message' => 'Servidor iniciado mas não está respondendo. Tente acessar http://localhost:5173 manualmente.',
            'logs' => $logs,
            'manual_url' => 'http://localhost:5173'
        ];
        
    } catch (Exception $e) {
        $logs[] = ['step' => 'error', 'message' => 'Erro: ' . $e->getMessage(), 'status' => 'error'];
        return [
            'success' => false,
            'message' => 'Erro ao iniciar servidor: ' . $e->getMessage(),
            'logs' => $logs
        ];
    }
}

function stopDevServerSimple() {
    $logs = [];
    
    try {
        $killed = false;
        
        $logs[] = ['step' => 'stop_init', 'message' => 'Parando servidor...', 'status' => 'info'];
        
        // Parar processos na porta 5173
        $netstatOutput = [];
        exec('netstat -ano | findstr :5173', $netstatOutput);
        
        foreach ($netstatOutput as $line) {
            if (strpos($line, 'LISTENING') !== false) {
                preg_match('/\s+(\d+)$/', $line, $matches);
                if (isset($matches[1])) {
                    $pid = $matches[1];
                    $logs[] = ['step' => 'stop_process', 'message' => "Finalizando PID: {$pid}", 'status' => 'info'];
                    exec("taskkill /F /PID {$pid}");
                    $killed = true;
                }
            }
        }
        
        // Parar processos node.exe
        exec('taskkill /F /IM node.exe 2>nul');
        $killed = true;
        
        if ($killed) {
            $logs[] = ['step' => 'stop_complete', 'message' => 'Servidor parado!', 'status' => 'success'];
            return [
                'success' => true,
                'message' => 'Servidor parado com sucesso!',
                'logs' => $logs
            ];
        } else {
            $logs[] = ['step' => 'stop_notfound', 'message' => 'Nenhum processo encontrado', 'status' => 'warning'];
            return [
                'success' => false,
                'message' => 'Nenhum servidor encontrado.',
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

function checkDevServerStatusSimple() {
    try {
        $socket = @fsockopen('localhost', 5173, $errno, $errstr, 3);
        if ($socket) {
            fclose($socket);
            return [
                'running' => true,
                'url' => 'http://localhost:5173',
                'message' => 'Servidor rodando'
            ];
        }
        
        return [
            'running' => false,
            'message' => 'Servidor não está rodando'
        ];
        
    } catch (Exception $e) {
        return [
            'running' => false,
            'message' => 'Erro ao verificar status'
        ];
    }
}

function openBrowserSimple() {
    try {
        $url = 'http://localhost:5173';
        exec("start \"\" \"{$url}\"");
        
        return [
            'success' => true,
            'message' => 'Navegador aberto!',
            'url' => $url
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Erro ao abrir navegador'
        ];
    }
}
?>