<?php
// Sistema de instalação automática
session_start();

// Verificar se já foi instalado
if (file_exists('installed.lock')) {
    header('Location: ../index.html');
    exit;
}

$step = $_GET['step'] ?? 1;
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($step == 1) {
        // Testar conexão com banco
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
            
            // Salvar configurações na sessão
            $_SESSION['install_config'] = [
                'host' => $host,
                'user' => $user,
                'pass' => $pass,
                'name' => $name
            ];
            
            $success = 'Conexão com banco estabelecida! Banco criado com sucesso.';
            $step = 2;
            
        } catch (PDOException $e) {
            $error = 'Erro de conexão: ' . $e->getMessage();
        }
        
    } elseif ($step == 2) {
        // Criar estrutura do banco e usuário
        $admin_user = $_POST['admin_user'] ?? 'admin';
        $admin_pass = $_POST['admin_pass'] ?? '';
        $admin_pass_confirm = $_POST['admin_pass_confirm'] ?? '';
        
        if (empty($admin_pass) || $admin_pass !== $admin_pass_confirm) {
            $error = 'Senhas não conferem ou estão vazias!';
        } else {
            try {
                $config = $_SESSION['install_config'];
                $pdo = new PDO("mysql:host={$config['host']};dbname={$config['name']}", $config['user'], $config['pass']);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                // Criar tabelas
                $sql = "
                -- Tabela de configurações
                CREATE TABLE IF NOT EXISTS settings (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    setting_key VARCHAR(100) UNIQUE NOT NULL,
                    setting_value TEXT NOT NULL,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                );

                -- Tabela de usuários/senhas
                CREATE TABLE IF NOT EXISTS users (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    username VARCHAR(50) UNIQUE NOT NULL,
                    password_hash VARCHAR(255) NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                );

                -- Tabela de logs de comandos
                CREATE TABLE IF NOT EXISTS command_history (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    command TEXT NOT NULL,
                    response TEXT,
                    success BOOLEAN DEFAULT TRUE,
                    executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                );

                -- Tabela de logs do servidor
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
                
                // Criar usuário admin
                $passwordHash = password_hash($admin_pass, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, password_hash) VALUES (?, ?) ON DUPLICATE KEY UPDATE password_hash = ?");
                $stmt->execute([$admin_user, $passwordHash, $passwordHash]);
                
                // Criar arquivo de configuração
                $configContent = "<?php
// Configurações do banco de dados - Gerado automaticamente
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
                
                file_put_contents('config.php', $configContent);
                
                // Criar arquivo de lock para indicar instalação completa
                file_put_contents('installed.lock', date('Y-m-d H:i:s'));
                
                $success = 'Sistema instalado com sucesso!';
                $step = 3;
                
            } catch (PDOException $e) {
                $error = 'Erro ao criar estrutura: ' . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalação - Minecraft Server Monitor</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        minecraft: {
                            50: '#f0fdf4',
                            100: '#dcfce7',
                            200: '#bbf7d0',
                            300: '#86efac',
                            400: '#4ade80',
                            500: '#22c55e',
                            600: '#16a34a',
                            700: '#15803d',
                            800: '#166534',
                            900: '#14532d',
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gradient-to-br from-minecraft-50 to-minecraft-100 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-2xl mx-auto">
            <!-- Header -->
            <div class="text-center mb-8">
                <div class="mx-auto w-16 h-16 bg-minecraft-600 rounded-2xl flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                    </svg>
                </div>
                <h1 class="text-3xl font-bold text-gray-900 mb-2">Minecraft Server Monitor</h1>
                <p class="text-gray-600">Instalação e Configuração Inicial</p>
            </div>

            <!-- Progress Bar -->
            <div class="mb-8">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm font-medium text-minecraft-700">Progresso da Instalação</span>
                    <span class="text-sm font-medium text-minecraft-700"><?php echo $step; ?>/3</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                    <div class="bg-minecraft-600 h-2 rounded-full transition-all duration-300" style="width: <?php echo ($step/3)*100; ?>%"></div>
                </div>
            </div>

            <!-- Installation Form -->
            <div class="bg-white rounded-2xl shadow-xl p-8">
                <?php if ($error): ?>
                    <div class="mb-6 p-4 bg-red-100 border border-red-300 rounded-lg">
                        <p class="text-red-700"><?php echo htmlspecialchars($error); ?></p>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="mb-6 p-4 bg-green-100 border border-green-300 rounded-lg">
                        <p class="text-green-700"><?php echo htmlspecialchars($success); ?></p>
                    </div>
                <?php endif; ?>

                <?php if ($step == 1): ?>
                    <!-- Step 1: Database Configuration -->
                    <div class="mb-6">
                        <h2 class="text-2xl font-bold text-gray-900 mb-4">Configuração do Banco de Dados</h2>
                        <p class="text-gray-600 mb-6">Configure a conexão com o MySQL do XAMPP</p>
                    </div>

                    <form method="POST" class="space-y-6">
                        <input type="hidden" name="step" value="1">
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Host do Banco</label>
                            <input type="text" name="db_host" value="localhost" required
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-minecraft-500 focus:border-minecraft-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Usuário do Banco</label>
                            <input type="text" name="db_user" value="root" required
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-minecraft-500 focus:border-minecraft-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Senha do Banco</label>
                            <input type="password" name="db_pass" placeholder="Deixe vazio se não houver senha"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-minecraft-500 focus:border-minecraft-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Nome do Banco</label>
                            <input type="text" name="db_name" value="minecraft_monitor" required
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-minecraft-500 focus:border-minecraft-500">
                            <p class="text-sm text-gray-500 mt-1">O banco será criado automaticamente se não existir</p>
                        </div>

                        <button type="submit" class="w-full bg-minecraft-600 hover:bg-minecraft-700 text-white font-medium py-3 px-4 rounded-lg transition-colors">
                            Testar Conexão e Continuar
                        </button>
                    </form>

                <?php elseif ($step == 2): ?>
                    <!-- Step 2: Admin User Creation -->
                    <div class="mb-6">
                        <h2 class="text-2xl font-bold text-gray-900 mb-4">Criar Usuário Administrador</h2>
                        <p class="text-gray-600 mb-6">Defina as credenciais do usuário administrador</p>
                    </div>

                    <form method="POST" class="space-y-6">
                        <input type="hidden" name="step" value="2">
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Nome de Usuário</label>
                            <input type="text" name="admin_user" value="admin" required
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-minecraft-500 focus:border-minecraft-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Senha</label>
                            <input type="password" name="admin_pass" required minlength="6"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-minecraft-500 focus:border-minecraft-500">
                            <p class="text-sm text-gray-500 mt-1">Mínimo 6 caracteres</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Confirmar Senha</label>
                            <input type="password" name="admin_pass_confirm" required minlength="6"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-minecraft-500 focus:border-minecraft-500">
                        </div>

                        <button type="submit" class="w-full bg-minecraft-600 hover:bg-minecraft-700 text-white font-medium py-3 px-4 rounded-lg transition-colors">
                            Criar Usuário e Finalizar Instalação
                        </button>
                    </form>

                <?php elseif ($step == 3): ?>
                    <!-- Step 3: Installation Complete -->
                    <div class="text-center">
                        <div class="mx-auto w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mb-4">
                            <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                        
                        <h2 class="text-2xl font-bold text-gray-900 mb-4">Instalação Concluída!</h2>
                        <p class="text-gray-600 mb-8">O sistema foi instalado com sucesso. Agora você pode acessar o painel de controle.</p>
                        
                        <div class="bg-gray-50 rounded-lg p-6 mb-6">
                            <h3 class="font-semibold text-gray-900 mb-4">Próximos Passos:</h3>
                            <ol class="text-left text-gray-700 space-y-2">
                                <li>1. Configure o caminho do seu servidor Minecraft nas configurações</li>
                                <li>2. Habilite RCON no seu server.properties se desejar usar o console remoto</li>
                                <li>3. Ajuste os caminhos dos logs para monitoramento em tempo real</li>
                            </ol>
                        </div>
                        
                        <a href="../index.html" class="inline-block bg-minecraft-600 hover:bg-minecraft-700 text-white font-medium py-3 px-6 rounded-lg transition-colors">
                            Acessar Sistema
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Footer -->
            <div class="text-center mt-8">
                <p class="text-gray-500 text-sm">
                    Minecraft Server Monitor - Prominence II RPG Edition
                </p>
            </div>
        </div>
    </div>
</body>
</html>