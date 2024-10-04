<?php
// rma_utils.php -- Utilities for rma processing
// 09/17/19 dse initial

/*
add_rmahdr($db,$tote,$comp,$mdsetype,$by="0") -- add a new rmahdr
chk_rmahdr($db,$tote) -- check status of rma hdr
save_dtl($db,$tote,$part,$bin,$mdseType,$avail,$alloc) -- Save detail
get_rmahdrs($db,$db1) -- get info on open rma hdrs
get_lines($db,$tote) -- get max of line
disp_mtype($in) -- return descriptive mdse type
get_lineloc($db,$tote) -- get all the major loc in the lines
*/

function add_rmahdr($db,$tote,$comp,$mdsetype,$by="0")
{
 $ret=0;
 $SQL=<<<SQL
if not exists (select * from WMS_RETURNS where tote_num = "{$tote}")
 insert into WMS_RETURNS 
 (tote_num, scan_date,scan_by,company,mdse_type,batch_status)
 values ("{$tote}",getdate(),"{$by}",{$comp},"{$mdsetype}",0)

SQL;
 $ret=$db->Update($SQL);
 return($ret);
} // end add_rmahdr
function chk_rmahdr($db,$tote)
{
 $ret=-1;
 $SQL=<<<SQL
 select batch_status from WMS_RETURNS
 where tote_num = "{$tote}"

SQL;
  $rc=$db->query($SQL);
  $numrows=$db->num_rows();
  $i=1;
  while ($i <= $numrows)
  {
   $db->next_record();
     if ($numrows)
     {
            $ret=$db->f("batch_status");
     }
     $i++;
   } // while i < numrows
 return($ret);
} // end function chk_rmahdr

function save_dtl($db,$tote,$part,$bin,$mdseType,$avail,$alloc)
{
 $ret=0;
 $nlines=get_lines($db,$tote);
 if ($nlines < 0)
 { // an error occurred, tote not found
  echo "<pre>Tote Not Found</pre>";
  exit;
 } // an error occurred, tote not found
 $nlines++;
 $q=-$part["alt_type_code"];
 $ty=$mdseType;
 $bn=$bin;
 if ($ty == "C") $bn="c";
 if ($ty == "D") $bn="d";
$SQL=<<<SQL
 if exists (select * from WMS_RDTL where dtote_num ="{$tote}" and shadow = {$part["shadow_number"]} and mdse_type = "{$ty}")
 update WMS_RDTL set qty = qty + {$q}
 where dtote_num ="{$tote}" and shadow = {$part["shadow_number"]} and mdse_type = "{$ty}"
 else
 insert into WMS_RDTL
 ( dtote_num, tote_line, whse_loc, shadow, qty, qty_avail,
    qty_alloc, line_status, mdse_type, qty_credited)
 values ( "{$tote}",{$nlines},"{$bn}",{$part["shadow_number"]},{$q},{$avail},
  {$alloc},0,"{$ty}",0)
SQL;
 $ret=$db->Update($SQL);
 return($ret);
} // end save_dtl
function get_rmahdrs($db,$db1)
{
 $ret=array();
 $SQL=<<<SQL
 select tote_num,
        convert(char(10),scan_date,101) as sDate,
        mdse_type
 from WMS_RETURNS
 where batch_status = 0
 order by tote_num

SQL;
  $rc=$db->query($SQL);
  $numrows=$db->num_rows();
  $i=1;
  while ($i <= $numrows)
  {
   $db->next_record();
     if ($numrows)
     {
        $t=$db->f("tote_num");
        $ret[$i]["tote"]=$t;
        $ret[$i]["date"]=$db->f("sDate");
        $ret[$i]["type"]=$db->f("mdse_type");
        $ret[$i]["lines"]=get_lines($db1,$t);
        $ret[$i]["loc"]=get_lineloc($db1,$t);
     }
     $i++;
   } // while i < numrows
 return($ret);
} // end function get_rmahdrs

function get_lines($db,$tote)
{
  $SQL=<<<SQL
select isnull(max(tote_line),0) as nlines from WMS_RDTL
 where dtote_num = "{$tote}"

SQL;
  $nlines=-1;
  $rc=$db->query($SQL);
  $numrows=$db->num_rows();
  $i=1;
  while ($i <= $numrows)
  {
   $db->next_record();
     if ($numrows)
     {
            $nlines=$db->f("nlines");
     }
     $i++;
   } // while i < numrows
 return($nlines);
}
function disp_mtype($in)
{
 $mdd="";
 switch($in)
  {
   case "D":
    $mdd="Defective";
    break;
   case "C":
    $mdd="Core";
    break;
   default:
    $mdd="Stock";
    break;
  } // end switch mdsetype
 return($mdd);
} // end disp_mtype
function get_lineloc($db,$tote)
{
 $ret="";
   $SQL=<<<SQL
select distinct 
isnull(whse_loc,"") as whse_loc from WMS_RDTL
 where dtote_num = "{$tote}"

SQL;
  $nlines=-1;
  $rc=$db->query($SQL);
  $numrows=$db->num_rows();
  $i=1;
  while ($i <= $numrows)
  {
   $db->next_record();
     if ($numrows)
     {
           $maj=substr($db->f("whse_loc"),0,1);
           if ($maj == "c") $maj="Core";
           if ($maj == "d") $maj="Def";
           $comma=" ";
           if (strlen($ret) > 0) $comma=",";
           if (!strpos($ret,$maj)) $ret.="{$comma}{$maj}";
     }
     $i++;
   } // while i < numrows
 return($ret);
  
} // end function get_lineloc
?>
