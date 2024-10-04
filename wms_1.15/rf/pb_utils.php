<?php
function frmtBin($part,$vendor,$qty_scanned,$pkguom,$mst,$color,$msg,$recvInfo,$infoColor="")
{
 global $thisprogram;
 global $prodline;
 global $sounds;
 global $recvTo;
 global $msg2;
 $msgcolor=$color;
 $bsound="";
  if (trim($msg)=="" or substr($msg,0,5) == "Last:" or substr($msg,0,8) == "Ordered:")
  {
   $msg="     <br>";
  }
  else
  {
   $msg="<h4>{$msg}</h4>";
   $bsound=<<<HTML
<audio controls autoplay >
  <source src="{$sounds}/sounds/psycho.wav" type="audio/wav">
</audio>
HTML;

   $color="red";
   $msgcolor="red";
  }
 if (substr($msg2,0,7) == "Tote is")
 {
  $bsound=<<<HTML
<audio controls autoplay >
  <source src="{$sounds}/sounds/psycho.wav" type="audio/wav">
</audio>
HTML;
  }

 if (trim($recvInfo) <> "")
  {
   $msgcolor="amber";
   if ($infoColor <> "") $msgcolor=$infoColor;
   $msg="<h4 class=\"w3-amber\">{$recvInfo}</h4>";
  }
 //$qty_scanned="12";
 //$pkguom="CS";
 $p_l=$part["p_l"];
 $pn=$part["part_number"];
 $shd=$part["shadow_number"];
 $pdesc=$part["part_desc"];
 $partuom=$part["unit_of_measure"];
 $tqty=$qty_scanned;
 $uomDesc="{$tqty} {$partuom}";
 $b="<strong>";
 $be="</strong>";
 $uom1="";
 if ($pkguom == $partuom) $uom=$partuom;
 else
 {
  $uom1="{$b} {$pkguom} of {$qty_scanned} = _tqty_ {$partuom}{$be}";
  $uomDesc="{$pkguom} of {$qty_scanned} = {$tqty} {$partuom}";
  $uom="{$b}{$uomDesc}{$be}";
  $tqty=1;
 }
 if (isset($mst[1]["primary_bin"])) $bin=$mst[1]["primary_bin"];
 else $bin="";
 $binPrompt="Primary Bin:";
 if (trim($bin)=="")
 {
  if (isset($prodline))
  {
   //$bin=$prodline["pl_perfered_zone"] . "-" . sprintf("%02d",$prodline["pl_perfered_aisle"]);
   $bin=$prodline["pl_perfered_zone"] . "-" . $prodline["pl_perfered_aisle"];
   $binPrompt="Preferred Zone-Aisle:";
  }
  else $bin="No Primary Bin Set";
 } // end bin is empty
 $prefBin=$bin;
 $bigBin="";
 if ($prefBin <> "")
 {
  $w="";
  $prefZone="";
  $k=explode("-",$prefBin);
  if (isset($k[0])) $w=$k[0];
  if (isset($k[0])) $prefZone=$k[0];
  if (isset($k[1])) $w.=" {$k[1]}";
  if (isset($k[1])) $prefZone.="|{$k[1]}";
  $bigBin=<<<HTML
<div style="font-weight: 900;font-size: 65px;position: relative; right: 0px;">{$w}</div>
HTML;
 }
if ($recvTo == "a") $binPrmpt="Tote"; else $binPrmpt="Bin";

if ($bigBin=="") $bigBin="<br>";

//toDo  Correct qty to be qty 1, add span with total qty extended to eaches
 if (!isset($ts)) $ts=time();
 $htm=<<<HTML
 <form name="form1" action="{$thisprogram}" method="get">
  <input type="hidden" name="thisprogram" value="{$thisprogram}">
  <input type="hidden" name="vendor" value="{$vendor}">
  <input type="hidden" name="func" value="scanBin">
  <input type="hidden" name="ts" value="{$ts}">
  <input type="hidden" name="msg2" value="{$msg2}">
  <input type="hidden" name="shadow" value="{$shd}">
  <input type="hidden" name="lookPO" value="2">
  <input type="hidden" name="comp" value="{$part["comp"]}">
  <input type="hidden" name="UPC" value="{$part["upc"]}">
  <input type="hidden" name="PPL" value="{$p_l}">
  <input type="hidden" name="PPN" value="{$pn}">
  <input type="hidden" name="PPD" value="{$pdesc}">
  <input type="hidden" name="partUOM" value="{$partuom}">
  <input type="hidden" name="pkgUOM" value="{$pkguom}">
  <input type="hidden" name="pkgQty" value="{$qty_scanned}">
  <input type="hidden" name="uomDesc" value="{$uomDesc}">
  <input type="hidden" name="tqty" value="{$tqty}">
  <input type="hidden" name="prefZone" value="{$prefZone}">

  <div class="w3-row-padding w3-medium w3-margin-bottom">
    <div class="w3-half">
      <div class="w3-container w3-{$color}">
      <div class="w3-{$msgcolor}">
         {$msg}
      <div>
<span class="w3-clear">
{$b}{$p_l} {$pn}{$be}
&nbsp;{$pdesc}</span>
<br>
<span class="w3-clear">
        <label>Qty</label>
<input type="number" min="0" max="99999" class="w3-white" value="{$tqty}" name="qtyRecvd" onchange="recalc_qty(this.value,{$qty_scanned});">
<span id="extQty" name="extQty">{$uom}</span>
{$bigBin}
{$binPrompt} <strong>{$bin}</strong>
</span>
<br>
HTML;
if ($msg2 <> "") $htm.="<span class=\"w3-amber\"><b>{$msg2}</b></span>\n";
$htm.=<<<HTML
        <div class="w3-clear">
                <label>Scan {$binPrmpt}</label>
<input type="text" class="w3-white" onchange="do_bin();" value=" " name="scaninput" placeholder="Enter Bin or Tote...">
<br>
<br>
     
        </div>
      </div>
    </div>
  </div>
    <input class="binbutton" type="button" value="Cancel" onclick="do_resetp();"></td>
 </form>
{$bsound}
<script>
function recalc_qty(qtyrec,pkgqty)
{
 var tq=(qtyrec * pkgqty);
 document.form1.tqty.value=tq;
 var extq=document.form1.uomDesc.value;
 document.getElementById('extQty').innerHTML=extq.replace("_tqty_",tq);
}
</script>
HTML;
file_put_contents("/tmp/recvPOBin.htm",$htm);
return $htm ;
} // end frmtBin

function frmtPartScan($vendor,$msg,$color)
{
  global $thisprogram;
  global $recvTo;
  global $recvType;
  global $topColor;
  $msgcolor=$color;
  if (trim($msg)=="" or substr($msg,0,5) == "Last:") 
  {
   $msg.="<br>";
   $msgcolor="green";
   $TopColor="green";
  }
  else
  {
   $msg="<h4>{$msg}</h4><br>";
   $color="red";
   $TopColor="red";
  }
  $htm=<<<HTML
 <form name="form1" action="javascript: do_bin(event);" method="get">
  <input type="hidden" name="vendor" value="{$vendor}">
  <input type="hidden" name="thisprogram" value="{$thisprogram}">
  <input type="hidden" name="func" id="func" value="scanPart">
  <input type="hidden" name="recvTo" id="recvTo" value="{$recvTo}">
  <input type="hidden" name="recvType" id="recvType" value="{$recvType}">
  <input type="hidden" name="lookPO" value="1">
  <div class="w3-row-padding w3-margin-bottom">
    <div class="w3-half">
      <div class="w3-container w3-{$color} w3-padding-8">
        <span class="w3-{$msgcolor}">{$msg}</span>
        <div class="w3-clear"></div>
                <label>Scan Part</label>
<input type="text" class="w3-white" onchange="do_bin(event);" value=" " name="scaninput" placeholder="Scan or Enter Part...">
<br>
<br>

      </div>
    </div>
  </div>
    _BUTTONS_
 </form>

 </div>
<script>
document.form1.scaninput.focus();

function do_bin(evt)
{
  if (evt !== undefined)
  {
   document.form1.action="{$thisprogram}";
   alert("do bin, scan=[" + document.form1.scaninput.value + "]");
   if (document.form1.scaninput.value != " ") document.form1.submit();
  } // end w > 0
 else 
  {
   return false;
  }
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
function chk_seltr(ele)
{
  if (ele.checked === true) ele.checked = false; else ele.checked=true;
  chk_sel();
}
</script>

HTML;
return $htm ;

} // end frmtPartScan

function showToteContents($comp, $toteId, $buttons, $formAction="")
{
//Probably need to pass in buttons too

 // need to make sure  wms home/config.php is sourced before calling
 global $wmsInclude;
 global $wmsIp;
 global $wmsServer;
 
 if (trim($toteId) == "") return "Error: Tote Not Defined";
 if (!isset($wmsInclude)) return "Error: wmsInclude Not Defined";
 if (!isset($wmsIp)) return "Error: wmsIp Not Defined";
 if (!isset($wmsServer)) return "Error: wmsServer Not Defined";
 
 if ($formAction == "") $formAction=$_SERVER["PHP_SELF"];

 require_once("{$wmsInclude}/cl_template.php");
 require_once("{$wmsInclude}/restSrv.php");
 $RESTSRV="http://{$wmsIp}{$wmsServer}/RcptLine.php";

 $parser = new parser;
 $parser->theme("en");
 $parser->config->show=false;

// get tote contents
 $req=array(
  "action"=>"getToteDetail",
  "company"=> $comp,
  "tote_id"=> $toteId
);
 $ret=restSrv($RESTSRV,$req);
 $w=(json_decode($ret,true));

 $tdtl=array();
 if (count($w) > 0)
  foreach( $w as $key=>$d)
  {
   $tdtl[$key]["primBin"]=$d["primary_bin"];
   $tdtl[$key]["p_l"]=$d["p_l"];
   $tdtl[$key]["part_number"]=$d["part_number"];
   $tdtl[$key]["part_desc"]=$d["part_desc"];
   $tdtl[$key]["qty"]=$d["tote_qty"];
   $tdtl[$key]["UOM"]=$d["tote_uom"];
  } // end foreach w

/*      $buttons=array(
1=>array(
"btn_id"=>"b1",
"btn_name"=>"B1",
"btn_value"=>"Close",
"btn_onclick"=>"do_close();",
"btn_prompt"=>"Close"
)
);
*/

$data=array(
"heading"=>"Tote {$toteId} Contents",
"formName"=>"toteDetail",
"formAction"=>$formAction,
"color"=>"w3-light-blue",
"items"=>$tdtl,
"buttons"=>$buttons,
"msg"=>"",
"msg2"=>""
);

 $temPlate="toteContents";
 $ret=$parser->parse($temPlate,$data);
 return $ret;
} // end showToteContents
