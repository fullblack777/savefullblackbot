<?php
header('Content-type: application/json');
session_start();
date_default_timezone_set('America/Sao_Paulo');

$nome = $_GET['query'] ?? '';

if (empty($nome)) {
    echo json_encode(['success' => false, 'message' => 'Query não fornecido.']);
    exit;
}


function v($campo) {
    return isset($campo) && $campo !== "" ? $campo : "SEM INFORMAÇÃO";
}

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://portal.pmerj.rj.gov.br/190/api/IdentCivil/getPessoa');
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'accept: application/json, text/plain, */*',
    'accept-language: pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7',
'authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpZFVzZXIiOjExNzAxMSwiSVNfQWRtaW4iOjAsImNwZiI6IjExOTM1NDM2NzMyIiwidGltZSI6IjIwMjAtMDYtMDVUMTU6NTU6MzdaIn0.uFKNHq58a-Oxw31cg6wbIvoc7S7gshIjqNeyIZ60EvM',
    'content-type: application/json',
    'origin: https://portal.pmerj.rj.gov.br',
    'referer: https://portal.pmerj.rj.gov.br/',
    'sec-ch-ua: "Not)A;Brand";v="24", "Chromium";v="116"',
    'sec-ch-ua-mobile: ?0',
    'sec-ch-ua-platform: "Linux"',
    'sec-fetch-dest: empty',
    'sec-fetch-mode: cors',
    'sec-fetch-site: same-origin',
    'user-agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/116.0.0.0 Safari/537.36',
]);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

$json = json_encode(['vnome' => $nome]);
curl_setopt($ch, CURLOPT_POSTFIELDS, $json);

$response = curl_exec($ch);
curl_close($ch);

$json_data = json_decode($response, true);

if (empty($json_data) || $json_data === []) {
    echo json_encode(["success" => false, "message" => "Nome não encontrado."]);
    if (function_exists('salvar')) {
        salvar($con, $_SESSION['usuario'], $nome, 'Nome Detran', 'Falha', date('d/m/Y H:i'));
    }
    exit;
}

$response_data = [
       "status" => '200',
    "apis" => 'SkyData',
    'total' => 0,
    'dados' => []
];

foreach ($json_data as $res) {
    $obito = isset($res['Obito']) ? $res['Obito'] : null;
    $substituicoes = [
        '1' => 'Sim',
        '0'  => 'Não'
    ];
    $obito = strtr($obito, $substituicoes);
    
    $response_data['dados'][] = [
        'nome' => v($res['NO_CIDADAO']),
        'nascimento' => v($res['Nascimento']),
        'obito' => v($obito),
        'filiacao' => [
            'mae' => v($res['NO_MAECIDADAO']),
            'pai' => v($res['NO_PAICIDADAO']),
        ],
        'foto' => v($res['FotoCivil'])
    ];
    $response_data['total']++;
}

if (function_exists('salvar')) {
    salvar($con, $_SESSION['usuario'], $nome, 'Nome Detran', 'Sucesso', date('d/m/Y H:i'));
}


echo json_encode($response_data);
?>