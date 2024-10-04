<?php
//proc_qoh.php -- process uploaded PW part ledger

foreach (array_keys($_REQUEST) as $w) { $$w=$_REQUEST[$w]; }
//error_reporting(0);
session_start();
require($_SESSION["wms"]["wmsConfig"]);
$debug=0;
$return="qoh_upl.php";

//echo "<pre>";
//print_r($_REQUEST);
//echo "</pre>";

$htm=<<<HTML
<html>
<head>
</head>
    <body marginwidth="0" marginheight="0" topmargin="0" leftmargin="2">
HTML;
echo $htm;
//change tabs and commas to pipes
$ok=1;
$fname="upc_upl.xls";
//$uploadfile = $uploaddir. $_FILES['FileUpload1_File']['name'];
$orig_file=$_FILES["FileUpload1_File"]["name"];
echo "<pre>";
//print_r($_FILES);
//phpinfo(INFO_VARIABLES);
$uploaddir = '/usr1/wms/tmp/';
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
require_once("{$wmsInclude}/db_main.php");
require_once("{$wmsInclude}/get_companys.php");
require_once("{$wmsInclude}/onlyascii.php");
require_once("{$wmsInclude}/cl_addupdel.php");
$db=new DB_MySQL;
$upd=new AddUpdDEL;

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
echo $output;
}  // convert from Excel to csv

echo "<pre>";
echo "<h2>File=:{$uploaddir}{$fname}</h2>\n";
$fullname="{$uploaddir}/{$fname}";
 $fields="";
 $types="";
 $select_fields="alt_shadow_num,alt_part_number";
 //extra fields
 if (!isset($pfields)) $pfields=array();
 if (count($pfields) > 0)
 {
  foreach($pfields as $key=>$fld)
  {
   $fields[$key + 2]=$fld;
   switch ($fld)
   {
     case "alt_type_code":
     case "alt_uom":
       $types[$fld]="1";
       break;
     default:
       $types[$fld]="0";
       break;
   } // end switch fld
   if ($select_fields <> "") $select_fields.=",";
   $select_fields.=" {$fld}";
  } // end foreach pfields
 } //pfields is set
echo "<pre>";
$bins=array();
$nofbins=array();
$comp=1;
$rec_read=0;
$rec_found=0;
$rec_add=0;
$rec_upd=0;
$rec_nupd=0;
$row = 1;

if (($handle = fopen($fullname, "r")) !== FALSE) {
    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        $num = count($data);
        if ($row > 3) 
        {
         $rec_read++;
         // replace new line with nothing
         foreach($data as $key=>$d)
         {
          $data[$key]=str_replace("\n","",$d);
         } // replace new line with nothing
if (!isset($data[10]))
{
 $msg="There is not enough columns in this spreadsheet, the Qty Change is empty";
echo "<pre>";
print_r($data);
exit;
disp_err($row,$data,$msg);
}
         $pl=onlyascii($data[4]);
         $partnum=onlyascii($data[7]);
         $oldQty=onlyascii($data[8]);
         $adjQty=onlyascii($data[10]);

         $uSQL="";
         $shadow=get_shadow($db,$pl,$partnum);
echo "shadow={$shadow}";
print_r($data);
exit;
         $altRec=array();
 	 if ($shadow > 0)
         {
echo "<pre>";
print_r($data);
exit;
         }
         if (count($altRec)) $rec_found++;
         $j=count($pfields);
         if ($j > 0)
         {
          for($i=0;$i<$j;$i++)
          {
           if (isset($data[$i + 3]))
           {
            $k=$i+3;
            $alts[$row][$pfields[$i]]=onlyascii($data[$k]);
	    if (trim($data[$k]) == "")
            {
             if ($types[$pfields[$i]] > 0 ) $alts[$row][$pfields[$i]]=0;
             else $alts[$row][$pfields[$i]]=" ";
            } // data is empty
           }
           else
           {
            if ($types[$pfields[$i]] > 0) $alts[$row][$pfields[$i]]=0;
             else $alts[$row][$pfields[$i]]=" ";
           }
          }
         }
         if (isset($alts[$row]["alt_type_code"]) 
         and  $alts[$row]["alt_type_code"] > 0)
         { // set type code to - type code and add if not o file
          $alts[$row]["alt_type_code"]= -$alts[$row]["alt_type_code"];
          $rc=chkAltType($db,$alts[$row]["alt_type_code"]);
         } // set type code to - type code and add if not o file
        
         if (count($altRec) < 1)
         { // alt not found add it
          $rec_add++;
          $comma="";
          $dataset="";
          foreach($alts[$row] as $key=>$Fld)
          {
           if (strlen($dataset) > 0) $comma=",";
           $val=trim($Fld);
           if ($key == "alt_shadow_num") $val=trim($Fld);
           else if ($key == "alt_part_number")  $val='"' . trim($Fld) . '"';
           else
            if ($types[$key] < 1) $val='"' . trim($Fld) . '"';
            $dataset.="{$comma}{$val}";
          } // end foreach alts[row]
        
          $uSQL=<<<SQL
insert into ALTERNAT ({$select_fields})
values ( {$dataset} );

SQL;
         } // alt not found add it
         else
         { // alt is there, update it
          $updRec=true;
          if ($alts[$row]["alt_type_code"] == $altRec["alt_type_code"]
           and (isset($alts[$row]["alt_uom"])
            and $alts[$row]["alt_uom"] == $altRec["alt_uom"])) $updRec=false;
          if ($updRec)
          {
           $comma="";
           $set="set";
           $dataset="";
           foreach($alts[$row] as $key=>$Fld)
           {
            if ($key <> "alt_shadow_num" and $key <> "alt_part_number")
            {
             if (strlen($dataset) > 0)
             {
              $comma=",";
              $set="";
             }
             $val=trim($Fld);
             if (isset($altRec[$key]) and $val <> trim($altRec[$key]))
             { // field is changed
              if ($types[$key] < 1) $val='"' . trim($Fld) . '"';
              $dataset.="{$comma}\n{$set} {$key}={$val}";
             } // field is changed
            } // not shadow or upc
           } // end foreach alts[row]
	 if (strlen($dataset) > 0)
         {
          $uSQL=<<<SQL
update ALTERNAT
{$dataset}
where alt_shadow_num = {$shadow}
  and alt_part_number = "{$upc}"

SQL;
           $rec_upd++;
          } // end if updRec is true
        }
        else 
        {
         $uSQL="";
         $rec_nupd++;
        }
         } // alt is there, update it
       if (strlen($uSQL) > 0)
       {
        $rc=$db->Update($uSQL);
        //echo "{$uSQL}\n";
       }
       else $rec_nupd++;

        } // row > 1
        if (($row % 100) == 0)
        {
         echo "Record# {$row} {$alts[$row]["alt_part_number"]}\n";
         ob_flush();
         flush();
         usleep(50000);

        }
        $row++;
    }
    fclose($handle);
}
//print_r($COMPS);
$ins=0;
//print_r($alts);
displayStats:
$dpl="";
echo "All Done\n Records Read: {$rec_read}\n UPCs Found: {$rec_found}\n Added: {$rec_add}\n Updated: {$rec_upd}\nNot Updated {$rec_nupd}{$dpl}";
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

function get_alt($db,$shadow,$upc)
{
 $ret=array();
 $SQL=<<<SQL
 select * from ALTERNAT
 where alt_part_number = "{$upc}"
   and alt_shadow_num = {$shadow}

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
} // end get_alt

function chkAltType($db,$typ)
{
 $ret=0;
 $j=-$typ;
 $desc="Case of {$j}";
 $SQL=<<<SQL
insert IGNORE into ALTYPES (al_key,al_desc)
values ({$typ},"{$desc}")

SQL;
 $rc=$db->Update($SQL);
 return($rc);
} // end chkAltType

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
