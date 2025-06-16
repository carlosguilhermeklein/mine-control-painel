# Minecraft Server Monitor - Sistema 100% Web

Sistema completo para monitorar e controlar servidor Minecraft modificado (Prominence II RPG - Hasturian Era) **totalmente pelo navegador** - sem necessidade de comandos no terminal!

## 🌟 **NOVIDADE: Launcher Web**

Agora você pode **iniciar, parar e gerenciar tudo pelo navegador**! Não precisa mais usar comandos no terminal.

## 🚀 Instalação Super Simples

### 1. Preparar XAMPP
1. Instale o [XAMPP](https://www.apachefriends.org/)
2. Inicie **Apache** e **MySQL** no painel de controle do XAMPP

### 2. Instalar o Sistema
1. Copie a pasta `php` para `C:\xampp\htdocs\minecraft-monitor\`
2. Acesse `http://localhost/minecraft-monitor/php/install.php`
3. Siga o assistente de instalação (4 passos simples)
4. **Pronto!** Sistema instalado automaticamente

### 3. Usar o Launcher Web
1. Após a instalação, acesse `http://localhost/minecraft-monitor/php/web-launcher.html`
2. Clique em **"Iniciar Sistema"** 
3. Aguarde o sistema carregar
4. Clique em **"Abrir no Navegador"**
5. **Pronto!** Sistema funcionando 100% pelo navegador

## ✨ Funcionalidades Completas

### 🎛️ **Dashboard Inteligente**
- ✅ Status do servidor em tempo real (Online/Offline)
- ✅ Contagem de jogadores conectados
- ✅ Controles para iniciar/parar/reiniciar servidor Minecraft
- ✅ Lista de jogadores online com tempo de conexão
- ✅ Informações detalhadas do servidor
- ✅ Uptime e estatísticas

### 📋 **Logs Avançados**
- ✅ Visualização em tempo real dos logs do servidor
- ✅ Filtros por nível (INFO, WARN, ERROR, DEBUG)
- ✅ Busca por texto nos logs
- ✅ Download dos logs em arquivo
- ✅ Auto-refresh configurável
- ✅ Interface tipo terminal

### 💻 **Console Remoto RCON**
- ✅ Execução de comandos diretamente no servidor
- ✅ Histórico completo de comandos
- ✅ Comandos rápidos predefinidos
- ✅ Status da conexão RCON em tempo real
- ✅ Navegação por histórico com setas ↑↓

### ⚙️ **Configurações Completas**
- ✅ Configuração de caminhos do servidor
- ✅ Configurações de porta e IP
- ✅ Configurações RCON completas
- ✅ Opções de auto-start e auto-restart
- ✅ Teste de conexão RCON
- ✅ Interface intuitiva

### 🌐 **Launcher Web (NOVO!)**
- ✅ Iniciar/parar sistema pelo navegador
- ✅ Status em tempo real
- ✅ Logs do sistema
- ✅ Abertura automática do navegador
- ✅ Não precisa de comandos no terminal!

## 🎮 Como Usar - Passo a Passo

### **Primeira Vez:**
1. **Instale XAMPP** e inicie Apache + MySQL
2. **Copie a pasta `php`** para `C:\xampp\htdocs\minecraft-monitor\`
3. **Acesse** `http://localhost/minecraft-monitor/php/install.php`
4. **Complete a instalação** (4 passos automáticos)

### **Uso Diário:**
1. **Abra** `http://localhost/minecraft-monitor/php/web-launcher.html`
2. **Clique** "Iniciar Sistema"
3. **Clique** "Abrir no Navegador"
4. **Configure** seu servidor na aba "Settings"
5. **Use** todas as funcionalidades!

## 🔧 Configuração do Servidor Minecraft

Para usar **todas as funcionalidades**, configure seu `server.properties`:

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
│   ├── web-launcher.html    # 🌟 Launcher Web (NOVO!)
│   ├── launcher.php         # 🌟 API do Launcher (NOVO!)
│   ├── config.php           # Configurações (gerado automaticamente)
│   ├── auth.php             # Sistema de autenticação
│   ├── server.php           # Controle do servidor Minecraft
│   ├── logs.php             # Leitura de logs
│   ├── console.php          # Console RCON
│   └── settings.php         # Gerenciamento de configurações
└── src/                     # Interface React (iniciada automaticamente)
```

## 🛠️ Funcionalidades Avançadas

### **Launcher Web**
- ✅ **Detecção automática** do Node.js
- ✅ **Instalação automática** de dependências
- ✅ **Inicialização automática** do servidor de desenvolvimento
- ✅ **Abertura automática** do navegador
- ✅ **Logs em tempo real** do sistema
- ✅ **Status visual** com indicadores

### **Sistema de Monitoramento**
- ✅ **Auto-refresh** a cada 10 segundos
- ✅ **Detecção de jogadores** via RCON
- ✅ **Monitoramento de processos** do servidor
- ✅ **Logs em tempo real** do Minecraft
- ✅ **Histórico de comandos** persistente

### **Interface Moderna**
- ✅ **Dark/Light Mode** automático
- ✅ **Design responsivo** para mobile/desktop
- ✅ **Animações suaves** e micro-interações
- ✅ **Tema Minecraft** com cores personalizadas
- ✅ **Ícones Lucide** profissionais

## 🔍 Troubleshooting

### **Sistema não inicia no Launcher Web**
- ✅ Instale o [Node.js](https://nodejs.org/) se solicitado
- ✅ Verifique se o XAMPP está rodando
- ✅ Execute novamente o instalador se necessário

### **Servidor Minecraft não inicia**
- ✅ Verifique o caminho do `.bat` nas configurações
- ✅ Certifique-se que Java está instalado
- ✅ Verifique permissões dos arquivos

### **RCON não funciona**
- ✅ Confirme `enable-rcon=true` no `server.properties`
- ✅ Verifique IP, porta e senha nas configurações
- ✅ Use o botão "Testar Conexão RCON"

### **Logs não aparecem**
- ✅ Verifique o caminho do arquivo de log
- ✅ Certifique-se que o arquivo existe
- ✅ Verifique permissões de leitura

## 🔒 Segurança

- ✅ **Senhas criptografadas** com hash seguro
- ✅ **Sessões com timeout** automático (15 min)
- ✅ **Proteção CSRF** em formulários
- ✅ **Validação de entrada** em todos os campos
- ✅ **Headers de segurança** configurados

## 📊 Tecnologias

- **Backend**: PHP 7.4+ com PDO MySQL
- **Frontend**: React + TypeScript + Tailwind CSS
- **Banco**: MySQL com estrutura otimizada
- **Launcher**: HTML5 + JavaScript puro
- **Segurança**: Autenticação por sessão
- **API**: RESTful com CORS configurado

## 🎮 Compatibilidade

- ✅ **Windows** (XAMPP)
- ✅ **Minecraft Java Edition**
- ✅ **Servidores modificados** (Forge, Fabric, etc.)
- ✅ **Prominence II RPG** - Hasturian Era
- ✅ **Qualquer modpack** que use arquivo `.bat`
- ✅ **Navegadores modernos** (Chrome, Firefox, Edge)

## 🌟 Diferenciais

### **🚀 Launcher Web Revolucionário**
- **Primeira vez** que um sistema Minecraft roda 100% pelo navegador
- **Sem comandos** no terminal - tudo visual
- **Detecção automática** de dependências
- **Instalação automática** de tudo que precisa

### **🎨 Interface Profissional**
- **Design Apple-level** com atenção aos detalhes
- **Animações suaves** e feedback visual
- **Responsivo** para todos os dispositivos
- **Tema escuro/claro** automático

### **⚡ Performance Otimizada**
- **Auto-refresh inteligente** sem sobrecarregar
- **Lazy loading** de componentes
- **Cache otimizado** de dados
- **Conexões persistentes** para RCON

---

## 🎉 **Pronto para Usar!**

O sistema detecta automaticamente se precisa ser instalado e guia você através do processo completo. 

**Tudo pelo navegador, sem complicação!** 🚀

### **Links Rápidos:**
- 🔧 **Instalação**: `http://localhost/minecraft-monitor/php/install.php`
- 🚀 **Launcher Web**: `http://localhost/minecraft-monitor/php/web-launcher.html`
- 🎮 **Sistema Principal**: `http://localhost:5173` (após iniciar)

**Minecraft Server Monitor** - A forma mais fácil de gerenciar seu servidor! 🛡️