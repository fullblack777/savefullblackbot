<?php
session_start();

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

// Função para verificar se o acesso temporário expirou
function checkTemporaryAccess($userData) {
    if ($userData['type'] === 'temporary') {
        $expiresAt = $userData['expires_at'];
        if (time() > $expiresAt) {
            return false;
        }
    }
    return true;
}

// Processar login
if (isset($_POST['login'])) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    $users = loadUsers();

    if (isset($users[$username]) && password_verify($password, $users[$username]['password'])) {
        if (!checkTemporaryAccess($users[$username])) {
            $login_error = 'Seu acesso expirou! Entre em contato com o administrador.';
        } else {
            $_SESSION['logged_in'] = true;
            $_SESSION['username'] = $username;
            $_SESSION['role'] = $users[$username]['role'];
            $_SESSION['type'] = $users[$username]['type'];
            $_SESSION['tools'] = $users[$username]['tools'] ?? ['paypal'];

            if ($users[$username]['type'] === 'temporary') {
                $_SESSION['expires_at'] = $users[$username]['expires_at'];
            }

            header('Location: index.php');
            exit;
        }
    } else {
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
            'tools' => $selected_tools
        ];
        saveUsers($users);

        $expireDate = date('d/m/Y H:i:s', $expiresAt);
        $success_message = "Acesso temporário criado para '$rental_username' por $rental_hours hora(s) com ferramentas: " . implode(', ', $selected_tools) . ". Expira em: $expireDate";
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

// Processar requisições AJAX das ferramentas
if (isset($_GET['action']) && $_GET['action'] === 'check' && isset($_GET['lista']) && isset($_GET['tool'])) {
    if (!isset($_SESSION['logged_in'])) {
        echo json_encode(['status' => 'error', 'message' => 'Não autorizado']);
        exit;
    }

    if ($_SESSION['type'] === 'temporary' && time() > $_SESSION['expires_at']) {
        echo json_encode(['status' => 'error', 'message' => 'Seu acesso expirou!']);
        exit;
    }

    $tool = $_GET['tool'];
    if (!in_array($tool, $_SESSION['tools'])) {
        echo json_encode(['status' => 'error', 'message' => 'Você não tem permissão para usar esta ferramenta']);
        exit;
    }

    error_reporting(0);
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
            
            // Verificar se o output é JSON válido
            $json_data = json_decode($output, true);
            
            if ($json_data !== null) {
                // Se for JSON, retornar como JSON
                echo json_encode($json_data);
            } else {
                // Se não for JSON, formatar como resposta HTML
                if (strpos($output, 'Aprovada') !== false || strpos($output, 'success') !== false) {
                    echo json_encode(['status' => 'Aprovada', 'message' => $output]);
                } else {
                    echo json_encode(['status' => 'Reprovada', 'message' => $output]);
                }
            }
            
        } elseif ($tool === 'ggsitau') {
            $card = $lista;
            $parts = explode('|', $card);
            if (count($parts) != 4) {
                $result = '<span class="badge badge-danger">Erro</span> » ' . $card . ' » <b>Retorno: <span class="text-danger">Formato inválido. Use: numero|mes|ano|cvv</span></b><br>';
                echo json_encode(['status' => 'Reprovada', 'message' => $result]);
            } else {
                $result = '<span class="badge badge-success">Aprovada</span> » ' . $card . ' » <b>Retorno: <span class="text-success">GGs ITAU AUTHORIZED - Configure sua API real aqui</span></b> » <span class="text-primary">GGs Itau ✓</span><br>';
                echo json_encode(['status' => 'Aprovada', 'message' => $result]);
            }
        } elseif ($tool === 'cpfchecker') {
            $cpf = $lista;
            $result = '<span class="badge badge-danger">Reprovada</span> » ' . $cpf . ' » <b>Retorno: <span class="text-danger">API não configurada. Configure sua API real aqui.</span></b><br>';
            echo json_encode(['status' => 'Reprovada', 'message' => $result]);
        } else {
            // Se chegou aqui, a ferramenta não foi encontrada
            echo json_encode(['status' => 'error', 'message' => 'Ferramenta não encontrada: ' . $tool]);
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Erro: ' . $e->getMessage()]);
    }

    exit;
}

// Se não estiver logado, mostrar página de login
if (!isset($_SESSION['logged_in'])) {
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Terminal - SaveFullBlack</title>
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
                            <label for="perm_ggsitau">GGs ITAU</label>
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
            <h2>📋 Usuários Cadastrados</h2>
            <div class="users-list">
                <?php 
                foreach ($users as $username => $data): 
                    $isExpired = false;
                    $expiresText = '';

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
                    }

                    $itemClass = 'user-item';
                    if ($data['type'] === 'temporary') {
                        $itemClass .= $isExpired ? ' expired' : ' temporary';
                    }

                    $toolsList = implode(', ', $data['tools'] ?? $data['checkers'] ?? ['paypal']);
                ?>
                    <div class="<?php echo $itemClass; ?>">
                        <div class="user-info">
                            <strong><?php echo $username; ?></strong>
                            <div class="user-role">
                                <?php 
                                echo $data['role'] === 'admin' ? '⭐ Administrador' : '👤 Usuário';
                                ?>
                            </div>
                            <div class="user-type">
                                <?php 
                                if ($data['type'] === 'permanent') {
                                    echo '♾️ Acesso Permanente';
                                } else {
                                    echo '⏱️ Acesso Temporário (' . $data['hours'] . ' hora(s))';
                                }
                                ?>
                            </div>
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
        $inputLabel = "💳 Cole os cartões abaixo (um por linha)";
        $inputFormat = "Formato: numero|mes|ano|cvv";
        $inputExample = "4532015112830366|12|2027|123\n5425233430109903|01|2028|456\n4716989580001234|03|2029|789";
        $placeholder = "Cole seus cartões aqui no formato:\nnumero|mes|ano|cvv";
        $howToUse = [
            "1. Cole os cartões no formato: <strong>numero|mes|ano|cvv</strong>",
            "2. Um cartão por linha",
            "3. Clique em <strong>Iniciar</strong> para começar a verificação",
            "4. Os resultados aparecerão em tempo real"
        ];
    } else {
        $inputLabel = "🔍 Cole os CPFs abaixo (um por linha)";
        $inputFormat = "Formato: apenas números (11 dígitos)";
        $inputExample = "12345678900\n98765432100\n11122233344\n22233344455";
        $placeholder = "Cole os CPFs aqui (apenas números):\n\n12345678900\n98765432100";
        $howToUse = [
            "1. Cole os CPFs no formato: <strong>apenas números (sem pontos ou traços)</strong>",
            "2. Um CPF por linha (11 dígitos cada)",
            "3. Clique em <strong>Iniciar</strong> para começar a consulta",
            "4. Os resultados aparecerão em tempo real com todas as informações"
        ];
    }

    $timeLeftText = '';
    if ($_SESSION['type'] === 'temporary') {
        $timeLeft = $_SESSION['expires_at'] - time();
        $hoursLeft = floor($timeLeft / 3600);
        $minutesLeft = floor(($timeLeft % 3600) / 60);
        $timeLeftText = "⏱️ Tempo restante: {$hoursLeft}h {$minutesLeft}min";
    }
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $toolName; ?> - SaveFullBlack</title>
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

        .time-left {
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
    </style>
</head>
<body>
    <div class="scanline"></div>
    <div class="container">
        <div class="header">
            <h1><?php echo $toolName; ?></h1>
            <p>Sistema de Verificação</p>
            <div class="user-info">
                Usuário: <?php echo $_SESSION['username']; ?>
                <?php if ($_SESSION['type'] === 'temporary'): ?>
                    <br><span style="color: #ff0;">⏱️ TEMPORÁRIO</span>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($_SESSION['type'] === 'temporary'): ?>
            <div class="time-left" id="timeLeft"><?php echo $timeLeftText; ?></div>
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

        <div class="controls">
            <button class="btn btn-start" onclick="startCheck()">▶ Iniciar</button>
            <button class="btn btn-stop" onclick="stopCheck()">⬛ Parar</button>
            <button class="btn btn-clear" onclick="clearAll()">🗑 Limpar</button>
            <div class="loading" id="loading">⏳ Processando...</div>
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

        <?php if ($_SESSION['type'] === 'temporary'): ?>
        setInterval(function() {
            const expiresAt = <?php echo $_SESSION['expires_at']; ?>;
            const now = Math.floor(Date.now() / 1000);
            const timeLeft = expiresAt - now;

            if (timeLeft <= 0) {
                alert('⏱️ Seu acesso expirou! Você será desconectado.');
                window.location.href = '?logout';
            } else {
                const hoursLeft = Math.floor(timeLeft / 3600);
                const minutesLeft = Math.floor((timeLeft % 3600) / 60);
                document.getElementById('timeLeft').textContent = `⏱️ Tempo restante: ${hoursLeft}h ${minutesLeft}min`;
            }
        }, 60000);
        <?php endif; ?>

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

            items = input.split('\n').filter(line => line.trim());
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
                let url = `?action=check&tool=${toolName}&lista=${encodeURIComponent(item)}`;
                <?php if ($isAmazonChecker): ?>
                if (window.amazonCookies) {
                    url += `&cookie=${encodeURIComponent(window.amazonCookies)}`;
                }
                <?php endif; ?>

                const response = await fetch(url);
                const text = await response.text();

                try {
                    const jsonData = JSON.parse(text);
                    
                    if (jsonData.status === 'error') {
                        if (jsonData.message === 'Seu acesso expirou!' || jsonData.message === 'Você não tem permissão para usar esta ferramenta') {
                            alert(jsonData.message);
                            window.location.href = '?logout';
                            return;
                        }
                        addResult(item, jsonData.message, false);
                    } else if (jsonData.status === 'Aprovada' || jsonData.status === 'success') {
                        addResult(item, jsonData.message || 'Aprovada', true);
                    } else {
                        addResult(item, jsonData.message || 'Reprovada', false);
                    }
                } catch (e) {
                    // Se não for JSON, tratar como HTML/texto
                    if (text.includes('Aprovada') || text.includes('success') || text.includes('✅')) {
                        addResult(item, text, true);
                    } else {
                        addResult(item, text, false);
                    }
                }

            } catch (error) {
                console.error('Error:', error);
                addResult(item, 'Erro: ' + error.message, false);
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

            // Limitar o tamanho da resposta para evitar problemas de exibição
            const responseText = typeof response === 'string' ? response.substring(0, 500) : response;

            resultDiv.innerHTML = `
                <strong>${item}</strong><br>
                <small>${responseText}</small>
            `;

            container.insertBefore(resultDiv, container.firstChild);

            if (isLive) {
                const liveCount = parseInt(document.getElementById('liveCount').textContent);
                document.getElementById('liveCount').textContent = liveCount + 1;
            } else {
                const dieCount = parseInt(document.getElementById('dieCount').textContent);
                document.getElementById('dieCount').textContent = dieCount + 1;
            }
        }
    </script>
</body>
</html>
<?php
exit;
}

// Menu principal de seleção de ferramentas
$availableTools = $_SESSION['tools'];
$timeLeftText = '';
if ($_SESSION['type'] === 'temporary') {
    $timeLeft = $_SESSION['expires_at'] - time();
    $hoursLeft = floor($timeLeft / 3600);
    $minutesLeft = floor(($timeLeft % 3600) / 60);
    $timeLeftText = "⏱️ Tempo restante: {$hoursLeft}h {$minutesLeft}min";
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu Principal - SaveFullBlack</title>
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

        .time-left {
            color: #ff0;
            font-size: 16px;
            margin: 20px 0;
            text-align: center;
            padding: 15px;
            background: rgba(255, 255, 0, 0.1);
            border: 1px solid #ff0;
            border-radius: 5px;
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
    </style>
</head>
<body>
    <div class="scanline"></div>
    <div class="container">
        <div class="header">
            <h1>CYBERSECOFC APIS</h1>
            <p style="color: #0ff; margin-top: 10px;">Selecione a Ferramenta</p>
            <div class="user-info">
                👤 <?php echo $_SESSION['username']; ?>
                <?php if ($_SESSION['role'] === 'admin'): ?>
                    <br><span style="color: #ff0;">⭐ ADMIN</span>
                <?php elseif ($_SESSION['type'] === 'temporary'): ?>
                    <br><span style="color: #ff0;">⏱️ TEMPORÁRIO</span>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($_SESSION['type'] === 'temporary'): ?>
            <div class="time-left" id="timeLeft"><?php echo $timeLeftText; ?></div>
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
        <?php if ($_SESSION['type'] === 'temporary'): ?>
        setInterval(function() {
            const expiresAt = <?php echo $_SESSION['expires_at']; ?>;
            const now = Math.floor(Date.now() / 1000);
            const timeLeft = expiresAt - now;

            if (timeLeft <= 0) {
                alert('⏱️ Seu acesso expirou! Você será desconectado.');
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
