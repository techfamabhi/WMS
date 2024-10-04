<?php
//RcptLine -- fetch and update Rcpt Line
/*
 06/28/22 dse add open PO and po count to return for this item
              so user can change the PO if the system got it wrong
 08/23/22 dse change tote_id to get it from tote_code instead
 10/26/22 dse Add pref zone and aisle if bin is blank in getToteDetail
 05/29/24 dse Add recv_to on getBatchDetail
 06/05/24 dse Add shadow on getBatchDetail
 07/10/24 dse Addchange where clause of getPoForPart
TODO
add update function
If recvto from RCPT_SCAN == b, update bin inventory and PARTHIST too

*/

$logfile="/tmp/RcptLine.log";
$update_table="";
$query_table="";
$u=true;
$DEBUG=true;
require("srv_hdr.php");
require("getToteId.php");


require_once("../include/quoteit.php");
require_once("../include/db_main.php");
require_once("../include/date_functions.php");
require_once("../include/cl_bins.php");
require_once("../include/cl_PARTS2.php");
require_once("../include/cl_PARTS2.php");
require_once("getUser.php");
require_once("updPoStat.php");
require_once("rcpt_utils.php");
require_once("../include/wr_log.php");
require_once("../include/get_contrl.php"); 
require_once("../include/get_option.php");


$inputdata = file_get_contents("php://input");
$reqdata=json_decode($inputdata,true);
$db=new WMS_DB;
$db1=new WMS_DB;
if ($DEBUG) wr_log("/tmp/RcptLine.log","Program={$_SERVER["PHP_SELF"]}");
if ($DEBUG) wr_log("/tmp/RcptLine.log","inputData:\n{$inputdata}");

if (!isset($reqdata["action"]))
{
 header('HTTP/1.1 400 Bad Request', true, 400);
 exit("Status: 400 Bad Request");
}

/* posArgs array of names and type of possible arguments
 types; 
	0 = numeric (default to 0)
	1 = string  (default to "")
        2 = datetime (default to now)
        3 = datetime (default to null)
*/
$posArgs=array(
"batch"=>0,
"userId"=>0,
"po"=>0,
"line"=>0,
"origQty"=>0,
"newQty"=>0,
"tote_id"=>1,
"shadow"=>0,
"id_num"=>0,
"userId"=>0,
"onlyOpen"=>0,
"task_id"=>0,
"target_aisle"=>0,
"POs"=>1,
"origBin"=>1,
"newBin"=>1,
"newLoc"=>1,
"zone"=>1,
"target_zone"=>1,
"task_type"=>1,
"operation"=>1,
"host_po_num"=>1,
"vendor"=>1,
"start_time"=>2,
"end_time"=>3,
"typeSearch"=>1,
"orderBy"=>1
);
$action=$reqdata["action"];
if (isset($reqdata["comp"]))    $comp   =$reqdata["comp"];    else $comp=1;
if (isset($reqdata["company"])) $comp   =$reqdata["company"]; 
if (isset($reqdata["rtype"]))   $rtype  =$reqdata["rtype"];
if (isset($reqdata["overage"])) $overage=$reqdata["overage"];

//parse args in posArgs
foreach($posArgs as $var=>$typ)
{
 $defVal=0;
 if ($typ == 1) $defVal="";
 if ($typ == 2) $defVal=date("Y/m/d h:i:s");
 if ($typ == 3) $defVal=null;
 if (isset($reqdata[$var]))   $$var  =$reqdata[$var];   else $$var=$defVal;
} // end foreach posArgs

/*
if (isset($reqdata["batch"]))   $batch  =$reqdata["batch"];   else $batch=0;
if (isset($reqdata["userId"]))  $userId =$reqdata["userId"];  else $userId=0;
if (isset($reqdata["po"]))      $po     =$reqdata["po"];      else $po=0;
if (isset($reqdata["line"]))    $line   =$reqdata["line"];    else $line=0;
if (isset($reqdata["origQty"])) $origQty=$reqdata["origQty"]; else $origQty=0;
if (isset($reqdata["tote_id"])) $tote_id=$reqdata["tote_id"]; else $tote_id=0;
if (isset($reqdata["id_num"])) $id_num=$reqdata["id_num"]; else $id_num=0;
if (isset($reqdata["userId"])) $userId=$reqdata["userId"]; else $userId=0;
if (isset($reqdata["newQty"]))  $newQty =$reqdata["newQty"];  else $newQty=0;
if (isset($reqdata["task_id"])) $task_id=$reqdata["task_id"]; else $task_id=0;

if (isset($reqdata["POs"]))     $POs    =$reqdata["POs"];     else $POs="";
if (isset($reqdata["origBin"])) $origBin=$reqdata["origBin"]; else $origBin="";
if (isset($reqdata["newBin"]))  $newBin =$reqdata["newBin"];  else $newBin="";
if (isset($reqdata["newLoc"]))  $newLoc =$reqdata["newLoc"];  else $newLoc="";
if (isset($reqdata["zone"]))  $zone =$reqdata["zone"];  else $zone="";
if (isset($reqdata["task_type"])) $task_type=$reqdata["task_type"]; else $task_type="";

if (isset($reqdata["operation"])) $operation= $reqdata["operation"]; else $operation="";
if (isset($reqdata["host_po_num"])) $host_po_num=$reqdata["host_po_num"]; else $host_po_num="";
if (isset($reqdata["typeSearch"])) $typeSearch=$reqdata["typeSearch"]; else $typeSearch="";

*/

$opt[27]=get_option($db,$comp,27);
if ($DEBUG) wr_log("/tmp/RcptLine.log","Switching={$action}");
$rdata=array();
$goodAction=false;
switch ($action)
{
 case "fetchSingle":
 {
  if ($batch < 1) break;
  $goodAction=true;
  $SQL=<<<SQL
select
batch_num,
line_num,
shadow,
p_l,
part_number,
part_desc,
part_desc,
partUOM,
totalOrd,
totalQty,
qty_stockd,
po_number,
pack_id
from RCPT_SCAN, PARTS
where batch_num = {$batch}
  and line_num = {$line}
and scan_status < 2
and shadow_number = shadow

SQL;
 $rc=$db->query($SQL);
  $numrows=$db->num_rows();
  $i=0;
  while ($i <= $numrows)
  {
      $db->next_record();
     if ($numrows and $db->Record)
     {
      foreach ($db->Record as $key=>$data)
      {
        if ($action == "fetchSingle") 
        { 
         $rdata[$key]=$data;
         if ($key == "shadow")
          {
           $tmp=chkPartOnPO($db1,$data,$POs);
           $rdata["multiPO"]=$tmp["PO"];
           $rdata["poCount"]=$tmp["numRows"];
          }
        }
        else { if (!is_numeric($key)) 
        { 
         $rdata[$i]["$key"]=$data; 
         if ($key == "shadow")
          {
           $tmp=chkPartOnPO($db1,$data,$POs);
           $rdata[$i]["multiPO"]=$tmp["PO"];
           $rdata[$i]["poCount"]=$tmp["numRows"];
          }
        } 
       }
      }
     }
    $i++;
  } // while i < numrows
$x=json_encode($rdata);
if ($DEBUG) wr_log("/tmp/RcptLine.log",$SQL);
if ($DEBUG) wr_log("/tmp/RcptLine.log","Response:{$x}");
echo $x;
 break;
 } // end fetchSingle
case "palletsToMove": // looks for pallets in receiving to move
 {
  $goodAction=true;
  $SQL=<<<SQL
select rcpt_num, 
A.tote_id as tote_num, 
A.tote_code as tote_id,
rcpt_status,
last_zone,
last_loc,
target_zone,
target_aisle 
from RCPT_TOTE A,TOTEHDR B
where B.tote_id = A.tote_id
and B.tote_company = {$comp}
and last_zone = "RCV"
order by rcpt_num
SQL;
  //if ($DEBUG) wr_log("/tmp/RcptLine.log",$SQL);
  $rdata=$db->gData($SQL);
  $numRows=$db->NumRows;
// may be a problem with this query if more than 1 po was put into a tote
// needs testing
  if ($numRows > 0)
  {
   foreach ($rdata as $key=>$data)
   {
    $SQL=<<<SQL
   select host_po_num,count(*) as numParts, vendor
from RCPT_SCAN A, POHEADER B
where pack_id = {$data["tote_num"]}
and B.wms_po_num = A.po_number

SQL;
   
  $rc=$db->query($SQL);
  $numrows=$db->num_rows();
  $i=1;
  while ($i <= $numrows)
  {
   $db->next_record();
     if ($numrows)
     {
        $rdata[$key]["vendor"]=$db->f("vendor");
        $rdata[$key]["host_po_num"]=$db->f("host_po_num");
        $rdata[$key]["numParts"]=$db->f("numParts");
     }
     $i++;
   } // while i < numrows
   } // end foreach rdata
  } // end numRows > 0
  // format and send response
  break;
 } // end fetchPutaway

case "getPoForPart": // givin tote# and shadow#, get po number for part in Tote 
 {
  $goodAction=true;
  $rdata=array();
  if (trim($tote_id) == "" or $shadow < 1) break;
  $tote_num=getToteId($tote_id,$comp);
  $sStatus="and scan_status < 2";
  if ($opt[27] > 0) $sStatus=""; // want it to work whether it's updated or not
  
  $with_pack="and pack_id = \"{$tote_id}\""; // works with tote or bin
  $openOnly="";
  if (isset($onlyOpen) and $onlyOpen > 0) $openOnly="and (totalQty - qty_stockd) > 0";

  $SQL=<<<SQL
select count(*) as cnt from RCPT_SCAN
where shadow = {$shadow}
{$with_pack}
{$sStatus}
{$openOnly}

SQL;
 $c=ldata($db,$SQL,"cnt");
 if ($c < 1) $with_pack="";
  
  $SQL=<<<SQL
select po_number, host_po_num,batch_num,totalQty - qty_stockd as Qty
from RCPT_SCAN, POHEADER
where shadow = {$shadow}
{$with_pack}
{$sStatus}
{$openOnly}
 and (totalQty - qty_stockd > 0
  or pack_id = "{$tote_id}")

and wms_po_num = po_number

SQL;
  if ($DEBUG) wr_log("/tmp/RcptLine.log",$SQL);
  
  $tq=0;
  $rc=$db->query($SQL);
  $numrows=$db->num_rows();
  $i=0;
  while ($i <= $numrows)
  {
   $db->next_record();
    if ($numrows and $db->Record)
     {
        $rdata[$i]["po_number"]=$db->f("po_number");
        $rdata[$i]["host_po_num"]=$db->f("host_po_num");
        $rdata[$i]["batch_num"]=$db->f("batch_num");
        $rdata[$i]["Qty"]=$db->f("Qty");
     }
     $i++;
   } // while i < numrows
  $rdata["numRows"]=$numrows;
  break;
 } // end getPoForPart
case "getPartInTote": // get part in tote Tote Info
 {
  $goodAction=true;
  $rdata=array();
  if (trim($tote_id) == "" or $shadow < 1) break;
  $tote_num=getToteId($tote_id,$comp);
  $SQL=<<<SQL
select 
B.tote_id,
tote_item,
tote_shadow,
tote_qty,
tote_uom
from TOTEHDR A, TOTEDTL B
where ( A.tote_id= {$tote_num} or tote_code = "{$tote_id}" )
and B.tote_id = A.tote_id
and tote_shadow = {$shadow}

SQL;
  if ($DEBUG) wr_log("/tmp/RcptLine.log",$SQL);
  $rdata=$db->gData($SQL);
  $rdata["numRows"]=$db->NumRows;
  if (!isset($rdata[1]))
  { //part not found in this tote
   $rdata["errCode"]=-1;
   $rdata["errText"]="Part not found in Tote {$tote_id}";
  } //part not found in this tote
  break;
 } // end getPartInTote

case "getTote": // get Tote Info
 {
  $goodAction=true;
  if (trim($tote_id) == "") break;
  $tote_num=getToteId($tote_id,$comp);
    $SQL=<<<SQL
select 
 tote_code as tote_id,
 tote_id as tote_num,
tote_company,tote_status,tote_location,tote_lastused,
num_items,tote_type,tote_ref 
from TOTEHDR 
where (tote_code = "{$tote_id}"
or tote_id = {$tote_num})
and tote_company = {$comp}
SQL;
  //if ($DEBUG) wr_log("/tmp/RcptLine.log",$SQL);
  $rdata=$db->gData($SQL);
  $numRows=$db->NumRows;
    if ($numRows > 0)
  {
   foreach ($rdata as $key=>$data)
   {
    $SQL=<<<SQL
   select count(*) as numItems,
   IFNULL(sum(tote_qty),0) as totalQty
from TOTEDTL 
where tote_id = {$tote_num}

SQL;
  $rc=$db->query($SQL);
  $numrows=$db->num_rows();
  $i=1;
  while ($i <= $numrows)
  {
   $db->next_record();
     if ($numrows)
     {
        $rdata[$key]["num_items"]=$db->f("numItems");
        $rdata[$key]["totalQty"]=$db->f("totalQty");
     }
     $i++;
   } // while i < numrows
   } // end foreach rdata
  } // end numRows > 0

  break;
 } // end getTote

case "updToteLoc":
{
  $goodAction=true;
  $rdata=array();
  if ($tote_id > 0)
  {
    $tote_num=getToteId($tote_id,$comp);
   $SQL=<<<SQL
select tote_code as tote_id
from RCPT_TOTE A,TOTEHDR B
where ( A.tote_id= {$tote_num} or tote_code = "{$tote_id}" )
and B.tote_id = A.tote_id
and B.tote_company = {$comp}
SQL;
   
  $rc=$db->query($SQL);
  $numrows=$db->num_rows();
  if ($numrows > 0)
  { // update tote location
   if ($zone <> "" and $newBin <> "")
   {
    $SQL=<<<SQL
update RCPT_TOTE
set last_zone = "{$zone}",
last_loc = "{$newBin}"
where tote_id = {$tote_num}
SQL;
    $rc=$db->Update($SQL);
   } // zone and newBin <> ""
  } // update tote location
   if (isset($operation) and $operation <> "")
   {
    $SQL=<<<SQL
update TOTEHDR
set tote_type = "{$operation}",
    tote_location = "{$newBin}",
    tote_lastused = NOW()
where tote_id= {$tote_num} 

SQL;
    $rc1=$db->Update($SQL);
   } // update operation
  if (!isset($rc)) $rc=-1;
  if (!isset($rc1)) $rc1=-1;
  $rdata=array("RcptUpd"=>$rc,"ToteUpd"=>$rc1);
  } // end tote_id > 0
  break;
} // end updToteLoc

case "getToteDetail": // get a totes contents
case "getToteContents": // get a totes contents
{
  $goodAction=true;
  $tote_num=getToteId($tote_id,$comp);
  if ($tote_num < 1 and $shadow < 1) $goodAction=false;
  else $rdata=getToteDtl($db,$tote_num,$comp,$shadow,$orderBy);
  break;
} // end getToteContents

case "getOpenLines": // get a POs open lines
 {
  $goodAction=true;
  // po numbers are the wms po number not the host po numbers
  // rtype 0=get detail records
  //       1=return record count
  //       2=return ovrages only (overage must be set to true)
  // overage false does not return overage records true does
  /* return                        | rtype | overage
     ------------------------------|-------|---------
     count open lines              |   1   | false
     get detail lines              |   0   | false
     get detail and overage lines  |   0   | true
     get just overage lines        |   2   | true
  */

  if (!is_array($POs) and !isset($POs)) break;
  if (!isset($rtype)) $rtype=0;
  if (!isset($overage)) $overage=false;
  $rdata=poOpenLines($db,$POs,$rtype,$overage);
  break;
 } // end getOpenLines

case "getNewLoc": // check New Location
 {
  $goodAction=true;
  $tote_num=getToteId($tote_id,$comp);
  if ($newLoc == "" or $tote_num < 1)
  {
   $rdata=array();
   $rdata["numRows"]=0;
   break;
  }
  // check if it is a staging area, if not, check if bin
  $SQL=<<<SQL
select
zone,
zone_type,
zone_desc,
is_pickable
from WHSEZONES
where zone_company = {$comp}
and zone = "{$newLoc}"

SQL;
   
  $rdata=$db->gData($SQL);
  $numRows=$db->NumRows;
  if ($numRows < 1)
  { // try if it is a bin
   $bincls=new BIN;
   $tmp=$bincls->getBinInfo($comp,$newLoc);
   $rdata["numRows"]=$tmp["numRows"];
   if ($rdata["numRows"] > 0)
   {
echo "<pre> count(tmp) = " . count($tmp);
print_r($tmp);
    $rdata["zone"]=$tmp["wb_location"];
    $rdata["zone_type"]="BIN";
    $rdata["zone_desc"]="Size = {$tmp["wb_length"]} x {$tmp["wb_width"]} x {$tmp["wb_height"]}";
    $rdata["is_pickable"]=$tmp["wb_pick"];
   }
   
  } // try if it is a bin
  break;
 } // end getNewLoc

 case "getToteLoc":
 {
  $goodAction=true;
  $tote_num=getToteId($tote_id,$comp);
  $SQL=<<<SQL
select distinct B.tote_id, last_zone , last_loc, target_zone,target_aisle
from RCPT_TOTE A,TOTEHDR B
where ( B.tote_id= {$tote_num} or tote_code = "{$tote_id}" )
and B.tote_id = A.tote_id
and B.tote_company = {$comp}
order by rcpt_num
SQL;
  if ($DEBUG) wr_log("/tmp/RcptLine.log",$SQL);
  $rdata=$db->gData($SQL);
  $numRows=$db->NumRows;
 if ($numRows < 1)
 {
    $SQL=<<<SQL
select tote_id,"" as last_zone , "" as last_loc, "" as target_zone, "" as target_aisle, tote_status
from TOTEHDR 
where ( tote_id= {$tote_num} or tote_code = "{$tote_id}" )
and tote_company = {$comp}
SQL;
  if ($DEBUG) wr_log("/tmp/RcptLine.log",$SQL);
  $rdata=$db->gData($SQL);
  $numRows=$db->NumRows;
 }

  break;
 } // end getToteLoc

case "chkTask": // Add new Task 
 {
  $goodAction=true;
  $rdata=checkTask($db,$comp,$tote_id,$task_id,$task_type,$id_num,$userId,$zone);
  if ($rdata === null) $rdata=array();
  $numRows=count($rdata);
  break;
 } // end chkTask

case "addTask": // Add new Task 
 {
  $goodAction=true;
  $rdata=array();
  $tote_num=getToteId($tote_id,$comp);
  if ($tote_num > 0 and $task_type <> "" and $userId > 0)
  { // got minimum data, proceed
   if ( !isset($task_date) or $task_date == "") $task_date=date("Y/m/d h:i:s");
    $uFlds=setFldDef($db,"TASKS");
   $check=checkTask($db,$comp,$tote_num,$task_id,$task_type,0,0,"");
   if ($task_id < 1)
   {
     if (isset($check[1]["task_id"])) $task_id=$check[1]["task_id"];
     if (isset($check[1]["start_time"])) $start_time=$check[1]["start_time"];
     if (isset($check[1]["task_date"])) $task_date=$check[1]["task_date"];
   if ($task_date == "0000-00-00 00:00:00") $task_date=date("Y/m/d h:i:s");
   } // end task_id not set, check $check
   if (count($check) < 1 or $task_id < 1)
   { // get new task#
     $task_id=get_contrl($db,0,"TASK");
   }  // get new task#
   $rowData=array();
   foreach ($uFlds as $var=>$typ)
   {
    $fld=$var;
    if ($fld == "user_id") $fld="userId";
    if ($fld == "last_zone") $fld="zone";
    if (isset($$fld))   $rowData[$var]=$$fld;   
    else if (isset($check[1][$fld])) $rowData[$var]=$check[1][$fld];
    else
    {
     if ($typ < 1) $rowData[$var]=0; else $rowData[$var]="";
    }
   } // end foreach uFlds
//print_r($rowData);
    
//"task_id" => $task_id,
//"task_type" => $task_type,
//"task_status" => 0,
//"id_num" => $id_num,
//"user_id" => 0
//"tote_id" => 0
//"last_zone" => 1
//"last_loc" => 1
//"target_zone" => 1
//"target_aisle" => 0
//"start_time" => 1
//"end_time" => 1
//);
   $where="where task_id = {$task_id}";
   $rc=$upd->updRecord($rowData,"TASKS",$where);
   $rdata=$rowData;
   $rdata["message"]=$rc;
  } // got minimum data, proceed
  break;
 } // end addTask

 case "getOpenBatches":
 { 
  /* get open batches, display by PO with detail of batches */
  $goodAction=true;
  $where="";
  $PO=$host_po_num;
  if ($PO <> "") $where.=" and host_po_num like \"{$PO}%\"\n";
  if ($vendor <> "") $where.=" and POHEADER.vendor like \"{$vendor}%\"\n";
  if ($typeSearch == "%") $typeSearch="";
  if ($typeSearch <> "") $where.=" and po_type = \"{$typeSearch}\"\n";

  $SQL=<<<SQL
 select distinct
 po_number,
 host_po_num,
 POHEADER.vendor,
 name,
 po_type

  from RCPT_SCAN A, RCPT_BATCH B, POHEADER, VENDORS
 where batch_status < 1
  and B.batch_num = A.batch_num
 and wms_po_num = po_number
 and VENDORS.vendor = POHEADER.vendor
 {$where}

SQL;
  
  if ($DEBUG) wr_log("/tmp/RcptLine.log",$SQL);
 $rdata=array();
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
     if (!is_numeric($key)) { $rdata[$i]["$key"]=$data; }
    }
   }
  $i++;
 } // while i < numrows

 if (count($rdata) > 0)
 {
$DSQL=<<<SQL
select distinct
A.batch_num,
line_num,
po_number,
scan_user,
 user_id,
 min(batch_date),
 batch_type,
scan_status

from RCPT_INWORK A, RCPT_BATCH B, RCPT_SCAN C
where wms_po_num = PONUMBER
and batch_status < 1
and B.batch_num = A.batch_num
and C.batch_num = A.batch_num
group by A.batch_num
SQL;
  foreach ($rdata as $ri=>$d)
  {
   if (isset($dtl)) unset($dtl);
   if (isset($numBatches)) unset($numBatches);
   $numBatches=array();
   $dtl=array();
   $ponum=$d["po_number"];
   $SQL=str_replace("PONUMBER",$ponum,$DSQL);
   $rc=$db->query($SQL);
   $numrows=$db->num_rows();
   $i=1;
   while ($i <= $numrows)
   {
    $db->next_record();
      if ($numrows and $db->Record)
      {
       $dtl[$i]=parseRcptDB($db1,$db->Record);
       if ($dtl[$i]["scan_status"] == 1) $dtl[$i]["statDesc"]="To Bin";
       else $dtl[$i]["statDesc"]="In Process";
       $batch=$dtl[$i]["batch_num"];
       if (!isset($numBatches[$batch])) $numBatches[$batch]=1;
      }
     $i++;
   } // while i < numrows
   $rdata[$ri]["numBatches"]=count($numBatches);
   $rdata[$ri]["numItems"]=count($dtl);
   $rdata[$ri]["Items"]=$dtl;
   if ($rdata[$ri]["numBatches"] < 1) unset($rdata[$ri]);
  } // end foreach rdata
 } // end count rdata > 0
 break;
 } // end getOpenBatches

case "getOpenBatches1":
 {
  /* get open batches, display by PO with detail of batches try #2*/
  $goodAction=true;
  $where="";
  $PO=$host_po_num;
  if ($PO <> "") $where.=" and host_po_num like \"{$PO}%\"\n";
  if ($vendor <> "") $where.=" and POHEADER.vendor like \"{$vendor}%\"\n";
  if ($typeSearch == "%") $typeSearch="";
  if ($typeSearch <> "") $where.=" and po_type = \"{$typeSearch}\"\n";

  $SQL=<<<SQL
select 
distinct
po_number,
host_po_num,
POHEADER.vendor,
name,
0 as qty_ord ,
0  as prevRecvd,
0 as thisRecvd ,  
0 as openQty ,
0 as Stocked

 from RCPT_SCAN A,PARTS P, POHEADER, POITEMS, ENTITY
where company = {$comp}
{$where}
 and scan_status < 2
 and shadow_number = A.shadow 
 and wms_po_num = po_number
 and poi_po_num = po_number
 and POITEMS.shadow  = A.shadow
 and host_id = vendor
 
group by host_po_num
;
SQL;

  if ($DEBUG) wr_log("/tmp/RcptLine.log",$SQL);
  $rdata=array();
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
      if (!is_numeric($key)) 
      { 
       $rdata[$i]["$key"]=$data; 
      }
     }
    }
   $i++;
  } // while i < numrows

  if (count($rdata) > 0)
  {
   $batch_search="";
   foreach ($rdata as $rkey=>$r)
   {
//    if (strpos($batch_search,$r["batch_num"]) === false)
    //{
      //if (strlen($batch_status) > 0) $batch_status.=",";
      //$batch_status.=$r["batch_num"];
      //$rdata[$rkey]["numBatches"]++;
    //}

// get sum of POITEMS qty ord, qty recvd
// the get sum of RCPT_SCAN totalQty, qty stocked
// and add that stuff to the return data

$thisR=0;
$thisS=0;
$thisI=0;
$batches="";
$SQL=<<<SQL

select 
distinct batch_num,
count(*) as Items ,  
sum(totalQty) as thisRecvd ,  
 sum(qty_stockd)   as Stocked
 from RCPT_SCAN A, POHEADER
where host_po_num = "{$r["host_po_num"]}"
 and scan_status < 2
 and wms_po_num = po_number

group by batch_num

SQL;
  
  $rc=$db->query($SQL);
  $numrows=$db->num_rows();
  $i=1;
  while ($i <= $numrows)
  {
   $db->next_record();
     if ($numrows)
     {
        $batch=$db->f("batch_num");
        $w1=$db->f("thisRecvd");
        $w2=$db->f("Stocked");
        $w3=$db->f("Items");
	$thisR=$thisR + $w1;
	$thisS=$thisS + $w2;
	$thisI=$thisI + $w3;
        if ($batches <> "") $batches.=",";
        $batches.=$batch;
        $j=substr_count($batches,",") + 1;
     }
     $i++;
   } // while i < numrows
  $rdata[$rkey]["thisRecvd"]=$thisR;
  $rdata[$rkey]["Stocked"]=$thisS;
  $rdata[$rkey]["Batches"]=$batches;
  $rdata[$rkey]["numBatches"]=$j;
  $rdata[$rkey]["lineItems"]=$thisI;
 
 $SQL=<<<SQL

select 
sum(qty_ord) as qtyOrd ,
sum(qty_recvd)  as prevRecvd
 from POHEADER A, POITEMS
where host_po_num = "{$r["host_po_num"]}"
 and poi_po_num = wms_po_num
 
SQL;
   $rc=$db->query($SQL);
  $numrows=$db->num_rows();
  $i=1;
  while ($i <= $numrows)
  {
   $db->next_record();
     if ($numrows)
     {
      $rdata[$rkey]["qty_ord"]=$db->f("qtyOrd");
      $rdata[$rkey]["prevRecvd"]=$db->f("prevRecvd");
      $rdata[$rkey]["openQty"]= $rdata[$rkey]["qty_ord"] 
			       - ($rdata[$rkey]["prevRecvd"]
                               + $rdata[$rkey]["thisRecvd"]);
     }
     $i++;
   } // while i < numrows

   } // end foreach rdata as r
  } // end count rdata > 0
  break;
 } // end getOpenBatches1

 case "addItemToPo":
 {
  $goodAction=true;
  foreach (array_keys($reqdata) as $w) { $$w=$reqdata[$w]; }
/*
Args in 
[action] => addItemToPo
   [po] => 1030
   [shadow] => 87630
    [p_l] => WIX
    [part_number] => 24006
    [uom] => EA
    [qty] => 1
    [pdesc] => Fuel Filter
    [upc] => 24006
    [mdse_price] => 6.970
    [core_price] => 0.000
    [weight] => 0.000
    [case_uom] => EA
    [case_qty] => 1

get max line# from POITEMS
insert POITEMS
update #lines in poheader

*/
 $SQL=<<<SQL
select
num_lines,
po_status
from POHEADER
where company = {$comp}
and wms_po_num = {$po}

SQL;
 $rc=$db->query($SQL);
  $numrows=$db->num_rows();
  $i=0;
  while ($i <= $numrows)
  {
   $db->next_record();
     if ($numrows and $db->Record)
     {
       $num_lines=$db->f("num_lines");
       $po_status=$db->f("po_status");
     }
    $i++;
  } // while i < numrows

 if (!isset($num_lines))
 { 
  $rdata["errCode"]=-1;
  $rdata["errText"]="PO not found";
  $rdata["errRows"]=$numrows;
  $rdata["errLines"]=$num_lines;
  $rdata["errStat"]=$po_status;
  break;
 }
 //if (isset($po_status) and $po_status > 6);
 //{
  //$rdata["errCode"]=-2;
  //$rdata["errText"]="PO is Already Closed";
  //$rdata["errRows"]=$numrows;
  //$rdata["errLines"]=$num_lines;
  //$rdata["errStat"]=$po_status;
  //break;
 //}

 $lineNum=$num_lines + 1;
 
 $sqls=array();

$sql[2]=<<<SQL
update POHEADER
set num_lines = {$lineNum}
where company = {$comp}
and wms_po_num = {$po}

SQL;
$sql[1]=<<<SQL
insert into POITEMS
(
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
line_type
)
values (
{$po},
{$lineNum},
{$shadow},
"{$p_l}",
"{$part_number}",
"{$pdesc}",
"{$uom}",
0,
0,
0,
0,
{$mdse_price},
{$core_price},
{$weight},
0.0,
"{$case_uom}",
{$case_qty},
0,
0,
"",
"",
"",
"",
"",
"0"
)
SQL;

  if ($DEBUG) wr_log($logfile,"SQL:\n{$sql[1]}");
  if ($DEBUG) wr_log($logfile,"SQL:\n{$sql[2]}");
  unset($rc);
  //Start Transaction
  $rc["start"]=$db->startTrans();
  // do transaction
  $rc["insertItem"]=$db->updTrans($sql[1]);
  $rc["updatePO"]=$db->updTrans($sql[2]);
  // commit or Rollback Transaction
  $rc["end"]=$db->endTrans($rc["insertItem"]);
  $rdata=$rc;
  break;
 } // end addItemToPo

 case "getBatchDetail":
 {
  $goodAction=true;
  $where="";
    $SQL=<<<SQL

select 
batch_num,
host_po_num,
A.shadow,
P.p_l,
P.part_number,
P.part_desc,
qty_ord ,
qty_recvd  as prevRecvd,
totalQty as thisRecvd ,  
(totalOrd - qty_recvd - totalQty) as OpenQty ,
 qty_stockd   as Stocked,
recv_to,
pack_id  as Tote
 from RCPT_SCAN A,PARTS P, POHEADER, POITEMS
where batch_num in ( {$batch} )
 and scan_status < 2
 and shadow_number = A.shadow 
 and wms_po_num = po_number
 and poi_po_num = po_number
 and POITEMS.shadow = A.shadow

 
SQL;
  if ($DEBUG) wr_log("/tmp/RcptLine.log",$SQL);
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
       if (!is_numeric($key)) { $rdata[$i]["$key"]=$data; }
      }
     }
    $i++;
   } // while i < numrows

  break;
 } // end getBatchDetail


 case "chkBatches":
 case "chkCloseBatch":
 case "chkUserBatch":
 {
  // called without POs, batch and user = 0, gets all open batches
  $goodAction=true;
  $ewhere="";
  $powhere="";
  if ($batch > 0) $ewhere=" and A.batch_num = {$batch}\n";
  if ($userId > 0) $ewhere.=" and scan_user = {$userId}\n";
  if ($host_po_num <> "") $ewhere.=" and host_po_num = \"{$host_po_num}\"\n";
  if ($typeSearch <> "") $ewhere.=" and po_type = \"{$typeSearch}\"\n";
  if ($POs <> "")
  {
   $POClause=frmtPOs4DB($POs);
   $powhere=" and po_number {$POClause}\n";
  } // end POs <> ""
  $SQL=<<<SQL
select distinct
A.batch_num , host_po_num, po_number, scan_user, user_id, batch_date, batch_type,scan_status, vendor

 from RCPT_SCAN A, RCPT_BATCH B, POHEADER
where batch_status < 1
{$powhere} {$ewhere} and B.batch_num = A.batch_num
and wms_po_num = po_number

SQL;
 if ($DEBUG) wr_log("/tmp/RcptLine.log",$SQL);
  $rdata=array();
  $rc=$db->query($SQL);
  $numrows=$db->num_rows();
  $i=1;
  while ($i <= $numrows)
  {
   $db->next_record();
     if ($numrows and $db->Record)
     {
      $rdata[$i]=parseRcptDB($db1,$db->Record);
      if ($rdata[$i]["scan_status"] == 1) $rdata[$i]["statDesc"]="To Bin";
      else $rdata[$i]["statDesc"]="In Process";
     }
    $i++;
  } // while i < numrows
  break; 
 } // end chkCloseBatch
} // end Switch action

if ($goodAction)
{ // return data not empty
  $x=json_encode($rdata);
  if ($DEBUG) wr_log("/tmp/RcptLine.log","Response:\n{$x}");
  echo $x;
} // return data not empty
else
{ // return data is empty
if ($DEBUG) wr_log("/tmp/RcptLine.log","Status: 400\n");
 header('HTTP/1.1 400 Bad Request', true, 400);
 exit("Status: 400 Bad Request");
} // return data is empty

function chkPartOnPO($db,$shadow,$POs)
{
 $poitems=array();
 if (count($POs) < 1 or $shadow < 1) return $poitems ;
 $POClause=frmtPOs4DB($POs);
 $where=<<<SQL
where poi_po_num {$POClause}
 and shadow = {$shadow}
 and poi_status < 2

SQL;
 
$SQL=<<<SQL
select
poi_po_num,
host_po_num
from POITEMS, POHEADER
{$where}
and wms_po_num = poi_po_num
 
SQL;

wr_log("/tmp/RcptLine.log","chkPartOnPO:\n{$SQL}");
$rc=$db->query($SQL);
  $numrows=$db->num_rows();
  $i=0;
  while ($i <= $numrows)
  {
   $db->next_record();
     if ($numrows and $db->Record)
     {
       $poitems[$i]["po_number"]=$db->f("poi_po_num");
       $poitems[$i]["host_po_num"]=$db->f("host_po_num");
      //foreach ($db->Record as $key=>$data)
       //{
        //if (!is_numeric($key)) { $poitems[$i]["$key"]=$data; }
       //}
     }
    $i++;
  } // while i < numrows
 $ret=array();
 $ret["numRows"]=count($poitems);
 $i=0;
 $ret["PO"]=$poitems;
 //if (count($poitems) > 0)
 //{
  //foreach($poitems as $fld=>$po) 
    //{ 
     //$ret["PO"][$i]=$po["poi_po_num"];
     //$i++;
    //}
 //}
 return $ret ;
} // end chkPartOnPO

function getHostPO($db,$wmsPO)
{
 $ret="";
 $SQL=<<<SQL
select
host_po_num
from POHEADER
where wms_po_num = "{$wmsPO}"

SQL;
 
  $rc=$db->query($SQL);
  $numrows=$db->num_rows();
  $i=1;
  while ($i <= $numrows)
  {
   $db->next_record();
     if ($numrows)
     {
     }
     $i++;
   } // while i < numrows
 return $ret ;
} // end getHostPO

function frmtPOs4DB($POs)
{
 $ret="";
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
 $ret=<<<SQL
{$wt}{$P}{$we}
SQL;

 return $ret;
} // end frmtPOs4DB

function checkTask($db,$comp,$tote_id,$task_id,$task_type,$id_num,$userId,$zone)
{
 global $DEBUG;

 $rdata=array();
  $tote_num=getToteId($tote_id,$comp);
  $where="where B.tote_company = {$comp} and A.task_status < 9";
  if (trim($tote_id) <> "") 
  {
   if (is_numeric($tote_id))
   
   $where.= ' and ( A.tote_id= ' . $tote_num . ' or tote_code = "' . $tote_id . '" )';
  else
   $where.= ' and tote_code = "' . $tote_id . '" ';
  }
  if ($task_id > 0) $where.="  and A.task_id = {$task_id}";
  if ($task_type <> "") $where.="  and A.task_type = \"{$task_type}\"";
  if ($id_num > 0) $where.="  and A.id_num = {$id_num}";
  if ($userId > 0) $where.="  and A.user_id = {$userId}";
  if ($zone <> "") $where.="  and A.last_zone = \"{$zone}\"";
  // need TOTEHDR to join company
    $SQL=<<<SQL
select
task_id,
task_type,
task_date,
task_status,
id_num,
user_id,
tote_code as tote_id,
last_zone,
last_loc,
target_zone,
target_aisle,
start_time,
end_time

from TASKS A,TOTEHDR B
{$where}
and B.tote_id = A.tote_id
order by task_id
SQL;
  if ($DEBUG) wr_log("/tmp/RcptLine.log",$SQL);
  $rdata=$db->gData($SQL);
  return $rdata;
} // end checkTask

function getToteDtl($db,$tote_id,$comp,$shadow=0,$ordBy="")
{
 // get tote contents by tote# or shadow regardless of function (RCV,PIC...)
 global $DEBUG;
 global $comp;
 $ret=array();
 $okToRun=true;
 $where="";
 if (intval($tote_id) < 1 or trim($tote_id) == "") $okToRun=false;
 else $where='where ( A.tote_id= ' . $tote_id . ' or tote_code = "' . $tote_id . '" )';
 if ($shadow > 0) 
 {
  $okToRun=true;
  $where="where tote_shadow = {$shadow}";
 }
 if ($comp < 1) $okToRun=false;
 else $where.=" and tote_company = {$comp}";

 if (!$okToRun) return $ret;

 $orderby ="Order by primary_bin, p_l, part_seq_num, part_number";
 if ($ordBy <> "")
 {
  if ($ordBy == "1") $orderby="Order by  p_l, part_seq_num, part_number, primary_bin"; 
  if ($ordBy == "2") $orderby="Order by  p_l, primary_bin,  part_seq_num, part_number"; 
 } // end ordBy <> ""

 $SQL=<<<SQL
 select
tote_code as tote_id,
tote_status,
tote_location,
DATE_FORMAT(tote_lastused,"%m/%d/%y") as tote_lastused,
num_items,
tote_type,
tote_ref,
tote_item,
shadow_number,
p_l,
part_number,
part_desc,
tote_qty,
tote_uom,
qty_avail as totalQty,
primary_bin
from TOTEHDR A, TOTEDTL B, PARTS, WHSEQTY
{$where}
and B.tote_id = A.tote_id
and shadow_number = tote_shadow
and ms_company = tote_company
and ms_shadow = tote_shadow

{$orderby}
SQL;

 if ($DEBUG) wr_log("/tmp/RcptLine.log",$SQL);
 $ret=$db->gData($SQL);
 $ret["numRows"]=$db->NumRows;
 if ($ret["numRows"] > 0) foreach($ret as $rec=>$d)
 {
  if (trim($d["primary_bin"]) == "")
  { // get prodline pref zone/aisle
   $SQL=<<<SQL
select pl_perfered_zone , pl_perfered_aisle
from PRODLINE
where pl_company = {$comp}
and pl_code = "{$d["p_l"]}"
SQL;
  $rc=$db->query($SQL);
  $numrows=$db->num_rows();
  $i=1;
  while ($i <= $numrows)
  {
   $db->next_record();
     if ($numrows)
     {
        $ret[$rec]["primary_bin"]="_" . $db->f("pl_perfered_zone") . "_" . 
        $db->f("pl_perfered_aisle");
        //sprintf("%02d",$db->f("pl_perfered_aisle"));
     }
     $i++;
   } // while i < numrows
  } // get prodline pref zone/aisle
 } // end foreach ret
 return $ret;
} // end getToteDtl

function parseRcptDB($db,$record)
{ // parse scan rcpt data record (used is several spots)
  $rdata=array();
 foreach ($record as $key=>$data)
 {
  if ($key == 'batch_date')
  {
   $rdata["batch_date"]=eur_to_usa($data,true);
  }
  else if (!is_numeric($key)) { $rdata["$key"]=$data; }
  if ($key == "scan_user")
   {
    $rdata["userName"]=getUser($db,$data);
    $tpo=$rdata["po_number"];
    $tbatch=$rdata["batch_num"];
    $tmp=sumPO($db,$tpo,$tbatch);
    $rdata["QtyOrd"]=$tmp["qOrd"];
    $rdata["totalRecvd"]=$tmp["qRec"];
    $rdata["qtyStocked"]=$tmp["sQty"];
    $rdata["inProcessRecvd"]=$tmp["cRec"];
    $rdata["num_lines"]=$tmp["lineCount"];
   }
 } // end foreach record
 return $rdata;
} // end parseRcptDB

 function ldata($db,$SQL,$fld)
 {
  $rc=$db->query($SQL);
  $numrows=$db->num_rows();
  $i=1;
  while ($i <= $numrows)
  {
   $db->next_record();
     if ($numrows)
     {
        $ret=$db->f($fld);
     }
     $i++;
   } // while i < numrows
  return $ret;
 } // end ldata
?>
