<?php
// review_rma.php -- Review or Release to WMS return totes 

/*TODO

Add Close totes and release to WMS
Add return after tote release


*/
$self=$_SERVER["PHP_SELF"];

foreach (array_keys($_REQUEST) as $w) { $$w=$_REQUEST[$w]; } 
//error_reporting(0);
if (!isset($mdseType)) $mdseType="S";
if (!isset($itype)) $itype="";
if (!isset($bin)) $bin="";
if (!isset($scaninput)) $scaninput="";
if (!isset($tote)) $tote="";
if (!isset($lineToEdit)) $lineToEdit=0;


require_once("/usr1/include/db_sybase.php");
require_once("/usr1/include/get_table.php");
require_once("/usr1/include/update.php");
require("config.php");
require_once("rma_utils.php");
require_once("cl_PARTS2.php");
$base_prog=$_SERVER["PHP_SELF"];
$bsound="";

$db = NEW DB_Sybase;

if ($tote <> "")
{
 $msg="";
if (isset($FUNC) and $FUNC == "Release")
 { // release
  $host=$_SERVER["HTTP_ORIGIN"];
  $host="http://localhost"; //patch for White Bros
  $url="{$host}/wms/ret_rcpt.php?tote={$tote}";
  $output=file_get_contents($url);

echo $output;
exit;
 } // release
if (isset($FUNC) and $FUNC == "Update" and isset($qty) and isset($linenum))
{ // update record
 $SQL=<<<SQL
 update WMS_RDTL
 set qty = {$qty}
 where dtote_num = "{$tote}"
   and tote_line = {$linenum}
SQL;
 $rc=Update($db,$SQL);
 $msg="Updated {$rc} Records";
} // update record
if (isset($FUNC) and $FUNC == "Delete" and isset($linenum))
{ // delete record
 $SQL=<<<SQL
 delete WMS_RDTL
 where dtote_num = "{$tote}"
   and tote_line = {$linenum}
SQL;
 $rc=Update($db,$SQL);
 $msg="Deleted Line#  {$linenum}";
 if ($rc < 1) $msg="Error Deleting Record";
} // delete record

 $rc=chk_rmahdr($db,$tote);
 //status codes -1, new tote, 0 - tote found, >0 tote is closed out
 if ($rc == -1)
  { // addit
   $msg="Tote Not Found!";
  } // stat = -1  addit
 if ($rc > 0)
  { // tote is closed
    $msg="Tote has been closed, Can not modify!";
  } // tote is closed
 $rma=array();
 $SQL=<<<SQL
 select tote_num,
        convert(char(10),scan_date,101) as sDate,
        mdse_type
 from WMS_RETURNS
 where batch_status = 0
 and tote_num = "{$tote}"
 order by tote_num

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
        if (!is_numeric($key)) { $rma["$key"]=$data; }
       }
     }
    $i++;
  } // while i < numrows

 $SQL=<<<SQL
select dtote_num,
       tote_line,
       whse_loc,
       p_l,
       part_number,
       part_desc,
       qty,
       mdse_type,
       line_status
 from WMS_RDTL,PARTS
 where dtote_num = "{$tote}"
   and shadow_number = shadow
 order by tote_line
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
        if (!is_numeric($key)) { $rma["Items"][$i]["$key"]=$data; }
       }
     }
    $i++;
  } // while i < numrows
//echo "<pre>";
//print_r($rma);
//exit;
 if ($lineToEdit > 0) $title="Edit Line: {$lineToEdit} of Tote: {$tote}";
 else $title="Tote {$tote} Contents";
 $fc="style=\"color: black;\"";
 $th="class=\"FormHeaderFont\" {$fc}";
 $tc="class=\"FieldCaptionTD\" {$fc}";
 $td="class=\"DataTD\" {$fc}";
 $ta="class=\"AltDataTD\" {$fc}";
 $htm=<<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<title>{$title}</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<meta http-equiv="content-language" content="en" />
<meta name="google" content="notranslate" />
<meta name="viewport" content="initial-scale=0.75, width=device-width, user-scalable=yes" />

<link rel="stylesheet" href="include/wdi3.css">
<link rel="stylesheet" href="include/font-awesome.min.css">
<link href="/Bluejay/Themes/Multipads/Style.css" rel="stylesheet">


<style>
html,body,h1,h2,h3,h4,h5 {font-family: "Raleway", sans-serif}

.binbutton, .stockbutton, .defbutton, .corebutton {
    background-color: #a6a6a6;
    border: none;
    color: white;
    padding: 15px 32px;
    text-align: center;
    text-decoration: none;
    display: inline-block;
    font-size: 20px;
    margin: 4px 2px;
    cursor: pointer;
}
.stockbutton { background-color: #00b33c; }
.defbutton { background-color: #ff4000; }
.corebutton { background-color: #ffad33; }
label {font-family:Verdana,sans-serif;font-size:25px;}
</style>
</head>
<body class="w3-light-grey">
<div class="w3-main" style="margin-left:10px;margin-top:4px;">
 <br>
  <div class="w3-row-padding w3-margin-bottom">
   <div class="w3-half">
    <div class="w3-container w3-padding-8">
     <div class="w3-clear"></div>
     <h2 class="FormHeaderFont">{$title}</h2>
     <table border="1">
      <tr>
       <th {$tc}>Line</th>
       <th {$tc}>P/L</th>
       <th {$tc}>Part Number</th>
       <th {$tc}>Description</th>
       <th {$tc}>Qty</th>
       <th {$tc}>Location</th>
       <th {$tc}>Type</th>
       <th {$tc}>Action</th>
      </tr>
      <form name="form1" action="{$self}" method="post">
      <input type="hidden" name="tote" value="{$tote}">
      <input type="hidden" name="r" value="{$r}">
      <input type="hidden" name="lineToEdit" value="">
      <input type="hidden" name="FUNC" value="">
HTML;
 if ($lineToEdit < 1)
 {
   foreach ($rma["Items"] as $i=>$part)
   {
    $p_l=$part["p_l"];
    $pn=$part["part_number"];
    $pdesc=$part["part_desc"];
    $linenum=$part["tote_line"];
    $mt=disp_mtype($part["mdse_type"]);
    $loc=$part["whse_loc"];
    $cqty=$part["qty"];
    $htm.=<<<HTML
      <tr>
       <td {$td} align="right">{$linenum}</td>
       <td {$td}>{$p_l}</td>
       <td {$td}>{$pn}</td>
       <td {$td}>{$pdesc}</td>
       <td {$td} align="right">{$cqty}</td>
       <td {$td}>{$loc}</td>
       <td {$td}>{$mt}</td>
       <td {$td}><a href="#" onclick="do_edit({$linenum});">Edit</a></td>
      </tr>

HTML;
   } // end foreach rma lines
  $htm.=<<<HTML
    </table>
    <p align="center">
     <input class="binbutton" type="button" value="Done" onclick="do_done();">
     <input class="binbutton" type="button" value="Release" title="Release to WMS" onclick="do_release();">
</p>
 
HTML;
 } // lineto edit < 1
 else
 { // in edit mode
   $part=$rma["Items"][$lineToEdit];
   $p_l=$part["p_l"];
   $pn=$part["part_number"];
   $pdesc=$part["part_desc"];
   $linenum=$part["tote_line"];
   $mt=disp_mtype($part["mdse_type"]);
   $loc=$part["whse_loc"];
   $cqty=$part["qty"];
   $htm.=<<<HTML
      <input type="hidden" name="linenum" value="{$linenum}">
      <tr>
       <td {$td} align="right">{$linenum}</td>
       <td {$td}>{$p_l}</td>
       <td {$td}>{$pn}</td>
       <td {$td}>{$pdesc}</td>
       <td {$td} align="right"><input type="number" min="0" max="99999" name="qty" value="{$cqty}"></td>
       <td {$td}>{$loc}</td>
       <td {$td}>{$mt}</td>
       <td {$td}>&nbsp;</a></td>
      </tr>
     </table>
     <p align="center"><input class="binbutton" type="button" value="Update" onclick="do_update();">
       <input class="binbutton" type="button" value="Delete" onclick="verify_del();">
       <input class="binbutton" type="button" value="Cancel" onclick="do_cancel();">
     </p>

HTML;

 } // in edit mode
 $htm.=<<<HTML
      </form>
     </div>
    </div>
   </div>
  </div>
<script>
function do_edit(ln)
 {
  document.form1.lineToEdit.value=ln;
  document.form1.submit();
 }
function do_update()
{
 document.form1.FUNC.value="Update";
 document.form1.submit();
}
function do_release()
{
 document.form1.FUNC.value="Release";
 document.form1.submit();
}
function do_cancel()
{
 document.form1.FUNC.value="";
 document.form1.submit();
}
function do_done()
{
 window.location.href="{$r}";
}
function verify_del()
{
if (confirm("Are you Sure you want to Delete this Item?"))
{
 document.form1.FUNC.value="Delete";
 document.form1.submit();
 return(true);
}
else
{
return(false);
}
 
}
</script>
</body>
</html>
HTML;
echo $htm;
} // end $tote <> ""
 
function tote_input($part,$bin,$scan_type,$qty,$color,$msg="")
{
 $m="";
 if (trim($msg) <> "")
  {
   $m=<<<HTML
<span style="font-color: red;}">{$msg}</span><br>
HTML;
  }
   $htm=<<<HTML
        {$m}
        <table>
         <tr>
          <td>{$part["p_l"]}</td>
          <td>{$part["part_number"]}</td>
          <td>{$part["part_desc"]}</td>
         </tr>
         <tr>
          <td colspan="3">
        <span style="font-weight: 900;font-size: 65px;{$color}">{$bin}</span>
</td>
        </table>
        <label>Scan {$scan_type}</label>
        <input type="text" name="scaninput" value="" style="text-transform:uppercase" onchange="do_bin();">
        <input type="hidden" name="itype" value="T">
        <input type="hidden" name="bin" value="{$bin}">
        <input type="hidden" name="shadow" value="{$part["shadow_number"]}">
        <input type="hidden" name="pl" value="{$part["p_l"]}">
        <input type="hidden" name="part_number" value="{$part["part_number"]}">
        <input type="hidden" name="part_desc" value="{$part["part_desc"]}">
        <input type="hidden" name="pnum" value="{$part["alt_part_number"]}">
        <input type="hidden" name="qty" value="{$qty}">

HTML;
 return($htm);
} // end tote_input
function get_pl($db,$pl,$comp)
{
 $pldesc="";
 $SQL=<<<SQL
select pl_desc from PRODLINE
where pl_code = "{$pl}"
and pl_company = {$comp}

SQL;
 $rc=$db->query($SQL);
  $numrows=$db->num_rows();
  $i=1;
  while ($i <= $numrows)
  {
   $db->next_record();
     if ($numrows)
     {
            $pldesc=$db->f("pl_desc");
     }
     $i++;
   } // while i < numrows
return($pldesc);
} // end get_pl
?>
