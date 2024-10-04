<?php

// invAdjust.php -- Adjst inventory from Stock Check
// 09/19/23 dse initial
/*TODO


*/

if (isset($_REQUEST['B2']) and $_REQUEST['B2'] == "cancel") {
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
if (!isset($adjType)) $adjType = "A";

$thisprogram = basename($_SERVER['PHP_SELF']);
require("{$wmsDir}/config.php");
require_once("{$wmsInclude}/chklogin.php");
require_once("{$wmsInclude}/cl_rf1.php");
require_once("{$wmsInclude}/cl_template.php");
require_once("{$wmsInclude}/cl_PARTS2.php");
require_once("{$wmsInclude}/db_main.php");
require_once("{$wmsInclude}/date_functions.php");
require_once("{$wmsInclude}/restSrv.php");
require_once("{$wmsInclude}/qtyField.php");

require_once("pb_utils.php");


$RESTSRV = "http://{$wmsIp}{$wmsServer}/RcptLine.php";
$PARTSRV = "http://{$wmsIp}{$wmsServer}/whse_srv.php";
$AdjSRV = "http://{$wmsIp}{$wmsServer}/WMS2ERP.php";
// get ip for javascript
$protocol = (!empty($_SERVER['HTTPS']) && (strtolower($_SERVER['HTTPS']) == 'on' || $_SERVER['HTTPS'] == '1')) ? 'https://' : 'http://';
$server = $_SERVER['SERVER_NAME'];
$port = $_SERVER['SERVER_PORT'] ? ':' . $_SERVER['SERVER_PORT'] : '';

$BINSRV = "{$protocol}{$server}{$port}{$wmsServer}/whse_srv.php";
$comp = $wmsDefComp;
$db = new WMS_DB;
//$qf=new qtyField;
//$qtyJs=$qf->js;
//$qf->defQty="";
//$qf->required=1;
//$qf->onfocus="onfocus=\"curField='quantity'\";";
//$qtyFld=$qf->qtyInput("");
$qtyJs = "";
$qtyFld = "";

// Application Specific Variables -------------------------------------
$temPlate = "generic1";
$title = "Adjust Inventory";
$panelTitle = "Move Pallet/Tote #";
//$SRVPHP="{$wmsServer}/COMPANY_srv.php";
//$DRPSRV="{$wmsServer}/dropdowns.php";
// end application specific variables ---------------------------------

// Load allowable ADJ Reasons from REASONS table
$req = array("action" => "getReasons",
    "company" => $comp,
    "Search" => "ADJ"
);
$ret = restSrv($PARTSRV, $req);
$w = (json_decode($ret, true));
if (!isset($reason)) $reason = "";
$rdrp = "";
if (count($w) > 0) {
    $reasons = array();
    $rdrp = <<<HTML
<span>
<select name="reason" id="reason" onfocus="curField='reason';" required>
 <option value="">Please Select</option>

HTML;
    foreach ($w as $key => $z) {
        if ($z['reason_code'] <> "") {
            $pzs = "";
            $reasons[$z['reason_code']] = $z['reason_desc'];
            if ($reason == $z['reason_code']) $pzs = " selected";
            $rdrp .= <<<HTML
 <option value="{$z['reason_code']}"{$pzs}>{$z['reason_code']} - {$z['reason_desc']}</option>

HTML;
        } // end reason <> ""
    } // end foreach w
    $rdrp .= <<<HTML
</select>
</span>
HTML;

} // end count w


$nh = 1;
if (!isset($func)) $func = "entInfo";
if (!isset($msg)) $msg = "";

// get Part and inventory info
$partRecord = array();
if (isset($shadow) and $shadow > 0) {
//{"action":"getPart","company":1,"partNumber":"24046"}
    $req = array("action" => "getPart",
        "company" => $comp,
        "partNumber" => ".{$shadow}"
    );
    $ret = restSrv($PARTSRV, $req);
    $w = (json_decode($ret, true));
    if (isset($w['numRows']) and $w['numRows'] > 0) {
        $partRecord = $w;
    }
} // end shadow isset
if (!isset($partRecord['numRows'])) $partRecord['numRows'] = 0;

if ($func == "invAdj" and $partRecord['numRows'] > 0) {
//echo "<pre> REQUEST=";
//print_r($partRecord);
//print_r($_REQUEST);

    $pbin = $partRecord['WhseQty'][$comp]['primary_bin'];
    if ($pbin == "") $binType = "P"; else $binType = "O";
    $uom = $partRecord['Part']['unit_of_measure'];
    $oldQty = 0;
    if (isset($partRecord['WhseLoc'])) {
        foreach ($partRecord['WhseLoc'] as $key => $w1) {
            if ($w1['whs_location'] == $bin) {
                $uom = $w1['whs_uom'];
                $binType = $w1['whs_code'];
                $oldQty = $w1['whs_qty'];
            }
        }
    } // end WhseLoc is set


    $adjQty = $quantity;
    if ($adjType == "R") $adjQty = ($oldQty - $quantity);
    $newQty = $oldQty + $adjQty;

    $req = array("action" => "invAdj",
        "userId" => $UserID,
        "comp" => $comp,
        "shadow" => $shadow,
        "pl" => $partRecord['Part']['p_l'],
        "partNumber" => $partRecord['Part']['part_number'],
        "Bin" => $bin,
        "Qty" => $adjQty,
        "reasonCode" => $reason,
        "reasonText" => $reasons[$reason],
        "uom" => $uom,
        "invCode" => 0, // add other types later
        "mdse" => $partRecord['Part']['cost'],
        "core" => $partRecord['Part']['core'],
        "qtyCore" => 0, // add later
        "qtyDef" => 0, // add later
        "binType" => $binType,
        "oldQty" => $oldQty,
        "newQty" => $newQty,
        "primaryBin" => $pbin
    );
// Do adjustment here, then close, and force stockcheck to reload
    $ret = restSrv($AdjSRV, $req);
    $w = (json_decode($ret, true));
    $msg = "";
    if (isset($w['status'])) {
        if ($w['status'] == "success") $msg = "Part successfully adjusted";
        else $msg = "Adjustment Failed, Please retry";
    }

//print_r($req);
//echo "</pre>";
    $htm = <<<HTML
  <html>
 <head>
 <script>
 // alert(typeof window.opener.closeAdjust);
 window.opener.closeAdjust("{$msg}")
 </script>
 </head>
 <body>
 </body>
</html>

HTML;
    echo $htm;
    exit;
}
if ($func == "palletToMove" and $toteId == "" and $B1 == "submit") $func = "scanScreen";
if ($func == "movingPallet" and $newLoc == "" and $B1 == "submit") $func = "palletToMove";

if ($func == "donePressed" and isset($B2) and $B2 == "cancel") $func = "scanScreen";
if ($func == "donePressed" and isset($B2) and $B2 == "done") {
    require("{$wmsInclude}/backToMenu.php");
}


switch ($func) {
    case "entInfo":
    { // Display Entry Screen
        if (isset($msg)) $msg = "";
        if (isset($msgCancel)) $msg = $msgCancel;
        $color = "light-blue";
        if ($msg <> "") $color = "green";
        if (!isset($binQty)) $binQty = 0;
        $mainSection = frmtAdjust($comp, $rdrp, $shadow, $part, $bin, $binQty);
        break;
    } // Display Entry Screen

    case "scanScreen":
    { // Display Scan Tote screen
        if (isset($msg)) $msg = "";
        if (isset($msgCancel)) $msg = $msgCancel;
        $color = "light-blue";
        if ($msg <> "") $color = "green";
        $mainSection = entOrderTote($msg, $color);
        break;
    } // End Display Scan Tote screen

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
            //echo "<pre> RESTSRV={$RESTSRV}";
//print_r($req);
//print_r($w);
//echo "</pre>";
            $req = array("action" => "chkTask",
                "company" => $comp,
                "tote_id" => $toteId
            );
            $ret1 = restSrv($RESTSRV, $req);
            $task = (json_decode($ret, true));
            if (count($task) > 0) {
                //check if tote is moving by the same user
                // echo "<pre> task=";
                //print_r($task);
                //echo "</pre>";
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
                $last_zone = "";
                $last_loc = "";
                if (isset($y[1])) foreach ($y as $idx => $target) {
                    if (isset($target['target_zone'])) $target_zone = att($target_zone, $target['target_zone']);
                    if (isset($target['target_aisle'])) $target_aisle = att($target_aisle, $target['target_aisle']);
                    if (isset($target['last_zone'])) $last_zone = att($last_zone, $target['last_zone']);
                    if (isset($target['last_location'])) $last_loc = att($last_location, $target['last_location']);
                } // end foreach y
                $msg = "";
                $mmsg = "";
                $templte = "palletMove";
                if ($last_zone == "" or $last_zone == "PUT") {
                    $templte = "generic2";
                    if (trim($task[1]['tote_location']) == "") $last_loc = "{$task[1]['tote_type']} {$task[1]['tote_ref']}";
                    else $last_loc = $task[1]['tote_location'];
                    $target_zone = "";
                    $target_aisle = "";
                    if ($task[1]['tote_type'] <> "RCV"
                        and $task[1]['tote_type'] <> "PUT")
                        $mmsg = "<br>Warning, this is not a Receiving Tote";
                    // ***** Putaway Mode ***************************
                    // ask Method Ad-Hoc Mode or directed mode
// temp code to try putaway
                    /*
                        $color="blue";
                        $msg="Scan Part";
                        $vendor="";
                        $title="Putaway Tote " . $toteId;
                        $work=frmtPartScan($vendor,$msg,$color);
                        $buttons=setStdButtons("C",true);
                        $mainSection=str_replace("_BUTTONS_",$buttons,$work);
                        unset($work);

                        break;  // **** End Putaway Mode *******
                    */
// temp code to try putaway
                }
                $num_items = "";
                $totalQty = "";
                if (isset($w[1]['num_items'])) $num_items = $w[1]['num_items'];
                if (isset($w[1]['totalQty'])) $totalQty = $w[1]['totalQty'];
                $req = array("action" => "addTask",
                    "company" => $comp,
                    "tote_id" => $toteId,
                    "operation" => "MOV"
                );

                $hiddens = <<<HTML
<form name="form1" action="invAdjust.php" method="get">
  <input type="hidden" name="func" id="func" value="movingPallet">
  <input type="hidden" name="nh" value="0">
  <input type="hidden" name="toteId" value="{$toteId}">
  <input type="hidden" name="target_zone" value="{$target_zone}">
  <input type="hidden" name="target_aisle" value="{$target_aisle}">
  <input type="hidden" name="num_items" value="{$num_items}">
  <input type="hidden" name="totalQty" value="{$totalQty}">

HTML;
                //if (isset($y[1]['last_loc']) and ($y[1]['last_loc'] <> "")) $last_loc=$y[1]['last_loc'];
                $color = "green";
                $fieldPrompt = "Scan New Location";
                $fieldPlaceHolder = "Scan Bin Location";
                $fieldId = "new_Loc";
                $fieldTitle = " title=\"Scan Bin Location\"";
                if ($last_zone <> "") $msg = "Last Zone: {$last_zone}{$mmsg}";
                if ($last_loc <> "") $msg = "   Last Location: {$last_loc}{$mmsg}";
                $msg2 = "Move Pallet/Tote {$toteId} -";
                $msg2 .= " Items: {$num_items}, Units: {$totalQty}";

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
                    "target_zone" => $target_zone,
                    "target_aisle" => $target_aisle,
                    "function" => ""
                );
                $mainSection = frmtScreen($data, $thisprogram, $templte);
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
        if (isset($w['numRows'])) $numRows = $w['numRows']; else $numRows = count($w);
        if ($numRows > 0) {
            $j = $w[1];
            $req = array(
                "action" => "updToteLoc",
                "company" => $comp,
                "tote_id" => $toteId,
                "operation" => $j['zone_type'],
                "zone" => $j['zone_type'],
                "newBin" => $j['zone']
            );
            $ret = restSrv($RESTSRV, $req);
            $w1 = (json_decode($ret, true));
            if ((isset($w1['RcptUpd']) and $w1['RcptUpd'] > 0)
                or (isset($w1['ToteUpd']) and $w1['ToteUpd'] > 0)) { // all good
                $color = "blue";
                $msg = "Move to {$newLoc} Successful";
                $mainSection = entOrderTote($msg, $color);
                break;
            } // all good
        } // end numRows > 0
        if ($numRows < 1) { // invalid area or Bin
            $color = "red";
            $msg = "Invalid Area or Location";
            $mainSection = askNewLoc($toteId, $target_zone, $target_aisle, $num_items, "Yellow", $totalQty, $msg, "", "");
            break;
        } // invalid area or Bin
        break;
    } // end moveingPallet

} // end switch func

$pg = new displayRF;
$pg->viewport = "1.0";
$pg->dispLogo = false;
$pg->Bootstrap = true;
if (isset($title)) $pg->title = $title;
if (isset($color)) $pg->color = $color; else $color = "light-blue";
$ejs = "";
if (isset($nh) and $nh > 0) {
    $pg->noHeader = true;
}

if (!isset($otherScripts)) $otherScripts = "";
$pg->jsh = <<<HTML

<script src="/jq/shortcut.js" type="text/javascript"></script>
<script src="/jq/axios.min.js"></script>
<script>
let curField="";

shortcut.add("Return",function() {
  setcurFld();
});

window.onblur= function()
{
 do_done();
} 
function do_done() {
 //window.opener.closeAdjust("")
 window.opener.closeAdjust("Adjustment Canceled")
}

function setcurFld() {
  document.getElementById("demo").innerHTML = curField;
}

function CurFld(fld)
{
 curField=fld;
 return true;
}

function validateBin(bin)
{
 var validBin=false;
 axios.post('{$BINSRV}', {
    action:'chkBin',
    company:{$comp},
    bin: bin
   }).then(function(response){
        validBin = response.data.binStat;
   });
 if (validBin) document.reason.focus();
}

</script>

<script>
function openalt(url,nlns) {
        hgt=210 + (nlns * 25);
        var popup=window.open(url,"popup", "toolbar=no,left=125,top=125,status=yes,resizable=yes,scrollbars=yes,width=750,height=" + hgt );
 return(false);
     }
</script>
{$qtyJs}
<style>
input[type=number]::-webkit-inner-spin-button {
    opacity: 1
}

.required {
    color: red;
   }
</style>

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
//print_r($w);

function closeScreen()
{
    // close popup
    // need to set a message in parent, then issue parent.windowClose();
    exit;
} // end closeScreen

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
 document.{$data['formName']}.submit();
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
    $fieldPlaceHolder = "Scan Bin to Move To";
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

function setStdButtons($DorC = "D", $tc = false)
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
    if ($tc) {
        global $toteId;
        $b = array(
            0 => array(
                "btn_id" => "b1",
                "btn_name" => "B1",
                "btn_value" => "View",
                "btn_onclick" => "openalt('tcont.php?toteId={$toteId}',10);",
                "btn_prompt" => "View Contents"
            ),
            1 => $buttons[0],
            2 => $buttons[1]
        );
        unset($buttons);
        $buttons = "";
        foreach ($b as $b1) {
            $buttons .= <<<HTML
<button class="binbutton-small" id="{b1['btn_id']}" name="{$b1['btn_name']}" value="{$b1['btn_value']}" onclick="{$b1['btn_onclick']}">{$b1['btn_prompt']}</button>

HTML;

        } // end foreach b
    } // end tc is true
    return $buttons;
} // end setStdButtons
function askNewLoc($toteId, $target_zone, $target_aisle, $num_items, $color,
                   $totalQty, $mmsg, $last_zone = "", $last_loc = "")
{
    global $nh;
    global $thisprogram;

    $templte = "generic2";

    $hiddens = <<<HTML
<form name="form1" action="invAdjust.php" method="get">
  <input type="hidden" name="func" id="func" value="movingPallet">
  <input type="hidden" name="nh" value="0">
  <input type="hidden" name="toteId" value="{$toteId}">
  <input type="hidden" name="target_zone" value="{$target_zone}">
  <input type="hidden" name="target_aisle" value="{$target_aisle}">
  <input type="hidden" name="num_items" value="{$num_items}">
  <input type="hidden" name="totalQty" value="{$totalQty}">

HTML;
    $msg = $mmsg;
    $fieldPrompt = "Scan New Location";
    $fieldPlaceHolder = "Scan New Pallet/Tote Location";
    $fieldId = "new_Loc";
    $fieldTitle = " title=\"Scan New Pallet/Tote Location\"";
    if ($last_zone <> "") $msg = "Last Zone: {$last_zone}{$mmsg}";
    if ($last_loc <> "") $msg = "   Last Location: {$last_loc}{$mmsg}";
    $msg2 = "Move Pallet/Tote {$toteId} -";
    $msg2 .= " Items: {$num_items}, Units: {$totalQty}";

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
        "target_zone" => $target_zone,
        "target_aisle" => $target_aisle,
        "function" => ""
    );
    $ret = frmtScreen($data, $thisprogram, $templte);
    return $ret;
} // end askNewLoc

function frmtAdjust($comp, $rdrp, $shadow, $pn, $bin, $binQty)
{
    //global $qtyFld;
    global $adjType;
//           <input name="toteId" type="text" class="w3-white" onchange="do_submit();" value="" placeholder="Scan Tote/Pallet Id to Move" id="toteid" title="Scan Tote/Pallet (Tote, Pallet, Cart, etc) to Move">
    if (trim($bin <> "")) {
        $binhtm = <<<HTML
          <td class="w3-white" colspan="4" align="left" width="10%">{$bin}
           <input type="hidden" name="bin" value="{$bin}">
          </td>

HTML;
    } else {
        $binhtm = <<<HTML
          <td class="w3-white" colspan="4" align="left" width="10%">
           <input name="bin" type="text" class="w3-white" onfocus="curField='bin';" onchange="validateBin(this.value);" value="" placeholder="Enter Bin" id="bon" title="Enter Bin" required>
          </td>

HTML;
    }
    $ts = array("R" => " checked", "A" => "");
    if ($adjType == "A") $ts = array("A" => " checked", "R" => "");
    $hd1 = "+/-";
    if ($ts['R'] <> "") $hd1 = "NewQty";
    $htm = <<<HTML
<script>
 function do_hd1(typ,bQty)
 {
  if (typ == "R")
  { 
   document.getElementById('hd1').innerHTML="NewQty";
   document.getElementById('Qty').min=0;
  }
  else 
  {
   document.getElementById('hd1').innerHTML="+/-";
   document.getElementById('Qty').min=(-bQty);
  }
 }

function valQty(ele)
{
  var val=ele.value;
  var min=ele.min;
  var max=ele.max;
  var ok=true;
 // if (min > -1 && (val > max || val < min)) ok=false;
  //if (min < 0 && (val > max || val > min)) ok=false;
  if (ok !== true)
  {
   alert("Value must be between " + min + " and " + max);
   ele.value="";
   return false;
  }
}
</script>

<form name="form1" action="invAdjust.php" method="get">
  <input type="hidden" name="func" id="func" value="invAdj">
  <input type="hidden" name="nh" value="1">
  <input type="hidden" name="comp" value="{$comp}">
  <input type="hidden" name="shadow" value="{$shadow}">
  <input type="hidden" name="pn" value="{$pn}">
  <div class="w3-row-padding w3-margin-bottom">
    <div class="w3-half">
     <div class="container w3-light-blue w3-padding-8">
     <div class="w3-white">
      <div class="w3-padding-8 FormHeaderFont">
</div>
        <span class="w3-light-blue"><br></span>
        <div class="clear"></div>
      <div class="row">
       <div class="col-75">
        <table style="position:relative;left: 6px;" class="table table-bordered table-striped">
         <tr>
          <td colspan="5" class="w3-white"><h3><strong><span id="demo"></span></strong></h3></td>
         </tr>
         <tr>
          <td class="FieldCaptionTD" align="left" width="10%">Part#</td>
          <td class="w3-white" colspan="4" align="left" width="10%">{$pn}</td>
         </tr>
         <tr>
          <td class="FieldCaptionTD" align="left" width="10%">Bin</td>
          {$binhtm}
         </tr>
         <tr>
          <td class="FieldCaptionTD" align="left" width="10%">Reason <span class="required">&nbsp;*</span></td>
          <td class="w3-white" colspan="4" align="left" width="10%">
{$rdrp}
          </td>
         <tr>
          <td class="FieldCaptionTD" align="left" width="10%">Replace/Adjust</span></td>
          <td nowrap class="w3-white" colspan="2" align="left" width="5%">
<input type="radio" name="adjType" value="R" checked="{$ts['R']}" onchange="do_hd1('R',{$binQty});"><strong>Replace</strong>
          </td>
          <td nowrap class="w3-white" colspan="2" align="left" width="5%">
<input type="radio" name="adjType" value="A" checked="{$ts['A']}" onchange="do_hd1('A',{$binQty});"><strong>Adjust</strong>
          </td>
         </tr>
         <tr>
          <td class="FieldCaptionTD" align="left" width="10%">Qty <span class="required">&nbsp;*</span></td>
          <td nowrap class="w3-white" colspan="2" align="center" width="5%"><strong>Current</strong><br>{$binQty}</td>
          <td nowrap class="w3-white" colspan="2" align="center" width="5%"><strong><span id="hd1">{$hd1}</span></strong><br>
<input type="number" min="-{$binQty}" max="999" id="Qty" name="quantity" onfocus="curField='quantity';" onblur="valQty(this);" required>
</td>
         </tr>
         </tr>
         <tr>
          <td colspan="5" class="w3-white">&nbsp;</td>
         </tr>

         <tr>
          <td colspan="5">

           <button class="binbutton-small" id="b1" name="B1" value="submit" onclick="do_submit();">Submit</button>

           <button class="binbutton-small" id="b2" name="B2" value="done" onclick="do_done();">Cancel</button>

          </td>
         </tr>

        </table>
       </div>
      </div>
    <br>

     <div class="col-75" style="word-wrap: normal;font-weight: bold; font-size: large; text-align: cput;">Adjust Qty
    </div>

     </div>
     </div>
    </div>
  </div>
 </form>
<script>
 document.form1.toteId.focus();

HTML;
    return $htm;
} // end frmtAdjust
?>
