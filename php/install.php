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
                    <span class="text-sm font-medium text-minecraft-700"><?php echo $step; ?>/4</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                    <div class="bg-minecraft-600 h-2 rounded-full transition-all duration-300" style="width: <?php echo ($step/4)*100; ?>%"></div>
                </div>
            </div>

            <!-- Installation Form -->
            <div class="bg-white rounded-2xl shadow-xl p-8">
                <?php if ($error): ?>
                    <div class="mb-6 p-4 bg-red-100 border border-red-300 rounded-lg">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 text-red-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                            </svg>
                            <p class="text-red-700"><?php echo htmlspecialchars($error); ?></p>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="mb-6 p-4 bg-green-100 border border-green-300 rounded-lg">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <p class="text-green-700"><?php echo htmlspecialchars($success); ?></p>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($step == 1): ?>
                    <!-- Step 1: System Check -->
                    <div class="mb-6">
                        <h2 class="text-2xl font-bold text-gray-900 mb-4">Verificação do Sistema</h2>
                        <p class="text-gray-600 mb-6">Verificando se todos os requisitos estão atendidos...</p>
                    </div>

                    <?php
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
                    ?>

                    <div class="space-y-3 mb-6">
                        <?php foreach ($checks as $check => $result): ?>
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                <span class="text-gray-700"><?php echo $check; ?></span>
                                <?php if ($result): ?>
                                    <span class="flex items-center text-green-600">
                                        <svg class="w-5 h-5 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                        </svg>
                                        OK
                                    </span>
                                <?php else: ?>
                                    <span class="flex items-center text-red-600">
                                        <svg class="w-5 h-5 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                        </svg>
                                        FALHOU
                                    </span>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <?php if ($allPassed): ?>
                        <a href="?step=2" class="w-full block text-center bg-minecraft-600 hover:bg-minecraft-700 text-white font-medium py-3 px-4 rounded-lg transition-colors">
                            Continuar para Configuração do Banco
                        </a>
                    <?php else: ?>
                        <div class="p-4 bg-red-50 border border-red-200 rounded-lg">
                            <p class="text-red-700 font-medium mb-2">Requisitos não atendidos!</p>
                            <p class="text-red-600 text-sm">Corrija os problemas acima antes de continuar. Verifique se o XAMPP está configurado corretamente.</p>
                        </div>
                    <?php endif; ?>

                <?php elseif ($step == 2): ?>
                    <!-- Step 2: Database Configuration -->
                    <div class="mb-6">
                        <h2 class="text-2xl font-bold text-gray-900 mb-4">Configuração do Banco de Dados</h2>
                        <p class="text-gray-600 mb-6">Configure a conexão com o MySQL do XAMPP. O banco e todas as tabelas serão criados automaticamente.</p>
                    </div>

                    <form method="POST" class="space-y-6">
                        <input type="hidden" name="step" value="2">
                        
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
                            Testar Conexão e Criar Estrutura
                        </button>
                    </form>

                <?php elseif ($step == 3): ?>
                    <!-- Step 3: Admin User Creation -->
                    <div class="mb-6">
                        <h2 class="text-2xl font-bold text-gray-900 mb-4">Criar Usuário Administrador</h2>
                        <p class="text-gray-600 mb-6">O banco e tabelas foram criados com sucesso! Agora defina as credenciais do usuário administrador.</p>
                    </div>

                    <form method="POST" class="space-y-6">
                        <input type="hidden" name="step" value="3">
                        
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

                <?php elseif ($step == 4): ?>
                    <!-- Step 4: Installation Complete -->
                    <div class="text-center">
                        <div class="mx-auto w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mb-4">
                            <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                        
                        <h2 class="text-2xl font-bold text-gray-900 mb-4">🎉 Instalação Concluída!</h2>
                        <p class="text-gray-600 mb-8">O sistema foi instalado com sucesso. Banco criado, tabelas estruturadas e usuário administrador configurado.</p>
                        
                        <div class="bg-gray-50 rounded-lg p-6 mb-6">
                            <h3 class="font-semibold text-gray-900 mb-4">📋 Próximos Passos:</h3>
                            <ol class="text-left text-gray-700 space-y-3">
                                <li class="flex items-start">
                                    <span class="flex-shrink-0 w-6 h-6 bg-minecraft-600 text-white rounded-full flex items-center justify-center text-sm font-bold mr-3 mt-0.5">1</span>
                                    <div>
                                        <strong>Iniciar Interface React:</strong><br>
                                        Execute <code class="bg-gray-200 px-2 py-1 rounded text-sm">npm run dev</code> no terminal
                                    </div>
                                </li>
                                <li class="flex items-start">
                                    <span class="flex-shrink-0 w-6 h-6 bg-minecraft-600 text-white rounded-full flex items-center justify-center text-sm font-bold mr-3 mt-0.5">2</span>
                                    <div>
                                        <strong>Acessar Sistema:</strong><br>
                                        Abra <code class="bg-gray-200 px-2 py-1 rounded text-sm">http://localhost:5173</code> no navegador
                                    </div>
                                </li>
                                <li class="flex items-start">
                                    <span class="flex-shrink-0 w-6 h-6 bg-minecraft-600 text-white rounded-full flex items-center justify-center text-sm font-bold mr-3 mt-0.5">3</span>
                                    <div>
                                        <strong>Configurar Servidor:</strong><br>
                                        Na aba "Settings", configure o caminho do seu arquivo <code class="bg-gray-200 px-2 py-1 rounded text-sm">.bat</code>
                                    </div>
                                </li>
                                <li class="flex items-start">
                                    <span class="flex-shrink-0 w-6 h-6 bg-minecraft-600 text-white rounded-full flex items-center justify-center text-sm font-bold mr-3 mt-0.5">4</span>
                                    <div>
                                        <strong>Habilitar RCON (Opcional):</strong><br>
                                        No <code class="bg-gray-200 px-2 py-1 rounded text-sm">server.properties</code>: <code class="bg-gray-200 px-2 py-1 rounded text-sm">enable-rcon=true</code>
                                    </div>
                                </li>
                            </ol>
                        </div>

                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                            <h4 class="font-semibold text-blue-900 mb-2">💡 Dicas Importantes:</h4>
                            <ul class="text-blue-800 text-sm space-y-1 text-left">
                                <li>• Mantenha o XAMPP (Apache + MySQL) sempre rodando</li>
                                <li>• Faça backup regular do banco de dados</li>
                                <li>• Configure caminhos corretos nas configurações</li>
                                <li>• Use RCON para comandos remotos no console</li>
                            </ul>
                        </div>
                        
                        <div class="flex space-x-4">
                            <a href="http://localhost:5173" target="_blank" class="flex-1 bg-minecraft-600 hover:bg-minecraft-700 text-white font-medium py-3 px-6 rounded-lg transition-colors">
                                🚀 Abrir Sistema
                            </a>
                            <a href="../README.md" target="_blank" class="flex-1 bg-gray-600 hover:bg-gray-700 text-white font-medium py-3 px-6 rounded-lg transition-colors">
                                📖 Ver Documentação
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Footer -->
            <div class="text-center mt-8">
                <p class="text-gray-500 text-sm">
                    Minecraft Server Monitor - Prominence II RPG Edition<br>
                    <span class="text-xs">Sistema de monitoramento completo para servidores Minecraft</span>
                </p>
            </div>
        </div>
    </div>

    <script>
        // Auto-refresh na verificação do sistema
        <?php if ($step == 1 && !$allPassed): ?>
        setTimeout(() => {
            location.reload();
        }, 5000);
        <?php endif; ?>
    </script>
</body>
</html>

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
    if ($step == 2) {
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
        
    } elseif ($step == 3) {
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
?>