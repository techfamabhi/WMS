<?php

// PICK.php -- Server for PICK.php
//03/11/22 dse initial
//03/15/22 dse add order to pick
//05/03/22 dse correct totedtl return
//05/30/22 modify ITEMPULL to add tote_id, then change updPickQty to insert tote_id where it updates the qty_picked
//05/30/22 dse correct LineTote return
//01/05/23 dse add skipLines
//08/16/23 dse add check of ok to release order to host (checkRel2Host)
//09/14/23 dse Allow pick zone to be % for all zones
//02/05/24 dse Allow order to be array for getPick and fetchPickOrder

// TODO


$DEBUG=true;
require("srv_hdr.php");
require("getToteId.php");
$wmsInclude="../include";
require_once("{$wmsInclude}/cl_PARTS2.php");
require_once("{$wmsInclude}/cl_ORDERS.php");
$db1=new WMS_DB;

if (isset($_REQUEST["searcH"])) $srch=$_REQUEST["searcH"]; else $srch="";
if ($DEBUG) wr_log("/tmp/PICK.log","inputData={$inputdata}");
$action=$reqdata["action"];
if (isset($reqdata["scaninput"])) $scaninput=$reqdata["scaninput"]; else $scaninput="";
if (isset($reqdata["company"])) $comp=$reqdata["company"]; else $comp=1;
if (isset($reqdata["case"])) $case=$reqdata["case"]; else $case="";
if (isset($reqdata["custname"])) $custname=$reqdata["custname"]; else $custname="";
if (isset($reqdata["order_num"])) $order_num=$reqdata["order_num"]; else $order_num="";
if (isset($reqdata["process"])) $process=$reqdata["process"]; else $process="";
if (isset($reqdata["line_num"])) $line_num=$reqdata["line_num"]; else $line_num="";
if (isset($reqdata["partNumber"])) $partNumber=$reqdata["partNumber"]; else $partNumber="";
if (isset($reqdata["updqty"])) $updqty=$reqdata["updqty"]; else $updqty=0;
if (isset($reqdata["pull_num"])) $pull_num=$reqdata["pull_num"]; else $pull_num="";
if (isset($reqdata["user_id"])) $user_id=$reqdata["user_id"]; else $user_id=0;
if (isset($reqdata["shadow"])) $shadow=$reqdata["shadow"]; else $shadow="";
if (isset($reqdata["qtyPicked"])) $qtyPicked=$reqdata["qtyPicked"]; else $qtyPicked="";
if (isset($reqdata["uom"])) $uom=$reqdata["uom"]; else $uom="";
if (isset($reqdata["p_l"])) $p_l=$reqdata["p_l"]; else $p_l="";
if (isset($reqdata["part_number"])) $part_number=$reqdata["part_number"]; else $part_number="";
if (isset($reqdata["picked"])) $picked=$reqdata["picked"]; else $picked="";
if (isset($reqdata["pickuom"])) $pickuom=$reqdata["pickuom"]; else $pickuom="";
if (isset($reqdata["host_order_num"])) $host_order_num=$reqdata["host_order_num"]; else $host_order_num="";
if (isset($reqdata["order_type"])) $order_type=$reqdata["order_type"]; else $order_type="";
if (isset($reqdata["skipLines"])) $skipLines=$reqdata["skipLines"];
if (isset($reqdata["priority"])) $priority=$reqdata["priority"]; else $priority="";
if (isset($reqdata["ship_via"])) $ship_via=$reqdata["ship_via"]; else $ship_via="";
if (isset($reqdata["order_stat"])) $order_stat=$reqdata["order_stat"]; else $order_stat="";
if (isset($reqdata["sortby"])) $sortby=$reqdata["sortby"]; else $sortby=array();
if (isset($reqdata["statRange"])) $statRange=$reqdata["statRange"]; else $statRange="-1|1";
if (isset($reqdata["zone"])) $zone=$reqdata["zone"]; else $zone="";
if (isset($reqdata["zones"])) $zones=$reqdata["zones"]; else $zones=array();
if (isset($reqdata["tote_id"])) $tote_code=$reqdata["tote_id"]; 
 else if (isset($reqdata["tote_code"])) $tote_code=$reqdata["tote_code"]; 
 else $tote_code="";
if (isset($reqdata["totes"])) $totes=$reqdata["totes"]; else $totes="";
if (isset($reqdata["whseLoc"])) $whseLoc=$reqdata["whseLoc"]; else $whseLoc="";
if (isset($reqdata["origBin"])) $origBin=$reqdata["origBin"]; else $origBin="";
if (isset($reqdata["newBin"])) $newBin=$reqdata["newBin"]; else $newBin="";
if (isset($reqdata["qty"])) $qty=$reqdata["qty"]; else $qty="";

if (isset($reqdata["numRows"])) $numRows=$reqdata["numRows"]; else $numRows=10;
if (isset($reqdata["startRec"])) $startRec=$reqdata["startRec"]; else $startRec=0;
if ($srch <> "") $comp=intval($srch);

// set table def and select and update fields
//$uFlds=setFldDef($db,$update_table);
//if ($query_table == $update_table) { $qFlds=$uFlds; }
 //else                              { $qFlds=setFldDef($db,$query_table); }

//$upd_flds=setFlds($db,$uFlds);
//$sel_flds=setFlds($db,$qFlds);

if ($DEBUG) wr_log("/tmp/PICK.log","Switching={$action}");
$rdata=array();
switch ($action)
{
 case "fetchOrder": // fetch open orders for packing
 {
  $lookupBy=0;
  $awhere="";
  if ($scaninput <> "")
  { // check either host order# or tote id scanned
     // check host order#
    $SQL=<<<SQL
select order_num from ORDERS
where company = {$comp}
and host_order_num = "{$scaninput}"

SQL;
   $order_num=gorderNum($db,$SQL);
   $tote_id=getToteId($scaninput);
   if ($order_num > 0) $lookupBy=1;
   if ($order_num < 1)
   { // check by tote
   $SQL=<<<SQL
 select order_num from ORDTOTE
 where tote_id = {$tote_id}

SQL;
   $order_num=gorderNum($db,$SQL);
   if ($order_num > 0) $lookupBy=2;
   } // check by tote
  } // check either host order# or tote id scanned
  else
  {
  $lookByOrder=true;
  if ($order_num <> "")       $awhere.="and order_num = \"{$order_num}\"\n";
  if ($host_order_num <> "")  $awhere.="and host_order_num = \"{$host_order_num}\"\n";
  if (!isset($tote_id)) $tote_id=0;
  if ($tote_id > 0)
  {
   $lookByOrder=false;
   $awhere="where tote_id = {$tote_id}\n";
  }

  if ($lookByOrder)
  {
   $SQL=<<<SQL
select order_num from ORDERS
where company = {$comp}
{$awhere}
SQL;
  } // end lookByOrder
 else
  {
   $SQL=<<<SQL
 select order_num from ORDTOTE
{$awhere}
 
SQL;
  } // look by tote
  if ($DEBUG) wr_log("/tmp/PICK.log",$SQL);

  $rc=$db->query($SQL);
  $numrows=$db->num_rows();
  $i=1;
  while ($i <= $numrows)
  {
   $db->next_record();
     if ($numrows)
     {
        $order_num=$db->f("order_num");
     }
     $i++;
   } // while i < numrows
  } // scaninput == ""
   $ret=array();
   if ($order_num > 0)
    {
     $ord=getOrd($db,$order_num);
     $lines=getItems($db,$order_num,0);
     $totes=getTotes($db,$order_num);
     $open=countOpenItems($db,$order_num);
     $picked=countPickedItems($db,$order_num,$lines);
     $ret=array();
     $ret["by"]=$lookupBy;
     $ret["Order"]=$ord;
     //$ret["Items"]=$lines;
     $ret["Items"]=$picked["Items"];
     $ret["Totes"]=$totes;
     $ret["unPicked"]=$open;
     $ret["qtyOrd"]=$picked["Totals"]["qtyOrd"];
     $ret["qty2Ship"]=$picked["Totals"]["qty2Ship"];
     $ret["qty2Pick"]=$picked["Totals"]["qty2Pick"];
     $ret["qtyPicked"]=$picked["Totals"]["qtyPicked"];
     $ret["LineTote"]=array();
     if ($lookupBy == 2 and $scaninput <> "" and $process == "PACK")
     { // packing scanned this tote, set location to PACK
      $rc=updOrdTote($db,$order_num,$tote_id," ","PACK");
     } // packing scanned this tote, set location to PACK
     if (count($totes) > 0)
     {
      $i=1;
      foreach ($totes as $key=>$t)
      {
       $tote_num=$t["tote_num"];
       $tmp=getToteDtl($db,$order_num,$tote_num);
       if (isset($tmp[1]) and count($tmp[1]) > 0)
       {
        foreach ($tmp as $kkey=>$ttt)
        {
         $ret["LineTote"][$i]=$ttt;
        } // end foreach tmp
       } // end totedetal isset tmp[1]
      
      
       $i++;  
      } // end foreach tote
     } // end count totes > 1
    }
  if (isset($x)) unset($x);
  $x=json_encode($ret);
  if ($DEBUG) wr_log("/tmp/PICK.log",$x);
  echo $x;
   break;

 } // end fetchOrder

 case "fetchOrder1": // fetch full orders 
 {
  if ($host_order_num <> "")
  {
   $rdata=array();
      $SQL=<<<SQL
select order_num from ORDERS
where company = {$comp}
and host_order_num = "{$host_order_num}"

SQL;
   $order_num=gorderNum($db,$SQL);

  $ord=new ORDERS;
  $ord->load($order_num);
  $rdata=$ord;
  } // end host order num is set
  if (isset($x)) unset($x);
  $x=json_encode($rdata);
  if ($DEBUG) wr_log("/tmp/PICK.log",$x);
  echo $x;
   break;
 } // end fetchOrder1
 case "getToteDetail":
 {
  $ret=array();
  $tote_id=getToteId($tote_code);
  $tmp=getTotes($db,$order_num,$tote_id);
  if (isset($tmp[1])) $ret["Order"]=$tmp[1]; else $ret["Order"]=array();
  $ret["Tote"]=getToteDtl($db,$order_num,$tote_id);
  if (isset($x)) unset($x);
  $x=json_encode($ret);
  if ($DEBUG) wr_log("/tmp/PICK.log",$x);
  echo $x;
   break;
 } // end getToteDetail

 case "fetchall":
 case "fetchAll":
 {
  $awhere="";
  if ($custname <> "")        $awhere.="and CUSTOMERS.name like \"{$custname}%\"\n";
  if ($order_num <> "")       $awhere.="and order_num = \"{$order_num}\"\n";
  if ($host_order_num <> "")  $awhere.="and host_order_num = \"{$host_order_num}\"\n";
  if ($order_type <> "")      $awhere.="and order_type = \"{$order_type}\"\n";
  if ($priority <> "")        $awhere.="and priority = {$priority}\n"; 
  if ($ship_via <> "")        $awhere.="and ship_via = \"{$ship_via}\"\n";
  if ($order_stat <> "")      $awhere.="and order_stat = {$order_stat}\n"; 
  $ob=0;
  if ($statRange <> "" and $action <> "fetchOrder")
  { 
   $w=explode("|",$statRange);
   if ($w[0] == 4 and $w[1] == 9) $ob=3;
   if ($w[0] == 1 and $w[1] == 1)
   { // looking for open picks
    $ob=1;
    $awhere.=<<<SQL
 and ( select count(*) from ITEMPULL
  where ord_num = ORDERS.order_num
 -- and zpuser < 1
 and qty_picked < qtytopick) > 0

SQL;
   } // looking for open picks
   else 
    {
     $awhere.="and (order_stat between {$w[0]} and {$w[1]}"; 
     if ((2 >= $w[0]) && (2 <= $w[1])) $awhere.=" or order_stat = -2\n)";  else $awhere.=")\n";
    }
  }
   
  if (!empty($zones))
  {
   $z2=false;
   foreach($zones as $z)
   {
    if ($z2) $z1.=" or zones like \"%{$z}%\"";
    else $z1=" and (zones like \"%{$z}%\"";
    $z2=true;
   }
   $awhere.="{$z1})\n";
  } // end zones not empty

  $SQL=<<<SQL
    select count(*) as cnt from ORDERS
    where company = {$comp}
    {$awhere}
SQL;
  
  if ($DEBUG) wr_log("/tmp/PICK.log",$SQL);
  $rc=$db->query($SQL);
  $numrows=$db->num_rows();
  $i=1;
  while ($i <= $numrows)
  {
   $db->next_record();
     if ($numrows)
     {
          $rowCount=$db->f("cnt");
     }
     $i++;
   } // while i < numrows

  $where="";
  $order_by="order by wms_date,order_num";
  if ($ob > 0) $order_by="order by order_stat desc, wms_date,order_num";
  //if ($ob > 2) $order_by="order by order_stat desc,pic_done desc, wms_date,order_num";
  if ($ob > 2) $order_by="order by wms_complete desc,order_stat desc,pic_done desc, wms_date,order_num";
  if (isset($sortby) and count($sortby) > 0)
  {
   $order_by="order by ";
   $comma="";
   $f1=$sortby[0];
   $d1=$sortby[1];
   if ($d1 == "asc") $d1="";
   $order_by.="{$comma}{$f1} {$d1}";
   $comma=",";
  }

//create a Cursor
$SQL=<<<SQL
 select company, order_num, host_order_num, order_type, order_stat, priority,
num_lines,
date_required,
wms_date,
pic_done,
enter_date,
wms_complete,
enter_by,
ship_complete,
ORDERS.ship_via,
cust_po_num,
customer_id,
name,
addr1,
addr2,
city,
state,
zip,
ctry,
phone,
mdse_type,
drop_ship_flag,
zones,
special_instr,
shipping_instr
FROM ORDERS,CUSTOMERS
where company = :COMP
and customer = customer_id
    {$awhere}
{$order_by}

SQL;

  if ($DEBUG) wr_log("/tmp/PICK.log",$SQL);
$rdata["rowFrom"]=0;
$rdata["rowThru"]=0;
$rdata["rowCount"]=$rowCount;
//Ony can get 1 param to bind on the create cursor function
$params=array(":COMP" => "{$comp}");
if ($startRec==0) $startRec=1;

  $sth=$db->create_cursor($SQL,$params);
  if ($sth==true)
  { //SQL ok
   $numrows=0;
   $rec_count=0;
   $rowsReturned=0;
   $i=0;
   while ( $results=$db->curfetch())
   {
    $rec_count++;
    if ($rec_count >= $startRec)
    {
     if ($rdata["rowFrom"] == 0) $rdata["rowFrom"]=$rec_count;
     $rdata["rowData"][$rowsReturned]=$results;
     $sdesc=setStat($rdata["rowData"][$rowsReturned]["order_stat"]);
     $o=$rdata["rowData"][$rowsReturned]["order_num"];
     $SQL=<<<SQL
select count(*) as cnt from ITEMS where ord_num = {$o}
SQL;
     $rdata["rowData"][$rowsReturned]["lines"]=loadCnt($db1,$SQL);
     if (strpos($rdata["rowData"][$rowsReturned]["zones"],",") 
      and abs($rdata["rowData"][$rowsReturned]["order_stat"]) == 2)
     {
      $zz="";
      $zs=0;
      $j=explode(",",$rdata["rowData"][$rowsReturned]["zones"]);
      $comma="";
      foreach ($j as $j1)
      {
       $SQL=<<<SQL
select count(*) as cnt from ITEMPULL
  where ord_num = {$o}
 and zone like "{$j1}"
 and qty_picked < qtytopick
SQL;
       $rc1=$db1->query($SQL);
       $cnt=$db1->Row[0]["cnt"];
       $SQL=<<<SQL
select count(*) as cnt from ITEMPULL
  where ord_num = {$o}
 and zone like "{$j1}"
 and qty_picked > qtytopick
SQL;
       $rc1=$db1->query($SQL);
       $cnt1=$db1->Row[0]["cnt"];
       if ($cnt == 0 and $cnt1 == 0) continue;
       if ($cnt > 0 and $cnt1 < 1)
       {
        $zz.="{$comma}{$j1}";
        $comma=",";
        $zs++;
       }
 
      } // end foreach j
      $s="";
      if ($zs > 1) $s="s";
      $rdata["rowData"][$rowsReturned]["order_stat"]=1;
      $sdesc="Awaiting Pick Zone{$s} {$zz}";
     }
     $rdata["rowData"][$rowsReturned]["stat_desc"]=$sdesc;
     $rowsReturned++;
     $numrows=$i;
     if ($rowsReturned >= $numRows) break;
    }
    $i++;
   }
   $rdata["rowThru"]=$rec_count;

  // $rdata["rows"]=$rowsReturned;
  } //SQL ok

  if (isset($x)) unset($x);
  $x=json_encode($rdata);
  if ($DEBUG) wr_log("/tmp/PICK.log","COMP:{$comp} startRec={$startRec} numRows={$numRows} rowsReturned={$rowsReturned}");
  if ($DEBUG) wr_log("/tmp/PICK.log",$SQL);
  if ($DEBUG) wr_log("/tmp/PICK.log",$x);
  echo $x;
   break;
 } // end fetchs

 case "fetchSingle":
 {
  $awhere="";
  if ($order_num <> "")       $awhere.="and order_num = {$order_num}\n";
  if ($host_order_num <> "")  $awhere.="and host_order_num = \"{$host_order_num}\"\n";

 $SQL=<<<SQL
 select company, order_num, host_order_num, order_type, order_stat, priority,
num_lines,
date_required,
enter_date,
enter_by,
ship_complete,
ORDERS.ship_via,
customer_id,
name,
addr1,
addr2,
city,
state,
zip,
ctry,
phone,
mdse_type,
drop_ship_flag,
zones,
special_instr,
shipping_instr
FROM ORDERS,CUSTOMERS
where company = {$comp}
{$awhere}

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
        if (!is_numeric($key)) { $rdata["$key"]=$data; }
       }
     }
    $i++;
  } // while i < numrows
  if ($DEBUG) wr_log("/tmp/PICK.log",$SQL);
  if ($numrows > 0)
  {
   $rdata["stat_desc"]=setStat($rdata["order_stat"]);
   $o=$rdata["order_num"];
   $rdata["Items"]=getItems($db,$o,0);
   if (count($rdata["Items"]))
   {
    foreach($rdata["Items"] as $rec=>$item)
    {
     $l=$item["line_num"];
     $rdata["Items"][$rec]["Bins"]=getBins($db,$o,$l);
    } // end foreach items
   } // end count rdata["Items"]
  } // end numrows > 0
  if (isset($x)) unset($x);
  $x=json_encode($rdata);
  if ($DEBUG) wr_log("/tmp/PICK.log",$x);
  echo $x;
   break;
 } // end fetch Single

 case "fetchItems":
 case "fetchLine":
  {
   $o=$order_num;
   $awhere="";
   $l=0;
   if ($line_num <> "") $l=intval($line_num);

   $rdata["Items"]=getItems($db,$o,$l);
   if (count($rdata["Items"]))
   {
    foreach($rdata["Items"] as $rec=>$item)
    {
     $l=$item["line_num"];
     $rdata["Items"][$rec]["Bins"]=getBins($db,$o,$l);
    } // end foreach items
   } // end count rdata["Items"]
  if (isset($x)) unset($x);
  $x=json_encode($rdata);
  if ($DEBUG) wr_log("/tmp/PICK.log",$x);
  echo $x;
   break;
  } // end fetchItems/fetchLine

 case "fetchPickOrder":  // get the open lines for a pick [within a zone]
  {
   $o=$order_num;
   $awhere="";
   $l=0;
   $pull=0;
   if ($line_num <> "") $l=intval($line_num);
   if ($pull_num <> "") $pull=intval($pull_num);
   $z="";
   //if ($zone <> "") $z=$zone;
   $rdata=getPick($db,$o,$l,$zones,$pull,$user_id,0);
   // get zero picked items too
   if (isset($rdata["errCode"]) or count($rdata) < 1) $rdata=getPick($db,$o,$l,$zones,$pull,$user_id,1);
   if (isset($x)) unset($x);
   $x=json_encode($rdata);
   if ($DEBUG) wr_log("/tmp/PICK.log",$x);
   echo $x;
   break;
  } // end fetchPickOrder

 case "fetchOpenPicks": // fetch open picks for [zone]
 {
   $o=$order_num;
   $awhere="";
   $z="";
   if ($zone <> "") $z=$zone;
   $rdata=getUnPickOrders($db,$comp,$z,$user_id);
   if (isset($x)) unset($x);
   $x=json_encode($rdata);
   if ($DEBUG) wr_log("/tmp/PICK.log",$x);
   echo $x;
   break;
 } // end fetchOpenPicks

 case "fetchUsers": // show all users on a order
 {
  $o=$order_num;
  $rdata=getUsers($db,$o);
  if (isset($x)) unset($x);
  $x=json_encode($rdata);
  //if ($DEBUG) wr_log("/tmp/PICK.log",$x);
  echo $x;
  break;

 } // end fetch users
 case "orderZones": // show all zones on a order
 {
   $o=$order_num;
   $rdata=getOrderZones($db,$o,$user_id);
   if (isset($x)) unset($x);
   $x=json_encode($rdata);
   if ($DEBUG) wr_log("/tmp/PICK.log",$x);
   echo $x;
   break;
 } // end orderZones
 case "orderToPick": // return lines to pick
 {
   $o=$host_order_num;
   if ($case <> "") 
   $rdata=getOrderToPick($db,$o,$user_id);
   if (isset($x)) unset($x);
   $x=json_encode($rdata);
   if ($DEBUG) wr_log("/tmp/PICK.log",$x);
   echo $x;
   break;
 } // end orderToPick
 case "flagOrder": // show all zones on a order
 {
   $o=$order_num;
   $l=0;
   $pull=0;
   $pick="";
   if ($line_num <> "") $l=intval($line_num);
   if ($pull_num <> "") $pull=intval($pull_num);
   if ($picked <> "") $pick.=",qty_picked = qty_picked + {$picked}";
   if ($pickuom <> "") $pick.=",uom_picked = \"{$pickuom}\"";
   if (is_array($o) and count($o) > 0)
   {
    $w="(";
    $comma="";
    foreach($o as $n) 
    {
     $w.="{$comma}{$w}";
     $comma=",";
    }
    $w.=")";
    $where=<<<SQL
where order_num in {$w}
   
SQL;
   }
   else
   {
    $where=<<<SQL
where order_num = {$o}
SQL;
   }
   if ($user_id == 0 or $l == 0 or $pull == 0) 
   {
    echo '{"rc":-1,"rc1":-1}';
    exit;
   }
   $SQL=<<<SQL
update ORDERS
set order_stat = 2
where order_num = {$o}
and order_stat < 2

SQL;
  if ($DEBUG) wr_log("/tmp/PICK.log",$SQL);
  $rc=$db->Update($SQL);
  $SQL=<<<SQL
update ITEMPULL
set user_id = {$user_id}{$pick}
where ord_num = {$o}
and line_num = {$l}
and pull_num = {$pull}

SQL;
  if ($DEBUG) wr_log("/tmp/PICK.log",$SQL);
  if (!isset($rc)) $rc=0;
  $rc1=$db->Update($SQL);
  $w='{"rc":' . $rc . ',"rc1":' . $rc1 . '}';
  if ($DEBUG) wr_log("/tmp/PICK.log",$w);
/*
| ord_num   ,int(11)    ,NO  ,PRI,NULL   ,      |
| line_num  ,int(11)    ,NO  ,PRI,NULL   ,      |
| pull_num  ,smallint(6),NO  ,PRI,NULL   ,      |
| user_id   ,int(11)    ,NO  ,   ,NULL   ,      |
| company   ,smallint(6),NO  ,   ,NULL   ,      |
| shadow    ,int(11)    ,NO  ,   ,NULL   ,      |
| zone      ,char(3)    ,YES ,   ,       ,      |
| whse_loc  ,varchar(18),NO  ,MUL,       ,      |
| qtytopick ,int(11)    ,NO  ,   ,NULL   ,      |
| qty_picked,int(11)    ,NO  ,   ,NULL   ,      |
| uom_picked,char(
*/
 } // end case flagOrder

case "chkPartBin":
{
 /* requires comp, shadow and whseLoc set */
 if ($shadow == "" or $whseLoc == "") return 0;
 $rc=getPartBinCount($db,$comp,$shadow,$whseLoc);
  $w='{"rc":' . $rc . '}';
  echo $w;
  break;
}
case "chgPickBin":
{
 $rc=getPartBinCount($db,$comp,$shadow,$newBin);
 if ($rc > 0)
 {
  $SQL=<<<SQL
update ITEMPULL
set whse_loc = "{$newBin}",
    qtytopick = {$qty}
where ord_num = {$order_num}
and line_num = {$line_num}
and pull_num = {$pull_num}
and whse_loc = "{$origBin}"
and user_id = {$user_id}
and shadow = {$shadow}
SQL;
   $rc=$db->Update($SQL);
 } // end rc from getPartBinCount > 0
  $w='{"rc":' . $rc . '}';
  echo $w;
  break;
 } // end chgPickBin

case "chkOrdTote":
{
 $SQL=<<<SQL
select  tote_code as tote_id, last_zone, last_loc
from ORDTOTE A, TOTEHDR B
where order_num= {$order_num}
and B.tote_id = A.tote_id

SQL;
 
$ret=array();
$ret["numRows"]=0;
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
  if (isset($x)) unset($x);
  $x=json_encode($ret);
  if ($DEBUG) wr_log("/tmp/PICK.log",$x);
  echo $x;
  break;
} // end chkOrdTote

case "chkTote": // check it tote is valid
{
 $ret=array();
 $ret["tote_code"]=$tote_code;
 $ret["numRows"]=0;
 $tote_id=getToteId($tote_code);
 if ($tote_id < 1) $ret["errText"]="Invalid Tote";
 if ($tote_id > 0)
 {
 $SQL=<<<SQL
select  tote_id,tote_status, tote_type,tote_ref
from TOTEHDR
where tote_id= {$tote_id}

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
 } // end tote_id > 0
  if (isset($x)) unset($x);
  $x=json_encode($ret);
  if ($DEBUG) wr_log("/tmp/PICK.log",$x);
  echo $x;
  break;
} // end chkTote

case "updTote":
{
 $rc=0;
 $tote_id=getToteId($tote_code);
 /* dunsel code
 // don't know why I'm updating the tote when the tote is blank 08/23/22
 //if ($tote_id == "" and $order_num > 0 and ($zone <> "" or $whseLoc <> "")) 
 if ($tote_id < 1 and $order_num > 0 and ($zone <> "" or $whseLoc <> "")) 
 { // tote is blank but loc is set
   $rc1=updOrdTote($db,$order_num,$tote_id,$zone,$whseLoc);
   $w='{"rc":' . $rc . ',"rc1:"' . $rc1 . '}';
   if ($DEBUG) wr_log("/tmp/PICK.log",$w);
   if ($DEBUG) wr_log("/tmp/PICK.log","numRows: {$db->NumRows}");
   echo $w;
   break;
 } // tote is blank
*/

 $SQL=<<<SQL
select  *
from TOTEHDR
where tote_id = "{$tote_id}" 
 and tote_company = {$comp}

SQL;
  if ($DEBUG) wr_log("/tmp/PICK.log",$SQL);
$ret=array();
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
  if ($DEBUG) wr_log("/tmp/PICK.log","numrows={$numrows}");
 if (isset($ret["tote_status"]))
 {
  $tote_stat=$ret["tote_status"];
  // add if tote is being used, warn of status and or current usage
  if ($tote_stat < 1)
  { //all good to update
   $SQL=<<<SQL
update TOTEHDR
set tote_location = "{$zone}",
tote_ref = {$order_num},
tote_type = "PIC",
tote_lastused = NOW()
where tote_id = "{$tote_id}" 

SQL;

  if ($DEBUG) wr_log("/tmp/PICK.log",$SQL);
   $rc=$db->Update($SQL);
   $rc1=updOrdTote($db,$order_num,$tote_id,$zone,$whseLoc);
  $w='{"rc":' . $rc . ',"rc1:"' . $rc1 . '}';
  if ($DEBUG) wr_log("/tmp/PICK.log",$w);
  if ($DEBUG) wr_log("/tmp/PICK.log","numRows: {$db->NumRows}");
  echo $w;
  break;
  } //all good to update
 else
  { // problem with tote status
   //check if tote is in use by this order, if so, return ok
   if (isset($ret["tote_ref"]) and (intval($ret["tote_ref"]) == $order_num or  intval($ret["tote_ref"]) == 0))
   {
    echo "{rc:1}";
    break;
   } // tote_ref isset
  else
   { // not same order
    $hostnum=chkTOteOrder($db,$ret["tote_ref"]);
    if (!isset($hostnum)) $hostnum="Invalid";
    if (isset($ret)) unset($ret);
   $ret=array("errCode"=>$tote_stat,"errText"=>"Tote is in use for Order {$hostnum}");
   } // not same order
  } // problem with tote status
 } // tote status is set
   if (isset($x)) unset($x);
   $x=json_encode($ret);
   if ($DEBUG) wr_log("/tmp/PICK.log",$x);
   echo $x;
  break;
} // end updTote

case "updToteLoc":
{

   $tote_id=getToteId($tote_code);
   if ($DEBUG) wr_log("/tmp/PICK.log","in UpdOrdTote: ord={$order_num} tote={$tote_id} loc={$whseLoc}");
 if ($order_num > 0 and $tote_id > 0 and $whseLoc <> "")
   $rc=updOrdTote($db,$order_num,$tote_id,$zone,$whseLoc);
   if ($DEBUG) wr_log("/tmp/PICK.log","UpdOrdTote: rc={$rc}");
    echo "{rc:{$rc}}";
    break;
} // end updOrdTote

case "updToteLoc2":
{
   $tote_id=getToteId($tote_code);
   if ($DEBUG) wr_log("/tmp/PICK.log","in UpdOrdTote2: ord={$order_num} tote={$tote_id} loc={$whseLoc}");
 if ($order_num > 0 and $tote_id > 0 and $whseLoc <> "")
   $rc=updOrdTote2($db,$order_num,$tote_id,$zone,$whseLoc);
   if ($DEBUG) wr_log("/tmp/PICK.log","UpdOrdTote2: rc={$rc}");
    echo "{rc:{$rc}}";
    break;
} // end updOrdTote2


case "getPackOrds":
{
 $SQL=<<<SQL
 select host_order_num,
        A.order_num,
        A.customer_id,
        C.name,
        cust_po_num,
        priority,
        num_lines,
        order_type,
        A.ship_via,
        via_desc,
        zones,
        order_stat,
        tote_code as tote_id,
        last_loc
from ORDERS A, ORDTOTE B,CUSTOMERS C,SHIPVIA, TOTEHDR D
where company = {$comp}
and B.order_num = A.order_num
and order_stat = 3
and C.customer = A.customer_id
and via_code = A.ship_via
and D.tote_id = B.tote_id

SQL;
 $ret=array();
 
  $rc=$db->query($SQL);
  $numrows=$db->num_rows();
  $ret["numRows"]=$numrows;
  $i=1;
  while ($i <= $numrows)
  {
   $db->next_record();
   $tote=1;
     if ($numrows)
     {
      if (!isset($ret["rowData"][$i]["host_order_num"]))
      {
       $ret["rowData"][$i]["host_order_num"]=$db->f("host_order_num");
       $ret["rowData"][$i]["order_num"]     =$db->f("order_num");
       $ret["rowData"][$i]["customer_id"]   =$db->f("customer_id");
       $ret["rowData"][$i]["name"]          =$db->f("name");
       $ret["rowData"][$i]["cust_po_num"]   =$db->f("cust_po_num");
       $ret["rowData"][$i]["priority"]      =$db->f("priority");
       $ret["rowData"][$i]["num_lines"]     =$db->f("num_lines");
       $ret["rowData"][$i]["order_type"]    =$db->f("order_type");
       $ret["rowData"][$i]["ship_via"]      =$db->f("ship_via");
       $ret["rowData"][$i]["via_desc"]      =$db->f("via_desc");
       $ret["rowData"][$i]["order_stat"]    =$db->f("order_stat");
      } // end non repreating fields are not set
      $ret["rowData"][$i]["totes"][$tote]["tote_id"] =$db->f("tote_id");
      $ret["rowData"][$i]["totes"][$tote]["last_loc"]=$db->f("last_loc");
      $tote++;
     }
     $i++;
   } // while i < numrows
   if (isset($x)) unset($x);
   $x=json_encode($ret);
   if ($DEBUG) wr_log("/tmp/PICK.log",$x);
   echo $x;
  break;
} // end getPackOrds

case "checkAnyPicked":
 {
    $SQL=<<<SQL
select count(*) as cnt
 from ITEMPULL
where ord_num = {$order_num}
 and zero_picked = 0
 -- and (user_id = 0 or user_id = {$user_id})

SQL;
 
  if ($DEBUG) wr_log("/tmp/PICK.log",$SQL);
  $picked=0; 
  $rc=$db->query($SQL);
  $numrows=$db->num_rows();
  $i=1;
  while ($i <= $numrows)
  {
   $db->next_record();
     if ($numrows)
     {
        $picked=$db->f("cnt");
     }
     $i++;
   } // while i < numrows
    $SQL=<<<SQL
select count(*) as cnt
 from ITEMPULL
where ord_num = {$order_num}
 and zero_picked > 0
--  and (user_id = 0 or user_id = {$user_id})

SQL;

  if ($DEBUG) wr_log("/tmp/PICK.log",$SQL);
  $zerod=0;
  $rc=$db->query($SQL);
  $numrows=$db->num_rows();
  $i=1;
  while ($i <= $numrows)
  {
   $db->next_record();
     if ($numrows)
     {
        $zerod=$db->f("cnt");
     }
     $i++;
   } // while i < numrows

   $rdata=array();
   $rdata["picked"]=$picked;
   $rdata["zeroed"]=$zerod;
   if (isset($x)) unset($x);
   $x=json_encode($rdata);
   if ($DEBUG) wr_log("/tmp/PICK.log",$x);
   echo $x;
  break;

 } // end checkAnyPicked

case "setZeroStat":
 {
  $SQL=<<<SQL
update ORDERS
set order_stat = -2
where order_num = {$order_num}
and order_stat = 2
SQL;

  if ($DEBUG) wr_log("/tmp/PICK.log",$SQL);
  $rc=$db->Update($SQL);

   if (isset($x)) unset($x);
   $x=json_encode($rc);
   if ($DEBUG) wr_log("/tmp/PICK.log",$x);
   echo $x;
  break;
 } // end setZeroStat

case "setZeroPick":
 {
  $SQL=<<<SQL
 update ITEMPULL
 set zero_picked= zero_picked + 1,
 zpuser = {$user_id}
where ord_num = {$order_num}
 and line_num = {$line_num}
 and pull_num = {$pull_num}

SQL;
  if ($DEBUG) wr_log("/tmp/PICK.log",$SQL);
  $rc=$db->Update($SQL);

 // TODO add TASK type CNT to invoke count of this item

   if (isset($x)) unset($x);
   $x=json_encode($rc);
   if ($DEBUG) wr_log("/tmp/PICK.log",$x);
   echo $x;
  break;
 } // end setZeroPick

case "updPickQty":
 {
  $SQL=<<<SQL
select totes from ITEMPULL
where ord_num = {$order_num}
 and line_num = {$line_num}
 and pull_num = {$pull_num}

SQL;
 $itote="";
 
  $rc=$db->query($SQL);
echo "rc={$rc}\n";
  $numrows=$db->num_rows();
  $i=1;
  while ($i <= $numrows)
  {
   $db->next_record();
     if ($numrows)
     {
        $itote=$db->f("totes");
     }
     $i++;
   } // while i < numrows
 if ($itote <> "") $itote.=":{$tote_code}"; else $itote=$tote_code;

  $SQL=<<<SQL
 update ITEMPULL
 set qty_picked = qty_picked + {$qtyPicked},
 totes = "{$itote}",
 uom_picked = "{$uom}"
where ord_num = {$order_num}
 and line_num = {$line_num}
 and pull_num = {$pull_num}

SQL;
  if ($DEBUG) wr_log("/tmp/PICK.log",$SQL);
  $rc=$db->Update($SQL);
 $SQL=<<<SQL
select 
count(*) as num_lines
from ITEMPULL, ORDERS
where ord_num = {$order_num}
and order_num = ord_num
and qty_picked < qtytopick
and order_stat < 3
and ITEMPULL.company = {$comp}

SQL;
  if ($DEBUG) wr_log("/tmp/PICK.log",$SQL);
  $rc1=$db->query($SQL);
  $cnt=$db->Row[0]["num_lines"];

 $rc2=0;
if ($cnt < 1)
{
 $SQL=<<<SQL
update ORDERS
set pic_done = NOW(), order_stat = 3
where order_num = {$order_num}

SQL;
  if ($DEBUG) wr_log("/tmp/PICK.log",$SQL);
  $rc2=$db->Update($SQL);

$SQL=<<<SQL
 select tote_id
 from ORDTOTE
where order_num  = {$order_num}

SQL;
$totes="";

  $rc=$db->query($SQL);
  $numrows=$db->num_rows();
  $i=1;
  while ($i <= $numrows)
  {
   $db->next_record();
     if ($numrows)
     {
        if (strlen($totes) > 0) $totes.=":";
        $totes.=$db->f("tote_id");
     }
     $i++;
   } // while i < numrows
$uwhere="";
if ($totes <> "") $uwhere=", que_data=\"{$totes}\"";
 $SQL=<<<SQL
update ORDQUE
set que_key = "PAK"{$uwhere}
where order_num = {$order_num}

SQL;
  if ($DEBUG) wr_log("/tmp/PICK.log",$SQL);
  $rc2a=$db->Update($SQL);
} // end cnt < 1

 if (!isset($tote_id) and isset($tote_code))
   $tote_id=getToteId($tote_code);
 
  $w='{"rc":' . $rc . '"openLines":' . $cnt . '"orderClosed":' . $rc2 . '}';
  
  if (($whseLoc <> "" or $zone <> ""))
  {
   $rc3=updOrdTote($db,$order_num,$tote_id,$zone,$whseLoc);
  }

$rc3="";
if ($tote_id > 0)
{
if (isset($tqty)) unset($tqty);
$item=0;
$SQL=<<<SQL
select tote_item, tote_qty
from TOTEDTL
where tote_id = {$tote_id}
and tote_shadow = {$shadow}
and tote_uom = "{$uom}"

SQL;
  if ($DEBUG) wr_log("/tmp/PICK.log",$SQL);
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
 if ($DEBUG) wr_log("/tmp/PICK.log",$SQL);
 $rc4=$db->query($SQL);
 $cnt2=$db->Row[0]["cnt"];
 $item=$cnt2 + 1;
} // end item < 1

if (isset($tqty))
{ // update
 $SQL=<<<SQL
update TOTEDTL
set tote_qty = tote_qty + {$qtyPicked}
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
values ( {$tote_id}, {$item}, {$shadow}, {$qtyPicked}, "{$uom}")

SQL;
} // insert
  if ($DEBUG) wr_log("/tmp/PICK.log",$SQL);
  $rc3=$db->Update($SQL);
} // end tote_id > 0
  $ret=array(
"rc"=>$rc,
"openLines"=>$cnt,
"orderClosed"=>$rc2,
"toteDtl"=>$rc3
);
 if (isset($rc2a)) $ret["ordQue"]=$rc2a;
  $w=json_encode($ret);
  echo $w;

/* Args       
  "action"=>"updPickQty",
  "company"=>$comp,
  "user_id" => $UserID,
  "host_order_num"=>$hostordernum,
  "bin"=>$bintoScan,
  "order_num"=>$orderNumber,
  "line_num"=>$lineNumber,
  "pull_num"=>$pullnum,
  "shadow"=>$shadow,
  "qtyPicked"=>$qty,
  "uom"=>$uom,
  "p_l"=>$p_l,
  "part_number"=>$part_number
   );
*/
 } // end updPickQty

case "scanVerifyTote": // scan verify tote contents for packing
  {
   // requires order#, totes to check, qty scanned and partNumber
   if ($order_num < 1) exit;
   if ($totes == "") exit;
   if (!isset($updqty)) $updqty=0;

   if (trim($partNumber) <> "")
   {
    $pr=new PARTS;
    $pnum=trim(strtoupper($partNumber));
    $rdata=$pr->chkPart($pnum,$comp);
  if ($rdata["status"] == 1)
   { // got 1 part
    $shadow=$rdata["Part"]["shadow_number"];
   } // end got 1 part
   else if ($rdata["status"] > 1)
    { // do choose
     $ret=$rdata;
    } // status > 1 do choose
   } // end partNUmber <> ""
   else $shadow=0;
   $t=str_replace(":",",",$totes);
   $ret=verifyPack($db,$comp,$t,$shadow,$order_num,$updqty);
    if (isset($x)) unset($x);
    $x=json_encode($ret);
    if ($DEBUG) wr_log("/tmp/PICK.log",$x);
    echo $x;
    break;
  } // end scanVerify

case "scanVerify": // scan verify order contents for packing
  {
   // requires order# to check, qty scanned and partNumber
   if ($order_num < 1) exit;
   if (!isset($updqty)) $updqty=0;

   if (trim($partNumber) <> "")
   {
    $pr=new PARTS;
    $pnum=trim(strtoupper($partNumber));
    $rdata=$pr->chkPart($pnum,$comp);
  if ($rdata["status"] == 1)
   { // got 1 part
    $shadow=$rdata["Part"]["shadow_number"];
    // get qty and uom from alternate result
    $tqty=$rdata["Result"]["alt_type_code"];
    if ($tqty < 0) $updqty=abs($tqty);
    if ($tqty < 0 and $updqty > 0) $updqty=$updqty * abs($tqty);
    if ($updqty == 0) $updqty=1;
    $uom=$rdata["Result"]["alt_uom"];
   } // end got 1 part
   else if ($rdata["status"] > 1)
    { // do choose
     $ret=$rdata;
    } // status > 1 do choose
   } // end partNUmber <> ""
   else $shadow=0;
   $ret=scanOrd($db,$order_num,$shadow,$updqty,$uom,$user_id);
    if (isset($x)) unset($x);
   if ($shadow == 0)
   {
    unset($ret);
     $ret=array("rc"=>-35,"errText"=>"Part Not Found");
   }
    if ($shadow > 0)
    {
     $ret["Part"]["pl"]=$rdata["Part"]["p_l"];
     $ret["Part"]["partNumber"]=$rdata["Part"]["part_number"];
     $ret["Part"]["partDesc"]=$rdata["Part"]["part_desc"];
     $ret["Part"]["qty"]=$updqty;
    }
    $x=json_encode($ret);
    if ($DEBUG) wr_log("/tmp/PICK.log",$x);
    echo $x;
    break;

 } // end scanVerify

case "getAllItems": // get all line items for an order not just pickable ones
{
 if ($order_num > 0)
 {
  $rdata=getAllItemsOnOrder($db,$order_num);
  $x=json_encode($rdata);
  if ($DEBUG) wr_log("/tmp/PICK.log",$x);
  echo $x;
  break;
 } // end order num > 0
} // end getAllItems
case "getItems": // get line items for an order
{
   if ($order_num < 1) exit;
   $items=getItems1($db,$order_num,0,1);
   $x=json_encode($items);
   if ($DEBUG) wr_log("/tmp/PICK.log",$x);
   echo $x;
    break;
} // end getItems

case "verifyResults": // scan verify order contents for packing
 {
   // requires order# to check
   if ($order_num < 1) exit;
   $items=getItems($db,$order_num,0);
   $scanned=getPackScan($db,$order_num,0);
   $rdata=array();
   $rdata["order_num"]=$order_num;
   if (count($items) > 0 and count($scanned) > 0)
   {
    foreach ($items as $key=>$item)
    {
     $l=$item["line_num"];
     $rdata[$l]["line_num"]=$l;
     $rdata[$l]["shadow"]=$item["shadow"];
     $rdata[$l]["p_l"]=$item["p_l"];
     $rdata[$l]["part_number"]=$item["part_number"];
     $rdata[$l]["part_desc"]=$item["part_desc"];
     $rdata[$l]["uom"]=$item["uom"];
     $rdata[$l]["qty_ord"]=$item["qty_ord"];
     $rdata[$l]["qty_ship"]=$item["qty_ship"];
     $rdata[$l]["qty_scanned"]=0;
     $rdata[$l]["qty_avail"]=$item["qty_avail"];
     $rdata[$l]["min_ship_qty"]=$item["min_ship_qty"];
     $sc=0;
     if (isset($scanned[$l]))
     {
      $rdata[$l]["qty_scanned"]=$scanned[$l]["qty_scan"];
      $sc=$scanned[$l]["qty_scan"];
     }
     $rdata[$l]["status"]=0;
     if (intval($item["qty_ord"]) - intval($sc) > 0) $s=-1;
     if (intval($item["qty_ord"]) - intval($sc) < 0) $s=1;
     $rdata[$l]["status"]=$s;
    } // end foreach items
    if (count($scanned)> 0)
    { // add not on order parts to results
     foreach ($scanned as $key=>$item)
     {
      if ($key >= 9000)
      {
       $l1=$key - 9000;
       $rdata[$key]["line_num"]=$l1;
       $rdata[$key]["shadow"]=$item["shadow"];
       $rdata[$key]["p_l"]=$item["p_l"];
       $rdata[$key]["part_number"]=$item["part_number"];
       $rdata[$key]["part_desc"]=$item["part_desc"];
       $rdata[$key]["uom"]=$item["uom"];
       $rdata[$key]["qty_ord"]=0;
       $rdata[$key]["qty_ship"]=0;
       $rdata[$key]["qty_scanned"]=$item["qty_scan"];
       $rdata[$key]["qty_avail"]="Not on Order";
       $rdata[$key]["status"]=-9;
      } // end key = 0
     } // end foreach scanned
    }  // add not on order parts to results
   } // end count items > 0

  $x=json_encode($rdata);
  if ($DEBUG) wr_log("/tmp/PICK.log",$x);
  echo $x;
    break;
 } // end verifyResults

  case "checkRel2Host":
  {
   $rdata=-1;
   if (isset($order_num) and $order_num > 0) 
	$rdata=isOrdComplete($db,$order_num);
   $x=json_encode($rdata);
   if ($DEBUG) wr_log("/tmp/PICK.log",$x);
   echo $x;
  } // end checkRel2Host


} // end switch reqdata action
// SWITCH

function getQTY($db1,$shadow,$comp)
{
 $SQL=<<<SQL
 select qty_avail,primary_bin
 from WHSEQTY
 where ms_shadow = {$shadow}
 and ms_company = {$comp}
SQL;

$ret=array();
$ret["qty_avail"]=0;
$ret["primary_bin"]="";
$rc=$db1->query($SQL);
  $numrows=$db1->num_rows();
  $i=1;
  while ($i <= $numrows)
  {
   $db1->next_record();
     if ($numrows and $db1->Record)
     {
      foreach ($db1->Record as $key=>$data)
       {
        if (!is_numeric($key)) { $ret["$key"]=$data; }
       }
     }
    $i++;
  } // while i < numrows
 return $ret;
}
function setStat($in)
{
 $w="";
 switch ($in)
  {
   case -1:
    $w="Awaiting Product";
    break;
   case 0:
    $w="Not Released";
    break;
   case 1:
    $w="Awaiting Pick";
    break;
   case -2:
    $w="Zero Picked";
    break;
   case 2:
    $w="Being Picked";
    break;
   case 3:
    $w="Being Packed";
    break;
   case 4:
    $w="In Shipping";
    break;
   case 5:
    $w="Whse Done";
    break;
   case 6:
    $w="Sent to Host";
    break;
   case 7:
    $w="Complete";
    break;
   case 9:
    $w="Done/Delete";
    break;
  } // end switch in
 return $w;
} // end setStat

function getItems($db,$order,$line=0)
{
 // get items
 $awhere="";
 if ($line > 0) $awhere.=" and line_num = {$line}\n";
 $SQL=<<<SQL
select
ord_num,
line_num,
shadow,
p_l,
part_number,
part_desc,
uom,
qty_ord,
qty_ship,
qty_bo,
qty_avail,
min_ship_qty,
case_qty,
inv_code,
line_status,
hazard_id,
zone,
whse_loc,
qty_in_primary,
num_messg,
part_weight,
part_subline,
part_category,
part_group,
part_class,
item_pulls,
specord_num,
inv_comp
from ITEMS
where ord_num = {$order}
{$awhere}

SQL;
   $ret=$db->gData($SQL);
 return $ret;
} // end getItems

function getItems1($db,$order,$line=0,$sortby=0)
{
 // get itempulls for a line or an order
 $where=bldOrderWhere($order,"A.ord_num");
 $awhere="";
 if ($line > 0) $awhere.=" and line_num = {$line}\n";
 $orderby="";
 if ($sortby == 1) $orderby="order by whse_loc , A.ord_num, line_num";

      $SQL=<<<SQL
select 
A.ord_num,
A.line_num,
pull_num,
user_id,
company,
A.shadow,
B.p_l,
B.part_number,
B.part_desc,
A.zone,
A.whse_loc ,
qty_ord,
qtytopick,
qty_picked,
uom_picked,
qty_verified,
unit_of_measure as uom,
totes,
zero_picked,
zpuser,
qty_avail
 from ITEMPULL A, PARTS B, ITEMS C
{$where}
and shadow_number = A.shadow
and C.ord_num = A.ord_num
and C.line_num = A.line_num
{$awhere}
{$orderby}

SQL;
  wr_log("/tmp/PICK.log",$SQL);
  $ret=$db->gData($SQL);
  return $ret;

} // end getBins


function getBins($db,$order,$line)
{
 // get itempulls for a line or an order
 $awhere="";
 if ($line > 0) $awhere.=" and line_num = {$line}\n";

      $SQL=<<<SQL
select * from ITEMPULL
where ord_num = {$order}
{$awhere}

SQL;
  $ret=$db->gData($SQL);
  return $ret;

} // end getBins

function getPick($db,$order,$line=0,$zones=array(),$pull=0,$user_id=0,$zeros=0)
{
 global $DEBUG;
 global $skipLines;
 // get itempulls for a order in Bin Sequence or an line
 $awhere="";
 $where=bldOrderWhere($order);
  
 if ($line > 0) $awhere.=" and A.line_num = {$line}\n";
 if ($pull > 0) $awhere.=" and A.pull_num = {$pull}\n";
 if ($zeros > 0) 
  { // search for zero picked regardless of user
   $awhere.=" and A.zero_picked > 0\n";
  } // search for zero picked regardless of user
 else
  { // only search for items that have not been zero picked
   $awhere.=<<<SQL
 and A.zero_picked = 0
-- and (user_id = 0 or user_id = {$user_id})

SQL;
  } // only search for items that have not been zero picked

 //if ($zone <> "") $awhere.=" and A.zone like \"{$zone}%\"\n";
 // select only open picks
 $awhere.=" and A.qty_picked < A.qtytopick";
  if ($DEBUG) wr_log("/tmp/PICK.log","Zones: " . json_encode($zones));
 if (!empty($zones) and !isset($zones["%"]))
  {
   $z1=" and A.zone in (";
   $comma="";
   foreach($zones as $z)
   {
    $z1.="{$comma}\"{$z}\"";
    $comma=",";
   }
   $awhere.="{$z1})\n";
  } // end zones not empty
   if (isset($skipLines) and is_array($skipLines) and count($skipLines) > 0)
   { // its a array of lines
    foreach ($skipLines as $n=>$v)
    $awhere.=<<<SQL
   and (ord_num <> {$v["ordNum"]}
   and line_num <> {$v["lineNum"]}
   and pull_num <> {$v["pullNum"]})

SQL;
   } // skipLine is a array of lines


 $order_by="order by A.whse_loc,B.p_l,B.part_number\n";

      $SQL=<<<SQL
select 
A.ord_num,
A.line_num,
A.pull_num,
A.user_id,
A.company,
A.zone,
A.whse_loc,
B.p_l,
B.part_number,
B.part_desc,
A.qtytopick,
B.uom,
B.qty_ord,
B.qty_ship,
B.qty_avail,
B.case_qty,
A.qty_picked,
A.uom_picked,
zero_picked,
zpuser,
A.shadow
from ITEMPULL A,ITEMS B
{$where}
and B.ord_num = A.ord_num
and B.line_num = A.line_num
{$awhere}
{$order_by}

SQL;
  if ($DEBUG) wr_log("/tmp/PICK.log",$SQL);
  $ret=$db->gData($SQL);
  if (!isset($zone)) $zone="";
  if (!empty($zones) and count($ret) < 1) return array("errCode"=>-35,"errText"=>"No Records for Zone:{$zone}");
  else return $ret;
} // end getPick

function getUnPickOrders($db,$comp,$zone="",$user_id=0)
{
 // selects only open picks
 $awhere="";
 if ($zone <> "") $awhere.=" and zone like \"{$zone}%\"\n";
 $order_by="order by zone,priority,ord_num\n";
 $SQL=<<<SQL
select distinct zone,
priority,
ord_num,
count(*) as num_lines,
sum((qtytopick - qty_picked)) as units
from ITEMPULL, ORDERS
where qty_picked < qtytopick
and order_num = ord_num
and order_stat < 2
and ITEMPULL.company = {$comp}
-- and (user_id = 0 or user_id = {$user_id})
{$awhere}
group by zone,ord_num
{$order_by}

SQL;
  $ret=$db->gData($SQL);
  if ($zone <> "" and count($ret) < 1) return array("errCode"=>-35,"errText"=>"No Records for Zone:{$zone}");
  else return $ret;
} // end getUnPickOrders

function getOrderZones($db,$order,$user_id=0)
{
 $SQL=<<<SQL
select distinct
ord_num,
zone,
count(*) as num_lines,
sum(qtytopick) as qtytopick,
sum(qty_picked) as qty_picked
from ITEMPULL, ORDERS
where order_num = {$order}
-- and (user_id = 0 or user_id = {$user_id})
and order_stat < 2
group by ord_num, zone

SQL;
  $ret=$db->gData($SQL);
  if (count($ret) < 1)
  { // no open order found, lets see if it's valid and get status
    $SQL=<<<SQL
select order_stat from ORDERS
where order_num = {$order}
SQL;
  $ret1=$db->gData($SQL);
  if (count($ret1) < 1)
   return array("errCode"=>-35,"errText"=>"No Records for Order: {$order}");
  else return array("errCode"=>1,"errText"=>"Order: {$order} has no items in this zone or is complete");
  } // no open order found, lets see if it's valid and get status
  else return $ret;
} // end getOrderZones

function getOrderToPick($db,$host_order,$user_id=0)
{
 global $case;
 global $DEBUG;
 $extraWhere="";
 if ($case <> "" and $case <> "showAll") $extraWhere="and order_stat < 3";
 else $extraWhere=<<<SQL
and ((user_id = 0 or user_id = {$user_id})
 or (qty_picked < qtytopick and zpuser <> {$user_id}))
SQL;
 if (is_array($host_order) and count($host_order) > 0)
 {
  $w="";
  $comma="";
  foreach ($host_order as $o)
  {
   $w.="{$comma}{$o}";
   $comma=",";
  }
  $where="where ord_num in ({$w})";
 } // end order is array
 else
 {
 $where=<<<SQL
where host_order_num = "{$host_order}"

SQL;
 }

 $SQL=<<<SQL
select distinct
 ORDERS.company, order_num, host_order_num, order_type, order_stat, priority,
num_lines, date_required, enter_date, enter_by, ship_complete, ORDERS.ship_via,
customer_id,
name,
addr1,
addr2,
city,
state,
zip,
ctry,
phone,
mdse_type,
drop_ship_flag,
zones,
special_instr,
shipping_instr,
zone,
sum(qtytopick) as qtytopick,
sum(qty_picked) as qty_picked
from ITEMPULL, ORDERS, CUSTOMERS
{$where}
and ord_num = order_num
{$extraWhere}
and customer_id = customer
group by ord_num, zone

SQL;
 if ($DEBUG) wr_log("/tmp/PICK.log",$SQL);
  $ret=$db->gData($SQL);
  if (count($ret) < 1)
  { // no open order found, lets see if it's valid and get status
    $SQL=<<<SQL
select order_stat,order_num from ORDERS
{$where}
SQL;
  $ret1=$db->gData($SQL);
  if (count($ret1) < 1)
   return array("errCode"=>-35,"errText"=>"No Records for Order: {$host_order}");
   else return array("errCode"=>1,"errText"=>"Order: {$host_order} has no items in this Zone or is complete");
  } // no open order found, lets see if it's valid and get status
  else 
   {
    //correct num_lines incase there are missing line#'s
    $SQL=<<<SQL
select count(*) as cnt from ORDERS, ITEMS {$where}
and ord_num = order_num
SQL;
    $ret[1]["num_lines"]=loadCnt($db,$SQL);
    $ret[1]["zones"]=$ret[1]["zone"];
    if ($db->NumRows > 1)
    { //consolidate the order and sum up zones
     $zones="";
     foreach ($ret as $rec=>$w)
     {
      $comma=",";
      if (!strlen($zones)) $comma="";
      $zones.="{$comma}{$w["zone"]}";
     } // end foreach ret
    $ret[1]["zones"]=$zones;
    } //consolidate the order and sum up zones
   }
  return $ret;
} // end getOrderToPick

function getUsers($db,$order)
{
 // get itempulls for an order
 $awhere="group by ord_num,user_id";

      $SQL=<<<SQL
select distinct ord_num, user_id, count(*) as cnt from ITEMPULL
where ord_num = {$order}
{$awhere}

SQL;
  $ret=$db->gData($SQL);
  return $ret;

} // end getBins
function updOrdTote($db,$order,$tote,$zone,$loc)
{
 global $DEBUG;
 if ($zone == "") $zone=substr($loc,0,1);
  $SQL=<<<SQL
insert into ORDTOTE
(order_num, tote_id, last_zone, last_loc)
values ( {$order},{$tote}, "{$zone}", "{$loc}")
ON DUPLICATE KEY UPDATE
 last_loc="{$loc}"

SQL;
 
 if ($DEBUG) wr_log("/tmp/PICK.log",$SQL);
 $rc=$db->Update($SQL);
 return $rc;

} // end updOrdTote

function updOrdTote2($db,$order,$tote,$zone,$loc)
{
 global $DEBUG;
 if ($zone == "") $zone=substr($loc,0,1);
  $SQL=<<<SQL
update ORDTOTE
set last_zone="{$zone}",
    last_loc="{$loc}"
where order_num = {$order}
and tote_id = {$tote}

SQL;

 if ($DEBUG) wr_log("/tmp/PICK.log",$SQL);
 $rc=$db->Update($SQL);
 return $rc;

} // end updOrdTote2


function getPartBinCount($db,$comp,$shadow,$whseLoc)
{
 global $DEBUG;
  $SQL=<<<SQL
select count(*) as cnt
from WHSELOC
where whs_company = {$comp}
and whs_shadow = {$shadow}
and whs_location = "{$whseLoc}"

SQL;
if ($DEBUG) wr_log("/tmp/PICK.log","{$SQL}");
  $rc1=$db->query($SQL);
  $cnt=$db->Row[0]["cnt"];
 return $cnt;

} // end getPartBinCount

function getOrd($db,$order_num)
{
 global $DEBUG;
 if (intval($order_num) < 1) return array();
 $SQL=<<<SQL
 select 
order_num,
company,
order_type,
host_order_num,
customer_id,
name,
addr1,
addr2,
city,
state,
zip,
ctry,
cust_po_num,
enter_by,
enter_date,
wms_date,
pic_release,
pic_done,
wms_complete,
date_required,
priority,
ship_complete,
order_stat,
num_lines,
spec_order_num,
mdse_type,
ORDERS.ship_via,
conveyor,
drop_ship_flag,
special_instr,
shipping_instr,
zones,
o_num_pieces,
messg,
track_recs
 from ORDERS, CUSTOMERS
where order_num = {$order_num}
and customer = customer_id

SQL;
   $ret=$db->gData($SQL);
if ($DEBUG) wr_log("/tmp/PICK.log","{$SQL}");
   if (isset($ret[1])) return $ret[1];
   else return array();
} // end getOrd

function getTotes($db,$order_num,$tote=0)
{
 global $DEBUG;
 if (intval($order_num) < 1) return array();
 $awhere="";
 if ($tote > 0 ) $awhere="and tote_id = {$tote}";

 $SQL=<<<SQL
select  
order_num,
A.tote_id as tote_num,
tote_code as tote_id, last_zone, last_loc
from ORDTOTE A, TOTEHDR B
where order_num= {$order_num}
and B.tote_id = A.tote_id
{$awhere}

SQL;
if ($DEBUG) wr_log("/tmp/PICK.log","{$SQL}");
   $ret=$db->gData($SQL);
   return $ret;
} // end getTote

function getToteDtl($db,$order_num,$tote_id)
{
 global $DEBUG;
 if (intval($tote_id) < 1) return array();
 $SQL=<<<SQL
select
tote_id,
tote_item,
ord_num,
line_num,
p_l,
part_number,
part_desc,
tote_shadow,
qty_ord,
tote_qty,
tote_uom,
qty_avail,
min_ship_qty,
hazard_id,
part_weight,
specord_num
from TOTEDTL,ITEMS
where tote_id = {$tote_id}
and ord_num = {$order_num}
and shadow = tote_shadow
order by tote_id,line_num

SQL;
if ($DEBUG) wr_log("/tmp/PICK.log","{$SQL}");
   $ret=$db->gData($SQL);
   return $ret;
} // end getToteDtl

function gorderNum($db,$SQL)
{
 $order_num=0;
 $rc=$db->query($SQL);
  $numrows=$db->num_rows();
  $i=1;
  while ($i <= $numrows)
  {
   $db->next_record();
     if ($numrows)
     {
        $order_num=$db->f("order_num");
     }
     $i++;
   } // while i < numrows
 return $order_num;
} // end gorderNum

function countOpenItems($db,$order_num)
{
 $ret=array();
 $SQL=<<<SQL
 select distinct ifnull(zone," ") as zone,count(*) as cnt
from ITEMPULL
  where ord_num = {$order_num}
 and zone like "%"
 and qty_picked <> qtytopick

SQL;
  $stat=array();
  $nr=0;
  $rc=$db->query($SQL);
  $numrows=$db->num_rows();
  $i=1;
  while ($i <= $numrows)
  {
   $db->next_record();
     if ($numrows)
     {
        $zone=$db->f("zone");
        $cnt=$db->f("cnt");
        if ($cnt > 0)
        {
         $stat[$i]["zone"]=$zone;
         $stat[$i]["numRows"]=$cnt;
         $nr++;
        }
     }
     $i++;
   } // while i < numrows
 $ret=$stat;
 return $ret;
} // end countOpenItems

function verifyPack($db,$comp,$totes,$shadow,$order,$updqty=0)
{
 // totes is a comma separated string of all totes to check
 // if update qty is set, update the qty after read is successful
 // returns array of records 
 // or rc array of status
 // > 0  rc=number of records updated
 // -1 rc=no records found
global $DEBUG;

 $SQL=<<<SQL
select
tote_id,
tote_qty,
line_num,
pull_num,
shadow,
qty_picked,
qty_verified
from ITEMPULL,TOTEDTL
where ord_num = {$order}
and company = {$comp}
and tote_id in ({$totes})
and tote_shadow = shadow
and qty_verified < qty_picked

SQL;

if ($DEBUG) wr_log("/tmp/PICK.log","{$SQL}");
$ret=array();
$sret=array();
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
        if (!is_numeric($key)) { $ret[$i]["$key"]=$data; }
       }
     }
    $i++;
  } // while i < numrows
 $rtype=0;
 if ($updqty <> 0 and $numrows > 0)
 { // update 1st record I find
  $ctrlQty=$updqty;
  foreach ($ret as $key=>$data)
  {
   $qty1=$data["qty_picked"];
   $qty=$data["qty_verified"];
   if ($ctrlQty > 0 and $qty <= $ctrlQty)
   {
    $uqty=$qty + $ctrlQty;
    if ($uqty > $qty1)
    {
     $uqty=$qty1;
     $ctrlQty=$ctrlQty - $uqty;
    }
    if ($uqty <> 0)
    {
     $SQL=<<<SQL
update ITEMPULL
set qty_verified = {$uqty}
where ord_num = {$order}
and line_num = {$data["line_num"]}
and pull_num = {$data["pull_num"]}
and shadow = {$data["shadow"]}

SQL;
if ($DEBUG) wr_log("/tmp/PICK.log","{$SQL}");
     $rc=$db->Update($SQL);
     $ret1["rc"]=$rc;
     $rtype=1;
    } // uqty <> 0
   }
  if ($ctrlQty < 1) break;
  } // end foreach ret
 } // update 1st record I find
 if ($numrows < 1) 
 {
  $ret1["rc"]=-1;
  $rtype=2;
 }
 if ($rtype > 0) return $ret1; else return $ret;
} // end verifyPack

function scanOrd($db,$order,$shadow,$qty,$uom,$user=0)
{ 
 // see if record for this shadow is there, same part, order and user
 // if so update qty
 // else get new scan line number and insert a new record
$totqty=$qty;
$rcode=-35;
$scanLine=0;
$msg="";
if (checkPartOnOrder($db,$order,$shadow) < 1) $msg="The Part is not on this Order";
$SQL=<<<SQL
select
scan_line,
qty_scan
from PACKSCAN 
where ord_number = {$order}
and shadow = {$shadow}
and checker = {$user}

SQL;
  $rc=$db->query($SQL);
  $numrows=$db->num_rows();
  $i=1;
  while ($i <= $numrows)
  {
   $db->next_record();
     if ($numrows)
     {
        $scanLine=$db->f("scan_line");
        $totqty=$db->f("qty_scan");
     }
     $i++;
   } // while i < numrows
 if ($scanLine > 0)
 { // query was succesful, update the row
  $SQL=<<<SQL
 update PACKSCAN
 set qty_scan = qty_scan + $qty
 where ord_number = {$order}
 and shadow = {$shadow}
 and checker = {$user}
 and scan_line = {$scanLine}

SQL;
  $totqty=$totqty + $qty;
  $rc=$db->Update($SQL);
 } // query was succesful, update the row
 else
 { // check if valid part for this order and add new scan record
  $SQL=<<<SQL
   select line_num,qty_ord
   from ITEMS
   where ord_num = {$order}
   and shadow = {$shadow}
SQL;
   
  $rc=$db->query($SQL);
  $numrows=$db->num_rows();
  $i=1;
  while ($i <= $numrows)
  {
   $db->next_record();
     if ($numrows)
     {
        $lineNum=$db->f("line_num");
        $qtyOrd=$db->f("qty_ord");
     }
     $i++;
   } // while i < numrows
  if ($numrows > 0)
  { // line item found
   $scanLine=getMaxScanLine($db,$order,$lineNum);
   $scanLine++;
   $SQL=<<<SQL
   insert into PACKSCAN
   (ord_number, line_num, shadow, qty_scan, checker, scan_line, scan_tote, uom)
  values
   ({$order},{$lineNum},{$shadow},{$qty},{$user},{$scanLine},0,"{$uom}")

SQL;
  $rc=$db->Update($SQL);
  } // line item found
 else
  { // line not found, add new scan record
    // get next scan line of line_num 0
   $scanLine=getMaxScanLine($db,$order,0);
   $scanLine++;
    $SQL=<<<SQL
   insert into PACKSCAN
   (ord_number, line_num, shadow, qty_scan, checker, scan_line, scan_tote, uom)
  values
   ({$order},0,{$shadow},{$qty},{$user},{$scanLine},0,"{$uom}")
SQL;
  $rc=$db->Update($SQL);
  $rcode=-1;
  } // line not found, add new scan record
 } // check if valid part for this order and add new scan record
 $ret=array("rc"=>$rc);
 if ($rcode == -1) $ret["errText"]="The Part is not on this Order";
 if ($msg <> "") $ret["errText"]=$msg;
 $ret["totQty"]=$totqty;
 return $ret;
} // end scanOrd

function getMaxScanLine($db,$order,$line)
{
 $ret=0;
 $SQL=<<<SQL
select
max(scan_line) as sline
from PACKSCAN
where ord_number = {$order}
and line_num = {$line}

SQL;

  $rc=$db->query($SQL);
  $numrows=$db->num_rows();
  $i=1;
  while ($i <= $numrows)
  {
   $db->next_record();
     if ($numrows)
     {
        $ret=$db->f("sline");
     }
     $i++;
   } // while i < numrows
 return $ret;
}

function getPackScan($db,$order,$line_num=0)
{
 $awhere="";
 if ($order < 1) return -1;
 if ($line_num > 0) $awhere=<<<SQL
and line_num = {$line_num}
SQL;
 
 $SQL=<<<SQL
select
ord_number,
line_num,
p_l,
part_number,
part_desc,
shadow,
qty_scan,
checker,
scan_line,
scan_tote,
uom
from PACKSCAN,PARTS
where ord_number = {$order}
{$awhere}
and shadow_number = shadow

SQL;

$ret=array();
$rc=$db->query($SQL);
  $numrows=$db->num_rows();
  $i=1;
  while ($i <= $numrows)
  {
   $db->next_record();
     if ($numrows and $db->Record)
     {
      $l=$db->f("line_num");
      if ($l == 0) $l=(9000 + intval($l)) + intval($db->f("scan_line"));
      $shadow=$db->f("shadow");
      if (isset($ret[$l]) and $ret[$l]["shadow"] == $shadow)
      {
       $ret[$l]["qty_scan"]=$ret[$l]["qty_scan"] + $db->f("qty_scan");
      } // lline is already set
      else
      { // line is not set add new 
       foreach ($db->Record as $key=>$data)
        {
         if (!is_numeric($key)) { $ret[$l]["$key"]=$data; }
        }
      }  // line is not set add new 
     }
    $i++;
  } // while i < numrows
 return $ret;
} // end getPackScan
function checkPartOnOrder($db,$order,$shadow)
{
 $ret=-1;
   $SQL=<<<SQL
   select line_num
   from ITEMS
   where ord_num = {$order}
   and shadow = {$shadow}
SQL;

  $rc=$db->query($SQL);
  $numrows=$db->num_rows();
  $i=1;
  while ($i <= $numrows)
  {
   $db->next_record();
     if ($numrows)
     {
        $ret=$db->f("line_num");
     }
     $i++;
   } // while i < numrows
 return $ret;

} // end checkPartOnOrder
function orderDetail($db,$order, $order_by="")
{
      $SQL=<<<SQL
select 
A.ord_num,
A.line_num,
A.pull_num,
A.user_id,
A.company,
A.zone,
A.whse_loc,
B.p_l,
B.part_number,
B.part_desc,
A.qtytopick,
B.uom,
B.qty_ord,
B.qty_ship,
B.qty_avail,
B.case_qty,
A.qty_picked,
A.uom_picked,
A.shadow
from ITEMPULL A,ITEMS B
where A.ord_num = {$order}
and B.ord_num = A.ord_num
and B.line_num = A.line_num
{$order_by}

SQL;
  if ($DEBUG) wr_log("/tmp/PICK.log",$SQL);
  $ret=$db->gData($SQL);
} // end orderDetail

function countPickedItems($db,$order_num,$lines)
{
 // sum up total qty to pick and qty picked from ITEMPULL
 // Totals are for the order header
 // Items are added to the line item detail
 $ret=array();
 $ret["Totals"]=array();
 $ret["Totals"]["qtyOrd"]=0;
 $ret["Totals"]["qty2Ship"]=0;
 $ret["Totals"]["qty2Pick"]=0;
 $ret["Totals"]["qtyPicked"]=0;
 $Items=$lines;
 if (is_array($lines) and count($lines) > 0)
 {
  foreach ($lines as $lineNum=>$line)
  {
   $ret["Totals"]["qtyOrd"]  =$ret["Totals"]["qtyOrd"]   + $line["qty_ord"];
   $ret["Totals"]["qty2Ship"]=$ret["Totals"]["qty2Ship"] + $line["qty_ship"];
   if (!isset($Items[$lineNum]["qty2Pick"])) $Items[$lineNum]["qty2Pick"]=0;
   if (!isset($Items[$lineNum]["qtyPicked"])) $Items[$lineNum]["qtyPicked"]=0;
   $toPick=$line["qty_ship"];
   $picked=0;
   $SQL=<<<SQL
select qtytopick, qty_picked, totes
 from ITEMPULL
 where ord_num = {$line["ord_num"]}
 and line_num = {$line["line_num"]}
 and shadow = {$line["shadow"]}

SQL;

  $rc=$db->query($SQL);
  $numrows=$db->num_rows();
  $i=1;
  while ($i <= $numrows)
  {
   $db->next_record();
     if ($numrows)
     {
      $toPick=$db->f("qtytopick");
      $picked=$db->f("qty_picked");
      $totes=$db->f("totes");
      $Items[$lineNum]["qty2Pick"] =$Items[$lineNum]["qty2Pick"] + $toPick;
      $Items[$lineNum]["qtyPicked"]=$Items[$lineNum]["qtyPicked"] + $picked;
      $Items[$lineNum]["totes"]=$totes;
      $ret["Totals"]["qty2Pick"]  =$ret["Totals"]["qty2Pick"]   + $line["qty_ord"];
      $ret["Totals"]["qtyPicked"]  =$ret["Totals"]["qtyPicked"]   + $line["qty_ord"];
     }
     $i++;
   } // while i < numrows
   $cls="";
   if ($picked < $toPick) $cls="Alt2DataTD"; // red
   if ($picked > $toPick) $cls="Alt5DataTD"; // blue
   if ($picked == $toPick) $cls="Alt4DataTD"; // green
   if ($toPick == 0 and $picked == 0) $cls="Alt3DataTD"; // gray, no inventory
   $Items[$lineNum]["cls"]="class=\"{$cls}\"";
  } // end foreach lines
 } // end lines is an array and not empty
 $ret["Items"]=$Items;
 return $ret;
} // end countPickedItems

function isOrdComplete($db,$order)
{
 $ret=array("openLines"=>-1);
 $SQL=<<<SQL
select count(*) as cnt 
from ITEMPULL
where ord_num = {$order}
  and qty_picked <> qtytopick

SQL;
 
  $rc=$db->query($SQL);
  $numrows=$db->num_rows();
  $i=1;
  while ($i <= $numrows)
  {
   $db->next_record();
     if ($numrows)
     {
        $ret["openLines"]=$db->f("cnt");
     }
     $i++;
   } // while i < numrows
 return $ret;
 
} // end isOrdCompete

function getAllItemsOnOrder($db,$order)
{
 global $DEBUG;
 $SQL=<<<SQL
select * from ITEMS
where ord_num = {$order}
order by line_num

SQL;

  if ($DEBUG) wr_log("/tmp/PICK.log",$SQL);
  $rdata=array();
  $line=0;
  $rc=$db->query($SQL);
  $numrows=$db->num_rows();
  $i=1;
  while ($i <= $numrows)
  {
   $db->next_record();
     if ($numrows and $db->Record)
     {
      $line=$db->f("line_num");
      foreach ($db->Record as $key=>$data)
       {
        if (!is_numeric($key)) { $rdata[$line]["$key"]=$data; }
       }
     }
    $i++;
  } // while i < numrows
 return $rdata;

} // end getALlItemsOnOrder
function loadCnt($db,$SQL)
{
  $cnt=0;
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
} // end loadCnt

function chkToteOrder($db, $ref)
{
 global $DEBUG;
 
     $SQL=<<<SQL
select host_order_num from ORDERS where order_num = {$ref}
SQL;
  if ($DEBUG) wr_log("/tmp/PICK.log",$SQL);
  $rc=$db->query($SQL);
  $numrows=$db->num_rows();
  $i=1;
  while ($i <= $numrows)
  {
   $db->next_record();
     if ($numrows)
     {
        $hostnum=$db->f("host_order_num");
     }
     $i++;
   } // while i < numrows

} // end chkToteOrder

function bldOrderWhere($order,$fld="A.ord_num")
{
 if (is_array($order) and count($order) > 0)
 {
  $w="";
  $comma="";
  foreach ($order as $o)
  {
   $w.="{$comma}{$o}";
   $comma=",";
  }
  $where="where {$fld} in ({$w})";
 } // end order is array
 else $where="where {$fld} = {$order}";
 return $where;

} // end bldOrderWhere

?>
