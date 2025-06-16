<?php
require_once 'config.php';

// Verificar se o sistema foi instalado
if (!file_exists(__DIR__ . '/installed.lock')) {
    http_response_code(400);
    echo json_encode(['error' => 'Sistema não instalado. Execute install.php primeiro.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['action'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Dados inválidos']);
        exit;
    }
    
    switch ($input['action']) {
        case 'login':
            $password = $input['password'] ?? '';
            
            if (empty($password)) {
                http_response_code(400);
                echo json_encode(['error' => 'Senha é obrigatória']);
                exit;
            }
            
            try {
                $pdo = getConnection();
                $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE username = 'admin'");
                $stmt->execute();
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($user && password_verify($password, $user['password_hash'])) {
                    $_SESSION['authenticated'] = true;
                    $_SESSION['last_activity'] = time();
                    echo json_encode(['success' => true]);
                } else {
                    http_response_code(401);
                    echo json_encode(['error' => 'Senha incorreta']);
                }
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(['error' => 'Erro interno do servidor']);
            }
            break;
            
        case 'logout':
            session_destroy();
            echo json_encode(['success' => true]);
            break;
            
        case 'check':
            if (isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true) {
                if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
                    session_destroy();
                    echo json_encode(['authenticated' => false]);
                } else {
                    $_SESSION['last_activity'] = time();
                    echo json_encode(['authenticated' => true]);
                }
            } else {
                echo json_encode(['authenticated' => false]);
            }
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Ação inválida']);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Método não permitido']);
}
?>