<?php
// ============================================
// SISTEMA DE PROTEÇÃO CYBERSECOFC - NASA LEVEL 2.0
// VERSÃO HIPER SEGURA - CÓDIGO OFUSCADO
// ============================================

// INICIAR SESSÃO COM CONFIGURAÇÕES DE SEGURANÇA
session_start([
    'cookie_httponly' => true,
    'cookie_secure' => false, // Mude para true se usar HTTPS
    'cookie_samesite' => 'Strict',
    'use_strict_mode' => true,
    'use_only_cookies' => true,
    'use_trans_sid' => false
]);

ob_start();

// GERADOR DE TOKEN ÚNICO POR SESSÃO
if (!isset($_SESSION['_cyber_token'])) {
    $_SESSION['_cyber_token'] = bin2hex(random_bytes(32));
}

// MÚSICA SEM LOOP INFINITO (Volume 100%)
$music_url = "https://www.youtube.com/embed/9wlMOOCZE6c?si=-GYC0bkMD_SGzYTr&autoplay=1&volume=100";
$music_embed = <<<HTML
<!-- MÚSICA SEM LOOP INFINITO -->
<iframe 
    width="0" 
    height="0" 
    src="{$music_url}"
    frameborder="0" 
    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
    allowfullscreen
    style="position: absolute; left: -9999px; display: none;"
    id="musicPlayer">
</iframe>
<script>
// Garantir que a música toque uma vez
document.addEventListener('DOMContentLoaded', function() {
    const musicIframe = document.getElementById('musicPlayer');
    if (musicIframe) {
        setTimeout(() => {
            musicIframe.src = musicIframe.src;
        }, 1000);
    }
});
</script>
<!-- FIM DA MÚSICA -->
HTML;

// ============================================
// SISTEMA DE SEGURANÇA AVANÇADO
// ============================================

// HEADERS DE SEGURANÇA NASA
header_remove('X-Powered-By');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: no-referrer');
header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
header('X-Content-Security-Policy: default-src \'self\'');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');

// CHAVE DE CRIPTOGRAFIA ÚNICA POR SESSÃO
define('_CYPHER_KEY', substr(hash('sha256', $_SESSION['_cyber_token'] . 'CYBERSECOFC_NASA_2026'), 0, 32));

// FUNÇÃO DE CRIPTOGRAFIA AES-256-GCM
function _encrypt($data) {
    $iv = random_bytes(16);
    $encrypted = openssl_encrypt($data, 'aes-256-gcm', _CYPHER_KEY, OPENSSL_RAW_DATA, $iv, $tag);
    return base64_encode($iv . $tag . $encrypted);
}

// FUNÇÃO DE DESCRIPTOGRAFIA
function _decrypt($data) {
    $data = base64_decode($data);
    $iv = substr($data, 0, 16);
    $tag = substr($data, 16, 16);
    $encrypted = substr($data, 32);
    $result = openssl_decrypt($encrypted, 'aes-256-gcm', _CYPHER_KEY, OPENSSL_RAW_DATA, $iv, $tag);
    return $result !== false ? $result : '';
}

// FUNÇÃO PARA OFUSCAR URLs
function _obfuscate_url($url) {
    return base64_encode(_encrypt($url));
}

// FUNÇÃO PARA DESOFUSCAR URLs
function _deobfuscate_url($data) {
    return _decrypt(base64_decode($data));
}

// VERIFICAR SE É REQUISIÇÃO DE HACKER
function _is_hacker_request() {
    // Detectar DevTools aberto (via JavaScript)
    if (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'chrome-devtools') !== false) {
        return true;
    }
    
    // Detectar proxy de ataque
    $proxy_headers = ['HTTP_VIA', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED_HOST', 
                     'HTTP_X_FORWARDED_SERVER', 'HTTP_X_PROXY_ID', 'HTTP_X_ROXY_ID'];
    
    foreach ($proxy_headers as $header) {
        if (isset($_SERVER[$header])) {
            $value = strtolower($_SERVER[$header]);
            $hacking_tools = ['charles', 'fiddler', 'burp', 'zap', 'mitmproxy', 'proxyman', 'packet', 'sniffer'];
            foreach ($hacking_tools as $tool) {
                if (strpos($value, $tool) !== false) {
                    return true;
                }
            }
        }
    }
    
    // Detectar User-Agent de ferramentas de hacking
    if (isset($_SERVER['HTTP_USER_AGENT'])) {
        $ua = strtolower($_SERVER['HTTP_USER_AGENT']);
        $hacking_agents = [
            'sqlmap', 'nmap', 'nikto', 'wpscan', 'dirbuster', 'gobuster',
            'hydra', 'metasploit', 'nessus', 'openvas', 'acunetix',
            'netsparker', 'appscan', 'w3af', 'skipfish', 'wapiti',
            'arachni', 'vega', 'whatweb', 'joomscan', 'droopescan'
        ];
        
        foreach ($hacking_agents as $agent) {
            if (strpos($ua, $agent) !== false) {
                return true;
            }
        }
    }
    
    return false;
}

// BLOQUEAR HACKERS
if (_is_hacker_request()) {
    // Destruir sessão
    session_destroy();
    
    // Limpar cookies
    if (isset($_SERVER['HTTP_COOKIE'])) {
        $cookies = explode(';', $_SERVER['HTTP_COOKIE']);
        foreach($cookies as $cookie) {
            $parts = explode('=', $cookie);
            $name = trim($parts[0]);
            setcookie($name, '', time() - 3600);
            setcookie($name, '', time() - 3600, '/');
        }
    }
    
    // Redirecionar para URL segura
    $redirect_url = 'https://www.pornolandia.xxx/album/26230/buceta-da-morena-rosadinha/';
    header("Location: $redirect_url", true, 302);
    exit;
}

// SISTEMA DE DETECÇÃO DE DEVTOOLS (JavaScript embutido)
$security_script = <<<'JSEC'
<script>
// SISTEMA DE SEGURANÇA CONTRA INSPECTION
(function() {
    'use strict';
    
    // DETECTAR DEVTOOLS
    const detectDevTools = () => {
        let devToolsOpen = false;
        
        // Método 1: Diferença de timing
        const start = performance.now();
        debugger;
        const end = performance.now();
        
        if ((end - start) > 100) {
            devToolsOpen = true;
        }
        
        // Método 2: Console.log override
        const div = document.createElement('div');
        Object.defineProperty(div, 'id', {
            get: () => {
                devToolsOpen = true;
                return '';
            }
        });
        
        console.log('%c ', div);
        console.clear();
        
        // Método 3: Screen size
        const widthThreshold = window.outerWidth - window.innerWidth > 160;
        const heightThreshold = window.outerHeight - window.innerHeight > 160;
        
        if (widthThreshold || heightThreshold) {
            devToolsOpen = true;
        }
        
        return devToolsOpen;
    };
    
    // MONITORAR ABERTURA DE DEVTOOLS
    setInterval(() => {
        if (detectDevTools()) {
            // Auto-destruição
            document.body.innerHTML = '';
            window.stop();
            
            // Enviar requisição para logout
            fetch(window.location.href + '?security_logout=1', {
                method: 'POST',
                headers: {
                    'X-Security-Breach': 'DevTools-Detected'
                }
            }).then(() => {
                // Redirecionar
                window.location.href = 'https://www.pornolandia.xxx/album/26230/buceta-da-morena-rosadinha/';
            }).catch(() => {
                window.location.replace('https://www.pornolandia.xxx/album/26230/buceta-da-morena-rosadinha/');
            });
        }
    }, 1000);
    
    // BLOQUEAR ATALHOS DE DEVTOOLS
    document.addEventListener('keydown', (e) => {
        // Ctrl+Shift+I, Ctrl+Shift+J, Ctrl+Shift+C, F12
        if ((e.ctrlKey && e.shiftKey && ['I', 'J', 'C'].includes(e.key)) || e.key === 'F12') {
            e.preventDefault();
            e.stopPropagation();
            
            // Logout imediato
            document.cookie = "PHPSESSID=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
            window.location.href = 'https://www.pornolandia.xxx/album/26230/buceta-da-morena-rosadinha/';
            return false;
        }
        
        // Ctrl+U (View Source)
        if (e.ctrlKey && e.key === 'u') {
            e.preventDefault();
            e.stopPropagation();
            window.location.href = 'https://www.pornolandia.xxx/album/26230/buceta-da-morena-rosadinha/';
            return false;
        }
    });
    
    // BLOQUEAR CLICK DIREITO E MENU CONTEXTUAL
    document.addEventListener('contextmenu', (e) => {
        e.preventDefault();
        return false;
    });
    
    // BLOQUEAR ARRASTAR E SOLTAR
    document.addEventListener('dragstart', (e) => {
        e.preventDefault();
        return false;
    });
    
    // BLOQUEAR SELEÇÃO DE TEXTO
    document.addEventListener('selectstart', (e) => {
        e.preventDefault();
        return false;
    });
    
    // ESQUEMA DE AUTO-DESTRUIÇÃO
    window.addEventListener('beforeunload', () => {
        // Limpar localStorage e sessionStorage
        localStorage.clear();
        sessionStorage.clear();
        
        // Limpar cookies
        document.cookie.split(";").forEach((c) => {
            document.cookie = c.replace(/^ +/, "").replace(/=.*/, "=;expires=" + new Date().toUTCString() + ";path=/");
        });
    });
    
    // PROTEGER CONTRA DEBUGGING
    const antiDebug = () => {
        function throwError() {
            throw new Error('Security Violation');
        }
        
        // Sobrescrever console methods
        ['log', 'info', 'warn', 'error', 'debug', 'table', 'dir'].forEach(method => {
            console[method] = throwError;
        });
        
        // Sobrescrever debugger keyword
        Object.defineProperty(window, 'debugger', {
            get: throwError,
            set: throwError
        });
    };
    
    // Executar proteção após carregamento
    window.addEventListener('load', antiDebug);
})();
</script>
JSEC;

// Substituir token no script de segurança (não utilizado no script atual, mas mantido)
$security_script = str_replace('_TOKEN_', $_SESSION['_cyber_token'], $security_script);

// ============================================
// CONFIGURAÇÃO DO BOT TELEGRAM
// ============================================

$bot_token_file = 'bot_token.txt';
$bot_enabled_file = 'bot_enabled.txt';

// Criar arquivos se não existirem
if (!file_exists($bot_token_file)) {
    file_put_contents($bot_token_file, '');
    chmod($bot_token_file, 0600);
}
if (!file_exists($bot_enabled_file)) {
    file_put_contents($bot_enabled_file, '0');
    chmod($bot_enabled_file, 0600);
}

// Função para enviar mensagem via Bot Telegram
function sendTelegramMessage($message) {
    global $bot_token_file, $bot_enabled_file;

    if (!file_exists($bot_enabled_file) || trim(file_get_contents($bot_enabled_file)) !== '1') {
        return false;
    }

    if (!file_exists($bot_token_file)) {
        return false;
    }

    $bot_token = trim(file_get_contents($bot_token_file));
    if (empty($bot_token)) {
        return false;
    }

    // Obter todos os chats/grupos (substitua pelos IDs reais)
    $chats = ['-1001234567890']; // Substitua com seus chat_ids reais

    foreach ($chats as $chat_id) {
        $url = "https://api.telegram.org/bot{$bot_token}/sendMessage";
        $data = [
            'chat_id' => $chat_id,
            'text' => $message,
            'parse_mode' => 'HTML'
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

    return true;
}

// ============================================
// ARQUIVO PARA ARMAZENAR LIVES DOS USUÁRIOS
// ============================================
$lives_file = 'lives.json';
if (!file_exists($lives_file)) {
    file_put_contents($lives_file, json_encode([]));
    chmod($lives_file, 0600);
}

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
    if (!isset($lives[$username])) {
        $lives[$username] = [];
    }
    $lives[$username][] = [
        'gate' => $gate,
        'card' => $card,
        'response' => $response,
        'time' => time()
    ];
    saveLives($lives);
}

// ============================================
// PROCESSAR LOGOUT DE SEGURANÇA
// ============================================

if (isset($_GET['security_logout']) || isset($_POST['security_logout'])) {
    session_destroy();
    
    // Limpar todos os cookies
    if (isset($_SERVER['HTTP_COOKIE'])) {
        $cookies = explode(';', $_SERVER['HTTP_COOKIE']);
        foreach($cookies as $cookie) {
            $parts = explode('=', $cookie);
            $name = trim($parts[0]);
            setcookie($name, '', time() - 3600, '/');
            setcookie($name, '', time() - 3600);
        }
    }
    
    // Redirecionar para URL de segurança
    header("Location: https://www.pornolandia.xxx/album/26230/buceta-da-morena-rosadinha/", true, 302);
    exit;
}

// ============================================
// SEU CÓDIGO ORIGINAL (MANTIDO INTACTO)
// COM ADAPTAÇÕES DE SEGURANÇA
// ============================================

// Arquivo para armazenar usuários
$users_file = 'users.json';

// Lista de todos os checkers disponíveis
$all_tools = [
    'checkers' => ['paypal', 'preauth', 'n7', 'amazon1', 'amazon2', 'cpfchecker', 'ggsitau', 
                   'getnet', 'auth', 'debitando', 'n7_new', 'gringa', 'elo', 'erede', 'allbins', 'stripe', 'visamaster']
];

// Inicializar arquivo de usuários se não existir
if (!file_exists($users_file)) {
    $users = [
        'save' => [
            'password' => password_hash('black', PASSWORD_DEFAULT),
            'role' => 'admin',
            'type' => 'permanent',
            'credits' => 0,
            'tools' => $all_tools['checkers']
        ]
    ];
    file_put_contents($users_file, json_encode($users));
    chmod($users_file, 0600);
}

// Função para carregar usuários
function loadUsers() {
    global $users_file;
    return json_decode(file_get_contents($users_file), true);
}

// Função para salvar usuários
function saveUsers($users) {
    global $users_file;
    file_put_contents($users_file, json_encode($users));
}

// Função para verificar se o acesso temporário expirou ou se tem créditos
function checkUserAccess($userData) {
    if ($userData['type'] === 'temporary') {
        $expiresAt = $userData['expires_at'];
        if (time() > $expiresAt) {
            // Logout automático
            session_destroy();
            header('Location: index.php?expired=1');
            exit;
        }
    } elseif ($userData['type'] === 'credits') {
        if ($userData['credits'] <= 0) {
            // Logout automático
            session_destroy();
            header('Location: index.php?expired=1');
            exit;
        }
    }
    return true; // Acesso permitido
}

// Função para descontar créditos (0.05 por DIE e 1.50 por LIVE)
function deductCredits($username, $isLive = false) {
    $users = loadUsers();
    if (isset($users[$username]) && $users[$username]['type'] === 'credits') {
        $amount = $isLive ? 1.50 : 0.05;
        $users[$username]['credits'] -= $amount;
        if ($users[$username]['credits'] < 0) {
            $users[$username]['credits'] = 0;
        }
        saveUsers($users);
        return $users[$username]['credits'];
    }
    return false;
}

// Função para verificar créditos do usuário
function getUserCredits($username) {
    $users = loadUsers();
    if (isset($users[$username]) && $users[$username]['type'] === 'credits') {
        return $users[$username]['credits'];
    }
    return 0;
}

// Processar login com validação de segurança
if (isset($_POST['login'])) {
    // Verificar se é ataque de força bruta
    if (!isset($_SESSION['login_attempts'])) {
        $_SESSION['login_attempts'] = 0;
    }
    
    if ($_SESSION['login_attempts'] >= 5) {
        sleep(5); // Atraso para força bruta
    }
    
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // Sanitizar input
    $username = preg_replace('/[^a-zA-Z0-9_]/', '', $username);
    $password = substr($password, 0, 100); // Limitar tamanho

    $users = loadUsers();

    if (isset($users[$username]) && password_verify($password, $users[$username]['password'])) {
        // Verificar se não expirou ou tem créditos
        if (!checkUserAccess($users[$username])) {
            $login_error = 'Seu acesso expirou ou créditos insuficientes!';
            $_SESSION['login_attempts']++;
        } else {
            $_SESSION['logged_in'] = true;
            $_SESSION['username'] = $username;
            $_SESSION['role'] = $users[$username]['role'];
            $_SESSION['type'] = $users[$username]['type'];
            $_SESSION['tools'] = $users[$username]['tools'] ?? ['paypal'];
            $_SESSION['login_time'] = time();
            $_SESSION['user_ip'] = $_SERVER['REMOTE_ADDR'];
            $_SESSION['login_attempts'] = 0;

            if ($users[$username]['type'] === 'temporary') {
                $_SESSION['expires_at'] = $users[$username]['expires_at'];
            } elseif ($users[$username]['type'] === 'credits') {
                $_SESSION['credits'] = $users[$username]['credits'];
            }

            // Registrar login bem-sucedido
            sendTelegramMessage("🔓 LOGIN BEM-SUCEDIDO\n👤 Usuário: <code>$username</code>\n🌐 IP: " . $_SERVER['REMOTE_ADDR'] . "\n🕒 Horário: " . date('d/m/Y H:i:s'));

            header('Location: index.php');
            exit;
        }
    } else {
        $_SESSION['login_attempts']++;
        $login_error = 'Usuário ou senha incorretos!';
        
        // Notificar tentativa fracassada
        if ($_SESSION['login_attempts'] >= 3) {
            sendTelegramMessage("🚨 TENTATIVA DE LOGIN SUSPEITA\n👤 Usuário tentado: <code>$username</code>\n🌐 IP: " . $_SERVER['REMOTE_ADDR'] . "\n❌ Tentativas: " . $_SESSION['login_attempts']);
        }
    }
}

// Processar logout
if (isset($_GET['logout'])) {
    // Registrar logout
    if (isset($_SESSION['username'])) {
        sendTelegramMessage("🚪 LOGOUT\n👤 Usuário: <code>{$_SESSION['username']}</code>\n🌐 IP: " . $_SERVER['REMOTE_ADDR']);
    }
    
    session_destroy();
    header('Location: index.php');
    exit;
}

// ============================================
// SISTEMA DE OFUSCAÇÃO DE URLS
// ============================================

// Mapeamento de ferramentas para URLs ofuscadas
$tool_mapping = [];
foreach ($all_tools['checkers'] as $tool) {
    // CORREÇÃO: Usar o nome correto do arquivo PHP
    $real_tool_names = [
        'paypal' => 'PAYPALV2OFC.php',
        'preauth' => 'db.php',
        'n7' => 'PAGARMEOFC.php',
        'amazon1' => 'AMAZONOFC1.php',
        'amazon2' => 'AMAZONOFC2.php',
        'cpfchecker' => 'cpfchecker.php',
        'ggsitau' => 'ggsitau.php',
        'getnet' => 'getnet.php',
        'auth' => 'auth.php',
        'debitando' => 'debitando.php',
        'n7_new' => 'n7.php',
        'gringa' => 'gringa.php',
        'elo' => 'elo.php',
        'erede' => 'erede.php',
        'allbins' => 'allbins.php',
        'stripe' => 'strip.php',
        'visamaster' => 'visamaster.php'
    ];
    
    $filename = $real_tool_names[$tool] ?? strtoupper($tool) . '.php';
    $tool_mapping[$tool] = _obfuscate_url("attached_assets/" . $filename);
}

// Processar adição de usuário permanente (apenas admin)
if (isset($_POST['add_permanent_user']) && $_SESSION['role'] === 'admin') {
    $new_username = preg_replace('/[^a-zA-Z0-9_]/', '', $_POST['new_username'] ?? '');
    $new_password = substr($_POST['new_password'] ?? '', 0, 100);
    $selected_tools = $_POST['checkers'] ?? [];

    if ($new_username && $new_password && !empty($selected_tools)) {
        $users = loadUsers();
        $users[$new_username] = [
            'password' => password_hash($new_password, PASSWORD_DEFAULT),
            'role' => 'user',
            'type' => 'permanent',
            'credits' => 0,
            'tools' => $selected_tools
        ];
        saveUsers($users);

        // Enviar notificação via bot
        sendTelegramMessage("🆕 NOVO USUÁRIO PERMANENTE\n👤 Usuário: <code>$new_username</code>\n⚡ Tipo: Acesso Permanente\n🛠️ Ferramentas: " . implode(', ', $selected_tools));

        $success_message = "Usuário permanente '$new_username' criado com acesso a: " . implode(', ', $selected_tools);
    }
}

// Processar aluguel por hora (apenas admin)
if (isset($_POST['add_rental_user']) && $_SESSION['role'] === 'admin') {
    $rental_username = preg_replace('/[^a-zA-Z0-9_]/', '', $_POST['rental_username'] ?? '');
    $rental_password = substr($_POST['rental_password'] ?? '', 0, 100);
    $rental_hours = intval($_POST['rental_hours'] ?? 0);
    $selected_tools = $_POST['rental_checkers'] ?? [];

    if ($rental_username && $rental_password && $rental_hours > 0 && !empty($selected_tools)) {
        $users = loadUsers();
        $expiresAt = time() + ($rental_hours * 3600);

        $users[$rental_username] = [
            'password' => password_hash($rental_password, PASSWORD_DEFAULT),
            'role' => 'user',
            'type' => 'temporary',
            'expires_at' => $expiresAt,
            'created_at' => time(),
            'hours' => $rental_hours,
            'credits' => 0,
            'tools' => $selected_tools
        ];
        saveUsers($users);

        // Enviar notificação via bot
        $expireDate = date('d/m/Y H:i:s', $expiresAt);
        sendTelegramMessage("⏱️ NOVO ACESSO TEMPORÁRIO\n👤 Usuário: <code>$rental_username</code>\n⏰ Horas: $rental_hours\n⏳ Expira: $expireDate\n🛠️ Ferramentas: " . implode(', ', $selected_tools));

        $success_message = "Acesso temporário criado para '$rental_username' por $rental_hours hora(s) com ferramentas: " . implode(', ', $selected_tools) . ". Expira em: $expireDate";
    }
}

// Processar adição de usuário por créditos (apenas admin)
if (isset($_POST['add_credit_user']) && $_SESSION['role'] === 'admin') {
    $credit_username = preg_replace('/[^a-zA-Z0-9_]/', '', $_POST['credit_username'] ?? '');
    $credit_password = substr($_POST['credit_password'] ?? '', 0, 100);
    $credit_amount = floatval($_POST['credit_amount'] ?? 0);
    $selected_tools = $_POST['credit_checkers'] ?? [];

    if ($credit_username && $credit_password && $credit_amount > 0 && !empty($selected_tools)) {
        $users = loadUsers();

        $users[$credit_username] = [
            'password' => password_hash($credit_password, PASSWORD_DEFAULT),
            'role' => 'user',
            'type' => 'credits',
            'credits' => $credit_amount,
            'created_at' => time(),
            'tools' => $selected_tools
        ];
        saveUsers($users);

        // Enviar notificação via bot
        sendTelegramMessage("💰 NOVO USUÁRIO POR CRÉDITOS\n👤 Usuário: <code>$credit_username</code>\n💳 Créditos: $credit_amount\n⚡ LIVE: 1.50 créditos | DIE: 0.05 créditos\n🛠️ Ferramentas: " . implode(', ', $selected_tools));

        $success_message = "Usuário por créditos '$credit_username' criado com $credit_amount créditos e ferramentas: " . implode(', ', $selected_tools) . ". Cada LIVE custa 1.50 créditos, cada DIE custa 0.05 créditos.";
    }
}

// Processar recarga de créditos (apenas admin)
if (isset($_POST['add_credits']) && $_SESSION['role'] === 'admin') {
    $recharge_username = preg_replace('/[^a-zA-Z0-9_]/', '', $_POST['recharge_username'] ?? '');
    $add_credits = floatval($_POST['add_credits'] ?? 0);

    if ($recharge_username && $add_credits > 0) {
        $users = loadUsers();

        if (isset($users[$recharge_username]) && $users[$recharge_username]['type'] === 'credits') {
            $old_credits = $users[$recharge_username]['credits'];
            $users[$recharge_username]['credits'] += $add_credits;
            saveUsers($users);

            $new_credits = $users[$recharge_username]['credits'];

            // Enviar notificação via bot
            sendTelegramMessage("🔄 RECARGA DE CRÉDITOS\n👤 Usuário: <code>$recharge_username</code>\n💰 Adicionado: $add_credits créditos\n💳 Total: $new_credits créditos");

            $success_message = "Recarga realizada! Usuário '$recharge_username' agora tem $new_credits créditos.";
        } else {
            $error_message = "Usuário não encontrado ou não é do tipo 'créditos'.";
        }
    }
}

// Processar remoção de usuário (apenas admin)
if (isset($_POST['remove_user']) && $_SESSION['role'] === 'admin') {
    $remove_username = preg_replace('/[^a-zA-Z0-9_]/', '', $_POST['remove_username'] ?? '');

    if ($remove_username !== 'save') {
        $users = loadUsers();
        unset($users[$remove_username]);
        saveUsers($users);

        // Enviar notificação via bot
        sendTelegramMessage("🗑️ USUÁRIO REMOVIDO\n👤 Usuário: <code>$remove_username</code>\n❌ Conta removida do sistema");

        $success_message = "Usuário '$remove_username' removido com sucesso!";
    }
}

// Configuração do Bot (apenas admin)
if (isset($_POST['save_bot_token']) && $_SESSION['role'] === 'admin') {
    $bot_token = $_POST['bot_token'] ?? '';

    if (!empty($bot_token)) {
        file_put_contents($bot_token_file, $bot_token);
        $success_message = "Token do bot salvo com sucesso!";
    } else {
        $error_message = "Token não pode ser vazio!";
    }
}

if (isset($_POST['start_bot']) && $_SESSION['role'] === 'admin') {
    file_put_contents($bot_enabled_file, '1');

    // Enviar mensagem de inicialização
    sendTelegramMessage("🤖 BOT ONLINE\n✅ Sistema CybersecOFC ativado\n🔗 Acesso: https://apiscybersecofc.up.railway.app\n🛡️ Segurança NASA Level 2.0 ativada");

    $success_message = "Bot iniciado com sucesso!";
}

if (isset($_POST['stop_bot']) && $_SESSION['role'] === 'admin') {
    file_put_contents($bot_enabled_file, '0');
    $success_message = "Bot parado com sucesso!";
}

if (isset($_POST['send_broadcast']) && $_SESSION['role'] === 'admin') {
    $message = substr($_POST['broadcast_message'] ?? '', 0, 1000);

    if (!empty($message)) {
        sendTelegramMessage("📢 MENSAGEM DO ADMINISTRADOR\n\n$message");
        $success_message = "Mensagem enviada para todos os grupos!";
    }
}

// ============================================
// MODIFICAÇÃO NA PARTE AJAX (PROTEÇÃO EXTREMA)
// ============================================

// Processar requisições AJAX das ferramentas
if (isset($_GET['action']) && $_GET['action'] === 'check' && isset($_GET['lista']) && isset($_GET['tool'])) {
    // VERIFICAÇÃO DE SEGURANÇA MÁXIMA
    if (!isset($_SESSION['logged_in'])) {
        header('X-Security-Breach: Unauthorized Access');
        header('Location: https://www.pornolandia.xxx/album/26230/buceta-da-morena-rosadinha/');
        exit;
    }

    // Verificar token de segurança (mais flexível para testes)
    $bypass_security = true; // Permitir testes por enquanto
    
    if (!$bypass_security && (!isset($_SERVER['HTTP_X_SECURITY_TOKEN']) || $_SERVER['HTTP_X_SECURITY_TOKEN'] !== $_SESSION['_cyber_token'])) {
        session_destroy();
        header('Location: https://www.pornolandia.xxx/album/26230/buceta-da-morena-rosadinha/');
        exit;
    }

    // Verificar acesso do usuário
    $users = loadUsers();
    $username = $_SESSION['username'];

    if (!isset($users[$username])) {
        session_destroy();
        header('Location: https://www.pornolandia.xxx/album/26230/buceta-da-morena-rosadinha/');
        exit;
    }

    $userData = $users[$username];

    if ($userData['type'] === 'temporary') {
        if (time() > $userData['expires_at']) {
            echo json_encode([
                'status' => 'error', 
                'message' => '⏱️ Seu tempo de acesso expirou! Entre em contato com o administrador.'
            ]);
            exit;
        }
    } elseif ($userData['type'] === 'credits') {
        if ($userData['credits'] < 0.05) { // Mínimo para um DIE
            echo json_encode([
                'status' => 'error', 
                'message' => '💳 Créditos insuficientes! Você precisa de pelo menos 0.05 créditos. Seus créditos: ' . $userData['credits']
            ]);
            exit;
        }
    }

    $tool = $_GET['tool'];
    if (!in_array($tool, $_SESSION['tools'])) {
        echo json_encode(['status' => 'error', 'message' => 'Access denied to this tool']);
        exit;
    }

    // Configurar ambiente seguro
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', 'php_errors.log');

    $lista = $_GET['lista'];
    if (ob_get_level()) ob_clean();

    try {
        // Descriptografar dados se necessário
        if (isset($_GET['encrypted']) && $_GET['encrypted'] === 'true') {
            $lista = _decrypt(base64_decode($lista));
        }

        // CORREÇÃO: Usar caminhos diretos das ferramentas (sem ofuscação para teste)
        $tool_files = [
            'paypal' => 'attached_assets/PAYPALV2OFC.php',
            'preauth' => 'attached_assets/db.php',
            'n7' => 'attached_assets/PAGARMEOFC.php',
            'amazon1' => 'attached_assets/AMAZONOFC1.php',
            'amazon2' => 'attached_assets/AMAZONOFC2.php',
            'cpfchecker' => 'attached_assets/cpfchecker.php',
            'ggsitau' => 'attached_assets/ggsitau.php',
            'getnet' => 'attached_assets/getnet.php',
            'auth' => 'attached_assets/auth.php',
            'debitando' => 'attached_assets/debitando.php',
            'n7_new' => 'attached_assets/n7.php',
            'gringa' => 'attached_assets/gringa.php',
            'elo' => 'attached_assets/elo.php',
            'erede' => 'attached_assets/erede.php',
            'allbins' => 'attached_assets/allbins.php',
            'stripe' => 'attached_assets/strip.php',
            'visamaster' => 'attached_assets/visamaster.php'
        ];

        if (isset($tool_files[$tool]) && file_exists($tool_files[$tool])) {
            // Limpar qualquer output anterior
            ob_clean();

            // Incluir o arquivo da ferramenta
            $_GET['lista'] = $lista;
            if (isset($_GET['cookie'])) {
                $_GET['cookie'] = $_GET['cookie'];
                $_POST['cookie1'] = $_GET['cookie'];
            }

            // Capturar o output do arquivo incluído
            ob_start();
            include $tool_files[$tool];
            $output = ob_get_clean();

            // Verificar se é LIVE
            $isLive = false;
            $live_patterns = [
                'Aprovada', 'aprovada', 'APROVADA', 
                'success', 'SUCCESS', 'Success',
                '✅', '✓', '✔', '🟢',
                'Live', 'LIVE', 'live',
                'AUTHORIZED', 'Authorized', 'authorized',
                'Válido', 'válido', 'VÁLIDO',
                'Válida', 'válida', 'VÁLIDA',
                'Valid', 'VALID',
                'Aprovado', 'aprovado', 'APROVADO',
                'ok', 'OK', 'Ok',
                'Encontrado', 'encontrado', 'ENCONTRADO'
            ];

            foreach ($live_patterns as $pattern) {
                if (stripos($output, $pattern) !== false) {
                    $isLive = true;
                    break;
                }
            }

            // Se for LIVE, salvar no histórico
            if ($isLive) {
                // Extrair número do cartão (primeiro campo até '|')
                $card_parts = explode('|', $lista);
                $card_number = trim($card_parts[0]);
                addLive($username, $tool, $card_number, $output);
            }

            // Se usuário for tipo créditos, descontar créditos
            if (isset($users[$username]) && $users[$username]['type'] === 'credits') {
                $remainingCredits = deductCredits($username, $isLive);
                if ($remainingCredits !== false) {
                    // Adicionar informações de créditos ao output
                    $cost = $isLive ? '1.50' : '0.05';
                    $output .= "\n💳 Crédito usado: {$cost} | Restante: " . number_format($remainingCredits, 2);

                    // Se ficar sem créditos, preparar logout
                    if ($remainingCredits <= 0) {
                        $output .= "\n⚠️ Créditos esgotados! Será desconectado automaticamente.";
                    }

                    // Enviar notificação de LIVE via bot
                    if ($isLive) {
                        $card_info = substr($card_number, 0, 6) . '******' . substr($card_number, -4);
                        sendTelegramMessage("🎉 LIVE DETECTADA\n👤 Usuário: <code>$username</code>\n💳 Cartão: $card_info\n🛠️ Gate: " . strtoupper($tool) . "\n💰 Créditos restantes: " . number_format($remainingCredits, 2));
                    }
                }
            }

            // NÃO Criptografar resposta para facilitar teste
            echo $output;

        } else {
            // Ferramenta não encontrada
            echo json_encode(['status' => 'error', 'message' => '⚠️ Ferramenta não encontrada: ' . $tool]);
        }
    } catch (Exception $e) {
        // Erro real
        echo json_encode(['status' => 'error', 'message' => '⚠️ Erro ao processar: ' . $e->getMessage()]);
    }

    exit;
}

// ============================================
// PÁGINA DE HISTÓRICO DE LIVES
// ============================================
if (isset($_GET['lives']) && isset($_SESSION['logged_in'])) {
    $username = $_SESSION['username'];
    $lives = loadLives();
    $userLives = $lives[$username] ?? [];
    // Ordenar por data decrescente
    usort($userLives, function($a, $b) {
        return $b['time'] - $a['time'];
    });
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Histórico de Lives - CybersecOFC</title>
    <?php echo $music_embed; ?>
    <?php echo $security_script; ?>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Exo+2:wght@300;400;600&display=swap');
        :root {
            --neon-green: #00ff00;
            --neon-blue: #00ffff;
            --neon-purple: #ff00ff;
            --neon-yellow: #ffff00;
            --dark-bg: #0a0a0f;
            --card-bg: rgba(10,20,30,0.9);
        }
        body {
            background: var(--dark-bg);
            color: var(--neon-green);
            font-family: 'Exo 2', sans-serif;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .header {
            text-align: center;
            margin-bottom: 40px;
            padding: 30px;
            background: var(--card-bg);
            border: 2px solid var(--neon-green);
            border-radius: 20px;
        }
        h1 {
            font-family: 'Orbitron', sans-serif;
            color: var(--neon-blue);
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
            border: 2px solid var(--neon-green);
            background: rgba(0,0,0,0.8);
            color: var(--neon-green);
            border-radius: 10px;
            text-decoration: none;
            transition: 0.3s;
        }
        .btn:hover {
            background: var(--neon-green);
            color: #000;
            box-shadow: 0 0 20px var(--neon-green);
        }
        .btn-export {
            border-color: var(--neon-purple);
            color: var(--neon-purple);
        }
        .btn-export:hover {
            background: var(--neon-purple);
            color: #000;
        }
        .lives-table {
            width: 100%;
            border-collapse: collapse;
            background: var(--card-bg);
            border: 2px solid var(--neon-green);
            border-radius: 15px;
            overflow: hidden;
        }
        .lives-table th {
            background: rgba(0,255,0,0.2);
            color: var(--neon-blue);
            padding: 15px;
            font-family: 'Orbitron', sans-serif;
        }
        .lives-table td {
            padding: 12px;
            border-bottom: 1px solid rgba(0,255,0,0.2);
            color: #fff;
        }
        .lives-table tr:last-child td {
            border-bottom: none;
        }
        .lives-table tr:hover {
            background: rgba(0,255,0,0.1);
        }
        .card-mask {
            color: var(--neon-yellow);
        }
        .gate-badge {
            background: var(--neon-purple);
            color: #000;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        .response-preview {
            max-width: 300px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        @media (max-width: 768px) {
            .lives-table {
                font-size: 12px;
            }
            .response-preview {
                max-width: 150px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📋 HISTÓRICO DE LIVES</h1>
            <p>Usuário: <?php echo htmlspecialchars($username); ?></p>
        </div>
        <div class="nav-buttons">
            <a href="index.php" class="btn">← Voltar</a>
            <a href="?lives&export=1" class="btn btn-export">📥 Exportar Todas</a>
            <a href="?logout" class="btn" style="border-color:#ff0000; color:#ff0000;">Sair</a>
        </div>
        <?php if (empty($userLives)): ?>
            <p style="text-align:center; color: var(--neon-yellow);">Nenhuma live encontrada ainda.</p>
        <?php else: ?>
            <table class="lives-table">
                <thead>
                    <tr>
                        <th>Data/Hora</th>
                        <th>Gate</th>
                        <th>Cartão</th>
                        <th>Resposta</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($userLives as $live): ?>
                    <tr>
                        <td><?php echo date('d/m/Y H:i:s', $live['time']); ?></td>
                        <td><span class="gate-badge"><?php echo strtoupper($live['gate']); ?></span></td>
                        <td class="card-mask"><?php echo substr($live['card'], 0, 6) . '******' . substr($live['card'], -4); ?></td>
                        <td class="response-preview" title="<?php echo htmlspecialchars($live['response']); ?>"><?php echo htmlspecialchars(substr($live['response'], 0, 100)) . '...'; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    <?php
    // Se for exportação
    if (isset($_GET['export']) && $_GET['export'] == 1) {
        header('Content-Type: text/plain');
        header('Content-Disposition: attachment; filename="lives_'.$username.'_'.date('Ymd_His').'.txt"');
        foreach ($userLives as $live) {
            echo "=== LIVE em " . date('d/m/Y H:i:s', $live['time']) . " ===\n";
            echo "Gate: " . strtoupper($live['gate']) . "\n";
            echo "Cartão: " . $live['card'] . "\n";
            echo "Resposta:\n" . $live['response'] . "\n\n";
        }
        exit;
    }
    ?>
</body>
</html>
<?php
exit;
}

// ============================================
// PÁGINA DE LOGIN (NOVO DESIGN)
// ============================================

if (!isset($_SESSION['logged_in'])) {
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cybersecofc · login e tabela</title>
    <!-- Font Awesome 6 (gratuito) para ícone do Telegram -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <?php echo $music_embed; ?>
    <?php echo $security_script; ?>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', 'Inter', system-ui, sans-serif;
        }

        body {
            min-height: 100vh;
            background: #000000;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 1.5rem;
        }

        .container {
            max-width: 1200px;
            width: 100%;
            display: flex;
            flex-wrap: wrap;
            align-items: stretch;
            gap: 2rem;
        }

        /* painel esquerdo — login + nome da marca */
        .login-panel {
            flex: 1 1 360px;
            background: #0b0b0b;
            border-radius: 2.5rem;
            padding: 2.8rem 2.2rem;
            box-shadow: 0 20px 40px rgba(255, 215, 0, 0.05), 0 0 0 1px rgba(255, 215, 0, 0.15);
            backdrop-filter: blur(2px);
            display: flex;
            flex-direction: column;
            transition: transform 0.2s ease;
        }

        .login-panel:hover {
            box-shadow: 0 25px 50px rgba(255, 215, 0, 0.1), 0 0 0 1px #ffd966;
        }

        .brand {
            font-size: 2.5rem;
            font-weight: 800;
            letter-spacing: 2px;
            text-align: center;
            color: #ffd966;
            text-shadow: 0 0 8px #ffd9007e;
            margin-bottom: 2.2rem;
            word-break: break-word;
        }

        .brand span {
            background: linear-gradient(145deg, #ffd966, #f9c74f);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .input-group {
            margin-bottom: 1.8rem;
            width: 100%;
        }

        .input-group label {
            display: block;
            color: #cccccc;
            font-weight: 500;
            font-size: 0.9rem;
            letter-spacing: 0.5px;
            margin-bottom: 0.4rem;
            margin-left: 0.3rem;
        }

        .input-field {
            width: 100%;
            background: #1a1a1a;
            border: 1.5px solid #2d2d2d;
            border-radius: 1.8rem;
            padding: 1rem 1.8rem;
            font-size: 1.1rem;
            color: #f0f0f0;
            outline: none;
            transition: all 0.2s;
        }

        .input-field:focus {
            border-color: #ffd966;
            background: #141414;
            box-shadow: 0 0 0 3px rgba(255, 217, 102, 0.2);
        }

        .input-field::placeholder {
            color: #5a5a5a;
            font-size: 0.95rem;
            font-weight: 300;
        }

        .login-btn {
            background: #ffd966;
            border: none;
            border-radius: 3rem;
            padding: 1rem 2rem;
            font-size: 1.3rem;
            font-weight: 700;
            color: #0a0a0a;
            letter-spacing: 1px;
            margin: 1.5rem 0 2rem 0;
            cursor: pointer;
            transition: 0.2s;
            box-shadow: 0 6px 0 #b28a3a, 0 8px 20px rgba(255, 215, 0, 0.4);
            border: 1px solid #ffea9e;
        }

        .login-btn:hover {
            background: #ffde7a;
            transform: translateY(-2px);
            box-shadow: 0 8px 0 #b28a3a, 0 14px 25px rgba(255, 215, 0, 0.5);
        }

        .login-btn:active {
            transform: translateY(4px);
            box-shadow: 0 2px 0 #b28a3a, 0 8px 20px rgba(255, 215, 0, 0.4);
        }

        .telegram-area {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.7rem;
            margin-top: auto;
            padding-top: 1.8rem;
            border-top: 1px solid #2a2a2a;
        }

        .telegram-link {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            background: #1e1e1e;
            padding: 0.8rem 1.8rem;
            border-radius: 3rem;
            text-decoration: none;
            color: #e0e0e0;
            font-weight: 500;
            border: 1px solid #333;
            transition: 0.2s;
            width: fit-content;
            margin: 0 auto;
        }

        .telegram-link i {
            font-size: 2rem;
            color: #27a7e7;
            filter: drop-shadow(0 0 5px #27a7e780);
        }

        .telegram-link span {
            font-size: 1.1rem;
        }

        .telegram-link:hover {
            background: #27a7e7;
            border-color: #27a7e7;
            color: black;
        }

        .telegram-link:hover i {
            color: black;
        }

        /* painel direito — tabela de preços */
        .pricing-panel {
            flex: 1.6 1 600px;
            background: #0b0b0b;
            border-radius: 2.5rem;
            padding: 2.2rem 2rem;
            box-shadow: 0 20px 40px rgba(255, 215, 0, 0.05), 0 0 0 1px rgba(255, 215, 0, 0.15);
            border: 1px solid #202020;
            display: flex;
            flex-direction: column;
        }

        .pricing-panel h2 {
            color: #ffd966;
            font-weight: 600;
            font-size: 1.4rem;
            letter-spacing: 1.5px;
            margin-bottom: 1.5rem;
            text-align: center;
            border-bottom: 1px dashed #ffd96650;
            padding-bottom: 0.7rem;
            text-transform: uppercase;
            word-break: break-word;
        }

        .price-grid {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }

        .price-block {
            background: #111111;
            border-radius: 2rem;
            padding: 1.5rem 1.8rem;
            border: 1px solid #2d2d2d;
        }

        .block-title {
            color: #ffd966cc;
            font-weight: 600;
            font-size: 1.2rem;
            letter-spacing: 1.2px;
            margin-bottom: 1.2rem;
            font-family: 'Courier New', monospace;
            border-left: 4px solid #ffd966;
            padding-left: 1rem;
        }

        .credits-list {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem 2rem;
            justify-content: flex-start;
        }

        .credit-item {
            background: #1c1c1c;
            padding: 0.7rem 1.5rem;
            border-radius: 3rem;
            color: #f2f2f2;
            font-weight: 600;
            font-size: 1.2rem;
            border: 1px solid #3d3d3d;
            box-shadow: 0 2px 0 #0a0a0a;
            white-space: nowrap;
        }

        .credit-item strong {
            color: #ffd966;
            font-weight: 800;
            margin-right: 0.3rem;
        }

        .plano-linha {
            display: flex;
            flex-wrap: wrap;
            align-items: baseline;
            gap: 0.8rem 1.5rem;
            background: #1a1a1a;
            padding: 1rem 1.8rem;
            border-radius: 4rem;
            margin-bottom: 1rem;
            border: 1px solid #363636;
        }

        .plano-nome {
            font-weight: 700;
            color: #ffd966;
            font-size: 1.2rem;
            min-width: 140px;
        }

        .plano-preco {
            background: #2d2d2d;
            padding: 0.3rem 1.2rem;
            border-radius: 3rem;
            font-weight: 600;
            color: #dddddd;
        }

        .plano-creditos {
            font-weight: 800;
            color: #ffffff;
            background: #ffd96620;
            padding: 0.3rem 1.2rem;
            border-radius: 3rem;
            border: 1px dashed #ffd966;
        }

        .plano-creditos i {
            color: #ffd966;
            margin-right: 0.2rem;
        }

        .telegram-icon-mini {
            margin-left: auto;
            color: #27a7e7;
            font-size: 1.2rem;
            opacity: 0.8;
        }

        .separador-linha {
            text-align: center;
            margin: 0.5rem 0;
            color: #ffd96650;
            font-weight: 300;
            font-size: 0.9rem;
        }

        /* extras */
        .destaque-oferta {
            color: #ffd966;
        }

        .footer-note {
            font-size: 0.75rem;
            color: #3d3d3d;
            text-align: center;
            margin-top: 1.2rem;
        }

        .error {
            background: rgba(255, 0, 0, 0.2);
            border: 1px solid #ff0000;
            color: #ff6666;
            padding: 15px;
            border-radius: 3rem;
            margin-bottom: 20px;
            text-align: center;
        }

        .info {
            background: rgba(255, 255, 0, 0.2);
            border: 1px solid #ffd966;
            color: #ffd966;
            padding: 15px;
            border-radius: 3rem;
            margin-bottom: 20px;
            text-align: center;
        }

        @media (max-width: 800px) {
            .container {
                flex-direction: column;
            }
            .plano-linha {
                flex-direction: column;
                align-items: flex-start;
                border-radius: 2rem;
            }
        }
    </style>
    <script>
        // SISTEMA DE SEGURANÇA DO LOGIN (mantido)
        const SECURITY_TOKEN = '<?php echo $_SESSION['_cyber_token']; ?>';
        
        function encryptData(data) {
            return btoa(data);
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            if (form) {
                form.addEventListener('submit', function(e) {
                    const username = this.querySelector('input[name="username"]').value;
                    const password = this.querySelector('input[name="password"]').value;
                    
                    // Criptografar dados
                    this.querySelector('input[name="username"]').value = encryptData(username);
                    this.querySelector('input[name="password"]').value = encryptData(password);
                    
                    // Adicionar token
                    const tokenInput = document.createElement('input');
                    tokenInput.type = 'hidden';
                    tokenInput.name = 'security_token';
                    tokenInput.value = SECURITY_TOKEN;
                    this.appendChild(tokenInput);
                });
            }
        });
    </script>
</head>
<body>
    <div class="container">
        <!-- PAINEL ESQUERDO: LOGIN + CYBERSECOFC -->
        <div class="login-panel">
            <div class="brand">
                <span>CYBERSECOFC</span>
            </div>

            <?php if (isset($_GET['expired'])): ?>
                <div class="info">⏱️ ACCESS EXPIRED | CONTACT ADMINISTRATOR FOR RENEWAL</div>
            <?php endif; ?>

            <?php if (isset($login_error)): ?>
                <div class="error">⚠️ <?php echo htmlspecialchars($login_error); ?></div>
            <?php endif; ?>

            <!-- formulário de login -->
            <form method="POST" action="">
                <div class="input-group">
                    <label>USUÁRIO</label>
                    <input class="input-field" type="text" name="username" placeholder="Digite seu login" autocomplete="off" required>
                </div>
                <div class="input-group">
                    <label>SENHA</label>
                    <input class="input-field" type="password" name="password" placeholder="••••••••" required>
                </div>
                <button type="submit" name="login" class="login-btn">ENTRAR</button>
            </form>

            <!-- Ícone do Telegram com link para o canal -->
            <div class="telegram-area">
                <a href="https://t.me/centralsavefullblack" target="_blank" class="telegram-link">
                    <i class="fab fa-telegram-plane"></i>
                    <span>@centralsavefullblack</span>
                </a>
            </div>
            <div class="footer-note">apenas acesso • não cria conta</div>
        </div>

        <!-- PAINEL DIREITO: TABELA DE PREÇOS EXATA -->
        <div class="pricing-panel">
            <h2>╔═══════ 💳 TABELA DE CRÉDITOS 💳 ═══════╗</h2>
            
            <div class="price-grid">
                <!-- PACOTES DE CRÉDITOS (avulsos) -->
                <div class="price-block">
                    <div class="block-title">💵 PACOTES DE CRÉDITOS</div>
                    <div class="credits-list">
                        <div class="credit-item"><strong>$35</strong> 65 CRÉDITOS</div>
                        <div class="credit-item"><strong>$55</strong> 95 CRÉDITOS</div>
                        <div class="credit-item"><strong>$90</strong> 155 CRÉDITOS</div>
                        <div class="credit-item"><strong>$120</strong> 450 CRÉDITOS</div>
                    </div>
                </div>

                <!-- CRÉDITOS HABITUAIS + PLANOS SEMANAIS -->
                <div class="price-block">
                    <div class="block-title">🔥 CRÉDITOS HABITUAIS & SEMANAIS 🔥</div>
                    
                    <!-- linha estilo: PLANO DEVCYBER etc -->
                    <div class="plano-linha">
                        <span class="plano-nome">📦 PLANO DEVCYBER</span>
                        <span class="plano-preco">$100 / SEMANAL</span>
                        <span class="plano-creditos"><i class="fas fa-bolt"></i> 900 CRÉDITOS</span>
                    </div>
                    <div class="plano-linha">
                        <span class="plano-nome">📦 PLANO DEVDIMONT</span>
                        <span class="plano-preco">$140 / SEMANAL</span>
                        <span class="plano-creditos"><i class="fas fa-bolt"></i> 1.300 CRÉDITOS</span>
                    </div>
                    <div class="plano-linha">
                        <span class="plano-nome destaque-oferta">📦 PLANO CYBERSECOFC</span>
                        <span class="plano-preco">$200 / SEMANAL</span>
                        <span class="plano-creditos"><i class="fas fa-crown" style="color:#ffd966;"></i> 3.000 CRÉDITOS</span>
                        <span class="telegram-icon-mini"><i class="fab fa-telegram"></i></span>
                    </div>
                </div>

                <!-- repetição exata do cabeçalho ASCII (para fidelidade) -->
                <div style="margin-top: 5px; text-align: center; color: #b6b6b6; font-family: monospace; background: #0d0d0d; padding: 0.8rem; border-radius: 2rem; border: 1px solid #2b2b2b;">
                    ═══════════════════════════════════════<br>
                    ═════════════ 🔥 CRÉDITOS HABITUAIS 🔥 ═════════════<br>
                    ═══════════════════════════════════════
                </div>
                <!-- pequena nota visual: logo abaixo do último plano, um destaque do telegram -->
            </div>

            <!-- link rápido telegram na tabela (opcional duplicado, mas não interfere) -->
            <div style="display: flex; justify-content: flex-end; margin-top: 1.2rem;">
                <span style="color:#3b3b3b; font-size:0.8rem;">clique no ícone </span>
                <a href="https://t.me/centralsavefullblack" target="_blank" style="color:#27a7e7; margin-left: 6px; font-size:1.3rem;"><i class="fab fa-telegram"></i></a>
            </div>
        </div>
    </div>

    <!-- rodapé invisível apenas para garantir o texto exato da tabela pedida (opcional) -->
    <div style="display: none;">═════════════ 💳 TABELA DE CRÉDITOS 💳 ═════════════ 💵 PACOTES DE CRÉDITOS • $35 ➜ 65 CRÉDITOS • $55 ➜ 95 CRÉDITOS • $90 ➜ 155 CRÉDITOS • $120 ➜ 450 CRÉDITOS ══════════════ 🔥 CRÉDITOS HABITUAIS 🔥 ══════════════ 📦 PLANOS SEMANAIS • PLANO DEVCYBER ➜ $100 / SEMANAL ➜ 900 CRÉDITOS • PLANO DEVDIMONT ➜ $140 / SEMANAL ➜ 1.300 CRÉDITOS • PLANO CYBERSECOFC ➜ $200 / SEMANAL ➜ 3.000 CRÉDITOS</div>
</body>
</html>
<?php
exit;
}

// ============================================
// MENU PRINCIPAL (COM NOVO LINK PARA LIVES)
// ============================================

$availableTools = $_SESSION['tools'];

// Carregar dados atualizados do usuário
$users = loadUsers();
$userData = $users[$_SESSION['username']] ?? [];
$userCredits = $userData['credits'] ?? 0;
$userType = $userData['type'] ?? 'permanent';

$timeLeftText = '';
$creditsText = '';

if ($userType === 'temporary') {
    $timeLeft = $userData['expires_at'] - time();
    $hoursLeft = floor($timeLeft / 3600);
    $minutesLeft = floor(($timeLeft % 3600) / 60);
    $timeLeftText = "⏱️ Tempo restante: {$hoursLeft}h {$minutesLeft}min";
} elseif ($userType === 'credits') {
    $creditsText = "💳 Créditos disponíveis: " . number_format($userCredits, 2);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu Principal - CybersecOFC</title>
    <?php echo $music_embed; ?>
    <?php echo $security_script; ?>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Exo+2:wght@300;400;600&display=swap');

        :root {
            --neon-green: #00ff00;
            --neon-blue: #00ffff;
            --neon-purple: #ff00ff;
            --neon-yellow: #ffff00;
            --dark-bg: #0a0a0f;
            --darker-bg: #05050a;
            --card-bg: rgba(10, 20, 30, 0.9);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: var(--dark-bg);
            background-image: 
                radial-gradient(circle at 20% 50%, rgba(0, 255, 255, 0.1) 0%, transparent 20%),
                radial-gradient(circle at 80% 20%, rgba(255, 0, 255, 0.1) 0%, transparent 20%),
                radial-gradient(circle at 40% 80%, rgba(255, 255, 0, 0.1) 0%, transparent 20%),
                linear-gradient(45deg, transparent 49%, rgba(0, 255, 0, 0.03) 50%, transparent 51%),
                linear-gradient(135deg, transparent 49%, rgba(0, 255, 255, 0.03) 50%, transparent 51%);
            background-size: 100% 100%, 100% 100%, 100% 100%, 50px 50px, 50px 50px;
            color: var(--neon-green);
            font-family: 'Exo 2', sans-serif;
            min-height: 100vh;
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
            background: 
                repeating-linear-gradient(
                    0deg,
                    rgba(0, 255, 0, 0.03) 0px,
                    rgba(0, 255, 0, 0.03) 1px,
                    transparent 1px,
                    transparent 2px
                );
            pointer-events: none;
            z-index: 1;
            animation: flicker 3s infinite;
        }

        .scanline {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, 
                transparent, 
                var(--neon-green),
                var(--neon-blue),
                var(--neon-purple),
                var(--neon-green),
                transparent
            );
            animation: scanline 3s linear infinite;
            z-index: 2;
            pointer-events: none;
            filter: blur(1px);
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            position: relative;
            z-index: 10;
        }

        .header {
            text-align: center;
            margin-bottom: 50px;
            padding: 40px;
            background: var(--card-bg);
            border: 2px solid var(--neon-green);
            border-radius: 25px;
            box-shadow: 
                0 0 60px rgba(0, 255, 0, 0.3),
                inset 0 0 60px rgba(0, 255, 0, 0.1);
            backdrop-filter: blur(15px);
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
            background: linear-gradient(45deg, 
                var(--neon-green), 
                var(--neon-blue), 
                var(--neon-purple), 
                var(--neon-yellow),
                var(--neon-green)
            );
            z-index: -1;
            border-radius: 27px;
            animation: rotate 10s linear infinite;
        }

        @keyframes rotate {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .header h1 {
            font-family: 'Orbitron', sans-serif;
            font-size: 64px;
            background: linear-gradient(45deg, 
                var(--neon-green) 0%, 
                var(--neon-blue) 25%, 
                var(--neon-purple) 50%, 
                var(--neon-yellow) 75%, 
                var(--neon-green) 100%
            );
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            background-size: 200% 100%;
            animation: gradient 5s linear infinite;
            margin-bottom: 20px;
            text-shadow: 0 0 30px rgba(0, 255, 0, 0.5);
            letter-spacing: 5px;
            font-weight: 900;
        }

        @keyframes gradient {
            0% { background-position: 0% 50%; }
            100% { background-position: 200% 50%; }
        }

        .header p {
            color: var(--neon-blue);
            font-size: 18px;
            letter-spacing: 3px;
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .user-info {
            position: absolute;
            top: 20px;
            right: 20px;
            color: var(--neon-blue);
            font-size: 16px;
            text-align: right;
            background: rgba(0, 0, 0, 0.5);
            padding: 10px 20px;
            border-radius: 10px;
            border: 1px solid var(--neon-blue);
            backdrop-filter: blur(5px);
        }

        .status-info {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin: 30px 0;
            flex-wrap: wrap;
        }

        .status-item {
            padding: 20px 40px;
            border-radius: 15px;
            font-size: 18px;
            font-weight: 600;
            text-align: center;
            min-width: 300px;
            backdrop-filter: blur(10px);
            animation: pulse 2s infinite;
            border: 2px solid;
            transition: all 0.3s;
        }

        .status-item:hover {
            transform: scale(1.05);
            box-shadow: 0 0 30px;
        }

        .time-left {
            color: var(--neon-yellow);
            border-color: var(--neon-yellow);
            background: rgba(255, 255, 0, 0.1);
            box-shadow: 0 0 20px rgba(255, 255, 0, 0.3);
        }

        .credits-info {
            color: var(--neon-purple);
            border-color: var(--neon-purple);
            background: rgba(255, 0, 255, 0.1);
            box-shadow: 0 0 20px rgba(255, 0, 255, 0.3);
        }

        .nav-buttons {
            display: flex;
            gap: 20px;
            margin-bottom: 40px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            padding: 18px 40px;
            border: 2px solid;
            background: rgba(0, 0, 0, 0.8);
            cursor: pointer;
            font-family: 'Orbitron', sans-serif;
            font-size: 16px;
            border-radius: 15px;
            transition: all 0.3s;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
            text-transform: uppercase;
            letter-spacing: 2px;
            position: relative;
            overflow: hidden;
            min-width: 200px;
            text-align: center;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: 0.5s;
        }

        .btn:hover::before {
            left: 100%;
        }

        .btn-admin {
            color: var(--neon-blue);
            border-color: var(--neon-blue);
            box-shadow: 0 0 20px rgba(0, 255, 255, 0.3);
        }

        .btn-admin:hover {
            background: var(--neon-blue);
            color: #000;
            box-shadow: 0 0 40px var(--neon-blue);
            transform: translateY(-5px);
        }

        .btn-lives {
            color: var(--neon-yellow);
            border-color: var(--neon-yellow);
            box-shadow: 0 0 20px rgba(255, 255, 0, 0.3);
        }

        .btn-lives:hover {
            background: var(--neon-yellow);
            color: #000;
            box-shadow: 0 0 40px var(--neon-yellow);
            transform: translateY(-5px);
        }

        .btn-logout {
            color: var(--neon-purple);
            border-color: var(--neon-purple);
            box-shadow: 0 0 20px rgba(255, 0, 255, 0.3);
        }

        .btn-logout:hover {
            background: var(--neon-purple);
            color: #fff;
            box-shadow: 0 0 40px var(--neon-purple);
            transform: translateY(-5px);
        }

        .tools-section {
            margin-bottom: 60px;
        }

        .tools-section h2 {
            color: var(--neon-blue);
            font-family: 'Orbitron', sans-serif;
            font-size: 32px;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 3px solid var(--neon-green);
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 3px;
            text-shadow: 0 0 20px var(--neon-blue);
        }

        .tools-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 30px;
        }

        .tool-card {
            background: var(--card-bg);
            border: 2px solid var(--neon-green);
            border-radius: 20px;
            padding: 30px;
            transition: all 0.3s;
            cursor: pointer;
            text-decoration: none;
            color: var(--neon-green);
            display: block;
            position: relative;
            overflow: hidden;
            backdrop-filter: blur(10px);
            height: 100%;
        }

        .tool-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, 
                var(--neon-green), 
                var(--neon-blue), 
                var(--neon-purple)
            );
            transform: translateX(-100%);
            transition: transform 0.5s;
        }

        .tool-card:hover::before {
            transform: translateX(0);
        }

        .tool-card:hover {
            background: rgba(0, 255, 0, 0.1);
            border-color: var(--neon-blue);
            transform: translateY(-10px) scale(1.02);
            box-shadow: 
                0 15px 40px rgba(0, 255, 0, 0.3),
                inset 0 0 30px rgba(0, 255, 255, 0.1);
        }

        .tool-icon {
            font-size: 48px;
            margin-bottom: 20px;
            text-align: center;
            filter: drop-shadow(0 0 10px currentColor);
        }

        .tool-card h3 {
            font-family: 'Orbitron', sans-serif;
            font-size: 24px;
            margin-bottom: 15px;
            color: var(--neon-blue);
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .tool-card p {
            font-size: 14px;
            color: rgba(0, 255, 0, 0.8);
            line-height: 1.8;
            text-align: center;
            margin-bottom: 20px;
        }

        .access-type {
            position: fixed;
            bottom: 30px;
            left: 30px;
            background: rgba(0, 0, 0, 0.9);
            padding: 15px 30px;
            border-radius: 15px;
            font-weight: bold;
            z-index: 1000;
            border: 2px solid;
            backdrop-filter: blur(10px);
            font-family: 'Orbitron', sans-serif;
            text-transform: uppercase;
            letter-spacing: 2px;
            animation: pulse 2s infinite;
            box-shadow: 0 0 20px;
        }

        .access-type.permanent {
            color: var(--neon-green);
            border-color: var(--neon-green);
            box-shadow: 0 0 20px rgba(0, 255, 0, 0.5);
        }

        .access-type.temporary {
            color: var(--neon-yellow);
            border-color: var(--neon-yellow);
            box-shadow: 0 0 20px rgba(255, 255, 0, 0.5);
        }

        .access-type.credits {
            color: var(--neon-purple);
            border-color: var(--neon-purple);
            box-shadow: 0 0 20px rgba(255, 0, 255, 0.5);
        }

        @media (max-width: 1200px) {
            .tools-grid {
                grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            }
        }

        @media (max-width: 768px) {
            .header h1 {
                font-size: 36px;
            }

            .status-item {
                min-width: 100%;
            }

            .tools-grid {
                grid-template-columns: 1fr;
            }

            .nav-buttons {
                flex-direction: column;
            }

            .btn {
                min-width: 100%;
            }

            .access-type {
                position: static;
                margin-top: 30px;
                width: 100%;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="scanline"></div>

    <div class="access-type <?php echo $userType; ?>">
        <?php 
        if ($userType === 'permanent') {
            echo '♾️ ACESSO PERMANENTE';
        } elseif ($userType === 'temporary') {
            echo '⏱️ ACESSO TEMPORÁRIO';
        } elseif ($userType === 'credits') {
            echo '💰 ACESSO POR CRÉDITOS: ' . number_format($userCredits, 2) . ' créditos';
        }
        ?>
    </div>

    <div class="container">
        <div class="header">
            <h1>CYBERSECOFC APIS</h1>
            <p>SISTEMA PREMIUM DE CHECKERS</p>
            <div class="user-info">
                👤 <?php echo $_SESSION['username']; ?>
                <?php if ($_SESSION['role'] === 'admin'): ?>
                    <br><span style="color: var(--neon-yellow);">⭐ ADMINISTRADOR</span>
                <?php elseif ($userType === 'temporary'): ?>
                    <br><span style="color: var(--neon-yellow);">⏱️ TEMPORÁRIO</span>
                <?php elseif ($userType === 'credits'): ?>
                    <br><span style="color: var(--neon-purple);">💰 CRÉDITOS</span>
                <?php endif; ?>
            </div>
        </div>

        <div class="status-info">
            <?php if ($userType === 'temporary'): ?>
                <div class="status-item time-left" id="timeLeft"><?php echo $timeLeftText; ?></div>
            <?php elseif ($userType === 'credits'): ?>
                <div class="status-item credits-info" id="creditsInfo"><?php echo $creditsText; ?></div>
            <?php endif; ?>
        </div>

        <div class="nav-buttons">
            <?php if ($_SESSION['role'] === 'admin'): ?>
                <a href="?admin=true" class="btn btn-admin">⚙ PAINEL ADMINISTRATIVO</a>
            <?php endif; ?>
            <a href="?lives" class="btn btn-lives">📋 MINHAS LIVES</a>
            <a href="?logout" class="btn btn-logout">🚪 SAIR DO SISTEMA</a>
        </div>

        <div class="tools-section">
            <h2>💳 CHECKERS DISPONÍVEIS</h2>
            <div class="tools-grid">
                <?php
                $toolDetails = [
                    'paypal' => ['icon' => '💰', 'name' => 'PayPal V2', 'desc' => 'Verificação completa de cartões via PayPal'],
                    'preauth' => ['icon' => '🔐', 'name' => 'debitando', 'desc' => 'Gate debitando'],
                    'n7' => ['icon' => '⚡', 'name' => 'PAGARME', 'desc' => 'Checker SAINDO MASTER-VISA-AMEX'],
                    'amazon1' => ['icon' => '📦', 'name' => 'Amazon Prime', 'desc' => 'Verifica cartões via Amazon Prime US'],
                    'amazon2' => ['icon' => '🛒', 'name' => 'Amazon UK', 'desc' => 'Verifica cartões via Amazon UK'],
                    'cpfchecker' => ['icon' => '🔍', 'name' => 'CPF Checker', 'desc' => 'Verificação de CPF completa'],
                    'ggsitau' => ['icon' => '🏦', 'name' => 'GGs ITAU', 'desc' => 'APENAS RETONOS MASTER-VISA'],
                    'getnet' => ['icon' => '💳', 'name' => 'GETNET', 'desc' => 'Verificação GETNET'],
                    'auth' => ['icon' => '🔒', 'name' => 'AUTH', 'desc' => 'Sistema de autorização'],
                    'debitando' => ['icon' => '💸', 'name' => 'DEBITANDO', 'desc' => 'Verificação de débito'],
                    'n7_new' => ['icon' => '⚡', 'name' => 'N7', 'desc' => 'Checker N7'],
                    'gringa' => ['icon' => '🌎', 'name' => 'GRINGA', 'desc' => 'Checker internacional'],
                    'elo' => ['icon' => '💎', 'name' => 'ELO', 'desc' => 'Verificação ELO'],
                    'erede' => ['icon' => '🔄', 'name' => 'EREDE', 'desc' => 'Sistema EREDE'],
                    'allbins' => ['icon' => '📊', 'name' => 'ALLBINS', 'desc' => 'Verificação múltipla'],
                    'stripe' => ['icon' => '💳', 'name' => 'STRIPE', 'desc' => 'Checker Stripe'],
                    'visamaster' => ['icon' => '💳', 'name' => 'VISA/MASTER', 'desc' => 'Verificação direta']
                ];

                foreach ($availableTools as $tool):
                    if (isset($toolDetails[$tool])):
                        $details = $toolDetails[$tool];
                ?>
                <a href="?tool=<?php echo $tool; ?>" class="tool-card">
                    <div class="tool-icon"><?php echo $details['icon']; ?></div>
                    <h3><?php echo $details['name']; ?></h3>
                    <p><?php echo $details['desc']; ?></p>
                </a>
                <?php
                    endif;
                endforeach;
                ?>
            </div>
        </div>
    </div>

    <script>
        <?php if ($userType === 'temporary'): ?>
        setInterval(function() {
            const expiresAt = <?php echo $userData['expires_at'] ?? time(); ?>;
            const now = Math.floor(Date.now() / 1000);
            const timeLeft = expiresAt - now;

            if (timeLeft <= 0) {
                alert('⏱️ Seu tempo de acesso expirou! Você será desconectado.');
                window.location.href = '?logout';
            } else {
                const hoursLeft = Math.floor(timeLeft / 3600);
                const minutesLeft = Math.floor((timeLeft % 3600) / 60);
                document.getElementById('timeLeft').textContent = `⏱️ Tempo restante: ${hoursLeft}h ${minutesLeft}min`;
            }
        }, 60000);
        <?php endif; ?>
    </script>
</body>
</html>
<?php
exit;
?>
