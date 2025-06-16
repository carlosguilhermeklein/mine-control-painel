<?php
require_once 'config.php';

checkAuth();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Obter status do servidor
    $status = getServerStatus();
    echo json_encode($status);
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    
    switch ($action) {
        case 'start':
            $result = startServer();
            echo json_encode($result);
            break;
            
        case 'stop':
            $result = stopServer();
            echo json_encode($result);
            break;
            
        case 'restart':
            $result = restartServer();
            echo json_encode($result);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Ação inválida']);
    }
}

function getServerStatus() {
    $pdo = getConnection();
    
    // Obter configurações
    $stmt = $pdo->prepare("SELECT setting_key, setting_value FROM settings");
    $stmt->execute();
    $settings = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    
    $serverIP = $settings['server_ip'] ?? DEFAULT_SERVER_IP;
    $serverPort = intval($settings['server_port'] ?? DEFAULT_SERVER_PORT);
    
    // Verificar se o servidor está online
    $isOnline = false;
    $players = [];
    $playerCount = 0;
    
    try {
        // Tentar conectar ao servidor para verificar status
        $socket = @fsockopen($serverIP, $serverPort, $errno, $errstr, 1);
        if ($socket) {
            $isOnline = true;
            fclose($socket);
            
            // Tentar obter lista de jogadores via RCON se habilitado
            if ($settings['rcon_enabled'] == '1') {
                $players = getPlayersViaRcon($settings);
                $playerCount = count($players);
            }
        }
    } catch (Exception $e) {
        // Servidor offline
    }
    
    // Calcular uptime (simplificado - baseado em quando o processo java foi iniciado)
    $uptime = getServerUptime();
    
    return [
        'status' => $isOnline ? 'online' : 'offline',
        'players' => $players,
        'playerCount' => $playerCount,
        'maxPlayers' => intval($settings['max_players'] ?? 20),
        'serverInfo' => [
            'ip' => $serverIP,
            'port' => $serverPort,
            'version' => 'Prominence II RPG v2.8.0',
            'uptime' => $uptime
        ]
    ];
}

function getPlayersViaRcon($settings) {
    $players = [];
    
    try {
        $rconIP = $settings['rcon_ip'] ?? DEFAULT_SERVER_IP;
        $rconPort = intval($settings['rcon_port'] ?? DEFAULT_RCON_PORT);
        $rconPassword = $settings['rcon_password'] ?? '';
        
        // Simular conexão RCON (você pode implementar uma biblioteca RCON real aqui)
        // Por enquanto, vamos simular alguns jogadores
        if (rand(0, 1)) {
            $players = [
                ['name' => 'DragonSlayer99', 'onlineTime' => '2h 34m', 'joinTime' => date('H:i', strtotime('-2 hours'))],
                ['name' => 'MysticCrafter', 'onlineTime' => '1h 12m', 'joinTime' => date('H:i', strtotime('-1 hour'))],
            ];
        }
    } catch (Exception $e) {
        // Falha na conexão RCON
    }
    
    return $players;
}

function getServerUptime() {
    // Verificar se existe processo java rodando (simplificado)
    $output = [];
    exec('tasklist /FI "IMAGENAME eq java.exe" /FO CSV', $output);
    
    if (count($output) > 1) {
        // Processo encontrado, calcular uptime aproximado
        return "5d 12h 23m"; // Placeholder - você pode implementar cálculo real
    }
    
    return "0m";
}

function startServer() {
    $pdo = getConnection();
    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'server_path'");
    $stmt->execute();
    $serverPath = $stmt->fetchColumn();
    
    if (!$serverPath) {
        return ['success' => false, 'message' => 'Caminho do servidor não configurado'];
    }
    
    if (!file_exists($serverPath)) {
        return ['success' => false, 'message' => 'Arquivo .bat não encontrado'];
    }
    
    try {
        // Executar o arquivo .bat em background
        $command = 'start /B "" "' . $serverPath . '"';
        pclose(popen($command, 'r'));
        
        return ['success' => true, 'message' => 'Servidor iniciado com sucesso'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Erro ao iniciar servidor: ' . $e->getMessage()];
    }
}

function stopServer() {
    try {
        // Parar todos os processos java (cuidado - isso pode afetar outros programas Java)
        exec('taskkill /F /IM java.exe 2>&1', $output, $returnCode);
        
        if ($returnCode === 0) {
            return ['success' => true, 'message' => 'Servidor parado com sucesso'];
        } else {
            return ['success' => false, 'message' => 'Nenhum processo do servidor encontrado'];
        }
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Erro ao parar servidor: ' . $e->getMessage()];
    }
}

function restartServer() {
    $stopResult = stopServer();
    if (!$stopResult['success']) {
        return $stopResult;
    }
    
    // Aguardar um pouco antes de reiniciar
    sleep(3);
    
    return startServer();
}
?>