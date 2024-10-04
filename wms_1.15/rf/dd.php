<?php
 case "letsPick":
 {
  //get Order by host Order num
  //$ord1=ordToPick($comp,$UserID,$order);
  $ord1=$ORDER;
  //$req=array("action"=>"orderToPick",
  //"company"=>$comp,
  //"user_id" => $UserID,
  //"host_order_num"=>$hostordernum,
  //"case"=>"letsPick"
   //);
   //$rc=restSrv($RESTSRV,$req);
   //$ord1=(json_decode($rc,true));

//if ( 1 == 2 )
//{
   if (isset($ORDER["curLine"]))
   {
    $j=$ORDER["curLine"];
    $j1=$ORDER["items"][$j]["slot"];
    $status=abs($ORDER[$j1]["order_stat"]);
   }
   else $status=9;

   if ($status == 2 and $B1 == "Help Pick") $status=1;
   switch ($status)
   {
     case 0:
     case 1:
     case 2:
      { // good to go
       if (isset($skipTo) and $skipTo > 0) $ln=$skipTo; else $ln=0;

       $allLines="";

       $j=1;
       if (isset($skipTo) and $skipTo > 0) $j=2;
       //if (count($line1) > 0) $allLines=displayItems($line1,$j);
       $line1=array();
       if (count($ord1) > 0) $allLines=displayItems($ord1,$j);
/*
       if (isset($skipTo) and $skipTo > 0) 
       {
        $req["line_num"]=0;
        $rc=restSrv($RESTSRV,$req);
        $j=count($line1);
        $line1[$j + 1]=$line1[1];
        $line1[1]=$line1[$skipTo];
        unset($line1["skipTo"]);
        $line1=(json_decode($rc,true)); 
       }
echo "<pre>";
print_r($line1);
*/

 $lln=1;
 if (isset($skipTo)) $lln=$skipTo;
       if(isset($_SESSION["wms"]["Pick"])
       and count($_SESSION["wms"]["Pick"]) > 0
       and isset($line1[$lln]["zero_picked"]))
       {
        $found=false;
        foreach($_SESSION["wms"]["Pick"] as $jj=>$pk)
        {
           //echo " zero={$pk["zeroed"]} ?= {$line1[1]["zero_picked"]}\n";
         if ($pk["line_num"] == $line1[$lln]["line_num"])
          {
           if($pk["zeroed"] > 0) $found="true";
          }
        }
//echo "<pre> found={$found} Sess";
//print_r($_SESSION["wms"]["Pick"]);
//echo "line1";
//print_r($line1);
//exit;
        if ($found) 
        {
         //unset($line1);

        if (!isset($cur_loc) and isset($binToScan)) $cur_loc=$binToScan;
        else $cur_loc="";
         $tmp1=chkIfMore($orderNumber,$orderNumber,$zones,$hostordernum,$toteId,$cur_loc);
         if ($tmp1 == false) $tmp=checkDrop($opt[103],$thisprogram,$nh,$orderNumber,$hostordernum);
         if (count($tmp) > 0)
       { // ned 2 drop
        foreach($tmp as $w=>$val) { $$w=$val; }
       } // ned 2 drop
         //$msg="Order {$hostordernum} Complete";
//echo"<pre>";
//print_r($tmp);
//exit;
         // set order stat to -2
         $req=array("action" => "setZeroStat",
           "company" => 1,
           "order_num" => $orderNumber);
         $rc=restSrv($RESTSRV,$req);
         break;
        }
       }

       $lln=1;
       if (isset($skipTo)) $lln=$skipTo;
       $lineCount=count($line1);
       if (isset($line1[$lln]))
       {
        $line=$line1[$lln];
        //flag order as being picked
        $req=array("action" => "flagOrder",
    "company" => 1,
    "user_id" => $UserID,
    "order_num" => $line["ord_num"],
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
        $title="Picking";
       } // end count line1 < 1
       break;
      } // good to go
     case 200: // should be 2
      { // uh ooh, someone else may have picked up the order or the order is deleted
echo "<pre>Status: 200";
print_r($_REQUEST);
 exit;
        //get Order by host Order num
        $ord1=ordToPick($comp,$UserID,$order);
        //$req=array("action"=>"orderToPick",
       //"company"=>$comp,
        //"user_id" => $UserID,
       //"host_order_num"=>$scaninput,
  //"case"=>"letsPick2"
        //);
        //$rc=restSrv($RESTSRV,$req);
        //$ord1=(json_decode($rc,true));

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
