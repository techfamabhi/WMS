<?php
//05/31/2017 dse Add min gp
//10/16/18 dse fix fieldname of part_long_desc

foreach (array_keys($_REQUEST) as $w) {
    $$w = $_REQUEST[$w];
}
error_reporting(0);

require_once("/usr1/include/cl_Bluejay.php");
$nextprog = "proc_parts.php";
$thisprog = "parts_upl.php";
$pg = new Bluejay;

if (isset($_REQUEST["Error"])) $Error = $_REQUEST["Error"]; else $Error = "";
if (isset($_REQUEST["referer"])) $referer = $_REQUEST["referer"]; else $referer = "";
$pg->title = "Upload Part Fields";
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
$UserLogin = $_SESSION["wms"]["UserLogin"];
$UserPriv = $_SESSION["wms"]["spriv_thru"];
$UserOper = $_SESSION["wms"]["operator"];
$hidden = "";
$fldname = "";
$fvals = "";
$allfields = load_fields();
$d1field = array();
if (isset($pfield)) {
    foreach ($pfield as $fld) {
        $d1field[$fld] = $allfields[$fld];
        $hidden .= <<<HTML
<input type="hidden"  name="pfields[]" value="{$fld}"/>

HTML;
        $fldname .= <<<HTML
   <th nowrap>{$d1field[$fld]}</th>

HTML;
        $fvals .= <<<HTML
    <td><i>value</i></td>

HTML;
    } // end foreach pfield
} // isset pfield
else $pfield[0] = "mfg_pop_class";

$dis_flds = <<<HTML
   <table class="MultipadsFormTABLE" cellspacing="2" cellpadding="2">
    <tr>
     <td class="MultipadsColumnTD"></td>
     <td class="MultipadsColumnTD">Select Field(s)</td>
    </tr>

HTML;
foreach ($allfields as $fld => $prompt) {
    $sel = "";
    if (isset($d1field[$fld])) $sel = "checked";
    $dis_flds .= <<<HTML
  <tr>
   <td><input {$sel} onclick="document.setfield.submit();" class="MultipadsDataTD" type="checkbox" name="pfield[]" value="{$fld}"></td><td class="MultipadsDataTD">{$prompt}</td>
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
if ($fldname == "") $file_upload = "&nbsp;";

$htm = <<<HTML
<table>
<tr>
 <td valign="top" width="50%">

  <form name="fupload" action="{$nextprog}" method="post" enctype="multipart/form-data" autocomplete="off">
  <font color="#666699">
  <h4>{$title}</h4>
  <input type="hidden" value="{$fname}" name="FileUpload1"/>
  <input type="hidden" value="{$PL}" size="3" name="PL"/>
  {$file_upload}
&nbsp;&nbsp;
  </font>
 </form>
  <p>File must be an Excel spreadsheet and contain the following columns;<br>
 <h4 style="color:red">Columns Must be in the same order as below!</h4>
</p>
  <table border="1">
   <tr>
    <th>P/L</th>
    <th>PartNumber</th>
   {$fldname}
   </tr>
   <tr>
    <td>WIX</td>
    <td>51515</td>
    {$fvals}
   </tr>
   <tr>
    <td>WIX</td>
    <td>51516</td>
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
        "part_desc" => "Description",
        "part_long_desc" => "Long Description",
        "part_category" => "Category",
        "part_class" => "Class",
        "part_alt_class" => "Price Group",
        "mfg" => "Mfg",
        "mfg_pop_class" => "Mfg Pop Class",
        "part_weight" => "Weight (nnnn.nn)",
        "unit_of_measure" => "UOM",
        "qty_per_car" => "Qty Per Car (Numeric)",
        "core_group" => "Core Group",
        "part_cf_flag" => "Rebatable (Y or N)",
        "tax_override" => "Taxable (0 or 1)",
        "part_spec_inst" => "Special Instructions",
        "recycle_fee" => "Recycle Fee (nnnn.nn)",
        "part_min_gp" => "Minimum GP% (nn)",
        "net_code" => "Net Code (blank or Y)"
    );
    return ($fields);
} // end load fields
?>
