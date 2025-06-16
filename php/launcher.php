<?php
require_once 'config.php';

// Sistema de launcher web - executa tudo pelo navegador
checkAuth();

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
                'install_url' => 'https://nodejs.org/'
            ];
        }
        
        // Verificar se npm está disponível
        exec('npm --version 2>&1', $npmOutput, $npmReturn);
        if ($npmReturn !== 0) {
            return [
                'success' => false, 
                'message' => 'NPM não encontrado. Reinstale o Node.js.',
                'install_url' => 'https://nodejs.org/'
            ];
        }
        
        // Verificar se package.json existe
        if (!file_exists($projectPath . '/package.json')) {
            return [
                'success' => false, 
                'message' => 'Arquivo package.json não encontrado no projeto.'
            ];
        }
        
        // Verificar se node_modules existe, se não, instalar dependências
        if (!is_dir($projectPath . '/node_modules')) {
            exec("cd \"$projectPath\" && npm install 2>&1", $installOutput, $installReturn);
            if ($installReturn !== 0) {
                return [
                    'success' => false, 
                    'message' => 'Erro ao instalar dependências: ' . implode('\n', $installOutput)
                ];
            }
        }
        
        // Verificar se já está rodando
        $status = checkDevServerStatus();
        if ($status['running']) {
            return [
                'success' => true, 
                'message' => 'Servidor de desenvolvimento já está rodando!',
                'url' => 'http://localhost:5173'
            ];
        }
        
        // Iniciar servidor de desenvolvimento
        $command = "cd \"$projectPath\" && start /B npm run dev > dev-server.log 2>&1";
        pclose(popen($command, 'r'));
        
        // Aguardar um pouco para o servidor iniciar
        sleep(3);
        
        // Verificar se iniciou com sucesso
        $newStatus = checkDevServerStatus();
        if ($newStatus['running']) {
            return [
                'success' => true, 
                'message' => 'Servidor de desenvolvimento iniciado com sucesso!',
                'url' => 'http://localhost:5173'
            ];
        } else {
            return [
                'success' => false, 
                'message' => 'Falha ao iniciar servidor de desenvolvimento. Verifique os logs.'
            ];
        }
        
    } catch (Exception $e) {
        return [
            'success' => false, 
            'message' => 'Erro ao iniciar servidor: ' . $e->getMessage()
        ];
    }
}

function stopDevServer() {
    try {
        // Parar processos do Vite/Node relacionados ao projeto
        exec('tasklist /FI "IMAGENAME eq node.exe" /FO CSV 2>&1', $processes);
        
        $killed = false;
        foreach ($processes as $process) {
            if (strpos($process, 'vite') !== false || strpos($process, '5173') !== false) {
                exec('taskkill /F /IM node.exe /FI "WINDOWTITLE eq vite*" 2>&1');
                $killed = true;
                break;
            }
        }
        
        if ($killed) {
            return [
                'success' => true, 
                'message' => 'Servidor de desenvolvimento parado com sucesso!'
            ];
        } else {
            return [
                'success' => false, 
                'message' => 'Nenhum servidor de desenvolvimento encontrado rodando.'
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
        $socket = @fsockopen('localhost', 5173, $errno, $errstr, 1);
        if ($socket) {
            fclose($socket);
            return [
                'running' => true,
                'url' => 'http://localhost:5173',
                'message' => 'Servidor de desenvolvimento está rodando'
            ];
        } else {
            return [
                'running' => false,
                'message' => 'Servidor de desenvolvimento não está rodando'
            ];
        }
    } catch (Exception $e) {
        return [
            'running' => false,
            'message' => 'Erro ao verificar status: ' . $e->getMessage()
        ];
    }
}

function openBrowser() {
    try {
        // Abrir navegador na URL do sistema
        $url = 'http://localhost:5173';
        exec("start \"\" \"$url\"");
        
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