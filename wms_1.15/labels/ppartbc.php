<?php

if (get_cfg_var('wmsdir')) $wmsDir=get_cfg_var('wmsdir');
else { echo "<h1>WMS System is not Configured on this System</h1>"; exit; }
$top=str_replace("/var/www","",$wmsDir);

session_start();
require($_SESSION["wms"]["wmsConfig"]);

$thisprogram="ppartbc.php";
require_once("{$wmsInclude}/chklogin.php");
require_once("{$wmsInclude}/cl_Bluejay.php");

$pg=new Bluejay;

$title="Print Part UPC Codes";
$pg->title=$title;
$panelTitle="";
$Bluejay=$top;
$SRVPHP="{$wmsServer}/PARTS_srv.php";


$htm=<<<HTML
<div style="margin-right:100px">
 <form name="form1" action="chooseLabel.php" method="get"> 
 <input type="hidden" name="nextprog" value="pr_partbc.php"> 
 <table class="FormTABLE" cellspacing="2" cellpadding="2">
 <tr>
 <td colspan="2"><h3 class="FormHeaderFont" align="center">{$title}</h3></td>
 </tr>
 <tr>
  <td align="right" class="FieldCaptionTD"><label for="plfrom">P/L Range - From:</label></td>
  <td class="DataTD"><input type="text" style="text-transform:uppercase;" id="plfrom" size="3" name="plfrom" value=""></td>
 <tr>
 </tr>
  <td align="right" class="FieldCaptionTD"><label for="plto">- To:</label></td>
  <td class="DataTD"><input type="text" style="text-transform:uppercase;" id="plto" size="3" name="plto" value=""></td>
 </tr>
 </tr>
  <td align="right" class="FieldCaptionTD"><label for="ppart">Parts starting with</label></td>
  <td class="DataTD"><input type="text" style="text-transform:uppercase;" id="ppart" size="22" name="ppart" value=""></td>
 </tr>
 </tr>
  <td align="right">&nbsp;</td>
  <td><input class="wms-light-blue" type="submit" name="B1" value="Submit"></td>
 </tr>
 </table>
 </form>
</div>
HTML;

$pg->noBootStrap=true;
$pg->Display();
echo $htm;
