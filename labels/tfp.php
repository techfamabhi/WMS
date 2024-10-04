<?php

require("fpictic.php");
//echo "<pre>";

$ord = 10111;
$lpt = "/usr1/client/outfile.sh";
//$output=picTic($ord);
//$cmd="echo {$output} | {$lpt}";
//$result=exec($cmd);
//echo "{$result}\n";


$aa = $_SERVER["HTTP_REFERER"] . "pictic.php?o_number={$ord}";
$aa = "http://localhost/wms/labels/pictic.php?o_number={$ord}";
$cmd = "lynx -dump {$aa} | /usr1/client/outfile.sh";
$result = exec($cmd);
$jj = strlen($result);
echo $jj;
exit;


ob_start();
include $filename;
$output = ob_get_contents();
ob_end_clean();


