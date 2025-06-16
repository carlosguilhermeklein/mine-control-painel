# Minecraft Server Monitor - Sistema PHP com Instalação Automática

Sistema web para monitorar e controlar servidor Minecraft modificado (Prominence II RPG - Hasturian Era) usando XAMPP com instalação automática.

## 🚀 Instalação Rápida

### 1. Preparar XAMPP
1. Instale o XAMPP
2. Inicie **Apache** e **MySQL** no painel de controle do XAMPP

### 2. Instalar o Sistema
1. Copie a pasta `php` para `C:\xampp\htdocs\minecraft-monitor\`
2. Acesse `http://localhost/minecraft-monitor/php/install.php`
3. Siga o assistente de instalação:
   - **Passo 1**: Configure a conexão com MySQL (geralmente `root` sem senha)
   - **Passo 2**: Crie seu usuário administrador
   - **Passo 3**: Instalação concluída!

### 3. Executar Interface Web
1. Execute `npm run dev` para iniciar a interface React
2. Acesse `http://localhost:5173`
3. Faça login com as credenciais criadas na instalação

## ✨ Funcionalidades

### 🎛️ Dashboard
- Status do servidor (Online/Offline) em tempo real
- Contagem de jogadores conectados
- Controles para iniciar/parar/reiniciar servidor
- Lista de jogadores online com tempo de conexão
- Informações detalhadas do servidor

### 📋 Logs
- Visualização em tempo real dos logs do servidor
- Filtros por nível (INFO, WARN, ERROR, DEBUG)
- Busca por texto nos logs
- Download dos logs em arquivo
- Auto-refresh configurável

### 💻 Console
- Execução de comandos via RCON
- Histórico completo de comandos
- Comandos rápidos predefinidos
- Status da conexão RCON em tempo real

### ⚙️ Configurações
- Configuração de caminhos do servidor
- Configurações de porta e IP
- Configurações RCON completas
- Opções de auto-start e auto-restart
- Teste de conexão RCON

## 🔧 Configuração do Servidor Minecraft

Para usar todas as funcionalidades, configure seu `server.properties`:

```properties
enable-rcon=true
rcon.port=25575
rcon.password=minecraft
```

Depois reinicie o servidor Minecraft.

## 📁 Estrutura do Sistema

```
minecraft-monitor/
├── php/
│   ├── install.php          # Instalador automático
│   ├── config.php           # Configurações (gerado automaticamente)
│   ├── auth.php             # Sistema de autenticação
│   ├── server.php           # Controle do servidor
│   ├── logs.php             # Leitura de logs
│   ├── console.php          # Console RCON
│   └── settings.php         # Gerenciamento de configurações
└── src/                     # Interface React
```

## 🛠️ Personalização

### Alterar Configurações Padrão
Após a instalação, acesse a aba **Configurações** para:
- Definir o caminho correto do seu arquivo `.bat`
- Configurar caminhos de logs
- Ajustar configurações RCON
- Definir opções de auto-start

### Alterar Senha de Admin
Na aba **Configurações**, você pode alterar a senha do administrador.

## 🔍 Troubleshooting

### Erro "Sistema não instalado"
- Acesse `http://localhost/minecraft-monitor/php/install.php`
- Complete o processo de instalação

### Servidor não inicia
- Verifique se o caminho do `.bat` está correto nas configurações
- Certifique-se que o arquivo existe e tem permissões
- Verifique se Java está instalado

### RCON não funciona
- Confirme que RCON está habilitado no `server.properties`
- Verifique IP, porta e senha nas configurações
- Use o botão "Testar Conexão RCON" nas configurações

### Logs não aparecem
- Verifique se o caminho do arquivo de log está correto
- Certifique-se que o arquivo existe e tem permissões de leitura

### Erro de conexão com banco
- Verifique se MySQL está rodando no XAMPP
- Execute novamente o instalador se necessário

## 🔒 Segurança

- ✅ Senhas são criptografadas com hash seguro
- ✅ Sessões expiram automaticamente em 15 minutos
- ✅ Proteção contra acesso não autorizado
- ✅ Validação de entrada em todos os formulários

## 📊 Recursos Técnicos

- **Backend**: PHP 7.4+ com PDO MySQL
- **Frontend**: React + TypeScript + Tailwind CSS
- **Banco**: MySQL com estrutura otimizada
- **Segurança**: Autenticação por sessão com timeout
- **API**: RESTful com CORS configurado
- **Logs**: Leitura em tempo real de arquivos
- **RCON**: Comunicação direta com servidor Minecraft

## 🎮 Compatibilidade

- ✅ Windows (XAMPP)
- ✅ Minecraft Java Edition
- ✅ Servidores modificados (Forge, Fabric, etc.)
- ✅ Prominence II RPG - Hasturian Era
- ✅ Qualquer modpack que use arquivo `.bat`

---

**Pronto para usar!** 🚀 O sistema detecta automaticamente se precisa ser instalado e guia você através do processo completo.