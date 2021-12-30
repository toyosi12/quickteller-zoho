<?php
/**
 * This page handles the site_redirect after payment
 */
ini_set('display_errors', 1); ini_set('display_startup_errors', 1); error_reporting(E_ALL);
function decodeUrl($url)
{
    return mb_convert_encoding($url, "UTF-8", "HTML-ENTITIES");
}

function stringBetween($string, $startString, $endString)
{
    $start = strpos($string, $startString);
    $end = strpos($string, $endString);

    return substr($string, $start + 1, $end - $start - 1);
}

/**
 * Get the current url, included the base64 encoded parameters and decode.
 */
$currentUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
$retrievedData = explode('&payment_complete_url=', base64_decode(explode('.php/', $currentUrl)[1]));

$queryString = $retrievedData[0];
$redirectUrl = $retrievedData[1];
// $payItemId = stringBetween($queryString, '&payment_item_id=', '&payment_mode');
$accountId = substr($queryString, strpos($queryString, '&account_id=') + 12);
$merchantCodePayItemId = explode(':', $accountId);
$merchantCode = $merchantCodePayItemId[0];
$payItemId = $merchantCodePayItemId[1];
$queryString = str_replace('&account_id=' . $accountId, '', $queryString);
$modQueryString = preg_replace('/&|\?|=/', '', $queryString);

$transactionStatus;
$transactionReference = stringBetween($queryString, '&gateway_reference_id=', '&payment_mode');
$transactionReference = str_replace('gateway_reference_id=', '', $transactionReference);

$amount = stringBetween($queryString, '?amount=', '&gateway_reference_id=');
$amount = str_replace('amount=', '', $amount) * 100;

if ($_POST['resp'] == '00') {
    //confirm transaction
    $requeryUrl = 'https://qa.interswitchng.com/collections/api/v1/gettransaction.json?merchantcode='
    . $merchantCode . '&transactionreference=' . $transactionReference . '&amount=' . $amount;
    
    $curlInit = curl_init();
    curl_setopt($curlInit, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json'
    ));
    curl_setopt($curlInit, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curlInit, CURLOPT_URL, $requeryUrl);
    $response = json_decode(curl_exec($curlInit));
    curl_close($curlInit);
    if ($response->ResponseCode == '00' || $response->ResponseCode == '11') {
        $transactionStatus = 1;
    } else {
        $transactionStatus = 0;
        $gatewayErrorCode = $response->ResponseCode;
        $zcmErrorCode = 101;
    }
} else {
    $transactionStatus = 0;
    if ($_POST['resp'] == '14') {
        $transactionStatus = 0;
        $gatewayErrorCode = $_POST['resp'];
        $zcmErrorCode = 105;
    } elseif ($_POST['resp'] == '56') {
        $transactionStatus = 0;
        $gatewayErrorCode = $_POST['resp'];
        $zcmErrorCode = 107;
    } else {
        $transactionStatus = 0;
        $gatewayErrorCode = $_POST['resp'];
        $zcmErrorCode = 101;
    }
}
$modQueryString .= 'transaction_status' . $transactionStatus;


if ($transactionStatus === 1) {
    $signature = hash_hmac('sha256', $modQueryString, $payItemId);
    $queryString .= '&transaction_status=' . $transactionStatus . '&signature=' . $signature;
} else {
    $modQueryString .= 'zcm_errorcode' . $zcmErrorCode;
    $position = strpos($modQueryString, 'gateway_reference_id');
    $modQueryString = substr_replace($modQueryString, 'gateway_errorcode' . $gatewayErrorCode, $position, 0);
    $signature = hash_hmac('sha256', $modQueryString, $payItemId);
    $queryString .= '&transaction_status=' . $transactionStatus . '&zcm_errorcode=' . $zcmErrorCode .
                    '&gateway_errorcode=' . $gatewayErrorCode . '&signature=' . $signature;
    //. '&gateway_errorcode=' . $gatewayErrorCode . '&zcm_errorcode=' . $zcmErrorCode;
}




header('Location: ' . $redirectUrl . $queryString);