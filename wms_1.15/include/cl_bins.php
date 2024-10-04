<?php

// cl_bins.php -- bin operations
// requires database set in $db and get_table
/* 
functions 

binLookup(bin,shadow)
if bin is set and no shadow number set, 
 looks for bin in WHSEBINS, first
 if not found, it looks in WHSEPACKS (mobile bins like totes, carts, pallets, etc)
 if found, retrieves all info for that bin 
 if shadow is set, returns info for that shadow and bin
 if shadow is set and bin is not, returns all bins and info for that shadow

updQtys(bin,shadow,qty,uom,chgQty)
 adds qty of uom to bin or pack
 if bin, it adds the qty to the bin and writes audit record, returns audit
 if pack, adds qty to pack, 
 if chgQty, updates the WHSEQTY qty_avail, core or defect inventory

*/

class BIN
{
 var $Company; // must be set
 var $User; // must be set

 var $numRows;
 var $binInfo;
 var $binQty;
 var $db;

 function init($db)
 {
  $this->db=$db;
 } // end function init
 function lookUp($bin,$shadow=0)
 {
  $ret=array();
  $j="00";
  $SQL="";
  if (!empty($bin)) $j="10";
  if (!empty($shadow) and $shadow > 0) $j=substr($j,0,1) . "1";
  switch ($j)
  {
   case "10": // bin is set, but not shadow, get bin info
    $SQL=<<<SQL
select * from WHSEBINS 
where wb_company = {$this->Company}
and wb_location = "{$bin}"

SQL;
    $tmp=$this->gData($SQL);
    $this->binInfo=$tmp;
    $SQL=<<<SQL
select * from WHSELOC
where whs_company = {$this->Company}
and whs_location = "{$bin}"

SQL;
    $tmp=$this->gData($SQL);
    $this->binQty=$tmp;
    break;
   case "01": // shadow is set, but not bin, get part bin info
    break;
   case "11": // bin and shadow is set, get part/bin info
    break;
   default: // nothing set, return -35
    return "" ;
    break;
  } // end switch $j
 } // end function lookUp

 function gData($SQL)
 {
  global $db;
  $tmp=array();
  $ret=array();
  $this->numRows=0;
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
        if (!is_numeric($key)) { $tmp[$i]["$key"]=$data; }
       }
     }
    $i++;
  } // while i < numrows
  if ($numrows == 1) $ret=$tmp[1]; else $ret=$tmp;
  $this->numRows=$numrows;
 return $ret ;
 } // end gData

function chkPart($pnum,$comp = 0)
{
 if ($comp == 0) $comp=$this->Company;
 $ret=array();
 $ret["upc"]=$pnum;
 $ret["comp"]=$comp;
 $pr=new PARTS;
 $pnum=trim($pnum);
 $a=$pr->lookup($pnum);
 if (count($a) == 1) $ret=$pr->Load($a[1]["shadow_number"],$comp);
 $ret["status"]=$pr->status;
 $ret["numRows"]=count($pr->status);
 if ($pr->status > 1)
 {
  $ret=$a;
  $ret["numRows"]=$pr->status;
  $ret["status"]=$pr->status;
 }
 else
 {
  $ret["Result"]=$a[1];
  $ret["Part"]=$pr->Data;
  $ret["ProdLine"]=$pr->ProdLine;
  $ret["WhseQty"]=$pr->WHSEQTY;
  $ret["Alternates"]=$pr->Alternates;
 }
 unset($pr);
 return $ret ;
}

function get_part($pnum_in)
{
  $ret=array();
  $ret["status"]=0;
  $ret["num_rows"]=0;
  $i=0;
  $SQL=<<<SQL
SELECT alt_part_number,alt_type_code, alt_uom,
 shadow_number,
 p_l,
 part_number,
 unit_of_measure,
 shadow_number
 part_desc,
 part_long_desc, 
 part_seq_num,
 part_category,
 part_class
 part_subline,
 part_group,
 part_returnable, 
 serial_num_flag,
 special_instr,
 hazard_id,
 kit_flag,
 cost,
 core,
 core_group 
 FROM ALTERNAT,PARTS
 WHERE alt_part_number like "{$pnum_in}"
 AND  shadow_number = alt_shadow_num
SQL;
 
  $ret=$this->gData($SQL);
  $ret["num_rows"]=$this->numRows;
  if ($ret["num_rows"] == 0) { $ret["status"]=-35; }
  return $ret ;
} // end get_part

function get_whsqty($comp,$shadow)
{
 $ret=array();
 $SQL=<<<SQL
 select 
 primary_bin as whse_location,
 qty_avail,
 qty_alloc,
 qty_on_order,
 qty_on_vendbo,
 qty_on_custbo,
 qty_defect,
 qty_core
 from WHSEQTY
 where ms_shadow = $shadow
 and ms_company = $comp

SQL;
    $ret=$this->gData($SQL);
    $this->binInfo=$tmp;
    $numrows=$this->numRows;
return $ret ;
} // end get_whsqty

function getWhseLoc($comp,$bin,$shadow,$wild=0)
{
 $awhere="";
 if ($shadow > 0) $awhere="and whs_shadow = {$shadow}";
 $binSearch = "= \"{$bin}\"";
 if ($wild = 1) $binSearch = "like \"{$bin}%\"";
 if ($wild = 2) $binSearch = "like \"%{$bin}%\"";

 $SQL=<<<SQL
select * from WHSELOC
where whs_company = {$this->Company}
and whs_location = {$binSearch}
{$awhere}

SQL;
    $ret=$this->gData($SQL);
    $ret["numRows"]=$this->numRows;
    return $ret ;

} // end getWhseLoc

function getLoc($comp,$bin,$wild=0)
{
 //returns parts in location
 $awhere="";
 $binSearch = "= \"{$bin}\"";
 if ($wild == 1) $binSearch = "like \"{$bin}%\"";
 if ($wild == 2) $binSearch = "like \"%{$bin}%\"";

 $SQL=<<<SQL
select 
whs_location,
p_l,
part_number,
part_desc,
whs_code,
whs_qty,
whs_uom,
whs_shadow
from WHSELOC,PARTS
where whs_company = {$comp}
and whs_location {$binSearch}
and whs_shadow > 0
and shadow_number = whs_shadow

SQL;
//echo "<pre>{$SQL}\n";
    $ret=$this->gData($SQL);
    $numRows=$this->numRows;
    if ($numRows > 0)
    { // found 1 or more parts, get other locations for the part
     if ($numRows > 1)
     { // loop thru all parts and set otherBins
      foreach ($ret as $key=>$r)
      {
       $w=$this->getOtherBins($r);
       if (count($w) > 0) $ret[$key]["otherBins"]=$w;
      } // end foreach ret
     } // loop thru all parts and set otherBins
     else
     { // get other bins for the 1 record
      $w=$this->getOtherBins($ret);
      if (count($w) > 0) $ret["otherBins"]=$w;
     } // get other bins for the 1 record
    } // found 1 or more parts, get other locations for the part

    $ret["numRows"]=$numRows;
    return $ret ;
} // end getWhseLoc

function getOtherBins($r)
{
       $SQL=<<<SQL
select
whs_location,
whs_code,
whs_qty,
whs_uom
 from WHSELOC
 where whs_shadow = {$r["whs_shadow"]}
 and whs_location <> "{$r["whs_location"]}"

SQL;
      $ret=$this->gData($SQL);
      return $ret;
} // end getOtherBins

function getBinInfo($comp,$bin)
{
 //validates a bin and returns info
 $ret=array();
 $SQL=<<<SQL
 select * from WHSEBINS
 where wb_company = {$comp}
   and wb_location = "{$bin}"
 
SQL;
//echo "<pre>{$SQL}\n";
    $ret=$this->gData($SQL);
    $ret["numRows"]=$this->numRows;
    return $ret ;
} // end getBinInfo

function updWhseLoc($comp,$loc,$shadow,$code,$qty,$uom)
{
 $SQL=array();
 $nextSQL=0;
 $whsQty=$this->get_whsqty($comp,$shadow);
 $whsLoc=$this->getWhseLoc($comp,$loc,$shadow);
 $old_qty=0;
 if ($whsLoc["numRows"] < 1)
 { //insert new record
  $SQL[$nextSQL]=<<<SQL
insert into WHSELOC
(whs_company, whs_location, whs_shadow, whs_code, whs_qty, whs_uom,whs_alloc)
values ({$comp},"{$loc}",{$shadow},"{$code}",{$qty},"{$uom}",0)

SQL;
  $nextSQL++;
 } //insert new record
 else
 { // update the record
  $SQL[$nextSQL]=<<<SQL
update WHSELOC set qty = qty + {$qty}
where whs_company = {$comp}
  and whs_shadow = {$shadow}
  and whs_location = "{$loc}"

SQL;
  $nextSQL++;
 } // update the record

 $fld="qty_avail";
 $q1=$qty;
 $q2=0;
 $q3=0;
 if ($uom == "CR") 
 {
  $fld="qty_core";
  $q1=0;
  $q2=$qty;
  $q3=0;
 }
 if ($uom == "DE") 
 {
  $fld="qty_defect";
  $q1=0;
  $q2=0;
  $q3=$qty;
 }
 if ($whsQty["numRows"] < 1)
 { //insert new Qty record
  $SQL[$nextSQL]=<<<SQL
insert into WHSEQTY
(ms_shadow, ms_company, primary_bin, qty_avail, qty_alloc,
 qty_putaway, qty_overstk, qty_on_order, qty_on_vendbo, 
 qty_on_custbo, qty_defect, qty_core, max_shelf,
 minimum, maximum, cost, core)
values ({$shadow},{$comp},"{$loc}",{$q1},0,
0,0,0,0,
0,{$q3},{$q2},0,
0,0,0.00,0.00)

SQL;
  $nextSQL++;
 } //insert new Qty record
 else
 { // update existing Qty record
  $extra="";
  if (trim($whsQty[1]["primary_bin"]) == "") $extra=<<<SQL
, primary_bin = "{$loc}"
SQL;
  $SQL[$nextSQL]=<<<SQL
update WHSEQTY set {$fld} = {$fld} + {$qty}{$extra}
where ms_shadow = {$shadow} and ms_company = {$comp}

SQL;
  $nextSQL++;
 } // update existing Qty record
 
/*
create table WHSELOC (
 whs_company     smallint,
 whs_location    varchar(18),
 whs_shadow      int default 0,
 whs_code        char(2) default " ", -- P=Primary Bin,S=Secondary,O=Overstock, M=Moveable
 whs_qty         int default 0,
 whs_uom        char(3) default " "

R=Recv,
P=Putway,
A=Adj,
M=Move,
I=Invoice,
C=Cust Return,
S=Special Order,
T=Transfer,
N=ASN
*/

} // end upd_WhseLoc
function chkPartOnPO($shadow,$POs,$qty_scanned=1)
{
 $poitems=array();
 if (count($POs) < 1 or $shadow < 1) return $poitems ;
 $P="";
 $comma="";
 foreach($POs as $p)
 {
  $P.="{$comma}{$p}";
  $comma=",";
 } // end foreach POs
 $wt="=";
 $we="";
 if (count($POs) > 1)
 {
  $wt="in (";
  $we=")";
 }
 $where=<<<SQL
where poi_po_num {$wt}{$P}{$we}
 and shadow = {$shadow}

SQL;
 
$SQL=<<<SQL
select
poi_po_num,
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
mdse_price,
core_price,
weight,
volume,
case_uom,
case_qty,
poi_status,
vendor_ship_qty,
packing_slip,
tracking_num,
bill_lading,
container_id,
carton_id,
line_type,
{$qty_scanned} as qty_scanned
from POITEMS
{$where}
order by poi_po_num,poi_line_num
 
SQL;
    $poitems=$this->gData($SQL);

 $poitems["numRows"]=0;
 $poitems["inRecv"]=0;
 $poitems["numRows"]=$this->numRows;
 //check current open receipts for this part
 $whr=str_replace("poi_po_num","wms_po_num",$where);
 $SQL=<<<SQL
 select sum(totalQty) as inRecv
from RCPT_INWORK,RCPT_SCAN
{$whr}
and  RCPT_INWORK.batch_num = RCPT_SCAN.batch_num
and scan_status < 1
SQL;
  $inRecv=0; 
  $db=$this->db;
  $rc=$db->query($SQL);
  $numrows=$db->num_rows();
  $i=1;
  while ($i <= $numrows)
  {
   $db->next_record();
     if ($numrows)
     {
           $inRecv=$db->f("inRecv");
     }
     $i++;
   } // while i < numrows

 $poitems["inRecv"]=$inRecv;
 return $poitems ;
} // end chkPartOnPO

function count_batch($batch)
{
 $ret=0;
 $SQL=<<<SQL
select count(*) as cnt
from RCPT_SCAN
where batch_num = {$batch}
SQL;

  $db=$this->db;
  $rc=$db->query($SQL);
  $numrows=$db->num_rows();
  $i=1;
  while ($i <= $numrows)
  {
   $db->next_record();
     if ($numrows)
     {
           $ret=$db->f("cnt");
     }
     $i++;
   } // while i < numrows
 return $ret ;
} // end count_batch

function get_batch($batch)
{
 // args batch = batchnum
 $ret=array();
 $ret["status"]=-35;
 if ($batch > 0)
 {
  $SQL=<<<SQL
select * from RCPT_BATCH
where batch_num = {$batch}

SQL;
$SQL1=<<<SQL
 select RCPT_INWORK.wms_po_num, host_po_num
 from RCPT_INWORK,POHEADER
where batch_num = {$batch}
  and POHEADER.wms_po_num = RCPT_INWORK.wms_po_num

SQL;
  $db=$this->db;
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
  if (count($ret) > 1) $ret["status"]=1;
  $rc=$db->query($SQL1);
  $numrows=$db->num_rows();
  $i=1;
  while ($i <= $numrows)
  {
   $db->next_record();
     if ($numrows and $db->Record)
     {
      foreach ($db->Record as $key=>$data)
       {
        if (!is_numeric($key)) { $ret["POs"][$i]["$key"]=$data; }
       }
     }
    $i++;
  } // while i < numrows

 } // end batch > 0
 return $ret ;
} // end get batch
function get_batchDetail($batch,$shadow,$user)
{
 $ret=array();
 $SQL=<<<SQL
 select	 batch_num,
	 line_num,
	 pkgUOM,
	 scan_upc,
	 po_number,
	 po_line_num,
	 scan_status,
	 scan_user,
	 pack_id,
	 shadow,
	 partUOM,
	 line_type,
	 pkgQty,
	 scanQty,
	 totalQty,
	 timesScanned,
         recv_to
 from RCPT_SCAN
 where batch_num = {$batch}
   and shadow = {$shadow}
   and scan_user = {$user}

SQL;
  $db=$this->db;
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
 return $ret ;
/*
update WHSEQTY table
Fields to update
        primary_bin, (if emtpt, set to the bin I'm putting it in)
        qty_avail
fieds to find record
        ms_shadow,
        ms_company,
check if WHSELOC for this shadow
insert or update WHSELOC table
key: whs_company, whs_location, whs_shadow,
 whs_code, (if above bin is empty, set this to Primary
 whs_qty, (what ever was there + ($reqdata["scanQty"] * $pkgQty))
 whs_uom ( $reqdata["partUOM"]=$partUOM;)
*/
    } // recv to Bin, update WHSEQTY and add PARTHIST

} // end class BINS

/*
create table WHSELOC (
 whs_company     smallint,
 whs_location    varchar(18),
 whs_shadow      int default 0,
 whs_code        char(2) default " ", -- P=Primary Bin,S=Secondary,O=Overstock, M=Moveable
 whs_qty         int default 0,
 whs_uom        char(3) default " "
);


create table WHSEBINS (
 wb_company     smallint,
 wb_location    char(18),
 wb_zone        char(3) default ' ',
 wb_aisle       smallint unsigned default 0,
 wb_section     tinyint unsigned default 0,
 wb_level       char(1) default ' ', -- shelf
 wb_subin       tinyint unsigned default 0,
 wb_length      numeric (7,2) default 0,
 wb_width       numeric (7,2) default 0,
 wb_height      numeric (7,2) default 0,
 wb_volume numeric(10,2) default 0.00,
 wb_pick      tinyint default 1, -- is pickable
 wb_recv      tinyint default 1, -- is allowed receiving
 wb_status    char(1) default "A" -- A=Active, I=inactive, D=delete
);
*/
?>

