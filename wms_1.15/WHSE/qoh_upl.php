<?php
//01/25/20s24 dse upload QOH from PW system

foreach (array_keys($_REQUEST) as $w) { $$w=$_REQUEST[$w]; }
error_reporting(0);

session_start();
require($_SESSION["wms"]["wmsConfig"]);
require_once("{$wmsInclude}/cl_Bluejay.php");
$nextprog="proc_qoh.php";
$thisprog="qoh_upl.php";
$pg=new Bluejay;

if (isset($_REQUEST["Error"])) $Error=$_REQUEST["Error"]; else $Error="";
if (isset($_REQUEST["referer"])) $referer=$_REQUEST["referer"]; else $referer="";
$pg->title="Upload PW Part Ledger Qty Update Since Conversion";
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

$dis_flds=<<<HTML
   <p>Export from PartsWatch Part Ledger fields</p>
   <table class="FormTABLE" cellspacing="2" cellpadding="2">
    <tr>
     <td width="25%" class="ColumnTD">Field</td>
     <td width="25%" class="ColumnTD">Sample Values</td>
     <td width="50%" class="ColumnTD">&nbsp;</td>
    </tr>
    <tr>
     <td width="25%" class="DataTD">Trans Date</td>
     <td width="25%" class="DataTD">7/1/2023</td>
     <td width="50%" class="DataTD"><strong>REQUIRED</strong></td>
    </tr>
    <tr>
     <td width="25%" class="DataTD">Trans Time</td>
     <td width="25%" class="DataTD">9:01:40 AM</td>
     <td width="50%" class="DataTD"><strong>REQUIRED</strong></td>
    </tr>
    <tr>
     <td width="25%" class="DataTD">Location</td>
     <td width="25%" class="DataTD">       1</td>
     <td width="50%" class="DataTD"><strong>REQUIRED</strong></td>
    </tr>
    <tr>
     <td width="25%" class="DataTD">Emp</td>
     <td width="25%" class="DataTD">DEN</td>
     <td width="50%" class="DataTD"><strong>REQUIRED</strong></td>
    </tr>
    <tr>
     <td width="25%" class="DataTD">P/L</td>
     <td width="25%" class="DataTD">ACB</td>
     <td width="50%" class="DataTD"><strong>REQUIRED</strong></td>
    </tr>
    <tr>
     <td width="25%" class="DataTD">Line Type</td>
     <td width="25%" class="DataTD">P</td>
     <td width="50%" class="DataTD"><strong>REQUIRED</strong></td>
    </tr>
    <tr>
     <td width="25%" class="DataTD">PN Code</td>
     <td width="25%" class="DataTD">HEN</td>
     <td width="50%" class="DataTD"><strong>REQUIRED</strong></td>
    </tr>
    <tr>
     <td width="25%" class="DataTD">Part Number</td>
     <td width="25%" class="DataTD">E1040L</td>
     <td width="50%" class="DataTD"><strong>REQUIRED</strong></td>
    </tr>
    <tr>
     <td width="25%" class="DataTD">Qty Before</td>
     <td width="25%" class="DataTD">9</td>
     <td width="50%" class="DataTD"><strong>REQUIRED</strong></td>
    </tr>
    <tr>
     <td width="25%" class="DataTD">Mod</td>
     <td width="25%" class="DataTD">S</td>
     <td width="50%" class="DataTD"><strong>REQUIRED</strong></td>
    </tr>
    <tr>
     <td width="25%" class="DataTD">Qty Change</td>
     <td width="25%" class="DataTD">-1</td>
     <td width="50%" class="DataTD"><strong>REQUIRED</strong></td>
    </tr>
    <tr>
     <td width="25%" class="DataTD">...</td>
     <td width="25%" class="DataTD">...</td>
     <td width="50%" class="DataTD"><strong>All Other Fields will be ignored</strong></td>
    </tr>
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
  <p>File must be an Excel spreadsheet and contain the following columns.<br><strong>Please include the first 3 Heading records</strong>&nbsp; <br>
</p>
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

?>
