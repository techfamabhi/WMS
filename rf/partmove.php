<?php

// partmove.php -- Stock Check a Pat and retrieve all inventory and location
// 03/02/23 dse initial
/*TODO

Get host doc# instead of internal doc# for Totes

Scan Part, bin or tote to Establish source bin, source part or source tote
if bin, scan part
if part, scan bin
if tote, scan part
then
Scan dest tote or bin
If bin, just remove the part from original bin and put in dest bin
If Tote, Remove the part from orig, add to tote, store in TASKS, lock tote

OR -------
Show 4 fields
Source Bin or Tote
Part
Qty
Dest Bin or Tote


Don't ask bin if only 1 bin for that part, auto move primary bin
if scan part,
ask Qty to Move,
ask Move to: (Bin or Tote)
	if bin, add parthist and change primary bin if needed
        if tote,
        if old part had only 1 bin, make primary bin the tote
        add task MOV in tasks for this tote

*/

$debug = false;
session_start();
if (get_cfg_var('wmsdir')) $wmsDir = get_cfg_var('wmsdir');
else {
    echo "<h1>WMS System is not Configured on this System</h1>";
    exit;
}
$top = str_replace("/var/www", "", $wmsDir);

//echo "<pre>";
//print_r($_REQUEST);
//echo "</pre>";

foreach (array_keys($_REQUEST) as $w) {
    $$w = $_REQUEST[$w];
}
//error_reporting(0);

$thisprogram = basename($_SERVER["PHP_SELF"]);
require("{$wmsDir}/config.php");
require_once("{$wmsInclude}/chklogin.php");
require_once("{$wmsInclude}/cl_rf1.php");
require_once("{$wmsInclude}/cl_template.php");
require_once("{$wmsInclude}/cl_bins.php");
require_once("{$wmsInclude}/cl_PARTS2.php");
require_once("{$wmsInclude}/db_main.php");
require_once("{$wmsInclude}/restSrv.php");
require_once("{$wmsInclude}/qtyField.php");


if (isset($_REQUEST["args"])) $args = $_REQUEST["args"]; else $args = array();

if (isset($clear) and $clear == "cLear") { // clear args
    $redirect = $_SERVER["PHP_SELF"];
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

// unset($args);
    //$args=array();
    //foreach (array_keys($_REQUEST) as $w) { unset($w); }

//echo "<pre>";
//print_r($_SERVER); 

} // clear args
$clear = "";
$PARTSRV = "http://{$wmsIp}{$wmsServer}/whse_srv.php";

$userId = $_SESSION["wms"]["UserID"];

// Application Specific Variables -------------------------------------
$comp = $wmsDefComp;
$temPlate = "scanpart";
$title = "Move Part";
$panelTitle = "Part";
//$SRVPHP="{$wmsServer}/COMPANY_srv.php";
//$DRPSRV="{$wmsServer}/dropdowns.php";
// end application specific variables ---------------------------------


$displayWarn = "";
$msg = "";
$msg2 = "";
$msgcolor = "";
$errSound = false;
$color = "blue";
$js = "";
if (!isset($func)) $func = "scanPart";
if (!isset($args["iLevel"])) {
    $args["iLevel"] = "0000";
} else {
    $iLevel = $args["iLevel"];
    if (isset($args["sourceBin"]) and $args["sourceBin"] <> "") $iLevel = setLevel($iLevel, 1);
    if (isset($args["shadow"]) and $args["shadow"] > 0) $iLevel = setLevel($iLevel, 2);
    if (isset($args["qty"]) and $args["qty"] > 0) {
        if (isset($quantity) and $quantity > 0) $args["qty"] = $quantity;
        $iLevel = setLevel($iLevel, 3);
    }
    if (isset($args["destBin"]) and $args["destBin"] <> "") $iLevel = setLevel($iLevel, 4);
    $args["iLevel"] = $iLevel;
    unset($ilevel);
} // end iLevel

$scanInput = frmtScanInput($color, $thisprogram, $msg2);

if (isset($func) and $func == "choosePart") {
    if (isset($shadow) and is_numeric($shadow) and $shadow > 0)
        $scaninput = ".{$shadow}";
}
if (isset($scaninput) and $scaninput <> "") {
    //process the scan, determine if it is a part, bin or tote
    $scaninput = trim(strtoupper($scaninput));
    $result = procScan($scaninput, $comp);
//echo "<pre>";
//print_r($result);
//echo "</pre>";

    $nextScreen = 0; // 0=scan input, 1=Part source bin, 2=bin enter part, 3=tote enter part
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
                $nextScreen = 1;
                $color = "green";
                $db = new WMS_DB;
                $bin = new BIN($db);
                $pnum = trim(strtoupper($scaninput));
                $useWild = "";
                if (strpos($useWild, "%") !== false) $useWild = 1;
                $result["binParts"] = $bin->getLoc($comp, $pnum, $useWild);
                $numParts = $result["binParts"]["numRows"];
                $numBins = $result["numRows"];
                $ok = true;
                // TBD check iLevel to see what bin to set, also if src, is part in it
                if (!isset($args["sourceBin"])) {
                    $args["sourceBin"] = $result["wb_location"];
                    if (isset($result["binParts"])) {
                        if (isset($args["sourceParts"])) unset($args["sourceParts"]);
                        $rbp = $result["binParts"];
                        foreach ($result["binParts"] as $key => $bp) {
                            switch ($key) {
                                case "p_l":
                                    $args["sourceParts"]["p_l"] = $bp;
                                    break;
                                case "part_number":
                                    $args["sourceParts"]["part_number"] = $bp;
                                    break;
                                case "whs_qty":
                                    $args["sourceParts"]["qty"] = $bp;
                                    break;
                                case "whs_shadow":
                                    $ok = true;
                                    $shadow = $bp;
                                    $args["sourceParts"]["shadow"] = $shadow;
                                    if (isset($args["shadow"]) and $shadow <> $args["shadow"]) $ok = false;
                                    break;
                            } // end switch key

                        }
                    }
                }
                if (!$ok) {
                    $msg = "Invalid, Part was not found in this Bin";
                    $color = "red";
                    $errSound = true;
                    break;
                }
                // TBD check iLevel to see what bin to set, also if src, is part in it
//this may need to be moved
                $tmpBin = $result["wb_location"];
                if (isset($args["sourceBin"]) and $args["sourceBin"] <> $tmpBin) $args["destBin"] = $tmpBin;
                else if (isset($args["destBin"])) $msg = "Can't move to the same bin!";
//echo "<pre>";
//print_r($args);
//print_r($result);
//exit;

// if part is set, check if part is in this bin, if not, throw a error.
                if (isset($args["shadow"]) and $args["shadow"] > 0) { // set source or dest bin
                    if (!isset($args["sourceBin"])) {
                        if (isset($args["WhseLoc"]) and count($args["WhseLoc"]) > 0) { // check if part is in this bin
                            $ok2Pass = false;
                            foreach ($args["WhseLoc"] as $key => $B) { // foreach WhseLoc
                                if ($B == $tmpBin) $ok2Pass = true;
                            } // end foreach WhseLoc
                            if ($ok2Pass) {
                                $args["sourceBin"] = $tmpBin;
                                $displayWarn = "Warning(1), the source bin has been set to the only location for this part ({$tmpBin}), please scan destination";
                            }
                        } // check if part is in this bin
                    } // end sourceBin not set
                    if (!isset($args["destBin"]) and isset($args["sourceBin"])) {
                        if ($tmpBin <> $args["sourceBin"]) $args["destBin"] = $tmpBin;

                    } // end destBin not set
                } // set source or dest bin
                $hiddens = setHiddens();
//echo "<pre>";
//print_r($result);
//print_r($args);
//exit;
                $mainSection = frmtBinInfo($result, $thisprogram);
                $title = <<<HTML
<strong>Bin: {$result["wb_location"]}</strong>
&nbsp;
<button class="btn btn-primary btn-xs" name="srClr" id="srClr" onclick="clearSearch()" title="Move antoher Part Number">Clear</button>
HTML;

                break;
            case "P": // Part
                $color = "green";
                $numParts = $result["status"];
                if (isset($result["WhseQty"][$comp]) and isset($result["WhseLoc"])) {
                    $msg = "";
                    $pn = "{$result["Part"]["p_l"]} {$result["Part"]["part_number"]}";
                    if ($result["WhseQty"][$comp]["qty_avail"] < 1) $msg = "Part# {$pn} has no Inventory.";
                    if ($msg == "" and count($result["WhseLoc"]) < 1)
                        $msg = "Part# {$pn} has no Bins assigned.";

                    if ($msg <> "") {
                        $color = "red";
                        $errSound = true;
                        break;
                    }
                }
                if (isset($result["Part"]) and $result["Part"]["shadow_number"] > 0 and isset($result["WhseLoc"]) and (isset($args["sourceBin"]) and $args["sourceBin"] <> "")) { // check if part is in this bin
                    $shadow = $result["Part"]["shadow_number"];
                    $ok = isPartinBin($shadow, $result["WhseLoc"], $args["sourceBin"]);
                    if (!$ok) { // part is not in this bin
                        $msg = "Invalid, Part was not found in this Bin";
                        $color = "red";
                        $errSound = true;
                        break;
                    } // part is not in this bin
                } // check if part is in this bin

                if ($numParts == 1) { // display part info
                    $shadow = $result["Part"]["shadow_number"];
                    if (isset($args["shadow"]) and $args["shadow"] <> $shadow) { // new part, unset old args
                        unset($args);
                    } // new part, unset old args
                    $args["shadow"] = $shadow;
                    $args["partNumber"] = "{$result["Part"]["p_l"]} {$result["Part"]["part_number"]}";
                    $args["qty"] = 1;
                    // TBD check iLevel to see what bin to set, also if src, is part in it
                    if (isset($result["WhseLoc"]) and count($result["WhseLoc"]) == 1) {
                        $bin = $result["WhseLoc"][1]["whs_location"];
                        if (isset($args["sourceBin"])) echo "SB=" . substr($args["sourceBin"], 0, 1);
                        if (isset($args["sourceBin"]) and substr($args["sourceBin"], 0, 1) <> "!") $args["destBin"] = $bin;
                        //if (!isset($args["sourceBin"])) $args["sourceBin"]=$bin;
                    }

                    if (isset($result["alt_type_code"])) {
                        $at = $result["alt_type_code"];
                        if ($at < 0) $args["qty"] = -$at;
                    }
                    $Totes = array();
                    $ItemPull = array();
                    //$Totes=$pr->TOTESelect($shadow,$comp);
                    //$ItemPull=$pr->ITEMPULLSelect($shadow,$comp);
                    $title = <<<HTML
<strong>{$result["Part"]["p_l"]} {$result["Part"]["part_number"]}</strong>
&nbsp;
<button class="btn btn-primary btn-xs" name="srClr" id="srClr" onclick="clearSearch()" title="Clear All">Clear</button>
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
                    $partDisplay = frmtPartInfo($result, $thisprogram);
// set flags for scanInput below (line 209)
                    $nextScreen = 1;
                    $mainSection = <<<HTML
{$partDisplay}
HTML;
                } // display part info

                break;
            case "T": // Tote
                if (!isset($partDisplay)) $partDisplay = "";
                $color = "green";
                $tote = $result["tote_id"];
                if (isset($result["tote_type"])
                    and ($result["tote_type"] <> "MOV" and $result["tote_type"] <> "RCV" and $result["tote_type"] <> "RCS")
                    and $result["tote_type"] <> "") {
                    $msg = "<br>Error, this Tote is in Use as a {$result["tote_type"]} Tote";
                    $color = "red";
                    $errSound = true;
                    $nextScreen = 1;
                    $mainSection = <<<HTML
{$partDisplay}
HTML;

                    break;
                }

                // TBD check iLevel to see what bin to set, also if src, is part in it
                if (isset($args["sourceBin"])) $args["destBin"] = "!" . $tote;
                else $args["sourceBin"] = "!" . $tote;
                $nextScreen = 1;
                $mainSection = frmtToteInfo($result);
                $title = <<<HTML
<strong>Tote: {$result["tote_id"]}</strong>
&nbsp;
<button class="btn btn-primary btn-xs" name="srClr" id="srClr" onclick="clearSearch()" title="Move antoher Part Number">Clear</button>
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
// here I need to figure what scan screen to display
    /* If Part,
        ask Source Bin if more than 1
        ask qty
        ask Destination
       make the form go to next field on return until all fields are filled in

      if Bin,
        ask part to move
        ask qty
        ask Destination
       make the form go to next field on return until all fields are filled in

      if Tote and tote type is PUT, MOV or RCV
       ask move tote to new location?
        if yes, ask new location
        if no,
         ask Part to move out of tote
         ask qty
         ask destination
       make the form go to next field on return until all fields are filled in
    */

//echo "<pre>";
//print_r($args);
//print_r($_REQUEST);
//exit;
    if (isset($args["shadow"])
        and isset($args["qty"])
        and isset($args["sourceBin"])
        and isset($args["destBin"])) { // got all info, lets move it
// if part is set, check if part is in this bin, if not, throw a error.
        // Set Args detail from gathered info along the way so I can check it
        // or make a server call to make sure all is good
        // on source bin lookup, create args array of possible shadows for this bin
//echo "<pre>";
//print_r($result);
//print_r($args);
//exit;

        $req = array("action" => "movePart",
            "comp" => $comp,
            "userId" => $userId,
            "shadow" => $args["shadow"],
            "qty" => $args["qty"],
            "sourceBin" => $args["sourceBin"],
            "destBin" => $args["destBin"],
        );
        $ret = restSrv($PARTSRV, $req);
        $result = (json_decode($ret, true));
//echo "<pre>";
//print_r($req);
//print_r($result);
//exit;
        if (isset($result["Status"]) and $result["Status"] == "OK") {
            $msg2 = "Moved Successfully";
            $color = "green";
            unset($args);
            $args = array();
            unset($func);
        } else { // error occurred
            $msg2 = "An Error Occurred trying to move Part";
            ob_start();
            $msg2 = ob_get_contents();
            ob_end_clean();

            $color = "red";
        } // error occurred
        $scanInput = frmtScanInput($color, $thisprogram, $msg2);
        $msg2 = "";
//echo "<pre>Line:248 ";
//print_r($req);
//echo "\nresult=";
//print_r($result);
//exit;

    } // got all info, lets move it
    else { // need more info to move it
        $scanInput = frmtScanInput($color, $thisprogram, $msg2);
        if ($nextScreen == 1) $scanInput = frmtMoveByPart($color, $result, $thisprogram, $msg2);
    } // need more info to move it


} // en isset scaninput and not empty
if (isset($result) and isset($result["infoType"]) and $result["infoType"] == "P" and $result["numRows"] > 1) { // format choose
    $msgcolor = "yellow";
    $title = <<<HTML
<strong>Please Choose</strong>
&nbsp;
<button class="btn btn-primary btn-xs" name="srClr" id="srClr" onclick="clearSearch()" title="Move another Part Number">Clear</button>
HTML;
    $js .= <<<HTML
<script src="/jq/shortcut.js" type="text/javascript"></script>
<script>
shortcut.add("return",function() {
  document.getElementById('srClr').click();
});
</script>

HTML;

    $scanInput = frmtChoosePart($result, $thisprogram);

}  // format choose

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
if (!isset($args["iLevel"])) $args["iLevel"] = "";
$iLevel = $args["iLevel"];
if (isset($args["sourceBin"]) and $args["sourceBin"] <> "") $iLevel = setLevel($iLevel, 1);
if (isset($args["shadow"]) and $args["shadow"] > 0) $iLevel = setLevel($iLevel, 2);
if (isset($args["qty"]) and $args["qty"] > 0) $iLevel = setLevel($iLevel, 3);
if (isset($args["destBin"]) and $args["destBin"] <> "") $iLevel = setLevel($iLevel, 4);
$args["iLevel"] = $iLevel;
unset($ilevel);

//Display Header
$pg = new displayRF;
$pg->viewport = "1.10";
$pg->dispLogo = false;
$pg->Bootstrap = true;
if (!strpos($title, "Clear")) {
    $title .= <<<HTML
&nbsp;
<button class="btn btn-primary btn-xs" name="srClr" id="srClr" onclick="clearSearch()" title="Move antoher Part Number">Clear</button>
HTML;
}
$pg->title = $title;
$js .= <<<HTML
<script>
function clearSearch()
{
 var element = document.getElementsByName("scaninput");
if(typeof(element) == 'undefined' && element != null)
{
 document.form1.scaninput.value="";
}
 document.form1.clear.value="cLear";
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
$eejs = addCollapse();
if ($displayWarn <> "") {
    $eejs .= <<<HTML
<audio controls autoplay hidden>
  <source src="{$wmsAssets}/sounds/psycho.wav" type="audio/wav">
</audio>
 <script>
  var ok=true;
  ok=(confirm("{$displayWarn} or cancel to re-enter part"))
  if (!ok) clearSearch();
 </script>

HTML;

} // end display Warn
$pg->jsh = $js;

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
  {$eejs}
HTML;

//echo "<pre>";
//print_r($_REQUEST);
//echo "</pre>";
$pg->Display();

if (!isset($debug)) $debug = false;
if ($userId == 1 and $debug) {
    echo '<div style="position: absolute; right: 10%; bottom: 10%;"><pre>Args ';
    print_r($args);
    echo "<hr>REQUEST ";
    print_r($_REQUEST);
} // end debug
//Rest of page
$htm = <<<HTML
 </body>
</html>

HTML;
echo $htm;

//echo " <br> <br> <br> <br> <br> <br> <br> <br> <br> <br> <br> <br> <br> <br> <br>";
//echo "<pre>";
//print_r($result);

function frmtPartInfo($part, $thisprogram)
{
    global $ItemPull;
    $color = "green";
    $i = $part["comp"];
    $hiddens = <<<HTML
  <input type="hidden" name="clear" value="">
HTML;
    $qa = $part["WhseQty"][$i]["qty_avail"];
    $qal = $part["WhseQty"][$i]["qty_alloc"];
    $htm = <<<HTML
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
    if (count($part["WhseLoc"]) > 0) {
        $detail = <<<HTML
          <table width="100%" class="table table-bordered table-striped">
           <tr>
            <td class="FieldCaptionTD">Bin</td>
            <td align="right" class="FieldCaptionTD">Qty</td>
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
            if (substr($theBin, 0, 1) == "!") $theBin = "Tote: " . substr($theBin, 1);
            $detail .= <<<HTML
           <tr>
            <td{$tdt}>{$btype}{$theBin}</td>
            <td align="right">{$l["whs_qty"]}</td>
            <td>{$l["whs_uom"]}</td>
           </tr>

HTML;
        } // end foreach whseLoc
        $detail .= "          </table>\n";
    } // end count whseLoc > 0
    $htm = str_replace("_DETAIL_", $detail, $htm);
    $EXTRA = "";
    $Totes = array();
    if (isset($part["Totes"])) $Totes = $part["Totes"];
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
            if (isset($d["host_ref"])) $x = $d["host_ref"]; else $x = $d["HostId"];
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
    global $wmsAssets;
    $hiddens = "";
    $cnt = $part["numRows"];
    $upc = $part["upc"];
    $detail = "";
    $htm = <<<HTML
 <div class="w3-clear"></div>
  <div class="w3-half w3-yellow">
   <div class="panel panel-default">
    <div class="panel-heading">
     <div class="row w3-yellow">
      <div class="col-md-6">
       <h3 class="panel-title">Found {$cnt} Parts matching "{$upc}"</h3>
      </div>
     </div>
    </div>
    <div class="panel-body">
     <div class="table-responsive">
      <form name="form1" action="{$thisprogram}" method="get">
      <input type="hidden" name="func" value="choosePart">
      <input type="hidden" name="upc" value="{$upc}">
      <input type="hidden" name="comp" value="{$part["comp"]}">
      <input type="hidden" name="scaninput" value="{$upc}">
      <input type="hidden" name="shadow" value="">
      <input type="hidden" name="clear" value="">
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
<audio controls autoplay hidden>
  <source src="{$wmsAssets}/sounds/boxing_bell.wav" type="audio/wav">
</audio>

<script>
function do_sel(shadow)
{
 document.form1.shadow.value=shadow;
 document.form1.submit();
}
function clearSearch()
{
 var element = document.getElementsByName("scaninput");
if(typeof(element) == 'undefined' && element != null)
{
 document.form1.scaninput.value="";
}
 document.form1.clear.value="cLear";
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
    global $func;
    if (trim($func) == "") $func = "scanPart";
    $hiddens = setHiddens();
    $msgh = "";
    if ($msg <> "") {
        $msgh = <<<HTML
<div  style="margin-left:0px;" class="w3-container wms-red"><span style="font-weight: bold; font-size: large; text-align: center;">{$msg}</span></div>
HTML;
    }

    $htm = <<<HTML
{$msgh}
 <form name="form1" action="{$thisprogram}" method="get">
  <input type="hidden" name="func" id="func" value="{$func}">
{$hiddens}
  <div class="w3-row-padding w3-margin-bottom">
   <div class="w3-half">
    <div class="w3-container w3-{$color}">
     <span class="w3-{$color}"><br></span>
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
 // do bin1
 document.form1.submit();
}
</script>

HTML;
    return $htm;
} // end frmtScanInput

function frmtInput($fld, $imask = "", $val = "", $fnc = "do_bin();", $titl = "")
{
    if ($imask == "") $imask = $fld;
    $htm = <<<HTML
 <input type="hidden" id="inputMask" name="inputMask" value="{$imask}">
 <div class="w3-clear"></div>
      <input type="text" class="w3-white" onchange="{$fnc}" value="{$val}" name="{$fld}" placeholder="{$titl}" id="scaninput" title="{$titl}">
     </div>

HTML;
    return $htm;
} // end frmt Input

function frmtBinInfo($data, $thisprogram)
{
    $bin = $data;
    $parts = $data["binParts"];
    unset($bin["binParts"]);
    $color = "green";
    $hiddens = setHiddens();
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
 var element = document.getElementsByName("scaninput");
if(typeof(element) == 'undefined' && element != null)
{
 document.form1.scaninput.value="";
}
 document.form1.clear.value="cLear";
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
            <td class="FieldCaptionTD">P/L</td>
            <td class="FieldCaptionTD">Part Number</td>
            <td style="text-align:right;padding-right:10px" class="FieldCaptionTD">Qty</td>
            <td class="FieldCaptionTD">UOM</td>
            <td class="FieldCaptionTD">Description</td>
            <td class="FieldCaptionTD">Type</td>
           </tr>

HTML;
        $p = array();
        if (isset($parts[2])) $p = $parts; else $p[1] = $parts;
        if (isset($parts[2])) $p = $parts; else $p[1] = $parts;
        $displayed = 0;
        foreach ($p as $rec => $l) {
            if (isset($l["whs_shadow"]) and $l["whs_shadow"] > 0) {
                $Extra = "{$l["part_desc"]}";
                if (isset($l["otherBins"]) and is_array($l["otherBins"])) {
                    if (!isset($l["otherBins"][1])) {
                        $w = $l["otherBins"];
                        unset($l["otherBins"]);
                        $l["otherBins"][1] = $w;
                        unset($w);
                    }
                    $Extra = frmtOtherBins($l["otherBins"], $Extra);
                }
                $displayed++;
                $detail .= <<<HTML
           <tr>
            <td>{$l["p_l"]}</td>
            <td>{$l["part_number"]}</td>
            <td style="text-align:right;padding-right:10px">{$l["whs_qty"]}</td>
            <td>{$l["whs_uom"]}</td>
            <td>{$Extra}</td>
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
    $usedfor = $data["tote_type"];
    $doc = $data["tote_ref"];
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

function frmtOtherBins($o, $pdesc)
{
    $EXTRA = "";
    if (is_array($o) and count($o) > 0) {
        $EXTRA .= <<<HTML

        <div class="collapsible">
        <span class="FormSubHeaderFont">{$pdesc}</span>
        </div>
         <div class="content">
          <table class="table table-bordered table-striped">
           <tr>
            <td class="FieldCaptionTD">Bin</td>
            <td align="right" class="FieldCaptionTD">Qty</td>
            <td class="FieldCaptionTD">UOM</td>
            <td class="FieldCaptionTD">Type</td>
           </tr>

HTML;
        $tqty = 0;
        foreach ($o as $t => $d) {
            $EXTRA .= <<<HTML
           <tr>
            <td nowrap>{$d["whs_location"]}</td>
            <td>{$d["whs_qty"]}</td>
            <td>{$d["whs_uom"]}</td>
            <td>{$d["whs_code"]}</td>
           </tr>

HTML;
        } // end foreach Totes
        $EXTRA .= <<<HTML
          </table>
         </div>

HTML;
    } // end count o
    return $EXTRA;
} // end frmtOtherBins

function addCollapse()
{
    $ret = <<<HTML
<style>
.collapsible {
  background-color: #87CEEB!important;
  color: white;
  cursor: pointer;
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
    return $ret;
}

function setHiddens()
{
    global $func;
    global $args;
    if (trim($func) == "") $func = "scanPart";
    $hiddens = <<<HTML
  <input type="hidden" name="clear" value="">
  <input type="hidden" name="func" value="{$func}">

HTML;
    if (count($args) > 0) {
        foreach ($args as $key => $a) {
            if (is_array($a)) {
                if (count($a) > 0) {
                    foreach ($a as $k => $v) {
                        $hiddens .= <<<HTML
  <input type="hidden" name="args[{$key}][{$k}]" value="{$v}">

HTML;
                    } // end foreach a
                } // end count > 0
            } // end is array
            else {
                $hiddens .= <<<HTML
  <input type="hidden" name="args[{$key}]" value="{$a}">

HTML;
            } // not an array
        } // end foreach args
    } // end count args > 0
    return $hiddens;
} // end setHiddens

function frmtMoveByPart($color, $result, $thisprogram, $msg = "")
{
    global $js;
    global $args;
//echo "<pre>";
//print_r($result);
//print_r($args);
//exit;

    if ($result["infoType"] == "P") { // store valid bins for this part
        if (isset($args["WhseLoc"])) unset($args["WhseLoc"]);
        if (isset($args["LocQty"])) unset($args["LocQty"]);
        if (count($result["WhseLoc"]) > 0) {
            $i = 0;
            foreach ($result["WhseLoc"] as $rec => $l) {
                $args["WhseLoc"][$i] = $l["whs_location"];
                $args["LocQty"][$i] = $l["whs_qty"];
                $i++;
            } // end foreach whseloc
        } // end count whseloc
    } // store valid bins for this part

    $hiddens = setHiddens();
    $msgh = "";
    if ($msg <> "") {
        $msgh = <<<HTML
<div  style="margin-left:0px;" class="w3-container wms-red"><span style="font-weight: bold; font-size: large; text-align: center;">{$msg}</span></div>
HTML;
    }

    $htm = frmtInputs($color, $result, $thisprogram, $hiddens, $msgh);
    return ($htm);
    $htm = <<<HTML
{$msgh}
 <form name="form1" action="{$thisprogram}" method="get">
  <input type="hidden" name="func" id="func" value="scanPart">
{$hiddens}
  <div class="w3-row-padding w3-margin-bottom">
   <div class="w3-half">
    <div class="w3-container w3-{$color}">
     <span class="w3-{$color}"><br></span>
     <div class="w3-clear"></div>
      <label class="wmslabel" for="scaninput" style="vertical-align: middle;" >Source Bin</label>
      <input type="text" class="w3-white" onchange="do_bin();" value="" name="scaninput" placeholder="Scan Source, Bin or Tote" id="partnum" title="Scan or Enter Bin or Tote">
      {$h}
     </div>
    </div>
   </div>
 </form>

<script>
 document.form1.scaninput.focus();

function do_bin()
{
 // do bin2
 document.form1.submit();
}
</script>

HTML;
    return $htm;
} // end frmtScanInput

function frmtInputs($color, $result, $thisprogram, $hiddens, $msg = "")
{
    global $js;
    global $args;
    global $displayWarn;
    /* results is the fields needed
     spart:     source part (p/l Part#, shadow)
     sourceBin: source bin or Tote
     Qty:       qty to move
     destBin:   dest bin or tote
    */
    $green = "Alt4DataTD";
    $yellow = "AltDataTD";
    $blue = "Alt3DataTD";

    $cls1 = "{$blue}";
    $cls2 = "{$blue}";
    $cls3 = "{$blue}";
    $ccls1 = "{$blue}";
    $ccls2 = "{$blue}";
    $ccls3 = "{$blue}";
    $spart = "";
    $sourceBin = "";
    $Qty = "1";
    $destBin = "";
    $scanPrompt = "Source Bin";
    $scanHolder = "Scan Source, Bin or Tote";
    $scanTitle = "Scan or Enter Bin or Tote";
    if (isset($args["partNumber"])) { // have a part
        $cls2 = $blue;
        $spart = $args["partNumber"];
        //if ($result["Result"]["alt_type_code"] < 0) $Qty=-$result["Result"]["alt_type_code"];
    } // have a part
    if (isset($args["qty"])) $Qty = $args["qty"];
    if (!isset($args["sourceBin"]) and isset($args["WhseLoc"]) and count($args["WhseLoc"]) == 1) {
        $sourceBin = $args["WhseLoc"][0];
        $args["sourceBin"] = $sourceBin;
        $scanPrompt = "Dest Bin/Tote";
        $scanHolder = "Scan To, Bin or Tote";
        $displayWarn = "Warning(2), the source bin has been set to the only location for this part ({$sourceBin}), please scan destination";
    } else if (isset($args["sourceBin"]) and $sourceBin == "") {
        $sourceBin = $args["sourceBin"];
        $scanPrompt = "Dest Bin/Tote";
        $scanHolder = "Scan To, Bin or Tote";
    }

    $hiddens = setHiddens();
    $ispart = $spart;
    $isourceBin = $sourceBin;
    $idestBin = $destBin;
    $ctrl = 1; // scan sourceBin
    if ($sourceBin <> "") $ctrl = 2; // scan part
    if ($sourceBin <> "" and $spart <> "") $ctrl = 3; // scan destBin
    switch ($ctrl) {
        case 1:
            $isourceBin = frmtInput("scaninput", $ctrl, $sourceBin, "do_bin();", $scanHolder);
            $cls1 = $yellow;
            $ccls1 = $cls1;
            $ccls2 = $cls2;
            $ccls3 = $cls3;
            break;
        case 2:
            $ispart = frmtInput("scaninput", $ctrl, $spart, "do_bin();", $scanHolder);
            $cls2 = "Alt4DataTD";
            $ccls2 = "Alt4DataTD";
            break;
        case 3:
            $idestBin = frmtInput("scaninput", $ctrl, $destBin, "do_bin();", $scanHolder);
            $cls3 = "Alt4DataTD";
            $ccls3 = "Alt4DataTD";
            break;
    } // end switch
    $qf = new qtyField;
    $js .= $qf->js;

    $qm = 0;
    if (isset($args["sourceBin"]) and $args["sourceBin"] <> "" and isset($args["WhseLoc"])) { // find how many are in the source bin
        $sb = $args["sourceBin"];
        foreach ($args["WhseLoc"] as $key => $b) {
            if ($b == $sb and isset($args["LocQty"][$key])) $qm = $args["LocQty"][$key];
        }
    } // find how many are in the source bin

//echo "<pre>qm={$qm}\n";
//print_r($args);
//echo "</pre>";
    if ($qm <> 0) $qf->qtyBin = $qm;
    $qtyFld = $qf->qtyInput("");
    //<label class="wmslabel" for="scaninput" style="vertical-align: middle;" >{$scanPrompt}</label>
    //<input type="text" class="w3-white" onchange="do_bin();" value="" name="scaninput" placeholder="{$scanHolder}" id="partnum" title="{$scanTitle}">
    $htm = <<<HTML
{$msg}
 <form name="form1" action="{$thisprogram}" method="get">
  <input type="hidden" name="func" id="func" value="scanPart">
  <input type="hidden" name="ctrl" id="ctrl" value="{$ctrl}">
{$hiddens}
  <div class="w3-row-padding w3-margin-bottom">
   <div class="w3-half">
    <div class="w3-container w3-{$color}">
     <span class="w3-{$color}"><br></span>
     <div class="w3-clear"></div>
     <div class="row">
    </div>
     </div>
    </div>
   </div>
     <div class="row w3-row-padding w3-half">
     <div class="panel-body">
      <table class="table table-bordered table-striped">
       <tr>
        <th width="25%" class="{$ccls1}">Move From</th>
        <td class="{$cls1}">{$isourceBin}</td>
       </tr>
       <tr>
        <th width="25%" class="{$ccls2}">Part To Move</th>
        <td class="{$cls2}">{$ispart}</td>
       </tr>
       <tr>
        <th width="25%" class="Alt5DataTD">Qty</th>
        <td>{$qtyFld}</td>
       </tr>
       <tr>
        <th width="25%" class="{$ccls3}">Move To</th>
        <td class="{$cls3}">{$idestBin}</td>
       </tr>
      </table>
    </div>
    </div>
 </form>

<script>
 document.form1.scaninput.focus();

function do_bin()
{
 // do bin 3
 document.form1.submit();
}
</script>

HTML;
    return $htm;
} // end frmtInputs

function setLevel($str, $pos)
{ // set bit position relative to 1 not 0 for a 4 char string
    $length = 4;
    $pos = $pos - 1;
    $left = substr($str, 0, $pos);
    $right = substr($str, $pos + 1);
    return substr("{$left}1{$right}", 0, 4);
} // end setLevel

function isPartinBin($shadow, $WhseLoc, $sourceBin)
{
    //WhseLoc is array of bins of this shadow
    $ok = false;
    if ($shadow > 0 and is_array($WhseLoc) and count($WhseLoc) > 0 and $sourceBin <> "") { // check if part is in this bin
        foreach ($WhseLoc as $key => $w) {
            if ($w["whs_location"] == $sourceBin) {
                $ok = true;
                break;
            }
        } // end foreach whseloc
    } // check if part is in this bin

    return $ok;

} // end isPartinBin

?>
