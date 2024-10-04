<?php

foreach (array_keys($_REQUEST) as $w) { $$w=$_REQUEST[$w]; }
//error_reporting(0);

session_start();
require($_SESSION["wms"]["wmsConfig"]);
require_once("{$wmsInclude}/cl_Bluejay.php");
require_once("{$wmsDir}/assets/pdf/fpdf_LabelCode39Js.php");
$nextprog="upc_upload3.php";
$thisprog="upc_upload2.php";
$pg=new Bluejay;



$return="upc_upload2.php";

//echo "<pre>";
//print_r($_REQUEST);
//echo "</pre>";

//change tabs and commas to pipes
$ok=1;
$fname="print_upc" . getmypid() . ".xls";
//$uploadfile = $uploaddir. $_FILES['FileUpload1_File']['name'];
$orig_file=$_FILES["FileUpload1_File"]["name"];
//echo "<pre>";
//print_r($_FILES);
//phpinfo(INFO_VARIABLES);
$uploaddir = '/usr1/wms/tmp/';
$uploadfile = $uploaddir. $fname;
//print "<p align=\"center\">";
if (move_uploaded_file($_FILES['FileUpload1_File']['tmp_name'], $uploadfile)) {
   //print "<h4>File $fname is valid, and was successfully uploaded.</h4> ";
//   print "Here's some more debugging info:\n";
//   print_r($_FILES);
} else {
   if ($_FILES['FileUpload1_File']['size'] == 0 )
   { 
	print "<h4>Please select a file to upload.</h4>";
	$ok=0;
   }
   else {
   print "<h4>Possible file upload attack!  Here's some debugging info:</h4>";
  print "<pre>";
  print_r($_FILES);
  $ok=0;
  print "</pre>";
}
}
require_once("{$wmsInclude}/db_main.php");
require_once("{$wmsInclude}/onlyascii.php");
$db=new DB_MySQL;

$j=strpos($fname,".xls");
$j1=strpos($orig_file,".xlsx");
//echo "<pre>uploaddir={$uploaddir} fname={$fname} j={$j}\n";
if ($j > 0)
{ // convert from Excel to csv
 $sfile="{$uploaddir}{$fname}";
 $fname=str_replace(".xls",".csv",$fname);

 $dfile="{$uploaddir}{$fname}";
 $cmd="xls2csv -x {$sfile} -s cp1253 -d 8859-1 >{$dfile}"; 
 if ($j1 > 0) $cmd="xlsx2csv {$sfile} {$dfile}";
 $output=exec($cmd);
//echo $output; // probably write to log
}  // convert from Excel to csv

//echo "<pre>";
//echo "<h2>File=:{$uploaddir}{$fname}</h2>\n";
$fullname="{$uploaddir}/{$fname}";
 $fields="";
 //extra fields
$nofbins=array();
$comp=1;
$rec_read=0;
$rec_found=0;
$rec_add=0;
$rec_upd=0;
$rec_nupd=0;
$row = 1;

$partrow=1;
$partlist=array();

if (($handle = fopen($fullname, "r")) !== FALSE)
 { // if handle <> false
    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) 
  { // while getcsv
        $num = count($data);
        if ($row > 1) 
        {
         $rec_read++;
         // replace new line with nothing
         foreach($data as $key=>$d)
         {
          $data[$key]=str_replace("\n","",$d);
         } // replace new line with nothing
         $pl=onlyascii($data[0]);
         $partnum=onlyascii($data[1]);
         if (!isset($data[1]))
         {
          $msg="There is not enough columns in this spreadsheet, the values (UPC column C) is empty";
         echo "<pre>";
         print_r($data);
         exit;
         disp_err($row,$data,$msg);
         }
         $shadow=get_shadow($db,$pl,$partnum);
         if ($shadow > 0)
         {
          $upc=getUPC($db,$shadow);
          if ($upc=="") $upc=".{$shadow}";
          $partlist[$partrow]["pl"]=$pl;
          $partlist[$partrow]["partnum"]=$partnum;
          $partlist[$partrow]["upc"]=$upc;
          
          $partrow++;
         } // end shadow > 0
        } // row > 1
        $row++;
  } // while getcsv
    fclose($handle);
 } // if handle <> false
if (count($partlist) > 0)
{
 printLabels($lbl,$partlist);
 exit;
 
}
print_r($partlist);
exit;
//print_r($COMPS);
$ins=0;
//print_r($alts);
displayStats:
$dpl="";
//echo "All Done\n Records Read: {$rec_read}\n UPCs Found: {$rec_found}\n Added: {$rec_add}\n Updated: {$rec_upd}\nNot Updated {$rec_nupd}{$dpl}";
//print_r($alts);
if (sizeof($nofbins) > 0)
{
 $htm=<<<HTML
<table>
 <tr>
  <td colspan="3"><h2>Parts Not on File</h2></td>
 </tr>
 <tr>
  <th>P/L</th>
  <th>Part Number</th>
 </tr>

HTML;
foreach ($nofbins as $key=>$dat)
{
 $htm.=<<<HTML
 <tr>
  <td>{$dat["pl"]}</td>
  <td>{$dat["part"]}</td>
 </tr>
HTML;
} // end foreach nofpart
 $htm.=<<<HTML
</table>
HTML;
echo $htm;
} // end nof

$htm=<<<HTML
<p>&nbsp;</p>
<a href="{$return}">Return</a>
 </body>
</html>
HTML;

echo $htm;

exit;

function get_shadow($db,$pl,$partnum)
{
 $ret=0;
 $SQL=<<<SQL
 select shadow_number from PARTS
 where p_l = "{$pl}"
   and part_number = "{$partnum}"

SQL;

$rc=$db->query($SQL);
  $numrows=$db->num_rows();
  $i=1;
  while ($i <= $numrows)
  {
   $db->next_record();
     if ($numrows and $db->Record)
     {
      $ret=$db->f("shadow_number");
     }
      $i++;
  } // while i < numrows
 return($ret);
} // end get_shadow

function disp_err($row,$data,$msg)
{
 $e="";
 $alph="ABCDEFGHIJKLMNOPQRSTUVWXYZ";
 for ($i=0;$i<=count($data);$i++)
 {
  $j=$i+1;
  $a=substr($alph,$j,1);
  $e.="Column {$a}=\"{$data[$i]}\" ";
 }
 $htm=<<<HTML
 <h2>{$msg}<h2><br><br>
This Data record (Row#: {$row});<br>
{$e}<br>
 Quiting...
HTML;
echo $htm;
exit;
} // end disp_error

function getUPC($db,$shadow)
{
 $ret="";
 $SQL=<<<SQL
select alt_part_number
from ALTERNAT
where alt_shadow_num = {$shadow}
and alt_type_code = -1

SQL;
 $rc=$db->query($SQL);
  $numrows=$db->num_rows();
  $i=1;
  while ($i <= $numrows)
  {
   $db->next_record();
     if ($numrows and $db->Record)
     {
      $ret=$db->f("alt_part_number");
     }
      $i++;
      if ($i > 1) break;
  } // while i < numrows
 return($ret);
} // end getUPC

function printLabels($lbl,$data)
{
 // data is array of pl, partnum and upc
 $pdf=new PDF_LabelCode39($lbl);
$pdf->AddPage();

// Print labels
if (count($data) > 0)
{
foreach ($data as $val)
 {
  $pre="{$val["pl"]} {$val["partnum"]}";
  $upc="{$val["upc"]}";
//Cell($w, $h=0, $txt='', $border=0, $ln=0, $align='', $fill=false, $link='')
  //$pdf->Add_Label($pre,1,10);
  $pdf->D($pre,10,4);
  $pdf->Add_BarCode($upc,1,10);
  $pdf->ln(20);
  //$pdf->D("",10,10);

  // to just add label use; $pdf->Add_Label($text);
  // to just add a barcode, Code39(X,Y,$text,barwidth, barheight)
  // $pdf->Code39(80,40,'CODE 39',1,10);

 } // end for i loop
} // end count data > 0
$pdf->Output();

} // end printLabels
?>
