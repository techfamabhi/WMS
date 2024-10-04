<?php
function updPoStat($db,$comp,$po,$stat)
{
 global $DEBUG;
 /*  0 = Open
 1 = On Dock (Send ON_DOCK message to Host)
 2 = In Process (Send LOCK message to Host)
 3 = In Putaway (Send PUTAWAY message to Host)
 4 = Updating
 5 = Received (send RECEIPT message to Host)
check if backorders are allowed, if not, update Qty Cancel in POITEMS

 6 = Received, Back Orders Exist (send OFF_DOCK to Host)
 7 = Done
if backorders exist, set status to -1 when all done
if not back orders exist, set status to 7
If PO_DELETE is received from host, set status to 9
 */

   if (isset($comp) and $comp> 0
  and isset($po) and $po > 0
  and isset($stat) and $stat <> "")
  {
   $SQL=<<<SQL
update POHEADER
set po_status = {$stat}
where company = {$comp}
and wms_po_num = {$po}

SQL;
  if ($DEBUG) wr_log("/tmp/PO_srv.log",$SQL);
  $rc=$db->Update($SQL);
  }
 return($rc);
} // end updPoStat

function poOpenLines($db,$POs,$rtype=1,$overage=false)
{
 // rtype=0 return data, 1 = return just the count
 if (is_array($POs))
 { // its an array
  $po="in (";
  $comma="";
  $beenHere=false;
  foreach($POs as $p)
  {
   if ($beenHere) $comma=',';
   $po.="{$comma}{$p}";
   $beenHere=true;
  } // end foreach POs
  $po.=")\n";
 } // its an array
 else
 { // not an array
  $po="= {$POs}\n";
 } // not an array

 $SQL=<<<SQL
select poi_po_num,
poi_line_num,
shadow,
p_l,
part_number,
part_desc,
uom,
qty_ord,
qty_recvd,
qty_bo,
qty_cancel,
poi_status,
line_type
from POITEMS
where poi_po_num {$po}
order by poi_po_num,poi_line_num

SQL;

$d=$db->gData($SQL);
$ret=array();
if ($db->NumRows > 0)
{
 foreach ($d as $key=>$data)
 {
  $SQL=<<<SQL
select IFNULL(sum(totalQty),0) as totalQty,
       IFNULL(sum(qty_stockd),0) as qtyStocked
from RCPT_SCAN
where po_number {$po}
and shadow = {$data["shadow"]}
and scan_status < 2

SQL;
// po line currently not set in RCPT_SCAN
// and po_line_num = {$data["poi_line_num"]}

  $rc=$db->query($SQL);
  $numrows=$db->num_rows();
  $i=1;
  while ($i <= $numrows)
  {
   $db->next_record();
     if ($numrows)
     {
        $totalQty=$db->f("totalQty");
        $qtyStocked=$db->f("qtyStocked");
     }
     $i++;
   } // while i < numrows

  $open=($data["qty_ord"] - ($totalQty + $data["qty_recvd"]));
  $ok=false;
  if ($open <> 0) $ok=true;
  if (!$overage and $open < 0) $ok=false;
  if ($overage and $rtype == 2 and $open > 0) $ok=false;
  if ($ok)
  {
   $ret[$key]=$d[$key];
   $ret[$key]["totalOpen"]=$open;
   $ret[$key]["qtyStocked"]=$qtyStocked;
  } // end open <> 0
 } // end foreach d

} // end numrows > 0
  if ($rtype == 1) return array("numRows"=>count($ret));
  else return $ret;
  unset($ret);
} // end poOpenLines
?>
