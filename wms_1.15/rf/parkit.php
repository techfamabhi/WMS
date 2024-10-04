<?php
// parkit.php -- for Packing, park tote awaiting other totes for this order

/*

Copy stuff from Order drop to set the toteloc, this came from packscan

  $req=array("action" => "updToteLoc",
    "company" => 1,
    "order_num" => $order_num,
    "tote_id" => $toteId,
    "zone"=>$myZones,
    "whseLoc"=>$dropZone
);
   $rc=restSrv($RESTSRV,$req);

*/

//echo "<pre>D1";
//print_r($_REQUEST);
//echo "</pre>";
if (!isset($toteId)) $toteId=0;
if (isset($_REQUEST["B2"]) and $_REQUEST["B2"] == "cancel")
{
 $msgCancel="Cancelled processing {$toteId}";
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
require_once("{$wmsInclude}/quoteit.php");
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
$title="Stage Tote";
$panelTitle="Tote #";
//$SRVPHP="{$wmsServer}/COMPANY_srv.php";
//$DRPSRV="{$wmsServer}/dropdowns.php";
// end application specific variables ---------------------------------

$opt[102]=get_option($db,$comp,102);
$opt[103]=get_option($db,$comp,103);
$packZones=getPackZones($db,$comp);
//echo "<pre>";
//print_r($packZones);
//echo "</pre>";

if (!isset($func)) $func="askArea";

switch ($func)
{
 case "askArea":
aA:
  $tmp=askArea(1,$thisprogram,$nh,$ord,$hord);
  if (count($tmp) > 0)
   { // Have a screen
    foreach($tmp as $w=>$val) { $$w=$val; }
   } // Have a screen

  
  break;
 
 case "checkArea":
  // this may have to expand to include valid bins too, if 3 characters is 
  // not enough for all stageing areas
  $newArea=$scaninput;
  if (isset($packZones[$newArea]))
  { // its a valid stage area
   $newloc=$newArea;
   $newzone="STG";
   $req=array("action" => "updToteLoc2",
    "company" => $comp,
    "order_num" => $ord,
    "tote_id" => $toteId,
    "zone"=>$newzone,
    "whseLoc"=>$newloc
);
  $nz=quoteit($newzone);
  $nl=quoteit($newloc);
   $rc=restSrv($RESTSRV,$req);
   $mainSection="";
   $js=<<<HTML
<script>
//alert(window.opener.document.getElementById('lastZone').textContent);
//alert(window.opener.document.getElementById('lastLoc').textContent);

  window.opener.setNewLoc({$toteId},{$nz},{$nl});
  //window.opener.document.getElementById('lastZone').textContent="{$newzone}";
  //window.opener.document.getElementById('lastLoc').textContent="{$newloc}";
   self.close();
</script>

HTML;
  } // its a valid stage area
 else goto aA;
  break;
 
} // end switch func
//echo "<pre>";
//print_r($_REQUEST);
//print_r($packZones);
//echo "</pre>";

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
if (isset($nh) and $nh > 0)
{
 $pg->noHeader=true;
}

if (!isset($ejs)) $ejs="";
if (!isset($otherScripts)) $otherScripts="";
$pg->jsh=<<<HTML
{$ejs}
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

function askArea($opt,$thisprogram,$nh,$orderNumber,$hostordernum,$msg="")
{
 global $toteId;
 $ret=array("msg"=>"","mainSection"=>"");
 if ($opt == "1")
  { //we need to drop
//Need to get all totes for the order to make sure the user drops all totes
   $hiddens=<<<HTML
  <input type="hidden" name="func" id="func" value="checkArea">
  <input type="hidden" name="nh" value="{$nh}">
  <input type="hidden" name="ord" value="{$orderNumber}">
  <input type="hidden" name="hord" value="{$hostordernum}">
  <input type="hidden" name="toteId" id="toteId" value="{$toteId}">
HTML;
   $fieldPrompt="Stage at";
   $fieldPlaceHolder="Scan or enter Stage Area";
   $fieldId=" id=\"dropzone\"";
   $fieldTitle=" title=\"Scan or Enter the Stage Area\"";
   $extra_js="";
   $color="blue";
   $msg2="Stage Tote {$toteId} in Stage Area";
   if ($msg=="")
   {
    $msg=$msg2;
    $msg2="";
   }
 
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
              "msg2"=>$msg2,
              "function"=>""
    );
   $ret["mainSection"]=frmtScreen($data,$thisprogram);
   $ret["msg"]="Moved Tote {$toteId}";
  } //we need to drop

  return $ret;
 }  // end askArea

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
and zone_type in ("PKG", "STG")

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

function frmtScreen($data,$thisprogram,$temPlate="generic2",$incFunction=true)
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

