<?php
foreach (array_keys($_REQUEST) as $w) {
    $$w = $_REQUEST[$w];
}
//error_reporting(0);

session_start();
if (get_cfg_var('wmsdir')) $wmsDir = get_cfg_var('wmsdir');
else {
    echo "<h1>WMS System is not Configured on this System</h1>";
    exit;
}
$top = str_replace("/var/www", "", $wmsDir);

if (!isset($nh)) $nh = 0;

$thisprogram = basename($_SERVER["PHP_SELF"]);
require("{$wmsDir}/config.php");
require_once("{$wmsInclude}/chklogin.php");

require_once("{$wmsInclude}/cl_template.php");
require_once("{$wmsInclude}/cl_rf1.php");
require_once("{$wmsInclude}/restSrv.php");

$RESTSRV = "http://{$wmsIp}{$wmsServer}/PICK_srv.php";

if (isset($_REQUEST["orderNum"])) $orderNum = $_REQUEST["orderNum"]; else $orderNum = 0;
if (isset($_REQUEST["comp"])) $comp = $_REQUEST["comp"]; else $comp = 1;
if ($orderNum < 1) exit;
$req = array("action" => "fetchOrder",
    "company" => $comp,
    "scaninput" => $orderNum,
    "process" => "PACK"
);
$ret = restSrv($RESTSRV, $req);
$w = (json_decode($ret, true));
if (!isset($w["Order"])) exit;

//echo " done";
//echo "<pre>";
//print_r($w);
//exit;


$pg = new displayRF;
$pg->viewport = "1.0";
$pg->dispLogo = false;
$pg->Bootstrap = true;
$pg->noHeader = true;
if (isset($title)) $pg->title = $title;
if (isset($color)) $pg->color = $color; else $color = "light-blue";

$parser = new parser;
$parser->theme("en");
$parser->config->show = false;

unset($w["numRows"]);
$data = array(
    "heading" => "Order {$orderNum} Items",
    "cols" => 5,
    "color" => "w3-blue",
    "msg" => "",
    "items" => $w["Items"]
);
$buttons = array(
    1 => array(
        "btn_id" => "b1",
        "btn_name" => "B1",
        "btn_value" => "Close",
        "btn_onclick" => "do_close();",
        "btn_prompt" => "Close"
    )
);
//echo "<pre>";
//print_r($data);
//echo "</pre>";

if (isset($nobtn) and $nobtn > 0) $buttons = array();
$data["buttons"] = $buttons;

$temPlate = "dispOrdItems";
$ret = $parser->parse($temPlate, $data);

$pg->title = "Order # {$orderNum} Items";
$pg->Display();
$htm = <<<HTML
{$ret}
<script>
 function do_close()
 {
  self.close();
 }
</script>
</body>
</html>

HTML;

echo $htm;
?>
