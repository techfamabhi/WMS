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
require("{$wmsDir}/config.php");
require_once("{$wmsInclude}/chklogin.php");
require_once("{$wmsInclude}/cl_rf.php");
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
       <table>
           <tr>
            <td nowrap width="5%" class="FieldCaptionTD">Order Id</td>
            <td width="15%" class="FieldCaptionTD">Status</td>
            <td width="5%" class="FieldCaptionTD">Customer</td>
            <td width="20%" class="FieldCaptionTD">Name</td>
            <td width="10%" class="FieldCaptionTD">PO Number</td>
            <td width="5%" class="FieldCaptionTD">Zones</td>
            <td width="5%" style="text-align:right;padding-right:10px" class="FieldCaptionTD">Lines</td>
            <td width="20%" class="FieldCaptionTD">Type</td>
            <td width="20%" class="FieldCaptionTD">Priority</td>
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
                $cls = " class=\"Alt2DataTD\"";
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
        if ($l["order_stat"] == "1") $addLink = true;
        if (count($d) > 0 and !$addLink) {
            foreach ($d as $pd) {
                if ($pd["user_id"] == $UserId and $l["order_stat"] < 3) $addLink = true;
            }
        } // we have d check 4 add link
        if ($addLink) { // awaiting pick, add href to pickit
            $lnk = <<<HTML
<a href="javascript:void(0);" onclick="pickit({$l["host_order_num"]});">
HTML;
            $lnke = "</a>";
            // check if priority
            //<td>{$l["priority"]}</td>
            // end check if priority
        } // awaiting pick, add href to pickit
        $$w .= <<<HTML
           <tr>
            <td>{$lnk}{$l["host_order_num"]}{$lnke}</td>
            <td{$cls}>{$l["stat_desc"]}</td>
            <td>{$l["customer_id"]}</td>
            <td>{$l["name"]}</td>
            <td>{$l["cust_po_num"]}</td>
            <td align="center">{$l["zones"]}</td>
            <td align="center">{$l["lines"]}</td>
            <td align="center">{$l["order_type"]}</td>
            <td align="center">{$l["priority"]}</td>
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

    $hiddens = <<<HTML
  <input type="hidden" name="nh" id="nh" value="{$nh}">
HTML;
} // end we have rowdata
else { // no rowData
    $msg = <<<HTML
<div class="row">
   <div class="col-75">
       <span style="word-wrap: normal; font-weight: bold; font-size: large; text-align: center;">No Records Found</span>
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

if (isset($opt[26]) and $opt[26] == "N") $zbutton = "&nbsp;";
else $zbutton = <<<HTML
<button class="zoneButton" onclick="changeZone();">Change Zone</button>
HTML;

$htm .= <<<HTML
<div class="container {color}">
 <form name="form1" action="$thisprogram" method="get">
  <input type="hidden" name="nh" id="nh" value="{$nh}">
  <input type="hidden" name="ret" id="ret" value="{$thisprogram}">
  <div class="row">
   <div class="col-25">
   <label class="FormSubHeaderFont" for="orderType">Queue Type</label>
   <select name="orderType" id="orderType" onchange="document.form1.submit();">
   {$op_htm}
   </select>
   </div>
   <div class="col-25">
    <span class="FormSubHeaderFont">{$myZones}</span>
   </div>
   <div class="col-10">
{$zbutton}
   </div>
  </div>
  {$msg}
  </form>

 {$h1}{$open}
 {$h2}{$inproc}
 {$h3}{$packing}
 {$h4}{$shipping}
 {$h5}{$needprod}
 {$notrel}
 {$other}
</table>
</div>
<script>
function pickit(ordNum)
{
 if ( window !== window.parent )
 {
 parent.loadPage("pickOrder.php?nh={$nh}&fPQ=1&func=enterOrd&scaninput=" + ordNum);
 return(false);
 }
 else
 window.location.href="pickOrder.php?nh={$nh}&fPQ=1&func=enterOrd&scaninput=" + ordNum;
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

