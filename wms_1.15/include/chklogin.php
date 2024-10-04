<?php
// do a session start before this 
// set $thisprogram to this script name plus ay arguments
// 05/25/22 dse add wmsDir env variable to figure out top
// 10/13/22 dse correct redirect path to return to subdirectory/program

if (isset($_SESSION["wms"]["UserLogin"]))
 {
  $UserID=$_SESSION["wms"]["UserID"];
  $UserLogin=$_SESSION["wms"]["UserLogin"];
  $UserPriv=$_SESSION["wms"]["spriv_thru"];
  $UsersName="{$_SESSION["wms"]["first_name"]} {$_SESSION["wms"]["last_name"]}";
  $GroupID=$_SESSION["wms"]["GroupID"];
  $UserOper=$_SESSION["wms"]["operator"];
 }
else $UserLogin="";

if (trim($UserLogin)=="")
{
  if (get_cfg_var('wmsdir')) $wmsDir=get_cfg_var('wmsdir');
 else { echo "<h1>WMS System is not Configured on this System</h1>"; exit; }
 $top=str_replace("/var/www","",$wmsDir);

 if (!isset($thisprogram)) $thisprogram="index.php";
 $cwd=getcwd();
 $w=str_replace("{$wmsDir}/","",$cwd);
 $rest="ret_link={$w}/{$thisprogram}&type=notLogged";
 $redirect=$top . "/Login.php?" . $rest;

 $htm=<<<HTML
 <html>
 <head>
 <script>
window.top.location.href="{$redirect}";
 </script>
 </head>
 <body>
 </body>
</html>

HTML;
echo $htm;
exit;
}
?>
