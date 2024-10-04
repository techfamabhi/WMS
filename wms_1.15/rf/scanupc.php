<?php

session_start();
foreach (array_keys($_REQUEST) as $w) { $$w=$_REQUEST[$w]; }
//error_reporting(0);
//if (isset($Aisle))
//{
//echo "<pre>";
//print_r($_REQUEST);
//echo "</pre>";
//exit;
//}

if (!isset($Aisle)) $Aisle="";
if (!isset($lastBin)) $lastBin="";
if (!isset($func)) $func="";
if (!isset($msg)) $msg="";
if (!isset($UPC)) $UPC="";
if (!isset($idx)) $idx=0;
if (!isset($partRec)) $partRec=1;
if (!isset($nh)) $nh=0;
if (!isset($orderType)) $orderType="1";
if (get_cfg_var('wmsdir')) $wmsDir=get_cfg_var('wmsdir');
else { echo "<h1>WMS System is not Configured on this System</h1>"; exit; }
$top=str_replace("/var/www","",$wmsDir);

$mainTitle="Assign UPC Codes by Aisle";
$thisprogram=basename($_SERVER["PHP_SELF"]);
require("{$wmsDir}/config.php");
require_once("{$wmsInclude}/chklogin.php");
require_once("{$wmsInclude}/cl_rf2.php");
require_once("{$wmsInclude}/db_main.php");
require_once("{$wmsInclude}/get_option.php");
require_once("../include/restSrv.php");

$db=new WMS_DB;

$comp=1;
$opt=array();
//$opt[26]=get_option($db,$comp,26);

$UserId= $_SESSION["wms"]["UserID"];

$hiddens="";

if ($msg == "") $msgh="";
else $msgh=<<<HTML
 <div class="row">
    <div class="col-75">
     <span style="font-weight: bold; font-size: large; text-align: center;">{$msg}</span>
    </div>
   </div>

HTML;

$dhtm="";

if ($func == "")
{ // display Aisles
 $lastBin="";
 $aCnt=countAisles($db,$comp);
 $dhtm=frmtAisles($aCnt);
} // display Aisles

if ($func == "goToAisle")
{ // go to first part in aisle
 $parts=getAisle($db,$comp,$Aisle,$lastBin);

 if ($idx == 0) $idx=1;
 if (!isset($parts[$idx])) $idx=1;
 if (isset($parts[$idx])) $lastBin=$parts[$idx]["whs_location"];
//echo "<pre>idx={$idx}\n";
//print_r($parts);
//echo "</pre>";
 $dhtm=frmtParts($parts,$idx);
} // go to first part in aisle

if ($func == "scanUPC" and $idx > 0)
{  // got the part, scan the UPC
 $part=$rec[$idx];
 $title="Add UPC for {$part["p_l"]} {$part["part_number"]}";
 $hiddens=<<<HTML
  <input type="hidden" name="rec[shadow]"       value="{$part["shadow"]}">
  <input type="hidden" name="rec[p_l]"          value="{$part["p_l"]}">
  <input type="hidden" name="rec[part_number]"  value="{$part["part_number"]}">
  <input type="hidden" name="rec[uom]"          value="{$part["uom"]}">
  <input type="hidden" name="rec[whs_location]" value="{$part["whs_location"]}">
  <input type="hidden" name="rec[whs_qty]"      value="{$part["whs_qty"]}">
  <input type="hidden" name="rec[part_desc]"    value="{$part["part_desc"]}">

HTML;

 $dhtm=frmtUPC($part);
 
} // got the part, scan the UPC

$setFunc="";
if ($func == "noUPC" and $UPC == "")
{
 // set UPC to NOUPC, update the record and set func
 $part=$rec;
 $rc=logUPC($db,$part["shadow"],1,"NOUPC",1);
 $parts=getAisle($db,$comp,$Aisle,$lastBin);
 $dhtm=frmtParts($parts);
 $setFunc="goToAisle";
} // end UPCscan and UPC = ""

if ($func == "UPCscan" and $UPC <> "")
{
 // check the UPC to see if it is on file
 // also check it to see if it is a different part
 $setFunc="enterQty";
 $part=$rec;
 $ok=checkUPC($db,$UPC,$part["shadow"]);
 $hiddens=<<<HTML
  <input type="hidden" name="UPC"               value="{$UPC}">
  <input type="hidden" name="rec[shadow]"       value="{$part["shadow"]}">
  <input type="hidden" name="rec[p_l]"          value="{$part["p_l"]}">
  <input type="hidden" name="rec[part_number]"  value="{$part["part_number"]}">
  <input type="hidden" name="rec[uom]"          value="{$part["uom"]}">
  <input type="hidden" name="rec[whs_location]" value="{$part["whs_location"]}">
  <input type="hidden" name="rec[whs_qty]"      value="{$part["whs_qty"]}">
  <input type="hidden" name="rec[part_desc]"    value="{$part["part_desc"]}">

HTML;

 $msg="";
 switch ($ok)
 {
  case -1:
   $msg="UPC is already on file for this part";
   break;
  case -2:
   $msg="UPC is linked to another part";
   break;
  case -3:
   $msg="UPC is the part # of another part";
   break;
  case -4:
   $msg="UPC is already linked to this part";
   break;
  case -5:
   $msg="UPC is linked to multiple parts";
   break;
 } // end switch ok
 // display screen other than enter qty if ok < 0 above
 // either new function or change frmtQty
 $shadow=$rec["shadow"];
 $rc1=logUPC($db,$shadow,$ok,$UPC,$upcQty);
 $dhtm=frmtQty($part,$msg);
 $msg="";
}
if ($func == "enterQty" and $UPC <> "")
{
 $shadow=$rec["shadow"];
 $rc1=logUPC($db,$shadow,-1,$UPC,$upcQty);
//echo "<pre>";
//print_r($_REQUEST);
//echo "</pre>";
 $part=$rec;
 $rc=addUpc($db,$UPC,$rec,$upcQty);
 $parts=getAisle($db,$comp,$Aisle,$lastBin);
 $dhtm=frmtParts($parts);
 $setFunc="goToAisle";
}
if ($func == "cancel" and $UPC <> "")
{
 $parts=getAisle($db,$comp,$Aisle,$lastBin);
 $dhtm=frmtParts($parts);
}
if ($dhtm == "")
{ // display Aisles
 $lastBin="";
 $aCnt=countAisles($db,$comp);
 $dhtm=frmtAisles($aCnt);
} // display Aisles


// Display screen
$pg=new displayRF;
$pg->viewport="1.0";
$pg->dispLogo=false;

if (isset($mainTitle)) $pg->title=$mainTitle;
if (isset($color)) $pg->color=$color; else $color="blue";
$ejs="";
$js="";
if (isset($nh) and $nh > 0)
{
 $pg->noHeader=true;
}
$pg->jsh=$js;
$pg->Bootstrap=true;
$pg->Display();
$h=<<<HTML
<div id="content w3-blue" style="position:absolute; top:50px;left:0px; right:0px; overflow:auto;"> 
{$msg}
 <form name="form1" action="{$thisprogram}" method="get">
 <input type="hidden" name="func" id="func" value="{$setFunc}"">
 <input type="hidden" name="Aisle" id="Aisle" value="{$Aisle}">
 <input type="hidden" name="lastBin" id="lastBin" value="{$lastBin}">
 <input type="hidden" name="idx" value="{$idx}">
{$hiddens}
{$dhtm}
 </form>
</div>
</body>
</html>

HTML;

echo $h;
//echo "<pre>";
//print_r($aCnt);
//print_r($parts);


function countAisles($db,$comp)
{
 if ($comp < 1) $comp=1;
 $j=2;
 $SQL=<<<SQL
select
substring(whs_location,1,{$j}) as Aisle,
 count(*) as numParts,
 sum(whs_qty) as totalQty
from NEEDUPC,WHSELOC
where upc_status = 0
and whs_shadow = shadow
and whs_company = 1

group by substring(whs_location,1,{$j})

SQL;
 $ret=$db->gData($SQL);
 return $ret;
  
} // end countAisles

function getAisle($db,$comp,$aisle="%",$lastBin)
{
 $awhere="";
 if ($lastBin <> "") $awhere="and whs_location >=\"{$lastBin}\n\"";

 if ($comp < 1) $comp=1;
 $SQL=<<<SQL
 select
whs_location,
p_l,part_number,part_desc,
whs_qty,whs_code,unit_of_measure as uom,
shadow
from NEEDUPC,PARTS, WHSELOC
where  whs_location like "{$aisle}%"
{$awhere}
and upc_status = 0
and shadow_number = shadow
and whs_shadow = shadow
and whs_company = {$comp}
order by whs_location, p_l,part_number
limit 25

SQL;
//echo "<pre>{$SQL}\n";
 $ret=$db->gData($SQL);
//print_r($ret);
//exit;
 return $ret;
 
} // end getAisle

function frmtParts($parts,$partRec=1,$msg="")
{
 global $lastBin;
 if (count($parts) > 0)
 {
 $bin=$parts[$partRec]["whs_location"];
 $lastBin=$bin;
 $endRec=$partRec;
 $pcount=0;
 $p1=array();
 foreach($parts as $r=>$p)
 {
  if ($r >= $partRec)
  {
   if ($p["whs_location"] == $bin)
   {
    $p1[$endRec]=$p;
    $endRec++;
    $pcount++;
   }
   else break;
  } // end r > partRec
 }

  $cntPrompt="{$pcount} Parts in this Bin";
  if ($pcount == 25) $cntPrompt="First {$pcount} Parts in this Bin";
  if ($pcount == 1) $cntPrompt="{$pcount} Part in this Bin";
  $htm=<<<HTML
    <div class="col-50">
       <table width="60%">
           <tr>
            <td nowrap class="FormHeaderFont" colspan="4">Go to Bin {$bin}</td>
           </tr>
           <tr>
            <td nowrap class="FormSubHeaderFont" colspan="4">{$cntPrompt}</td>
           </tr>
           <tr>
            <td nowrap width="2%" class="FieldCaptionTD">&nbsp;</td>
            <td nowrap width="5%" class="FieldCaptionTD">BIN</td>
            <td nowrap width="1%" class="FieldCaptionTD">P/L</td>
            <td nowrap width="5%" class="FieldCaptionTD">Part #</td>
            <td nowrap align="center" width="5%" class="FieldCaptionTD">Part Qty</td>
            <td nowrap width="5%" class="FieldCaptionTD">Desc</td>
           </tr>

HTML;

 foreach($p1 as $r=>$p)
 {
  $htm.=<<<HTML
  <input type="hidden" name="rec[{$r}][shadow]" value="{$p["shadow"]}">
  <input type="hidden" name="rec[{$r}][p_l]" value="{$p["p_l"]}">
  <input type="hidden" name="rec[{$r}][part_number]" value="{$p["part_number"]}">
  <input type="hidden" name="rec[{$r}][uom]"          value="{$p["uom"]}">
  <input type="hidden" name="rec[{$r}][whs_location]" value="{$p["whs_location"]}">
  <input type="hidden" name="rec[{$r}][whs_qty]" value="{$p["whs_qty"]}">
  <input type="hidden" name="rec[{$r}][part_desc]" value="{$p["part_desc"]}">
           <tr>
            <td nowrap class="DataTD"><div class="binbutton-tiny" id="Scan" name="Scan" onclick="letScan('{$r}');">Scan</div></td>
            <td nowrap class="DataTD">{$p["whs_location"]}&nbsp;</td>
            <td nowrap class="DataTD">{$p["p_l"]}</td>
            <td nowrap class="DataTD">{$p["part_number"]}</td>
            <td nowrap align="center" class="DataTD">{$p["whs_qty"]}</td>
            <td nowrap class="DataTD">{$p["part_desc"]}</td>
           </tr>

HTML;

 } // foreach p1
   $htm.=<<<HTML
        <tr>
         <td colspan="6">
<br>
           <button class="binbutton-tiny" id="b2" name="B2" value="Cancel" onclick="do_cancel();">Cancel</button>
         </td>
        </tr>
       </table>
    <div>
<script>
 function letScan(idx)
 {
  document.form1.func.value="scanUPC";
  document.form1.idx.value=idx;
  document.form1.submit();
 }
function do_cancel()
{
  document.form1.func.value="";
  document.form1.submit();
}
</script>
HTML;
 return $htm;

//echo "<pre>bin={$bin} {$pcount} Parts";
//print_r($p1);
//print_r($parts);
//exit;
 } // end parts count > 0
} // end frmtParts

function frmtAisles($aisles)
{
 $htm=<<<HTML
    <div class="col-25">
       <table>
           <tr>
            <td nowrap class="FormHeaderFont" colspan="4">Select Aisle to Assign UPC codes Below</td>
<br>
           </tr>
           <tr>
            <td nowrap colspan="4">&nbsp;</td>
           </tr>
           <tr>
            <td nowrap width="1%" class="FieldCaptionTD">&nbsp;</td>
            <td nowrap width="5%" class="FieldCaptionTD">Aisle</td>
            <td width="5%" align="center" class="FieldCaptionTD">Part Count</td>
            <td width="5%" align="center" class="FieldCaptionTD">Part Qty</td>
           </tr>

HTML;
  if (count($aisles) < 1)
  { // there are no aisles to display
   $htm.=<<<HTML
           <tr>
            <td nowrap class="DataTD" colspan="4">The are no Parts without UPC's</td>
           </tr>

HTML;
  } // there are no aisles to display
  else
  { // display aisles
   foreach ($aisles as $key=>$a)
   {
    $htm.=<<<HTML
           <tr>
            <td nowrap class="DataTD"><div class="binbutton-tiny" id="Go" name="aisle{$a["Aisle"]}" onclick="letsgo('{$a["Aisle"]}');">Go</div></td>
            <td nowrap class="DataTD">{$a["Aisle"]}</td>
            <td nowrap align="center" class="DataTD">{$a["numParts"]}</td>
            <td nowrap align="center" class="DataTD">{$a["totalQty"]}</td>
           </tr>

HTML;
   } // end foreach aisles
  } // display aisles
  $htm.=<<<HTML
       </table>
    <div>
<script>
 function letsgo(aisle)
 {
  document.form1.func.value="goToAisle";
  document.form1.Aisle.value=aisle;
  document.form1.submit();
 }
</script>
HTML;
 return $htm;

} // end frmtAisles

function frmtUPC($part)
{ 
 global $Aisle;
 global $lastBin;
 global $thisprogram;
 
 $htm=<<<HTML
  <div class="w3-row-padding w3-margin-bottom">
   <div class="w3-half">
    <div class="w3-container w3-green">
     <span class="w3-w3-green"><br></span>
     <div class="w3-clear"></div>
      <label class="wmslabel" for="UPC" style="vertical-align: top;" >Scan UPC</label>
      <input type="text" class="w3-white" onchange="do_upc();" value="" name="UPC" placeholder="Scan Bar Code on Package" id="upccode" title="Scan the Bar Code on the package">
     </div>
    </div>
   </div>
 </form>

<script>
 document.form1.UPC.focus();

function do_upc()
{
 document.form1.func.value="UPCscan";
 document.form1.submit();
}
</script>

   <div class="w3-half wms-red" id="adjMsg" style="display:none">&nbsp;&nbsp;&nbsp;</div>
 <div class="w3-half">
        <div class="panel-body">
         <div class="table-responsive">
          <table class="table table-bordered table-striped">
           <tr>
            <td colspan="2" class="FormHeaderFont">Bin: {$part["whs_location"]}
           </tr>
           <tr>
            <td colspan="2" class="FormHeaderFont">Part #: {$part["p_l"]} {$part["part_number"]}</td>
           </tr>
           <tr>
            <td>
             <table width="60%">
              <tr>
               <tr>
                <td class="FieldCaptionTD" align="left">Description</td>
                <td class="FieldCaptionTD" align="right">Units This Bin</td>
               </tr>
               <tr>
                <td>{$part["part_desc"]}</td>
                <td align="right">{$part["whs_qty"]}</td>
               </tr>
              </tr>
             </table>
            </td>
           </tr>
          </table>
         </div>
         <button class="binbutton-small" id="b1" name="B1" value="submit" onclick="noUpc();">No UPC</button>
<button class="binbutton-small" id="B2" name="B2" title="Not Found" value="" onclick="do_NF();">Not Found</button>
        </div>
      <br>
</div>

  
 </div> 
<script>
  function do_NF()
 {
  if (confirm("Are you sure you want to Skip this Part?") == true)
  {
   document.form1.func.value="goToAisle";
   document.form1.idx.value++;
   document.form1.submit();
  }
 else return false;
 } // end do_NF

 function noUpc()
 {
  document.form1.func.value="noUPC";
  document.form1.submit();
 }
</script>

HTML;
 return $htm;
  
} // end frmtUPC

function frmtQty($part,$msg="")
{
 global $thisprogram;
 global $UPC;
 // if we got here, we found the part#
 $color="green";
 $p=$part;
 $pn="{$p["p_l"]} {$p["part_number"]}";
 $title="Adding UPC {$UPC}";
 $foot="Enter Qty of {$pn} for UPC {$UPC}";
 $sb='          <button class="binbutton-small" id="b1" name="B1" value="submit" onclick="do_submit();">Submit</button>';
 $qtyHtm=<<<HTML
         <tr>
          <td class="FieldCaptionTD" align="left" width="10%">Enter Quantity</td>
          <td class="w3-white" colspan="4" align="left" width="10%">
           <input name="upcQty" type="number" min="1" max="999" class="w3-white" value="1" id="upc_qty" title="Enter Qty of {$pn} for this UPC">
          </td>
         </tr>

HTML;
 $j=strlen($UPC);
 if ($msg == "" and $j < 11) 
 {
  $msg=<<<HTML

         <tr>
          <td colspan="5" class="w3-white FormSubHeaderFont"><mark class="w3-red">
Warning! Most UPC's have 8, 12 or more digits, Are you Sure?</mark>
</td>
         </tr>
HTML;
 }
 if ($msg <> "") 
 {
  $title="Invalid UPC {$UPC}";
  $msg=<<<HTML

         <tr>
          <td colspan="5" class="w3-white FormSubHeaderFont">{$msg}</td>
         </tr>
HTML;
  $qtyHtm="";
  $sb="";
  $foot="Press Cancel to continue...";
 }

$htm=<<<HTML
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
          <td colspan="5" class="w3-white FormSubHeaderFont">{$title}</td>
         </tr>
{$msg}
         <tr>
          <td colspan="1" class="FieldCaptionTD">Part Number</td>
          <td class="FieldCaptionTD">Description</td>
          <td colspan="2" class="w3-white">&nbsp;</td>
         </tr>
         <tr>
          <td colspan="1" class="w3-white">{$pn}</td>
          <td class="w3-white">{$p["part_desc"]}</td>
          <td colspan="2" class="w3-white">&nbsp;</td>
         </tr>
         <tr>
          <td colspan="5" class="w3-white">&nbsp;</td>
         </tr>
{$qtyHtm}
         <tr>
          <td colspan="5" class="w3-white">&nbsp;</td>
         </tr>
         <tr>
          <td colspan="5">
{$sb}
           <button class="binbutton-small" id="b2" name="B2" value="Cancel" onclick="do_cancel();">Cancel</button>

          </td>
         </tr>

        </table>
       </div>
      </div>
    <br>

     <div class="col-75" style="word-wrap: normal;font-weight: bold; font-size: large; text-align: cput;">{$foot}
    </div>

     </div>
     </div>
    </div>
  </div>

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
function scanUPC()
{
 $htm=<<<HTML
<!-- !PAGE CONTENT! -->
<div class="container w3-blue">
 <form name="form1" action="pickOrder1.php" method="get">
  <input type="hidden" name="func" id="func" value="pickScanPart">
  <input type="hidden" name="nh" value="0">
  <input type="hidden" name="order[]" value="10132"> 
  <input type="hidden" name="order[]" value="10136"> 

  <input type="hidden" name="hostordernum" id="hostordernum" value="240247">
  <input type="hidden" name="toteId" id="toteId" value="">
  <input type="hidden" name="bintoScan" id="bintoScan" value="A-03-17-B">
  <input type="hidden" name="orderNumber" id="orderNumber" value="10132">
  <input type="hidden" name="lineNumber" id="lineNumber" value="2">
  <input type="hidden" name="skipTo" id="skipTo" value="0">
  <input type="hidden" name="pullnum" id="pullnum" value="1">
  <input type="hidden" name="shadow" id="shadow" value="87657">
  <input type="hidden" name="p_l" id="p_l" value="WIX">
  <input type="hidden" name="part_number" id="part_number" value="24100">
  <input type="hidden" name="part_desc" id="part_desc" value="FUEL FLTR">
  <input type="hidden" name="uom" id="uom" value="EA">
  <input type="hidden" name="qtytopick" id="qtytopick" value="1">
  <input type="hidden" name="qtypicked" id="qtypicked" value="22">
   <div class="row">
    <div class="col-75">
     <span style="font-weight: bold; font-size: large; text-align: center;">at Bin A-03-17-B, Scan WIX 24100&nbsp;&nbsp;&nbsp; (qty -21 EA)</span>
    </div>
   </div>
   <div class="row">
    <div class="toteDiv">Tote </div>
    <div class="col-10">
     <label for="partnumber">Scan Part</label>
    </div>
    <div class="col-75">
       <input name="partnumber" type="text" onchange="do_submit();" value="" placeholder="Scan Part WIX 24100" id=" id="part_number"" title=" title="at Bin: A-03-17-B, Scan WIX 24100"">
    </div>
   </div>
   <div class="row">
    <div class="col-10">
     <label for="qty">Qty</label>
    </div>

    <div class="col-10">
    <input type="button" value="-" class="button-minus w3-red" onclick="plusMinus('Qty',0);" data-field="qty">
    <input type="number" step="1" max="" style="width: 62px" value="1" id="Qty" name="qty" class="quantity-field">
    <input type="button" value="+" class="button-plus w3-green" onclick="plusMinus('Qty',1);" data-field="qty">
<span>&nbsp;&nbsp;EA</span>
   </div>

   </div>
   <div class="row">
    <div class="col-10">
     &nbsp;
    </div>
    <div class="col-75">
     <span class="infoSpan"></span>
    </div>
   </div>
</div>
<div class="container">
 <div class="row">
  <div class="col-25">
   <span class="Bin" style="font-size: large;">at Bin: A-03-17-B</span>
  </div>
  <div class="col-25">
   <div class="Middle">
    <span style="font-size: large;">Scan WIX 24100 (qty -21 EA)</span>
   </div>
  </div>
  <div class="col-25">
   <span class="imAt" style="font-size: large;"></span>
  </div>
 </div>

<button class="binbutton wms-blue" id="B1" name="B1" title="Not Found" value="" onclick="do_NF();">Not Found</button>
<button title="No or unreadable UPC on Part" class="binbutton wms-blue" id="NoUPC" name="NoUPC" value="" onclick="setUPC();">No UPC</button>

 </form>
</div>
 <script>

  function setUPC()
  {
   var shd = document.getElementById('shadow').value;
  document.getElementById('NoUPC').value=1;
  document.form1.partnumber.value="." + shd;

  }

  function do_NF()
 {
  if (confirm("Are you sure you want to short pick this item?") == true)
  {
   document.form1.B1.value="NFP";
   document.form1.submit();
  }
 else return false;
 } // end do_NF

  document.form1.partnumber.focus();
 </script>

<script>
function do_submit()
{
 document.form1.submit();
}
</script>
  
HTML;
 return $htm;
}

function checkUPC($db,$UPC,$shadow)
{
 $UPC=trim($UPC);
 if ($UPC == "NOUPC") return -1;
 $SQL=<<<SQL
select p_l,part_number,shadow_number,alt_type_code,unit_of_measure as uom
from ALTERNAT,PARTS
where alt_part_number = "{$UPC}"
and shadow_number = alt_shadow_num

SQL;
 $data=$db->gData($SQL);
 $ret=0;
 $rc=$db->NumRows;
 if ($rc == 1)
 {
  $s=$data[1]["shadow_number"];
  $at=$data[1]["alt_type_code"];
  $pl=$data[1]["p_l"];
  $pn=$data[1]["part_number"];
  $plpn=trim($data[1]["p_l"]) . trim($data[1]["part_number"]);
// 0=open,
// 1=No UPC on box,
// 2=duplicate upc with other part,
// 3=UPC is the part # of another part
// 4=UPC is same as the part#
// 5=UPC is linked to multiple parts

  if ($s <> $shadow)
  {
   if ($at < 0) $ret=-2; else $ret=-3;
  } // <> shadow

  if ($s == $shadow) if ($at < 0) $ret=-6; else $ret=-4;
 } // end rc > 0

 if ($rc > 1) $ret=-5; // linked to multiple parts

 return $ret;
}

function logUPC($db,$shadow,$stat,$upc,$qty)
{
 if ($qty == "" or !is_numeric($qty)) $qty=1;
 global $UserId;
 $SQL=<<<SQL
update NEEDUPC
set upc_status={$stat},
 upc_scanned="{$upc}",
 upc_qty = {$qty}
where shadow = {$shadow}

SQL;
 $rc=$db->Update($SQL);
 return ($rc);
} // end logUPC

function addUpc($db,$UPC,$part,$upcQty)
{ // have all info, add ALTERNAT
 global $UserId;
 global $msg;
 $altype=-1;
 if (isset($upcQty) and $upcQty > 0) $altype=-$upcQty;
 $shadow=$part["shadow"];
 $uom=$part["uom"];
 $auom="EA";
 if ($upcQty > 1) $auom="CS";
 if (isset($uom) and $uom <> "EA") $auom=$uom;
 $SQL0=<<<SQL
insert into UPCLOG
( source, upc, userId, shadow, qty, upc_status)
values ("MAU","{$UPC}",{$UserId},{$shadow},{$upcQty},0)
SQL;

//echo "<pre>";
//echo "shadow={$shadow} UOM={$uom} type={$altype} UPC={$UPC}\n";
//echo "{$SQL0}\n";
//exit;
// insert Alternat and return with msg of rc added
  $rt=array();
  $rt["log"]=$db->Update($SQL0);
  $rt["alt"]= chkAddAlt($db,$shadow,$uom,$altype,$UPC);
  $msg="UPC {$UPC} Added";
  if ($rt["alt"] < 1)
  {
   $SQL=<<<SQL
select count(*) as cnt from ALTERNAT where alt_part_number = "{$UPC}"

SQL;

  $rc=$db->query($SQL);
  $numrows=$db->num_rows();
  $i=1;
  while ($i <= $numrows)
  {
   $db->next_record();
     if ($numrows)
     {
        $cnt=$db->f("cnt");
     }
     $i++;
   } // while i < numrows
   if ($cnt < 1) $msg="Add of UPC {$UPC} Failed";
  }
} // have all info, add ALTERNAT

function chkAddAlt($db,$shadow,$uom,$altype,$upc)
{
 $ret=0;
 $cnt=0;
 $t=abs($altype);
 $SQL=<<<SQL
select count(*) as cnt from ALTYPES
where al_key = {$altype}

SQL;

  $rc=$db->query($SQL);
  $numrows=$db->num_rows();
  $i=1;
  while ($i <= $numrows)
  {
   $db->next_record();
     if ($numrows)
     {
        $cnt=$db->f("cnt");
     }
     $i++;
   } // while i < numrows
 if ($cnt < 1)
 { // add type
  $SQL=<<<SQL
insert into ALTYPES (al_key,al_desc)
values ({$altype},"Case of {$t}")

SQL;
 $rc1=$db->Update($SQL);
 } // add type
if ((isset($rc1) and $rc1 > 0) or $cnt > 0)
{ //add was success
 $SQL=<<<SQL
insert ignore into ALTERNAT
(alt_shadow_num,alt_part_number,alt_type_code,alt_uom,alt_sort)
values ({$shadow},"{$upc}",{$altype},"{$uom}",0)

SQL;
 $ret=$db->Update($SQL);
} //add was success
 return($ret);
} // end chkAddAlt

?>
