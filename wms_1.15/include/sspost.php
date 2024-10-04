<?php
// Function to post data stream using curl
// from www.php.net
function sspost($url,$xml)
{
$xml = "in=" . urlencode($xml);
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
