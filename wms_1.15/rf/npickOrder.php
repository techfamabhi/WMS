<?php

// pickOrder.php -- Discrete Order Picking, 1 Order at a time
// 03/15/22 dse initial
/*TODO
problems
1) With drop option turned off, screen goes blank when pick is complete
2) Drop screen not showing -FIXED
3) Tote enter was not working, added new func enterTote but not active yet
4) Totes and Drops
   Need place to store all tote ids for an order
   need place to store all tote ids for an item
   need place to store all drop locations for a tote
*/

//echo "<pre>";
//print_r($_REQUEST);
//echo "</pre>";
foreach (array_keys($_REQUEST) as $w) { $$w=$_REQUEST[$w]; }
//error_reporting(0);

session_start();
if (get_cfg_var('wmsdir')) $wmsDir=get_cfg_var('wmsdir');
else { echo "<h1>WMS System is not Configured on this System</h1>"; exit; }
$top=str_replace("/var/www","",$wmsDir);


$thisprogram=basename($_SERVER["PHP_SELF"]);
require("{$wmsDir}/config.php");
require_once("{$wmsInclude}/chklogin.php");
require_once("{$wmsInclude}/cl_rf.php");
require_once("{$wmsInclude}/cl_template.php");
require_once("{$wmsInclude}/cl_PARTS2.php");
require_once("{$wmsInclude}/db_main.php");
require_once("{$wmsInclude}/get_option.php");
require_once("../include/restSrv.php");

$RESTSRV="http://{$wmsIp}{$wmsServer}/PICK_srv.php";
$comp=$wmsDefComp;
$db=new WMS_DB;
$opt=array();

// Application Specific Variables -------------------------------------
$temPlate="generic1";
$title="Picking";
$panelTitle="Order #";
//$SRVPHP="{$wmsServer}/COMPANY_srv.php";
//$DRPSRV="{$wmsServer}/dropdowns.php";
// end application specific variables ---------------------------------

$opt[102]=get_option($db,$comp,102);
$opt[103]=get_option($db,$comp,103);

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
if (!isset($fPQ)) $fPQ="0";
if (!isset($msg)) $origMsg="";
else $origMsg=$msg;
$msg="";
$msgcolor="";
$js="";
if (isset($func) and $func == "sOrder")
{
 unset($func);
 if (isset($scaninput)) unset($scaninput);
 if (isset($hostordernum)) unset($hostordernum);
 if (isset($orderFound)) unset($orderFound);
}
if (!isset($B1)) $B1="";
if (!isset($toteId)) $toteId="";
if (!isset($scaninput)) $scaninput="";
if (!isset($func)) $func="enterOrd";
if ($func == "pickScanPart" and trim($partnumber) == "")
{
 $func="pickGoToBin";
}
if ($func == "pickGoToBin" and isset($binLocation))
{
 $binLocation=strtoupper($binLocation);
 // check for valid bin entered
 $req=array("action"=>"chkPartBin",
  "company"=>$comp,
  "shadow" => $shadow,
  "whseLoc"=>$binLocation
   );
 $ret=restSrv($RESTSRV,$req);
 $w=(json_decode($ret,true)); 
 $rc=0;
 if (isset($w["rc"])) $rc=$w["rc"];
 unset($w);
 if ($rc < 1) $binLocation=""; 
 if (trim($binLocation) == "")
 {
  $msg="Invalid Bin";
  $func="letsPick";
 }
 
 //echo "<pre>";
 //print_r($_REQUEST);
 //print_r($ret);
 //print_r($req);
 //exit;
} // end func = pickGoToBin
if ($opt[102] == "1" and $func == "letsPick" and isset($orderFound))
{
  $msg="";
  $order_num=$orderFound;
  $func="enterTote";
//echo "<pre>";
//print_r($_REQUEST);
//exit;
} // end opt 102, letspick and order found

switch ($func)
{
 case "enterOrd":
 {
  if (isset($scaninput) and $scaninput <> "")
  {
   //get Order by host Order num
   $req=array("action"=>"orderToPick",
  "company"=>$comp,
  "user_id" => $UserID,
  "host_order_num"=>$scaninput
   );
   $rc=restSrv($RESTSRV,$req);
   $ord1=(json_decode($rc,true)); 
   if (isset($ord1["errCode"]))
   {
    $msg=$ord1["errText"];
    $msgcolor="red";
    $extra_js="";
    $mainSection=enterOrder($msg,$msgcolor);
    $mainSection.=setPlaySound($playsound);
    $mainSection.=$extra_js;
   }  // end order not found
   else
   { // order found
    $ord=$ord1[1];
    $title="Order {$ord["host_order_num"]}";
    $color="green";
  
    $mainSection=letsPick($ord,"Lets Pick It");
   } // order found
  } // scaninput <> ""
  else
  { // no scaninput
   $extra_js="";
   $mainSection=enterOrder("","blue");
   $mainSection.=$extra_js;
  } // no scaninput
 break;
 } // end case enterOrd

 case "enterTote":
 {
    //get Order by host Order num
  $req=array("action"=>"orderToPick",
  "company"=>$comp,
  "user_id" => $UserID,
  "host_order_num"=>$hostordernum
   );
   $rc=restSrv($RESTSRV,$req);
   $ord1=(json_decode($rc,true));
   if (isset($ord1[1]))
   {
    $ord=$ord1[1];
   }
  if (!isset($toteId)) $toteId="";
  if ($toteId == "" or intval($toteId) < 1)
  {
      $gotTote=checkTote($ord);
      if ($gotTote <> "false" and intval($gotTote) < 1)
      { // asking
       $mainSection=$gotTote;
       break;
      } // asking
      else if ($gotTote <> "false") $toteId=$gotTote;
  } // ask tote initially
  if ($toteId <> "" and intval($toteId) > 0)
  { // check and update tote

  $req=array("action"=>"updTote",
        "company"=>$comp,
        "order_num" => $order_num,
        "host_order_num"=>$hostordernum,
        "tote_id"=>$toteId
         );
  $rc=restSrv($RESTSRV,$req);
  $toteInfo=(json_decode($rc,true));
  if (isset($toteInfo["errCode"]))
  { // error Code is Set
   $gotTote=checkTote($ord,$toteInfo["errText"]);
   if ($gotTote !== false)
   {
    $mainSection=$gotTote;
    break;
   } // end ask tote
  } // error Code is set
 } // end enterTote
  } // check and update tote

 case "letsPick":
 {
  //get Order by host Order num
  $req=array("action"=>"orderToPick",
  "company"=>$comp,
  "user_id" => $UserID,
  "host_order_num"=>$hostordernum
   );
   $rc=restSrv($RESTSRV,$req);
   $ord1=(json_decode($rc,true));
   if (isset($ord1[1]))
   {
    $ord=$ord1[1];
    $status=$ord["order_stat"];
   }
   else $status=9;
   if ($status == 2 and $B1 == "Lets Help Pick") $status=1;
   switch ($status)
   {
     case 0:
     case 1:
     case 2:
      { // good to go
       $req=array("action" => "fetchPickOrder",
    "company" => 1,
    "user_id" => $UserID,
    "order_num" => $ord["order_num"],
    "line_num" => 0,
    "zone" => ""
);
       $rc=restSrv($RESTSRV,$req);
       $line1=(json_decode($rc,true)); 
//echo "<pre>";
//print_r($req);
//print_r($line1);
       if (isset($line1[1]))
       {
        $line=$line1[1];
        //flag order as being picked
        $req=array("action" => "flagOrder",
    "company" => 1,
    "user_id" => $UserID,
    "order_num" => $ord["order_num"],
    "line_num" => $line["line_num"],
    "pull_num" => $line["pull_num"],
    "zone" => $line["whse_loc"]
);
       $rc=restSrv($RESTSRV,$req);
       $updrc=(json_decode($rc,true)); 
       $partInfo=chkPart(".{$line["shadow"]}",$comp);
       $otherLoc="";
       if (isset($partInfo["WhseLoc"]))
       {
        foreach ($partInfo["WhseLoc"] as $rec=>$loc)
        {
         if ($loc["whs_location"] <> $line["whse_loc"])
        {
         $otherLoc.=<<<HTML
  <input type="hidden" name="otherLoc[]" id="othLoc[]" value="{$loc["whs_location"]}|{$loc["whs_qty"]}">

HTML;
        }
      } // end foreach whseloc
     } // end isset WhseLoc
    if ($otherLoc == "")
    {
         $otherLoc.=<<<HTML
  <input type="hidden" name="otherLoc[]" id="othLoc[]" value="">

HTML;
    }
//echo "<pre>";
//print_r($line);
//print_r($partInfo);
//echo "</pre>";
        if (!isset($msg)) $msg="";    
        $mainSection=pickBin($msg,$ord,$line);
        $title="Order {$ord["host_order_num"]}";
       } // end count line1 < 1
       break;
      } // good to go
     case 200: // should be 2
      { // uh ooh, someone else may have picked up the order or the order is deleted
echo "<pre>Status: 200";
print_r($_REQUEST);
exit;
        //get Order by host Order num
        $req=array("action"=>"orderToPick",
       "company"=>$comp,
        "user_id" => $UserID,
       "host_order_num"=>$scaninput
        );
        $rc=restSrv($RESTSRV,$req);
        $ord1=(json_decode($rc,true));

echo "<pre> someone else picked up this order";
print_r($ord1);
exit;
       break;
      } // uh ooh, someone else may have picked up the order or the order is deleted
     case 3: // in packing aleady
      {
       $msg="Picking complete for Order {$hostordernum}, currently in Packing";
       $msg.=", Status:{$status}";
       $mainSection=reDirect($thisprogram,$nh,$msg);
       break;
      }
     case 4: // in shipping aleady
      {
       $msg="Picking complete for Order {$hostordernum}, currently in Packing";
       $msg.=", Status:{$status}";
       $mainSection=reDirect($thisprogram,$nh,$msg);
       break;
      }
     case 5:
     case 6:
     case 7: 
      { // order is complete
       $msg="Picking complete for Order {$hostordernum}";
       $msg.=", Status:{$status}";
       $mainSection=reDirect($thisprogram,$nh,$msg);
       break;
      } // order is complete
     default:
      { // uh ooh, order is no longer on file
       $msg="Can't find Order {$hostordernum}";
       $mainSection=reDirect($thisprogram,$nh,$msg);
       break;
      } // uh ooh, order is no longer on file
   } // end switch status
  break;
 } // end case letsPick

 case "pickGoToBin":
 {
//get pick line item, display screen for user to pick the part
// if qty > 1, let em enter it, but return submits 1, they may have to scan more parts
  //get Order by host Order num
  $req=array("action"=>"orderToPick",
  "company"=>$comp,
  "user_id" => $UserID,
  "host_order_num"=>$hostordernum
   );
   $rc=restSrv($RESTSRV,$req);
   $ord1=(json_decode($rc,true));
   if (isset($ord1[1]))
   {
    $ord=$ord1[1];
   }
   else
   {
    if (isset($ord1["errCode"]) and $ord1["errCode"] == 1)
    { // there are no more items in this zone to pick for this order
      $tmp=checkDrop($opt[103],$thisprogram,$nh,$orderNumber,$hostordernum);
      if (count($tmp) > 0)
       { // ned 2 drop
        foreach($tmp as $w=>$val) { $$w=$val; }
       } // ned 2 drop
    } // there are no more items in this zone to pick for this order
   else
    {
//echo "<pre>";
//print_r($_REQUEST);
//print_r($req);
//print_r($ord1);
    die("Error: order number {$hostordernum} not found at line:" .  __LINE__);
    }
   }

  if (!isset($lineNumber)) $lineNumber=0;
  if (!isset($pullnum)) $pullnum=0;
  $req=array("action"=>"fetchPickOrder",
  "company"=>$comp,
  "user_id" => $UserID,
  "order_num"=>$orderNumber,
  "line_num"=>$lineNumber,
  "pull_num"=>$pullnum
  );
  $rc=restSrv($RESTSRV,$req);
  $line1=(json_decode($rc,true));
//echo "<pre> line";
//print_r($req);
//print_r($line1);
//print_r($_REQUEST);
  if (isset($line1[1]))
   {
    $line=$line1[1];
    //check if bin entered is the same as itempull bin
    if (!isset($binLocation)) $binLocation ="";
    if ($binLocation <> "" and $bintoScan <> $binLocation)
    { // user is changing bin, update Itempull and allocation
     $req=array("action"=>"chgPickBin",
                "company"=>$comp,
                "user_id" => $UserID,
                "order_num"=>$orderNumber,
                "line_num"=>$lineNumber,
                "pull_num"=>$pullnum,
                "origBin"=>$bintoScan,
                "newBin"=>$binLocation,
                "shadow"=>$shadow,
                "uom"=>$uom,
                "qty"=>$qtytopick
     );
     $rc=restSrv($RESTSRV,$req);
     // check rc here, make sure it worked, 1 = OK
     $line["whse_loc"]=$binLocation;
    } // user is changing bin, update Itempull and allocation
    if (!isset($msg)) $msg="";
    $title="Order {$hostordernum}";
    $color="blue";
    $part="{$line["p_l"]} {$line["part_number"]}";
    $hiddens=<<<HTML
  <input type="hidden" name="func" id="func" value="pickScanPart">
  <input type="hidden" name="nh" value="{$nh}">
  <input type="hidden" name="hostordernum" id="hostordernum" value="{$hostordernum}">
  <input type="hidden" name="toteId" id="toteId" value="{$toteId}">
  <input type="hidden" name="bintoScan" id="bintoScan" value="{$line["whse_loc"]}">
  <input type="hidden" name="orderNumber" id="orderNumber" value="{$line["ord_num"]}">
  <input type="hidden" name="lineNumber" id="lineNumber" value="{$line["line_num"]}">
  <input type="hidden" name="pullnum" id="pullnum" value="{$line["pull_num"]}">
  <input type="hidden" name="shadow" id="shadow" value="{$line["shadow"]}">
  <input type="hidden" name="p_l" id="p_l" value="{$line["p_l"]}">
  <input type="hidden" name="part_number" id="part_number" value="{$line["part_number"]}">
  <input type="hidden" name="part_desc" id="part_desc" value="{$line["part_desc"]}">
  <input type="hidden" name="uom" id="uom" value="{$line["uom"]}">
  <input type="hidden" name="qtytopick" id="qtytopick" value="{$line["qtytopick"]}">
  <input type="hidden" name="qtypicked" id="qtypicked" value="{$line["qty_picked"]}">
HTML;
    $color="blue";
    $qty=$line["qtytopick"] - $line["qty_picked"];
    $fieldPrompt="Scan Part";
    $fieldPlaceHolder="Scan Part {$part}";
    $fieldId=" id=\"part_number\"";
    $fieldTitle=" title=\"at Bin: {$line["whse_loc"]}, Scan {$part}\"";
    $msg=<<<HTML
at Bin {$line["whse_loc"]}, Scan {$part}&nbsp;&nbsp;&nbsp; (qty {$qty} {$line["uom"]})
HTML;
    $msg2="";
    if ($qty > 1)
    {
     $msg2="<span class=\"w3-red\">Total Quantity is <strong>{$qty}</strong></span>";
    }
    if ($origMsg <> "")
    { 
      $a=$msg2;
      $msg2=$origMsg;
      $origMsg=$a;
      unset($a);
    }
    $data=array(
		"formName"=>"form1",
		"formAction"=>$thisprogram,
		"hiddens"=>$hiddens,
		"color"=>"w3-{$color}",
		"focusField"=>"partnumber",
                "msg"=>$msg,
                "msg2"=>$msg2,
		"partOnChange"=>"do_submit();",
		"partPrompt"=>$fieldPrompt,
		"partType"=>"text",
		"partField"=>"partnumber",
		"partValue"=>"",
		"partPlaceHolder"=>$fieldPlaceHolder,
		"partId"=>$fieldId,
		"partTitle"=>$fieldTitle,
		"qtyOnChange"=>"",
		"qtyPrompt"=>"Qty",
		"qtyField"=>"qty",
                "qtyType"=>"number",
		"qtyValue"=>1,
		"qtyPlaceHolder"=>"",
		"qtyFieldId"=>"qtyid",
		"qtyuom"=>"&nbsp;{$line["uom"]}",
		"qtyTitle"=>"Adjust Quantity",
		"bottomLeft"=>"at Bin: {$line["whse_loc"]}",
		"bottomCenter"=>"Scan {$part} (qty {$qty} {$line["uom"]})",
		"bottomRight"=>"",
		"toteId"=>$toteId,
                "origMsg"=>$origMsg,
                "function"=>""
    );
    $mainSection=frmtScreen($data,$thisprogram,"pickPart1");
    $msg="";
   } // end line[1] is set
   else
   {
    $tmp=checkDrop($opt[103],$thisprogram,$nh,$orderNumber,$hostordernum);
//echo "<pre>at 390";
//print_r($tmp);
//exit;
      if (count($tmp) > 0)
       { // ned 2 drop
        foreach($tmp as $w=>$val) { $$w=$val; }
       } // ned 2 drop
      else
       { // pick is done
        $msg="Order {$hostordernum} Complete";
        $mainSection=reDirect($thisprogram,$nh,$msg);
       } // pick is done
   }

   break;
 } // end case pickGoToBin
 case "pickScanPart":
 {
//get pick line item, display screen for user to pick the part
// if qty > 1, let em enter it, but return submits 1, they may have to scan more parts
  //get Order by host Order num
  $req=array("action"=>"fetchPickOrder",
  "company"=>$comp,
  "user_id" => $UserID,
  "order_num"=>$orderNumber,
  "line_num"=>$lineNumber,
  "pull_num"=>$pullnum
  );
  $rc=restSrv($RESTSRV,$req);
  $line1=(json_decode($rc,true));
  if (isset($line1[1]))
  {
    $line=$line1[1];
    $part=chkPart($partnumber,$comp);
    if ($part["numRows"] == 1)
    { // got the part check to make sure it's correct
     $partOK=false;
     $binOK=false;
     $qtyOk=false;
     $lineDone=false;
     if (!isset($part["Result"]["shadow_number"])) $entered_shadow=0;
     else $entered_shadow=$part["Result"]["shadow_number"];
     if ($entered_shadow <> $shadow)
     { // oops, wrong part
      $part="{$line["p_l"]} {$line["part_number"]}";
      $hiddens=<<<HTML
  <input type="hidden" name="func" id="func" value="pickScanPart">
  <input type="hidden" name="nh" value="{$nh}">
  <input type="hidden" name="hostordernum" id="hostordernum" value="{$hostordernum}">
  <input type="hidden" name="toteId" id="toteId" value="{$toteId}">
  <input type="hidden" name="bintoScan" id="bintoScan" value="{$bintoScan}">
  <input type="hidden" name="orderNumber" id="orderNumber" value="{$orderNumber}">
  <input type="hidden" name="lineNumber" id="lineNumber" value="{$lineNumber}">
  <input type="hidden" name="pullnum" id="pullnum" value="{$pullnum}">
  <input type="hidden" name="shadow" id="shadow" value="{$shadow}">
  <input type="hidden" name="p_l" id="p_l" value="{$p_l}">
  <input type="hidden" name="part_number" id="part_number" value="{$part_number}">
  <input type="hidden" name="part_desc" id="part_desc" value="{$part_desc}">
  <input type="hidden" name="uom" id="uom" value="{$uom}">
  <input type="hidden" name="qtytopick" id="qtytopick" value="{$qtytopick}">
  <input type="hidden" name="qtypicked" id="qtypicked" value="{$qtypicked}">

HTML;
    $color="red";
    $qty=$qtytopick - $qtypicked;
    $fieldPrompt="Scan Part";
    $fieldPlaceHolder="Scan Part {$part}";
    $fieldId=" id=\"part_number\"";
    $fieldTitle=" title=\"at Bin: {$bintoScan}, Scan {$part}\"";
    $msg=<<<HTML
at Bin {$line["whse_loc"]}, Scan {$part}&nbsp;&nbsp;&nbsp; (qty {$qty} {$uom})
HTML;
    $msg2="Wrong Part, you Entered {$partnumber}, Need {$part_number}";
    if ( $entered_shadow == 0) $msg2="Invalid Part, Need {$part_number}";
    
    if ($qty > 1)
    {
     $msg2.="<span class=\"w3-red\">Total Quantity is <strong>{$qty}</strong></span>";
    }
    if ($origMsg <> "")
    {
      $a=$msg2;
      $msg2=$origMsg;
      $origMsg=$a;
      unset($a);
    }
    $data=array(
                "formName"=>"form1",
                "formAction"=>$thisprogram,
                "hiddens"=>$hiddens,
                "color"=>"w3-{$color}",
                "focusField"=>"partnumber",
                "msg"=>$msg,
                "msg2"=>$msg2,
                "partOnChange"=>"do_submit();",
                "partPrompt"=>$fieldPrompt,
                "partType"=>"text",
                "partField"=>"partnumber",
                "partValue"=>"",
                "partPlaceHolder"=>$fieldPlaceHolder,
                "partId"=>$fieldId,
                "partTitle"=>$fieldTitle,
                "qtyOnChange"=>"",
                "qtyPrompt"=>"Qty",
                "qtyField"=>"qty",
                "qtyType"=>"number",
                "qtyValue"=>1,
                "qtyPlaceHolder"=>"",
                "qtyFieldId"=>"qtyid",
                "qtyuom"=>"&nbsp;{$line["uom"]}",
                "qtyTitle"=>"Adjust Quantity",
                "bottomLeft"=>"at Bin: {$line["whse_loc"]}",
                "bottomCenter"=>"Scan {$part} (qty {$qty} {$line["uom"]})",
                "bottomRight"=>"",
		"toteId"=>$toteId,
                "origMsg"=>$origMsg,
                "function"=>""
    );
    $mainSection=frmtScreen($data,$thisprogram,"pickPart1");
    $msg="";
    break;
     } // oops, wrong part
     if ($part["Result"]["shadow_number"] == $line["shadow"])
      { // we have the correct part
  //echo "part is correct";
        $partOK=true;
        //check that this part is in this bin
        $binNum=0;
        foreach($part["WhseLoc"] as $rec=>$wl)
        {
         if ($wl["whs_location"] == $line["whse_loc"])
         {
          $binOK=true;
          $binNum=$rec;
          break;
         } //good bin
        } // end for each whseloc
//       if ($binNum > 0) echo ", good Bin"; else echo ", Incorrect Bin";
       //check that we have not more that the correct qty
       if ($qty > $line["qtytopick"])
       { // they picked to many
   //echo " OVERAGE, you picked to many, please re-scan and enter the correct qty";
        $msg="OVERAGE, only {$line["qtytopick"]} needed, picked {$qty},<br> please re-scan and enter the correct qty";

        $htm=<<<HTML
<!DOCTYPE html>
<html>
<!--reDirect1 -->
<body onload="document.form1.submit()">
<form name="form1" action="{$thisprogram}" method="get">
 <input type="hidden" name="func" id="func" value="pickGoToBin">
  <input type="hidden" name="nh" value="{$nh}">
  <input type="hidden" name="hostordernum" id="hostordernum" value="{$hostordernum}">
  <input type="hidden" name="toteId" id="toteId" value="{$toteId}">
  <input type="hidden" name="bintoScan" id="bintoScan" value="{$line["whse_loc"]}">
  <input type="hidden" name="orderNumber" id="orderNumber" value="{$line["ord_num"]}">
  <input type="hidden" name="lineNumber" id="lineNumber" value="{$line["line_num"]}">
  <input type="hidden" name="pullnum" id="pullnum" value="{$line["pull_num"]}">
  <input type="hidden" name="shadow" id="shadow" value="{$line["shadow"]}">
  <input type="hidden" name="p_l" id="p_l" value="{$line["p_l"]}">
  <input type="hidden" name="part_number" id="part_number" value="{$line["part_number"]}">
  <input type="hidden" name="part_desc" id="part_desc" value="{$line["part_desc"]}">
  <input type="hidden" name="uom" id="uom" value="{$line["uom"]}">
  <input type="hidden" name="qtytopick" id="qtytopick" value="{$line["qtytopick"]}">
  <input type="hidden" name="qtypicked" id="qtypicked" value="{$line["qty_picked"]}">
  <input type="hidden" name="msg" id="msg" value="{$msg2}">
</form>
</body>
</html>

HTML;
       echo $htm;
       exit;
       } // they picked to many
      else $qtyOK=true;
      //if ($qtyOK) echo ", Qty is Correct";
      $msg="";
      if ($qty < $line["qtytopick"])
       {
        $msg="Need " . ($line["qtytopick"] - ($line["qty_picked"] + $qty)) . " more from this Bin";
       }
       else $lineDone=true;
       //Update what we picked
       $req=array("action"=>"updPickQty",
  		"company"=>$comp,
  		"user_id" => $UserID,
  		"host_order_num"=>$hostordernum,
  		"bin"=>$bintoScan,
  		"order_num"=>$orderNumber,
  		"line_num"=>$lineNumber,
  		"pull_num"=>$pullnum,
  		"shadow"=>$shadow,
  		"qtyPicked"=>$qty,
  		"tote_id"=>$toteId,
  		"uom"=>$uom,
  		"p_l"=>$p_l,
  		"part_number"=>$part_number
   		);
       $rc=restSrv($RESTSRV,$req);
       $response=(json_decode($rc,true));
       if (!$lineDone)
       { // more to pick in this bin
                $htm=<<<HTML
<!DOCTYPE html>
<html>
<!--reDirect2 -->
<body onload="document.form1.submit()">
<form name="form1" action="{$thisprogram}" method="get">
 <input type="hidden" name="func" id="func" value="pickGoToBin">
  <input type="hidden" name="nh" value="{$nh}">
  <input type="hidden" name="hostordernum" id="hostordernum" value="{$hostordernum}">
  <input type="hidden" name="toteId" id="toteId" value="{$toteId}">
  <input type="hidden" name="bintoScan" id="bintoScan" value="{$line["whse_loc"]}">
  <input type="hidden" name="orderNumber" id="orderNumber" value="{$line["ord_num"]}">
  <input type="hidden" name="lineNumber" id="lineNumber" value="{$line["line_num"]}">
  <input type="hidden" name="pullnum" id="pullnum" value="{$line["pull_num"]}">
  <input type="hidden" name="shadow" id="shadow" value="{$line["shadow"]}">
  <input type="hidden" name="p_l" id="p_l" value="{$line["p_l"]}">
  <input type="hidden" name="part_number" id="part_number" value="{$line["part_number"]}">
  <input type="hidden" name="part_desc" id="part_desc" value="{$line["part_desc"]}">
  <input type="hidden" name="uom" id="uom" value="{$line["uom"]}">
  <input type="hidden" name="qtytopick" id="qtytopick" value="{$line["qtytopick"]}">
  <input type="hidden" name="qtypicked" id="qtypicked" value="{$line["qty_picked"]}">
  <input type="hidden" name="msg" id="msg" value="{$msg}">
</form>
</body>
</html>

HTML;
       echo $htm;
       exit;
       } // more to pick in this bin
      } // we have the correct part
     else
      { // not the correct part
//Add code to abandon line and redo the picking of this part, and log the error
//echo "not the correct part";
//exit;
      } // not the correct part
    } // got the part, check to make sure it's correct
    else if ($part["numRows"] > 1)
    { // uh ohh, the have more than 1 part
// whoah, looks like a dupe in ALTERNAT, and log the error
//echo "uh ooh, the have more than 1 part";
//exit;
    }  // uh ohh, the have more than 1 part
   else
    { // nof part
// invalid part do the same as not the correct part, and log the error
//echo "Invalid part";
//exit;
    } // nof part
//echo "<pre> request";
//print_r($_REQUEST);
//echo "line";
//print_r($line1);
//echo "part";
//print_r($part);
//exit;
   //then check if more parts to pick on this order
   $cur_loc=$line["whse_loc"];
   $req=array("action" => "fetchPickOrder",
    "company" => 1,
    "user_id" => $UserID,
    "order_num" => $orderNumber,
    "line_num" => 0,
    "zone" => ""
);
   $rc=restSrv($RESTSRV,$req);
   $next_line1=(json_decode($rc,true));
   if (isset($next_line1[1]))
    { // there is more to pick next_line[1] isset
     $next_line=$next_line1[1];
     $next_loc=$line["whse_loc"];
     if ($next_loc == $cur_loc)
     { //next part is in this bin goto pickGoToBin
        $htm=<<<HTML
<!DOCTYPE html>
<html>
<!--reDirect3 -->
<body onload="document.form1.submit()">
 <form name="form1" action="{$thisprogram}" method="get">
  <input type="hidden" name="func" id="func" value="pickGoToBin">
  <input type="hidden" name="nh" value="{$nh}">
  <input type="hidden" name="hostordernum" id="hostordernum" value="{$hostordernum}">
  <input type="hidden" name="toteId" id="toteId" value="{$toteId}">
  <input type="hidden" name="bintoScan" id="bintoScan" value="{$next_line["whse_loc"]}">
  <input type="hidden" name="orderNumber" id="orderNumber" value="{$next_line["ord_num"]}">
  <input type="hidden" name="lineNumber" id="lineNumber" value="{$next_line["line_num"]}">
  <input type="hidden" name="pullnum" id="pullnum" value="{$next_line["pull_num"]}">
  <input type="hidden" name="shadow" id="shadow" value="{$next_line["shadow"]}">
  <input type="hidden" name="p_l" id="p_l" value="{$next_line["p_l"]}">
  <input type="hidden" name="part_number" id="part_number" value="{$next_line["part_number"]}">
  <input type="hidden" name="part_desc" id="part_desc" value="{$next_line["part_desc"]}">
  <input type="hidden" name="uom" id="uom" value="{$next_line["uom"]}">
  <input type="hidden" name="qtytopick" id="qtytopick" value="{$next_line["qtytopick"]}">
  <input type="hidden" name="qtypicked" id="qtypicked" value="{$next_line["qty_picked"]}">
 </form>
HTML;
      echo $htm;
      exit;
     } //next part is in this bin goto pickGoToBin
    else
     { // goto letsPick different bin
   // if not, display complete screen, if option is on to drop, show drop screen
      $htm=<<<HTML
<!DOCTYPE html>
<html>
<!--reDirect4 -->
<body onload="document.form1.submit()">
<form name="form1" action="{$thisprogram}" method="get">
 <input type="hidden" name="func" value="letsPick">
 <input type="hidden" name="nh" value="{$nh}">
 <input type="hidden" name="orderNumber" id="orderNumber" value="{$line["ord_num"]}">
 <input type="hidden" name="hostordernum" id="hostordernum" value="{$hostordernum}">
  <input type="hidden" name="toteId" id="toteId" value="{$toteId}">
</form>

HTML;
      echo $htm;
      exit;
     } // goto letsPick different bin
    } // there is more to pick next_line[1] isset
  else
   { // order is complete, go to pickComplete
    $htm=<<<HTML
<!DOCTYPE html>
<html>
<!--reDirect5 -->
<body onload="document.form1.submit()">
<form name="form1" action="{$thisprogram}" method="get">
 <input type="hidden" name="func" value="pickComplete">
 <input type="hidden" name="nh" value="{$nh}">
 <input type="hidden" name="orderNumber" id="orderNumber" value="{$line["ord_num"]}">
 <input type="hidden" name="hostordernum" id="hostordernum" value="{$hostordernum}">
  <input type="hidden" name="toteId" id="toteId" value="{$toteId}">
</form>

HTML;

      echo $htm;
      exit;
   } // order is complete, go to pickComplete
  } // end isset(line1[1])
  break;
 } // end pickScanPart
 case "pickComplete":
 {
  //this is where you land after picking is complete
  $tmp=checkDrop($opt[103],$thisprogram,$nh,$orderNumber,$hostordernum);
  if (count($tmp) > 0) 
   { 
    foreach($tmp as $w=>$val) { $$w=$val; }
   }
  break;
 } // end pickComplete
 case "Drop":
 {
   //update drop zone info and reDirect
   $msg="Order {$hostordernum} Complete";
   $mainSection=pickComplete($thisprogram,$nh,$hostordernum);
   breal;
 } // end Drop
} // end switch func

//******************************************************

//Display Header
$pg=new displayRF;
$pg->viewport="1.0";
$pg->dispLogo=false;
if (isset($ord["host_order_num"])) $title="Order {$ord["host_order_num"]}";
if (isset($title)) $pg->title=$title;
if (isset($color)) $pg->color=$color; else $color="blue";
$ejs="";
if (isset($nh) and $nh > 0)
{
 $pg->noHeader=true; 
 $ejs=<<<HTML
<script>
 if ( window !== window.parent ) 
 {
  parent.document.getElementById('pageTitle').innerHTML="{$pg->title}";
 }
</script>

HTML;
}
else $nh=0;
$reshtm=<<<HTML
  document.form1.func.value=arg;
  document.form1.submit();

HTML;
if ($fPQ > 0) $reshtm="history.back();";

$pg->title=$title;
if ($color == "green") $color="#47d147";
if (!isset($fPQ)) $fPQ="0";
$js.=<<<HTML
{$ejs}
<script>
function do_reset(arg)
{
{$reshtm}
}

</script>
<style>
input[type=text], input[type=number], select, textarea {
width: 100%;
padding: 12px;
border: 1px solid #ccc;
border-radius: 4px;
resize: vertical;
color: black;
}

.toteDiv
{
 position: relative;
 top: 0px;
 float: right;
 right: 10px;
}

.myRed
{
color:#fff!important;
background-color:#ff7777!important
}
html,body,h1,h2,h3,h4,h5 {font-family: "Raleway", sans-serif}
.binbutton {
    background-color: #2196F3;
    border: none;
    border-radius: 8px;
    color: white;
    padding: 15px 32px;
    text-align: center;
    text-decoration: none;
    display: inline-block;
    font-size: 20px;
    margin: 4px 2px;
    cursor: pointer;
}
.binbutton:disabled {
    background-color: #dddddd;
}
.binbutton:enabled {
    background-color: {$color};
}
</style>
HTML;
$pg->jsh=$js;
if ($msg <> "") $pg->msg=$msg;
if (!isset($otherScripts)) $otherScripts="";
if (!isset($mainSection))
{
  $msg="MainSection not set";
  $mainSection=reDirect($thisprogram,$nh,$msg);
exit;
} // end mainSection not set
if ($msgcolor <> "")
{
 $pg->color=$msgcolor;
}
$pg->Display();
//echo "<textarea>{$mainSection}</textarea>";
//exit;
//Rest of page
$htm=<<<HTML
  {$mainSection}
  {$otherScripts}
 </body>
</html>

HTML;
echo $htm;
echo "<pre>";
print_r($_REQUEST);

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

function setPlaySound($playsound)
{
 $htm="";
 if ($playsound)
 {
   $htm=<<<HTML
<audio controls autoplay hidden>
  <source src="/Bluejay/sounds/psycho.wav" type="audio/wav">
</audio>
HTML;
 }
 return $htm;
} // end setPlaySound

function enterOrder($msg,$color)
{
 global $thisprogram;
 global $nh;
 global $toteId;
 $msghtm="";
 if (trim($msg) <> "") $msghtm=<<<HTML
  <input type="hidden" name="msg" id="msg" value="{$msg}">

HTML;
     $hiddens=<<<HTML
  {$msghtm}<input type="hidden" name="nh" value="{$nh}">
  <input type="hidden" name="toteId" id="toteId" value="{$toteId}">
HTML;
    $data=array("formName"=>"form1",
                "formAction"=>$thisprogram,
                "hiddens"=>$hiddens,
                "color"=>"w3-{$color}",
                "fieldValue"=>"",
                "msg"=>$msg,
                "msg2"=>"",
  );
  $htm=frmtScreen($data,$thisprogram,"enterOrd");
  return $htm;

} // end enterOrder
function letsPick($ord,$B1Prompt)
{
 global $thisprogram;
 global $title;
 global $nh;
 global $fPQ;
 global $toteId;
 $odate=date("m/d/Y",strtotime($ord["date_required"]));
 $hiddens=<<<HTML
      <input type="hidden" name="fPQ" value="{$fPQ}">
      <input type="hidden" name="toteId" id="toteId" value="{$toteId}">

HTML;
 $color="w3-blue";
 $data=array( "formAction"=>$thisprogram,
              "hiddens"=>$hiddens,
              "color"=>$color,
              "nh"=>$nh,
              "order_num"=>$ord["order_num"],
              "hostordernum"=>$ord["host_order_num"],
              "customer_id"=>$ord["customer_id"],
              "name"=>$ord["name"],
              "priority"=>$ord["priority"],
              "ship_via"=>$ord["ship_via"],
              "zones"=>$ord["zones"],
              "num_lines"=>$ord["num_lines"],
              "orderDate"=>$odate,
              "B1Prompt"=>$B1Prompt
 );
  $htm=frmtScreen($data,$thisprogram,$temPlate="dispOrder",$incFunction=false);
  return $htm;
} // end letsPick
function chkPart($pnum,$comp)
{
 global $main_ms;
 $ret=array();
 $ret["upc"]=$pnum;
 $ret["comp"]=$comp;
 $pr=new PARTS;
 $pnum=trim($pnum);
 $ret=$pr->chkPart($pnum,$comp);
 return $ret;
} // end chkPart

function reDirect($thisprogram,$nh,$msg)
{
 global $toteId;
 $htm=<<<HTML
<!DOCTYPE html>
<html>
<!--reDirect6 -->
<body onload="document.form1.submit()">
<form name="form1" action="{$thisprogram}" method="get">
 <input type="hidden" name="func" id="func" value="">
  <input type="hidden" name="nh" value="{$nh}">
  <input type="hidden" name="msg" id="msg" value="{$msg}">
  <input type="hidden" name="toteId" id="toteId" value="{$toteId}">
</form>
</body>
</html>

HTML;
 return $htm;
} // end reDirect

function checkDrop($opt,$thisprogram,$nh,$orderNumber,$hostordernum)
{
 global $toteId;
 $ret=array("msg"=>"","mainSection"=>"");
 if ($opt == "1")
  { //we need to drop
   $hiddens=<<<HTML
  <input type="hidden" name="func" id="func" value="Drop">
  <input type="hidden" name="nh" value="{$nh}">
  <input type="hidden" name="orderNumber" value="{$orderNumber}">
  <input type="hidden" name="hostordernum" value="{$hostordernum}">
  <input type="hidden" name="toteId" id="toteId" value="{$toteId}">
HTML;
   $fieldPrompt="Dropped at";
   $fieldPlaceHolder="Scan or Drop Zone";
   $fieldId=" id=\"dropzone\"";
   $fieldTitle=" title=\"Scan or Enter the Drop Zone\"";
   $extra_js="";
   $color="blue";

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
              "msg"=>"Drop Order {$hostordernum} in Drop Zone",
              "msg2"=>"Order is Complete",
              "function"=>""
    );
   $ret["mainSection"]=frmtScreen($data,$thisprogram);
   $ret["msg"]="Completed Picking for {$hostordernum}";
  }//we need to drop
 else
  { // redirect for next pick
   $ret["msg"]="Completed Picking for {$hostordernum}";
   $ret["mainSection"]=pickComplete($thisprogram,$nh,$hostordernum);
  } // redirect for next pick
 return $ret ;
} // end checkDrop

function pickComplete($thisprogram,$nh,$hostOrder)
{
 global $wmsAssets;
 $panelTitle="Order {$hostOrder} is Complete";
     $htm=<<<HTML
<!DOCTYPE html>
<html>
<head>
 <title>{$panelTitle}</title>
 <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
 <meta name="viewport" content="initial-scale=1.0, width=device-width, user-scalable=yes" />
 <script src="/jq/shortcut.js" type="text/javascript"></script>
 <script>
shortcut.add("return",function() {
  var rc=do_reset();
  return(rc);
});
 </script>


 <link rel="stylesheet" href="../Themes/Multipads/Style.css">

 <link rel="stylesheet" href="../assets/css/wms.css">
</head>
<body>
<!--Pick Complete -->
 <h3 class="FormHeaderFont" align="left">{$panelTitle}</h3>
 <br>
 <button class="binbutton" style="background-color: #A9F5BC; color: black;" id="B1" name="B1" onclick="do_reset();">Done</button>
<script>
function do_reset()
{
 document.location.href="{$thisprogram}?nh={$nh}";
}
</script>
</body>
</html>

HTML;
 echo $htm;
 exit;

} // end pickComplete
function checkTote($ord,$msg="")
{ // check/enter totes
 global $RESTSRV;
 global $nh;
 global $thisprogram;
 global $toteId;
   if (!isset($toteId)) $toteId="";
   $color="blue";
   if ($msg <> "") $color="red";
  // have to scan a tote ID if option is on to use totes
   if (isset($ord))
   {
    $order_num = $ord["order_num"];
    $comp=$ord["company"];
    //check if order has assigned tote
    $req=array("action"=>"chkOrdTote",
    "company"=>$comp,
    "order_num" => $order_num
     );
    $rc=restSrv($RESTSRV,$req);
    $toteInfo=(json_decode($rc,true));
    if ($toteInfo["numRows"] < 1)
    { // ask for tote
          $hiddens=<<<HTML
  <input type="hidden" name="nh" value="{$nh}">
  <input type="hidden" name="hostordernum" id="hostordernum" value="{$ord["host_order_num"]}">
  <input type="hidden" name="order_num" id="order_num" value="{$ord["order_num"]}">
  <input type="hidden" name="orderNumber" id="orderNumber" value="{$ord["order_num"]}">
  <input type="hidden" name="orderFound" id="orderFound" value="{$ord["order_num"]}">
  <input type="hidden" name="scaninput" value="{$ord["order_num"]}">
  <input type="hidden" name="B1" value="">

HTML;
     $data=array("formName"=>"form1",
           "formAction"=>$thisprogram,
           "hiddens"=>$hiddens,
           "color"=>"w3-{$color}",
           "fieldValue"=>$toteId,
           "msg"=>"Order Id {$ord["order_num"]}",
           "msg2"=>$msg
     );
     $mainSection=frmtScreen($data,$thisprogram,"enterTote");
    } // ask for tote
  } // end if isset ord
 $ret="";
 if (isset($mainSection)) $ret=$mainSection;
 if (isset($toteInfo["numRows"]) and $toteInfo["numRows"] > 0)
 {
  if (isset($toteInfo))
  foreach($toteInfo as $key=>$t)
  {  
   if ($key <> "numRows")
   $ret=$t["tote_id"];
  }
 } // end numRows isset and > 0
 if ($ret == "") $ret="false";
 return $ret;
} // end check/enter tote
function pickBin($msg,$ord,$line)
{
   global $nh;
   global $thisprogram;
   global $otherLoc;
   global $toteId;
   if (!isset($msg)) $msg="";    
   $hostordernum=$ord["host_order_num"];
   $title="Order {$ord["host_order_num"]}";
   $color="blue";
   $hiddens=<<<HTML
  <input type="hidden" name="func" id="func" value="pickGoToBin">
  <input type="hidden" name="nh" value="{$nh}">
  <input type="hidden" name="toteId" id="toteId" value="{$toteId}">
  <input type="hidden" name="hostordernum" id="hostordernum" value="{$hostordernum}">
  <input type="hidden" name="bintoScan" id="bintoScan" value="{$line["whse_loc"]}">
  <input type="hidden" name="orderNumber" id="orderNumber" value="{$line["ord_num"]}">
  <input type="hidden" name="lineNumber" id="lineNumber" value="{$line["line_num"]}">
  <input type="hidden" name="pullnum" id="pullnum" value="{$line["pull_num"]}">
  <input type="hidden" name="shadow" id="shadow" value="{$line["shadow"]}">
  <input type="hidden" name="p_l" id="p_l" value="{$line["p_l"]}">
  <input type="hidden" name="part_number" id="part_number" value="{$line["part_number"]}">
  <input type="hidden" name="part_desc" id="part_desc" value="{$line["part_desc"]}">
  <input type="hidden" name="uom" id="uom" value="{$line["uom"]}">
  <input type="hidden" name="qtytopick" id="qtytopick" value="{$line["qtytopick"]}">
  <input type="hidden" name="qtypicked" id="qtypicked" value="{$line["qty_picked"]}">
{$otherLoc}
HTML;
    
        $color="blue";
        $qty=$line["qtytopick"] - $line["qty_picked"];
        $fieldPrompt="Scan Bin";
        $fieldPlaceHolder="Scan Bin {$line["whse_loc"]} Label";
        $fieldId=" id=\"whse_loc\"";
        $fieldTitle="Go to Bin {$line["whse_loc"]} and Scan Bin Label";
        $msg=<<<HTML
{$line["p_l"]} {$line["part_number"]} {$line["part_desc"]} (qty {$qty})
<br>
Go to Bin {$line["whse_loc"]} and Scan Bin Label
HTML;

        $data=array("formName"=>"form1",
              "formAction"=>$thisprogram,
              "hiddens"=>$hiddens,
              "color"=>"w3-{$color}",
              "onChange"=>"",
              "fieldType"=>"text",
              "fieldValue"=>"",
              "fieldPrompt"=>$fieldPrompt,
              "fieldPlaceHolder"=>$fieldPlaceHolder,
              "fieldName"=>"binLocation",
              "fieldId"=>$fieldId,
              "fieldTitle"=>$fieldTitle,
              "Qty"=>$qty,
	      "toteId"=>$toteId,
              "msg"=>$msg,
              "msg2"=>"",
              "function"=>""
    );
         $msg="";
         $ret=frmtScreen($data,$thisprogram,"pickBin");
   return $ret;
} // end pickBin
?>
