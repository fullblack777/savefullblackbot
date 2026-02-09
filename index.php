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
    
    // Lista de chats/grupos (você precisa adicionar os chat_ids reais aqui)
    // Para testes, use um grupo seu
    $chats = ['-1001234567890']; // SUBSTITUA pelo seu chat_id real
    
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

// DETECTAR PROXY E FERRAMENTAS DE HACKING (APENAS SE DETECTAR TENTATIVAS REAIS)
if (isset($_SERVER['HTTP_USER_AGENT'])) {
    $user_agent = strtolower($_SERVER['HTTP_USER_AGENT']);
    
    $is_browser = (
        strpos($user_agent, 'mozilla') !== false ||
        strpos($user_agent, 'chrome') !== false ||
        strpos($user_agent, 'safari') !== false ||
        strpos($user_agent, 'firefox') !== false ||
        strpos($user_agent, 'edge') !== false ||
        strpos($user_agent, 'opera') !== false
    );
    
    if (!$is_browser) {
        $blacklisted_agents = [
            'sqlmap', 'nikto', 'burp', 'zap', 'hydra', 'metasploit',
            'nessus', 'openvas', 'acunetix', 'netsparker'
        ];
        
        foreach ($blacklisted_agents as $agent) {
            if (stripos($user_agent, $agent) !== false) {
                header('HTTP/1.1 403 Forbidden');
                exit('Access Denied');
            }
        }
    }
}

// ============================================
// SEU CÓDIGO ORIGINAL (MANTIDO INTACTO)
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
    file_put_contents($users_file, json_encode($users, JSON_PRETTY_PRINT));
}

// Função para carregar usuários
function loadUsers() {
    global $users_file;
    if (!file_exists($users_file)) {
        return [];
    }
    $data = file_get_contents($users_file);
    return json_decode($data, true) ?: [];
}

// Função para salvar usuários
function saveUsers($users) {
    global $users_file;
    file_put_contents($users_file, json_encode($users, JSON_PRETTY_PRINT));
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
    $username = trim($_POST['username'] ?? '');
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
if (isset($_POST['add_permanent_user']) && isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    $new_username = trim($_POST['new_username'] ?? '');
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
        
        $success_message = "Usuário permanente '$new_username' criado com sucesso!";
    }
}

// Processar aluguel por hora (apenas admin)
if (isset($_POST['add_rental_user']) && isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    $rental_username = trim($_POST['rental_username'] ?? '');
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

        $success_message = "Acesso temporário criado para '$rental_username' por $rental_hours hora(s)!";
    }
}

// Processar adição de usuário por créditos (apenas admin)
if (isset($_POST['add_credit_user']) && isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    $credit_username = trim($_POST['credit_username'] ?? '');
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

        $success_message = "Usuário por créditos '$credit_username' criado com $credit_amount créditos!";
    }
}

// Processar recarga de créditos (apenas admin)
if (isset($_POST['add_credits']) && isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    $recharge_username = trim($_POST['recharge_username'] ?? '');
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
if (isset($_POST['remove_user']) && isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    $remove_username = trim($_POST['remove_username'] ?? '');

    if ($remove_username !== 'save' && $remove_username !== '') {
        $users = loadUsers();
        if (isset($users[$remove_username])) {
            unset($users[$remove_username]);
            saveUsers($users);
            
            // Enviar notificação via bot
            sendTelegramMessage("🗑️ USUÁRIO REMOVIDO\n👤 Usuário: <code>$remove_username</code>\n❌ Conta removida do sistema");
            
            $success_message = "Usuário '$remove_username' removido com sucesso!";
        }
    }
}

// Configuração do Bot (apenas admin)
if (isset($_POST['save_bot_token']) && isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    $bot_token = trim($_POST['bot_token'] ?? '');
    
    if (!empty($bot_token)) {
        if (strpos($bot_token, ':') === false) {
            $error_message = "Token inválido! Formato correto: 1234567890:ABCDEFGHIJKLMNOPQRSTUVWXYZ";
        } else {
            file_put_contents($bot_token_file, $bot_token);
            $success_message = "Token do bot salvo com sucesso!";
        }
    } else {
        $error_message = "Token não pode ser vazio!";
    }
}

if (isset($_POST['start_bot']) && isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    file_put_contents($bot_enabled_file, '1');
    
    // Enviar mensagem de inicialização
    sendTelegramMessage("🤖 BOT ONLINE\n✅ Sistema CybersecOFC ativado\n🔗 Acesso: https://apiscybersecofc.up.railway.app");
    
    $success_message = "Bot iniciado com sucesso!";
}

if (isset($_POST['stop_bot']) && isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    file_put_contents($bot_enabled_file, '0');
    $success_message = "Bot parado com sucesso!";
}

if (isset($_POST['send_broadcast']) && isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    $message = trim($_POST['broadcast_message'] ?? '');
    
    if (!empty($message)) {
        sendTelegramMessage("📢 MENSAGEM DO ADMINISTRADOR\n\n$message");
        $success_message = "Mensagem enviada para todos os grupos!";
    }
}

// ============================================
// PROCESSAMENTO DAS FERRAMENTAS (AJAX)
// ============================================

if (isset($_GET['action']) && $_GET['action'] === 'check' && isset($_GET['lista']) && isset($_GET['tool'])) {
    if (!isset($_SESSION['logged_in'])) {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Não autenticado']);
        exit;
    }

    // Verificar acesso do usuário
    $users = loadUsers();
    $username = $_SESSION['username'];
    
    if (!isset($users[$username])) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Usuário não encontrado']);
        exit;
    }
    
    $userData = $users[$username];
    
    // Verificar expiração
    if ($userData['type'] === 'temporary') {
        if (time() > $userData['expires_at']) {
            echo json_encode([
                'status' => 'error', 
                'message' => '⏱️ Seu tempo de acesso expirou! Entre em contato com o administrador.'
            ]);
            exit;
        }
    } elseif ($userData['type'] === 'credits') {
        if ($userData['credits'] < 0.05) {
            echo json_encode([
                'status' => 'error', 
                'message' => '💳 Créditos insuficientes! Você precisa de pelo menos 0.05 créditos. Seus créditos: ' . $userData['credits']
            ]);
            exit;
        }
    }

    $tool = $_GET['tool'];
    if (!in_array($tool, $_SESSION['tools'])) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Acesso negado para esta ferramenta']);
        exit;
    }

    error_reporting(0);
    ini_set('display_errors', 0);
    
    $lista = $_GET['lista'];
    ob_clean();

    try {
        // Mapear as ferramentas
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
            while (ob_get_level()) ob_end_clean();
            
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
                'ok', 'OK', 'Ok'
            ];
            
            foreach ($live_patterns as $pattern) {
                if (stripos($output, $pattern) !== false) {
                    $isLive = true;
                    break;
                }
            }
            
            // Se usuário for tipo créditos, descontar créditos
            if ($userData['type'] === 'credits') {
                $remainingCredits = deductCredits($username, $isLive);
                if ($remainingCredits !== false) {
                    $cost = $isLive ? '1.50' : '0.05';
                    $output .= "\n💳 Crédito usado: {$cost} | Restante: " . number_format($remainingCredits, 2);
                    
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
            
            // Retornar o output
            echo $output;
            
        } else {
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'Ferramenta não encontrada']);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Erro interno: ' . $e->getMessage()]);
    }

    exit;
}

// ============================================
// SISTEMA DE SEGURANÇA JAVASCRIPT MODIFICADO
// ============================================

$security_script = <<<'HTML'
<!-- SISTEMA DE SEGURANÇA CYBERSECOFC -->
<script>
(function() {
    // MENSAGEM NO CONSOLE
    console.log('%c🔒 SISTEMA PROTEGIDO POR @cybersecofc', 
        'color: #0f0; font-size: 16px; font-weight: bold;');
    
    // BLOQUEAR TECLAS DE DESENVOLVEDOR (MAS NÃO INTERFERIR NO SISTEMA)
    document.addEventListener('keydown', e => {
        const isDevKey = (
            e.keyCode === 123 || // F12
            (e.ctrlKey && e.shiftKey && e.keyCode === 73) || // Ctrl+Shift+I
            (e.ctrlKey && e.shiftKey && e.keyCode === 74) || // Ctrl+Shift+J
            (e.ctrlKey && e.shiftKey && e.keyCode === 67) || // Ctrl+Shift+C
            (e.ctrlKey && e.keyCode === 85) // Ctrl+U
        );
        
        if (isDevKey) {
            e.preventDefault();
            console.log('%c⚠️ Acesso ao console desabilitado por segurança', 'color: #ff0; font-size: 14px;');
            return false;
        }
    });
    
    // BLOQUEAR BOTÃO DIREITO (OPCIONAL)
    document.addEventListener('contextmenu', e => {
        console.log('%c⚠️ Botão direito desabilitado', 'color: #ff0; font-size: 14px;');
        e.preventDefault();
        return false;
    });
})();
</script>
HTML;

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
            --card-bg: rgba(10, 20, 30, 0.95);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        @keyframes scanline {
            0% { top: 0%; }
            100% { top: 100%; }
        }

        @keyframes pulse {
            0%, 100% { box-shadow: 0 0 20px var(--neon-green); }
            50% { box-shadow: 0 0 40px var(--neon-green); }
        }

        body {
            background: var(--dark-bg);
            background-image: 
                radial-gradient(circle at 20% 50%, rgba(0, 255, 255, 0.1) 0%, transparent 20%),
                radial-gradient(circle at 80% 20%, rgba(255, 0, 255, 0.1) 0%, transparent 20%);
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

        .scanline {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 2px;
            background: linear-gradient(90deg, 
                transparent, 
                var(--neon-green),
                var(--neon-blue),
                transparent
            );
            animation: scanline 3s linear infinite;
            z-index: 2;
            pointer-events: none;
        }

        .login-container {
            background: var(--card-bg);
            border: 2px solid var(--neon-green);
            border-radius: 15px;
            padding: 40px 30px;
            width: 100%;
            max-width: 500px;
            box-shadow: 
                0 0 30px rgba(0, 255, 0, 0.3),
                inset 0 0 30px rgba(0, 255, 0, 0.1);
            position: relative;
            z-index: 10;
            animation: pulse 3s infinite;
            backdrop-filter: blur(10px);
        }

        .terminal-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--neon-green);
        }

        .terminal-header h1 {
            font-family: 'Orbitron', sans-serif;
            font-size: 32px;
            color: var(--neon-green);
            text-shadow: 0 0 10px var(--neon-green);
            margin-bottom: 10px;
            letter-spacing: 2px;
        }

        .terminal-header .subtitle {
            color: var(--neon-blue);
            font-size: 12px;
            letter-spacing: 1px;
        }

        .features-section {
            margin: 25px 0;
            padding: 20px;
            background: rgba(0, 255, 0, 0.05);
            border: 1px solid var(--neon-green);
            border-radius: 10px;
        }

        .features-section h2 {
            color: var(--neon-blue);
            font-size: 16px;
            margin-bottom: 15px;
            text-align: center;
        }

        .features-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 15px;
            margin-top: 15px;
            font-size: 11px;
        }

        .feature-category h3 {
            color: var(--neon-purple);
            font-size: 13px;
            margin-bottom: 8px;
        }

        .feature-category ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .feature-category ul li {
            color: var(--neon-green);
            font-size: 10px;
            padding: 4px 0;
            padding-left: 15px;
            position: relative;
            line-height: 1.4;
        }

        .feature-category ul li::before {
            content: '▸';
            position: absolute;
            left: 0;
            color: var(--neon-blue);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group input {
            width: 100%;
            padding: 12px 15px;
            background: rgba(0, 0, 0, 0.8);
            border: 1px solid var(--neon-green);
            color: var(--neon-green);
            font-family: 'Exo 2', sans-serif;
            font-size: 14px;
            outline: none;
            transition: all 0.3s;
            border-radius: 8px;
        }

        .form-group input:focus {
            border-color: var(--neon-blue);
            box-shadow: 0 0 15px rgba(0, 255, 255, 0.5);
        }

        .btn-login {
            width: 100%;
            padding: 15px;
            background: linear-gradient(45deg, rgba(0, 255, 0, 0.1), rgba(0, 255, 255, 0.1));
            border: 2px solid var(--neon-green);
            color: var(--neon-green);
            font-family: 'Orbitron', sans-serif;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: bold;
            letter-spacing: 2px;
            text-transform: uppercase;
            border-radius: 8px;
            margin-top: 10px;
        }

        .btn-login:hover {
            background: linear-gradient(45deg, var(--neon-green), var(--neon-blue));
            color: #000;
            box-shadow: 0 0 25px rgba(0, 255, 0, 0.8);
        }

        .error {
            color: #ff0000;
            text-align: center;
            margin-bottom: 20px;
            padding: 12px;
            background: rgba(255, 0, 0, 0.1);
            border: 1px solid #ff0000;
            border-radius: 8px;
            font-size: 13px;
        }

        .info {
            color: var(--neon-yellow);
            text-align: center;
            margin-bottom: 20px;
            padding: 12px;
            background: rgba(255, 255, 0, 0.1);
            border: 1px solid var(--neon-yellow);
            border-radius: 8px;
            font-size: 13px;
        }

        .access-denied {
            color: rgba(255, 0, 0, 0.7);
            text-align: center;
            margin-top: 20px;
            font-size: 10px;
            letter-spacing: 1px;
        }

        @media (max-width: 768px) {
            .login-container {
                padding: 25px 20px;
                max-width: 95%;
            }
            
            .terminal-header h1 {
                font-size: 24px;
            }
            
            .features-section {
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="scanline"></div>

    <div class="login-container">
        <div class="terminal-header">
            <h1>█ CYBERSECOFC █</h1>
            <div class="subtitle">[ PREMIUM CHECKER SYSTEM ]</div>
        </div>

        <div class="features-section">
            <h2>⚡ CHECKERS DISPONÍVEIS</h2>
            <div class="features-grid">
                <div class="feature-category">
                    <h3>💳 GATES PRINCIPAIS</h3>
                    <ul>
                        <li>PAYPAL V2 - PAYPAL @CYBERSECOFC</li>
                        <li>PAGARME - VISA/MASTER/AMEX/ELO</li>
                        <li>CIELO - Gate cielo</li>
                        <li>GETNET - Verificação GETNET</li>
                        <li>AUTH - Sistema de autorização</li>
                    </ul>
                </div>
            </div>
        </div>

        <?php if (isset($_GET['expired'])): ?>
            <div class="info">⏱️ ACCESS EXPIRED | CONTACT ADMINISTRATOR</div>
        <?php endif; ?>

        <?php if (isset($login_error)): ?>
            <div class="error">⚠️ <?php echo htmlspecialchars($login_error); ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <input type="text" name="username" placeholder="USERNAME" required autofocus autocomplete="off">
            </div>
            <div class="form-group">
                <input type="password" name="password" placeholder="PASSWORD" required autocomplete="off">
            </div>
            <button type="submit" name="login" class="btn-login">
                ► AUTHENTICATE
            </button>
        </form>

        <div class="access-denied">
            UNAUTHORIZED ACCESS MONITORED
        </div>
    </div>
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
        @import url('https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&family=Exo+2:wght@300;400;600&display=swap');

        :root {
            --neon-green: #00ff00;
            --neon-blue: #00ffff;
            --neon-purple: #ff00ff;
            --neon-yellow: #ffff00;
            --dark-bg: #0a0a0f;
            --card-bg: rgba(10, 20, 30, 0.95);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: var(--dark-bg);
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
            margin-bottom: 30px;
            padding: 25px;
            background: var(--card-bg);
            border: 2px solid var(--neon-green);
            border-radius: 15px;
            box-shadow: 0 0 30px rgba(0, 255, 0, 0.2);
            backdrop-filter: blur(10px);
        }

        .header h1 {
            font-family: 'Orbitron', sans-serif;
            font-size: 32px;
            color: var(--neon-green);
            margin-bottom: 10px;
        }

        .nav-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 10px 25px;
            border: 2px solid;
            background: rgba(0, 0, 0, 0.8);
            cursor: pointer;
            font-family: 'Exo 2', sans-serif;
            font-size: 14px;
            border-radius: 8px;
            transition: all 0.3s;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            color: var(--neon-green);
            border-color: var(--neon-green);
        }

        .btn-primary:hover {
            background: var(--neon-green);
            color: #000;
        }

        .btn-danger {
            color: #ff0000;
            border-color: #ff0000;
        }

        .btn-danger:hover {
            background: #ff0000;
            color: #000;
        }

        .btn-warning {
            color: var(--neon-yellow);
            border-color: var(--neon-yellow);
        }

        .btn-warning:hover {
            background: var(--neon-yellow);
            color: #000;
        }

        .btn-bot {
            color: var(--neon-purple);
            border-color: var(--neon-purple);
        }

        .btn-bot:hover {
            background: var(--neon-purple);
            color: #fff;
        }

        .admin-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .admin-section {
            background: var(--card-bg);
            border: 2px solid var(--neon-green);
            border-radius: 12px;
            padding: 20px;
            backdrop-filter: blur(10px);
        }

        .admin-section h2 {
            color: var(--neon-blue);
            font-family: 'Orbitron', sans-serif;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--neon-green);
            font-size: 18px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            color: var(--neon-blue);
            margin-bottom: 5px;
            font-size: 13px;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px 12px;
            background: rgba(0, 0, 0, 0.8);
            border: 1px solid var(--neon-green);
            color: var(--neon-green);
            font-family: 'Exo 2', sans-serif;
            border-radius: 8px;
            font-size: 14px;
        }

        .checker-options {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
        }

        .checker-option {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 6px 10px;
            background: rgba(0, 255, 0, 0.1);
            border-radius: 6px;
            border: 1px solid var(--neon-green);
            font-size: 12px;
        }

        .users-list {
            margin-top: 15px;
        }

        .user-item {
            background: rgba(0, 255, 0, 0.05);
            border: 1px solid var(--neon-green);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .user-info strong {
            color: var(--neon-green);
            font-size: 16px;
            display: block;
            margin-bottom: 5px;
        }

        .type-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 15px;
            font-size: 10px;
            font-weight: bold;
            margin-left: 8px;
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

        .user-details div {
            color: var(--neon-blue);
            font-size: 12px;
            margin: 2px 0;
        }

        .success {
            background: rgba(0, 255, 0, 0.1);
            border: 1px solid var(--neon-green);
            color: var(--neon-green);
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }

        .error {
            background: rgba(255, 0, 0, 0.1);
            border: 1px solid #ff0000;
            color: #ff0000;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }

        .bot-status {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 15px;
            padding: 12px;
            border-radius: 8px;
            background: rgba(0, 0, 0, 0.3);
        }

        .bot-indicator {
            width: 10px;
            height: 10px;
            border-radius: 50%;
        }

        .bot-indicator.online {
            background: var(--neon-green);
            box-shadow: 0 0 10px var(--neon-green);
            animation: pulse 2s infinite;
        }

        .bot-indicator.offline {
            background: #ff0000;
        }

        .bot-controls {
            display: flex;
            gap: 8px;
            margin-top: 15px;
        }

        @media (max-width: 768px) {
            .admin-grid {
                grid-template-columns: 1fr;
            }
            
            .nav-buttons {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>⚙️ PAINEL ADMINISTRATIVO</h1>
            <p style="color: var(--neon-blue);">Bem-vindo, <?php echo htmlspecialchars($_SESSION['username']); ?>!</p>
        </div>

        <div class="nav-buttons">
            <a href="index.php" class="btn btn-primary">← Voltar ao Menu</a>
            <a href="#bot-section" class="btn btn-bot">🤖 Bot Telegram</a>
            <a href="?logout" class="btn btn-danger">Sair</a>
        </div>

        <?php if (isset($success_message)): ?>
            <div class="success"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="error"><?php echo htmlspecialchars($error_message); ?></div>
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
                            $checker_names = [
                                'paypal' => 'PayPal', 'preauth' => 'cielo', 'n7' => 'PAGARME',
                                'amazon1' => 'Amazon Prime', 'amazon2' => 'Amazon UK', 'cpfchecker' => 'CPF Checker',
                                'ggsitau' => 'GGs ITAU', 'getnet' => 'GETNET', 'auth' => 'AUTH',
                                'debitando' => 'DEBITANDO', 'n7_new' => 'N7', 'gringa' => 'GRINGA',
                                'elo' => 'ELO', 'erede' => 'EREDE', 'allbins' => 'ALLBINS',
                                'stripe' => 'STRIPE', 'visamaster' => 'VISA/MASTER'
                            ];
                            foreach ($all_checkers as $checker): 
                            ?>
                            <div class="checker-option">
                                <input type="checkbox" name="checkers[]" value="<?php echo $checker; ?>" id="perm_<?php echo $checker; ?>">
                                <label for="perm_<?php echo $checker; ?>"><?php echo $checker_names[$checker] ?? $checker; ?></label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <button type="submit" name="add_permanent_user" class="btn btn-primary">Adicionar</button>
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
                        <label>Horas:</label>
                        <input type="number" name="rental_hours" min="1" max="720" placeholder="Ex: 1, 24, 168" required>
                    </div>
                    <button type="submit" name="add_rental_user" class="btn btn-primary">Criar Acesso</button>
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
                        <label>Créditos:</label>
                        <input type="number" name="credit_amount" min="0.05" step="0.01" placeholder="Ex: 10.00" required>
                        <small style="color: var(--neon-blue);">LIVE: 1.50 | DIE: 0.05 créditos</small>
                    </div>
                    <button type="submit" name="add_credit_user" class="btn btn-warning">Criar Usuário</button>
                </form>
            </div>

            <div class="admin-section" id="bot-section">
                <h2>🤖 Bot Telegram</h2>
                
                <div class="bot-status">
                    <div class="bot-indicator <?php echo $bot_enabled ? 'online' : 'offline'; ?>"></div>
                    <span><?php echo $bot_enabled ? 'BOT ONLINE' : 'BOT OFFLINE'; ?></span>
                </div>
                
                <form method="POST">
                    <div class="form-group">
                        <label>Token do Bot:</label>
                        <input type="text" name="bot_token" value="<?php echo htmlspecialchars($bot_token); ?>" placeholder="1234567890:ABCDEFGHIJKLMNOPQRSTUVWXYZ">
                    </div>
                    
                    <div class="bot-controls">
                        <button type="submit" name="save_bot_token" class="btn btn-bot">💾 Salvar</button>
                        <button type="submit" name="start_bot" class="btn btn-primary" <?php echo empty($bot_token) ? 'disabled' : ''; ?>>▶ Iniciar</button>
                        <button type="submit" name="stop_bot" class="btn btn-danger">⏹ Parar</button>
                    </div>
                </form>
                
                <div class="form-group" style="margin-top: 15px;">
                    <label>Mensagem:</label>
                    <textarea name="broadcast_message" rows="3" placeholder="Digite a mensagem para broadcast"></textarea>
                    <button type="submit" name="send_broadcast" class="btn btn-warning" style="margin-top: 10px;">📢 Enviar</button>
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
                    <button type="submit" name="add_credits" class="btn btn-warning">Recarregar</button>
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
                                $expiresText = "⏳ Expira em: $expiresAt ($hoursLeft h $minutesLeft min)";
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
                    ?>
                        <div class="<?php echo $itemClass; ?>" style="<?php echo $isExpired ? 'opacity: 0.7; border-color: #ff0000;' : ''; ?>">
                            <div class="user-info">
                                <strong><?php echo htmlspecialchars($username); ?></strong>
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
                                    <?php if ($data['type'] === 'temporary'): ?>
                                        <div><?php echo $expiresText; ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php if ($username !== 'save'): ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="remove_username" value="<?php echo htmlspecialchars($username); ?>">
                                    <button type="submit" name="remove_user" class="btn btn-danger" onclick="return confirm('Remover usuário <?php echo htmlspecialchars($username); ?>?')">🗑</button>
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
    $isAmazonChecker = in_array($selectedTool, ['amazon1', 'amazon2']);

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
    <title><?php echo htmlspecialchars($toolName); ?> - CybersecOFC</title>
    <?php echo $music_embed; ?>
    <?php echo $security_script; ?>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&family=Exo+2:wght@300;400;600&display=swap');

        :root {
            --neon-green: #00ff00;
            --neon-blue: #00ffff;
            --neon-purple: #ff00ff;
            --neon-yellow: #ffff00;
            --dark-bg: #0a0a0f;
            --card-bg: rgba(10, 20, 30, 0.95);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: var(--dark-bg);
            color: var(--neon-green);
            font-family: 'Exo 2', sans-serif;
            padding: 15px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            text-align: center;
            margin-bottom: 25px;
            padding: 20px;
            background: var(--card-bg);
            border: 2px solid var(--neon-green);
            border-radius: 12px;
            box-shadow: 0 0 25px rgba(0, 255, 0, 0.2);
        }

        .header h1 {
            font-family: 'Orbitron', sans-serif;
            font-size: 24px;
            color: var(--neon-green);
            margin-bottom: 10px;
        }

        .user-info {
            position: absolute;
            top: 20px;
            right: 20px;
            color: var(--neon-blue);
            font-size: 14px;
        }

        .time-left, .credits-info {
            color: var(--neon-yellow);
            font-size: 14px;
            margin: 15px 0;
            text-align: center;
            padding: 12px;
            background: rgba(255, 255, 0, 0.1);
            border: 1px solid var(--neon-yellow);
            border-radius: 8px;
        }

        .credits-info {
            color: var(--neon-purple);
            border-color: var(--neon-purple);
            background: rgba(255, 0, 255, 0.1);
        }

        .nav-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 10px 20px;
            border: 2px solid;
            background: rgba(0, 0, 0, 0.8);
            cursor: pointer;
            font-family: 'Exo 2', sans-serif;
            font-size: 14px;
            border-radius: 8px;
            transition: all 0.3s;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            color: var(--neon-green);
            border-color: var(--neon-green);
        }

        .btn-primary:hover {
            background: var(--neon-green);
            color: #000;
        }

        .btn-start {
            color: var(--neon-green);
            border-color: var(--neon-green);
        }

        .btn-start:hover {
            background: var(--neon-green);
            color: #000;
        }

        .btn-stop {
            color: #ff0000;
            border-color: #ff0000;
        }

        .btn-stop:hover {
            background: #ff0000;
            color: #000;
        }

        .btn-clear {
            color: var(--neon-yellow);
            border-color: var(--neon-yellow);
        }

        .btn-clear:hover {
            background: var(--neon-yellow);
            color: #000;
        }

        .input-section {
            margin-bottom: 20px;
        }

        .input-section h3 {
            color: var(--neon-green);
            font-size: 16px;
            margin-bottom: 10px;
        }

        .input-section textarea {
            width: 100%;
            height: 150px;
            background: rgba(0, 0, 0, 0.8);
            color: var(--neon-green);
            border: 1px solid var(--neon-green);
            padding: 15px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            resize: vertical;
            border-radius: 8px;
        }

        .controls {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            align-items: center;
        }

        .loading {
            display: none;
            color: var(--neon-yellow);
            font-size: 14px;
            margin-left: 15px;
        }

        .loading.active {
            display: block;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            padding: 20px;
            background: var(--card-bg);
            border: 1px solid var(--neon-green);
            border-radius: 10px;
            margin-bottom: 25px;
        }

        .stat-item {
            text-align: center;
            padding: 15px;
            background: rgba(0, 0, 0, 0.3);
            border-radius: 8px;
        }

        .stat-label {
            color: var(--neon-blue);
            font-size: 12px;
            margin-bottom: 8px;
        }

        .stat-value {
            color: var(--neon-green);
            font-size: 24px;
            font-weight: bold;
            font-family: 'Orbitron', sans-serif;
        }

        .results-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }

        @media (max-width: 768px) {
            .results-container {
                grid-template-columns: 1fr;
            }
        }

        .result-box {
            border: 1px solid;
            padding: 20px;
            border-radius: 10px;
            min-height: 400px;
            max-height: 500px;
            overflow-y: auto;
        }

        .live-box {
            border-color: var(--neon-green);
            background: rgba(0, 255, 0, 0.05);
        }

        .live-box h3 {
            color: var(--neon-green);
            margin-bottom: 15px;
        }

        .die-box {
            border-color: #ff0000;
            background: rgba(255, 0, 0, 0.05);
        }

        .die-box h3 {
            color: #ff0000;
            margin-bottom: 15px;
        }

        .result-item {
            margin-bottom: 10px;
            padding: 10px;
            border-radius: 6px;
            font-size: 12px;
            font-family: 'Courier New', monospace;
        }

        .result-item.live {
            background: rgba(0, 255, 0, 0.1);
            color: var(--neon-green);
            border-left: 3px solid var(--neon-green);
        }

        .result-item.die {
            background: rgba(255, 0, 0, 0.1);
            color: #ff0000;
            border-left: 3px solid #ff0000;
        }

        .credits-counter {
            position: fixed;
            bottom: 15px;
            right: 15px;
            background: var(--card-bg);
            color: var(--neon-purple);
            padding: 10px 15px;
            border-radius: 8px;
            font-weight: bold;
            z-index: 1000;
            border: 1px solid var(--neon-purple);
            font-size: 14px;
        }

        .remaining-items {
            color: var(--neon-blue);
            font-size: 13px;
            margin-top: 10px;
            text-align: center;
            padding: 8px;
            background: rgba(0, 255, 255, 0.1);
            border: 1px solid var(--neon-blue);
            border-radius: 6px;
            display: none;
        }

        .remaining-items.active {
            display: block;
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
            <h1><?php echo htmlspecialchars($toolName); ?></h1>
            <div class="user-info">
                👤 <?php echo htmlspecialchars($_SESSION['username']); ?>
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
            <a href="index.php" class="btn btn-primary">← Voltar</a>
            <?php if ($_SESSION['role'] === 'admin'): ?>
                <a href="?admin=true" class="btn btn-primary">⚙ Admin</a>
            <?php endif; ?>
            <a href="?logout" class="btn btn-primary">🚪 Sair</a>
        </div>

        <div class="input-section">
            <h3>💳 Cole os cartões abaixo (um por linha)</h3>
            <textarea id="dataInput" placeholder="Formato: numero|mes|ano|cvv&#10;Exemplo: 4532015112830366|12|2027|123&#10;Máximo: 200 cartões"></textarea>
        </div>
        
        <div class="remaining-items" id="remainingItems">
            📊 Itens restantes: <span id="remainingCount">0</span>
        </div>

        <div class="controls">
            <button class="btn btn-start" onclick="startCheck()">▶ Iniciar</button>
            <button class="btn btn-stop" onclick="stopCheck()">⏹ Parar</button>
            <button class="btn btn-clear" onclick="clearAll()">🗑 Limpar</button>
            <div class="loading" id="loading">⏳ Processando...</div>
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
                'ok', 'OK', 'Ok'
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

            if (userType === 'credits' && currentCredits < 0.05) {
                alert('💳 Créditos insuficientes! Você precisa de pelo menos 0.05 créditos.');
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
                const url = `?action=check&tool=${toolName}&lista=${encodeURIComponent(item)}`;

                const response = await fetch(url);
                const text = await response.text();

                // Verificar se é um erro de segurança (agora mais preciso)
                if (text.includes('error') && text.includes('message') && 
                    (text.includes('pornolandia.xxx') || text.includes('Access Denied'))) {
                    console.error('Erro de segurança:', text);
                    stopCheck();
                    return;
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
                setTimeout(processNextItem, 2000);
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
        @import url('https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&family=Exo+2:wght@300;400;600&display=swap');

        :root {
            --neon-green: #00ff00;
            --neon-blue: #00ffff;
            --neon-purple: #ff00ff;
            --neon-yellow: #ffff00;
            --dark-bg: #0a0a0f;
            --card-bg: rgba(10, 20, 30, 0.95);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: var(--dark-bg);
            color: var(--neon-green);
            font-family: 'Exo 2', sans-serif;
            padding: 15px;
            min-height: 100vh;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            text-align: center;
            margin-bottom: 25px;
            padding: 20px;
            background: var(--card-bg);
            border: 2px solid var(--neon-green);
            border-radius: 12px;
            box-shadow: 0 0 25px rgba(0, 255, 0, 0.2);
        }

        .header h1 {
            font-family: 'Orbitron', sans-serif;
            font-size: 28px;
            color: var(--neon-green);
            margin-bottom: 10px;
        }

        .user-info {
            position: absolute;
            top: 20px;
            right: 20px;
            color: var(--neon-blue);
            font-size: 14px;
        }

        .status-info {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin: 20px 0;
            flex-wrap: wrap;
        }

        .status-item {
            padding: 15px 25px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            min-width: 250px;
            text-align: center;
        }

        .time-left {
            color: var(--neon-yellow);
            border: 1px solid var(--neon-yellow);
            background: rgba(255, 255, 0, 0.1);
        }

        .credits-info {
            color: var(--neon-purple);
            border: 1px solid var(--neon-purple);
            background: rgba(255, 0, 255, 0.1);
        }

        .nav-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 25px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            padding: 12px 25px;
            border: 2px solid;
            background: rgba(0, 0, 0, 0.8);
            cursor: pointer;
            font-family: 'Orbitron', sans-serif;
            font-size: 14px;
            border-radius: 8px;
            transition: all 0.3s;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
            min-width: 180px;
            text-align: center;
        }

        .btn-admin {
            color: var(--neon-blue);
            border-color: var(--neon-blue);
        }

        .btn-admin:hover {
            background: var(--neon-blue);
            color: #000;
        }

        .btn-logout {
            color: var(--neon-purple);
            border-color: var(--neon-purple);
        }

        .btn-logout:hover {
            background: var(--neon-purple);
            color: #fff;
        }

        .tools-section {
            margin-bottom: 30px;
        }

        .tools-section h2 {
            color: var(--neon-blue);
            font-family: 'Orbitron', sans-serif;
            font-size: 22px;
            margin-bottom: 20px;
            text-align: center;
        }

        .tools-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
        }

        .tool-card {
            background: var(--card-bg);
            border: 2px solid var(--neon-green);
            border-radius: 12px;
            padding: 20px;
            transition: all 0.3s;
            cursor: pointer;
            text-decoration: none;
            color: var(--neon-green);
            display: block;
            height: 100%;
        }

        .tool-card:hover {
            background: rgba(0, 255, 0, 0.1);
            border-color: var(--neon-blue);
            transform: translateY(-5px);
        }

        .tool-icon {
            font-size: 36px;
            margin-bottom: 15px;
            text-align: center;
        }

        .tool-card h3 {
            font-family: 'Orbitron', sans-serif;
            font-size: 18px;
            margin-bottom: 10px;
            color: var(--neon-blue);
            text-align: center;
        }

        .tool-card p {
            font-size: 12px;
            color: rgba(0, 255, 0, 0.8);
            line-height: 1.6;
            text-align: center;
        }

        .access-type {
            position: fixed;
            bottom: 15px;
            left: 15px;
            background: rgba(0, 0, 0, 0.9);
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: bold;
            z-index: 1000;
            border: 1px solid;
            font-family: 'Orbitron', sans-serif;
            font-size: 12px;
        }

        .access-type.permanent {
            color: var(--neon-green);
            border-color: var(--neon-green);
        }

        .access-type.temporary {
            color: var(--neon-yellow);
            border-color: var(--neon-yellow);
        }

        .access-type.credits {
            color: var(--neon-purple);
            border-color: var(--neon-purple);
        }

        @media (max-width: 768px) {
            .header h1 {
                font-size: 22px;
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
                margin-top: 20px;
                width: 100%;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    
    <div class="access-type <?php echo $userType; ?>">
        <?php 
        if ($userType === 'permanent') {
            echo '♾️ ACESSO PERMANENTE';
        } elseif ($userType === 'temporary') {
            echo '⏱️ ACESSO TEMPORÁRIO';
        } elseif ($userType === 'credits') {
            echo '💰 CRÉDITOS: ' . number_format($userCredits, 2);
        }
        ?>
    </div>
    
    <div class="container">
        <div class="header">
            <h1>CYBERSECOFC APIS</h1>
            <div class="user-info">
                👤 <?php echo htmlspecialchars($_SESSION['username']); ?>
                <?php if ($_SESSION['role'] === 'admin'): ?>
                    <br><span style="color: var(--neon-yellow);">⭐ ADMIN</span>
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
                <a href="?admin=true" class="btn btn-admin">⚙ PAINEL ADMIN</a>
            <?php endif; ?>
            <a href="?logout" class="btn btn-logout">🚪 SAIR</a>
        </div>

        <div class="tools-section">
            <h2>💳 CHECKERS DISPONÍVEIS</h2>
            <div class="tools-grid">
                <?php
                $toolDetails = [
                    'paypal' => ['icon' => '💰', 'name' => 'PayPal V2', 'desc' => 'Verificação PayPal'],
                    'preauth' => ['icon' => '🔐', 'name' => 'cielo', 'desc' => 'Gate cielo'],
                    'n7' => ['icon' => '⚡', 'name' => 'PAGARME', 'desc' => 'VISA/MASTER/AMEX/ELO'],
                    'amazon1' => ['icon' => '📦', 'name' => 'Amazon Prime', 'desc' => 'Amazon Prime US'],
                    'amazon2' => ['icon' => '🛒', 'name' => 'Amazon UK', 'desc' => 'Amazon UK'],
                    'cpfchecker' => ['icon' => '🔍', 'name' => 'CPF Checker', 'desc' => 'Verificação CPF'],
                    'ggsitau' => ['icon' => '🏦', 'name' => 'GGs ITAU', 'desc' => 'MASTER/VISA'],
                    'getnet' => ['icon' => '💳', 'name' => 'GETNET', 'desc' => 'Verificação GETNET'],
                    'auth' => ['icon' => '🔒', 'name' => 'AUTH', 'desc' => 'Sistema autorização'],
                    'debitando' => ['icon' => '💸', 'name' => 'DEBITANDO', 'desc' => 'Verificação débito'],
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
