<?php

// ordEdit.php -- Order Edit
// 01/15/24 dse initial
/*TODO

*/

//echo "<pre>D1";
//print_r($_REQUEST);
//echo "</pre>";

$extraJS=false;
if (isset($_REQUEST["B2"]) and $_REQUEST["B2"] == "cancel")
{
 $tote="";
 if (isset($_REQUEST["scanned"]))
 {
  $j=count($_REQUEST["scanned"]);
  if ($j > 1) $tote="Totes: "; else $tote="Tote: ";
  $comma="";
  foreach ($_REQUEST["scanned"] as $key=>$val)
  {
   $tote.="{$comma}{$val}";
   $comma=",";
  } // end foreach scanned
 } // end scanned is set
 $msgCancel="Cancelled processing {$tote}";
 $_REQUEST=[];
} // end b2 is set
foreach (array_keys($_REQUEST) as $w) { $$w=$_REQUEST[$w]; }
//error_reporting(0);

session_start();
if (get_cfg_var('wmsdir')) $wmsDir=get_cfg_var('wmsdir');
else { echo "<h1>WMS System is not Configured on this System</h1>"; exit; }
$top=str_replace("/var/www","",$wmsDir);

if (!isset($nh)) $nh=0;

$thisprogram=basename($_SERVER["PHP_SELF"]);
require("{$wmsDir}/config.php");
require_once("{$wmsInclude}/chklogin.php");
require_once("{$wmsInclude}/cl_rf1.php");
require_once("{$wmsInclude}/cl_template.php");
require_once("{$wmsInclude}/cl_PARTS2.php");
require_once("{$wmsInclude}/db_main.php");
require_once("{$wmsInclude}/date_functions.php");
require_once("{$wmsInclude}/get_option.php");
require_once("../include/restSrv.php");

$output=""; // temp debug variable

$RESTSRV="http://{$wmsIp}{$wmsServer}/PICK_srv.php";
$PARTSRV="http://{$wmsIp}{$wmsServer}/whse_srv.php";
$ShipSRV="http://{$wmsIp}{$wmsServer}/WMS2ERP.php";
$comp=$wmsDefComp;
$db=new WMS_DB;
$opt=array();

// Application Specific Variables -------------------------------------
$temPlate="generic1";
$title="Packing";
$panelTitle="Order #";
//$SRVPHP="{$wmsServer}/COMPANY_srv.php";
//$DRPSRV="{$wmsServer}/dropdowns.php";
// end application specific variables ---------------------------------

$opt[102]=get_option($db,$comp,102);
$opt[103]=get_option($db,$comp,103);
$packZones=getPackZones($db,$comp);
//echo "<pre>";
//print_r($packZones);
//echo "</pre>";


if (isset($fPQ) and $fPQ > 0 and isset($B2))
{
 // redirect to Pick Queue screen
 $htm=<<<HTML
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
if (!isset($func)) $func="scanScreen";
if (!isset($msg)) $msg="";

switch ($func)
{
 case "scanScreen":
 { // Display Scan Tote screen
  if (isset($msg)) $msg="";
  if (isset($msgCancel)) $msg=$msgCancel;
  $color="blue";
  if ($msg <> "") $color="green";
  $mainSection=entOrderTote($msg,$color);
  break;
 } // End Display Scan Tote screen

 case "packit":
 {
  $req=array("action"=>"fetchOrder",
  "company"=>$comp,
  "scaninput"=>$hostordernum,
  "process"=>"PACK"
   );
 $ret=restSrv($RESTSRV,$req);
 $w=(json_decode($ret,true));
 if (isset($w["Order"])) 
 { // display Order and Tote Info
  $host_order_num = $w["Order"]["host_order_num"];


if ($w["Order"]["order_num"] > 0)
{
 $order_num=$w["Order"]["order_num"];
 $hostOrder=$w["Order"]["host_order_num"];
 // check unpicked
 if (count($w["unPicked"]) < 1 or $w["Order"]["order_stat"] == -2)
 {
   $contr=0;
  if (isset($w["Totes"]) and count($w["Totes"]) > 0)
  {
   // release all the totes into same container and ship it
   foreach ($w["Totes"] as $t)
   {
    $req=array("action"=>"releaseTote",
  "order_num" => $order_num,
  "comp"=> $comp,
  "toteId"=> $t["tote_num"],
  "container"=> $contr
   );
    $ret=restSrv($ShipSRV,$req);
    $rdata=(json_decode($ret,true));
    if (isset($rdata["Container"])) $contr=$rdata["Container"];
   } // end foreach tote
//echo "<pre>";
//print_r($rdata);
   
  } // end there are totes
 $req=array("action"=>"Ship",
  "order_num" => $order_num,
  "comp"=> $comp,
  "override"=>"1"
   );
  $ret=restSrv($ShipSRV,$req);
  $rdata=(json_decode($ret,true));
 //echo "<pre>D2";
  //print_r($rdata);
//exit;
  if (isset($msg)) $msg="";
  if ($contr > 0) $msg="{$hostOrder} Shipped in Container {$contr}";
  $color="blue";
  if ($msg <> "") $color="green";
  $mainSection=entOrderTote($msg,$color);
  break;
 } // end unpicked < 1
 else
echo "Display unpicked screen";
exit;
} // order number is set

//  echo "<pre>";
//print_r($w); 
 } // display Order and Tote Info

  break;
 } // end packit

 case "orderOrTote":
 {
 if (isset($scaninput) and $scaninput <> "")
  { // get info for scanned order or scanned tote
 $req=array("action"=>"fetchOrder",
  "company"=>$comp,
  "scaninput"=>$scaninput,
  "process"=>"PACK"
   );
//echo "<pre>";
//print_r($req);
 $ret=restSrv($RESTSRV,$req);
 $w=(json_decode($ret,true));
//echo $ret;
//echo "<pre>";
//print_r($w);
//echo "</pre>";

  } // end get by order num or tote id
 if (isset($w["Order"])) 
 { // display Order and Tote Info
  $host_order_num = $w["Order"]["host_order_num"];
//echo "<pre>D3";
//print_r($w);
//echo "</pre>";

  $scan=$scaninput;
  $mainSection=dispOrdInfo($w);
  $menuSubmit="Finalize";
if (1 == 2)
{
  $hiddens=<<<HTML
  <input type="hidden" name="func" id="func" value="orderOrTote">
  <input type="hidden" name="nh" value="{$nh}">
  <input type="hidden" name="scanTote" value="">
HTML;

  $x=$w["Order"];
  $dr=eur_to_usa($x["date_required"],false);
  $numTotes=count($w["Totes"]);
  $msg="";
  if (count($w["unPicked"]) > 0) $msg="Order is still being Picked in other Zones";
  $numLines=count($w["LineTote"]);
  $data=array("formName"=>"form1",
                          "formAction"=>$thisprogram,
                          "hiddens"=>$hiddens,
                          "color"=>"blue",
                          "hostordernum"=>$x["host_order_num"],
                          "order_num"=>$x["order_num"],
                          "customer_id"=>$x["customer_id"],
                          "cust_po_num"=>$x["cust_po_num"],
                          "date_req"=>$dr,
                          "ship_via"=>$x["ship_via"],
                          "ship_complete"=>$x["ship_complete"],
                          "priority"=>$x["priority"],
                          "zones"=>$x["zones"],
                          "numLines"=>$numLines,
                          "lines"=>$w["LineTote"],
                          "numTotes"=>$numTotes,
                          "totes"=>$w["Totes"],
                          "unPicked"=>$w["unPicked"],
                          "msg"=>$msg
  );
  $mainSection=frmtScreen($data,$thisprogram,"dispToteDtl");
} // end 1 == 2
  if (!isset($msg)) $msg="";
  //$msg="";
 } // display Order and Tote Info
 else
 { // tote or Order not found
  $msg="Order not Found or Tote is not used for an order.";
  $color="red";
  $mainSection=entOrderTote($msg,$color);
  break;
 } // tote or Order not found
  break;
 } // end orderOrTote
 case "showDetail":
 {
 if (isset($detailTote) and $detailTote <> "")
  { // get info for tote
 $req=array("action"=>"getToteDetail",
  "tote_id"=>$detailTote,
  "order_num"=>$orderFound
   );
  } // end get by order num or tote id
 $ret=restSrv($RESTSRV,$req);
 $w=(json_decode($ret,true));
 $cls="";
 if (isset($w["Order"]["last_zone"])) $lz=$w["Order"]["last_zone"]; else $lz="";
 $sc="";
 if (isset($scanned[0]) and $scanned[0] <> "") $scaninput=$scanned[0]; else $scaninput=$hostordernum;
 $menuSubmit="Close";
 $mainSection=<<<HTML
  <form name="form1" action="ordEdit.php" method="get">
  <input type="hidden" name="func" id="func" value="orderOrTote">
{$sc}
  <input type="hidden" name="nh" value="0">
  <input type="hidden" name="scanTote" value="">
  <input type="hidden" name="scaninput" value="{$scaninput}">
  <input type="hidden" name="orderFound" value="{$orderFound}">
  <input type="hidden" name="hostordernum" value="{$hostordernum}">
HTML;
 $mainSection.=toteDtlTable($w["Tote"],$cls,"Tote {$detailTote} Contents",$lz,true);
 $mainSection.=<<<HTML
<button class="binbutton-small" id="B1" name="B1" onclick="document.form1.submit();">Close</button>

  </form>
HTML;
//echo "<pre>";
//print_r($w);
  break;
 } // end showDetail

case "scanVerify":
 {
 $ppl="";
 $ppart="";
 $pdesc="";
 $pqty="";
 $tqty="";
 $nh=$nh;
 $tmp_totes="";
 $hidden_totes="";
 if (isset($orderFound)) $order_num=$orderFound;
 if (isset($_SESSION["wms"]["UserID"])) $user=$_SESSION["wms"]["UserID"]; else $user=0;
 $pageRefreshed = isset($_SERVER['HTTP_CACHE_CONTROL']) &&($_SERVER['HTTP_CACHE_CONTROL'] === 'max-age=0' ||  $_SERVER['HTTP_CACHE_CONTROL'] == 'no-cache'); 
 if($pageRefreshed == 1)
 { 
  $scaninput=""; //  Yes page Refreshed
 }

 if (isset($scaninput) and trim($scaninput) <> "")
 { // scaninput is set
  if (!isset($msg)) $msg="";
  $req=array(
   "action"=> "scanVerify",
  "company"=> $comp,
  "order_num"=> $order_num,
  "totes"=> "", // don't use totes verifycation right now
  "user_id"=> $user,
  "partNumber"=>$scaninput,
   "updqty"=>0,
   "uom"=>""
  );
  $ret=restSrv($RESTSRV,$req);
  $w=(json_decode($ret,true));
  if (isset($w["Part"]))
  { // valid part
   $ppl=$w["Part"]["pl"];
   $ppart=$w["Part"]["partNumber"];
   $pdesc=$w["Part"]["partDesc"];
   $pqty=$w["Part"]["qty"];
   if (isset($w["totQty"])) $tqty=$w["totQty"];
   else $tqty=$w["Part"]["qty"];

  }  // valid part
  if (isset($w["errText"]))
  { // there is a message
   $msg=$w["errText"];
  } // there is a message
 } // scaninput is set
 if (isset($scanTote)) $tmp_totes = $scanTote;
 if (isset($totes) and is_array($totes))
  { // there is more than 1 tote to verify
   $comma="";
   $tmp_totes="";
   foreach($totes as $key=>$t)
   {
    $tmp_totes.="{$comma}{$t["tote_id"]}";
    $comma=",";
    $hidden_totes.=<<<HTML
 <input type="hidden" name="totes[]" values="{$t["tote_id"]}">

HTML;
   } // end foreach totes
  } // there is more than 1 tote to verify
 $title="Verifing  {$tmp_totes}"; 
if (!isset($color)) $color="blue";

    $hiddens=<<<HTML
  <input type="hidden" name="func" value="scanVerify">
  <input type="hidden" name="order_num" id="order_num" value="{$order_num}">
  <input type="hidden" name="nh" value="{$nh}">
  <input type="hidden" name="hostordernum" value="{$hostordernum}">
HTML;


 $data=array("formName"=>"form1",
              "formAction"=>$thisprogram,
              "hiddens"=>$hiddens,
              "color"=>"w3-{$color}",
              "onChange"=>"do_submit();",
              "ppl"=>$ppl,
              "ppart"=>$ppart,
              "pdesc"=>$pdesc,
              "pqty"=>$pqty,
              "tqty"=>$tqty,
              "fieldType"=>"text",
              "fieldValue"=>"",
              "fieldPrompt"=>"Scan Part",
              "fieldPlaceHolder"=>"Scan Part UPC",
              "fieldName"=>"scaninput",
              "fieldId"=>"thescan",
              "fieldTitle"=>"",
              "msg"=>$msg,
              "msg2"=>"",
              "totes"=>$tmp_totes
    );
//echo "<pre>{$ret}";
//print_r($w);
//print_r($data);
//echo "</pre>";

  $mainSection=frmtScreen($data,$thisprogram,"scanVerify");
  break;

 } // end scanVerify
case "scanVerDone":
{
 if (isset($orderFound)) $order_num=$orderFound;
 // get Order into for top section
  $req=array("action"=>"fetchOrder",
  "company"=>$comp,
  "order_num"=>$order_num
   );
 $ret=restSrv($RESTSRV,$req);
 $ord=(json_decode($ret,true));
 $ordInfo="";
 if (isset($ord["Order"]))
 { // display Order and unset tote and line item info
   unset($ord["LineTote"]);
   unset($ord["Totes"]);
   unset($ord["unPicked"]);
   unset($ord["Items"]);
   $ord["LineTote"]=array();
   $ord["Totes"]=array();
   $ord["unPicked"]=array();
   $ord["Items"]=array();
   $host_order_num = $w["Order"]["host_order_num"];
   $ordInfo=dispOrdInfo($ord,true);
 } // display Order and unset tote and line item info

 // note: totes can be passed to server too as an inclause,
 // ie "totes"=>"103" or "totes"=>"103,124"


 $req=array(
  "action"=> "verifyResults",
  "company"=> 1,
  "order_num"=> $order_num
 );
  $ret=restSrv($RESTSRV,$req);
  $w=(json_decode($ret,true));
  $okToPass=true;
  if (count($w) > 0)
  {
   $flds=array( 0=>"P/L",
		1=>"Part Number",
		2=>"Description",
		3=>"Ordered",
		4=>"Scanned",
		5=>"&nbsp;",
		6=>"Expected",
		7=>"Avail"
   );
   $htm=<<<HTML
<link rel="stylesheet" href="../assets/css/responsiveTable.css">
<table role="table" class="rspTable table table-bordered table-striped">
  <thead role="rowgroup">
    <tr role="row">

HTML;
   foreach($flds as $f)
   {
    $htm.=<<<HTML
        <td role"columnheader" class="FieldCaptionTD" align="center" width="10%">{$f}</td>

HTML;
   } // end foreach flds
   $htm.=<<<HTML
    </tr>
  </thead>
  <tbody role="rowgroup">

HTML;
   foreach($w as $key=>$item)
   {
    if (is_array($item))
    {
     $ind='correct.png';
     if ($item["status"] > 0) $ind='moreqty.png';
     if ($item["status"] < 0 and $item["status"] <> -9) $ind='lessqty.png';
     if ($item["status"] == -9) $ind='incorrect.png';
     if ($item["status"] <> 0) $okToPass=false;
     $ind1="";
     $cls="";
     $clss="";
     if ($ind == "lessqty.png" or $ind == "moreqty.png")
     {
      $clss="class=\"Alt2DataTD\"";
      $ind1=<<<HTML
<img src="../images/incorrect.png"/ width="16" height="16">
HTML;
     }

     $htm.=<<<HTML
       <tr role="row">
        <td {$cls} data-label="{$flds[0]}" align="center">{$item["p_l"]}</td>
        <td {$cls} data-label="{$flds[1]}" align="center">{$item["part_number"]}</td>
        <td {$cls} data-label="{$flds[2]}" align="center">{$item["part_desc"]}</td>
        <td {$cls} data-label="{$flds[3]}" align="center">{$item["qty_ord"]}</td>
        <td {$clss} data-label="{$flds[4]}" align="center">{$item["qty_scanned"]}</td>
        <td {$cls} data-label="{$flds[5]}" align="center">{$ind1}<img src="../images/{$ind}"/ width="16" height="16"></td>
        <td {$cls} data-label="{$flds[6]}" align="center">{$item["qty_ship"]}</td>
        <td {$cls} data-label="{$flds[7]}" align="center">{$item["qty_avail"]}</td>
       </tr>
    
HTML;
    } // item is array
   } // end foreach w
   $htm.=<<<HTML
   </table>

HTML;
  $mainSection=<<<HTML
{$ordInfo}
{$htm}

HTML;
  } // end count w > 0
//echo "<pre>";
//print_r($w);
 
 break;
} // end scanVerDone

case "verifyTote":
 {
//echo "<pre>Order={$order_num} {$hostordernum} {$comp}\n";
  $req=array("action"=>"chkOrdTote",
    "company"=>$comp,
    "order_num" => $order_num
     );
    $rc=restSrv($RESTSRV,$req);
    $toteInfo=(json_decode($rc,true));
//print_r($toteInfo);

 
 } // verifyTote

} // end switch func

$pg=new displayRF;
$pg->viewport="1.0";
$pg->dispLogo=false;
$pg->Bootstrap=true;
if (isset($menuSubmit) and $menuSubmit <> "")
{
 $pg->addMenuLink("javascript:do_pack('packit');","{$menuSubmit}");
}

if (isset($title)) $pg->title=$title;
if (isset($color)) $pg->color=$color; else $color="blue";
$ejs="";
if (isset($nh) and $nh > 0)
{
 $pg->noHeader=true;
}

if (!isset($otherScripts)) $otherScripts="";
$pg->jsh=<<<HTML
<style>
.binbutton-small:disabled,
.binbutton-small[disabled]{
  border: 1px solid #dddddd;
  background-color: #999999;
  color: #666666;
}
</style>
<script>
function do_pack(arg)
{
 document.form1.func.value=arg;
 document.form1.submit();
}

function okShipQty(idx,picked)
{
 var vId='q2ship' + idx;
 var bId='q2ship' + idx;
 var BSP=document.getElementById(bId);
 document.getElementById(vId).innerHTML=picked;
 BSP.style.display='none';
 // check Bship array
 var ok=true;
 //for (var i =0; i < BSD.length; i++)
 //{
  //if (BSD[idx].style.display !== 'none') ok=false;
 //}
 if (ok) document.getElementById('BS1').disabled=false;
}

function setNewLoc(tote,newzone,newloc)
{
//alert(newzone + ' ' + newloc);
 var z=document.getElementById("lastZone" + tote);
 var l=document.getElementById("lastLoc" + tote);
 var s=document.getElementById("showStage" + tote);
 z.textContent=newzone;
 l.textContent=newloc;
 s.style.display='none';
 document.getElementById("row" + tote).classList.add('w3-green');

} 
function openalt(url,nlns) {
        hgt=210 + (nlns * 25);
        popup=window.open(url,"popup", "toolbar=no,left=0,top=125,status=yes,resizable=yes,scrollbars=yes,width=600,height=" + hgt );
 return(false);
     }

function showItems(ordnum)
{
 var url="orddtl.php?orderNum=" + ordnum;
 openalt(url,10);
 return false;
}
</script>

</script>
HTML;
if (isset($js)) $pg->jsh.=$js;
$pg->Display();
//Rest of page
$extraJSS="";
if ($extraJS) $extraJSS=<<<HTML
<script>
 document.getElementById('BS1').disabled=true;
</script>
HTML;

$htm=<<<HTML
  {$mainSection}
  {$otherScripts}
  {$extraJSS}
 </body>
</html>

HTML;
echo $htm;
//echo "<pre>";
//echo $output;
//print_r($w);


function frmtScreen($data,$thisprogram,$temPlate="generic1",$incFunction=true)
{
 $ret="";
 $parser = new parser;
 $parser->theme("en");
 $parser->config->show=false;
 $ret=$parser->parse($temPlate,$data);
 if ($incFunction)
 {
  $ret.=<<<HTML
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

function entOrderTote($msg,$color="blue")
{
 global $thisprogram;
 global $nh;
    $hiddens=<<<HTML
  <input type="hidden" name="func" id="func" value="orderOrTote">
  <input type="hidden" name="nh" value="{$nh}">
  <input type="hidden" name="scanTote" value="">
HTML;

   $fieldPrompt="Order or Tote";
   $fieldPlaceHolder="Scan or Order or Tote";
   $fieldId=" id=\"thescan\"";
   $fieldTitle=" title=\"Scan or Enter the Order # or the Tote Id\"";
   $extra_js="";
 
   $data=array("formName"=>"form1",
              "formAction"=>$thisprogram,
              "hiddens"=>$hiddens,
              "color"=>"w3-{$color}",
              "onChange"=>"do_submit();",
              "fieldType"=>"text",
              "fieldValue"=>"",
              "fieldPrompt"=>$fieldPrompt,
              "fieldPlaceHolder"=>$fieldPlaceHolder,
              "fieldName"=>"scaninput",
              "fieldId"=>$fieldId,
              "fieldTitle"=>$fieldTitle,
              "msg"=>$msg,
              "msg2"=>"",
              "function"=>""
    );
  $ret=frmtScreen($data,$thisprogram);
  return $ret;
} // end entOrderTote

function dispOrdInfo($w,$inVerify=false)
{
 global $thisprogram;
 global $nh;
 global $scan;
 global $packZones;
 global $extraJS;
 global $output;
 $funchtm="";
 $conthtm="";
  if ($w["by"] == 1)
  { // looked up by order, find totes to make sure they scan them all
    unset($scan);
    $scan="";
  } // looked up by order, find totes to make sure they scan them all
  if ($w["by"] == 2)
  { // looked by by tote, find any other totes to make sure they scan them all
  } // looked by by tote, find any other totes to make sure they scan them all
 $x=$w["Order"];
 $B1Prompt="";
    $hiddens=<<<HTML
  <input type="hidden" name="func" id="func" value="orderOrTote">
  <input type="hidden" name="nh" value="{$nh}">
  <input type="hidden" name="scanTote" value="">
  <input type="hidden" name="scanned[]" value="{$scan}">
HTML;
  $dr=eur_to_usa($x["date_required"],false);

 $htm=<<<HTML
   <div class="panel-body">
    <div class="table-responsive">
     <form name="form1" action="{$thisprogram}" method="get">
{$hiddens}
      <input type="hidden" name="orderFound" value="{$x["order_num"]}">
      <input type="hidden" name="hostordernum" value="{$x["host_order_num"]}">
      <input type="hidden" name="detailTote" value="">

HTML;
  $msg="";
  $ok2Pack=true;
  if (count($w["unPicked"]) > 0)
  {
   $msg="Order is still being Picked in other Zones";
   if ($w["Order"]["order_stat"] <> -2) $ok2Pack=false;
  }
if ($msg <> "")
$htm.=<<<HTML
<div  style="margin-left:0px;" class="w3-container wms-red"><span style="font-weight: bold; font-size: large; text-align: center;">{$msg}</span></div>

HTML;

$name=$x["name"];
$addr=$x["addr1"];
if (trim($addr) <> "") $addr.="<br>";
$addr.=$x["addr2"];
$city=trim($x["city"]);
$city.=", {$x["state"]} {$x["zip"]} {$x["ctry"]}";
$colStyle=collapseCss();
$colJs=collapseJs();

$lineItems=dispItems($w);

$ordcust=<<<HTML
{$colStyle}
       <div class="collapsible">
        <span class="wmsBold">Order #:</span>
        <span>{$x["host_order_num"]}&nbsp;&nbsp;&nbsp;&nbsp;</span>
        <span class="wmsBold">Customer:</span>
        <span>{$x["customer_id"]}&nbsp;&nbsp;</span>
        <span><strong>{$name}</strong></span>
       </div>
       <div class="content">
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
{$lineItems}
HTML;

$xordcust=<<<HTML
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
HTML;

$ordcust.=<<<HTML
  {$colJs}

HTML;

 if ($inVerify)
 {
  $htm.=<<<HTML
  {$ordcust}
     </form>
    </div>
   </div>
HTML;

  return $ordcust;
 } // end inVerify
 $totehtm="";
 $addShowDetail=false;
if (count($w["Totes"]) > 0)
{
 $totes=array();
 foreach ($w["Totes"] as $key=>$t)
 {
  $tote_code=$t["tote_id"];
  $totes[$t["tote_num"]]["tote_code"]=$tote_code;
  $totes[$t["tote_num"]]["last_zone"]=$t["last_zone"];
  $totes[$t["tote_num"]]["last_loc"]=$t["last_loc"];
 } // end foreach w Totes
 if (count($w["LineTote"]) > 0)
 {
  $i=0;
  foreach ($w["LineTote"] as $key=>$t)
  {
   $i++;
   $t_id=$t["tote_id"];
   $totes[$t_id]["contents"][$i]=$t;
   $totes[$t_id]["contents"][$i]["tote_code"]=$tote_code;
  } // end foreach LineTote
 } // end count LineTote
if (count($w["unPicked"]) > 0)
 {
  $i=0;
  foreach ($w["unPicked"] as $key=>$t)
  {
   $i++;
   $totes["NP{$i}"]["last_zone"]=$t["zone"];
   $totes["NP{$i}"]["numRows"]=$t["numRows"];
   $totes["NP{$i}"]["last_loc"]="Not Picked";
  } // end foreach unPicked
 } // end count unPicked > 0
 $conthtm=<<<HTML
         <table class="table table-bordered table-striped">
          <tr>
           <td colspan="5" class="FormSubHeaderFont">Totes and Contents</td>
          </tr>
          <tr>
           <th class="FieldCaptionTD">Tote Id</th>
           <th class="FieldCaptionTD">Part Number</th>
           <th class="FieldCaptionTD">Ordered</th>
           <th class="FieldCaptionTD">Ship</th>
           <th class="FieldCaptionTD">UOM</th>
           <th class="FieldCaptionTD">Zone</th>
          </tr>

HTML;
 $totehtm=<<<HTML
         <table class="table table-bordered table-striped">
          <tr>
           <td colspan="3" class="FormSubHeaderFont">Totes</td>
          </tr>
          <tr>
           <th class="FieldCaptionTD">Tote Id</th>
           <th class="FieldCaptionTD">Zone</th>
           <th class="FieldCaptionTD">Last Location</th>
           <th class="FieldCaptionTD">&nbsp;</th>
          </tr>

HTML;
$okVerify=false;
$i=0;
$i1=0;
//echo "<pre>TOTES:";
//print_r($totes);
//echo "End</pre>";
foreach ($totes as $key=>$t)
{ // pass1, see if verify is allowed
 $i++;
 if (isset($packZones[$t["last_loc"]]) and $packZones[$t["last_loc"]]["zone_type"] == "PKG" or $t["last_loc"] == "PACK") $i1++;
} // pass1, see if verify is allowed
if ($i == $i1) $okVerify=true;
foreach ($totes as $key=>$t)
{
 $cls="";
 $j=$key;
 if (substr($key,0,2) == "NP") // check for not picked
  {
    $cls=" class=\"wms-red\"";
    $j1=$t["numRows"];
    $s="";
    if ($j1 > 1 or $j1 == 0) $s="s";
    $j="{$t["numRows"]} Item{$s}";
  }
 $but="";
 if ($t["last_loc"] == "PACK") $okVerify=true;
 //if (isset($t["last_zone"]) and $t["last_zone"] == "STG")
 if ($t["last_zone"] == "STG" and $t["last_loc"] <> "PACK")
 {
  $okVerify=false;
  $cls=" class=\"wms-green\"";
 }
 if ($okVerify)
 {
  $but=<<<HTML
<input type="hidden" name="scaninput" value="">
<input type="button" name="showStage{$key}" id="showStage{$key}" onclick="showStageIt('{$key}');" class="btn btn-info btn-xs" value="Stage It">
<script>
function showStageIt(tote)
{
 var url="parkit.php?toteId=" + tote 
+ "&nh=1"
+ "&ord=" + {$x["order_num"]}
+ "&hord=" + {$x["host_order_num"]}
;

 document.form1.func.value="orderOrTote";
 document.form1.scaninput.value=tote;
 openalt(url,10);
 return false;
}
function scanVerify(tote)
{
 document.form1.func.value="scanVerify";
 document.form1.scanTote.value=tote;
 document.form1.submit();
}
</script>
HTML;
 } 
 if (isset($t["tote_code"]))
 {
 $j1=$t["tote_code"];
 $totehtm.=<<<HTML
          <tr id="row{$key}">
           <td{$cls}>{$j1}</td>
           <td{$cls}><span id="lastZone{$key}">{$t["last_zone"]}</span></td>
           <td{$cls}><span id="lastLoc{$key}">{$t["last_loc"]}</span></td>
           <td{$cls}>{$but}</td>
          </tr>

HTML;
 } // tote_code isset
 $i=0;
 if (isset($t["contents"]))
 {
  if (count($t["contents"]) > 9)
  { // too many parts to show on small screen, link to detail
    $i=count($t["contents"]);
    $addShowDetail=true;
     $conthtm.=<<<HTML
          <tr>
           <td colspan="2"{$cls} align="left">{$i} Items</td>
           <td colspan="3" align="left">
<button name="showDtl[{$j}]" onclick="showDetail('{$j}');" class="btn btn-info btn-xs">Show Detail</button>
</td>
          </tr>

HTML;
  } // too many parts to show on small screen, link to detail
  else
  { // less than 10 parts, show on screen
   $conthtm.=toteDtlTable($t["contents"],$cls,"Totes and Contents",$t["last_zone"],false);
  } // less than 10 parts, show on screen
 } // end contents is set
} // end foreach totes

if ($addShowDetail)
{
 $funchtm=<<<HTML
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

$totehtm.=<<<HTML
         </table>
        </td>
       </tr>
HTML;
$conthtm.=<<<HTML
         </table>
        </td>
       </tr>
HTML;
} // end count totes

$htm.=<<<HTML
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
if ($ok2Pack)
{
 $a="";
 if ($extraJS) $a="disabled ";
 $B1Prompt="Finalize";
$htm.=<<<HTML
<tr>
<td colspan="4">
         <button class="binbutton-small" {$a}id="BS1" name="B1" onclick="do_pack('packit');">{$B1Prompt}</button>

HTML;
} // end ok2pack

$svButton=<<<HTML
         <button class="binbutton-small" id="BV" name="BV" onclick="scanVerify('All');" value="Verify">Scan Verify</button>
HTML;

$htm.=<<<HTML
         <button type="button" class="binbutton-small" id="BL" name="BL" onclick="showItems({$x["host_order_num"]}); return false;" value="showD">Detail</button>
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
 $htm=<<<HTML
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
 $htm=<<<HTML
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

function getPackZones($db,$comp)
{
 $SQL=<<<SQL
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
$ret=array();
$rc=$db->query($SQL);
  $numrows=$db->num_rows();
  $i=1;
  while ($i <= $numrows)
  {
   $db->next_record();
     if ($numrows and $db->Record)
     {
      $zone=$db->f("zone");
      foreach ($db->Record as $key=>$data)
       {
        if (!is_numeric($key)) { $ret[$zone]["$key"]=$data; }
       }
     }
    $i++;
  } // while i < numrows
 return $ret;
} // end getPackZones
function toteDtlTable($contents,$cls,$title,$last_zone,$fullTable=false)
{
 $ret="";
 if ($fullTable)
 {
  $ret=<<<HTML
         <table class="table table-bordered table-striped">
          <tr>
           <td colspan="5" class="FormSubHeaderFont">{$title}</td>
          </tr>
          <tr>
           <th class="FieldCaptionTD">Tote Id</th>
           <th class="FieldCaptionTD">Part Number</th>
           <th class="FieldCaptionTD">Ordered</th>
           <th class="FieldCaptionTD">Ship</th>
           <th class="FieldCaptionTD">UOM</th>
           <th class="FieldCaptionTD">Zone</th>
          </tr>

HTML;

 } // end fulltable
 if (is_array($contents) and count($contents) > 0)
 foreach ($contents as $l=>$c)
   {
     $cls1="";
     if ($c["tote_qty"] > $c["qty_ord"]) $cls1='class="Alt2DataTD"';
     $ret.=<<<HTML
          <tr>
           <td{$cls}>{$c["tote_code"]}</td>
           <td align="left">{$c["p_l"]} {$c["part_number"]}</td>
           <td align="center">{$c["qty_ord"]}</td>
           <td {$cls1} align="center">{$c["tote_qty"]}</td>
           <td align="center">{$c["tote_uom"]}</td>
           <td{$cls} align="center">{$last_zone}</td>
          </tr>

HTML;
   } // end foreach contents
 if ($fullTable) $ret.="         </table>\n";
 return $ret;
} // end toteDtlTable

function dispItems($w)
{
 global $RESTSRV;
 global $comp;
 global $extraJS;
 $ret="";
 if (count($w["Items"]) > 0)
 {
  $title="Order Detail Items";
   $ret=<<<HTML
         <table class="table table-bordered table-striped">
          <tr>
           <td colspan="5" class="FormSubHeaderFont">{$title}</td>
          </tr>
          <tr>
           <th class="FieldCaptionTD">Bin</th>
           <th class="FieldCaptionTD">Part Number</th>
           <th class="FieldCaptionTD">Description</th>
           <th class="FieldCaptionTD">Ordered</th>
           <th class="FieldCaptionTD">Picked</th>
           <th class="FieldCaptionTD">Ship</th>
           <th class="FieldCaptionTD">UOM</th>
           <th class="FieldCaptionTD">Tote</th>
          </tr>

HTML;

  // lets get all the items, then total up w Items which came from ITEMPULL
  $req=array("action"=>"getAllItems",
  "company"=>$comp,
  "order_num"=>$w["Order"]["order_num"]
   );
 //$ret=restSrv($RESTSRV,$req);
 //$lineItems=(json_decode($ret,true));
echo "<pre>";
print_r($w);
//print_r($lineItems);
exit;

  foreach($w["Items"] as $key=>$d)
  {
   $picked=$d["qtyPicked"];
   $ship=$d["qty_ship"];
   $avail=$d["qty_avail"];
   $q2pick=$d["qty2Pick"];
   
   if (isset($d["totes"])) $totes=$d["totes"]; else $totes="";
   $cls=$d["cls"];
   $bin=$d["whse_loc"];
   $cls1="";
   if ($picked <> $q2pick) $cls1="class=\"AltDataTD\"";
   if ($picked > $q2pick) $cls1="class=\"Alt2DataTD\"";
   if ($picked > $d["qty_ord"]) $cls1="class=\"Alt2DataTD\"";
   if ($picked == 0) $cls1="class=\"Alt5DataTD\"";
   $slnk="{$ship}";
   $plnk="{$picked}";
  //TODO
   if (intval($picked) > intval($d["qty_ord"]))
   {
    // What to do if qty picked is > qty ordered
   } 
   if (intval($picked) < intval($ship))
   {
    $ln=$d["line_num"];
    $ff="q2ship{$ln}";
    $fb="Bship{$ln}";
    $slnk=<<<HTML
<span id="{$ff}">{$ship}</span>
HTML;
// TODO need to add line number to Bship id, and make function
// to set ship and check array of Bship to see if we can activate Pack It button
  $plnk.=<<<HTML
&nbsp;&nbsp;<div class="binbutton-tiny" id="{$fb}" name="ship{$ln}" onclick="okShipQty({$ln},{$picked});">OK</div>
HTML;
  $extraJS=true;
   }
   $ret.=<<<HTML
          <tr>
           <td nowrap align="center">{$bin}</td>
           <td nowrap align="left">{$d["p_l"]} {$d["part_number"]}</td>
           <td align="left">{$d["part_desc"]}</td>
           <td align="center">{$d["qty_ord"]}</td>
           <td {$cls1} align="center">{$plnk}</td>
           <td {$cls1} align="center">{$slnk}</td>
           <td align="center">{$d["uom"]}</td>
           <td align="center">{$totes}</td>
          </tr>

HTML;

  }  // end foreach Items
  $ret.="         </table>\n";
 } // end count items > 0
 return $ret;
} // end dispItems
 
?>
