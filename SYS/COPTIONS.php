<?php

// COPTIONS.php -- Option Description Maintenance
// 03/04/22 dse initial
/*TODO
 get option description when adding after coption entry
 allow some sort of zoom to lookup options while adding
*/
session_start();
if (get_cfg_var('wmsdir')) $wmsDir = get_cfg_var('wmsdir');
else {
    echo "<h1>WMS System is not Configured on this System</h1>";
    exit;
}
$top = str_replace("/var/www", "", $wmsDir);

$thisprogram = basename($_SERVER["PHP_SELF"]);
require("{$wmsDir}/config.php");
require_once("{$wmsInclude}/chklogin.php");
require_once("{$wmsInclude}/cl_Bluejay.php");
require_once("{$wmsInclude}/cl_template.php");
$Bluejay = $top;

// Application Specific Variables -------------------------------------
$temPlate = "COPTIONS";
$title = "Warehouse Option Maintenance";
$panelTitle = "Warehouse Options";
$SRVPHP = "{$wmsServer}/COPTIONS_srv.php";
$DRPSRV = "{$wmsServer}/dropdowns.php";
// end application specific variables ---------------------------------

//Load html template **********************************
$parser = new parser;
$parser->theme("en");
$parser->config->show = false;
$data = array("title" => $title, "panelTitle" => $panelTitle);
$theVueHtm = $parser->parse($temPlate, $data);
//******************************************************

//Read the vue app script from the js directory ************
$conf = array("extension" => 'js', "theme" => 'js');
$data = array("SRVPHP" => "{$SRVPHP}", "DRPSRV" => "{$DRPSRV}");
$vueAppScript = $parser->parse($temPlate, $data, $conf);
//******************************************************

//load the vue header script needed for html head section
$w = $parser->parse("vueheader", $data, $conf);
$js = $w;

//Display Bluejay Header
$pg = new Bluejay;
$pg->title = $title;
$pg->js = $js;
$pg->Display();

//Rest of page
$htm = <<<HTML
  {$theVueHtm}
<script>
  {$vueAppScript}
</script>
 </body>
</html>

HTML;
echo $htm;
?>
