<?php
//05/31/2017 dse Add min gp
//10/16/18 dse fix fieldname of part_long_desc

foreach (array_keys($_REQUEST) as $w) {
    $$w = $_REQUEST[$w];
}
error_reporting(0);

session_start();
require($_SESSION["wms"]["wmsConfig"]);
require_once("{$wmsInclude}/cl_Bluejay.php");
$nextprog = "proc_upc.php";
$thisprog = "upc_upl.php";
$pg = new Bluejay;

if (isset($_REQUEST["Error"])) $Error = $_REQUEST["Error"]; else $Error = "";
if (isset($_REQUEST["referer"])) $referer = $_REQUEST["referer"]; else $referer = "";
$pg->title = "Upload UPC and Case Bar Codes";
$title = $pg->title;
$pg->js = <<<HTML
<script>
function do_upload() {
       if (document.fupload.FileUpload1_File.value != "" )
         { 
          document.fupload.submit();
         }
}
</script>
HTML;
$pg->Display();
$UserPriv = $spriv_thru;
$UserOper = $operator;
$hidden = "";
$fldname = "";
$fvals = "";
$allfields = load_fields();
$d1field = array();
if (isset($pfield)) {
    foreach ($pfield as $fld) {
        $hdr = $allfields[$fld]["hdr"];
        $prompt = $allfields[$fld]["prompt"];
        $d1field[$fld] = $allfields[$fld];
        $hidden .= <<<HTML
<input type="hidden"  name="pfields[]" value="{$fld}"/>

HTML;
        $fldname .= <<<HTML
   <th nowrap>{$hdr}</th>

HTML;
        $fvals .= <<<HTML
    <td><i>value</i></td>

HTML;
    } // end foreach pfield
} // isset pfield
else $pfield[0] = "mfg_pop_class";

$dis_flds = <<<HTML
   <p>Check the fields below to Upload</p>
   <table class="FormTABLE" cellspacing="2" cellpadding="2">
    <tr>
     <td width="2%" class="ColumnTD"></td>
     <td width="25%" class="ColumnTD">Field</td>
     <td width="50%" class="ColumnTD">Sample Values</td>
    </tr>
    <tr>
     <td width="2%" class="DataTD"><input  type="checkbox" name="" checked disabled></td>
     <td width="25%" class="DataTD">P/L</td>
     <td width="50%" class="DataTD"><strong>REQUIRED</strong></td>
    </tr>
    <tr>
     <td width="2%" class="DataTD"><input  type="checkbox" name="" checked disabled></td>
     <td width="25%" class="DataTD">Part Number</td>
     <td width="50%" class="DataTD"><strong>REQUIRED</strong></td>
    </tr>
    <tr>
     <td width="2%" class="DataTD"><input  type="checkbox" name="" checked disabled></td>
     <td width="25%" class="DataTD">UPC/Barcode</td>
     <td width="50%" class="DataTD"><strong>REQUIRED</strong></td>
    </tr>

HTML;
foreach ($allfields as $fld => $p) {
    $prompt = $p["hdr"];
    $title = $p["prompt"];
    $sel = "";
    if (isset($d1field[$fld])) $sel = "checked";
    $dis_flds .= <<<HTML
  <tr title="{$title}">
   <td><input {$sel} onclick="document.setfield.submit();" class="DataTD" type="checkbox" name="pfield[]" value="{$fld}"></td>
   <td class="DataTD">{$prompt}</td>
   <td class="DataTD">{$title}</td>
  </tr>

HTML;
} // end foreach allfields

$dis_flds .= <<<HTML
   </table>

HTML;
$file_upload = <<<HTML
{$hidden}
<input type="file" size="48" name="FileUpload1_File" onchange="do_upload();"/>
HTML;
//if ($fldname == "") $file_upload="&nbsp;";

$htm = <<<HTML
<table width="60%">
<tr>
 <td valign="top" width="50%">

  <form name="fupload" action="{$nextprog}" method="post" enctype="multipart/form-data" autocomplete="off">
  <font color="#666699">
  <h4>{$pg->title}</h4>
  <input type="hidden" value="{$fname}" name="FileUpload1"/>
  <input type="hidden" value="{$PL}" size="3" name="PL"/>
  {$file_upload}
&nbsp;&nbsp;
  </font>
 </form>
  <p>File must be an Excel spreadsheet and contain the following columns.<br><strong>Please include the Headings</strong>&nbsp; <br>
 <h4 style="color:red">Columns Must be in the same order as below!&nbsp; </h4>
</p>
  <table style="padding: 15px;" border="1">
   <tr>
    <th>&nbsp;P/L&nbsp;</th>
    <th>&nbsp;Part Number&nbsp;</th>
    <th>&nbsp;UPC&nbsp;</th>
   {$fldname}
   </tr>
   <tr>
    <td>ABC</td>
    <td>123-456</td>
    <td>012345123456</td>
    {$fvals}
   </tr>
   <tr>
    <td>ABC</td>
    <td>123-457</td>
    <td>012345123457</td>
    {$fvals}
   </tr>
   <tr>
    <td>ABC</td>
    <td>123-458</td>
    <td>012345123458</td>
    {$fvals}
   </tr>
   <tr>
    <td>...</td>
    <td>...</td>
    {$fvals}
   </tr>
  </table>
 </td>
 <td valign="top" width="50%">
  <br><br>
  <form name="setfield" action="{$thisprog}" method="post" enctype="multipart/form-data" autocomplete="off">
{$dis_flds}
  </form>
 </td>
 </tr>
</table>
</body>
</html>

HTML;
echo $htm;
function load_fields()
{
    $fields = array(
        "alt_type_code" => array("hdr" => "Qty", "prompt" => "UPC Quantity (Default is 1)"),
        "alt_uom" => array("hdr" => "UOM", "prompt" => "Unit Of Measure"),
        "action" => array("hdr" => "Action", "prompt" => "A=Add/Update (Default), D=delete)")
    );
    return ($fields);
} // end load fields

?>
