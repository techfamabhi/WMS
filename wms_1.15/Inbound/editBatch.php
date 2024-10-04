<?php

foreach (array_keys($_REQUEST) as $w) { $$w=$_REQUEST[$w]; }
//error_reporting(0);


session_start();
//echo "<pre>";
//print_r($_REQUEST);
//echo "</pre>";
$thisprogram=$_SERVER["SCRIPT_NAME"];
$returnTo=$thisprogram;
if (isset($_REQUEST["sorter"])) $sorter=$_REQUEST["sorter"]; else $sorter="";
if (isset($_REQUEST["sortDir"])) $sortDir=$_REQUEST["sortDir"]; else $sortDir="";
require($_SESSION["wms"]["wmsConfig"]);
require_once("{$wmsInclude}/cl_rf1.php");
require_once("{$wmsInclude}/wr_log.php");
require_once("{$wmsInclude}/db_main.php");
require_once("{$wmsInclude}/chklogin.php");
require_once("{$wmsInclude}/cl_inv.php");
require_once("{$wmsInclude}/restSrv.php");
require_once("{$wmsInclude}/get_option.php");

$SRV="http://{$wmsIp}{$wmsServer}/RcptLine.php";
$partSRV="http://{$wmsIp}{$wmsServer}/whse_srv.php";

if (!isset($comp)) $comp=1;

$db = new WMS_DB;
$pg=new displayRF;
$pg->viewport="1.0";
$pg->dispLogo=false;
$opt[27]=get_option($db,$comp,27);

if (isset($json)) $data=json_decode($json,true); else $data=array();


if (count($data) > 0)
{
if (isset($newRecvd)) $data["newRecvd"]=$newRecvd; else $newRecvd=$data["thisRecvd"];
if (isset($newStocked)) $data["newStocked"]=$newStocked; else $newStocked=$data["Stocked"];

} // end count data > 0

if (isset($func) and $func == "adjRecpt")
{
 $rcvAdj=$newRecvd - $data["thisRecvd"];
 $stkAdj=$newStocked - $data["Stocked"]; 
 $data["rcvAdj"]=$rcvAdj;
 $data["stkAdj"]=$stkAdj;

echo "<pre>";
print_r($_REQUEST);
exit;
/*

update RCPT_SCAN set qty_stockd = 8 where batch_num = 983 and shadow = 573501;

update PARTHIST set paud_qty = 8 where paud_num = 15934;

update WHSELOC set whs_qty = whs_qty - 2 where whs_shadow = 573501
and whs_location = "E 12 F 4";


update WHSEQTY  set qty_avail = qty_avail -2 where ms_shadow = 573501;

*/ 
$usql=array();
$k=0;
 $stockd="";
if ($data["rcvAdj"] <> 0 or $data["stkAdj"] <> 0)
{ // adjusting recvd or stockd
 if ($data["stkAdj"] <> 0) $stockd=<<<SQL
    , qty_stockd = qty_stockd + {$data["stkAdj"]}
SQL;
 $usql[$k]=<<<SQL
update RCPT_SCAN 
set scanQty = scanQty + {$data["rcvAdj"]},
    totalQty = totalQty + {$data["rcvAdj"]} {$stockd}

 where batch_num = {$data["batch_num"]} and shadow = {$data["shadow"]};

SQL;
 $k++;
} // adjusting recvd or stockd
if ($data["recv_to"] == "a" and $opt[27] < 1)
{
 if (isset($data["Bins"][$data["Tote"]]) and isset($data["toteDtl"]))
 { // adjust tote or bin
  $tote=$data["Tote"];
  $tt=$data["Bins"][$tote];
  $code="";
  if (isset($data["Bins"][$tote]["t"])) $code="t";
  $toteId=$data["toteDtl"][1]["tote_id"];
  $toteline=$data["toteDtl"][1]["tote_item"];

  if ($code == "t")
  {
   $tQty=$data["Bins"][$tote]["t"];
   $usql[$k]=<<<SQL
update IGNORE TOTEDTL set tote_qty = tote_qty + {$data["rcvAdj"]}
where tote_id = {$toteId}
and tote_item={$toteline}

SQL;
  }
 } // adjust tote
} // end type a
if ($data["recv_to"] == "b" or ($data["recv_to"] == "a" and $opt[27] > 0) and $data["stkAdj"] <> 0)
{

// check tote or bin
// adjust Inv
$sparams1=array(
      "wms_trans_id"=>$po,
      "shadow"=>$shadow,
      "company"=>$comp,
      "psource"=>$vendor,
      "user_id"=>$theUser,
      "host_id"=>$hpo,
      "ext_ref"=>"Direct To Bin",
      "trans_type"=>"RCV",
      "in_qty"=>$qty,
      "uom"=>$partUOM,
      "floc"=>$BinTote,
      "tloc"=>"Received",
      "inv_code"=>"0",
      "mdse_price"=>$tmp["cost"],
      "core_price"=>$tmp["core"],
      "in_qty_core"=>0,
      "in_qty_def"=>0,
      "bin_type"=>$binType
      );

      $trans=new invUpdate;
      $rd["updQty"]=$trans->updQty($sparams1); // 1=success, 0=failed
      if ($recvTo == "a")
      {
       if (substr($BinTote,0,1) == "!") $tote=substr($BinTote,1); else $tote=$BinTote;
       $bincls=new TOTE;
       $rd["addItemToTote"]=$bincls->addItemToTote($tote,$shadow, $qty , $partUOM);
      }

// end adjust Inv
} // end type b or a with opt 27
//  echo "<pre>tt={$code} ";
//print_r($_REQUEST);
//print_r($data);
//print_r($usql);
//exit;
 // do the updates and close the modal 
} // end adjust recpt
else
{ // load stockvw and tote info
// Mock up, if part has been stocked but was recvTo tote, get bin location if ajusting stocked
// if recved to bin, the Tote field is the bin it was recevd to
// if it has not been stocked and recv to tote, adjust tote

$toteBin=$data["Tote"];
$shadow=$data["shadow"];
$hpo=$data["host_po_num"];
$typeSearch="RCV";
$SQL="";
$SQL=<<<SQL
select batch_num,DATE_FORMAT(batch_date,"%m/%d/%Y") as batch_date
from RCPT_BATCH
where batch_num in ({$data["batch_num"]})

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
      foreach ($db->Record as $key=>$d)
       {
        if (!is_numeric($key)) { $ret[$i]["$key"]=$d; }
       }
     }
    $i++;
  } // while i < numrows

//echo "<pre>";
//print_r($ret);

$typeSearch="PUT";
// to get the bin location if recv_type = "a" and part has been putaway
$SQL=<<<SQL
select paud_num, paud_date, paud_type,paud_floc, paud_tloc, paud_qty
 from PARTHIST
where paud_shadow = {$shadow}
and paud_ref= "{$hpo}"
and paud_type in ("RCV","PUT")
-- and (paud_floc = "{$toteBin}" or paud_tloc = "{$toteBin}")
and paud_date >= "{$ret[1]["batch_date"]}"


SQL;
//echo "<pre>{$SQL}\n";

$ret1=$db->gData($SQL);
if (count($ret1) > 0)
{
 $totes=array();
 $bins=array();
 $totei=0;
 $binsi=0;
 foreach ($ret1 as $key=>$d)
 {
  $func=$d["paud_type"];
  $floc=$d["paud_floc"];
  $tloc=$d["paud_tloc"];
  if (substr($floc,0,1) == "!") $floc=substr($floc,1);
  if (substr($tloc,0,1) == "!") $tloc=substr($tloc,1);
  $qty=$d["paud_qty"];
  $tyf=chkTorB($db,$floc,$comp);
  $tyt=chkTorB($db,$tloc,$comp);

  // if ($tyf == "T")
  // {  
   //if (isset($totes[$floc]["f"])) $totes[$floc]["f"]=$totes[$floc]["f"] + $qty;
   //else $totes[$floc]["f"]=$qty;
  // } // end tyf=T
  //if ($tyf == "B")
  //{
   if ($tyf == "T") $floc="!" . $floc;
   if ($tyt == "T") $tloc="!" . $tloc;
   if (isset($bins[$floc]["f"])) 
    {
     $bins[$floc]["f"]=$bins[$floc]["f"] + $qty;
   if (isset($totes[$floc]["f"])) $totes[$floc]["f"]=$totes[$floc]["f"] + $qty;
   else $totes[$floc]["f"]=$qty;
     //$totes[$floc]["f"]=$totes[$floc]["f"] - $qty;
    }
   else $bins[$floc]["f"]=$qty;
  // } // end tyf=B
  // if ($tyf == "T")
  // {
   //if (isset($totes[$tloc])) $totes[$tloc]["t"]=$totes[$tloc]["t"] + $qty;
   //else $totes[$tloc]["t"]=$qty;
  // } // end tyf=T
  //if ($tyf == "B")
  //{
   if (isset($bins[$tloc]["t"]))
    {
     $bins[$tloc]["t"]=$bins[$tloc]["t"] + $qty;
   if (isset($totes[$tloc])) $totes[$tloc]["t"]=$totes[$tloc]["t"] + $qty;
   else $totes[$tloc]["t"]=$qty;
     //$totes[$tloc]["t"]=$totes[$tloc]["t"] - $qty;
    }
   else $bins[$tloc]["t"]=$qty;
 // } // end tyf=B

 } // end foreach ret1
} // end count ret1 > 0
//if (isset($totes)) $data["Totes"]=$totes; else $data["Totes"]=array();
if (isset($bins) and count($bins) > 0) 
{
 $tot=array();
 $stockdBins=array();
//echo "<pre>";
 $k=0;
 foreach ($bins as $key=>$d)
 {
  if (!isset($tot[$key]))
  {
   $tot[$k]["loc"]=$key;
   $tot[$k]["qty"]=0;
   if (substr($key,0,1) == "!") $tot[$k]["type"]="T"; 
   else $tot[$k]["type"]="B";
  }
  foreach($d as $k1=>$d1)
  {
   if ($k1=="t") $tot[$k]["qty"]=$tot[$k]["qty"] + $d1;
   if ($k1=="f") $tot[$k]["qty"]=$tot[$k]["qty"] - $d1;
  }
  $k++;
 } // end foreach bins
 foreach ($tot as $key=>$d)
 {
  if ($d["qty"] < 1) unset($tot[$key]);
 } // end foreach tot
 $data["Bins"]=$tot; 
 unset($tot);
} // end isset $bins
else $data["Bins"]=array();
//echo "<pre>";
//print_r($totes);
//print_r($bins);
//exit;
//print_r($data);
//echo "</pre>";

$json=json_encode($data);

} // load stockvw and tote info

// get current part locations
 $pn=".{$data["shadow"]}";
 $req=array("action"=>"findIt",
            "comp"=>$comp,
            "Search"=>$pn
 );
 $ret=restSrv($partSRV,$req);
 $result=(json_decode($ret,true));
 if (isset($result["WhseLoc"]) and count($result["WhseLoc"]) > 0)
 {
  if (count($result["WhseLoc"]) > 1)
  { // reorder result so totes are on top
   $na=array();
   $nb=array();
   $cont=true;
   foreach($result["WhseLoc"] as $key=>$d)
   {
    $na[$d["whs_location"]]=$key;
    if ($d["whs_location"] == $data["Tote"])
    {
     $q=$d["whs_qty"];
     if ($qty >= $data["Stocked"])
     {
      $result["WhseLoc"]=array($key=>$d);
      $cont=false;
      break;
     }
    } // end loc = tote

    if (substr($d["whs_location"],0,1) == "!") $nb[$d["whs_location"]]=$key;
   } // foreach result
   if ($cont)
   {
    ksort($na);
    if (count($nb) > 0)
    {
     $nb1=array();
     $tq=0;
     $st=$data["Stocked"];
     $j=0;
     foreach ($nb as $d)
     {
      $nb1[$j]=$result["WhseLoc"][$d];
      $tq=$tq + $nb1[$j]["whs_qty"];
      $j++;
      if ($tq >=$st) break;
     }
    } // end count nb > 0
    if (isset($nb1)) $result["WhseLoc"]=$nb1;
   } // cont = true

//echo "<pre>";
//print_r($data);
//print_r($result["WhseLoc"]);
//natsort($result["WhseLoc"]);
//print_r($result["WhseLoc"]);
//exit;
  } // reorder result so totes are on top
  $data["Inv"]=$result["WhseLoc"]; 
 }
else $data["Inv"]=array();
//echo "<pre>";
//print_r($req);
//print_r($result);
//print_r($data);
//exit;


// main display
$sc="";
if ($data["Stocked"] > 0)
{
 if (count($data["Inv"]) > 0)
 {
  $j=count($data["Inv"]);
  $bsel=<<<HTML

    <input type="hidden" id="qtyStocked" name="qtyStocked" value="{$data["Stocked"]}">
    <input type="hidden" id="Icnt" name="Icnt" value="{$j}">
     <tr>
      <td colspan="3" align="center" class="FieldCaptionTD">Totes and Bins</td>
     </tr>
     <tr>
      <td class="FieldCaptionTD">Bin</td>
      <td class="FieldCaptionTD">Qty</td>
      <td class="FieldCaptionTD">Type</td>
      <td align="center" class="FieldCaptionTD">New</td>
     </tr>
HTML;
  $chkd=0;
  foreach ($data["Inv"] as $key=>$d)
  {
    $chk="";
    if ($chkd == 0) $chk=" checked";
    $rgrp="";
    if ($key == 1) $rgrp='<fieldset id="binGrp">';
    $ergrp="";
    if ($key == $j) $ergrp='</fieldset>';
echo "k={$key} j={$j} ergrp={$ergrp} ";
    
    $chkd++;
    $bin=$d["whs_location"];
    $bin1=$bin;
    if (substr($bin,0,1) == "!")
    {
     $bin="Tote: " . substr($bin,1);
    }
    $ty=$d["whs_code"];
    $qty=$d["whs_qty"];
    $stkd=$data["Stocked"];
    $mn=$qty - $stkd;
    $mx=$qty;
    $bsel.=<<<HTML
  <tr>
   <td>
    <input type="hidden" name="bloc{$key}" value="{$bin1}">
    <input type="hidden" name="sQty{$key}" value="{$qty}">
    {$bin}
   </td>
   <td>{$qty}</td>
   <td>{$ty}</td>
   <td align="center">
{$rgrp}
<input type="radio" id="qR{$key}" name="qR{$key}" value="{$key}" onclick="chkqR({$key});" {$chk}>
<input type="number" min="{$mn}" max="{$mx}" id="nsQty{$key}" value="{$qty}" name="nsQty{$key}" onblur="valQty(this, {$qty});">
{$ergrp}
</td>
 </tr>

HTML;
  } // end foreach Inv
   $bsel.=<<<HTML
</table>

HTML;

 } // end count Inv > 0
 $sc="Current";
 $stk=$bsel;
// build html of source bin for the stocked adjustment
} // end if stocked

else 
{
 $stk=<<<HTML
<td>&nbsp;</td>
HTML;
}
 $htm=<<<HTML
 <form name="form1" action="{$thisprogram}" method="get">
  <input type="hidden" name="func" id="func" value="adjRecpt">
  <input type="hidden" name="nh" value="1">
  <input type="hidden" name="comp" value="{$comp}">
  <input type="hidden" name="shadow" value="{$data["shadow"]}">
  <input type="hidden" name="pl" value="{$data["p_l"]}">
  <input type="hidden" name="pn" value="{$data["part_number"]}">
  <input type="hidden" name="json" value='{$json}'>
  <div class="w3-row-padding w3-margin-bottom">
    <div class="w3-half">
     <div class="container w3-light-blue w3-padding-4">
     <div class="w3-white">
      <div class="w3-padding-8 FormHeaderFont">
</div>
      <div class="row">
       <div class="col-75">
        <table style="position:relative;left: 6px;" class="table table-bordered table-striped">
         <tr>
          <td colspan="4">
     <div class="col-75" style="word-wrap: normal;font-weight: bold; font-size: medium; text-align: cput;">Change Receipt Qty
    </div>
          </td>
         </tr>
         <tr>
          <td colspan="2" class="w3-white"><h5><strong>PO: {$data["host_po_num"]}</h5></td>
          <td colspan="1" class="w3-white"><h5><strong>Batch: {$data["batch_num"]}<span id="demo"></span></strong></h5></td>
         </tr>
         <tr>
          <td class="FieldCaptionTD" align="left" width="10%">Part Number<td>
          <td class="w3-white" colspan="2" align="left" width="10%">{$data["p_l"]} {$data["part_number"]}</td>
         </tr>
         <tr>
          <td class="FieldCaptionTD" align="left" width="10%">Description</td>
          <td class="w3-white" colspan="4" align="left" width="10%">{$data["part_desc"]}</td>
         </tr>
         <tr>
          <td class="FieldCaptionTD" align="left" width="10%">Qty Ordered</td>
          <td nowrap class="w3-white" colspan="1" align="right" width="5%">{$data["qty_ord"]}</td>
          <td nowrap class="w3-white" colspan="2" align="right" width="5%">&nbsp;</td>
         </tr>
         <tr>
          <td class="FieldCaptionTD" align="left" width="10%">Qty Recieved</td>
          <td nowrap class="w3-white" colspan="2" align="center" width="5%"><strong>Current</strong><br>{$data["thisRecvd"]}</td>
        <input type="hidden" name="oldRecvd" value="{$data["thisRecvd"]}">
          <td nowrap class="w3-white" colspan="2" align="center" width="5%"><strong><span id="hd1">New</span></strong><br>
<input type="number" min="0" max="999" id="Qty" value="{$data["thisRecvd"]}" name="newRecvd" onfocus="curField='newRecvd';" onblur="valQty(this, {$data["thisRecvd"]});">
</td>
         </tr>
         <tr>
          <td class="FieldCaptionTD" align="left" width="10%">Qty Stocked</td>
        <input type="hidden" name="oldStocked" value="{$data["Stocked"]}">
          <td nowrap class="w3-white" colspan="2" align="center" width="5%"><strong>{$sc}</strong><br>{$data["Stocked"]}</td>
          <td nowrap class="w3-white" colspan="2" align="center" width="5%"><strong><span id="hd1">New</span></strong><br>
<input style="background-color: #e8eaed;" type="number" min="0" max="999"  id="nStocked" value="{$data["Stocked"]}" name="nStocked" disabled>
{$stk}
         </tr>
         <tr>
          <td colspan="5" class="w3-white">&nbsp;</td>
         </tr>

         <tr>
          <td colspan="5">

           <button class="binbutton-small" id="b1" name="B1" value="submit" onclick="do_submit();">Submit</button>

           <button class="binbutton-small" id="b2" name="B2" value="done" onclick="javascript:parent.cancel_modal();">Cancel</button>

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
HTML;
// end main display

//echo "</pre>";
 $pg->Bootstrap=true;
 $pg->title="Review Receipt";
 $nh=1;
 if (isset($nh) and $nh) $pg->noHeader=true;
 $pg->jsh=<<<HTML
<script>

function do_submit()
{
 document.form1.submit();
}

function valQty(ele,origValue)
{
  var val=ele.value;
  var min=ele.min;
  var max=ele.max;
//alert( min + " < " + val + " || " + val + " > " + max );
  var ok=true;
  if (ele.name == "newStocked")
  {
    max=document.getElementById('Qty').value;
  }
//if (val > max) alert (val + " > " + max);
//if (val < min) alert (min + " < " + val);
  if ((val > max || val < min)) ok=false;
  // if (min < 0 && (val >= max || val >= min)) ok=false;
  if (ok !== true)
  {
   alert("Value must be between " + min + " and " + max);
   ele.value=origValue;
   return false;
  }
 if (ele.name == "newRecvd" && ele.value !== origValue)
 {
    if (ele.value !== origValue)
     { 
      document.getElementById('Qty').max=ele.value
      var nS=document.getElementById('nStocked').value;
     }
     if (nS !== undefined)
     {
      document.getElementById('nStocked').value=ele.value;
      var j=document.getElementById('Icnt').value;
      var diff=origValue - ele.value;
      for (let x=1; x <= j; x++)
      {
        var S=document.getElementById('nsQty' + x).value;
      var R=document.getElementById('qR + x');
        if (R.value == x && R.checked)
        {
         S=S - diff;
         document.getElementById('nsQty' + x).value = S;
        }
 alert("x=" + x + " qR" + x + "=" + chkVal + " S=" + S);
      }
    }
 }
}
function chkqR(val)
{
 var j=document.getElementById('Icnt').value;
 for (let x=1; x <= j; x++)
  {
   var R=document.getElementById('qR + x');
   R.checked=false;
   if (R.value == val) R.checked=true;
  }

}

</script>

HTML;
 $pg->body=$htm;
 $pg->Display();
 echo "</body>\n</html>";

// fix RCPT_SCAN where stocked does not have to be messed with 
$SQL=<<<SQL
update RCPT_SCAN
set scanQty =1,
 totalQty =1,
 timesScanned =1

 where batch_num =817
 and line_num  = 23
and shadow = 1354445

SQL;

// delete tote dtl if changed qty recvd
$SQL=<<<SQL
delete from TOTEDTL where tote_shadow = 1354445 and tote_id = 112

SQL;


// if stocked is changed

/*
 read old stockvw record
 read current inv
 if old=2 and new = 1, adjust = -1
 if old=1 and new = 2, adjust = +1
 $stkAdj = ( newStockd - oldStockd )
update RCPT_SCAN
write stockvw with adjust, reason Recv Correction
update WHSEQTY and WHSELOC

*/

function chkTorB($db,$srch,$comp=1)
{
 $SQL=<<<SQL
SELECT result.type
FROM (
select "T" as type
from TOTEHDR A
where A.tote_code = "{$srch}"
and A.tote_company = {$comp}
    UNION ALL
select "B" as type
from WHSEBINS B
where wb_location = "{$srch}"
and B.wb_company = {$comp}
) AS result
LIMIT 1

SQL;
 
 $ret="";
  $rc=$db->query($SQL);
  $numrows=$db->num_rows();
  $i=1;
  while ($i <= $numrows)
  {
   $db->next_record();
     if ($numrows)
     {
        $ret=$db->f("type");
     }
     $i++;
   } // while i < numrows
 return $ret;
} // end chkTorB
