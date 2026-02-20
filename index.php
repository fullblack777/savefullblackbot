<?php
// ============================================
// CYBERSEC 4.0 - VERSÃO PREMIUM
// SISTEMA COMPLETO DE CHECKERS COM LOJA GG
// ============================================

ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);
date_default_timezone_set('America/Sao_Paulo');
session_start();

// ============================================
// CONFIGURAÇÕES DO BANCO DE DADOS SQLITE
// ============================================
class Database {
    private static $instance = null;
    private $db;
    
    private function __construct() {
        $db_file = __DIR__ . '/data/cybersec.db';
        $db_dir = dirname($db_file);
        
        if (!file_exists($db_dir)) {
            mkdir($db_dir, 0755, true);
        }
        
        $this->db = new SQLite3($db_file);
        $this->db->busyTimeout(5000);
        $this->db->exec('PRAGMA foreign_keys = ON');
        $this->createTables();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance->db;
    }
    
    private function createTables() {
        // Tabela de usuários
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
        
        // Tabela de lives
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
        
        // Tabela de GGs (cartões para venda)
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
        
        // Tabela de BINs (configurações de preço)
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
        
        // Inserir configurações padrão se não existirem
        $default_settings = [
            'telegram_token' => '8586131107:AAF6fDbrjm7CoVI2g1Zkx2agmXJgmbdnCVQ',
            'telegram_chat' => '-1003581267007',
            'site_url' => 'https://' . $_SERVER['HTTP_HOST'],
            'live_cost' => '2.00',
            'die_cost' => '0.05'
        ];
        
        foreach ($default_settings as $key => $value) {
            $stmt = $this->db->prepare('INSERT OR IGNORE INTO settings (key, value) VALUES (:key, :value)');
            $stmt->bindValue(':key', $key, SQLITE3_TEXT);
            $stmt->bindValue(':value', $value, SQLITE3_TEXT);
            $stmt->execute();
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
// FUNÇÕES DO BANCO DE DADOS
// ============================================
function getDB() {
    return Database::getInstance();
}

// Configurações
function loadSettings() {
    $db = getDB();
    $result = $db->query('SELECT key, value FROM settings');
    $settings = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $settings[$row['key']] = $row['value'];
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

// Usuários
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
// FUNÇÃO DO TELEGRAM
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
// PROCESSAR LOGIN/REGISTRO (COM CYBERCAP)
// ============================================
// Gerar novo desafio CyberCap se não existir
if (!isset($_SESSION['cybercap_num1']) || !isset($_SESSION['cybercap_num2']) || !isset($_SESSION['cybercap_result'])) {
    $_SESSION['cybercap_num1'] = rand(1, 9);
    $_SESSION['cybercap_num2'] = rand(1, 9);
    $_SESSION['cybercap_result'] = $_SESSION['cybercap_num1'] + $_SESSION['cybercap_num2'];
}

if (isset($_POST['login'])) {
    if (!isset($_SESSION['login_attempts'])) $_SESSION['login_attempts'] = 0;
    
    $username = preg_replace('/[^a-zA-Z0-9_]/', '', $_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $cybercap_verified = isset($_POST['cybercap_verified']) && $_POST['cybercap_verified'] === 'true';
    $cybercap_answer = intval($_POST['cybercap'] ?? -1);
    
    // Verificar CyberCap
    if (!$cybercap_verified) {
        $login_error = 'Please complete the CyberCap verification';
    } elseif ($cybercap_answer !== $_SESSION['cybercap_result']) {
        $login_error = 'Incorrect CyberCap answer';
        // Gerar novo desafio
        $_SESSION['cybercap_num1'] = rand(1, 9);
        $_SESSION['cybercap_num2'] = rand(1, 9);
        $_SESSION['cybercap_result'] = $_SESSION['cybercap_num1'] + $_SESSION['cybercap_num2'];
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
            $_SESSION['cybercap_num1'] = rand(1, 9);
            $_SESSION['cybercap_num2'] = rand(1, 9);
            $_SESSION['cybercap_result'] = $_SESSION['cybercap_num1'] + $_SESSION['cybercap_num2'];
        }
    }
}

if (isset($_POST['register'])) {
    $username = preg_replace('/[^a-zA-Z0-9_]/', '', $_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $cybercap_verified = isset($_POST['cybercap_verified']) && $_POST['cybercap_verified'] === 'true';
    $cybercap_answer = intval($_POST['cybercap'] ?? -1);
    
    // Verificar CyberCap
    if (!$cybercap_verified) {
        $register_error = 'Please complete the CyberCap verification';
    } elseif ($cybercap_answer !== $_SESSION['cybercap_result']) {
        $register_error = 'Incorrect CyberCap answer';
        // Gerar novo desafio
        $_SESSION['cybercap_num1'] = rand(1, 9);
        $_SESSION['cybercap_num2'] = rand(1, 9);
        $_SESSION['cybercap_result'] = $_SESSION['cybercap_num1'] + $_SESSION['cybercap_num2'];
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
// PROCESSAR ADMIN ACTIONS
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
// PROCESSAR CHECKER (AJAX)
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
        
        // Notificar live no Telegram
        $gate_name = $all_gates[$tool]['name'];
        sendTelegramMessage("✅ *LIVE*\n\n**User:** `$username`\n**Gate:** $gate_name\n**BIN:** `$bin`");
    }
    
    if ($user['type'] === 'credits') {
        $cost = $isLive ? LIVE_COST : DIE_COST;
        $remaining = deductCredits($username, $cost);
        $response .= "\n\n💳 Cost: R$ " . number_format($cost, 2) . " | Remaining: R$ " . number_format($remaining, 2);
    }
    
    echo $response;
    exit;
}

// ============================================
// VERIFICAR ACESSO
// ============================================
if (!checkAccess()) {
    // Gerar novo desafio CyberCap se não existir
    if (!isset($_SESSION['cybercap_num1']) || !isset($_SESSION['cybercap_num2']) || !isset($_SESSION['cybercap_result'])) {
        $_SESSION['cybercap_num1'] = rand(1, 9);
        $_SESSION['cybercap_num2'] = rand(1, 9);
        $_SESSION['cybercap_result'] = $_SESSION['cybercap_num1'] + $_SESSION['cybercap_num2'];
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CYBERSEC OFC - Login</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: "Inter", -apple-system, BlinkMacSystemFont, sans-serif;
            background: #0a0c0f;
            color: #e4e6eb;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            line-height: 1.5;
        }

        .container {
            width: 100%;
            max-width: 480px;
            padding: 20px;
        }

        .brand {
            text-align: center;
            margin-bottom: 32px;
        }

        .brand h1 {
            font-size: 28px;
            font-weight: 600;
            letter-spacing: -0.5px;
            background: linear-gradient(135deg, #8b949e, #e4e6eb);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 4px;
        }

        .brand p {
            color: #6e7681;
            font-size: 14px;
            font-weight: 400;
        }

        .card {
            background: #161b22;
            border: 1px solid #30363d;
            border-radius: 12px;
            padding: 32px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.5);
        }

        .tabs {
            display: flex;
            gap: 8px;
            margin-bottom: 24px;
            background: #0d1117;
            padding: 4px;
            border-radius: 8px;
            border: 1px solid #30363d;
        }

        .tab {
            flex: 1;
            padding: 10px;
            text-align: center;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            color: #8b949e;
            cursor: pointer;
            transition: all 0.2s;
        }

        .tab.active {
            background: #21262d;
            color: #e4e6eb;
        }

        .form {
            display: none;
        }

        .form.active {
            display: block;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-size: 13px;
            font-weight: 500;
            color: #8b949e;
        }

        .form-group input {
            width: 100%;
            padding: 12px 14px;
            background: #0d1117;
            border: 1px solid #30363d;
            border-radius: 8px;
            color: #e4e6eb;
            font-size: 14px;
            transition: all 0.2s;
        }

        .form-group input:focus {
            outline: none;
            border-color: #2f81f7;
            box-shadow: 0 0 0 3px rgba(47, 129, 247, 0.1);
        }

        .form-group input::placeholder {
            color: #6e7681;
        }

        /* CyberCap - Estilo igual reCAPTCHA */
        .cybercap-container {
            margin: 24px 0;
            border: 1px solid #30363d;
            border-radius: 8px;
            background: #0d1117;
            overflow: hidden;
        }

        .cybercap-header {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 16px;
            background: #161b22;
            border-bottom: 1px solid #30363d;
        }

        .cybercap-checkbox {
            width: 24px;
            height: 24px;
            border: 2px solid #30363d;
            border-radius: 4px;
            background: #0d1117;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }

        .cybercap-checkbox.checked {
            background: #238636;
            border-color: #2ea043;
        }

        .cybercap-checkbox.checked::after {
            content: "✓";
            color: #fff;
            font-size: 16px;
            font-weight: bold;
        }

        .cybercap-title {
            flex: 1;
            font-size: 14px;
            font-weight: 500;
            color: #e4e6eb;
        }

        .cybercap-logo {
            color: #8b949e;
            font-size: 12px;
            font-weight: 400;
        }

        .cybercap-challenge {
            display: none;
            padding: 20px;
            background: #0d1117;
        }

        .cybercap-challenge.active {
            display: block;
        }

        .challenge-content {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 16px;
            margin-bottom: 16px;
        }

        .challenge-number {
            background: #161b22;
            border: 1px solid #30363d;
            border-radius: 6px;
            padding: 12px 24px;
            font-size: 24px;
            font-weight: 600;
            color: #2f81f7;
        }

        .challenge-operator {
            font-size: 20px;
            font-weight: 600;
            color: #8b949e;
        }

        .challenge-input {
            width: 100%;
            padding: 12px;
            background: #0d1117;
            border: 1px solid #30363d;
            border-radius: 6px;
            color: #e4e6eb;
            font-size: 16px;
            text-align: center;
        }

        .challenge-input:focus {
            outline: none;
            border-color: #2f81f7;
        }

        .btn-primary {
            width: 100%;
            padding: 14px;
            background: #238636;
            border: none;
            border-radius: 8px;
            color: #fff;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.2s;
            margin: 8px 0 16px;
        }

        .btn-primary:hover {
            background: #2ea043;
        }

        .btn-primary:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .message {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            font-weight: 500;
        }

        .message.error {
            background: rgba(248, 81, 73, 0.1);
            border: 1px solid #f85149;
            color: #f85149;
        }

        .message.success {
            background: rgba(46, 160, 67, 0.1);
            border: 1px solid #2ea043;
            color: #2ea043;
        }

        .footer {
            margin-top: 24px;
            text-align: center;
        }

        .footer a {
            color: #6e7681;
            text-decoration: none;
            font-size: 13px;
            transition: color 0.2s;
        }

        .footer a:hover {
            color: #2f81f7;
        }

        .version {
            margin-top: 20px;
            color: #30363d;
            font-size: 12px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="brand">
            <h1>CYBERSEC OFC</h1>
            <p>Enterprise Security Platform</p>
        </div>

        <div class="card">
            <div class="tabs">
                <div class="tab active" onclick="switchTab('login')">Login</div>
                <div class="tab" onclick="switchTab('register')">Register</div>
                <div class="tab" onclick="switchTab('shop')">Shop</div>
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
            <form class="form active" id="loginForm" method="POST" onsubmit="return validateCyberCap('login')">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" required placeholder="Enter your username">
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required placeholder="Enter your password">
                </div>

                <!-- CyberCap Component -->
                <div class="cybercap-container">
                    <div class="cybercap-header" onclick="toggleCyberCap('login')">
                        <div class="cybercap-checkbox" id="loginCyberCapCheck"></div>
                        <div class="cybercap-title">I'm not a robot</div>
                        <div class="cybercap-logo">CyberCap</div>
                    </div>
                    <div class="cybercap-challenge" id="loginCyberCapChallenge">
                        <div class="challenge-content">
                            <span class="challenge-number"><?php echo $_SESSION['cybercap_num1']; ?></span>
                            <span class="challenge-operator">+</span>
                            <span class="challenge-number"><?php echo $_SESSION['cybercap_num2']; ?></span>
                        </div>
                        <input type="number" class="challenge-input" id="loginCyberCapAnswer" placeholder="Enter result" autocomplete="off">
                        <input type="hidden" name="cybercap_verified" id="loginCyberCapVerified" value="false">
                        <input type="hidden" name="cybercap" id="loginCyberCapValue" value="">
                    </div>
                </div>

                <button type="submit" name="login" class="btn-primary">Sign In</button>
            </form>

            <!-- Register Form -->
            <form class="form" id="registerForm" method="POST" onsubmit="return validateCyberCap('register')">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" required placeholder="Minimum 3 characters" pattern="[a-zA-Z0-9_]{3,}">
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required placeholder="Minimum 4 characters" minlength="4">
                </div>
                <div class="form-group">
                    <label>Confirm Password</label>
                    <input type="password" name="confirm_password" required placeholder="Confirm your password">
                </div>

                <!-- CyberCap Component -->
                <div class="cybercap-container">
                    <div class="cybercap-header" onclick="toggleCyberCap('register')">
                        <div class="cybercap-checkbox" id="registerCyberCapCheck"></div>
                        <div class="cybercap-title">I'm not a robot</div>
                        <div class="cybercap-logo">CyberCap</div>
                    </div>
                    <div class="cybercap-challenge" id="registerCyberCapChallenge">
                        <div class="challenge-content">
                            <span class="challenge-number"><?php echo $_SESSION['cybercap_num1']; ?></span>
                            <span class="challenge-operator">+</span>
                            <span class="challenge-number"><?php echo $_SESSION['cybercap_num2']; ?></span>
                        </div>
                        <input type="number" class="challenge-input" id="registerCyberCapAnswer" placeholder="Enter result" autocomplete="off">
                        <input type="hidden" name="cybercap_verified" id="registerCyberCapVerified" value="false">
                        <input type="hidden" name="cybercap" id="registerCyberCapValue" value="">
                    </div>
                </div>

                <button type="submit" name="register" class="btn-primary">Create Account</button>
            </form>

            <!-- Shop Form -->
            <form class="form" id="shopForm" method="GET" action="?ggs">
                <div style="text-align: center; padding: 20px 0;">
                    <div style="font-size: 48px; margin-bottom: 16px; opacity: 0.5;">🛒</div>
                    <h3 style="color: #e4e6eb; margin-bottom: 12px;">GG Shop</h3>
                    <p style="color: #8b949e; margin-bottom: 24px;">Access the premium card shop after login.</p>
                    <button type="submit" class="btn-primary" style="width: auto; padding: 12px 32px;">Go to Shop</button>
                </div>
            </form>

            <div class="footer">
                <a href="https://t.me/centralsavefullblack" target="_blank">@centralsavefullblack</a>
            </div>
            <div class="version">v4.0 • Enterprise Edition</div>
        </div>
    </div>

    <script>
        let activeCyberCap = null;

        function switchTab(tab) {
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.form').forEach(f => f.classList.remove('active'));

            if (tab === 'login') {
                document.querySelectorAll('.tab')[0].classList.add('active');
                document.getElementById('loginForm').classList.add('active');
                resetCyberCap('login');
            } else if (tab === 'register') {
                document.querySelectorAll('.tab')[1].classList.add('active');
                document.getElementById('registerForm').classList.add('active');
                resetCyberCap('register');
            } else if (tab === 'shop') {
                document.querySelectorAll('.tab')[2].classList.add('active');
                document.getElementById('shopForm').classList.add('active');
            }
        }

        function toggleCyberCap(formId) {
            const checkEl = document.getElementById(formId + 'CyberCapCheck');
            const challengeEl = document.getElementById(formId + 'CyberCapChallenge');
            
            if (activeCyberCap && activeCyberCap !== formId) {
                resetCyberCap(activeCyberCap);
            }
            
            if (!checkEl.classList.contains('checked')) {
                checkEl.classList.add('checked');
                challengeEl.classList.add('active');
                activeCyberCap = formId;
            }
        }

        function resetCyberCap(formId) {
            const checkEl = document.getElementById(formId + 'CyberCapCheck');
            const challengeEl = document.getElementById(formId + 'CyberCapChallenge');
            const verifiedEl = document.getElementById(formId + 'CyberCapVerified');
            const answerEl = document.getElementById(formId + 'CyberCapAnswer');
            
            if (checkEl) checkEl.classList.remove('checked');
            if (challengeEl) challengeEl.classList.remove('active');
            if (verifiedEl) verifiedEl.value = 'false';
            if (answerEl) answerEl.value = '';
            
            if (activeCyberCap === formId) {
                activeCyberCap = null;
            }
        }

        function validateCyberCap(formId) {
            const verifiedEl = document.getElementById(formId + 'CyberCapVerified');
            const answerEl = document.getElementById(formId + 'CyberCapAnswer');
            const valueEl = document.getElementById(formId + 'CyberCapValue');
            
            if (!verifiedEl || verifiedEl.value !== 'true') {
                alert('Please complete the CyberCap verification');
                return false;
            }
            
            if (answerEl && valueEl) {
                valueEl.value = answerEl.value;
            }
            
            return true;
        }

        // Handle CyberCap answer input
        document.getElementById('loginCyberCapAnswer')?.addEventListener('input', function(e) {
            const verifiedEl = document.getElementById('loginCyberCapVerified');
            verifiedEl.value = 'true';
        });

        document.getElementById('registerCyberCapAnswer')?.addEventListener('input', function(e) {
            const verifiedEl = document.getElementById('registerCyberCapVerified');
            verifiedEl.value = 'true';
        });

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
// PAINEL ADMIN (versão profissional)
// ============================================
if ($user_role === 'admin' && isset($_GET['admin'])) {
    $users = loadUsers();
    $gates_config = loadGatesConfig();
    $bins = getAllBins();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CYBERSEC - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
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
            background: #161b22;
            border: 1px solid #30363d;
            border-radius: 8px;
            padding: 20px 30px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .header h1 {
            font-size: 24px;
            font-weight: 500;
            color: #e4e6eb;
        }

        .header-actions {
            display: flex;
            gap: 12px;
        }

        .btn {
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.2s;
            border: 1px solid #30363d;
            background: #0d1117;
            color: #8b949e;
        }

        .btn:hover {
            border-color: #2f81f7;
            color: #e4e6eb;
        }

        .btn-danger {
            border-color: #f85149;
            color: #f85149;
        }

        .btn-danger:hover {
            background: #f85149;
            color: #0d1117;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 24px;
            margin-bottom: 30px;
        }

        .card {
            background: #161b22;
            border: 1px solid #30363d;
            border-radius: 8px;
            padding: 24px;
        }

        .card-header {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 20px;
            padding-bottom: 16px;
            border-bottom: 1px solid #30363d;
        }

        .card-header h2 {
            font-size: 18px;
            font-weight: 500;
            color: #e4e6eb;
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-size: 13px;
            font-weight: 500;
            color: #8b949e;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px 12px;
            background: #0d1117;
            border: 1px solid #30363d;
            border-radius: 6px;
            color: #e4e6eb;
            font-size: 14px;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #2f81f7;
        }

        .btn-primary {
            width: 100%;
            padding: 12px;
            background: #238636;
            border: none;
            border-radius: 6px;
            color: #fff;
            font-weight: 500;
            cursor: pointer;
        }

        .btn-primary:hover {
            background: #2ea043;
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
            padding: 10px 16px;
            background: #0d1117;
            border: 1px solid;
            border-radius: 6px;
            cursor: pointer;
        }

        .gate-status {
            width: 8px;
            height: 8px;
            border-radius: 50%;
        }

        .status-active {
            background: #2ea043;
            box-shadow: 0 0 10px #2ea043;
        }

        .status-inactive {
            background: #f85149;
            box-shadow: 0 0 10px #f85149;
        }

        .users-table {
            width: 100%;
            border-collapse: collapse;
        }

        .users-table th {
            text-align: left;
            padding: 12px;
            background: #0d1117;
            color: #8b949e;
            font-weight: 500;
            font-size: 13px;
        }

        .users-table td {
            padding: 12px;
            border-bottom: 1px solid #30363d;
        }

        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 500;
        }

        .badge-admin {
            background: #f85149;
            color: #0d1117;
        }

        .badge-permanent {
            background: #2ea043;
            color: #0d1117;
        }

        .badge-temporary {
            background: #f7b731;
            color: #0d1117;
        }

        .badge-credits {
            background: #2f81f7;
            color: #fff;
        }

        .action-btn {
            padding: 4px 8px;
            background: #0d1117;
            border: 1px solid #30363d;
            border-radius: 4px;
            color: #8b949e;
            cursor: pointer;
        }

        .action-btn:hover {
            border-color: #2f81f7;
            color: #e4e6eb;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>⚙️ Admin Dashboard</h1>
            <div class="header-actions">
                <span style="color: #8b949e;">👑 <?php echo $_SESSION['username']; ?></span>
                <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="btn">Main</a>
                <a href="?logout" class="btn btn-danger">Logout</a>
            </div>
        </div>

        <?php if (isset($success_message)): ?>
        <div style="background: rgba(46, 160, 67, 0.1); border: 1px solid #2ea043; border-radius: 6px; padding: 12px 20px; margin-bottom: 20px; color: #2ea043;"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
        <div style="background: rgba(248, 81, 73, 0.1); border: 1px solid #f85149; border-radius: 6px; padding: 12px 20px; margin-bottom: 20px; color: #f85149;"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <div class="grid">
            <!-- Create User -->
            <div class="card">
                <div class="card-header">
                    <span>👤</span>
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
                    <button type="submit" class="btn-primary">Create</button>
                </form>
            </div>

            <!-- Gate Management -->
            <div class="card">
                <div class="card-header">
                    <span>🔧</span>
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
                    <span>📥</span>
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
                    <span>💰</span>
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
                                        <input type="number" name="price" step="0.01" value="<?php echo $bin['price']; ?>" min="0.01" style="width: 80px; background: #0d1117; border: 1px solid #30363d; color: #e4e6eb; padding: 4px; border-radius: 4px;">
                                        <button type="submit" style="background: #238636; border: none; color: #fff; padding: 4px 8px; border-radius: 4px; cursor: pointer;">OK</button>
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
                <span>📋</span>
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
                                <button class="action-btn" onclick="editUser('<?php echo $username; ?>', <?php echo $data['credits']; ?>, <?php echo $data['cyber_money']; ?>)">✏️</button>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="admin_action" value="remove">
                                    <input type="hidden" name="username" value="<?php echo $username; ?>">
                                    <button class="action-btn" onclick="return confirm('Remove user?')">🗑️</button>
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

        function editUser(username, credits, cyber) {
            const addCredits = prompt(`Add credits to ${username} (current: ${credits}):`);
            if (addCredits !== null && !isNaN(addCredits) && addCredits > 0) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="admin_action" value="add_user_credits">
                    <input type="hidden" name="username" value="${username}">
                    <input type="hidden" name="credits" value="${addCredits}">
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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CYBERSEC - GG Shop</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
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
            background: #161b22;
            border: 1px solid #30363d;
            border-radius: 8px;
            padding: 20px 30px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .header h1 {
            font-size: 24px;
            font-weight: 500;
            color: #e4e6eb;
        }

        .balance {
            background: #0d1117;
            border: 1px solid #30363d;
            border-radius: 6px;
            padding: 8px 16px;
            color: #2ea043;
            font-weight: 500;
        }

        .nav {
            display: flex;
            gap: 12px;
            margin-bottom: 30px;
        }

        .nav-btn {
            padding: 8px 16px;
            background: #0d1117;
            border: 1px solid #30363d;
            border-radius: 6px;
            color: #8b949e;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s;
        }

        .nav-btn:hover,
        .nav-btn.active {
            border-color: #2f81f7;
            color: #e4e6eb;
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
            background: #161b22;
            border: 1px solid #30363d;
            border-radius: 8px;
            padding: 20px;
            cursor: pointer;
            transition: all 0.2s;
            white-space: normal;
        }

        .bin-card:hover {
            border-color: #2f81f7;
            transform: translateY(-2px);
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
            color: #e4e6eb;
        }

        .bin-price {
            background: #238636;
            color: #fff;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 13px;
            font-weight: 500;
        }

        .bin-info {
            color: #8b949e;
            font-size: 14px;
        }

        .cards-table {
            width: 100%;
            border-collapse: collapse;
        }

        .cards-table th {
            text-align: left;
            padding: 12px;
            background: #0d1117;
            color: #8b949e;
            font-weight: 500;
            font-size: 13px;
        }

        .cards-table td {
            padding: 12px;
            border-bottom: 1px solid #30363d;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: #161b22;
            border: 1px solid #30363d;
            border-radius: 8px;
            padding: 24px;
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
            border-bottom: 1px solid #30363d;
        }

        .modal-header h2 {
            font-size: 18px;
            font-weight: 500;
            color: #e4e6eb;
        }

        .modal-close {
            font-size: 20px;
            cursor: pointer;
            color: #8b949e;
        }

        .modal-close:hover {
            color: #f85149;
        }

        .card-item {
            background: #0d1117;
            border: 1px solid #30363d;
            border-radius: 6px;
            padding: 16px;
            margin-bottom: 12px;
        }

        .btn-buy {
            width: 100%;
            padding: 10px;
            background: #238636;
            border: none;
            border-radius: 4px;
            color: #fff;
            font-weight: 500;
            cursor: pointer;
            margin-top: 12px;
        }

        .btn-buy:hover {
            background: #2ea043;
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
            background: #0d1117;
            border: 1px solid #30363d;
            border-radius: 4px;
            color: #8b949e;
            cursor: pointer;
        }

        .page-btn.active {
            border-color: #2f81f7;
            color: #e4e6eb;
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
            <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="nav-btn">Main</a>
            <a href="?ggs" class="nav-btn <?php echo !isset($_GET['mycards']) ? 'active' : ''; ?>">Buy</a>
            <a href="?ggs&mycards=1" class="nav-btn <?php echo isset($_GET['mycards']) ? 'active' : ''; ?>">My Cards</a>
            <a href="?lives" class="nav-btn">Lives</a>
            <a href="?logout" class="nav-btn">Logout</a>
        </div>

        <?php if (isset($_SESSION['purchase_result'])): ?>
        <div style="background: rgba(46, 160, 67, 0.1); border: 1px solid #2ea043; border-radius: 6px; padding: 16px; margin-bottom: 20px; white-space: pre-wrap; font-family: monospace;">
            <?php echo nl2br($_SESSION['purchase_result']); ?>
        </div>
        <?php unset($_SESSION['purchase_result']); ?>
        <?php endif; ?>

        <?php if (isset($_GET['mycards'])): ?>
            <div style="background: #161b22; border: 1px solid #30363d; border-radius: 8px; padding: 24px;">
                <h2 style="font-size: 18px; font-weight: 500; color: #e4e6eb; margin-bottom: 20px;">📋 My Purchased Cards</h2>
                <?php if (empty($purchased_cards)): ?>
                <div style="text-align: center; padding: 40px; color: #8b949e;">
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
            </div>
        <?php else: ?>
            <h2 style="font-size: 18px; font-weight: 500; color: #e4e6eb; margin-bottom: 16px;">🔍 Available Cards</h2>
            <?php if (empty($ggs_by_bin)): ?>
            <div style="text-align: center; padding: 60px; background: #161b22; border: 1px solid #30363d; border-radius: 8px;">
                <div style="font-size: 48px; margin-bottom: 16px;">📭</div>
                <h3 style="color: #8b949e;">No cards available</h3>
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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CYBERSEC - Live History</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
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
            background: #161b22;
            border: 1px solid #30363d;
            border-radius: 8px;
            padding: 20px 30px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .header h1 {
            font-size: 24px;
            font-weight: 500;
            color: #e4e6eb;
        }

        .nav {
            display: flex;
            gap: 12px;
        }

        .btn {
            padding: 6px 12px;
            background: #0d1117;
            border: 1px solid #30363d;
            border-radius: 4px;
            color: #8b949e;
            text-decoration: none;
            font-size: 13px;
            transition: all 0.2s;
        }

        .btn:hover {
            border-color: #2f81f7;
            color: #e4e6eb;
        }

        .btn-export {
            border-color: #2ea043;
            color: #2ea043;
        }

        .btn-export:hover {
            background: #2ea043;
            color: #0d1117;
        }

        .lives-container {
            background: #161b22;
            border: 1px solid #30363d;
            border-radius: 8px;
            padding: 24px;
        }

        .live-card {
            background: #0d1117;
            border: 1px solid #30363d;
            border-radius: 6px;
            padding: 16px;
            margin-bottom: 16px;
        }

        .live-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 1px solid #30363d;
        }

        .live-gate {
            background: #238636;
            color: #fff;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }

        .live-date {
            color: #8b949e;
            font-size: 12px;
        }

        .live-detail {
            margin-bottom: 6px;
            font-size: 13px;
        }

        .live-detail strong {
            color: #2f81f7;
        }

        .live-response {
            background: #0a0c0f;
            border: 1px solid #30363d;
            border-radius: 4px;
            padding: 12px;
            font-family: 'Monaco', 'Menlo', monospace;
            font-size: 12px;
            white-space: pre-wrap;
            margin-top: 12px;
        }

        .empty {
            text-align: center;
            padding: 40px;
            color: #8b949e;
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
// FERRAMENTA ESPECÍFICA
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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $gate['name']; ?> - CYBERSEC</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=JetBrains+Mono&display=swap" rel="stylesheet">
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
            background: #161b22;
            border: 1px solid #30363d;
            border-radius: 8px;
            padding: 20px 30px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .header h1 {
            font-size: 24px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .header h1 span {
            color: <?php echo $gate['color']; ?>;
        }

        .user-info {
            background: #0d1117;
            border: 1px solid #30363d;
            border-radius: 6px;
            padding: 6px 12px;
            font-size: 14px;
        }

        .nav {
            display: flex;
            gap: 12px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }

        .nav-btn {
            padding: 6px 16px;
            background: #0d1117;
            border: 1px solid #30363d;
            border-radius: 4px;
            color: #8b949e;
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
        }

        .nav-btn:hover {
            border-color: #2f81f7;
            color: #e4e6eb;
        }

        .nav-btn.start {
            background: #238636;
            border-color: #2ea043;
            color: #fff;
        }

        .nav-btn.stop {
            border-color: #f85149;
            color: #f85149;
        }

        .nav-btn.stop:hover {
            background: #f85149;
            color: #0d1117;
        }

        .nav-btn.clear {
            border-color: #f7b731;
            color: #f7b731;
        }

        .nav-btn.clear:hover {
            background: #f7b731;
            color: #0d1117;
        }

        .loading {
            display: none;
            align-items: center;
            gap: 8px;
            color: #2f81f7;
        }

        .loading.active {
            display: flex;
        }

        .spinner {
            width: 16px;
            height: 16px;
            border: 2px solid #2f81f7;
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
            background: #0d1117;
            border: 1px solid #30363d;
            border-radius: 6px;
            color: #e4e6eb;
            padding: 16px;
            font-family: 'JetBrains Mono', monospace;
            font-size: 13px;
            resize: vertical;
            margin-bottom: 30px;
        }

        textarea:focus {
            outline: none;
            border-color: #2f81f7;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-box {
            background: #161b22;
            border: 1px solid #30363d;
            border-radius: 6px;
            padding: 16px;
            text-align: center;
        }

        .stat-label {
            color: #8b949e;
            font-size: 12px;
            margin-bottom: 8px;
        }

        .stat-value {
            font-size: 24px;
            font-weight: 600;
            color: #e4e6eb;
        }

        .results-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }

        .result-box {
            background: #161b22;
            border: 1px solid;
            border-radius: 6px;
            padding: 20px;
            max-height: 500px;
            overflow-y: auto;
        }

        .result-box.live {
            border-color: #2ea043;
        }

        .result-box.die {
            border-color: #f85149;
        }

        .result-box h3 {
            color: #e4e6eb;
            font-size: 16px;
            font-weight: 500;
            margin-bottom: 16px;
            padding-bottom: 8px;
            border-bottom: 1px solid #30363d;
            position: sticky;
            top: 0;
            background: #161b22;
        }

        .result-item {
            background: #0d1117;
            border-left: 4px solid;
            border-radius: 4px;
            padding: 12px;
            margin-bottom: 12px;
            font-family: 'JetBrains Mono', monospace;
            font-size: 12px;
            white-space: pre-wrap;
        }

        .result-item.live {
            border-left-color: #2ea043;
        }

        .result-item.die {
            border-left-color: #f85149;
        }

        .credits-counter, .cyber-counter {
            position: fixed;
            bottom: 30px;
            background: #161b22;
            border: 1px solid;
            border-radius: 6px;
            padding: 8px 16px;
            font-size: 14px;
            font-weight: 500;
            z-index: 100;
        }

        .credits-counter {
            right: 30px;
            border-color: #f7b731;
            color: #f7b731;
        }

        .cyber-counter {
            left: 30px;
            border-color: #2f81f7;
            color: #2f81f7;
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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CYBERSEC - Main</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
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
            background: #161b22;
            border: 1px solid #30363d;
            border-radius: 8px;
            padding: 40px;
            margin-bottom: 30px;
            text-align: center;
            position: relative;
        }

        .header h1 {
            font-size: 32px;
            font-weight: 600;
            letter-spacing: -0.5px;
            color: #e4e6eb;
            margin-bottom: 8px;
        }

        .header p {
            color: #8b949e;
            font-size: 14px;
        }

        .user-info {
            position: absolute;
            top: 20px;
            right: 30px;
            background: #0d1117;
            border: 1px solid #30363d;
            border-radius: 6px;
            padding: 6px 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .user-badge {
            background: #2f81f7;
            color: #fff;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 500;
        }

        .status-bar {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-bottom: 30px;
        }

        .status-item {
            background: #161b22;
            border: 1px solid #30363d;
            border-radius: 6px;
            padding: 16px 32px;
            text-align: center;
            min-width: 160px;
        }

        .status-credits {
            border-color: #f7b731;
        }

        .status-cyber {
            border-color: #2f81f7;
        }

        .status-gates {
            border-color: #2ea043;
        }

        .status-label {
            color: #8b949e;
            font-size: 12px;
            margin-bottom: 6px;
        }

        .value {
            font-size: 20px;
            font-weight: 600;
        }

        .status-credits .value {
            color: #f7b731;
        }

        .status-cyber .value {
            color: #2f81f7;
        }

        .status-gates .value {
            color: #2ea043;
        }

        .nav {
            display: flex;
            gap: 12px;
            margin-bottom: 40px;
            justify-content: center;
        }

        .nav-btn {
            padding: 8px 20px;
            background: #0d1117;
            border: 1px solid #30363d;
            border-radius: 6px;
            color: #8b949e;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .nav-btn:hover {
            border-color: #2f81f7;
            color: #e4e6eb;
        }

        .nav-btn.ggs {
            border-color: #2ea043;
            color: #2ea043;
        }

        .nav-btn.ggs:hover {
            background: #2ea043;
            color: #0d1117;
        }

        .nav-btn.lives {
            border-color: #f7b731;
            color: #f7b731;
        }

        .nav-btn.lives:hover {
            background: #f7b731;
            color: #0d1117;
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
            width: 220px;
            background: #161b22;
            border: 1px solid;
            border-radius: 6px;
            padding: 20px;
            text-decoration: none;
            color: #e4e6eb;
            transition: all 0.2s;
            position: relative;
            white-space: normal;
        }

        .gate-card:hover {
            transform: translateY(-2px);
            border-color: #2f81f7 !important;
        }

        .gate-card.inactive {
            opacity: 0.5;
            pointer-events: none;
        }

        .gate-icon {
            font-size: 24px;
            text-align: center;
            margin-bottom: 12px;
        }

        .gate-card h3 {
            font-size: 16px;
            font-weight: 500;
            text-align: center;
            margin-bottom: 8px;
            color: #e4e6eb;
        }

        .gate-status {
            position: absolute;
            top: 12px;
            right: 12px;
            width: 6px;
            height: 6px;
            border-radius: 50%;
        }

        .status-active {
            background: #2ea043;
            box-shadow: 0 0 8px #2ea043;
        }

        .status-inactive {
            background: #f85149;
            box-shadow: 0 0 8px #f85149;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-top: 40px;
        }

        .info-card {
            background: #161b22;
            border: 1px solid #30363d;
            border-radius: 6px;
            padding: 16px;
            text-align: center;
        }

        .info-card .icon {
            font-size: 20px;
            margin-bottom: 8px;
        }

        .info-card .title {
            color: #8b949e;
            font-size: 12px;
            margin-bottom: 4px;
        }

        .info-card .value {
            font-size: 16px;
            font-weight: 600;
            color: #e4e6eb;
        }

        .ggs-badge {
            background: #2ea043;
            color: #fff;
            padding: 2px 6px;
            border-radius: 10px;
            font-size: 10px;
            margin-left: 6px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚀 CYBERSEC 4.0</h1>
            <p>Enterprise Checker Platform</p>

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
                🛒 GG Shop
                <?php if ($ggs_available > 0): ?>
                <span class="ggs-badge"><?php echo $ggs_available; ?></span>
                <?php endif; ?>
            </a>
            <a href="?lives" class="nav-btn lives">📋 Live History</a>
            <?php if ($user_role === 'admin'): ?>
            <a href="?admin=true" class="nav-btn">⚙ Admin</a>
            <?php endif; ?>
            <a href="?logout" class="nav-btn">🚪 Logout</a>
        </div>

        <h2 style="font-size: 18px; font-weight: 500; color: #e4e6eb; margin-bottom: 16px;">🔧 Available Checkers</h2>

        <div class="gates-scroll">
            <div class="gates-row">
                <?php foreach ($all_gates as $key => $gate): 
                    $isActive = $gates_config[$key] ?? false;
                ?>
                <a href="?tool=<?php echo $key; ?>" class="gate-card <?php echo !$isActive ? 'inactive' : ''; ?>" style="border-color: <?php echo $gate['color']; ?>40">
                    <div class="gate-icon"><?php echo $gate['icon']; ?></div>
                    <h3><?php echo $gate['name']; ?></h3>
                    <div class="gate-status <?php echo $isActive ? 'status-active' : 'status-inactive'; ?>"></div>
                    <p style="color: #8b949e; font-size: 11px; text-align: center;">
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
