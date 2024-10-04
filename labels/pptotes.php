<?php

if (get_cfg_var('wmsdir')) $wmsDir = get_cfg_var('wmsdir');
else {
    echo "<h1>WMS System is not Configured on this System</h1>";
    exit;
}
$top = str_replace("/var/www", "", $wmsDir);

session_start();
require($_SESSION["wms"]["wmsConfig"]);

$thisprogram = "ppbin.php";
require_once("{$wmsInclude}/chklogin.php");
require_once("{$wmsInclude}/cl_Bluejay.php");

$pg = new Bluejay;

$title = "Print Tote Bar Codes";
$pg->title = $title;
$panelTitle = "";
$Bluejay = $top;


$htm = <<<HTML
<div style="margin-right:100px">
 <form name="form1" action="chooseLabel.php" method="get"> 
 <input type="hidden" name="nextprog" value="ppacks.php"> 
 <table class="FormTABLE" cellspacing="2" cellpadding="2">
 <tr>
 <td colspan="2"><h3 class="FormHeaderFont" align="center">{$title}</h3></td>
 </tr>
 <tr>
  <td align="right" class="FieldCaptionTD"><label for="binFrom">Tote Range - From:</label></td>
  <td class="DataTD"><input type="text" style="text-transform:uppercase;" id="binFrom" size="18" name="binFrom" value=""></td>
 <tr>
 </tr>
  <td align="right" class="FieldCaptionTD"><label for="binTo">- To:</label></td>
  <td class="DataTD"><input type="text" style="text-transform:uppercase;" id="binTo" size="18" name="binTo" value=""></td>
 </tr>
  <td align="right">&nbsp;</td>
  <td><input class="wms-light-blue" type="submit" name="B1" value="Submit"></td>
 </tr>
 </table>
 </form>
</div>
HTML;

$pg->noBootStrap = true;
$pg->Display();
echo $htm;
