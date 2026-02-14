<?php
// ============================================
// CYBERSEC 4.0 - VERSÃO RENOVADA
// SISTEMA COMPLETO DE CHECKERS
// ============================================

// Configurações iniciais
ini_set('display_errors', 1);
ini_set('log_errors', 1);
error_reporting(E_ALL);
date_default_timezone_set('America/Sao_Paulo');
session_start();

// ============================================
// CARREGAR BANCO DE DADOS
// ============================================
require_once __DIR__ . '/db.php';

// ============================================
// CONFIGURAÇÕES DO SISTEMA
// ============================================

// Carregar configurações do banco
$settings = loadSettings();

// Token do Telegram (usando valores do banco ou padrão)
define('TELEGRAM_TOKEN', $settings['telegram_token'] ?? '8586131107:AAF6fDbrjm7CoVI2g1Zkx2agmXJgmbdnCVQ');
define('TELEGRAM_CHAT', $settings['telegram_chat'] ?? '-1003581267007');
define('SITE_URL', $settings['site_url'] ?? 'https://cyebrsecofcapis.up.railway.app');

// Custos dos créditos
define('LIVE_COST', $settings['live_cost'] ?? 2.00);
define('DIE_COST', $settings['die_cost'] ?? 0.05);

// Diretórios
define('BASE_PATH', __DIR__);
define('API_PATH', BASE_PATH . '/api/');
define('DATA_PATH', BASE_PATH . '/data/');

// Criar diretórios se não existirem
if (!file_exists(DATA_PATH)) mkdir(DATA_PATH, 0755, true);
if (!file_exists(API_PATH)) mkdir(API_PATH, 0755, true);

// ============================================
// CONFIGURAÇÃO DAS GATES (CHECKERS)
// ============================================
$all_gates = [
    'n7' => ['name' => 'N7', 'icon' => '⚡', 'file' => 'n7.php', 'color' => '#00ff00'],
    'auth' => ['name' => 'AUTH', 'icon' => '🔒', 'file' => 'auth.php', 'color' => '#ff00ff'],
    'zerodolar' => ['name' => 'ZERO DOLAR', 'icon' => '💵', 'file' => 'zerodolar.php', 'color' => '#ffff00'],
    'stripe' => ['name' => 'STRIPE', 'icon' => '💳', 'file' => 'stripe.php', 'color' => '#00ffff'],
    'braintre' => ['name' => 'BRAINTRE', 'icon' => '🔄', 'file' => 'braintre.php', 'color' => '#ff6600'],
    'debitando' => ['name' => 'DEBITANDO', 'icon' => '💸', 'file' => 'debitando.php', 'color' => '#ff0000'],
    'cc' => ['name' => 'CC', 'icon' => '💎', 'file' => 'cc.php', 'color' => '#00ff88'],
    'amex' => ['name' => 'AMEX', 'icon' => '🏦', 'file' => 'amex.php', 'color' => '#0066ff'],
    'visamaster' => ['name' => 'VISA/MASTER', 'icon' => '💳', 'file' => 'visamaster.php', 'color' => '#ff3366'],
    'elo' => ['name' => 'ELO', 'icon' => '💎', 'file' => 'elo.php', 'color' => '#9933ff'],
    'ggsgringa' => ['name' => 'GGS GRINGA', 'icon' => '🌎', 'file' => 'ggsgringa.php', 'color' => '#ff9900']
];

// ============================================
// FUNÇÕES AUXILIARES (REDIRECIONADAS PARA DB)
// ============================================

// Usuários
function loadUsers() { return \loadUsers(); }
function saveUsers($users) { return \saveUsers($users); }
function getUser($username) { return \getUser($username); }
function addUser($username, $password, $role, $type, $credits = 0, $expires_at = null) { 
    return \addUser($username, $password, $role, $type, $credits, $expires_at); 
}
function updateUser($username, $data) { return \updateUser($username, $data); }
function deleteUser($username) { return \deleteUser($username); }
function deductCredits($username, $amount) { return \deductCredits($username, $amount); }
function updateLastLogin($username) { return \updateLastLogin($username); }

// Gates
function loadGatesConfig() { return \loadGatesConfig(); }
function saveGatesConfig($config) { return \saveGatesConfig($config); }
function isGateActive($gate) { return \isGateActive($gate); }

// Lives
function loadLives() { return \loadLives(); }
function saveLives($lives) { return \saveLives($lives); }
function addLive($username, $gate, $card, $bin, $response) { 
    return \addLive($username, $gate, $card, $bin, $response); 
}
function getUserLives($username) { return \getUserLives($username); }

// Telegram
function sendTelegramMessage($message) {
    $url = "https://api.telegram.org/bot" . TELEGRAM_TOKEN . "/sendMessage";
    $data = [
        'chat_id' => TELEGRAM_CHAT,
        'text' => $message,
        'parse_mode' => 'Markdown'
    ];
    
    $options = [
        'http' => [
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($data),
            'timeout' => 5
        ]
    ];
    
    $context = stream_context_create($options);
    @file_get_contents($url, false, $context);
}

// Verificar acesso
function checkAccess() {
    if (!isset($_SESSION['logged_in'])) return false;
    
    $user = getUser($_SESSION['username']);
    if (!$user) {
        session_destroy();
        return false;
    }
    
    if ($user['type'] === 'temporary' && $user['expires_at']) {
        if (time() > strtotime($user['expires_at'])) {
            session_destroy();
            return false;
        }
    }
    
    return true;
}

// ============================================
// CONTINUA SEU CÓDIGO A PARTIR DAQUI...
// ============================================
// TODO: Colocar o resto do seu código aqui

// Criar arquivo de configuração das gates se não existir
if (!file_exists($gates_file)) {
    $default_gates = [];
    foreach ($all_gates as $key => $gate) {
        $default_gates[$key] = true;
    }
    file_put_contents($gates_file, json_encode($default_gates, JSON_PRETTY_PRINT));
    chmod($gates_file, 0600);
}

// Criar arquivo de usuários se não existir
if (!file_exists($users_file)) {
    $default_users = [
        'save' => [
            'password' => password_hash('black', PASSWORD_DEFAULT),
            'role' => 'admin',
            'type' => 'permanent',
            'credits' => 0,
            'expires_at' => null,
            'created_at' => date('Y-m-d H:i:s'),
            'last_login' => null,
            'total_lives' => 0,
            'total_checks' => 0
        ]
    ];
    file_put_contents($users_file, json_encode($default_users, JSON_PRETTY_PRINT));
    chmod($users_file, 0600);
}

// Criar arquivo de lives se não existir
if (!file_exists($lives_file)) {
    file_put_contents($lives_file, json_encode([]));
    chmod($lives_file, 0600);
}

// ============================================
// FUNÇÕES DE UTILITÁRIOS
// ============================================

// Carregar usuários
function loadUsers() {
    global $users_file;
    return json_decode(file_get_contents($users_file), true);
}

// Salvar usuários
function saveUsers($users) {
    global $users_file;
    file_put_contents($users_file, json_encode($users, JSON_PRETTY_PRINT));
}

// Buscar usuário
function getUser($username) {
    $users = loadUsers();
    return $users[$username] ?? null;
}

// Carregar configuração das gates
function loadGatesConfig() {
    global $gates_file;
    return json_decode(file_get_contents($gates_file), true);
}

// Salvar configuração das gates
function saveGatesConfig($config) {
    global $gates_file;
    file_put_contents($gates_file, json_encode($config, JSON_PRETTY_PRINT));
}

// Verificar se gate está ativa
function isGateActive($gate) {
    $config = loadGatesConfig();
    return isset($config[$gate]) ? $config[$gate] : false;
}

// Carregar lives
function loadLives() {
    global $lives_file;
    return json_decode(file_get_contents($lives_file), true);
}

// Salvar lives
function saveLives($lives) {
    global $lives_file;
    file_put_contents($lives_file, json_encode($lives, JSON_PRETTY_PRINT));
}

// Adicionar live
function addLive($username, $gate, $card, $bin, $response) {
    $lives = loadLives();
    $lives[] = [
        'username' => $username,
        'gate' => $gate,
        'card' => $card,
        'bin' => $bin,
        'response' => $response,
        'created_at' => date('Y-m-d H:i:s')
    ];
    saveLives($lives);
    
    // Atualizar contador do usuário
    $users = loadUsers();
    if (isset($users[$username])) {
        $users[$username]['total_lives'] = ($users[$username]['total_lives'] ?? 0) + 1;
        saveUsers($users);
    }
    
    return true;
}

// Buscar lives do usuário
function getUserLives($username) {
    $lives = loadLives();
    $user_lives = [];
    foreach ($lives as $live) {
        if ($live['username'] === $username) {
            $user_lives[] = $live;
        }
    }
    return array_reverse($user_lives);
}

// Deduzir créditos
function deductCredits($username, $amount) {
    $users = loadUsers();
    if (!isset($users[$username])) return false;
    if ($users[$username]['type'] !== 'credits') return false;
    
    $users[$username]['credits'] -= $amount;
    if ($users[$username]['credits'] < 0) $users[$username]['credits'] = 0;
    $users[$username]['total_checks'] = ($users[$username]['total_checks'] ?? 0) + 1;
    
    saveUsers($users);
    return $users[$username]['credits'];
}

// Adicionar usuário
function addUser($username, $password, $role, $type, $credits = 0, $expires_at = null) {
    $users = loadUsers();
    if (isset($users[$username])) return false;
    
    $users[$username] = [
        'password' => password_hash($password, PASSWORD_DEFAULT),
        'role' => $role,
        'type' => $type,
        'credits' => floatval($credits),
        'expires_at' => $expires_at,
        'created_at' => date('Y-m-d H:i:s'),
        'last_login' => null,
        'total_lives' => 0,
        'total_checks' => 0
    ];
    
    saveUsers($users);
    
    // Notificar Telegram
    sendTelegramMessage("🆕 *NOVO USUÁRIO CRIADO*\n\n👤 **Usuário:** `$username`\n📋 **Tipo:** " . strtoupper($type) . "\n" . ($type === 'credits' ? "💰 **Créditos:** $credits\n" : ($expires_at ? "⏱️ **Expira:** " . date('d/m/Y H:i', strtotime($expires_at)) . "\n" : "")) . "👑 **Admin:** " . ($_SESSION['username'] ?? 'Sistema') . "\n🔗 " . SITE_URL);
    
    return true;
}

// Atualizar usuário
function updateUser($username, $data) {
    $users = loadUsers();
    if (!isset($users[$username])) return false;
    
    foreach ($data as $key => $value) {
        $users[$username][$key] = $value;
    }
    
    saveUsers($users);
    return true;
}

// Deletar usuário
function deleteUser($username) {
    $users = loadUsers();
    if ($username === 'save') return false;
    if (!isset($users[$username])) return false;
    
    unset($users[$username]);
    saveUsers($users);
    return true;
}

// Enviar mensagem Telegram
function sendTelegramMessage($message) {
    $url = "https://api.telegram.org/bot" . TELEGRAM_TOKEN . "/sendMessage";
    $data = [
        'chat_id' => TELEGRAM_CHAT,
        'text' => $message,
        'parse_mode' => 'Markdown'
    ];
    
    $options = [
        'http' => [
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($data),
            'timeout' => 5
        ]
    ];
    
    $context = stream_context_create($options);
    @file_get_contents($url, false, $context);
}

// Verificar acesso
function checkAccess() {
    if (!isset($_SESSION['logged_in'])) return false;
    
    $user = getUser($_SESSION['username']);
    if (!$user) {
        session_destroy();
        return false;
    }
    
    if ($user['type'] === 'temporary' && $user['expires_at']) {
        if (time() > strtotime($user['expires_at'])) {
            session_destroy();
            return false;
        }
    }
    
    return true;
}

// Atualizar último login
function updateLastLogin($username) {
    $users = loadUsers();
    if (isset($users[$username])) {
        $users[$username]['last_login'] = date('Y-m-d H:i:s');
        saveUsers($users);
    }
}

// ============================================
// PROCESSAR LOGIN
// ============================================
if (isset($_POST['login'])) {
    if (!isset($_SESSION['login_attempts'])) $_SESSION['login_attempts'] = 0;
    
    $username = preg_replace('/[^a-zA-Z0-9_]/', '', $_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    $user = getUser($username);
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['logged_in'] = true;
        $_SESSION['username'] = $username;
        $_SESSION['role'] = $user['role'];
        $_SESSION['type'] = $user['type'];
        $_SESSION['login_time'] = time();
        $_SESSION['login_attempts'] = 0;
        
        updateLastLogin($username);
        
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
// PROCESSAR ADMIN ACTIONS
// ============================================
if (isset($_POST['admin_action']) && isset($_SESSION['logged_in']) && $_SESSION['role'] === 'admin') {
    $action = $_POST['admin_action'];
    
    if ($action === 'add_user') {
        $username = preg_replace('/[^a-zA-Z0-9_]/', '', $_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $type = $_POST['type'] ?? 'temporary';
        $credits = floatval($_POST['credits'] ?? 0);
        $hours = intval($_POST['hours'] ?? 0);
        
        if ($username && $password) {
            if (!getUser($username)) {
                $expires_at = null;
                if ($type === 'temporary' && $hours > 0) {
                    $expires_at = date('Y-m-d H:i:s', time() + ($hours * 3600));
                }
                
                if (addUser($username, $password, 'user', $type, $credits, $expires_at)) {
                    $success_message = "✅ Usuário criado com sucesso!";
                } else {
                    $error_message = "❌ Erro ao criar usuário!";
                }
            } else {
                $error_message = "❌ Usuário já existe!";
            }
        } else {
            $error_message = "❌ Preencha todos os campos!";
        }
    }
    
    if ($action === 'recharge') {
        $username = preg_replace('/[^a-zA-Z0-9_]/', '', $_POST['username'] ?? '');
        $credits = floatval($_POST['credits'] ?? 0);
        
        if ($username && $credits > 0) {
            $user = getUser($username);
            if ($user) {
                $new_credits = $user['credits'] + $credits;
                if (updateUser($username, ['credits' => $new_credits])) {
                    $success_message = "✅ Créditos recarregados!";
                    
                    // Notificar Telegram
                    sendTelegramMessage("💰 *CRÉDITOS RECARREGADOS*\n\n👤 **Usuário:** `$username`\n💳 **Créditos:** +$credits\n💰 **Total:** $new_credits\n👑 **Admin:** {$_SESSION['username']}\n🔗 " . SITE_URL);
                } else {
                    $error_message = "❌ Erro ao recarregar!";
                }
            } else {
                $error_message = "❌ Usuário não encontrado!";
            }
        } else {
            $error_message = "❌ Preencha todos os campos!";
        }
    }
    
    if ($action === 'remove') {
        $username = preg_replace('/[^a-zA-Z0-9_]/', '', $_POST['username'] ?? '');
        
        if ($username && $username !== 'save') {
            if (deleteUser($username)) {
                $success_message = "✅ Usuário removido!";
            } else {
                $error_message = "❌ Erro ao remover!";
            }
        } else {
            $error_message = "❌ Não é possível remover o admin!";
        }
    }
    
    if ($action === 'toggle_gate') {
        $gate = $_POST['gate'] ?? '';
        $status = $_POST['status'] === 'true';
        
        $config = loadGatesConfig();
        $config[$gate] = $status;
        saveGatesConfig($config);
        
        echo json_encode(['success' => true]);
        exit;
    }
    
    if ($action === 'extend') {
        $username = preg_replace('/[^a-zA-Z0-9_]/', '', $_POST['username'] ?? '');
        $hours = intval($_POST['hours'] ?? 0);
        
        if ($username && $hours > 0) {
            $user = getUser($username);
            if ($user && $user['type'] === 'temporary') {
                $current_expires = strtotime($user['expires_at']);
                $new_expires = date('Y-m-d H:i:s', $current_expires + ($hours * 3600));
                
                if (updateUser($username, ['expires_at' => $new_expires])) {
                    $success_message = "✅ Tempo estendido em $hours horas!";
                } else {
                    $error_message = "❌ Erro ao estender tempo!";
                }
            } else {
                $error_message = "❌ Usuário não é temporário!";
            }
        } else {
            $error_message = "❌ Preencha todos os campos!";
        }
    }
}

// ============================================
// PROCESSAR CHECKER (AJAX)
// ============================================
if (isset($_GET['action']) && $_GET['action'] === 'check' && isset($_GET['lista']) && isset($_GET['tool'])) {
    if (!checkAccess()) {
        die("Acesso negado!");
    }
    
    $tool = $_GET['tool'];
    $lista = $_GET['lista'];
    $username = $_SESSION['username'];
    $user = getUser($username);
    
    // Verificar se a gate está ativa
    if (!isGateActive($tool)) {
        die("❌ Gate desativada temporariamente!");
    }
    
    // Verificar créditos
    if ($user['type'] === 'credits' && $user['credits'] < 0.05) {
        die("❌ Créditos insuficientes!");
    }
    
    // Processar a lista
    $parts = explode('|', $lista);
    $card = preg_replace('/[^0-9]/', '', $parts[0] ?? '');
    $mes = $parts[1] ?? '';
    $ano = $parts[2] ?? '';
    $cvv = $parts[3] ?? '';
    $bin = substr($card, 0, 6);
    
    if (strlen($card) < 15 || strlen($card) > 16) {
        die("❌ Cartão inválido!");
    }
    
    // Chamar o checker específico
    $checker_file = API_PATH . $all_gates[$tool]['file'];
    
    if (!file_exists($checker_file)) {
        // Simular resultado se o arquivo não existir (para testes)
        $rand = rand(1, 10);
        $isLive = ($rand <= 3);
        
        if ($isLive) {
            $response = "✅ APROVADA | Cartão: " . substr($card, 0, 6) . "******" . substr($card, -4) . " | BIN: $bin";
            $full_response = "✅ LIVE - " . $all_gates[$tool]['name'] . "\n📱 Cartão: $card|$mes|$ano|$cvv\n💳 BIN: $bin\n✅ Status: Aprovada";
            addLive($username, $tool, $card, $bin, $full_response);
            
            // Notificar live no Telegram (só BIN)
            sendTelegramMessage("✅ *LIVE ENCONTRADA*\n\n👤 **Usuário:** `$username`\n🔧 **Gate:** " . $all_gates[$tool]['name'] . "\n💳 **BIN:** `$bin`\n🔗 " . SITE_URL);
        } else {
            $response = "❌ REPROVADA | Cartão: " . substr($card, 0, 6) . "******" . substr($card, -4);
        }
        
        // Deduzir créditos (live = 2, die = 0.05)
        if ($user['type'] === 'credits') {
            $cost = $isLive ? 2.00 : 0.05;
            $remaining = deductCredits($username, $cost);
            $response .= " | 💳 Custo: R$ " . number_format($cost, 2) . " | Restante: R$ " . number_format($remaining, 2);
        }
        
        echo $response;
        exit;
    }
    
    // Incluir o checker real
    ob_start();
    include $checker_file;
    $response = ob_get_clean();
    
    // Verificar se é live
    $isLive = false;
    $live_patterns = ['✅', 'aprovada', 'approved', 'success', 'live', 'autorizado', 'authorized'];
    $response_lower = strtolower($response);
    foreach ($live_patterns as $pattern) {
        if (strpos($response_lower, strtolower($pattern)) !== false) {
            $isLive = true;
            break;
        }
    }
    
    // Salvar se for live
    if ($isLive) {
        addLive($username, $tool, $card, $bin, $response);
        
        // Notificar live no Telegram (só BIN)
        sendTelegramMessage("✅ *LIVE ENCONTRADA*\n\n👤 **Usuário:** `$username`\n🔧 **Gate:** " . $all_gates[$tool]['name'] . "\n💳 **BIN:** `$bin`\n🔗 " . SITE_URL);
    }
    
    // Deduzir créditos (live = 2, die = 0.05)
    if ($user['type'] === 'credits') {
        $cost = $isLive ? 2.00 : 0.05;
        $remaining = deductCredits($username, $cost);
        $response .= "\n💳 Custo: R$ " . number_format($cost, 2) . " | Restante: R$ " . number_format($remaining, 2);
    }
    
    echo $response;
    exit;
}

// ============================================
// VERIFICAR ACESSO
// ============================================
if (!checkAccess()) {
    $gates_config = loadGatesConfig();
    // PÁGINA DE LOGIN
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CYBERSEC 4.0 - LOGIN</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #000000 0%, #0a0a0a 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }
        
        body::before {
            content: '';
            position: absolute;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(0,255,0,0.1) 0%, transparent 50%);
            animation: rotate 20s linear infinite;
        }
        
        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        .login-container {
            width: 100%;
            max-width: 500px;
            position: relative;
            z-index: 1;
        }
        
        .login-box {
            background: rgba(10, 10, 10, 0.95);
            backdrop-filter: blur(10px);
            border: 2px solid #00ff00;
            border-radius: 30px;
            padding: 40px;
            box-shadow: 0 0 50px rgba(0, 255, 0, 0.3);
            position: relative;
            overflow: hidden;
            margin-bottom: 20px;
        }
        
        .login-box::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent, rgba(0, 255, 0, 0.1), transparent);
            animation: shine 3s linear infinite;
        }
        
        @keyframes shine {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .logo h1 {
            font-size: 48px;
            color: #00ff00;
            text-shadow: 0 0 20px #00ff00;
            margin-bottom: 10px;
            font-weight: 800;
        }
        
        .logo .subtitle {
            color: #00ffff;
            font-size: 14px;
            letter-spacing: 5px;
            text-transform: uppercase;
        }
        
        .input-group {
            margin-bottom: 25px;
            position: relative;
        }
        
        .input-group label {
            display: block;
            color: #00ff00;
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .input-group input {
            width: 100%;
            padding: 15px 20px;
            background: rgba(0, 0, 0, 0.7);
            border: 2px solid #00ff00;
            border-radius: 15px;
            color: #00ff00;
            font-size: 16px;
            transition: all 0.3s;
        }
        
        .input-group input:focus {
            outline: none;
            border-color: #00ffff;
            box-shadow: 0 0 30px rgba(0, 255, 255, 0.3);
            transform: scale(1.02);
        }
        
        .btn-login {
            width: 100%;
            padding: 18px;
            background: linear-gradient(45deg, #00ff00, #00ffff);
            border: none;
            border-radius: 15px;
            color: #000;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            margin: 30px 0;
            text-transform: uppercase;
            letter-spacing: 2px;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }
        
        .btn-login:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(0, 255, 0, 0.5);
        }
        
        .btn-login::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            animation: btn-shine 2s infinite;
        }
        
        @keyframes btn-shine {
            from { left: -100%; }
            to { left: 100%; }
        }
        
        .telegram-link {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 15px;
            background: rgba(0, 0, 0, 0.5);
            border: 2px solid #00ff00;
            border-radius: 15px;
            text-decoration: none;
            color: #00ff00;
            transition: all 0.3s;
        }
        
        .telegram-link:hover {
            background: #00ff00;
            color: #000;
            transform: translateY(-3px);
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
            animation: shake 0.5s;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-10px); }
            75% { transform: translateX(10px); }
        }
        
        .status {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 20px;
            color: #666;
            font-size: 12px;
        }
        
        .status span {
            color: #00ff00;
        }
        
        .gates-status {
            background: rgba(0, 0, 0, 0.8);
            border: 2px solid #00ff00;
            border-radius: 20px;
            padding: 20px;
            margin-top: 20px;
        }
        
        .gates-status h3 {
            color: #00ffff;
            text-align: center;
            margin-bottom: 15px;
            font-size: 16px;
        }
        
        .gates-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 10px;
        }
        
        .gate-status-item {
            display: flex;
            align-items: center;
            gap: 5px;
            padding: 8px;
            border-radius: 8px;
            background: rgba(0, 0, 0, 0.5);
            font-size: 12px;
        }
        
        .gate-status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
        }
        
        .dot-active {
            background: #00ff00;
            box-shadow: 0 0 10px #00ff00;
            animation: pulse 2s infinite;
        }
        
        .dot-inactive {
            background: #ff0000;
            box-shadow: 0 0 10px #ff0000;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <div class="logo">
                <h1>CYBERSEC 4.0</h1>
                <div class="subtitle">PREMIUM CHECKER SYSTEM</div>
            </div>
            
            <?php if (isset($login_error)): ?>
            <div class="error"><?php echo $login_error; ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="input-group">
                    <label>👤 Usuário</label>
                    <input type="text" name="username" required placeholder="Digite seu usuário">
                </div>
                
                <div class="input-group">
                    <label>🔐 Senha</label>
                    <input type="password" name="password" required placeholder="Digite sua senha">
                </div>
                
                <button type="submit" name="login" class="btn-login">Entrar no Sistema</button>
            </form>
            
            <a href="https://t.me/centralsavefullblack" target="_blank" class="telegram-link">
                <span>📱</span>
                <span>@centralsavefullblack</span>
            </a>
            
            <div class="status">
                <div>🔒 Sistema Seguro</div>
                <div>⚡ Online: <span id="status">Ativo</span></div>
            </div>
        </div>
        
        <!-- Status das Gates -->
        <div class="gates-status">
            <h3>🔧 STATUS DAS GATES</h3>
            <div class="gates-grid">
                <?php foreach ($all_gates as $key => $gate): 
                    $isActive = $gates_config[$key] ?? true;
                ?>
                <div class="gate-status-item" style="border-left: 3px solid <?php echo $gate['color']; ?>">
                    <span class="gate-status-dot <?php echo $isActive ? 'dot-active' : 'dot-inactive'; ?>"></span>
                    <span><?php echo $gate['icon']; ?> <?php echo $gate['name']; ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <script>
        // Atualizar status online
        setInterval(() => {
            document.getElementById('status').style.opacity = 
                document.getElementById('status').style.opacity === '1' ? '0.5' : '1';
        }, 500);
    </script>
</body>
</html>
<?php
exit;
}

// ============================================
// CARREGAR DADOS DO USUÁRIO
// ============================================
$current_user = getUser($_SESSION['username']);
$user_type = $current_user['type'];
$user_credits = $current_user['credits'];
$user_role = $current_user['role'];

// ============================================
// PAINEL ADMIN
// ============================================
if ($user_role === 'admin' && isset($_GET['admin'])) {
    $users = loadUsers();
    $gates_config = loadGatesConfig();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CYBERSEC 4.0 - PAINEL ADMIN</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #000000 0%, #0a0a0a 100%);
            color: #00ff00;
            min-height: 100vh;
            padding: 30px;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        /* Header */
        .header {
            background: rgba(0, 0, 0, 0.8);
            border: 2px solid #00ff00;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            backdrop-filter: blur(10px);
            box-shadow: 0 0 30px rgba(0, 255, 0, 0.2);
        }
        
        .header h1 {
            font-size: 32px;
            color: #00ffff;
            text-shadow: 0 0 15px #00ffff;
        }
        
        .header h1 span {
            color: #00ff00;
            font-size: 16px;
            margin-left: 10px;
        }
        
        .admin-badge {
            background: linear-gradient(45deg, #00ff00, #00ffff);
            color: #000;
            padding: 10px 20px;
            border-radius: 10px;
            font-weight: bold;
        }
        
        /* Navigation */
        .nav {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }
        
        .nav-btn {
            padding: 12px 25px;
            background: rgba(0, 0, 0, 0.7);
            border: 2px solid #00ff00;
            border-radius: 10px;
            color: #00ff00;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .nav-btn:hover {
            background: #00ff00;
            color: #000;
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0, 255, 0, 0.3);
        }
        
        .nav-btn.danger {
            border-color: #ff0000;
            color: #ff0000;
        }
        
        .nav-btn.danger:hover {
            background: #ff0000;
            color: #000;
        }
        
        /* Messages */
        .message {
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: bold;
            animation: slideDown 0.5s;
        }
        
        @keyframes slideDown {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
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
        
        /* Dashboard Grid */
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-bottom: 30px;
        }
        
        /* Cards */
        .card {
            background: rgba(0, 0, 0, 0.8);
            border: 2px solid #00ff00;
            border-radius: 20px;
            padding: 25px;
            backdrop-filter: blur(10px);
            transition: all 0.3s;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 255, 0, 0.3);
        }
        
        .card-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #00ff00;
        }
        
        .card-header h2 {
            color: #00ffff;
            font-size: 20px;
        }
        
        .card-header .icon {
            font-size: 24px;
        }
        
        /* Forms */
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            color: #00ffff;
            margin-bottom: 5px;
            font-size: 13px;
            font-weight: 600;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px;
            background: rgba(0, 0, 0, 0.7);
            border: 2px solid #00ff00;
            border-radius: 10px;
            color: #00ff00;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #00ffff;
            box-shadow: 0 0 20px rgba(0, 255, 255, 0.3);
        }
        
        .btn-submit {
            width: 100%;
            padding: 15px;
            background: linear-gradient(45deg, #00ff00, #00ffff);
            border: none;
            border-radius: 10px;
            color: #000;
            font-weight: bold;
            cursor: pointer;
            margin-top: 10px;
            transition: all 0.3s;
        }
        
        .btn-submit:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0, 255, 0, 0.3);
        }
        
        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: rgba(0, 0, 0, 0.8);
            border: 2px solid #00ff00;
            border-radius: 15px;
            padding: 20px;
            text-align: center;
        }
        
        .stat-value {
            font-size: 36px;
            font-weight: bold;
            color: #00ffff;
            margin: 10px 0;
        }
        
        .stat-label {
            color: #00ff00;
            font-size: 14px;
        }
        
        /* Gates Grid */
        .gates-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        
        .gate-item {
            background: rgba(0, 0, 0, 0.7);
            border: 2px solid;
            border-radius: 15px;
            padding: 15px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .gate-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0, 255, 0, 0.2);
        }
        
        .gate-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .gate-icon {
            font-size: 20px;
        }
        
        .gate-name {
            font-weight: bold;
        }
        
        .gate-status {
            width: 12px;
            height: 12px;
            border-radius: 50%;
        }
        
        .status-active {
            background: #00ff00;
            box-shadow: 0 0 15px #00ff00;
            animation: pulse 2s infinite;
        }
        
        .status-inactive {
            background: #ff0000;
            box-shadow: 0 0 15px #ff0000;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        
        /* Users Table */
        .users-section {
            background: rgba(0, 0, 0, 0.8);
            border: 2px solid #00ff00;
            border-radius: 20px;
            padding: 25px;
            margin-top: 30px;
        }
        
        .users-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .users-header h2 {
            color: #00ffff;
        }
        
        .search-box {
            padding: 10px;
            background: rgba(0, 0, 0, 0.7);
            border: 2px solid #00ff00;
            border-radius: 10px;
            color: #00ff00;
            width: 250px;
        }
        
        .table-responsive {
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th {
            background: rgba(0, 255, 0, 0.1);
            color: #00ffff;
            padding: 15px;
            text-align: left;
            font-size: 14px;
        }
        
        td {
            padding: 15px;
            border-bottom: 1px solid rgba(0, 255, 0, 0.2);
            color: #00ff00;
        }
        
        tr:hover {
            background: rgba(0, 255, 0, 0.05);
        }
        
        .user-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
        }
        
        .badge-permanent {
            background: #00ff00;
            color: #000;
        }
        
        .badge-temporary {
            background: #ffff00;
            color: #000;
        }
        
        .badge-credits {
            background: #ff00ff;
            color: #fff;
        }
        
        .badge-admin {
            background: #ff0000;
            color: #fff;
        }
        
        .action-btns {
            display: flex;
            gap: 5px;
        }
        
        .action-btn {
            padding: 5px 10px;
            border: 1px solid;
            border-radius: 5px;
            background: none;
            cursor: pointer;
            font-size: 12px;
            transition: all 0.3s;
        }
        
        .action-btn:hover {
            transform: scale(1.1);
        }
        
        .btn-recharge {
            border-color: #ff00ff;
            color: #ff00ff;
        }
        
        .btn-recharge:hover {
            background: #ff00ff;
            color: #000;
        }
        
        .btn-extend {
            border-color: #ffff00;
            color: #ffff00;
        }
        
        .btn-extend:hover {
            background: #ffff00;
            color: #000;
        }
        
        .btn-delete {
            border-color: #ff0000;
            color: #ff0000;
        }
        
        .btn-delete:hover {
            background: #ff0000;
            color: #000;
        }
        
        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.9);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal.active {
            display: flex;
        }
        
        .modal-content {
            background: #111;
            border: 2px solid #00ff00;
            border-radius: 20px;
            padding: 30px;
            max-width: 500px;
            width: 90%;
            position: relative;
        }
        
        .modal-close {
            position: absolute;
            top: 15px;
            right: 15px;
            font-size: 24px;
            cursor: pointer;
            color: #ff0000;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            
            .users-header {
                flex-direction: column;
                gap: 15px;
            }
            
            .search-box {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div>
                <h1>⚙️ PAINEL ADMIN <span>v4.0</span></h1>
                <div style="color: #00ff00; margin-top: 5px;">👑 <?php echo $_SESSION['username']; ?></div>
            </div>
            <div class="admin-badge">
                🔥 MODO ADMINISTRADOR
            </div>
        </div>
        
        <!-- Navigation -->
        <div class="nav">
            <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="nav-btn">🏠 VOLTAR AO SITE</a>
            <a href="?lives" class="nav-btn">📋 VER LIVES</a>
            <a href="?logout" class="nav-btn danger">🚪 SAIR</a>
        </div>
        
        <!-- Messages -->
        <?php if (isset($success_message)): ?>
        <div class="message success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
        <div class="message error"><?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <!-- Stats Cards -->
        <div class="stats-grid">
            <?php
            $total_users = count($users);
            $total_credits_users = 0;
            $total_credits = 0;
            $total_lives = count(loadLives());
            
            foreach ($users as $u) {
                if ($u['type'] === 'credits') {
                    $total_credits_users++;
                    $total_credits += $u['credits'];
                }
            }
            ?>
            <div class="stat-card">
                <div>👥</div>
                <div class="stat-value"><?php echo $total_users; ?></div>
                <div class="stat-label">Total de Usuários</div>
            </div>
            <div class="stat-card">
                <div>💰</div>
                <div class="stat-value"><?php echo $total_credits_users; ?></div>
                <div class="stat-label">Usuários com Créditos</div>
            </div>
            <div class="stat-card">
                <div>💳</div>
                <div class="stat-value"><?php echo number_format($total_credits, 2); ?></div>
                <div class="stat-label">Total de Créditos</div>
            </div>
            <div class="stat-card">
                <div>✅</div>
                <div class="stat-value"><?php echo $total_lives; ?></div>
                <div class="stat-label">Total de Lives</div>
            </div>
        </div>
        
        <!-- Dashboard Grid -->
        <div class="dashboard-grid">
            <!-- Criar Usuário -->
            <div class="card">
                <div class="card-header">
                    <span class="icon">👤</span>
                    <h2>CRIAR USUÁRIO</h2>
                </div>
                <form method="POST">
                    <input type="hidden" name="admin_action" value="add_user">
                    
                    <div class="form-group">
                        <label>👤 Usuário</label>
                        <input type="text" name="username" required placeholder="Digite o nome">
                    </div>
                    
                    <div class="form-group">
                        <label>🔐 Senha</label>
                        <input type="password" name="password" required placeholder="Digite a senha">
                    </div>
                    
                    <div class="form-group">
                        <label>📋 Tipo</label>
                        <select name="type" id="userType" onchange="toggleUserFields()">
                            <option value="permanent">♾️ Permanente</option>
                            <option value="temporary">⏱️ Temporário</option>
                            <option value="credits">💰 Créditos</option>
                        </select>
                    </div>
                    
                    <div class="form-group" id="creditsField" style="display: none;">
                        <label>💰 Créditos</label>
                        <input type="number" name="credits" min="0.05" step="0.01" value="10">
                    </div>
                    
                    <div class="form-group" id="hoursField" style="display: none;">
                        <label>⏱️ Horas</label>
                        <input type="number" name="hours" min="1" max="720" value="24">
                    </div>
                    
                    <button type="submit" class="btn-submit">CRIAR USUÁRIO</button>
                </form>
            </div>
            
            <!-- Gerenciar Gates -->
            <div class="card">
                <div class="card-header">
                    <span class="icon">🔧</span>
                    <h2>GERENCIAR GATES</h2>
                </div>
                <div class="gates-grid">
                    <?php foreach ($all_gates as $key => $gate): ?>
                    <div class="gate-item" style="border-color: <?php echo $gate['color']; ?>" onclick="toggleGate('<?php echo $key; ?>')">
                        <div class="gate-info">
                            <span class="gate-icon"><?php echo $gate['icon']; ?></span>
                            <span class="gate-name"><?php echo $gate['name']; ?></span>
                        </div>
                        <div class="gate-status <?php echo ($gates_config[$key] ?? true) ? 'status-active' : 'status-inactive'; ?>" id="status-<?php echo $key; ?>"></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Recarregar Créditos -->
            <div class="card">
                <div class="card-header">
                    <span class="icon">💰</span>
                    <h2>RECARREGAR CRÉDITOS</h2>
                </div>
                <form method="POST">
                    <input type="hidden" name="admin_action" value="recharge">
                    
                    <div class="form-group">
                        <label>👤 Usuário</label>
                        <select name="username" required>
                            <option value="">Selecione...</option>
                            <?php foreach ($users as $u => $data): ?>
                                <?php if ($data['type'] === 'credits'): ?>
                                <option value="<?php echo $u; ?>">
                                    <?php echo $u; ?> (💰 <?php echo number_format($data['credits'], 2); ?>)
                                </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>💰 Valor</label>
                        <input type="number" name="credits" required min="0.05" step="0.01" value="10">
                    </div>
                    
                    <button type="submit" class="btn-submit">RECARREGAR</button>
                </form>
            </div>
            
            <!-- Estender Tempo -->
            <div class="card">
                <div class="card-header">
                    <span class="icon">⏱️</span>
                    <h2>ESTENDER TEMPO</h2>
                </div>
                <form method="POST">
                    <input type="hidden" name="admin_action" value="extend">
                    
                    <div class="form-group">
                        <label>👤 Usuário</label>
                        <select name="username" required>
                            <option value="">Selecione...</option>
                            <?php foreach ($users as $u => $data): ?>
                                <?php if ($data['type'] === 'temporary'): ?>
                                <option value="<?php echo $u; ?>">
                                    <?php echo $u; ?> (⏱️ Expira: <?php echo date('d/m H:i', strtotime($data['expires_at'])); ?>)
                                </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>⏱️ Horas</label>
                        <input type="number" name="hours" required min="1" max="720" value="24">
                    </div>
                    
                    <button type="submit" class="btn-submit">ESTENDER</button>
                </form>
            </div>
        </div>
        
        <!-- Lista de Usuários -->
        <div class="users-section">
            <div class="users-header">
                <h2>📋 USUÁRIOS CADASTRADOS</h2>
                <input type="text" class="search-box" placeholder="🔍 Buscar usuário..." id="searchUser" onkeyup="searchUsers()">
            </div>
            
            <div class="table-responsive">
                <table id="usersTable">
                    <thead>
                        <tr>
                            <th>USUÁRIO</th>
                            <th>TIPO</th>
                            <th>CRÉDITOS</th>
                            <th>EXPIRA EM</th>
                            <th>LIVES</th>
                            <th>CHECKS</th>
                            <th>CRIAÇÃO</th>
                            <th>ÚLTIMO ACESSO</th>
                            <th>AÇÕES</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $username => $data): ?>
                        <tr class="user-row">
                            <td>
                                <strong><?php echo $username; ?></strong>
                                <?php if ($data['role'] === 'admin'): ?>
                                <span class="user-badge badge-admin">ADMIN</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="user-badge badge-<?php echo $data['type']; ?>">
                                    <?php echo strtoupper($data['type']); ?>
                                </span>
                            </td>
                            <td><?php echo $data['type'] === 'credits' ? number_format($data['credits'], 2) : '-'; ?></td>
                            <td>
                                <?php if ($data['type'] === 'temporary' && $data['expires_at']): ?>
                                    <?php echo date('d/m/Y H:i', strtotime($data['expires_at'])); ?>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td><?php echo $data['total_lives'] ?? 0; ?></td>
                            <td><?php echo $data['total_checks'] ?? 0; ?></td>
                            <td><?php echo date('d/m/Y', strtotime($data['created_at'])); ?></td>
                            <td><?php echo $data['last_login'] ? date('d/m/Y H:i', strtotime($data['last_login'])) : '-'; ?></td>
                            <td>
                                <div class="action-btns">
                                    <?php if ($username !== 'save'): ?>
                                        <?php if ($data['type'] === 'credits'): ?>
                                        <button class="action-btn btn-recharge" onclick="quickRecharge('<?php echo $username; ?>')">💰</button>
                                        <?php endif; ?>
                                        
                                        <?php if ($data['type'] === 'temporary'): ?>
                                        <button class="action-btn btn-extend" onclick="quickExtend('<?php echo $username; ?>')">⏱️</button>
                                        <?php endif; ?>
                                        
                                        <button class="action-btn btn-delete" onclick="deleteUser('<?php echo $username; ?>')">🗑️</button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Modal Rápido -->
    <div class="modal" id="quickModal">
        <div class="modal-content">
            <div class="modal-close" onclick="closeModal()">✖</div>
            <h2 style="color: #00ffff; margin-bottom: 20px;" id="modalTitle"></h2>
            <form method="POST" id="modalForm">
                <input type="hidden" name="admin_action" id="modalAction">
                <input type="hidden" name="username" id="modalUsername">
                
                <div class="form-group">
                    <label id="modalLabel"></label>
                    <input type="number" name="credits" id="modalValue" required min="0.05" step="0.01" value="10">
                </div>
                
                <button type="submit" class="btn-submit" id="modalButton">CONFIRMAR</button>
            </form>
        </div>
    </div>
    
    <script>
        // Toggle campos do formulário
        function toggleUserFields() {
            const type = document.getElementById('userType').value;
            document.getElementById('creditsField').style.display = type === 'credits' ? 'block' : 'none';
            document.getElementById('hoursField').style.display = type === 'temporary' ? 'block' : 'none';
        }
        
        // Toggle gate status
        function toggleGate(gate) {
            const statusEl = document.getElementById('status-' + gate);
            const newStatus = !statusEl.classList.contains('status-active');
            
            fetch('<?php echo $_SERVER['PHP_SELF']; ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'admin_action=toggle_gate&gate=' + gate + '&status=' + newStatus
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    statusEl.classList.toggle('status-active', newStatus);
                    statusEl.classList.toggle('status-inactive', !newStatus);
                }
            });
        }
        
        // Buscar usuários
        function searchUsers() {
            const search = document.getElementById('searchUser').value.toLowerCase();
            const rows = document.querySelectorAll('.user-row');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(search) ? '' : 'none';
            });
        }
        
        // Quick recharge
        function quickRecharge(username) {
            document.getElementById('modalTitle').textContent = 'RECARREGAR CRÉDITOS - ' + username;
            document.getElementById('modalAction').value = 'recharge';
            document.getElementById('modalUsername').value = username;
            document.getElementById('modalLabel').textContent = '💰 Valor dos créditos:';
            document.getElementById('modalValue').name = 'credits';
            document.getElementById('modalButton').textContent = 'RECARREGAR';
            document.getElementById('quickModal').classList.add('active');
        }
        
        // Quick extend
        function quickExtend(username) {
            document.getElementById('modalTitle').textContent = 'ESTENDER TEMPO - ' + username;
            document.getElementById('modalAction').value = 'extend';
            document.getElementById('modalUsername').value = username;
            document.getElementById('modalLabel').textContent = '⏱️ Horas para adicionar:';
            document.getElementById('modalValue').name = 'hours';
            document.getElementById('modalButton').textContent = 'ESTENDER';
            document.getElementById('quickModal').classList.add('active');
        }
        
        // Delete user
        function deleteUser(username) {
            if (confirm('Tem certeza que deseja remover o usuário ' + username + '?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="admin_action" value="remove">
                    <input type="hidden" name="username" value="${username}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        // Close modal
        function closeModal() {
            document.getElementById('quickModal').classList.remove('active');
        }
        
        // Auto refresh stats
        setInterval(() => {
            location.reload();
        }, 300000); // 5 minutos
    </script>
</body>
</html>
<?php
exit;
}

// ============================================
// HISTÓRICO DE LIVES
// ============================================
if (isset($_GET['lives'])) {
    $lives = getUserLives($_SESSION['username']);
    $export = isset($_GET['export']) && $_GET['export'] == 1;
    
    if ($export) {
        header('Content-Type: text/plain');
        header('Content-Disposition: attachment; filename="lives_' . $_SESSION['username'] . '_' . date('Ymd_His') . '.txt"');
        foreach ($lives as $live) {
            echo "========================================\n";
            echo "DATA: " . $live['created_at'] . "\n";
            echo "GATE: " . strtoupper($live['gate']) . "\n";
            echo "BIN: " . $live['bin'] . "\n";
            echo "CARTÃO: " . $live['card'] . "\n";
            echo "RESPOSTA:\n" . $live['response'] . "\n\n";
        }
        exit;
    }
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CYBERSEC 4.0 - MINHAS LIVES</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #000000 0%, #0a0a0a 100%);
            color: #00ff00;
            min-height: 100vh;
            padding: 30px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .header {
            background: rgba(0, 0, 0, 0.8);
            border: 2px solid #00ff00;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            backdrop-filter: blur(10px);
        }
        
        .header h1 {
            font-size: 32px;
            color: #00ffff;
        }
        
        .nav {
            display: flex;
            gap: 15px;
        }
        
        .btn {
            padding: 12px 25px;
            background: rgba(0, 0, 0, 0.7);
            border: 2px solid #00ff00;
            border-radius: 10px;
            color: #00ff00;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s;
        }
        
        .btn:hover {
            background: #00ff00;
            color: #000;
            transform: translateY(-3px);
        }
        
        .btn-export {
            border-color: #ffff00;
            color: #ffff00;
        }
        
        .btn-export:hover {
            background: #ffff00;
            color: #000;
        }
        
        .lives-container {
            background: rgba(0, 0, 0, 0.8);
            border: 2px solid #00ff00;
            border-radius: 20px;
            padding: 30px;
        }
        
        .lives-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-box {
            background: rgba(0, 0, 0, 0.5);
            border: 2px solid #00ff00;
            border-radius: 15px;
            padding: 20px;
            text-align: center;
        }
        
        .stat-value {
            font-size: 36px;
            font-weight: bold;
            color: #00ffff;
            margin: 10px 0;
        }
        
        .lives-grid {
            display: grid;
            gap: 20px;
        }
        
        .live-card {
            background: rgba(0, 0, 0, 0.7);
            border: 2px solid #00ff00;
            border-radius: 15px;
            padding: 20px;
            transition: all 0.3s;
        }
        
        .live-card:hover {
            transform: translateX(10px);
            border-color: #00ffff;
        }
        
        .live-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #00ff00;
        }
        
        .live-gate {
            background: linear-gradient(45deg, #00ff00, #00ffff);
            color: #000;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: bold;
        }
        
        .live-date {
            color: #00ffff;
            font-size: 14px;
        }
        
        .live-bin {
            display: inline-block;
            background: rgba(0, 255, 0, 0.2);
            border: 1px solid #00ff00;
            padding: 5px 15px;
            border-radius: 20px;
            margin: 10px 0;
        }
        
        .live-response {
            background: rgba(0, 0, 0, 0.5);
            border: 1px solid #00ff00;
            border-radius: 10px;
            padding: 15px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            white-space: pre-wrap;
            max-height: 200px;
            overflow-y: auto;
        }
        
        .empty {
            text-align: center;
            padding: 50px;
            color: #ffff00;
            font-size: 18px;
        }
        
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .lives-stats {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <h1>📋 MINHAS LIVES</h1>
                <div style="color: #00ff00; margin-top: 5px;">👤 <?php echo $_SESSION['username']; ?></div>
            </div>
            <div class="nav">
                <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="btn">🏠 VOLTAR</a>
                <a href="?lives&export=1" class="btn btn-export">📥 EXPORTAR</a>
                <a href="?logout" class="btn">🚪 SAIR</a>
            </div>
        </div>
        
        <?php if (empty($lives)): ?>
        <div class="lives-container">
            <div class="empty">
                <div style="font-size: 64px; margin-bottom: 20px;">📭</div>
                <h2>Nenhuma live encontrada ainda</h2>
                <p style="color: #00ff00; margin-top: 20px;">Continue usando os checkers para encontrar lives!</p>
            </div>
        </div>
        <?php else: ?>
        
        <?php
        $total_lives = count($lives);
        $unique_gates = count(array_unique(array_column($lives, 'gate')));
        $last_live = $lives[0]['created_at'];
        ?>
        
        <div class="lives-container">
            <div class="lives-stats">
                <div class="stat-box">
                    <div>✅ Total de Lives</div>
                    <div class="stat-value"><?php echo $total_lives; ?></div>
                </div>
                <div class="stat-box">
                    <div>🔧 Gates Diferentes</div>
                    <div class="stat-value"><?php echo $unique_gates; ?></div>
                </div>
                <div class="stat-box">
                    <div>⏱️ Última Live</div>
                    <div class="stat-value" style="font-size: 16px;"><?php echo date('d/m/Y H:i', strtotime($last_live)); ?></div>
                </div>
            </div>
            
            <div class="lives-grid">
                <?php foreach ($lives as $live): ?>
                <div class="live-card">
                    <div class="live-header">
                        <span class="live-gate"><?php echo strtoupper($live['gate']); ?></span>
                        <span class="live-date"><?php echo date('d/m/Y H:i:s', strtotime($live['created_at'])); ?></span>
                    </div>
                    
                    <div class="live-bin">
                        💳 BIN: <?php echo $live['bin']; ?>
                    </div>
                    
                    <div style="margin-bottom: 10px;">
                        <strong>📱 Cartão:</strong> <?php echo substr($live['card'], 0, 6) . '******' . substr($live['card'], -4); ?>
                    </div>
                    
                    <div class="live-response">
                        <?php echo nl2br(htmlspecialchars($live['response'])); ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
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
    
    // Verificar se a gate existe e está ativa
    if (!isset($all_gates[$selected_tool])) {
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
    
    if (!isGateActive($selected_tool)) {
        header('Location: ' . $_SERVER['PHP_SELF'] . '?error=gate_inactive');
        exit;
    }
    
    $gate = $all_gates[$selected_tool];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $gate['name']; ?> - CYBERSEC 4.0</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #000000 0%, #0a0a0a 100%);
            color: #00ff00;
            min-height: 100vh;
            padding: 30px;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        /* Header */
        .header {
            background: rgba(0, 0, 0, 0.8);
            border: 2px solid <?php echo $gate['color']; ?>;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            backdrop-filter: blur(10px);
            box-shadow: 0 0 30px rgba(<?php echo hexdec(substr($gate['color'],1,2)) ?>, <?php echo hexdec(substr($gate['color'],3,2)) ?>, <?php echo hexdec(substr($gate['color'],5,2)) ?>, 0.3);
        }
        
        .header h1 {
            font-size: 36px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .header h1 span {
            font-size: 48px;
        }
        
        .user-info {
            background: rgba(0, 0, 0, 0.5);
            border: 2px solid #00ff00;
            border-radius: 10px;
            padding: 10px 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        /* Navigation */
        .nav {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
            flex-wrap: wrap;
            justify-content: center;
        }
        
        .nav-btn {
            padding: 12px 25px;
            background: rgba(0, 0, 0, 0.7);
            border: 2px solid #00ff00;
            border-radius: 10px;
            color: #00ff00;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s;
            cursor: pointer;
        }
        
        .nav-btn:hover {
            background: #00ff00;
            color: #000;
            transform: translateY(-3px);
        }
        
        .nav-btn.start {
            background: linear-gradient(45deg, #00ff00, #00ffff);
            color: #000;
            border: none;
        }
        
        .nav-btn.stop {
            border-color: #ff0000;
            color: #ff0000;
        }
        
        .nav-btn.stop:hover {
            background: #ff0000;
            color: #000;
        }
        
        .nav-btn.clear {
            border-color: #ffff00;
            color: #ffff00;
        }
        
        .nav-btn.clear:hover {
            background: #ffff00;
            color: #000;
        }
        
        .loading {
            display: none;
            align-items: center;
            gap: 10px;
            color: #ffff00;
        }
        
        .loading.active {
            display: flex;
        }
        
        .spinner {
            width: 20px;
            height: 20px;
            border: 3px solid #ffff00;
            border-top-color: transparent;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* Status Bar */
        .status-bar {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }
        
        .status-item {
            flex: 1;
            background: rgba(0, 0, 0, 0.7);
            border: 2px solid;
            border-radius: 10px;
            padding: 15px;
            text-align: center;
        }
        
        .status-credits {
            border-color: #ff00ff;
        }
        
        .status-gate {
            border-color: <?php echo $gate['color']; ?>;
        }
        
        /* Input Area */
        .input-area {
            margin-bottom: 30px;
        }
        
        textarea {
            width: 100%;
            height: 200px;
            background: rgba(0, 0, 0, 0.7);
            border: 2px solid <?php echo $gate['color']; ?>;
            border-radius: 15px;
            color: #00ff00;
            padding: 20px;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            resize: vertical;
            transition: all 0.3s;
        }
        
        textarea:focus {
            outline: none;
            border-color: #00ffff;
            box-shadow: 0 0 30px rgba(0, 255, 255, 0.3);
        }
        
        /* Stats */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-box {
            background: rgba(0, 0, 0, 0.7);
            border: 2px solid <?php echo $gate['color']; ?>;
            border-radius: 15px;
            padding: 20px;
            text-align: center;
        }
        
        .stat-label {
            color: #00ffff;
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        .stat-value {
            font-size: 32px;
            font-weight: bold;
            color: #00ff00;
        }
        
        /* Results */
        .results-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }
        
        .result-box {
            background: rgba(0, 0, 0, 0.7);
            border: 2px solid;
            border-radius: 15px;
            padding: 20px;
            max-height: 500px;
            overflow-y: auto;
        }
        
        .result-box.live {
            border-color: #00ff00;
        }
        
        .result-box.die {
            border-color: #ff0000;
        }
        
        .result-box h3 {
            color: #00ffff;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid;
            position: sticky;
            top: 0;
            background: rgba(0, 0, 0, 0.9);
        }
        
        .result-item {
            background: rgba(0, 0, 0, 0.5);
            border-left: 4px solid;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            white-space: pre-wrap;
        }
        
        .result-item.live {
            border-left-color: #00ff00;
        }
        
        .result-item.die {
            border-left-color: #ff0000;
        }
        
        /* Credits Counter */
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
            font-size: 18px;
            backdrop-filter: blur(10px);
            z-index: 100;
        }
        
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr 1fr;
            }
            
            .results-grid {
                grid-template-columns: 1fr;
            }
            
            .credits-counter {
                position: static;
                margin-top: 20px;
            }
        }
    </style>
</head>
<body>
    <?php if ($user_type === 'credits'): ?>
    <div class="credits-counter">
        💳 <span id="currentCredits"><?php echo number_format($user_credits, 2); ?></span> créditos
    </div>
    <?php endif; ?>
    
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>
                <span><?php echo $gate['icon']; ?></span>
                <?php echo $gate['name']; ?>
            </h1>
            <div class="user-info">
                <span>👤 <?php echo $_SESSION['username']; ?></span>
                <?php if ($user_role === 'admin'): ?>
                <span style="color: #00ffff;">⭐</span>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Navigation -->
        <div class="nav">
            <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="nav-btn">🏠 MENU</a>
            <?php if ($user_role === 'admin'): ?>
            <a href="?admin=true" class="nav-btn">⚙ ADMIN</a>
            <?php endif; ?>
            <a href="?lives" class="nav-btn">📋 LIVES</a>
            <button class="nav-btn start" onclick="startCheck()">▶ INICIAR</button>
            <button class="nav-btn stop" onclick="stopCheck()">⏹ PARAR</button>
            <button class="nav-btn clear" onclick="clearAll()">🗑 LIMPAR</button>
            <a href="?logout" class="nav-btn">🚪 SAIR</a>
            <div class="loading" id="loading">
                <div class="spinner"></div>
                <span>PROCESSANDO...</span>
            </div>
        </div>
        
        <!-- Status Bar -->
        <div class="status-bar">
            <?php if ($user_type === 'credits'): ?>
            <div class="status-item status-credits">
                <div style="color: #ff00ff; margin-bottom: 5px;">💳 CRÉDITOS</div>
                <div style="font-size: 24px;"><?php echo number_format($user_credits, 2); ?></div>
            </div>
            <?php endif; ?>
            
            <div class="status-item status-gate" style="border-color: <?php echo $gate['color']; ?>">
                <div style="color: <?php echo $gate['color']; ?>; margin-bottom: 5px;">🔧 GATE</div>
                <div style="font-size: 24px;"><?php echo $gate['name']; ?></div>
            </div>
        </div>
        
        <!-- Input Area -->
        <div class="input-area">
            <textarea id="dataInput" placeholder="Cole os cartões (um por linha):
numero|mes|ano|cvv

Exemplos:
4532015112830366|12|2027|123
5425233430109903|01|2028|456
5555666677778884|05|2026|789"></textarea>
        </div>
        
        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-box">
                <div class="stat-label">TOTAL</div>
                <div class="stat-value" id="totalCount">0</div>
            </div>
            <div class="stat-box">
                <div class="stat-label">✅ APROVADOS</div>
                <div class="stat-value" id="liveCount">0</div>
            </div>
            <div class="stat-box">
                <div class="stat-label">❌ REPROVADOS</div>
                <div class="stat-value" id="dieCount">0</div>
            </div>
            <div class="stat-box">
                <div class="stat-label">⚡ PROCESSADOS</div>
                <div class="stat-value" id="processedCount">0</div>
            </div>
        </div>
        
        <!-- Results -->
        <div class="results-grid">
            <div class="result-box live">
                <h3>✅ APROVADOS</h3>
                <div id="liveResults"></div>
            </div>
            <div class="result-box die">
                <h3>❌ REPROVADOS</h3>
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
        const DELAY = 4000; // 4 segundos entre cada requisição
        
        function checkIfLive(response) {
            const patterns = ['✅', 'aprovada', 'approved', 'success', 'live', 'autorizado', 'authorized', 'valid', 'aprovado'];
            response = response.toLowerCase();
            for (const p of patterns) {
                if (response.includes(p.toLowerCase())) return true;
            }
            return false;
        }
        
        function updateCredits() {
            if (userType === 'credits') {
                document.getElementById('currentCredits').textContent = currentCredits.toFixed(2);
            }
        }
        
        function startCheck() {
            const input = document.getElementById('dataInput').value.trim();
            if (!input) {
                alert('❌ Insira os cartões para verificar!');
                return;
            }
            
            if (userType === 'credits' && currentCredits < 0.05) {
                alert('❌ Créditos insuficientes!');
                return;
            }
            
            items = input.split('\n').filter(l => l.trim());
            if (items.length > MAX_ITEMS) {
                alert(`⚠️ Máximo de ${MAX_ITEMS} itens por vez!`);
                items = items.slice(0, MAX_ITEMS);
            }
            
            currentIndex = 0;
            isChecking = true;
            document.getElementById('loading').classList.add('active');
            document.getElementById('totalCount').textContent = items.length;
            
            processNext();
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
        }
        
        async function processNext() {
            if (!isChecking || currentIndex >= items.length) {
                stopCheck();
                return;
            }
            
            const item = items[currentIndex];
            
            try {
                const res = await fetch(`?action=check&tool=${toolName}&lista=${encodeURIComponent(item)}`);
                const text = await res.text();
                
                const isLive = checkIfLive(text);
                
                if (userType === 'credits') {
                    const cost = isLive ? 2.00 : 0.05;
                    currentCredits -= cost;
                    if (currentCredits < 0) currentCredits = 0;
                    updateCredits();
                }
                
                addResult(item, text, isLive);
                
            } catch (e) {
                addResult(item, '❌ Erro na requisição: ' + e.message, false);
            }
            
            currentIndex++;
            document.getElementById('processedCount').textContent = currentIndex;
            
            if (isChecking && currentIndex < items.length) {
                setTimeout(processNext, DELAY);
            } else {
                stopCheck();
            }
        }
        
        function addResult(item, response, isLive) {
            const container = isLive ? document.getElementById('liveResults') : document.getElementById('dieResults');
            const div = document.createElement('div');
            div.className = `result-item ${isLive ? 'live' : 'die'}`;
            
            // Formatar a resposta
            const formattedResponse = response.replace(/\n/g, '<br>');
            div.innerHTML = `
                <strong>📱 ${item}</strong><br>
                <br>
                ${formattedResponse}
            `;
            
            container.insertBefore(div, container.firstChild);
            
            // Limitar número de itens
            if (container.children.length > 50) {
                container.removeChild(container.lastChild);
            }
            
            if (isLive) {
                document.getElementById('liveCount').textContent = parseInt(document.getElementById('liveCount').textContent) + 1;
            } else {
                document.getElementById('dieCount').textContent = parseInt(document.getElementById('dieCount').textContent) + 1;
            }
        }
        
        // Atualizar créditos inicial
        updateCredits();
        
        // Auto-scroll para novos resultados (opcional)
        function scrollToBottom(element) {
            element.scrollTop = element.scrollHeight;
        }
    </script>
</body>
</html>
<?php
exit;
}

// ============================================
// MENU PRINCIPAL
// ============================================
$gates_config = loadGatesConfig();
$active_gates = array_filter($gates_config, function($v) { return $v; });
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CYBERSEC 4.0 - MENU PRINCIPAL</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #000000 0%, #0a0a0a 100%);
            color: #00ff00;
            padding: 30px;
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
            background: radial-gradient(circle at 50% 50%, rgba(0, 255, 0, 0.1) 0%, transparent 50%);
            pointer-events: none;
            z-index: 0;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
        }
        
        /* Header */
        .header {
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(10px);
            border: 2px solid #00ff00;
            border-radius: 30px;
            padding: 50px;
            margin-bottom: 40px;
            text-align: center;
            position: relative;
            overflow: hidden;
            box-shadow: 0 0 50px rgba(0, 255, 0, 0.2);
        }
        
        .header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent, rgba(0, 255, 0, 0.1), transparent);
            animation: rotate 10s linear infinite;
        }
        
        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        .header h1 {
            font-size: 64px;
            color: #00ffff;
            text-shadow: 0 0 30px #00ffff;
            margin-bottom: 20px;
            position: relative;
        }
        
        .header p {
            color: #00ff00;
            font-size: 16px;
            letter-spacing: 5px;
            text-transform: uppercase;
        }
        
        .user-info {
            position: absolute;
            top: 30px;
            right: 30px;
            background: rgba(0, 0, 0, 0.7);
            border: 2px solid #00ff00;
            border-radius: 15px;
            padding: 12px 25px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .user-badge {
            background: linear-gradient(45deg, #00ff00, #00ffff);
            color: #000;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 12px;
        }
        
        /* Status Bar */
        .status-bar {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin-bottom: 40px;
            flex-wrap: wrap;
        }
        
        .status-item {
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(10px);
            border: 2px solid;
            border-radius: 15px;
            padding: 20px 40px;
            text-align: center;
            min-width: 200px;
        }
        
        .status-credits {
            border-color: #ff00ff;
            box-shadow: 0 0 30px rgba(255, 0, 255, 0.2);
        }
        
        .status-credits .value {
            color: #ff00ff;
            font-size: 28px;
            font-weight: bold;
        }
        
        .status-time {
            border-color: #ffff00;
            box-shadow: 0 0 30px rgba(255, 255, 0, 0.2);
        }
        
        .status-time .value {
            color: #ffff00;
            font-size: 20px;
        }
        
        .status-label {
            color: #00ffff;
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        /* Navigation */
        .nav {
            display: flex;
            gap: 20px;
            margin-bottom: 50px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .nav-btn {
            padding: 15px 35px;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(10px);
            border: 2px solid #00ff00;
            border-radius: 15px;
            color: #00ff00;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .nav-btn:hover {
            background: #00ff00;
            color: #000;
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 255, 0, 0.3);
        }
        
        .nav-btn.admin {
            border-color: #00ffff;
            color: #00ffff;
        }
        
        .nav-btn.admin:hover {
            background: #00ffff;
            color: #000;
        }
        
        .nav-btn.lives {
            border-color: #ffff00;
            color: #ffff00;
        }
        
        .nav-btn.lives:hover {
            background: #ffff00;
            color: #000;
        }
        
        .nav-btn.logout {
            border-color: #ff0000;
            color: #ff0000;
        }
        
        .nav-btn.logout:hover {
            background: #ff0000;
            color: #000;
        }
        
        /* Gates Grid */
        .gates-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px;
            margin-top: 30px;
        }
        
        .gate-card {
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(10px);
            border: 2px solid;
            border-radius: 20px;
            padding: 30px;
            text-decoration: none;
            color: #00ff00;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }
        
        .gate-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            transition: left 0.5s;
        }
        
        .gate-card:hover::before {
            left: 100%;
        }
        
        .gate-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0, 255, 0, 0.3);
        }
        
        .gate-card.inactive {
            opacity: 0.5;
            filter: grayscale(1);
            pointer-events: none;
        }
        
        .gate-icon {
            font-size: 48px;
            text-align: center;
            margin-bottom: 20px;
        }
        
        .gate-card h3 {
            color: #00ffff;
            text-align: center;
            margin-bottom: 15px;
            font-size: 20px;
        }
        
        .gate-status {
            position: absolute;
            top: 15px;
            right: 15px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
        }
        
        .status-active {
            background: #00ff00;
            box-shadow: 0 0 15px #00ff00;
            animation: pulse 2s infinite;
        }
        
        .status-inactive {
            background: #ff0000;
            box-shadow: 0 0 15px #ff0000;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        
        /* Info Cards */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin-top: 50px;
        }
        
        .info-card {
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(10px);
            border: 2px solid #00ff00;
            border-radius: 20px;
            padding: 25px;
            text-align: center;
        }
        
        .info-card .icon {
            font-size: 32px;
            margin-bottom: 15px;
        }
        
        .info-card .title {
            color: #00ffff;
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        .info-card .value {
            font-size: 24px;
            font-weight: bold;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .header h1 {
                font-size: 36px;
            }
            
            .user-info {
                position: static;
                margin-top: 20px;
                justify-content: center;
            }
            
            .status-bar {
                flex-direction: column;
            }
            
            .gates-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>🚀 CYBERSEC 4.0</h1>
            <p>PREMIUM CHECKER SYSTEM</p>
            
            <div class="user-info">
                <span>👤 <?php echo $_SESSION['username']; ?></span>
                <?php if ($user_role === 'admin'): ?>
                <span class="user-badge">ADMIN</span>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Status Bar -->
        <div class="status-bar">
            <?php if ($user_type === 'credits'): ?>
            <div class="status-item status-credits">
                <div class="status-label">💰 CRÉDITOS</div>
                <div class="value"><?php echo number_format($user_credits, 2); ?></div>
            </div>
            <?php endif; ?>
            
            <?php if ($user_type === 'temporary' && isset($current_user['expires_at'])): 
                $expires = strtotime($current_user['expires_at']);
                $now = time();
                $remaining = $expires - $now;
                $hours = floor($remaining / 3600);
                $minutes = floor(($remaining % 3600) / 60);
            ?>
            <div class="status-item status-time">
                <div class="status-label">⏱️ TEMPO RESTANTE</div>
                <div class="value"><?php echo $hours; ?>h <?php echo $minutes; ?>m</div>
            </div>
            <?php endif; ?>
            
            <div class="status-item" style="border-color: #00ff00;">
                <div class="status-label">🔧 GATES ATIVAS</div>
                <div class="value"><?php echo count($active_gates); ?>/<?php echo count($all_gates); ?></div>
            </div>
        </div>
        
        <!-- Navigation -->
        <div class="nav">
            <?php if ($user_role === 'admin'): ?>
            <a href="?admin=true" class="nav-btn admin">⚙ PAINEL ADMIN</a>
            <?php endif; ?>
            <a href="?lives" class="nav-btn lives">📋 MINHAS LIVES</a>
            <a href="?logout" class="nav-btn logout">🚪 SAIR</a>
        </div>
        
        <!-- Gates Grid -->
        <h2 style="color: #00ffff; margin-bottom: 20px; text-align: center;">🔧 CHECKERS DISPONÍVEIS</h2>
        
        <div class="gates-grid">
            <?php foreach ($all_gates as $key => $gate): 
                $isActive = $gates_config[$key] ?? true;
            ?>
            <a href="?tool=<?php echo $key; ?>" class="gate-card <?php echo !$isActive ? 'inactive' : ''; ?>" style="border-color: <?php echo $gate['color']; ?>">
                <div class="gate-icon"><?php echo $gate['icon']; ?></div>
                <h3><?php echo $gate['name']; ?></h3>
                <div class="gate-status <?php echo $isActive ? 'status-active' : 'status-inactive'; ?>"></div>
                <p style="color: #00ff00; font-size: 12px; margin-top: 15px;">
                    <?php echo $isActive ? '✅ Disponível' : '⛔ Em manutenção'; ?>
                </p>
            </a>
            <?php endforeach; ?>
        </div>
        
        <!-- Info Cards -->
        <div class="info-grid">
            <div class="info-card">
                <div class="icon">📊</div>
                <div class="title">TOTAL DE CHECKS</div>
                <div class="value"><?php echo $current_user['total_checks'] ?? 0; ?></div>
            </div>
            
            <div class="info-card">
                <div class="icon">✅</div>
                <div class="title">TOTAL DE LIVES</div>
                <div class="value"><?php echo $current_user['total_lives'] ?? 0; ?></div>
            </div>
            
            <div class="info-card">
                <div class="icon">📅</div>
                <div class="title">MEMBRO DESDE</div>
                <div class="value"><?php echo isset($current_user['created_at']) ? date('d/m/Y', strtotime($current_user['created_at'])) : date('d/m/Y'); ?></div>
            </div>
            
            <div class="info-card">
                <div class="icon">🔐</div>
                <div class="title">ÚLTIMO ACESSO</div>
                <div class="value"><?php echo isset($current_user['last_login']) ? date('d/m/Y H:i', strtotime($current_user['last_login'])) : 'Primeiro acesso'; ?></div>
            </div>
        </div>
    </div>
    
    <script>
        // Atualizar status em tempo real
        setInterval(() => {
            // Animação dos cards
            document.querySelectorAll('.gate-card').forEach(card => {
                card.style.transform = 'translateY(0)';
            });
        }, 1000);
    </script>
</body>
</html>
<?php
// Fim do código
?>
