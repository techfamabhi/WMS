<?php
// addUpc.php -- version 1.0 Add Upc Code in Receiving
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
$top = str_replace("/var/www", "", $wmsDir) . "/";

require($_SESSION["wms"]["wmsConfig"]);
require_once("{$wmsInclude}/chk_login.php");
require_once("{$wmsInclude}/cl_rf1.php");
require_once("{$wmsInclude}/db_main.php");
require_once("collapse.php");
$RESTSRV = "http://{$wmsIp}{$wmsServer}/PO_srv.php";

$db = new WMS_DB;

//echo "<pre>";
//print_r($_REQUEST);
//echo "</pre>";
$hostPoNum = "";
$poNum = 0;
if (!isset($recvTo)) $recvTo = "b";
if (!isset($recvType)) $recvType = "1";
if (!isset($lookPO)) $lookPO = "1";
if (!isset($vendor)) $vendor = "";
if (isset($_SESSION["rf"]["POs"]) and isset($_SESSION["rf"]["HPO"])) { // only setting first PO right now
    $hostPoNum = $_SESSION["rf"]["HPO"][0];
    $poNum = $_SESSION["rf"]["POs"][0];
    $recvTo = $_SESSION["rf"]["recvTo"];
    $recvType = $_SESSION["rf"]["recvType"];
    $vendor = $_SESSION["rf"]["vend"]["vendor"];
}  // only setting first PO right now

if ($poNum == 0) { // display error screen
    echo "<pre>Error, PO not found";
    exit;
//need close button
} // display error screen

if (!isset($func)) $func = "enterPart";
if (!isset($nh)) $nh = 0;
if (!isset($partNumber)) $partNumber = "";
if (!isset($scaninput)) $scaninput = "";
if (!isset($shadow)) $shadow = 0;
if (!isset($lookPO)) $lookPO = 1;
if (!isset($UPC)) $UPC = "";
if (!isset($reqst)) $reqst = "";

$thisprogram = basename($_SERVER["PHP_SELF"]);
$htm = "";

if (isset($func) and $func == "cancel") {
//echo "<pre>";
//echo "cancel pressed";
//$r=urldecode($reqst);
    $htm = frmtReturn($reqst);
    echo $htm;
    exit;
}
$title = "UPC Code {$UPC} not Found";

$hiddenFlds = array(
    "func" => "{$func}",
    "nh" => "{$nh}",
    "poNum" => "{$poNum}",
    "HostPoNum" => "{$hostPoNum}",
    "UPC" => "{$UPC}",
    "scaninput" => $scaninput,
    "recvTo" => $recvTo,
    "recvType" => $recvType,
    "lookPO" => $lookPO,
    "vendor" => $vendor,
    "reqst" => urlencode($reqst)
);
if ($partNumber <> "") $hiddenFlds["partNumber"] = $partNumber;

$msg = "";
$hiddens = bldHiddens($hiddenFlds);

if ($func == "enterQty" and $shadow > 0 and $UPC <> "") { // have all info, add ALTERNAT
    $w = -1;
    if (isset($upcQty) and $upcQty > 0) $w = -$upcQty;
    $auom = "EA";
    if (isset($uom) and $uom <> "EA") $auom = $uom;
    if (isset($case_uom) and $case_uom <> "" and $upcQty > 1) $auom = $case_uom;
    $theUser = $_SESSION["wms"]["UserID"];
    $SQL0 = <<<SQL
insert into UPCLOG
( source, upc, userId, shadow, qty, upc_status)
values ("REC","{$UPC}",{$theUser},{$shadow},{$upcQty},0)
SQL;
    $SQL1 = <<<SQL
insert IGNORE into ALTERNAT
( alt_shadow_num, alt_part_number, alt_type_code, alt_uom, alt_sort)
values ( {$shadow}, "{$UPC}", {$w}, "{$auom}", 0)
 
SQL;
//echo "<pre>";
//echo "{$SQL0}\n";
//echo "{$SQL1}\n";
// insert Alternat and return with msg of rc added
    $rt = array();
    $rt["log"] = $db->Update($SQL0);
    $rt["alt"] = $db->Update($SQL1);
    //$rt["start"]=$db->startTrans(); // do transaction
    //$rt["log"]=$db->updTrans($SQL0);
    //$rt["alt"]=$db->updTrans($SQL1);
    //$rt["end"]=$db->endTrans($rc["trans"]); // commit or Rollback Transaction
//print_r($rt);
//echo "</pre>";
//exit;
    $msg = "UPC {$UPC} Added";
    if ($rt["alt"] < 1) {
        $SQL = <<<SQL
select count(*) as cnt from ALTERNAT where alt_part_number = "{$UPC}"

SQL;

        $rc = $db->query($SQL);
        $numrows = $db->num_rows();
        $i = 1;
        while ($i <= $numrows) {
            $db->next_record();
            if ($numrows) {
                $cnt = $db->f("cnt");
            }
            $i++;
        } // while i < numrows
        if ($cnt < 1) $msg = "Add of UPC {$UPC} Failed";
    }
    $htm = frmtReturn($reqst, $msg);
    echo $htm;
    exit;
} // have all info, add ALTERNAT

if ($func == "enterPart" and $partNumber <> "") {
    // validate part number from PO
    $part = chkPart($db, $poNum, $partNumber);
    $j = $part["numRows"];
    if ($j > 0) { // part or parts found
        if ($j > 1) { // ask Choose
        } // ask Choose
        else { // ask Qty
            $hiddenFlds["func"] = "enterQty";
            $hiddenFlds["shadow"] = $part[1]["shadow"];
            $hiddenFlds["p_l"] = $part[1]["p_l"];
            $hiddenFlds["part_number"] = $part[1]["part_number"];
            $hiddenFlds["part_desc"] = $part[1]["part_desc"];
            $hiddenFlds["uom"] = $part[1]["uom"];
            $hiddenFlds["case_uom"] = $part[1]["case_uom"];
            $hiddenFlds["case_qty"] = $part[1]["case_qty"];
            $title = "Adding UPC Code {$UPC} - Enter Qty";
            $hiddens = bldHiddens($hiddenFlds);
            $htm = frmtQty($partNumber, $hiddens, $part);
        } // ask Qty
    } // part or parts found
    if ($j < 1) {
        echo "here";
        $msg = "Part not Found on Receiving Document # {$hostPoNum}";
        unset($part);
    }
//echo "<pre>";
//echo "j={$j} msg={$msg}\n";
//echo "</pre>";
//print_r($part);
//exit;
} // end enterPart


$hdr = <<<HTML
<!DOCTYPE html>
<html>
 <head>
 <title>{$title}</title>
 <meta name="robots" content="noindex">
 <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
 <meta name="viewport" content="initial-scale=1.0, width=device-width, user-scalable=yes" />
 <script>
  window.name="assignBins";
 </script>

  <link rel="stylesheet" href="/wms/assets/css/wdi3.css">
 <link rel="stylesheet" href="/wms/assets/css/font-awesome.min.css">
 <link rel="stylesheet" href="/wms/Themes/Multipads/Style.css">
 <link href="/jq/bootstrap.min.css" rel="stylesheet">
 <link rel="stylesheet" href="/wms/assets/css/wms.css">
 <style>
 .menuI {
  position: absolute;
  right:0;
 }
 </style>
 
 <script>
function openalt(url,nlns) {
        hgt=210 + (nlns * 25);
        var popup=window.open(url,"popup", "toolbar=no,left=0,top=125,status=yes,resizable=yes,scrollbars=yes,width=600,height=" + hgt );
 return(false);
     }
function doView(tote)
{
 var url="tcont.php?toteId=" + tote;
 openalt(url,10);
 return false;
}
</script>

</head>

 <body class="w3-light-grey" >
<!-- !PAGE CONTENT! -->
<header class="w3-container w3-light-blue" style="border-radius: 5px;padding-top:4px;padding-bottom:8px;">
 <table width="98%" class="topnav1 z-blue">
  <tr>
   <td nowrap width="25%">
     <span><b><span id="pageTitle">{$title}</span></b>
   </td>
     <div style='float:right;'>
      <div style='position: fixed; top:1px;'>
           <a class="menuI" title="Menu" href="/wms/webmenu.php"><img border="0" src="/wms/images/menu_grey.png"></a>

      </div>
     </div>

  </tr>
 </table>
</header>

HTML;

$color = "yellow";
$mcolor = "yellow";
if ($msg <> "") {
    $color = "red";
    $mcolor = "red";
}

//echo "<pre>";
//echo "color={$color} mcolor={$mcolor} msg={$msg}\n";
//echo "</pre>";

if ($htm == "") $htm = <<<HTML
 <form name="form1" action="{$thisprogram}" method="get">
{$hiddens}
  <div class="w3-row-padding w3-margin-bottom">
    <div class="w3-half">
     <div class="container w3-{$color} w3-padding-8">
     <div class="w3-white">
      <div class="w3-padding-8 FormHeaderFont">
</div>
        <span class="w3-light-blue"><br></span>
        <div class="clear"></div>
      <div class="row">
       <div class="col-75">
        <table style="position:relative;left: 6px;" class="table table-bordered table-striped">
         <tr>
          <td colspan="5" class="w3-{$mcolor}"><strong>{$msg}</strong></td>
         </tr>
         <tr>
          <td class="FieldCaptionTD" align="left" width="10%">Enter Part number</td>
          <td class="w3-white" colspan="4" align="left" width="10%">
           <input name="partNumber" type="text" class="w3-white" onchange="do_submit();" value="" placeholder="Enter Part Number for this UPC" id="part_number" title="Enter Part Number for this UPC">
          </td>
         </tr>
         <tr>
          <td colspan="5" class="w3-white">&nbsp;</td>
         </tr>

         <tr>
          <td colspan="5">

           <button class="binbutton-small" id="b1" name="B1" value="submit" onclick="do_submit();">Submit</button>

           <button class="binbutton-small" id="b2" name="B2" value="Cancel" onclick="do_cancel();">Cancel</button>

          </td>
         </tr>

        </table>
       </div>
      </div>
    <br>

     <div class="col-75" style="word-wrap: normal;font-weight: bold; font-size: large; text-align: cput;">Enter Part Number for UPC {$UPC}
    </div>

     </div>
     </div>
    </div>
  </div>
 </form>

<script>
 document.form1.partNumber.focus();

function do_cancel()
{
 document.form1.func.value="cancel";
 document.form1.submit();
}

function do_submit()
{
 document.form1.submit();
}
</script>
  
 </body>
</html>

HTML;

$pg = new displayRF;
$pg->title = $title;
$pg->msg = "";
$pg->viewport = "1.10";
$pg->dispLogo = false;
$pg->body = $htm;
$pg->Bootstrap = true;

$pg->Display();
//echo $htm;


function frmtQty($partNumber, $hiddens, $part)
{
    global $thisprogram;
    global $UPC;
    // if we got here, we found the part#
    $color = "green";
    $p = $part[1];
    $pn = "{$p["p_l"]} {$p["part_number"]}";

    $htm = <<<HTML
 <form name="form1" action="{$thisprogram}" method="get">
{$hiddens}
  <div class="w3-row-padding w3-margin-bottom">
    <div class="w3-half">
     <div class="container w3-{$color} w3-padding-8">
     <div class="w3-white">
      <div class="w3-padding-8 FormHeaderFont">
</div>
        <span class="w3-{$color}"><br></span>
        <div class="clear"></div>
      <div class="row">
       <div class="col-75">
        <table style="position:relative;left: 6px;" class="table table-bordered table-striped">
         <tr>
          <td colspan="5" class="w3-white">&nbsp;</td>
         </tr>
         <tr>
          <td colspan="1" class="FieldCaptionTD">Part Number</td>
          <td class="FieldCaptionTD">Description</td>
          <td class="FieldCaptionTD">UOM</td>
          <td colspan="2" class="w3-white">&nbsp;</td>
         </tr>
         <tr>
          <td colspan="1" class="w3-white">{$pn}</td>
          <td class="w3-white">{$p["part_desc"]}</td>
          <td class="w3-white">{$p["uom"]}</td>
          <td colspan="2" class="w3-white">&nbsp;</td>
         </tr>
         <tr>
          <td colspan="5" class="w3-white">&nbsp;</td>
         </tr>
         <tr>
          <td class="FieldCaptionTD" align="left" width="10%">Enter Quantity</td>
          <td class="w3-white" colspan="4" align="left" width="10%">
           <input name="upcQty" type="number" min="1" max="999" class="w3-white" onblur="do_submit();" value="1" id="upc_qty" title="Enter Qty of {$pn} for this UPC">
          </td>
         </tr>
         <tr>
          <td colspan="5" class="w3-white">&nbsp;</td>
         </tr>
         <tr>
          <td class="w3-white">Case Qty: {$p["case_qty"]}</td>
          <td class="w3-white">Case UOM: {$p["case_uom"]}</td>
          <td colspan="3" class="w3-white">&nbsp;</td>
         </tr>
         <tr>
          <td colspan="5" class="w3-white">&nbsp;</td>
         </tr>

         <tr>
          <td colspan="5">

           <button class="binbutton-small" id="b1" name="B1" value="submit" onclick="do_submit();">Submit</button>

           <button class="binbutton-small" id="b2" name="B2" value="Cancel" onclick="do_cancel();">Cancel</button>

          </td>
         </tr>

        </table>
       </div>
      </div>
    <br>

     <div class="col-75" style="word-wrap: normal;font-weight: bold; font-size: large; text-align: cput;">Enter Qty of {$pn} for UPC {$UPC}
    </div>

     </div>
     </div>
    </div>
  </div>
 </form>

<script>
 document.form1.upcQty.focus();

function do_cancel()
{
 document.form1.func.value="cancel";
 document.form1.submit();
}

function do_submit()
{
 document.form1.submit();
}
</script>
  
 </body>
</html>

HTML;
    return $htm;
} // end frmtQty

function chkPart($db, $po, $partnum)
{
    $SQL = <<<SQL
SELECT
 shadow,
 p_l,
 part_number,
 part_desc,
 uom,
 case_uom,
 case_qty,
 alt_part_number,
 alt_type_code,
 alt_uom
 from ALTERNAT,POITEMS
 WHERE alt_part_number like "{$partnum}"
 AND  shadow = alt_shadow_num
 AND poi_po_num = {$po}

SQL;

    $ret = array();
    $rc = $db->query($SQL);
    $numrows = $db->num_rows();
    $i = 1;
    while ($i <= $numrows) {
        $db->next_record();
        if ($numrows and $db->Record) {
            foreach ($db->Record as $key => $data) {
                if (!is_numeric($key)) {
                    $ret[$i]["$key"] = $data;
                }
            }
        }
        $i++;
    } // while i < numrows
    $ret["numRows"] = $numrows;
    return $ret;
} // end chkPart

function bldHiddens($hiddenFlds)
{
    $hiddens = "";
    foreach ($hiddenFlds as $key => $val) {
        $hiddens .= <<<HTML
  <input type="hidden" name="{$key}" value="{$val}">

HTML;
    } // end foreach hiddens
    return $hiddens;
} // end bldHiddens

function frmtReturn($reqst, $msg = "")
{

    $r = urldecode($reqst);
    if (substr($r, 0, 1) == "%") $r = urldecode($r);
    if (substr($r, 0, 1) == "%") $r = urldecode($r);
    if (substr($r, 0, 1) == "%") $r = urldecode($r);
    $r1 = json_decode($r, true);
    if (json_last_error() !== JSON_ERROR_NONE) { // reqst is not in json format
        echo "Passed Args are incorrect";
        exit;
    } // reqst is not in json format
    if (isset($r1["thisprogram"])) $formaction = $r1["thisprogram"];
    else $formaction = "recv_po.php";

    if ($msg <> "") {
        $r1["msg"] = $msg;
    } else {
        $r1["msg"] = "Add of UPC Code Cancelled";
        if (isset($r1["scaninput"])) $r1["scaninput"] = "";
    }

    $hiddens = bldHiddens($r1);
    $htm = <<<HTML
<html>
<head>
</head>
<body onload="document.form1.submit();">
<form name="form1" action="{$formaction}" method="post">
{$hiddens}
</form>
</body>
</html>
HTML;
    return $htm;
} // end frmtReturn
?>
