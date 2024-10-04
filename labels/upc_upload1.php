<?php

// packids.php -- print packids to laser
// 03/29/22 dse initial 

foreach (array_keys($_REQUEST) as $w) {
    $$w = $_REQUEST[$w];
}
error_reporting(0);

if (get_cfg_var('wmsdir')) $wmsDir = get_cfg_var('wmsdir');
else {
    echo "<h1>WMS System is not Configured on this System</h1>";
    exit;
}
$top = str_replace("/var/www", "", $wmsDir) . "/";

session_start();
require($_SESSION["wms"]["wmsConfig"]);

$thisprogram = "upc_upload1.php";
require_once("{$wmsInclude}/chklogin.php");
require_once("{$wmsInclude}/cl_Bluejay.php");
$title = "Upload and Print Part UPC Code Labels";

$nextprog = "upc_upload2.php";
$pg = new Bluejay;

if (isset($_REQUEST["Error"])) $Error = $_REQUEST["Error"]; else $Error = "";
if (isset($_REQUEST["referer"])) $referer = $_REQUEST["referer"]; else $referer = "";
$pg->title = $title;
$pg->js = <<<HTML
<script>
function do_print() {
       if (document.form1.labelType.value != "" )
         {
          document.form1.submit();
         }
}
</script>
HTML;
$pg->Display();

$htm = <<<HTML
<h3 class="FormHeaderFont" align="center">{$title}</h3>
<br>
<p>{$title} on Laser/Inkjet printers. Please Note, Laser Printer Lables are in the 5xxx series. For Inkjet series, you will need the 8xxx series labels</p>
<br>
<form name="form1" action="{$nextprog}" method="post">
<table>
 <tr>
  <td colspan="2" class="FormSubHeaderFont">Click on desired Label Format</td>
 </tr>
 <tr>
  <td><a href="{$nextprog}?lbl=5160"><img src="{$wmsImages}/labels/avery_5160.png" border="0"></a></td>
  <td><a href="{$nextprog}?lbl=5161"><img src="{$wmsImages}/labels/avery_5161.png" border="0"></a></td>
  <td><a href="{$nextprog}?lbl=5162"><img src="{$wmsImages}/labels/avery_5162.png" border="0"></a></td>
 </tr>
 <tr>
  <td><a href="{$nextprog}?lbl=5163"><img src="{$wmsImages}/labels/avery_5163.png" border="0"></a></td>
  <td><a href="{$nextprog}?lbl=5164"><img src="{$wmsImages}/labels/avery_5164.png" border="0"></a></td>
  <td><a href="{$nextprog}?lbl=5167"><img src="{$wmsImages}/labels/avery_5167.png" border="0"></a></td>
 </tr>
</table>
</form>
</body>
</html>

HTML;
echo $htm;

?>
