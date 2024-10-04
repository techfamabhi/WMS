<?php
session_start();
phpinfo();
echo dirname($_SERVER["SCRIPT_NAME"]);
echo "<h2>SESSION Information</h2>";
echo "<pre>";
print_r($_SESSION);

?>
