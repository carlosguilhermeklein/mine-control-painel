<?php
require_once 'config.php';

checkAuth();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $settings = getSettings();
    echo json_encode($settings);
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo json_encode(['error' => 'Dados inválidos']);
        exit;
    }
    
    $result = updateSettings($input);
    echo json_encode($result);
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Método não permitido']);
}

function getSettings() {
    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare("SELECT setting_key, setting_value FROM settings");
        $stmt->execute();
        
        $settings = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        
        // Garantir que todas as configurações necessárias existam
        $defaultSettings = [
            'server_path' => 'C:\\Minecraft\\Prominence II RPG\\start.bat',
            'server_port' => '25565',
            'log_path' => 'C:\\Minecraft\\Prominence II RPG\\logs\\latest.log',
            'rcon_enabled' => '1',
            'rcon_ip' => '127.0.0.1',
            'rcon_port' => '25575',
            'rcon_password' => 'minecraft',
            'auto_start' => '0',
            'auto_restart' => '1',
            'max_players' => '20',
            'difficulty' => 'normal'
        ];
        
        foreach ($defaultSettings as $key => $defaultValue) {
            if (!isset($settings[$key])) {
                $settings[$key] = $defaultValue;
            }
        }
        
        return $settings;
    } catch (Exception $e) {
        // Retornar configurações padrão em caso de erro
        return [
            'server_path' => 'C:\\Minecraft\\Prominence II RPG\\start.bat',
            'server_port' => '25565',
            'log_path' => 'C:\\Minecraft\\Prominence II RPG\\logs\\latest.log',
            'rcon_enabled' => '1',
            'rcon_ip' => '127.0.0.1',
            'rcon_port' => '25575',
            'rcon_password' => 'minecraft',
            'auto_start' => '0',
            'auto_restart' => '1',
            'max_players' => '20',
            'difficulty' => 'normal'
        ];
    }
}

function updateSettings($newSettings) {
    try {
        $pdo = getConnection();
        $pdo->beginTransaction();
        
        foreach ($newSettings as $key => $value) {
            // Validar chaves permitidas
            $allowedKeys = [
                'server_path', 'server_port', 'log_path', 'rcon_enabled',
                'rcon_ip', 'rcon_port', 'rcon_password', 'auto_start',
                'auto_restart', 'max_players', 'difficulty'
            ];
            
            if (!in_array($key, $allowedKeys)) {
                continue; // Pular chaves não permitidas
            }
            
            $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt->execute([$key, $value, $value]);
        }
        
        $pdo->commit();
        return ['success' => true, 'message' => 'Configurações salvas com sucesso'];
        
    } catch (Exception $e) {
        if (isset($pdo)) {
            $pdo->rollBack();
        }
        return ['success' => false, 'message' => 'Erro ao salvar configurações: ' . $e->getMessage()];
    }
}
?>