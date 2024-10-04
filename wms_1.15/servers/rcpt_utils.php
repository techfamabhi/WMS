<?php

function sumPO($db,$ponum,$batchNum=0)
{
 $where="";
 if ($batchNum > 0) $where=" and batch_num = {$batchNum}\n";
$SQL=<<<SQL
select sum(qty_ord) as qOrd,
       sum(totalQty) as qRec,
       sum(scanQty) as cRec,
       sum(qty_stockd) as sQty,
       count(*) as lineCount
from POITEMS A,RCPT_SCAN B
where po_number = {$ponum}
{$where} and poi_po_num = po_number
and A.shadow = B.shadow
and scan_status < 1

SQL;

$ret=array();
$ret["qOrd"]=0;
$ret["qRec"]=0;
$ret["cRec"]=0;
$ret["lineCount"]=0;
$rc=$db->query($SQL);
  $numrows=$db->num_rows();
  $i=1;
  while ($i <= $numrows)
  {
   $db->next_record();
     if ($numrows and $db->Record)
     {
      foreach ($db->Record as $key=>$data)
       {
        if (!is_numeric($key)) { $ret["$key"]=$data; }
       }
     }
    $i++;
  } // while i < numrows
 return($ret);
}

function getRecpt($db,$rcpt)
{
 $ret=array();
 $ret["numRows"]=0;
 $SQL=<<<SQL
 select
RCPT_SCAN.batch_num,
host_po_num,
 po_number,
line_num,
PARTS.p_l,
PARTS.part_number,
PARTS.part_desc,
 pkgUOM,
 scan_upc,
 po_line_num,
 scan_status,
 scan_user,
 pack_id,
 D.shadow,
 partUOM,
 RCPT_SCAN.line_type,
 pkgQty,
 qty_ord,
 scanQty,
 totalQty,
 timesScanned,
 qty_recvd,
 recv_to
from RCPT_INWORK,RCPT_SCAN, PARTS, POHEADER, POITEMS D
where RCPT_INWORK.batch_num = {$rcpt}
and  RCPT_INWORK.batch_num = RCPT_SCAN.batch_num
and scan_status < 2
and shadow_number = RCPT_SCAN.shadow
and POHEADER.wms_po_num = po_number
and poi_po_num = po_number
and D.shadow = RCPT_SCAN.shadow

SQL;
 $rc=$db->query($SQL);
  $numrows=$db->num_rows();
  $i=1;
  while ($i <= $numrows)
  {
   $db->next_record();
     if ($numrows and $db->Record)
     {
      foreach ($db->Record as $key=>$data)
       {
        if (!is_numeric($key)) { $ret[$i]["$key"]=$data; }
       }
     }
    $i++;
  } // while i < numrows

 $ret["numRows"]=$numrows;
 return $ret ;

} // end getRecpt
?>
