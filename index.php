<?php
// ============================================
// CYBERSECOFC 3.0 - VERSÃO SUPER SIMPLES
// SEM BANCO DE DADOS - ARQUIVOS PLANOS
// ============================================

// Configurações iniciais
ini_set('display_errors', 1);
ini_set('log_errors', 1);
error_reporting(E_ALL);
date_default_timezone_set('America/Sao_Paulo');

// ============================================
// SISTEMA DE SESSÃO
// ============================================
session_start();

// ============================================
// ARQUIVO DE USUÁRIOS (SEM BANCO DE DADOS)
// ============================================
$users_file = __DIR__ . '/users_v3.json';

// Criar arquivo de usuários se não existir
if (!file_exists($users_file)) {
    $default_users = [
        'save' => [
            'password' => password_hash('black', PASSWORD_DEFAULT),
            'role' => 'admin',
            'type' => 'permanent',
            'credits' => 0,
            'expires_at' => null,
            'tools' => ['paypal', 'preauth', 'n7', 'amazon1', 'amazon2', 'cpfchecker', 
                       'ggsitau', 'getnet', 'auth', 'debitando', 'n7_new', 'gringa', 
                       'elo', 'erede', 'allbins', 'stripe', 'visamaster']
        ]
    ];
    file_put_contents($users_file, json_encode($default_users, JSON_PRETTY_PRINT));
    chmod($users_file, 0600);
}

// ============================================
// ARQUIVO DE LIVES
// ============================================
$lives_file = __DIR__ . '/lives_v3.json';
if (!file_exists($lives_file)) {
    file_put_contents($lives_file, json_encode([]));
    chmod($lives_file, 0600);
}

// ============================================
// FUNÇÕES DE USUÁRIOS
// ============================================
function loadUsers() {
    global $users_file;
    return json_decode(file_get_contents($users_file), true);
}

function saveUsers($users) {
    global $users_file;
    file_put_contents($users_file, json_encode($users, JSON_PRETTY_PRINT));
}

function getUser($username) {
    $users = loadUsers();
    return $users[$username] ?? null;
}

function addUser($username, $password, $role, $type, $credits = 0, $tools = [], $expires_at = null) {
    $users = loadUsers();
    if (isset($users[$username])) return false;
    
    $users[$username] = [
        'password' => password_hash($password, PASSWORD_DEFAULT),
        'role' => $role,
        'type' => $type,
        'credits' => floatval($credits),
        'expires_at' => $expires_at,
        'tools' => $tools
    ];
    
    saveUsers($users);
    return true;
}

function updateUser($username, $data) {
    $users = loadUsers();
    if (!isset($users[$username])) return false;
    
    foreach ($data as $key => $value) {
        $users[$username][$key] = $value;
    }
    
    saveUsers($users);
    return true;
}

function deleteUser($username) {
    $users = loadUsers();
    if ($username === 'save') return false;
    if (!isset($users[$username])) return false;
    
    unset($users[$username]);
    saveUsers($users);
    return true;
}

function deductCredits($username, $amount) {
    $users = loadUsers();
    if (!isset($users[$username])) return false;
    if ($users[$username]['type'] !== 'credits') return false;
    
    $users[$username]['credits'] -= $amount;
    if ($users[$username]['credits'] < 0) $users[$username]['credits'] = 0;
    
    saveUsers($users);
    return $users[$username]['credits'];
}

// ============================================
// FUNÇÕES DE LIVES
// ============================================
function loadLives() {
    global $lives_file;
    return json_decode(file_get_contents($lives_file), true);
}

function saveLives($lives) {
    global $lives_file;
    file_put_contents($lives_file, json_encode($lives, JSON_PRETTY_PRINT));
}

function addLive($username, $gate, $card, $response) {
    $lives = loadLives();
    $lives[] = [
        'username' => $username,
        'gate' => $gate,
        'card' => $card,
        'response' => $response,
        'created_at' => date('Y-m-d H:i:s')
    ];
    saveLives($lives);
}

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

// ============================================
// CONFIGURAÇÕES DAS FERRAMENTAS
// ============================================
$all_tools = [
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

$tool_icons = [
    'paypal' => '💰', 'preauth' => '🔐', 'n7' => '⚡', 'amazon1' => '📦',
    'amazon2' => '🛒', 'cpfchecker' => '🔍', 'ggsitau' => '🏦', 'getnet' => '💳',
    'auth' => '🔒', 'debitando' => '💸', 'n7_new' => '⚡', 'gringa' => '🌎',
    'elo' => '💎', 'erede' => '🔄', 'allbins' => '📊', 'stripe' => '💳',
    'visamaster' => '💳'
];

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
        $_SESSION['tools'] = $user['tools'];
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
// VERIFICAR ACESSO
// ============================================
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
// PROCESSAR ADMIN ACTIONS
// ============================================
if (isset($_POST['admin_action']) && isset($_SESSION['logged_in']) && $_SESSION['role'] === 'admin') {
    $action = $_POST['admin_action'];
    
    if ($action === 'add_permanent') {
        $username = preg_replace('/[^a-zA-Z0-9_]/', '', $_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $tools = $_POST['tools'] ?? [];
        
        if ($username && $password && !empty($tools)) {
            if (!getUser($username)) {
                if (addUser($username, $password, 'user', 'permanent', 0, $tools)) {
                    $success_message = "Usuário permanente criado!";
                } else {
                    $error_message = "Erro ao criar usuário!";
                }
            } else {
                $error_message = "Usuário já existe!";
            }
        } else {
            $error_message = "Preencha todos os campos!";
        }
    }
    
    if ($action === 'add_temporary') {
        $username = preg_replace('/[^a-zA-Z0-9_]/', '', $_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $hours = intval($_POST['hours'] ?? 0);
        $tools = $_POST['tools'] ?? [];
        
        if ($username && $password && $hours > 0 && !empty($tools)) {
            if (!getUser($username)) {
                $expires_at = date('Y-m-d H:i:s', time() + ($hours * 3600));
                if (addUser($username, $password, 'user', 'temporary', 0, $tools, $expires_at)) {
                    $success_message = "Acesso temporário criado por $hours horas!";
                } else {
                    $error_message = "Erro ao criar usuário!";
                }
            } else {
                $error_message = "Usuário já existe!";
            }
        } else {
            $error_message = "Preencha todos os campos!";
        }
    }
    
    if ($action === 'add_credits') {
        $username = preg_replace('/[^a-zA-Z0-9_]/', '', $_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $credits = floatval($_POST['credits'] ?? 0);
        $tools = $_POST['tools'] ?? [];
        
        if ($username && $password && $credits > 0 && !empty($tools)) {
            if (!getUser($username)) {
                if (addUser($username, $password, 'user', 'credits', $credits, $tools)) {
                    $success_message = "Usuário por créditos criado com $credits créditos!";
                } else {
                    $error_message = "Erro ao criar usuário!";
                }
            } else {
                $error_message = "Usuário já existe!";
            }
        } else {
            $error_message = "Preencha todos os campos!";
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
                    $success_message = "Créditos recarregados!";
                } else {
                    $error_message = "Erro ao recarregar!";
                }
            } else {
                $error_message = "Usuário não encontrado!";
            }
        } else {
            $error_message = "Preencha todos os campos!";
        }
    }
    
    if ($action === 'remove') {
        $username = preg_replace('/[^a-zA-Z0-9_]/', '', $_POST['username'] ?? '');
        
        if ($username && $username !== 'save') {
            if (deleteUser($username)) {
                $success_message = "Usuário removido!";
            } else {
                $error_message = "Erro ao remover!";
            }
        } else {
            $error_message = "Não é possível remover o admin!";
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
    
    // Verificar se tem acesso à ferramenta
    if (!in_array($tool, $user['tools'])) {
        die("Ferramenta não liberada!");
    }
    
    // Simular resultado (substitua pela sua lógica real)
    $rand = rand(1, 10);
    $parts = explode('|', $lista);
    $card = $parts[0] ?? '';
    $mes = $parts[1] ?? '';
    $ano = $parts[2] ?? '';
    $cvv = $parts[3] ?? '';
    
    $isLive = ($rand <= 3);
    
    if ($isLive) {
        $response = "✅ APROVADA - $tool | Cartão: $card | $mes/$ano | CVV: $cvv";
        addLive($username, $tool, $card, $response);
    } else {
        $response = "❌ REPROVADA - $tool | Cartão: $card | $mes/$ano | CVV: $cvv";
    }
    
    // Deduzir créditos se for usuário por créditos
    if ($user['type'] === 'credits') {
        $cost = $isLive ? 1.50 : 0.05;
        $remaining = deductCredits($username, $cost);
        $response .= "\n💳 Crédito: $cost | Restante: " . number_format($remaining, 2);
    }
    
    echo $response;
    exit;
}

// ============================================
// PÁGINA DE LOGIN
// ============================================
if (!checkAccess()) {
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CYBERSEC 3.0 - LOGIN</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background: #000;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: Arial, sans-serif;
            padding: 20px;
        }
        .login-box {
            background: #111;
            border: 2px solid #0f0;
            border-radius: 20px;
            padding: 50px 40px;
            max-width: 450px;
            width: 100%;
            box-shadow: 0 0 50px rgba(0,255,0,0.3);
        }
        h1 {
            color: #0f0;
            text-align: center;
            font-size: 42px;
            margin-bottom: 10px;
            font-family: 'Courier New', monospace;
        }
        .subtitle {
            color: #0ff;
            text-align: center;
            margin-bottom: 40px;
            font-size: 14px;
            text-transform: uppercase;
        }
        .input-group {
            margin-bottom: 25px;
        }
        .input-group label {
            display: block;
            color: #0ff;
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: bold;
        }
        .input-group input {
            width: 100%;
            padding: 15px;
            background: #222;
            border: 2px solid #0f0;
            border-radius: 10px;
            color: #0f0;
            font-size: 16px;
        }
        .input-group input:focus {
            outline: none;
            border-color: #0ff;
            box-shadow: 0 0 20px rgba(0,255,255,0.3);
        }
        .btn-login {
            width: 100%;
            padding: 18px;
            background: #0f0;
            border: none;
            border-radius: 10px;
            color: #000;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            margin: 20px 0;
        }
        .btn-login:hover {
            background: #0ff;
            box-shadow: 0 0 30px #0ff;
        }
        .telegram-link {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 15px;
            background: #222;
            border: 2px solid #0f0;
            border-radius: 10px;
            text-decoration: none;
            color: #0f0;
        }
        .telegram-link:hover {
            background: #0ff;
            border-color: #0ff;
            color: #000;
        }
        .error {
            background: rgba(255,0,0,0.2);
            border: 2px solid #f00;
            color: #f00;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="login-box">
        <h1>CYBERSEC 3.0</h1>
        <div class="subtitle">SISTEMA PREMIUM</div>
        
        <?php if (isset($login_error)): ?>
        <div class="error"><?php echo $login_error; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="input-group">
                <label>USUÁRIO</label>
                <input type="text" name="username" required>
            </div>
            <div class="input-group">
                <label>SENHA</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit" name="login" class="btn-login">ENTRAR</button>
        </form>
        
        <a href="https://t.me/centralsavefullblack" target="_blank" class="telegram-link">
            <span>📱 @centralsavefullblack</span>
        </a>
    </div>
</body>
</html>
<?php
exit;
}

// ============================================
// CARREGAR DADOS DO USUÁRIO LOGADO
// ============================================
$current_user = getUser($_SESSION['username']);
$user_tools = $current_user['tools'];
$user_type = $current_user['type'];
$user_credits = $current_user['credits'];

// ============================================
// PAINEL ADMIN
// ============================================
if ($_SESSION['role'] === 'admin' && isset($_GET['admin'])) {
    $users = loadUsers();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - CYBERSEC 3.0</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background: #000;
            color: #0f0;
            font-family: Arial, sans-serif;
            padding: 30px;
        }
        .container { max-width: 1400px; margin: 0 auto; }
        h1 {
            text-align: center;
            color: #0ff;
            font-size: 42px;
            margin-bottom: 40px;
            font-family: 'Courier New', monospace;
        }
        .nav {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
            justify-content: center;
        }
        .btn {
            padding: 12px 30px;
            background: #111;
            border: 2px solid #0f0;
            color: #0f0;
            text-decoration: none;
            border-radius: 10px;
            font-weight: bold;
        }
        .btn:hover {
            background: #0f0;
            color: #000;
        }
        .btn-danger { border-color: #f00; color: #f00; }
        .btn-danger:hover { background: #f00; color: #000; }
        
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 30px;
        }
        
        .section {
            background: #111;
            border: 2px solid #0f0;
            border-radius: 20px;
            padding: 30px;
        }
        
        .section h2 {
            color: #0ff;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #0f0;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            color: #0ff;
            margin-bottom: 8px;
            font-weight: bold;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px;
            background: #222;
            border: 2px solid #0f0;
            border-radius: 8px;
            color: #0f0;
        }
        
        .tools-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 10px;
            padding: 15px;
            background: #222;
            border-radius: 8px;
            max-height: 200px;
            overflow-y: auto;
        }
        
        .tool-item {
            display: flex;
            align-items: center;
            gap: 5px;
            color: #0f0;
            font-size: 12px;
        }
        
        .users-list {
            margin-top: 30px;
        }
        
        .user-card {
            background: #222;
            border: 1px solid #0f0;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .user-info h3 {
            color: #0ff;
            margin-bottom: 10px;
        }
        
        .user-info p {
            color: #0f0;
            font-size: 13px;
            margin: 3px 0;
        }
        
        .type-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
            margin-left: 10px;
        }
        .type-permanent { background: #0f0; color: #000; }
        .type-temporary { background: #ff0; color: #000; }
        .type-credits { background: #f0f; color: #fff; }
        
        .message {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: bold;
        }
        .success {
            background: rgba(0,255,0,0.2);
            border: 2px solid #0f0;
            color: #0f0;
        }
        .error {
            background: rgba(255,0,0,0.2);
            border: 2px solid #f00;
            color: #f00;
        }
        
        @media (max-width: 768px) {
            .grid { grid-template-columns: 1fr; }
            .user-card { flex-direction: column; gap: 15px; text-align: center; }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>⚙️ PAINEL ADMIN</h1>
        
        <div class="nav">
            <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="btn">← VOLTAR</a>
            <a href="?logout" class="btn btn-danger">SAIR</a>
        </div>
        
        <?php if (isset($success_message)): ?>
        <div class="message success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
        <div class="message error"><?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <div class="grid">
            <!-- Permanente -->
            <div class="section">
                <h2>➕ PERMANENTE</h2>
                <form method="POST">
                    <input type="hidden" name="admin_action" value="add_permanent">
                    <div class="form-group">
                        <label>Usuário:</label>
                        <input type="text" name="username" required>
                    </div>
                    <div class="form-group">
                        <label>Senha:</label>
                        <input type="password" name="password" required>
                    </div>
                    <div class="form-group">
                        <label>Ferramentas:</label>
                        <div class="tools-grid">
                            <?php foreach ($all_tools as $tool => $name): ?>
                            <div class="tool-item">
                                <input type="checkbox" name="tools[]" value="<?php echo $tool; ?>" id="perm_<?php echo $tool; ?>">
                                <label for="perm_<?php echo $tool; ?>"><?php echo $name; ?></label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <button type="submit" class="btn" style="width:100%;">CRIAR</button>
                </form>
            </div>
            
            <!-- Temporário -->
            <div class="section">
                <h2>⏱️ TEMPORÁRIO</h2>
                <form method="POST">
                    <input type="hidden" name="admin_action" value="add_temporary">
                    <div class="form-group">
                        <label>Usuário:</label>
                        <input type="text" name="username" required>
                    </div>
                    <div class="form-group">
                        <label>Senha:</label>
                        <input type="password" name="password" required>
                    </div>
                    <div class="form-group">
                        <label>Horas:</label>
                        <input type="number" name="hours" required min="1" max="720">
                    </div>
                    <div class="form-group">
                        <label>Ferramentas:</label>
                        <div class="tools-grid">
                            <?php foreach ($all_tools as $tool => $name): ?>
                            <div class="tool-item">
                                <input type="checkbox" name="tools[]" value="<?php echo $tool; ?>" id="temp_<?php echo $tool; ?>">
                                <label for="temp_<?php echo $tool; ?>"><?php echo $name; ?></label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <button type="submit" class="btn" style="width:100%;">CRIAR</button>
                </form>
            </div>
            
            <!-- Créditos -->
            <div class="section">
                <h2>💰 CRÉDITOS</h2>
                <form method="POST">
                    <input type="hidden" name="admin_action" value="add_credits">
                    <div class="form-group">
                        <label>Usuário:</label>
                        <input type="text" name="username" required>
                    </div>
                    <div class="form-group">
                        <label>Senha:</label>
                        <input type="password" name="password" required>
                    </div>
                    <div class="form-group">
                        <label>Créditos:</label>
                        <input type="number" name="credits" required min="0.05" step="0.01">
                    </div>
                    <div class="form-group">
                        <label>Ferramentas:</label>
                        <div class="tools-grid">
                            <?php foreach ($all_tools as $tool => $name): ?>
                            <div class="tool-item">
                                <input type="checkbox" name="tools[]" value="<?php echo $tool; ?>" id="cred_<?php echo $tool; ?>">
                                <label for="cred_<?php echo $tool; ?>"><?php echo $name; ?></label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <button type="submit" class="btn" style="width:100%;">CRIAR</button>
                </form>
            </div>
            
            <!-- Recarregar -->
            <div class="section">
                <h2>⚡ RECARREGAR</h2>
                <form method="POST">
                    <input type="hidden" name="admin_action" value="recharge">
                    <div class="form-group">
                        <label>Usuário:</label>
                        <select name="username" required>
                            <option value="">Selecione</option>
                            <?php foreach ($users as $u => $data): ?>
                                <?php if ($data['type'] === 'credits'): ?>
                                <option value="<?php echo $u; ?>">
                                    <?php echo $u; ?> (<?php echo $data['credits']; ?> créditos)
                                </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Créditos:</label>
                        <input type="number" name="credits" required min="0.05" step="0.01">
                    </div>
                    <button type="submit" class="btn" style="width:100%;">RECARREGAR</button>
                </form>
            </div>
        </div>
        
        <!-- Lista de Usuários -->
        <div class="section" style="margin-top:30px;">
            <h2>📋 USUÁRIOS</h2>
            <div class="users-list">
                <?php foreach ($users as $username => $data): ?>
                <div class="user-card">
                    <div class="user-info">
                        <h3>
                            <?php echo $username; ?>
                            <span class="type-badge type-<?php echo $data['type']; ?>">
                                <?php echo strtoupper($data['type']); ?>
                            </span>
                        </h3>
                        <p>Role: <?php echo $data['role']; ?></p>
                        <?php if ($data['type'] === 'credits'): ?>
                        <p>Créditos: <?php echo number_format($data['credits'], 2); ?></p>
                        <?php endif; ?>
                        <?php if ($data['type'] === 'temporary' && $data['expires_at']): ?>
                        <p>Expira: <?php echo date('d/m/Y H:i', strtotime($data['expires_at'])); ?></p>
                        <?php endif; ?>
                        <p>Tools: <?php echo count($data['tools']); ?> ferramentas</p>
                    </div>
                    
                    <?php if ($username !== 'save'): ?>
                    <form method="POST" onsubmit="return confirm('Remover usuário?')">
                        <input type="hidden" name="admin_action" value="remove">
                        <input type="hidden" name="username" value="<?php echo $username; ?>">
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
    $lives = getUserLives($_SESSION['username']);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Histórico - CYBERSEC 3.0</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background: #000;
            color: #0f0;
            font-family: Arial, sans-serif;
            padding: 30px;
        }
        .container { max-width: 1200px; margin: 0 auto; }
        h1 {
            text-align: center;
            color: #0ff;
            font-size: 42px;
            margin-bottom: 40px;
            font-family: 'Courier New', monospace;
        }
        .nav {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
            justify-content: center;
        }
        .btn {
            padding: 12px 30px;
            background: #111;
            border: 2px solid #0f0;
            color: #0f0;
            text-decoration: none;
            border-radius: 10px;
            font-weight: bold;
        }
        .btn:hover {
            background: #0f0;
            color: #000;
        }
        .btn-export { border-color: #ff0; color: #ff0; }
        .btn-export:hover { background: #ff0; color: #000; }
        
        .table-container {
            background: #111;
            border: 2px solid #0f0;
            border-radius: 20px;
            padding: 30px;
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th {
            background: #222;
            color: #0ff;
            padding: 15px;
            text-align: left;
        }
        
        td {
            padding: 15px;
            border-bottom: 1px solid #333;
            color: #0f0;
        }
        
        tr:hover {
            background: #222;
        }
        
        .empty {
            text-align: center;
            color: #ff0;
            padding: 50px;
            font-size: 18px;
        }
        
        @media (max-width: 768px) {
            th, td { font-size: 12px; padding: 8px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📋 MINHAS LIVES</h1>
        
        <div class="nav">
            <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="btn">← VOLTAR</a>
            <a href="?lives&export=1" class="btn btn-export">📥 EXPORTAR</a>
            <a href="?logout" class="btn">SAIR</a>
        </div>
        
        <div class="table-container">
            <?php if (empty($lives)): ?>
            <div class="empty">Nenhuma live encontrada ainda.</div>
            <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>DATA</th>
                        <th>GATE</th>
                        <th>CARTÃO</th>
                        <th>RESPOSTA</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($lives as $live): ?>
                    <tr>
                        <td><?php echo date('d/m/Y H:i', strtotime($live['created_at'])); ?></td>
                        <td><strong><?php echo strtoupper($live['gate']); ?></strong></td>
                        <td><?php echo substr($live['card'], 0, 6) . '******' . substr($live['card'], -4); ?></td>
                        <td><?php echo htmlspecialchars(substr($live['response'], 0, 100)) . '...'; ?></td>
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
        echo "=== " . $live['created_at'] . " ===\n";
        echo "Gate: " . $live['gate'] . "\n";
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
    
    $tool_name = $all_tools[$selected_tool];
    $tool_icon = $tool_icons[$selected_tool] ?? '🔧';
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $tool_name; ?> - CYBERSEC 3.0</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background: #000;
            color: #0f0;
            font-family: Arial, sans-serif;
            padding: 30px;
        }
        .container { max-width: 1200px; margin: 0 auto; }
        
        .header {
            text-align: center;
            margin-bottom: 40px;
            padding: 40px;
            background: #111;
            border: 2px solid #0f0;
            border-radius: 20px;
        }
        
        .header h1 {
            font-size: 48px;
            color: #0ff;
            margin-bottom: 10px;
            font-family: 'Courier New', monospace;
        }
        
        .user-info {
            position: absolute;
            top: 30px;
            right: 30px;
            padding: 10px 20px;
            background: #111;
            border: 2px solid #0f0;
            border-radius: 10px;
            color: #0ff;
        }
        
        .nav {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 12px 30px;
            background: #111;
            border: 2px solid #0f0;
            color: #0f0;
            text-decoration: none;
            border-radius: 10px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .btn:hover {
            background: #0f0;
            color: #000;
        }
        
        .btn-start {
            background: linear-gradient(45deg, #0f0, #0ff);
            color: #000;
            border: none;
            padding: 15px 50px;
            font-size: 18px;
        }
        
        .btn-stop { border-color: #f00; color: #f00; }
        .btn-stop:hover { background: #f00; color: #000; }
        .btn-clear { border-color: #ff0; color: #ff0; }
        .btn-clear:hover { background: #ff0; color: #000; }
        
        .status-bar {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin: 30px 0;
        }
        
        .status-item {
            padding: 15px 30px;
            border: 2px solid;
            border-radius: 10px;
            font-size: 16px;
            font-weight: bold;
        }
        
        .status-time { border-color: #ff0; color: #ff0; }
        .status-credits { border-color: #f0f; color: #f0f; }
        
        .input-area {
            margin: 30px 0;
        }
        
        textarea {
            width: 100%;
            height: 200px;
            background: #111;
            border: 2px solid #0f0;
            border-radius: 15px;
            color: #0f0;
            padding: 20px;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            resize: vertical;
        }
        
        .stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin: 30px 0;
        }
        
        .stat-box {
            background: #111;
            border: 2px solid #0f0;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
        }
        
        .stat-label {
            color: #0ff;
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        .stat-value {
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
            background: #111;
        }
        
        .result-box h3 {
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid;
            font-size: 20px;
            position: sticky;
            top: 0;
            background: #111;
        }
        
        .live-box { border-color: #0f0; }
        .live-box h3 { color: #0f0; border-color: #0f0; }
        .die-box { border-color: #f00; }
        .die-box h3 { color: #f00; border-color: #f00; }
        
        .result-item {
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 10px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
        }
        
        .result-item.live { background: rgba(0,255,0,0.1); border-left: 4px solid #0f0; }
        .result-item.die { background: rgba(255,0,0,0.1); border-left: 4px solid #f00; }
        
        .loading {
            display: none;
            color: #ff0;
            animation: blink 1s infinite;
        }
        .loading.active { display: inline-block; }
        
        @keyframes blink {
            0%,50% { opacity: 1; }
            51%,100% { opacity: 0.3; }
        }
        
        .credits-counter {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: #111;
            border: 2px solid #f0f;
            border-radius: 10px;
            padding: 15px 25px;
            color: #f0f;
            font-weight: bold;
        }
        
        @media (max-width: 768px) {
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
            <div class="user-info">
                👤 <?php echo $_SESSION['username']; ?>
                <?php if ($_SESSION['role'] === 'admin'): ?> ⭐<?php endif; ?>
            </div>
        </div>
        
        <div class="status-bar">
            <?php if ($user_type === 'credits'): ?>
            <div class="status-item status-credits" id="creditsInfo">
                <?php echo number_format($user_credits, 2); ?> créditos
            </div>
            <?php endif; ?>
        </div>
        
        <div class="nav">
            <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="btn">← VOLTAR</a>
            <?php if ($_SESSION['role'] === 'admin'): ?>
            <a href="?admin=true" class="btn">⚙ ADMIN</a>
            <?php endif; ?>
            <a href="?lives" class="btn">📋 LIVES</a>
            <a href="?logout" class="btn">SAIR</a>
            <button class="btn btn-start" onclick="startCheck()">▶ INICIAR</button>
            <button class="btn btn-stop" onclick="stopCheck()">⏹ PARAR</button>
            <button class="btn btn-clear" onclick="clearAll()">🗑 LIMPAR</button>
            <span class="loading" id="loading">⏳ PROCESSANDO...</span>
        </div>
        
        <div class="input-area">
            <textarea id="dataInput" placeholder="Cole os cartões (um por linha):
numero|mes|ano|cvv

Exemplo:
4532015112830366|12|2027|123
5425233430109903|01|2028|456"></textarea>
        </div>
        
        <div class="stats">
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
        
        <div class="results">
            <div class="result-box live-box">
                <h3>✅ APROVADOS</h3>
                <div id="liveResults"></div>
            </div>
            <div class="result-box die-box">
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
        
        function checkIfLive(response) {
            const patterns = ['aprovada','approved','success','live','✅','✓','✔','authorized','valid'];
            response = response.toLowerCase();
            for (const p of patterns) {
                if (response.includes(p)) return true;
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
            if (!input) return alert('Insira os dados!');
            
            if (userType === 'credits' && currentCredits < 0.05) {
                return alert('Créditos insuficientes!');
            }
            
            items = input.split('\n').filter(l => l.trim());
            if (items.length > MAX_ITEMS) {
                alert(`Máximo ${MAX_ITEMS} itens!`);
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
                    const cost = isLive ? 1.50 : 0.05;
                    currentCredits -= cost;
                    if (currentCredits < 0) currentCredits = 0;
                    updateCredits();
                }
                
                addResult(item, text, isLive);
                
            } catch (e) {
                addResult(item, 'Erro: ' + e.message, false);
            }
            
            currentIndex++;
            document.getElementById('processedCount').textContent = currentIndex;
            
            if (isChecking && currentIndex < items.length) {
                setTimeout(processNext, 4000);
            } else {
                stopCheck();
            }
        }
        
        function addResult(item, response, isLive) {
            const container = isLive ? document.getElementById('liveResults') : document.getElementById('dieResults');
            const div = document.createElement('div');
            div.className = `result-item ${isLive ? 'live' : 'die'}`;
            div.innerHTML = `<strong>${item}</strong><br><br>${response.replace(/\n/g,'<br>')}`;
            container.insertBefore(div, container.firstChild);
            
            if (isLive) {
                document.getElementById('liveCount').textContent = parseInt(document.getElementById('liveCount').textContent) + 1;
            } else {
                document.getElementById('dieCount').textContent = parseInt(document.getElementById('dieCount').textContent) + 1;
            }
        }
        
        updateCredits();
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
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CYBERSEC 3.0 - MENU</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background: #000;
            color: #0f0;
            font-family: Arial, sans-serif;
            padding: 30px;
        }
        .container { max-width: 1200px; margin: 0 auto; }
        
        .header {
            text-align: center;
            margin-bottom: 50px;
            padding: 50px;
            background: #111;
            border: 2px solid #0f0;
            border-radius: 30px;
        }
        
        .header h1 {
            font-size: 64px;
            color: #0ff;
            margin-bottom: 20px;
            font-family: 'Courier New', monospace;
        }
        
        .user-info {
            position: absolute;
            top: 30px;
            right: 30px;
            padding: 10px 20px;
            background: #111;
            border: 2px solid #0f0;
            border-radius: 10px;
        }
        
        .status-bar {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin: 30px 0;
        }
        
        .status-item {
            padding: 15px 40px;
            border: 2px solid;
            border-radius: 10px;
            font-size: 18px;
            font-weight: bold;
            text-align: center;
        }
        
        .status-credits { border-color: #f0f; color: #f0f; }
        
        .nav {
            display: flex;
            gap: 20px;
            margin-bottom: 50px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 15px 40px;
            background: #111;
            border: 2px solid #0f0;
            color: #0f0;
            text-decoration: none;
            border-radius: 10px;
            font-weight: bold;
            min-width: 200px;
            text-align: center;
        }
        
        .btn:hover {
            background: #0f0;
            color: #000;
        }
        
        .btn-admin { border-color: #0ff; color: #0ff; }
        .btn-admin:hover { background: #0ff; color: #000; }
        .btn-lives { border-color: #ff0; color: #ff0; }
        .btn-lives:hover { background: #ff0; color: #000; }
        .btn-logout { border-color: #f00; color: #f00; }
        .btn-logout:hover { background: #f00; color: #000; }
        
        .tools-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 30px;
        }
        
        .tool-card {
            background: #111;
            border: 2px solid #0f0;
            border-radius: 20px;
            padding: 30px;
            text-decoration: none;
            color: #0f0;
            transition: 0.3s;
        }
        
        .tool-card:hover {
            border-color: #0ff;
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0,255,255,0.3);
        }
        
        .tool-icon {
            font-size: 48px;
            text-align: center;
            margin-bottom: 20px;
        }
        
        .tool-card h3 {
            color: #0ff;
            text-align: center;
            margin-bottom: 15px;
            font-size: 20px;
        }
        
        .tool-card p {
            text-align: center;
            color: #0f0;
            font-size: 14px;
            opacity: 0.8;
        }
        
        @media (max-width: 768px) {
            .header h1 { font-size: 36px; }
            .status-bar { flex-direction: column; }
            .tools-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>CYBERSEC 3.0</h1>
            <p>SISTEMA PREMIUM DE CHECKERS</p>
            <div class="user-info">
                👤 <?php echo $_SESSION['username']; ?>
                <?php if ($_SESSION['role'] === 'admin'): ?> ⭐<?php endif; ?>
            </div>
        </div>
        
        <?php if ($user_type === 'credits'): ?>
        <div class="status-bar">
            <div class="status-item status-credits">
                💳 <?php echo number_format($user_credits, 2); ?> créditos
            </div>
        </div>
        <?php endif; ?>
        
        <div class="nav">
            <?php if ($_SESSION['role'] === 'admin'): ?>
            <a href="?admin=true" class="btn btn-admin">⚙ ADMIN</a>
            <?php endif; ?>
            <a href="?lives" class="btn btn-lives">📋 LIVES</a>
            <a href="?logout" class="btn btn-logout">🚪 SAIR</a>
        </div>
        
        <div class="tools-grid">
            <?php foreach ($user_tools as $tool): ?>
            <a href="?tool=<?php echo $tool; ?>" class="tool-card">
                <div class="tool-icon"><?php echo $tool_icons[$tool] ?? '🔧'; ?></div>
                <h3><?php echo $all_tools[$tool]; ?></h3>
                <p>Clique para acessar a ferramenta</p>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>
<?php
// Fim do código
?>
