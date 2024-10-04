<?php

// stockchk.php -- Stock Check a Pat and retrieve all inventory and location
// 03/03/22 dse initial
// 01/16/24 dse Add on Pick to bin display
// 07/17/24 dse correct display of bins starting with "!"
// 07/30/24 dse remove <strong> and </strong> from page title
/*TODO
display tote info,
do an ITEMPULLSelect from $pr for the shadow
possible move the TOTESElect to the same instead of always getting it in chkPart
*/

session_start();
if (get_cfg_var('wmsdir')) $wmsDir = get_cfg_var('wmsdir');
else {
    echo "<h1>WMS System is not Configured on this System</h1>";
    exit;
}
$top = str_replace("/var/www", "", $wmsDir);

foreach (array_keys($_REQUEST) as $w) {
    $$w = $_REQUEST[$w];
}
error_reporting(0);

$thisprogram = basename($_SERVER["PHP_SELF"]);
require("{$wmsDir}/config.php");
require_once("{$wmsInclude}/chklogin.php");
require_once("{$wmsInclude}/cl_rf2.php");
require_once("{$wmsInclude}/cl_template.php");
require_once("{$wmsInclude}/cl_bins.php");
require_once("{$wmsInclude}/cl_PARTS2.php");
require_once("{$wmsInclude}/db_main.php");
require_once("{$wmsInclude}/restSrv.php");


$PARTSRV = "http://{$wmsIp}{$wmsServer}/whse_srv.php";

if (!isset($invAdj)) $invAdj = "";

// Application Specific Variables -------------------------------------
$comp = $wmsDefComp;
$temPlate = "scanpart";
$title = "Stock Check";
if ($invAdj == "Y") $title = "Stock Check/Stock Adjustment";
$panelTitle = "Part";
//$SRVPHP="{$wmsServer}/COMPANY_srv.php";
//$DRPSRV="{$wmsServer}/dropdowns.php";
// end application specific variables ---------------------------------


if (!isset($msg)) $msg = "";
$msg2 = "";
$msgcolor = "";
$errSound = false;
$color = "blue";
$js = "";
$scanInput = frmtScanInput($color, $thisprogram, $msg2);
if (isset($scaninput) and $scaninput <> "") {
    //process the scan, determine if it is a part, bin or tote
    $scaninput = trim(strtoupper($scaninput));
    $result = procScan($scaninput, $comp);
    if (!isset($result["infoType"])) { // re-ask scaninput
        $msg2 = "Invalid, scan was not a Part, Bin or Tote";
        $color = "red";
        $errSound = true;
        $scanInput = frmtScanInput($color, $thisprogram, $msg2);
    } // re-ask scaninput
    else { // disect info type and display
        $iType = $result["infoType"];
        switch ($iType) {
            case "NF":
                $msg = "Invalid, scan was not a Part, Bin or Tote";
                $color = "red";
                $errSound = true;
                break;
            case "B": // Bin
                $color = "green";
                $db = new WMS_DB;
                $bin = new BIN($db);
                $pnum = trim(strtoupper($scaninput));
                $useWild = "";
                if (strpos($useWild, "%") !== false) $useWild = 1;
                $result["binParts"] = $bin->getLoc($comp, $pnum, $useWild);
                $numParts = $result["binParts"]["numRows"];
                $numBins = $result["numRows"];
                $mainSection = frmtBinInfo($result, $thisprogram);
                $title = <<<HTML
<strong>Bin: {$result["wb_location"]}</strong>
&nbsp;
<button class="btn btn-primary btn-xs" name="srClr" id="srClr" onclick="clearSearch()" title="Stock Check antoher Part Number">Clear</button>
HTML;

                break;
            case "P": // Part
                $color = "green";
                $numParts = $result["status"];
                if ($numParts == 1) { // display part info
                    $shadow = $result["Part"]["shadow_number"];
                    $Totes = array();
                    $ItemPull = array();
                    if (!isset($pr)) {
                        $db = new WMS_DB;
                        $pr = new PARTS;
                    }
                    $Totes = $pr->TOTESelect($shadow, $comp);
                    //$ItemPull=$pr->ITEMPULLSelect($shadow,$comp);
                    $title = <<<HTML
<strong>{$result["Part"]["p_l"]} {$result["Part"]["part_number"]}</strong>
&nbsp;
<button class="btn btn-primary btn-xs" name="srClr" id="srClr" onclick="clearSearch()" title="Stock Check antoher Part Number">Clear</button>
HTML;
                    $xjs = "";
                    $xjs .= <<<HTML
<script src="/jq/shortcut.js" type="text/javascript"></script>
<script>
shortcut.add("return",function() {
  document.getElementById('srClr').click();
});
</script>

HTML;
                    $mainSection = frmtPartInfo($result, $thisprogram);
                } // display part info
                else { // choose
                    $mainSection = frmtChoosePart($result, $thisprogram);
                } // choose

                break;
            case "T": // Tote
                $color = "green";
                $mainSection = frmtToteInfo($result);
                $title = <<<HTML
<strong>Tote: {$result["tote_id"]}</strong>
&nbsp;
<button class="btn btn-primary btn-xs" name="srClr" id="srClr" onclick="clearSearch()" title="Stock Check antoher Part Number">Clear</button>
HTML;

                break;
            default:
                echo "<pre> type {$iType}";
                print_r($result);
                echo "</pre>";
                exit;
                break;

        } // end switch iType
    } // disect info type and display
    $scanInput = frmtScanInput($color, $thisprogram, $msg2);

//echo "<pre> type {$iType}";
//print_r($result);
//echo "</pre>";
//exit;


} // en isset scaninput and not empty
if (1 == 2) {
    if (isset($shadow)) $scaninput = ".{$shadow}";
    $db = new WMS_DB;
    $pr = new PARTS;
    $pnum = trim(strtoupper($scaninput));
    $parts = $pr->chkPart($pnum, 1);
    $numParts = $parts["status"];
    if ($numParts == 1) { // display part info
        $shadow = $parts["Part"]["shadow_number"];
        $Totes = array();
        $ItemPull = array();
        $Totes = $pr->TOTESelect($shadow, $comp);
        //$ItemPull=$pr->ITEMPULLSelect($shadow,$comp);
        $title = <<<HTML
<strong>{$parts["Part"]["p_l"]} {$parts["Part"]["part_number"]}</strong>
&nbsp;
<button class="btn btn-primary btn-xs" name="srClr" id="srClr" onclick="clearSearch()" title="Stock Check antoher Part Number">Clear</button>
HTML;
        $js .= <<<HTML
<script src="/jq/shortcut.js" type="text/javascript"></script>
<script>
shortcut.add("return",function() {
  document.getElementById('srClr').click();
});
</script>

HTML;
        $mainSection = frmtPartInfo($parts, $thisprogram);
        //echo "<pre>";
        //print_r($parts);
        //exit;
    } // display part info
    else if ($numParts > 1) { // parts > 1, display choose
        $title = <<<HTML
<strong>Please Choose</strong>
&nbsp;
<button class="btn btn-primary btn-xs" name="srClr" id="srClr" onclick="clearSearch()" title="Stock Check another Part Number">Clear</button>
HTML;
        $js .= <<<HTML
<script src="/jq/shortcut.js" type="text/javascript"></script>
<script>
shortcut.add("return",function() {
  document.getElementById('srClr').click();
});
</script>

HTML;

        $mainSection = frmtChoosePart($parts, $thisprogram);
        //echo "<pre>";
        //print_r($parts);
        //exit;
    } // parts > 1, display choose
    else if ($numParts < 1) { // part NOT, inform user and re-enter part
        $msg = "Invalid Part: {$pnum} not found";
        $title .= <<<HTML
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<button class="btn btn-primary btn-xs" name="srClr" id="srClr" onclick="do_binl()" title="Lookup Parts by Bin">Lookup by Bin</button>
HTML;
        $hiddens = <<<HTML
  <input type="hidden" name="func" id="func" value="scanPart">
  <input type="hidden" name="invAdj" id="invAdj" value="{$invAdj}">
  <input type="hidden" name="lastfunc" value="">
HTML;
        $data = array("formName" => "form1",
            "formAction" => $thisprogram,
            "hiddens" => $hiddens,
            "color" => "myRed",
            "onChange" => "do_bin();",
            "fieldType" => "text",
            "fieldValue" => "",
            "fieldPrompt" => "Part Number",
            "fieldPlaceHolder" => "Scan or enter Part",
            "fieldName" => "scaninput",
            "fieldId" => " id=\"partnum\"",
            "fieldTitle" => " title=\"Scan or Enter Part Number\""
        );
        $mainSection = frmtPartScan($data, $thisprogram);
        if (isset($playsound) and $playsound > 0) $mainSection .= <<<HTML
<audio controls autoplay hidden>
  <source src="{$wmsAssets}/sounds/psycho.wav" type="audio/wav">
</audio>
HTML;

    } // part NOT, inform user and re-enter part

} else if (2 == 3) { // no scaninput
    $ext_js = "";
    $title .= <<<HTML
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<button class="btn btn-primary btn-xs" name="srClr" id="srClr" onclick="do_binl()" title="Lookup Parts by Bin">Lookup by Bin</button>
HTML;
    $hiddens = <<<HTML
  <input type="hidden" name="func" id="func" value="scanPart">
  <input type="hidden" name="invAdj" id="invAdj" value="{$invAdj}">
  <input type="hidden" name="lastfunc" value="">
HTML;
    $fieldPrompt = "Part Number";
    $fieldPlaceHolder = "Scan or enter Part";
    $fieldId = " id=\"partnum\"";
    $fieldTitle = " title=\"Scan or Enter Part Number\"";
    $extra_js = "";

    $data = array("formName" => "form1",
        "formAction" => $thisprogram,
        "hiddens" => $hiddens,
        "color" => "w3-blue",
        "onChange" => "do_bin();",
        "fieldType" => "text",
        "fieldValue" => "",
        "fieldPrompt" => $fieldPrompt,
        "fieldPlaceHolder" => $fieldPlaceHolder,
        "fieldName" => "scaninput",
        "fieldId" => $fieldId,
        "fieldTitle" => $fieldTitle
    );
    $mainSection = frmtPartScan($data, $thisprogram);
} // no scaninput

//Read the vue app script from the js directory ************
//$conf=array( "extension"=>'js', "theme"=>'js');
//$data=array("SRVPHP"=>"{$SRVPHP}","DRPSRV"=>"{$DRPSRV}");
//$vueAppScript=$parser->parse($temPlate,$data,$conf);
$otherScripts = "";
if (isset($Totes) and count($Totes) > 0) {
    $otherScripts = <<<HTML
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
} // end totes are set and count > 0

//******************************************************

//Display Header
$pg = new displayRF;
$pg->viewport = "1.10";
$pg->dispLogo = false;
$pg->Bootstrap = true;
$t = str_replace("<strong>", "", $title);
$t = str_replace("</strong>", "", $t);
$pg->title = $t;
$js .= <<<HTML
<script>

let popup;

function openalt(url,nlns) 
{
 var ok=false;
 if (typeof popup !== "undefined")
 {
  if (popup.closed !== true) ok=true;
 }
 if (ok)
 {
  popup.location.xref=url;
  popup.focus();
 }
 else
 {
        hgt=210 + (nlns * 25);
        popup=window.open(url,"altpage", "toolbar=no,left=10,top=125,status=1,resizable=1,scrollbars=1,width=350,height=" + hgt );
 }
}
 // function that Closes the open Window
        function windowClose() {
        if (typeof popup !== "undefined") popup.close();
        }

        //function that focus on open Window
        function windowFocus() {
        if (typeof popup !== "undefined") popup.focus();
        }

function closeAdjust(msg)
{
        if (typeof popup !== "undefined") popup.close();
        var theMsg = document.getElementById('adjMsg');
        if (msg !== "")
         {
          theMsg.innerHTML=msg;
          theMsg.style.display="block";
         }
        
        var newHref = location.href + "&msg=" + msg;
        window.location=newHref;
        //location.reload(true);
}

function clearSearch()
{
 document.form1.scaninput.value="";
 windowClose();
 document.form1.submit();
}
</script>
<style>
.myRed
{
color:#fff!important;
background-color:#ff7777!important
}
</style>
HTML;
$pg->jsh = $js;
if ($msg <> "") $pg->msg = $msg;
if ($msgcolor <> "") {
    $pg->color = $msgcolor;
}
$funcs = array(
    0 => array("fkey" => "",
        "prompt" => "Clear",
        "name" => "srClr",
        "onClick" => "clearSearch();",
        "value" => "srClr",
        "title" => "Bin Check another Bin"
    ),
    1 => array("fkey" => "F1",
        "prompt" => "Lookup by Bin",
        "name" => "srClr",
        "onClick" => "do_binl();",
        "value" => "srClr",
        "title" => "Lookup Parts by Bin"
    )
);
//$pg->bldFooter($funcs);

if (!isset($mainSection)) $mainSection = "";
if (isset($playsound) and $playsound > 0 and $errSound) $mainSection .= <<<HTML
<audio controls autoplay hidden>
  <source src="{$wmsAssets}/sounds/psycho.wav" type="audio/wav">
</audio>
HTML;
$pg->body = <<<HTML
  {$scanInput}
  {$mainSection}
  {$otherScripts}
HTML;

//echo "<pre>";
//print_r($_REQUEST);
//echo "</pre>";
$pg->Display();

//echo "<pre><br><br><br><br><br><br>";
//print_r($result);
//Rest of page
$htm = <<<HTML
 </body>
</html>

HTML;
echo $htm;

function frmtPartScan($data, $thisprogram)
{
    global $invAdj;
    $ret = "";
    $temPlate = "scanpart";
    $parser = new parser;
    $parser->theme("en");
    $parser->config->show = false;
    $ret = $parser->parse($temPlate, $data);
    //$ret.=<<<HTML
//<script>
//function do_bin()
//{
    //document.{$data["formName"]}.submit();
//}
//</script>
//HTML;

    return $ret;

} // end frmtPartScan

function frmtPartInfo($part, $thisprogram)
{
    global $comp;
    global $Totes;
    global $invAdj;
    global $ItemPull;
    global $msg;
    $color = "green";
    $i = $part["comp"];
    $hiddens = <<<HTML
HTML;
    $detail = "";
    $qa = 0;
    $qal = 0;
    if (count($part["WhseQty"]) > 0) {
        $qa = $part["WhseQty"][$i]["qty_avail"];
        $qal = $part["WhseQty"][$i]["qty_alloc"];
    } // end count whseqty > 0

    $adisp = 'style="display:none"';
    if (isset($msg) and $msg <> "") $adisp = "";
    $htm = <<<HTML
 <div class="w3-half wms-red" id="adjMsg" {$adisp}">&nbsp;&nbsp;&nbsp;{$msg}</div>
 <div class="w3-half">
        <div class="panel-body">
         <div class="table-responsive">
          <table class="table table-bordered table-striped">
           <tr>
            <td colspan="3" class="FormSubHeaderFont">{$part["Part"]["p_l"]} {$part["Part"]["part_number"]}</td>
           </tr>
           <tr>
            <td>
             <table width="60%">
              <tr>
               <tr>
                <td class="FieldCaptionTD" align="left">Description</td>
                <td class="FieldCaptionTD" align="right">Avail</td>
                <td class="FieldCaptionTD" align="right">On Pick</td>
               </tr>
               <tr>
                <td>{$part["Part"]["part_desc"]}</td>
                <td align="right">{$qa}</td>
                <td align="right">{$qal}</td>
               </tr>
              </tr>
             </table>
            </td>
           </tr>
           <tr>
            <td>
_DETAIL_ 
            </td>
           </tr>
          </table>
_EXTRA_
         </div>
        </div>
      <br>
</div>

HTML;
    $detail = <<<HTML
           <tr>
            <td colspan="3" class="btn-warning"><strong>There are no Bins assigned to this Part</strong></td>
           </tr>
HTML;
    //if (count($part["WhseLoc"]) > 0)
    if (1 == 1) {
        $ihdr = <<<HTML
           <tr>
HTML;
        $ihtm = "";
        if ($invAdj == "Y") {
            $ihdr = <<<HTML
           <tr>
            <td width="5%" class="FieldCaptionTD">Adjust</td>

HTML;
        }
        $detail = <<<HTML
          <table width="100%" class="table table-bordered table-striped">
           <tr>
{$ihdr}
            <td class="FieldCaptionTD">Bin</td>
            <td width="8%" align="right" class="FieldCaptionTD">Qty</td>
            <td width="8%" align="right" class="FieldCaptionTD">On Pick</td>
            <td class="FieldCaptionTD">UOM</td>
           </tr>

HTML;
        foreach ($part["WhseLoc"] as $rec => $l) {
            $tdt = "";
            $btype = $l["whs_code"];
            if ($btype == "P") {
                $btype = "*&nbsp;";
                $tdt = " title=\"This is the Primary Bin\"";
            } else $btype = "&nbsp;&nbsp;";
            $theBin = $l["whs_location"];
            $theQty = $l["whs_qty"];
            $ihtm = "<tr>";
            if (substr($theBin, 0, 1) == "!") $theBin = "Tote: " . substr($theBin, 1);
            else { // not a tote
                if ($invAdj == "Y") {
                    $args = "?shadow={$l["whs_shadow"]}&comp={$comp}&bin={$theBin}&part={$part["Part"]["p_l"]} {$part["Part"]["part_number"]}&binQty={$theQty}";
                    $lnk = "openalt('invAdjust.php" . $args . "',10);";
                    $ihtm = <<<HTML
           <tr>
<td><button class="btn btn-primary btn-xs" name="adjust" id="adjust" onclick="{$lnk}" title="Adjust Inventory">Adjust</button></td>

HTML;
                } // end invAdj = Y #2
            } // not a tote
            $detail .= <<<HTML
           <tr>
            {$ihtm}
            <td{$tdt}>{$btype}{$theBin}</td>
            <td align="right">{$l["whs_qty"]}</td>
            <td align="right">{$l["whs_alloc"]}</td>
            <td>{$l["whs_uom"]}</td>
           </tr>

HTML;

        } // end foreach whseLoc
        if ($invAdj == "Y") {
            $args = "?shadow={$part["Part"]["shadow_number"]}&comp={$comp}&bin=&part={$part["Part"]["p_l"]} {$part["Part"]["part_number"]}&binQty={$part["WhseQty"][$comp]["qty_avail"]}";
            $lnk = "openalt('invAdjust.php" . $args . "',10);";
            $lnk1 = "openalt('setPrimary.php" . $args . "',10);";
            $detail .= <<<HTML
           <tr>
            <td colspan="4">
             <button class="btn btn-primary btn-xs" name="adjust" id="adjust" onclick="{$lnk}" title="Adjust Inventory">Adjust Other Bin</button>
            </td>
           </tr>
HTML;
            $detail .= <<<HTML
           <tr>
            <td colspan="4">
             <button class="btn btn-primary btn-xs" name="setPrimary" id="setPrimary" onclick="{$lnk1}" title="Set Primary Bin">Set Primary Bin</button>
            </td>
           </tr>
HTML;
        } // end invAdj #3
        $detail .= "          </table>\n";
    } // end count whseLoc > 0
    $htm = str_replace("_DETAIL_", $detail, $htm);
    $EXTRA = "";
    if (count($Totes) > 0) {
        $EXTRA .= <<<HTML
        <div class="collapsible">
        <span class="FormSubHeaderFont">Totes</span>
        </div>
         <div class="content">
          <table class="table table-bordered table-striped">
           <tr>
            <td class="FieldCaptionTD">Tote</td>
            <td class="FieldCaptionTD">Type</td>
            <td class="FieldCaptionTD">Ref</td>
            <td class="FieldCaptionTD">Location</td>
            <td align="right" class="FieldCaptionTD">Qty</td>
            <td class="FieldCaptionTD">UOM</td>
           </tr>

HTML;
        $tqty = 0;
        foreach ($Totes as $t => $d) {
            if (isset($d["HostId"])) $x = $d["HostId"]; else $x = $d["tote_ref"];
            if (isset($d["host_ref"])) $x = $d["host_ref"];

            $EXTRA .= <<<HTML
           <tr>
            <td>{$t}</td>
            <td>{$d["tote_type"]}</td>
            <td>{$x}</td>
            <td align="center">{$d["tote_location"]}</td>
            <td align="right">{$d["tote_qty"]}</td>
            <td>{$d["tote_uom"]}</td>
           </tr>

HTML;
            $tqty = $tqty + $d["tote_qty"];
        } // end foreach Totes
        $EXTRA .= <<<HTML
           <tr>
            <td colspan="3" class="AltDataTD" align="right"><strong>Total</strong></td>
            <td colspan="2" class="AltDataTD" align="right"><strong>{$tqty}</strong></td>
            <td class="AltDataTD">&nbsp;</td>
           </tr>
          </table>
         </div>

HTML;
    } // end count Totes
    if (count($ItemPull) > 0) {
        $EXTRA .= <<<HTML
      <br>
      <div class="collapsible">
        <span class="FormSubHeaderFont">Pick Tickets</span>
        </div>
         <div class="content">
          <table class="table table-bordered table-striped">
           <tr>
            <td class="FieldCaptionTD">Order</td>
            <td class="FieldCaptionTD">Zone</td>
            <td class="FieldCaptionTD">Location</td>
            <td align="right" class="FieldCaptionTD">Qty</td>
            <td align="right" class="FieldCaptionTD">Picked</td>
            <td align="right" class="FieldCaptionTD">Verified</td>
           </tr>

HTML;
        $pqty = 0;
        $tqty = 0;
        $vqty = 0;
        foreach ($ItemPull as $t => $d) {
            $EXTRA .= <<<HTML
           <tr>
            <td>{$d["host_order_num"]}</td>
            <td>{$d["zone"]}</td>
            <td align="center">{$d["whse_loc"]}</td>
            <td align="right">{$d["qtytopick"]}</td>
            <td align="right">{$d["qty_picked"]}</td>
            <td align="right">{$d["qty_verified"]}</td>
           </tr>

HTML;
            $pqty = $pqty + $d["qtytopick"];
            $tqty = $tqty + $d["qty_picked"];
            $vqty = $vqty + $d["qty_verified"];
        } // end foreach ItemPull
        $EXTRA .= <<<HTML
           <tr>
            <td colspan="3" class="AltDataTD" align="right"><strong>Total</strong></td>
            <td class="AltDataTD" align="right"><strong>{$pqty}</strong></td>
            <td class="AltDataTD" align="right"><strong>{$tqty}</strong></td>
            <td class="AltDataTD" align="right"><strong>{$vqty}</strong></td>
           </tr>
          </table>
         </div>

HTML;
    } // end count ItemPull
    $htm = str_replace("_EXTRA_", $EXTRA, $htm);
    return $htm;
} // end frmtPartInfo
function frmtChoosePart($part, $thisprogram)
{
    global $invAdj;
    $hiddens = "";
    $cnt = $part["numRows"];
    $upc = $part["upc"];
    $detail = "";
    $htm = <<<HTML
 <div class="w3-clear"></div>
  <div class="w3-half">
   <div class="panel panel-default">
    <div class="panel-heading">
     <div class="row">
      <div class="col-md-6">
       <h3 class="panel-title">Found {$cnt} Parts matching "{$upc}"</h3>
      </div>
     </div>
    </div>
    <div class="panel-body">
     <div class="table-responsive">
      <form name="form2" action="{$thisprogram}" method="get">
      <input type="hidden" name="func" value="choosePart">
      <input type="hidden" name="invAdj" id="invAdj" value="{$invAdj}">
      <input type="hidden" name="upc" value="{$upc}">
      <input type="hidden" name="comp" value="{$part["comp"]}">
      <input type="hidden" name="scaninput" value="{$upc}">
      <input type="hidden" name="shadow" value="">
      <table class="table table-bordered table-striped">
       <tr>
        <td width="1%" class="FieldCaptionTD">&nbsp;</td>
        <td width="3%" class="FieldCaptionTD">P/L</td>
        <td width="10%" class="FieldCaptionTD">Part Number</td>
        <td width="25%" class="FieldCaptionTD">Part Desc</td>
        <td align="right" width="10%" class="FieldCaptionTD">Avail</td>
       </tr>
_DETAIL_
      </table>
      </form>
     </div>
    </div>
   </div>
  </div>
 </div>
<br />
<script>
function do_sel(shadow)
{
 document.form2.scaninput.value='.' + shadow;
 windowClose();
 document.form2.submit();
}
function clearSearch()
{
 document.form1.scaninput.value="";
 windowClose();
 document.form1.submit();
}
</script>
HTML;
    if (count($part["choose"]) > 0) {
        foreach ($part["choose"] as $rec => $l) {
            $cls = "";
            $t = "Click here to select Part Number: {$l["p_l"]} {$l["part_number"]}";
            if ($l["qty_avail"] > 0) $cls = " class=\"bg-success\"";
            $detail .= <<<HTML
       <tr onclick="do_sel({$l["shadow_number"]});">
        <td title="{$t}"><input type="checkbox" name="UPCs[]" value="{$l["shadow_number"]}"></td>
        <td>{$l["p_l"]}</td>
        <td>{$l["part_number"]}</td>
        <td>{$l["part_desc"]}</td>
        <td align="right"{$cls}>{$l["qty_avail"]}</td>
       </tr>

HTML;
        } // end foreach part
    } // end choose count > 0
    $htm = str_replace("_DETAIL_", $detail, $htm);
    return $htm;
} // end frmtChoosePart

function procScan($searchStr, $comp)
{
    global $invAdj;
    global $PARTSRV;

    $req = array("action" => "findIt",
        "comp" => $comp,
        "Search" => $searchStr
    );
    $ret = restSrv($PARTSRV, $req);
    $result = (json_decode($ret, true));
    return $result;
} // end procScan

function frmtScanInput($color, $thisprogram, $msg = "")
{
    global $invAdj;
    $msgh = "";
    if ($msg <> "") {
        $msgh = <<<HTML
<div  style="margin-left:0px;" class="w3-container wms-red"><span style="font-weight: bold; font-size: large; text-align: center;">{$msg}</span></div>
HTML;
    }
    $htm = <<<HTML
{$msgh}
 <form name="form1" action="{$thisprogram}" method="get">
  <input type="hidden" name="func" id="func" value="scanPart">
  <input type="hidden" name="invAdj" id="invAdj" value="{$invAdj}">
  <input type="hidden" name="lastfunc" value="">
  <div class="w3-row-padding w3-margin-bottom">
   <div class="w3-half">
    <div class="w3-container w3-{$color}">
     <span class="w3-w3-{$color}"><br></span>
     <div class="w3-clear"></div>
      <label class="wmslabel" for="scaninput" style="vertical-align: top;" >Scan</label>
      <input type="text" class="w3-white" onchange="do_bin();" value="" name="scaninput" placeholder="Scan Part, Bin or Tote" id="partnum" title="Scan or Enter Part, Bin or Tote">
     </div>
    </div>
   </div>
 </form>

<script>
 document.form1.scaninput.focus();

function do_bin()
{
 windowClose();
 document.form1.submit();
}
</script>

HTML;
    return $htm;
} // end frmtScanInput

function frmtBinInfo($data, $thisprogram)
{
    global $invAdj;
    $bin = $data;
    $parts = $data["binParts"];
    unset($bin["binParts"]);
    $color = "green";
    $hiddens = <<<HTML
HTML;
    $sqft = round((((($bin["wb_depth"] * $bin["wb_width"] * $bin["wb_height"]) / 12) / 12) / 12), 2);
    $zone = $bin["wb_zone"];
    $aisle = $bin["wb_aisle"];
    $section = $bin["wb_section"];
    $level = $bin["wb_level"];
    $subin = $bin["wb_subin"];
    if (trim($zone) == "") $f1 = "&nbsp;"; else $f1 = "Zone";
    if (trim($aisle) == "") $f2 = "&nbsp;"; else $f2 = "Aisle";
    if (trim($section) == "") $f3 = "&nbsp;"; else $f3 = "Section";
    if (trim($level) == "") $f4 = "&nbsp;"; else $f4 = "Level";
    $cls = "";
    if (trim($subin) == 0) {
        $f5 = "";
        $subin = "&nbsp;";
    } else {
        $f5 = "Sub Bin";
        $cls = "class=\"FieldCaptionTD\"";
    }
    if ($aisle == "") $aisle = "&nbsp;";
    if ($section == "") $section = "&nbsp;";
    $htm = <<<HTML
 <div class="w3-half">
        <div class="panel-body">
         <div class="table-responsive">
          <table class="table table-bordered table-striped">
           <tr>
            <td colspan="6" class="FormSubHeaderFont">Bin: {$bin["wb_location"]}</td>
           </tr>
           <tr>
            <td>
             <table width="60%">
              <tr>
               <th style="text-align:center" class="FieldCaptionTD">{$f1}</th>
               <th style="text-align:center" class="FieldCaptionTD">{$f2}</th>
               <th style="text-align:center" class="FieldCaptionTD">{$f3}</th>
               <th style="text-align:center" class="FieldCaptionTD">{$f4}</th>
               <th style="text-align:center" {$cls}>{$f5}</th>
               <th>&nbsp;</th>
               <th class="FieldCaptionTD" style="text-align:right;padding-right:10px">Width</th>
               <th class="FieldCaptionTD" style="text-align:right;padding-right:10px">Depth</th>
               <th class="FieldCaptionTD" style="text-align:right;padding-right:10px">Height</th>
               <th class="FieldCaptionTD" style="text-align:right;padding-right:10px">Volume</th>
               <th class="FieldCaptionTD" style="text-align:right;padding-right:10px">SqFt</th>
              </tr>
              <tr>
               <td style="text-align:center">{$zone}</td>
               <td style="text-align:center">{$aisle}</td>
               <td style="text-align:center">{$section}</td>
               <td style="text-align:center">{$level}</td>
               <td style="text-align:center">{$subin}</td>
               <td>&nbsp;</td>
               <td style="text-align:right;padding-right:10px">{$bin["wb_depth"]}</td>
               <td style="text-align:right;padding-right:10px">{$bin["wb_width"]}</td>
               <td style="text-align:right;padding-right:10px">{$bin["wb_height"]}</td>
               <td style="text-align:right;padding-right:10px">{$bin["wb_volume"]}</td>
               <td style="text-align:right;padding-right:10px">{$sqft}</td>
              </tr>
             </table>
            </td>
           </tr>
          </table>
          <table class="table table-bordered table-striped">
           <tr>
            <td colspan="6" class="FormSubHeaderFont">Parts in this Bin:</td>
           </tr>
_DETAIL_
          </table>
         </div>
        </div>
      <br>
</div>
<script>
function clearSearch()
{
 document.form1.scaninput.value="";
 windowClose();
 document.form1.submit();
}
</script>
HTML;
    $detail = <<<HTML
           <tr>
            <td colspan="6" class="btn-warning"><strong>There are no Parts assigned to this Bin</strong></td>
           </tr>
HTML;
    if (count($parts) > 0) {
        $detail = <<<HTML
           <tr>
            <td width="3%" class="FieldCaptionTD">P/L</td>
            <td width="12%" class="FieldCaptionTD">Part Number</td>
            <td width="5%" style="text-align:right;padding-right:10px" class="FieldCaptionTD">Qty</td>
            <td width="3%" class="FieldCaptionTD">UOM</td>
            <td width="20%" class="FieldCaptionTD">Description</td>
            <td width="3%" class="FieldCaptionTD">Type</td>
           </tr>

HTML;
        $p = array();
        if (isset($parts[2])) $p = $parts; else $p[1] = $parts;
        $displayed = 0;
        foreach ($p as $rec => $l) {
            if (isset($l["whs_shadow"]) and $l["whs_shadow"] > 0) {
                $displayed++;
                $detail .= <<<HTML
           <tr>
            <td>{$l["p_l"]}</td>
            <td>{$l["part_number"]}</td>
            <td style="text-align:right;padding-right:10px">{$l["whs_qty"]}</td>
            <td>{$l["whs_uom"]}</td>
            <td>{$l["part_desc"]}</td>
            <td>{$l["whs_code"]}</td>
           </tr>
HTML;
            } // end shadow > 0
        } // end foreach p
    } // end count parts > 0
    if ($displayed < 1) {
        $detail = <<<HTML
           <tr>
            <td colspan="6" class="FormSubHeaderFont">There are no Parts assigned to this bin</td>
           </tr>
HTML;
    }
    $htm = str_replace("_DETAIL_", $detail, $htm);
    return $htm;

} // end frmtBinInfo

function frmtToteInfo($data)
{
    global $invAdj;
    $usedfor = $data["tote_type"];
    $doc = $data["tote_ref"];
    if (isset($data["HostId"])) $doc = $data["HostId"];
    if (isset($data["host_ref"])) $doc = $data["host_ref"];
    if ($usedfor == "") $usedfor = "-";
    if (trim($doc) == "0") $doc = "-";
    $cls = "class=\"FieldCaptionTD\"";
    $htm = <<<HTML
 <div class="w3-half">
        <div class="panel-body">
         <div class="table-responsive">
          <table class="table table-bordered table-striped">
           <tr>
            <td colspan="6" class="FormSubHeaderFont">Tote: {$data["tote_id"]}</td>
           </tr>
           <tr>
            <td>
             <table width="60%">
              <tr>
               <th style="text-align:center" {$cls}>Use</th>
               <th style="text-align:center" {$cls}>Document#</th>
               <th style="text-align:center" class="FieldCaptionTD">Last Loc</th>
               <th style="text-align:center" class="FieldCaptionTD">Last Used</th>
              </tr>
              <tr>
               <td style="text-align:center">{$usedfor}</td>
               <td style="text-align:center">{$doc}</td>
               <td style="text-align:center">{$data["tote_location"]}</td>
               <td style="text-align:center">{$data["tote_lastused"]}</td>
              </tr>
             </table>
            </td>
           </tr>
          </table>
          <table class="table table-bordered table-striped">
           <tr>
            <td colspan="6" class="FormSubHeaderFont">Parts in this Tote:</td>
           </tr>
_DETAIL_
          </table>
         </div>
        </div>
      <br>
</div>
HTML;
    $detail = <<<HTML
           <tr>
            <td colspan="6" class="btn-warning"><strong>There are no Parts assigned to this Tote</strong></td>
           </tr>
HTML;
    if (count($data["toteDtl"]) > 0) {
        $detail = <<<HTML
           <tr>
            <td class="FieldCaptionTD">P/L</td>
            <td class="FieldCaptionTD">Part Number</td>
            <td style="text-align:right;padding-right:10px" class="FieldCaptionTD">Qty</td>
            <td class="FieldCaptionTD">UOM</td>
            <td class="FieldCaptionTD">Description</td>
            <td class="FieldCaptionTD">Bin</td>
           </tr>

HTML;
        foreach ($data["toteDtl"] as $rec => $l) {
            if (is_numeric($rec)) {
                $detail .= <<<HTML
           <tr>
            <td nowrap>{$l["p_l"]}</td>
            <td nowrap>{$l["part_number"]}</td>
            <td nowrap style="text-align:right;padding-right:10px">{$l["tote_qty"]}</td>
            <td nowrap>{$l["tote_uom"]}</td>
            <td nowrap>{$l["part_desc"]}</td>
            <td nowrap>{$l["primary_bin"]}</td>
           </tr>
HTML;
            } // end shadow > 0
        } // end foreach p
    } // end count parts > 0
    $htm = str_replace("_DETAIL_", $detail, $htm);
    return $htm;
} // end frmtToteInfo
?>
