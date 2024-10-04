<?php
function chk_bin($db,$comp,$in)
{
 $in=substr($in,0,8); //chop off anything after 8 chars
 $ret=array();
 $SQL=<<<SQL
  select wmsbin
  from tmp_bin_xref
  where bin  = "{$in}"

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
return($ret);
} // end chk_bin
function frmt_bin($in)
{
 if (is_numeric(substr($in,1,1))) //normal old style bin
 {
 $out=substr($in,0,1) . " " .
      substr($in,1,2) . " " .
      substr($in,3,2) . " " .
      substr($in,5,1) . " " .
      substr($in,6,2) . " ";
 }
 else if (is_numeric(substr($in,2,1))) // 2 char major loc
 {
  $out=substr($in,0,2) . " " .
       substr($in,2,1) . " " .
       substr($in,3,2) . " " .
       substr($in,5,1) . " " .
       substr($in,6,2) . " ";
 }
 else // 3 char major loc
 {
  $out=substr($in,0,3) . " " .
       substr($in,3,1) . " " .
       substr($in,4,2) . " " .
       substr($in,6,1) . " " .
       substr($in,7,1) . " ";
 }
 return($out);

}

function chk_part($db,$pnum,$comp)
{
 global $thisprogram;
 global $batchnum;
 global $whseloc;
 global $oldloc;
if (isset($pnum))
{
 $part=get_part($db,trim(strtoupper($pnum)));
 $numparts=$part["num_rows"];
 $bgsound="";
 if ($part["status"]==-35)
 {
  $numparts=1;
  $shadow=0;
  $pl="???";
  $part[1]["p_l"]=$pl;
  $part[1]["part_number"]=$pnum;
  $part[1]["shadow_number"]=0;
  $part[1]["part_desc"]="Not Found!";
  $part[1]["alt_type_code"]=0;
  $pnum="";
  if (isset($playsound) and $playsound > 0) $bgsound=<<<HTML
<audio controls autoplay hidden>
  <source src="/Bluejay/sounds/psycho.wav" type="audio/wav">
</audio>
HTML;
  } // end of -35

 $i=1;
 $choose_htm="";
 if ($pnum=="") {$numparts=1;}
 if ($numparts > 1) //More than 1 part found, set choose_htm
 {
  $th="class=\"FormHeaderFont\" ";
  $tc="class=\"FieldCaptionTD\" ";
  $td="class=\"DataTD\" ";
  $td="class=\"w3-bar-block\" ";
  $ta="class=\"AltDataTD\" ";
  $bsound="";
  $choose_htm=<<<HTML
 <form method="post" name="form2" action="{$thisprogram}">
  <input type="hidden" name="form_name" value="form2">
  <input type="hidden" name="eline" value="">
  <input type="hidden" name="batchnum" value="{$batchnum}">
  <input type="hidden" name="whseloc" value="{$whseloc}">
  <input type="hidden" name="oldloc" value="{$oldloc}">
  <input type="hidden" name="comp" value="{$comp}">
  <input type="hidden" name="shd" value="">
  <input type="hidden" name="cqty" value="">
     <div class="w3-half">
      <div class="w3-container w3-yellow w3-padding-8">
        <div class="w3-clear"></div>
          <div class="w3-container w3-white">Please Choose Line Code!</div>

  <table border="1" width="40%">
   <tr>
    <th {$tc}>Select</th>
    <th {$tc}>P/L</th>
    <th {$tc}>Part#</th>
    <th {$tc}>Description</th>
   </tr>
   {$bsound}
HTML;
 while ($i <= $numparts)
 {
  $p_l=$part[$i]["p_l"];
  $pn=$part[$i]["part_number"];
  $shd=$part[$i]["shadow_number"];
  $pdesc=$part[$i]["part_desc"];
  $upc=$part[$i]["alt_part_number"];
  $cqty=1;
  $atype=$part[$i]["alt_type_code"];
  if ($atype < 0 ) { $cqty= -$atype; };
  $choose_htm.=<<<HTML
   <tr>
    <td {$td}><input type="button" name="pnum" value="{$p_l}{$pn}" onclick="do_choose({$shd},{$cqty},{$i});"></td>
    <td {$td}>{$p_l}</td>
    <input type="hidden" name="cshadow[{$i}]" value="{$shd}">
    <input type="hidden" name="dupeupc[{$i}]" value="{$upc}">
    <td nowrap {$td}>{$pn}</td>
    <td nowrap {$td}>{$pdesc}</td>
   </tr>

HTML;
  $i++;
 }
$choose_htm.=<<<HTML
    <input type="hidden" name="choice" value=""
 </table>
      </div>
    </div>
   </div>
  </div>
 <input type="hidden" name="oldupc" value="{$upc}">
</form>

HTML;

} // if numparts > 1 More than 1 part found, set choose_htm
 if ($numparts==1)
 {
  $th="class=\"FormHeaderFont\" ";
  $tc="class=\"FieldCaptionTD\" ";
  $td="class=\"DataTD\" ";
  $ta="class=\"AltDataTD\" ";
  $tv="class=\"DataTD\" ";
  $shadow=$part[1]["shadow_number"];
  $mst=get_mstqty($db,$comp,$shadow);
  if (isset($oldupc)) { $part[1]["alt_part_number"]=$oldupc; }
  $i=1;
  $qty=1;
  $atype=$part[$i]["alt_type_code"];
  if ($atype < 0 ) { $qty= -$atype; };
  $tdd=$td;
  if ($shadow == 0) { $tdd=$ta;
 }

 $pnum="";

 $formname="form1b";
 $plchk="";
 if (isset($playsound)) $plchk="checked";
 $th="class=\"FormHeaderFont\" ";
 $tc="class=\"FieldCaptionTD\" ";
 $td="class=\"DataTD\" ";
 $ta="class=\"AltDataTD\" ";
 $tv="class=\"DataTD\" ";
 
 if ($shadow == 0) $tdd="class=\"Alt2DataTD\""; else $tdd=$td;
 if ($shadow > 0) 
 {
  //swith to add to biscan table
 //$rc=addupd_line($db,$batchnum,$PM,$qty,$Rtype,$tmp["Last_12"]);
  $rc=array(0=>0,1=>$qty,2=>0);
 if ($rc[1] <> $qty) $qty=$rc[1];
 $line=$rc[2];
 }
 else
 {
  $qty="";
  $line="";
 }
 $valid="OK";
 $vt="";

  $part_htm=<<<HTML
{$part[1]["p_l"]} {$part[1]["part_number"]} {$part[1]["part_desc"]}
HTML;

 } // end if numparts=1
} // end isset pnum


$htm=<<<HTML
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<link href="/Bluejay/Themes/Multipads/Style.css" type="text/css" rel="stylesheet">
HTML;
$part["choose"]=$choose_htm;
return($part);
} // end chk_part

function get_part($db,$pnum_in)
{
  $ret=array();
  $ret["status"]=0;
  $ret["num_rows"]=0;
  $i=0;
  $qstring=<<<SQL
SELECT alt_part_number,alt_type_code, part_desc, p_l,
 part_number, unit_of_measure, shadow_number, num_supercedes,
 num_interchanges, ord_hdr_bucket, part_seq_num, part_category,
 part_long_desc, part_class, part_weight,
 convert(char(10),sale_date_from,101) as sale_on_date,
 convert(char(10),sale_date_thru, 101)as sale_off_date,
 sale_price_code, part_returnable, qty_per_car,
 broken_pack_chrg, restocking_fee, part_kit_type, part_min_gp,
 part_cf_flag, qty_break_flag, price13,price14
 FROM ALTERNAT,PARTS
 WHERE alt_part_number like "{$pnum_in}"
 AND  shadow_number = alt_shadow_num
SQL;

  $rc=$db->query($qstring);
  $numrows=$db->num_rows();
  $ret["num_rows"]=$numrows;
$i=1;
 while ($i <= $numrows)
 {
    $db->next_record();
    $ret[$i]["alt_part_number"]=$db->f("alt_part_number");
    $ret[$i]["alt_type_code"]=$db->f("alt_type_code");
    $ret[$i]["part_desc"]= $db->f("part_desc");
    $ret[$i]["p_l"]= $db->f("p_l");
    $ret[$i]["part_number"]= $db->f("part_number");
    $ret[$i]["unit_of_measure"]= $db->f("unit_of_measure");
    $ret[$i]["shadow"]=$db->f("shadow_number");
    $ret[$i]["shadow_number"]= $db->f("shadow_number");
    $ret[$i]["num_supercedes"]= $db->f("num_supercedes");
    $ret[$i]["num_interchanges"]= $db->f("num_interchanges");
    $ret[$i]["ord_hdr_bucket"]= $db->f("ord_hdr_bucket");
    $ret[$i]["part_seq_num"]= $db->f("part_seq_num");
    $ret[$i]["part_category"]= $db->f("part_category");
    $ret[$i]["part_long_desc"]= $db->f("part_long_desc");
    $ret[$i]["part_class"]= $db->f("part_class");
    $ret[$i]["part_weight"]= $db->f("part_weight");
    $ret[$i]["sale_on_date"]= $db->f("sale_on_date");
    $ret[$i]["sale_off_date"]= $db->f("sale_off_date");
    $ret[$i]["sale_price_code"]= $db->f("sale_price_code");
    $ret[$i]["part_returnable"]= $db->f("part_returnable");
    $ret[$i]["qty_per_car"]= $db->f("qty_per_car");
    $ret[$i]["broken_pack_chrg"]= $db->f("broken_pack_chrg");
    $ret[$i]["restocking_fee"]= $db->f("restocking_fee");
    $ret[$i]["part_kit_type"]= $db->f("part_kit_type");
    $ret[$i]["part_min_gp"]= $db->f("part_min_gp");
    $ret[$i]["part_cf_flag"]= $db->f("part_cf_flag");
    $ret[$i]["qty_break_flag"]= $db->f("qty_break_flag");
    $ret[$i]["price13"]= $db->f("price13");
    $ret[$i]["price14"]= $db->f("price14");
    $i++;
   }
  if ($ret["num_rows"] == 0) { $ret["status"]=-35; }
  return($ret);
} // end get_part

function get_mstqty($db,$comp,$shadow)
{
 $ret=array();
 $SQL=<<<SQL
 select 
 whse_location,
 qty_avail,
 qty_alloc
 from MSTQTY
 where ms_shadow = $shadow
 and ms_company = $comp


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
        if (!is_numeric($key)) { $ret["$key"]=$data; }
       }
     }
    $i++;
  } // while i < numrows
return($ret);
} // end get_mstqty

function addupd_line($db,$rma,$part,$qty,$Rtype,$last12)
{
  $rcc=array(0=>0,1=>$qty,2=>0);
  $linenum=-1; 
 //check if part exists on rma
 $SQL=<<<SQL
select rmd_line_num,rmd_qty
from RMA_DTL
where rmd_number = {$rma}
and rmd_shadow = {$part->Data["shadow_number"]}
and rmd_type ="{$Rtype}"
SQL;


  $rc=$db->query($SQL);
  $numrows=$db->num_rows();
  $i=1;
  while ($i <= $numrows)
  {
   $db->next_record();
     if ($numrows)
     {
            $linenum=$db->f("rmd_line_num");
            $oqty=$db->f("rmd_qty");
     }
     $i++;
   } // while i < numrows

if ($linenum < 0)
{ //add new line
  $SQL=<<<SQL
  select rma_num_lines from RMA_HDR where rma_number = {$rma}

SQL;
//echo $SQL;

  $rc=$db->query($SQL);
  $numrows=$db->num_rows();
  $i=1;
  while ($i <= $numrows)
  {
   $db->next_record();
     if ($numrows)
     {
            $linenum=$db->f("rma_num_lines");
     }
     $i++;
   } // while i < numrows

if ($linenum < 0)
{
  echo "Header Not Found!";
  exit;
}

 $linenum++;
  //check part and type in rma dtl, if there add to qty, else increment line#
 //insert SQL
 $iSQL=<<<SQL
  insert into RMA_DTL
  (
    rmd_number,
    rmd_line_num,
    rmd_shadow,
    rmd_pl,
    rmd_part_number,
    rmd_desc,
    rmd_qty,
    rmd_type,
    rmd_last_12,
    rmd_act_type, rmd_line_stat, rmd_orig_comp, rmd_orig_inv, rmd_orig_order, 
    rmd_orig_line, rmd_mdse_price, rmd_def_price, rmd_core_price,
    rmd_mdse_prsc, rmd_def_prsc, rmd_core_prsc
   )
   values(
    {$rma},
    {$linenum}, 
    {$part->Data["shadow_number"]},
    "{$part->Data["p_l"]}",
    "{$part->Data["part_number"]}",
    "{$part->Data["part_desc"]}",
    {$qty},
    "{$Rtype}",
    {$last12},
    "",0,0,"",0,
    0,0.00,0.00,0.00,
    "","","")

   update RMA_HDR set rma_num_lines={$linenum} where rma_number = {$rma}
SQL;
 $rcc[0]=$db->Update($iSQL);
}  //add new line
else
{ // update line
  $nqty=$oqty + $qty;
  $rcc[1]=$nqty;
 $uSQL=<<<SQL
update RMA_DTL set rmd_qty = {$nqty}
where rmd_number = {$rma}
and rmd_line_num = {$linenum}
and rmd_shadow = {$part->Data["shadow_number"]}
and rmd_type ="{$Rtype}"

SQL;
 $rcc[0]=$db->Update($uSQL);
} // update line
$rcc[2]=$linenum;
return($rcc);
} // end addupd_line

function frmt_Nline($line,$pl,$part,$desc,$qty,$ty,$valid,$title,$dclass)
{
 if ($pl == "???") $valid="";
 $bgsound="";
 $cls="DataTD";
 if (trim($dclass)=="") $dclass="class=\"DataTD\"";
 $link=<<<HTML
<a href="#" onclick="do_edit('rma_edit.php',{$line});">Edit</a>
HTML;
 $dty=$ty;
 if ($ty == "C" and $line == "") 
 { // it's a core part with no value
   $dty="&nbsp;";
   $link="&nbsp;";
   $cls="Alt2DataTD";
   $dclass="class=\"Alt2DataTD\"";
   $bgsound=<<<HTML
<audio controls autoplay hidden>
  <source src="/Bluejay/sounds/psycho.wav" type="audio/wav">
</audio>
HTML;

 } // it's a core part with no value
 $htm=<<<HTML
<tr>
 <td width="5%" class="{$cls}" >{$line}</td>
 <td width="5%" class="{$cls}" >{$qty}</td>
 <td width="5%" class="{$cls}" ><strong>{$pl}</strong></td>
 <td width="20%" class="{$cls}" ><strong>{$part}</strong></td>
 <td width="30%" class="{$cls}" ><strong>{$desc}</strong></td>
 <td width="5%" class="{$cls}" >{$dty}</td>
 <td width="5%" class="{$cls}" >{$link}</td>
 <td width="50%" nowrap {$title}{$dclass}>{$valid}{$bgsound}</td>
</tr>

HTML;
 return($htm);
} // end frmt_Nline
function load_dtl($db,$batchnum,$line="")
{
$part_htm="";
$extra="";
if (isset($rmalines)) unset($rmalines);
$rmalines=array();
if ($line <> "")
{ //not invalid
 $extra="and rmd_line_num <> {$line}";
} //not invalid
$SQL=<<<SQL
select rmd_line_num,rmd_pl,rmd_part_number,rmd_desc,rmd_qty,rmd_last_12,rmd_type
from RMA_DTL
where rmd_number = {$batchnum}
{$extra}
order by rmd_number,rmd_line_num

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
        if (!is_numeric($key)) { $rmalines[$i]["$key"]=$data; }
       }
     }
    $i++;
  } // while i < numrows
if (count($rmalines))
{ // have existing lines
 foreach ($rmalines as $key=>$litem)
 {
$valid="OK";
$vt="";
$tv="";
if ($litem["rmd_type"] <> "C") if ($litem["rmd_last_12"] < 1) 
 {
  $valid="Can't Find Purchase Record!";
  $tv="class=\"Alt2DataTD\" ";
  $vt="title=\"Customer Has Not Bought this Part!\"";
 }
$part_htm.=frmt_Nline($litem["rmd_line_num"],$litem["rmd_pl"],$litem["rmd_part_number"],$litem["rmd_desc"],$litem["rmd_qty"],$litem["rmd_type"],$valid,$tv,$vt);

 } // end foreach rmalines
} // have existing lines
return($part_htm);
} // end load_dtl
function add_batch($db,$batchnum,$comp,$user)
{
$SQL=<<<SQL
insert into WDI_BINSCAN
 (batch_num, scan_date, scan_by, company, batch_status)
values({$batchnum},getdate(),"{$user}",$comp,0)

SQL;
 $rc=$db->Update($SQL);
 return($rc);
} // end add_batch
function add_binscan($db,$batchnum,$whseloc,$shadow,$qty_avail,$qty_alloc,$sqty,$btype="M")
{ 
 $bline=0;
 $mode="upd";
 $SQL=<<<SQL
select batch_line,qty
from WDI_ASGBIN
where batch_num = {$batchnum}
and shadow = {$shadow}
and whse_loc = "{$whseloc}"
SQL;
  $rc=$db->query($SQL);
  $numrows=$db->num_rows();
  $i=1;
  while ($i <= $numrows)
  {
   $db->next_record();
     if ($numrows)
     {
            $bline=$db->f("batch_line");
            $qty  =$db->f("qty");
     }
     $i++;
   } // while i < numrows

if ($bline < 1)
{
 $mode="add";
 $SQL=<<<SQL
select isnull(max(batch_line),0) as line_num from WDI_ASGBIN
where batch_num = {$batchnum}
SQL;

  $rc=$db->query($SQL);
  $numrows=$db->num_rows();
  $i=1;
  while ($i <= $numrows)
  {
   $db->next_record();
     if ($numrows)
     {
            $bline=$db->f("line_num") + 1;
     }
     $i++;
   } // while i < numrows
 $qty=0;
} // end bline < 1
 if ($mode == "upd")
 {
  $sqty=$sqty + $qty;
  $SQL=<<<SQL
   update WDI_ASGBIN set qty = {$sqty}
   where batch_num = $batchnum
    and batch_line = {$bline}
    and shadow = {$shadow}
    and whse_loc = "{$whseloc}"

SQL;
  $rc=$db->Update($SQL);
 } // end upd mode
else
 { //add
  $SQL=<<<SQL
 insert into WDI_ASGBIN
 (batch_num, batch_line, whse_loc, bin_type, shadow,
  qty, qty_avail, qty_alloc , line_status)
values ({$batchnum},{$bline},"{$whseloc}","{$btype}",{$shadow},
        {$sqty},{$qty_avail},{$qty_alloc},0)
SQL;
  $rc=$db->Update($SQL);
 } // end add mode
 return($rc);
} // end add_binscan

function log_error($db,$batchnum,$type,$lbin,$bin,$upc)
{
// 1=BIN NOF, 2=upc NOF, 3=No UPC on box, 4 Duplicate UPC found, 5=Shadow not chosen
 $SQL=<<<SQL
insert into WDI_BINERROR
(batch_num, ex_type, last_bin, this_bin, upc)
values({$batchnum},{$type},"{$lbin}","{$bin}","{$upc}")
SQL;
 $rc=$db->Update($SQL);
 return($rc);
} // end log_error
?>
