<?php
require_once 'config.php';

checkAuth();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['action'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Dados inválidos']);
        exit;
    }
    
    $action = $input['action'];
    
    switch ($action) {
        case 'execute':
            $command = $input['command'] ?? '';
            $result = executeCommand($command);
            echo json_encode($result);
            break;
            
        case 'history':
            $history = getCommandHistory();
            echo json_encode($history);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Ação inválida']);
    }
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $history = getCommandHistory();
    echo json_encode($history);
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Método não permitido']);
}

function executeCommand($command) {
    if (empty($command)) {
        return ['success' => false, 'response' => 'Comando vazio'];
    }
    
    try {
        $pdo = getConnection();
        
        // Obter configurações RCON
        $stmt = $pdo->prepare("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('rcon_enabled', 'rcon_ip', 'rcon_port', 'rcon_password')");
        $stmt->execute();
        $settings = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        
        $response = '';
        $success = false;
        
        if (($settings['rcon_enabled'] ?? '0') == '1') {
            // Tentar executar via RCON
            $result = executeViaRcon($command, $settings);
            $response = $result['response'];
            $success = $result['success'];
        } else {
            $response = 'RCON não está habilitado';
            $success = false;
        }
        
        // Salvar no histórico
        $stmt = $pdo->prepare("INSERT INTO command_history (command, response, success) VALUES (?, ?, ?)");
        $stmt->execute([$command, $response, $success]);
        
        return [
            'success' => $success,
            'response' => $response,
            'timestamp' => date('H:i:s')
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'response' => 'Erro interno: ' . $e->getMessage(),
            'timestamp' => date('H:i:s')
        ];
    }
}

function executeViaRcon($command, $settings) {
    // Simular execução RCON (você pode implementar uma biblioteca RCON real aqui)
    $response = simulateCommandResponse($command);
    
    return [
        'success' => !strpos($command, 'error'),
        'response' => $response
    ];
}

function simulateCommandResponse($command) {
    $cmd = strtolower(trim($command));
    
    if ($cmd === '/list' || $cmd === 'list') {
        return 'There are 3 of a max of 20 players online: DragonSlayer99, MysticCrafter, SwordMaster77';
    } elseif (strpos($cmd, '/weather') === 0 || strpos($cmd, 'weather') === 0) {
        return 'Set the weather to ' . (strpos($cmd, 'clear') ? 'clear' : 'rain');
    } elseif (strpos($cmd, '/time') === 0 || strpos($cmd, 'time') === 0) {
        return 'Set the time to ' . (strpos($cmd, 'day') ? 'day' : 'night');
    } elseif (strpos($cmd, '/tp') === 0 || strpos($cmd, 'tp') === 0) {
        return 'Teleported player successfully';
    } elseif (strpos($cmd, '/gamemode') === 0 || strpos($cmd, 'gamemode') === 0) {
        return 'Updated gamemode for player';
    } elseif (strpos($cmd, '/give') === 0 || strpos($cmd, 'give') === 0) {
        return 'Gave items to player';
    } elseif (strpos($cmd, 'error') !== false) {
        return 'Error: Command failed to execute';
    } else {
        return 'Command executed successfully';
    }
}

function getCommandHistory($limit = 50) {
    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare("SELECT * FROM command_history ORDER BY executed_at DESC LIMIT ?");
        $stmt->execute([$limit]);
        
        $history = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $history[] = [
                'id' => $row['id'],
                'command' => $row['command'],
                'response' => $row['response'],
                'success' => (bool)$row['success'],
                'timestamp' => date('H:i:s', strtotime($row['executed_at']))
            ];
        }
        
        return array_reverse($history);
    } catch (Exception $e) {
        return [];
    }
}
?>