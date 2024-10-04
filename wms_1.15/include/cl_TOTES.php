<?php
// cl_TOTES.php
  // 08/23/22 dse get tote code instead of tote_id
  // 06/23/23 dse add countItems
  // 06/23/23 dse add freeTote
  // 06/29/23 dse add cdShadow
  // 07/10/24 dse correct tote_id/tote_code handling

if (get_cfg_var('wmsdir')) $wmsDir=get_cfg_var('wmsdir');
else { echo "<h1>WMS System is not Configured on this System</h1>"; exit; }
$top=str_replace("/var/www","",$wmsDir);

$wmsInclude="{$wmsDir}/include"; // main incude for this system
require_once("{$wmsInclude}/db_main.php");
require_once("{$wmsInclude}/wr_log.php");

class Tote
{
 public $numRows;
 public $toteContents;
 private $db;

 public function __construct()
 {
  $this->db = new WMS_DB;
  $this->db->DBDBG="/tmp/moveQty_SQL.log";
 } // end construct 

 public function addItemToTote($tote_code,$shadow,$qty,$uom)
 {
  $db=$this->db;
  $rc3="";
  $tote_id=$this->getToteId($tote_code);
  if ($tote_id < 0) return -1;
  $item=0;
  $SQL=<<<SQL
select tote_item, tote_qty
from TOTEDTL
where tote_id = {$tote_id}
and tote_shadow = {$shadow}
-- and tote_uom = "{$uom}"

SQL;
  $rc4=$db->query($SQL);
  $numrows=$db->num_rows();
  $i=1;
  while ($i <= $numrows)
  {
   $db->next_record();
     if ($numrows)
     {
        $item=$db->f("tote_item");
        $tqty=$db->f("tote_qty");
     }
     $i++;
   } // while i < numrows
  if ($item < 1)
  {
   $SQL=<<<SQL
 select max(tote_item) as cnt from TOTEDTL
 where tote_id = {$tote_id}
SQL;
   $rc4=$db->query($SQL);
   $cnt2=$db->Row[0]["cnt"];
   $item=$cnt2 + 1;
  } // end item < 1

  if (isset($tqty))
  { // update
   $SQL=<<<SQL
update TOTEDTL
set tote_qty = tote_qty + {$qty}
where tote_id = {$tote_id}
and tote_item = {$item}
and tote_shadow = {$shadow}
-- and tote_uom = "{$uom}"

SQL;
  } // update
  else
  { // insert
   $SQL=<<<SQL
insert into TOTEDTL
( tote_id, tote_item, tote_shadow, tote_qty, tote_uom)
values ( {$tote_id}, {$item}, {$shadow}, {$qty}, "{$uom}")

SQL;
  } // insert
  // wr_log("/tmp/cl_totes.log","SQL={$SQL}");

  $rc3=$db->Update($SQL);
 $this->numRows=$rc3;
  return $rc3;
 } // end addItem

 public function getToteHdr($tote_code,$comp, $ref_num="",$zone="",$whseLoc="")
 {
  $tote_id=$this->getToteId($tote_code);
  $db=$this->db;
  $SQL=<<<SQL
select  
tote_code as tote_id,
tote_company,
tote_status,
tote_location,
tote_lastused,
num_items,
tote_type,
tote_ref
from TOTEHDR
where tote_id = {$tote_id}
 and tote_company = {$comp}

SQL;
  $ret=array();
  //if (!is_numeric($tote_id))
  //{
   //$this->numRows=-1;
   //return($ret);
  //}
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
 $this->numRows=$numrows;
 return $ret;
 } // end getToteHdr
 public function updRcptTote($req)
 {
  $db=$this->db;
  // req is an array with the field names in the flds array
  $tote_id=$this->getToteId($req["tote_id"]);
  $flds=array(
   "rcpt_num"=>0,
   "tote_id"=>0,
   "rcpt_status"=>0,
   "last_zone"=>1,
   "last_loc"=>1,
   "target_zone"=>1,
   "target_aisle"=>0
   );
   foreach ($flds as $fldname=>$isString)
   {
    if (!isset($req[$fldname])) $req["fldname"]=$this->setDefault($isString);
   }
   $SQL=<<<SQL
select
  rcpt_num,
  tote_id,
  rcpt_status,
  last_zone,
  last_loc,
  target_zone,
  target_aisle
 from RCPT_TOTE
 where rcpt_num = {$req["rcpt_num"]}
   and tote_id = {$tote_id}

SQL;
  $data=$db->gData($SQL);
  $exists=$db->NumRows;
 
  if ($exists > 0) $SQL=<<<SQL
update RCPT_TOTE
set rcpt_status = {$req["rcpt_status"]},
      last_zone = "{$req["last_zone"]}",
       last_loc = "{$req["last_loc"]}",
    target_zone = "{$req["target_zone"]}",
   target_aisle = {$req["target_aisle"]}
 where rcpt_num = {$req["rcpt_num"]}
   and tote_id = {$tote_id}
SQL;
  else $SQL=<<<SQL
insert into RCPT_TOTE
( rcpt_num, tote_id, rcpt_status, last_zone, last_loc, target_zone, target_aisle)
values (
 {$req["rcpt_num"]}, {$tote_id}, {$req["rcpt_status"]}, "{$req["last_zone"]}", "{$req["last_loc"]}", "{$req["target_zone"]}", {$req["target_aisle"]}
)

SQL;
  $rc=$db->Update($SQL);
  return $rc;
 } // end updRcptTote

 function setDefault($is)
 {
  if ($is > 0) return ""; else return 0;
 } // end setDefault

 public function updToteHdr($tote_code,$comp, $status=0,$type="",$zone="",$ref="")
 {
  $tote_id=$this->getToteId($tote_code);
  $db=$this->db;
  $this->numRows=0;
  if (!is_numeric($status)) $status = 0;
  $SQL=<<<SQL
update TOTEHDR
set tote_status = {$status},
    tote_location = "{$zone}",
    tote_type = "{$type}",
    tote_ref = "{$ref}"
where tote_id = {$tote_id}
 and tote_company = {$comp}
SQL;
  $rc=$db->Update($SQL);
  return $rc;
 }
 public function getToteId($tote_code)
 {
  $db=$this->db;
  $ret=-1;
  if (is_numeric($tote_code)) $where=<<<SQL
where (tote_code = "{$tote_code}" or tote_id = {$tote_code})
SQL;
  else $where=<<<SQL
where tote_code = "{$tote_code}"
SQL;
  $SQL=<<<SQL
select tote_id from TOTEHDR
{$where}

SQL;
  $rc4=$db->query($SQL);
  $numrows=$db->num_rows();
  $i=1;
  while ($i <= $numrows)
  {
   $db->next_record();
     if ($numrows)
     {
        $ret=$db->f("tote_id");
     }
     $i++;
   } // while i < numrows
  return $ret;
 } // end getToteId

 function countItems($tote_code,$shadow=0)
 {
  $tote_id=$this->getToteId($tote_code);
  $db=$this->db;
  $cnt=0;
  $shd="";
  if ($shadow > 0) $shd=" and tote_shadow = {$shadow}";
  $SQL=<<<SQL
 select count(*) as cnt from TOTEDTL
 where tote_id = {$tote_id} {$shd}
SQL;
  $rc=$db->query($SQL);
  $numrows=$db->num_rows();
  $i=1;
  while ($i <= $numrows)
  {
   $db->next_record();
     if ($numrows)
     {
        $cnt=$db->f("cnt");
     }
     $i++;
   } // while i < numrows
  return $cnt;
 } // end countItems


function freeTote($tote_code)
{
 // returns 0 if tote freed, esle returns the count of items in tote
 $tote_id=$this->getToteId($tote_code);
 $rc=0;
 $db=$this->db;
 $cnt=$this->countItems($tote_id);
  if ($cnt < 1)
  { // update tote status to free
   $SQL=<<<SQL
update TOTEHDR
set tote_status = 0,
    tote_type = " ",
    tote_location = " ",
    tote_ref = 0,
    tote_lastused = NOW()
where tote_id = {$tote_id}

SQL;
   $rc=$db->Update($SQL);
  } // update tote status
 if ($rc > 0) return 0; else return $cnt;

} // end freeTote

function cdShadow($tote_code,$shadow=0)
{ // check and delete shadow from tote if qty = 0
 if ($shadow < 1) return 0;
 $tote_id=$this->getToteId($tote_code);
  if ($tote_id < 0) return -1;
 $rc=0;
 $db=$this->db;
 $SQL=<<<SQL
 delete from TOTEDTL 
where tote_id = {$tote_id}
 and tote_shadow = {$shadow}
 and tote_qty = 0

SQL;
   $rc=$db->Update($SQL);
  return $rc;
} // end cdShadow

} // end class Tote
?>
