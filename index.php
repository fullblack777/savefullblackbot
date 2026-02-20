<?php
// ============================================
// CYBERSEC 4.0 - VERSÃO ULTRA PREMIUM
// SISTEMA COMPLETO DE CHECKERS COM LOJA GG
// ============================================

ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);
date_default_timezone_set('America/Sao_Paulo');
session_start();

// ============================================
// ANTI-INSPECTOR - DETECTA DEVTOOLS
// ============================================
if (isset($_GET['devtools_detected']) && $_GET['devtools_detected'] == 1) {
    header('Location: https://www.pornolandia.xxx/video/54944/novinha-gostosa-de-shortinho-curto-rebolando-a-bunda');
    exit;
}

// ============================================
// FUNÇÃO DE DOWNLOAD DE VÍDEO
// ============================================
function downloadVideo($url, $format = 'video') {
    // Criar diretório temporário se não existir
    $temp_dir = __DIR__ . '/temp';
    if (!file_exists($temp_dir)) {
        mkdir($temp_dir, 0755, true);
    }
    
    // Gerar nome de arquivo único
    $filename = $temp_dir . '/' . uniqid() . '.mp4';
    
    // Baixar o vídeo (simplificado - em produção usar yt-dlp ou similar)
    $ch = curl_init($url);
    $fp = fopen($filename, 'wb');
    curl_setopt($ch, CURLOPT_FILE, $fp);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 300);
    curl_exec($ch);
    curl_close($ch);
    fclose($fp);
    
    return $filename;
}

// ============================================
// CONFIGURAÇÕES DO BANCO DE DADOS SQLITE OTIMIZADO
// ============================================
class Database {
    private static $instance = null;
    private $db;
    private $stmtCache = [];
    
    private function __construct() {
        $db_file = __DIR__ . '/data/cybersec.db';
        $db_dir = dirname($db_file);
        
        if (!file_exists($db_dir)) {
            mkdir($db_dir, 0755, true);
        }
        
        // Otimizações SQLite para performance e segurança
        $this->db = new SQLite3($db_file);
        $this->db->busyTimeout(10000);
        $this->db->exec('PRAGMA foreign_keys = ON');
        $this->db->exec('PRAGMA journal_mode = WAL');
        $this->db->exec('PRAGMA synchronous = NORMAL');
        $this->db->exec('PRAGMA cache_size = 10000');
        $this->db->exec('PRAGMA temp_store = MEMORY');
        $this->createTables();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance->db;
    }
    
    // Prepared statement com cache
    public function prepareCached($sql) {
        $hash = md5($sql);
        if (!isset($this->stmtCache[$hash])) {
            $this->stmtCache[$hash] = $this->db->prepare($sql);
        }
        return $this->stmtCache[$hash];
    }
    
    private function createTables() {
        // Tabela de usuários com índices
        $this->db->exec('
            CREATE TABLE IF NOT EXISTS users (
                username TEXT PRIMARY KEY,
                password TEXT NOT NULL,
                role TEXT DEFAULT "user",
                type TEXT DEFAULT "temporary",
                credits REAL DEFAULT 0,
                cyber_money REAL DEFAULT 0,
                expires_at TEXT,
                total_checks INTEGER DEFAULT 0,
                total_lives INTEGER DEFAULT 0,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                last_login TEXT
            )
        ');
        $this->db->exec('CREATE INDEX IF NOT EXISTS idx_users_role ON users(role)');
        $this->db->exec('CREATE INDEX IF NOT EXISTS idx_users_type ON users(type)');
        
        // Tabela de configurações
        $this->db->exec('
            CREATE TABLE IF NOT EXISTS settings (
                key TEXT PRIMARY KEY,
                value TEXT
            )
        ');
        
        // Tabela de status das gates
        $this->db->exec('
            CREATE TABLE IF NOT EXISTS gates_status (
                gate_name TEXT PRIMARY KEY,
                active INTEGER DEFAULT 0,
                updated_at TEXT DEFAULT CURRENT_TIMESTAMP
            )
        ');
        
        // Tabela de lives com índices
        $this->db->exec('
            CREATE TABLE IF NOT EXISTS lives (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username TEXT NOT NULL,
                gate TEXT NOT NULL,
                card TEXT NOT NULL,
                bin TEXT NOT NULL,
                response TEXT,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (username) REFERENCES users(username) ON DELETE CASCADE
            )
        ');
        $this->db->exec('CREATE INDEX IF NOT EXISTS idx_lives_username ON lives(username)');
        $this->db->exec('CREATE INDEX IF NOT EXISTS idx_lives_created ON lives(created_at)');
        
        // Tabela de GGs com índices
        $this->db->exec('
            CREATE TABLE IF NOT EXISTS ggs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                bin TEXT NOT NULL,
                card_number TEXT NOT NULL,
                expiry TEXT NOT NULL,
                cvv TEXT NOT NULL,
                price REAL DEFAULT 3.00,
                sold INTEGER DEFAULT 0,
                sold_to TEXT,
                sold_at TEXT,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP
            )
        ');
        $this->db->exec('CREATE INDEX IF NOT EXISTS idx_ggs_bin ON ggs(bin)');
        $this->db->exec('CREATE INDEX IF NOT EXISTS idx_ggs_sold ON ggs(sold)');
        
        // Tabela de BINs
        $this->db->exec('
            CREATE TABLE IF NOT EXISTS bin_prices (
                bin TEXT PRIMARY KEY,
                price REAL DEFAULT 3.00,
                updated_at TEXT DEFAULT CURRENT_TIMESTAMP
            )
        ');
        
        // Tabela de cartões comprados
        $this->db->exec('
            CREATE TABLE IF NOT EXISTS purchased_cards (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username TEXT NOT NULL,
                bin TEXT NOT NULL,
                card_number TEXT NOT NULL,
                expiry TEXT NOT NULL,
                cvv TEXT NOT NULL,
                price REAL NOT NULL,
                purchased_at TEXT DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (username) REFERENCES users(username) ON DELETE CASCADE
            )
        ');
        $this->db->exec('CREATE INDEX IF NOT EXISTS idx_purchased_username ON purchased_cards(username)');
        
        // Inserir configurações padrão se não existirem
        $default_settings = [
            'telegram_token' => '8586131107:AAF6fDbrjm7CoVI2g1Zkx2agmXJgmbdnCVQ',
            'telegram_chat' => '-1003581267007',
            'site_url' => 'https://' . $_SERVER['HTTP_HOST'],
            'live_cost' => '2.00',
            'die_cost' => '0.05'
        ];
        
        $stmt = $this->db->prepare('INSERT OR IGNORE INTO settings (key, value) VALUES (:key, :value)');
        foreach ($default_settings as $key => $value) {
            $stmt->bindValue(':key', $key, SQLITE3_TEXT);
            $stmt->bindValue(':value', $value, SQLITE3_TEXT);
            $stmt->execute();
            $stmt->reset();
        }
        
        // Criar usuário admin padrão se não existir
        $admin_exists = $this->db->querySingle("SELECT COUNT(*) FROM users WHERE username = 'save'");
        if (!$admin_exists) {
            $stmt = $this->db->prepare('INSERT INTO users (username, password, role, type, credits, cyber_money) VALUES (:username, :password, :role, :type, :credits, :cyber_money)');
            $stmt->bindValue(':username', 'save', SQLITE3_TEXT);
            $stmt->bindValue(':password', password_hash('black', PASSWORD_DEFAULT), SQLITE3_TEXT);
            $stmt->bindValue(':role', 'admin', SQLITE3_TEXT);
            $stmt->bindValue(':type', 'permanent', SQLITE3_TEXT);
            $stmt->bindValue(':credits', 0, SQLITE3_FLOAT);
            $stmt->bindValue(':cyber_money', 0, SQLITE3_FLOAT);
            $stmt->execute();
        }
        
        // Garantir que qualquer usuário com nome 'admin' não tenha privilégios de administrador
        $stmt = $this->db->prepare("UPDATE users SET role = 'user' WHERE username = 'admin' AND role = 'admin'");
        $stmt->execute();
    }
}

// ============================================
// FUNÇÕES DO BANCO DE DADOS OTIMIZADAS
// ============================================
function getDB() {
    return Database::getInstance();
}

// Configurações com cache
function loadSettings() {
    static $settings = null;
    if ($settings === null) {
        $db = getDB();
        $result = $db->query('SELECT key, value FROM settings');
        $settings = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $settings[$row['key']] = $row['value'];
        }
    }
    return $settings;
}

function saveSetting($key, $value) {
    $db = getDB();
    $stmt = $db->prepare('INSERT OR REPLACE INTO settings (key, value) VALUES (:key, :value)');
    $stmt->bindValue(':key', $key, SQLITE3_TEXT);
    $stmt->bindValue(':value', $value, SQLITE3_TEXT);
    return $stmt->execute();
}

// Usuários com prepared statements
function loadUsers() {
    $db = getDB();
    $result = $db->query('SELECT * FROM users ORDER BY created_at DESC');
    $users = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $users[$row['username']] = $row;
    }
    return $users;
}

function getUser($username) {
    $db = getDB();
    $stmt = $db->prepare('SELECT * FROM users WHERE username = :username');
    $stmt->bindValue(':username', $username, SQLITE3_TEXT);
    $result = $stmt->execute();
    return $result->fetchArray(SQLITE3_ASSOC);
}

function addUser($username, $password, $role = 'user', $type = 'temporary', $credits = 0, $cyber_money = 0, $expires_at = null) {
    $db = getDB();
    $stmt = $db->prepare('
        INSERT INTO users (username, password, role, type, credits, cyber_money, expires_at)
        VALUES (:username, :password, :role, :type, :credits, :cyber_money, :expires_at)
    ');
    $stmt->bindValue(':username', $username, SQLITE3_TEXT);
    $stmt->bindValue(':password', password_hash($password, PASSWORD_DEFAULT), SQLITE3_TEXT);
    $stmt->bindValue(':role', $role, SQLITE3_TEXT);
    $stmt->bindValue(':type', $type, SQLITE3_TEXT);
    $stmt->bindValue(':credits', $credits, SQLITE3_FLOAT);
    $stmt->bindValue(':cyber_money', $cyber_money, SQLITE3_FLOAT);
    $stmt->bindValue(':expires_at', $expires_at, SQLITE3_TEXT);
    return $stmt->execute();
}

function updateUser($username, $data) {
    $db = getDB();
    $fields = [];
    $values = [];
    foreach ($data as $key => $value) {
        $fields[] = "$key = :$key";
        $values[":$key"] = $value;
    }
    $values[':username'] = $username;
    
    $sql = 'UPDATE users SET ' . implode(', ', $fields) . ' WHERE username = :username';
    $stmt = $db->prepare($sql);
    foreach ($values as $key => $value) {
        $type = is_float($value) ? SQLITE3_FLOAT : (is_int($value) ? SQLITE3_INTEGER : SQLITE3_TEXT);
        $stmt->bindValue($key, $value, $type);
    }
    return $stmt->execute();
}

function deleteUser($username) {
    $db = getDB();
    $stmt = $db->prepare('DELETE FROM users WHERE username = :username AND username != "admin"');
    $stmt->bindValue(':username', $username, SQLITE3_TEXT);
    return $stmt->execute();
}

function deductCredits($username, $amount) {
    $user = getUser($username);
    if (!$user || $user['credits'] < $amount) {
        return false;
    }
    $new_credits = $user['credits'] - $amount;
    updateUser($username, ['credits' => $new_credits]);
    return $new_credits;
}

function deductCyberMoney($username, $amount) {
    $user = getUser($username);
    if (!$user || $user['cyber_money'] < $amount) {
        return false;
    }
    $new_balance = $user['cyber_money'] - $amount;
    updateUser($username, ['cyber_money' => $new_balance]);
    return $new_balance;
}

function updateLastLogin($username) {
    $db = getDB();
    $stmt = $db->prepare('UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE username = :username');
    $stmt->bindValue(':username', $username, SQLITE3_TEXT);
    return $stmt->execute();
}

// Gates
function loadGatesConfig() {
    $db = getDB();
    $result = $db->query('SELECT gate_name, active FROM gates_status');
    $config = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $config[$row['gate_name']] = (bool)$row['active'];
    }
    return $config;
}

function saveGatesConfig($config) {
    $db = getDB();
    foreach ($config as $gate => $active) {
        $stmt = $db->prepare('INSERT OR REPLACE INTO gates_status (gate_name, active, updated_at) VALUES (:gate, :active, CURRENT_TIMESTAMP)');
        $stmt->bindValue(':gate', $gate, SQLITE3_TEXT);
        $stmt->bindValue(':active', $active ? 1 : 0, SQLITE3_INTEGER);
        $stmt->execute();
    }
    return true;
}

function isGateActive($gate) {
    $db = getDB();
    $stmt = $db->prepare('SELECT active FROM gates_status WHERE gate_name = :gate');
    $stmt->bindValue(':gate', $gate, SQLITE3_TEXT);
    $result = $stmt->execute();
    $row = $result->fetchArray(SQLITE3_ASSOC);
    return $row ? (bool)$row['active'] : false;
}

// Lives
function loadLives() {
    $db = getDB();
    $result = $db->query('SELECT * FROM lives ORDER BY created_at DESC');
    $lives = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $lives[] = $row;
    }
    return $lives;
}

function addLive($username, $gate, $card, $bin, $response) {
    $db = getDB();
    $stmt = $db->prepare('
        INSERT INTO lives (username, gate, card, bin, response)
        VALUES (:username, :gate, :card, :bin, :response)
    ');
    $stmt->bindValue(':username', $username, SQLITE3_TEXT);
    $stmt->bindValue(':gate', $gate, SQLITE3_TEXT);
    $stmt->bindValue(':card', $card, SQLITE3_TEXT);
    $stmt->bindValue(':bin', $bin, SQLITE3_TEXT);
    $stmt->bindValue(':response', $response, SQLITE3_TEXT);
    return $stmt->execute();
}

function getUserLives($username) {
    $db = getDB();
    $stmt = $db->prepare('SELECT * FROM lives WHERE username = :username ORDER BY created_at DESC');
    $stmt->bindValue(':username', $username, SQLITE3_TEXT);
    $result = $stmt->execute();
    $lives = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $lives[] = $row;
    }
    return $lives;
}

// GGs
function addGG($bin, $card_number, $expiry, $cvv, $price = 3.00) {
    $db = getDB();
    
    // Verificar se existe preço personalizado para esta BIN
    $stmt = $db->prepare('SELECT price FROM bin_prices WHERE bin = :bin');
    $stmt->bindValue(':bin', $bin, SQLITE3_TEXT);
    $result = $stmt->execute();
    $bin_price = $result->fetchArray(SQLITE3_ASSOC);
    
    if ($bin_price) {
        $price = $bin_price['price'];
    }
    
    $stmt = $db->prepare('
        INSERT INTO ggs (bin, card_number, expiry, cvv, price)
        VALUES (:bin, :card_number, :expiry, :cvv, :price)
    ');
    $stmt->bindValue(':bin', $bin, SQLITE3_TEXT);
    $stmt->bindValue(':card_number', $card_number, SQLITE3_TEXT);
    $stmt->bindValue(':expiry', $expiry, SQLITE3_TEXT);
    $stmt->bindValue(':cvv', $cvv, SQLITE3_TEXT);
    $stmt->bindValue(':price', $price, SQLITE3_FLOAT);
    return $stmt->execute();
}

function getGGsByBin() {
    $db = getDB();
    $result = $db->query('
        SELECT bin, COUNT(*) as total, price 
        FROM ggs 
        WHERE sold = 0 
        GROUP BY bin 
        ORDER BY bin
    ');
    $ggs = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $ggs[] = $row;
    }
    return $ggs;
}

function getGGsByBinDetailed($bin) {
    $db = getDB();
    $stmt = $db->prepare('
        SELECT * FROM ggs 
        WHERE bin = :bin AND sold = 0 
        ORDER BY created_at
    ');
    $stmt->bindValue(':bin', $bin, SQLITE3_TEXT);
    $result = $stmt->execute();
    $cards = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $cards[] = $row;
    }
    return $cards;
}

function purchaseGG($id, $username) {
    $db = getDB();
    $db->exec('BEGIN TRANSACTION');
    
    try {
        // Verificar se o cartão existe e não foi vendido
        $stmt = $db->prepare('SELECT * FROM ggs WHERE id = :id AND sold = 0');
        $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
        $result = $stmt->execute();
        $gg = $result->fetchArray(SQLITE3_ASSOC);
        
        if (!$gg) {
            throw new Exception('Cartão não disponível');
        }
        
        // Verificar saldo do usuário
        $user = getUser($username);
        if (!$user || $user['cyber_money'] < $gg['price']) {
            throw new Exception('Saldo insuficiente');
        }
        
        // Deduzir saldo
        $new_balance = $user['cyber_money'] - $gg['price'];
        updateUser($username, ['cyber_money' => $new_balance]);
        
        // Marcar como vendido
        $stmt = $db->prepare('
            UPDATE ggs 
            SET sold = 1, sold_to = :username, sold_at = CURRENT_TIMESTAMP 
            WHERE id = :id
        ');
        $stmt->bindValue(':username', $username, SQLITE3_TEXT);
        $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
        $stmt->execute();
        
        // Adicionar aos cartões comprados
        $stmt = $db->prepare('
            INSERT INTO purchased_cards (username, bin, card_number, expiry, cvv, price)
            VALUES (:username, :bin, :card_number, :expiry, :cvv, :price)
        ');
        $stmt->bindValue(':username', $username, SQLITE3_TEXT);
        $stmt->bindValue(':bin', $gg['bin'], SQLITE3_TEXT);
        $stmt->bindValue(':card_number', $gg['card_number'], SQLITE3_TEXT);
        $stmt->bindValue(':expiry', $gg['expiry'], SQLITE3_TEXT);
        $stmt->bindValue(':cvv', $gg['cvv'], SQLITE3_TEXT);
        $stmt->bindValue(':price', $gg['price'], SQLITE3_FLOAT);
        $stmt->execute();
        
        $db->exec('COMMIT');
        
        return [
            'success' => true,
            'card' => $gg,
            'new_balance' => $new_balance
        ];
        
    } catch (Exception $e) {
        $db->exec('ROLLBACK');
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

function getUserPurchasedCards($username) {
    $db = getDB();
    $stmt = $db->prepare('SELECT * FROM purchased_cards WHERE username = :username ORDER BY purchased_at DESC');
    $stmt->bindValue(':username', $username, SQLITE3_TEXT);
    $result = $stmt->execute();
    $cards = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $cards[] = $row;
    }
    return $cards;
}

function updateBinPrice($bin, $price) {
    $db = getDB();
    
    // Atualizar preço na tabela de BINs
    $stmt = $db->prepare('INSERT OR REPLACE INTO bin_prices (bin, price, updated_at) VALUES (:bin, :price, CURRENT_TIMESTAMP)');
    $stmt->bindValue(':bin', $bin, SQLITE3_TEXT);
    $stmt->bindValue(':price', $price, SQLITE3_FLOAT);
    $stmt->execute();
    
    // Atualizar preço de todos os cartões não vendidos desta BIN
    $stmt = $db->prepare('UPDATE ggs SET price = :price WHERE bin = :bin AND sold = 0');
    $stmt->bindValue(':bin', $bin, SQLITE3_TEXT);
    $stmt->bindValue(':price', $price, SQLITE3_FLOAT);
    $stmt->execute();
    
    return true;
}

function getAllBins() {
    $db = getDB();
    $result = $db->query('
        SELECT DISTINCT g.bin, 
               COUNT(*) as total_cards,
               SUM(CASE WHEN sold = 0 THEN 1 ELSE 0 END) as available,
               COALESCE(bp.price, 3.00) as price
        FROM ggs g
        LEFT JOIN bin_prices bp ON g.bin = bp.bin
        GROUP BY g.bin
        ORDER BY g.bin
    ');
    $bins = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $bins[] = $row;
    }
    return $bins;
}

// ============================================
// CONFIGURAÇÕES DO SISTEMA
// ============================================
$settings = loadSettings();

define('TELEGRAM_TOKEN', $settings['telegram_token'] ?? '8586131107:AAF6fDbrjm7CoVI2g1Zkx2agmXJgmbdnCVQ');
define('TELEGRAM_CHAT', $settings['telegram_chat'] ?? '-1003581267007');
define('SITE_URL', $settings['site_url'] ?? 'https://' . $_SERVER['HTTP_HOST']);
define('LIVE_COST', (float)($settings['live_cost'] ?? 2.00));
define('DIE_COST', (float)($settings['die_cost'] ?? 0.05));

define('BASE_PATH', __DIR__);
define('API_PATH', BASE_PATH . '/api/');

if (!file_exists(API_PATH)) mkdir(API_PATH, 0755, true);

// ============================================
// CONFIGURAÇÃO DAS GATES
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
    'ggsgringa' => ['name' => 'GGS GRINGA', 'icon' => '🌎', 'file' => 'ggsgringa.php', 'color' => '#ff9900'],
    'authnet' => ['name' => 'AUTHNET', 'icon' => '🔐', 'file' => 'authnet.php', 'color' => '#ff00aa'],
    '0auth' => ['name' => '0 AUTH', 'icon' => '0️⃣', 'file' => '0auth.php', 'color' => '#aa00ff'],
    'apenascc' => ['name' => 'APENAS CC', 'icon' => '💳', 'file' => 'apenascc.php', 'color' => '#00aa00'],
    'ccn' => ['name' => 'CCN', 'icon' => '🌐', 'file' => 'ccn.php', 'color' => '#ffaa00'],
    'charge' => ['name' => 'CHARGE 0.01', 'icon' => '💰', 'file' => 'charge.php', 'color' => '#00aaff'],
    'paypal' => ['name' => 'PAYPAL', 'icon' => '🅿️', 'file' => 'paypal.php', 'color' => '#003087'],
    'paypalv2' => ['name' => 'PAYPAL V2', 'icon' => '🅿️', 'file' => 'paypalv2.php', 'color' => '#009cde'],
    '40' => ['name' => 'DEBITANDO $40', 'icon' => '💸', 'file' => '40.php', 'color' => '#ff4444'],
    'getnet' => ['name' => 'GETNET', 'icon' => '🏦', 'file' => 'getnet.php', 'color' => '#00cc99'],
    'cvv' => ['name' => 'CVV FAILURE', 'icon' => '❌', 'file' => 'cvv.php', 'color' => '#ff6666'],
    'ggbr' => ['name' => 'GG BR', 'icon' => '🇧🇷', 'file' => 'ggbr.php', 'color' => '#00ff99'],
    'erede' => ['name' => 'E-REDE', 'icon' => '🔴', 'file' => 'erede.php', 'color' => '#ff3333'],
    'braintree1' => ['name' => 'BRAINTREE $0.01', 'icon' => '💳', 'file' => 'braintree1.php', 'color' => '#663399']
];

// Inicializar status das gates (todas desativadas por padrão)
$gates_config = loadGatesConfig();
if (empty($gates_config)) {
    $default_config = [];
    foreach ($all_gates as $key => $gate) {
        $default_config[$key] = false;
    }
    saveGatesConfig($default_config);
    $gates_config = $default_config;
}

// ============================================
// FUNÇÃO DO TELEGRAM MELHORADA
// ============================================
function sendTelegramMessage($message) {
    $token = TELEGRAM_TOKEN;
    $chat_id = TELEGRAM_CHAT;
    
    $url = "https://api.telegram.org/bot{$token}/sendMessage";
    
    $data = [
        'chat_id' => $chat_id,
        'text' => $message,
        'parse_mode' => 'Markdown',
        'disable_web_page_preview' => true
    ];
    
    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }
    
    return false;
}

// ============================================
// VERIFICAR ACESSO
// ============================================
function checkAccess() {
    if (!isset($_SESSION['logged_in']) || !isset($_SESSION['username'])) {
        return false;
    }
    
    $user = getUser($_SESSION['username']);
    if (!$user) {
        session_destroy();
        return false;
    }
    
    if ($user['type'] === 'temporary' && !empty($user['expires_at'])) {
        if (time() > strtotime($user['expires_at'])) {
            session_destroy();
            return false;
        }
    }
    
    return true;
}

// ============================================
// PROCESSAR LOGIN/REGISTRO (COM CYBERCAP DE IMAGEM REAL)
// ============================================
// Lista de imagens reais para o CyberCap
$cybercap_images = [
    ['file' => 'carro.jpg', 'label' => 'Carro', 'correct' => false],
    ['file' => 'casa.jpg', 'label' => 'Casa', 'correct' => true],
    ['file' => 'arvore.jpg', 'label' => 'Árvore', 'correct' => false],
    ['file' => 'cachorro.jpg', 'label' => 'Cachorro', 'correct' => false],
];

// Gerar novo desafio CyberCap se não existir
if (!isset($_SESSION['cybercap_images']) || !isset($_SESSION['cybercap_correct_index'])) {
    // Embaralhar as imagens
    $images = $cybercap_images;
    shuffle($images);
    
    // Encontrar o índice da imagem correta
    $correct_index = 0;
    foreach ($images as $index => $image) {
        if ($image['correct']) {
            $correct_index = $index;
            break;
        }
    }
    
    $_SESSION['cybercap_images'] = $images;
    $_SESSION['cybercap_correct_index'] = $correct_index;
}

if (isset($_POST['login'])) {
    if (!isset($_SESSION['login_attempts'])) $_SESSION['login_attempts'] = 0;
    
    $username = preg_replace('/[^a-zA-Z0-9_]/', '', $_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $cybercap_selected = isset($_POST['cybercap_selected']) ? intval($_POST['cybercap_selected']) : -1;
    
    // Verificar CyberCap
    if ($cybercap_selected === -1) {
        $login_error = 'Please select the correct image';
    } elseif ($cybercap_selected !== $_SESSION['cybercap_correct_index']) {
        $login_error = 'Incorrect image selected';
        // Gerar novo desafio
        $images = $cybercap_images;
        shuffle($images);
        $correct_index = 0;
        foreach ($images as $index => $image) {
            if ($image['correct']) {
                $correct_index = $index;
                break;
            }
        }
        $_SESSION['cybercap_images'] = $images;
        $_SESSION['cybercap_correct_index'] = $correct_index;
    } else {
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
            $login_error = 'Invalid username or password';
            // Gerar novo desafio após falha
            $images = $cybercap_images;
            shuffle($images);
            $correct_index = 0;
            foreach ($images as $index => $image) {
                if ($image['correct']) {
                    $correct_index = $index;
                    break;
                }
            }
            $_SESSION['cybercap_images'] = $images;
            $_SESSION['cybercap_correct_index'] = $correct_index;
        }
    }
}

if (isset($_POST['register'])) {
    $username = preg_replace('/[^a-zA-Z0-9_]/', '', $_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $cybercap_selected = isset($_POST['cybercap_selected']) ? intval($_POST['cybercap_selected']) : -1;
    
    // Verificar CyberCap
    if ($cybercap_selected === -1) {
        $register_error = 'Please select the correct image';
    } elseif ($cybercap_selected !== $_SESSION['cybercap_correct_index']) {
        $register_error = 'Incorrect image selected';
        // Gerar novo desafio
        $images = $cybercap_images;
        shuffle($images);
        $correct_index = 0;
        foreach ($images as $index => $image) {
            if ($image['correct']) {
                $correct_index = $index;
                break;
            }
        }
        $_SESSION['cybercap_images'] = $images;
        $_SESSION['cybercap_correct_index'] = $correct_index;
    }
    // Verificações
    elseif (strlen($username) < 3) {
        $register_error = 'Username must be at least 3 characters';
    } elseif (strlen($password) < 4) {
        $register_error = 'Password must be at least 4 characters';
    } elseif ($password !== $confirm_password) {
        $register_error = 'Passwords do not match';
    } elseif (preg_match('/^[sc]/i', $username)) {
        $register_error = 'Username cannot start with S or C';
    } elseif (strtolower($username) === 'admin') {
        $register_error = 'Username cannot be "admin"';
    } elseif (getUser($username)) {
        $register_error = 'Username already exists';
    } else {
        // Criar usuário com saldo zero
        if (addUser($username, $password, 'user', 'credits', 0, 0)) {
            $success_message = 'Account created successfully! Please login.';
            sendTelegramMessage("👤 *NEW USER REGISTERED*\n\n**User:** `$username`");
        } else {
            $register_error = 'Error creating account';
        }
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
// PROCESSAR ADMIN ACTIONS (COM HORAS)
// ============================================
if (isset($_POST['admin_action']) && isset($_SESSION['logged_in']) && $_SESSION['role'] === 'admin') {
    $action = $_POST['admin_action'];
    
    if ($action === 'add_user') {
        $username = preg_replace('/[^a-zA-Z0-9_]/', '', $_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $type = $_POST['type'] ?? 'temporary';
        $credits = floatval($_POST['credits'] ?? 0);
        $cyber_money = floatval($_POST['cyber_money'] ?? 0);
        $hours = intval($_POST['hours'] ?? 0);
        
        if ($username && $password) {
            if (!getUser($username)) {
                $expires_at = null;
                if ($type === 'temporary' && $hours > 0) {
                    $expires_at = date('Y-m-d H:i:s', time() + ($hours * 3600));
                }
                
                if (addUser($username, $password, 'user', $type, $credits, $cyber_money, $expires_at)) {
                    $success_message = "User created successfully";
                    sendTelegramMessage("👤 *NEW USER*\n\n**User:** `$username`\n**Type:** $type\n**Credits:** $credits\n**Cyber:** $cyber_money\n**Hours:** $hours");
                } else {
                    $error_message = "Error creating user";
                }
            } else {
                $error_message = "User already exists";
            }
        } else {
            $error_message = "Fill all fields";
        }
    }
    
    if ($action === 'recharge_credits') {
        $username = preg_replace('/[^a-zA-Z0-9_]/', '', $_POST['username'] ?? '');
        $credits = floatval($_POST['credits'] ?? 0);
        
        if ($username && $credits > 0) {
            $user = getUser($username);
            if ($user) {
                $new_credits = $user['credits'] + $credits;
                if (updateUser($username, ['credits' => $new_credits])) {
                    $success_message = "Credits added successfully";
                    sendTelegramMessage("💰 *CREDITS ADDED*\n\n**User:** `$username`\n**Amount:** +$credits\n**Total:** $new_credits");
                } else {
                    $error_message = "Error adding credits";
                }
            } else {
                $error_message = "User not found";
            }
        } else {
            $error_message = "Fill all fields";
        }
    }
    
    if ($action === 'recharge_cyber') {
        $username = preg_replace('/[^a-zA-Z0-9_]/', '', $_POST['username'] ?? '');
        $cyber_money = floatval($_POST['cyber_money'] ?? 0);
        
        if ($username && $cyber_money > 0) {
            $user = getUser($username);
            if ($user) {
                $new_balance = $user['cyber_money'] + $cyber_money;
                if (updateUser($username, ['cyber_money' => $new_balance])) {
                    $success_message = "Cyber coins added successfully";
                    sendTelegramMessage("🪙 *CYBER COINS ADDED*\n\n**User:** `$username`\n**Amount:** +$cyber_money\n**Total:** $new_balance");
                } else {
                    $error_message = "Error adding coins";
                }
            } else {
                $error_message = "User not found";
            }
        } else {
            $error_message = "Fill all fields";
        }
    }
    
    if ($action === 'add_user_credits') {
        $username = preg_replace('/[^a-zA-Z0-9_]/', '', $_POST['username'] ?? '');
        $credits = floatval($_POST['credits'] ?? 0);
        
        if ($username && $credits > 0) {
            $user = getUser($username);
            if ($user) {
                $new_credits = $user['credits'] + $credits;
                if (updateUser($username, ['credits' => $new_credits])) {
                    $success_message = "Credits added to user";
                    sendTelegramMessage("💰 *CREDITS ADDED TO USER*\n\n**User:** `$username`\n**Added:** +$credits\n**Total:** $new_credits");
                } else {
                    $error_message = "Error adding credits";
                }
            } else {
                $error_message = "User not found";
            }
        } else {
            $error_message = "Fill all fields";
        }
    }
    
    if ($action === 'add_user_cyber') {
        $username = preg_replace('/[^a-zA-Z0-9_]/', '', $_POST['username'] ?? '');
        $cyber_money = floatval($_POST['cyber_money'] ?? 0);
        
        if ($username && $cyber_money > 0) {
            $user = getUser($username);
            if ($user) {
                $new_balance = $user['cyber_money'] + $cyber_money;
                if (updateUser($username, ['cyber_money' => $new_balance])) {
                    $success_message = "Cyber coins added to user";
                    sendTelegramMessage("🪙 *CYBER COINS ADDED TO USER*\n\n**User:** `$username`\n**Added:** +$cyber_money\n**Total:** $new_balance");
                } else {
                    $error_message = "Error adding coins";
                }
            } else {
                $error_message = "User not found";
            }
        } else {
            $error_message = "Fill all fields";
        }
    }
    
    // NOVA OPÇÃO: Adicionar horas
    if ($action === 'add_user_hours') {
        $username = preg_replace('/[^a-zA-Z0-9_]/', '', $_POST['username'] ?? '');
        $hours = intval($_POST['hours'] ?? 0);
        
        if ($username && $hours > 0) {
            $user = getUser($username);
            if ($user) {
                $current_expires = $user['expires_at'] ? strtotime($user['expires_at']) : time();
                $new_expires = date('Y-m-d H:i:s', $current_expires + ($hours * 3600));
                
                if (updateUser($username, ['expires_at' => $new_expires])) {
                    $success_message = "Hours added to user";
                    sendTelegramMessage("⏱️ *HOURS ADDED TO USER*\n\n**User:** `$username`\n**Added:** +$hours hours\n**New Expiry:** " . date('d/m/Y H:i', strtotime($new_expires)));
                } else {
                    $error_message = "Error adding hours";
                }
            } else {
                $error_message = "User not found";
            }
        } else {
            $error_message = "Fill all fields";
        }
    }
    
    if ($action === 'extend_user_hours') {
        $username = preg_replace('/[^a-zA-Z0-9_]/', '', $_POST['username'] ?? '');
        $hours = intval($_POST['hours'] ?? 0);
        
        if ($username && $hours > 0) {
            $user = getUser($username);
            if ($user && $user['type'] === 'temporary') {
                $current_expires = $user['expires_at'] ? strtotime($user['expires_at']) : time();
                $new_expires = date('Y-m-d H:i:s', $current_expires + ($hours * 3600));
                
                if (updateUser($username, ['expires_at' => $new_expires])) {
                    $success_message = "Hours extended successfully";
                    sendTelegramMessage("⏱️ *HOURS EXTENDED*\n\n**User:** `$username`\n**Added:** +$hours hours\n**New Expiry:** " . date('d/m/Y H:i', strtotime($new_expires)));
                } else {
                    $error_message = "Error extending hours";
                }
            } else {
                $error_message = "User not found or not temporary";
            }
        } else {
            $error_message = "Fill all fields";
        }
    }
    
    if ($action === 'remove') {
        $username = preg_replace('/[^a-zA-Z0-9_]/', '', $_POST['username'] ?? '');
        
        if ($username && $username !== 'admin') {
            if (deleteUser($username)) {
                $success_message = "User removed successfully";
                sendTelegramMessage("🗑️ *USER REMOVED*\n\n**User:** `$username`");
            } else {
                $error_message = "Error removing user";
            }
        } else {
            $error_message = "Cannot remove this user";
        }
    }
    
    if ($action === 'toggle_gate') {
        $gate = $_POST['gate'] ?? '';
        $status = $_POST['status'] === 'true';
        
        $config = loadGatesConfig();
        $config[$gate] = $status;
        saveGatesConfig($config);
        
        $status_text = $status ? 'ACTIVATED' : 'DEACTIVATED';
        sendTelegramMessage("🔧 *GATE $status_text*\n\n**Gate:** " . strtoupper($gate));
        
        echo json_encode(['success' => true]);
        exit;
    }
    
    if ($action === 'add_ggs') {
        $ggs_text = trim($_POST['ggs'] ?? '');
        $lines = explode("\n", $ggs_text);
        $added = 0;
        $errors = 0;
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            
            $parts = explode('|', $line);
            if (count($parts) >= 4) {
                $card = preg_replace('/[^0-9]/', '', $parts[0]);
                $mes = $parts[1];
                $ano = $parts[2];
                $cvv = $parts[3];
                $expiry = $mes . '|' . $ano;
                $bin = substr($card, 0, 6);
                
                if (strlen($card) >= 15 && strlen($card) <= 16 && strlen($cvv) >= 3) {
                    if (addGG($bin, $card, $expiry, $cvv)) {
                        $added++;
                    } else {
                        $errors++;
                    }
                } else {
                    $errors++;
                }
            } else {
                $errors++;
            }
        }
        
        $success_message = "GGs added: $added | Errors: $errors";
        sendTelegramMessage("📥 *GGS ADDED*\n\n**Added:** $added\n**Errors:** $errors");
    }
    
    if ($action === 'update_bin_price') {
        $bin = $_POST['bin'] ?? '';
        $price = floatval($_POST['price'] ?? 0);
        
        if ($bin && $price > 0) {
            if (updateBinPrice($bin, $price)) {
                $success_message = "BIN $bin price updated to R$ $price";
                sendTelegramMessage("💰 *PRICE UPDATED*\n\n**BIN:** `$bin`\n**New Price:** R$ $price");
            } else {
                $error_message = "Error updating price";
            }
        } else {
            $error_message = "Fill all fields";
        }
    }
}

// ============================================
// PROCESSAR COMPRA DE GG
// ============================================
if (isset($_POST['purchase_gg']) && checkAccess()) {
    $id = intval($_POST['gg_id'] ?? 0);
    $username = $_SESSION['username'];
    
    if ($id > 0) {
        $result = purchaseGG($id, $username);
        
        if ($result['success']) {
            $purchase_success = "Card purchased successfully!";
            
            // Mostrar dados completos do cartão
            $card = $result['card'];
            $card_display = "💳 *PURCHASED CARD*\n\n";
            $card_display .= "**Number:** `{$card['card_number']}`\n";
            $card_display .= "**Expiry:** `{$card['expiry']}`\n";
            $card_display .= "**CVV:** `{$card['cvv']}`\n";
            $card_display .= "**Price:** R$ {$card['price']}\n";
            $card_display .= "**Remaining balance:** R$ {$result['new_balance']}";
            
            $_SESSION['purchase_result'] = $card_display;
            
            // Notificar Telegram
            sendTelegramMessage("🛒 *GG PURCHASED*\n\n**User:** `$username`\n**BIN:** `{$card['bin']}`\n**Price:** R$ {$card['price']}");
        } else {
            $purchase_error = $result['error'];
        }
    }
}

// ============================================
// PROCESSAR DOWNLOAD DE VÍDEO
// ============================================
if (isset($_POST['download_video'])) {
    $url = $_POST['video_url'] ?? '';
    $format = $_POST['format'] ?? 'video';
    
    if (filter_var($url, FILTER_VALIDATE_URL)) {
        $file = downloadVideo($url, $format);
        if (file_exists($file)) {
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($file) . '"');
            readfile($file);
            unlink($file); // Remove arquivo temporário
            exit;
        } else {
            $download_error = "Failed to download video";
        }
    } else {
        $download_error = "Invalid URL";
    }
}

// ============================================
// PROCESSAR CHECKER (AJAX) - CORRIGIDO DESCONTO
// ============================================
if (isset($_GET['action']) && $_GET['action'] === 'check' && isset($_GET['lista']) && isset($_GET['tool'])) {
    if (!checkAccess()) {
        die("Access denied");
    }
    
    $tool = $_GET['tool'];
    $lista = $_GET['lista'];
    $username = $_SESSION['username'];
    $user = getUser($username);
    
    if (!isset($all_gates[$tool])) {
        die("Gate not found");
    }
    
    if (!isGateActive($tool)) {
        die("Gate temporarily disabled");
    }
    
    if ($user['type'] === 'credits' && $user['credits'] < 0.05) {
        die("Insufficient credits");
    }
    
    $parts = explode('|', $lista);
    $card = preg_replace('/[^0-9]/', '', $parts[0] ?? '');
    $mes = $parts[1] ?? '';
    $ano = $parts[2] ?? '';
    $cvv = $parts[3] ?? '';
    $bin = substr($card, 0, 6);
    
    if (strlen($card) < 15 || strlen($card) > 16) {
        die("Invalid card");
    }
    
    $user['total_checks'] = ($user['total_checks'] ?? 0) + 1;
    updateUser($username, ['total_checks' => $user['total_checks']]);
    
    $checker_file = API_PATH . $all_gates[$tool]['file'];
    
    // Verificar se o arquivo da API existe
    if (!file_exists($checker_file)) {
        echo "Tool not found";
        exit;
    }
    
    // Incluir o checker real
    ob_start();
    try {
        include $checker_file;
        $response = ob_get_clean();
    } catch (Exception $e) {
        ob_end_clean();
        $response = "Checker error: " . $e->getMessage();
    }
    
    $isLive = false;
    $live_patterns = ['✅', 'aprovada', 'approved', 'success', 'live', 'autorizado', 'authorized', 'valid', 'aprovado', 'apvd', 'ativa', 'active', 'cvv', 'crédito', 'credito', 'saldo'];
    
    $response_lower = strtolower($response);
    foreach ($live_patterns as $pattern) {
        if (strpos($response_lower, strtolower($pattern)) !== false) {
            $isLive = true;
            break;
        }
    }
    
    if (strpos($response, '❌') === 0) {
        $isLive = false;
    }
    
    if ($isLive) {
        addLive($username, $tool, $card, $bin, $response);
        
        $user['total_lives'] = ($user['total_lives'] ?? 0) + 1;
        updateUser($username, ['total_lives' => $user['total_lives']]);
        
        // Notificar live no Telegram com detalhes completos
        $gate_name = $all_gates[$tool]['name'];
        sendTelegramMessage("✅ *LIVE DETECTED*\n\n**User:** `$username`\n**Gate:** $gate_name\n**BIN:** `$bin`\n**Card:** `" . substr($card, 0, 6) . "******" . substr($card, -4) . "`");
    }
    
    if ($user['type'] === 'credits') {
        // CORREÇÃO: Desconta R$2.00 por live e R$0.05 por die
        $cost = $isLive ? LIVE_COST : DIE_COST;
        $remaining = deductCredits($username, $cost);
        if ($remaining !== false) {
            $response .= "\n\n💳 Cost: R$ " . number_format($cost, 2) . " | Remaining: R$ " . number_format($remaining, 2);
        } else {
            $response .= "\n\n❌ Error deducting credits";
        }
    }
    
    echo $response;
    exit;
}

// ============================================
// VERIFICAR ACESSO
// ============================================
if (!checkAccess()) {
    // Garantir que as imagens do CyberCap existem na sessão
    if (!isset($_SESSION['cybercap_images']) || !isset($_SESSION['cybercap_correct_index'])) {
        $images = [
            ['file' => 'carro.jpg', 'label' => 'Carro', 'correct' => false],
            ['file' => 'casa.jpg', 'label' => 'Casa', 'correct' => true],
            ['file' => 'arvore.jpg', 'label' => 'Árvore', 'correct' => false],
            ['file' => 'cachorro.jpg', 'label' => 'Cachorro', 'correct' => false],
        ];
        shuffle($images);
        $correct_index = 0;
        foreach ($images as $index => $image) {
            if ($image['correct']) {
                $correct_index = $index;
                break;
            }
        }
        $_SESSION['cybercap_images'] = $images;
        $_SESSION['cybercap_correct_index'] = $correct_index;
    }
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CYBERSEC OFC - Enterprise Login</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #0a0c0f;
            color: #e4e6eb;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        /* Background elegante */
        .bg-gradient {
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle at 30% 50%, rgba(45, 55, 72, 0.8) 0%, transparent 50%),
                        radial-gradient(circle at 70% 80%, rgba(25, 30, 40, 0.9) 0%, transparent 50%),
                        linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
            animation: rotate 60s linear infinite;
            z-index: -2;
        }

        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .bg-noise {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIzMDAiIGhlaWdodD0iMzAwIj48ZmlsdGVyIGlkPSJmIj48ZmVUdXJidWxlbmNlIHR5cGU9ImZyYWN0YWxOb2lzZSIgYmFzZUZyZXF1ZW5jeT0iLjc0IiBudW1PY3RhdmVzPSIzIiAvPjwvZmlsdGVyPjxyZWN0IHdpZHRoPSIxMDAlIiBoZWlnaHQ9IjEwMCUiIGZpbHRlcj0idXJsKCNmKSIgb3BhY2l0eT0iMC4wNCIgLz48L3N2Zz4=');
            opacity: 0.4;
            z-index: -1;
        }

        .container {
            width: 100%;
            max-width: 500px;
            padding: 20px;
            position: relative;
            z-index: 10;
            animation: fadeInUp 0.8s ease-out;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .brand {
            text-align: center;
            margin-bottom: 40px;
        }

        .brand h1 {
            font-size: 36px;
            font-weight: 700;
            letter-spacing: -0.5px;
            background: linear-gradient(135deg, #ffffff 0%, #94a3b8 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 8px;
        }

        .brand p {
            color: #64748b;
            font-size: 14px;
            font-weight: 500;
            letter-spacing: 0.5px;
        }

        .card {
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 24px;
            padding: 32px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }

        .tabs {
            display: flex;
            gap: 8px;
            margin-bottom: 28px;
            background: rgba(15, 23, 42, 0.5);
            padding: 4px;
            border-radius: 12px;
        }

        .tab {
            flex: 1;
            padding: 12px;
            text-align: center;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            color: #94a3b8;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .tab.active {
            background: #2d3a4f;
            color: #ffffff;
        }

        .form {
            display: none;
        }

        .form.active {
            display: block;
            animation: fadeIn 0.4s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateX(10px); }
            to { opacity: 1; transform: translateX(0); }
        }

        .input-group {
            margin-bottom: 20px;
        }

        .input-group label {
            display: block;
            margin-bottom: 6px;
            font-size: 13px;
            font-weight: 600;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .input-group input {
            width: 100%;
            padding: 14px 16px;
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            color: #ffffff;
            font-size: 15px;
            transition: all 0.2s;
        }

        .input-group input:focus {
            outline: none;
            border-color: #3b82f6;
            background: rgba(15, 23, 42, 0.8);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .input-group input::placeholder {
            color: #475569;
        }

        /* CyberCap com imagens reais */
        .cybercap-container {
            margin: 24px 0;
            background: rgba(15, 23, 42, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 16px;
            padding: 20px;
        }

        .cybercap-title {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 16px;
            color: #94a3b8;
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .cybercap-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            margin-bottom: 12px;
        }

        .cybercap-item {
            background: rgba(15, 23, 42, 0.8);
            border: 2px solid transparent;
            border-radius: 12px;
            padding: 12px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
            position: relative;
        }

        .cybercap-item:hover {
            border-color: #3b82f6;
            transform: translateY(-2px);
        }

        .cybercap-item.selected {
            border-color: #10b981;
            background: rgba(16, 185, 129, 0.1);
        }

        .cybercap-item.selected::after {
            content: '\f00c';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            position: absolute;
            top: 8px;
            right: 8px;
            width: 24px;
            height: 24px;
            background: #10b981;
            color: #ffffff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
        }

        /* Imagens reais */
        .cybercap-image {
            width: 80px;
            height: 80px;
            margin: 0 auto 8px;
            border-radius: 12px;
            overflow: hidden;
            background: #1e293b;
        }

        .cybercap-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .cybercap-label {
            color: #e4e6eb;
            font-size: 14px;
            font-weight: 500;
        }

        .cybercap-instruction {
            text-align: center;
            color: #64748b;
            font-size: 13px;
            margin-top: 12px;
        }

        .cybercap-instruction strong {
            color: #3b82f6;
            font-weight: 600;
        }

        .btn-primary {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            border: none;
            border-radius: 12px;
            color: #ffffff;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            margin: 8px 0;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px -10px #3b82f6;
        }

        .message {
            padding: 14px 18px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 14px;
            font-weight: 500;
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .message.error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid #ef4444;
            color: #fca5a5;
        }

        .message.success {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid #10b981;
            color: #6ee7b7;
        }

        .footer {
            margin-top: 24px;
            text-align: center;
        }

        .footer a {
            color: #64748b;
            text-decoration: none;
            font-size: 13px;
            transition: color 0.2s;
        }

        .footer a:hover {
            color: #3b82f6;
        }

        .telegram-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 16px;
            padding: 8px 20px;
            background: rgba(15, 23, 42, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 40px;
            color: #94a3b8;
            text-decoration: none;
            font-size: 13px;
            transition: all 0.2s;
        }

        .telegram-link:hover {
            border-color: #3b82f6;
            color: #ffffff;
        }

        .version {
            margin-top: 20px;
            color: #334155;
            font-size: 12px;
            text-align: center;
        }

        /* Anti-inspector */
        .anti-inspector {
            display: none;
        }
    </style>
    <!-- Anti-inspector script -->
    <script>
        // Detect DevTools
        (function() {
            const devtools = { open: false };
            const threshold = 160;
            
            const emit = () => {
                if (devtools.open) {
                    window.location.href = '?devtools_detected=1';
                }
            };
            
            setInterval(() => {
                const widthThreshold = window.outerWidth - window.innerWidth > threshold;
                const heightThreshold = window.outerHeight - window.innerHeight > threshold;
                devtools.open = widthThreshold || heightThreshold;
                emit();
            }, 1000);
            
            // Detect console.log
            const originalConsole = console.log;
            console.log = function() {
                window.location.href = '?devtools_detected=1';
                originalConsole.apply(console, arguments);
            };
            
            // Detect keyboard shortcuts
            document.addEventListener('keydown', function(e) {
                if (e.key === 'F12' || 
                    (e.ctrlKey && e.shiftKey && e.key === 'I') ||
                    (e.ctrlKey && e.shiftKey && e.key === 'J') ||
                    (e.ctrlKey && e.key === 'U')) {
                    e.preventDefault();
                    window.location.href = '?devtools_detected=1';
                }
            });
            
            // Detect right click
            document.addEventListener('contextmenu', function(e) {
                e.preventDefault();
                window.location.href = '?devtools_detected=1';
            });
        })();
    </script>
</head>
<body>
    <div class="bg-gradient"></div>
    <div class="bg-noise"></div>

    <div class="container">
        <div class="brand">
            <h1>CYBERSEC OFC</h1>
            <p>ENTERPRISE SECURITY PLATFORM</p>
        </div>

        <div class="card">
            <div class="tabs">
                <div class="tab active" onclick="switchTab('login')">LOGIN</div>
                <div class="tab" onclick="switchTab('register')">REGISTER</div>
            </div>

            <?php if (isset($login_error)): ?>
            <div class="message error"><?php echo $login_error; ?></div>
            <?php endif; ?>
            
            <?php if (isset($register_error)): ?>
            <div class="message error"><?php echo $register_error; ?></div>
            <?php endif; ?>
            
            <?php if (isset($success_message)): ?>
            <div class="message success"><?php echo $success_message; ?></div>
            <?php endif; ?>

            <!-- Login Form -->
            <form class="form active" id="loginForm" method="POST" onsubmit="return validateCyberCap()">
                <div class="input-group">
                    <label>USERNAME</label>
                    <input type="text" name="username" required placeholder="Enter your username">
                </div>
                <div class="input-group">
                    <label>PASSWORD</label>
                    <input type="password" name="password" required placeholder="Enter your password">
                </div>

                <!-- CyberCap Image Challenge com imagens reais -->
                <div class="cybercap-container">
                    <div class="cybercap-title">
                        <i class="fas fa-shield-alt"></i> SELECT THE CORRECT IMAGE
                    </div>
                    <div class="cybercap-grid" id="cybercapGrid">
                        <?php foreach ($_SESSION['cybercap_images'] as $index => $image): ?>
                        <div class="cybercap-item" onclick="selectImage(<?php echo $index; ?>)">
                            <div class="cybercap-image">
                                <!-- Substitua pelo caminho real das suas imagens -->
                                <img src="foto/<?php echo $image['file']; ?>" alt="<?php echo $image['label']; ?>" onerror="this.src='https://via.placeholder.com/80?text=<?php echo $image['label']; ?>'">
                            </div>
                            <div class="cybercap-label"><?php echo $image['label']; ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <input type="hidden" name="cybercap_selected" id="cybercapSelected" value="-1">
                    <div class="cybercap-instruction">
                        Click on the image that shows a <strong>HOUSE</strong>
                    </div>
                </div>

                <button type="submit" name="login" class="btn-primary">ACCESS PLATFORM</button>
            </form>

            <!-- Register Form -->
            <form class="form" id="registerForm" method="POST" onsubmit="return validateCyberCapRegister()">
                <div class="input-group">
                    <label>USERNAME</label>
                    <input type="text" name="username" required placeholder="Minimum 3 characters" pattern="[a-zA-Z0-9_]{3,}">
                </div>
                <div class="input-group">
                    <label>PASSWORD</label>
                    <input type="password" name="password" required placeholder="Minimum 4 characters" minlength="4">
                </div>
                <div class="input-group">
                    <label>CONFIRM PASSWORD</label>
                    <input type="password" name="confirm_password" required placeholder="Confirm your password">
                </div>

                <!-- CyberCap Image Challenge com imagens reais -->
                <div class="cybercap-container">
                    <div class="cybercap-title">
                        <i class="fas fa-shield-alt"></i> SELECT THE CORRECT IMAGE
                    </div>
                    <div class="cybercap-grid" id="cybercapGridRegister">
                        <?php foreach ($_SESSION['cybercap_images'] as $index => $image): ?>
                        <div class="cybercap-item" onclick="selectImageRegister(<?php echo $index; ?>)">
                            <div class="cybercap-image">
                                <img src="foto/<?php echo $image['file']; ?>" alt="<?php echo $image['label']; ?>" onerror="this.src='https://via.placeholder.com/80?text=<?php echo $image['label']; ?>'">
                            </div>
                            <div class="cybercap-label"><?php echo $image['label']; ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <input type="hidden" name="cybercap_selected" id="cybercapSelectedRegister" value="-1">
                    <div class="cybercap-instruction">
                        Click on the image that shows a <strong>HOUSE</strong>
                    </div>
                </div>

                <button type="submit" name="register" class="btn-primary">CREATE ACCOUNT</button>
            </form>

            <div class="footer">
                <a href="https://t.me/centralsavefullblack" target="_blank" class="telegram-link">
                    <i class="fab fa-telegram"></i>
                    @centralsavefullblack
                </a>
            </div>

            <div class="version">v4.0 ULTRA • Enterprise Edition</div>
        </div>
    </div>

    <script>
        let selectedImage = -1;
        let selectedImageRegister = -1;

        function switchTab(tab) {
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.form').forEach(f => f.classList.remove('active'));

            if (tab === 'login') {
                document.querySelectorAll('.tab')[0].classList.add('active');
                document.getElementById('loginForm').classList.add('active');
                resetCyberCap();
            } else if (tab === 'register') {
                document.querySelectorAll('.tab')[1].classList.add('active');
                document.getElementById('registerForm').classList.add('active');
                resetCyberCapRegister();
            }
        }

        function selectImage(index) {
            // Remove selected class from all items
            document.querySelectorAll('#cybercapGrid .cybercap-item').forEach(item => {
                item.classList.remove('selected');
            });
            
            // Add selected class to clicked item
            document.querySelectorAll('#cybercapGrid .cybercap-item')[index].classList.add('selected');
            
            // Set hidden input value
            document.getElementById('cybercapSelected').value = index;
            selectedImage = index;
        }

        function selectImageRegister(index) {
            document.querySelectorAll('#cybercapGridRegister .cybercap-item').forEach(item => {
                item.classList.remove('selected');
            });
            
            document.querySelectorAll('#cybercapGridRegister .cybercap-item')[index].classList.add('selected');
            document.getElementById('cybercapSelectedRegister').value = index;
            selectedImageRegister = index;
        }

        function validateCyberCap() {
            if (selectedImage === -1) {
                alert('Please select the correct image');
                return false;
            }
            return true;
        }

        function validateCyberCapRegister() {
            if (selectedImageRegister === -1) {
                alert('Please select the correct image');
                return false;
            }
            return true;
        }

        function resetCyberCap() {
            selectedImage = -1;
            document.getElementById('cybercapSelected').value = '-1';
            document.querySelectorAll('#cybercapGrid .cybercap-item').forEach(item => {
                item.classList.remove('selected');
            });
        }

        function resetCyberCapRegister() {
            selectedImageRegister = -1;
            document.getElementById('cybercapSelectedRegister').value = '-1';
            document.querySelectorAll('#cybercapGridRegister .cybercap-item').forEach(item => {
                item.classList.remove('selected');
            });
        }

        // Form validation for register
        document.getElementById('registerForm')?.addEventListener('submit', function(e) {
            const username = this.querySelector('input[name="username"]').value;
            const password = this.querySelector('input[name="password"]').value;
            const confirm = this.querySelector('input[name="confirm_password"]').value;

            if (username.toLowerCase().startsWith('s') || username.toLowerCase().startsWith('c')) {
                e.preventDefault();
                alert('Username cannot start with S or C');
                return false;
            }
            
            if (password !== confirm) {
                e.preventDefault();
                alert('Passwords do not match');
                return false;
            }
        });
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
$user_cyber = $current_user['cyber_money'];
$user_role = $current_user['role'];

// ============================================
// PAINEL ADMIN (com opção de horas)
// ============================================
if ($user_role === 'admin' && isset($_GET['admin'])) {
    $users = loadUsers();
    $gates_config = loadGatesConfig();
    $bins = getAllBins();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CYBERSEC - Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #0a0c0f;
            color: #e4e6eb;
            padding: 30px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .header {
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 16px;
            padding: 20px 30px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .header h1 {
            font-size: 24px;
            font-weight: 600;
            color: #ffffff;
        }

        .header-actions {
            display: flex;
            gap: 12px;
        }

        .btn {
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.2s;
            border: 1px solid rgba(255, 255, 255, 0.05);
            background: rgba(15, 23, 42, 0.6);
            color: #94a3b8;
        }

        .btn:hover {
            border-color: #3b82f6;
            color: #ffffff;
        }

        .btn-danger {
            border-color: #ef4444;
            color: #fca5a5;
        }

        .btn-danger:hover {
            background: #ef4444;
            color: #ffffff;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 24px;
            margin-bottom: 30px;
        }

        .card {
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 16px;
            padding: 24px;
        }

        .card-header {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 20px;
            padding-bottom: 16px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .card-header h2 {
            font-size: 18px;
            font-weight: 600;
            color: #94a3b8;
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-size: 13px;
            font-weight: 500;
            color: #64748b;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px 12px;
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 8px;
            color: #e4e6eb;
            font-size: 14px;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #3b82f6;
        }

        .btn-primary {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            border: none;
            border-radius: 8px;
            color: #ffffff;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px -10px #3b82f6;
        }

        .gates-scroll {
            overflow-x: auto;
            white-space: nowrap;
            padding-bottom: 8px;
        }

        .gates-row {
            display: inline-flex;
            gap: 12px;
        }

        .gate-item {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid;
            border-radius: 8px;
            cursor: pointer;
        }

        .gate-status {
            width: 8px;
            height: 8px;
            border-radius: 50%;
        }

        .status-active {
            background: #10b981;
            box-shadow: 0 0 10px #10b981;
        }

        .status-inactive {
            background: #ef4444;
            box-shadow: 0 0 10px #ef4444;
        }

        .users-table {
            width: 100%;
            border-collapse: collapse;
        }

        .users-table th {
            text-align: left;
            padding: 12px;
            background: rgba(15, 23, 42, 0.6);
            color: #94a3b8;
            font-weight: 600;
            font-size: 13px;
        }

        .users-table td {
            padding: 12px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }

        .badge-admin {
            background: #ef4444;
            color: #ffffff;
        }

        .badge-permanent {
            background: #10b981;
            color: #000000;
        }

        .badge-temporary {
            background: #f59e0b;
            color: #000000;
        }

        .badge-credits {
            background: #3b82f6;
            color: #ffffff;
        }

        .action-btn {
            padding: 4px 8px;
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 6px;
            color: #94a3b8;
            cursor: pointer;
            margin: 0 2px;
        }

        .action-btn:hover {
            border-color: #3b82f6;
            color: #ffffff;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>⚙️ Admin Dashboard</h1>
            <div class="header-actions">
                <span style="color: #94a3b8;">👑 <?php echo $_SESSION['username']; ?></span>
                <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="btn">Main</a>
                <a href="?logout" class="btn btn-danger">Logout</a>
            </div>
        </div>

        <?php if (isset($success_message)): ?>
        <div style="background: rgba(16, 185, 129, 0.1); border: 1px solid #10b981; border-radius: 8px; padding: 12px 20px; margin-bottom: 20px; color: #6ee7b7;"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
        <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid #ef4444; border-radius: 8px; padding: 12px 20px; margin-bottom: 20px; color: #fca5a5;"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <div class="grid">
            <!-- Create User -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-user-plus" style="color: #94a3b8;"></i>
                    <h2>Create User</h2>
                </div>
                <form method="POST">
                    <input type="hidden" name="admin_action" value="add_user">
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" name="username" required>
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="password" required>
                    </div>
                    <div class="form-group">
                        <label>Type</label>
                        <select name="type" id="userType" onchange="toggleUserFields()">
                            <option value="permanent">Permanent</option>
                            <option value="temporary">Temporary</option>
                            <option value="credits">Credits</option>
                        </select>
                    </div>
                    <div class="form-group" id="creditsField">
                        <label>Credits</label>
                        <input type="number" name="credits" step="0.01" value="10">
                    </div>
                    <div class="form-group" id="cyberField">
                        <label>Cyber Coins</label>
                        <input type="number" name="cyber_money" step="0.01" value="10">
                    </div>
                    <div class="form-group" id="hoursField" style="display: none;">
                        <label>Hours</label>
                        <input type="number" name="hours" value="24">
                    </div>
                    <button type="submit" class="btn-primary">Create User</button>
                </form>
            </div>

            <!-- Gate Management -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-cogs" style="color: #94a3b8;"></i>
                    <h2>Gate Management</h2>
                </div>
                <div class="gates-scroll">
                    <div class="gates-row">
                        <?php foreach ($all_gates as $key => $gate): ?>
                        <div class="gate-item" style="border-color: <?php echo $gate['color']; ?>40" onclick="toggleGate('<?php echo $key; ?>')">
                            <span><?php echo $gate['icon']; ?> <?php echo $gate['name']; ?></span>
                            <div class="gate-status <?php echo ($gates_config[$key] ?? false) ? 'status-active' : 'status-inactive'; ?>" id="status-<?php echo $key; ?>"></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Add GGs -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-credit-card" style="color: #94a3b8;"></i>
                    <h2>Add GGs</h2>
                </div>
                <form method="POST">
                    <input type="hidden" name="admin_action" value="add_ggs">
                    <div class="form-group">
                        <label>Cards (number|month|year|cvv)</label>
                        <textarea name="ggs" required rows="5" placeholder="4532015112830366|12|2027|123&#10;5425233430109903|01|2028|456"></textarea>
                    </div>
                    <button type="submit" class="btn-primary">Add Cards</button>
                </form>
            </div>

            <!-- BIN Prices -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-tags" style="color: #94a3b8;"></i>
                    <h2>BIN Prices</h2>
                </div>
                <div style="max-height: 300px; overflow-y: auto;">
                    <table style="width: 100%;">
                        <thead>
                            <tr>
                                <th>BIN</th>
                                <th>Available</th>
                                <th>Price</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bins as $bin): ?>
                            <tr>
                                <td><?php echo $bin['bin']; ?></td>
                                <td><?php echo $bin['available']; ?>/<?php echo $bin['total_cards']; ?></td>
                                <td>R$ <?php echo number_format($bin['price'], 2); ?></td>
                                <td>
                                    <form method="POST" style="display: flex; gap: 5px;">
                                        <input type="hidden" name="admin_action" value="update_bin_price">
                                        <input type="hidden" name="bin" value="<?php echo $bin['bin']; ?>">
                                        <input type="number" name="price" step="0.01" value="<?php echo $bin['price']; ?>" min="0.01" style="width: 80px; background: #0f172a; border: 1px solid rgba(255,255,255,0.05); color: #e4e6eb; padding: 4px; border-radius: 4px;">
                                        <button type="submit" style="background: #3b82f6; border: none; color: #fff; padding: 4px 8px; border-radius: 4px; cursor: pointer;">OK</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Users Table -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-users" style="color: #94a3b8;"></i>
                <h2>Users</h2>
            </div>
            <div style="overflow-x: auto;">
                <table class="users-table">
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Type</th>
                            <th>Credits</th>
                            <th>Cyber</th>
                            <th>Expires</th>
                            <th>Lives</th>
                            <th>Checks</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $username => $data): ?>
                        <tr>
                            <td>
                                <?php echo $username; ?>
                                <?php if ($data['role'] === 'admin'): ?>
                                <span class="badge badge-admin">ADMIN</span>
                                <?php endif; ?>
                            </td>
                            <td><span class="badge badge-<?php echo $data['type']; ?>"><?php echo strtoupper($data['type']); ?></span></td>
                            <td><?php echo number_format($data['credits'], 2); ?></td>
                            <td><?php echo number_format($data['cyber_money'], 2); ?></td>
                            <td>
                                <?php if ($data['type'] === 'temporary' && $data['expires_at']): ?>
                                    <?php echo date('d/m H:i', strtotime($data['expires_at'])); ?>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td><?php echo $data['total_lives'] ?? 0; ?></td>
                            <td><?php echo $data['total_checks'] ?? 0; ?></td>
                            <td>
                                <?php if ($username !== 'admin'): ?>
                                <button class="action-btn" onclick="editUserCredits('<?php echo $username; ?>', <?php echo $data['credits']; ?>)"><i class="fas fa-coins"></i></button>
                                <button class="action-btn" onclick="editUserCyber('<?php echo $username; ?>', <?php echo $data['cyber_money']; ?>)"><i class="fas fa-crown"></i></button>
                                <button class="action-btn" onclick="editUserHours('<?php echo $username; ?>')"><i class="fas fa-clock"></i></button>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="admin_action" value="remove">
                                    <input type="hidden" name="username" value="<?php echo $username; ?>">
                                    <button class="action-btn" onclick="return confirm('Remove user?')"><i class="fas fa-trash"></i></button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        function toggleUserFields() {
            const type = document.getElementById('userType').value;
            document.getElementById('hoursField').style.display = type === 'temporary' ? 'block' : 'none';
        }

        function toggleGate(gate) {
            const statusEl = document.getElementById('status-' + gate);
            const newStatus = !statusEl.classList.contains('status-active');
            
            fetch('<?php echo $_SERVER['PHP_SELF']; ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
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

        function editUserCredits(username, current) {
            const amount = prompt(`Add credits to ${username} (current: ${current}):`);
            if (amount && !isNaN(amount) && amount > 0) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="admin_action" value="add_user_credits">
                    <input type="hidden" name="username" value="${username}">
                    <input type="hidden" name="credits" value="${amount}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function editUserCyber(username, current) {
            const amount = prompt(`Add cyber coins to ${username} (current: ${current}):`);
            if (amount && !isNaN(amount) && amount > 0) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="admin_action" value="add_user_cyber">
                    <input type="hidden" name="username" value="${username}">
                    <input type="hidden" name="cyber_money" value="${amount}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function editUserHours(username) {
            const hours = prompt(`Add hours to ${username}:`);
            if (hours && !isNaN(hours) && hours > 0) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="admin_action" value="add_user_hours">
                    <input type="hidden" name="username" value="${username}">
                    <input type="hidden" name="hours" value="${hours}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>
<?php
exit;
}

// ============================================
// LOJA DE GGS (com rolagem horizontal)
// ============================================
if (isset($_GET['ggs'])) {
    $ggs_by_bin = getGGsByBin();
    $purchased_cards = isset($_GET['mycards']) ? getUserPurchasedCards($_SESSION['username']) : [];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CYBERSEC - GG Shop</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #0a0c0f;
            color: #e4e6eb;
            padding: 30px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 16px;
            padding: 20px 30px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .header h1 {
            font-size: 24px;
            font-weight: 600;
            color: #ffffff;
        }

        .balance {
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 30px;
            padding: 8px 20px;
            color: #10b981;
            font-weight: 500;
        }

        .nav {
            display: flex;
            gap: 12px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }

        .nav-btn {
            padding: 8px 20px;
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 30px;
            color: #94a3b8;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .nav-btn:hover,
        .nav-btn.active {
            border-color: #3b82f6;
            color: #ffffff;
        }

        .nav-btn.youtube {
            border-color: #ff0000;
            color: #ff8888;
        }

        .nav-btn.youtube:hover {
            background: #ff0000;
            color: #ffffff;
        }

        .nav-btn.refs {
            border-color: #10b981;
            color: #6ee7b7;
        }

        .nav-btn.refs:hover {
            background: #10b981;
            color: #000000;
        }

        .nav-btn.baixar {
            border-color: #8b5cf6;
            color: #c4b5fd;
        }

        .nav-btn.baixar:hover {
            background: #8b5cf6;
            color: #ffffff;
        }

        .nav-btn.gerador {
            border-color: #f59e0b;
            color: #fcd34d;
        }

        .nav-btn.gerador:hover {
            background: #f59e0b;
            color: #000000;
        }

        .content-section {
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 16px;
            padding: 24px;
            min-height: 500px;
        }

        .bins-scroll {
            overflow-x: auto;
            white-space: nowrap;
            padding-bottom: 8px;
            margin-top: 20px;
        }

        .bins-row {
            display: inline-flex;
            gap: 16px;
        }

        .bin-card {
            display: inline-block;
            width: 280px;
            background: rgba(15, 23, 42, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 20px;
            cursor: pointer;
            transition: all 0.2s;
            white-space: normal;
        }

        .bin-card:hover {
            border-color: #3b82f6;
            transform: translateY(-4px);
        }

        .bin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }

        .bin-number {
            font-size: 18px;
            font-weight: 600;
            color: #94a3b8;
        }

        .bin-price {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: #ffffff;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
        }

        .bin-info {
            color: #64748b;
            font-size: 14px;
        }

        .cards-table {
            width: 100%;
            border-collapse: collapse;
        }

        .cards-table th {
            text-align: left;
            padding: 12px;
            background: rgba(15, 23, 42, 0.6);
            color: #94a3b8;
            font-weight: 600;
            font-size: 13px;
        }

        .cards-table td {
            padding: 12px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(10px);
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: rgba(30, 41, 59, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 24px;
            padding: 30px;
            max-width: 500px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 16px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .modal-header h2 {
            font-size: 20px;
            font-weight: 600;
            color: #94a3b8;
        }

        .modal-close {
            font-size: 24px;
            cursor: pointer;
            color: #64748b;
        }

        .modal-close:hover {
            color: #ef4444;
        }

        .card-item {
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 16px;
        }

        .btn-buy {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #10b981, #059669);
            border: none;
            border-radius: 30px;
            color: #ffffff;
            font-weight: 600;
            cursor: pointer;
            margin-top: 12px;
            transition: all 0.2s;
        }

        .btn-buy:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px -10px #10b981;
        }

        .btn-buy:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-top: 20px;
        }

        .page-btn {
            padding: 4px 10px;
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 6px;
            color: #64748b;
            cursor: pointer;
        }

        .page-btn.active {
            border-color: #3b82f6;
            color: #ffffff;
        }

        .download-form {
            max-width: 500px;
            margin: 0 auto;
        }

        .radio-group {
            display: flex;
            gap: 20px;
            margin: 20px 0;
            justify-content: center;
        }

        .radio-group label {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #94a3b8;
            cursor: pointer;
        }

        .image-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 16px;
            margin-top: 20px;
        }

        .image-item {
            background: rgba(15, 23, 42, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 8px;
            padding: 8px;
            text-align: center;
        }

        .image-item img {
            width: 100%;
            height: 120px;
            object-fit: cover;
            border-radius: 4px;
        }

        .image-item span {
            display: block;
            margin-top: 8px;
            font-size: 12px;
            color: #94a3b8;
        }

        .youtube-iframe {
            width: 100%;
            height: 600px;
            border: none;
            border-radius: 12px;
        }

        .iframe-container {
            width: 100%;
            height: 600px;
            border-radius: 12px;
            overflow: hidden;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🛒 GG Shop</h1>
            <div class="balance">🪙 <?php echo number_format($user_cyber, 2); ?></div>
        </div>

        <div class="nav">
            <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="nav-btn"><i class="fas fa-home"></i> Main</a>
            <a href="?ggs" class="nav-btn <?php echo (!isset($_GET['mycards']) && !isset($_GET['tab'])) ? 'active' : ''; ?>"><i class="fas fa-store"></i> Buy</a>
            <a href="?ggs&mycards=1" class="nav-btn <?php echo isset($_GET['mycards']) ? 'active' : ''; ?>"><i class="fas fa-credit-card"></i> My Cards</a>
            <a href="?ggs&tab=youtube" class="nav-btn youtube <?php echo (isset($_GET['tab']) && $_GET['tab'] == 'youtube') ? 'active' : ''; ?>"><i class="fab fa-youtube"></i> YouTube</a>
            <a href="?ggs&tab=refs" class="nav-btn refs <?php echo (isset($_GET['tab']) && $_GET['tab'] == 'refs') ? 'active' : ''; ?>"><i class="fas fa-images"></i> Refs</a>
            <a href="?ggs&tab=baixar" class="nav-btn baixar <?php echo (isset($_GET['tab']) && $_GET['tab'] == 'baixar') ? 'active' : ''; ?>"><i class="fas fa-download"></i> Baixar</a>
            <a href="?ggs&tab=gerador" class="nav-btn gerador <?php echo (isset($_GET['tab']) && $_GET['tab'] == 'gerador') ? 'active' : ''; ?>"><i class="fas fa-dice"></i> Gerador</a>
            <a href="?lives" class="nav-btn lives"><i class="fas fa-history"></i> Lives</a>
            <a href="?logout" class="nav-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>

        <?php if (isset($_SESSION['purchase_result'])): ?>
        <div style="background: rgba(16, 185, 129, 0.1); border: 1px solid #10b981; border-radius: 12px; padding: 16px; margin-bottom: 20px; white-space: pre-wrap; font-family: monospace;">
            <?php echo nl2br($_SESSION['purchase_result']); ?>
        </div>
        <?php unset($_SESSION['purchase_result']); ?>
        <?php endif; ?>

        <?php if (isset($download_error)): ?>
        <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid #ef4444; border-radius: 12px; padding: 16px; margin-bottom: 20px;"><?php echo $download_error; ?></div>
        <?php endif; ?>

        <div class="content-section">
            <?php if (isset($_GET['tab']) && $_GET['tab'] == 'youtube'): ?>
                <!-- YouTube -->
                <h2 style="font-size: 20px; font-weight: 600; color: #94a3b8; margin-bottom: 20px;"><i class="fab fa-youtube" style="color: #ff0000;"></i> YouTube Channel</h2>
                <div class="iframe-container">
                    <iframe class="youtube-iframe" src="https://www.youtube.com/embed?listType=user&list=cybersecofc" allowfullscreen></iframe>
                </div>

            <?php elseif (isset($_GET['tab']) && $_GET['tab'] == 'refs'): ?>
                <!-- Refs - Fotos da pasta /foto -->
                <h2 style="font-size: 20px; font-weight: 600; color: #94a3b8; margin-bottom: 20px;"><i class="fas fa-images"></i> Reference Images</h2>
                <div class="image-grid">
                    <?php
                    $foto_dir = __DIR__ . '/foto';
                    if (file_exists($foto_dir)) {
                        $images = glob($foto_dir . '/*.{jpg,jpeg,png,gif}', GLOB_BRACE);
                        foreach ($images as $image) {
                            $filename = basename($image);
                            echo '<div class="image-item">';
                            echo '<img src="foto/' . $filename . '" alt="' . $filename . '">';
                            echo '<span>' . $filename . '</span>';
                            echo '</div>';
                        }
                    } else {
                        echo '<p style="color: #64748b; text-align: center; padding: 40px;">No images found in /foto directory</p>';
                    }
                    ?>
                </div>

            <?php elseif (isset($_GET['tab']) && $_GET['tab'] == 'baixar'): ?>
                <!-- Baixar Vídeo -->
                <h2 style="font-size: 20px; font-weight: 600; color: #94a3b8; margin-bottom: 20px;"><i class="fas fa-download"></i> Video Downloader</h2>
                <form method="POST" class="download-form">
                    <div class="input-group">
                        <label>VIDEO URL</label>
                        <input type="url" name="video_url" required placeholder="https://youtube.com/watch?v=...">
                    </div>
                    
                    <div class="radio-group">
                        <label>
                            <input type="radio" name="format" value="video" checked> 🎬 Video (MP4)
                        </label>
                        <label>
                            <input type="radio" name="format" value="audio"> 🎵 Audio Only (MP3)
                        </label>
                    </div>

                    <button type="submit" name="download_video" class="btn-primary">Download</button>
                </form>

                <div style="margin-top: 30px; padding: 20px; background: rgba(15, 23, 42, 0.6); border-radius: 12px;">
                    <h3 style="color: #94a3b8; margin-bottom: 10px;">Supported Sites:</h3>
                    <p style="color: #64748b; font-size: 14px;">YouTube, Vimeo, Dailymotion, Facebook, Twitter, Instagram, TikTok, and many more...</p>
                </div>

            <?php elseif (isset($_GET['tab']) && $_GET['tab'] == 'gerador'): ?>
                <!-- Gerador de Cartões -->
                <h2 style="font-size: 20px; font-weight: 600; color: #94a3b8; margin-bottom: 20px;"><i class="fas fa-dice"></i> Card Generator</h2>
                <div class="iframe-container">
                    <iframe src="https://peruyashgen.netlify.app" style="width: 100%; height: 600px; border: none; border-radius: 12px;"></iframe>
                </div>

            <?php elseif (isset($_GET['mycards'])): ?>
                <!-- Meus Cartões Comprados -->
                <h2 style="font-size: 20px; font-weight: 600; color: #94a3b8; margin-bottom: 20px;">📋 My Purchased Cards</h2>
                <?php if (empty($purchased_cards)): ?>
                <div style="text-align: center; padding: 40px; color: #64748b;">
                    <div style="font-size: 48px; margin-bottom: 16px;">📭</div>
                    <h3>No cards purchased yet</h3>
                </div>
                <?php else: ?>
                <div style="overflow-x: auto;">
                    <table class="cards-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>BIN</th>
                                <th>Card</th>
                                <th>Expiry</th>
                                <th>CVV</th>
                                <th>Price</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($purchased_cards as $card): ?>
                            <tr>
                                <td><?php echo date('d/m/Y H:i', strtotime($card['purchased_at'])); ?></td>
                                <td><?php echo $card['bin']; ?></td>
                                <td><?php echo $card['card_number']; ?></td>
                                <td><?php echo $card['expiry']; ?></td>
                                <td><?php echo $card['cvv']; ?></td>
                                <td>R$ <?php echo number_format($card['price'], 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>

            <?php else: ?>
                <!-- Loja de GGs -->
                <h2 style="font-size: 20px; font-weight: 600; color: #94a3b8; margin-bottom: 16px;">🔍 Available Cards</h2>
                <?php if (empty($ggs_by_bin)): ?>
                <div style="text-align: center; padding: 60px;">
                    <div style="font-size: 48px; margin-bottom: 16px;">📭</div>
                    <h3 style="color: #64748b;">No cards available</h3>
                </div>
                <?php else: ?>
                <div class="bins-scroll">
                    <div class="bins-row">
                        <?php foreach ($ggs_by_bin as $bin): ?>
                        <div class="bin-card" onclick="showCards('<?php echo $bin['bin']; ?>')">
                            <div class="bin-header">
                                <span class="bin-number">BIN <?php echo $bin['bin']; ?></span>
                                <span class="bin-price">R$ <?php echo number_format($bin['price'], 2); ?></span>
                            </div>
                            <div class="bin-info">
                                <span>📦 Available: <?php echo $bin['total']; ?></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Cards Modal -->
    <div class="modal" id="cardsModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalBinTitle">Cards</h2>
                <span class="modal-close" onclick="closeModal()">✕</span>
            </div>
            <div id="cardsList"></div>
            <div class="pagination" id="pagination"></div>
        </div>
    </div>

    <script>
        let currentBin = '';
        let currentPage = 0;
        let allCards = [];

        function showCards(bin) {
            currentBin = bin;
            currentPage = 0;
            
            fetch(`?action=get_ggs&bin=${bin}`)
                .then(response => response.json())
                .then(cards => {
                    allCards = cards;
                    document.getElementById('modalBinTitle').textContent = `BIN ${bin}`;
                    renderCards();
                    document.getElementById('cardsModal').classList.add('active');
                });
        }

        function renderCards() {
            const start = currentPage * 10;
            const end = start + 10;
            const pageCards = allCards.slice(start, end);
            
            let html = '';
            pageCards.forEach(card => {
                html += `
                    <div class="card-item">
                        <div style="margin-bottom: 8px;"><strong>BIN:</strong> ${card.bin}</div>
                        <div style="margin-bottom: 8px;"><strong>Expiry:</strong> ${card.expiry.replace('|', '/')}</div>
                        <div style="margin-bottom: 12px;"><strong>Price:</strong> R$ ${card.price.toFixed(2)}</div>
                        <form method="POST">
                            <input type="hidden" name="purchase_gg" value="1">
                            <input type="hidden" name="gg_id" value="${card.id}">
                            <button type="submit" class="btn-buy" ${card.price > <?php echo $user_cyber; ?> ? 'disabled' : ''}>
                                Purchase
                            </button>
                        </form>
                    </div>
                `;
            });
            
            document.getElementById('cardsList').innerHTML = html;
            
            const totalPages = Math.ceil(allCards.length / 10);
            let paginationHtml = '';
            for (let i = 0; i < totalPages; i++) {
                paginationHtml += `<button class="page-btn ${i === currentPage ? 'active' : ''}" onclick="goToPage(${i})">${i + 1}</button>`;
            }
            document.getElementById('pagination').innerHTML = paginationHtml;
        }

        function goToPage(page) {
            currentPage = page;
            renderCards();
        }

        function closeModal() {
            document.getElementById('cardsModal').classList.remove('active');
        }
    </script>
</body>
</html>
<?php
exit;
}

// ============================================
// API PARA BUSCAR GGS
// ============================================
if (isset($_GET['action']) && $_GET['action'] === 'get_ggs' && isset($_GET['bin'])) {
    $bin = $_GET['bin'];
    $cards = getGGsByBinDetailed($bin);
    
    $result = [];
    foreach ($cards as $card) {
        $result[] = [
            'id' => $card['id'],
            'bin' => $card['bin'],
            'card_number' => $card['card_number'],
            'expiry' => $card['expiry'],
            'cvv' => $card['cvv'],
            'price' => (float)$card['price']
        ];
    }
    
    header('Content-Type: application/json');
    echo json_encode($result);
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
            echo "DATE: " . $live['created_at'] . "\n";
            echo "GATE: " . strtoupper($live['gate']) . "\n";
            echo "BIN: " . $live['bin'] . "\n";
            echo "CARD: " . $live['card'] . "\n";
            echo "RESPONSE:\n" . $live['response'] . "\n\n";
        }
        exit;
    }
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CYBERSEC - Live History</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #0a0c0f;
            color: #e4e6eb;
            padding: 30px;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
        }

        .header {
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 16px;
            padding: 20px 30px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .header h1 {
            font-size: 24px;
            font-weight: 600;
            color: #ffffff;
        }

        .nav {
            display: flex;
            gap: 12px;
        }

        .btn {
            padding: 6px 14px;
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 30px;
            color: #94a3b8;
            text-decoration: none;
            font-size: 13px;
            transition: all 0.2s;
        }

        .btn:hover {
            border-color: #3b82f6;
            color: #ffffff;
        }

        .btn-export {
            border-color: #10b981;
            color: #6ee7b7;
        }

        .btn-export:hover {
            background: #10b981;
            color: #ffffff;
        }

        .lives-container {
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 16px;
            padding: 24px;
        }

        .live-card {
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 16px;
        }

        .live-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .live-gate {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: #ffffff;
            padding: 2px 10px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 600;
        }

        .live-date {
            color: #64748b;
            font-size: 12px;
        }

        .live-detail {
            margin-bottom: 6px;
            font-size: 13px;
        }

        .live-detail strong {
            color: #94a3b8;
        }

        .live-response {
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 8px;
            padding: 12px;
            font-family: 'Monaco', 'Menlo', monospace;
            font-size: 12px;
            white-space: pre-wrap;
            margin-top: 12px;
        }

        .empty {
            text-align: center;
            padding: 40px;
            color: #64748b;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📋 Live History</h1>
            <div class="nav">
                <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="btn">Main</a>
                <a href="?ggs" class="btn">Shop</a>
                <a href="?lives&export=1" class="btn btn-export">Export</a>
                <a href="?logout" class="btn">Logout</a>
            </div>
        </div>

        <?php if (empty($lives)): ?>
        <div class="lives-container">
            <div class="empty">
                <div style="font-size: 48px; margin-bottom: 16px;">📭</div>
                <h3>No lives found</h3>
            </div>
        </div>
        <?php else: ?>
        <div class="lives-container">
            <?php foreach ($lives as $live): ?>
            <div class="live-card">
                <div class="live-header">
                    <span class="live-gate"><?php echo strtoupper($live['gate']); ?></span>
                    <span class="live-date"><?php echo date('d/m/Y H:i:s', strtotime($live['created_at'])); ?></span>
                </div>
                
                <div class="live-detail">
                    <strong>BIN:</strong> <?php echo $live['bin']; ?>
                </div>
                
                <div class="live-detail">
                    <strong>Card:</strong> <?php echo substr($live['card'], 0, 6) . '******' . substr($live['card'], -4); ?>
                </div>
                
                <div class="live-response">
                    <?php echo nl2br(htmlspecialchars($live['response'])); ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
<?php
exit;
}

// ============================================
// FERRAMENTA ESPECÍFICA (com desconto corrigido)
// ============================================
if (isset($_GET['tool'])) {
    $selected_tool = $_GET['tool'];
    
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
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $gate['name']; ?> - CYBERSEC</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #0a0c0f;
            color: #e4e6eb;
            padding: 30px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .header {
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 16px;
            padding: 20px 30px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .header h1 {
            font-size: 24px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .header h1 span {
            color: <?php echo $gate['color']; ?>;
        }

        .user-info {
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 30px;
            padding: 6px 16px;
            font-size: 14px;
        }

        .nav {
            display: flex;
            gap: 12px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }

        .nav-btn {
            padding: 6px 20px;
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 30px;
            color: #94a3b8;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
        }

        .nav-btn:hover {
            border-color: #3b82f6;
            color: #ffffff;
        }

        .nav-btn.start {
            background: linear-gradient(135deg, #10b981, #059669);
            border: none;
            color: #ffffff;
        }

        .nav-btn.stop {
            border-color: #ef4444;
            color: #fca5a5;
        }

        .nav-btn.stop:hover {
            background: #ef4444;
            color: #ffffff;
        }

        .nav-btn.clear {
            border-color: #f59e0b;
            color: #fcd34d;
        }

        .nav-btn.clear:hover {
            background: #f59e0b;
            color: #000000;
        }

        .loading {
            display: none;
            align-items: center;
            gap: 8px;
            color: #94a3b8;
        }

        .loading.active {
            display: flex;
        }

        .spinner {
            width: 16px;
            height: 16px;
            border: 2px solid #94a3b8;
            border-top-color: transparent;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        textarea {
            width: 100%;
            height: 200px;
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            color: #e4e6eb;
            padding: 16px;
            font-family: 'JetBrains Mono', monospace;
            font-size: 13px;
            resize: vertical;
            margin-bottom: 30px;
        }

        textarea:focus {
            outline: none;
            border-color: #3b82f6;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-box {
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 20px;
            text-align: center;
        }

        .stat-label {
            color: #64748b;
            font-size: 13px;
            margin-bottom: 8px;
        }

        .stat-value {
            font-size: 28px;
            font-weight: 600;
            color: #94a3b8;
        }

        .results-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }

        .result-box {
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(10px);
            border: 1px solid;
            border-radius: 12px;
            padding: 20px;
            max-height: 500px;
            overflow-y: auto;
        }

        .result-box.live {
            border-color: #10b981;
        }

        .result-box.die {
            border-color: #ef4444;
        }

        .result-box h3 {
            color: #ffffff;
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 16px;
            padding-bottom: 8px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            position: sticky;
            top: 0;
            background: rgba(30, 41, 59, 0.9);
        }

        .result-item {
            background: rgba(15, 23, 42, 0.6);
            border-left: 4px solid;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 12px;
            font-family: 'JetBrains Mono', monospace;
            font-size: 12px;
            white-space: pre-wrap;
        }

        .result-item.live {
            border-left-color: #10b981;
        }

        .result-item.die {
            border-left-color: #ef4444;
        }

        .credits-counter, .cyber-counter {
            position: fixed;
            bottom: 30px;
            background: rgba(30, 41, 59, 0.9);
            backdrop-filter: blur(10px);
            border: 1px solid;
            border-radius: 30px;
            padding: 10px 20px;
            font-size: 14px;
            font-weight: 500;
            z-index: 100;
        }

        .credits-counter {
            right: 30px;
            border-color: #f59e0b;
            color: #fcd34d;
        }

        .cyber-counter {
            left: 30px;
            border-color: #94a3b8;
            color: #94a3b8;
        }
    </style>
</head>
<body>
    <?php if ($user_type === 'credits'): ?>
    <div class="credits-counter">
        💳 <span id="currentCredits"><?php echo number_format($user_credits, 2); ?></span>
    </div>
    <?php endif; ?>
    
    <div class="cyber-counter">
        🪙 <span id="currentCyber"><?php echo number_format($user_cyber, 2); ?></span>
    </div>

    <div class="container">
        <div class="header">
            <h1>
                <span><?php echo $gate['icon']; ?></span>
                <?php echo $gate['name']; ?>
            </h1>
            <div class="user-info">👤 <?php echo $_SESSION['username']; ?></div>
        </div>

        <div class="nav">
            <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="nav-btn">Main</a>
            <a href="?ggs" class="nav-btn">Shop</a>
            <?php if ($user_role === 'admin'): ?>
            <a href="?admin=true" class="nav-btn">Admin</a>
            <?php endif; ?>
            <a href="?lives" class="nav-btn">Lives</a>
            <button class="nav-btn start" onclick="startCheck()">▶ Start</button>
            <button class="nav-btn stop" onclick="stopCheck()">⏹ Stop</button>
            <button class="nav-btn clear" onclick="clearAll()">🗑 Clear</button>
            <a href="?logout" class="nav-btn">Logout</a>
            <div class="loading" id="loading">
                <div class="spinner"></div>
                <span>Processing...</span>
            </div>
        </div>

        <textarea id="dataInput" placeholder="Paste cards (one per line):&#10;number|month|year|cvv"></textarea>

        <div class="stats-grid">
            <div class="stat-box">
                <div class="stat-label">Total</div>
                <div class="stat-value" id="totalCount">0</div>
            </div>
            <div class="stat-box">
                <div class="stat-label">✅ Approved</div>
                <div class="stat-value" id="liveCount">0</div>
            </div>
            <div class="stat-box">
                <div class="stat-label">❌ Declined</div>
                <div class="stat-value" id="dieCount">0</div>
            </div>
            <div class="stat-box">
                <div class="stat-label">⚡ Processed</div>
                <div class="stat-value" id="processedCount">0</div>
            </div>
        </div>

        <div class="results-grid">
            <div class="result-box live">
                <h3>✅ Approved</h3>
                <div id="liveResults"></div>
            </div>
            <div class="result-box die">
                <h3>❌ Declined</h3>
                <div id="dieResults"></div>
            </div>
        </div>
    </div>

    <script>
        let isChecking = false;
        let currentIndex = 0;
        let items = [];
        let currentCredits = <?php echo $user_credits; ?>;
        let currentCyber = <?php echo $user_cyber; ?>;
        const toolName = '<?php echo $selected_tool; ?>';
        const userType = '<?php echo $user_type; ?>';
        const MAX_ITEMS = 200;
        const DELAY = 4000;

        function checkIfLive(response) {
            const patterns = ['✅', 'aprovada', 'approved', 'success', 'live', 'autorizado', 'authorized', 'valid', 'aprovado', 'apvd', 'ativa', 'active'];
            response = response.toLowerCase();
            for (const p of patterns) {
                if (response.includes(p.toLowerCase())) return true;
            }
            return false;
        }

        function updateCounters() {
            if (document.getElementById('currentCredits')) {
                document.getElementById('currentCredits').textContent = currentCredits.toFixed(2);
            }
            document.getElementById('currentCyber').textContent = currentCyber.toFixed(2);
        }

        function startCheck() {
            const input = document.getElementById('dataInput').value.trim();
            if (!input) {
                alert('Please enter cards');
                return;
            }

            if (userType === 'credits' && currentCredits < 0.05) {
                alert('Insufficient credits');
                return;
            }

            items = input.split('\n').filter(l => l.trim());
            if (items.length > MAX_ITEMS) {
                alert(`Maximum ${MAX_ITEMS} items allowed`);
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
                    const cost = isLive ? <?php echo LIVE_COST; ?> : <?php echo DIE_COST; ?>;
                    currentCredits = Math.max(0, currentCredits - cost);
                }

                addResult(item, text, isLive);

            } catch (e) {
                addResult(item, 'Error: ' + e.message, false);
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

            const formattedResponse = response.replace(/\n/g, '<br>');
            div.innerHTML = `
                <strong>📱 ${item}</strong><br>
                <br>
                ${formattedResponse}
            `;

            container.insertBefore(div, container.firstChild);

            if (container.children.length > 50) {
                container.removeChild(container.lastChild);
            }

            if (isLive) {
                document.getElementById('liveCount').textContent = parseInt(document.getElementById('liveCount').textContent) + 1;
            } else {
                document.getElementById('dieCount').textContent = parseInt(document.getElementById('dieCount').textContent) + 1;
            }
        }
    </script>
</body>
</html>
<?php
exit;
}

// ============================================
// MENU PRINCIPAL (com rolagem horizontal)
// ============================================
$gates_config = loadGatesConfig();
$active_gates = array_filter($gates_config, function($v) { return $v; });
$ggs_available = count(getGGsByBin());
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CYBERSEC - Main Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #0a0c0f;
            color: #e4e6eb;
            padding: 30px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .header {
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 24px;
            padding: 40px;
            margin-bottom: 30px;
            text-align: center;
            position: relative;
        }

        .header h1 {
            font-size: 36px;
            font-weight: 700;
            letter-spacing: -0.5px;
            color: #ffffff;
            margin-bottom: 8px;
        }

        .header p {
            color: #64748b;
            font-size: 14px;
        }

        .user-info {
            position: absolute;
            top: 20px;
            right: 30px;
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 30px;
            padding: 6px 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .user-badge {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: #ffffff;
            padding: 2px 10px;
            border-radius: 30px;
            font-size: 11px;
            font-weight: 600;
        }

        .status-bar {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-bottom: 30px;
        }

        .status-item {
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 16px 32px;
            text-align: center;
            min-width: 160px;
        }

        .status-credits {
            border-color: #f59e0b;
        }

        .status-cyber {
            border-color: #94a3b8;
        }

        .status-gates {
            border-color: #10b981;
        }

        .status-label {
            color: #64748b;
            font-size: 12px;
            margin-bottom: 6px;
        }

        .value {
            font-size: 20px;
            font-weight: 600;
        }

        .status-credits .value {
            color: #fcd34d;
        }

        .status-cyber .value {
            color: #94a3b8;
        }

        .status-gates .value {
            color: #6ee7b7;
        }

        .nav {
            display: flex;
            gap: 12px;
            margin-bottom: 40px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .nav-btn {
            padding: 8px 24px;
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 30px;
            color: #94a3b8;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .nav-btn:hover {
            border-color: #3b82f6;
            color: #ffffff;
        }

        .nav-btn.ggs {
            border-color: #10b981;
            color: #6ee7b7;
        }

        .nav-btn.ggs:hover {
            background: #10b981;
            color: #000000;
        }

        .nav-btn.lives {
            border-color: #8b5cf6;
            color: #c4b5fd;
        }

        .nav-btn.lives:hover {
            background: #8b5cf6;
            color: #ffffff;
        }

        .nav-btn.youtube {
            border-color: #ff0000;
            color: #ff8888;
        }

        .nav-btn.youtube:hover {
            background: #ff0000;
            color: #ffffff;
        }

        .nav-btn.refs {
            border-color: #10b981;
            color: #6ee7b7;
        }

        .nav-btn.refs:hover {
            background: #10b981;
            color: #000000;
        }

        .nav-btn.baixar {
            border-color: #8b5cf6;
            color: #c4b5fd;
        }

        .nav-btn.baixar:hover {
            background: #8b5cf6;
            color: #ffffff;
        }

        .nav-btn.gerador {
            border-color: #f59e0b;
            color: #fcd34d;
        }

        .nav-btn.gerador:hover {
            background: #f59e0b;
            color: #000000;
        }

        .gates-scroll {
            overflow-x: auto;
            white-space: nowrap;
            padding-bottom: 16px;
            margin-top: 20px;
        }

        .gates-row {
            display: inline-flex;
            gap: 16px;
        }

        .gate-card {
            display: inline-block;
            width: 240px;
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(10px);
            border: 1px solid;
            border-radius: 16px;
            padding: 24px;
            text-decoration: none;
            color: #e4e6eb;
            transition: all 0.2s;
            position: relative;
            white-space: normal;
        }

        .gate-card:hover {
            transform: translateY(-4px);
            border-color: #3b82f6 !important;
            box-shadow: 0 20px 30px -10px rgba(59, 130, 246, 0.3);
        }

        .gate-card.inactive {
            opacity: 0.5;
            pointer-events: none;
        }

        .gate-icon {
            font-size: 32px;
            text-align: center;
            margin-bottom: 16px;
        }

        .gate-card h3 {
            font-size: 16px;
            font-weight: 600;
            text-align: center;
            margin-bottom: 12px;
            color: #94a3b8;
        }

        .gate-status {
            position: absolute;
            top: 16px;
            right: 16px;
            width: 8px;
            height: 8px;
            border-radius: 50%;
        }

        .status-active {
            background: #10b981;
            box-shadow: 0 0 10px #10b981;
        }

        .status-inactive {
            background: #ef4444;
            box-shadow: 0 0 10px #ef4444;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-top: 40px;
        }

        .info-card {
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 20px;
            text-align: center;
        }

        .info-card .icon {
            font-size: 24px;
            margin-bottom: 8px;
        }

        .info-card .title {
            color: #64748b;
            font-size: 13px;
            margin-bottom: 4px;
        }

        .info-card .value {
            font-size: 16px;
            font-weight: 600;
            color: #94a3b8;
        }

        .ggs-badge {
            background: #10b981;
            color: #000000;
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 600;
            margin-left: 8px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚀 CYBERSEC 4.0</h1>
            <p>ENTERPRISE CHECKER PLATFORM</p>

            <div class="user-info">
                <span>👤 <?php echo $_SESSION['username']; ?></span>
                <?php if ($user_role === 'admin'): ?>
                <span class="user-badge">ADMIN</span>
                <?php endif; ?>
            </div>
        </div>

        <div class="status-bar">
            <?php if ($user_type === 'credits'): ?>
            <div class="status-item status-credits">
                <div class="status-label">Credits</div>
                <div class="value"><?php echo number_format($user_credits, 2); ?></div>
            </div>
            <?php endif; ?>

            <div class="status-item status-cyber">
                <div class="status-label">Cyber Coins</div>
                <div class="value"><?php echo number_format($user_cyber, 2); ?></div>
            </div>

            <div class="status-item status-gates">
                <div class="status-label">Active Gates</div>
                <div class="value"><?php echo count($active_gates); ?>/<?php echo count($all_gates); ?></div>
            </div>
        </div>

        <div class="nav">
            <a href="?ggs" class="nav-btn ggs">
                <i class="fas fa-store"></i> GG Shop
                <?php if ($ggs_available > 0): ?>
                <span class="ggs-badge"><?php echo $ggs_available; ?></span>
                <?php endif; ?>
            </a>
            <a href="?lives" class="nav-btn lives">
                <i class="fas fa-history"></i> Live History
            </a>
            <a href="?ggs&tab=youtube" class="nav-btn youtube">
                <i class="fab fa-youtube"></i> YouTube
            </a>
            <a href="?ggs&tab=refs" class="nav-btn refs">
                <i class="fas fa-images"></i> Refs
            </a>
            <a href="?ggs&tab=baixar" class="nav-btn baixar">
                <i class="fas fa-download"></i> Baixar
            </a>
            <a href="?ggs&tab=gerador" class="nav-btn gerador">
                <i class="fas fa-dice"></i> Gerador
            </a>
            <?php if ($user_role === 'admin'): ?>
            <a href="?admin=true" class="nav-btn">
                <i class="fas fa-cog"></i> Admin
            </a>
            <?php endif; ?>
            <a href="?logout" class="nav-btn">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>

        <h2 style="font-size: 18px; font-weight: 600; color: #94a3b8; margin-bottom: 16px;">🔧 Available Checkers</h2>

        <div class="gates-scroll">
            <div class="gates-row">
                <?php foreach ($all_gates as $key => $gate): 
                    $isActive = $gates_config[$key] ?? false;
                ?>
                <a href="?tool=<?php echo $key; ?>" class="gate-card <?php echo !$isActive ? 'inactive' : ''; ?>" style="border-color: <?php echo $gate['color']; ?>40">
                    <div class="gate-icon"><?php echo $gate['icon']; ?></div>
                    <h3><?php echo $gate['name']; ?></h3>
                    <div class="gate-status <?php echo $isActive ? 'status-active' : 'status-inactive'; ?>"></div>
                    <p style="color: #64748b; font-size: 11px; text-align: center;">
                        <?php echo $isActive ? 'Active' : 'Inactive'; ?>
                    </p>
                </a>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="info-grid">
            <div class="info-card">
                <div class="icon">📊</div>
                <div class="title">Total Checks</div>
                <div class="value"><?php echo $current_user['total_checks'] ?? 0; ?></div>
            </div>

            <div class="info-card">
                <div class="icon">✅</div>
                <div class="title">Total Lives</div>
                <div class="value"><?php echo $current_user['total_lives'] ?? 0; ?></div>
            </div>

            <div class="info-card">
                <div class="icon">📅</div>
                <div class="title">Member Since</div>
                <div class="value"><?php echo isset($current_user['created_at']) ? date('d/m/Y', strtotime($current_user['created_at'])) : date('d/m/Y'); ?></div>
            </div>

            <div class="info-card">
                <div class="icon">🔐</div>
                <div class="title">Last Login</div>
                <div class="value"><?php echo isset($current_user['last_login']) ? date('d/m/Y H:i', strtotime($current_user['last_login'])) : 'First access'; ?></div>
            </div>
        </div>
    </div>
</body>
</html>
<?php
// Fim do código
?>
