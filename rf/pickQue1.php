<?php
// pickQueue.php - list open reelased orders and select 1 to pick
/*
TODO
1) include header and STD routines
2) set zone
3) get open picks
4) allow selection of a pick
5) re-invoke pick.php so it can open a new tab with the selected pick
6) decide if each pick should have it's own tab, or have pick.php tell them next bin

future SQL to sort by type and prio, then filter out status and type and more
select
distinct
order_type,
priority,
zone,
user_id,
ord_num,
count(*)
 as num_lines
from ITEMPULL,ORDERS
where order_num = ord_num
group by order_type,priority, zone, ord_num, user_id

*/
foreach (array_keys($_REQUEST) as $w) {
    $$w = $_REQUEST[$w];
}
//error_reporting(0);
if (isset($order)) {
    echo "<pre>";
    print_r($_REQUEST);
    echo "</pre>";
//exit;
}
if (!isset($nh)) $nh = 0;
if (!isset($orderType)) $orderType = "1";
session_start();
if (get_cfg_var('wmsdir')) $wmsDir = get_cfg_var('wmsdir');
else {
    echo "<h1>WMS System is not Configured on this System</h1>";
    exit;
}
$top = str_replace("/var/www", "", $wmsDir);

if (isset($_SESSION["wms"]["Pick"])) unset($_SESSION["wms"]["Pick"]);

$thisprogram = basename($_SERVER["PHP_SELF"]);
$nextprogram = "pickOrder1.php";
require("{$wmsDir}/config.php");
require_once("{$wmsInclude}/chklogin.php");
require_once("{$wmsInclude}/cl_rf1.php");
require_once("{$wmsInclude}/db_main.php");
require_once("{$wmsInclude}/get_option.php");
require_once("../include/restSrv.php");

$db = new WMS_DB;

$comp = 1;
$opt = array();
$opt[26] = get_option($db, $comp, 26);

$UserId = $_SESSION["wms"]["UserID"];
if (isset($_SESSION["wms"]["zones"])) $zones = $_SESSION["wms"]["zones"];
else $zones = array();

if (isset($opt[26]) and $opt[26] == "N") {
    $zones = array("%" => "%");
    $_SESSION["wms"]["zones"] = $zones;
}

$ordernum = 2;
$i = 0;
$stat = "-1|3";
if ($orderType <> "%" and $orderType <> "O") $stat = "{$orderType}|{$orderType}";
if ($orderType == "%") $stat = "-1|3";
if ($orderType == "O") $stat = "4|9";
if ($orderType == "3") $stat = "3|3";
if ($orderType == "4") $stat = "4|4";

//get open orders
$f = array("action" => "fetchall",
    "numRows" => 9999,
    "startRec" => 1,
    "company" => $comp,
    "custname" => "",
    "statRange" => $stat
);
$myZones = "";
if (!empty($zones)) {
    $comma = "";
    $myZones = " Zones: ";
    if (count($zones) == 1) $myZones = " ";
    // remove redundent Zones wording
    $myZones = " ";

    $f["zones"] = $zones;
    foreach ($zones as $z) {
        if ($z == "%") $z = " ";
        $myZones .= "{$comma}{$z}";
        $comma = ",";
    }
} // end zones not empty
else { // zones are empty, lets ask to set zone
    $redirect = "selectZone.php?ret=pickQue.php";
    $htm = <<<HTML
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
} // zones are empty, lets ask to set zone

//get open picks
$f1 = array("action" => "fetchOpenPicks",
    "company" => $comp,
    "zones" => $zones
);

$htm = "";
$oty = "";
$options = array(
    "1" => "Awaiting Pick",
    "2" => "Being Picked",
    "3" => "In Packing",
    "4" => "In shipping",
    "-1" => "Awaiting Product",
    "0" => "Not Released",
    "O" => "Awaiting Product");

if ($orderType == "%") $oty = " selected"; else $oty = "";
$op_htm = <<<HTML
 <option value="%"{$oty}>All{$myZones}</option>

HTML;
foreach ($options as $key => $o) {
    if (strcmp($orderType, $key) == 0) $oty = " selected"; else $oty = "";
    if ($key < 4 and $key <> "O") {
        $op_htm .= <<<HTML
 <option value="{$key}"{$oty}>{$o}{$myZones}</option>

HTML;
    }
} // end foreach option

$msg = "";
$rc = restSrv("http://localhost/wms/servers/PICK_srv.php", $f);
$data = (json_decode($rc, true)); // dumps array
//echo "<pre>rc={$rc}";
//print_r($f);
//print_r($data);
//echo "</pre>";
//exit;

if (isset($data["rowData"])) $rowData = $data["rowData"];
else $rowData = array();
if (count($rowData)) {
    $needprod = "";
    $notrel = "";
    $open = "";
    $inproc = "";
    $packing = "";
    $shipping = "";
    $other = "";
    $htm = <<<HTML
 <body>
       <table>
           <tr>
            <td nowrap width="1%" class="FieldCaptionTD">&nbsp;</td>
            <td nowrap width="5%" class="FieldCaptionTD">Order Id</td>
            <td width="5%" class="FieldCaptionTD">Customer</td>
            <td width="5%" class="FieldCaptionTD">Priority</td>
            <td width="5%" class="FieldCaptionTD">Via</td>
            <td width="10%" class="FieldCaptionTD">PO #</td>
            <td width="5%" style="text-align:right;padding-right:10px" class="FieldCaptionTD">Lines</td>
            <td width="5%" class="FieldCaptionTD">Zones</td>
           </tr>

HTML;
    foreach ($rowData as $rec => $l) {
        $cls = "";
        switch ($l["order_stat"]) {
            case "-1": // awaiting product
                $w = "needprod";
            case "0": // not released
                $w = "notrel";
                break;
            case "1": // awaiting Pick
                $w = "open";
                $cls = " class=\"Alt5DataTD\"";
                break;
            case "-2": // being Picked
                $cls = " class=\"Alt7DataTD\"";
                $w = "inproc";
                break;
            case "2": // being Picked and zerod
                $cls = " class=\"Alt4DataTD\"";
                $w = "inproc";
                break;
            case "3": // being packed
                $w = "packing";
                $cls = " class=\"Alt6DataTD\"";
                break;
            case "4": // being shipped
                $w = "shipping";
                $cls = " class=\"Alt7DataTD\"";
                break;
            default:  // done
                $w = "other";
                $cls = "";
                break;
        } // end switch order_stat
        $lnk = "";
        $lnke = "";
        //get users on an order
        $f2 = array("action" => "fetchUsers",
            "order_num" => $l["order_num"]);
        $rc = restSrv("http://localhost/wms/servers/PICK_srv.php", $f2);
        $d = (json_decode($rc, true)); // dumps array
        $addLink = false;
        $j1 = 0;
        if ($l["order_stat"] == "1") $addLink = true;
        if (count($d) > 0 and !$addLink) {
            foreach ($d as $pd) {
                if ($pd["user_id"] == $UserId and $l["order_stat"] < 3) $addLink = true;
                if ($l["order_stat"] == -2 and $pd["user_id"] == $UserId) $addLink = false;
                else $addLink = true;
            }
        } // we have d check 4 add link
        if ($addLink) { // awaiting pick, add href to pickit
            $lnk = <<<HTML
<a href="javascript:void(0);" onclick="pickit({$l["host_order_num"]});">
HTML;
            $lnke = "</a>";
            $lnk = "";
            $lnke = "";
            // check if priority
            //<td>{$l["priority"]}</td>
            // end check if priority
        } // awaiting pick, add href to pickit
        $j1++;
        $cb = "";
        $trc = "";
//<input type="hidden" name="hostOrd[]" value="{$l["host_order_num"]}">
        if ($addLink) {
            $cb = <<<HTML
            <td><input type="checkbox" id="c{$j1}" name="order[]" onclick="setChk('c{$j1}');" value="{$l["order_num"]}">
</td>
HTML;
            $trc = " onclick=\"setChk('c{$j1}');\"";
        }
        $$w .= <<<HTML
           <tr {$cls} height="30px" {$trc}>
{$cb}
            <td>{$lnk}{$l["host_order_num"]}{$lnke}</td>
            <td>{$l["customer_id"]}</td>
            <td align="center">{$l["priority"]}</td>
            <td align="center">{$l["ship_via"]}</td>
            <td>{$l["cust_po_num"]}</td>
            <td align="center">{$l["lines"]}</td>
            <td align="center">{$l["zones"]}</td>
           </tr>

HTML;
    } // end foreach rowData

    if ($open == "") $h1 = "";
    else $h1 = <<<HTML
           <tr>
            <td colspan="8" class="FormSubHeaderFont">Awaiting Pick</td>
           </tr>

HTML;
    if ($inproc == "") $h2 = "";
    else $h2 = <<<HTML
           <tr>
            <td colspan="8" class="FormSubHeaderFont">Being Picked</td>
           </tr>

HTML;
    $h3 = <<<HTML
           <tr>
            <td colspan="8" class="FormSubHeaderFont">In Packing</td>
           </tr>

HTML;
    if ($packing == "") $h3 = "";
    $h4 = <<<HTML
           <tr>
            <td colspan="8" class="FormSubHeaderFont">In Shipping</td>
           </tr>

HTML;
    if ($shipping == "") $h4 = "";
    $h5 = <<<HTML
           <tr>
            <td colspan="8" class="FormSubHeaderFont">Awaiting Product</td>
           </tr>

HTML;
    if ($other == "" and $notrel == "" and $needprod == "") $h5 = "";
    $formAction = "get";
    $lgButton = <<<HTML
<button class="binbutton-tiny" id="letsGo" onclick="document.form1.submit();" disabled>Lets Go</button>
HTML;
    $hiddens = <<<HTML
  <input type="hidden" name="nh" id="nh" value="{$nh}">
HTML;
} // end we have rowdata
else { // no rowData
    $nextprogram = "{$top}/webmenu.php";
    $formAction = "";
    $lgButton = <<<HTML
<input type="hidden" name="menu_num" value="{$_SESSION["wms"]["last_menu"]}">
<button class="binbutton-tiny" onclick="document.form1.submit();">Menu</button>
HTML;
    $msg = <<<HTML
<div class="row">
   <div class="col-75">
       <span style="word-wrap: normal; font-weight: bold; font-size: large; text-align: center;">No Records Found to Pick</span>
   </div>
  </div>

HTML;
    $h1 = "";
    $open = "";
    $h2 = "";
    $inproc = "";
    $h3 = "";
    $packing = "";
    $h4 = "";
    $shipping = "";
    $h5 = "";
    $needprod = "";
    $notrel = "";
    $other = "";
}  // no rowData

$addZones = "";
if (isset($opt[26]) and $opt[26] == "N") $zbutton = "&nbsp;";
else {
    $zbutton = <<<HTML
<button class="zoneButton" onclick="changeZone();">Change Zone</button>
HTML;
    $addZones = <<<HTML
  <div class="row">
   <div class="col-25">
    <span class="FormSubHeaderFont">{$myZones}</span>
   </div>
   <div class="col-10">
{$zbutton}
   </div>
  </div>

HTML;
}


$htm .= <<<HTML
<div class="container {color}">
 <form name="form1" action="$nextprogram" method="{$formAction}">
  <input type="hidden" name="nh" id="nh" value="{$nh}">
  <input type="hidden" name="ret" id="ret" value="{$thisprogram}">
  {$addZones}
  {$msg}

 {$h1}{$open}
  <tr>
  <td align="center" colspan="8"> 
</tr>
  <tr>
  <td align="center" colspan="8"> 
{$lgButton}
</td>

 {$h2}{$inproc}
 {$h3}{$packing}
 {$h4}{$shipping}
 {$h5}{$needprod}
 {$notrel}
 {$other}
</table>
  </form>
</div>
<script>
function setChk(chkbox)
{
 var checkbox = document.getElementById(chkbox);
 checkbox.checked = !checkbox.checked;
 if (checkbox.checked == 1) document.getElementById('letsGo').disabled=false;
}

function pickit(ordNum)
{
 if ( window !== window.parent )
 {
 parent.loadPage("pickOrder1.php?nh={$nh}&fPQ=1&func=enterOrd&scaninput=" + ordNum);
 return(false);
 }
 else
 window.location.href="pickOrder1.php?nh={$nh}&fPQ=1&func=enterOrd&scaninput=" + ordNum;
}
function changeZone()
{
 var url="selectZone.php";
 document.form1.action=url;
 document.form1.submit();
 //window.location.href=url;
}
</script>
</body>
</html>
HTML;

//Display Header
$pg = new displayRF;
$pg->viewport = "1.0";
$pg->dispLogo = false;
if (isset($ord["host_order_num"])) $title = "Order {$ord["host_order_num"]}";
else $title = "Picking Queue";
if (isset($title)) $pg->title = $title;
if (isset($color)) $pg->color = $color; else $color = "blue";
$pg->jsh = <<<HTML
<style>
.zoneButton {
  background-color: #4db8ff;
  border: none;
  color: white;
  padding: 2px;
  text-align: center;
  text-decoration: none;
  display: inline-block;
  font-size: 12px;
  font-weight: bold;
  margin: 0px 2px;
  border-radius: 10px;
}

</style>

HTML;
$ejs = "";
if (isset($nh) and $nh > 0) {
    $pg->noHeader = true;
}
$pg->Display();
echo $htm;
//echo "<pre>";
//print_r($rowData);

