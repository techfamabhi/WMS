<?php

// toteContent.php -- move pallet to new area
// 07/06/22 dse initial
/*TODO
Need method to go back to menu


*/

//echo "<pre>";
//print_r($_REQUEST);
//echo "</pre>";
if (isset($_REQUEST["B2"]) and $_REQUEST["B2"] == "cancel") {
} // end b2 is set

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
require_once("{$wmsInclude}/cl_rf1.php");
require_once("{$wmsInclude}/cl_template.php");
require_once("{$wmsInclude}/cl_PARTS2.php");
require_once("{$wmsInclude}/db_main.php");
require_once("{$wmsInclude}/date_functions.php");
require_once("{$wmsInclude}/get_option.php");
require_once("../include/restSrv.php");

$output = ""; // temp debug variable

$RESTSRV = "http://{$wmsIp}{$wmsServer}/RcptLine.php";
$PARTSRV = "http://{$wmsIp}{$wmsServer}/whse_srv.php";
$comp = $wmsDefComp;
$db = new WMS_DB;
$opt = array();

// Application Specific Variables -------------------------------------
$temPlate = "generic1";
$title = "Pallet/Tote Move";
$panelTitle = "Move Pallet/Tote #";
//$SRVPHP="{$wmsServer}/COMPANY_srv.php";
//$DRPSRV="{$wmsServer}/dropdowns.php";
// end application specific variables ---------------------------------

$opt[102] = get_option($db, $comp, 102);
$opt[103] = get_option($db, $comp, 103);
$packZones = getPackZones($db, $comp);
//echo "<pre>";
//print_r($packZones);
//echo "</pre>";


if (isset($fPQ) and $fPQ > 0 and isset($B2)) {
    // redirect to Pick Queue screen
    $htm = <<<HTML
 <html>
 <head>
 <script>
 window.location.href="pickQue.php?nh={$nh}";
 </script>
 </head>
 <body>
 </body>
</html>

HTML;
    echo $htm;
    exit;
}
if (!isset($func)) $func = "scanScreen";
if (!isset($msg)) $msg = "";
if ($func == "palletToMove" and $toteId == "" and $B1 == "submit") $func = "scanScreen";
if ($func == "movingPallet" and $newLoc == "" and $B1 == "submit") $func = "palletToMove";

if ($func == "donePressed" and isset($B2) and $B2 == "cancel") $func = "scanScreen";
switch ($func) {
    case "scanScreen":
    { // Display Scan Tote screen
        if (isset($msg)) $msg = "";
        if (isset($msgCancel)) $msg = $msgCancel;
        $color = "light-blue";
        if ($msg <> "") $color = "green";
        $mainSection = entOrderTote($msg, $color);
        break;
    } // End Display Scan Tote screen

    case "packit":
    {

        break;
    } // end packit

    case "palletToMove":
    {
        if (isset($toteId) and $toteId <> "") { // a tote or pallet was scanned , diplay tote info
            $title = $panelTitle . $toteId;
            $req = array("action" => "getTote",
                "company" => $comp,
                "tote_id" => $toteId,
                "operation" => "MOV"
            );
            $ret = restSrv($RESTSRV, $req);
            $w = (json_decode($ret, true));
            $req = array("action" => "chkTask",
                "company" => $comp,
                "tote_id" => $toteId
            );
            $ret1 = restSrv($RESTSRV, $req);
            $task = (json_decode($ret, true));
            if (count($task > 0)
            {
                //check if tote is moving by the same user
   echo "<pre>";
            print_r($task);
        } // end count task > 0

            if (isset($w[1])) { // display Order and Tote Info
                $target_zone = "";
                $target_aisle = "";
                $req = array("action" => "getToteLoc",
                    "company" => $comp,
                    "tote_id" => $toteId
                );
                $ret = restSrv($RESTSRV, $req);
                $y = (json_decode($ret, true));
                if (isset($y[1])) foreach ($y as $idx => $target) {

                    $last_zone = "";
                    $last_loc = "";
                    if (isset($target["target_zone"])) $target_zone = att($target_zone, $target["target_zone"]);
                    if (isset($target["target_aisle"])) $target_aisle = att($target_aisle, $target["target_aisle"]);
                    if (isset($target["last_zone"])) $last_zone = att($last_zone, $target["last_zone"]);
                    if (isset($target["last_location"])) $last_loc = att($last_location, $target["last_location"]);
                } // end foreach y
                $num_items = "";
                $totalQty = "";
                if (isset($w[1]["num_items"])) $num_items = $w[1]["num_items"];
                if (isset($w[1]["totalQty"])) $totalQty = $w[1]["totalQty"];
                $req = array("action" => "addTask",
                    "company" => $comp,
                    "tote_id" => $toteId,
                    "operation" => "MOV"
                );

                $hiddens = <<<HTML
<form name="form1" action="toteContent.php" method="get">
  <input type="hidden" name="func" id="func" value="movingPallet">
  <input type="hidden" name="nh" value="0">
  <input type="hidden" name="toteId" value="{$toteId}">
  <input type="hidden" name="target_zone" value="{$target_zone}">
  <input type="hidden" name="target_aisle" value="{$target_aisle}">
  <input type="hidden" name="num_items" value="{$num_items}">
  <input type="hidden" name="totalQty" value="{$totalQty}">

HTML;
                $color = "green";
                $fieldPrompt = "Scan New Location";
                $fieldPlaceHolder = "Scan New Pallet/Tote Location";
                $fieldId = "new_Loc";
                $fieldTitle = " title=\"Scan New Pallet/Tote Location\"";
                $msg = "";
                if ($last_zone <> "") $msg = "Last Zone: {$last_zone}";
                if ($last_loc <> "") $msg = "   Last Location: {$last_loc}";
                $msg2 = "Move Pallet/Tote {$toteId} -";
                if (trim($target_zone) <> "") $msg2 .= " Target Zone: {$target_zone}";
                if (trim($target_aisle) <> "") $msg2 .= " Target Aisle: {$target_aisle}";
                $msg2 .= " Items: {$num_items} Qty: {$totalQty}";

                $buttons = setStdButtons("C");
                $data = array("formName" => "form1",
                    "formAction" => $thisprogram,
                    "hiddens" => $hiddens,
                    "color" => "w3-{$color}",
                    "onChange" => "do_submit();",
                    "fieldType" => "text",
                    "fieldValue" => "",
                    "fieldPrompt" => $fieldPrompt,
                    "fieldPlaceHolder" => $fieldPlaceHolder,
                    "fieldName" => "newLoc",
                    "fieldId" => $fieldId,
                    "fieldTitle" => $fieldTitle,
                    "msg" => $msg,
                    "msg2" => $msg2,
                    "buttons" => $buttons,
                    "function" => ""
                );
                $mainSection = frmtScreen($data, $thisprogram, "generic2");
                break;
            } // display Order and Tote Info
            else { // tote not found
                $color = "red";
                $msg = "Pallet/Tote Not Found";
                $mainSection = entOrderTote($msg, $color);

            } // end tote not found
        } // end Display Tote
        break;
    } // end palletToMove
    case "movingPallet":
    {
        $title = $panelTitle . $toteId;
        $req = array("action" => "getNewLoc",
            "company" => $comp,
            "tote_id" => $toteId,
            "newLoc" => $newLoc
        );
        $ret = restSrv($RESTSRV, $req);
        $w = (json_decode($ret, true));
        if (count($w)) {

            echo "<pre>";
            print_r($w);
        } // end count w
        break;
    } // end moveingPallet

    case "showDetail":
    {
        if (isset($detailTote) and $detailTote <> "") { // get info for tote
            $req = array("action" => "getToteDetail",
                "tote_id" => $detailTote,
                "order_num" => $orderFound
            );
        } // end get by order num or tote id
        $ret = restSrv($RESTSRV, $req);
        $w = (json_decode($ret, true));
        $cls = "";
        if (isset($w["Order"]["last_zone"])) $lz = $w["Order"]["last_zone"]; else $lz = "";
        $sc = "";
        if (isset($scanned[0]) and $scanned[0] <> "") $scaninput = $scanned[0]; else $scaninput = $hostordernum;
        $menuSubmit = "Close";
        $mainSection = <<<HTML
  <form name="form1" action="toteContent.php" method="get">
  <input type="hidden" name="func" id="func" value="palletToMove">
{$sc}
  <input type="hidden" name="nh" value="0">
  <input type="hidden" name="scanTote" value="">
  <input type="hidden" name="scaninput" value="{$scaninput}">
  <input type="hidden" name="orderFound" value="{$orderFound}">
  <input type="hidden" name="hostordernum" value="{$hostordernum}">
HTML;
        $mainSection .= toteDtlTable($w["Tote"], $cls, "Tote {$detailTote} Contents", $lz, true);
        $mainSection .= <<<HTML
<button class="binbutton-small" id="B1" name="B1" onclick="document.form1.submit();">Close</button>

  </form>
HTML;
//echo "<pre>";
//print_r($w);
        break;
    } // end showDetail

    case "scanVerify":
    {
        break;
    } // end scanVerify
    case "scanVerDone":
    {
        break;
    } // end scanVerDone

    case "verifyTote":
    {
    } // verifyTote

} // end switch func

$pg = new displayRF;
$pg->viewport = "1.0";
$pg->dispLogo = false;
$pg->Bootstrap = true;
if (isset($menuSubmit) and $menuSubmit <> "") {
    $pg->addMenuLink("javascript:do_pack('packit');", "{$menuSubmit}");
}

if (isset($title)) $pg->title = $title;
if (isset($color)) $pg->color = $color; else $color = "light-blue";
$ejs = "";
if (isset($nh) and $nh > 0) {
    $pg->noHeader = true;
}

if (!isset($otherScripts)) $otherScripts = "";
$pg->jsh = <<<HTML
<script>
function do_pack(arg)
{
 document.form1.func.value=arg;
 document.form1.submit();
}
</script>
HTML;
if (isset($js)) $pg->jsh .= $js;
$pg->Display();
//Rest of page
$htm = <<<HTML
  {$mainSection}
  {$otherScripts}
 </body>
</html>

HTML;
echo $htm;
echo "<pre>";
echo $output;
//print_r($w);


function frmtScreen($data, $thisprogram, $temPlate = "generic1", $incFunction = true)
{
    $ret = "";
    $parser = new parser;
    $parser->theme("en");
    $parser->config->show = false;
    $ret = $parser->parse($temPlate, $data);
    if ($incFunction) {
        $ret .= <<<HTML
<script>
function do_submit()
{
 document.{$data["formName"]}.submit();
}
</script>
HTML;
    }
    return $ret;

} // end frmtScreen

function entOrderTote($msg, $color = "blue")
{
    global $thisprogram;
    global $nh;
    if ($msg <> "") $color = "red";

    $hiddens = <<<HTML
  <input type="hidden" name="func" id="func" value="palletToMove">
  <input type="hidden" name="nh" value="{$nh}">
  <input type="hidden" name="scanTote" value="">
HTML;
    $fieldPrompt = "Tote or Pallet";
    $fieldPlaceHolder = "Scan Tote/Pallet Id to Move";
    $fieldId = " id=\"toteid\"";
    $msg2 = "Scan Tote/Pallet (Tote, Pallet, Cart, etc) to Move";
    $fieldTitle = " title=\"{$msg2}\"";
    $extra_js = "";
    $buttons = setStdButtons();

    $data = array("formName" => "form1",
        "formAction" => $thisprogram,
        "hiddens" => $hiddens,
        "color" => "w3-{$color}",
        "onChange" => "do_submit();",
        "fieldType" => "text",
        "fieldValue" => "",
        "fieldPrompt" => $fieldPrompt,
        "fieldPlaceHolder" => $fieldPlaceHolder,
        "fieldName" => "toteId",
        "fieldId" => $fieldId,
        "fieldTitle" => $fieldTitle,
        "msg" => $msg,
        "msg2" => $msg2,
        "buttons" => $buttons,
        "function" => ""
    );
    $ret = frmtScreen($data, $thisprogram, "generic2");
    return $ret;
} // end entOrderTote

function dispOrdInfo($w, $inVerify = false)
{
    global $thisprogram;
    global $nh;
    global $scan;
    global $packZones;
    global $output;
    $funchtm = "";
    $conthtm = "";
    if ($w["by"] == 1) { // looked up by order, find totes to make sure they scan them all
        unset($scan);
        $scan = "";
    } // looked up by order, find totes to make sure they scan them all
    if ($w["by"] == 2) { // looked by by tote, find any other totes to make sure they scan them all
    } // looked by by tote, find any other totes to make sure they scan them all
    $x = $w["Order"];
    $B1Prompt = "";
    $hiddens = <<<HTML
  <input type="hidden" name="func" id="func" value="palletToMove">
  <input type="hidden" name="nh" value="{$nh}">
  <input type="hidden" name="scanTote" value="">
  <input type="hidden" name="scanned[]" value="{$scan}">
HTML;
    $dr = eur_to_usa($x["date_required"], false);

    $htm = <<<HTML
   <div class="panel-body">
    <div class="table-responsive">
     <form name="form1" action="{$thisprogram}" method="get">
{$hiddens}
      <input type="hidden" name="orderFound" value="{$x["order_num"]}">
      <input type="hidden" name="hostordernum" value="{$x["host_order_num"]}">
      <input type="hidden" name="detailTote" value="">

HTML;
    $msg = "";
    $ok2Pack = true;
    if (count($w["unPicked"]) > 0) {
        $msg = "Order is still being Picked in other Zones";
        $ok2Pack = false;
    }
    if ($msg <> "")
        $htm .= <<<HTML
<div  style="margin-left:0px;" class="w3-container wms-red"><span style="font-weight: bold; font-size: large; text-align: center;">{$msg}</span></div>

HTML;

    $name = $x["name"];
    $addr = $x["addr1"];
    if (trim($addr) <> "") $addr .= "<br>";
    $addr .= $x["addr2"];
    $city = trim($x["city"]);
    $city .= ", {$x["state"]} {$x["zip"]} {$x["ctry"]}";
    $colStyle = collapseCss();
    $colJs = collapseJs();

    $ordcust = <<<HTML
{$colStyle}
       <div class="collapsible">
        <span class="wmsBold">Order #:</span>
        <span>{$x["host_order_num"]}&nbsp;&nbsp;&nbsp;&nbsp;</span>
        <span class="wmsBold">Customer:</span>
        <span>{$x["customer_id"]}</span>
       </div>
       <div class="content">
        <div class="row">
         <span class="wmsBold">{$name}</span>
        </div>
        <div class="row">
         <span>{$addr}</span>
        </div>
        <div class="row">
         <span>{$city}</span>
        </div>
        <div class="row">
         <span class="wmsBold">PO#:</span>
         <span width="10%">{$x["cust_po_num"]}&nbsp;&nbsp;&nbsp;&nbsp;</span>
         <span class="wmsBold">Date Req:</span>
         <span colspan="1">{$dr}</span>
        </div>
       </div>
      <table role="table" class="rspTable table table-bordered table-striped">
      <thead role="rowgroup">
       <tr role="row">
        <td class="FieldCaptionTD" align="center" width="10%">Zones</td>
        <td class="FieldCaptionTD" align="center" width="10%">Ship Via</td>
        <td class="FieldCaptionTD" align="center" width="10%">Priority</td>
        <td class="FieldCaptionTD" align="center" width="10%">Ship Compl</td>
       </tr>
      </thead>
       <tr role="row">
        <td data-label="Zones" align="center">{$x["zones"]}</td>
        <td data-label="Ship Via" align="center">{$x["ship_via"]}</td>
        <td data-label="Priority" align="center">{$x["priority"]}</td>
        <td data-label="Ship Compl" align="center">{$x["ship_complete"]}</td>
       </tr>
      </table>
  {$colJs}

HTML;

    if ($inVerify) {
        $htm .= <<<HTML
  {$ordcust}
     </form>
    </div>
   </div>
HTML;

        return $ordcust;
    } // end inVerify
    $totehtm = "";
    $addShowDetail = false;
    if (count($w["Totes"]) > 0) {
        $totes = array();
        foreach ($w["Totes"] as $key => $t) {
            $totes[$t["tote_id"]]["last_zone"] = $t["last_zone"];
            $totes[$t["tote_id"]]["last_loc"] = $t["last_loc"];
        } // end foreach w Totes
        if (count($w["LineTote"]) > 0) {
            $i = 0;
            foreach ($w["LineTote"] as $key => $t) {
                $i++;
                $t_id = $t["tote_id"];
                $totes[$t_id]["contents"][$i] = $t;
            } // end foreach LineTote
        } // end count LineTote
        if (count($w["unPicked"]) > 0) {
            $i = 0;
            foreach ($w["unPicked"] as $key => $t) {
                $i++;
                $totes["NP{$i}"]["last_zone"] = $t["zone"];
                $totes["NP{$i}"]["numRows"] = $t["numRows"];
                $totes["NP{$i}"]["last_loc"] = "Not Picked";
            } // end foreach unPicked
        } // end count unPicked > 0
        $conthtm = <<<HTML
         <table class="table table-bordered table-striped">
          <tr>
           <td colspan="5" class="FormSubHeaderFont">Totes and Contents</td>
          </tr>
          <tr>
           <th class="FieldCaptionTD">Zone</th>
           <th class="FieldCaptionTD">Tote Id</th>
           <th class="FieldCaptionTD">Part Number</th>
           <th class="FieldCaptionTD">Qty</th>
           <th class="FieldCaptionTD">UOM</th>
          </tr>

HTML;
        $totehtm = <<<HTML
         <table class="table table-bordered table-striped">
          <tr>
           <td colspan="3" class="FormSubHeaderFont">Totes</td>
          </tr>
          <tr>
           <th class="FieldCaptionTD">Tote Id</th>
           <th class="FieldCaptionTD">&nbsp;</th>
           <th class="FieldCaptionTD">Zone</th>
           <th class="FieldCaptionTD" align="center">Staging Area</th>
          <tr>

HTML;
        $okVerify = false;
        $i = 0;
        $i1 = 0;
        foreach ($totes as $key => $t) { // pass1, see if verify is allowed
            $i++;
            if (isset($packZones[$t["last_loc"]]) and $packZones[$t["last_loc"]]["zone_type"] == "PKG" or $t["last_loc"] == "PACK") $i1++;
        } // pass1, see if verify is allowed
        if ($i == $i1) $okVerify = true;
        foreach ($totes as $key => $t) {
            $cls = "";
            $j = $key;
            if (substr($key, 0, 2) == "NP") {
                $cls = " class=\"wms-red\"";
                $j1 = $t["numRows"];
                $s = "";
                if ($j1 > 1 or $j1 == 0) $s = "s";
                $j = "{$t["numRows"]} Item{$s}";
            }
            $but = "";
            if ($okVerify) {
                $but = <<<HTML
<button name="verify[{$key}]" onclick="scanVerify({$key});" class="btn btn-info btn-xs">Scan Verify</button>
<script>
function scanVerify(tote)
{
 document.form1.func.value="scanVerify";
 document.form1.scanTote.value=tote;
 document.form1.submit();
}
</script>
HTML;
            }
            $totehtm .= <<<HTML
          <tr>
           <td{$cls}>{$j}</td>
           <td{$cls}>{$but}</td>
           <td{$cls} align="center">{$t["last_zone"]}</td>
           <td{$cls} align="center">{$t["last_loc"]}</td>
          </tr>

HTML;
            $i = 0;
            if (isset($t["contents"])) {
                if (count($t["contents"]) > 3) { // too many parts to show on small screen, link to detail
                    $i = count($t["contents"]);
                    $addShowDetail = true;
                    $conthtm .= <<<HTML
          <tr>
           <td colspan="2"{$cls} align="left">{$i} Items</td>
           <td colspan="3" align="left">
<button name="showDtl[{$j}]" onclick="showDetail('{$j}');" class="btn btn-info btn-xs">Show Detail</button>
</td>
          </tr>

HTML;
                } // too many parts to show on small screen, link to detail
                else { // less than 4 parts, show on screen
                    $conthtm .= toteDtlTable($t["contents"], $cls, "Totes and Contents", $t["last_zone"], false);
                } // less than 4 parts, show on screen
            } // end contents is set
        } // end foreach totes

        if ($addShowDetail) {
            $funchtm = <<<HTML
 <script>
 function showDetail(tote)
 {
  if (tote > 0) 
  {
   document.form1.func.value="showDetail";
   document.form1.detailTote.value=tote;
  }
  document.form1.submit();
 }
 </script>
HTML;
        }

        $totehtm .= <<<HTML
         </table>
        </td>
       </tr>
HTML;
        $conthtm .= <<<HTML
         </table>
        </td>
       </tr>
HTML;
    } // end count totes

    $htm .= <<<HTML
<table class="w3-half table table-bordered table-striped">
<tr>
<td>
{$ordcust}
</td>
</tr>
<tr>
<td>
{$totehtm}
</td>
</tr>
<tr>
<td>
{$conthtm}
</tr>
HTML;
    if ($ok2Pack) {
        $B1Prompt = "Pack It";
        $htm .= <<<HTML
<tr>
<td colspan="4">
         <button class="binbutton-small" id="B1" name="B1" onclick="do_pack('packit');">{$B1Prompt}</button>

HTML;
    } // end ok2pack

    $htm .= <<<HTML
         <button class="binbutton-small" id="BV" name="BV" onclick="scanVerify('All');" value="Verify">Scan Verify</button>
         <button class="binbutton-small" id="B2" name="B2" value="cancel">Cancel</button>
       </tr>
      </table>
     </form>
    </div>
   </div>
{$funchtm}
HTML;
    return $htm;

} // end dispOrdInfo

function collapseCss()
{
    $htm = <<<HTML
<style>
.collapsible {
  background-color: #87CEEB!important;
  color: white;
  cursor: pointer;
  padding: 18px;
  width: 100%;
  border: none;
  text-align: left;
  outline: none;
  font-size: 15px;
}

.active, .collapsible:hover {
  background-color: #555;
}

.collapsible:after {
  content: '+';
  color: white;
  font-weight: bold;
  float: right;
  margin-left: 5px;
}

.active:after {
  content: "";
}

.content {
  padding: 0 18px;
  max-height: 0;
  overflow: hidden;
  transition: max-height 0.2s ease-out;
  background-color: #f1f1f1;
}
</style>
HTML;
    return $htm;
} // end CollapseCss
function collapseJs()
{
    $htm = <<<HTML
  <script>
var coll = document.getElementsByClassName("collapsible");
var i;

for (i = 0; i < coll.length; i++) {
  coll[i].addEventListener("click", function() {
    this.classList.toggle("active");
    var content = this.nextElementSibling;
    if (content.style.maxHeight){
      content.style.maxHeight = null;
    } else {
      content.style.maxHeight = content.scrollHeight + "px";
    }
  });
}
</script>

HTML;
    return $htm;
} // end collapseJs

function getPackZones($db, $comp)
{
    $SQL = <<<SQL
 select 
zone,
zone_desc,
display_seq,
is_pickable,
zone_color,
zone_company,
zone_type
from WHSEZONES
where zone_company = {$comp}
and zone_type = "PKG"

SQL;
    $ret = array();
    $rc = $db->query($SQL);
    $numrows = $db->num_rows();
    $i = 1;
    while ($i <= $numrows) {
        $db->next_record();
        if ($numrows and $db->Record) {
            $zone = $db->f("zone");
            foreach ($db->Record as $key => $data) {
                if (!is_numeric($key)) {
                    $ret[$zone]["$key"] = $data;
                }
            }
        }
        $i++;
    } // while i < numrows
    return $ret;
} // end getPackZones
function toteDtlTable($contents, $cls, $title, $last_zone, $fullTable = false)
{
    $ret = "";
    if ($fullTable) {
        $ret = <<<HTML
         <table class="table table-bordered table-striped">
          <tr>
           <td colspan="5" class="FormSubHeaderFont">{$title}</td>
          </tr>
          <tr>
           <th class="FieldCaptionTD">Zone</th>
           <th class="FieldCaptionTD">Tote Id</th>
           <th class="FieldCaptionTD">Part Number</th>
           <th class="FieldCaptionTD">Qty</th>
           <th class="FieldCaptionTD">UOM</th>
          </tr>

HTML;

    } // end fulltable
    if (is_array($contents) and count($contents) > 0)
        foreach ($contents as $l => $c) {
            $ret .= <<<HTML
          <tr>
           <td{$cls} align="center">{$last_zone}</td>
           <td{$cls}>{$c["tote_id"]}</td>
           <td align="left">{$c["p_l"]} {$c["part_number"]}</td>
           <td align="center">{$c["tote_qty"]}</td>
           <td align="center">{$c["tote_uom"]}</td>
          </tr>

HTML;
        } // end foreach contents
    if ($fullTable) $ret .= "         </table>\n";
    return $ret;
} // end toteDtlTable

function att($in, $add)
{ // att - add to target
    $comma = "";
    if (strlen($in) > 0) {
        $comma = ",";
        if (trim($in) == trim($add)) return $in;
        if (strpos($in, "{$add}{$comma}") !== false) return $in;
    }
    return "{$in}{$comma}{$add}";
} // end att

function setStdButtons($DorC = "D")
{
    // args D=Done, C=Cancel
    $w = "done";
    $w1 = "Done";
    if ($DorC == "C") {
        $w = "cancel";
        $w1 = "Cancel";
    }
    $buttons = array(
        0 => array(
            "btn_id" => "b1",
            "btn_name" => "B1",
            "btn_value" => "submit",
            "btn_onclick" => "do_submit();",
            "btn_prompt" => "Submit"
        ),
        1 => array(
            "btn_id" => "b2",
            "btn_name" => "B2",
            "btn_value" => $w,
            "btn_onclick" => "do_done();",
            "btn_prompt" => $w1
        )
    );
    return $buttons;
} // end setStdButtons
?>
