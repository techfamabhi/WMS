<?php
//chk_login -- Checks if user is logged it, if not redirects to Login.php
// returns true is logged in else redirects and exits

function chk_login($top="/",$return_to="")
{
 if (get_cfg_var('wmsdir')) $wmsDir=get_cfg_var('wmsdir');
else { echo "<h1>WMS System is not Configured on this System</h1>"; exit; }
$top=str_replace("/var/www","",$wmsDir);

 if (isset($_SESSION["wms"]["UserLogin"])) $UserLogin=$_SESSION["wms"]["UserLogin"]; else $UserLogin="";
 if (trim($UserLogin)=="")
 {
  $rest="ret_link=" . $_SERVER["REQUEST_URI"] . "&type=notLogged";
  if ($return_to <> "")
  {
   $rest="ret_link={$return_to}&type=notLogged";
  }
  $redirect=$top . "Login.php?" . $rest;
  $htm=<<<HTML
 <html>
 <head>
 <script>
window.location.href="{$redirect}";
 </script>
 </head>
 <body>
 </body>
 </html>

HTML;
 echo $htm;
 exit;
 }
 return(true);
} // end chk_login
?>
