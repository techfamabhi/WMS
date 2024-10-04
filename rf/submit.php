<?php
/*
 submit.php - ver 1.0

 Program to set request data in the session, then redirect to the 
 original program. This prevents data from being re-submitted if the user
 presses refresh

 Requires the forms to have a variable "thisprogram" set to re-direct to.
*/
session_start();
$_SESSION["REQDATA"] = $_REQUEST;

if (!isset($_REQUEST["thisprogram"])) {
    echo "An Error occurred, the is no place to re-direct to";
    exit;
}
header("Location: " . $_REQUEST["thisprogram"]);
exit;
?>
