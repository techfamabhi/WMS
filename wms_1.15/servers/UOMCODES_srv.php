<?php

// UOMCODES_srv.php -- Server for UOMCODES.php
//12/10/21 dse initial
//02/09/22 dse move field defs to function in srv_hdr.php


$update_table="UOMCODES";
$query_table="UOMCODES";
$DEBUG=true;
$u=true;

require("srv_hdr.php");

if (isset($_REQUEST["searcH"])) $srch=$_REQUEST["searcH"]; else $srch="";
$comp=0;
if ($srch <> "") $comp=intval($srch);
if ($DEBUG) wr_log("/tmp/UOM.log","inputData={$inputdata}");
$action=$reqdata["action"];
if (isset($reqdata["uom_code"])) $uom_code=$reqdata["uom_code"]; else $uom_code="";
if (isset($reqdata["orig_uom"])) $orig_uom=$reqdata["orig_uom"]; else $orig_uom=$uom_code;
// set table def and select and update fields
$uFlds=setFldDef($db,$update_table);
if ($query_table == $update_table) { $qFlds=$uFlds; }
 else                              { $qFlds=setFldDef($db,$query_table); }

$upd_flds=setFlds($db,$uFlds);
$sel_flds=setFlds($db,$qFlds);


if ($DEBUG) wr_log("/tmp/UOM.log","Switching={$action}");
switch ($action)
{
 case "fetchall":
 case "fetchSingle":
 {
  $where="";
  $order_by="order by uom_inv_code,uom_code";
  if ($uom_code <> "") $where=<<<SQL
where uom_code = "{$uom_code}"

SQL;

  $SQL=<<<SQL
select
{$sel_flds}
from {$query_table}
{$where}
{$order_by}

SQL;
  if ($DEBUG) wr_log("/tmp/UOM.log","SQL=" . $SQL);

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
       if (!is_numeric($key)) 
        {
         if ($action == "fetchSingle") { $rdata[$key]=$data; }
         else { $rdata[$i]["$key"]=$data; } 
         if ($key == "uom_inv_code")
         {
          $rdata[$i]["uom_icode_desc"]="Regular Inventory";
          if ($data == "1") $rdata[$i]["uom_icode_desc"]="Defective Inventory";
          if ($data == "2") $rdata[$i]["uom_icode_desc"]="Core Inventory";
         }
        } // key is not numeric
       }
     }
    $i++;
  } // while i < numrows
  if (isset($x)) unset($x);
  $x=json_encode($rdata);
  if ($DEBUG) wr_log("/tmp/UOM.log",$SQL);
  if ($DEBUG) wr_log("/tmp/UOM.log",$x);
  echo $x;
   break;
 } // end fetchs

 case "update":
 case "insert":
 {
  $where=<<<SQL
where uom_code = "{$orig_uom}"

SQL;
 $rdata=$upd->updRecord($reqdata,$update_table,$where);
  if ($DEBUG) wr_log("/tmp/UOM.log",$rdata);
  echo $rdata;
 break;
 } // end update

 case "delete":
 {
  $where=<<<SQL
where uom_code = "{$orig_uom}"

SQL;

 $reqdata["action"]=1;
 //$rdata=$upd->updRecord($reqdata,$update_table,$where);
 // for some reason this does not work, even though the class calls the 
 // same function, it logs the delete but noting comes back
 $rdata=$upd->delRecord($update_table,$where);
 echo $rdata;
 break;
 } // end delete
} // end switch reqdata action

?>
