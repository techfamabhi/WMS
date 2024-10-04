<?php
// Function to post data stream using curl
// from www.php.net
// 2/2/18 dse Add Optional variable field name (defaulted to in for compatabilty
//            this allows sending json and other data to stdin
// 04/27/18 dse add urlencode param

function sspost1($url,$xml,$fldnm="in",$urlencode=false)
{
$fld="";
if (trim($fldnm) <> "") $fld="{$fldnm}=";

if ($urlencode) $xml = "{$fld}" . urlencode($xml);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, 0);
curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 0);
$xmlresult = curl_exec($ch);
curl_close($ch);
return($xmlresult);
}
?>
