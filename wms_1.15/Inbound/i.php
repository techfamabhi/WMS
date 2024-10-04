<?php
session_start();
phpinfo();
$scr=dirname($_SERVER["SCRIPT_NAME"]);
$w=explode("/",$scr);
echo "top={$w[1]}\n";
echo "cur={$w[2]}\n";
echo "<h2>SESSION Information</h2>";
echo "<pre>";
print_r($_SESSION);

?>
