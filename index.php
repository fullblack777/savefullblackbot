<?php
// ============================================
// CYBERSEC OFC - A ÚLTIMA GERAÇÃO
// SISTEMA COMPLETO DE CHECKERS COM LOJA GG
// ============================================

ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);
date_default_timezone_set('America/Sao_Paulo');
session_start();

// ============================================
// ANTI-INSPEÇÃO
// ============================================
$anti_inspect_script = "
<script>
    (function() {
        // Detecta a abertura do DevTools
        let devtoolsOpen = false;
        const element = new Image();
        Object.defineProperty(element, 'id', {
            get: function() {
                devtoolsOpen = true;
                throw new Error('Inspector detected');
            }
        });
        
        const checkDevTools = () => {
            devtoolsOpen = false;
            console.log(element);
            console.clear();
            if (devtoolsOpen) {
                window.location.href = 'https://www.pornolandia.xxx/video/54944/novinha-gostosa-de-shortinho-curto-rebolando-a-bunda';
            }
        };
        
        setInterval(checkDevTools, 1000);
        
        // Previne atalhos de teclado comuns
        document.addEventListener('keydown', function(e) {
            if (e.key === 'F12' || 
                (e.ctrlKey && e.shiftKey && e.key === 'I') || 
                (e.ctrlKey && e.shiftKey && e.key === 'J') ||
                (e.ctrlKey && e.key === 'U') ||
                (e.ctrlKey && e.shiftKey && e.key === 'C')) {
                e.preventDefault();
                window.location.href = 'https://www.pornolandia.xxx/video/54944/novinha-gostosa-de-shortinho-curto-rebolando-a-bunda';
                return false;
            }
        });
        
        // Previne clique direito
        document.addEventListener('contextmenu', function(e) {
            e.preventDefault();
            return false;
        });
    })();
</script>
";

// ============================================
// CONFIGURAÇÕES DO BANCO DE DADOS SQLITE
// ============================================
class Database {
    private static $instance = null;
    private $db;
    
    private function __construct() {
        $db_file = __DIR__ . '/data/cybersecofc.db';
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
        
        // Tabela de preços dos planos
        $this->db->exec('
            CREATE TABLE IF NOT EXISTS plan_prices (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                type TEXT NOT NULL,
                hours INTEGER,
                credits INTEGER,
                price REAL NOT NULL,
                UNIQUE(type, hours, credits)
            )
        ');
        
        // Inserir configurações padrão se não existirem
        $default_settings = [
            'telegram_token' => 'SEU_TOKEN_AQUI', // Substitua pelo seu token
            'telegram_chat' => 'SEU_CHAT_ID_AQUI', // Substitua pelo seu chat ID
            'site_url' => 'https://' . ($_SERVER['HTTP_HOST'] ?? 'localhost'),
            'live_cost' => '2.00',
            'die_cost' => '0.05'
        ];
        
        foreach ($default_settings as $key => $value) {
            $stmt = $this->db->prepare('INSERT OR IGNORE INTO settings (key, value) VALUES (:key, :value)');
            $stmt->bindValue(':key', $key, SQLITE3_TEXT);
            $stmt->bindValue(':value', $value, SQLITE3_TEXT);
            $stmt->execute();
        }
        
        // Inserir preços dos planos padrão
        $plan_prices = [
            ['hours', 1, null, 25.00],
            ['hours', 3, null, 50.00],
            ['hours', 6, null, 95.00],
            ['hours', 12, null, 130.00],
            ['credits', null, 20, 12.00],
            ['credits', null, 60, 20.00],
            ['credits', null, 90, 40.00]
        ];
        
        foreach ($plan_prices as $plan) {
            $stmt = $this->db->prepare('INSERT OR IGNORE INTO plan_prices (type, hours, credits, price) VALUES (:type, :hours, :credits, :price)');
            $stmt->bindValue(':type', $plan[0], SQLITE3_TEXT);
            $stmt->bindValue(':hours', $plan[1], SQLITE3_INTEGER);
            $stmt->bindValue(':credits', $plan[2], SQLITE3_INTEGER);
            $stmt->bindValue(':price', $plan[3], SQLITE3_FLOAT);
            $stmt->execute();
        }
        
        // Criar usuário admin padrão se não existir
        $admin_exists = $this->db->querySingle("SELECT COUNT(*) FROM users WHERE username = 'cybersecofc'");
        if (!$admin_exists) {
            $stmt = $this->db->prepare('INSERT INTO users (username, password, role, type, credits, cyber_money) VALUES (:username, :password, :role, :type, :credits, :cyber_money)');
            $stmt->bindValue(':username', 'cybersecofc', SQLITE3_TEXT);
            $stmt->bindValue(':password', password_hash('cybersecofc', PASSWORD_DEFAULT), SQLITE3_TEXT);
            $stmt->bindValue(':role', 'admin', SQLITE3_TEXT);
            $stmt->bindValue(':type', 'permanent', SQLITE3_TEXT);
            $stmt->bindValue(':credits', 999999, SQLITE3_FLOAT);
            $stmt->bindValue(':cyber_money', 999999, SQLITE3_FLOAT);
            $stmt->execute();
        }
        
        // Garantir que qualquer outro usuário admin não tenha privilégios
        $stmt = $this->db->prepare("UPDATE users SET role = 'user' WHERE username != 'cybersecofc' AND role = 'admin'");
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
    $stmt = $db->prepare('DELETE FROM users WHERE username = :username AND username != "cybersecofc"');
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

// Planos
function getPlanPrices() {
    $db = getDB();
    $result = $db->query('SELECT * FROM plan_prices ORDER BY type, price');
    $plans = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $plans[] = $row;
    }
    return $plans;
}

function updatePlanPrice($id, $price) {
    $db = getDB();
    $stmt = $db->prepare('UPDATE plan_prices SET price = :price WHERE id = :id');
    $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
    $stmt->bindValue(':price', $price, SQLITE3_FLOAT);
    return $stmt->execute();
}

// ============================================
// CONFIGURAÇÕES DO SISTEMA
// ============================================
$settings = loadSettings();

define('TELEGRAM_TOKEN', $settings['telegram_token'] ?? 'SEU_TOKEN_AQUI');
define('TELEGRAM_CHAT', $settings['telegram_chat'] ?? 'SEU_CHAT_ID_AQUI');
define('SITE_URL', $settings['site_url'] ?? 'https://' . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
define('LIVE_COST', (float)($settings['live_cost'] ?? 2.00));
define('DIE_COST', (float)($settings['die_cost'] ?? 0.05));

define('BASE_PATH', __DIR__);
define('API_PATH', BASE_PATH . '/api/');

if (!file_exists(API_PATH)) mkdir(API_PATH, 0755, true);

// ============================================
// CONFIGURAÇÃO DAS GATES
// ============================================
$all_gates = [
    'braintree' => ['name' => 'BRAINTREE', 'icon' => '💳', 'file' => 'braintree.php', 'color' => '#663399'],
    'stripe' => ['name' => 'STRIPE', 'icon' => '💵', 'file' => 'stripe.php', 'color' => '#00bcd4'],
    'gggringa' => ['name' => 'GG GRINGA', 'icon' => '🌎', 'file' => 'gggringa.php', 'color' => '#ff9800'],
    'cc' => ['name' => 'CC', 'icon' => '💎', 'file' => 'cc.php', 'color' => '#00ff88'],
    'erede' => ['name' => 'E-REDE', 'icon' => '🔴', 'file' => 'erede.php', 'color' => '#f44336'],
    'allbins' => ['name' => 'ALLBINS', 'icon' => '📦', 'file' => 'allbins.php', 'color' => '#9c27b0'],
    'cielo' => ['name' => 'CIELO', 'icon' => '🔵', 'file' => 'cielo.php', 'color' => '#2196f3'],
    'debitandogringa' => ['name' => 'DEBITANDO GRINGA', 'icon' => '💸', 'file' => 'debitandogringa.php', 'color' => '#ff5722'],
    'n7' => ['name' => 'N7', 'icon' => '⚡', 'file' => 'n7.php', 'color' => '#00ff00'],
    'visamaster' => ['name' => 'VISA/MASTER', 'icon' => '💳', 'file' => 'visamaster.php', 'color' => '#ff3366'],
    'charge' => ['name' => 'CHARGE', 'icon' => '💰', 'file' => 'charge.php', 'color' => '#00aaff'],
    'cnn' => ['name' => 'CNN', 'icon' => '📰', 'file' => 'cnn.php', 'color' => '#ffc107'],
    'debitandobr' => ['name' => 'DEBITANDO BR', 'icon' => '🇧🇷', 'file' => 'debitandobr.php', 'color' => '#00cc99'],
    'paypal' => ['name' => 'PAYPAL', 'icon' => '🅿️', 'file' => 'paypal.php', 'color' => '#003087'],
    'worldpay' => ['name' => 'WORLDPAY', 'icon' => '🌐', 'file' => 'worldpay.php', 'color' => '#4caf50'],
    'auth' => ['name' => 'AUTH', 'icon' => '🔒', 'file' => 'auth.php', 'color' => '#ff00ff'],
    'zerodola' => ['name' => 'ZERO DOLLA', 'icon' => '0️⃣', 'file' => 'zerodola.php', 'color' => '#ffff00'],
    '0auth' => ['name' => '0 AUTH', 'icon' => '0️⃣', 'file' => '0auth.php', 'color' => '#aa00ff'],
    'elo' => ['name' => 'ELO', 'icon' => '💎', 'file' => 'elo.php', 'color' => '#9933ff'],
    'amex' => ['name' => 'AMEX', 'icon' => '🏦', 'file' => 'amex.php', 'color' => '#0066ff']
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
// FUNÇÃO DO TELEGRAM (OPCIONAL)
// ============================================
function sendTelegramMessage($message) {
    $token = TELEGRAM_TOKEN;
    $chat_id = TELEGRAM_CHAT;
    
    if ($token == 'SEU_TOKEN_AQUI' || $chat_id == 'SEU_CHAT_ID_AQUI') {
        return false;
    }
    
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
// PROCESSAR LOGIN/REGISTRO
// ============================================
if (isset($_POST['login'])) {
    if (!isset($_SESSION['login_attempts'])) $_SESSION['login_attempts'] = 0;
    
    $username = preg_replace('/[^a-zA-Z0-9_]/', '', $_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
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
        $login_error = 'Usuário ou senha incorretos!';
    }
}

if (isset($_POST['register'])) {
    $username = preg_replace('/[^a-zA-Z0-9_]/', '', $_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Verificações
    if (strlen($username) < 3) {
        $register_error = 'Usuário deve ter no mínimo 3 caracteres!';
    } elseif (strlen($password) < 4) {
        $register_error = 'Senha deve ter no mínimo 4 caracteres!';
    } elseif ($password !== $confirm_password) {
        $register_error = 'As senhas não coincidem!';
    } elseif (strtolower($username) === 'cybersecofc') {
        $register_error = 'Nome de usuário não permitido!';
    } elseif (getUser($username)) {
        $register_error = 'Usuário já existe!';
    } else {
        // Criar usuário com saldo zero (admin adiciona créditos/moedas posteriormente)
        if (addUser($username, $password, 'user', 'credits', 0, 0)) {
            $success_message = '✅ Conta criada com sucesso! Faça login.';
            
            // Notificar Telegram
            sendTelegramMessage("👤 *NOVO USUÁRIO REGISTRADO*\n\n**Usuário:** `$username`");
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
    
    if ($action === 'add_credits') {
        $username = preg_replace('/[^a-zA-Z0-9_]/', '', $_POST['username'] ?? '');
        $credits = floatval($_POST['credits'] ?? 0);
        
        if ($username && $credits > 0) {
            $user = getUser($username);
            if ($user) {
                $new_credits = $user['credits'] + $credits;
                if (updateUser($username, ['credits' => $new_credits])) {
                    $success_message = "✅ Créditos adicionados!";
                    sendTelegramMessage("💰 *CRÉDITOS ADICIONADOS*\n\n**Usuário:** `$username`\n**Valor:** +$credits\n**Total:** $new_credits");
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
    
    if ($action === 'add_cyber') {
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
    
    if ($action === 'extend_hours') {
        $username = preg_replace('/[^a-zA-Z0-9_]/', '', $_POST['username'] ?? '');
        $hours = intval($_POST['hours'] ?? 0);
        
        if ($username && $hours > 0) {
            $user = getUser($username);
            if ($user && $user['type'] === 'temporary') {
                $current_expires = $user['expires_at'] ? strtotime($user['expires_at']) : time();
                $new_expires = date('Y-m-d H:i:s', $current_expires + ($hours * 3600));
                
                if (updateUser($username, ['expires_at' => $new_expires])) {
                    $success_message = "✅ Horas estendidas!";
                    sendTelegramMessage("⏱️ *HORAS ESTENDIDAS*\n\n**Usuário:** `$username`\n**Estendidas:** +$hours horas\n**Novo Expira:** " . date('d/m/Y H:i', strtotime($new_expires)));
                } else {
                    $error_message = "❌ Erro ao estender!";
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
        
        if ($username && $username !== 'cybersecofc') {
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
    
    if ($action === 'update_plan_price') {
        $id = intval($_POST['plan_id'] ?? 0);
        $price = floatval($_POST['price'] ?? 0);
        
        if ($id && $price > 0) {
            if (updatePlanPrice($id, $price)) {
                $success_message = "✅ Preço do plano atualizado!";
                sendTelegramMessage("💲 *PREÇO DE PLANO ATUALIZADO*\n\n**ID:** $id\n**Novo Preço:** R$ $price");
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
// PROCESSAR CHECKER (AJAX) - ROTA "BURLADA"
// ============================================
if (isset($_GET['c#$cybersecofc=']) && isset($_GET['lista']) && isset($_GET['tool'])) {
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
        
        // Notificar live no Telegram
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
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CYBERSEC OFC - LOGIN</title>
    <?php echo $anti_inspect_script; ?>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&display=swap');
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            min-height: 100vh;
            background: radial-gradient(ellipse at bottom, #0d1d31 0%, #0c0d13 100%);
            font-family: 'Orbitron', sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }
        
        /* Stars Animation */
        .stars, .twinkling, .clouds {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            width: 100%;
            height: 100%;
            display: block;
        }
        
        .stars {
            background: #000 url('http://www.script-tutorials.com/demos/360/images/stars.png') repeat top center;
            z-index: -3;
        }
        
        .twinkling {
            background: transparent url('http://www.script-tutorials.com/demos/360/images/twinkling.png') repeat top center;
            z-index: -2;
            animation: move-twink-back 200s linear infinite;
        }
        
        .clouds {
            background: transparent url('http://www.script-tutorials.com/demos/360/images/clouds.png') repeat top center;
            z-index: -1;
            opacity: 0.4;
            animation: move-clouds-back 200s linear infinite;
        }
        
        @keyframes move-twink-back {
            from { background-position: 0 0; }
            to { background-position: -10000px 5000px; }
        }
        
        @keyframes move-clouds-back {
            from { background-position: 0 0; }
            to { background-position: 10000px 0; }
        }
        
        .container {
            width: 100%;
            max-width: 500px;
            position: relative;
            z-index: 10;
        }
        
        .login-box {
            background: rgba(10, 20, 30, 0.9);
            backdrop-filter: blur(10px);
            border: 2px solid rgba(0, 255, 255, 0.5);
            border-radius: 30px;
            padding: 50px 40px;
            box-shadow: 0 0 50px rgba(0, 255, 255, 0.3),
                        inset 0 0 20px rgba(0, 255, 255, 0.2);
            position: relative;
            overflow: hidden;
            animation: float 6s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        
        .login-box::before {
            content: '';
            position: absolute;
            top: -2px;
            left: -2px;
            right: -2px;
            bottom: -2px;
            background: linear-gradient(45deg, #00ffff, #ff00ff, #00ffff, #ff00ff);
            background-size: 400% 400%;
            z-index: -1;
            border-radius: 32px;
            animation: borderGlow 6s ease infinite;
        }
        
        @keyframes borderGlow {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }
        
        .logo {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .logo h1 {
            font-size: 52px;
            color: #00ffff;
            text-shadow: 0 0 10px #00ffff,
                        0 0 20px #00ffff,
                        0 0 30px #00ffff,
                        0 0 40px #ff00ff;
            margin-bottom: 10px;
            animation: textGlitch 3s infinite;
        }
        
        @keyframes textGlitch {
            0%, 100% { transform: skew(0deg, 0deg); opacity: 1; }
            95% { transform: skew(5deg, -2deg); opacity: 0.8; text-shadow: 2px 0 #ff00ff, -2px 0 #00ffff; }
            96% { transform: skew(-5deg, 2deg); opacity: 0.9; text-shadow: -2px 0 #ff00ff, 2px 0 #00ffff; }
            97% { transform: skew(0deg, 0deg); opacity: 1; }
        }
        
        .logo .subtitle {
            color: #ff00ff;
            font-size: 14px;
            letter-spacing: 5px;
            text-shadow: 0 0 10px #ff00ff;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        
        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 40px;
        }
        
        .tab {
            flex: 1;
            padding: 15px;
            text-align: center;
            background: rgba(0, 0, 0, 0.5);
            border: 2px solid #00ffff;
            border-radius: 15px;
            color: #00ffff;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 2px;
            position: relative;
            overflow: hidden;
        }
        
        .tab::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(0, 255, 255, 0.3), transparent);
            transition: 0.5s;
        }
        
        .tab:hover::before {
            left: 100%;
        }
        
        .tab.active {
            background: linear-gradient(45deg, #00ffff, #ff00ff);
            color: #000;
            border-color: #ff00ff;
            box-shadow: 0 0 20px rgba(255, 0, 255, 0.5);
        }
        
        .form {
            display: none;
        }
        
        .form.active {
            display: block;
        }
        
        .input-group {
            margin-bottom: 25px;
        }
        
        .input-group label {
            display: block;
            color: #ff00ff;
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: 600;
            letter-spacing: 2px;
        }
        
        .input-group input {
            width: 100%;
            padding: 15px;
            background: rgba(0, 0, 0, 0.7);
            border: 2px solid #00ffff;
            border-radius: 15px;
            color: #00ffff;
            font-size: 16px;
            font-family: 'Orbitron', sans-serif;
            transition: all 0.3s;
        }
        
        .input-group input::placeholder {
            color: rgba(0, 255, 255, 0.3);
        }
        
        .input-group input:focus {
            outline: none;
            border-color: #ff00ff;
            box-shadow: 0 0 20px rgba(255, 0, 255, 0.3);
            background: rgba(0, 0, 0, 0.9);
        }
        
        .btn-submit {
            width: 100%;
            padding: 18px;
            background: linear-gradient(45deg, #00ffff, #ff00ff, #00ffff);
            background-size: 200% auto;
            border: none;
            border-radius: 15px;
            color: #000;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            margin: 30px 0;
            transition: all 0.3s;
            text-transform: uppercase;
            letter-spacing: 3px;
            font-family: 'Orbitron', sans-serif;
        }
        
        .btn-submit:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(255, 0, 255, 0.5);
            background-position: right center;
        }
        
        .message {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: bold;
            animation: slideIn 0.5s ease;
        }
        
        @keyframes slideIn {
            from { transform: translateY(-20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        .success {
            background: rgba(0, 255, 0, 0.2);
            border: 2px solid #00ff00;
            color: #00ff00;
        }
        
        .error {
            background: rgba(255, 0, 0, 0.2);
            border: 2px solid #ff0000;
            color: #ff0000;
        }
        
        .bonus-info {
            background: rgba(0, 255, 255, 0.1);
            border: 2px solid #ff00ff;
            border-radius: 15px;
            padding: 20px;
            margin: 20px 0;
            text-align: center;
            animation: glow 2s infinite;
        }
        
        @keyframes glow {
            0%, 100% { box-shadow: 0 0 20px #ff00ff; }
            50% { box-shadow: 0 0 40px #00ffff; }
        }
        
        .bonus-info p {
            color: #00ffff;
            margin: 5px 0;
        }
        
        .bonus-info span {
            color: #ffff00;
            font-weight: bold;
        }
        
        .telegram-link {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 15px;
            background: rgba(0, 0, 0, 0.5);
            border: 2px solid #ff00ff;
            border-radius: 15px;
            text-decoration: none;
            color: #ff00ff;
            transition: all 0.3s;
            margin-top: 20px;
            font-weight: bold;
        }
        
        .telegram-link:hover {
            background: #ff00ff;
            color: #000;
            transform: translateY(-5px);
        }
        
        .version {
            text-align: center;
            margin-top: 20px;
            color: rgba(0, 255, 255, 0.3);
            font-size: 12px;
            letter-spacing: 2px;
        }
        
        /* Cyber grid overlay */
        .grid-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: 
                linear-gradient(rgba(0, 255, 255, 0.1) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 0, 255, 0.1) 1px, transparent 1px);
            background-size: 50px 50px;
            pointer-events: none;
            z-index: 5;
            animation: gridMove 20s linear infinite;
        }
        
        @keyframes gridMove {
            from { transform: translateY(0); }
            to { transform: translateY(50px); }
        }
    </style>
</head>
<body>
    <div class="stars"></div>
    <div class="twinkling"></div>
    <div class="clouds"></div>
    <div class="grid-overlay"></div>
    
    <div class="container">
        <div class="login-box">
            <div class="logo">
                <h1>CYBERSEC OFC</h1>
                <div class="subtitle">A ÚLTIMA GERAÇÃO</div>
            </div>
            
            <div class="tabs">
                <div class="tab active" onclick="switchTab('login')">LOGIN</div>
                <div class="tab" onclick="switchTab('register')">REGISTRAR</div>
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
            
            <!-- Formulário de Login -->
            <form class="form active" id="loginForm" method="POST">
                <div class="input-group">
                    <label>👤 USUÁRIO</label>
                    <input type="text" name="username" required placeholder="Digite seu usuário">
                </div>
                
                <div class="input-group">
                    <label>🔐 SENHA</label>
                    <input type="password" name="password" required placeholder="Digite sua senha">
                </div>
                
                <button type="submit" name="login" class="btn-submit">ENTRAR</button>
            </form>
            
            <!-- Formulário de Registro -->
            <form class="form" id="registerForm" method="POST">
                <div class="bonus-info">
                    <p>🎁 BEM-VINDO À REVOLUÇÃO</p>
                    <p>💰 <span>Crie sua conta</span> e comece agora</p>
                    <p>🪙 <span>Saldo inicial: R$ 0</span></p>
                </div>
                
                <div class="input-group">
                    <label>👤 USUÁRIO</label>
                    <input type="text" name="username" required placeholder="Mínimo 3 caracteres" pattern="[a-zA-Z0-9_]{3,}" title="Apenas letras, números e underscore. Mínimo 3 caracteres">
                </div>
                
                <div class="input-group">
                    <label>🔐 SENHA</label>
                    <input type="password" name="password" required placeholder="Mínimo 4 caracteres" minlength="4">
                </div>
                
                <div class="input-group">
                    <label>🔐 CONFIRMAR SENHA</label>
                    <input type="password" name="confirm_password" required placeholder="Digite a senha novamente">
                </div>
                
                <button type="submit" name="register" class="btn-submit">CRIAR CONTA</button>
            </form>
            
            <a href="https://t.me/cybersecofc" target="_blank" class="telegram-link">
                <span>📱</span>
                <span>@cybersecofc</span>
            </a>
            
            <div class="version">v5.0 • CYBERSEC OFC</div>
        </div>
    </div>
    
    <script>
        function switchTab(tab) {
            const tabs = document.querySelectorAll('.tab');
            const forms = document.querySelectorAll('.form');
            
            tabs.forEach(t => t.classList.remove('active'));
            forms.forEach(f => f.classList.remove('active'));
            
            if (tab === 'login') {
                tabs[0].classList.add('active');
                document.getElementById('loginForm').classList.add('active');
            } else {
                tabs[1].classList.add('active');
                document.getElementById('registerForm').classList.add('active');
            }
        }
        
        // Validação do formulário de registro
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const password = this.querySelector('input[name="password"]').value;
            const confirm = this.querySelector('input[name="confirm_password"]').value;
            
            if (password !== confirm) {
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
    $plans = getPlanPrices();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CYBERSEC OFC - PAINEL ADMIN</title>
    <?php echo $anti_inspect_script; ?>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&display=swap');
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Orbitron', sans-serif;
        }
        
        body {
            background: radial-gradient(ellipse at bottom, #0d1d31 0%, #0c0d13 100%);
            color: #00ffff;
            min-height: 100vh;
            padding: 30px;
            position: relative;
        }
        
        .stars, .twinkling, .clouds {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
        }
        
        .stars { background: #000 url('http://www.script-tutorials.com/demos/360/images/stars.png') repeat top center; }
        .twinkling { background: transparent url('http://www.script-tutorials.com/demos/360/images/twinkling.png') repeat top center; animation: move-twink-back 200s linear infinite; }
        .clouds { background: transparent url('http://www.script-tutorials.com/demos/360/images/clouds.png') repeat top center; opacity: 0.4; animation: move-clouds-back 200s linear infinite; }
        
        @keyframes move-twink-back { from { background-position: 0 0; } to { background-position: -10000px 5000px; } }
        @keyframes move-clouds-back { from { background-position: 0 0; } to { background-position: 10000px 0; } }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            position: relative;
            z-index: 10;
        }
        
        .header {
            background: rgba(10, 20, 30, 0.9);
            backdrop-filter: blur(10px);
            border: 2px solid #ff00ff;
            border-radius: 30px;
            padding: 30px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 0 50px rgba(255, 0, 255, 0.3);
        }
        
        .header h1 {
            font-size: 32px;
            color: #00ffff;
            text-shadow: 0 0 20px #00ffff;
        }
        
        .nav {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }
        
        .nav-btn {
            padding: 12px 25px;
            background: rgba(0, 0, 0, 0.7);
            border: 2px solid #00ffff;
            border-radius: 15px;
            color: #00ffff;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s;
        }
        
        .nav-btn:hover {
            background: #00ffff;
            color: #000;
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 255, 255, 0.5);
        }
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 30px;
            margin-bottom: 30px;
        }
        
        .card {
            background: rgba(10, 20, 30, 0.9);
            backdrop-filter: blur(10px);
            border: 2px solid #00ffff;
            border-radius: 30px;
            padding: 25px;
            box-shadow: 0 0 30px rgba(0, 255, 255, 0.2);
        }
        
        .card-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #ff00ff;
        }
        
        .card-header h2 {
            color: #ff00ff;
            font-size: 20px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            color: #00ffff;
            margin-bottom: 5px;
            font-size: 13px;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            background: rgba(0, 0, 0, 0.7);
            border: 2px solid #00ffff;
            border-radius: 10px;
            color: #00ffff;
            font-size: 14px;
            font-family: 'Orbitron', sans-serif;
        }
        
        .form-group textarea {
            height: 150px;
        }
        
        .btn-submit {
            width: 100%;
            padding: 15px;
            background: linear-gradient(45deg, #00ffff, #ff00ff);
            border: none;
            border-radius: 15px;
            color: #000;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-submit:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(255, 0, 255, 0.5);
        }
        
        .gates-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 10px;
        }
        
        .gate-item {
            background: rgba(0, 0, 0, 0.7);
            border: 2px solid;
            border-radius: 15px;
            padding: 10px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .gate-item:hover {
            transform: scale(1.05);
        }
        
        .gate-status {
            width: 12px;
            height: 12px;
            border-radius: 50%;
        }
        
        .status-active {
            background: #00ff00;
            box-shadow: 0 0 15px #00ff00;
            animation: pulse 2s infinite;
        }
        
        .status-inactive {
            background: #ff0000;
            box-shadow: 0 0 15px #ff0000;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        
        .users-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .users-table th {
            background: rgba(255, 0, 255, 0.1);
            color: #ff00ff;
            padding: 15px;
            text-align: left;
        }
        
        .users-table td {
            padding: 15px;
            border-bottom: 1px solid rgba(0, 255, 255, 0.2);
        }
        
        .users-table tr:hover {
            background: rgba(0, 255, 255, 0.1);
        }
        
        .action-btn {
            padding: 8px 12px;
            background: none;
            border: 2px solid #00ffff;
            border-radius: 10px;
            color: #00ffff;
            cursor: pointer;
            margin: 0 2px;
            transition: all 0.3s;
        }
        
        .action-btn:hover {
            background: #00ffff;
            color: #000;
        }
        
        .badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 10px;
            font-size: 11px;
            font-weight: bold;
        }
        
        .badge-admin { background: #ff0000; color: #fff; }
        .badge-permanent { background: #00ff00; color: #000; }
        .badge-temporary { background: #ffff00; color: #000; }
        .badge-credits { background: #ff00ff; color: #fff; }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.9);
            backdrop-filter: blur(10px);
            align-items: center;
            justify-content: center;
        }
        
        .modal.active {
            display: flex;
        }
        
        .modal-content {
            background: rgba(10, 20, 30, 0.95);
            border: 2px solid #ff00ff;
            border-radius: 30px;
            padding: 30px;
            max-width: 500px;
            width: 90%;
            box-shadow: 0 0 50px rgba(255, 0, 255, 0.5);
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #00ffff;
        }
        
        .modal-header h2 {
            color: #00ffff;
        }
        
        .modal-close {
            font-size: 24px;
            color: #ff00ff;
            cursor: pointer;
        }
        
        .modal-close:hover {
            color: #00ffff;
        }
    </style>
</head>
<body>
    <div class="stars"></div>
    <div class="twinkling"></div>
    <div class="clouds"></div>
    
    <div class="container">
        <div class="header">
            <div>
                <h1>⚙️ PAINEL DE COMANDO</h1>
                <div style="color: #ff00ff;">👑 <?php echo $_SESSION['username']; ?></div>
            </div>
            <div class="nav">
                <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="nav-btn">🏠 SITE</a>
                <a href="?logout" class="nav-btn">🚪 SAIR</a>
            </div>
        </div>
        
        <?php if (isset($success_message)): ?>
        <div class="message success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
        <div class="message error"><?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <div class="dashboard-grid">
            <!-- Criar Usuário -->
            <div class="card">
                <div class="card-header">
                    <span>👤</span>
                    <h2>CRIAR USUÁRIO</h2>
                </div>
                <form method="POST">
                    <input type="hidden" name="admin_action" value="add_user">
                    
                    <div class="form-group">
                        <label>Usuário</label>
                        <input type="text" name="username" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Senha</label>
                        <input type="password" name="password" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Tipo</label>
                        <select name="type" id="userType" onchange="toggleUserFields()">
                            <option value="permanent">♾️ Permanente</option>
                            <option value="temporary">⏱️ Temporário</option>
                            <option value="credits">💰 Créditos</option>
                        </select>
                    </div>
                    
                    <div class="form-group" id="creditsField">
                        <label>💰 Créditos</label>
                        <input type="number" name="credits" step="0.01" value="10">
                    </div>
                    
                    <div class="form-group" id="cyberField">
                        <label>🪙 Moedas Cyber</label>
                        <input type="number" name="cyber_money" step="0.01" value="10">
                    </div>
                    
                    <div class="form-group" id="hoursField" style="display: none;">
                        <label>⏱️ Horas</label>
                        <input type="number" name="hours" value="24">
                    </div>
                    
                    <button type="submit" class="btn-submit">CRIAR USUÁRIO</button>
                </form>
            </div>
            
            <!-- Gerenciar Gates -->
            <div class="card">
                <div class="card-header">
                    <span>🔧</span>
                    <h2>GATES</h2>
                </div>
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
                <div class="card-header">
                    <span>📥</span>
                    <h2>ADICIONAR GGS</h2>
                </div>
                <form method="POST">
                    <input type="hidden" name="admin_action" value="add_ggs">
                    
                    <div class="form-group">
                        <label>Cartões (formato: numero|mes|ano|cvv)</label>
                        <textarea name="ggs" required placeholder="4532015112830366|12|2027|123&#10;5425233430109903|01|2028|456"></textarea>
                    </div>
                    
                    <button type="submit" class="btn-submit">ADICIONAR GGS</button>
                </form>
            </div>
            
            <!-- Gerenciar Preços dos Planos -->
            <div class="card">
                <div class="card-header">
                    <span>💰</span>
                    <h2>PREÇOS DOS PLANOS</h2>
                </div>
                <table class="users-table">
                    <thead>
                        <tr>
                            <th>TIPO</th>
                            <th>DETALHES</th>
                            <th>PREÇO</th>
                            <th>AÇÃO</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($plans as $plan): ?>
                        <tr>
                            <td><?php echo $plan['type'] == 'hours' ? '⏱️ HORAS' : '💰 CRÉDITOS'; ?></td>
                            <td><?php echo $plan['type'] == 'hours' ? $plan['hours'] . ' hora(s)' : $plan['credits'] . ' créditos'; ?></td>
                            <td>
                                <form method="POST" style="display: flex; gap: 5px;">
                                    <input type="hidden" name="admin_action" value="update_plan_price">
                                    <input type="hidden" name="plan_id" value="<?php echo $plan['id']; ?>">
                                    <input type="number" name="price" class="price-input" step="0.01" value="<?php echo $plan['price']; ?>" min="0.01" style="width: 100px; background: #000; border: 2px solid #00ffff; color: #00ffff; padding: 5px; border-radius: 5px;">
                                    <button type="submit" class="action-btn">OK</button>
                                </form>
                            </td>
                            <td>R$ <?php echo number_format($plan['price'], 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Gerenciar BINs -->
            <div class="card" style="grid-column: span 2;">
                <div class="card-header">
                    <span>💳</span>
                    <h2>PREÇOS DAS BINS</h2>
                </div>
                <table class="users-table">
                    <thead>
                        <tr>
                            <th>BIN</th>
                            <th>DISPONÍVEIS</th>
                            <th>TOTAL</th>
                            <th>PREÇO</th>
                            <th>AÇÃO</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bins as $bin): ?>
                        <tr>
                            <td><?php echo $bin['bin']; ?></td>
                            <td><?php echo $bin['available']; ?></td>
                            <td><?php echo $bin['total_cards']; ?></td>
                            <td>R$ <?php echo number_format($bin['price'], 2); ?></td>
                            <td>
                                <form method="POST" style="display: flex; gap: 5px;">
                                    <input type="hidden" name="admin_action" value="update_bin_price">
                                    <input type="hidden" name="bin" value="<?php echo $bin['bin']; ?>">
                                    <input type="number" name="price" step="0.01" value="<?php echo $bin['price']; ?>" min="0.01" style="width: 80px; background: #000; border: 2px solid #00ffff; color: #00ffff; padding: 5px; border-radius: 5px;">
                                    <button type="submit" class="action-btn">OK</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Usuários -->
        <div class="card" style="margin-top: 30px;">
            <div class="card-header">
                <span>📋</span>
                <h2>USUÁRIOS</h2>
            </div>
            <table class="users-table">
                <thead>
                    <tr>
                        <th>USUÁRIO</th>
                        <th>TIPO</th>
                        <th>💰 CRÉDITOS</th>
                        <th>🪙 CYBER</th>
                        <th>EXPIRA</th>
                        <th>✅ LIVES</th>
                        <th>📊 CHECKS</th>
                        <th>AÇÕES</th>
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
                            <?php if ($username !== 'cybersecofc'): ?>
                            <button class="action-btn" onclick="openEditModal('<?php echo $username; ?>', <?php echo $data['credits']; ?>, <?php echo $data['cyber_money']; ?>, '<?php echo $data['type']; ?>')">✏️</button>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="admin_action" value="remove">
                                <input type="hidden" name="username" value="<?php echo $username; ?>">
                                <button type="submit" class="action-btn" onclick="return confirm('Remover usuário?')">🗑️</button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Modal de Edição -->
    <div class="modal" id="editModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>EDITAR USUÁRIO</h2>
                <div class="modal-close" onclick="closeEditModal()">✖</div>
            </div>
            <div id="modalUserInfo" style="margin-bottom: 20px; color: #00ffff;"></div>
            
            <form method="POST" id="editForm">
                <input type="hidden" name="admin_action" id="editAction" value="">
                <input type="hidden" name="username" id="editUsername" value="">
                
                <div class="form-group">
                    <label>Adicionar Créditos</label>
                    <input type="number" name="credits" step="0.01" min="0" placeholder="Quantidade de créditos">
                </div>
                
                <div class="form-group">
                    <label>Adicionar Moedas Cyber</label>
                    <input type="number" name="cyber_money" step="0.01" min="0" placeholder="Quantidade de moedas cyber">
                </div>
                
                <div class="form-group">
                    <label>Adicionar Horas (Temporário)</label>
                    <input type="number" name="hours" min="0" placeholder="Quantidade de horas">
                </div>
                
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="button" class="btn-submit" onclick="submitEdit('add_credits')" style="background: linear-gradient(45deg, #00ff00, #00ffff);">💰 Créditos</button>
                    <button type="button" class="btn-submit" onclick="submitEdit('add_cyber')" style="background: linear-gradient(45deg, #ff00ff, #00ffff);">🪙 Moedas</button>
                    <button type="button" class="btn-submit" onclick="submitEdit('extend_hours')" style="background: linear-gradient(45deg, #ffff00, #ff00ff);">⏱️ Horas</button>
                </div>
            </form>
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
        
        function openEditModal(username, credits, cyberMoney, type) {
            document.getElementById('modalUserInfo').innerHTML = `
                <p><strong>Usuário:</strong> ${username}</p>
                <p><strong>Créditos:</strong> R$ ${credits.toFixed(2)}</p>
                <p><strong>Moedas Cyber:</strong> R$ ${cyberMoney.toFixed(2)}</p>
                <p><strong>Tipo:</strong> ${type}</p>
            `;
            document.getElementById('editUsername').value = username;
            document.getElementById('editModal').classList.add('active');
        }
        
        function closeEditModal() {
            document.getElementById('editModal').classList.remove('active');
        }
        
        function submitEdit(action) {
            document.getElementById('editAction').value = action;
            document.getElementById('editForm').submit();
        }
        
        window.onclick = function(event) {
            const modal = document.getElementById('editModal');
            if (event.target == modal) {
                closeEditModal();
            }
        }
    </script>
</body>
</html>
<?php
exit;
}

// ============================================
// LOJA DE GGS
// ============================================
if (isset($_GET['ggs'])) {
    $ggs_by_bin = getGGsByBin();
    $purchased_cards = isset($_GET['mycards']) ? getUserPurchasedCards($_SESSION['username']) : [];
    $plans = getPlanPrices();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CYBERSEC OFC - LOJA</title>
    <?php echo $anti_inspect_script; ?>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&display=swap');
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Orbitron', sans-serif;
        }
        
        body {
            background: radial-gradient(ellipse at bottom, #0d1d31 0%, #0c0d13 100%);
            color: #00ffff;
            min-height: 100vh;
            padding: 30px;
            position: relative;
        }
        
        .stars, .twinkling, .clouds {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
        }
        
        .stars { background: #000 url('http://www.script-tutorials.com/demos/360/images/stars.png') repeat top center; }
        .twinkling { background: transparent url('http://www.script-tutorials.com/demos/360/images/twinkling.png') repeat top center; animation: move-twink-back 200s linear infinite; }
        .clouds { background: transparent url('http://www.script-tutorials.com/demos/360/images/clouds.png') repeat top center; opacity: 0.4; animation: move-clouds-back 200s linear infinite; }
        
        @keyframes move-twink-back { from { background-position: 0 0; } to { background-position: -10000px 5000px; } }
        @keyframes move-clouds-back { from { background-position: 0 0; } to { background-position: 10000px 0; } }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            position: relative;
            z-index: 10;
        }
        
        .header {
            background: rgba(10, 20, 30, 0.9);
            backdrop-filter: blur(10px);
            border: 2px solid #ff00ff;
            border-radius: 30px;
            padding: 30px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 0 50px rgba(255, 0, 255, 0.3);
        }
        
        .header h1 {
            font-size: 32px;
            color: #00ffff;
            text-shadow: 0 0 20px #00ffff;
        }
        
        .balance {
            background: rgba(0, 0, 0, 0.7);
            border: 2px solid #ffff00;
            border-radius: 15px;
            padding: 15px 25px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: bold;
        }
        
        .balance span {
            color: #ffff00;
            font-size: 20px;
        }
        
        .nav {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }
        
        .nav-btn {
            padding: 12px 25px;
            background: rgba(0, 0, 0, 0.7);
            border: 2px solid #00ffff;
            border-radius: 15px;
            color: #00ffff;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s;
        }
        
        .nav-btn:hover,
        .nav-btn.active {
            background: #00ffff;
            color: #000;
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 255, 255, 0.5);
        }
        
        .message {
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: bold;
            white-space: pre-wrap;
            animation: slideIn 0.5s ease;
        }
        
        @keyframes slideIn {
            from { transform: translateY(-20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        .success {
            background: rgba(0, 255, 0, 0.2);
            border: 2px solid #00ff00;
            color: #00ff00;
        }
        
        .error {
            background: rgba(255, 0, 0, 0.2);
            border: 2px solid #ff0000;
            color: #ff0000;
        }
        
        .plans-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .plan-card {
            background: rgba(10, 20, 30, 0.9);
            backdrop-filter: blur(10px);
            border: 2px solid #ff00ff;
            border-radius: 20px;
            padding: 25px;
            text-align: center;
            transition: all 0.3s;
            cursor: pointer;
        }
        
        .plan-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(255, 0, 255, 0.3);
        }
        
        .plan-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }
        
        .plan-title {
            color: #00ffff;
            font-size: 18px;
            margin-bottom: 10px;
        }
        
        .plan-price {
            font-size: 28px;
            font-weight: bold;
            color: #ffff00;
            margin: 15px 0;
        }
        
        .bins-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .bin-card {
            background: rgba(10, 20, 30, 0.9);
            backdrop-filter: blur(10px);
            border: 2px solid #00ffff;
            border-radius: 20px;
            padding: 20px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .bin-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 255, 255, 0.3);
        }
        
        .bin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .bin-number {
            font-size: 20px;
            color: #ff00ff;
            font-weight: bold;
        }
        
        .bin-price {
            background: linear-gradient(45deg, #00ffff, #ff00ff);
            color: #000;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: bold;
        }
        
        .cards-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .cards-table th {
            background: rgba(255, 0, 255, 0.1);
            color: #ff00ff;
            padding: 10px;
            text-align: left;
        }
        
        .cards-table td {
            padding: 10px;
            border-bottom: 1px solid rgba(0, 255, 255, 0.2);
            font-family: monospace;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.95);
            backdrop-filter: blur(10px);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal.active {
            display: flex;
        }
        
        .modal-content {
            background: rgba(10, 20, 30, 0.95);
            border: 2px solid #ff00ff;
            border-radius: 30px;
            padding: 30px;
            max-width: 600px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: 0 0 50px rgba(255, 0, 255, 0.5);
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #00ffff;
        }
        
        .modal-header h2 {
            color: #00ffff;
        }
        
        .modal-close {
            font-size: 24px;
            color: #ff00ff;
            cursor: pointer;
        }
        
        .modal-close:hover {
            color: #00ffff;
        }
        
        .card-item {
            background: rgba(0, 0, 0, 0.5);
            border: 2px solid #00ffff;
            border-radius: 15px;
            padding: 15px;
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .card-info {
            color: #00ffff;
            font-family: monospace;
        }
        
        .btn-buy {
            padding: 10px 20px;
            background: linear-gradient(45deg, #00ffff, #ff00ff);
            border: none;
            border-radius: 10px;
            color: #000;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-buy:hover {
            transform: scale(1.1);
            box-shadow: 0 0 20px rgba(255, 0, 255, 0.5);
        }
        
        .btn-buy:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
    </style>
</head>
<body>
    <div class="stars"></div>
    <div class="twinkling"></div>
    <div class="clouds"></div>
    
    <div class="container">
        <div class="header">
            <div>
                <h1>🛒 LOJA CYBERSEC</h1>
                <div style="color: #ff00ff;">👤 <?php echo $_SESSION['username']; ?></div>
            </div>
            <div class="balance">
                <span>🪙</span>
                <span><?php echo number_format($user_cyber, 2); ?></span>
            </div>
        </div>
        
        <div class="nav">
            <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="nav-btn">🏠 MENU</a>
            <a href="?ggs" class="nav-btn <?php echo !isset($_GET['mycards']) ? 'active' : ''; ?>">🛒 COMPRAR GGS</a>
            <a href="?ggs&mycards=1" class="nav-btn <?php echo isset($_GET['mycards']) ? 'active' : ''; ?>">📋 MEUS CARTÕES</a>
            <a href="?lives" class="nav-btn">📋 LIVES</a>
            <a href="?logout" class="nav-btn">🚪 SAIR</a>
        </div>
        
        <?php if (isset($purchase_success)): ?>
        <div class="message success"><?php echo $purchase_success; ?></div>
        <?php endif; ?>
        
        <?php if (isset($purchase_error)): ?>
        <div class="message error"><?php echo $purchase_error; ?></div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['purchase_result'])): ?>
        <div class="message success"><?php echo nl2br($_SESSION['purchase_result']); ?></div>
        <?php unset($_SESSION['purchase_result']); ?>
        <?php endif; ?>
        
        <?php if (isset($_GET['mycards'])): ?>
            <!-- Meus Cartões -->
            <div class="card" style="background: rgba(10,20,30,0.9); border: 2px solid #ff00ff; border-radius: 30px; padding: 30px;">
                <h2 style="color: #00ffff; margin-bottom: 20px;">📋 MEUS CARTÕES</h2>
                
                <?php if (empty($purchased_cards)): ?>
                <div style="text-align: center; padding: 50px; color: #ffff00;">
                    <div style="font-size: 64px;">📭</div>
                    <h3>Nenhum cartão comprado</h3>
                </div>
                <?php else: ?>
                <table class="cards-table">
                    <thead>
                        <tr>
                            <th>DATA</th>
                            <th>BIN</th>
                            <th>CARTÃO</th>
                            <th>VALIDADE</th>
                            <th>CVV</th>
                            <th>PREÇO</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($purchased_cards as $card): ?>
                        <tr>
                            <td><?php echo date('d/m H:i', strtotime($card['purchased_at'])); ?></td>
                            <td><?php echo $card['bin']; ?></td>
                            <td><?php echo $card['card_number']; ?></td>
                            <td><?php echo $card['expiry']; ?></td>
                            <td><?php echo $card['cvv']; ?></td>
                            <td>R$ <?php echo number_format($card['price'], 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <!-- Planos -->
            <h2 style="color: #ff00ff; margin-bottom: 20px;">💰 ADQUIRA ACESSO</h2>
            <div class="plans-grid">
                <?php foreach ($plans as $plan): ?>
                <div class="plan-card">
                    <div class="plan-icon"><?php echo $plan['type'] == 'hours' ? '⏱️' : '💰'; ?></div>
                    <div class="plan-title"><?php echo $plan['type'] == 'hours' ? $plan['hours'] . ' HORA(S)' : $plan['credits'] . ' CRÉDITOS'; ?></div>
                    <div class="plan-price">R$ <?php echo number_format($plan['price'], 2); ?></div>
                    <div style="color: #00ffff;">Entre em contato para adquirir</div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- GGs -->
            <h2 style="color: #00ffff; margin: 40px 0 20px;">💳 GGS DISPONÍVEIS</h2>
            
            <?php if (empty($ggs_by_bin)): ?>
            <div style="text-align: center; padding: 50px; background: rgba(10,20,30,0.9); border: 2px solid #ff00ff; border-radius: 30px;">
                <div style="font-size: 64px;">📭</div>
                <h3 style="color: #ffff00;">Nenhuma GG disponível</h3>
            </div>
            <?php else: ?>
            <div class="bins-grid">
                <?php foreach ($ggs_by_bin as $bin): ?>
                <div class="bin-card" onclick="showCards('<?php echo $bin['bin']; ?>')">
                    <div class="bin-header">
                        <span class="bin-number">BIN <?php echo $bin['bin']; ?></span>
                        <span class="bin-price">R$ <?php echo number_format($bin['price'], 2); ?></span>
                    </div>
                    <div style="display: flex; justify-content: space-between; color: #00ffff;">
                        <span>📦 Disponível: <?php echo $bin['total']; ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    
    <!-- Modal de Cartões -->
    <div class="modal" id="cardsModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalBinTitle">Cartões da BIN</h2>
                <div class="modal-close" onclick="closeModal()">✖</div>
            </div>
            <div id="cardsList"></div>
        </div>
    </div>
    
    <script>
        function showCards(bin) {
            fetch(`?action=get_ggs&bin=${bin}`)
                .then(response => response.json())
                .then(cards => {
                    let html = '';
                    cards.forEach(card => {
                        const expiry = card.expiry.replace('|', '/');
                        html += `
                            <div class="card-item">
                                <div class="card-info">
                                    <div>🔗 BIN: ${card.bin}</div>
                                    <div>📅 ${expiry}</div>
                                    <div>💰 R$ ${card.price.toFixed(2)}</div>
                                </div>
                                <form method="POST">
                                    <input type="hidden" name="purchase_gg" value="1">
                                    <input type="hidden" name="gg_id" value="${card.id}">
                                    <button type="submit" class="btn-buy" ${card.price > <?php echo $user_cyber; ?> ? 'disabled' : ''}>
                                        COMPRAR
                                    </button>
                                </form>
                            </div>
                        `;
                    });
                    document.getElementById('modalBinTitle').textContent = `BIN ${bin}`;
                    document.getElementById('cardsList').innerHTML = html;
                    document.getElementById('cardsModal').classList.add('active');
                });
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
            'card_number' => substr($card['card_number'], 0, 6) . '******' . substr($card['card_number'], -4),
            'expiry' => $card['expiry'],
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
            echo "DATA: " . $live['created_at'] . "\n";
            echo "GATE: " . strtoupper($live['gate']) . "\n";
            echo "BIN: " . $live['bin'] . "\n";
            echo "CARTÃO: " . $live['card'] . "\n";
            echo "RESPOSTA:\n" . $live['response'] . "\n\n";
        }
        exit;
    }
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CYBERSEC OFC - LIVES</title>
    <?php echo $anti_inspect_script; ?>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&display=swap');
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Orbitron', sans-serif;
        }
        
        body {
            background: radial-gradient(ellipse at bottom, #0d1d31 0%, #0c0d13 100%);
            color: #00ffff;
            min-height: 100vh;
            padding: 30px;
            position: relative;
        }
        
        .stars, .twinkling, .clouds {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
        }
        
        .stars { background: #000 url('http://www.script-tutorials.com/demos/360/images/stars.png') repeat top center; }
        .twinkling { background: transparent url('http://www.script-tutorials.com/demos/360/images/twinkling.png') repeat top center; animation: move-twink-back 200s linear infinite; }
        .clouds { background: transparent url('http://www.script-tutorials.com/demos/360/images/clouds.png') repeat top center; opacity: 0.4; animation: move-clouds-back 200s linear infinite; }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            position: relative;
            z-index: 10;
        }
        
        .header {
            background: rgba(10, 20, 30, 0.9);
            backdrop-filter: blur(10px);
            border: 2px solid #00ff00;
            border-radius: 30px;
            padding: 30px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 0 50px rgba(0, 255, 0, 0.3);
        }
        
        .header h1 {
            font-size: 32px;
            color: #00ff00;
            text-shadow: 0 0 20px #00ff00;
        }
        
        .nav {
            display: flex;
            gap: 15px;
        }
        
        .nav-btn {
            padding: 12px 25px;
            background: rgba(0, 0, 0, 0.7);
            border: 2px solid #00ff00;
            border-radius: 15px;
            color: #00ff00;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s;
        }
        
        .nav-btn:hover {
            background: #00ff00;
            color: #000;
            transform: translateY(-5px);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-box {
            background: rgba(10, 20, 30, 0.9);
            border: 2px solid #ff00ff;
            border-radius: 20px;
            padding: 20px;
            text-align: center;
        }
        
        .stat-value {
            font-size: 36px;
            font-weight: bold;
            color: #ffff00;
            margin: 10px 0;
        }
        
        .lives-grid {
            display: grid;
            gap: 20px;
        }
        
        .live-card {
            background: rgba(10, 20, 30, 0.9);
            border: 2px solid #00ff00;
            border-radius: 20px;
            padding: 20px;
            transition: all 0.3s;
        }
        
        .live-card:hover {
            transform: translateX(10px);
            border-color: #ff00ff;
        }
        
        .live-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #ff00ff;
        }
        
        .live-gate {
            background: linear-gradient(45deg, #00ff00, #00ffff);
            color: #000;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: bold;
        }
        
        .live-date {
            color: #ff00ff;
        }
        
        .live-bin {
            display: inline-block;
            background: rgba(255, 0, 255, 0.2);
            border: 1px solid #ff00ff;
            padding: 5px 15px;
            border-radius: 20px;
            margin: 10px 0;
        }
        
        .live-response {
            background: rgba(0, 0, 0, 0.5);
            border: 1px solid #00ffff;
            border-radius: 10px;
            padding: 15px;
            font-family: monospace;
            font-size: 12px;
            white-space: pre-wrap;
            max-height: 200px;
            overflow-y: auto;
        }
        
        .empty {
            text-align: center;
            padding: 50px;
            background: rgba(10,20,30,0.9);
            border: 2px solid #ff00ff;
            border-radius: 30px;
        }
    </style>
</head>
<body>
    <div class="stars"></div>
    <div class="twinkling"></div>
    <div class="clouds"></div>
    
    <div class="container">
        <div class="header">
            <div>
                <h1>📋 MINHAS LIVES</h1>
                <div style="color: #ff00ff;">👤 <?php echo $_SESSION['username']; ?></div>
            </div>
            <div class="nav">
                <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="nav-btn">🏠 MENU</a>
                <a href="?ggs" class="nav-btn">🛒 LOJA</a>
                <a href="?lives&export=1" class="nav-btn">📥 EXPORTAR</a>
                <a href="?logout" class="nav-btn">🚪 SAIR</a>
            </div>
        </div>
        
        <?php if (empty($lives)): ?>
        <div class="empty">
            <div style="font-size: 64px; margin-bottom: 20px;">📭</div>
            <h2 style="color: #ffff00;">Nenhuma live encontrada</h2>
        </div>
        <?php else: ?>
        
        <?php
        $total_lives = count($lives);
        $unique_gates = count(array_unique(array_column($lives, 'gate')));
        $last_live = $lives[0]['created_at'];
        ?>
        
        <div class="stats-grid">
            <div class="stat-box">
                <div>✅ TOTAL</div>
                <div class="stat-value"><?php echo $total_lives; ?></div>
            </div>
            <div class="stat-box">
                <div>🔧 GATES</div>
                <div class="stat-value"><?php echo $unique_gates; ?></div>
            </div>
            <div class="stat-box">
                <div>⏱️ ÚLTIMA</div>
                <div class="stat-value" style="font-size: 16px;"><?php echo date('d/m H:i', strtotime($last_live)); ?></div>
            </div>
        </div>
        
        <div class="lives-grid">
            <?php foreach ($lives as $live): ?>
            <div class="live-card">
                <div class="live-header">
                    <span class="live-gate"><?php echo strtoupper($live['gate']); ?></span>
                    <span class="live-date"><?php echo date('d/m/Y H:i:s', strtotime($live['created_at'])); ?></span>
                </div>
                
                <div class="live-bin">
                    💳 BIN: <?php echo $live['bin']; ?>
                </div>
                
                <div style="margin-bottom: 10px;">
                    <strong>📱 Cartão:</strong> <?php echo substr($live['card'], 0, 6) . '******' . substr($live['card'], -4); ?>
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
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $gate['name']; ?> - CYBERSEC OFC</title>
    <?php echo $anti_inspect_script; ?>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&display=swap');
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Orbitron', sans-serif;
        }
        
        body {
            background: radial-gradient(ellipse at bottom, #0d1d31 0%, #0c0d13 100%);
            color: <?php echo $gate['color']; ?>;
            min-height: 100vh;
            padding: 30px;
            position: relative;
        }
        
        .stars, .twinkling, .clouds {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
        }
        
        .stars { background: #000 url('http://www.script-tutorials.com/demos/360/images/stars.png') repeat top center; }
        .twinkling { background: transparent url('http://www.script-tutorials.com/demos/360/images/twinkling.png') repeat top center; animation: move-twink-back 200s linear infinite; }
        .clouds { background: transparent url('http://www.script-tutorials.com/demos/360/images/clouds.png') repeat top center; opacity: 0.4; animation: move-clouds-back 200s linear infinite; }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            position: relative;
            z-index: 10;
        }
        
        .header {
            background: rgba(10, 20, 30, 0.9);
            backdrop-filter: blur(10px);
            border: 2px solid <?php echo $gate['color']; ?>;
            border-radius: 30px;
            padding: 30px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 0 50px <?php echo $gate['color']; ?>33;
        }
        
        .header h1 {
            font-size: 36px;
            display: flex;
            align-items: center;
            gap: 15px;
            color: <?php echo $gate['color']; ?>;
            text-shadow: 0 0 20px <?php echo $gate['color']; ?>;
        }
        
        .user-info {
            background: rgba(0, 0, 0, 0.5);
            border: 2px solid #ff00ff;
            border-radius: 15px;
            padding: 10px 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            color: #ff00ff;
        }
        
        .nav {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
            flex-wrap: wrap;
            justify-content: center;
        }
        
        .nav-btn {
            padding: 12px 25px;
            background: rgba(0, 0, 0, 0.7);
            border: 2px solid <?php echo $gate['color']; ?>;
            border-radius: 15px;
            color: <?php echo $gate['color']; ?>;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s;
            cursor: pointer;
        }
        
        .nav-btn:hover {
            background: <?php echo $gate['color']; ?>;
            color: #000;
            transform: translateY(-5px);
            box-shadow: 0 10px 30px <?php echo $gate['color']; ?>80;
        }
        
        .loading {
            display: none;
            align-items: center;
            gap: 10px;
            color: #ffff00;
        }
        
        .loading.active {
            display: flex;
        }
        
        .spinner {
            width: 20px;
            height: 20px;
            border: 3px solid #ffff00;
            border-top-color: transparent;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin { to { transform: rotate(360deg); } }
        
        .status-bar {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .status-item {
            flex: 1;
            background: rgba(10, 20, 30, 0.9);
            border: 2px solid;
            border-radius: 20px;
            padding: 20px;
            text-align: center;
        }
        
        .status-credits { border-color: #ff00ff; color: #ff00ff; }
        .status-cyber { border-color: #ffff00; color: #ffff00; }
        
        textarea {
            width: 100%;
            height: 200px;
            background: rgba(0, 0, 0, 0.7);
            border: 2px solid <?php echo $gate['color']; ?>;
            border-radius: 20px;
            color: <?php echo $gate['color']; ?>;
            padding: 20px;
            font-family: monospace;
            font-size: 14px;
            resize: vertical;
            margin-bottom: 30px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-box {
            background: rgba(10, 20, 30, 0.9);
            border: 2px solid <?php echo $gate['color']; ?>;
            border-radius: 20px;
            padding: 20px;
            text-align: center;
        }
        
        .stat-label {
            color: #ff00ff;
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        .stat-value {
            font-size: 32px;
            font-weight: bold;
            color: <?php echo $gate['color']; ?>;
        }
        
        .results-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }
        
        .result-box {
            background: rgba(10, 20, 30, 0.9);
            backdrop-filter: blur(10px);
            border: 2px solid;
            border-radius: 20px;
            padding: 20px;
            max-height: 500px;
            overflow-y: auto;
        }
        
        .result-box.live { border-color: #00ff00; }
        .result-box.die { border-color: #ff0000; }
        
        .result-box h3 {
            color: #ff00ff;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid;
            position: sticky;
            top: 0;
            background: rgba(10, 20, 30, 0.95);
        }
        
        .result-item {
            background: rgba(0, 0, 0, 0.5);
            border-left: 4px solid;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            font-family: monospace;
            font-size: 12px;
            white-space: pre-wrap;
        }
        
        .result-item.live { border-left-color: #00ff00; }
        .result-item.die { border-left-color: #ff0000; }
        
        .credits-counter {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: rgba(0, 0, 0, 0.9);
            border: 2px solid #ff00ff;
            border-radius: 15px;
            padding: 15px 25px;
            color: #ff00ff;
            font-weight: bold;
            font-size: 18px;
            z-index: 100;
            backdrop-filter: blur(10px);
        }
        
        .cyber-counter {
            position: fixed;
            bottom: 30px;
            left: 30px;
            background: rgba(0, 0, 0, 0.9);
            border: 2px solid #ffff00;
            border-radius: 15px;
            padding: 15px 25px;
            color: #ffff00;
            font-weight: bold;
            font-size: 18px;
            z-index: 100;
            backdrop-filter: blur(10px);
        }
    </style>
</head>
<body>
    <div class="stars"></div>
    <div class="twinkling"></div>
    <div class="clouds"></div>
    
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
            <div class="user-info">
                <span>👤 <?php echo $_SESSION['username']; ?></span>
            </div>
        </div>
        
        <div class="nav">
            <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="nav-btn">🏠 MENU</a>
            <a href="?ggs" class="nav-btn">🛒 LOJA</a>
            <?php if ($user_role === 'admin'): ?>
            <a href="?admin=true" class="nav-btn">⚙ ADMIN</a>
            <?php endif; ?>
            <a href="?lives" class="nav-btn">📋 LIVES</a>
            <button class="nav-btn" onclick="startCheck()">▶ INICIAR</button>
            <button class="nav-btn" onclick="stopCheck()">⏹ PARAR</button>
            <button class="nav-btn" onclick="clearAll()">🗑 LIMPAR</button>
            <a href="?logout" class="nav-btn">🚪 SAIR</a>
            <div class="loading" id="loading">
                <div class="spinner"></div>
                <span>PROCESSANDO...</span>
            </div>
        </div>
        
        <div class="status-bar">
            <?php if ($user_type === 'credits'): ?>
            <div class="status-item status-credits">
                <div style="font-size: 14px; margin-bottom: 5px;">💰 CRÉDITOS</div>
                <div style="font-size: 28px;"><?php echo number_format($user_credits, 2); ?></div>
            </div>
            <?php endif; ?>
            
            <div class="status-item status-cyber">
                <div style="font-size: 14px; margin-bottom: 5px;">🪙 MOEDAS CYBER</div>
                <div style="font-size: 28px;"><?php echo number_format($user_cyber, 2); ?></div>
            </div>
        </div>
        
        <textarea id="dataInput" placeholder="Cole os cartões (um por linha):
numero|mes|ano|cvv

Exemplos:
4532015112830366|12|2027|123
5425233430109903|01|2028|456"></textarea>
        
        <div class="stats-grid">
            <div class="stat-box">
                <div class="stat-label">TOTAL</div>
                <div class="stat-value" id="totalCount">0</div>
            </div>
            <div class="stat-box">
                <div class="stat-label">✅ LIVES</div>
                <div class="stat-value" id="liveCount">0</div>
            </div>
            <div class="stat-box">
                <div class="stat-label">❌ DIES</div>
                <div class="stat-value" id="dieCount">0</div>
            </div>
            <div class="stat-box">
                <div class="stat-label">⚡ PROCESSADOS</div>
                <div class="stat-value" id="processedCount">0</div>
            </div>
        </div>
        
        <div class="results-grid">
            <div class="result-box live">
                <h3>✅ APROVADOS</h3>
                <div id="liveResults"></div>
            </div>
            <div class="result-box die">
                <h3>❌ REPROVADOS</h3>
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
            document.getElementById('currentCredits').textContent = currentCredits.toFixed(2);
            document.getElementById('currentCyber').textContent = currentCyber.toFixed(2);
        }
        
        function startCheck() {
            const input = document.getElementById('dataInput').value.trim();
            if (!input) {
                alert('❌ Insira os cartões!');
                return;
            }
            
            if (userType === 'credits' && currentCredits < 0.05) {
                alert('❌ Créditos insuficientes!');
                return;
            }
            
            items = input.split('\n').filter(l => l.trim());
            if (items.length > MAX_ITEMS) {
                alert(`⚠️ Máximo de ${MAX_ITEMS} itens!`);
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
                // URL com a rota "burlada"
                const res = await fetch(`?c#$cybersecofc=1&tool=${toolName}&lista=${encodeURIComponent(item)}`);
                const text = await res.text();
                
                const isLive = checkIfLive(text);
                
                if (userType === 'credits') {
                    const cost = isLive ? <?php echo LIVE_COST; ?> : <?php echo DIE_COST; ?>;
                    currentCredits -= cost;
                    if (currentCredits < 0) currentCredits = 0;
                    updateCounters();
                }
                
                addResult(item, text, isLive);
                
            } catch (e) {
                addResult(item, '❌ Erro: ' + e.message, false);
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
// MENU PRINCIPAL
// ============================================
$gates_config = loadGatesConfig();
$active_gates = array_filter($gates_config, function($v) { return $v; });
$ggs_available = count(getGGsByBin());
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CYBERSEC OFC - MENU</title>
    <?php echo $anti_inspect_script; ?>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&display=swap');
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Orbitron', sans-serif;
        }
        
        body {
            min-height: 100vh;
            background: radial-gradient(ellipse at bottom, #0d1d31 0%, #0c0d13 100%);
            color: #00ffff;
            padding: 30px;
            position: relative;
            overflow-x: hidden;
        }
        
        .stars, .twinkling, .clouds {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
        }
        
        .stars { background: #000 url('http://www.script-tutorials.com/demos/360/images/stars.png') repeat top center; }
        .twinkling { background: transparent url('http://www.script-tutorials.com/demos/360/images/twinkling.png') repeat top center; animation: move-twink-back 200s linear infinite; }
        .clouds { background: transparent url('http://www.script-tutorials.com/demos/360/images/clouds.png') repeat top center; opacity: 0.4; animation: move-clouds-back 200s linear infinite; }
        
        @keyframes move-twink-back { from { background-position: 0 0; } to { background-position: -10000px 5000px; } }
        @keyframes move-clouds-back { from { background-position: 0 0; } to { background-position: 10000px 0; } }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            position: relative;
            z-index: 10;
        }
        
        .header {
            background: rgba(10, 20, 30, 0.9);
            backdrop-filter: blur(10px);
            border: 2px solid #ff00ff;
            border-radius: 50px;
            padding: 60px;
            margin-bottom: 40px;
            text-align: center;
            position: relative;
            box-shadow: 0 0 50px rgba(255, 0, 255, 0.3);
            animation: float 6s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        
        .header h1 {
            font-size: 72px;
            color: #00ffff;
            text-shadow: 0 0 30px #00ffff,
                         0 0 60px #ff00ff;
            margin-bottom: 20px;
            animation: textGlitch 3s infinite;
        }
        
        @keyframes textGlitch {
            0%, 100% { transform: skew(0deg, 0deg); }
            95% { transform: skew(5deg, -2deg); text-shadow: 5px 0 #ff00ff, -5px 0 #00ffff; }
            96% { transform: skew(-5deg, 2deg); text-shadow: -5px 0 #ff00ff, 5px 0 #00ffff; }
            97% { transform: skew(0deg, 0deg); }
        }
        
        .header p {
            color: #ff00ff;
            font-size: 18px;
            letter-spacing: 8px;
        }
        
        .user-info {
            position: absolute;
            top: 30px;
            right: 30px;
            background: rgba(0, 0, 0, 0.7);
            border: 2px solid #00ffff;
            border-radius: 20px;
            padding: 15px 25px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .user-badge {
            background: linear-gradient(45deg, #00ffff, #ff00ff);
            color: #000;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: bold;
        }
        
        .status-bar {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin-bottom: 40px;
            flex-wrap: wrap;
        }
        
        .status-item {
            background: rgba(10, 20, 30, 0.9);
            backdrop-filter: blur(10px);
            border: 2px solid;
            border-radius: 30px;
            padding: 25px 50px;
            text-align: center;
            min-width: 250px;
            transition: all 0.3s;
        }
        
        .status-item:hover {
            transform: scale(1.05);
            box-shadow: 0 0 30px currentColor;
        }
        
        .status-credits { border-color: #ff00ff; color: #ff00ff; }
        .status-cyber { border-color: #ffff00; color: #ffff00; }
        .status-gates { border-color: #00ffff; color: #00ffff; }
        
        .status-label {
            font-size: 14px;
            margin-bottom: 10px;
            color: inherit;
        }
        
        .status-value {
            font-size: 36px;
            font-weight: bold;
            color: inherit;
        }
        
        .nav {
            display: flex;
            gap: 20px;
            margin-bottom: 50px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .nav-btn {
            padding: 15px 35px;
            background: rgba(0, 0, 0, 0.7);
            border: 2px solid #00ffff;
            border-radius: 25px;
            color: #00ffff;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 10px;
            position: relative;
        }
        
        .nav-btn:hover {
            background: #00ffff;
            color: #000;
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 255, 255, 0.3);
        }
        
        .ggs-badge {
            background: #ff00ff;
            color: #000;
            padding: 2px 8px;
            border-radius: 15px;
            font-size: 12px;
            margin-left: 5px;
        }
        
        .gates-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
            margin-top: 30px;
        }
        
        .gate-card {
            background: rgba(10, 20, 30, 0.9);
            backdrop-filter: blur(10px);
            border: 2px solid;
            border-radius: 30px;
            padding: 30px;
            text-decoration: none;
            color: inherit;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }
        
        .gate-card::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            transform: rotate(45deg);
            transition: 0.5s;
            opacity: 0;
        }
        
        .gate-card:hover::before {
            opacity: 1;
            left: 100%;
        }
        
        .gate-card:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: 0 30px 60px rgba(0, 255, 255, 0.3);
        }
        
        .gate-card.inactive {
            opacity: 0.5;
            filter: grayscale(1);
            pointer-events: none;
        }
        
        .gate-icon {
            font-size: 64px;
            text-align: center;
            margin-bottom: 20px;
        }
        
        .gate-card h3 {
            color: #00ffff;
            text-align: center;
            margin-bottom: 15px;
            font-size: 22px;
        }
        
        .gate-status {
            position: absolute;
            top: 20px;
            right: 20px;
            width: 16px;
            height: 16px;
            border-radius: 50%;
        }
        
        .status-active {
            background: #00ff00;
            box-shadow: 0 0 20px #00ff00;
            animation: pulse 2s infinite;
        }
        
        .status-inactive {
            background: #ff0000;
            box-shadow: 0 0 20px #ff0000;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin-top: 50px;
        }
        
        .info-card {
            background: rgba(10, 20, 30, 0.9);
            backdrop-filter: blur(10px);
            border: 2px solid #ff00ff;
            border-radius: 30px;
            padding: 30px;
            text-align: center;
            transition: all 0.3s;
        }
        
        .info-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 0 30px #ff00ff;
        }
        
        .info-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }
        
        .info-title {
            color: #00ffff;
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        .info-value {
            font-size: 28px;
            font-weight: bold;
            color: #ffff00;
        }
    </style>
</head>
<body>
    <div class="stars"></div>
    <div class="twinkling"></div>
    <div class="clouds"></div>
    
    <div class="container">
        <div class="header">
            <h1>🚀 CYBERSEC OFC</h1>
            <p>A ÚLTIMA GERAÇÃO</p>
            
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
                <div class="status-label">💰 CRÉDITOS</div>
                <div class="status-value"><?php echo number_format($user_credits, 2); ?></div>
            </div>
            <?php endif; ?>
            
            <div class="status-item status-cyber">
                <div class="status-label">🪙 MOEDAS CYBER</div>
                <div class="status-value"><?php echo number_format($user_cyber, 2); ?></div>
            </div>
            
            <div class="status-item status-gates">
                <div class="status-label">🔧 GATES ATIVAS</div>
                <div class="status-value"><?php echo count($active_gates); ?>/<?php echo count($all_gates); ?></div>
            </div>
        </div>
        
        <div class="nav">
            <a href="?ggs" class="nav-btn">
                🛒 LOJA GG
                <?php if ($ggs_available > 0): ?>
                <span class="ggs-badge"><?php echo $ggs_available; ?></span>
                <?php endif; ?>
            </a>
            <a href="?lives" class="nav-btn">📋 MINHAS LIVES</a>
            <?php if ($user_role === 'admin'): ?>
            <a href="?admin=true" class="nav-btn">⚙ PAINEL ADMIN</a>
            <?php endif; ?>
            <a href="?logout" class="nav-btn">🚪 SAIR</a>
        </div>
        
        <h2 style="color: #ff00ff; margin-bottom: 20px; text-align: center;">🔧 CHECKERS DISPONÍVEIS</h2>
        
        <div class="gates-grid">
            <?php foreach ($all_gates as $key => $gate): 
                $isActive = $gates_config[$key] ?? false;
            ?>
            <a href="?tool=<?php echo $key; ?>" class="gate-card <?php echo !$isActive ? 'inactive' : ''; ?>" style="border-color: <?php echo $gate['color']; ?>">
                <div class="gate-icon"><?php echo $gate['icon']; ?></div>
                <h3><?php echo $gate['name']; ?></h3>
                <div class="gate-status <?php echo $isActive ? 'status-active' : 'status-inactive'; ?>"></div>
            </a>
            <?php endforeach; ?>
        </div>
        
        <div class="info-grid">
            <div class="info-card">
                <div class="info-icon">📊</div>
                <div class="info-title">TOTAL DE CHECKS</div>
                <div class="info-value"><?php echo $current_user['total_checks'] ?? 0; ?></div>
            </div>
            
            <div class="info-card">
                <div class="info-icon">✅</div>
                <div class="info-title">TOTAL DE LIVES</div>
                <div class="info-value"><?php echo $current_user['total_lives'] ?? 0; ?></div>
            </div>
            
            <div class="info-card">
                <div class="info-icon">📅</div>
                <div class="info-title">MEMBRO DESDE</div>
                <div class="info-value"><?php echo isset($current_user['created_at']) ? date('d/m/Y', strtotime($current_user['created_at'])) : date('d/m/Y'); ?></div>
            </div>
            
            <div class="info-card">
                <div class="info-icon">🔐</div>
                <div class="info-title">ÚLTIMO ACESSO</div>
                <div class="info-value"><?php echo isset($current_user['last_login']) ? date('d/m H:i', strtotime($current_user['last_login'])) : 'Primeiro'; ?></div>
            </div>
        </div>
    </div>
</body>
</html>
<?php
// Fim do código
?>
