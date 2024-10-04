<?php

foreach (array_keys($_REQUEST) as $w) { $$w=$_REQUEST[$w]; }
//error_reporting(0);

if (get_cfg_var('wmsdir') !== false) $wmsDir=get_cfg_var('wmsdir');
else { echo "<h1>WMS System is not Configured on this System</h1>"; exit; }

echo "1\n";
$setConfig=true;
echo "2\n";
require("{$wmsDir}/config.php");
echo "3\n";
unset($setConfig);

require_once("{$wmsInclude}/db_main.php");
require_once("{$wmsInclude}/get_option.php");
require_once("{$wmsDir}/labels/fpictic.php");

error_reporting(E_ALL);
$db=new WMS_DB;
$db1=new WMS_DB;
/* TODO
make a class out of this to handle all of it.
test multiple lines with ship_complete = "N"
test multiple lines with ship_complete = "Y" (should not release)

1) what orders are waiting in my zone
2) what orders are waiting for all zones (done)
3) what tote belongs to what order


add new table PICKLIST (use ORDTRACK)
zone,
whse_status, 0=waiting, 1=in process, 2=in Packing, 3=In shipping, 9=done
order#,
#lines

(may have to add ORDPACK table to store packing info) 
ORDPACK
pack_id,
order_num,
line_num,
qty_in_pack


pick screen would now scan for order_stat = 1
ordered by prio

Once released, 
*/

echo "<pre>";
$debug=false;

// Check for missing Ordque record for order stat < 1
$SQL=<<<SQL
insert into ORDQUE
select order_num,"REL",""
from ORDERS
where order_stat < 1
and order_num not in (select order_num from ORDQUE
where order_num = ORDERS.order_num)

SQL;
$rc=$db->Update($SQL);

$SQL=<<<SQL
select A.order_num,que_key,que_data,priority
from ORDQUE A,ORDERS B
where que_key = "REL"
and B.order_num = A.order_num
order by priority, B.order_num
SQL;

$ords=array();
$rc=$db->query($SQL);
$numrows=$db->num_rows();
$i=1;
while ($i <= $numrows)
 { // while i < numrows
  $db->next_record();
   if ($numrows and $db->Record)
   { // numrows and db rec
    foreach ($db->Record as $key=>$data)
    { // foreach db rec
     if (!is_numeric($key)) { $ords[$i]["$key"]=$data; }
    } // foreach db rec
   } // numrows and db rec
  $i++;
 } // while i < numrows
if (count($ords) > 0)
{ //count ords > 0
 foreach ($ords as $orders)
 { // foreach ords as orders
  $updOrder=false;
  $ord=$orders["order_num"];
  $orderZones="";
  echo "Processing Order {$ord}";
  $ship_complete="";
  $order_stat=-35;
  $SQL=<<<SQL
select ship_complete, order_stat, company
from ORDERS
where order_num = {$ord}
and order_stat < 2

SQL;
  $rc=$db->query($SQL);
  $numrows=$db->num_rows();
  $i=1;
  while ($i <= $numrows)
  { // while i < numrows
   $db->next_record();
     if ($numrows)
     { // if numrows
        $shipComplete=$db->f("ship_complete");
        $order_stat=$db->f("order_stat");
        $comp=$db->f("company");
     } // if numrows
     $i++;
   } // while i < numrows
  if ($order_stat == -35)
   { // stat = -35
    echo "Order Not Found";
    $SQL=<<<SQL
delete from ORDQUE
where order_num = {$ord}
SQL;
    $rc=$db1->Update($SQL);
   } // stat = -35
  if ($ship_complete == "Y")
  { // ship complete = Y
   // check all line items where we dont have enough inventory
   $cnt=9999;
   $SQL=<<<SQL
 select count(*) as cnt from ITEMS
 where ord_num = {$ord}
 and qty_avail < qty_ord
SQL;
   $rc=$db->query($SQL);
   $numrows=$db->num_rows();
   $i=1;
   while ($i <= $numrows)
   { // i < numrows
    $db->next_record();
     if ($numrows)
     { // if numrows
        $cnt=$db->f("cnt");
     } // if numrows
     $i++;
   } // while i < numrows
   if ($cnt == 9999) 
   { // cnt=9999
    echo "There are no Items for this Order";
    continue;
   } // cnt=9999
   if ($cnt > 0) 
   { // cnt > 0
    echo "Order is flagged 'Ship Complete' and there are non-fillble Items on this Order";
    $SQL=<<<SQL
    update ORDERS set order_stat = -1 
    where order_num = {$ord}
 
SQL;
   if ($debug) echo "{$SQL}\n";
   else $rc=$db->Update($SQL);
    continue;
   } // end cnt > 0
  } // end ship_complete
  $SQL=<<<SQL
select 
 ord_num,
 line_num,
 shadow,
 part_number,
 uom,
 qty_ord,
 qty_ship,
 qty_bo,
 A.qty_avail,
 min_ship_qty,
 case_qty,
 inv_code,
 line_status,
 zone,
 whse_loc,
 qty_in_primary,
 item_pulls,
 inv_comp
from ITEMS,WHSEQTY A
where ord_num = {$ord}
and ms_shadow = shadow
and ms_company = {$comp}

SQL;
  $items=$db->gData($SQL);

  if (isset($items[1]))
  { // isset items[1]
   foreach ($items as $rec=>$item)
   { // foreach items 
    $shipSoFar=0;
    $process=0;
    $process=0;
    $qtyord=$item["qty_ord"];
    $qtyship=$item["qty_ship"];
 // add option to process if we have some but not all
    // if ($item["qty_avail"] >= ($qtyord - $qtyship))
echo "\nPart#: {$item["part_number"]} Avail: {$item["qty_avail"]}\n";
    if (!is_null($item["qty_avail"]))
    { // start avail > qty to ship
     $process=1;
     $shadow=$item["shadow"];
     $SQL=<<<SQL
 select 
primary_bin,
qty_avail,
qty_alloc,
qty_putaway,
qty_overstk,
qty_on_order,
qty_on_vendbo,
qty_on_custbo,
qty_defect,
qty_core
from WHSEQTY
where ms_shadow = {$shadow}
and ms_company = {$comp}

SQL;
    $qty=$db->gData($SQL);
    $SQL=<<<SQL
 select 
whs_location,
whs_code,
whs_code,
whs_qty,
whs_uom,
wb_zone
from WHSELOC,WHSEBINS
where whs_shadow = {$shadow}
and whs_company = {$comp}
and wb_company = whs_company
and wb_location = whs_location
order by whs_code desc, whs_location

SQL;
    $bins=$db->gData($SQL);
    $SQL=<<<SQL
select
uom,
uom_qty
from PARTUOM
where shadow = {$shadow}
and company = {$comp}

SQL;
    $uoms=$db->gData($SQL);

    $avail=$qty[1]["qty_avail"];
    $pbin=$qty[1]["primary_bin"];
    $ovstqty=0;
    if (count($bins) > 0)
     foreach ($bins as $key=>$bin)
     { // foreach bins
      if ($bin["whs_code"] == "P")
      { //get primary bin info
        foreach($bin as $key1=>$b)
         { // foreach bin
          $f=$key1;
          if ($f == "whs_qty") $f="primary_qty";
          if ($f == "whs_location") $f="location";
          if ($f == "whs_uom") $f="uom";
          if ($f == "wb_zone") $f="zone";
          $$f=$b;
         } // end foreach bin
      } //get primary bin info
      if ($bin["whs_code"] == "O") $ovstqty=$ovstqty + $bin["whs_qty"];
     } // end foreach bins
    $orderedBins=arrageBinArray($bins);
    $shipSoFar=0;
    $itempull=array();
    $itemship=array();
    $itemship["ord_num"]=$item["ord_num"];
    $itemship["line_num"]=$item["line_num"];
    $itempulls=1;
    $adjustInv=array();
    
    $leftToFill=(($qtyord - $qtyship) - $shipSoFar);
if ($debug) echo "246 leftToFill {$leftToFill}\n";
    //check primary bin to see if we can fill it from there
    if ( $leftToFill > 0 and $primary_qty >= $leftToFill)
     { // fill from primary
       $process=2; // no need to go further
if ($debug) echo "primary qty={$orderedBins["primQty"]}\n";
       if ($orderedBins["primQty"] >= $leftToFill) $fill=$leftToFill;
        else $fill=$orderedBins["primQty"];
       $shipSoFar=$shipSoFar + $fill;
       $itempull[$itempulls]=initItemPull($item,$itempulls,$comp);
       $itempull[$itempulls]["zone"]=$zone;
       $orderZones=addZone($orderZones,$zone);
       $itempull[$itempulls]["whse_loc"]=$location;
       $itempull[$itempulls]["qtytopick"]=$fill;
       $itempull[$itempulls]["qty_avail"]=$item["qty_avail"];
       $itempull[$itempulls]["uom_picked"]=$uom;
       $qtyship=$fill;
       $leftToFill=$leftToFill - $fill;
if ($debug) echo "132 leftToFill {$leftToFill}\n";
if ($debug) echo "P1 filling {$fill} from primary bin, {$leftToFill} to go\n";
       $itempulls++;
     } // end fill from primary

     //if ( $leftToFill > 0 and $avail >= $leftToFill)
     if ( $leftToFill > 0 )
      { // not enough in primary, lets try primary and overstock
        $totQty=0;
        if ( $orderedBins["primQty"] > 0)
         { // pull as much as possible from primary bin
if ($debug) echo "primary qty={$orderedBins["primQty"]}\n";
          if ($orderedBins["primQty"] >= $leftToFill) $fill=$leftToFill;
          else $fill=$orderedBins["primQty"];
          $leftToFill=$leftToFill - $fill;
          $itempull[$itempulls]=initItemPull($item,$itempulls,$comp);
          $itempull[$itempulls]["zone"]      =$orderedBins["P"]["wb_zone"];
          $orderZones=addZone($orderZones,$orderedBins["P"]["wb_zone"]);
          $itempull[$itempulls]["whse_loc"]  =$orderedBins["P"]["whs_location"];
          $itempull[$itempulls]["qtytopick"] =$fill;
       $itempull[$itempulls]["qty_avail"]=$item["qty_avail"];
          $itempull[$itempulls]["uom_picked"]=$orderedBins["P"]["whs_uom"];
          $totQty=$totQty + $fill;
          $shipSoFar=$shipSoFar + $fill;
if ($debug) echo "152 leftToFill {$leftToFill}\n";
          $qtyship=$shipSoFar;
if ($debug) echo "P2 filling {$fill} from primary bin, Shipped so Far={$shipSoFar}, {$leftToFill} to go\n";
          $itempulls++;
          if ($qtyord <= $shipSoFar) $process=2; // no need to go further
         } // end pull as much as possible from primary bin
if ($debug) echo "Shipped so Far={$shipSoFar}, {$leftToFill} to go, {$orderedBins["ovstQty"]} in Overstock \n";
        if ( $leftToFill > 0 and $orderedBins["ovstQty"] > 0)
         { // loop thru overstock to pull rest
           //loop 1, check to see if 1 overstock bin has enough
           foreach ($orderedBins["O"] as $ovnum=>$o)
           { // loop 1
            if ($leftToFill > 0 and $o["whs_qty"] > 0)
             { // found it, fill it and end
              if ($o["whs_qty"] >= $leftToFill) $fill=$leftToFill;
              else $fill=$o["whs_qty"];
              $leftToFill=$leftToFill - $fill;
              $shipSoFar=$shipSoFar + $fill;
              $itempull[$itempulls]=initItemPull($item,$itempulls,$comp);
              $itempull[$itempulls]["zone"]      =$o["wb_zone"];
              $orderZones=addZone($orderZones,$o["wb_zone"]);
              $itempull[$itempulls]["whse_loc"]  =$o["whs_location"];
              $itempull[$itempulls]["qtytopick"] =$fill;
       $itempull[$itempulls]["qty_avail"]=$item["qty_avail"];
              $itempull[$itempulls]["uom_picked"]=$o["whs_uom"];
              $totQty=$totQty + $fill;
              $qtyship=$shipSoFar;
if ($debug) echo "177 leftToFill {$leftToFill}\n";
if ($debug) echo " o1 filling {$fill} from Overstock bin {$o["whs_location"]}, {$leftToFill} to go\n";
              $itempulls++;
              if ($qtyord <= $shipSoFar) $process=2; // no need to go further
             } // end found it, fill it and end
            else 
             { // fill any avail and keep going
               if ($leftToFill < $o["whs_qty"])
                { // there is enough to fill the rest in this bin
                  $fill=$leftToFill;
                } // end there is enough to fill the rest in this bin
               else
                { // not enough, fill what we can
                  $fill=$o["whs_qty"];
                } // end not enough, fill what we can
              //if ($fill > 0)
              if ($fill >= 0)
               { // if fill > 0
                $shipSoFar=$shipSoFar + $fill;
                $itempull[$itempulls]=initItemPull($item,$itempulls,$comp);
                $itempull[$itempulls]["zone"]      =$o["wb_zone"];
                $orderZones=addZone($orderZones,$o["wb_zone"]);
                $itempull[$itempulls]["whse_loc"]  =$o["whs_location"];
                $itempull[$itempulls]["qtytopick"] =$fill;
       $itempull[$itempulls]["qty_avail"]=$item["qty_avail"];
                $itempull[$itempulls]["uom_picked"]=$o["whs_uom"];
                $totQty=$totQty + $fill;
                $qtyship=$shipSoFar;
                if ($qtyord <= $shipSoFar) $process=2; // no need to go further
                $leftToFill=$leftToFill - $fill;
if ($debug) echo "203 leftToFill {$leftToFill}\n";
                $itempulls++;
               } // fill > 0
             } // fill any avail and keep going
           } // end loop 1
         } // end loop thru overstock to pull rest
      } // end not enough in primary, lets try ordinary and overstock

     // still not filled, lets try partial ship
     // but need order header field
     if ($leftToFill > 0 and $ship_complete <> "Y")
     { // lets ship partial
if ($debug) echo "ship complete section";
        $a=1;
     }  // lets ship partial
    if ($leftToFill > 0)
     { // lefttofill > 0
      if ($debug) echo "cant fillit, set item status to 1 (awaiting product)";
exit;
     } // lefttofill > 0
   } // end avail > qty to ship
   $itemship["qtyship"]=$shipSoFar;
   if (isset($itempull[1])) // at least 1 item pull, update the records
   { // isset itempull[1]
     $itemship["zone"]=$itempull[1]["zone"];
     $orderZones=addZone($orderZones,$itempull[1]["zone"]);
     $itemship["whse_loc"]=$itempull[1]["whse_loc"];
     $itemship["item_pulls"]=count($itempull);
     if (isset($k)) unset($k);
     if (isset($SQL)) unset($SQL);
     foreach ($itempull as $k=>$ip)
     { // foreach itempull
      if ($ip["qty_avail"] < $ip["qtytopick"])
      {
       $ip["qtytopick"]=$ip["qty_avail"];
       if ($ip["qtytopick"] < 0) $ip["qtytopick"]=0;
       $itemship["qtyship"]=$ip["qtytopick"];
       $qtyship=$ip["qtytopick"];
       $shipSoFar=$ip["qtytopick"];
      }
      $SQL=array();
      $SQL[$k]=<<<SQL
insert into ITEMPULL
( ord_num, line_num, pull_num, user_id, company, shadow, zone, whse_loc, qtytopick, qty_picked, uom_picked)
values (
SQL;
      $i=0;
      foreach($ip as $fld=>$val)
      { // foreach ip
       if ($fld <> "qty_avail")
       {
        $c=",";
        if ($i == 0) $c=""; 
        $q="";
        if ($fld == "zone" or $fld == "whse_loc" or $fld == "uom_picked") $q='"';
        $SQL[$k].="{$c}{$q}{$val}{$q}";
        $i++;
       } // not qty_avail
      } // end foreach ip
      $SQL[$k].=<<<SQL
)
ON DUPLICATE key update
qtytopick = {$ip["qtytopick"]},
qty_picked = {$ip["qty_picked"]}

SQL;
     } // end foreach itempull 
    $i=$k;
  $updOrder=true;
  if ($itemship["qtyship"] <> 0)
  {
    $k++;
  echo "settiing updOrder 394";
  $SQL[$k]=<<<SQL
 update ITEMS
  set qty_ship={$itemship["qtyship"]},
      zone="{$itemship["zone"]}",
      whse_loc="{$itemship["whse_loc"]}",
      item_pulls={$itemship["item_pulls"]}
where ord_num = {$itemship["ord_num"]}
and line_num = {$itemship["line_num"]}

SQL;
  } // qtyship <> 0
   } // isset itempull[1]
    if (!isset($k)) $k=0;
    $k++;
    $SQL[$k]=<<<SQL
update ORDERS set order_stat = 1, zones="{$orderZones}" where order_num = {$ord}

SQL;
    if (isset($SQL) and is_array($SQL) and count($SQL) > 0) foreach ($SQL as $k=>$usql)
     { // foreach SQL
      if ($debug) echo "{$usql}\n";
      else $rc=$db->Update($usql);
      echo "SQL# {$k}, rc={$rc}\n";
     } // end foreach SQL
    
    if ($debug) 
    { // if debug
     echo "qtyord       = {$qtyord}\n";
     echo "qtyship      = {$qtyship}\n";
     if (isset($shipSoFar))
     { // shipSoFar
      echo "shipSoFar    = {$shipSoFar}\n";
      if (isset($uom)) echo "uom          = {$uom}\n";
      if (isset($avail)) echo "avail        = {$avail}\n";
      if (isset($pbin)) echo "pbin         = {$pbin}\n";
      if (isset($location)) echo "location     = {$location}\n";
      if (isset($whs_code)) echo "whs_code     = {$whs_code}\n";
      if (isset($primary_qty)) echo "primary_qty  = {$primary_qty}\n";
      if (isset($uom)) echo "uom          = {$uom}\n";
      if (isset($zone)) echo "zone         = {$zone}\n";
      if (isset($ovstqty)) echo "ovstqty      = {$ovstqty}\n";
     } // end shipSoFar is set

     //echo "Items ";
     //print_r($items);
     if (isset($qty))
     { // qty is set
      echo "Qty ";
      print_r($qty);
      echo "Bins ";
      print_r($bins);
      echo "UOMS ";
      print_r($uoms);
      echo "ITEMPULL ";
      print_r($itempull);
      echo "ITEMSHIP ";
      print_r($itemship);
     } // qty is set
    } // end debug
   } // end foreach item
  } // end items[1] is set
  $msg=" Awaiting Product\n";  
  if ($updOrder) 
  {
   $msg=" Released to Pick\n";
   $uSQL=<<<SQL
 update ORDQUE
 set que_key="PIC"
 where order_num = {$ord}
SQL;
   
   if ($debug) echo "{$uSQL}\n";
   else 
    {
     $rc=$db->Update($uSQL);
     //get Pictic device
     $opt=get_option($db,$comp,9501);
     // get printer pathname
     // hard coding it to test
     $lpt="/usr1/client/outfile.sh";
     //$output=picTic($ord);
     //$cmd="echo {$output} | {$lpt}";
     //$result=exec($cmd);
     //echo "{$result}\n";
    }
  } // end updOrder
 echo $msg;
 } // end foreach ords
} // end count ords > 0
else echo "No Orders to Release";


function initItemPull($item,$num,$comp)
{ 
 $ret=array();
 $ret["ord_num"]=$item["ord_num"];
 $ret["line_num"]=$item["line_num"];
 $ret["pull_num"]=$num;
 $ret["user_id"]=0;
 $ret["company"]=$comp;
 $ret["shadow"]=$item["shadow"];
 $ret["zone"]=" ";
 $ret["whse_loc"]=" ";
 $ret["qtytopick"]=0;
 $ret["qty_picked"]=0;
 $ret["uom_picked"]=$item["uom"];
 return $ret;
} // end initItemPull
function arrageBinArray($in)
{
 $ret=array();
 $ret["P"]=array();
 $ret["O"]=array();
 $ret["numOvst"]=0;
 $ret["numMove"]=0;
 $ret["primQty"]=0;
 $ret["ovstQty"]=0;
 $ret["totQty"]=0;
 $numOvst=0;
 $numMove=0;
 foreach ($in as $key=>$bin)
 {
  if ($bin["whs_code"] == "P") 
   {
    $ret["P"]=$bin;
    $ret["totQty"]=$ret["totQty"] + $bin["whs_qty"];
    $ret["primQty"]=$bin["whs_qty"];
   } // end type P
  if ($bin["whs_code"] == "O")
   {
    $numOvst++;
    $ret["O"][$numOvst]=$bin;
    $ret["totQty"]=$ret["totQty"] + $bin["whs_qty"];
    $ret["ovstQty"]=$ret["ovstQty"] + $bin["whs_qty"];
   } // end type O
  if ($bin["whs_code"] == "M")
   {
    $numMove++;
    $ret["M"][$numMove]=$bin;
    // qty is not pickable
   } // end type O
 } // end foreach in
 $ret["numOvst"]=$numOvst;
 $ret["numMove"]=$numMove;
 return($ret);
} // end arrageBinArray

function addZone($orderZones,$zone)
{
       $zone=trim($zone);
       if (strpos(trim($orderZones),$zone) === false)
       {
        $comma="";
        if (strlen($orderZones) > 0) $comma=",";
        $orderZones.="{$comma}{$zone}";
       }

 return $orderZones;
}
?>
