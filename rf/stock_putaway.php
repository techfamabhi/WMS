<?php
// stock_putaway -- Load up return totes to sens to WMS

/*TODO
 perhaps add major whse loc to WMS_RETURNS so we know which tote is
going where

 If so, check if there is an open tote for the specific maj loc and warn user



Add Close totes and release to WMS
*/
$self = $_SERVER["PHP_SELF"];

foreach (array_keys($_REQUEST) as $w) {
    $$w = $_REQUEST[$w];
}
//error_reporting(0);
if (!isset($mdseType)) $mdseType = "S";
if (!isset($itype)) $itype = "";
if (!isset($bin)) $bin = "";
if (!isset($scaninput)) $scaninput = "";
$mobile = false;
if (isset($ty) and $ty > 0) $mobile = true;


require_once("../include/cl_rf.php");
require_once("../include/db_main.php");
require_once("../include/get_table.php");
require("config.php");
require_once("rma_utils.php");
require_once("cl_PARTS2.php");
$base_prog = $_SERVER["PHP_SELF"];
$bsound = "";

$db = new WMS_DB;
$db1 = new WMS_DB;
$pg = new DisplayRF;

$mdd = disp_mtype($mdseType);

$title = "Awaiting {$mdd} Part Scan";
$pg->title = "Process Returns for WMS";
$pg->js = <<<HTML
<link rel="stylesheet" href="include/wdi3.css">
<link rel="stylesheet" href="include/font-awesome.min.css">
<link href="/Bluejay/Themes/Multipads/Style.css" rel="stylesheet">


<style>
html,body,h1,h2,h3,h4,h5 {font-family: "Raleway", sans-serif}

.binbutton, .stockbutton, .defbutton, .corebutton {
    background-color: #a6a6a6;
    border: none;
    color: white;
    padding: 15px 32px;
    text-align: center;
    text-decoration: none;
    display: inline-block;
    font-size: 20px;
    margin: 4px 2px;
    cursor: pointer;
}
.stockbutton { background-color: #00b33c; }
.defbutton { background-color: #ff4000; }
.corebutton { background-color: #ffad33; }
label {font-family:Verdana,sans-serif;font-size:25px;}
</style>

HTML;
if (!$mobile) $pg->Display();
else { // it's mobile, don't display Bluejay header
    $htm = <<<HTML
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Language" content="en-us">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<link href="/Bluejay/Themes/Multipads/Style.css?=time()" type="text/css" rel="stylesheet">
<title>Process Returns for WMS</title>
<link rel="stylesheet" href="include/wdi3.css">
<link rel="stylesheet" href="include/font-awesome.min.css">
<link href="/Bluejay/Themes/Multipads/Style.css" rel="stylesheet">


<style>
html,body,h1,h2,h3,h4,h5 {font-family: "Raleway", sans-serif}

.binbutton, .stockbutton, .defbutton, .corebutton {
    background-color: #a6a6a6;
    border: none;
    color: white;
    padding: 15px 32px;
    text-align: center;
    text-decoration: none;
    display: inline-block;
    font-size: 20px;
    margin: 4px 2px;
    cursor: pointer;
}
.stockbutton { background-color: #00b33c; }
.defbutton { background-color: #ff4000; }
.corebutton { background-color: #ffad33; }
label {font-family:Verdana,sans-serif;font-size:25px;}
</style>

<script language="Javascript">
function openalt(url,nlns) {
        hgt=210 + (nlns * 25);
        window.open(url,"altpage", "toolbar=no,left=125,top=125,status=yes,resizable=yes,scrollbars=yes,width=750,height=" + hgt );
     }

</script>
<style>
.dropbtn {
    background-color : transparent;
    color: #4d4d4d;
    padding: 2px;
    font-size: 14px;
    border: none;
    cursor: pointer;
}

.dropdown {
    position: relative;
    display: inline-block;
}

.dropdown-content {
    display: none;
    position: absolute;
    background-color: #f9f9f9;
    min-width: 160px;
    box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
    z-index: 1;
}

.dropdown-content a {
    color: black;
    padding: 12px 16px;
    text-decoration: none;
    display: block;
}

.dropdown-content a:hover {background-color: #f1f1f1}

.dropdown:hover .dropdown-content {
    display: block;
}

.dropdown:hover .dropbtn {
    background-color: #00a3cf;
}
</style>
</head>

<body>
HTML;
    echo $htm;
    unset($htm);
} // it's mobile, don't display Bluejay header
$hiddens = <<<HTML
  <input type="hidden" name="tote" value="">
  <input type="hidden" name="oldtote" value="">
  <input type="hidden" name="mdseType" value="{$mdseType}">

HTML;
$scan_type = "Part";
$inputs = <<<HTML
        <label>Scan {$scan_type}</label>
        <input type="text" name="scaninput" value="" style="text-transform:uppercase" onchange="do_bin();">
        <input type="hidden" name="itype" value="P">
        <input type="hidden" name="bin" value="{$bin}">

HTML;

$setfocus = "document.form1.scaninput.focus();";

if ($itype == "P" and trim($scaninput) <> "") {
    $PM = new PARTS;
    $tmp = $PM->lookup(strtoupper($scaninput));
    $j = count($tmp);
//echo "<pre>count={$j}\n";
//print_r($tmp);
//echo "</pre>";
    if (count($tmp) == 0) {
        $built_inputs = true;
        $msg = "Part Number not Found!";
        $inputs = <<<HTML
        <span style="font-color: red;}">{$msg}</span><br>
        <label>Scan {$scan_type}</label>
        <input type="text" name="scaninput" value="" style="text-transform:uppercase" onchange="do_bin();">
        <input type="hidden" name="itype" value="P">
        <input type="hidden" name="bin" value="{$bin}">

HTML;

    } // count = 0 , part not found
    if (count($tmp) == 1) {
        $built_inputs = false;
        $PM->Load($tmp[1]["shadow_number"]);
        if ($mdseType == "S") $bin = $PM->MSTQTY[$main_ms]["whse_location"];
        else $bin = "";
        if ($mdseType == "C" and $PM->Data["price13"] < 0.01) { // its a core type but no core
            $built_inputs = true;
            $msg = "The Part requested does not have a core value";
            $inputs = <<<HTML
        <span style="font-color: red;}">{$msg}</span><br>
        <label>Scan {$scan_type}</label>
        <input type="text" name="scaninput" value="" style="text-transform:uppercase" onchange="do_bin();">
        <input type="hidden" name="itype" value="P">
        <input type="hidden" name="bin" value="{$bin}">

HTML;

        } // its a core type but no core

        if (!$built_inputs) { // ok to build input
            $scan_type = "Tote";
            $title = "Awaiting Tote Scan";
            $qty = 1;
            if ($tmp[1]["alt_type_code"] < 0) $qty = -$tmp[1]["alt_type_code"];
            $color = "";
            $built_inputs = true;
            if (isset($part)) unset($part);
            $part = $tmp[1];
            $inputs = tote_input($part, $bin, $scan_type, $qty, $color);
        } // ok to build input
    } // end count == 1

    $fc = "style=\"color: black;\"";
    $th = "class=\"FormHeaderFont\" {$fc}";
    $tc = "class=\"FieldCaptionTD\" {$fc}";
    $td = "class=\"DataTD\" {$fc}";
    $ta = "class=\"AltDataTD\" {$fc}";
    if (count($tmp) > 1) { // choose part
//echo "<pre>count={$j}\n";
//print_r($tmp);
//echo "</pre>";
        $choose_htm = "";
        $setfocus = "";
        $title = "Please Choose Line Code!";
        $choose_htm = <<<HTML
<input type="hidden" name="scaninput" value="">
<input type="hidden" name="itype" value="P">
<input type="hidden" name="bin" value="{$bin}">
<table border="1" width="100%">
<tr>
<th {$tc}>&#10004;</th>
<th {$tc}>P/L</th>
<th {$tc}>Part#</th>
<th {$tc}>P/L Desc</th>
<th {$tc}>Description</th>
<th {$tc}>Qty</th>
</tr>
{$bsound}
HTML;
        foreach ($tmp as $i => $part) {
            $pldesc = get_pl($db, $part["p_l"], $main_pl);
            $p_l = $part["p_l"];
            $pn = $part["part_number"];
            $pdesc = $part["part_desc"];
            $upc = $part["alt_part_number"];
            $cqty = 1;
            $atype = $part["alt_type_code"];
            if ($atype < 0) {
                $cqty = -$atype;
            };
            $choose_htm .= <<<HTML
<tr>
<td {$td}><input type="checkbox" name="pnum" value="{$p_l}{$pn}" onchange="do_choose('{$p_l}{$pn}');"></td>
<td {$td}>{$p_l}</td>
<td {$td}>{$pn}</td>
<td {$td}>{$pldesc}</td>
<td {$td}>{$pdesc}</td>
<td {$td}>{$cqty}</td>
</tr>

HTML;
            $i++;
        } // end foreach tmp
        $choose_htm .= <<<HTML
</table>
<input type="hidden" name="oldupc" value="{$upc}">

HTML;
        $inputs = $choose_htm;
        unset($choose_htm);

    } // choose part

    //echo "<pre>";
    //print_r($tmp);
//exit;
} // lookup part
// if part and shadow are set, set type to tote
if ($itype == "T" and trim($scaninput) <> "") { // a tote was scanned
    //echo "<pre>";
    //print_r($_REQUEST);
    $tote = trim($scaninput);
    $rc = chk_rmahdr($db, $tote);
    //status codes -1, new tote, 0 - tote found, >0 tote is closed out
    if ($rc <> 0) { // tote status not 0
        if ($rc == -1) { // addit
            $rc1 = add_rmahdr($db, $tote, $main_ms, $mdseType, "0");
            if ($rc1 > 0) $rc = 0;
        } // stat = -1  addit
        if ($rc > 0) { // tote is closed
            $bsound = <<<HTML
   <audio id="myAudio">
  <source src="/Bluejay/sounds/shrub.wav" type="audio/wav">
  <source src="/Bluejay/sounds/boing.wav" type="audio/wav">
  Your browser does not support the audio element.
</audio>


<script>
document.getElementById("myAudio").play();
</script>

HTML;
            $msg = "Tote has been closed, Please Scan Another Tote!";
            $part = array();
            $part["p_l"] = $pl;
            $part["part_number"] = $part_number;
            $part["part_desc"] = $part_desc;
            $part["alt_part_number"] = $pnum;
            $part["alt_type_code"] = -$qty;
            $part["shadow_number"] = $shadow;
            $bin = $bin;
            $scan_type = "Tote";
            $title = "Awaiting Tote Scan";
            $color = "";
            $inputs = tote_input($part, $bin, $scan_type, $qty, $color, $msg);
        } // tote is closed
    } // tote status not 0
    if ($rc < 1) { // we ave an open tote, save detail
        if (isset($part)) unset($part);
        $part = array();
        $part["p_l"] = $pl;
        $part["part_number"] = $part_number;
        $part["part_desc"] = $part_desc;
        $part["alt_part_number"] = $pnum;
        $part["alt_type_code"] = -$qty;
        $part["shadow_number"] = $shadow;
        $PM = new PARTS;
        $PM->Load($shadow, $main_ms);
        $bin = $bin;
        $avail = $PM->MSTQTY[$main_ms]["qty_avail"];
        $alloc = $PM->MSTQTY[$main_ms]["qty_avail"];
        $rc1 = save_dtl($db, $tote, $part, $bin, $mdseType, $avail, $alloc);
    } // we ave an open tote, save detail
} // a tote was scanned

$tmp = get_rmahdrs($db, $db1);
$fc = "style=\"color: black;\"";
$th = "class=\"FormHeaderFont\" {$fc}";
$tc = "class=\"FieldCaptionTD\" {$fc}";
$td = "class=\"DataTD\" {$fc}";
$ta = "class=\"AltDataTD\" {$fc}";

$tote_htm = <<<HTML
 <br>
 <div class="w3-row-padding w3-margin-bottom">
  <div class="w3-half">
   <div class="w3-container w3-padding-8">
    <div class="w3-clear"></div>
    <H4>Current Open Totes</H4>
    <table border="1" width="40%">
     <tr>
      <th {$tc}>Tote</th>
      <th {$tc}>Date</th>
      <th {$tc}>Type</th>
      <th {$tc}>Location</th>
      <th {$tc}>#Lines</th>
      <th {$tc}>Action</th>
     </tr>
HTML;
foreach ($tmp as $i => $t) {
    $d = disp_mtype($t["type"]);
    $tote_htm .= <<<HTML
     <tr>
      <td {$td}>{$t["tote"]}</td>
      <td {$td}>{$t["date"]}</td>
      <td {$td}>{$d}</td>
      <td {$td}>{$t["loc"]}</td>
      <td {$td} align="right">{$t["lines"]}</td>
      <td {$td}><a href="review_tote.php?tote={$t["tote"]}&r={$self}">Review</a></td>
     </tr>

HTML;

} // end foreach tmp
$tote_htm .= <<<HTML
    </table>
   </div>
  </div>
 </div>
</div>

HTML;

//<link rel="stylesheet" href="include/css">
$sbutton = "binbutton";
$dbutton = "binbutton";
$cbutton = "binbutton";
if ($mdseType == "S") $sbutton = "stockbutton";
if ($mdseType == "D") $dbutton = "stockbutton";
if ($mdseType == "C") $cbutton = "stockbutton";
$htm = <<<HTML
<!-- !PAGE CONTENT! -->
<div class="w3-main" style="margin-left:10px;margin-top:4px;">

  <!-- Header -->
  <header class="w3-container" style="padding-top:12px">
    <h2 class="w3-text-blue"><b>Process Returns</b></h2>
    <h5><b>{$title}</b></h5>
  </header>
 <form name="form1" action="{$self}" method="post">
 {$hiddens}

  <div class="w3-row-padding w3-margin-bottom">
    <div class="w3-half">
      <div class="w3-container w3-blue w3-padding-8">
        <div class="w3-clear"></div>
                <h4>{$inputs}</h4>

<br>
<br>

      </div>
    </div>
  </div>
  <table>
   <tr>
    <td><input class="{$sbutton}" type="button" value="Stock" onclick="do_stock();"></td>
    <td><input class="{$dbutton}" type="button" value="Defective" onclick="do_def();"></td>
    <td><input class="{$cbutton}" type="button" name="logoff" value="Core" onclick="do_core();"></td>
    <td><input class="binbutton" type="button" name="clear" value="Clear" onclick="do_clear();"></td>
   </tr>
   </tr>
  </table>
 </form>
</div>
{$bsound}
<script>
{$setfocus}

function do_bin()
{
 document.form1.submit();
}
function do_def()
{
 document.form1.mdseType.value="D";
 document.form1.submit();
}
function do_core()
{
 document.form1.mdseType.value="C";
 document.form1.submit();
}
function do_stock()
{
 document.form1.mdseType.value="S";
 document.form1.submit();
}
function do_choose(pn) {
       document.form1.scaninput.value=pn;
       document.form1.submit();
      }

function do_clear()
{
       document.form1.scaninput.value="";
       if (document.form1.itype.value == "T")
       {
         document.form1.bin.value="";
         document.form1.shadow.value="";
         document.form1.pl.value="";
         document.form1.part_number.value="";
         document.form1.pnum.value="";
         document.form1.qty.value="";
       }
       document.form1.submit();
}
</script>
{$tote_htm}
</body>
</html>

HTML;
echo $htm;

function tote_input($part, $bin, $scan_type, $qty, $color, $msg = "")
{
    $m = "";
    if (trim($msg) <> "") {
        $m = <<<HTML
<span style="font-color: red;}">{$msg}</span><br>
HTML;
    }
    $htm = <<<HTML
        {$m}
        <table>
         <tr>
          <td>{$part["p_l"]}</td>
          <td>{$part["part_number"]}</td>
          <td>{$part["part_desc"]}</td>
         </tr>
         <tr>
          <td colspan="3">
        <span style="font-weight: 900;font-size: 65px;{$color}">{$bin}</span>
</td>
        </table>
        <label>Scan {$scan_type}</label>
        <input type="text" name="scaninput" value="" style="text-transform:uppercase" onchange="do_bin();">
        <input type="hidden" name="itype" value="T">
        <input type="hidden" name="bin" value="{$bin}">
        <input type="hidden" name="shadow" value="{$part["shadow_number"]}">
        <input type="hidden" name="pl" value="{$part["p_l"]}">
        <input type="hidden" name="part_number" value="{$part["part_number"]}">
        <input type="hidden" name="part_desc" value="{$part["part_desc"]}">
        <input type="hidden" name="pnum" value="{$part["alt_part_number"]}">
        <input type="hidden" name="qty" value="{$qty}">

HTML;
    return ($htm);
} // end tote_input
function get_pl($db, $pl, $comp)
{
    $pldesc = "";
    $SQL = <<<SQL
select pl_desc from PRODLINE
where pl_code = "{$pl}"
and pl_company = {$comp}

SQL;
    $rc = $db->query($SQL);
    $numrows = $db->num_rows();
    $i = 1;
    while ($i <= $numrows) {
        $db->next_record();
        if ($numrows) {
            $pldesc = $db->f("pl_desc");
        }
        $i++;
    } // while i < numrows
    return ($pldesc);
} // end get_pl
?>
