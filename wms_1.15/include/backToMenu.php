<?php
 if (!isset($top))
 {
  if (get_cfg_var('wmsdir') !== false) $wmsDir=get_cfg_var('wmsdir');
  else { echo "<h1>WMS System is not Configured on this System</h1>"; exit; }
  $top=str_replace("/var/www","",$wmsDir);
 }

  $rest="";
  if (isset($_SESSION["wms"]["last_menu"]))
  {
  $rest="menu_num={$_SESSION["wms"]["last_menu"]}";
  }
  $redirect=$top . "/webmenu.php?" . $rest;
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
?>
