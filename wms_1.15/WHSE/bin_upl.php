<?php
//05/31/2017 dse Add min gp
//10/16/18 dse fix fieldname of part_long_desc

foreach (array_keys($_REQUEST) as $w) { $$w=$_REQUEST[$w]; }
error_reporting(0);

session_start();
require($_SESSION["wms"]["wmsConfig"]);
require_once("{$wmsInclude}/cl_Bluejay.php");
$nextprog="proc_bins.php";
$thisprog="bin_upl.php";
$pg=new Bluejay;

if (isset($_REQUEST["Error"])) $Error=$_REQUEST["Error"]; else $Error="";
if (isset($_REQUEST["referer"])) $referer=$_REQUEST["referer"]; else $referer="";
$pg->title="Upload Bins";
$title=$pg->title;
$pg->js=<<<HTML
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
$UserPriv=$spriv_thru;
$UserOper=$operator;
$hidden="";
$fldname="";
$fvals="";
$allfields=load_fields();
$d1field=array();
if (isset($pfield))
{
 foreach($pfield as $fld)
 {
  $hdr=$allfields[$fld]["hdr"];
  $prompt=$allfields[$fld]["prompt"];
  $d1field[$fld]=$allfields[$fld];
  $hidden.=<<<HTML
<input type="hidden"  name="pfields[]" value="{$fld}"/>

HTML;
  $fldname.=<<<HTML
   <th nowrap>{$hdr}</th>

HTML;
  $fvals.=<<<HTML
    <td><i>value</i></td>

HTML;
 } // end foreach pfield
} // isset pfield
else $pfield[0]="mfg_pop_class";

$dis_flds=<<<HTML
   <p>Check the fields below to Upload</p>
   <table class="FormTABLE" cellspacing="2" cellpadding="2">
    <tr>
     <td width="2%" class="ColumnTD"></td>
     <td width="25%" class="ColumnTD">Field</td>
     <td width="50%" class="ColumnTD">Sample Values</td>
    </tr>
    <tr>
     <td width="2%" class="DataTD"><input  type="checkbox" name="" checked disabled></td>
     <td width="25%" class="DataTD">Whse/Store#</td>
     <td width="50%" class="DataTD"><strong>REQUIRED</strong></td>
    </tr>
    <tr>
     <td width="2%" class="DataTD"><input  type="checkbox" name="" checked disabled></td>
     <td width="25%" class="DataTD">Bin</td>
     <td width="50%" class="DataTD"><strong>REQUIRED</strong></td>
    </tr>

HTML;
foreach ($allfields as $fld=>$p)
{
 $prompt=$p["hdr"];
 $title=$p["prompt"];
 $sel="";
 if (isset($d1field[$fld])) $sel = "checked";
 $dis_flds.=<<<HTML
  <tr title="{$title}">
   <td><input {$sel} onclick="document.setfield.submit();" class="DataTD" type="checkbox" name="pfield[]" value="{$fld}"></td>
   <td class="DataTD">{$prompt}</td>
   <td class="DataTD">{$title}</td>
  </tr>

HTML;
} // end foreach allfields

$dis_flds.=<<<HTML
   </table>

HTML;
$file_upload=<<<HTML
{$hidden}
<input type="file" size="48" name="FileUpload1_File" onchange="do_upload();"/>
HTML;
//if ($fldname == "") $file_upload="&nbsp;";

$htm=<<<HTML
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
  <p>File must be an Excel spreadsheet and contain the following columns&nbsp; <br>
 <h4 style="color:red">Columns Must be in the same order as below!&nbsp; </h4>
</p>
  <table border="1">
   <tr>
    <th>Whse#</th>
    <th>Bin (as appears on barcode)</th>
   {$fldname}
   </tr>
   <tr>
    <td>1</td>
    <td>A01-01A</td>
    {$fvals}
   </tr>
   <tr>
    <td>1</td>
    <td>A01-01B</td>
    {$fvals}
   </tr>
   <tr>
    <td>1</td>
    <td>A01-01C</td>
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
  $fields=array(
 "wb_zone"=>array("hdr"=>"Zone","prompt"=>"[A]-Z"),
 "wb_aisle"=>array("hdr"=>"Aisle","prompt"=>"([0]-65535)"),
 "wb_section"=>array("hdr"=>"Section","prompt"=>"([0]-255)"),
 "wb_level"=>array("hdr"=>"Level","prompt"=>"(Shelf A-Z)"),
 "wb_subin"=>array("hdr"=>"Sub-Bin","prompt"=>"([0]-255)"),
 "wb_width"=>array("hdr"=>"Width","prompt"=>"(in inches)"),
 "wb_depth"=>array("hdr"=>"Depth","prompt"=>"(in inches)"),
 "wb_height"=>array("hdr"=>"Height","prompt"=>"(in inches)"),
 "wb_volume"=>array("hdr"=>"Volume","prompt"=>"(Length * Width * Height)"),
 "wb_pick"=>array("hdr"=>"Pickable","prompt"=>"(0=No [1]=Yes)"),
 "wb_recv"=>array("hdr"=>"Receive","prompt"=>"(0=No [1]=Yes)"),
 "wb_status"=>array("hdr"=>"Status","prompt"=>"([A]=Active, I=inactive, D=delete)")
);
  return($fields);
} // end load fields
?>
