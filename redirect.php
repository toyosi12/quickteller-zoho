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
$transactionReference = stringBetween($queryString, '&gateway_reference_id=', '&payment_mode=card');
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
    if ($response->ResponseCode == '00') {
        $transactionStatus = 1;
    } else {
        $transactionStatus = 0;
    }
} else {
    $transactionStatus = 0;
}
$modQueryString .= 'transaction_status' . $transactionStatus;


$signature = hash_hmac('sha256', $modQueryString, $payItemId);
$queryString .= '&transaction_status=' . $transactionStatus . '&signature=' . $signature;

header('Location: ' . $redirectUrl . $queryString);