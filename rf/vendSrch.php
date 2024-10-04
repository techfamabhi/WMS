<?php
//01/03/24 dse Update to include customers and search ENTITY

/*TODO

*/
foreach (array_keys($_REQUEST) as $w) {
    $$w = $_REQUEST[$w];
}
//error_reporting(0);

session_start();
//echo "<pre>";
//print_r($_REQUEST);
//echo "</pre>";

if (get_cfg_var('wmsdir')) $wmsDir = get_cfg_var('wmsdir');
else {
    echo "<h1>WMS System is not Configured on this System</h1>";
    exit;
}
$top = str_replace("/var/www", "", $wmsDir);

require($_SESSION["wms"]["wmsConfig"]);
if (isset($_REQUEST["recvType"])) $recvType = $_REQUEST["recvType"]; else $recvType = "1";
if (isset($_REQUEST["recvTo"])) $recvTo = $_REQUEST["recvTo"]; else $recvTo = "a";

require_once("{$wmsInclude}/cl_rf.php");
//require_once("ddform.php");
//require_once("cl_PARTS2.php");
require_once("{$wmsInclude}/db_main.php");
if (!function_exists("get_contrl")) {
    require_once("{$wmsInclude}/get_contrl.php");
}
require_once("{$wmsInclude}/chklogin.php");


//temp
$main_ms = 1;
$sounds = "../assets";

$pinfo = "";
$db = new WMS_DB;

$pg = new displayRF;
$pg->viewport = "1.10";
$pg->dispLogo = false;
$pg->jsh = <<<HTML
<style>
html,body,h1,h2,h3,h4,h5 {font-family: "Raleway", sans-serif}
.binbutton {
    background-color: #2196F3;
    border: none;
    color: white;
    padding: 8px 16px;
    text-align: center;
    text-decoration: none;
    display: inline-block;
    font-size: 20px;
    margin: 2px 2px;
    cursor: pointer;
}
.binbutton:disabled {
    background-color: #dddddd;
}
.binbutton:enabled {
    background-color: #2196F3;
}
.btn {
  width: 95px;
}
label {
 font-size: 1.2em;
}
strong {
 font-size: 1.2em;
}

</style>

HTML;
$pg->jsb = <<<HTML
<script>
 document.getElementById('scaninput').focus();

function movetoend(fld)
{
 var val=fld.value;
 fld.value='';
 fld.value=val;
 return(true);
}
function do_submit()
{
 document.form1.submit();
}
function do_reset()
{
 document.form1.scaninput.style.display='none';
 document.form1.scaninput.value="ClEaR";
 document.form1.submit();
}
function chk_sel()
{
 document.getElementById('B1').disabled=true;
 var ele = document.getElementsByName('POs[]');
 for (var i = 0; i < ele.length; i++) {
  if (ele[i].checked) document.getElementById('B1').disabled=false;
}
}
</script>

HTML;

$pg->title = "Vendor Select";
$thisprogram = $_SERVER["SCRIPT_NAME"];

if (isset($_REQUEST["scaninput"])) $scaninput = trim($_REQUEST["scaninput"]); else $scaninput = "";
if (isset($_REQUEST["func"])) $func = $_REQUEST["func"]; else $func = "";
if (isset($_REQUEST["comp"])) $comp = $_REQUEST["comp"]; else $comp = $main_ms;
if (isset($_REQUEST["msg"])) $msg = $_REQUEST["msg"]; else $msg = "";
if (isset($_REQUEST["msgColor"])) $msgColor = $_REQUEST["msgColor"]; else $msgColor = "";
if (isset($_REQUEST["vendor"])) $vendor = $_REQUEST["vendor"]; else $vendor = "";
if (isset($_REQUEST["incCust"])) $incCust = $_REQUEST["incCust"]; else $incCust = "";
if ($func == "selectVend") $vendor = $scaninput;
$htm = "";
if ($scaninput <> "" and $vendor == "") $vendor = $scaninput;
if ($msg <> "") $pg->msg = $msg;
if ($msgColor <> "") $pg->msgColor = $msgColor;
if ($scaninput == "ClEaR") {
    $func = "";
    $vendor = "";
}
$vendor = strtoupper($vendor);
//echo "vendor={$vendor}\n";
$v = urlencode($vendor . "%");
$u = "";
if ($incCust > 0 or $incCust === true) $u = "&etype=c";
$url = "http://localhost/{$wmsServer}/sVendor.php?stype=j&vendor={$v}{$u}";
$x = file_get_contents($url);
$vend = json_decode($x, true);
$detail = "";
if ($vendor == "") {
    $_SESSION["rf"]["function"] = "RPO";
    $_SESSION["rf"]["vend"] = $vend;
} // end vendor <> ""
else { // vendor is empty
//Vendor input
//get vendors
    $v = urlencode($vendor . "%");
    $u = "";
    if ($incCust > 0 or $incCust === true) $u = "&etype=c";
    $url = "http://localhost/{$wmsServer}/sVendor.php?stype=j&vendor={$v}{$u}";
    $x = file_get_contents($url);
    $vend = json_decode($x, true);
    $pinfo = <<<HTML
<div class="table-responsive">
      <form name="form2" action="{$wmsDir}/rf/recv_po.php" method="get">
      <input type="hidden" name="func" value="selectVend">
      <input type="hidden" name="recvType" value="{$recvType}">
      <input type="hidden" name="recvTo" value="{$recvTo}">
      <table class="table table-bordered table-striped">
       <tr>
        <th class="FieldCaptionTD">Vendor</th>
        <th class="FieldCaptionTD">Name</th>
       </tr>
_DETAIL_
      </table>
      </form>
     </div>

HTML;
    if (count($vend) > 0) {
        foreach ($vend as $key => $data) {
            $v = $data["vendor"];
            $detail .= <<<HTML
       <tr>
        <td>
        <a href="recv_po.php?func=vendor&scaninput={$data["vendor"]}&recvType={$recvType}&recvTo={$recvTo}" class="btn btn-success" target="_self"> {$v}</a>
</td>
        <td>{$data["name"]}</td>
       </tr>

HTML;
        } // end foreach vend
    } //end count vend > 0
} // end vendor is empty
if ($detail <> "") $pinfo = str_replace("_DETAIL_", $detail, $pinfo);
else {
    if ($scaninput <> "") {
        $pinfo = <<<HTML
      <div class="w3-container w3-blue w3-padding-8">
       <div class="w3-clear"></div>
<span class="w3-red"><strong>{$scaninput} is an Invalid Vendor</span>
<br>
      </div>
      </div>

HTML;
    }
    $scaninput = "";
    $vendor = "";
}

//$showScreenSize=<<<HTML
//<script>
//var sz='width=' + screen.width + ' height=' + screen.height;
//alert(sz);
//</script>
//
//HTML;

$pg->dispLogo = false;
$ch = 0;
$checked = "";
if ($incCust) $ch = 1;
if ($ch > 0) $checked = "checked";
$pg->body = <<<HTML
 <form name="form1" action="{$thisprogram}" method="get">
 <input type="hidden" name="func" value="">
 <input type="hidden" name="recvType" value="{$recvType}">
 <input type="hidden" name="recvTo" value="{$recvTo}">

  <div class="w3-row-padding w3-margin-bottom">
    <div class="w3-half">
      <div class="w3-container w3-blue w3-padding-8">
        <div class="w3-clear"></div>
                <label>Vendor</label>
<input class="w3-white" onchange="do_submit();" id="scaninput" name="scaninput" placeholder="Enter Vendor" value="{$vendor}" onfocus="movetoend(this);">
&nbsp;
<input type="checkbox" name="incCust" value="{$ch}" {$checked}  onclick="setCBvalue(this);"> Include Customers
<br>

      </div>
{$pinfo}
    </div>
<br>
        <div class="w3-clear"></div>
<br>

<button class="binbutton" type="button" value="clr" onclick="do_reset1();">Clear</button></td>
<button class="binbutton" type="button" value="Can" onclick="do_cancel();">Cancel</button></td>
  </div>
 </form>
<script>
function setCBvalue(ele)
{
     ele.value = ele.checked ;
}
function do_cancel()
{
 document.form1.action="recv_po.php";
 document.form1.scaninput.value="";
 document.form1.submit();
 
}
function do_reset1()
{
 document.form1.scaninput.style.display='none';
 document.form1.scaninput.value="";
 document.form1.submit();
}

</script>

HTML;

$pg->Display();
?>
