<?php
// ============================================
// CHECKER N7 COM VALIDAÇÃO LUHN
// ============================================

// Recebe os dados do cartão
$card = $parts[0] ?? '';
$mes = $parts[1] ?? '';
$ano = $parts[2] ?? '';
$cvv = $parts[3] ?? '';

// ============================================
// FUNÇÃO LUHN (ALGORITMO DO CARTÃO)
// ============================================
function validateLuhn($number) {
    $number = preg_replace('/[^0-9]/', '', $number);
    $sum = 0;
    $alt = false;
    
    for ($i = strlen($number) - 1; $i >= 0; $i--) {
        $n = $number[$i];
        if ($alt) {
            $n *= 2;
            if ($n > 9) {
                $n = $n - 9;
            }
        }
        $sum += $n;
        $alt = !$alt;
    }
    
    return ($sum % 10 == 0);
}

// ============================================
// FUNÇÃO PARA IDENTIFICAR BANDEIRA
// ============================================
function getCardBrand($bin) {
    $bin = substr($bin, 0, 6);
    
    if (preg_match('/^4[0-9]/', $bin)) {
        return 'VISA';
    } elseif (preg_match('/^5[1-5]/', $bin)) {
        return 'MASTERCARD';
    } elseif (preg_match('/^3[47]/', $bin)) {
        return 'AMEX';
    } elseif (preg_match('/^6(?:011|5)/', $bin)) {
        return 'DISCOVER';
    } elseif (preg_match('/^3(?:0[0-5]|[68])/', $bin)) {
        return 'DINERS';
    } elseif (preg_match('/^(636368|438935|504175|451416|636297)/', $bin)) {
        return 'ELO';
    } elseif (preg_match('/^606282/', $bin)) {
        return 'HIPERCARD';
    } else {
        return 'DESCONHECIDA';
    }
}

// ============================================
// VALIDAÇÕES BÁSICAS
// ============================================

// Limpar cartão (só números)
$card_clean = preg_replace('/[^0-9]/', '', $card);
$bin = substr($card_clean, 0, 6);

// Validar tamanho do cartão
if (strlen($card_clean) < 15 || strlen($card_clean) > 16) {
    echo "❌ REPROVADA - Cartão inválido (tamanho incorreto)";
    exit;
}

// Validar LUHN
if (!validateLuhn($card_clean)) {
    echo "❌ REPROVADA - Cartão inválido (falhou no teste Luhn)";
    exit;
}

// Validar mês
if ($mes < 1 || $mes > 12) {
    echo "❌ REPROVADA - Mês inválido";
    exit;
}

// Validar ano (não pode ser ano passado)
$ano_atual = date('y');
$ano_completo = date('Y');

if (strlen($ano) == 2) {
    $ano_check = 2000 + $ano;
    if ($ano_check < $ano_completo) {
        echo "❌ REPROVADA - Cartão expirado (ano)";
        exit;
    }
    if ($ano_check == $ano_completo && $mes < date('m')) {
        echo "❌ REPROVADA - Cartão expirado (mês/ano)";
        exit;
    }
}

if (strlen($ano) == 4) {
    if ($ano < $ano_completo) {
        echo "❌ REPROVADA - Cartão expirado (ano)";
        exit;
    }
    if ($ano == $ano_completo && $mes < date('m')) {
        echo "❌ REPROVADA - Cartão expirado (mês/ano)";
        exit;
    }
}

// Validar CVV
if (strlen($cvv) < 3 || strlen($cvv) > 4) {
    echo "❌ REPROVADA - CVV inválido";
    exit;
}

// ============================================
// SIMULAÇÃO DE RESULTADO (LIVE ou DIE)
// ============================================

$bandeira = getCardBrand($bin);
$porcentagem_live = rand(1, 100);

// Regras para considerar LIVE
$isLive = false;
$mensagem = "";

// BINS mais propensos a dar live (exemplo)
$bins_premium = ['453201', '542523', '555566', '402400', '4916'];
$bins_ruins = ['123456', '000000', '111111'];

if (in_array($bin, $bins_premium)) {
    $porcentagem_live += 20; // +20% de chance
}

if (in_array($bin, $bins_ruins)) {
    $porcentagem_live -= 30; // -30% de chance
}

// Bandeiras com mais chance
if ($bandeira == 'VISA' || $bandeira == 'MASTERCARD') {
    $porcentagem_live += 10;
}

// Decidir se é LIVE (30% de chance base)
if ($porcentagem_live > 70) {
    $isLive = true;
    $mensagem = "✅ APROVADA - Transação autorizada com sucesso!";
} elseif ($porcentagem_live > 40) {
    // 50% de chance
    $isLive = (rand(1, 100) > 50);
    $mensagem = $isLive ? "✅ APROVADA - Transação autorizada!" : "❌ REPROVADA - Saldo insuficiente";
} else {
    $isLive = false;
    $mensagem = "❌ REPROVADA - Cartão negado pela operadora";
}

// ============================================
// RESPOSTA FORMATADA
// ============================================

echo "=====================================\n";
echo "🔧 N7 CHECKER\n";
echo "=====================================\n";
echo "📱 Cartão: " . substr($card_clean, 0, 6) . "******" . substr($card_clean, -4) . "\n";
echo "📅 Data: $mes/$ano\n";
echo "💳 CVV: $cvv\n";
echo "🔢 BIN: $bin\n";
echo "💳 Bandeira: $bandeira\n";
echo "✅ Luhn: " . (validateLuhn($card_clean) ? "Válido" : "Inválido") . "\n";
echo "=====================================\n";
echo "$mensagem\n";
echo "=====================================\n";

// Detalhes extras se for LIVE
if ($isLive) {
    echo "💰 Saldo: R$ " . number_format(rand(100, 5000), 2) . "\n";
    echo "🏦 Banco: Banco " . rand(1, 999) . "\n";
    echo "🌎 País: Brasil\n";
    echo "=====================================\n";
}

// Log para debug (opcional)
$log = date('Y-m-d H:i:s') . " | $card_clean | $bin | " . ($isLive ? "LIVE" : "DIE") . " | $bandeira\n";
file_put_contents(__DIR__ . '/../data/n7_log.txt', $log, FILE_APPEND);
?>
