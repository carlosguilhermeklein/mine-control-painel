<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (isset($input['action'])) {
        switch ($input['action']) {
            case 'login':
                $password = $input['password'] ?? '';
                
                if (empty($password)) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Senha é obrigatória']);
                    exit;
                }
                
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
        }
    }
}
?>