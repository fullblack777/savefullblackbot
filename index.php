<?php
// ============================================
// CYBERSECOFC 3.0 - VERSÃO ULTRA OTIMIZADA
// SISTEMA COMPLETO COM SQLITE EMBUTIDO
// ============================================

// Configurações iniciais
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);
date_default_timezone_set('America/Sao_Paulo');

// Configurações de memória e tempo
ini_set('memory_limit', '512M');
ini_set('max_execution_time', 600);
ini_set('upload_max_filesize', '20M');
ini_set('post_max_size', '20M');

// ============================================
// SISTEMA DE SESSÃO SEGURO
// ============================================
$session_path = sys_get_temp_dir() . '/cybersecofc_v3_sessions';
if (!is_dir($session_path)) {
    mkdir($session_path, 0777, true);
}
session_save_path($session_path);

session_start([
    'cookie_httponly' => true,
    'cookie_secure' => isset($_SERVER['HTTPS']),
    'cookie_samesite' => 'Strict',
    'use_strict_mode' => true,
    'use_only_cookies' => true,
    'gc_maxlifetime' => 3600
]);

// Gerar token de segurança único
if (!isset($_SESSION['_cyber_token'])) {
    $_SESSION['_cyber_token'] = bin2hex(random_bytes(32));
}

// ============================================
// BANCO DE DADOS SQLITE EMBUTIDO (SEM ERROS)
// ============================================
class Database {
    private static $instance = null;
    private $connection = null;
    
    private function __construct() {
        try {
            $db_path = __DIR__ . '/cybersecofc_v3.db';
            $this->connection = new PDO('sqlite:' . $db_path);
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->connection->exec('PRAGMA foreign_keys = ON');
            $this->connection->exec('PRAGMA journal_mode = WAL');
            $this->connection->exec('PRAGMA synchronous = NORMAL');
            
            // Criar tabelas se não existirem
            $this->createTables();
            
        } catch (PDOException $e) {
            error_log("Erro SQLite: " . $e->getMessage());
            die("Sistema em manutenção. Tente novamente em instantes.");
        }
    }
    
    private function createTables() {
        // Tabela de usuários
        $this->connection->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username TEXT UNIQUE NOT NULL,
                password TEXT NOT NULL,
                role TEXT DEFAULT 'user',
                type TEXT DEFAULT 'permanent',
                credits REAL DEFAULT 0,
                expires_at DATETIME,
                tools TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        // Tabela de lives
        $this->connection->exec("
            CREATE TABLE IF NOT EXISTS lives (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username TEXT NOT NULL,
                gate TEXT NOT NULL,
                card TEXT NOT NULL,
                response TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        // Tabela de configurações
        $this->connection->exec("
            CREATE TABLE IF NOT EXISTS settings (
                key TEXT PRIMARY KEY,
                value TEXT,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        // Verificar se usuário admin existe
        $stmt = $this->connection->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
        $stmt->execute(['save']);
        
        if ($stmt->fetchColumn() == 0) {
            // Criar usuário admin padrão
            $tools = json_encode([
                'paypal', 'preauth', 'n7', 'amazon1', 'amazon2', 'cpfchecker', 
                'ggsitau', 'getnet', 'auth', 'debitando', 'n7_new', 'gringa', 
                'elo', 'erede', 'allbins', 'stripe', 'visamaster'
            ]);
            
            $stmt = $this->connection->prepare("
                INSERT INTO users (username, password, role, type, credits, tools) 
                VALUES (?, ?, 'admin', 'permanent', 0, ?)
            ");
            $stmt->execute(['save', password_hash('black', PASSWORD_DEFAULT), $tools]);
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    // Funções de usuário
    public function getUser($username) {
        $stmt = $this->connection->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        return $stmt->fetch();
    }
    
    public function getAllUsers() {
        return $this->connection->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll();
    }
    
    public function addUser($username, $password, $role, $type, $credits = 0, $tools = [], $expiresAt = null) {
        $stmt = $this->connection->prepare("
            INSERT INTO users (username, password, role, type, credits, tools, expires_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        return $stmt->execute([
            $username, 
            password_hash($password, PASSWORD_DEFAULT), 
            $role, 
            $type, 
            $credits, 
            json_encode($tools), 
            $expiresAt
        ]);
    }
    
    public function updateUser($username, $data) {
        $fields = [];
        $values = [];
        
        foreach ($data as $field => $value) {
            if ($field === 'tools') {
                $value = json_encode($value);
            }
            $fields[] = "$field = ?";
            $values[] = $value;
        }
        
        $values[] = $username;
        $stmt = $this->connection->prepare("
            UPDATE users SET " . implode(', ', $fields) . " WHERE username = ?
        ");
        return $stmt->execute($values);
    }
    
    public function deleteUser($username) {
        $stmt = $this->connection->prepare("DELETE FROM users WHERE username = ?");
        return $stmt->execute([$username]);
    }
    
    public function deductCredits($username, $amount) {
        $stmt = $this->connection->prepare("
            UPDATE users SET credits = credits - ? 
            WHERE username = ? AND credits >= ?
        ");
        return $stmt->execute([$amount, $username, $amount]);
    }
    
    // Funções de lives
    public function addLive($username, $gate, $card, $response) {
        $stmt = $this->connection->prepare("
            INSERT INTO lives (username, gate, card, response) VALUES (?, ?, ?, ?)
        ");
        return $stmt->execute([$username, $gate, $card, $response]);
    }
    
    public function getUserLives($username) {
        $stmt = $this->connection->prepare("
            SELECT * FROM lives WHERE username = ? ORDER BY created_at DESC
        ");
        $stmt->execute([$username]);
        return $stmt->fetchAll();
    }
    
    // Funções de configuração
    public function getSetting($key, $default = '') {
        $stmt = $this->connection->prepare("SELECT value FROM settings WHERE key = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetch();
        return $result ? $result['value'] : $default;
    }
    
    public function setSetting($key, $value) {
        $stmt = $this->connection->prepare("
            INSERT OR REPLACE INTO settings (key, value, updated_at) VALUES (?, ?, CURRENT_TIMESTAMP)
        ");
        return $stmt->execute([$key, $value]);
    }
}

// Inicializar banco de dados
$db = Database::getInstance();

// ============================================
// CONFIGURAÇÕES DAS FERRAMENTAS
// ============================================
$all_tools = [
    'checkers' => [
        'paypal', 'preauth', 'n7', 'amazon1', 'amazon2', 'cpfchecker', 'ggsitau', 
        'getnet', 'auth', 'debitando', 'n7_new', 'gringa', 'elo', 'erede', 
        'allbins', 'stripe', 'visamaster'
    ]
];

$checker_names = [
    'paypal' => 'PayPal V2',
    'preauth' => 'Preauth',
    'n7' => 'PAGARME',
    'amazon1' => 'Amazon Prime',
    'amazon2' => 'Amazon UK',
    'cpfchecker' => 'CPF Checker',
    'ggsitau' => 'GGs ITAU',
    'getnet' => 'GETNET',
    'auth' => 'AUTH',
    'debitando' => 'DEBITANDO',
    'n7_new' => 'N7',
    'gringa' => 'GRINGA',
    'elo' => 'ELO',
    'erede' => 'EREDE',
    'allbins' => 'ALLBINS',
    'stripe' => 'STRIPE',
    'visamaster' => 'VISA/MASTER'
];

$tool_details = [
    'paypal' => ['icon' => '💰', 'desc' => 'Verificação completa de cartões via PayPal'],
    'preauth' => ['icon' => '🔐', 'desc' => 'Gate debitando com alta taxa de aprovação'],
    'n7' => ['icon' => '⚡', 'desc' => 'Checker SAINDO MASTER-VISA-AMEX'],
    'amazon1' => ['icon' => '📦', 'desc' => 'Verifica cartões via Amazon Prime US'],
    'amazon2' => ['icon' => '🛒', 'desc' => 'Verifica cartões via Amazon UK'],
    'cpfchecker' => ['icon' => '🔍', 'desc' => 'Verificação de CPF completa'],
    'ggsitau' => ['icon' => '🏦', 'desc' => 'APENAS RETONOS MASTER-VISA'],
    'getnet' => ['icon' => '💳', 'desc' => 'Verificação GETNET com alta taxa'],
    'auth' => ['icon' => '🔒', 'desc' => 'Sistema de autorização avançado'],
    'debitando' => ['icon' => '💸', 'desc' => 'Verificação de débito em tempo real'],
    'n7_new' => ['icon' => '⚡', 'desc' => 'Checker N7 atualizado'],
    'gringa' => ['icon' => '🌎', 'desc' => 'Checker internacional completo'],
    'elo' => ['icon' => '💎', 'desc' => 'Verificação ELO premium'],
    'erede' => ['icon' => '🔄', 'desc' => 'Sistema EREDE otimizado'],
    'allbins' => ['icon' => '📊', 'desc' => 'Verificação múltipla de bins'],
    'stripe' => ['icon' => '💳', 'desc' => 'Checker Stripe com alta taxa'],
    'visamaster' => ['icon' => '💳', 'desc' => 'Verificação direta VISA/MASTER']
];

// ============================================
// SISTEMA DE SEGURANÇA AVANÇADO
// ============================================
header_remove('X-Powered-By');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: no-referrer');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');

// Detecção de ferramentas de hacking
function isHackerRequest() {
    // Verificar user agents suspeitos
    if (isset($_SERVER['HTTP_USER_AGENT'])) {
        $ua = strtolower($_SERVER['HTTP_USER_AGENT']);
        $hacking_agents = ['sqlmap', 'nmap', 'nikto', 'wpscan', 'burp', 'zap', 'hydra'];
        foreach ($hacking_agents as $agent) {
            if (strpos($ua, $agent) !== false) return true;
        }
    }
    
    // Verificar tentativas de SQL injection
    $request_data = array_merge($_GET, $_POST);
    $sql_patterns = ['union select', 'insert into', 'drop table', 'delete from'];
    
    foreach ($request_data as $value) {
        if (is_string($value)) {
            $lower = strtolower($value);
            foreach ($sql_patterns as $pattern) {
                if (strpos($lower, $pattern) !== false) return true;
            }
        }
    }
    
    return false;
}

if (isHackerRequest()) {
    session_destroy();
    header("Location: https://www.google.com");
    exit;
}

// Script de segurança JavaScript
$security_script = <<<JS
<script>
(function() {
    'use strict';
    
    // Detectar DevTools
    const detectDevTools = () => {
        const start = performance.now();
        debugger;
        const end = performance.now();
        return (end - start) > 100;
    };
    
    setInterval(() => {
        if (detectDevTools()) {
            document.body.innerHTML = '<h1>Acesso negado</h1>';
            setTimeout(() => window.location.href = 'https://www.google.com', 1000);
        }
    }, 2000);
    
    // Bloquear teclas de inspeção
    document.addEventListener('keydown', (e) => {
        if ((e.ctrlKey && e.shiftKey && ['I', 'J', 'C'].includes(e.key)) || 
            e.key === 'F12' || (e.ctrlKey && e.key === 'u')) {
            e.preventDefault();
            e.stopPropagation();
            return false;
        }
    });
    
    // Bloquear clique direito
    document.addEventListener('contextmenu', (e) => {
        e.preventDefault();
        return false;
    });
})();
</script>
JS;

// ============================================
// PROCESSAR LOGIN
// ============================================
if (isset($_POST['login'])) {
    if (!isset($_SESSION['login_attempts'])) $_SESSION['login_attempts'] = 0;
    
    $username = preg_replace('/[^a-zA-Z0-9_]/', '', $_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Decodificar base64 se necessário
    if (base64_encode(base64_decode($username, true)) === $username) {
        $username = base64_decode($username);
    }
    if (base64_encode(base64_decode($password, true)) === $password) {
        $password = base64_decode($password);
    }
    
    $user = $db->getUser($username);
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['logged_in'] = true;
        $_SESSION['username'] = $username;
        $_SESSION['role'] = $user['role'];
        $_SESSION['type'] = $user['type'];
        $_SESSION['tools'] = json_decode($user['tools'], true) ?: [];
        $_SESSION['login_time'] = time();
        $_SESSION['login_attempts'] = 0;
        
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    } else {
        $_SESSION['login_attempts']++;
        $login_error = 'Usuário ou senha incorretos!';
    }
}

// ============================================
// PROCESSAR LOGOUT
// ============================================
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// ============================================
// VERIFICAR ACESSO DO USUÁRIO
// ============================================
function checkUserAccess() {
    global $db;
    
    if (!isset($_SESSION['logged_in'])) {
        return false;
    }
    
    $user = $db->getUser($_SESSION['username']);
    if (!$user) {
        session_destroy();
        return false;
    }
    
    // Verificar acesso temporário
    if ($user['type'] === 'temporary' && $user['expires_at']) {
        if (time() > strtotime($user['expires_at'])) {
            session_destroy();
            return false;
        }
    }
    
    // Verificar créditos
    if ($user['type'] === 'credits' && $user['credits'] < 0.05) {
        // Ainda pode acessar, mas não usar ferramentas
    }
    
    return true;
}

// ============================================
// PROCESSAR ADMIN ACTIONS
// ============================================
if (isset($_SESSION['logged_in']) && $_SESSION['role'] === 'admin' && isset($_POST['admin_action'])) {
    $action = $_POST['admin_action'];
    
    // Adicionar usuário permanente
    if ($action === 'add_permanent') {
        $username = preg_replace('/[^a-zA-Z0-9_]/', '', $_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $tools = $_POST['tools'] ?? [];
        
        if ($username && $password && !empty($tools)) {
            if (!$db->getUser($username)) {
                if ($db->addUser($username, $password, 'user', 'permanent', 0, $tools)) {
                    $success_message = "Usuário permanente criado com sucesso!";
                } else {
                    $error_message = "Erro ao criar usuário.";
                }
            } else {
                $error_message = "Usuário já existe!";
            }
        } else {
            $error_message = "Preencha todos os campos!";
        }
    }
    
    // Adicionar usuário temporário
    if ($action === 'add_temporary') {
        $username = preg_replace('/[^a-zA-Z0-9_]/', '', $_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $hours = intval($_POST['hours'] ?? 0);
        $tools = $_POST['tools'] ?? [];
        
        if ($username && $password && $hours > 0 && !empty($tools)) {
            if (!$db->getUser($username)) {
                $expiresAt = date('Y-m-d H:i:s', time() + ($hours * 3600));
                if ($db->addUser($username, $password, 'user', 'temporary', 0, $tools, $expiresAt)) {
                    $success_message = "Acesso temporário criado por $hours hora(s)!";
                } else {
                    $error_message = "Erro ao criar usuário.";
                }
            } else {
                $error_message = "Usuário já existe!";
            }
        } else {
            $error_message = "Preencha todos os campos corretamente!";
        }
    }
    
    // Adicionar usuário por créditos
    if ($action === 'add_credits') {
        $username = preg_replace('/[^a-zA-Z0-9_]/', '', $_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $credits = floatval($_POST['credits'] ?? 0);
        $tools = $_POST['tools'] ?? [];
        
        if ($username && $password && $credits > 0 && !empty($tools)) {
            if (!$db->getUser($username)) {
                if ($db->addUser($username, $password, 'user', 'credits', $credits, $tools)) {
                    $success_message = "Usuário por créditos criado com $credits créditos!";
                } else {
                    $error_message = "Erro ao criar usuário.";
                }
            } else {
                $error_message = "Usuário já existe!";
            }
        } else {
            $error_message = "Preencha todos os campos corretamente!";
        }
    }
    
    // Recarregar créditos
    if ($action === 'recharge') {
        $username = preg_replace('/[^a-zA-Z0-9_]/', '', $_POST['username'] ?? '');
        $credits = floatval($_POST['credits'] ?? 0);
        
        if ($username && $credits > 0) {
            $user = $db->getUser($username);
            if ($user) {
                if ($db->updateUser($username, ['credits' => $user['credits'] + $credits])) {
                    $success_message = "Créditos recarregados com sucesso!";
                } else {
                    $error_message = "Erro ao recarregar créditos.";
                }
            } else {
                $error_message = "Usuário não encontrado!";
            }
        } else {
            $error_message = "Preencha todos os campos!";
        }
    }
    
    // Remover usuário
    if ($action === 'remove') {
        $username = preg_replace('/[^a-zA-Z0-9_]/', '', $_POST['username'] ?? '');
        
        if ($username && $username !== 'save') {
            if ($db->deleteUser($username)) {
                $success_message = "Usuário removido com sucesso!";
            } else {
                $error_message = "Erro ao remover usuário.";
            }
        } else {
            $error_message = "Não é possível remover o usuário admin!";
        }
    }
}

// ============================================
// PROCESSAR CHECKER (AJAX)
// ============================================
if (isset($_GET['action']) && $_GET['action'] === 'check' && isset($_GET['lista']) && isset($_GET['tool'])) {
    if (!checkUserAccess()) {
        http_response_code(403);
        echo "Acesso negado.";
        exit;
    }
    
    $tool = $_GET['tool'];
    $lista = $_GET['lista'];
    $username = $_SESSION['username'];
    $user = $db->getUser($username);
    
    // Verificar se usuário tem acesso à ferramenta
    $userTools = json_decode($user['tools'], true) ?: [];
    if (!in_array($tool, $userTools)) {
        http_response_code(403);
        echo "Ferramenta não liberada para seu usuário.";
        exit;
    }
    
    // Verificar créditos
    $isLive = false;
    $response = "";
    
    // Simular resposta da ferramenta (substituir pela lógica real)
    $rand = rand(1, 10);
    $card_parts = explode('|', $lista);
    $card_number = $card_parts[0] ?? '';
    $card_month = $card_parts[1] ?? '';
    $card_year = $card_parts[2] ?? '';
    $card_cvv = $card_parts[3] ?? '';
    
    if ($rand <= 3) {
        $isLive = true;
        $response = "✅ Aprovada - $tool | Cartão: $card_number | Data: $card_month/$card_year | CVV: $card_cvv";
    } else {
        $response = "❌ Reprovada - $tool | Cartão: $card_number | Data: $card_month/$card_year | CVV: $card_cvv";
    }
    
    // Salvar live se aprovada
    if ($isLive) {
        $db->addLive($username, $tool, $card_number, $response);
    }
    
    // Deduzir créditos se for usuário por créditos
    if ($user['type'] === 'credits') {
        $cost = $isLive ? 1.50 : 0.05;
        $db->deductCredits($username, $cost);
        $response .= "\n💳 Crédito usado: $cost | Restante: " . number_format($user['credits'] - $cost, 2);
    }
    
    echo $response;
    exit;
}

// ============================================
// PÁGINA DE LOGIN
// ============================================
if (!isset($_SESSION['logged_in']) || !checkUserAccess()) {
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CYBERSECOFC 3.0 - LOGIN</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <?php echo $security_script; ?>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            min-height: 100vh;
            background: #000;
            background-image: 
                radial-gradient(circle at 10% 20%, rgba(0, 255, 255, 0.05) 0%, transparent 30%),
                radial-gradient(circle at 90% 80%, rgba(255, 0, 255, 0.05) 0%, transparent 30%),
                linear-gradient(45deg, transparent 45%, rgba(0, 255, 0, 0.02) 50%, transparent 55%);
            background-size: 100% 100%, 100% 100%, 50px 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 20px;
            position: relative;
            overflow-x: hidden;
        }
        
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: repeating-linear-gradient(0deg, rgba(0, 255, 0, 0.02) 0px, rgba(0, 255, 0, 0.02) 2px, transparent 2px, transparent 4px);
            pointer-events: none;
            animation: scan 8s linear infinite;
        }
        
        @keyframes scan {
            0% { transform: translateY(0); }
            100% { transform: translateY(100%); }
        }
        
        .container {
            max-width: 1200px;
            width: 100%;
            display: flex;
            flex-wrap: wrap;
            gap: 30px;
            position: relative;
            z-index: 10;
        }
        
        .login-box {
            flex: 1 1 400px;
            background: rgba(10, 10, 15, 0.95);
            backdrop-filter: blur(10px);
            border: 2px solid #00ff00;
            border-radius: 30px;
            padding: 50px 40px;
            box-shadow: 0 0 50px rgba(0, 255, 0, 0.3),
                        0 0 0 1px rgba(0, 255, 0, 0.5) inset;
            animation: glow 3s infinite;
        }
        
        @keyframes glow {
            0%, 100% { box-shadow: 0 0 50px rgba(0, 255, 0, 0.3), 0 0 0 1px rgba(0, 255, 0, 0.5) inset; }
            50% { box-shadow: 0 0 70px rgba(0, 255, 255, 0.4), 0 0 0 2px rgba(0, 255, 255, 0.5) inset; }
        }
        
        .brand {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .brand h1 {
            font-size: 48px;
            font-weight: 900;
            background: linear-gradient(45deg, #00ff00, #00ffff, #ff00ff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            letter-spacing: 5px;
            text-transform: uppercase;
            font-family: 'Courier New', monospace;
            animation: rainbow 3s linear infinite;
            background-size: 200% 200%;
        }
        
        @keyframes rainbow {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        .brand p {
            color: #00ff00;
            font-size: 14px;
            letter-spacing: 3px;
            margin-top: 10px;
            text-transform: uppercase;
            opacity: 0.8;
        }
        
        .input-group {
            margin-bottom: 25px;
        }
        
        .input-group label {
            display: block;
            color: #00ffff;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 8px;
            letter-spacing: 1px;
            text-transform: uppercase;
        }
        
        .input-group input {
            width: 100%;
            padding: 15px 20px;
            background: rgba(0, 0, 0, 0.7);
            border: 2px solid #00ff00;
            border-radius: 15px;
            color: #00ff00;
            font-size: 16px;
            font-family: 'Courier New', monospace;
            transition: all 0.3s;
        }
        
        .input-group input:focus {
            outline: none;
            border-color: #00ffff;
            box-shadow: 0 0 30px rgba(0, 255, 255, 0.3);
            background: rgba(0, 0, 0, 0.9);
        }
        
        .input-group input::placeholder {
            color: rgba(0, 255, 0, 0.3);
        }
        
        .login-btn {
            width: 100%;
            padding: 18px;
            background: linear-gradient(45deg, #00ff00, #00ffff);
            border: none;
            border-radius: 15px;
            color: #000;
            font-size: 20px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin: 20px 0 30px;
            border: 2px solid transparent;
            box-shadow: 0 0 30px rgba(0, 255, 0, 0.3);
        }
        
        .login-btn:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 40px rgba(0, 255, 0, 0.5);
            border-color: #fff;
        }
        
        .telegram-link {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            background: rgba(0, 0, 0, 0.5);
            padding: 15px 30px;
            border-radius: 50px;
            text-decoration: none;
            border: 2px solid #00ff00;
            transition: all 0.3s;
            margin-top: 20px;
        }
        
        .telegram-link i {
            font-size: 28px;
            color: #00ffff;
        }
        
        .telegram-link span {
            color: #00ff00;
            font-size: 18px;
            font-weight: 600;
        }
        
        .telegram-link:hover {
            background: #00ffff;
            border-color: #000;
        }
        
        .telegram-link:hover i,
        .telegram-link:hover span {
            color: #000;
        }
        
        .pricing-box {
            flex: 1.5 1 600px;
            background: rgba(10, 10, 15, 0.95);
            backdrop-filter: blur(10px);
            border: 2px solid #00ff00;
            border-radius: 30px;
            padding: 40px;
            box-shadow: 0 0 50px rgba(0, 255, 0, 0.3);
        }
        
        .pricing-title {
            text-align: center;
            margin-bottom: 30px;
            color: #00ffff;
            font-size: 28px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 3px;
            font-family: 'Courier New', monospace;
            border-bottom: 2px solid #00ff00;
            padding-bottom: 20px;
        }
        
        .credit-packages {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .credit-card {
            background: rgba(0, 0, 0, 0.5);
            border: 2px solid #00ff00;
            border-radius: 20px;
            padding: 25px 15px;
            text-align: center;
            transition: all 0.3s;
            cursor: pointer;
        }
        
        .credit-card:hover {
            transform: translateY(-5px);
            border-color: #00ffff;
            box-shadow: 0 10px 30px rgba(0, 255, 255, 0.3);
        }
        
        .credit-card .price {
            color: #00ff00;
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .credit-card .credits {
            color: #00ffff;
            font-size: 20px;
            font-weight: 600;
        }
        
        .planos-semanais {
            background: rgba(0, 0, 0, 0.5);
            border: 2px solid #00ff00;
            border-radius: 20px;
            padding: 30px;
        }
        
        .plano-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            border-bottom: 1px solid rgba(0, 255, 0, 0.3);
        }
        
        .plano-item:last-child {
            border-bottom: none;
        }
        
        .plano-nome {
            color: #00ffff;
            font-size: 18px;
            font-weight: bold;
        }
        
        .plano-preco {
            color: #00ff00;
            font-size: 20px;
            font-weight: bold;
        }
        
        .plano-creditos {
            color: #fff;
            font-size: 16px;
            background: rgba(0, 255, 255, 0.2);
            padding: 5px 15px;
            border-radius: 20px;
        }
        
        .error {
            background: rgba(255, 0, 0, 0.2);
            border: 2px solid #ff0000;
            color: #ff0000;
            padding: 15px;
            border-radius: 15px;
            margin-bottom: 25px;
            text-align: center;
            font-weight: bold;
        }
        
        .info {
            background: rgba(255, 255, 0, 0.2);
            border: 2px solid #ffff00;
            color: #ffff00;
            padding: 15px;
            border-radius: 15px;
            margin-bottom: 25px;
            text-align: center;
            font-weight: bold;
        }
        
        @media (max-width: 768px) {
            .brand h1 { font-size: 32px; }
            .login-box { padding: 30px 20px; }
            .pricing-box { padding: 30px 20px; }
            .credit-packages { grid-template-columns: 1fr; }
            .plano-item { flex-direction: column; gap: 10px; text-align: center; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-box">
            <div class="brand">
                <h1>CYBERSEC 3.0</h1>
                <p>SISTEMA PREMIUM DE CHECKERS</p>
            </div>
            
            <?php if (isset($_GET['expired'])): ?>
                <div class="info">⏱️ ACESSO EXPIRADO - CONTATE O ADMIN</div>
            <?php endif; ?>
            
            <?php if (isset($login_error)): ?>
                <div class="error">⚠️ <?php echo htmlspecialchars($login_error); ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="input-group">
                    <label><i class="fas fa-user"></i> USUÁRIO</label>
                    <input type="text" name="username" placeholder="Digite seu login" autocomplete="off" required>
                </div>
                
                <div class="input-group">
                    <label><i class="fas fa-lock"></i> SENHA</label>
                    <input type="password" name="password" placeholder="••••••••" required>
                </div>
                
                <button type="submit" name="login" class="login-btn">
                    <i class="fas fa-sign-in-alt"></i> ENTRAR
                </button>
            </form>
            
            <a href="https://t.me/centralsavefullblack" target="_blank" class="telegram-link">
                <i class="fab fa-telegram-plane"></i>
                <span>@centralsavefullblack</span>
            </a>
        </div>
        
        <div class="pricing-box">
            <div class="pricing-title">💳 TABELA DE CRÉDITOS</div>
            
            <div class="credit-packages">
                <div class="credit-card">
                    <div class="price">$35</div>
                    <div class="credits">65 CRÉDITOS</div>
                </div>
                <div class="credit-card">
                    <div class="price">$55</div>
                    <div class="credits">95 CRÉDITOS</div>
                </div>
                <div class="credit-card">
                    <div class="price">$90</div>
                    <div class="credits">155 CRÉDITOS</div>
                </div>
                <div class="credit-card">
                    <div class="price">$120</div>
                    <div class="credits">450 CRÉDITOS</div>
                </div>
            </div>
            
            <div class="planos-semanais">
                <div style="text-align: center; margin-bottom: 20px; color: #00ffff; font-size: 22px; font-weight: bold;">
                    🔥 PLANOS SEMANAIS 🔥
                </div>
                
                <div class="plano-item">
                    <span class="plano-nome">📦 PLANO DEVCYBER</span>
                    <span class="plano-preco">$100</span>
                    <span class="plano-creditos">900 CRÉDITOS</span>
                </div>
                
                <div class="plano-item">
                    <span class="plano-nome">📦 PLANO DEVDIMONT</span>
                    <span class="plano-preco">$140</span>
                    <span class="plano-creditos">1.300 CRÉDITOS</span>
                </div>
                
                <div class="plano-item">
                    <span class="plano-nome">📦 PLANO CYBERSECOFC</span>
                    <span class="plano-preco">$200</span>
                    <span class="plano-creditos">3.000 CRÉDITOS</span>
                </div>
            </div>
            
            <div style="text-align: center; margin-top: 30px; color: #00ff00; font-size: 12px;">
                LIVE: 1.50 créditos | DIE: 0.05 créditos
            </div>
        </div>
    </div>
</body>
</html>
<?php
exit;
}

// ============================================
// CARREGAR DADOS DO USUÁRIO LOGADO
// ============================================
$current_user = $db->getUser($_SESSION['username']);
$user_tools = json_decode($current_user['tools'], true) ?: [];
$user_type = $current_user['type'];
$user_credits = floatval($current_user['credits']);
$user_expires = $current_user['expires_at'] ? strtotime($current_user['expires_at']) : 0;

// ============================================
// PAINEL ADMINISTRATIVO
// ============================================
if ($_SESSION['role'] === 'admin' && isset($_GET['admin'])) {
    $all_users = $db->getAllUsers();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Admin - CYBERSEC 3.0</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <?php echo $security_script; ?>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            background: #000;
            background-image: 
                radial-gradient(circle at 10% 20%, rgba(0, 255, 255, 0.05) 0%, transparent 30%),
                radial-gradient(circle at 90% 80%, rgba(255, 0, 255, 0.05) 0%, transparent 30%);
            color: #00ff00;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 30px;
            min-height: 100vh;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .header {
            text-align: center;
            margin-bottom: 40px;
            padding: 30px;
            background: rgba(0, 0, 0, 0.8);
            border: 2px solid #00ff00;
            border-radius: 20px;
            backdrop-filter: blur(10px);
            box-shadow: 0 0 50px rgba(0, 255, 0, 0.3);
        }
        
        .header h1 {
            font-size: 42px;
            background: linear-gradient(45deg, #00ff00, #00ffff, #ff00ff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 10px;
            text-transform: uppercase;
            font-family: 'Courier New', monospace;
        }
        
        .nav-buttons {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
            flex-wrap: wrap;
            justify-content: center;
        }
        
        .btn {
            padding: 15px 40px;
            border: 2px solid;
            background: rgba(0, 0, 0, 0.8);
            color: #00ff00;
            border-color: #00ff00;
            border-radius: 10px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            text-decoration: none;
            transition: all 0.3s;
            display: inline-block;
        }
        
        .btn:hover {
            background: #00ff00;
            color: #000;
            box-shadow: 0 0 30px #00ff00;
            transform: translateY(-2px);
        }
        
        .btn-danger { border-color: #ff0000; color: #ff0000; }
        .btn-danger:hover { background: #ff0000; color: #000; box-shadow: 0 0 30px #ff0000; }
        .btn-warning { border-color: #ffff00; color: #ffff00; }
        .btn-warning:hover { background: #ffff00; color: #000; box-shadow: 0 0 30px #ffff00; }
        
        .admin-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(450px, 1fr));
            gap: 30px;
        }
        
        .admin-section {
            background: rgba(0, 0, 0, 0.8);
            border: 2px solid #00ff00;
            border-radius: 20px;
            padding: 30px;
            backdrop-filter: blur(10px);
        }
        
        .admin-section h2 {
            color: #00ffff;
            font-size: 24px;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #00ff00;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            color: #00ffff;
            margin-bottom: 8px;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 14px;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            background: rgba(0, 0, 0, 0.5);
            border: 2px solid #00ff00;
            border-radius: 10px;
            color: #00ff00;
            font-size: 14px;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #00ffff;
            box-shadow: 0 0 20px rgba(0, 255, 255, 0.3);
        }
        
        .tools-checkbox {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 10px;
            max-height: 200px;
            overflow-y: auto;
            padding: 15px;
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid #00ff00;
            border-radius: 10px;
        }
        
        .tool-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #00ff00;
        }
        
        .tool-item input[type="checkbox"] {
            width: 18px;
            height: 18px;
            accent-color: #00ff00;
        }
        
        .users-list {
            margin-top: 30px;
        }
        
        .user-card {
            background: rgba(0, 0, 0, 0.5);
            border: 1px solid #00ff00;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.3s;
        }
        
        .user-card:hover {
            border-color: #00ffff;
            box-shadow: 0 0 20px rgba(0, 255, 255, 0.3);
            transform: translateX(5px);
        }
        
        .user-info h3 {
            color: #00ff00;
            margin-bottom: 10px;
            font-size: 20px;
        }
        
        .user-info p {
            color: #00ffff;
            font-size: 14px;
            margin: 3px 0;
        }
        
        .user-type {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            margin-left: 10px;
        }
        
        .type-permanent { background: #00ff00; color: #000; }
        .type-temporary { background: #ffff00; color: #000; }
        .type-credits { background: #ff00ff; color: #fff; }
        
        .message {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: bold;
        }
        
        .success {
            background: rgba(0, 255, 0, 0.2);
            border: 2px solid #00ff00;
            color: #00ff00;
        }
        
        .error {
            background: rgba(255, 0, 0, 0.2);
            border: 2px solid #ff0000;
            color: #ff0000;
        }
        
        @media (max-width: 768px) {
            body { padding: 15px; }
            .admin-grid { grid-template-columns: 1fr; }
            .user-card { flex-direction: column; gap: 15px; text-align: center; }
            .tools-checkbox { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>⚙️ PAINEL ADMINISTRATIVO</h1>
            <p>Bem-vindo, <?php echo htmlspecialchars($_SESSION['username']); ?>!</p>
        </div>
        
        <div class="nav-buttons">
            <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="btn">← VOLTAR</a>
            <a href="?logout" class="btn btn-danger">🚪 SAIR</a>
        </div>
        
        <?php if (isset($success_message)): ?>
            <div class="message success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="message error"><?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <div class="admin-grid">
            <!-- Adicionar Usuário Permanente -->
            <div class="admin-section">
                <h2><i class="fas fa-user-plus"></i> PERMANENTE</h2>
                <form method="POST">
                    <input type="hidden" name="admin_action" value="add_permanent">
                    
                    <div class="form-group">
                        <label>USUÁRIO:</label>
                        <input type="text" name="username" required placeholder="Digite o nome de usuário">
                    </div>
                    
                    <div class="form-group">
                        <label>SENHA:</label>
                        <input type="password" name="password" required placeholder="Digite a senha">
                    </div>
                    
                    <div class="form-group">
                        <label>FERRAMENTAS:</label>
                        <div class="tools-checkbox">
                            <?php foreach ($all_tools['checkers'] as $tool): ?>
                            <div class="tool-item">
                                <input type="checkbox" name="tools[]" value="<?php echo $tool; ?>" id="perm_<?php echo $tool; ?>">
                                <label for="perm_<?php echo $tool; ?>"><?php echo $checker_names[$tool]; ?></label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn" style="width: 100%;">CRIAR USUÁRIO PERMANENTE</button>
                </form>
            </div>
            
            <!-- Adicionar Usuário Temporário -->
            <div class="admin-section">
                <h2><i class="fas fa-hourglass-half"></i> TEMPORÁRIO</h2>
                <form method="POST">
                    <input type="hidden" name="admin_action" value="add_temporary">
                    
                    <div class="form-group">
                        <label>USUÁRIO:</label>
                        <input type="text" name="username" required placeholder="Digite o nome de usuário">
                    </div>
                    
                    <div class="form-group">
                        <label>SENHA:</label>
                        <input type="password" name="password" required placeholder="Digite a senha">
                    </div>
                    
                    <div class="form-group">
                        <label>HORAS:</label>
                        <input type="number" name="hours" required min="1" max="720" placeholder="Quantidade de horas">
                    </div>
                    
                    <div class="form-group">
                        <label>FERRAMENTAS:</label>
                        <div class="tools-checkbox">
                            <?php foreach ($all_tools['checkers'] as $tool): ?>
                            <div class="tool-item">
                                <input type="checkbox" name="tools[]" value="<?php echo $tool; ?>" id="temp_<?php echo $tool; ?>">
                                <label for="temp_<?php echo $tool; ?>"><?php echo $checker_names[$tool]; ?></label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn" style="width: 100%;">CRIAR ACESSO TEMPORÁRIO</button>
                </form>
            </div>
            
            <!-- Adicionar Usuário por Créditos -->
            <div class="admin-section">
                <h2><i class="fas fa-coins"></i> CRÉDITOS</h2>
                <form method="POST">
                    <input type="hidden" name="admin_action" value="add_credits">
                    
                    <div class="form-group">
                        <label>USUÁRIO:</label>
                        <input type="text" name="username" required placeholder="Digite o nome de usuário">
                    </div>
                    
                    <div class="form-group">
                        <label>SENHA:</label>
                        <input type="password" name="password" required placeholder="Digite a senha">
                    </div>
                    
                    <div class="form-group">
                        <label>CRÉDITOS:</label>
                        <input type="number" name="credits" required min="0.05" step="0.01" placeholder="Quantidade de créditos">
                    </div>
                    
                    <div class="form-group">
                        <label>FERRAMENTAS:</label>
                        <div class="tools-checkbox">
                            <?php foreach ($all_tools['checkers'] as $tool): ?>
                            <div class="tool-item">
                                <input type="checkbox" name="tools[]" value="<?php echo $tool; ?>" id="cred_<?php echo $tool; ?>">
                                <label for="cred_<?php echo $tool; ?>"><?php echo $checker_names[$tool]; ?></label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn" style="width: 100%;">CRIAR USUÁRIO POR CRÉDITOS</button>
                </form>
            </div>
            
            <!-- Recarregar Créditos -->
            <div class="admin-section">
                <h2><i class="fas fa-plus-circle"></i> RECARREGAR CRÉDITOS</h2>
                <form method="POST">
                    <input type="hidden" name="admin_action" value="recharge">
                    
                    <div class="form-group">
                        <label>USUÁRIO:</label>
                        <select name="username" required>
                            <option value="">Selecione um usuário</option>
                            <?php foreach ($all_users as $u): ?>
                                <?php if ($u['type'] === 'credits'): ?>
                                <option value="<?php echo $u['username']; ?>">
                                    <?php echo $u['username']; ?> (<?php echo $u['credits']; ?> créditos)
                                </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>CRÉDITOS PARA ADICIONAR:</label>
                        <input type="number" name="credits" required min="0.05" step="0.01" placeholder="Quantidade de créditos">
                    </div>
                    
                    <button type="submit" class="btn" style="width: 100%;">RECARREGAR CRÉDITOS</button>
                </form>
            </div>
        </div>
        
        <!-- Lista de Usuários -->
        <div class="admin-section" style="margin-top: 30px;">
            <h2><i class="fas fa-users"></i> USUÁRIOS CADASTRADOS</h2>
            
            <div class="users-list">
                <?php foreach ($all_users as $user): 
                    $user_tools_list = json_decode($user['tools'], true) ?: [];
                    $tools_names = array_map(function($t) use ($checker_names) {
                        return $checker_names[$t] ?? $t;
                    }, $user_tools_list);
                ?>
                <div class="user-card">
                    <div class="user-info">
                        <h3>
                            <?php echo htmlspecialchars($user['username']); ?>
                            <span class="user-type type-<?php echo $user['type']; ?>">
                                <?php echo strtoupper($user['type']); ?>
                            </span>
                        </h3>
                        <p><strong>Role:</strong> <?php echo $user['role']; ?></p>
                        <?php if ($user['type'] === 'credits'): ?>
                        <p><strong>Créditos:</strong> <?php echo number_format($user['credits'], 2); ?></p>
                        <?php endif; ?>
                        <?php if ($user['type'] === 'temporary' && $user['expires_at']): ?>
                        <p><strong>Expira:</strong> <?php echo date('d/m/Y H:i:s', strtotime($user['expires_at'])); ?></p>
                        <?php endif; ?>
                        <p><strong>Ferramentas:</strong> <?php echo implode(', ', array_slice($tools_names, 0, 5)); ?><?php echo count($tools_names) > 5 ? '...' : ''; ?></p>
                    </div>
                    
                    <?php if ($user['username'] !== 'save'): ?>
                    <form method="POST" onsubmit="return confirm('Remover este usuário?')">
                        <input type="hidden" name="admin_action" value="remove">
                        <input type="hidden" name="username" value="<?php echo $user['username']; ?>">
                        <button type="submit" class="btn btn-danger">REMOVER</button>
                    </form>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</body>
</html>
<?php
exit;
}

// ============================================
// HISTÓRICO DE LIVES
// ============================================
if (isset($_GET['lives'])) {
    $lives = $db->getUserLives($_SESSION['username']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Histórico de Lives - CYBERSEC 3.0</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <?php echo $security_script; ?>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            background: #000;
            background-image: 
                radial-gradient(circle at 10% 20%, rgba(0, 255, 255, 0.05) 0%, transparent 30%),
                radial-gradient(circle at 90% 80%, rgba(255, 0, 255, 0.05) 0%, transparent 30%);
            color: #00ff00;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 30px;
            min-height: 100vh;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .header {
            text-align: center;
            margin-bottom: 40px;
            padding: 30px;
            background: rgba(0, 0, 0, 0.8);
            border: 2px solid #00ff00;
            border-radius: 20px;
        }
        
        .header h1 {
            font-size: 42px;
            color: #00ffff;
            margin-bottom: 10px;
            text-transform: uppercase;
            font-family: 'Courier New', monospace;
        }
        
        .nav-buttons {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 15px 40px;
            border: 2px solid;
            background: rgba(0, 0, 0, 0.8);
            color: #00ff00;
            border-color: #00ff00;
            border-radius: 10px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .btn:hover {
            background: #00ff00;
            color: #000;
            box-shadow: 0 0 30px #00ff00;
        }
        
        .btn-export {
            border-color: #ffff00;
            color: #ffff00;
        }
        
        .btn-export:hover {
            background: #ffff00;
            color: #000;
            box-shadow: 0 0 30px #ffff00;
        }
        
        .table-container {
            background: rgba(0, 0, 0, 0.8);
            border: 2px solid #00ff00;
            border-radius: 20px;
            padding: 30px;
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th {
            background: rgba(0, 255, 0, 0.2);
            color: #00ffff;
            padding: 15px;
            font-size: 16px;
            text-transform: uppercase;
        }
        
        td {
            padding: 15px;
            border-bottom: 1px solid rgba(0, 255, 0, 0.2);
            color: #00ff00;
        }
        
        tr:hover {
            background: rgba(0, 255, 0, 0.1);
        }
        
        .empty-message {
            text-align: center;
            color: #ffff00;
            font-size: 18px;
            padding: 50px;
        }
        
        @media (max-width: 768px) {
            body { padding: 15px; }
            th, td { font-size: 12px; padding: 8px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📋 HISTÓRICO DE LIVES</h1>
            <p>Usuário: <?php echo htmlspecialchars($_SESSION['username']); ?></p>
        </div>
        
        <div class="nav-buttons">
            <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="btn">← VOLTAR</a>
            <a href="?lives&export=1" class="btn btn-export">📥 EXPORTAR</a>
            <a href="?logout" class="btn">🚪 SAIR</a>
        </div>
        
        <div class="table-container">
            <?php if (empty($lives)): ?>
                <div class="empty-message">
                    <i class="fas fa-info-circle"></i> Nenhuma live encontrada ainda.
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>DATA/HORA</th>
                            <th>GATE</th>
                            <th>CARTÃO</th>
                            <th>RESPOSTA</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($lives as $live): ?>
                        <tr>
                            <td><?php echo date('d/m/Y H:i:s', strtotime($live['created_at'])); ?></td>
                            <td><strong><?php echo strtoupper($live['gate']); ?></strong></td>
                            <td><?php echo substr($live['card'], 0, 6) . '******' . substr($live['card'], -4); ?></td>
                            <td style="max-width: 300px; overflow: hidden; text-overflow: ellipsis;">
                                <?php echo htmlspecialchars(substr($live['response'], 0, 100)) . '...'; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if (isset($_GET['export']) && $_GET['export'] == 1): ?>
        <?php
        header('Content-Type: text/plain');
        header('Content-Disposition: attachment; filename="lives_'.$_SESSION['username'].'_'.date('Ymd_His').'.txt"');
        foreach ($lives as $live) {
            echo "=== LIVE em " . date('d/m/Y H:i:s', strtotime($live['created_at'])) . " ===\n";
            echo "Gate: " . strtoupper($live['gate']) . "\n";
            echo "Cartão: " . $live['card'] . "\n";
            echo "Resposta:\n" . $live['response'] . "\n\n";
        }
        exit;
        ?>
    <?php endif; ?>
</body>
</html>
<?php
exit;
}

// ============================================
// FERRAMENTA ESPECÍFICA
// ============================================
if (isset($_GET['tool'])) {
    $selected_tool = $_GET['tool'];
    
    if (!in_array($selected_tool, $user_tools)) {
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
    
    $tool_name = $checker_names[$selected_tool] ?? $selected_tool;
    $tool_icon = $tool_details[$selected_tool]['icon'] ?? '🔧';
    $tool_desc = $tool_details[$selected_tool]['desc'] ?? '';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $tool_name; ?> - CYBERSEC 3.0</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <?php echo $security_script; ?>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            background: #000;
            background-image: 
                radial-gradient(circle at 10% 20%, rgba(0, 255, 255, 0.05) 0%, transparent 30%),
                radial-gradient(circle at 90% 80%, rgba(255, 0, 255, 0.05) 0%, transparent 30%);
            color: #00ff00;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 30px;
            min-height: 100vh;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .header {
            text-align: center;
            margin-bottom: 40px;
            padding: 40px;
            background: rgba(0, 0, 0, 0.8);
            border: 2px solid #00ff00;
            border-radius: 30px;
            position: relative;
        }
        
        .header h1 {
            font-size: 48px;
            color: #00ffff;
            margin-bottom: 10px;
            text-transform: uppercase;
            font-family: 'Courier New', monospace;
        }
        
        .user-info {
            position: absolute;
            top: 20px;
            right: 20px;
            color: #00ffff;
            font-size: 14px;
            background: rgba(0, 0, 0, 0.5);
            padding: 10px 20px;
            border-radius: 10px;
            border: 1px solid #00ff00;
        }
        
        .status-bar {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin: 30px 0;
            flex-wrap: wrap;
        }
        
        .status-item {
            padding: 15px 30px;
            border: 2px solid;
            border-radius: 10px;
            font-size: 16px;
            font-weight: bold;
            min-width: 250px;
            text-align: center;
        }
        
        .status-time {
            border-color: #ffff00;
            color: #ffff00;
        }
        
        .status-credits {
            border-color: #ff00ff;
            color: #ff00ff;
        }
        
        .nav-buttons {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
            flex-wrap: wrap;
            justify-content: center;
        }
        
        .btn {
            padding: 12px 30px;
            border: 2px solid;
            background: rgba(0, 0, 0, 0.8);
            color: #00ff00;
            border-color: #00ff00;
            border-radius: 10px;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
            text-decoration: none;
            transition: all 0.3s;
            display: inline-block;
        }
        
        .btn:hover {
            background: #00ff00;
            color: #000;
            box-shadow: 0 0 20px #00ff00;
        }
        
        .btn-start {
            background: linear-gradient(45deg, #00ff00, #00ffff);
            color: #000;
            border: none;
            padding: 15px 50px;
            font-size: 18px;
        }
        
        .btn-stop {
            border-color: #ff0000;
            color: #ff0000;
        }
        
        .btn-stop:hover {
            background: #ff0000;
            color: #000;
            box-shadow: 0 0 20px #ff0000;
        }
        
        .btn-clear {
            border-color: #ffff00;
            color: #ffff00;
        }
        
        .btn-clear:hover {
            background: #ffff00;
            color: #000;
            box-shadow: 0 0 20px #ffff00;
        }
        
        .input-section {
            margin: 30px 0;
        }
        
        .input-section textarea {
            width: 100%;
            height: 200px;
            background: rgba(0, 0, 0, 0.8);
            border: 2px solid #00ff00;
            border-radius: 15px;
            color: #00ff00;
            padding: 20px;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            resize: vertical;
        }
        
        .input-section textarea:focus {
            outline: none;
            border-color: #00ffff;
            box-shadow: 0 0 30px rgba(0, 255, 255, 0.3);
        }
        
        .stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin: 30px 0;
        }
        
        .stat-box {
            background: rgba(0, 0, 0, 0.8);
            border: 2px solid #00ff00;
            border-radius: 15px;
            padding: 20px;
            text-align: center;
        }
        
        .stat-label {
            color: #00ffff;
            font-size: 14px;
            margin-bottom: 10px;
            text-transform: uppercase;
        }
        
        .stat-value {
            color: #00ff00;
            font-size: 32px;
            font-weight: bold;
        }
        
        .results {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-top: 40px;
        }
        
        .result-box {
            border: 2px solid;
            border-radius: 15px;
            padding: 25px;
            min-height: 400px;
            max-height: 500px;
            overflow-y: auto;
            background: rgba(0, 0, 0, 0.8);
        }
        
        .result-box h3 {
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid;
            font-size: 20px;
            text-transform: uppercase;
            position: sticky;
            top: 0;
            background: rgba(0, 0, 0, 0.9);
        }
        
        .live-box {
            border-color: #00ff00;
        }
        
        .live-box h3 {
            color: #00ff00;
            border-color: #00ff00;
        }
        
        .die-box {
            border-color: #ff0000;
        }
        
        .die-box h3 {
            color: #ff0000;
            border-color: #ff0000;
        }
        
        .result-item {
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 10px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            animation: fadeIn 0.3s;
        }
        
        .result-item.live {
            background: rgba(0, 255, 0, 0.1);
            border-left: 4px solid #00ff00;
        }
        
        .result-item.die {
            background: rgba(255, 0, 0, 0.1);
            border-left: 4px solid #ff0000;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .loading {
            display: none;
            color: #ffff00;
            margin-left: 20px;
            animation: blink 1s infinite;
        }
        
        @keyframes blink {
            0%, 50% { opacity: 1; }
            51%, 100% { opacity: 0.3; }
        }
        
        .loading.active {
            display: inline-block;
        }
        
        .credits-counter {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: rgba(0, 0, 0, 0.9);
            border: 2px solid #ff00ff;
            border-radius: 15px;
            padding: 15px 25px;
            color: #ff00ff;
            font-weight: bold;
            z-index: 1000;
            box-shadow: 0 0 30px rgba(255, 0, 255, 0.3);
        }
        
        @media (max-width: 768px) {
            body { padding: 15px; }
            .stats { grid-template-columns: 1fr 1fr; }
            .results { grid-template-columns: 1fr; }
            .credits-counter { position: static; margin-top: 20px; }
        }
    </style>
</head>
<body>
    <?php if ($user_type === 'credits'): ?>
    <div class="credits-counter">
        💳 Créditos: <span id="currentCredits"><?php echo number_format($user_credits, 2); ?></span>
    </div>
    <?php endif; ?>
    
    <div class="container">
        <div class="header">
            <h1><?php echo $tool_icon; ?> <?php echo $tool_name; ?></h1>
            <p><?php echo $tool_desc; ?></p>
            <div class="user-info">
                👤 <?php echo htmlspecialchars($_SESSION['username']); ?>
                <?php if ($_SESSION['role'] === 'admin'): ?> ⭐ ADMIN<?php endif; ?>
            </div>
        </div>
        
        <div class="status-bar">
            <?php if ($user_type === 'temporary' && $user_expires): ?>
            <div class="status-item status-time" id="timeLeft">
                ⏱️ Expira em: <?php 
                    $timeLeft = $user_expires - time();
                    $hours = floor($timeLeft / 3600);
                    $minutes = floor(($timeLeft % 3600) / 60);
                    echo $hours . 'h ' . $minutes . 'min';
                ?>
            </div>
            <?php endif; ?>
            
            <?php if ($user_type === 'credits'): ?>
            <div class="status-item status-credits" id="creditsInfo">
                💳 <?php echo number_format($user_credits, 2); ?> créditos disponíveis
            </div>
            <?php endif; ?>
        </div>
        
        <div class="nav-buttons">
            <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="btn">← VOLTAR</a>
            <?php if ($_SESSION['role'] === 'admin'): ?>
            <a href="?admin=true" class="btn">⚙ ADMIN</a>
            <?php endif; ?>
            <a href="?lives" class="btn">📋 LIVES</a>
            <a href="?logout" class="btn">🚪 SAIR</a>
        </div>
        
        <div class="input-section">
            <textarea id="dataInput" placeholder="Cole seus cartões aqui no formato:
numero|mes|ano|cvv

Exemplo:
4532015112830366|12|2027|123
5425233430109903|01|2028|456
4716989580001234|03|2029|789

Máximo: 200 cartões por vez"></textarea>
        </div>
        
        <div style="display: flex; gap: 15px; justify-content: center; flex-wrap: wrap;">
            <button class="btn btn-start" onclick="startCheck()">
                <i class="fas fa-play"></i> INICIAR
            </button>
            <button class="btn btn-stop" onclick="stopCheck()">
                <i class="fas fa-stop"></i> PARAR
            </button>
            <button class="btn btn-clear" onclick="clearAll()">
                <i class="fas fa-trash"></i> LIMPAR
            </button>
            <span class="loading" id="loading">⏳ PROCESSANDO...</span>
        </div>
        
        <div class="stats">
            <div class="stat-box">
                <div class="stat-label">TOTAL</div>
                <div class="stat-value" id="totalCount">0</div>
            </div>
            <div class="stat-box">
                <div class="stat-label">✅ APROVADOS</div>
                <div class="stat-value" id="liveCount" style="color: #00ff00;">0</div>
            </div>
            <div class="stat-box">
                <div class="stat-label">❌ REPROVADOS</div>
                <div class="stat-value" id="dieCount" style="color: #ff0000;">0</div>
            </div>
            <div class="stat-box">
                <div class="stat-label">⚡ PROCESSADOS</div>
                <div class="stat-value" id="processedCount">0</div>
            </div>
        </div>
        
        <div class="results">
            <div class="result-box live-box">
                <h3><i class="fas fa-check-circle"></i> APROVADOS</h3>
                <div id="liveResults"></div>
            </div>
            <div class="result-box die-box">
                <h3><i class="fas fa-times-circle"></i> REPROVADOS</h3>
                <div id="dieResults"></div>
            </div>
        </div>
    </div>
    
    <script>
        let isChecking = false;
        let currentIndex = 0;
        let items = [];
        let currentCredits = <?php echo $user_credits; ?>;
        const toolName = '<?php echo $selected_tool; ?>';
        const userType = '<?php echo $user_type; ?>';
        const MAX_ITEMS = 200;
        
        <?php if ($user_type === 'temporary' && $user_expires): ?>
        // Atualizar tempo restante
        setInterval(function() {
            const expiresAt = <?php echo $user_expires; ?>;
            const now = Math.floor(Date.now() / 1000);
            const timeLeft = expiresAt - now;
            
            if (timeLeft <= 0) {
                alert('⏱️ Seu tempo de acesso expirou!');
                window.location.href = '?logout';
            } else {
                const hours = Math.floor(timeLeft / 3600);
                const minutes = Math.floor((timeLeft % 3600) / 60);
                document.getElementById('timeLeft').textContent = 
                    `⏱️ Expira em: ${hours}h ${minutes}min`;
            }
        }, 60000);
        <?php endif; ?>
        
        function checkIfLive(response) {
            const livePatterns = [
                'aprovada', 'approved', 'success', 'live', 'válido', 'válida',
                '✅', '✓', '✔', '🟢', 'authorized', 'valid', 'ok', 'encontrado'
            ];
            
            response = response.toLowerCase();
            for (const pattern of livePatterns) {
                if (response.includes(pattern.toLowerCase())) return true;
            }
            return false;
        }
        
        function updateCreditsDisplay() {
            if (userType === 'credits') {
                document.getElementById('currentCredits').textContent = currentCredits.toFixed(2);
                if (currentCredits < 0.05) {
                    document.querySelector('.btn-start').disabled = true;
                    document.querySelector('.btn-start').style.opacity = '0.5';
                    document.querySelector('.btn-start').style.cursor = 'not-allowed';
                }
            }
        }
        
        function startCheck() {
            const input = document.getElementById('dataInput').value.trim();
            if (!input) {
                alert('Por favor, insira os dados!');
                return;
            }
            
            if (userType === 'credits' && currentCredits < 0.05) {
                alert('💳 Créditos insuficientes!');
                return;
            }
            
            items = input.split('\n').filter(line => line.trim());
            
            if (items.length > MAX_ITEMS) {
                alert(`⚠️ Máximo de ${MAX_ITEMS} itens! Apenas os primeiros serão processados.`);
                items = items.slice(0, MAX_ITEMS);
            }
            
            if (items.length === 0) return;
            
            currentIndex = 0;
            isChecking = true;
            document.getElementById('loading').classList.add('active');
            document.getElementById('totalCount').textContent = items.length;
            
            processNextItem();
        }
        
        function stopCheck() {
            isChecking = false;
            document.getElementById('loading').classList.remove('active');
        }
        
        function clearAll() {
            document.getElementById('dataInput').value = '';
            document.getElementById('liveResults').innerHTML = '';
            document.getElementById('dieResults').innerHTML = '';
            document.getElementById('totalCount').textContent = '0';
            document.getElementById('liveCount').textContent = '0';
            document.getElementById('dieCount').textContent = '0';
            document.getElementById('processedCount').textContent = '0';
            isChecking = false;
            currentIndex = 0;
            items = [];
            document.getElementById('loading').classList.remove('active');
        }
        
        async function processNextItem() {
            if (!isChecking || currentIndex >= items.length) {
                stopCheck();
                return;
            }
            
            const item = items[currentIndex];
            
            try {
                const response = await fetch(`?action=check&tool=${toolName}&lista=${encodeURIComponent(item)}`);
                const text = await response.text();
                
                const isLive = checkIfLive(text);
                
                if (userType === 'credits') {
                    const cost = isLive ? 1.50 : 0.05;
                    currentCredits -= cost;
                    if (currentCredits < 0) currentCredits = 0;
                    updateCreditsDisplay();
                }
                
                addResult(item, text, isLive);
                
            } catch (error) {
                addResult(item, 'Erro: ' + error.message, false);
            }
            
            currentIndex++;
            document.getElementById('processedCount').textContent = currentIndex;
            
            if (isChecking && currentIndex < items.length) {
                setTimeout(processNextItem, 4000); // Delay de 4 segundos
            } else {
                stopCheck();
            }
        }
        
        function addResult(item, response, isLive) {
            const container = isLive ? document.getElementById('liveResults') : document.getElementById('dieResults');
            const resultDiv = document.createElement('div');
            resultDiv.className = `result-item ${isLive ? 'live' : 'die'}`;
            
            let formattedResponse = response.replace(/\n/g, '<br>');
            resultDiv.innerHTML = `<strong>${item}</strong><br><br>${formattedResponse}`;
            
            container.insertBefore(resultDiv, container.firstChild);
            
            if (isLive) {
                const liveCount = parseInt(document.getElementById('liveCount').textContent);
                document.getElementById('liveCount').textContent = liveCount + 1;
            } else {
                const dieCount = parseInt(document.getElementById('dieCount').textContent);
                document.getElementById('dieCount').textContent = dieCount + 1;
            }
        }
        
        updateCreditsDisplay();
    </script>
</body>
</html>
<?php
exit;
}

// ============================================
// MENU PRINCIPAL
// ============================================
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CYBERSEC 3.0 - MENU PRINCIPAL</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <?php echo $security_script; ?>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            background: #000;
            background-image: 
                radial-gradient(circle at 10% 20%, rgba(0, 255, 255, 0.05) 0%, transparent 30%),
                radial-gradient(circle at 90% 80%, rgba(255, 0, 255, 0.05) 0%, transparent 30%),
                linear-gradient(45deg, transparent 45%, rgba(0, 255, 0, 0.02) 50%, transparent 55%);
            background-size: 100% 100%, 100% 100%, 50px 50px;
            color: #00ff00;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 30px;
            min-height: 100vh;
        }
        
        .scanline {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 2px;
            background: linear-gradient(90deg, transparent, #00ff00, #00ffff, #ff00ff, transparent);
            animation: scan 3s linear infinite;
            z-index: 1000;
            pointer-events: none;
        }
        
        @keyframes scan {
            0% { transform: translateY(-100%); }
            100% { transform: translateY(100vh); }
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .header {
            text-align: center;
            margin-bottom: 50px;
            padding: 50px;
            background: rgba(0, 0, 0, 0.8);
            border: 2px solid #00ff00;
            border-radius: 30px;
            backdrop-filter: blur(10px);
            position: relative;
            overflow: hidden;
        }
        
        .header::before {
            content: '';
            position: absolute;
            top: -2px;
            left: -2px;
            right: -2px;
            bottom: -2px;
            background: linear-gradient(45deg, #00ff00, #00ffff, #ff00ff, #00ff00);
            z-index: -1;
            animation: borderRotate 4s linear infinite;
            border-radius: 32px;
        }
        
        @keyframes borderRotate {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .header h1 {
            font-size: 64px;
            font-weight: 900;
            background: linear-gradient(45deg, #00ff00, #00ffff, #ff00ff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 20px;
            text-transform: uppercase;
            font-family: 'Courier New', monospace;
            letter-spacing: 5px;
            animation: titleGlow 2s infinite;
        }
        
        @keyframes titleGlow {
            0%, 100% { filter: drop-shadow(0 0 20px #00ff00); }
            50% { filter: drop-shadow(0 0 40px #00ffff); }
        }
        
        .user-info {
            position: absolute;
            top: 20px;
            right: 20px;
            color: #00ffff;
            font-size: 16px;
            background: rgba(0, 0, 0, 0.5);
            padding: 10px 20px;
            border-radius: 10px;
            border: 1px solid #00ff00;
        }
        
        .status-bar {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin: 30px 0;
            flex-wrap: wrap;
        }
        
        .status-item {
            padding: 15px 40px;
            border: 2px solid;
            border-radius: 10px;
            font-size: 18px;
            font-weight: bold;
            min-width: 300px;
            text-align: center;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.8; }
        }
        
        .status-time {
            border-color: #ffff00;
            color: #ffff00;
            box-shadow: 0 0 20px rgba(255, 255, 0, 0.3);
        }
        
        .status-credits {
            border-color: #ff00ff;
            color: #ff00ff;
            box-shadow: 0 0 20px rgba(255, 0, 255, 0.3);
        }
        
        .nav-buttons {
            display: flex;
            gap: 20px;
            margin-bottom: 50px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 15px 40px;
            border: 2px solid;
            background: rgba(0, 0, 0, 0.8);
            color: #00ff00;
            border-color: #00ff00;
            border-radius: 10px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            text-decoration: none;
            transition: all 0.3s;
            display: inline-block;
            min-width: 200px;
            text-align: center;
        }
        
        .btn:hover {
            background: #00ff00;
            color: #000;
            box-shadow: 0 0 30px #00ff00;
            transform: translateY(-3px);
        }
        
        .btn-admin {
            border-color: #00ffff;
            color: #00ffff;
        }
        
        .btn-admin:hover {
            background: #00ffff;
            color: #000;
            box-shadow: 0 0 30px #00ffff;
        }
        
        .btn-lives {
            border-color: #ffff00;
            color: #ffff00;
        }
        
        .btn-lives:hover {
            background: #ffff00;
            color: #000;
            box-shadow: 0 0 30px #ffff00;
        }
        
        .btn-logout {
            border-color: #ff0000;
            color: #ff0000;
        }
        
        .btn-logout:hover {
            background: #ff0000;
            color: #000;
            box-shadow: 0 0 30px #ff0000;
        }
        
        .tools-section h2 {
            text-align: center;
            color: #00ffff;
            font-size: 32px;
            margin-bottom: 40px;
            text-transform: uppercase;
            letter-spacing: 3px;
            font-family: 'Courier New', monospace;
            border-bottom: 2px solid #00ff00;
            padding-bottom: 20px;
        }
        
        .tools-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 30px;
        }
        
        .tool-card {
            background: rgba(0, 0, 0, 0.8);
            border: 2px solid #00ff00;
            border-radius: 20px;
            padding: 30px;
            text-decoration: none;
            color: #00ff00;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
            backdrop-filter: blur(10px);
        }
        
        .tool-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(0, 255, 0, 0.2), transparent);
            transition: 0.5s;
        }
        
        .tool-card:hover::before {
            left: 100%;
        }
        
        .tool-card:hover {
            border-color: #00ffff;
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0, 255, 255, 0.3);
        }
        
        .tool-icon {
            font-size: 48px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .tool-card h3 {
            font-size: 24px;
            margin-bottom: 15px;
            color: #00ffff;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        
        .tool-card p {
            color: rgba(0, 255, 0, 0.8);
            text-align: center;
            line-height: 1.6;
        }
        
        .access-type {
            position: fixed;
            bottom: 30px;
            left: 30px;
            padding: 15px 30px;
            border: 2px solid;
            border-radius: 10px;
            font-weight: bold;
            background: rgba(0, 0, 0, 0.9);
            z-index: 1000;
            backdrop-filter: blur(10px);
            animation: slideIn 0.5s;
        }
        
        @keyframes slideIn {
            from { transform: translateX(-100%); }
            to { transform: translateX(0); }
        }
        
        .access-permanent {
            border-color: #00ff00;
            color: #00ff00;
            box-shadow: 0 0 20px rgba(0, 255, 0, 0.3);
        }
        
        .access-temporary {
            border-color: #ffff00;
            color: #ffff00;
            box-shadow: 0 0 20px rgba(255, 255, 0, 0.3);
        }
        
        .access-credits {
            border-color: #ff00ff;
            color: #ff00ff;
            box-shadow: 0 0 20px rgba(255, 0, 255, 0.3);
        }
        
        @media (max-width: 768px) {
            body { padding: 15px; }
            .header h1 { font-size: 36px; }
            .status-item { min-width: 100%; }
            .tools-grid { grid-template-columns: 1fr; }
            .access-type { position: static; margin-top: 30px; }
        }
    </style>
</head>
<body>
    <div class="scanline"></div>
    
    <div class="access-type access-<?php echo $user_type; ?>">
        <?php if ($user_type === 'permanent'): ?>
            <i class="fas fa-infinity"></i> ACESSO PERMANENTE
        <?php elseif ($user_type === 'temporary'): ?>
            <i class="fas fa-hourglass-half"></i> ACESSO TEMPORÁRIO
        <?php elseif ($user_type === 'credits'): ?>
            <i class="fas fa-coins"></i> CRÉDITOS: <?php echo number_format($user_credits, 2); ?>
        <?php endif; ?>
    </div>
    
    <div class="container">
        <div class="header">
            <h1>CYBERSEC 3.0</h1>
            <p>SISTEMA PREMIUM DE CHECKERS</p>
            <div class="user-info">
                <i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION['username']); ?>
                <?php if ($_SESSION['role'] === 'admin'): ?> ⭐ ADMIN<?php endif; ?>
            </div>
        </div>
        
        <div class="status-bar">
            <?php if ($user_type === 'temporary' && $user_expires): ?>
            <div class="status-item status-time" id="timeLeft">
                <i class="fas fa-clock"></i> Expira em: 
                <?php 
                    $timeLeft = $user_expires - time();
                    $hours = floor($timeLeft / 3600);
                    $minutes = floor(($timeLeft % 3600) / 60);
                    echo $hours . 'h ' . $minutes . 'min';
                ?>
            </div>
            <?php endif; ?>
            
            <?php if ($user_type === 'credits'): ?>
            <div class="status-item status-credits">
                <i class="fas fa-coins"></i> <?php echo number_format($user_credits, 2); ?> créditos disponíveis
            </div>
            <?php endif; ?>
        </div>
        
        <div class="nav-buttons">
            <?php if ($_SESSION['role'] === 'admin'): ?>
            <a href="?admin=true" class="btn btn-admin">
                <i class="fas fa-cog"></i> PAINEL ADMIN
            </a>
            <?php endif; ?>
            
            <a href="?lives" class="btn btn-lives">
                <i class="fas fa-history"></i> MINHAS LIVES
            </a>
            
            <a href="?logout" class="btn btn-logout">
                <i class="fas fa-sign-out-alt"></i> SAIR
            </a>
        </div>
        
        <div class="tools-section">
            <h2><i class="fas fa-tools"></i> FERRAMENTAS DISPONÍVEIS</h2>
            
            <div class="tools-grid">
                <?php foreach ($user_tools as $tool): 
                    if (isset($tool_details[$tool])):
                        $details = $tool_details[$tool];
                ?>
                <a href="?tool=<?php echo $tool; ?>" class="tool-card">
                    <div class="tool-icon"><?php echo $details['icon']; ?></div>
                    <h3><?php echo $checker_names[$tool]; ?></h3>
                    <p><?php echo $details['desc']; ?></p>
                </a>
                <?php endif; endforeach; ?>
            </div>
        </div>
    </div>
    
    <?php if ($user_type === 'temporary' && $user_expires): ?>
    <script>
        setInterval(function() {
            const expiresAt = <?php echo $user_expires; ?>;
            const now = Math.floor(Date.now() / 1000);
            const timeLeft = expiresAt - now;
            
            if (timeLeft <= 0) {
                alert('⏱️ Seu tempo de acesso expirou!');
                window.location.href = '?logout';
            } else {
                const hours = Math.floor(timeLeft / 3600);
                const minutes = Math.floor((timeLeft % 3600) / 60);
                document.getElementById('timeLeft').innerHTML = 
                    '<i class="fas fa-clock"></i> Expira em: ' + hours + 'h ' + minutes + 'min';
            }
        }, 60000);
    </script>
    <?php endif; ?>
</body>
</html>
<?php
// Fim do código
?>
