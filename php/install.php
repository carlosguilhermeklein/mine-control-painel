<?php
// Sistema de instalação automática
session_start();

// Verificar se já foi instalado
if (file_exists('installed.lock')) {
    header('Location: web-launcher.html');
    exit;
}

$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$error = '';
$success = '';
$allPassed = true;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($_POST['step'] == 2) {
        // Testar conexão com banco e criar estrutura
        $host = $_POST['db_host'] ?? 'localhost';
        $user = $_POST['db_user'] ?? 'root';
        $pass = $_POST['db_pass'] ?? '';
        $name = $_POST['db_name'] ?? 'minecraft_monitor';
        
        try {
            // Testar conexão
            $pdo = new PDO("mysql:host=$host", $user, $pass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Criar banco se não existir
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `$name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE `$name`");
            
            // Criar todas as tabelas
            $sql = "
            CREATE TABLE IF NOT EXISTS settings (
                id INT PRIMARY KEY AUTO_INCREMENT,
                setting_key VARCHAR(100) UNIQUE NOT NULL,
                setting_value TEXT NOT NULL,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS users (
                id INT PRIMARY KEY AUTO_INCREMENT,
                username VARCHAR(50) UNIQUE NOT NULL,
                password_hash VARCHAR(255) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS command_history (
                id INT PRIMARY KEY AUTO_INCREMENT,
                command TEXT NOT NULL,
                response TEXT,
                success BOOLEAN DEFAULT TRUE,
                executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS server_logs (
                id INT PRIMARY KEY AUTO_INCREMENT,
                log_level ENUM('INFO', 'WARN', 'ERROR', 'DEBUG') NOT NULL,
                source VARCHAR(50),
                message TEXT NOT NULL,
                timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );
            ";
            
            // Executar criação das tabelas
            $pdo->exec($sql);
            
            // Inserir configurações padrão
            $defaultSettings = [
                ['server_path', 'C:\\Minecraft\\Prominence II RPG\\start.bat'],
                ['log_path', 'C:\\Minecraft\\Prominence II RPG\\logs\\latest.log'],
                ['server_ip', '127.0.0.1'],
                ['server_port', '25565'],
                ['rcon_enabled', '1'],
                ['rcon_ip', '127.0.0.1'],
                ['rcon_port', '25575'],
                ['rcon_password', 'minecraft'],
                ['auto_start', '0'],
                ['auto_restart', '1'],
                ['max_players', '20'],
                ['difficulty', 'normal']
            ];
            
            $stmt = $pdo->prepare("INSERT IGNORE INTO settings (setting_key, setting_value) VALUES (?, ?)");
            foreach ($defaultSettings as $setting) {
                $stmt->execute($setting);
            }
            
            // Salvar configurações na sessão
            $_SESSION['install_config'] = [
                'host' => $host,
                'user' => $user,
                'pass' => $pass,
                'name' => $name
            ];
            
            $success = '✅ Banco criado e estrutura instalada com sucesso! Agora crie o usuário administrador.';
            $step = 3;
            
        } catch (PDOException $e) {
            $error = 'Erro de conexão com banco: ' . $e->getMessage();
        }
        
    } elseif ($_POST['step'] == 3) {
        // Criar usuário administrador
        $admin_user = trim($_POST['admin_user'] ?? 'admin');
        $admin_pass = $_POST['admin_pass'] ?? '';
        $admin_pass_confirm = $_POST['admin_pass_confirm'] ?? '';
        
        if (empty($admin_pass) || $admin_pass !== $admin_pass_confirm) {
            $error = 'Senhas não conferem ou estão vazias!';
        } elseif (strlen($admin_pass) < 6) {
            $error = 'Senha deve ter pelo menos 6 caracteres!';
        } else {
            try {
                $config = $_SESSION['install_config'];
                $pdo = new PDO("mysql:host={$config['host']};dbname={$config['name']}", $config['user'], $config['pass']);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                // Criar usuário admin
                $passwordHash = password_hash($admin_pass, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, password_hash) VALUES (?, ?) ON DUPLICATE KEY UPDATE password_hash = ?");
                $stmt->execute([$admin_user, $passwordHash, $passwordHash]);
                
                // Criar arquivo de configuração
                $configContent = "<?php
// Configurações do banco de dados - Gerado automaticamente pela instalação
define('DB_HOST', '{$config['host']}');
define('DB_USER', '{$config['user']}');
define('DB_PASS', '{$config['pass']}');
define('DB_NAME', '{$config['name']}');

// Configurações do servidor Minecraft
define('DEFAULT_SERVER_PATH', 'C:\\\\Minecraft\\\\Prominence II RPG\\\\start.bat');
define('DEFAULT_LOG_PATH', 'C:\\\\Minecraft\\\\Prominence II RPG\\\\logs\\\\latest.log');
define('DEFAULT_SERVER_IP', '127.0.0.1');
define('DEFAULT_SERVER_PORT', '25565');
define('DEFAULT_RCON_PORT', '25575');

// Configurações de sessão
define('SESSION_TIMEOUT', 900); // 15 minutos

// Iniciar sessão
session_start();

// Função para conectar ao banco
function getConnection() {
    try {
        \$pdo = new PDO(\"mysql:host=\" . DB_HOST . \";dbname=\" . DB_NAME, DB_USER, DB_PASS);
        \$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return \$pdo;
    } catch(PDOException \$e) {
        die(\"Erro de conexão: \" . \$e->getMessage());
    }
}

// Função para verificar autenticação
function checkAuth() {
    if (!isset(\$_SESSION['authenticated']) || \$_SESSION['authenticated'] !== true) {
        http_response_code(401);
        echo json_encode(['error' => 'Não autenticado']);
        exit;
    }
    
    // Verificar timeout da sessão
    if (isset(\$_SESSION['last_activity']) && (time() - \$_SESSION['last_activity'] > SESSION_TIMEOUT)) {
        session_destroy();
        http_response_code(401);
        echo json_encode(['error' => 'Sessão expirada']);
        exit;
    }
    
    \$_SESSION['last_activity'] = time();
}

// Headers para API
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:5173');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');

if (\$_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}
?>";
                
                if (!file_put_contents('config.php', $configContent)) {
                    throw new Exception('Não foi possível criar o arquivo config.php');
                }
                
                // Criar arquivo de lock
                $lockContent = json_encode([
                    'installed_at' => date('Y-m-d H:i:s'),
                    'version' => '1.0.0',
                    'admin_user' => $admin_user,
                    'database' => $config['name']
                ], JSON_PRETTY_PRINT);
                
                if (!file_put_contents('installed.lock', $lockContent)) {
                    throw new Exception('Não foi possível criar o arquivo installed.lock');
                }
                
                // Limpar sessão de instalação
                unset($_SESSION['install_config']);
                
                $success = '🎉 Sistema instalado com sucesso! Usuário administrador criado e sistema pronto para uso.';
                $step = 4;
                
            } catch (Exception $e) {
                $error = 'Erro ao finalizar instalação: ' . $e->getMessage();
            }
        }
    }
}

// Verificações do sistema para step 1
if ($step == 1) {
    $checks = [
        'PHP Version' => version_compare(PHP_VERSION, '7.4.0', '>='),
        'PDO Extension' => extension_loaded('pdo'),
        'PDO MySQL' => extension_loaded('pdo_mysql'),
        'JSON Extension' => extension_loaded('json'),
        'Session Support' => function_exists('session_start'),
        'File Write Permission' => is_writable(__DIR__)
    ];
    
    $allPassed = true;
    foreach ($checks as $check => $result) {
        if (!$result) $allPassed = false;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalação - Minecraft Server Monitor</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
            min-height: 100vh;
            padding: 2rem 1rem;
        }
        .container { max-width: 800px; margin: 0 auto; }
        .card { 
            background: white; 
            border-radius: 16px; 
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            padding: 2rem;
            margin-bottom: 2rem;
        }
        .header { text-align: center; margin-bottom: 2rem; }
        .logo { 
            width: 64px; height: 64px; 
            background: #16a34a; 
            border-radius: 16px; 
            margin: 0 auto 1rem;
            display: flex; align-items: center; justify-content: center;
            color: white; font-size: 24px; font-weight: bold;
        }
        h1 { color: #1f2937; margin-bottom: 0.5rem; font-size: 2rem; }
        .subtitle { color: #6b7280; }
        .progress { margin-bottom: 2rem; }
        .progress-bar { 
            width: 100%; height: 8px; 
            background: #e5e7eb; border-radius: 4px; 
            overflow: hidden;
        }
        .progress-fill { 
            height: 100%; background: #16a34a; 
            transition: width 0.3s ease;
        }
        .progress-text { 
            display: flex; justify-content: space-between; 
            margin-bottom: 0.5rem; font-size: 0.875rem; 
            color: #16a34a; font-weight: 600;
        }
        .alert { 
            padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;
            display: flex; align-items: center;
        }
        .alert-error { background: #fef2f2; border: 1px solid #fecaca; color: #dc2626; }
        .alert-success { background: #f0fdf4; border: 1px solid #bbf7d0; color: #16a34a; }
        .form-group { margin-bottom: 1.5rem; }
        .form-label { 
            display: block; margin-bottom: 0.5rem; 
            font-weight: 600; color: #374151;
        }
        .form-input { 
            width: 100%; padding: 0.75rem; 
            border: 1px solid #d1d5db; border-radius: 8px;
            font-size: 1rem; transition: border-color 0.2s;
        }
        .form-input:focus { 
            outline: none; border-color: #16a34a; 
            box-shadow: 0 0 0 3px rgba(22, 163, 74, 0.1);
        }
        .form-help { font-size: 0.875rem; color: #6b7280; margin-top: 0.25rem; }
        .btn { 
            display: inline-block; padding: 0.75rem 1.5rem;
            background: #16a34a; color: white; text-decoration: none;
            border-radius: 8px; font-weight: 600; text-align: center;
            border: none; cursor: pointer; font-size: 1rem;
            transition: background-color 0.2s;
        }
        .btn:hover { background: #15803d; }
        .btn:disabled { background: #9ca3af; cursor: not-allowed; }
        .btn-block { width: 100%; }
        .check-list { margin-bottom: 1.5rem; }
        .check-item { 
            display: flex; justify-content: space-between; align-items: center;
            padding: 0.75rem; background: #f9fafb; border-radius: 8px;
            margin-bottom: 0.5rem;
        }
        .check-status { font-weight: 600; }
        .check-ok { color: #16a34a; }
        .check-fail { color: #dc2626; }
        .steps { margin-bottom: 2rem; }
        .step { 
            display: flex; align-items: flex-start; margin-bottom: 1rem;
            padding: 1rem; background: #f9fafb; border-radius: 8px;
        }
        .step-number { 
            width: 32px; height: 32px; background: #16a34a; color: white;
            border-radius: 50%; display: flex; align-items: center; justify-content: center;
            font-weight: bold; margin-right: 1rem; flex-shrink: 0;
        }
        .step-content strong { display: block; margin-bottom: 0.25rem; }
        .code { 
            background: #f3f4f6; padding: 0.25rem 0.5rem; 
            border-radius: 4px; font-family: monospace; font-size: 0.875rem;
        }
        .info-box { 
            background: #eff6ff; border: 1px solid #bfdbfe; 
            border-radius: 8px; padding: 1rem; margin-bottom: 1.5rem;
        }
        .info-title { font-weight: 600; color: #1e40af; margin-bottom: 0.5rem; }
        .info-list { color: #1e40af; font-size: 0.875rem; }
        .info-list li { margin-bottom: 0.25rem; }
        .flex { display: flex; gap: 1rem; }
        .flex > * { flex: 1; }
        .text-center { text-align: center; }
        .success-icon { 
            width: 64px; height: 64px; background: #dcfce7; 
            border-radius: 50%; margin: 0 auto 1rem;
            display: flex; align-items: center; justify-content: center;
            color: #16a34a; font-size: 24px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="logo">🛡️</div>
            <h1>Minecraft Server Monitor</h1>
            <p class="subtitle">Instalação e Configuração Inicial</p>
        </div>

        <!-- Progress Bar -->
        <div class="progress">
            <div class="progress-text">
                <span>Progresso da Instalação</span>
                <span><?php echo $step; ?>/4</span>
            </div>
            <div class="progress-bar">
                <div class="progress-fill" style="width: <?php echo ($step/4)*100; ?>%"></div>
            </div>
        </div>

        <!-- Main Card -->
        <div class="card">
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <span style="margin-right: 0.5rem;">❌</span>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <span style="margin-right: 0.5rem;">✅</span>
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <?php if ($step == 1): ?>
                <!-- Step 1: System Check -->
                <h2 style="margin-bottom: 1rem; color: #1f2937;">Verificação do Sistema</h2>
                <p style="color: #6b7280; margin-bottom: 1.5rem;">Verificando se todos os requisitos estão atendidos...</p>

                <div class="check-list">
                    <?php foreach ($checks as $check => $result): ?>
                        <div class="check-item">
                            <span><?php echo $check; ?></span>
                            <span class="check-status <?php echo $result ? 'check-ok' : 'check-fail'; ?>">
                                <?php echo $result ? '✅ OK' : '❌ FALHOU'; ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if ($allPassed): ?>
                    <a href="?step=2" class="btn btn-block">Continuar para Configuração do Banco</a>
                <?php else: ?>
                    <div class="alert alert-error">
                        <div>
                            <strong>Requisitos não atendidos!</strong><br>
                            Corrija os problemas acima antes de continuar. Verifique se o XAMPP está configurado corretamente.
                        </div>
                    </div>
                <?php endif; ?>

            <?php elseif ($step == 2): ?>
                <!-- Step 2: Database Configuration -->
                <h2 style="margin-bottom: 1rem; color: #1f2937;">Configuração do Banco de Dados</h2>
                <p style="color: #6b7280; margin-bottom: 1.5rem;">Configure a conexão com o MySQL do XAMPP. O banco e todas as tabelas serão criados automaticamente.</p>

                <form method="POST">
                    <input type="hidden" name="step" value="2">
                    
                    <div class="form-group">
                        <label class="form-label">Host do Banco</label>
                        <input type="text" name="db_host" value="localhost" required class="form-input">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Usuário do Banco</label>
                        <input type="text" name="db_user" value="root" required class="form-input">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Senha do Banco</label>
                        <input type="password" name="db_pass" placeholder="Deixe vazio se não houver senha" class="form-input">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Nome do Banco</label>
                        <input type="text" name="db_name" value="minecraft_monitor" required class="form-input">
                        <p class="form-help">O banco será criado automaticamente se não existir</p>
                    </div>

                    <button type="submit" class="btn btn-block">Testar Conexão e Criar Estrutura</button>
                </form>

            <?php elseif ($step == 3): ?>
                <!-- Step 3: Admin User Creation -->
                <h2 style="margin-bottom: 1rem; color: #1f2937;">Criar Usuário Administrador</h2>
                <p style="color: #6b7280; margin-bottom: 1.5rem;">O banco e tabelas foram criados com sucesso! Agora defina as credenciais do usuário administrador.</p>

                <form method="POST">
                    <input type="hidden" name="step" value="3">
                    
                    <div class="form-group">
                        <label class="form-label">Nome de Usuário</label>
                        <input type="text" name="admin_user" value="admin" required class="form-input">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Senha</label>
                        <input type="password" name="admin_pass" required minlength="6" class="form-input">
                        <p class="form-help">Mínimo 6 caracteres</p>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Confirmar Senha</label>
                        <input type="password" name="admin_pass_confirm" required minlength="6" class="form-input">
                    </div>

                    <button type="submit" class="btn btn-block">Criar Usuário e Finalizar Instalação</button>
                </form>

            <?php elseif ($step == 4): ?>
                <!-- Step 4: Installation Complete -->
                <div class="text-center">
                    <div class="success-icon">🎉</div>
                    <h2 style="margin-bottom: 1rem; color: #1f2937;">Instalação Concluída!</h2>
                    <p style="color: #6b7280; margin-bottom: 2rem;">O sistema foi instalado com sucesso. Banco criado, tabelas estruturadas e usuário administrador configurado.</p>
                    
                    <div class="info-box">
                        <div class="info-title">🚀 Sistema Pronto!</div>
                        <p style="color: #1e40af; margin-bottom: 1rem;">
                            Agora você pode usar o <strong>Launcher Web</strong> para controlar tudo pelo navegador!
                        </p>
                        <p style="color: #1e40af; font-size: 0.9rem;">
                            O launcher web permite iniciar, parar e gerenciar o sistema sem usar comandos no terminal.
                        </p>
                    </div>

                    <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 1rem; margin-bottom: 1.5rem;">
                        <div style="font-weight: 600; color: #1e40af; margin-bottom: 0.5rem;">💡 Próximos Passos:</div>
                        <ul style="color: #1e40af; font-size: 0.875rem; text-align: left;">
                            <li>• Use o <strong>Launcher Web</strong> para controlar o sistema</li>
                            <li>• Configure seu servidor Minecraft nas configurações</li>
                            <li>• Habilite RCON para comandos remotos</li>
                            <li>• Mantenha o XAMPP sempre rodando</li>
                        </ul>
                    </div>
                    
                    <div class="flex">
                        <a href="web-launcher.html" class="btn">🚀 Abrir Launcher Web</a>
                        <a href="../README.md" target="_blank" class="btn" style="background: #6b7280;">📖 Ver Documentação</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Footer -->
        <div style="text-align: center; color: #6b7280; font-size: 0.875rem;">
            Minecraft Server Monitor - Prominence II RPG Edition<br>
            <span style="font-size: 0.75rem;">Sistema completo - Tudo pelo navegador!</span>
        </div>
    </div>

    <?php if ($step == 1 && !$allPassed): ?>
    <script>
        // Auto-refresh na verificação do sistema se houver falhas
        setTimeout(() => {
            location.reload();
        }, 5000);
    </script>
    <?php endif; ?>
</body>
</html>