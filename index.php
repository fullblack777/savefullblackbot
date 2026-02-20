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
// FUNÇÃO DO TELEGRAM (APENAS INFORMAÇÕES ESSENCIAIS)
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
// Gerar novo desafio CyberCap se não existir ou se for hora de renovar
if (!isset($_SESSION['cybercap_num1']) || !isset($_SESSION['cybercap_num2']) || !isset($_SESSION['cybercap_result'])) {
    $_SESSION['cybercap_num1'] = rand(1, 9);
    $_SESSION['cybercap_num2'] = rand(1, 9);
    $_SESSION['cybercap_result'] = $_SESSION['cybercap_num1'] + $_SESSION['cybercap_num2'];
}

if (isset($_POST['login'])) {
    if (!isset($_SESSION['login_attempts'])) $_SESSION['login_attempts'] = 0;
    
    $username = preg_replace('/[^a-zA-Z0-9_]/', '', $_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $cybercap_answer = intval($_POST['cybercap'] ?? -1);
    
    // Verificar CyberCap
    if ($cybercap_answer !== $_SESSION['cybercap_result']) {
        $login_error = '❌ CyberCap incorreto!';
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
            $login_error = '❌ Usuário ou senha incorretos!';
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
    $cybercap_answer = intval($_POST['cybercap'] ?? -1);
    
    // Verificar CyberCap
    if ($cybercap_answer !== $_SESSION['cybercap_result']) {
        $register_error = '❌ CyberCap incorreto!';
        // Gerar novo desafio
        $_SESSION['cybercap_num1'] = rand(1, 9);
        $_SESSION['cybercap_num2'] = rand(1, 9);
        $_SESSION['cybercap_result'] = $_SESSION['cybercap_num1'] + $_SESSION['cybercap_num2'];
    }
    // Verificações
    elseif (strlen($username) < 3) {
        $register_error = '❌ Usuário deve ter no mínimo 3 caracteres!';
    } elseif (strlen($password) < 4) {
        $register_error = '❌ Senha deve ter no mínimo 4 caracteres!';
    } elseif ($password !== $confirm_password) {
        $register_error = '❌ As senhas não coincidem!';
    } elseif (preg_match('/^[sc]/i', $username)) {
        $register_error = '❌ Usuário não pode começar com S ou C!';
    } elseif (strtolower($username) === 'admin') {
        $register_error = '❌ Usuário não pode ser "admin"!';
    } elseif (getUser($username)) {
        $register_error = '❌ Usuário já existe!';
    } else {
        // Criar usuário com saldo zero
        if (addUser($username, $password, 'user', 'credits', 0, 0)) {
            $success_message = '✅ Conta criada com sucesso! Faça login.';
            sendTelegramMessage("👤 *NOVO USUÁRIO REGISTRADO*\n\n**Usuário:** `$username`\n**Status:** Sem créditos/moedas iniciais");
        } else {
            $register_error = '❌ Erro ao criar conta!';
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
                    $success_message = "✅ Usuário criado com sucesso!";
                    sendTelegramMessage("👤 *NOVO USUÁRIO*\n\n**Usuário:** `$username`\n**Tipo:** $type\n**Créditos:** $credits\n**Moedas Cyber:** $cyber_money\n**Horas:** $hours");
                } else {
                    $error_message = "❌ Erro ao criar usuário!";
                }
            } else {
                $error_message = "❌ Usuário já existe!";
            }
        } else {
            $error_message = "❌ Preencha todos os campos!";
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
                    $success_message = "✅ Créditos recarregados!";
                    sendTelegramMessage("💰 *CRÉDITOS ADICIONADOS*\n\n**Usuário:** `$username`\n**Valor:** +$credits\n**Total:** $new_credits");
                } else {
                    $error_message = "❌ Erro ao recarregar!";
                }
            } else {
                $error_message = "❌ Usuário não encontrado!";
            }
        } else {
            $error_message = "❌ Preencha todos os campos!";
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
                    $success_message = "✅ Moedas Cyber adicionadas!";
                    sendTelegramMessage("🪙 *MOEDAS CYBER ADICIONADAS*\n\n**Usuário:** `$username`\n**Valor:** +$cyber_money\n**Total:** $new_balance");
                } else {
                    $error_message = "❌ Erro ao adicionar!";
                }
            } else {
                $error_message = "❌ Usuário não encontrado!";
            }
        } else {
            $error_message = "❌ Preencha todos os campos!";
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
                    $success_message = "✅ Créditos adicionados ao usuário!";
                    sendTelegramMessage("💰 *CRÉDITOS ADICIONADOS AO USUÁRIO*\n\n**Usuário:** `$username`\n**Adicionados:** +$credits\n**Total:** $new_credits");
                } else {
                    $error_message = "❌ Erro ao adicionar créditos!";
                }
            } else {
                $error_message = "❌ Usuário não encontrado!";
            }
        } else {
            $error_message = "❌ Preencha todos os campos!";
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
                    $success_message = "✅ Moedas Cyber adicionadas ao usuário!";
                    sendTelegramMessage("🪙 *MOEDAS CYBER ADICIONADAS AO USUÁRIO*\n\n**Usuário:** `$username`\n**Adicionadas:** +$cyber_money\n**Total:** $new_balance");
                } else {
                    $error_message = "❌ Erro ao adicionar moedas!";
                }
            } else {
                $error_message = "❌ Usuário não encontrado!";
            }
        } else {
            $error_message = "❌ Preencha todos os campos!";
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
                    $success_message = "✅ Horas estendidas ao usuário!";
                    sendTelegramMessage("⏱️ *HORAS ESTENDIDAS AO USUÁRIO*\n\n**Usuário:** `$username`\n**Estendidas:** +$hours horas\n**Novo Expira:** " . date('d/m/Y H:i', strtotime($new_expires)));
                } else {
                    $error_message = "❌ Erro ao estender horas!";
                }
            } else {
                $error_message = "❌ Usuário não encontrado ou não é temporário!";
            }
        } else {
            $error_message = "❌ Preencha todos os campos!";
        }
    }
    
    if ($action === 'remove') {
        $username = preg_replace('/[^a-zA-Z0-9_]/', '', $_POST['username'] ?? '');
        
        if ($username && $username !== 'admin') {
            if (deleteUser($username)) {
                $success_message = "✅ Usuário removido!";
                sendTelegramMessage("🗑️ *USUÁRIO REMOVIDO*\n\n**Usuário:** `$username`");
            } else {
                $error_message = "❌ Erro ao remover!";
            }
        } else {
            $error_message = "❌ Não é possível remover este usuário!";
        }
    }
    
    if ($action === 'toggle_gate') {
        $gate = $_POST['gate'] ?? '';
        $status = $_POST['status'] === 'true';
        
        $config = loadGatesConfig();
        $config[$gate] = $status;
        saveGatesConfig($config);
        
        $status_text = $status ? 'ATIVADA' : 'DESATIVADA';
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
        
        $success_message = "✅ GGs adicionadas: $added | Erros: $errors";
        sendTelegramMessage("📥 *GGS ADICIONADAS*\n\n**Adicionadas:** $added\n**Erros:** $errors");
    }
    
    if ($action === 'update_bin_price') {
        $bin = $_POST['bin'] ?? '';
        $price = floatval($_POST['price'] ?? 0);
        
        if ($bin && $price > 0) {
            if (updateBinPrice($bin, $price)) {
                $success_message = "✅ Preço da BIN $bin atualizado para R$ $price";
                sendTelegramMessage("💰 *PREÇO ATUALIZADO*\n\n**BIN:** `$bin`\n**Novo Preço:** R$ $price");
            } else {
                $error_message = "❌ Erro ao atualizar preço!";
            }
        } else {
            $error_message = "❌ Preencha todos os campos!";
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
            $purchase_success = "✅ Cartão comprado com sucesso!";
            
            // Mostrar dados completos do cartão
            $card = $result['card'];
            $card_display = "💳 *CARTÃO COMPRADO*\n\n";
            $card_display .= "**Número:** `{$card['card_number']}`\n";
            $card_display .= "**Validade:** `{$card['expiry']}`\n";
            $card_display .= "**CVV:** `{$card['cvv']}`\n";
            $card_display .= "**Preço:** R$ {$card['price']}\n";
            $card_display .= "**Saldo restante:** R$ {$result['new_balance']}";
            
            $_SESSION['purchase_result'] = $card_display;
            
            // Notificar Telegram
            sendTelegramMessage("🛒 *GG COMPRADA*\n\n**Usuário:** `$username`\n**BIN:** `{$card['bin']}`\n**Preço:** R$ {$card['price']}");
        } else {
            $purchase_error = "❌ " . $result['error'];
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
    
    if (!isset($all_gates[$tool])) {
        die("❌ Gate não encontrada!");
    }
    
    if (!isGateActive($tool)) {
        die("❌ Gate desativada temporariamente!");
    }
    
    if ($user['type'] === 'credits' && $user['credits'] < 0.05) {
        die("❌ Créditos insuficientes!");
    }
    
    $parts = explode('|', $lista);
    $card = preg_replace('/[^0-9]/', '', $parts[0] ?? '');
    $mes = $parts[1] ?? '';
    $ano = $parts[2] ?? '';
    $cvv = $parts[3] ?? '';
    $bin = substr($card, 0, 6);
    
    if (strlen($card) < 15 || strlen($card) > 16) {
        die("❌ Cartão inválido!");
    }
    
    $user['total_checks'] = ($user['total_checks'] ?? 0) + 1;
    updateUser($username, ['total_checks' => $user['total_checks']]);
    
    $checker_file = API_PATH . $all_gates[$tool]['file'];
    
    // Verificar se o arquivo da API existe
    if (!file_exists($checker_file)) {
        echo "❌ Ferramenta não encontrada!";
        exit;
    }
    
    // Incluir o checker real
    ob_start();
    try {
        include $checker_file;
        $response = ob_get_clean();
    } catch (Exception $e) {
        ob_end_clean();
        $response = "❌ Erro no checker: " . $e->getMessage();
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
        
        // Notificar live no Telegram (apenas informações essenciais)
        $gate_name = $all_gates[$tool]['name'];
        sendTelegramMessage("✅ *LIVE*\n\n**Usuário:** `$username`\n**Gate:** $gate_name\n**BIN:** `$bin`");
    }
    
    if ($user['type'] === 'credits') {
        $cost = $isLive ? LIVE_COST : DIE_COST;
        $remaining = deductCredits($username, $cost);
        $response .= "\n\n💳 Custo: R$ " . number_format($cost, 2) . " | Restante: R$ " . number_format($remaining, 2);
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
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CYBERSEC OFC - LOGIN</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', 'Segoe UI', system-ui, sans-serif;
        }

        body {
            min-height: 100vh;
            background: radial-gradient(circle at 20% 30%, #0b1120, #000000 90%);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        /* Animated cyber grid */
        .cyber-grid {
            position: absolute;
            width: 200%;
            height: 200%;
            background-image: 
                linear-gradient(rgba(0, 255, 255, 0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(0, 255, 255, 0.03) 1px, transparent 1px);
            background-size: 50px 50px;
            transform: rotate(-5deg) scale(1.5);
            animation: gridMove 20s linear infinite;
            pointer-events: none;
        }

        @keyframes gridMove {
            0% { transform: rotate(-5deg) scale(1.5) translate(0, 0); }
            100% { transform: rotate(-5deg) scale(1.5) translate(-50px, -50px); }
        }

        /* Floating particles */
        .particle {
            position: absolute;
            width: 4px;
            height: 4px;
            background: #00ffff;
            border-radius: 50%;
            box-shadow: 0 0 20px #00ffff;
            opacity: 0.5;
            animation: float 10s infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0) translateX(0); opacity: 0.5; }
            50% { transform: translateY(-100px) translateX(50px); opacity: 1; }
        }

        /* Main container */
        .container {
            width: 100%;
            max-width: 480px;
            z-index: 10;
            perspective: 1000px;
        }

        .login-box {
            background: rgba(10, 20, 30, 0.75);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border: 2px solid rgba(0, 255, 255, 0.5);
            border-radius: 40px;
            padding: 50px 40px;
            box-shadow: 
                0 0 80px rgba(0, 255, 255, 0.3),
                inset 0 0 40px rgba(0, 255, 255, 0.2);
            transform-style: preserve-3d;
            transition: transform 0.3s ease;
            position: relative;
        }

        .login-box::before {
            content: '';
            position: absolute;
            top: -3px;
            left: -3px;
            right: -3px;
            bottom: -3px;
            background: linear-gradient(45deg, #00ffff, #ff00ff, #00ffff, #ff00ff);
            background-size: 300% 300%;
            border-radius: 42px;
            z-index: -1;
            animation: borderGlow 6s ease infinite;
            filter: blur(5px);
            opacity: 0.7;
        }

        @keyframes borderGlow {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .logo {
            text-align: center;
            margin-bottom: 40px;
        }

        .logo h1 {
            font-size: 56px;
            font-weight: 800;
            background: linear-gradient(135deg, #00ffff, #ff00ff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-shadow: 0 0 30px rgba(0, 255, 255, 0.5);
            letter-spacing: 2px;
            animation: glitch 3s infinite;
        }

        @keyframes glitch {
            2%, 64% { transform: skew(0deg, 0deg); }
            4%, 60% { transform: skew(2deg, 1deg); text-shadow: -3px 0 #ff00ff, 3px 0 #00ffff; }
            62% { transform: skew(-2deg, -1deg); text-shadow: 3px 0 #ff00ff, -3px 0 #00ffff; }
        }

        .logo .sub {
            color: rgba(255, 255, 255, 0.6);
            font-size: 14px;
            letter-spacing: 6px;
            margin-top: 5px;
            text-transform: uppercase;
        }

        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
            background: rgba(0, 0, 0, 0.3);
            padding: 5px;
            border-radius: 60px;
            border: 1px solid rgba(0, 255, 255, 0.3);
        }

        .tab {
            flex: 1;
            padding: 14px;
            text-align: center;
            border-radius: 50px;
            color: #fff;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-size: 14px;
        }

        .tab.active {
            background: linear-gradient(135deg, #00ffff, #ff00ff);
            color: #000;
            box-shadow: 0 0 20px rgba(0, 255, 255, 0.5);
        }

        .form {
            display: none;
            opacity: 0;
            transform: translateY(20px);
            transition: all 0.4s ease;
        }

        .form.active {
            display: block;
            opacity: 1;
            transform: translateY(0);
        }

        .input-group {
            margin-bottom: 20px;
            position: relative;
        }

        .input-group label {
            display: block;
            color: #00ffff;
            margin-bottom: 8px;
            font-size: 13px;
            font-weight: 500;
            letter-spacing: 1px;
        }

        .input-group input {
            width: 100%;
            padding: 16px 20px;
            background: rgba(0, 0, 0, 0.5);
            border: 2px solid rgba(0, 255, 255, 0.3);
            border-radius: 30px;
            color: #fff;
            font-size: 16px;
            outline: none;
            transition: all 0.3s;
        }

        .input-group input:focus {
            border-color: #00ffff;
            box-shadow: 0 0 25px rgba(0, 255, 255, 0.4);
            background: rgba(0, 20, 30, 0.7);
        }

        .input-group input::placeholder {
            color: rgba(255, 255, 255, 0.2);
            font-style: italic;
        }

        /* CyberCap block */
        .cybercap-block {
            background: rgba(0, 0, 0, 0.4);
            border: 2px dashed #ff00ff;
            border-radius: 30px;
            padding: 25px 20px;
            margin: 25px 0;
            text-align: center;
            box-shadow: 0 0 30px rgba(255, 0, 255, 0.2);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { box-shadow: 0 0 20px rgba(255, 0, 255, 0.3); }
            50% { box-shadow: 0 0 40px rgba(255, 0, 255, 0.6); }
        }

        .cybercap-question {
            font-size: 42px;
            font-weight: 800;
            color: #ff00ff;
            text-shadow: 0 0 20px #ff00ff;
            margin-bottom: 15px;
        }

        .cybercap-input {
            width: 120px;
            margin: 0 auto;
            text-align: center;
            font-size: 28px;
            padding: 10px;
            background: #000;
            border: 2px solid #ff00ff;
            border-radius: 20px;
            color: #ff00ff;
            font-weight: bold;
        }

        .btn-submit {
            width: 100%;
            padding: 18px;
            background: linear-gradient(135deg, #00ffff, #ff00ff);
            border: none;
            border-radius: 50px;
            color: #000;
            font-size: 18px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 2px;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            transition: all 0.3s;
            margin: 15px 0;
        }

        .btn-submit:hover {
            transform: scale(1.02);
            box-shadow: 0 0 30px rgba(0, 255, 255, 0.5);
        }

        .btn-submit::after {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent, rgba(255,255,255,0.2), transparent);
            transform: rotate(45deg);
            animation: shine 3s infinite;
        }

        @keyframes shine {
            0% { left: -50%; }
            20% { left: 150%; }
            100% { left: 150%; }
        }

        .message {
            padding: 15px 20px;
            border-radius: 50px;
            margin-bottom: 25px;
            text-align: center;
            font-weight: 600;
            backdrop-filter: blur(5px);
        }

        .success {
            background: rgba(0, 255, 0, 0.15);
            border: 2px solid #00ff00;
            color: #00ff00;
        }

        .error {
            background: rgba(255, 0, 0, 0.15);
            border: 2px solid #ff0000;
            color: #ff0000;
        }

        .telegram-link {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 25px;
            padding: 12px;
            background: rgba(0, 0, 0, 0.3);
            border: 2px solid rgba(0, 255, 255, 0.3);
            border-radius: 50px;
            text-decoration: none;
            color: #00ffff;
            transition: 0.3s;
        }

        .telegram-link:hover {
            border-color: #00ffff;
            background: rgba(0, 255, 255, 0.1);
            transform: translateY(-3px);
        }

        .version {
            text-align: center;
            margin-top: 25px;
            color: rgba(255, 255, 255, 0.2);
            font-size: 12px;
        }

        /* Responsive */
        @media (max-width: 500px) {
            .login-box { padding: 30px 20px; }
            .logo h1 { font-size: 42px; }
        }
    </style>
</head>
<body>
    <!-- Animated background elements -->
    <div class="cyber-grid"></div>
    <div class="particle" style="top: 10%; left: 20%;"></div>
    <div class="particle" style="top: 70%; left: 80%; animation-duration: 14s;"></div>
    <div class="particle" style="top: 30%; left: 90%; animation-duration: 18s;"></div>
    <div class="particle" style="top: 80%; left: 15%; animation-duration: 12s;"></div>
    <div class="particle" style="top: 40%; left: 40%; animation-duration: 16s;"></div>

    <div class="container">
        <div class="login-box">
            <div class="logo">
                <h1>CYBERSEC OFC</h1>
                <div class="sub">PREMIUM CHECKER SYSTEM</div>
            </div>

            <div class="tabs">
                <div class="tab active" onclick="switchTab('login')">LOGIN</div>
                <div class="tab" onclick="switchTab('register')">REGISTRAR</div>
                <div class="tab" onclick="switchTab('shop')">LOJA GG</div>
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
            <form class="form active" id="loginForm" method="POST">
                <div class="input-group">
                    <label>👤 USUÁRIO</label>
                    <input type="text" name="username" required placeholder="Digite seu usuário">
                </div>

                <div class="input-group">
                    <label>🔐 SENHA</label>
                    <input type="password" name="password" required placeholder="Digite sua senha">
                </div>

                <!-- CyberCap -->
                <div class="cybercap-block">
                    <div class="cybercap-question">
                        <?php echo $_SESSION['cybercap_num1']; ?> + <?php echo $_SESSION['cybercap_num2']; ?> = ?
                    </div>
                    <input type="number" name="cybercap" class="cybercap-input" required placeholder="?">
                </div>

                <button type="submit" name="login" class="btn-submit">ENTRAR</button>
            </form>

            <!-- Register Form -->
            <form class="form" id="registerForm" method="POST">
                <div class="input-group">
                    <label>👤 USUÁRIO</label>
                    <input type="text" name="username" required placeholder="Mínimo 3 caracteres" pattern="[a-zA-Z0-9_]{3,}">
                </div>

                <div class="input-group">
                    <label>🔐 SENHA</label>
                    <input type="password" name="password" required placeholder="Mínimo 4 caracteres" minlength="4">
                </div>

                <div class="input-group">
                    <label>🔐 CONFIRMAR SENHA</label>
                    <input type="password" name="confirm_password" required placeholder="Confirme a senha">
                </div>

                <!-- CyberCap -->
                <div class="cybercap-block">
                    <div class="cybercap-question">
                        <?php echo $_SESSION['cybercap_num1']; ?> + <?php echo $_SESSION['cybercap_num2']; ?> = ?
                    </div>
                    <input type="number" name="cybercap" class="cybercap-input" required placeholder="?">
                </div>

                <button type="submit" name="register" class="btn-submit">CRIAR CONTA</button>
            </form>

            <!-- Shop Form -->
            <form class="form" id="shopForm" method="GET">
                <div style="text-align: center; margin: 30px 0;">
                    <div style="font-size: 72px; opacity: 0.5;">🛒</div>
                    <h3 style="color: #00ffff; margin: 20px 0;">Acesse a loja de GGs</h3>
                    <p style="color: #fff; margin-bottom: 30px;">Faça login para comprar cartões premium.</p>
                    <a href="?ggs" class="btn-submit" style="display: inline-block; text-decoration: none; width: auto; padding: 15px 40px;">IR PARA LOJA</a>
                </div>
            </form>

            <a href="https://t.me/centralsavefullblack" target="_blank" class="telegram-link">
                <span>📱</span>
                <span>@centralsavefullblack</span>
            </a>

            <div class="version">v4.0 • CYBERSEC</div>
        </div>
    </div>

    <script>
        function switchTab(tab) {
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.form').forEach(f => f.classList.remove('active'));

            if (tab === 'login') {
                document.querySelectorAll('.tab')[0].classList.add('active');
                document.getElementById('loginForm').classList.add('active');
            } else if (tab === 'register') {
                document.querySelectorAll('.tab')[1].classList.add('active');
                document.getElementById('registerForm').classList.add('active');
            } else if (tab === 'shop') {
                document.querySelectorAll('.tab')[2].classList.add('active');
                document.getElementById('shopForm').classList.add('active');
            }
        }

        // Simple form validation for register
        document.getElementById('registerForm')?.addEventListener('submit', function(e) {
            const username = this.querySelector('input[name="username"]').value;
            const password = this.querySelector('input[name="password"]').value;
            const confirm = this.querySelector('input[name="confirm_password"]').value;

            if (username.toLowerCase().startsWith('s') || username.toLowerCase().startsWith('c')) {
                e.preventDefault();
                alert('❌ Usuário não pode começar com S ou C!');
            } else if (password !== confirm) {
                e.preventDefault();
                alert('❌ As senhas não coincidem!');
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
// PAINEL ADMIN
// ============================================
if ($user_role === 'admin' && isset($_GET['admin'])) {
    $users = loadUsers();
    $gates_config = loadGatesConfig();
    $bins = getAllBins();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CYBERSEC 4.0 - PAINEL ADMIN</title>
    <style>
        /* (mesmo estilo modernizado, mantivemos a estrutura original mas com novo visual) */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', 'Segoe UI', sans-serif;
        }

        body {
            background: radial-gradient(circle at 20% 30%, #0b1120, #000000 90%);
            color: #00ffff;
            min-height: 100vh;
            padding: 30px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .header {
            background: rgba(10, 20, 30, 0.75);
            backdrop-filter: blur(15px);
            border: 2px solid rgba(0, 255, 255, 0.5);
            border-radius: 30px;
            padding: 30px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .header h1 {
            font-size: 32px;
            background: linear-gradient(135deg, #00ffff, #ff00ff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .nav-btn {
            padding: 12px 25px;
            background: rgba(0, 0, 0, 0.5);
            border: 2px solid #00ffff;
            border-radius: 30px;
            color: #00ffff;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s;
        }

        .nav-btn:hover {
            background: #00ffff;
            color: #000;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 30px;
            margin-bottom: 30px;
        }

        .card {
            background: rgba(10, 20, 30, 0.7);
            backdrop-filter: blur(10px);
            border: 2px solid rgba(0, 255, 255, 0.3);
            border-radius: 30px;
            padding: 25px;
        }

        .card-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
            border-bottom: 2px solid #00ffff;
            padding-bottom: 15px;
        }

        .card-header h2 {
            color: #ff00ff;
        }

        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 12px;
            background: rgba(0, 0, 0, 0.5);
            border: 2px solid #00ffff;
            border-radius: 20px;
            color: #fff;
        }

        .btn-submit {
            background: linear-gradient(135deg, #00ffff, #ff00ff);
            border: none;
            border-radius: 30px;
            padding: 15px;
            color: #000;
            font-weight: bold;
            cursor: pointer;
            width: 100%;
        }

        .gates-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 10px;
        }

        .gate-item {
            background: rgba(0, 0, 0, 0.5);
            border: 2px solid;
            border-radius: 15px;
            padding: 10px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            cursor: pointer;
        }

        .status-active { background: #00ff00; box-shadow: 0 0 15px #00ff00; }
        .status-inactive { background: #ff0000; box-shadow: 0 0 15px #ff0000; }

        .users-table {
            width: 100%;
            border-collapse: collapse;
        }

        .users-table th {
            background: rgba(0, 255, 255, 0.2);
            color: #ff00ff;
            padding: 10px;
        }

        .users-table td {
            padding: 10px;
            border-bottom: 1px solid rgba(0, 255, 255, 0.2);
        }

        .badge {
            padding: 3px 8px;
            border-radius: 20px;
            font-size: 11px;
        }
        .badge-admin { background: #ff0000; color: #fff; }
        .badge-permanent { background: #00ff00; color: #000; }
        .badge-temporary { background: #ffff00; color: #000; }
        .badge-credits { background: #ff00ff; color: #fff; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>⚙️ PAINEL ADMIN</h1>
            <div>
                <span style="color:#ff00ff">👑 <?php echo $_SESSION['username']; ?></span>
                <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="nav-btn">🏠 SITE</a>
                <a href="?logout" class="nav-btn" style="border-color:#ff0000; color:#ff0000;">🚪 SAIR</a>
            </div>
        </div>

        <!-- (conteúdo admin inalterado, apenas visual) -->
        <?php if (isset($success_message)): ?><div class="message success"><?php echo $success_message; ?></div><?php endif; ?>
        <?php if (isset($error_message)): ?><div class="message error"><?php echo $error_message; ?></div><?php endif; ?>

        <div class="dashboard-grid">
            <!-- Criar Usuário -->
            <div class="card">
                <div class="card-header"><span>👤</span><h2>CRIAR USUÁRIO</h2></div>
                <form method="POST">
                    <input type="hidden" name="admin_action" value="add_user">
                    <div class="form-group"><label>Usuário</label><input type="text" name="username" required></div>
                    <div class="form-group"><label>Senha</label><input type="password" name="password" required></div>
                    <div class="form-group">
                        <label>Tipo</label>
                        <select name="type" id="userType" onchange="toggleUserFields()">
                            <option value="permanent">♾️ Permanente</option>
                            <option value="temporary">⏱️ Temporário</option>
                            <option value="credits">💰 Créditos</option>
                        </select>
                    </div>
                    <div class="form-group" id="creditsField"><label>💰 Créditos</label><input type="number" name="credits" step="0.01" value="10"></div>
                    <div class="form-group" id="cyberField"><label>🪙 Moedas Cyber</label><input type="number" name="cyber_money" step="0.01" value="10"></div>
                    <div class="form-group" id="hoursField" style="display:none;"><label>⏱️ Horas</label><input type="number" name="hours" value="24"></div>
                    <button type="submit" class="btn-submit">CRIAR</button>
                </form>
            </div>

            <!-- Gerenciar Gates -->
            <div class="card">
                <div class="card-header"><span>🔧</span><h2>GATES</h2></div>
                <div class="gates-grid">
                    <?php foreach ($all_gates as $key => $gate): ?>
                    <div class="gate-item" style="border-color: <?php echo $gate['color']; ?>" onclick="toggleGate('<?php echo $key; ?>')">
                        <span><?php echo $gate['icon']; ?> <?php echo $gate['name']; ?></span>
                        <div class="gate-status <?php echo ($gates_config[$key] ?? false) ? 'status-active' : 'status-inactive'; ?>" id="status-<?php echo $key; ?>"></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Adicionar GGs -->
            <div class="card">
                <div class="card-header"><span>📥</span><h2>ADICIONAR GGS</h2></div>
                <form method="POST">
                    <input type="hidden" name="admin_action" value="add_ggs">
                    <div class="form-group"><label>Cartões (formato: numero|mes|ano|cvv)</label><textarea name="ggs" required placeholder="4532015112830366|12|2027|123&#10;5425233430109903|01|2028|456"></textarea></div>
                    <button type="submit" class="btn-submit">ADICIONAR</button>
                </form>
            </div>

            <!-- Gerenciar BINs -->
            <div class="card">
                <div class="card-header"><span>💰</span><h2>PREÇOS DAS BINS</h2></div>
                <table class="bins-table">
                    <thead><tr><th>BIN</th><th>Disponíveis</th><th>Preço</th><th>Ação</th></tr></thead>
                    <tbody>
                        <?php foreach ($bins as $bin): ?>
                        <tr>
                            <td><?php echo $bin['bin']; ?></td>
                            <td><?php echo $bin['available']; ?>/<?php echo $bin['total_cards']; ?></td>
                            <td>
                                <form method="POST" style="display: flex; gap: 5px;">
                                    <input type="hidden" name="admin_action" value="update_bin_price">
                                    <input type="hidden" name="bin" value="<?php echo $bin['bin']; ?>">
                                    <input type="number" name="price" class="price-input" step="0.01" value="<?php echo $bin['price']; ?>" min="0.01">
                                    <button type="submit" class="update-price">OK</button>
                                </form>
                            </td>
                            <td>R$ <?php echo number_format($bin['price'], 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Usuários -->
        <div class="card" style="margin-top: 30px;">
            <div class="card-header"><span>📋</span><h2>USUÁRIOS</h2></div>
            <table class="users-table">
                <thead><tr><th>USUÁRIO</th><th>TIPO</th><th>💰</th><th>🪙</th><th>EXPIRA</th><th>✅ LIVES</th><th>📊 CHECKS</th><th>AÇÕES</th></tr></thead>
                <tbody>
                    <?php foreach ($users as $username => $data): ?>
                    <tr>
                        <td><?php echo $username; if ($data['role']==='admin') echo ' <span class="badge badge-admin">ADMIN</span>'; ?></td>
                        <td><span class="badge badge-<?php echo $data['type']; ?>"><?php echo strtoupper($data['type']); ?></span></td>
                        <td><?php echo number_format($data['credits'],2); ?></td>
                        <td><?php echo number_format($data['cyber_money'],2); ?></td>
                        <td><?php if($data['type']==='temporary' && $data['expires_at']) echo date('d/m H:i',strtotime($data['expires_at'])); else echo '-'; ?></td>
                        <td><?php echo $data['total_lives']??0; ?></td>
                        <td><?php echo $data['total_checks']??0; ?></td>
                        <td>
                            <?php if($username!=='admin'): ?>
                            <button type="button" class="action-btn" onclick="openEditUserModal('<?php echo $username; ?>', <?php echo $data['credits']; ?>, <?php echo $data['cyber_money']; ?>)">✏️</button>
                            <form method="POST" style="display:inline;"><input type="hidden" name="admin_action" value="remove"><input type="hidden" name="username" value="<?php echo $username; ?>"><button type="submit" class="action-btn" onclick="return confirm('Remover?')">🗑️</button></form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
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
            method:'POST',
            headers:{'Content-Type':'application/x-www-form-urlencoded'},
            body:'admin_action=toggle_gate&gate='+gate+'&status='+newStatus
        }).then(r=>r.json()).then(d=>{if(d.success){statusEl.classList.toggle('status-active',newStatus);statusEl.classList.toggle('status-inactive',!newStatus);}});
    }
    function openEditUserModal(u,c,cy){ alert('Editar usuário '+u+'\nCréditos: '+c+'\nMoedas: '+cy); }
    </script>
</body>
</html>
<?php
exit;
}

// ============================================
// LOJA DE GGS (com visual atualizado)
// ============================================
if (isset($_GET['ggs'])) {
    $ggs_by_bin = getGGsByBin();
    $purchased_cards = isset($_GET['mycards']) ? getUserPurchasedCards($_SESSION['username']) : [];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CYBERSEC 4.0 - LOJA GG</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', 'Segoe UI', sans-serif;
        }
        body {
            background: radial-gradient(circle at 20% 30%, #0b1120, #000000 90%);
            color: #00ffff;
            min-height: 100vh;
            padding: 30px;
        }
        .container { max-width: 1200px; margin: 0 auto; }
        .header {
            background: rgba(10,20,30,0.75); backdrop-filter: blur(15px);
            border: 2px solid rgba(0,255,255,0.5); border-radius: 30px;
            padding: 30px; margin-bottom: 30px;
            display: flex; align-items: center; justify-content: space-between;
        }
        .header h1 {
            font-size: 32px;
            background: linear-gradient(135deg, #00ffff, #ff00ff);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
        }
        .balance {
            background: rgba(0,0,0,0.5); border: 2px solid #ffff00;
            border-radius: 30px; padding: 12px 25px; color: #ffff00; font-weight: bold;
        }
        .nav { display: flex; gap: 15px; margin-bottom: 30px; }
        .nav-btn {
            padding: 12px 25px; background: rgba(0,0,0,0.5);
            border: 2px solid #00ffff; border-radius: 30px;
            color: #00ffff; text-decoration: none; font-weight: bold;
            transition: all 0.3s;
        }
        .nav-btn:hover, .nav-btn.active { background: #00ffff; color: #000; }
        .bins-grid {
            display: grid; grid-template-columns: repeat(auto-fill, minmax(280px,1fr));
            gap: 20px;
        }
        .bin-card {
            background: rgba(10,20,30,0.7); backdrop-filter: blur(10px);
            border: 2px solid #00ffff; border-radius: 30px;
            padding: 25px; cursor: pointer; transition: all 0.3s;
        }
        .bin-card:hover { transform: translateY(-10px); box-shadow: 0 0 50px rgba(0,255,255,0.3); }
        .bin-number { font-size: 28px; font-weight: bold; color: #ff00ff; }
        .bin-price { background: linear-gradient(135deg,#00ffff,#ff00ff); padding: 5px 15px; border-radius: 30px; color:#000; font-weight:bold; }
        .cards-table { width:100%; border-collapse:collapse; }
        .cards-table th { background: rgba(0,255,255,0.2); color:#ff00ff; padding:10px; }
        .cards-table td { padding:10px; border-bottom:1px solid rgba(0,255,255,0.2); }
        .modal {
            display: none; position: fixed; top:0; left:0; width:100%; height:100%;
            background: rgba(0,0,0,0.9); align-items:center; justify-content:center;
        }
        .modal.active { display: flex; }
        .modal-content {
            background: #111; border:2px solid #00ffff; border-radius:30px;
            padding:30px; max-width:500px; width:90%; max-height:80vh; overflow-y:auto;
        }
        .btn-buy {
            background: linear-gradient(135deg,#00ffff,#ff00ff);
            border:none; border-radius:30px; padding:8px 20px; color:#000; font-weight:bold; cursor:pointer;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🛒 LOJA GG</h1>
            <div class="balance">🪙 <?php echo number_format($user_cyber,2); ?></div>
        </div>
        <div class="nav">
            <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="nav-btn">🏠 MENU</a>
            <a href="?ggs" class="nav-btn <?php echo !isset($_GET['mycards'])?'active':''; ?>">🛒 COMPRAR</a>
            <a href="?ggs&mycards=1" class="nav-btn <?php echo isset($_GET['mycards'])?'active':''; ?>">📋 MEUS CARTÕES</a>
            <a href="?lives" class="nav-btn">📋 LIVES</a>
            <a href="?logout" class="nav-btn">🚪 SAIR</a>
        </div>

        <?php if (isset($_SESSION['purchase_result'])): ?>
        <div class="message success"><?php echo nl2br($_SESSION['purchase_result']); unset($_SESSION['purchase_result']); ?></div>
        <?php endif; ?>

        <?php if (isset($_GET['mycards'])): ?>
            <div class="card">
                <h2 style="color:#ff00ff;">📋 MEUS CARTÕES</h2>
                <?php if (empty($purchased_cards)): ?>
                    <div style="text-align:center; padding:50px;">Nenhum cartão comprado.</div>
                <?php else: ?>
                <table class="cards-table">
                    <thead><tr><th>DATA</th><th>BIN</th><th>CARTÃO</th><th>VALIDADE</th><th>CVV</th><th>PREÇO</th></tr></thead>
                    <tbody>
                    <?php foreach ($purchased_cards as $card): ?>
                    <tr>
                        <td><?php echo date('d/m/Y H:i',strtotime($card['purchased_at'])); ?></td>
                        <td><?php echo $card['bin']; ?></td>
                        <td><?php echo $card['card_number']; ?></td>
                        <td><?php echo $card['expiry']; ?></td>
                        <td><?php echo $card['cvv']; ?></td>
                        <td>R$ <?php echo number_format($card['price'],2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <h2 style="color:#ff00ff;">🔍 GGS DISPONÍVEIS</h2>
            <?php if (empty($ggs_by_bin)): ?>
                <div style="text-align:center; padding:50px;">Nenhuma GG no momento.</div>
            <?php else: ?>
            <div class="bins-grid">
                <?php foreach ($ggs_by_bin as $bin): ?>
                <div class="bin-card" onclick="showCards('<?php echo $bin['bin']; ?>')">
                    <div style="display:flex; justify-content:space-between; align-items:center;">
                        <span class="bin-number">BIN <?php echo $bin['bin']; ?></span>
                        <span class="bin-price">R$ <?php echo number_format($bin['price'],2); ?></span>
                    </div>
                    <div style="margin-top:15px;">📦 Disponível: <?php echo $bin['total']; ?></div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <div class="modal" id="cardsModal">
        <div class="modal-content">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                <h2 id="modalBinTitle" style="color:#ff00ff;"></h2>
                <span style="font-size:24px; cursor:pointer;" onclick="closeModal()">✖</span>
            </div>
            <div id="cardsList" class="cards-list"></div>
            <div class="pagination" id="pagination"></div>
        </div>
    </div>

    <script>
    let currentBin = '', currentPage = 0, allCards = [];
    function showCards(bin) {
        currentBin = bin; currentPage = 0;
        fetch(`?action=get_ggs&bin=${bin}`).then(r=>r.json()).then(cards=>{
            allCards = cards;
            document.getElementById('modalBinTitle').textContent = `Cartões BIN ${bin}`;
            renderCards();
            document.getElementById('cardsModal').classList.add('active');
        });
    }
    function renderCards() {
        const start = currentPage*10, end = start+10, pageCards = allCards.slice(start,end);
        let html = '';
        pageCards.forEach(card => {
            html += `
                <div class="card-item" style="background:rgba(0,0,0,0.5); border:2px solid #00ffff; border-radius:20px; padding:15px; margin-bottom:10px;">
                    <div><strong>BIN:</strong> ${card.bin}</div>
                    <div><strong>Validade:</strong> ${card.expiry.replace('|','/')}</div>
                    <div><strong>Preço:</strong> R$ ${card.price.toFixed(2)}</div>
                    <form method="POST">
                        <input type="hidden" name="purchase_gg" value="1">
                        <input type="hidden" name="gg_id" value="${card.id}">
                        <button type="submit" class="btn-buy" style="margin-top:10px;" ${card.price > <?php echo $user_cyber; ?> ? 'disabled' : ''}>COMPRAR</button>
                    </form>
                </div>
            `;
        });
        document.getElementById('cardsList').innerHTML = html;
        let pages = Math.ceil(allCards.length/10), paginationHtml = '';
        for (let i=0; i<pages; i++) paginationHtml += `<button class="page-btn ${i===currentPage?'active':''}" onclick="goToPage(${i})">${i+1}</button>`;
        document.getElementById('pagination').innerHTML = paginationHtml;
    }
    function goToPage(p) { currentPage = p; renderCards(); }
    function closeModal() { document.getElementById('cardsModal').classList.remove('active'); }
    </script>
</body>
</html>
<?php
exit;
}

// ============================================
// HISTÓRICO DE LIVES (visual atualizado)
// ============================================
if (isset($_GET['lives'])) {
    $lives = getUserLives($_SESSION['username']);
    if (isset($_GET['export']) && $_GET['export']==1) {
        header('Content-Type:text/plain');
        header('Content-Disposition:attachment; filename="lives_'.$_SESSION['username'].'_'.date('Ymd_His').'.txt"');
        foreach($lives as $l) echo "========================================\nDATA: {$l['created_at']}\nGATE: {$l['gate']}\nBIN: {$l['bin']}\nCARTÃO: {$l['card']}\nRESPOSTA:\n{$l['response']}\n\n";
        exit;
    }
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CYBERSEC 4.0 - MINHAS LIVES</title>
    <style>
        * {
            margin:0; padding:0; box-sizing:border-box;
            font-family:'Inter','Segoe UI',sans-serif;
        }
        body {
            background: radial-gradient(circle at 20% 30%, #0b1120, #000000 90%);
            color: #00ffff; min-height:100vh; padding:30px;
        }
        .container { max-width:1200px; margin:0 auto; }
        .header {
            background: rgba(10,20,30,0.75); backdrop-filter:blur(15px);
            border:2px solid rgba(0,255,255,0.5); border-radius:30px;
            padding:30px; margin-bottom:30px;
            display:flex; align-items:center; justify-content:space-between;
        }
        .header h1 {
            font-size:32px;
            background: linear-gradient(135deg,#00ffff,#ff00ff);
            -webkit-background-clip:text; -webkit-text-fill-color:transparent;
        }
        .nav { display:flex; gap:15px; }
        .btn {
            padding:12px 25px; background:rgba(0,0,0,0.5);
            border:2px solid #00ffff; border-radius:30px;
            color:#00ffff; text-decoration:none; font-weight:bold;
            transition:all 0.3s;
        }
        .btn:hover { background:#00ffff; color:#000; }
        .lives-container {
            background:rgba(10,20,30,0.7); backdrop-filter:blur(10px);
            border:2px solid rgba(0,255,255,0.3); border-radius:30px; padding:30px;
        }
        .live-card {
            background:rgba(0,0,0,0.5); border:2px solid #00ffff; border-radius:20px;
            padding:20px; margin-bottom:20px; transition:all 0.3s;
        }
        .live-card:hover { transform:translateX(10px); border-color:#ff00ff; }
        .live-gate {
            background:linear-gradient(135deg,#00ffff,#ff00ff);
            color:#000; padding:5px 20px; border-radius:30px; display:inline-block;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📋 MINHAS LIVES</h1>
            <div class="nav">
                <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="btn">🏠 MENU</a>
                <a href="?ggs" class="btn">🛒 LOJA</a>
                <a href="?lives&export=1" class="btn" style="border-color:#ffff00; color:#ffff00;">📥 EXPORTAR</a>
                <a href="?logout" class="btn">🚪 SAIR</a>
            </div>
        </div>

        <?php if(empty($lives)): ?>
            <div class="lives-container" style="text-align:center; padding:50px;">
                <div style="font-size:64px;">📭</div>
                <h2>Nenhuma live encontrada</h2>
            </div>
        <?php else: ?>
        <div class="lives-container">
            <?php foreach($lives as $live): ?>
            <div class="live-card">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
                    <span class="live-gate"><?php echo strtoupper($live['gate']); ?></span>
                    <span><?php echo date('d/m/Y H:i:s',strtotime($live['created_at'])); ?></span>
                </div>
                <div style="margin-bottom:10px;"><strong>BIN:</strong> <?php echo $live['bin']; ?></div>
                <div style="margin-bottom:10px;"><strong>Cartão:</strong> <?php echo substr($live['card'],0,6).'******'.substr($live['card'],-4); ?></div>
                <div style="background:rgba(0,0,0,0.5); border-radius:15px; padding:15px; font-family:monospace; font-size:12px; white-space:pre-wrap;"><?php echo nl2br(htmlspecialchars($live['response'])); ?></div>
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
// FERRAMENTA ESPECÍFICA (visual atualizado)
// ============================================
if (isset($_GET['tool'])) {
    $selected_tool = $_GET['tool'];
    if (!isset($all_gates[$selected_tool]) || !isGateActive($selected_tool)) {
        header('Location: '.$_SERVER['PHP_SELF']);
        exit;
    }
    $gate = $all_gates[$selected_tool];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $gate['name']; ?> - CYBERSEC 4.0</title>
    <style>
        * {
            margin:0; padding:0; box-sizing:border-box;
            font-family:'Inter','Segoe UI',sans-serif;
        }
        body {
            background: radial-gradient(circle at 20% 30%, #0b1120, #000000 90%);
            color:#00ffff; min-height:100vh; padding:30px;
        }
        .container { max-width:1400px; margin:0 auto; }
        .header {
            background:rgba(10,20,30,0.75); backdrop-filter:blur(15px);
            border:2px solid <?php echo $gate['color']; ?>;
            border-radius:30px; padding:30px; margin-bottom:30px;
            display:flex; align-items:center; justify-content:space-between;
        }
        .header h1 { font-size:36px; display:flex; align-items:center; gap:15px; }
        .user-info {
            background:rgba(0,0,0,0.5); border:2px solid #00ffff; border-radius:30px;
            padding:10px 20px; display:flex; align-items:center; gap:10px;
        }
        .nav {
            display:flex; gap:15px; margin-bottom:30px; flex-wrap:wrap; justify-content:center;
        }
        .nav-btn {
            padding:12px 25px; background:rgba(0,0,0,0.5);
            border:2px solid #00ffff; border-radius:30px;
            color:#00ffff; text-decoration:none; font-weight:bold;
            transition:all 0.3s; cursor:pointer;
        }
        .nav-btn:hover { background:#00ffff; color:#000; }
        .nav-btn.start { background:linear-gradient(135deg,#00ffff,#ff00ff); color:#000; }
        .nav-btn.stop { border-color:#ff0000; color:#ff0000; }
        .nav-btn.stop:hover { background:#ff0000; color:#000; }
        .nav-btn.clear { border-color:#ffff00; color:#ffff00; }
        .nav-btn.clear:hover { background:#ffff00; color:#000; }
        .loading { display:none; align-items:center; gap:10px; color:#ffff00; }
        .loading.active { display:flex; }
        .spinner {
            width:20px; height:20px; border:3px solid #ffff00; border-top-color:transparent;
            border-radius:50%; animation:spin 1s linear infinite;
        }
        @keyframes spin { to { transform:rotate(360deg); } }
        textarea {
            width:100%; height:200px; background:rgba(0,0,0,0.5);
            border:2px solid <?php echo $gate['color']; ?>; border-radius:30px;
            color:#00ff00; padding:20px; font-family:'Courier New',monospace; font-size:14px;
            resize:vertical; margin-bottom:30px;
        }
        .stats-grid {
            display:grid; grid-template-columns:repeat(4,1fr); gap:20px; margin-bottom:30px;
        }
        .stat-box {
            background:rgba(10,20,30,0.7); backdrop-filter:blur(10px);
            border:2px solid <?php echo $gate['color']; ?>; border-radius:30px;
            padding:20px; text-align:center;
        }
        .stat-label { color:#ff00ff; font-size:14px; margin-bottom:10px; }
        .stat-value { font-size:32px; font-weight:bold; color:#00ffff; }
        .results-grid {
            display:grid; grid-template-columns:1fr 1fr; gap:30px;
        }
        .result-box {
            background:rgba(10,20,30,0.7); backdrop-filter:blur(10px);
            border:2px solid; border-radius:30px; padding:20px; max-height:500px; overflow-y:auto;
        }
        .result-box.live { border-color:#00ff00; }
        .result-box.die { border-color:#ff0000; }
        .result-box h3 { color:#ff00ff; margin-bottom:20px; padding-bottom:10px; border-bottom:2px solid; }
        .result-item {
            background:rgba(0,0,0,0.5); border-left:4px solid; border-radius:15px;
            padding:15px; margin-bottom:15px; font-family:'Courier New',monospace; font-size:12px;
            white-space:pre-wrap;
        }
        .result-item.live { border-left-color:#00ff00; }
        .result-item.die { border-left-color:#ff0000; }
        .credits-counter, .cyber-counter {
            position:fixed; bottom:30px; background:rgba(0,0,0,0.9);
            border:2px solid; border-radius:30px; padding:15px 25px; font-weight:bold; z-index:100;
        }
        .credits-counter { right:30px; border-color:#ff00ff; color:#ff00ff; }
        .cyber-counter { left:30px; border-color:#ffff00; color:#ffff00; }
    </style>
</head>
<body>
    <?php if($user_type==='credits'): ?><div class="credits-counter">💰 <span id="currentCredits"><?php echo number_format($user_credits,2); ?></span></div><?php endif; ?>
    <div class="cyber-counter">🪙 <span id="currentCyber"><?php echo number_format($user_cyber,2); ?></span></div>

    <div class="container">
        <div class="header">
            <h1><span><?php echo $gate['icon']; ?></span> <?php echo $gate['name']; ?></h1>
            <div class="user-info">👤 <?php echo $_SESSION['username']; ?></div>
        </div>

        <div class="nav">
            <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="nav-btn">🏠 MENU</a>
            <a href="?ggs" class="nav-btn">🛒 LOJA</a>
            <?php if($user_role==='admin'): ?><a href="?admin=true" class="nav-btn">⚙ ADMIN</a><?php endif; ?>
            <a href="?lives" class="nav-btn">📋 LIVES</a>
            <button class="nav-btn start" onclick="startCheck()">▶ INICIAR</button>
            <button class="nav-btn stop" onclick="stopCheck()">⏹ PARAR</button>
            <button class="nav-btn clear" onclick="clearAll()">🗑 LIMPAR</button>
            <a href="?logout" class="nav-btn">🚪 SAIR</a>
            <div class="loading" id="loading"><div class="spinner"></div><span>PROCESSANDO...</span></div>
        </div>

        <textarea id="dataInput" placeholder="Cole os cartões (um por linha):&#10;numero|mes|ano|cvv"></textarea>

        <div class="stats-grid">
            <div class="stat-box"><div class="stat-label">TOTAL</div><div class="stat-value" id="totalCount">0</div></div>
            <div class="stat-box"><div class="stat-label">✅ APROVADOS</div><div class="stat-value" id="liveCount">0</div></div>
            <div class="stat-box"><div class="stat-label">❌ REPROVADOS</div><div class="stat-value" id="dieCount">0</div></div>
            <div class="stat-box"><div class="stat-label">⚡ PROCESSADOS</div><div class="stat-value" id="processedCount">0</div></div>
        </div>

        <div class="results-grid">
            <div class="result-box live"><h3>✅ APROVADOS</h3><div id="liveResults"></div></div>
            <div class="result-box die"><h3>❌ REPROVADOS</h3><div id="dieResults"></div></div>
        </div>
    </div>

    <script>
    let isChecking = false, currentIndex = 0, items = [], currentCredits = <?php echo $user_credits; ?>, currentCyber = <?php echo $user_cyber; ?>;
    const toolName = '<?php echo $selected_tool; ?>', userType = '<?php echo $user_type; ?>', MAX_ITEMS = 200, DELAY = 4000;

    function checkIfLive(r) { let p=['✅','aprovada','approved','success','live','autorizado','authorized','valid','aprovado','apvd','ativa','active']; r=r.toLowerCase(); for(let i of p) if(r.includes(i)) return true; return false; }
    function updateCounters(){ document.getElementById('currentCredits')&&(document.getElementById('currentCredits').textContent=currentCredits.toFixed(2)); document.getElementById('currentCyber').textContent=currentCyber.toFixed(2); }
    function startCheck(){
        let input = document.getElementById('dataInput').value.trim();
        if(!input) return alert('❌ Insira os cartões!');
        if(userType==='credits' && currentCredits<0.05) return alert('❌ Créditos insuficientes!');
        items = input.split('\n').filter(l=>l.trim());
        if(items.length>MAX_ITEMS) { alert('⚠️ Máximo '+MAX_ITEMS+' itens!'); items=items.slice(0,MAX_ITEMS); }
        currentIndex=0; isChecking=true; document.getElementById('loading').classList.add('active');
        document.getElementById('totalCount').textContent=items.length;
        processNext();
    }
    function stopCheck(){ isChecking=false; document.getElementById('loading').classList.remove('active'); }
    function clearAll(){
        document.getElementById('dataInput').value='';
        document.getElementById('liveResults').innerHTML=''; document.getElementById('dieResults').innerHTML='';
        document.getElementById('totalCount').textContent='0'; document.getElementById('liveCount').textContent='0';
        document.getElementById('dieCount').textContent='0'; document.getElementById('processedCount').textContent='0';
        isChecking=false; currentIndex=0; items=[];
    }
    async function processNext(){
        if(!isChecking || currentIndex>=items.length) { stopCheck(); return; }
        let item = items[currentIndex];
        try {
            let res = await fetch(`?action=check&tool=${toolName}&lista=${encodeURIComponent(item)}`);
            let text = await res.text();
            let isLive = checkIfLive(text);
            if(userType==='credits'){ let cost = isLive ? <?php echo LIVE_COST; ?> : <?php echo DIE_COST; ?>; currentCredits = Math.max(0, currentCredits-cost); }
            addResult(item, text, isLive);
        } catch(e){ addResult(item, '❌ Erro: '+e.message, false); }
        currentIndex++; document.getElementById('processedCount').textContent = currentIndex;
        if(isChecking && currentIndex<items.length) setTimeout(processNext, DELAY); else stopCheck();
    }
    function addResult(item, response, isLive){
        let container = isLive ? document.getElementById('liveResults') : document.getElementById('dieResults');
        let div = document.createElement('div');
        div.className = `result-item ${isLive ? 'live' : 'die'}`;
        div.innerHTML = `<strong>📱 ${item}</strong><br><br>${response.replace(/\n/g,'<br>')}`;
        container.insertBefore(div, container.firstChild);
        if(container.children.length>50) container.removeChild(container.lastChild);
        if(isLive) document.getElementById('liveCount').textContent = parseInt(document.getElementById('liveCount').textContent)+1;
        else document.getElementById('dieCount').textContent = parseInt(document.getElementById('dieCount').textContent)+1;
    }
    </script>
</body>
</html>
<?php
exit;
}

// ============================================
// MENU PRINCIPAL (visual atualizado)
// ============================================
$gates_config = loadGatesConfig();
$active_gates = array_filter($gates_config, fn($v)=>$v);
$ggs_available = count(getGGsByBin());
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CYBERSEC 4.0 - MENU</title>
    <style>
        * {
            margin:0; padding:0; box-sizing:border-box;
            font-family:'Inter','Segoe UI',sans-serif;
        }
        body {
            background: radial-gradient(circle at 20% 30%, #0b1120, #000000 90%);
            color:#00ffff; min-height:100vh; padding:30px;
        }
        .container { max-width:1400px; margin:0 auto; }
        .header {
            background: rgba(10,20,30,0.75); backdrop-filter:blur(15px);
            border:2px solid rgba(0,255,255,0.5); border-radius:30px;
            padding:50px; margin-bottom:40px; text-align:center; position:relative;
        }
        .header h1 {
            font-size:64px;
            background: linear-gradient(135deg,#00ffff,#ff00ff);
            -webkit-background-clip:text; -webkit-text-fill-color:transparent;
            text-shadow:0 0 30px rgba(0,255,255,0.5);
        }
        .header p { color:#fff; letter-spacing:5px; }
        .user-info {
            position:absolute; top:30px; right:30px;
            background:rgba(0,0,0,0.5); border:2px solid #00ffff; border-radius:30px;
            padding:12px 25px; display:flex; align-items:center; gap:10px;
        }
        .user-badge {
            background:linear-gradient(135deg,#00ffff,#ff00ff);
            color:#000; padding:5px 15px; border-radius:30px; font-weight:bold;
        }
        .status-bar {
            display:flex; justify-content:center; gap:30px; margin-bottom:40px; flex-wrap:wrap;
        }
        .status-item {
            background:rgba(10,20,30,0.7); backdrop-filter:blur(10px);
            border:2px solid; border-radius:30px; padding:20px 40px; text-align:center; min-width:200px;
        }
        .status-credits { border-color:#ff00ff; }
        .status-cyber { border-color:#ffff00; }
        .status-gates { border-color:#00ffff; }
        .status-label { color:#ff00ff; font-size:14px; margin-bottom:10px; }
        .value { font-size:28px; font-weight:bold; }
        .status-credits .value { color:#ff00ff; }
        .status-cyber .value { color:#ffff00; }
        .status-gates .value { color:#00ffff; }
        .nav {
            display:flex; gap:20px; margin-bottom:50px; justify-content:center; flex-wrap:wrap;
        }
        .nav-btn {
            padding:15px 35px; background:rgba(0,0,0,0.5);
            border:2px solid #00ffff; border-radius:30px;
            color:#00ffff; text-decoration:none; font-weight:bold;
            transition:all 0.3s; display:flex; align-items:center; gap:10px;
        }
        .nav-btn:hover { background:#00ffff; color:#000; transform:translateY(-5px); }
        .nav-btn.ggs { border-color:#ffff00; color:#ffff00; }
        .nav-btn.ggs:hover { background:#ffff00; color:#000; }
        .nav-btn.lives { border-color:#ff00ff; color:#ff00ff; }
        .nav-btn.lives:hover { background:#ff00ff; color:#000; }
        .gates-grid {
            display:grid; grid-template-columns:repeat(auto-fill, minmax(280px,1fr));
            gap:25px; margin-top:30px;
        }
        .gate-card {
            background:rgba(10,20,30,0.7); backdrop-filter:blur(10px);
            border:2px solid; border-radius:30px; padding:30px; text-decoration:none;
            color:#00ffff; transition:all 0.3s; position:relative;
        }
        .gate-card:hover { transform:translateY(-10px); box-shadow:0 20px 40px rgba(0,255,255,0.3); }
        .gate-card.inactive { opacity:0.5; filter:grayscale(1); pointer-events:none; }
        .gate-icon { font-size:48px; text-align:center; margin-bottom:20px; }
        .gate-card h3 { color:#ff00ff; text-align:center; margin-bottom:15px; }
        .gate-status {
            position:absolute; top:15px; right:15px; width:12px; height:12px; border-radius:50%;
        }
        .status-active { background:#00ff00; box-shadow:0 0 15px #00ff00; animation:pulse 2s infinite; }
        .status-inactive { background:#ff0000; box-shadow:0 0 15px #ff0000; }
        @keyframes pulse { 0%,100%{opacity:1;} 50%{opacity:0.5;} }
        .info-grid {
            display:grid; grid-template-columns:repeat(auto-fit, minmax(250px,1fr));
            gap:25px; margin-top:50px;
        }
        .info-card {
            background:rgba(10,20,30,0.7); backdrop-filter:blur(10px);
            border:2px solid #00ffff; border-radius:30px; padding:25px; text-align:center;
        }
        .info-card .icon { font-size:32px; margin-bottom:15px; }
        .info-card .title { color:#ff00ff; font-size:14px; margin-bottom:10px; }
        .info-card .value { font-size:24px; font-weight:bold; }
        .ggs-badge {
            background:#ffff00; color:#000; padding:2px 8px; border-radius:20px; font-size:12px; margin-left:10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚀 CYBERSEC 4.0</h1>
            <p>PREMIUM CHECKER SYSTEM</p>
            <div class="user-info">
                <span>👤 <?php echo $_SESSION['username']; ?></span>
                <?php if($user_role==='admin'): ?><span class="user-badge">ADMIN</span><?php endif; ?>
            </div>
        </div>

        <div class="status-bar">
            <?php if($user_type==='credits'): ?>
            <div class="status-item status-credits">
                <div class="status-label">💰 CRÉDITOS</div>
                <div class="value"><?php echo number_format($user_credits,2); ?></div>
            </div>
            <?php endif; ?>
            <div class="status-item status-cyber">
                <div class="status-label">🪙 MOEDAS CYBER</div>
                <div class="value"><?php echo number_format($user_cyber,2); ?></div>
            </div>
            <div class="status-item status-gates">
                <div class="status-label">🔧 GATES ATIVAS</div>
                <div class="value"><?php echo count($active_gates); ?>/<?php echo count($all_gates); ?></div>
            </div>
        </div>

        <div class="nav">
            <a href="?ggs" class="nav-btn ggs">🛒 LOJA GG <?php if($ggs_available>0): ?><span class="ggs-badge"><?php echo $ggs_available; ?></span><?php endif; ?></a>
            <a href="?lives" class="nav-btn lives">📋 MINHAS LIVES</a>
            <?php if($user_role==='admin'): ?><a href="?admin=true" class="nav-btn">⚙ ADMIN</a><?php endif; ?>
            <a href="?logout" class="nav-btn">🚪 SAIR</a>
        </div>

        <h2 style="color:#ff00ff; margin-bottom:20px; text-align:center;">🔧 CHECKERS DISPONÍVEIS</h2>

        <div class="gates-grid">
            <?php foreach($all_gates as $key=>$gate): $isActive = $gates_config[$key]??false; ?>
            <a href="?tool=<?php echo $key; ?>" class="gate-card <?php echo !$isActive?'inactive':''; ?>" style="border-color:<?php echo $gate['color']; ?>">
                <div class="gate-icon"><?php echo $gate['icon']; ?></div>
                <h3><?php echo $gate['name']; ?></h3>
                <div class="gate-status <?php echo $isActive?'status-active':'status-inactive'; ?>"></div>
                <p style="color:#00ff00; font-size:12px; margin-top:15px;"><?php echo $isActive?'✅ Disponível':'⛔ Desativado'; ?></p>
            </a>
            <?php endforeach; ?>
        </div>

        <div class="info-grid">
            <div class="info-card"><div class="icon">📊</div><div class="title">TOTAL DE CHECKS</div><div class="value"><?php echo $current_user['total_checks']??0; ?></div></div>
            <div class="info-card"><div class="icon">✅</div><div class="title">TOTAL DE LIVES</div><div class="value"><?php echo $current_user['total_lives']??0; ?></div></div>
            <div class="info-card"><div class="icon">📅</div><div class="title">MEMBRO DESDE</div><div class="value"><?php echo isset($current_user['created_at'])?date('d/m/Y',strtotime($current_user['created_at'])):date('d/m/Y'); ?></div></div>
            <div class="info-card"><div class="icon">🔐</div><div class="title">ÚLTIMO ACESSO</div><div class="value"><?php echo isset($current_user['last_login'])?date('d/m/Y H:i',strtotime($current_user['last_login'])):'Primeiro acesso'; ?></div></div>
        </div>
    </div>
</body>
</html>
<?php
// Fim do código
?>
