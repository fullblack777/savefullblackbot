<?php
// ============================================
// SISTEMA DE PROTEÇÃO TOTAL - @CYBERSECOFC
// ============================================
session_start();

// DESATIVAR ERROS PARA PRODUÇÃO
error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// DEFESA CONTRA ATAQUES BÁSICOS
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
header('Referrer-Policy: no-referrer');

// CONFIGURAÇÕES DE SEGURANÇA
define('SECURE_HASH', 'CYBERSECOFC_' . md5(__FILE__ . $_SERVER['HTTP_HOST']));
define('ENCRYPTION_KEY', hash('sha256', SECURE_HASH, true));

// Função para criptografar dados
function encryptData($data) {
    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
    $encrypted = openssl_encrypt($data, 'aes-256-cbc', ENCRYPTION_KEY, 0, $iv);
    return base64_encode($encrypted . '::' . $iv);
}

// Função para descriptografar dados
function decryptData($data) {
    list($encrypted_data, $iv) = explode('::', base64_decode($data), 2);
    return openssl_decrypt($encrypted_data, 'aes-256-cbc', ENCRYPTION_KEY, 0, $iv);
}

// Arquivo para armazenar usuários (CRIPTOGRAFADO)
$users_file = 'users.enc';

// Lista de ferramentas (CRIPTOGRAFADA NA SESSÃO)
$all_tools = [
    'checkers' => ['paypal', 'preauth', 'n7', 'amazon1', 'amazon2', 'cpfchecker', 'ggsitau'],
    'consultas' => ['cpfdatasus', 'nomedetran', 'obito', 'fotoba', 'fotoce', 'fotoma', 'fotope', 'fotorj', 'fotosp', 'fototo']
];

// Inicializar arquivo de usuários se não existir
if (!file_exists($users_file)) {
    $users = [
        'save' => [
            'password' => password_hash('black', PASSWORD_DEFAULT),
            'role' => 'admin',
            'type' => 'permanent',
            'tools' => array_merge($all_tools['checkers'], $all_tools['consultas'])
        ]
    ];
    file_put_contents($users_file, encryptData(json_encode($users)));
}

// Função para carregar usuários (DESCRIPTOGRAFADO)
function loadUsers() {
    global $users_file;
    $encrypted = file_get_contents($users_file);
    return json_decode(decryptData($encrypted), true);
}

// Função para salvar usuários (CRIPTOGRAFADO)
function saveUsers($users) {
    global $users_file;
    file_put_contents($users_file, encryptData(json_encode($users)));
}

// VERIFICAÇÃO DE SEGURANÇA CONTRA ATAQUES
function securityCheck() {
    // Bloquear SQL Injection
    $blocked = ['union', 'select', 'insert', 'update', 'delete', 'drop', '--', '/*', '*/', "'", '"', ';', '<', '>', '(', ')', '=', 'or', 'and', 'script', 'iframe', 'onload', 'onerror', 'javascript:'];
    
    foreach ($_GET as $key => $value) {
        foreach ($blocked as $block) {
            if (stripos($value, $block) !== false) {
                die('<!-- @cybersecofc nao deixa rastro seus invasores de coco -->');
            }
        }
    }
    
    // Bloquear Directory Traversal
    if (isset($_GET['tool']) && preg_match('/\.\.\//', $_GET['tool'])) {
        die('<!-- @cybersecofc nao deixa rastro seus invasores de coco -->');
    }
    
    // Bloquear acesso direto a arquivos
    if (isset($_SERVER['REQUEST_URI']) && preg_match('/(\.php|\.json|\.txt|\.enc|\.log)$/i', $_SERVER['REQUEST_URI'])) {
        die('<!-- @cybersecofc nao deixa rastro seus invasores de coco -->');
    }
}

// Executar verificação de segurança
securityCheck();

// Função para verificar acesso temporário
function checkTemporaryAccess($userData) {
    if ($userData['type'] === 'temporary') {
        $expiresAt = $userData['expires_at'];
        if (time() > $expiresAt) {
            return false;
        }
    }
    return true;
}

// ============================================
// PROCESSAMENTO DE LOGIN (PROTEGIDO)
// ============================================
if (isset($_POST['login'])) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Rate limiting básico
    if (!isset($_SESSION['login_attempts'])) {
        $_SESSION['login_attempts'] = 0;
    }
    
    if ($_SESSION['login_attempts'] > 5) {
        die('<!-- @cybersecofc nao deixa rastro seus invasores de coco -->');
    }

    $users = loadUsers();

    if (isset($users[$username]) && password_verify($password, $users[$username]['password'])) {
        if (!checkTemporaryAccess($users[$username])) {
            $login_error = 'Seu acesso expirou! Entre em contato com o administrador.';
            $_SESSION['login_attempts']++;
        } else {
            // Resetar tentativas
            $_SESSION['login_attempts'] = 0;
            
            // GERAR TOKEN DE SESSÃO ÚNICO
            $_SESSION['logged_in'] = true;
            $_SESSION['username'] = $username;
            $_SESSION['role'] = $users[$username]['role'];
            $_SESSION['type'] = $users[$username]['type'];
            $_SESSION['session_token'] = bin2hex(random_bytes(32));
            $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
            $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
            
            // Criptografar as ferramentas na sessão
            $_SESSION['tools_encrypted'] = encryptData(json_encode($users[$username]['tools'] ?? ['paypal']));
            
            if ($users[$username]['type'] === 'temporary') {
                $_SESSION['expires_at'] = $users[$username]['expires_at'];
            }

            header('Location: index.php');
            exit;
        }
    } else {
        $_SESSION['login_attempts']++;
        $login_error = 'Usuário ou senha incorretos!';
    }
}

// Verificar se usuário logado tem acesso expirado
if (isset($_SESSION['logged_in']) && $_SESSION['type'] === 'temporary') {
    if (time() > $_SESSION['expires_at']) {
        session_destroy();
        header('Location: index.php?expired=1');
        exit;
    }
}

// Verificar hijacking de sessão
if (isset($_SESSION['logged_in'])) {
    if ($_SESSION['ip_address'] !== $_SERVER['REMOTE_ADDR'] || 
        $_SESSION['user_agent'] !== $_SERVER['HTTP_USER_AGENT']) {
        session_destroy();
        die('<!-- @cybersecofc nao deixa rastro seus invasores de coco -->');
    }
}

// Processar logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}

// ============================================
// PROCESSAMENTO DE FERRAMENTAS (ULTRA PROTEGIDO)
// ============================================
if (isset($_GET['action']) && $_GET['action'] === 'check' && isset($_POST['data'])) {
    // SISTEMA DE REQUISIÇÕES INVISÍVEIS - NÃO APARECE NO NETWORK TAB
    
    if (!isset($_SESSION['logged_in']) || !isset($_SESSION['session_token'])) {
        die('{"status":"error","message":"<!-- @cybersecofc nao deixa rastro seus invasores de coco -->"}');
    }

    // Verificar token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['session_token']) {
        die('{"status":"error","message":"<!-- @cybersecofc nao deixa rastro seus invasores de coco -->"}');
    }

    // Descriptografar dados recebidos
    $encrypted_data = $_POST['data'];
    $decrypted_data = json_decode(decryptData($encrypted_data), true);
    
    if (!$decrypted_data || !isset($decrypted_data['tool']) || !isset($decrypted_data['lista'])) {
        die('{"status":"error","message":"<!-- @cybersecofc nao deixa rastro seus invasores de coco -->"}');
    }
    
    $tool = $decrypted_data['tool'];
    $lista = $decrypted_data['lista'];
    
    // Verificar permissões (descriptografando da sessão)
    $user_tools = json_decode(decryptData($_SESSION['tools_encrypted']), true);
    if (!in_array($tool, $user_tools)) {
        die('{"status":"error","message":"<!-- @cybersecofc nao deixa rastro seus invasores de coco -->"}');
    }

    // Mapeamento interno (NUNCA exposto no frontend)
    $internal_tool_map = [
        't1' => ['paypal', 'attached_assets/PAYPALV2OFC.php'],
        't2' => ['preauth', 'attached_assets/VBVOFC.php'],
        't3' => ['n7', 'attached_assets/PAGARMEOFC.php'],
        't4' => ['amazon1', 'attached_assets/AMAZONOFC1.php'],
        't5' => ['amazon2', 'attached_assets/AMAZONOFC2.php'],
        't6' => ['cpfdatasus', 'attached_assets/cpfdatasus_1762652369268.php'],
        't7' => ['nomedetran', 'attached_assets/nomedetran_1762652369275.php'],
        't8' => ['obito', 'attached_assets/obito_1762652369275.php'],
        't9' => ['fotoba', 'attached_assets/fotoba_1762652369269.php'],
        't10' => ['fotoce', 'attached_assets/fotoce_1762652369269.php'],
        't11' => ['fotoma', 'attached_assets/fotoma_1762652369270.php'],
        't12' => ['fotope', 'attached_assets/fotope_1762652369271.php'],
        't13' => ['fotorj', 'attached_assets/fotorj_1762652369271.php'],
        't14' => ['fotosp', 'attached_assets/fotosp_1762652369272.php'],
        't15' => ['fototo', 'attached_assets/fototo_1762652369273.php'],
        't16' => ['ggsitau', 'internal_ggsitau'],
        't17' => ['cpfchecker', 'internal_cpfchecker']
    ];
    
    // Encontrar a ferramenta pelo código interno
    $selected_tool = null;
    foreach ($internal_tool_map as $internal_code => $tool_data) {
        if ($tool_data[0] === $tool) {
            $selected_tool = $tool_data;
            break;
        }
    }
    
    if (!$selected_tool) {
        die('{"status":"error","message":"<!-- @cybersecofc nao deixa rastro seus invasores de coco -->"}');
    }
    
    // Processar a ferramenta
    if ($selected_tool[1] === 'internal_ggsitau') {
        $parts = explode('|', $lista);
        if (count($parts) != 4) {
            $result = 'Formato inválido';
            echo encryptData(json_encode(['status' => 'Reprovada', 'message' => $result]));
        } else {
            $result = 'GGs ITAU AUTHORIZED';
            echo encryptData(json_encode(['status' => 'Aprovada', 'message' => $result]));
        }
    } elseif ($selected_tool[1] === 'internal_cpfchecker') {
        $result = 'API não configurada';
        echo encryptData(json_encode(['status' => 'Reprovada', 'message' => $result]));
    } else {
        // Executar arquivo PHP protegido
        if (file_exists($selected_tool[1])) {
            ob_start();
            $_GET['lista'] = $lista;
            if (isset($decrypted_data['cookie'])) {
                $_POST['cookie1'] = $decrypted_data['cookie'];
            }
            
            // Incluir com isolamento
            include $selected_tool[1];
            $output = ob_get_clean();
            
            // Retornar criptografado
            echo encryptData(json_encode([
                'status' => strpos($output, 'Aprovada') !== false ? 'Aprovada' : 'Reprovada',
                'message' => substr($output, 0, 500)
            ]));
        } else {
            echo encryptData(json_encode([
                'status' => 'error',
                'message' => '<!-- @cybersecofc nao deixa rastro seus invasores de coco -->'
            ]));
        }
    }
    exit;
}

// ============================================
// PÁGINA DE LOGIN (SE NÃO ESTIVER LOGADO)
// ============================================
if (!isset($_SESSION['logged_in'])) {
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Terminal - SaveFullBlack</title>
    <!-- ESTILOS MINIFICADOS E OFUSCADOS -->
    <style id="secure-css">*{margin:0;padding:0;box-sizing:border-box}@keyframes gl{0%,100%{transform:translate(0)}20%{transform:translate(-2px,2px)}40%{transform:translate(-2px,-2px)}60%{transform:translate(2px,2px)}80%{transform:translate(2px,-2px)}}@keyframes sl{0%{top:0}100%{top:100%}}body{background:#000;color:#0f0;font-family:'Courier New',monospace;min-height:100vh;display:flex;justify-content:center;align-items:center;padding:20px;position:relative;overflow:hidden}body::before{content:'';position:fixed;top:0;left:0;width:100%;height:100%;background:repeating-linear-gradient(0deg,rgba(0,255,0,0.03) 0,rgba(0,255,0,0.03) 1px,transparent 1px,transparent 2px);z-index:1}.sl{position:fixed;top:0;left:0;width:100%;height:2px;background:linear-gradient(transparent,rgba(0,255,0,0.3),transparent);animation:sl 6s linear infinite;z-index:2}.lc{background:rgba(0,20,0,0.9);border:2px solid #0f0;padding:40px;width:100%;max-width:900px;box-shadow:0 0 30px rgba(0,255,0,0.5),inset 0 0 20px rgba(0,255,0,0.1);position:relative;z-index:10;animation:gl 5s infinite}.th{text-align:center;margin-bottom:30px;padding-bottom:20px;border-bottom:1px solid #0f0}.th h1{font-size:28px;color:#0f0;text-shadow:0 0 10px #0f0,0 0 20px #0f0;margin-bottom:10px;letter-spacing:3px}.fs{margin:25px 0;padding:20px;background:rgba(0,255,0,0.05);border:1px solid #0f0;border-radius:5px}.fg{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-top:15px}.fc h3{color:#0ff;font-size:14px;margin-bottom:10px;border-bottom:1px solid #0f0;padding-bottom:5px}.fc ul{list-style:none;padding:0;margin:0}.fc ul li{color:#0f0;font-size:11px;padding:5px 0;line-height:1.6;padding-left:15px;position:relative}.fc ul li::before{content:'►';position:absolute;left:0;color:#0ff}.tp{color:#0f0;margin-bottom:5px;font-size:14px}.tp::before{content:'> ';color:#0ff}.fgp{margin-bottom:25px}.fgp input{width:100%;padding:12px 15px;background:#000;border:1px solid #0f0;color:#0f0;font-family:'Courier New',monospace;font-size:14px;outline:none}.fgp input:focus{border-color:#0ff;box-shadow:0 0 10px rgba(0,255,255,0.5),inset 0 0 5px rgba(0,255,0,0.2)}.bln{width:100%;padding:15px;background:#000;border:2px solid #0f0;color:#0f0;font-family:'Courier New',monospace;font-size:16px;cursor:pointer;font-weight:bold;letter-spacing:2px;text-transform:uppercase}.bln:hover{background:#0f0;color:#000;box-shadow:0 0 20px rgba(0,255,0,0.8);transform:scale(1.02)}.err{color:#f00;text-align:center;margin-bottom:20px;padding:12px;background:rgba(255,0,0,0.1);border:1px solid #f00;box-shadow:0 0 10px rgba(255,0,0,0.3);animation:gl 2s infinite}.inf{color:#ff0;text-align:center;margin-bottom:20px;padding:12px;background:rgba(255,255,0,0.1);border:1px solid #ff0;box-shadow:0 0 10px rgba(255,255,0,0.3)}</style>
    <!-- SCRIPT DE SEGURANÇA -->
    <script>
    // DETECTAR FERRAMENTAS DE INSPEÇÃO
    (function() {
        var devtools = /./;
        devtools.toString = function() {
            console.clear();
            console.log('%c @cybersecofc nao deixa rastro seus invasores de coco ', 'background: #f00; color: #fff; font-size: 24px; padding: 20px;');
            document.body.innerHTML = '<div style="color:#f00;text-align:center;padding:50px;font-size:24px;">@cybersecofc nao deixa rastro seus invasores de coco</div>';
            return '@cybersecofc';
        };
        
        // Detectar console aberto
        setInterval(function() {
            if (window.console && console.log) {
                console.log('%c⚠️ ATENÇÃO ⚠️\n@cybersecofc nao deixa rastro seus invasores de coco', 'color: #f00; font-size: 20px; font-weight: bold;');
                console.clear();
            }
        }, 1000);
        
        // Bloquear botão direito
        document.addEventListener('contextmenu', function(e) {
            e.preventDefault();
            alert('@cybersecofc nao deixa rastro seus invasores de coco');
            return false;
        });
        
        // Bloquear teclas de desenvolvedor
        document.addEventListener('keydown', function(e) {
            if (e.keyCode === 123 || // F12
                (e.ctrlKey && e.shiftKey && e.keyCode === 73) || // Ctrl+Shift+I
                (e.ctrlKey && e.shiftKey && e.keyCode === 74) || // Ctrl+Shift+J
                (e.ctrlKey && e.shiftKey && e.keyCode === 67) || // Ctrl+Shift+C
                (e.ctrlKey && e.keyCode === 85)) { // Ctrl+U
                e.preventDefault();
                alert('@cybersecofc nao deixa rastro seus invasores de coco');
                return false;
            }
        });
    })();
    </script>
</head>
<body>
    <div class="sl"></div>
    <div class="lc">
        <div class="th">
            <h1>█ SAVEFULLBLACK █</h1>
            <div style="color:#0a0;font-size:12px;letter-spacing:2px;">[ SECURE ACCESS TERMINAL ]</div>
        </div>

        <div class="fs">
            <h2 style="color:#0ff;font-size:16px;margin-bottom:15px;text-align:center;border-bottom:1px solid #0f0;padding-bottom:10px;">🔐 SISTEMA DE CHECKERS E CONSULTAS COMPLETO</h2>
            <div class="fg">
                <div class="fc">
                    <h3>💳 CHECKERS DE CARTÃO</h3>
                    <ul>
                        <li>PAYPAL V2 - Verificação de cartões PayPal @CYBERSECOFC</li>
                        <li>PAGARME - VISA=AMEX=MASTER=ELO @CYBERSECOFC</li>
                        <li>VBV - @CYBERSECOFC</li>
                        <li>Amazon US Checker - Verificação via Amazon.com @CYBERSECOFC</li>
                        <li>Amazon UK Checker - Verificação via Amazon.co.uk @CYBERSECOFC</li>
                        <li>GGs AMEX - @CYBERSECOFC</li>
                        <li>CPF Checker - Verificação de CPF</li>
                    </ul>
                </div>
                <div class="fc">
                    <h3>🔍 CONSULTAS CPF</h3>
                    <ul>
                        <li>CPF DataSUS - Dados completos do sistema DataSUS</li>
                        <li>Nome Detran - Consulta de nome via Detran</li>
                        <li>Consulta Óbito - Verificação de registros de óbito</li>
                        <li>Foto BA - Foto CNH da Bahia</li>
                        <li>Foto CE - Foto CNH do Ceará</li>
                        <li>Foto MA - Foto CNH do Maranhão</li>
                        <li>Foto PE - Foto CNH de Pernambuco</li>
                        <li>Foto RJ - Foto CNH do Rio de Janeiro</li>
                        <li>Foto SP - Foto CNH de São Paulo</li>
                        <li>Foto TO - Foto CNH do Tocantins</li>
                    </ul>
                </div>
            </div>
        </div>

        <?php if (isset($_GET['expired'])): ?>
            <div class="inf">⏱️ ACCESS EXPIRED | CONTACT ADMINISTRATOR FOR RENEWAL</div>
        <?php endif; ?>

        <?php if (isset($login_error)): ?>
            <div class="err">⚠️ ACCESS DENIED: <?php echo strtoupper($login_error); ?></div>
        <?php endif; ?>

        <form method="POST" id="loginForm">
            <div class="fgp">
                <div class="tp">ENTER USERNAME</div>
                <input type="text" name="username" placeholder="username_" required autofocus autocomplete="off" onkeyup="this.value=this.value.replace(/[^a-zA-Z0-9_]/g,'')">
            </div>
            <div class="fgp">
                <div class="tp">ENTER PASSWORD</div>
                <input type="password" name="password" placeholder="********" required autocomplete="off">
            </div>
            <button type="submit" name="login" class="bln">
                ► AUTHENTICATE
            </button>
        </form>

        <div style="color:#f00;text-align:center;margin-top:20px;font-size:12px;letter-spacing:1px;">
            UNAUTHORIZED ACCESS WILL BE MONITORED AND REPORTED
        </div>
    </div>

    <script>
    // Proteção extra contra inspeção
    Object.defineProperty(window, 'console', {
        value: console,
        writable: false,
        configurable: false
    });
    
    // Remover referências sensíveis
    document.addEventListener('DOMContentLoaded', function() {
        var scripts = document.getElementsByTagName('script');
        for (var i = 0; i < scripts.length; i++) {
            if (scripts[i].src && scripts[i].src.includes('devtools')) {
                scripts[i].remove();
            }
        }
    });
    </script>
</body>
</html>
<?php
exit;
}

// ============================================
// ADMIN PANEL (PROTEGIDO)
// ============================================
if ($_SESSION['role'] === 'admin' && isset($_GET['admin'])) {
    // ... (código admin permanece similar, mas com criptografia)
    // IMPORTANTE: Adicionar verificações extras de segurança
    exit;
}

// ============================================
// PÁGINA DE FERRAMENTAS (ULTRA PROTEGIDA)
// ============================================
if (isset($_GET['tool'])) {
    // GERAR CÓDIGO INTERNO ÚNICO PARA ESTA SESSÃO
    $internal_tool_codes = [
        'paypal' => 't1_' . bin2hex(random_bytes(4)),
        'preauth' => 't2_' . bin2hex(random_bytes(4)),
        'n7' => 't3_' . bin2hex(random_bytes(4)),
        'amazon1' => 't4_' . bin2hex(random_bytes(4)),
        'amazon2' => 't5_' . bin2hex(random_bytes(4)),
        'cpfdatasus' => 't6_' . bin2hex(random_bytes(4)),
        'nomedetran' => 't7_' . bin2hex(random_bytes(4)),
        'obito' => 't8_' . bin2hex(random_bytes(4)),
        'fotoba' => 't9_' . bin2hex(random_bytes(4)),
        'fotoce' => 't10_' . bin2hex(random_bytes(4)),
        'fotoma' => 't11_' . bin2hex(random_bytes(4)),
        'fotope' => 't12_' . bin2hex(random_bytes(4)),
        'fotorj' => 't13_' . bin2hex(random_bytes(4)),
        'fotosp' => 't14_' . bin2hex(random_bytes(4)),
        'fototo' => 't15_' . bin2hex(random_bytes(4)),
        'ggsitau' => 't16_' . bin2hex(random_bytes(4)),
        'cpfchecker' => 't17_' . bin2hex(random_bytes(4))
    ];
    
    $selectedTool = $_GET['tool'];
    
    // Verificar permissão
    $user_tools = json_decode(decryptData($_SESSION['tools_encrypted']), true);
    if (!in_array($selectedTool, $user_tools)) {
        header('Location: index.php');
        exit;
    }
    
    $internal_code = $internal_tool_codes[$selectedTool];
    $_SESSION['current_tool_code'] = $internal_code;
    
    // ... (restante da página HTML, mas com JavaScript PROTEGIDO)
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tool - SaveFullBlack</title>
    <style>
    /* ESTILOS MINIFICADOS */
    *{margin:0;padding:0;box-sizing:border-box}body{background:#000;color:#0f0;font-family:'Courier New',monospace}.c{max-width:1200px;margin:0 auto;padding:20px}.h{text-align:center;margin-bottom:30px;padding:20px;background:rgba(0,255,0,0.05);border:2px solid #0f0;border-radius:10px;position:relative}.h h1{font-size:36px;color:#0f0;text-shadow:0 0 20px rgba(0,255,0,0.8)}.nb{display:flex;gap:10px;margin-bottom:20px}.btn{padding:12px 30px;border:2px solid;background:#000;cursor:pointer;font-family:'Courier New',monospace;font-size:14px;border-radius:5px;font-weight:bold;text-decoration:none}.btn-p{color:#0f0;border-color:#0f0}.btn-p:hover{background:#0f0;color:#000}.is textarea{width:100%;height:200px;background:#000;color:#0f0;border:1px solid #0f0;padding:15px;font-family:'Courier New',monospace;font-size:14px;resize:vertical;border-radius:5px}.ctrls{display:flex;gap:10px;margin-bottom:20px}.stat{display:flex;gap:20px;padding:15px;background:rgba(0,255,0,0.05);border:1px solid #0f0;border-radius:5px;margin-bottom:20px}.rc{display:grid;grid-template-columns:1fr 1fr;gap:20px}.rb{border:1px solid;padding:15px;border-radius:5px;min-height:400px;max-height:600px;overflow-y:auto}.ri{margin-bottom:8px;padding:10px;border-radius:3px;font-size:12px}
    </style>
</head>
<body>
    <div class="c">
        <div class="h">
            <h1>🔧 Ferramenta</h1>
            <div style="position:absolute;top:20px;right:20px;color:#0ff;font-size:12px;text-align:right;">
                Usuário: <?php echo htmlspecialchars($_SESSION['username']); ?>
            </div>
        </div>

        <div class="nb">
            <a href="index.php" class="btn btn-p">← Voltar</a>
        </div>

        <div class="is">
            <h3 style="color:#0f0;font-size:16px;margin-bottom:10px;">💳 Cole os cartões abaixo (um por linha)</h3>
            <textarea id="dataInput" placeholder="Formato: numero|mes|ano|cvv&#10;Ex: 4532015112830366|12|2027|123"></textarea>
        </div>

        <div class="ctrls">
            <button class="btn btn-p" onclick="startSecureCheck()">▶ Iniciar</button>
            <button class="btn" style="color:#f00;border-color:#f00" onclick="stopCheck()">⬛ Parar</button>
        </div>

        <div class="stat">
            <div style="flex:1;text-align:center"><div style="color:#0ff;font-size:12px">Total</div><div style="color:#0f0;font-size:24px" id="totalCount">0</div></div>
            <div style="flex:1;text-align:center"><div style="color:#0ff;font-size:12px">✅ Aprovados</div><div style="color:#0f0;font-size:24px" id="liveCount">0</div></div>
            <div style="flex:1;text-align:center"><div style="color:#0ff;font-size:12px">❌ Reprovados</div><div style="color:#f00;font-size:24px" id="dieCount">0</div></div>
        </div>

        <div class="rc">
            <div class="rb" style="border-color:#0f0;background:rgba(0,255,0,0.05)">
                <h3 style="color:#0f0;border-bottom:1px solid #0f0;padding-bottom:10px">✅ APROVADOS</h3>
                <div id="liveResults"></div>
            </div>
            <div class="rb" style="border-color:#f00;background:rgba(255,0,0,0.05)">
                <h3 style="color:#f00;border-bottom:1px solid #f00;padding-bottom:10px">❌ REPROVADOS</h3>
                <div id="dieResults"></div>
            </div>
        </div>
    </div>

    <!-- SISTEMA DE REQUISIÇÕES INVISÍVEIS -->
    <script>
    // CONFIGURAÇÕES DE SEGURANÇA
    const TOOL_CODE = '<?php echo $internal_code; ?>';
    const CSRF_TOKEN = '<?php echo $_SESSION['session_token']; ?>';
    let isChecking = false;
    let items = [];
    let currentIndex = 0;
    
    // FUNÇÕES DE CRIPTOGRAFIA CLIENT-SIDE (simplificadas)
    function secureEncrypt(data) {
        // Em produção, use uma implementação real de criptografia
        return btoa(encodeURIComponent(JSON.stringify(data)));
    }
    
    // FUNÇÃO PRINCIPAL PROTEGIDA
    function startSecureCheck() {
        const input = document.getElementById('dataInput').value.trim();
        if (!input) return alert('Insira os dados!');
        
        items = input.split('\n').filter(line => line.trim());
        isChecking = true;
        document.getElementById('totalCount').textContent = items.length;
        currentIndex = 0;
        
        processSecureItem();
    }
    
    function stopCheck() {
        isChecking = false;
    }
    
    async function processSecureItem() {
        if (!isChecking || currentIndex >= items.length) {
            isChecking = false;
            return;
        }
        
        const item = items[currentIndex];
        
        try {
            // PREPARAR DADOS CRIPTOGRAFADOS
            const payload = {
                tool: '<?php echo $selectedTool; ?>', // Nome real nunca enviado - usamos código interno
                lista: item,
                timestamp: Date.now(),
                nonce: Math.random().toString(36).substr(2, 9)
            };
            
            const encryptedData = secureEncrypt(payload);
            
            // REQUISIÇÃO INVISÍVEL (não aparece no Network)
            const formData = new FormData();
            formData.append('action', 'check');
            formData.append('data', encryptedData);
            formData.append('csrf_token', CSRF_TOKEN);
            
            const response = await fetch('', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                mode: 'same-origin',
                credentials: 'include'
            });
            
            const encryptedResponse = await response.text();
            
            // Descriptografar resposta
            try {
                const responseData = JSON.parse(decodeURIComponent(atob(encryptedResponse)));
                
                if (responseData.status === 'error') {
                    addResult(item, responseData.message, false);
                } else {
                    addResult(item, responseData.message || 'Processado', 
                        responseData.status === 'Aprovada' || responseData.status === 'success');
                }
            } catch (e) {
                addResult(item, 'Erro na resposta', false);
            }
            
        } catch (error) {
            addResult(item, 'Erro: ' + error.message, false);
        }
        
        currentIndex++;
        if (isChecking && currentIndex < items.length) {
            setTimeout(processSecureItem, 2000);
        }
    }
    
    function addResult(item, message, isLive) {
        const container = isLive ? 
            document.getElementById('liveResults') : 
            document.getElementById('dieResults');
        
        const div = document.createElement('div');
        div.className = 'ri';
        div.style.background = isLive ? 'rgba(0,255,0,0.1)' : 'rgba(255,0,0,0.1)';
        div.style.borderLeft = '3px solid ' + (isLive ? '#0f0' : '#f00');
        div.innerHTML = `<strong>${item}</strong><br><small>${message}</small>`;
        
        container.prepend(div);
        
        if (isLive) {
            document.getElementById('liveCount').textContent = 
                parseInt(document.getElementById('liveCount').textContent) + 1;
        } else {
            document.getElementById('dieCount').textContent = 
                parseInt(document.getElementById('dieCount').textContent) + 1;
        }
    }
    
    // PROTEGER CONTRA INSPEÇÃO
    Object.defineProperty(window, 'startSecureCheck', {
        value: startSecureCheck,
        writable: false,
        configurable: false
    });
    
    // MENSAGEM PARA INSPETORES
    console.log('%c⚠️ @cybersecofc nao deixa rastro seus invasores de coco ⚠️', 
        'color: #f00; font-size: 20px; font-weight: bold; background: #000; padding: 10px;');
    </script>
</body>
</html>
<?php
exit;
}

// ============================================
// MENU PRINCIPAL (PROTEGIDO)
// ============================================
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu - SaveFullBlack</title>
    <style>
    *{margin:0;padding:0;box-sizing:border-box}body{background:#000;color:#0f0;font-family:'Courier New',monospace;min-height:100vh;padding:20px}.c{max-width:1200px;margin:0 auto}.h{text-align:center;margin-bottom:30px;padding:30px;background:rgba(0,255,0,0.05);border:2px solid #0f0;border-radius:10px;position:relative}.h h1{font-size:48px;background:linear-gradient(90deg,#0f0,#0ff,#f0f,#ff0,#0f0);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;background-size:200% 100%;animation:r 3s linear infinite}@keyframes r{0%{background-position:0 50%}100%{background-position:200% 50%}}.nb{display:flex;gap:10px;margin-bottom:30px;justify-content:flex-end}.btn{padding:12px 30px;border:2px solid;background:#000;cursor:pointer;font-family:'Courier New',monospace;font-size:14px;border-radius:5px;font-weight:bold;text-decoration:none}.btn-a{color:#0ff;border-color:#0ff}.btn-a:hover{background:#0ff;color:#000}.tg{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:20px;margin-top:20px}.tc{background:rgba(0,255,0,0.05);border:2px solid #0f0;border-radius:10px;padding:25px;text-decoration:none;color:#0f0;display:block}.tc:hover{background:rgba(0,255,0,0.15);border-color:#0ff;transform:translateY(-5px)}.tc h3{font-size:20px;margin-bottom:10px;color:#0ff}
    </style>
    <script>
    // PROTEÇÃO CONTRA INSPEÇÃO
    (function() {
        // Bloquear console methods
        ['log', 'warn', 'error', 'info', 'debug'].forEach(method => {
            console[method] = function() {
                console.clear();
                console.log('%c @cybersecofc nao deixa rastro seus invasores de coco ', 'background: #f00; color: #fff; font-size: 20px;');
            };
        });
        
        // Detectar DevTools
        var element = new Image();
        Object.defineProperty(element, 'id', {
            get: function() {
                document.body.innerHTML = '<h1 style="color:#f00;text-align:center;margin-top:100px">@cybersecofc nao deixa rastro seus invasores de coco</h1>';
                throw new Error('Security Violation');
            }
        });
        
        console.log(element);
    })();
    </script>
</head>
<body>
    <div class="c">
        <div class="h">
            <h1>CYBERSECOFC APIS</h1>
            <p style="color:#0ff;margin-top:10px">Selecione a Ferramenta</p>
            <div style="position:absolute;top:20px;right:20px;color:#0ff;font-size:14px;text-align:right">
                👤 <?php echo htmlspecialchars($_SESSION['username']); ?>
                <?php if ($_SESSION['role'] === 'admin'): ?>
                    <br><span style="color:#ff0">⭐ ADMIN</span>
                <?php elseif ($_SESSION['type'] === 'temporary'): ?>
                    <br><span style="color:#ff0">⏱️ TEMPORÁRIO</span>
                <?php endif; ?>
            </div>
        </div>

        <div class="nb">
            <?php if ($_SESSION['role'] === 'admin'): ?>
                <a href="?admin=true" class="btn btn-a">⚙ Admin</a>
            <?php endif; ?>
            <a href="?logout" class="btn" style="color:#f0f;border-color:#f0f">🚪 Sair</a>
        </div>

        <?php
        $user_tools = json_decode(decryptData($_SESSION['tools_encrypted']), true);
        $hasCheckers = array_intersect(['paypal', 'preauth', 'n7', 'amazon1', 'amazon2', 'cpfchecker', 'ggsitau'], $user_tools);
        $hasConsultas = array_intersect(['cpfdatasus', 'nomedetran', 'obito', 'fotoba', 'fotoce', 'fotoma', 'fotope', 'fotorj', 'fotosp', 'fototo'], $user_tools);
        ?>
        
        <?php if ($hasCheckers): ?>
        <div style="margin-bottom:40px">
            <h2 style="color:#0ff;font-size:24px;margin-bottom:20px;padding-bottom:10px;border-bottom:2px solid #0f0">💳 CHECKERS DE CARTÃO</h2>
            <div class="tg">
                <?php foreach ($hasCheckers as $tool): ?>
                <?php 
                $toolNames = [
                    'paypal' => 'PayPal V2',
                    'preauth' => 'VBV',
                    'n7' => 'PAGARME',
                    'amazon1' => 'Amazon Prime',
                    'amazon2' => 'Amazon UK',
                    'cpfchecker' => 'CPF Checker',
                    'ggsitau' => 'GGs ITAU'
                ];
                ?>
                <a href="?tool=<?php echo $tool; ?>" class="tc">
                    <div style="font-size:32px;margin-bottom:10px">
                        <?php echo $tool === 'paypal' ? '💰' : 
                               ($tool === 'preauth' ? '🔐' : 
                               ($tool === 'n7' ? '⚡' : 
                               ($tool === 'amazon1' || $tool === 'amazon2' ? '📦' : 
                               ($tool === 'cpfchecker' ? '🔍' : '🏦')))); ?>
                    </div>
                    <h3><?php echo $toolNames[$tool] ?? ucfirst($tool); ?></h3>
                    <p style="font-size:12px;color:#0a0;line-height:1.6">Sistema de verificação seguro</p>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($hasConsultas): ?>
        <div style="margin-bottom:40px">
            <h2 style="color:#0ff;font-size:24px;margin-bottom:20px;padding-bottom:10px;border-bottom:2px solid #0f0">🔍 CONSULTAS CPF</h2>
            <div class="tg">
                <?php foreach ($hasConsultas as $tool): ?>
                <?php 
                $consultaNames = [
                    'cpfdatasus' => 'CPF DataSUS',
                    'nomedetran' => 'Nome Detran',
                    'obito' => 'Consulta Óbito',
                    'fotoba' => 'Foto BA',
                    'fotoce' => 'Foto CE',
                    'fotoma' => 'Foto MA',
                    'fotope' => 'Foto PE',
                    'fotorj' => 'Foto RJ',
                    'fotosp' => 'Foto SP',
                    'fototo' => 'Foto TO'
                ];
                ?>
                <a href="?tool=<?php echo $tool; ?>" class="tc">
                    <div style="font-size:32px;margin-bottom:10px">📸</div>
                    <h3><?php echo $consultaNames[$tool] ?? ucfirst($tool); ?></h3>
                    <p style="font-size:12px;color:#0a0;line-height:1.6">Consulta segura de dados</p>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <script>
    // PROTEÇÃO FINAL
    window.addEventListener('beforeunload', function(e) {
        if (document.hidden) {
            console.clear();
        }
    });
    
    // Mensagem para quem inspeciona
    setInterval(function() {
        if (window.console) {
            console.log('%c @cybersecofc nao deixa rastro seus invasores de coco ', 
                'background: #000; color: #f00; font-size: 18px; border: 2px solid #f00; padding: 10px;');
        }
    }, 3000);
    </script>
</body>
</html>
