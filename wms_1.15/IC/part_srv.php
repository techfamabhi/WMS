<?php
//Part ajax server

foreach (array_keys($_REQUEST) as $w) { $$w=$_REQUEST[$w]; } 
//error_reporting(0);

if (get_cfg_var('wmsdir')) $wmsDir=get_cfg_var('wmsdir');
else { echo "<h1>WMS System is not Configured on this System</h1>"; exit; }
$top=str_replace("/var/www","",$wmsDir);




require_once("{$wmsDir}/include/db_main.php");
//require_once("get_parts.php");
require_once("{$wmsDir}/include/cl_PARTS2.php");
$db = new WMS_DB;
$PM = new PARTS;

if (!isset($pnum)) $pnum="";

if (!isset($comp)) $comp=1; // needs to come from config

$srv=array();
//echo "<pre>pnum={$pnum}\n";

if (trim($pnum) <> "")
{
//echo "Last Part#=$pnum";
//$part=get_part($db,trim(strtoupper($pnum)));
//$j=$part["num_rows"];
$part=array();
$part["partno"]=$pnum;
$a=$PM->lookup(trim(strtoupper($pnum)));
$j=count($a);
$part["num_rows"]=$j;
if ($j < 1) $part["status"]=-35; else $part["status"]=0;
if ($j > 0)
{
 foreach ($a as $key=>$data)
 {
  $part[$key]=$data;
 }
 if ($j == 1 and isset($a[1])) $part["partno"]=$a[1]["p_l"] . $a[1]["part_number"];
}
echo "<pre>";
print_r($part);
exit;
//echo "j={$j}\n";

//echo " Parts found: $j\n";
//if ($part["status"]==-35) 
//{ 
//$j=1;
//$srv["part"]["partno"]=$pnum;
//$srv["part"]["numrows"]=0;
//$srv["part"]["status"]="-35";
//$srv["part"][1]["shadow_number"]=0;
//$srv["part"][1]["p_l"]="???";
//$srv["part"][1]["part_number"]=$pnum;
//$srv["part"][1]["part_desc"]="Not Found!";

//$pnum=""; 
//} // end of -35
//else
//{ // part found

//$i=1;
#echo "Parts found: $j\n";
//print_r($part);
//if ($pnum=="") {$j=1;}
//if ($j > 1)
//{
 //while ($i <= $j)
 //{
  //$p_l=$part[$i]["p_l"];
  //$pn=$part[$i]["part_number"];
  //$pdesc=$part[$i]["part_desc"];
  //$upc=$part[$i]["alt_part_number"];
//echo "<tr>\n";
//echo "<td $td><input type=\"checkbox\" name=\"pnum\" value=\"$p_l$pn\" onchange=\"do_choose('$p_l$pn');\"></td>\n";
//echo "<td $td>$p_l</td>\n";
//echo "<td $td>$pn</td>\n";
//echo "<td $td>$pdesc</td>\n";
//
//echo "</tr>\n";
  //$i++;
 //}
//} // if j > 1
//else 
$srv["part"]=$part;
//} // part found

//echo "<pre>";
//print_r($srv);
$json=json_encode($srv);
echo $json;
} // echo got a pnum
?>
