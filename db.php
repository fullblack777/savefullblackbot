<?php
// ============================================
// DATABASE MANAGER - CYBERSEC 4.0
// GERENCIADOR DE DADOS PERSISTENTES
// ============================================

// Diretório de dados (fora do escopo principal para persistência)
define('DB_PATH', __DIR__ . '/data/');

// ============================================
// FUNÇÕES DE INICIALIZAÇÃO
// ============================================

// Criar diretório de dados se não existir
if (!file_exists(DB_PATH)) {
    mkdir(DB_PATH, 0755, true);
    chmod(DB_PATH, 0755);
}

// ============================================
// ARQUIVOS DE DADOS
// ============================================
define('USERS_FILE', DB_PATH . 'users_v4.json');
define('LIVES_FILE', DB_PATH . 'lives_v4.json');
define('GATES_FILE', DB_PATH . 'gates_config.json');
define('SETTINGS_FILE', DB_PATH . 'settings.json');
define('LOGS_FILE', DB_PATH . 'system_logs.json');

// ============================================
// FUNÇÕES DE UTILITÁRIOS
// ============================================

// Carregar dados com verificação de integridade
function loadData($file, $default = []) {
    if (!file_exists($file)) {
        file_put_contents($file, json_encode($default, JSON_PRETTY_PRINT));
        chmod($file, 0600);
        return $default;
    }
    
    $content = file_get_contents($file);
    $data = json_decode($content, true);
    
    // Verificar se o JSON é válido
    if (json_last_error() !== JSON_ERROR_NONE) {
        // Backup do arquivo corrompido
        $backup = $file . '.backup_' . date('Ymd_His');
        copy($file, $backup);
        
        // Restaurar com dados padrão
        file_put_contents($file, json_encode($default, JSON_PRETTY_PRINT));
        return $default;
    }
    
    return $data ?: $default;
}

// Salvar dados com transação segura
function saveData($file, $data) {
    // Criar arquivo temporário
    $temp = $file . '.tmp';
    file_put_contents($temp, json_encode($data, JSON_PRETTY_PRINT));
    
    // Verificar se o arquivo temporário foi criado com sucesso
    if (file_exists($temp)) {
        // Renomear arquivo temporário (operação atômica)
        rename($temp, $file);
        chmod($file, 0600);
        return true;
    }
    
    return false;
}

// ============================================
// FUNÇÕES DE USUÁRIOS
// ============================================

function loadUsers() {
    $default = [
        'save' => [
            'password' => password_hash('black', PASSWORD_DEFAULT),
            'role' => 'admin',
            'type' => 'permanent',
            'credits' => 0,
            'expires_at' => null,
            'created_at' => date('Y-m-d H:i:s'),
            'last_login' => null,
            'total_lives' => 0,
            'total_checks' => 0,
            'last_ip' => null,
            'user_agent' => null
        ]
    ];
    return loadData(USERS_FILE, $default);
}

function saveUsers($users) {
    return saveData(USERS_FILE, $users);
}

function getUser($username) {
    $users = loadUsers();
    return $users[$username] ?? null;
}

function updateUser($username, $data) {
    $users = loadUsers();
    if (!isset($users[$username])) return false;
    
    foreach ($data as $key => $value) {
        $users[$username][$key] = $value;
    }
    
    return saveUsers($users);
}

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
        'last_ip' => null,
        'user_agent' => null,
        'total_lives' => 0,
        'total_checks' => 0
    ];
    
    return saveUsers($users);
}

function deleteUser($username) {
    $users = loadUsers();
    if ($username === 'save') return false; // Não permitir deletar admin principal
    if (!isset($users[$username])) return false;
    
    unset($users[$username]);
    return saveUsers($users);
}

function deductCredits($username, $amount) {
    $users = loadUsers();
    if (!isset($users[$username])) return false;
    if ($users[$username]['type'] !== 'credits') return false;
    
    $users[$username]['credits'] -= $amount;
    if ($users[$username]['credits'] < 0) $users[$username]['credits'] = 0;
    $users[$username]['total_checks'] = ($users[$username]['total_checks'] ?? 0) + 1;
    
    return saveUsers($users) ? $users[$username]['credits'] : false;
}

function updateLastLogin($username, $ip = null, $ua = null) {
    $users = loadUsers();
    if (!isset($users[$username])) return false;
    
    $users[$username]['last_login'] = date('Y-m-d H:i:s');
    $users[$username]['last_ip'] = $ip ?? $_SERVER['REMOTE_ADDR'] ?? null;
    $users[$username]['user_agent'] = $ua ?? $_SERVER['HTTP_USER_AGENT'] ?? null;
    
    return saveUsers($users);
}

// ============================================
// FUNÇÕES DE GATES
// ============================================

function loadGatesConfig() {
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
    
    $default = [];
    foreach ($all_gates as $key => $gate) {
        $default[$key] = true;
    }
    
    return loadData(GATES_FILE, $default);
}

function saveGatesConfig($config) {
    return saveData(GATES_FILE, $config);
}

function isGateActive($gate) {
    $config = loadGatesConfig();
    return isset($config[$gate]) ? $config[$gate] : false;
}

function toggleGate($gate, $status) {
    $config = loadGatesConfig();
    if (!isset($config[$gate])) return false;
    
    $config[$gate] = $status;
    return saveGatesConfig($config);
}

// ============================================
// FUNÇÕES DE LIVES
// ============================================

function loadLives() {
    return loadData(LIVES_FILE, []);
}

function saveLives($lives) {
    return saveData(LIVES_FILE, $lives);
}

function addLive($username, $gate, $card, $bin, $response) {
    $lives = loadLives();
    $lives[] = [
        'username' => $username,
        'gate' => $gate,
        'card' => $card,
        'bin' => $bin,
        'response' => $response,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    // Manter apenas últimas 1000 lives
    if (count($lives) > 1000) {
        $lives = array_slice($lives, -1000);
    }
    
    if (saveLives($lives)) {
        // Atualizar contador do usuário
        $users = loadUsers();
        if (isset($users[$username])) {
            $users[$username]['total_lives'] = ($users[$username]['total_lives'] ?? 0) + 1;
            saveUsers($users);
        }
        return true;
    }
    
    return false;
}

function getUserLives($username, $limit = 100) {
    $lives = loadLives();
    $user_lives = [];
    $count = 0;
    
    foreach ($lives as $live) {
        if ($live['username'] === $username) {
            $user_lives[] = $live;
            $count++;
            if ($count >= $limit) break;
        }
    }
    
    return array_reverse($user_lives);
}

function getRecentLives($limit = 50) {
    $lives = loadLives();
    return array_reverse(array_slice($lives, -$limit));
}

// ============================================
// FUNÇÕES DE CONFIGURAÇÕES
// ============================================

function loadSettings() {
    $default = [
        'site_name' => 'CYBERSEC 4.0',
        'site_url' => 'https://cyebrsecofcapis.up.railway.app',
        'telegram_token' => '8586131107:AAF6fDbrjm7CoVI2g1Zkx2agmXJgmbdnCVQ',
        'telegram_chat' => '-1003581267007',
        'live_cost' => 2.00,
        'die_cost' => 0.05,
        'max_items_per_check' => 200,
        'delay_between_checks' => 4000,
        'maintenance_mode' => false,
        'maintenance_message' => 'Sistema em manutenção. Volte em instantes!',
        'version' => '4.0',
        'last_update' => date('Y-m-d H:i:s')
    ];
    
    return loadData(SETTINGS_FILE, $default);
}

function saveSettings($settings) {
    return saveData(SETTINGS_FILE, $settings);
}

function updateSetting($key, $value) {
    $settings = loadSettings();
    if (!array_key_exists($key, $settings)) return false;
    
    $settings[$key] = $value;
    $settings['last_update'] = date('Y-m-d H:i:s');
    
    return saveSettings($settings);
}

// ============================================
// FUNÇÕES DE LOGS
// ============================================

function addLog($type, $message, $data = []) {
    $logs = loadData(LOGS_FILE, []);
    
    $logs[] = [
        'type' => $type,
        'message' => $message,
        'data' => $data,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
        'user' => $_SESSION['username'] ?? 'system',
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    // Manter apenas últimos 500 logs
    if (count($logs) > 500) {
        $logs = array_slice($logs, -500);
    }
    
    return saveData(LOGS_FILE, $logs);
}

function getLogs($type = null, $limit = 100) {
    $logs = loadData(LOGS_FILE, []);
    $filtered = [];
    
    foreach ($logs as $log) {
        if ($type === null || $log['type'] === $type) {
            $filtered[] = $log;
            if (count($filtered) >= $limit) break;
        }
    }
    
    return array_reverse($filtered);
}

// ============================================
// FUNÇÕES DE ESTATÍSTICAS
// ============================================

function getSystemStats() {
    $users = loadUsers();
    $lives = loadLives();
    $settings = loadSettings();
    
    $stats = [
        'total_users' => count($users),
        'total_admins' => 0,
        'total_credits_users' => 0,
        'total_temporary_users' => 0,
        'total_permanent_users' => 0,
        'total_credits' => 0,
        'total_lives' => count($lives),
        'total_checks' => 0,
        'active_gates' => 0,
        'inactive_gates' => 0,
        'last_24h_lives' => 0,
        'last_24h_users' => 0
    ];
    
    $now = time();
    $one_day_ago = date('Y-m-d H:i:s', $now - 86400);
    
    foreach ($users as $user) {
        // Contar por tipo
        if ($user['role'] === 'admin') $stats['total_admins']++;
        if ($user['type'] === 'credits') {
            $stats['total_credits_users']++;
            $stats['total_credits'] += $user['credits'];
        }
        if ($user['type'] === 'temporary') $stats['total_temporary_users']++;
        if ($user['type'] === 'permanent') $stats['total_permanent_users']++;
        
        // Total de checks
        $stats['total_checks'] += ($user['total_checks'] ?? 0);
        
        // Usuários nas últimas 24h
        if ($user['created_at'] >= $one_day_ago) {
            $stats['last_24h_users']++;
        }
    }
    
    // Lives nas últimas 24h
    foreach ($lives as $live) {
        if ($live['created_at'] >= $one_day_ago) {
            $stats['last_24h_lives']++;
        }
    }
    
    // Gates ativas/inativas
    $gates = loadGatesConfig();
    foreach ($gates as $status) {
        if ($status) {
            $stats['active_gates']++;
        } else {
            $stats['inactive_gates']++;
        }
    }
    
    return $stats;
}

// ============================================
// FUNÇÕES DE BACKUP
// ============================================

function createBackup() {
    $backup_dir = DB_PATH . 'backups/';
    if (!file_exists($backup_dir)) {
        mkdir($backup_dir, 0755, true);
    }
    
    $backup_file = $backup_dir . 'backup_' . date('Y-m-d_His') . '.json';
    
    $data = [
        'users' => loadUsers(),
        'lives' => loadLives(),
        'gates' => loadGatesConfig(),
        'settings' => loadSettings(),
        'logs' => loadData(LOGS_FILE, []),
        'backup_date' => date('Y-m-d H:i:s'),
        'version' => '4.0'
    ];
    
    return saveData($backup_file, $data);
}

function restoreBackup($backup_file) {
    if (!file_exists($backup_file)) return false;
    
    $data = loadData($backup_file, []);
    if (empty($data)) return false;
    
    if (isset($data['users'])) saveUsers($data['users']);
    if (isset($data['lives'])) saveLives($data['lives']);
    if (isset($data['gates'])) saveGatesConfig($data['gates']);
    if (isset($data['settings'])) saveSettings($data['settings']);
    if (isset($data['logs'])) saveData(LOGS_FILE, $data['logs']);
    
    return true;
}

function listBackups() {
    $backup_dir = DB_PATH . 'backups/';
    if (!file_exists($backup_dir)) return [];
    
    $backups = [];
    $files = glob($backup_dir . 'backup_*.json');
    
    foreach ($files as $file) {
        $backups[] = [
            'file' => basename($file),
            'path' => $file,
            'size' => filesize($file),
            'date' => date('Y-m-d H:i:s', filemtime($file))
        ];
    }
    
    return array_reverse($backups);
}

// ============================================
// FUNÇÕES DE MANUTENÇÃO
// ============================================

function cleanOldData($days = 30) {
    $lives = loadLives();
    $cutoff = date('Y-m-d H:i:s', time() - ($days * 86400));
    
    $new_lives = [];
    $removed = 0;
    
    foreach ($lives as $live) {
        if ($live['created_at'] >= $cutoff) {
            $new_lives[] = $live;
        } else {
            $removed++;
        }
    }
    
    if ($removed > 0) {
        saveLives($new_lives);
        addLog('cleanup', "Removidas $removed lives antigas (mais de $days dias)");
    }
    
    return $removed;
}

function optimizeDatabase() {
    $files = [USERS_FILE, LIVES_FILE, GATES_FILE, SETTINGS_FILE, LOGS_FILE];
    $optimized = 0;
    
    foreach ($files as $file) {
        if (file_exists($file)) {
            $data = loadData($file, []);
            if (saveData($file, $data)) {
                $optimized++;
            }
        }
    }
    
    addLog('optimize', "Banco de dados otimizado: $optimized arquivos");
    return $optimized;
}

// ============================================
// INICIALIZAÇÃO AUTOMÁTICA
// ============================================

// Garantir que todos os arquivos existam
loadUsers();
loadGatesConfig();
loadLives();
loadSettings();
loadData(LOGS_FILE, []);

// Criar backup automático semanal (opcional)
if (rand(1, 100) === 1) { // 1% de chance a cada requisição
    $last_backup = DB_PATH . 'last_backup.txt';
    if (file_exists($last_backup)) {
        $last = file_get_contents($last_backup);
        if (time() - intval($last) > 604800) { // 7 dias
            createBackup();
            file_put_contents($last_backup, time());
        }
    } else {
        file_put_contents($last_backup, time());
    }
}

// ============================================
// EXPORTAR FUNÇÕES
// ============================================

return [
    'loadUsers' => 'loadUsers',
    'saveUsers' => 'saveUsers',
    'getUser' => 'getUser',
    'addUser' => 'addUser',
    'updateUser' => 'updateUser',
    'deleteUser' => 'deleteUser',
    'deductCredits' => 'deductCredits',
    'updateLastLogin' => 'updateLastLogin',
    'loadGatesConfig' => 'loadGatesConfig',
    'saveGatesConfig' => 'saveGatesConfig',
    'isGateActive' => 'isGateActive',
    'toggleGate' => 'toggleGate',
    'loadLives' => 'loadLives',
    'saveLives' => 'saveLives',
    'addLive' => 'addLive',
    'getUserLives' => 'getUserLives',
    'getRecentLives' => 'getRecentLives',
    'loadSettings' => 'loadSettings',
    'saveSettings' => 'saveSettings',
    'updateSetting' => 'updateSetting',
    'addLog' => 'addLog',
    'getLogs' => 'getLogs',
    'getSystemStats' => 'getSystemStats',
    'createBackup' => 'createBackup',
    'restoreBackup' => 'restoreBackup',
    'listBackups' => 'listBackups',
    'cleanOldData' => 'cleanOldData',
    'optimizeDatabase' => 'optimizeDatabase'
];

?>
