<?php
// cl_TOTES.php
  // 08/23/22 dse get tote code instead of tote_id
  // 04/26/22 dse Add delItemFromTote 

if (get_cfg_var('wmsdir')) $wmsDir=get_cfg_var('wmsdir');
else { echo "<h1>WMS System is not Configured on this System</h1>"; exit; }
$top=str_replace("/var/www","",$wmsDir);

$wmsInclude="{$wmsDir}/include"; // main incude for this system

require_once("{$wmsInclude}/db_main.php");

class Tote
{
 public $numRows;
 public $toteContents;
 private $db;

 public function __construct()
 {
  $this->db = new WMS_DB;
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
and tote_uom = "{$uom}"

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
 select count(*) as cnt from TOTEDTL
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
and tote_uom = "{$uom}"

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
  $rc3=$db->Update($SQL);
 $this->numRows=$rc3;
  return $rc3;
 } // end addItem

 public function getToteHdr($tote_id,$comp, $ref_num="",$zone="",$whseLoc="")
 {
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
where tote_code = "{$tote_id}"
 and tote_company = {$comp}

SQL;
  $ret=array();
  if (!is_numeric($tote_id))
  {
   $this->numRows=-1;
   return($ret);
  }
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

 public function updToteHdr($tote_id,$comp, $status=0,$type="",$zone="",$ref="")
 {
  $db=$this->db;
  $this->numRows=0;
  if (!is_numeric($status)) $status = 0;
  $SQL=<<<SQL
update TOTEHDR
set tote_status = {$status},
    tote_location = "{$zone}",
    tote_type = "{$type}",
    tote_ref = "{$ref}"
where tote_code = "{$tote_id}"
 and tote_company = {$comp}
SQL;
  $rc=$db->Update($SQL);
  return $rc;
 }
 private function getToteId($tote_code)
 {
  $db=$this->db;
  $ret=-1;
  $SQL=<<<SQL
select tote_id from TOTEHDR
where tote_code = "{$tote_code}"

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

 public function delItemFromTote($tote_code,$shadow,$qty,$uom)
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
and tote_uom = "{$uom}"

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
  $rc3=0;
//if tqty, the part exists, subtract unles it's qty is less than $qty, then delete
  if (isset($tqty))
  {
   if ($tqty > $qty)
   { // update
    $SQL=<<<SQL
update TOTEDTL
set tote_qty = tote_qty - {$qty}
where tote_id = {$tote_id}
and tote_item = {$item}
and tote_shadow = {$shadow}
and tote_uom = "{$uom}"

SQL;
   } // update
   else
   { // delete
   $SQL=<<<SQL
delete from TOTEDTL
where tote_id = {$tote_id}
and tote_item = {$item}
and tote_shadow = {$shadow}
and tote_uom = "{$uom}"

SQL;
   } // delete
  $rc3=$db->Update($SQL);
 } // isset tqty
 $this->numRows=$rc3;
 return $rc3;
  // check if tote is empty, if so free the tote
 } // end delItemFromTote

} // end class Tote
?>
