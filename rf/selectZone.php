<?php
//selectZone.php   -- select zone for picking
// 03/14/22 dse initial

foreach (array_keys($_REQUEST) as $w) {
    $$w = $_REQUEST[$w];
}
//error_reporting(0);

if (isset($_REQUEST["nh"])) $nh = $_REQUEST["nh"]; else $nh = 0;
$thisprogram = $_SERVER["SCRIPT_NAME"];
session_start();
require($_SESSION["wms"]["wmsConfig"]);
require_once("{$wmsInclude}/cl_rf.php");
require_once("{$wmsInclude}/chk_login.php");
require_once("{$wmsInclude}/get_username.php");
require_once("../include/restSrv.php");
$ZONESRV = "{$wmsIp}/{$wmsServer}/WHSEZONES_srv.php";
$f = array("action" => "fetchall", "company" => -1);
$rc = restSrv($ZONESRV, $f);
$ZONES = json_decode($rc, true);
if (!isset($ret)) $ret = $thisprogram;

if (!isset($Zone) and isset($beenHere)) {
    $Zone = array();
    unset($_SESSION["wms"]["zones"]);
}
if (!isset($beenHere)) {
    $beenHere = 0;
    $bprompt = "Update";
    $pclass = "#b3e0ff";
} else {
    $beenHere++;
    $bprompt = "Done";
    $pclass = "#D6D6D6";
}
if (isset($_SESSION["wms"]["zones"])) $zones = $_SESSION["wms"]["zones"]; else $zones = array();
if (isset($Zone) and is_array($Zone) and count($Zone) > 0) {
    $_SESSION["wms"]["zones"] = $Zone;
    $zones = $Zone;
}
$pzs = array();
//if ($Zone == "%") $pzs=" selected";
$i = 0;
$i1 = 0;
if (count($ZONES)) foreach ($ZONES as $key => $z) {
    if ($z["is_pickable"]) {
        $i++;
        $pzs[$key] = "";
        $k = $z["zone"];
        if (isset($zones[$k])) {
            $pzs[$key] = " checked";
            $i1++;
        }
    } // end is pickable
} // end foreach zones
if ($i == $i1) $az = " checked"; else $az = "";
$zone_htm = <<<HTML
<div>
<form name="form1" action="{$thisprogram}" method="get">
 <input type="hidden" name="nh" id="nh" value="{$nh}">
 <input type="hidden" name="beenHere" id="beenHere" value="{$beenHere}">
 <input type="hidden" name="ret" id="ret" value="{$ret}">
 <h2 class="FormHeaderFont">Select Zone(s)</h2>
 <input type="checkbox" name="checkAll" value="" onclick="check_all(this)"{$az}>
 <label for="checkAll">Select All</label>
 <br>

HTML;
foreach ($ZONES as $key => $z) {
    if ($z["is_pickable"]) {
        if (isset($zones[$z["zone"]])) $ch = " checked"; else $ch = "";
        $zone_htm .= <<<HTML
 <div>
 <input type="checkbox" name="Zone[{$z["zone"]}]" value="{$z["zone"]}"{$ch}>
 <label for="Zone[{$z["zone"]}]">{$z["zone"]} - {$z["zone_desc"]}</label>
    </div>
HTML;
    } // end is_pickable
} // end foreach ZONES

$zone_htm .= <<<HTML
<button class="binbutton" style="background-color: {$pclass}; color: black;" id="B1" name="B1" onclick="do_submit();">{$bprompt}</button>
</div>
 <script>
 function check_all(stat) {
    var checkboxes = document.querySelectorAll('input[type="checkbox"]');
    for (var checkbox of checkboxes) {
        checkbox.checked = stat.checked;
    }
}
 function do_submit()
 {
  var bh=document.form1.beenHere;
  if (bh.value > 0) document.form1.action="{$ret}";
  document.form1.submit();
 }
 </script>
HTML;

$rc = chk_login($wmsHome, $thisprogram);
$username = get_username();
if (isset($spriv_thru)) $user_priv = $spriv_thru;
else $user_priv = -1;
$title = "Picking";
$helpPage = "{$wmsHelp}/picking.pdf";

$pg = new displayRF;
$pg->viewport = "1.10";
$pg->dispLogo = false;
$pg->title = $title;
if (isset($nh) and $nh > 0) $pg->noHeader = true; else $nh = 0;
if ($username <> "") $pg->User["Name"] = $username;
$pg->jsh = "";

$pg->jsh = <<<HTML
<script src="/jq/shortcut.js" type="text/javascript"></script>
<script>
shortcut.add("F1",function() {
        do_reset();
});
</script>

HTML;
$pg->SystemName = "Picking Operations";

$htm = <<<HTML
<div class="w3-row-padding w3-margin-bottom">
 <div class="w3-half">
  <div class="w3-container w3-padding-8">
  <h3 class="FormHeaderFont">{$pg->SystemName}</h3>
   {$zone_htm}
  </div>
 </div>
</div>
HTML;
$pg->body = $htm;
$pg->Display();
//echo "<pre>";
//print_r($_REQUEST);
//print_r($_SESSION);
?>
