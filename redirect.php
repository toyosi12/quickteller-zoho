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
$payItemId = stringBetween($queryString, '&payment_item_id=', '&payment_mode');
$modQueryString = preg_replace('/&|\?|=/', '', $queryString);

$transactionStatus;

if ($_POST['resp'] == '00') {
    $transactionStatus = 1;
} else {
    $transactionStatus = 0;
}
$modQueryString .= 'transaction_status' . $transactionStatus;


$signature = hash_hmac('sha256', $modQueryString, 'Default_Payable_MX26070');
$queryString .= '&transaction_status=' . $transactionStatus . '&signature=' . $signature;

header('Location: ' . $redirectUrl . $queryString);