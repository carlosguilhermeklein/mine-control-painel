-- Criar banco de dados
CREATE DATABASE IF NOT EXISTS minecraft_monitor;
USE minecraft_monitor;

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

-- Inserir configurações padrão
INSERT IGNORE INTO settings (setting_key, setting_value) VALUES
('server_path', 'C:\\Minecraft\\Prominence II RPG\\start.bat'),
('log_path', 'C:\\Minecraft\\Prominence II RPG\\logs\\latest.log'),
('server_ip', '127.0.0.1'),
('server_port', '25565'),
('rcon_enabled', '1'),
('rcon_ip', '127.0.0.1'),
('rcon_port', '25575'),
('rcon_password', 'minecraft'),
('auto_start', '0'),
('auto_restart', '1'),
('max_players', '20'),
('difficulty', 'normal');

-- Inserir usuário padrão (senha: admin123)
INSERT IGNORE INTO users (username, password_hash) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');