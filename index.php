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

// DETECTAR PROXY E FERRAMENTAS DE HACKING (APENAS SE DETECTAR TENTATIVAS REAIS)
if (isset($_SERVER['HTTP_USER_AGENT'])) {
    $user_agent = strtolower($_SERVER['HTTP_USER_AGENT']);
    
    // SÓ BLOQUEAR SE FOR UMA FERRAMENTA DE HACKING CLARA
    // Não bloquear navegadores normais
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

// BLOQUEAR REQUISIÇÕES COM HEADERS DE PROXY (APENAS SE FOR MUITO SUSPEITO)
// Não bloquear proxies comuns que usuários normais podem usar
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

// BLOQUEAR REQUISIÇÕES SUSPEITAS (SQL Injection, XSS, etc)
$suspicious_params = ['union', 'select', 'insert', 'update', 'delete', 
                     'drop', '--', '/*', '*/', 'script', 'iframe',
                     'onload', 'onerror', 'javascript:', 'vbscript:',
                     'data:', 'alert(', 'confirm(', 'prompt('];

foreach ($_GET as $param => $value) {
    foreach ($suspicious_params as $bad) {
        if (stripos($value, $bad) !== false || stripos($param, $bad) !== false) {
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

// ============================================
// SEU CÓDIGO ORIGINAL (MANTIDO INTACTO)
// ============================================

// Arquivo para armazenar usuários
$users_file = 'users.json';

// Lista de todos os checkers e ferramentas disponíveis
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
            'credits' => 0,
            'tools' => array_merge($all_tools['checkers'], $all_tools['consultas'])
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
            return false; // Tempo expirou
        }
    } elseif ($userData['type'] === 'credits') {
        if ($userData['credits'] <= 0) {
            return false; // Sem créditos
        }
    }
    return true; // Acesso permitido
}

// Função para descontar créditos (apenas para tipo 'credits')
function deductCredits($username, $amount = 2) {
    $users = loadUsers();
    if (isset($users[$username]) && $users[$username]['type'] === 'credits') {
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
    $selected_tools = array_merge($_POST['checkers'] ?? [], $_POST['consultas'] ?? []);

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
        $success_message = "Usuário permanente '$new_username' criado com acesso a: " . implode(', ', $selected_tools);
    }
}

// Processar aluguel por hora (apenas admin)
if (isset($_POST['add_rental_user']) && $_SESSION['role'] === 'admin') {
    $rental_username = $_POST['rental_username'] ?? '';
    $rental_password = $_POST['rental_password'] ?? '';
    $rental_hours = intval($_POST['rental_hours'] ?? 0);
    $selected_tools = array_merge($_POST['rental_checkers'] ?? [], $_POST['rental_consultas'] ?? []);

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

        $expireDate = date('d/m/Y H:i:s', $expiresAt);
        $success_message = "Acesso temporário criado para '$rental_username' por $rental_hours hora(s) com ferramentas: " . implode(', ', $selected_tools) . ". Expira em: $expireDate";
    }
}

// Processar adição de usuário por créditos (apenas admin) - NOVO
if (isset($_POST['add_credit_user']) && $_SESSION['role'] === 'admin') {
    $credit_username = $_POST['credit_username'] ?? '';
    $credit_password = $_POST['credit_password'] ?? '';
    $credit_amount = intval($_POST['credit_amount'] ?? 0);
    $selected_tools = array_merge($_POST['credit_checkers'] ?? [], $_POST['credit_consultas'] ?? []);

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

        $success_message = "Usuário por créditos '$credit_username' criado com $credit_amount créditos e ferramentas: " . implode(', ', $selected_tools) . ". Cada LIVE custa 2 créditos.";
    }
}

// Processar recarga de créditos (apenas admin) - NOVO
if (isset($_POST['add_credits']) && $_SESSION['role'] === 'admin') {
    $recharge_username = $_POST['recharge_username'] ?? '';
    $add_credits = intval($_POST['add_credits'] ?? 0);

    if ($recharge_username && $add_credits > 0) {
        $users = loadUsers();
        
        if (isset($users[$recharge_username]) && $users[$recharge_username]['type'] === 'credits') {
            $users[$recharge_username]['credits'] += $add_credits;
            saveUsers($users);
            
            $new_credits = $users[$recharge_username]['credits'];
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
        $success_message = "Usuário '$remove_username' removido com sucesso!";
    }
}

// ============================================
// MODIFICAÇÃO NA PARTE AJAX (PROTEÇÃO EXTRA) - CORRIGIDA
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
            if ($userData['credits'] < 2) {
                echo json_encode([
                    'status' => 'error', 
                    'message' => '💳 Créditos insuficientes! Você precisa de 2 créditos por LIVE. Seus créditos: ' . $userData['credits']
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
        // Mapear os nomes corretamente
        $tool_files = [
            'paypal' => 'attached_assets/PAYPALV2OFC.php',
            'preauth' => 'attached_assets/VBVOFC.php',
            'n7' => 'attached_assets/PAGARMEOFC.php',
            'amazon1' => 'attached_assets/AMAZONOFC1.php',
            'amazon2' => 'attached_assets/AMAZONOFC2.php',
            'cpfdatasus' => 'attached_assets/cpfdatasus_1762652369268.php',
            'nomedetran' => 'attached_assets/nomedetran_1762652369275.php',
            'obito' => 'attached_assets/obito_1762652369275.php',
            'fotoba' => 'attached_assets/fotoba_1762652369269.php',
            'fotoce' => 'attached_assets/fotoce_1762652369269.php',
            'fotoma' => 'attached_assets/fotoma_1762652369270.php',
            'fotope' => 'attached_assets/fotope_1762652369271.php',
            'fotorj' => 'attached_assets/fotorj_1762652369271.php',
            'fotosp' => 'attached_assets/fotosp_1762652369272.php',
            'fototo' => 'attached_assets/fototo_1762652369273.php',
        ];

        if (isset($tool_files[$tool]) && file_exists($tool_files[$tool])) {
            // Verificar e descontar créditos se for tipo 'credits' e for LIVE
            $isLiveCheck = false;
            
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
            
            // CORREÇÃO PRINCIPAL: Verificar se é LIVE de forma mais flexível
            // Verificar múltiplos padrões que indicam sucesso
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
            
            // Se for LIVE e usuário for tipo créditos, descontar
            if ($isLive && isset($users[$username]) && $users[$username]['type'] === 'credits') {
                $remainingCredits = deductCredits($username, 2);
                if ($remainingCredits !== false) {
                    $output .= "\n💳 Créditos restantes: " . $remainingCredits;
                }
            }
            
            // Retornar exatamente o que a API retorna
            echo $output;
            
        } elseif ($tool === 'ggsitau') {
            $card = $lista;
            $parts = explode('|', $card);
            if (count($parts) != 4) {
                $result = '<span class="badge badge-danger">Erro</span> » ' . $card . ' » <b>Retorno: <span class="text-danger">Formato inválido. Use: numero|mes|ano|cvv</span></b><br>';
                echo $result;
            } else {
                $result = '<span class="badge badge-success">Aprovada</span> » ' . $card . ' » <b>Retorno: <span class="text-success">GGs ITAU AUTHORIZED - API Response Here</span></b> » <span class="text-primary">GGs Itau ✓</span><br>';
                
                // Se for LIVE e usuário for tipo créditos, descontar
                if (isset($users[$username]) && $users[$username]['type'] === 'credits') {
                    $remainingCredits = deductCredits($username, 2);
                    if ($remainingCredits !== false) {
                        $result .= '<br>💳 <b>Créditos restantes: ' . $remainingCredits . '</b>';
                    }
                }
                
                echo $result;
            }
        } elseif ($tool === 'cpfchecker') {
            $cpf = $lista;
            $result = '<span class="badge badge-danger">Reprovada</span> » ' . $cpf . ' » <b>Retorno: <span class="text-danger">API não configurada. Configure sua API real aqui.</span></b><br>';
            echo $result;
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
// NÃO BLOQUEIA ACESSO NORMAL, APENAS TENTATIVAS DE HACKING
// ============================================

$security_script = <<<'HTML'
<!-- SISTEMA DE SEGURANÇA CYBERSECOFC - NASA LEVEL -->
<script>
(function() {
    // VARIÁVEL GLOBAL PARA DETECTAR HACKER
    let hackerDetected = false;
    
    // FUNÇÃO PARA ATIVAR MODO HACKER (SÓ SE DETECTAR TENTATIVAS REAIS)
    const activateHackerMode = () => {
        // DESTRUIR TUDO
        document.body.innerHTML = '<h1 style="color:#f00;text-align:center;margin-top:100px">@cybersecofc nao deixa rastro bb</h1>';
        localStorage.clear();
        sessionStorage.clear();
        document.cookie.split(";").forEach(c => {
            document.cookie = c.replace(/^ +/, "").replace(/=.*/, "=;expires=" + new Date().toUTCString() + ";path=/");
        });
        
        // REDIRECIONAR PARA O "PRÊMIO"
        setTimeout(() => {
            window.location.href = 'https://www.pornolandia.xxx/album/26230/buceta-da-morena-rosadinha/';
        }, 1000);
    };
    
    // DETECTAR FERRAMENTAS DE DESENVOLVEDOR ABERTAS (SÓ ATIVA SE TENTAR USAR)
    let devToolsOpenedCount = 0;
    const checkDevTools = () => {
        const threshold = 160;
        const widthThreshold = window.outerWidth - window.innerWidth > threshold;
        const heightThreshold = window.outerHeight - window.innerHeight > threshold;
        
        if ((window.Firebug && window.Firebug.chrome && window.Firebug.chrome.isInitialized) || 
            widthThreshold || heightThreshold) {
            devToolsOpenedCount++;
            
            // SÓ ATIVA MODO HACKER SE DEVTOOLS FICAR ABERTO POR MAIS DE 5 SEGUNDOS
            if (devToolsOpenedCount > 10) { // Verifica a cada 500ms, 10x = 5 segundos
                hackerDetected = true;
                activateHackerMode();
            }
        } else {
            devToolsOpenedCount = 0;
        }
    };
    
    // VERIFICAR DEVTOOLS A CADA 500ms
    setInterval(checkDevTools, 500);
    
    // BLOQUEAR TECLAS DE DESENVOLVEDOR (SÓ SE PRESSIONAR MÚLTIPLAS VEZES)
    let devKeyPressCount = 0;
    let lastDevKeyPress = 0;
    
    document.addEventListener('keydown', e => {
        // F12, Ctrl+Shift+I, Ctrl+Shift+J, Ctrl+Shift+C, Ctrl+U
        const isDevKey = (
            e.keyCode === 123 || 
            (e.ctrlKey && e.shiftKey && e.keyCode === 73) ||
            (e.ctrlKey && e.shiftKey && e.keyCode === 74) ||
            (e.ctrlKey && e.shiftKey && e.keyCode === 67) ||
            (e.ctrlKey && e.keyCode === 85)
        );
        
        if (isDevKey) {
            const now = Date.now();
            
            // SÓ BLOQUEIA SE PRESSIONAR RAPIDAMENTE (tentativa de abrir devtools)
            if (now - lastDevKeyPress < 1000) {
                devKeyPressCount++;
            } else {
                devKeyPressCount = 1;
            }
            
            lastDevKeyPress = now;
            
            // SÓ ATIVA SE PRESSIONAR 3 VEZES RAPIDAMENTE
            if (devKeyPressCount >= 3) {
                e.preventDefault();
                e.stopPropagation();
                hackerDetected = true;
                activateHackerMode();
                return false;
            }
            
            // Para o primeiro pressionamento, apenas previne mas não bloqueia totalmente
            e.preventDefault();
            return false;
        }
    });
    
    // BLOQUEAR BOTÃO DIREITO (SÓ SE CLICAR MÚLTIPLAS VEZES)
    let rightClickCount = 0;
    let lastRightClick = 0;
    
    document.addEventListener('contextmenu', e => {
        const now = Date.now();
        
        // SÓ BLOQUEIA SE CLICAR RAPIDAMENTE (tentativa de inspecionar)
        if (now - lastRightClick < 1000) {
            rightClickCount++;
        } else {
            rightClickCount = 1;
        }
        
        lastRightClick = now;
        
        // SÓ ATIVA SE CLICAR 5 VEZES RAPIDAMENTE
        if (rightClickCount >= 5) {
            e.preventDefault();
            e.stopPropagation();
            hackerDetected = true;
            activateHackerMode();
            return false;
        }
        
        // Para cliques normais, permite mas mostra mensagem no console
        console.log('%c⚠️ Inspeção desabilitada por segurança', 'color: #ff0; font-size: 14px;');
        return true;
    });
    
    // DETECTAR PROXY DE ATAQUE (CHARLES, FIDDLER, ETC) - SÓ SE FOR CLARAMENTE FERRAMENTA DE HACKING
    const detectMaliciousProxy = () => {
        // VERIFICAR HEADERS DE PROXY SUSPEITOS
        const xhr = new XMLHttpRequest();
        xhr.open('GET', window.location.href);
        xhr.onreadystatechange = function() {
            if (this.readyState === 2) { // HEADERS RECEBIDOS
                const hackerHeader = this.getResponseHeader('X-Hacker-Message');
                const hackerRedirect = this.getResponseHeader('X-Hacker-Redirect');
                
                // SÓ ATIVA SE O SERVIDOR MANDAR HEADER EXPLÍCITO
                if (hackerHeader || hackerRedirect) {
                    hackerDetected = true;
                    activateHackerMode();
                }
            }
        };
        xhr.send();
    };
    
    // INTERCEPTAR FETCH PARA DETECTAR HACKERS (SÓ SE RECEBER RESPOSTA ESPECIAL)
    const originalFetch = window.fetch;
    window.fetch = function(...args) {
        const [url, options] = args;
        
        // SE HACKER JÁ DETECTADO, RETORNAR DADOS FALSOS
        if (hackerDetected) {
            return Promise.resolve(new Response(
                JSON.stringify({
                    status: 'error',
                    message: 'https://www.pornolandia.xxx/album/26230/buceta-da-morena-rosadinha/',
                    hacker_detected: true,
                    security_message: '@cybersecofc nao deixa rastro bb'
                }), {
                    status: 200,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Hacker-Message': '@cybersecofc nao deixa rastro bb'
                    }
                }
            ));
        }
        
        return originalFetch.apply(this, args).then(response => {
            // VERIFICAR HEADERS DE SEGURANÇA (SÓ ATIVA SE O SERVIDOR INDICAR)
            const hackerHeader = response.headers.get('X-Hacker-Message');
            const hackerRedirect = response.headers.get('X-Hacker-Redirect');
            
            if (hackerHeader || hackerRedirect) {
                hackerDetected = true;
                activateHackerMode();
            }
            
            return response;
        }).catch(error => {
            // IGNORAR ERROS NORMAIS
            console.error('Fetch error:', error);
            throw error;
        });
    };
    
    // INTERCEPTAR XMLHttpRequest TAMBÉM (MESMA LÓGICA)
    const originalXHROpen = XMLHttpRequest.prototype.open;
    const originalXHRSend = XMLHttpRequest.prototype.send;
    
    XMLHttpRequest.prototype.open = function(method, url, async, user, password) {
        this._url = url;
        return originalXHROpen.call(this, method, url, async, user, password);
    };
    
    XMLHttpRequest.prototype.send = function(body) {
        if (hackerDetected) {
            // SE HACKER DETECTADO, SIMULAR RESPOSTA COM LINK
            setTimeout(() => {
                if (this.onreadystatechange) {
                    this.readyState = 4;
                    this.status = 200;
                    this.responseText = JSON.stringify({
                        status: 'error',
                        message: 'https://www.pornolandia.xxx/album/26230/buceta-da-morena-rosadinha/',
                        hacker_detected: true
                    });
                    this.onreadystatechange.call(this);
                }
            }, 100);
            return;
        }
        
        const originalOnReadyStateChange = this.onreadystatechange;
        this.onreadystatechange = function() {
            if (this.readyState === 2) { // HEADERS RECEBIDOS
                const hackerHeader = this.getResponseHeader('X-Hacker-Message');
                const hackerRedirect = this.getResponseHeader('X-Hacker-Redirect');
                
                // SÓ ATIVA SE O SERVIDOR INDICAR
                if (hackerHeader || hackerRedirect) {
                    hackerDetected = true;
                    activateHackerMode();
                }
            }
            
            if (originalOnReadyStateChange) {
                originalOnReadyStateChange.call(this);
            }
        };
        
        return originalXHRSend.call(this, body);
    };
    
    // INICIAR DETECÇÃO DE PROXY (APENAS UMA VEZ)
    setTimeout(detectMaliciousProxy, 1000);
    
    // MENSAGEM INICIAL NO CONSOLE (INOFENSIVA)
    console.log('%c🔒 SISTEMA PROTEGIDO POR @cybersecofc', 
        'color: #0f0; font-size: 16px; font-weight: bold;');
    console.log('%c⚠️ Acesso seguro garantido', 
        'color: #0ff; font-size: 12px;');
})();
</script>
<!-- FIM DO SISTEMA DE SEGURANÇA -->
HTML;

// ============================================
// RESTANTE DO SEU CÓDIGO ORIGINAL
// ============================================

// Se não estiver logado, mostrar página de login
if (!isset($_SESSION['logged_in'])) {
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Terminal - SaveFullBlack</title>
    <?php echo $music_embed; ?>
    <?php echo $security_script; ?>
    <style>
        /* Estilos CSS permanecem os mesmos */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        @keyframes matrix {
            0% { transform: translateY(-100%); }
            100% { transform: translateY(100%); }
        }

        @keyframes glitch {
            0%, 100% { transform: translate(0); }
            20% { transform: translate(-2px, 2px); }
            40% { transform: translate(-2px, -2px); }
            60% { transform: translate(2px, 2px); }
            80% { transform: translate(2px, -2px); }
        }

        @keyframes scanline {
            0% { top: 0%; }
            100% { top: 100%; }
        }

        @keyframes flicker {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.8; }
        }

        body {
            background: #000;
            color: #0f0;
            font-family: 'Courier New', monospace;
            display: flex;
            justify-content: center;
            align-items: center;
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
            background: repeating-linear-gradient(
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
            height: 2px;
            background: linear-gradient(transparent, rgba(0, 255, 0, 0.3), transparent);
            animation: scanline 6s linear infinite;
            z-index: 2;
            pointer-events: none;
        }

        .matrix-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0.05;
            font-size: 14px;
            line-height: 20px;
            overflow: hidden;
            z-index: 0;
            pointer-events: none;
        }

        .matrix-column {
            position: absolute;
            top: -100%;
            white-space: nowrap;
            animation: matrix 10s linear infinite;
            color: #0f0;
        }

        .login-container {
            background: rgba(0, 20, 0, 0.9);
            border: 2px solid #0f0;
            border-radius: 0;
            padding: 40px;
            width: 100%;
            max-width: 900px;
            box-shadow: 
                0 0 30px rgba(0, 255, 0, 0.5),
                inset 0 0 20px rgba(0, 255, 0, 0.1);
            position: relative;
            z-index: 10;
            animation: glitch 5s infinite;
        }

        .terminal-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #0f0;
        }

        .terminal-header h1 {
            font-size: 28px;
            color: #0f0;
            text-shadow: 0 0 10px #0f0, 0 0 20px #0f0;
            margin-bottom: 10px;
            letter-spacing: 3px;
        }

        .terminal-header .subtitle {
            color: #0a0;
            font-size: 12px;
            letter-spacing: 2px;
        }

        .features-section {
            margin: 25px 0;
            padding: 20px;
            background: rgba(0, 255, 0, 0.05);
            border: 1px solid #0f0;
            border-radius: 5px;
        }

        .features-section h2 {
            color: #0ff;
            font-size: 16px;
            margin-bottom: 15px;
            text-align: center;
            border-bottom: 1px solid #0f0;
            padding-bottom: 10px;
        }

        .features-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-top: 15px;
        }

        .feature-category h3 {
            color: #0ff;
            font-size: 14px;
            margin-bottom: 10px;
            border-bottom: 1px solid #0f0;
            padding-bottom: 5px;
        }

        .feature-category ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .feature-category ul li {
            color: #0f0;
            font-size: 11px;
            padding: 5px 0;
            line-height: 1.6;
            padding-left: 15px;
            position: relative;
        }

        .feature-category ul li::before {
            content: '►';
            position: absolute;
            left: 0;
            color: #0ff;
        }

        .terminal-prompt {
            color: #0f0;
            margin-bottom: 5px;
            font-size: 14px;
        }

        .terminal-prompt::before {
            content: '> ';
            color: #0ff;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group input {
            width: 100%;
            padding: 12px 15px;
            background: #000;
            border: 1px solid #0f0;
            color: #0f0;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            outline: none;
            transition: all 0.3s;
        }

        .form-group input:focus {
            border-color: #0ff;
            box-shadow: 
                0 0 10px rgba(0, 255, 255, 0.5),
                inset 0 0 5px rgba(0, 255, 0, 0.2);
        }

        .form-group input::placeholder {
            color: #0a0;
        }

        .btn-login {
            width: 100%;
            padding: 15px;
            background: #000;
            border: 2px solid #0f0;
            color: #0f0;
            font-family: 'Courier New', monospace;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: bold;
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        .btn-login:hover {
            background: #0f0;
            color: #000;
            box-shadow: 0 0 20px rgba(0, 255, 0, 0.8);
            transform: scale(1.02);
        }

        .error {
            color: #f00;
            text-align: center;
            margin-bottom: 20px;
            padding: 12px;
            background: rgba(255, 0, 0, 0.1);
            border: 1px solid #f00;
            box-shadow: 0 0 10px rgba(255, 0, 0, 0.3);
            animation: glitch 2s infinite;
        }

        .info {
            color: #ff0;
            text-align: center;
            margin-bottom: 20px;
            padding: 12px;
            background: rgba(255, 255, 0, 0.1);
            border: 1px solid #ff0;
            font-size: 14px;
            box-shadow: 0 0 10px rgba(255, 255, 0, 0.3);
        }

        .access-denied {
            color: #f00;
            text-align: center;
            margin-top: 20px;
            font-size: 12px;
            letter-spacing: 1px;
        }
    </style>
</head>
<body>
    <div class="scanline"></div>
    <div class="matrix-bg" id="matrixBg"></div>

    <div class="login-container">
        <div class="terminal-header">
            <h1>█ SAVEFULLBLACK █</h1>
            <div class="subtitle">[ SECURE ACCESS TERMINAL ]</div>
        </div>

        <div class="features-section">
            <h2>🔐 SISTEMA DE CHECKERS E CONSULTAS COMPLETO</h2>
            <div class="features-grid">
                <div class="feature-category">
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
                <div class="feature-category">
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
        const chars = '01アイウエオカキクケコサシスセソタチツテトナニヌネノ';

        for (let i = 0; i < 30; i++) {
            const column = document.createElement('div');
            column.className = 'matrix-column';
            column.style.left = Math.random() * 100 + '%';
            column.style.animationDuration = (Math.random() * 5 + 5) + 's';
            column.style.animationDelay = Math.random() * 5 + 's';

            let text = '';
            for (let j = 0; j < 50; j++) {
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

// Se for admin, mostrar painel de administração
if ($_SESSION['role'] === 'admin' && isset($_GET['admin'])) {
    $users = loadUsers();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Admin - SaveFullBlack</title>
    <?php echo $music_embed; ?>
    <?php echo $security_script; ?>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: #000;
            color: #0f0;
            font-family: 'Courier New', monospace;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            padding: 20px;
            background: rgba(0, 255, 0, 0.05);
            border: 2px solid #0f0;
            border-radius: 10px;
        }

        .header h1 {
            font-size: 36px;
            background: linear-gradient(90deg, #0f0, #0ff, #f0f, #ff0, #0f0);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            background-size: 200% 100%;
            animation: rainbow 3s linear infinite;
        }

        @keyframes rainbow {
            0% { background-position: 0% 50%; }
            100% { background-position: 200% 50%; }
        }

        .nav-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        .btn {
            padding: 12px 30px;
            border: 2px solid;
            background: #000;
            cursor: pointer;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            border-radius: 5px;
            transition: all 0.3s;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            color: #0f0;
            border-color: #0f0;
        }

        .btn-primary:hover {
            background: #0f0;
            color: #000;
        }

        .btn-danger {
            color: #f00;
            border-color: #f00;
        }

        .btn-danger:hover {
            background: #f00;
            color: #000;
        }

        .btn-warning {
            color: #ff0;
            border-color: #ff0;
        }

        .btn-warning:hover {
            background: #ff0;
            color: #000;
        }

        .admin-section {
            background: rgba(0, 255, 0, 0.05);
            border: 1px solid #0f0;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .admin-section h2 {
            color: #0ff;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #0f0;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 15px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            color: #0ff;
            margin-bottom: 5px;
            font-size: 14px;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px;
            background: #000;
            border: 1px solid #0f0;
            color: #0f0;
            font-family: 'Courier New', monospace;
            border-radius: 5px;
        }

        .checker-options {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            margin-top: 10px;
        }

        .checker-option {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .checker-option input[type="checkbox"] {
            width: auto;
            cursor: pointer;
        }

        .checker-option label {
            color: #0f0;
            cursor: pointer;
            margin: 0;
        }

        .users-list {
            margin-top: 20px;
        }

        .user-item {
            background: rgba(0, 255, 0, 0.1);
            border: 1px solid #0f0;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .user-item.temporary {
            border-color: #ff0;
            background: rgba(255, 255, 0, 0.1);
        }

        .user-item.credits {
            border-color: #f0f;
            background: rgba(255, 0, 255, 0.1);
        }

        .user-item.expired {
            border-color: #f00;
            background: rgba(255, 0, 0, 0.1);
            opacity: 0.7;
        }

        .user-info {
            flex: 1;
        }

        .user-role {
            color: #ff0;
            font-size: 12px;
        }

        .user-type {
            color: #0ff;
            font-size: 12px;
            margin-top: 5px;
        }

        .user-credits {
            color: #f0f;
            font-size: 14px;
            font-weight: bold;
            margin-top: 5px;
        }

        .user-checkers {
            color: #0f0;
            font-size: 11px;
            margin-top: 3px;
        }

        .user-expires {
            color: #f90;
            font-size: 11px;
            margin-top: 3px;
        }

        .success {
            background: rgba(0, 255, 0, 0.2);
            border: 1px solid #0f0;
            color: #0f0;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .error {
            background: rgba(255, 0, 0, 0.2);
            border: 1px solid #f00;
            color: #f00;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .type-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 10px;
            font-weight: bold;
            margin-left: 5px;
        }

        .type-permanent {
            background: #0f0;
            color: #000;
        }

        .type-temporary {
            background: #ff0;
            color: #000;
        }

        .type-credits {
            background: #f0f;
            color: #000;
        }
    </style>
</head>
<body>
    <div class="scanline"></div>
    <div class="container">
        <div class="header">
            <h1>⚙️ Painel Admin</h1>
            <p style="color: #0ff;">Bem-vindo, <?php echo $_SESSION['username']; ?>!</p>
        </div>

        <div class="nav-buttons">
            <a href="index.php" class="btn btn-primary">← Voltar ao Menu</a>
            <a href="?logout" class="btn btn-danger">Sair</a>
        </div>

        <?php if (isset($success_message)): ?>
            <div class="success"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="error"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <div class="admin-section">
            <h2>👤 Adicionar Usuário Permanente</h2>
            <form method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label>Nome de Usuário:</label>
                        <input type="text" name="new_username" required>
                    </div>
                    <div class="form-group">
                        <label>Senha:</label>
                        <input type="password" name="new_password" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Selecione os Checkers Permitidos:</label>
                    <div class="checker-options">
                        <div class="checker-option">
                            <input type="checkbox" name="checkers[]" value="paypal" id="perm_paypal">
                            <label for="perm_paypal">PayPal</label>
                        </div>
                        <div class="checker-option">
                            <input type="checkbox" name="checkers[]" value="preauth" id="perm_preauth">
                            <label for="perm_preauth">VBV</label>
                        </div>
                        <div class="checker-option">
                            <input type="checkbox" name="checkers[]" value="n7" id="perm_n7">
                            <label for="perm_n7">PAGARME</label>
                        </div>
                        <div class="checker-option">
                            <input type="checkbox" name="checkers[]" value="amazon1" id="perm_amazon1">
                            <label for="perm_amazon1">Amazon Prime</label>
                        </div>
                        <div class="checker-option">
                            <input type="checkbox" name="checkers[]" value="amazon2" id="perm_amazon2">
                            <label for="perm_amazon2">Amazon UK</label>
                        </div>
                        <div class="checker-option">
                            <input type="checkbox" name="checkers[]" value="cpfchecker" id="perm_cpfchecker">
                            <label for="perm_cpfchecker">CPF Checker</label>
                        </div>
                        <div class="checker-option">
                            <input type="checkbox" name="checkers[]" value="ggsitau" id="perm_ggsitau">
                            <label for="perm_ggsitau">GETNET</label>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label>Selecione as Consultas Permitidas:</label>
                    <div class="checker-options">
                        <div class="checker-option">
                            <input type="checkbox" name="consultas[]" value="cpfdatasus" id="perm_cpfdatasus">
                            <label for="perm_cpfdatasus">CPF DataSUS</label>
                        </div>
                        <div class="checker-option">
                            <input type="checkbox" name="consultas[]" value="nomedetran" id="perm_nomedetran">
                            <label for="perm_nomedetran">Nome Detran</label>
                        </div>
                        <div class="checker-option">
                            <input type="checkbox" name="consultas[]" value="obito" id="perm_obito">
                            <label for="perm_obito">Óbito</label>
                        </div>
                        <div class="checker-option">
                            <input type="checkbox" name="consultas[]" value="fotoba" id="perm_fotoba">
                            <label for="perm_fotoba">Foto BA</label>
                        </div>
                        <div class="checker-option">
                            <input type="checkbox" name="consultas[]" value="fotoce" id="perm_fotoce">
                            <label for="perm_fotoce">Foto CE</label>
                        </div>
                        <div class="checker-option">
                            <input type="checkbox" name="consultas[]" value="fotoma" id="perm_fotoma">
                            <label for="perm_fotoma">Foto MA</label>
                        </div>
                        <div class="checker-option">
                            <input type="checkbox" name="consultas[]" value="fotope" id="perm_fotope">
                            <label for="perm_fotope">Foto PE</label>
                        </div>
                        <div class="checker-option">
                            <input type="checkbox" name="consultas[]" value="fotorj" id="perm_fotorj">
                            <label for="perm_fotorj">Foto RJ</label>
                        </div>
                        <div class="checker-option">
                            <input type="checkbox" name="consultas[]" value="fotosp" id="perm_fotosp">
                            <label for="perm_fotosp">Foto SP</label>
                        </div>
                        <div class="checker-option">
                            <input type="checkbox" name="consultas[]" value="fototo" id="perm_fototo">
                            <label for="perm_fototo">Foto TO</label>
                        </div>
                    </div>
                </div>
                <button type="submit" name="add_permanent_user" class="btn btn-primary">Adicionar Usuário Permanente</button>
            </form>
        </div>

        <div class="admin-section">
            <h2>⏱️ Criar Acesso Temporário (Aluguel por Hora)</h2>
            <form method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label>Nome de Usuário:</label>
                        <input type="text" name="rental_username" required>
                    </div>
                    <div class="form-group">
                        <label>Senha:</label>
                        <input type="password" name="rental_password" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Quantidade de Horas:</label>
                    <input type="number" name="rental_hours" min="1" max="720" placeholder="Ex: 1, 24, 168 (1 semana)" required>
                </div>
                <div class="form-group">
                    <label>Selecione os Checkers Permitidos:</label>
                    <div class="checker-options">
                        <div class="checker-option">
                            <input type="checkbox" name="rental_checkers[]" value="paypal" id="rental_paypal">
                            <label for="rental_paypal">PayPal</label>
                        </div>
                        <div class="checker-option">
                            <input type="checkbox" name="rental_checkers[]" value="preauth" id="rental_preauth">
                            <label for="rental_preauth">VBV</label>
                        </div>
                        <div class="checker-option">
                            <input type="checkbox" name="rental_checkers[]" value="n7" id="rental_n7">
                            <label for="rental_n7">PAGARME</label>
                        </div>
                        <div class="checker-option">
                            <input type="checkbox" name="rental_checkers[]" value="amazon1" id="rental_amazon1">
                            <label for="rental_amazon1">Amazon Prime</label>
                        </div>
                        <div class="checker-option">
                            <input type="checkbox" name="rental_checkers[]" value="amazon2" id="rental_amazon2">
                            <label for="rental_amazon2">Amazon UK</label>
                        </div>
                        <div class="checker-option">
                            <input type="checkbox" name="rental_checkers[]" value="cpfchecker" id="rental_cpfchecker">
                            <label for="rental_cpfchecker">CPF Checker</label>
                        </div>
                        <div class="checker-option">
                            <input type="checkbox" name="rental_checkers[]" value="ggsitau" id="rental_ggsitau">
                            <label for="rental_ggsitau">GGs ITAU</label>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label>Selecione as Consultas Permitidas:</label>
                    <div class="checker-options">
                        <div class="checker-option">
                            <input type="checkbox" name="rental_consultas[]" value="cpfdatasus" id="rental_cpfdatasus">
                            <label for="rental_cpfdatasus">CPF DataSUS</label>
                        </div>
                        <div class="checker-option">
                            <input type="checkbox" name="rental_consultas[]" value="nomedetran" id="rental_nomedetran">
                            <label for="rental_nomedetran">Nome Detran</label>
                        </div>
                        <div class="checker-option">
                            <input type="checkbox" name="rental_consultas[]" value="obito" id="rental_obito">
                            <label for="rental_obito">Óbito</label>
                        </div>
                        <div class="checker-option">
                            <input type="checkbox" name="rental_consultas[]" value="fotoba" id="rental_fotoba">
                            <label for="rental_fotoba">Foto BA</label>
                        </div>
                        <div class="checker-option">
                            <input type="checkbox" name="rental_consultas[]" value="fotoce" id="rental_fotoce">
                            <label for="rental_fotoce">Foto CE</label>
                        </div>
                        <div class="checker-option">
                            <input type="checkbox" name="rental_consultas[]" value="fotoma" id="rental_fotoma">
                            <label for="rental_fotoma">Foto MA</label>
                        </div>
                        <div class="checker-option">
                            <input type="checkbox" name="rental_consultas[]" value="fotope" id="rental_fotope">
                            <label for="rental_fotope">Foto PE</label>
                        </div>
                        <div class="checker-option">
                            <input type="checkbox" name="rental_consultas[]" value="fotorj" id="rental_fotorj">
                            <label for="rental_fotorj">Foto RJ</label>
                        </div>
                        <div class="checker-option">
                            <input type="checkbox" name="rental_consultas[]" value="fotosp" id="rental_fotosp">
                            <label for="rental_fotosp">Foto SP</label>
                        </div>
                        <div class="checker-option">
                            <input type="checkbox" name="rental_consultas[]" value="fototo" id="rental_fototo">
                            <label for="rental_fototo">Foto TO</label>
                        </div>
                    </div>
                </div>
                <button type="submit" name="add_rental_user" class="btn btn-primary">Criar Acesso Temporário</button>
            </form>
        </div>

        <div class="admin-section">
            <h2>💳 Criar Usuário por Créditos (2 créditos por LIVE)</h2>
            <form method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label>Nome de Usuário:</label>
                        <input type="text" name="credit_username" required>
                    </div>
                    <div class="form-group">
                        <label>Senha:</label>
                        <input type="password" name="credit_password" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Quantidade de Créditos:</label>
                    <input type="number" name="credit_amount" min="2" placeholder="Mínimo: 2 créditos" required>
                    <small style="color: #0ff;">Cada LIVE consome 2 créditos</small>
                </div>
                <div class="form-group">
                    <label>Selecione os Checkers Permitidos:</label>
                    <div class="checker-options">
                        <div class="checker-option">
                            <input type="checkbox" name="credit_checkers[]" value="paypal" id="credit_paypal">
                            <label for="credit_paypal">PayPal</label>
                        </div>
                        <div class="checker-option">
                            <input type="checkbox" name="credit_checkers[]" value="preauth" id="credit_preauth">
                            <label for="credit_preauth">VBV</label>
                        </div>
                        <div class="checker-option">
                            <input type="checkbox" name="credit_checkers[]" value="n7" id="credit_n7">
                            <label for="credit_n7">PAGARME</label>
                        </div>
                        <div class="checker-option">
                            <input type="checkbox" name="credit_checkers[]" value="amazon1" id="credit_amazon1">
                            <label for="credit_amazon1">Amazon Prime</label>
                        </div>
                        <div class="checker-option">
                            <input type="checkbox" name="credit_checkers[]" value="amazon2" id="credit_amazon2">
                            <label for="credit_amazon2">Amazon UK</label>
                        </div>
                        <div class="checker-option">
                            <input type="checkbox" name="credit_checkers[]" value="cpfchecker" id="credit_cpfchecker">
                            <label for="credit_cpfchecker">CPF Checker</label>
                        </div>
                        <div class="checker-option">
                            <input type="checkbox" name="credit_checkers[]" value="ggsitau" id="credit_ggsitau">
                            <label for="credit_ggsitau">GGs ITAU</label>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label>Selecione as Consultas Permitidas:</label>
                    <div class="checker-options">
                        <div class="checker-option">
                            <input type="checkbox" name="credit_consultas[]" value="cpfdatasus" id="credit_cpfdatasus">
                            <label for="credit_cpfdatasus">CPF DataSUS</label>
                        </div>
                        <div class="checker-option">
                            <input type="checkbox" name="credit_consultas[]" value="nomedetran" id="credit_nomedetran">
                            <label for="credit_nomedetran">Nome Detran</label>
                        </div>
                        <div class="checker-option">
                            <input type="checkbox" name="credit_consultas[]" value="obito" id="credit_obito">
                            <label for="credit_obito">Óbito</label>
                        </div>
                        <div class="checker-option">
                            <input type="checkbox" name="credit_consultas[]" value="fotoba" id="credit_fotoba">
                            <label for="credit_fotoba">Foto BA</label>
                        </div>
                        <div class="checker-option">
                            <input type="checkbox" name="credit_consultas[]" value="fotoce" id="credit_fotoce">
                            <label for="credit_fotoce">Foto CE</label>
                        </div>
                        <div class="checker-option">
                            <input type="checkbox" name="credit_consultas[]" value="fotoma" id="credit_fotoma">
                            <label for="credit_fotoma">Foto MA</label>
                        </div>
                        <div class="checker-option">
                            <input type="checkbox" name="credit_consultas[]" value="fotope" id="credit_fotope">
                            <label for="credit_fotope">Foto PE</label>
                        </div>
                        <div class="checker-option">
                            <input type="checkbox" name="credit_consultas[]" value="fotorj" id="credit_fotorj">
                            <label for="credit_fotorj">Foto RJ</label>
                        </div>
                        <div class="checker-option">
                            <input type="checkbox" name="credit_consultas[]" value="fotosp" id="credit_fotosp">
                            <label for="credit_fotosp">Foto SP</label>
                        </div>
                        <div class="checker-option">
                            <input type="checkbox" name="credit_consultas[]" value="fototo" id="credit_fototo">
                            <label for="credit_fototo">Foto TO</label>
                        </div>
                    </div>
                </div>
                <button type="submit" name="add_credit_user" class="btn btn-warning">Criar Usuário por Créditos</button>
            </form>
        </div>

        <div class="admin-section">
            <h2>💰 Recarregar Créditos</h2>
            <form method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label>Nome de Usuário:</label>
                        <input type="text" name="recharge_username" placeholder="Digite o nome do usuário" required>
                    </div>
                    <div class="form-group">
                        <label>Créditos para Adicionar:</label>
                        <input type="number" name="add_credits" min="1" placeholder="Quantidade de créditos" required>
                    </div>
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
                        $creditsText = "💳 Créditos: $credits (2 por LIVE)";
                    }

                    $itemClass = 'user-item';
                    if ($data['type'] === 'temporary') {
                        $itemClass .= $isExpired ? ' expired' : ' temporary';
                    } elseif ($data['type'] === 'credits') {
                        $itemClass .= ' credits';
                    }

                    $toolsList = implode(', ', $data['tools'] ?? $data['checkers'] ?? ['paypal']);
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
                            <div class="user-role">
                                <?php 
                                echo $data['role'] === 'admin' ? '⭐ Administrador' : '👤 Usuário';
                                ?>
                            </div>
                            <div class="user-type">
                                <?php 
                                if ($data['type'] === 'permanent') {
                                    echo '♾️ Acesso Permanente';
                                } elseif ($data['type'] === 'temporary') {
                                    echo '⏱️ Acesso Temporário (' . $data['hours'] . ' hora(s))';
                                } else {
                                    echo '💰 Acesso por Créditos';
                                }
                                ?>
                            </div>
                            <?php if ($creditsText): ?>
                                <div class="user-credits"><?php echo $creditsText; ?></div>
                            <?php endif; ?>
                            <div class="user-checkers">
                                🔧 Ferramentas: <?php echo strtoupper($toolsList); ?>
                            </div>
                            <?php if ($data['type'] === 'temporary'): ?>
                                <div class="user-expires"><?php echo $expiresText; ?></div>
                            <?php endif; ?>
                        </div>
                        <?php if ($username !== 'save'): ?>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="remove_username" value="<?php echo $username; ?>">
                                <button type="submit" name="remove_user" class="btn btn-danger" onclick="return confirm('Deseja remover este usuário?')">🗑️ Remover</button>
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

// Se estiver acessando uma ferramenta específica
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
        'preauth' => 'VBV',
        'n7' => 'PAGARME',
        'amazon1' => 'Amazon Prime Checker',
        'amazon2' => 'Amazon UK Checker',
        'cpfchecker' => 'CPF Checker',
        'ggsitau' => 'GGs ITAU',
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

    $toolName = $toolNames[$selectedTool] ?? 'Ferramenta';
    $isChecker = in_array($selectedTool, ['paypal', 'preauth', 'n7', 'amazon1', 'amazon2', 'cpfchecker', 'ggsitau']);
    $isAmazonChecker = in_array($selectedTool, ['amazon1', 'amazon2']);

    // Configurações específicas por tipo de ferramenta
    if ($isChecker) {
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
    } else {
        $inputLabel = "🔍 Cole os CPFs abaixo (um por linha) - MÁXIMO 200 CPFS";
        $inputFormat = "Formato: apenas números (11 dígitos)";
        $inputExample = "12345678900\n98765432100\n11122233344\n22233344455";
        $placeholder = "Cole os CPFs aqui (apenas números):\n\n12345678900\n98765432100\n\nMÁXIMO: 200 CPFs por vez";
        $howToUse = [
            "1. Cole os CPFs no formato: <strong>apenas números (sem pontos ou traços)</strong>",
            "2. Um CPF por linha (11 dígitos cada, máximo 200 por verificação)",
            "3. Clique em <strong>Iniciar</strong> para começar a consulta",
            "4. Os resultados aparecerão em tempo real com todas as informações exatamente como a API retorna"
        ];
    }

    $timeLeftText = '';
    $creditsText = '';
    
    if ($userType === 'temporary') {
        $timeLeft = $userData['expires_at'] - time();
        $hoursLeft = floor($timeLeft / 3600);
        $minutesLeft = floor(($timeLeft % 3600) / 60);
        $timeLeftText = "⏱️ Tempo restante: {$hoursLeft}h {$minutesLeft}min";
    } elseif ($userType === 'credits') {
        $creditsText = "💳 Créditos disponíveis: {$userCredits} (2 créditos por LIVE)";
    }
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $toolName; ?> - SaveFullBlack</title>
    <?php echo $music_embed; ?>
    <?php echo $security_script; ?>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: #000;
            color: #0f0;
            font-family: 'Courier New', monospace;
            overflow-x: hidden;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            padding: 20px;
            background: rgba(0, 255, 0, 0.05);
            border: 2px solid #0f0;
            border-radius: 10px;
            position: relative;
        }

        .header h1 {
            font-size: 36px;
            color: #0f0;
            text-shadow: 0 0 20px rgba(0, 255, 0, 0.8);
        }

        .header p {
            color: #0ff;
            margin-top: 10px;
        }

        .user-info {
            position: absolute;
            top: 20px;
            right: 20px;
            color: #0ff;
            font-size: 12px;
            text-align: right;
        }

        .how-to-section {
            background: rgba(0, 255, 255, 0.05);
            border: 1px solid #0ff;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .how-to-section h3 {
            color: #0ff;
            font-size: 18px;
            margin-bottom: 15px;
            border-bottom: 1px solid #0ff;
            padding-bottom: 8px;
        }

        .how-to-section ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .how-to-section ul li {
            color: #0f0;
            font-size: 14px;
            padding: 8px 0;
            padding-left: 25px;
            position: relative;
            line-height: 1.6;
        }

        .how-to-section ul li::before {
            content: '▶';
            position: absolute;
            left: 0;
            color: #0ff;
        }

        .example-section {
            background: rgba(255, 255, 0, 0.05);
            border: 1px solid #ff0;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .example-section h3 {
            color: #ff0;
            font-size: 18px;
            margin-bottom: 15px;
            border-bottom: 1px solid #ff0;
            padding-bottom: 8px;
        }

        .example-box {
            background: #000;
            border: 1px solid #ff0;
            border-radius: 5px;
            padding: 15px;
        }

        .format-label {
            color: #0ff;
            font-size: 12px;
            margin-bottom: 10px;
            font-weight: bold;
        }

        .example-box pre {
            color: #0f0;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            margin: 0;
            line-height: 1.8;
            white-space: pre-wrap;
        }

        .input-section h3 {
            color: #0f0;
            font-size: 16px;
            margin-bottom: 10px;
            padding-bottom: 8px;
            border-bottom: 1px solid #0f0;
        }

        .time-left, .credits-info {
            color: #ff0;
            font-size: 14px;
            margin-top: 10px;
            text-align: center;
            padding: 10px;
            background: rgba(255, 255, 0, 0.1);
            border: 1px solid #ff0;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .credits-info {
            color: #f0f;
            border-color: #f0f;
            background: rgba(255, 0, 255, 0.1);
        }

        .nav-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        .btn {
            padding: 12px 30px;
            border: 2px solid;
            background: #000;
            cursor: pointer;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            border-radius: 5px;
            transition: all 0.3s;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            color: #0f0;
            border-color: #0f0;
        }

        .btn-primary:hover {
            background: #0f0;
            color: #000;
        }

        .btn-start {
            color: #0f0;
            border-color: #0f0;
        }

        .btn-start:hover {
            background: #0f0;
            color: #000;
        }

        .btn-stop {
            color: #f00;
            border-color: #f00;
        }

        .btn-stop:hover {
            background: #f00;
            color: #000;
        }

        .btn-clear {
            color: #ff0;
            border-color: #ff0;
        }

        .btn-clear:hover {
            background: #ff0;
            color: #000;
        }

        .input-section {
            margin-bottom: 20px;
        }

        .input-section textarea {
            width: 100%;
            height: 200px;
            background: #000;
            color: #0f0;
            border: 1px solid #0f0;
            padding: 15px;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            resize: vertical;
            border-radius: 5px;
        }

        .input-section textarea::placeholder {
            color: #0a0;
        }

        .controls {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .loading {
            display: none;
            color: #ff0;
            font-size: 14px;
            margin-top: 10px;
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
            display: flex;
            gap: 20px;
            padding: 15px;
            background: rgba(0, 255, 0, 0.05);
            border: 1px solid #0f0;
            border-radius: 5px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }

        .stat-item {
            flex: 1;
            min-width: 150px;
            text-align: center;
        }

        .stat-label {
            color: #0ff;
            font-size: 12px;
        }

        .stat-value {
            color: #0f0;
            font-size: 24px;
            font-weight: bold;
        }

        .results-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .result-box {
            border: 1px solid;
            padding: 15px;
            border-radius: 5px;
            min-height: 400px;
            max-height: 600px;
            overflow-y: auto;
        }

        .result-box h3 {
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid;
        }

        .live-box {
            border-color: #0f0;
            background: rgba(0, 255, 0, 0.05);
        }

        .live-box h3 {
            color: #0f0;
            border-color: #0f0;
        }

        .die-box {
            border-color: #f00;
            background: rgba(255, 0, 0, 0.05);
        }

        .die-box h3 {
            color: #f00;
            border-color: #f00;
        }

        .result-item {
            margin-bottom: 8px;
            padding: 10px;
            border-radius: 3px;
            font-size: 12px;
            animation: fadeIn 0.3s;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .result-item.live {
            background: rgba(0, 255, 0, 0.1);
            color: #0f0;
            border-left: 3px solid #0f0;
        }

        .result-item.die {
            background: rgba(255, 0, 0, 0.1);
            color: #f00;
            border-left: 3px solid #f00;
        }

        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #000;
        }

        ::-webkit-scrollbar-thumb {
            background: #0f0;
            border-radius: 4px;
        }

        .credits-counter {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: rgba(255, 0, 255, 0.9);
            color: #fff;
            padding: 10px 15px;
            border-radius: 5px;
            font-weight: bold;
            z-index: 1000;
            border: 2px solid #f0f;
        }
        
        .remaining-items {
            color: #0ff;
            font-size: 12px;
            margin-top: 10px;
            text-align: center;
            padding: 5px;
            background: rgba(0, 255, 255, 0.1);
            border: 1px solid #0ff;
            border-radius: 5px;
            display: none;
        }
        
        .remaining-items.active {
            display: block;
        }
    </style>
</head>
<body>
    <div class="scanline"></div>
    
    <?php if ($userType === 'credits'): ?>
    <div class="credits-counter" id="creditsCounter">
        💳 Créditos: <span id="currentCredits"><?php echo $userCredits; ?></span>
    </div>
    <?php endif; ?>
    
    <div class="container">
        <div class="header">
            <h1><?php echo $toolName; ?></h1>
            <p>Sistema de Verificação</p>
            <div class="user-info">
                Usuário: <?php echo $_SESSION['username']; ?>
                <?php if ($userType === 'temporary'): ?>
                    <br><span style="color: #ff0;">⏱️ TEMPORÁRIO</span>
                <?php elseif ($userType === 'credits'): ?>
                    <br><span style="color: #f0f;">💰 CRÉDITOS</span>
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
        </div>

        <div class="how-to-section">
            <h3>📖 Como Usar</h3>
            <ul>
                <?php foreach ($howToUse as $step): ?>
                    <li><?php echo $step; ?></li>
                <?php endforeach; ?>
                <?php if ($userType === 'credits'): ?>
                    <li><strong>💡 Cada LIVE aprovada consome 2 créditos!</strong></li>
                <?php endif; ?>
                <li><strong>⏱️ Delay automático de 4 segundos entre cada verificação</strong></li>
                <li><strong>📊 Os cartões/CPFs são removidos da lista conforme são processados</strong></li>
            </ul>
        </div>

        <div class="example-section">
            <h3>💡 Exemplo de Formato</h3>
            <div class="example-box">
                <div class="format-label"><?php echo $inputFormat; ?></div>
                <pre><?php echo htmlspecialchars($inputExample); ?></pre>
            </div>
        </div>

        <?php if ($isAmazonChecker): ?>
        <div class="input-section">
            <h3>🔐 Cookies da Amazon</h3>
            <textarea id="amazonCookies" placeholder="Cole aqui os cookies da amazon.com..." style="height: 100px;"></textarea>
            <p style="color: #ff0; font-size: 12px; margin-top: 10px;">⚠️ IMPORTANTE: Cole os cookies completos da sua conta Amazon</p>
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
            <button class="btn btn-stop" onclick="stopCheck()">⬛ Parar</button>
            <button class="btn btn-clear" onclick="clearAll()">🗑 Limpar</button>
            <div class="loading" id="loading">⏳ Processando... (Aguarde 4 segundos entre cada verificação)</div>
        </div>

        <div class="stats">
            <div class="stat-item">
                <div class="stat-label">Total</div>
                <div class="stat-value" id="totalCount">0</div>
            </div>
            <div class="stat-item">
                <div class="stat-label"><?php echo $isChecker ? '✅ Aprovados' : '✅ Encontrados'; ?></div>
                <div class="stat-value" id="liveCount" style="color: #0f0;">0</div>
            </div>
            <div class="stat-item">
                <div class="stat-label"><?php echo $isChecker ? '❌ Reprovados' : '❌ Não Encontrados'; ?></div>
                <div class="stat-value" id="dieCount" style="color: #f00;">0</div>
            </div>
            <div class="stat-item">
                <div class="stat-label">⚡ Processados</div>
                <div class="stat-value" id="processedCount" style="color: #0ff;">0</div>
            </div>
        </div>

        <div class="results-container">
            <div class="result-box live-box">
                <h3><?php echo $isChecker ? '✅ APROVADOS' : '✅ ENCONTRADOS'; ?></h3>
                <div id="liveResults"></div>
            </div>
            <div class="result-box die-box">
                <h3><?php echo $isChecker ? '❌ REPROVADOS' : '❌ NÃO ENCONTRADOS / ERRO'; ?></h3>
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
        const MAX_ITEMS = 200; // Máximo de 200 itens por vez
        
        // CORREÇÃO NO JAVASCRIPT: Verificar se é LIVE de forma mais flexível
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
            
            // Verificar se contém qualquer um dos padrões
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
                alert('⏱️ Seu tempo de acesso expirou! Você ainda pode acessar o site, mas não pode usar os checkers.');
                document.querySelector('.btn-start').disabled = true;
                document.querySelector('.btn-start').style.opacity = '0.5';
                document.querySelector('.btn-start').style.cursor = 'not-allowed';
            } else {
                const hoursLeft = Math.floor(timeLeft / 3600);
                const minutesLeft = Math.floor((timeLeft % 3600) / 60);
                document.getElementById('timeLeft').textContent = `⏱️ Tempo restante: ${hoursLeft}h ${minutesLeft}min`;
            }
        }, 60000);
        <?php endif; ?>

        function updateCreditsDisplay() {
            if (userType === 'credits') {
                document.getElementById('currentCredits').textContent = currentCredits;
                document.getElementById('creditsInfo').textContent = `💳 Créditos disponíveis: ${currentCredits} (2 créditos por LIVE)`;
                
                if (currentCredits < 2) {
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

            if (userType === 'credits' && currentCredits < 2) {
                alert('💳 Créditos insuficientes! Você precisa de pelo menos 2 créditos para iniciar uma verificação.');
                return;
            }

            items = input.split('\n').filter(line => line.trim());
            
            // Limitar a 200 itens
            if (items.length > MAX_ITEMS) {
                alert(`⚠️ MÁXIMO ${MAX_ITEMS} ITENS POR VEZ! Foram selecionados apenas os primeiros ${MAX_ITEMS} itens.`);
                items = items.slice(0, MAX_ITEMS);
                // Atualizar o textarea com apenas os 200 primeiros itens
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

                // Verificar se é uma resposta de erro de segurança
                if (text.includes('pornolandia.xxx') || text.includes('cybersecofc')) {
                    // Verificar se é realmente um erro de segurança ou apenas um resultado normal
                    if (text.includes('error') && text.includes('message') && text.includes('pornolandia.xxx')) {
                        alert('⚠️ Sistema de segurança ativado! Verificação interrompida.');
                        stopCheck();
                        return;
                    }
                    // Se não for um erro JSON, provavelmente é apenas um resultado normal
                }

                // Usar a nova função para verificar se é LIVE
                const isLive = checkIfLive(text);
                
                // Se for LIVE e usuário for tipo créditos, descontar
                if (isLive && userType === 'credits') {
                    currentCredits -= 2;
                    updateCreditsDisplay();
                }
                
                addResult(item, text, isLive);
                
                // Remover o item processado da lista
                items[currentIndex] = '';
                updateRemainingItems();

            } catch (error) {
                console.error('Error:', error);
                addResult(item, 'Erro: ' + error.message, false);
                
                // Remover o item processado mesmo em caso de erro
                items[currentIndex] = '';
                updateRemainingItems();
            }

            currentIndex++;
            document.getElementById('processedCount').textContent = currentIndex;

            if (isChecking && currentIndex < items.length) {
                // Delay de 4 segundos antes do próximo processamento
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

            // Exibir exatamente o que a API retorna
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

        // Inicializar display de créditos
        updateCreditsDisplay();
    </script>
</body>
</html>
<?php
exit;
}

// Menu principal de seleção de ferramentas
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
    $creditsText = "💳 Créditos disponíveis: {$userCredits}";
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu Principal - SaveFullBlack</title>
    <?php echo $music_embed; ?>
    <?php echo $security_script; ?>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: #000;
            color: #0f0;
            font-family: 'Courier New', monospace;
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            padding: 30px;
            background: rgba(0, 255, 0, 0.05);
            border: 2px solid #0f0;
            border-radius: 10px;
            position: relative;
        }

        .header h1 {
            font-size: 48px;
            background: linear-gradient(90deg, #0f0, #0ff, #f0f, #ff0, #0f0);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            background-size: 200% 100%;
            animation: rainbow 3s linear infinite;
        }

        @keyframes rainbow {
            0% { background-position: 0% 50%; }
            100% { background-position: 200% 50%; }
        }

        .user-info {
            position: absolute;
            top: 20px;
            right: 20px;
            color: #0ff;
            font-size: 14px;
            text-align: right;
        }

        .time-left, .credits-info {
            color: #ff0;
            font-size: 16px;
            margin: 20px 0;
            text-align: center;
            padding: 15px;
            background: rgba(255, 255, 0, 0.1);
            border: 1px solid #ff0;
            border-radius: 5px;
        }

        .credits-info {
            color: #f0f;
            border-color: #f0f;
            background: rgba(255, 0, 255, 0.1);
        }

        .nav-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
            justify-content: flex-end;
        }

        .btn {
            padding: 12px 30px;
            border: 2px solid;
            background: #000;
            cursor: pointer;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            border-radius: 5px;
            transition: all 0.3s;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
        }

        .btn-admin {
            color: #0ff;
            border-color: #0ff;
        }

        .btn-admin:hover {
            background: #0ff;
            color: #000;
        }

        .btn-logout {
            color: #f0f;
            border-color: #f0f;
        }

        .btn-logout:hover {
            background: #f0f;
            color: #000;
        }

        .tools-section {
            margin-bottom: 40px;
        }

        .tools-section h2 {
            color: #0ff;
            font-size: 24px;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #0f0;
        }

        .tools-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }

        .tool-card {
            background: rgba(0, 255, 0, 0.05);
            border: 2px solid #0f0;
            border-radius: 10px;
            padding: 25px;
            transition: all 0.3s;
            cursor: pointer;
            text-decoration: none;
            color: #0f0;
            display: block;
        }

        .tool-card:hover {
            background: rgba(0, 255, 0, 0.15);
            border-color: #0ff;
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 255, 0, 0.3);
        }

        .tool-card h3 {
            font-size: 20px;
            margin-bottom: 10px;
            color: #0ff;
        }

        .tool-card p {
            font-size: 12px;
            color: #0a0;
            line-height: 1.6;
        }

        .tool-icon {
            font-size: 32px;
            margin-bottom: 10px;
        }

        .access-type {
            position: fixed;
            bottom: 20px;
            left: 20px;
            background: rgba(0, 255, 0, 0.9);
            color: #000;
            padding: 10px 15px;
            border-radius: 5px;
            font-weight: bold;
            z-index: 1000;
            border: 2px solid #0f0;
        }

        .access-type.temporary {
            background: rgba(255, 255, 0, 0.9);
            color: #000;
            border-color: #ff0;
        }

        .access-type.credits {
            background: rgba(255, 0, 255, 0.9);
            color: #fff;
            border-color: #f0f;
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
            echo '💰 ACESSO POR CRÉDITOS: ' . $userCredits . ' créditos';
        }
        ?>
    </div>
    
    <div class="container">
        <div class="header">
            <h1>CYBERSECOFC APIS</h1>
            <p style="color: #0ff; margin-top: 10px;">Selecione a Ferramenta</p>
            <div class="user-info">
                👤 <?php echo $_SESSION['username']; ?>
                <?php if ($_SESSION['role'] === 'admin'): ?>
                    <br><span style="color: #ff0;">⭐ ADMIN</span>
                <?php elseif ($userType === 'temporary'): ?>
                    <br><span style="color: #ff0;">⏱️ TEMPORÁRIO</span>
                <?php elseif ($userType === 'credits'): ?>
                    <br><span style="color: #f0f;">💰 CRÉDITOS</span>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($userType === 'temporary'): ?>
            <div class="time-left" id="timeLeft"><?php echo $timeLeftText; ?></div>
        <?php elseif ($userType === 'credits'): ?>
            <div class="credits-info" id="creditsInfo"><?php echo $creditsText; ?></div>
        <?php endif; ?>

        <div class="nav-buttons">
            <?php if ($_SESSION['role'] === 'admin'): ?>
                <a href="?admin=true" class="btn btn-admin">⚙ Painel Admin</a>
            <?php endif; ?>
            <a href="?logout" class="btn btn-logout">🚪 Sair</a>
        </div>

        <?php
        $hasCheckers = array_intersect(['paypal', 'preauth', 'n7', 'amazon1', 'amazon2', 'cpfchecker', 'ggsitau'], $availableTools);
        $hasConsultas = array_intersect(['cpfdatasus', 'nomedetran', 'obito', 'fotoba', 'fotoce', 'fotoma', 'fotope', 'fotorj', 'fotosp', 'fototo'], $availableTools);
        ?>

        <?php if ($hasCheckers): ?>
        <div class="tools-section">
            <h2>💳 CHECKERS DE CARTÃO</h2>
            <div class="tools-grid">
                <?php if (in_array('paypal', $availableTools)): ?>
                <a href="?tool=paypal" class="tool-card">
                    <div class="tool-icon">💰</div>
                    <h3>PayPal V2</h3>
                    <p>Verificação completa de cartões via PayPal. Retorna status de aprovação e detalhes da transação.</p>
                </a>
                <?php endif; ?>

                <?php if (in_array('preauth', $availableTools)): ?>
                <a href="?tool=preauth" class="tool-card">
                    <div class="tool-icon">🔐</div>
                    <h3>VBV</h3>
                    <p>VERIFICAÇAO DE 3DS GGS</p>
                </a>
                <?php endif; ?>

                <?php if (in_array('n7', $availableTools)): ?>
                <a href="?tool=n7" class="tool-card">
                    <div class="tool-icon">⚡</div>
                    <h3>PAGARME</h3>
                    <p>Checker SAINDO MASTER-VISA-AMEX</p>
                </a>
                <?php endif; ?>

                <?php if (in_array('amazon1', $availableTools)): ?>
                <a href="?tool=amazon1" class="tool-card">
                    <div class="tool-icon">📦</div>
                    <h3>Amazon Prime Checker</h3>
                    <p>Verifica cartões via Amazon Prime US. Requer cookies da amazon.com</p>
                </a>
                <?php endif; ?>

                <?php if (in_array('amazon2', $availableTools)): ?>
                <a href="?tool=amazon2" class="tool-card">
                    <div class="tool-icon">🛒</div>
                    <h3>Amazon UK Checker</h3>
                    <p>Verifica cartões via Amazon UK. Requer cookies da amazon.co.uk</p>
                </a>
                <?php endif; ?>

                <?php if (in_array('cpfchecker', $availableTools)): ?>
                <a href="?tool=cpfchecker" class="tool-card">
                    <div class="tool-icon">🔍</div>
                    <h3>CPF Checker</h3>
                    <p>Verificação de CPF completa com validação de dados</p>
                </a>
                <?php endif; ?>

                <?php if (in_array('ggsitau', $availableTools)): ?>
                <a href="?tool=ggsitau" class="tool-card">
                    <div class="tool-icon">🏦</div>
                    <h3>GGs ITAU</h3>
                    <p>APENAS RETONOS MASTER-VISA</p>
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($hasConsultas): ?>
        <div class="tools-section">
            <h2>🔍 CONSULTAS CPF</h2>
            <div class="tools-grid">
                <?php if (in_array('cpfdatasus', $availableTools)): ?>
                <a href="?tool=cpfdatasus" class="tool-card">
                    <div class="tool-icon">📋</div>
                    <h3>CPF DataSUS</h3>
                    <p>Consulta completa no banco de dados do DataSUS. Retorna informações detalhadas do CPF.</p>
                </a>
                <?php endif; ?>

                <?php if (in_array('nomedetran', $availableTools)): ?>
                <a href="?tool=nomedetran" class="tool-card">
                    <div class="tool-icon">🚗</div>
                    <h3>Nome Detran</h3>
                    <p>Busca nome completo através do CPF no sistema Detran.</p>
                </a>
                <?php endif; ?>

                <?php if (in_array('obito', $availableTools)): ?>
                <a href="?tool=obito" class="tool-card">
                    <div class="tool-icon">⚰️</div>
                    <h3>Consulta Óbito</h3>
                    <p>Verifica registros de óbito através do CPF fornecido.</p>
                </a>
                <?php endif; ?>

                <?php if (in_array('fotoba', $availableTools)): ?>
                <a href="?tool=fotoba" class="tool-card">
                    <div class="tool-icon">📸</div>
                    <h3>Foto BA</h3>
                    <p>Consulta foto da CNH - Bahia</p>
                </a>
                <?php endif; ?>

                <?php if (in_array('fotoce', $availableTools)): ?>
                <a href="?tool=fotoce" class="tool-card">
                    <div class="tool-icon">📸</div>
                    <h3>Foto CE</h3>
                    <p>Consulta foto da CNH - Ceará</p>
                </a>
                <?php endif; ?>

                <?php if (in_array('fotoma', $availableTools)): ?>
                <a href="?tool=fotoma" class="tool-card">
                    <div class="tool-icon">📸</div>
                    <h3>Foto MA</h3>
                    <p>Consulta foto da CNH - Maranhão</p>
                </a>
                <?php endif; ?>

                <?php if (in_array('fotope', $availableTools)): ?>
                <a href="?tool=fotope" class="tool-card">
                    <div class="tool-icon">📸</div>
                    <h3>Foto PE</h3>
                    <p>Consulta foto da CNH - Pernambuco</p>
                </a>
                <?php endif; ?>

                <?php if (in_array('fotorj', $availableTools)): ?>
                <a href="?tool=fotorj" class="tool-card">
                    <div class="tool-icon">📸</div>
                    <h3>Foto RJ</h3>
                    <p>Consulta foto da CNH - Rio de Janeiro</p>
                </a>
                <?php endif; ?>

                <?php if (in_array('fotosp', $availableTools)): ?>
                <a href="?tool=fotosp" class="tool-card">
                    <div class="tool-icon">📸</div>
                    <h3>Foto SP</h3>
                    <p>Consulta foto da CNH - São Paulo</p>
                </a>
                <?php endif; ?>

                <?php if (in_array('fototo', $availableTools)): ?>
                <a href="?tool=fototo" class="tool-card">
                    <div class="tool-icon">📸</div>
                    <h3>Foto TO</h3>
                    <p>Consulta foto da CNH - Tocantins</p>
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script>
        <?php if ($userType === 'temporary'): ?>
        setInterval(function() {
            const expiresAt = <?php echo $userData['expires_at'] ?? time(); ?>;
            const now = Math.floor(Date.now() / 1000);
            const timeLeft = expiresAt - now;

            if (timeLeft <= 0) {
                document.getElementById('timeLeft').textContent = '⏱️ TEMPO ESGOTADO';
                document.getElementById('timeLeft').style.color = '#f00';
                document.getElementById('timeLeft').style.background = 'rgba(255, 0, 0, 0.1)';
                document.getElementById('timeLeft').style.borderColor = '#f00';
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
