<?php

// putaway.php -- put product away
// 07/06/22 dse initial
// 01/03/24 dse allow entry of new bin after confirmation
// 07/19/24 dse show all bins this part is in

/*TODO
// updated TOTE hdr after the move, need to redirct for next tote and display messg if successful
Need method to go back to menu

check to make sure part is in the tote
make sure they don't go over the qty in the tote

*/
session_start();
foreach (array_keys($_REQUEST) as $w) { $$w=$_REQUEST[$w]; }
//error_reporting(0);

//echo "<pre> REQUEST=";
//print_r($_REQUEST);
//exit;
//echo "</pre>";
//PHPINFO(INFO_VARIABLES);
if (isset($_REQUEST["B2"]) and $_REQUEST["B2"] == "cancel")
{
} // end b2 is set
if (isset($_SESSION["REQDATA"]))
{
 $w="";
 if (isset($_SESSION["REQDATA"]["ts"])) $w=$_SESSION["REQDATA"]["ts"];
 if (isset($_REQUEST["ts"])) $w1=$_REQUEST["ts"]; else $w1=time();
 if ($w == $w1 and 1 == 1)
 { // looks like they refreshed the screen
//echo "<pre>";
//print_r($_REQUEST);
//exit;

   if (isset($_REQUEST["func"]) and $_REQUEST["func"]=="putBin")
   { // reset back to scan the part again
     $r=array("func"=>"whatToDo",
              "toteId"=>$_REQUEST["toteId"],
              "nh"=>$_REQUEST["nh"],
              "R1"=>1,
              "B1"=>"submit"
     );
     $r["msg"]  ="";
     if (isset($msg)) $r["msg"]=$msg;
     $_REQUEST=$r;
     unset($r);
     unset($w);
     unset($w1);
   } // reset back to scan the part again
 } // looks like they refreshed the screen
} // end REQDATA is set
if (isset($_REQUEST))
{
 $_SESSION["REQDATA"]=$_REQUEST;
}



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
require_once("{$wmsInclude}/wr_log.php");
require_once("{$wmsInclude}/date_functions.php");
require_once("{$wmsInclude}/get_option.php");

require_once("../include/restSrv.php");
require_once("pb_utils.php");
require_once("collapse.php");
require_once("dispBin.php");


$RESTSRV="http://{$wmsIp}{$wmsServer}/RcptLine.php";
$PARTSRV="http://{$wmsIp}{$wmsServer}/whse_srv.php";
$UPDSRV="http://{$wmsIp}{$wmsServer}/PO_srv.php";
$comp=$wmsDefComp;
$db=new WMS_DB;
//if ($UserID == 1)
//{
//echo "<pre> REQUEST=";
//print_r($_REQUEST);
//echo "</pre>";
//}

$opt[27]=get_option($db,$comp,27);
// Application Specific Variables -------------------------------------
$temPlate="generic1";
$title="Putaway";
if (isset($toteId) and $toteId <> "") $title="Putaway Tote # {$toteId}";
$panelTitle="Putaway Tote #";
//$SRVPHP="{$wmsServer}/COMPANY_srv.php";
//$DRPSRV="{$wmsServer}/dropdowns.php";
// end application specific variables ---------------------------------

if (!isset($func)) $func="scanScreen";
if (!isset($msg)) $msg="";
if (!isset($newLoc)) $newLoc="";
if (!isset($toteId)) $toteId="";
if ($func == "palletToMove" and $toteId == "" and $B1 == "submit") $func="scanScreen";
if ($func == "movingPallet" and $newLoc == "" and $B1 == "submit") $func="palletToMove";
if ($func == "palletToMove" and $toteId <> "") 
{ // press Random Putawat for the user right now
 $func="whatToDo";
 $R1=1;
} // press Random Putawat for the user right now

if ($func == "whatToDo")
{
 if (isset($R1))
 { // user answered what to do
  if ($R1 == 3) $func="movePallet";
  else if ($R1 == 2) $func="directedPutaway";
  else $func="askPart";
  if ($func == "directedPutaway") $func="askPart"; // No Directed Yet
 } // user answered what to do
} // end func == whatToDo

//if ($func == "donePressed" and isset($B2) and $B2 == "cancel")
if ($func == "donePressed")
{
 $toteId="";
 $title="Putaway";
 $func="scanScreen";
} // end donePressed

if ($func == "donePressed" and isset($B2) and $B2 == "done")
{
 require("{$wmsInclude}/backToMenu.php");
}
if ($func == "movePallet" and isset($toteId))
{
  $redirect="palletmove.php?func=palletToMove&nh=0&toteId={$toteId}&from=putaway";
  redirect($redirect);
}

//echo $func;
switch ($func)
{
 case "scanScreen":
 { // Display Scan Tote screen
  if (isset($msg)) $msg="";
  if (isset($msgCancel)) $msg=$msgCancel;
  $color="light-blue";
  if ($msg <> "") $color="green";
  $mainSection=entOrderTote($msg,$color);
  break;
 } // End Display Scan Tote screen

 case "askPart":
 case "chkPart":
 {
  if (isset($toteId) and $toteId <> "")
  { // tote not empty
   $w=getToteInfo($toteId);
   if (isset($w[1]))
   {
     if ($w[1]["num_items"] < 1)
      {
      $color="blue";
      $msg="There are no more Items in Tote {$toteId}";
      $mainSection=entOrderTote($msg,$color,true);
      break;
     }
   } // end w[1] is set 
   else 
   {
      $color="red";
      $msg="Invalid Tote {$toteId}, please try again.";
      $mainSection=entOrderTote($msg,$color,true);
      break;
   } // invalit tote

   if (isset($partNumber)) $partNumber=strtoupper($partNumber);
   if (isset($w[1]))
   {
//echo "<pre>";
//print_r($w);
//exit;
    if ($func == "chkPart")
    {
     $ok=true;
     $req=array("action"=>"getPart",
              "company"=>$comp,
              "partNumber"=>$partNumber
     );
     $ret=restSrv($PARTSRV,$req);
     $part=(json_decode($ret,true));
//echo "<pre>";
//print_r($part);
//echo "</pre>";
     if (!isset($part["status"]) 
      or (isset($part["status"]) and $part["status"] == -35))
     { //part not found
       $ok=false;
       $msg="Invalid Part!";
       $mainSection=askPart($toteId,"red",$msg);
       break; 
     } //part not found
     if (isset($part["choose"]))
     { // find which part is in the tote
      foreach($part["choose"] as $key=>$p)
      {
       $s=$p["shadow_number"];
       $aqty=$p["alt_type_code"];
       $auom=$p["alt_uom"];
       $pn=$p["alt_part_number"];
       $req=array("action"=>"getToteDetail",
              "company"=>$comp,
              "tote_id"=>$toteId,
              "shadow"=>$s
        );
       $ret1=restSrv($RESTSRV,$req);
       $tpart=(json_decode($ret1,true));
       if ($tpart["numRows"] > 0)
       {
        $pn1="." . trim($s);
        $req=array("action"=>"getPart",
              "company"=>$comp,
              "partNumber"=>$pn1
     );
     $ret=restSrv($PARTSRV,$req);
     $part=(json_decode($ret,true));
        if (isset($part["Result"]))
        {
         $part["Result"]["alt_type_code"]=$aqty;
         $part["Result"]["alt_part_number"]=$pn;
         $part["Result"]["alt_uom"]=$auom;
        }
       }
      } // end foreach choose part
     } // find which part is in the tote
     if (isset($part["Result"]))
     { // part is good, see if it's in this tote
      $req=array("action"=>"getPartInTote",
              "company"=>$comp,
              "tote_id"=>$toteId,
              "shadow"=>$part["Result"]["shadow_number"],
              "onlyOpen"=>1
       );
      $ret1=restSrv($RESTSRV,$req);
      $toteDtl=(json_decode($ret1,true));
      if (isset($toteDtl["errCode"]))
      {
       $ok=false;
       $msg=$toteDtl["errText"];
       $mainSection=askPart($toteId,"red",$msg);
       break; 
      } // end errCode is set
     } // part is good, see if it's in this tote
    if (count($toteDtl) > 0 and $ok)
    { // good part and it is in this tote, send them to the bin
      $req["action"]="getPoForPart";
      $ret1=restSrv($RESTSRV,$req);
      $poInfo=(json_decode($ret1,true));
      $mainSection=sendToBin($toteDtl,$part,$poInfo,$color="green",$msg="");
      if (count($part["WhseLoc"]) > 0)
      {
       $mainSection.="\n" . collapseCss();
       $mainSection.=dispBins($part["WhseLoc"]);
       $mainSection.="\n" . collapseJs();
      }
//echo "<pre>{$toteId}";
//print_r($part["WhseLoc"]);
//echo $mainSection;
//print_r($poInfo);
//echo "</pre>";
//print_r($part);
//exit;
    } // good part and it is in this tote, send them to the bin
    } // end chkPart
    else
    { // ask scan part
     $mainSection=askPart($toteId);
    } // ask scan part
   } // w[1] is set
  } // tote not empty
  break;
 } // askPart

 case "putBin":
 {
  // bin is entered, check if valid for this part
  // If so,  update the part
  $validbins=array();
  if (isset($primaryBin)) array_push($validbins,$primaryBin);
  if (isset($obin) and count($obin > 0))
  {
     foreach ($obin as $key=>$b)
     {
      if (substr($b,0,1) <> "!") array_push($validbins,$b);
     } // end foreach obin
  } // push the obin
  $ok=false;
  if (count($validbins))
  { // validate the bins, else let it fall thru means there is no bins yet
   foreach ($validbins as $b)
   {
    if ($bin == $b) $ok=true;
   }
   $ovr=false;
   if (isset($binOverRide) and $binOverRide) $ok=true;
   if (!$ok)
   { // not a valid bin for this part, redirect them to a proper bin
     $msg="Invalid Bin For this Part";
   } // not a valid bin for this part, redirect them to a proper bin
  } // validate the bins, else let it fall thru means there is no bins yet
 // validate bin entry 
   $req=array("action"=>"validateBin",
              "company"=>$comp,
              "bin"=>$bin
   );
//Should be PARTSRV, but the update fails
  $ret=restSrv($PARTSRV,$req);
  $w=(json_decode($ret,true));
  if (isset($w["numRows"]) and $w["numRows"] < 1)
  { // invalid bin
   //redisplay putbin screen
     $msg="Invalid Bin 2";
     $ok=false;
  }  // invalid bin
  else
  { // check if primary bin is set or blank, if so, set it to this bin
   $setPrim=false;
   if (!isset($primaryBin) and trim($bin) <> "") $setPrim=true;
   if (isset($primaryBin) and trim($primaryBin) == "") $setPrim=true;
   if ($setPrim)
   {
    $primaryBin=$bin;
    $ok=true;
   }
  } // check if primary bin is set or blank, if so, set it to this bin
  if (isset($ok) and $ok) 
  { // its ok, update it
   $userId=$_SESSION["wms"]["UserID"];
   $toteCode=$toteId;
   $w=getToteInfo($toteId);
   if (count($w) > 0)
    {
     $toteType=$w[1]["tote_type"];
     $toteCode=$w[1]["tote_id"];
     $numItems=$w[1]["totalQty"];
    }
   if (!isset($toteType)) $toteType="";
   if (($opt[27] > 0 and $toteType == "RCS") or $toteType == "MOV" or $toteType == "RET")
   { // part has already been stocked, move it to the bin
         $req=array("action"=>"movePart",
              "comp"=>$comp,
              "userId"=>$userId,
              "shadow"=>$shadow,
              "qty"=>$Qty,
              "sourceBin"=>"!{$toteCode}",
              "destBin"=>$bin,
              "po"=>$hostpo
     );
      if ($toteType == "RET") $req["updWhseQty"]=1;
      $ret=restSrv($PARTSRV,$req);
      $w1=(json_decode($ret,true));

      if (isset($w1["Status"]) and $w1["Status"] == "OK")
      { 
       $numItems--;
       $ret='{"status":1,"updQty":true,"updScan":1,"updTote":1,"toteItems":"' . $numItems . '"}';
      }
      else
      {
       $ret='{"status":0 ' .  $w1["Status"] . ',"updQty":false,"updScan":0,"updTote":0,"toteItems":"' . $numItems . '"}';
      }
      $y=(json_decode($ret,true));
//echo "<pre>";
//print_r($req);
//print_r($w1);
//print_r($_REQUEST);
//exit;
   } // part has already been stocked, move it to the bin
   else
   { // move part to bin and update stocked and inventory
   if (is_array($wmspo) and count($wmspo > 0))
   {
    $ok=true;
    $qtyLeft=$Qty;
    foreach ($wmspo as $key=>$p)
    {
     $pop=$wmspo[$key];
     $poh=$hostpo[$key];
     $pob=$batch_num[$key];
     $poq=$po_qty[$key];
//echo "<pre>key={$key} wmspo={$pop} poh={$poh} pob={$pob} poq={$poq}";
//exit;
     $qtp=0;
     if ($qtyLeft > 0)
     {
      $qtp=$qtyLeft;
      if ($qtp > $poq)
      {
       $qtp=$poq;
      }
      $qtyLeft=$qtyLeft - $qtp;
     }
     if ($qtp > 0)
     {
      $req=array("action"=>"putAway",
              "company"=>$comp,
              "userId"=>$userId,
              "wms_po_num"=>$pop,
              "host_po_num"=>$poh,
              "batch"=>$pob,
              "toteId"=>$toteId,
              "shadow"=>$shadow,
              "primaryBin"=>$primaryBin,
              "qtyStockd"=>$qtp,
              "BinTote"=>$bin,
              "partUOM"=>$partUOM,
              "pkgQty"=>$pkgQty
        );
//echo "<pre>";
//print_r($req);
      $ret=restSrv($UPDSRV,$req);
      $y=(json_decode($ret,true));
//print_r($y);
//echo "ql={$qtyLeft}\n";
//exit;
      if ($y["status"] <> 1 or $qtyLeft < 1) break;
     }
    } // end foreach wmspo
   } // end wmspo is array
   else
   { // wms not an array
    $req=array("action"=>"putAway",
              "company"=>$comp,
              "userId"=>$userId,
              "wms_po_num"=>$wmspo,
              "host_po_num"=>$hostpo,
              "batch"=>$batch_num,
              "toteId"=>$toteId,
              "shadow"=>$shadow,
              "primaryBin"=>$primaryBin,
              "qtyStockd"=>$Qty,
              "BinTote"=>$bin,
              "partUOM"=>$partUOM,
              "pkgQty"=>$pkgQty
      );
    $ret=restSrv($UPDSRV,$req);
    $y=(json_decode($ret,true));

   } // wms not an array
   } // move part to bin and update stocked and inventory
//echo "<pre>";
//print_r($req);
//print_r($w1);
//print_r($y);
//exit;
//print_r($ret);
   if (!isset($msg)) $msg="";
   if (!isset($y["status"])) $y["status"]=0;
   if ($y["status"] == 1)
   {
     if ($y["toteItems"] > 0) 
      {
       //$mainSection=askPart($toteId);
       $redirect="putaway.php?func=whatToDo&nh={$nh}&R1=1&toteId={$toteId}";
       redirect($redirect);
      } 
     else
     {
      $color="blue";
      $msg="There are no more Items in Tote {$toteId}";
      $mainSection=entOrderTote($msg,$color,true);
     }
   } // end status = 1
   else
   { // and error occured
       $redirect="putaway.php?func=whatToDo&nh={$nh}&R1=1&toteId={$toteId}&msg=";
     $redirect.=urlencode("Error Occurred, Please try again. {$ret}");
     redirect($redirect);
   } // and error occured
  } // its ok, update it
 else
  {
   if (isset($save_sendToBin))
   {
    $data=json_decode(base64_decode($save_sendToBin),true);
    if ($msg <> "") $data["msg"]=$msg;
    else $data["msg"]="Invalid Bin";
    $data["color"]="w3-red";
    if (count($data["obins"]) > 0 and $data["obins"][0]["obin"] == "") unset($data["obins"]);
//echo "<pre>";
//print_r($data);
//echo "</pre>";

      $save_sendToBin=base64_encode(json_encode($data));
  $data["hiddens"].=<<<HTML
  <input type="hidden" name="save_sendToBin" value="{$save_sendToBin}">

HTML;

//echo "<pre>";
//print_r($data);
//exit;
    $mainSection=frmtScreen($data,$thisprogram,"putBin1");
    break;
   } // end save_sendToBin
  }
  break;
 } // end putBin

 case "palletToMove":
 {
 if (isset($toteId) and $toteId <> "")
  { // a tote or pallet was scanned , diplay tote info
  $title=$panelTitle . $toteId;
  $w=getToteInfo($toteId);
//print_r($req);
 $ww="";
 $notGoodTote="";
 
 if (isset($w[1]) and array_key_exists("tote_type",$w[1])) $ww=$w[1]["tote_type"];
 if ($ww <> "RCV" and $ww <> "PUT" and $ww <> "MOV" and $ww <> "RET")
 {
  $notGoodTote=$ww;
  //unset($w);
  //$w=array();
 }
 if (isset($w[1]) and isset($w[1]["totalQty"]) and intval($w[1]["totalQty"]) > 0)
 { // display Order and Tote Info
 $task=chkTask($toteId);
  if (count($task) > 0)
  {
   //check if tote is moving by the same user
   //echo "<pre> task=";
  //print_r($task);
   //echo "</pre>";
  } // end count task > 0

  $target_zone="";
  $target_aisle="";
  $req=array("action"=>"getToteLoc",
              "company"=>$comp,
              "tote_id"=>$toteId
   );
   $ret=restSrv($RESTSRV,$req);
   $y=(json_decode($ret,true));
   if (isset($y[1])) foreach ($y as $idx=>$target)
   {
    $last_zone="";
    $last_loc="";
    if (isset($target["target_zone"])) $target_zone=att($target_zone,$target["target_zone"]);
    if (isset($target["target_aisle"])) $target_aisle=att($target_aisle,$target["target_aisle"]);
    if (isset($target["last_zone"])) $last_zone=att($last_zone,$target["last_zone"]);
    if (isset($target["last_location"])) $last_loc=att($last_location,$target["last_location"]);
   } // end foreach y
   $msg="";
   $mmsg="";
   $templte="palletMove";
   if (!isset($last_zone))
   {
    $templte="generic2";
    if (isset($task[1])) $last_loc="{$task[1]["tote_type"]} {$task[1]["tote_ref"]}";
    else $last_loc="";
    $target_zone="";
    $target_aisle="";
    $mmsg="<br>Warning, this is not a Receiving Tote";
    // ***** Putaway Mode ***************************
 
// temp code to try putaway
/*
    $color="blue";
    $msg="Scan Part";
    $vendor="";
    $title="Putaway Tote " . $toteId;
    $work=frmtPartScan($vendor,$msg,$color);
    $buttons=setStdButtons("C",true);
    $mainSection=str_replace("_BUTTONS_",$buttons,$work);
    unset($work);

    break;  // **** End Putaway Mode *******
*/
// temp code to try putaway
   }
    // ask Method Ad-Hoc Mode or directed mode
   $mainSection=askWhatToDo($mmsg,$toteId,$color="light-blue");

} // display Order and Tote Info
else
{ // tote not found
 $color="red";
 $msg="Pallet/Tote Not Found";
 if (isset($w[1]))
 {
  //if (intval($w[1]["totalQty"]) < 1) echo "\nit is less than 1\n";
  if (array_key_exists("totalQty",$w[1]) and intval($w[1]["totalQty"]) < 1)
  {
   $color="yellow";
   $msg="Pallet/Tote {$toteId} does not have any Parts in it to Put Away";
   }
  } // w[1] is set
 if ($notGoodTote <> "")
 {
  $msg="Tote is not a Receiving Tote, Please choose another Tote";
 }
 
 $mainSection=entOrderTote($msg,$color);

} // end tote not found
} // end Display Tote
break;
} // end palletToMove

case "movePallet":
{
  $num_items="";
  $totalQty="";
  if (isset($w[1]["num_items"])) $num_items=$w[1]["num_items"];
  if (isset($w[1]["totalQty"])) $totalQty=$w[1]["totalQty"];
   $req=array("action"=>"addTask",
              "company"=>$comp,
              "tote_id"=>$toteId,
              "operation"=>"MOV"
   );
 $ts=time();

   $hiddens=<<<HTML
<form name="form1" action="palletmove.php" method="get">
  <input type="hidden" name="func" id="func" value="movingPallet">
  <input type="hidden" name="nh" value="0">
  <input type="hidden" name="ts" value="{$ts}">
  <input type="hidden" name="toteId" value="{$toteId}">
  <input type="hidden" name="target_zone" value="{$target_zone}">
  <input type="hidden" name="target_aisle" value="{$target_aisle}">
  <input type="hidden" name="num_items" value="{$num_items}">
  <input type="hidden" name="totalQty" value="{$totalQty}">

HTML;
   $color="green";
   $fieldPrompt="Scan New Location";
   $fieldPlaceHolder="Scan New Pallet/Tote Location";
   $fieldId="new_Loc";
   $fieldTitle=" title=\"Scan New Pallet/Tote Location\"";
   if ($last_zone <> "") $msg="Last Zone: {$last_zone}{$mmsg}";
   if ($last_loc <> "") $msg="   Last Location: {$last_loc}{$mmsg}";
   $msg2="Move Pallet/Tote {$toteId} -";
   $msg2.=" Items: {$num_items}, Units: {$totalQty}";

   $buttons=setStdButtons("C");
   $data=array("formName"=>"form1",
              "formAction"=>$thisprogram,
              "hiddens"=>$hiddens,
              "color"=>"w3-{$color}",
              "onChange"=>"do_submit();",
              "fieldType"=>"text",
              "fieldValue"=>"",
              "fieldPrompt"=>$fieldPrompt,
              "fieldPlaceHolder"=>$fieldPlaceHolder,
              "fieldName"=>"newLoc",
              "fieldId"=>$fieldId,
              "fieldTitle"=>$fieldTitle,
              "msg"=>$msg,
              "msg2"=>$msg2,
              "buttons"=>$buttons,
              "target_zone"=>$target_zone,
              "target_aisle"=>$target_aisle,
              "function"=>""
    );
  $mainSection=frmtScreen($data,$thisprogram,$templte);
  break;
 
} // end movePallet

 case "movingPallet":
 {
  $title=$panelTitle . $toteId;
  $req=array("action"=>"getNewLoc",
              "company"=>$comp,
              "tote_id"=>$toteId,
              "newLoc"=>$newLoc
   );
   $ret=restSrv($RESTSRV,$req);
 $w=(json_decode($ret,true));
 if (count($w))
 {
  $j=$w[1];
  $req=array(
   "action"=> "updToteLoc",
   "company"=> $comp,
   "tote_id"=> $toteId,
   "operation"=> $j["zone_type"],
   "zone"=> $j["zone_type"],
   "newBin"=> $j["zone"]
  );
   $ret=restSrv($RESTSRV,$req);
   $w1=(json_decode($ret,true));
//  echo "<pre>getNewLoc Results=";
//print_r($w);
//print_r($w1);
 } // end count w
  break;
 } // end moveingPallet

} // end switch func

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
        var popup=window.open(url,"popup", "toolbar=no,left=10,top=10,status=yes,resizable=yes,scrollbars=yes,width=400,height=" + hgt );
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
if (!isset($mainSection))
{
 $logfile="/tmp/putaway.log";
 $x=json_encode(get_defined_vars());
 wr_log($logfile,$x);
}
//echo "<pre>";
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

 $ts=time();
    $hiddens=<<<HTML
  <input type="hidden" name="func" id="func" value="palletToMove">
  <input type="hidden" name="nh" value="{$nh}">
  <input type="hidden" name="ts" value="{$ts}">
  <input type="hidden" name="scanTote" value="">
HTML;
   $fieldPrompt="Tote or Pallet";
   $fieldPlaceHolder="Scan Tote/Pallet Id to Putaway";
   $fieldId=" id=\"toteid\"";
   $msg2="Scan Tote/Pallet (Tote, Pallet, Cart, etc) to Putaway";
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

function setCustomButtons($DorC="D")
{
 global $toteId;
 // args D=Done, C=Cancel
 $w="done";
 $w1="Done";
 if ($DorC == "C")
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
            "btn_value" => "ViewTote",
            "btn_onclick" => "doView('{$toteId}'); return false;",
            "btn_prompt" => "View"
        ),
    2 => Array(
            "btn_id" => "b2",
            "btn_name" => "B2",
            "btn_value" => $w,
            "btn_onclick" => "do_done();",
            "btn_prompt" => $w1 
        )
);
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

 $ts=time();
    $hiddens=<<<HTML
  <input type="hidden" name="func" id="func" value="whatToDo">
  <input type="hidden" name="nh" value="{$nh}">
  <input type="hidden" name="ts" value="{$ts}">
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

function askPart($toteId,$color="light-blue",$msg="")
{
 global $thisprogram;
 global $nh;

 if ($msg=="" and isset($_REQUEST["msg"]) and $_REQUEST["msg"] <> "") $msg=$_REQUEST["msg"];
 if ($msg <> "") $color="red";

 $ts=time();
    $hiddens=<<<HTML
  <input type="hidden" name="func" id="func" value="chkPart">
  <input type="hidden" name="nh" value="{$nh}">
  <input type="hidden" name="ts" value="{$ts}">
  <input type="hidden" name="toteId" value="{$toteId}">
HTML;
   $fieldPrompt="Scan Part";
   $fieldPlaceHolder="Scan";
   $fieldId="";
   $msg2="Scan a Part from Tote {$toteId}";
   $fieldTitle=" title=\"{$msg2}\"";
   $extra_js="";
   $buttons=setCustomButtons("C");
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
 if (!isset($po["po_number"]) or (is_array($po) and count($po) < 1))
 {
 $pohidden=<<<HTML
  <input type="hidden" name="wmspo" value="">
  <input type="hidden" name="hostpo" value="">
  <input type="hidden" name="batch_num" value="0">
  <input type="hidden" name="po_qty" value="1">
 
HTML;
 }
 if (is_array($po) and count($po > 0))
 {
  $pohiddens="";
  foreach ($po as $key=>$p)
  {
   if (is_numeric($key))
   {
    $pohidden.=<<<HTML
  <input type="hidden" name="wmspo[{$key}]" value="{$p["po_number"]}">
  <input type="hidden" name="hostpo[{$key}]" value="{$p["host_po_num"]}">
  <input type="hidden" name="batch_num[{$key}]" value="{$p["batch_num"]}">
  <input type="hidden" name="po_qty[{$key}]" value="{$p["Qty"]}">
 
HTML;
   }
  }
 }

  //<input type="hidden" name="toteId" value="{$tote[1]["tote_id"]}">
 $ts=time();
    $hiddens=<<<HTML
  <input type="hidden" name="func" id="func" value="putBin">
  <input type="hidden" name="nh" value="{$nh}">
  <input type="hidden" name="ts" value="{$ts}">
  <input type="hidden" name="toteId" value="{$toteId}">
  {$pohidden}
  <input type="hidden" name="comp" value="{$comp}">
  <input type="hidden" name="shadow" value="{$part["Part"]["shadow_number"]}">
  <input type="hidden" name="partUOM" value="{$part["Part"]["unit_of_measure"]}">
  <input type="hidden" name="binOverRide" id="bOR" value="N">
  <input type="hidden" name="pkgQty" value="{$pkgQty}">

HTML;
   $bin="";
   $bin2="";
   $obin=array();
   $binPrompt="Primary Bin";
   $binPrompt2="Other Bins";
   $msg2="Scan the Bin to put this item into";
  
$obin=array();
   if (count($part["WhseLoc"]) > 0)
   { // fill in primary bin and other bins array
    foreach ($part["WhseLoc"] as $key=>$w)
     {
      if ($w["whs_code"] == "P")
       {
        $bin2=$w["whs_location"];
        $bin=<<<HTML
<button type="button" class="binbutton-tiny" id="pbb" name="pbb" value="" onclick="setBin('{$bin2}');">{$bin2}</button>
HTML;
        $hiddens.=<<<HTML
  <input type="hidden" name="primaryBin" value="{$bin2}">

HTML;
       }
      else 
      {
       $wb=$w["whs_location"];
       $a=<<<HTML
<button type="button" class="binbutton-tiny" value="" onclick="setBin('{$wb}');">{$wb}</button>
HTML;

      if (substr($wb,0,1) <> "!") array_push($obin,array("obin"=>$wb));
      }
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
    array_push($obin,array("obin"=>$binPrompt2));
   } // set prefered zone and aisle because primary is not set

    $binPrompt2="otherBins";
   if (count($obin) > 0)
   {
     foreach ($obin as $key=>$b)
     {
      if (substr($b["obin"],0,1) <> "!")
      $hiddens.=<<<HTML
  <input type="hidden" name="obin[{$key}]" value="{$b["obin"]}">

HTML;

     } // end foreach obin
   } // end obin count > 0
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
   $oc="validateBin(this);";
   if (trim($bin) == "") $oc="document.form1.submit();";
   $data=array("formName"=>"form1",
              "formAction"=>$thisprogram,
              "hiddens"=>$hiddens,
              "color"=>"w3-{$color}",
              "onChange"=>$oc,
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
                 "obins"=>$obin,
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
//exit;
//echo "</pre>";
  $ret=frmtScreen($data,$thisprogram,"putBin1");
  return $ret;


} // end sendToBin

function redirect($url)
{ // redirect and end
  $redirect=$url;
  $htm=<<<HTML
     <html>
     <head>
     <script>
    window.location.href="{$redirect}";
     </script>
     </head>
     <body>
     </body>
     </html>

HTML;
     echo $htm;
     exit;
} // end redirect
?>
