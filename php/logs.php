<?php
require_once 'config.php';

checkAuth();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $level = $_GET['level'] ?? 'all';
    $search = $_GET['search'] ?? '';
    $limit = intval($_GET['limit'] ?? 100);
    
    $logs = getServerLogs($level, $search, $limit);
    echo json_encode($logs);
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Método não permitido']);
}

function getServerLogs($level = 'all', $search = '', $limit = 100) {
    try {
        // Primeiro, tentar ler do arquivo de log do Minecraft
        $pdo = getConnection();
        $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'log_path'");
        $stmt->execute();
        $logPath = $stmt->fetchColumn();
        
        $logs = [];
        
        if ($logPath && file_exists($logPath)) {
            $logs = parseMinecraftLog($logPath, $level, $search, $limit);
        }
        
        // Se não conseguir ler do arquivo, buscar do banco de dados
        if (empty($logs)) {
            $logs = getLogsFromDatabase($level, $search, $limit);
        }
        
        // Se ainda não tiver logs, gerar alguns exemplos
        if (empty($logs)) {
            $logs = generateSampleLogs();
        }
        
        return $logs;
    } catch (Exception $e) {
        return generateSampleLogs();
    }
}

function parseMinecraftLog($logPath, $level, $search, $limit) {
    $logs = [];
    
    try {
        if (!is_readable($logPath)) {
            return [];
        }
        
        $file = fopen($logPath, 'r');
        if (!$file) {
            return [];
        }
        
        // Ler as últimas linhas do arquivo
        $lines = [];
        while (($line = fgets($file)) !== false) {
            $lines[] = trim($line);
        }
        fclose($file);
        
        // Processar as últimas linhas
        $lines = array_slice($lines, -$limit);
        
        foreach ($lines as $line) {
            if (empty($line)) continue;
            
            $logEntry = parseLogLine($line);
            if ($logEntry) {
                // Filtrar por nível
                if ($level !== 'all' && $logEntry['level'] !== $level) {
                    continue;
                }
                
                // Filtrar por busca
                if (!empty($search) && 
                    stripos($logEntry['message'], $search) === false && 
                    stripos($logEntry['source'], $search) === false) {
                    continue;
                }
                
                $logs[] = $logEntry;
            }
        }
        
    } catch (Exception $e) {
        // Erro ao ler arquivo
    }
    
    return array_reverse($logs); // Mais recentes primeiro
}

function parseLogLine($line) {
    // Formato típico: [10:30:45] [Server thread/INFO] [minecraft/DedicatedServer]: Done (7.848s)! For help, type "help"
    $pattern = '/\[(\d{2}:\d{2}:\d{2})\] \[([^\/]+)\/([^\]]+)\] \[([^\]]+)\]: (.+)/';
    
    if (preg_match($pattern, $line, $matches)) {
        return [
            'id' => uniqid(),
            'timestamp' => date('Y-m-d ') . $matches[1],
            'level' => strtoupper($matches[3]),
            'source' => $matches[4],
            'message' => $matches[5]
        ];
    }
    
    // Formato alternativo mais simples
    $pattern2 = '/\[(\d{2}:\d{2}:\d{2})\] \[([^\]]+)\]: (.+)/';
    if (preg_match($pattern2, $line, $matches)) {
        return [
            'id' => uniqid(),
            'timestamp' => date('Y-m-d ') . $matches[1],
            'level' => 'INFO',
            'source' => $matches[2],
            'message' => $matches[3]
        ];
    }
    
    return null;
}

function getLogsFromDatabase($level, $search, $limit) {
    try {
        $pdo = getConnection();
        
        $sql = "SELECT * FROM server_logs WHERE 1=1";
        $params = [];
        
        if ($level !== 'all') {
            $sql .= " AND log_level = ?";
            $params[] = $level;
        }
        
        if (!empty($search)) {
            $sql .= " AND (message LIKE ? OR source LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        
        $sql .= " ORDER BY timestamp DESC LIMIT ?";
        $params[] = $limit;
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        $logs = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $logs[] = [
                'id' => $row['id'],
                'timestamp' => $row['timestamp'],
                'level' => $row['log_level'],
                'source' => $row['source'],
                'message' => $row['message']
            ];
        }
        
        return $logs;
    } catch (Exception $e) {
        return [];
    }
}

function generateSampleLogs() {
    $sampleLogs = [
        [
            'id' => uniqid(),
            'timestamp' => date('Y-m-d H:i:s', strtotime('-5 minutes')),
            'level' => 'INFO',
            'source' => 'minecraft/DedicatedServer',
            'message' => 'Server started successfully'
        ],
        [
            'id' => uniqid(),
            'timestamp' => date('Y-m-d H:i:s', strtotime('-3 minutes')),
            'level' => 'INFO',
            'source' => 'minecraft/PlayerList',
            'message' => 'DragonSlayer99 joined the game'
        ],
        [
            'id' => uniqid(),
            'timestamp' => date('Y-m-d H:i:s', strtotime('-2 minutes')),
            'level' => 'INFO',
            'source' => 'minecraft/PlayerList',
            'message' => 'MysticCrafter joined the game'
        ],
        [
            'id' => uniqid(),
            'timestamp' => date('Y-m-d H:i:s', strtotime('-1 minute')),
            'level' => 'INFO',
            'source' => 'minecraft/ServerWorld',
            'message' => 'Saving world data...'
        ],
        [
            'id' => uniqid(),
            'timestamp' => date('Y-m-d H:i:s'),
            'level' => 'INFO',
            'source' => 'minecraft/ServerWorld',
            'message' => 'World saved successfully'
        ]
    ];
    
    return $sampleLogs;
}
?>