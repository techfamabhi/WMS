<?php
// 05/23/2017 dse Initial
// 05/31/2017 dse add min gp
foreach (array_keys($_REQUEST) as $w) { $$w=$_REQUEST[$w]; }
error_reporting(0);

ob_start();
$debug=0;
$return="parts_upl.php";

function linux_eol($s) {
  /* Return text with Linux end of line conventions.
   *
   * Input: $s  The string to convert.
   *
   * Returns: The string with Linux end of line conventions.
   *
   */
       return strtr(ereg_replace("\r\n", "\n", $s), "\r", "\n");
}


    //<body marginwidth=0 marginheight=0 topmargin=0 leftmargin=2 OnLoad = "document.load.submit();">
$htm=<<<HTML
<html>
<head>
</head>
    <body marginwidth="0" marginheight="0" topmargin="0" leftmargin="2">
HTML;
echo $htm;
//change tabs and commas to pipes
$ok=1;
$fname=str_replace("/","",$PL) . "_mfg.xls";
//$uploadfile = $uploaddir. $_FILES['FileUpload1_File']['name'];
$orig_file=$_FILES["FileUpload1_File"]["name"];
echo "<pre>file={$orig_file}\n";
//print_r($_FILES);
//phpinfo(INFO_VARIABLES);
$uploaddir = '/usr1/price_files/';
$uploadfile = $uploaddir. $fname;
print "<p align=\"center\">";
if (move_uploaded_file($_FILES['FileUpload1_File']['tmp_name'], $uploadfile)) {
   print "<h4>File $fname is valid, and was successfully uploaded.</h4> ";
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
require_once("/usr1/include/db_sybase.php");
require_once("/usr1/include/update.php");
require_once("/usr1/include/onlyascii.php");
$db=new WMS_DB;

$j=strpos($fname,".xls");
$j1=strpos($orig_file,".xlsx");
//echo "<pre>uploaddir={$uploaddir} fname={$fname} j={$j}\n";
if ($j > 0)
{ // convert from Excel to csv
 $sfile="{$uploaddir}/{$fname}";
 $fname=str_replace(".xls",".csv",$fname);

 $dfile="{$uploaddir}/{$fname}";
 $cmd="xls2csv -x {$sfile} -s cp1253 -d 8859-1 >{$dfile}"; 
 if ($j1 > 0) $cmd="xlsx2csv {$sfile} {$dfile}";
 exec($cmd);
}  // convert from Excel to csv

echo "<pre>";
echo "<h2>File=:{$uploaddir}/{$fname}</h2>\n";
$fullname="{$uploaddir}/{$fname}";
if (!isset($pfields))
{
$htm=<<<HTML
 <h2>You must specify some fields</h2>
 
HTML;
echo $htm;
exit;
} // not isset pfields
else
{ // pfields is set
 $fields="";
 $types="";
 $select_fields="";
 foreach($pfields as $key=>$fld)
 {
  $fields[$key + 2]=$fld;
  switch ($fld)
  {
    case "part_weight":
    case "qty_per_car":
    case "tax_override":
    case "recycle_fee":
    case "part_min_gp":
      $types[$fld]="1";
      break;
    default:
      $types[$fld]="0";
      break;
  } // end switch fld
  if ($select_fields <> "") $select_fields.=",";
  $select_fields.=" {$fld}";
 }
} // pfields is set
$parts=array();
$nofparts=array();
$comp=3;
$rec_read=0;
$rec_found=0;
$rec_nof=0;
$rec_upd=0;
$row = 1;
if (($handle = fopen($fullname, "r")) !== FALSE) {
    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        $num = count($data);
        if ($row > 1) 
        {
//print_r($data);
//exit;
         $rec_read++;
 	 $fpl=$data[0];
         $parts[$row]["pl"]=onlyascii($data[0]);
	 if (!isset($PLXREF[$fpl]))
          {
           $rc=val_pl($db,$fpl);
           if ($rc < 0) 
           {
            echo "<pre>";
            echo "Invalid Product Line: {$fpl}\n";
            echo "Data record is row:{$row}\n";
            print_r($data);
            exit;
           }
	  }
         $parts[$row]["part"]=onlyascii($data[1]);
if (!isset($data[2]))
{
 $msg="There is not enough columns in this spreadsheet, the New values (column C) is empty or non-numeric";
echo "<pre>";
print_r($data);
exit;
disp_err($row,$data,$msg);
}
         $parts[$row]=get_partinfo($parts[$row],$select_fields,$fields);
         $update="";
         foreach($fields as $key=>$fld)
         {
           $newname="n{$fld}";
           $parts[$row][$newname]=str_replace("'","",onlyascii($data[$key]));
           if ($parts[$row][$newname] <> $parts[$row][$fld])
           {
            if ($update == "") $update = "set ";
            else $update.=",\n";
            if ($types[$fld] == 0) $update.="{$fld}='{$parts[$row][$newname]}'";
            else 
             { // its numeric
               // check if its numeric and it's not null
              if (trim($parts[$row][$newname]) == "") $parts[$row][$newname]=0;
              if (!is_numeric(trim($parts[$row][$newname])))
               { // non numeric
                $msg="A Numeric or Amount value is not a number.";
                disp_err($row,$data,$msg);
               } // non numeric
              $update.="{$fld}={$parts[$row][$newname]}";
             } // its numeric
           } // end value has changed
            
         } // end foreach fields
//echo "<pre>{$select_fields}\n";
//print_r($parts);
//print_r($update);
//exit;
         if ($parts[$row]["shadow"]==-1)
         { //not found
          $rec_nof++;
          $nofparts[$row]=$parts[$row];
          unset($parts[$row]);
         } //not found
          else 
         { //found
          $rec_found++;
//if ($debug > 0) print_r($data);
          if ($update <> "")
          { // update Parts
           $uSQL=<<<SQL
update PARTS {$update}
where shadow_number = {$parts[$row]["shadow"]}

SQL;
//echo "{$uSQL}\n";
           $rec_upd++;
           if ($debug < 1) $rc=Update($db,$uSQL);
	   else echo "{$uSQL}\n";
          } // update price
          $fld=0;
         } //found
        } // row > 1
        if (($row % 100) == 0)
        {
         echo "Record# {$row} {$parts[$row]["part"]}\n";
         ob_flush();
         flush();
         usleep(50000);

        }
        $row++;
    }
    fclose($handle);
}
echo "<pre>";
//print_r($PLXREF);
$ins=0;
//print_r($parts);
$dpl="";
echo "All Done\n Records Read: {$rec_read}\n Parts Found: {$rec_found}\n NOF: {$rec_nof}\n Parts Updated: {$rec_upd}\n{$dpl}";
//print_r($parts);
if (sizeof($nofparts) > 0)
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
foreach ($nofparts as $key=>$dat)
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
function get_partinfo($in,$select_fields,$fields)
{
 global $db;
 $out=$in;
 $SQL=<<<SQL
 select shadow_number,{$select_fields}

from PARTS
where p_l = "{$in["pl"]}"
  and part_number = "{$in["part"]}"

SQL;

  $rc=$db->query($SQL);
  $numrows=$db->num_rows();
  $i=1;
  while ($i <= $numrows)
  {
   $db->next_record();
     if ($numrows)
     {
            $out["shadow"]=$db->f("shadow_number");
            foreach($fields as $fld)
             {
	       $out[$fld]=$db->f($fld);
             }
     }
     $i++;
   } // while i < numrows
 if ($i==1)
 {
  $out["shadow"]=-1;
 }
 return($out);
} // end get_partinfo

function val_pl($db,$pl)
{
$ret=-1;
 $SQL=<<<SQL
select pl_vend_code from PRODLINE
where pl_code = "{$pl}"
and pl_company = 9

SQL;
 $rc=$db->query($SQL);
  $numrows=$db->num_rows();
  $i=1;
  while ($i <= $numrows)
  {
   $db->next_record();
     if ($numrows)
     {
            $vend=$db->f("pl_vend_code");
            $ret=1;
     }
     $i++;
   } // while i < numrows

return($ret);
} // end val pl
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
?>
