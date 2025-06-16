<?php
require_once 'config.php';

checkAuth();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $settings = getSettings();
    echo json_encode($settings);
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $result = updateSettings($input);
    echo json_encode($result);
}

function getSettings() {
    $pdo = getConnection();
    $stmt = $pdo->prepare("SELECT setting_key, setting_value FROM settings");
    $stmt->execute();
    
    $settings = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    
    return $settings;
}

function updateSettings($newSettings) {
    $pdo = getConnection();
    
    try {
        $pdo->beginTransaction();
        
        foreach ($newSettings as $key => $value) {
            $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt->execute([$key, $value, $value]);
        }
        
        $pdo->commit();
        return ['success' => true, 'message' => 'Configurações salvas com sucesso'];
        
    } catch (Exception $e) {
        $pdo->rollBack();
        return ['success' => false, 'message' => 'Erro ao salvar configurações: ' . $e->getMessage()];
    }
}
?>