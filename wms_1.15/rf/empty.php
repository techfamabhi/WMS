<?php
//  empty.php -- php template for creating new rf apps
// 

/*TODO
*/
session_start();

echo "<pre> REQUEST=";
print_r($_REQUEST);
echo "</pre>";
//PHPINFO(INFO_VARIABLES);
if (isset($_REQUEST["B2"]) and $_REQUEST["B2"] == "cancel")
{
} // end b2 is set

$dispBin="";
$lastPart="";
foreach (array_keys($_REQUEST) as $w) { $$w=$_REQUEST[$w]; }
//error_reporting(0);

if (get_cfg_var('wmsdir') !== false) $wmsDir=get_cfg_var('wmsdir');
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
require_once("../include/restSrv.php");
require_once("pb_utils.php");
require_once("collapse.php");


$RESTSRV="http://{$wmsIp}{$wmsServer}/RcptLine.php";
$PARTSRV="http://{$wmsIp}{$wmsServer}/whse_srv.php";
$UPDSRV="http://{$wmsIp}{$wmsServer}/PO_srv.php";
$comp=$wmsDefComp;
$db=new WMS_DB;

// Application Specific Variables -------------------------------------
$temPlate="generic1";
$title="Inventory";
if (isset($lastPart) and $lastPart <> "") $title="Last Part # {$lastPart}";
$panelTitle="Inventory";
//$SRVPHP="{$wmsServer}/COMPANY_srv.php";
//$DRPSRV="{$wmsServer}/dropdowns.php";
// end application specific variables ---------------------------------

if (!isset($func)) $func="startInv";
if (!isset($msg)) $msg="";
if ($func == "palletToMove" and $toteId == "" and $B1 == "submit") $func="scanScreen";
if ($func == "movingPallet" and $newLoc == "" and $B1 == "submit") $func="palletToMove";
if ($func == "whatToDo")
{
 if (isset($R1))
 { // user answered what to do
  if ($R1 == 3) $func="movePallet";
  else if ($R1 == 2) $func="directedPutaway";
  else $func="askPart";
 } // user answered what to do
} // end func == whatToDo

if ($func == "donePressed" and isset($B2) and $B2 == "cancel")
{
 $toteId="";
 $title="Inventory";
 $func="startInv";
} // end donePressed

if ($func == "donePressed" and isset($B2) and $B2 == "done")
{
 require("{$wmsInclude}/backToMenu.php");
}

switch ($func)
{
 case "startInv":
 {
  break;
 } // end startInv
 case "scanScreen":
 { // Display Scan Bin screen
  break;
 } // End Display Scan screen

} // end switch func

// Display the screen
$pg=new displayRF;
$pg->viewport="1.0";
$pg->dispLogo=false;
$pg->Bootstrap=true;
if (isset($title)) $pg->title=$title;
if (isset($color)) $pg->color=$color; else $color="light-blue";
$ejs="";
if (isset($nh) and $nh > 0)
{
 $pg->noHeader=true;
}

if (!isset($otherScripts)) $otherScripts="";
$pg->jsh=<<<HTML
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

HTML;
if (isset($js)) $pg->jsh.=$js;
$pg->Display();
//Rest of page
$htm=<<<HTML
  {$mainSection}
  {$otherScripts}
 </body>
</html>

HTML;
echo $htm;
echo "<pre>";
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

function entOrderTote($msg,$color="blue",$override=false)
{
 global $thisprogram;
 global $nh;
 if ($msg <> "" and !$override) $color="red";

    $hiddens=<<<HTML
  <input type="hidden" name="func" id="func" value="palletToMove">
  <input type="hidden" name="nh" value="{$nh}">
  <input type="hidden" name="scanTote" value="">
HTML;
   $fieldPrompt="Tote or Pallet";
   $fieldPlaceHolder="Scan Tote/Pallet Id to Move";
   $fieldId=" id=\"toteid\"";
   $msg2="Scan Tote/Pallet (Tote, Pallet, Cart, etc) to Move";
   $fieldTitle=" title=\"{$msg2}\"";
   $extra_js="";
   $buttons=setStdButtons("D");

   $data=array("formName"=>"form1",
              "formAction"=>$thisprogram,
              "hiddens"=>$hiddens,
              "color"=>"w3-{$color}",
              "onChange"=>"do_submit();",
              "fieldType"=>"text",
              "fieldValue"=>"",
              "fieldPrompt"=>$fieldPrompt,
              "fieldPlaceHolder"=>$fieldPlaceHolder,
              "fieldName"=>"toteId",
              "fieldId"=>$fieldId,
              "fieldTitle"=>$fieldTitle,
              "msg"=>$msg,
              "msg2"=>$msg2,
              "buttons"=>$buttons,
              "function"=>""
    );
  $ret=frmtScreen($data,$thisprogram,"generic2");
  return $ret;
} // end entOrderTote

function att($in,$add)
{ // att - add to target
 $comma="";
 if (strlen($in) > 0)
 {
  $comma=",";
  if (trim($in) == trim($add)) return $in;
  if (strpos($in,"{$add}{$comma}") !== false) return $in;
 }
 return "{$in}{$comma}{$add}";
} // end att

function setCustomButtons($flag="D")
{
 global $lastPart;
 // args D=Done, C=Cancel
 $w="done";
 $w1="Done";
 if ($flag == "C")
 {
  $w="cancel";
  $w1="Cancel";
 }

    $buttons=array(
    0 => Array(
            "btn_id" => "b1",
            "btn_name" => "B1",
            "btn_value" => "submit",
            "btn_onclick" => "document.form1.submit();",
            "btn_prompt" => "Submit"
        ),
    1 => Array(
            "btn_id" => "b3",
            "btn_name" => "B3",
            "btn_value" => "ViewQty",
            "btn_onclick" => "doQty({$lastPart}); return false;",
            "btn_prompt" => "Chg Qty"
        ),
    2 => Array(
            "btn_id" => "b2",
            "btn_name" => "B2",
            "btn_value" => $w,
            "btn_onclick" => "do_done();",
            "btn_prompt" => $w1 
        )
);
 if ($lastPart <> "") unset($buttons[1]);
 return $buttons;

} // end setCustomButtons
function setStdButtons($DorC="D", $tc=false)
{
 // args D=Done, C=Cancel
 $w="done";
 $w1="Done";
 if ($DorC == "C")
 {
  $w="cancel";
  $w1="Cancel";
 }
    $buttons=array(
0=>array(
"btn_id"=>"b1",
"btn_name"=>"B1",
"btn_value"=>"submit",
"btn_onclick"=>"do_submit();",
"btn_prompt"=>"Submit"
),
1=>array(
"btn_id"=>"b2",
"btn_name"=>"B2",
"btn_value"=>$w,
"btn_onclick"=>"do_done();",
"btn_prompt"=>$w1
)
);
if ($tc)
{
 global $toteId;
 $b=array(
 0=>$buttons[0],
1=>array(
"btn_id"=>"b1",
"btn_name"=>"B1",
"btn_value"=>"View",
"btn_onclick" => "doView({$toteId}); return false;",
"btn_prompt"=>"View Contents"
),
 2=>$buttons[1]
);
 unset($buttons);
 $buttons="";
 foreach ($b as $b1)
 {
  $buttons.=<<<HTML
<button class="binbutton-small" id="{b1["btn_id"]}" name="{$b1["btn_name"]}" value="{$b1["btn_value"]}" onclick="{$b1["btn_onclick"]}">{$b1["btn_prompt"]}</button>

HTML;

 } // end foreach b
} // end tc is true
 return $buttons;
} // end setStdButtons

function askWhatToDo($msg,$toteId,$color="light-blue")
{
 global $thisprogram;
 global $nh;
 //if ($msg <> "") $color="red";

    $hiddens=<<<HTML
  <input type="hidden" name="func" id="func" value="whatToDo">
  <input type="hidden" name="nh" value="{$nh}">
  <input type="hidden" name="toteId" value="{$toteId}">
HTML;
   $fieldPrompt="Choose Action";
   $fieldName="R1";
   $msg2="";
   $fieldTitle=" title=\"{$msg2}\"";
   $extra_js="";
   $buttons=setStdButtons("C");

   $data=array("formName"=>"form1",
   "heading"=>"Putaway/Move Tote # {$toteId}",
   "hiddens"=>$hiddens,
   "fieldPrompt"=>"Choose Action",
   "fieldName"=>"R1",
   "msg"=>$msg,
   "cols"=>4,
   "color"=>"w3-{$color}",
   "buttons"=>$buttons
   );

  $ret=frmtScreen($data,$thisprogram,"radio1");
 return $ret;
} // end askWhatToDo

function getToteInfo($toteId)
{
  global $comp;
  global $RESTSRV;
 $w=array();
 if (isset($toteId) and $toteId <> "")
  { // a tote or pallet was scanned , diplay tote info
   $req=array("action"=>"getTote",
              "company"=>$comp,
              "tote_id"=>$toteId
   );
 $ret=restSrv($RESTSRV,$req);
 $w=(json_decode($ret,true));
   }
 return $w;
} // end getToteInfo

function chkTask($toteId)
{
  global $comp;
  global $RESTSRV;
  if (isset($toteId) and $toteId <> "")
   { // a tote or pallet was scanned , diplay tote info
    $req=array("action"=>"chkTask",
              "company"=>$comp,
              "tote_id"=>$toteId
    );
    $ret1=restSrv($RESTSRV,$req);
    $task=(json_decode($ret1,true));
   }
  return $task;
 } // end chkTask

function askPart($theBin,$color="light-blue",$msg="")
{
 global $thisprogram;
 global $lastPart;
 global $dispBin;
 global $comp;
 global $nh;

 //if ($msg <> "") $color="red";

    $hiddens=<<<HTML
  <input type="hidden" name="func" id="func" value="scanPart">
  <input type="hidden" name="nh" value="{$nh}">
  <input type="hidden" name="theBin" value="{$theBin}">
  <input type="hidden" name="lastPart" value="{$lastPart}">
  <input type="hidden" name="dispBin" value="{$dispBin}">
  <input type="hidden" name="comp" value="{$comp}">
HTML;
   $fieldPrompt="Scan Part";
   $fieldPlaceHolder="Scan";
   $fieldId="";
   $msg2="Scan a Part from Bin {$theBin}";
   $fieldTitle=" title=\"{$msg2}\"";
   $extra_js="";
   $buttons=setCustomButtons("C");
   if ($lastPart <> "") $buttons=setCustomButtons("Q");
   $data=array("formName"=>"form1",
              "formAction"=>$thisprogram,
              "hiddens"=>$hiddens,
              "color"=>"w3-{$color}",
              "onChange"=>"do_submit();",
              "fieldType"=>"text",
              "fieldValue"=>"",
              "fieldPrompt"=>$fieldPrompt,
              "fieldPlaceHolder"=>$fieldPlaceHolder,
              "fieldName"=>"partNumber",
              "fieldId"=>$fieldId,
              "fieldTitle"=>$fieldTitle,
              "msg"=>$msg,
              "msg2"=>$msg2,
              "buttons"=>$buttons,
              "function"=>""
    );
  $ret=frmtScreen($data,$thisprogram,"generic2");
  return $ret;


} // end askPart
function sendToBin($tote,$part,$po,$color="light-blue",$msg="")
{
 global $thisprogram;
 global $nh;
 global $comp;
 global $toteId;
 $pkgQty=1;
 if ($part["Result"]["alt_type_code"] < 0) $pkgQty=intval(-$part["Result"]["alt_type_code"]);
 if ($msg <> "") $color="red";

    $hiddens=<<<HTML
  <input type="hidden" name="func" id="func" value="putBin">
  <input type="hidden" name="nh" value="{$nh}">
  <input type="hidden" name="toteId" value="{$tote[1]["tote_id"]}">
  <input type="hidden" name="wmspo" value="{$po["po_number"]}">
  <input type="hidden" name="hostpo" value="{$po["host_po_num"]}">
  <input type="hidden" name="batch_num" value="{$po["batch_num"]}">
  <input type="hidden" name="comp" value="{$comp}">
  <input type="hidden" name="shadow" value="{$part["Part"]["shadow_number"]}">
  <input type="hidden" name="partUOM" value="{$part["Part"]["unit_of_measure"]}">
  <input type="hidden" name="pkgQty" value="{$pkgQty}">

HTML;
   $bin="";
   $bin2="";
   $obins=array();
   $binPrompt="Primary Bin";
   $binPrompt2="Other Bins";
   $msg2="Scan the Bin to put this item into";
  
$obins=array();
   if (count($part["WhseLoc"]) > 0)
   { // fill in primary bin and other bins array
    foreach ($part["WhseLoc"] as $key=>$w)
     {
      if ($w["whs_code"] == "P")
       {
        $bin=$w["whs_location"];
        $hiddens.=<<<HTML
  <input type="hidden" name="primaryBin" value="{$bin}">

HTML;
       }
      else array_push($obins,array("obin"=>$w["whs_location"]));
     } // end for each whseloc
   } // fill in primary bin and other bins array

  if (trim($part["WhseQty"][$comp]["primary_bin"])== "")
   { // set prefered zone and aisle because primary is not set
    $color="yellow";
    $msg="No Primary Bin is Set";
    $binPrompt="Pref Zone/Aisle";
    $binPrompt2="Zone: {$part["ProdLine"]["pl_perfered_zone"]} Aisle: {$part["ProdLine"]["pl_perfered_aisle"]}";
    $msg2="Preferred {$binPrompt2}";
    $bin="";
    $bin2="";
    array_push($obins,array("obin"=>$binPrompt2));
   } // set prefered zone and aisle because primary is not set

    $binPrompt2="otherBins";
   if (count($obins) > 0)
   {
     foreach ($obins as $key=>$b)
     {
      $hiddens.=<<<HTML
  <input type="hidden" name="obin[{$key}]" value="{$b["obin"]}">

HTML;

     } // end foreach obins
   } // end obins count > 0
   $fieldPrompt="Scan Bin {$bin}";
   $fieldPlaceHolder="Scan Bin";
   $fieldId="";
   $fieldTitle=" title=\"{$msg2}\"";
   $extra_js="";
   $buttons=setCustomButtons("C");
   $Qty=1;
   if ($part["Result"]["alt_type_code"] < 0) $Qty= -$part["Result"]["alt_type_code"];
   $tQty=1;
   if ($tote[1]["tote_qty"] <> 1) $tQty=$tote[1]["tote_qty"];
   $tqClass="";
   if ($tQty > $Qty)
    {
     $tqClass="class=\"Alt7DataTD\"";
     $msg2="<span class=\"Alt7DataTD\" style=\"word-wrap: normal;font-weight: bold; font-size: large; margin-left: 0px; text-align: cput;\">Total {$tQty} of this Part are in this Tote</span><br>Scan the Bin to put this item into";
    }
   $data=array("formName"=>"form1",
              "formAction"=>$thisprogram,
              "hiddens"=>$hiddens,
              "color"=>"w3-{$color}",
              "onChange"=>"document.form1.submit();",
              "fieldType"=>"text",
              "fieldValue"=>"",
              "fieldPrompt"=>$fieldPrompt,
              "fieldPlaceHolder"=>$fieldPlaceHolder,
              "fieldName"=>"bin",
              "fieldId"=>$fieldId,
              "fieldTitle"=>$fieldTitle,
 		"pl"=>$part["Part"]["p_l"],
 		"partNumber"=>$part["Part"]["part_number"],
 		"pdesc"=>$part["Part"]["part_desc"],
 		"Qty"=>$Qty,
                 "toteQty"=>$tQty,
                 "tqClass"=>$tqClass,
                 "binPrompt"=>$binPrompt,
                 "binPrompt2"=>$binPrompt2,
                 "bin"=>$bin,
                 "bin2"=>$bin2,
                 "obins"=>$obins,
              "msg"=>$msg,
              "msg2"=>$msg2,
              "buttons"=>$buttons,
              "function"=>""
    );
//echo "<pre>Here";
//print_r($data);
//echo "</pre>";
  $save_sendToBin=base64_encode(json_encode($data));
  $data["hiddens"].=<<<HTML
  <input type="hidden" name="save_sendToBin" value="{$save_sendToBin}">

HTML;
//echo "<pre>";
//print_r($data);
//echo "</pre>";
  $ret=frmtScreen($data,$thisprogram,"putBin");
  return $ret;


} // end sendToBin

function startScreen($msg,$color="blue",$override=false)
{
  global $thisprogram;
 global $nh;
 global $db;
 global $PARTSRV;
 global $comp;

  $req=array("action"=>"getOpenInvBatches",
              "company"=>$comp
     );
     $ret=restSrv($PARTSRV,$req);
     $invBatches=(json_decode($ret,true));

 if ($msg <> "" and !$override) $color="red";

    $hiddens=<<<HTML
  <input type="hidden" name="func" id="func" value="chooseType">
  <input type="hidden" name="nh" value="{$nh}">
  <input type="hidden" name="scanBin" value="">
HTML;
   $fieldPrompt="Please Choose";
   $fieldPlaceHolder="";
   $fieldId="";
   $msg2="";
   $fieldTitle="";
   $extra_js="";
   $buttons=setStdButtons("D");
   $data=array("formName"=>"form1",
              "formAction"=>$thisprogram,
              "hiddens"=>$hiddens,
              "color"=>"w3-{$color}",
              "onChange"=>"do_submit();",
              "fieldType"=>"text",
              "fieldValue"=>"",
              "fieldPrompt"=>$fieldPrompt,
              "fieldPlaceHolder"=>$fieldPlaceHolder,
              "fieldName"=>"invType",
              "fieldId"=>$fieldId,
              "fieldTitle"=>$fieldTitle,
              "curcounts"=>$invBatches,
              "msg"=>$msg,
              "msg2"=>$msg2,
              "buttons"=>$buttons,
              "function"=>""
    );
  $ret=frmtScreen($data,$thisprogram,"invStart");
  return $ret;


} // end startScreen

function scanBin($msg,$color="blue",$override=false)
{
 global $thisprogram;
 global $nh;
 if ($msg <> "" and !$override) $color="red";

    $hiddens=<<<HTML
  <input type="hidden" name="func" id="func" value="scanBin">
  <input type="hidden" name="nh" value="{$nh}">
  <input type="hidden" name="scanBin" value="">
HTML;
   $fieldPrompt="Scan Bin";
   $fieldPlaceHolder="Scan Bin to Inventory";
   $fieldId=" id=\"bin\"";
   $msg2="Scan Bin to Inventory";
   $fieldTitle=" title=\"{$msg2}\"";
   $extra_js="";
   $buttons=setStdButtons("D");

   $data=array("formName"=>"form1",
              "formAction"=>$thisprogram,
              "hiddens"=>$hiddens,
              "color"=>"w3-{$color}",
              "onChange"=>"do_submit();",
              "fieldType"=>"text",
              "fieldValue"=>"",
              "fieldPrompt"=>$fieldPrompt,
              "fieldPlaceHolder"=>$fieldPlaceHolder,
              "fieldName"=>"theBin",
              "fieldId"=>$fieldId,
              "fieldTitle"=>$fieldTitle,
              "msg"=>$msg,
              "msg2"=>$msg2,
              "buttons"=>$buttons,
              "function"=>""
    );
  $ret=frmtScreen($data,$thisprogram,"generic2");
  return $ret;
} // end scanBin

?>
