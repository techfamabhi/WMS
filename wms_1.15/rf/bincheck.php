<?php

// bincheck.php -- Bin Check a Bin and retrieve all Parts and inventory 
// 03/03/22 dse initial
/*TODO
*/

session_start();
if (get_cfg_var('wmsdir')) $wmsDir=get_cfg_var('wmsdir');
else { echo "<h1>WMS System is not Configured on this System</h1>"; exit; }
$top=str_replace("/var/www","",$wmsDir);

foreach (array_keys($_REQUEST) as $w) { $$w=$_REQUEST[$w]; }
//error_reporting(0);

if (get_cfg_var('wmsdir')) $wmsDir=get_cfg_var('wmsdir');
else { echo "<h1>WMS System is not Configured on this System</h1>"; exit; }
$top=str_replace("/var/www","",$wmsDir) . "/";

$thisprogram=basename($_SERVER["PHP_SELF"]);
require("{$wmsDir}/config.php");
require_once("{$wmsInclude}/chklogin.php");
require_once("{$wmsInclude}/cl_rf2.php");
require_once("{$wmsInclude}/cl_template.php");
require_once("{$wmsInclude}/cl_PARTS2.php");
require_once("{$wmsInclude}/cl_bins.php");
require_once("{$wmsInclude}/db_main.php");
//require_once("{$wmsDir}/test/footer.php");

// Application Specific Variables -------------------------------------
$comp=1;
$temPlate="scanpart";
$title="Show Parts by Bin";
$panelTitle="Part";
$playsound=1;
//$SRVPHP="{$wmsServer}/COMPANY_srv.php";
//$DRPSRV="{$wmsServer}/dropdowns.php";
// end application specific variables ---------------------------------


$msg="";
$msgcolor="";
$curcolor="w3-blue";
$js="";
if (isset($scaninput) and $scaninput <> "")
{
 $db=new WMS_DB;
 $bin=new BIN;
 $pnum=trim(strtoupper($scaninput));
 $useWild="";
 if (strpos($useWild,"%") !== false) $useWild=1;
 $binParts=$bin->getLoc($comp,$pnum,$useWild);
 $binInfo=$bin->getBinInfo($comp,$pnum);
 $numParts=$binParts["numRows"];
 $numBins=$binInfo["numRows"];
//echo "<pre>";
//print_r($binParts);
//print_r($binInfo);
if ($numBins > 0)
{
// Display bin info, then parts in that bin
    //$title=<<<HTML
//<strong>{$binInfo["wb_location"]}</strong>
//&nbsp;
//HTML;
    $mainSection=frmtBinInfo($binInfo,$binParts,$thisprogram);
} // end numBins > 0
 else if ($numBins < 1)
  { // bin NOT, inform user invalid bin
    $msg="Invalid Bin";
    $curcolor="myRed";
    $scaninput="";
  } // bin NOT, inform user invalid bin
} // scan input <> ""
if (!isset($scaninput)) $scaninput="";
  $extra_js="";

if ($scaninput == "")
{ // scaninput == ""
 $ext_js="";
  $hiddens=<<<HTML
  <input type="hidden" name="func" id="func" value="scanPart">
  <input type="hidden" name="lastfunc" value="">
HTML;
  $fieldPrompt="Bin Number";
  $fieldPlaceHolder="Scan or enter Bin";
  $fieldId=" id=\"partnum\"";
  $fieldTitle=" title=\"Scan or Enter Bin Number\"";

  $data=array("formName"=>"form1",
            "formAction"=>$thisprogram,
            "hiddens"=>$hiddens,
            "color"=>$curcolor,
            "onChange"=>"do_bin();",
            "fieldType"=>"text",
            "fieldValue"=>"",
            "fieldPrompt"=>$fieldPrompt,
            "fieldPlaceHolder"=>$fieldPlaceHolder,
            "fieldName"=>"scaninput",
            "fieldId"=>$fieldId,
            "fieldTitle"=>$fieldTitle
  );
  $mainSection=frmtPartScan($data,$thisprogram);
  if ($curcolor == "myRed") if (isset($playsound) and $playsound > 0) $mainSection.=<<<HTML
<audio controls autoplay>
  <source src="{$wmsAssets}/sounds/psycho.wav" type="audio/wav">
</audio>
HTML;

  $mainSection.=$extra_js;
} // scaninput == ""

//Read the vue app script from the js directory ************
//$conf=array( "extension"=>'js', "theme"=>'js');
//$data=array("SRVPHP"=>"{$SRVPHP}","DRPSRV"=>"{$DRPSRV}");
//$vueAppScript=$parser->parse($temPlate,$data,$conf);
$otherScripts="";

//******************************************************

//Display Header
$pg=new displayRF;
$pg->viewport="1.50";
$pg->dispLogo=false;
$pg->title=$title;
$js.=<<<HTML
<script src="/jq/shortcut.js" type="text/javascript"></script>
<script>
 function do_binl()
 {
  document.location.href="stockchk.php";
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
$pg->jsh=$js;
if ($msg <> "") $pg->msg=$msg;
if ($msgcolor <> "")
{
 $pg->color=$msgcolor;
}

//Build function keys
$funcs=array(
  0=>array("fkey"=>"",
          "prompt"=>"Clear",
          "name"=>"srClr",
          "onClick"=>"clearSearch();",
          "value"=>"srClr",
          "title"=>"Bin Check another Bin"
  ),
  1=>array("fkey"=>"F1",
          "prompt"=>"Lookup by Part",
          "name"=>"srClr",
          "onClick"=>"do_binl();",
          "value"=>"srClr",
          "title"=>"Lookup Parts by Part Number"
  )
);
$pg->bldFooter($funcs);
//{$fk}
$pg->body=<<<HTML
  {$mainSection}
  {$otherScripts}

HTML;
$pg->Display();

//Rest of page
$htm=<<<HTML
 </body>
</html>

HTML;
echo $htm;

function frmtPartScan($data,$thisprogram)
{
 $ret="";
 $temPlate="scanpart";
 $parser = new parser;
 $parser->theme("en");
 $parser->config->show=false;
 $ret=$parser->parse($temPlate,$data);
 $ret.=<<<HTML
<script>
function do_bin()
{
 document.{$data["formName"]}.submit();
}
</script>
HTML;

 return $ret;

} // end frmtPartScan

function frmtPartInfo($part,$thisprogram)
{
 $color="green";
 $i=$part["comp"];
 $hiddens=<<<HTML
      <input type="hidden" name="scaninput" value="">
HTML;
 $qa=$part["WhseQty"][$i]["qty_avail"];
 $qal=$part["WhseQty"][$i]["qty_alloc"];
 $htm=<<<HTML
 <div class="w3-clear"></div>
 <div class="w3-half">
 <form name="form1" action="{$thisprogram}" method="get">
{$hiddens}
        <div class="panel-body">
         <div class="table-responsive">
          <table class="table table-bordered table-striped">
           <tr>
            <td colspan="2"><strong>{$part["Part"]["part_desc"]}</strong></td>
            <td>
             <table width="100%">
              <tr>
               <td style="text-align:right;padding-right:10px">Avail</td>
               <td style="text-align:right;padding-right:10px"><strong>{$qa}</strong></td>
               <td style="text-align:right;padding-right:10px">OnPick</td>
               <td style="text-align:right;padding-right:10px"><strong>{$qal}</strong></td>
              </tr>
             </table>
            </td>
           </tr>
_DETAIL_
          </table>
         </div>
        </div>
      <br>
 </form>
</div>
<script>
function clearSearch()
{
 document.form1.scaninput.value="";
 document.form1.submit();
}
</script>
HTML;
 $detail=<<<HTML
           <tr>
            <td colspan="3" class="btn-warning"><strong>There are no Bins assigned to this Part</strong></td>
           </tr>
HTML;
 if (count($part["WhseLoc"]) > 0)
 {
  $detail=<<<HTML
           <tr>
            <td class="FieldCaptionTD">Bin</td>
            <td style="text-align:right;padding-right:10px" class="FieldCaptionTD">Qty</td>
            <td class="FieldCaptionTD">UOM</td>
           </tr>

HTML;
  foreach($part["WhseLoc"] as $rec=>$l)
  {
   $tdt="";
   $btype=$l["whs_code"];
   if ($btype == "P")
   {
    $btype="*&nbsp;";
    $tdt=" title=\"This is the Primary Bin\"";
   }
   else $btype="&nbsp;&nbsp;";
   $theBin=$l["whs_location"];
   if (substr($theBin,0,1) == "!") $theBin="Tote: " . substr($theBin,1);
   $detail.=<<<HTML
           <tr>
            <td{$tdt}>{$btype}{$theBin}</td>
            <td style="text-align:right;padding-right:10px">{$l["whs_qty"]}</td>
            <td>{$l["whs_uom"]}</td>
           </tr>
HTML;
  } // end foreach whseLoc
 } // end count whseLoc > 0
 $htm=str_replace("_DETAIL_",$detail,$htm);
 return $htm;
} // end frmtPartInfo

function frmtChoosePart($part,$thisprogram)
{
 $hiddens="";
 $cnt=$part["numRows"];
 $upc=$part["upc"];
 $detail="";
 $htm=<<<HTML
 <div class="w3-clear"></div>
  <div class="w3-half">
   <div class="panel panel-default">
    <div class="panel-heading">
     <div class="row">
      <div class="col-md-6">
       <h3 class="panel-title">Found {$cnt} Bins matching "{$upc}"</h3>
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
      <table class="table table-bordered table-striped">
       <tr>
        <td width="1%" class="FieldCaptionTD">&nbsp;</td>
        <td width="3%" class="FieldCaptionTD">P/L</td>
        <td width="10%" class="FieldCaptionTD">Part Number</td>
        <td width="25%" class="FieldCaptionTD">Part Desc</td>
        <td style="text-align:right;padding-right:10px" width="10%" class="FieldCaptionTD">Avail</td>
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
<script>
function do_sel(shadow)
{
 document.form1.shadow.value=shadow;
 document.form1.submit();
}
function clearSearch()
{
 document.form1.scaninput.value="";
 document.form1.submit();
}
</script>
HTML;
  if (count($part["choose"]) > 0)
 {
  foreach($part["choose"] as $rec=>$l)
  {
   $cls="";
   $t="Click here to select Bin Number: {$l["p_l"]} {$l["part_number"]}";
   if ($l["qty_avail"] > 0) $cls=" class=\"bg-success\"";
   $detail.=<<<HTML
       <tr onclick="do_sel({$l["shadow_number"]});">
        <td title="{$t}"><input type="checkbox" name="UPCs[]" value="{$l["shadow_number"]}"></td>
        <td>{$l["p_l"]}</td>
        <td>{$l["part_number"]}</td>
        <td>{$l["part_desc"]}</td>
        <td style="text-align:right;padding-right:10px"{$cls}>{$l["qty_avail"]}</td>
       </tr>

HTML;
  } // end foreach part
 } // end choose count > 0
 $htm=str_replace("_DETAIL_",$detail,$htm);
 return $htm;
} // end frmtChoosePart
function frmtBinInfo($bin,$parts,$thisprogram)
{
 $color="green";
 $hiddens=<<<HTML
      <input type="hidden" name="scaninput" value="">
HTML;
 $sqft=(((($bin["wb_depth"] * $bin["wb_width"] * $bin["wb_height"]) / 12) / 12) / 12);
 $zone=$bin["wb_zone"];
 $aisle=$bin["wb_aisle"];
 $section=$bin["wb_section"];
 $level=$bin["wb_level"];
 $subin=$bin["wb_subin"];
 if (trim($zone) == "")    $f1="&nbsp;"; else $f1="Zone";
 if (trim($aisle) == 0)   $f2="&nbsp;"; else $f2="Aisle";
 if (trim($section) == 0) $f3="&nbsp;"; else $f3="Section";
 if (trim($level) == "")   $f4="&nbsp;"; else $f4="Level";
 $cls="";
 if (trim($subin) == 0)
 {
  $f5=""; 
  $subin="&nbsp;";
 }
else 
 {
  $f5="Sub Bin";
  $cls="class=\"FieldCaptionTD\"";
 }
 if ($aisle == 0) $aisle="&nbsp;";
 if ($section == 0) $section="&nbsp;";
 $htm=<<<HTML
 <div class="w3-clear"></div>
 <div class="w3-half">
 <form name="form1" action="{$thisprogram}" method="get">
{$hiddens}
        <div class="panel-body">
         <div class="table-responsive">
          <table class="table table-bordered table-striped">
           <tr>
            <td colspan="6" class="FormSubHeaderFont">Bin: {$bin["wb_location"]}</td>
           </tr>
           <tr>
            <td>
             <table width="100%">
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
 </form>
</div>
<script>
function clearSearch()
{
 document.form1.scaninput.value="";
 document.form1.submit();
}
</script>
HTML;
 $detail=<<<HTML
           <tr>
            <td colspan="6" class="btn-warning"><strong>There are no Parts assigned to this Bin</strong></td>
           </tr>
HTML;
 if (count($parts) > 0)
 {
  $detail=<<<HTML
           <tr>
            <td width="3%" class="FieldCaptionTD">P/L</td>
            <td width="12%" class="FieldCaptionTD">Part Number</td>
            <td width="5%" style="text-align:right;padding-right:10px" class="FieldCaptionTD">Qty</td>
            <td width="3%" class="FieldCaptionTD">UOM</td>
            <td width="20%" class="FieldCaptionTD">Description</td>
            <td width="3%" class="FieldCaptionTD">Type</td>
           </tr>

HTML;
  $p=array();
  if (isset($parts[2])) $p=$parts; else $p[1]=$parts;
  foreach($p as $rec=>$l)
  {
   if ($l["whs_shadow"] > 0)
   {
   $detail.=<<<HTML
           <tr>
            <td>{$l["p_l"]}</td>
            <td>{$l["part_number"]}</td>
            <td style="text-align:right;padding-right:10px">{$l["whs_qty"]}</td>
            <td>{$l["whs_uom"]}</td>
            <td>{$l["part_desc"]}</td>
            <td>{$l["whs_code"]}</td>
           </tr>
HTML;
   } // end shadow > 0
  } // end foreach p
 } // end count parts > 0
 $htm=str_replace("_DETAIL_",$detail,$htm);
 return $htm;

} // end frmtBinInfo
?>
