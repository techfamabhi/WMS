<?php

// setPrimary.php -- Set Primary Bin from Stock Check
// 06/18/24 dse initial
// 07/17/24 dse dont display of bins starting with "!"
/*TODO


*/

if (isset($_REQUEST["B2"]) and $_REQUEST["B2"] == "cancel") {
} // end b2 is set

//echo "<pre>";
//print_r($_REQUEST);
//echo "</pre>";


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
$title = "Set Primary Bin";
$panelTitle = "Set Primary Bin";
//$SRVPHP="{$wmsServer}/COMPANY_srv.php";
//$DRPSRV="{$wmsServer}/dropdowns.php";
// end application specific variables ---------------------------------

// Load allowable ADJ Reasons from REASONS table

$nh = 1;
if (!isset($func)) $func = "selectPrimary";
if (!isset($msg)) $msg = "";

// get Part and inventory info
$detail = "";
$partRecord = array();
if (isset($shadow) and $shadow > 0) {
//{"action":"getPart","company":1,"partNumber":"24046"}
    $req = array("action" => "getPart",
        "company" => $comp,
        "partNumber" => ".{$shadow}"
    );
    $ret = restSrv($PARTSRV, $req);
    $w = (json_decode($ret, true));
//echo "<pre>";
//print_r($w);
//exit;
    if (isset($w["numRows"]) and $w["numRows"] > 0) {
        $partRecord = $w;
    }
} // end shadow isset
if (!isset($partRecord["numRows"])) $partRecord["numRows"] = 0;

if ($func == "setPrimary" and $partRecord["numRows"] > 0 and $B1 == "submit" and $R1 <> "") {
    /* func] => setPrimary
        [nh] => 1
        [comp] => 1
        [shadow] => 1231408
        [pn] => BSH 7406
        [R1] => A-03-17-B
        [B1] => submit */
    $SQL = <<<SQL
 update WHSELOC set whs_code = "O" 
 where whs_shadow = {$shadow}
 and whs_code = "P"

SQL;
    $rc0 = $db->Update($SQL);

    $SQL = <<<SQL
update WHSEQTY
set primary_bin = "{$R1}"
where ms_company = {$comp}
and ms_shadow = {$shadow}

SQL;
    $rc = $db->Update($SQL);
    $numRows = $db->NumRows;
    $SQL = <<<SQL
 update WHSELOC set whs_code = "P"
 where whs_shadow = {$shadow}
 and  whs_location = "{$R1}"

SQL;
    $rc0 = $db->Update($SQL);

    $msg = "";
    if ($numRows > 0) $msg = "Primary Bin successfully Updated";
    else $msg = "Primary Bin Not Updated, Please retry";

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

if ($func == "selectPrimary" and $partRecord["numRows"] > 0) {
//echo "<pre> REQUEST=";
//print_r($partRecord);
//print_r($_REQUEST);
    $pbin = $partRecord["WhseQty"][$comp]["primary_bin"];
    if (isset($partRecord["WhseLoc"])) {
        $detail = <<<HTML
<table class="table table-bordered table-striped">
 <tr>
  <th class="FieldCaptionTD">&nbsp;</th>
  <th class="FieldCaptionTD">Location </th>
  <th class="FieldCaptionTD">Type </th>
  <th class="FieldCaptionTD">Qty </th>
 </tr>

HTML;
        foreach ($partRecord["WhseLoc"] as $key => $w1) {
            $chk = "";
            $bin = $w1["whs_location"];
            if (substr($bin, 0, 1) <> "!") { // end its not a tote
                $ty = $w1["whs_code"];
                $qty = $w1["whs_qty"];
                if ($bin == $pbin) $chk = "checked";
                $detail .= <<<HTML
  <tr>
   <td><input type="radio" name="R1" value="{$bin}" {$chk}/></td>
   <td>{$bin}</td>
   <td>{$ty}</td>
   <td>{$qty}</td>
 </tr>

HTML;
            } // end its not a tote
        }
        $detail .= <<<HTML
</table>

HTML;
    } // end WhseLoc is set

} // end setPrimart and part rows > 0

$jsh = <<<HTML

<script>
window.onblur= function()
{
 do_done();
} 
function do_done() {
 //window.opener.closeAdjust("")
 window.opener.closeAdjust("Set Primary Bin Canceled")
}

</script>


HTML;

$otherScripts = "";
$mainSection = frmtSet($comp, $shadow, $part, $detail);
$tophtm = <<<HTML
<!DOCTYPE html>
<html>
 <head>
 <meta name="robots" content="noindex">
 <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
 <meta name="viewport" content="initial-scale=1.10, width=device-width, user-scalable=yes" />
 <link href="/jq/bootstrap.min.css" rel="stylesheet">
 <link rel="stylesheet" href="/wms/Themes/Multipads/Style.css">
 <link rel="stylesheet" href="/wms/assets/css/wms.css">
 <script>
window.onblur= function()
{
 do_done();
}
function do_done() {
 //window.opener.closeAdjust("")
 window.opener.closeAdjust("Set Primary Bin Canceled")
}
function do_submit()
{
 document.form1.submit();
}
</script>
</head>

 <body class="w3-light-grey" style="height:100%; width:100%" >
<!-- !PAGE CONTENT! -->
HTML;

$htm = <<<HTML
  {$tophtm}
  {$mainSection}
  {$otherScripts}
 </body>
</html>

HTML;
echo $htm;
echo "<pre>";
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

function frmtSet($comp, $shadow, $pn, $detail)
{
    global $title;
    global $panelTitle;


    $htm = <<<HTML
<form name="form1" action="setPrimary.php" method="get">
  <input type="hidden" name="func" id="func" value="setPrimary">
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
     <div class="col-75" style="word-wrap: normal;font-weight: bold; font-size: large; text-align: cput;">{$panelTitle}</div>
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
          <td class="w3-white" colspan="4" align="left" width="10%">
{$detail}
          </td>
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

     </div>
     </div>
    </div>
  </div>
 </form>
<script>

HTML;
    return $htm;
} // end frmtsetPrimary
?>
