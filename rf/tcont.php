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

$RESTSRV = "http://{$wmsIp}{$wmsServer}/RcptLine.php";
$comp = 1;


if (isset($_REQUEST["toteId"])) $toteId = $_REQUEST["toteId"]; else $toteId = 0;
//if ($toteId < 1) exit;
$req = array(
    "action" => "getToteDetail",
    "company" => $comp,
    "tote_id" => $toteId
);
//echo "calling";
$ret = restSrv($RESTSRV, $req);
$w = (json_decode($ret, true));
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
    "heading" => "Tote {$toteId} Contents",
    "cols" => 5,
    "color" => "w3-blue",
    "msg" => "",
    "items" => $w
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


$data["buttons"] = $buttons;

$temPlate = "dispToteContents";
$ret = $parser->parse($temPlate, $data);

$pg->title = "Tote # {$toteId} Contents";
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
