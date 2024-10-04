<?php
/* 
  whse_srv.php - serve up whse and part info
  09/28/22 dse add validateBin
  02/24/23 dse add getToteDtl

*/
$DEBUG=true;
require("srv_hdr.php");
require("getToteId.php");

$wmsDir="..";
$wmsInclude="../include";
$thisprogram=basename($_SERVER["PHP_SELF"]);
require("{$wmsDir}/config.php");
require_once("{$wmsInclude}/cl_PARTS2.php");
require_once("{$wmsInclude}/cl_bins.php");
require_once("{$wmsInclude}/cl_inv.php");
require_once("{$wmsInclude}/cl_TOTES.php");
require_once("{$wmsInclude}/db_main.php");
require_once("{$wmsInclude}/get_contrl.php");
require_once("{$wmsInclude}/getOneField.php");

$db=new WMS_DB;

if (isset($_REQUEST["searcH"])) $srch=$_REQUEST["searcH"]; else $srch="";
$comp=0;
if ($srch <> "") $comp=intval($srch);
if ($DEBUG) wr_log("/tmp/whse_srv.log","inputData={$inputdata}");
$action=$reqdata["action"];
if (isset($reqdata["comp"])) $comp=$reqdata["comp"]; else $comp=1;
if (isset($reqdata["tote_company"])) $comp=$reqdata["tote_company"]; else $comp=1;
if (isset($reqdata["ctrlKey"])) $ctrlKey=$reqdata["ctrlKey"]; else $ctrlKey="";
if (isset($reqdata["ctrlComp"])) $ctrlComp=$reqdata["ctrlComp"]; else $ctrlComp=0;
if (isset($reqdata["partNumber"])) $partNumber=$reqdata["partNumber"]; else $partNumber="";
if (isset($reqdata["Search"])) $Search=$reqdata["Search"]; else $Search="";
if (isset($reqdata["showBins"])) $showBins=$reqdata["showBins"]; else $showBins=false;
if (isset($reqdata["showTotes"])) $showTotes=$reqdata["showTotes"]; else $showTotes=false;
if (isset($reqdata["bin"])) $bin=$reqdata["bin"]; else $bin="";
if (isset($reqdata["useWild"])) $useWild=$reqdata["useWild"]; else $useWild="";
if ($DEBUG) wr_log("/tmp/whse_srv.log","Switching={$action}");
switch ($action)
{

 case "chkBin": // Check if bin exists in WHSEBINS
 {
  $rdata=array("binStat"=>false);
  if ($bin <> "" and $comp > 0)
  {
   $SQL=<<<SQL
   select * from WHSEBINS 
  where wb_company = {$comp}
  and wb_location = "{$bin}"

SQL;
   $ret=$db->gData($SQL);
   $rdata["binStat"]=$db->NumRows;
  } // end bin is set and comp > 0
  if (isset($x)) unset($x);
  $x=json_encode($rdata);
  if ($DEBUG) wr_log("/tmp/whse_srv.log",$x);
  echo $x;
  break;
 } // end chkBin

 case "getPart": // get Part by alternat number
  {
   if (trim($partNumber) <> "")
   {
    $pr=new PARTS;
    $pnum=trim(strtoupper($partNumber));
    $rdata=$pr->chkPart($pnum,$comp);
    $shadow_number = 0;
    if (isset($rdata["Result"]["shadow_number"])) $shadow_number=$rdata["Result"]["shadow_number"];
    if ($showTotes)
    {
     $tmp=$pr->TOTESelect($shadow_number,$comp);
     $rdata["Totes"]=$tmp;
    } // end showTotes
    if (isset($x)) unset($x);
    $x=json_encode($rdata);
    if ($DEBUG) wr_log("/tmp/whse_srv.log",$x);
    echo $x;
   } // end partNUmber <> ""
    break;
  } // end getPart

 case "getReasons":
 {
  $where="";
  if ($Search <> "") 
  {
   if ($Search == "ADJ") $S="Adjustment"; else $S="%Count%";
   $where="where host_reason like '{$S}'";
  }
  $SQL=<<<SQL
select * from REASONS
{$where}

SQL;
  $rdata=$db->gData($SQL);
  if (isset($x)) unset($x);
  $x=json_encode($rdata);
  if ($DEBUG) wr_log("/tmp/whse_srv.log",$x);
  echo $x;
  break;
 } // end get Reasons

 case "getBin":
 {
  $bincls=new BIN;
  $theBin=trim(strtoupper($bin));
  $useWild=""; 
  if (strpos($theBin,"%") !== false) $useWild=1;
  $binParts=$bincls->getLoc($comp,$theBin,$useWild);
  $binInfo=$bincls->getBinInfo($comp,$theBin);
  $numBins=$binInfo["numRows"];
  $rdata=$binInfo;
  $rdata["numParts"]=$binParts["numRows"];
  unset($binParts["numRows"]);
  $rdata["Parts"]=$binParts;

  if (isset($x)) unset($x);
  $x=json_encode($rdata);
  if ($DEBUG) wr_log("/tmp/whse_srv.log",$x);
  echo $x;
  break;
 } // end getBin

 case "getOpenInvBatches": // get Part by alternat number
 {
  $rdata=array();
  if ($comp > 0)
  {
   $SQL=<<<SQL
select count_num,
	company,
	create_by,
        DATE_FORMAT(create_date, "%m/%d/%Y") as create_date,
        DATE_FORMAT(due_date, "%m/%d/%Y") as due_date,
	count_status,
	count_type
from INV_BATCH
where company = {$comp}

SQL;
   $rdata=$db->gData($SQL);
  }
  if (isset($x)) unset($x);
  $x=json_encode($rdata);
  if ($DEBUG) wr_log("/tmp/whse_srv.log",$x);
  echo $x;
  break;
 } // end getBin
 case "validateBin":
 {
  $bincls=new BIN;
  $theBin=trim(strtoupper($bin));
  $rdata=$bincls->getBinInfo($comp,$theBin);
  if (isset($x)) unset($x);
  $x=json_encode($rdata);
  if ($DEBUG) wr_log("/tmp/whse_srv.log",$x);
  echo $x;
  break;
 } // end validateBin

 case "movePart":
 {
  /* movepart
  input fields;
    [action] => movePart
    [comp] => 1
    [shadow] => 88136
    [qty] => 1
    [sourceBin] => !158
    [destBin] => A-02-02-C
    [po] => 12345 // set if putaway

    if source to dest bin starts with a !, it is a tote instead of a bin.

    if source is a bin,
    subtract qty from source location and add to new loc. 
    if new location does not exist (on a tote add tote dtl and set tote hdr to mov)
    if a bin, add or update that shadow for that location.
    if old bin was primary and has no stock, set new loc to primary.
    if the old loc now has zero, and it's not the primary, delete the record.
   
  */
  if (!isset($reqdata)) exit;
  foreach (array_keys($reqdata) as $w) { $$w=$reqdata[$w]; }
  $ok=0;
  if (!isset($shadow)) $ok=1;
  if (!isset($comp)) $ok=2;
  if (!isset($qty)) $ok=3;
  if (!isset($sourceBin)) $ok=4;
  if (!isset($destBin)) $ok=5;
  if (!isset($userId)) $ok=6;
  if ($ok < 1 and $sourceBin == $destBin) $ok=7;
  if ($ok == 0)
  { // all good, we can move it
   //Load part
   $pr=new PARTS;
   $pr->Load($shadow,$comp);
   $part=array();
   $stat=$pr->status;
   if ($stat == 1) // found 1 part
   {
    $part["status"]=$stat;
    $part["p_l"]=$pr->p_l;
    $part["part_number"]=$pr->part_number;
    $part["shadow_number"]=$pr->shadow_number;
    $part["primary_bin"]=$pr->WHSEQTY[$comp]["primary_bin"];
    $part["qty_avail"]=$pr->WHSEQTY[$comp]["qty_avail"];
    $part["WHSELOC"]=array();
    if (count($pr->WHSELOC) > 0)
    {
      $part["WHSELOC"]=$pr->WHSELOC;
      //foreach($pr->WHSELOC as $c=>$l) { $part["WHSELOC"][$c]=$l; }
    }
   } // end found 1 part
  
   $fromTo=array();
   $fromTo["comp"]=$comp;
   $fromTo["shadow"]=$shadow;
   $fromTo["qty"]=$qty;
   $fromTo["userId"]=$userId;
   if (isset($updWhseQty)) $fromTo["updWhseQty"]=$updWhseQty;
   if (isset($po))
   {
     $tpo=$po;
     if (is_array($tpo)) $tpo=array_shift($tpo);
     $fromTo["po"]=$tpo;
     $fromTo["qty_avail"]=$part["qty_avail"];
   }
   $idx="from";
   for($i=0;$i<2;$i++)
   {
    if ($i > 0) 
    {
     $idx="to";
     $theBin=$destBin; 
    }
    else 
    {
     $idx="from";
     $theBin=$sourceBin;
    }
//Loop this to get source and dest 
   //Load source bin or tote 
   if (substr($theBin,0,1) == "!")
    { // its a tote
     $theBin=substr($theBin,1);
     $totecls=new TOTE;
     $tmp=$totecls->getToteHdr($theBin,$comp);
     if (count($tmp) > 0) 
     {
      $fromTo[$idx]["type"]="T";
      $fromTo[$idx]["Bin"]=$theBin;
     } 
    } // its a tote
   else
    { // its a bin
     $bincls=new BIN;
     $tmp=$bincls->getBinInfo($comp,$theBin);
     if (count($tmp) > 0) 
     {
      $fromTo[$idx]["type"]="B";
      $fromTo[$idx]["Bin"]=$theBin;
     } 
    } // its a bin
   } // end i loop
  
   if ($fromTo["to"]["type"] == "T")
   { // set tote type to move and update last location
   } // set tote type to move and update last location
    
//if (count($fromTo)== 5) $part["fromTo"]=$fromTo;
 if (isset($fromTo["shadow"]) 
  and isset($fromTo["comp"]) 
  and isset($fromTo["qty"]) 
  and isset($fromTo["userId"]) 
  and isset($fromTo["from"]) 
  and isset($fromTo["to"])) $part["fromTo"]=$fromTo;

//echo "part=";
//print_r($part);
   $trans=new invUpdate;
   $rc=$trans->moveQty($fromTo);
  if ($DEBUG) wr_log("/tmp/whse_srv.log","count(rc)=" . count($rc));
   $j2=0;
   if (is_array($rc) and count($rc) > 0)
   {
    foreach ($rc  as $workey=>$dd)
    if (is_numeric($workey))
    {
     $j2=$j2 + intval($dd);
    }
   }
   if ($j2 > 0) $rdata["Status"]="OK"; else $rdata["Status"]="Error Moving Part {$j2}";
  } // all good, we can move it
 else
  { // ok > 0, return error
   $rdata=array();
   $rdata["errNo"]= -$ok;
   $rdata["errText"]= "Error";
   switch ($ok)
   {
    case 1: 
     $rdata["errText"]="Part Number not set";
     break;
    case 2: 
     $rdata["errText"]="Company Number not set";
     break;
    case 3: 
     $rdata["errText"]="Quantity not set";
     break;
    case 4: 
     $rdata["errText"]="Source Location not set";
     break;
    case 5: 
     $rdata["errText"]="Destination Location not set";
     break;
    case 6: 
     $rdata["errText"]="User Id not set";
     break;
    case 7: 
     $rdata["errText"]="Source and Destination Locations are the same";
     break;
   } // end switch ok block 
  } // ok > 0, return error
  if (isset($x)) unset($x);
  $x=json_encode($rdata);
  if ($DEBUG) wr_log("/tmp/whse_srv.log",$x);
  echo $x;
  break;
 } // end movePart

 case "findIt":
 {
  $foundIt=false;
  if (trim($Search) <> "")
  {
   $srchStr=trim(strtoupper($Search));
   $pr=new PARTS;
   // check if scanned is a part
   $pnum=$srchStr;
   $rdata=$pr->chkPart($pnum,$comp);
   $numParts=$rdata["status"];
   if ($numParts == 1)
   {
    $shadow_number = 0;
    if (isset($rdata["Result"]["shadow_number"]))
    { // we found a part
     $shadow=$rdata["Result"]["shadow_number"];
     $foundIt=true;
     $rdata["infoType"]="P";
     $tmp=$pr->TOTESelect($shadow,$comp);
     $rdata["Totes"]=$tmp;
    } // we found a part
   } // end numParts = 1
   if ($numParts > 1)
   {
     $foundIt=true;
     $rdata["infoType"]="P";
   } // end numParts > 1

   
   // not foundIt #1, not a part, check if it's a bin
   if (!$foundIt)
   {
    $theBin=$srchStr;
    $bincls=new BIN;
    $rdata=$bincls->getBinInfo($comp,$theBin);
    if ($rdata["numRows"] > 0)
    {
      $foundIt=true;
      $rdata["infoType"]="B";
    } // end numRows > 0
   } // end not foundIt #1
 
   // not foundIt #2, not a part or bin, check if it is a tote
   if (!$foundIt)
   {
    $totecls=new TOTE;
    $theBin=$srchStr;
    $ttheBin=$theBin;
    if (substr($ttheBin,0,1) == "!") $ttheBin=substr($theBin,1);
    $rdata=$totecls->getToteHdr($ttheBin,$comp);
    if (isset($rdata["tote_ref"]))
    {
     $rdata["host_ref"]=setDocNum($db,$rdata["tote_type"],$rdata["tote_ref"]);
    }
    $numRows=$totecls->numRows;
    if ($totecls->numRows > 0)
    {
     $foundIt=true;
     $rdata["toteDtl"]=getToteDtl($db,$ttheBin,$comp,0);
     if (count($rdata["toteDtl"])> 0)
    {
     foreach ($rdata["toteDtl"]  as $key=>$t)
     {
      if ($key <> "numRows")
      $rdata["toteDtl"][$key]["host_ref"]=setDocNum($db,$t["tote_type"],$t["tote_ref"]);
     }
    }

     $rdata["infoType"]="T";
    } // end numRows > 0
   } // end not foundIt #2

   // not foundIt #3, not a part, bin or tote, check if it is a area
   // not foundIt #4, not a part, bin tote or area, check if it is a document
   // documents could be PO, Order, Container, ?

   } // end $Search is not ""
  if (!isset($rdata["infoType"])) 
  {
   $rdata["infoType"]="NF";
   $rdata["nunmRows"]=0;
  }
  if (isset($x)) unset($x);
  $x=json_encode($rdata);
  if ($DEBUG) wr_log("/tmp/whse_srv.log",$x);
  echo $x;
  break;
 } // end findIt

 case "getControlNum":
 {
  $rdata=array();
  // requires ctrlKey, ctrlComp defaults to 0
  if ($ctrlKey <> "")
  {
   $rdata["controlNum"]=get_contrl($db,$ctrlComp,$ctrlKey);
  } // end ctrlKey <> ""
  if (!isset($rdata["controlNum"]) ) $rdata["controlNum"]=0;
  if (intval($rdata["controlNum"]) > 0) $rdata["numRows"]=1;
  else $rdata["numRows"]=0;
  
  if (isset($x)) unset($x);
  $x=json_encode($rdata);
  if ($DEBUG) wr_log("/tmp/whse_srv.log",$x);
  echo $x;
  break;
 } // end getControlNum

} // end switch action

function chk_container($db,$container)
{
// Needs work, neeed a way to distingish between a tote and container
 $ret=array();
 $ret["status"]=-1;
 if (!is_numeric($container)) $ret["status"]=-2;
 if ($container > 0)
 {
 $SQL=<<<SQL
select
carton_num,
A.order_num,
customer_id,
entity_type,
name,
DATE_FORMAT(enter_date,"%m/%d/%Y") as enter_date,
line_num,
p_l,
part_number,
qty,
uom
from ORDPACK A, PARTS, ORDERS B, ENTITY
where carton_num = {$container}
and shadow_number = shadow
and B.order_num = A.order_num
and host_id = customer_id

SQL;
  $ret=$db->gData($SQL);
 } // end container > 0
 return $ret;
} // end chk_container

function getToteDtl($db,$tote_id,$comp,$shadow=0,$ordBy="")
{
 // get tote contents by tote# or shadow regardless of function (RCV,PIC...)
 global $DEBUG;
 global $comp;
 $ret=array();
 $okToRun=true;
 $where="";
 $tote_num=getToteId($tote_id,$comp);
   if (trim($tote_id) <> "")
  {
   if (is_numeric($tote_id))

   $where.= ' and ( A.tote_id= ' . $tote_num . ' or tote_code = "' . $tote_id . '" )';
  else
   $where.= ' and tote_code = "' . $tote_id . '" ';
  }

 if ($shadow > 0)
 {
  $okToRun=true;
  $where="and tote_shadow = {$shadow}";
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
p_l,
part_number,
part_desc,
tote_qty,
tote_uom,
qty_avail as totalQty,
primary_bin
from TOTEHDR A, TOTEDTL B, PARTS, WHSEQTY
where B.tote_id = A.tote_id
{$where}
and shadow_number = tote_shadow
and ms_company = tote_company
and ms_shadow = tote_shadow

{$orderby}
SQL;

 if ($DEBUG) wr_log("/tmp/whse_srv.log",$SQL);
 $ret=$db->gData($SQL);
 $ret["numRows"]=$db->NumRows;
 return $ret;
} // end getToteDtl


function setDocNum($db,$tote_type,$tote_ref)
{
 $ret=$tote_ref;
 switch($tote_type)
 {
  case "PIC":
   $SQL=<<<SQL
select host_order_num as tote_ref from ORDERS where order_num = "{$tote_ref}"
SQL;
   break;
  case "RCV":
  case "PUT":
   $SQL=<<<SQL
select host_po_num as tote_ref from POHEADER where wms_po_num = "{$tote_ref}"
SQL;
  break;
  default:
 $SQL="";
 } // end switch type
 $w=getOneField($db,$SQL,"tote_ref");
 if ($w <> "") $ret=$w;
 return $ret;
} // end setDocNum
function getToteHostId($db,$tote)
{
  // tote is an array of a TOTEHDR record
   $f=array();
   $f["O"][0]="ORDERS";
   $f["O"][1]="host_order_num";
   $f["O"][2]="order_num";
   $f["P"][0]="POHEADER";
   $f["P"][1]="host_po_num";
   $f["P"][2]="wms_po_num";
   foreach ($tote as $key=>$d)
   {
    $t=($d["tote_type"] == "PIC") ? "O":"P";
   }
    $SQL=<<<SQL
select {$f[$t][1]} from {$f[$t][0]} where {$f[$t][2]} = "{$d["tote_ref"]}"

SQL;
   $rc=$db->query($SQL);
   $numrows=$db->num_rows();
   $db->next_record();

   if (isset($tote["HostId"])) $tote["hostId"]=$db->f($f[$t][1]);
   else $tote["HostId"]="N/A";
   return $tote;

} // end getToteHostId
?>
