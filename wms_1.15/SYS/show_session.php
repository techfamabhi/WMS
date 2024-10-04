<?php
session_start();
if (isset($_REQUEST["reset"]) and $_REQUEST["reset"] == "y")
{
 session_destroy();
 exit;
}
echo "<pre>";
print_r($_SESSION);
?>
