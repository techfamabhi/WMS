<?php

// standard_top.php -- standard top of program
// mm/dd/yy who initial  (mm/dd/yy is the date, who is you, inital is what you did)

foreach (array_keys($_REQUEST) as $w) { $$w=$_REQUEST[$w]; }
//error_reporting(0);

if (get_cfg_var('wmsdir')) $wmsDir=get_cfg_var('wmsdir');
else { echo "<h1>WMS System is not Configured on this System</h1>"; exit; }
$top=str_replace("/var/www","",$wmsDir) . "/";

require_once("{$wmsDir}/assets/pdf/fpdf_LabelCode39Js.php");
require_once("{$wmsDir}/include/db_main.php");
$db=new WMS_DB;
ini_set('memory_limit', '-1');
set_time_limit(120);


if (isset($lbl)) $label=$lbl; else $lbl="5160";

$where="";
$SQL=<<<SQL
select alt_part_number, alt_type_code, p_l, part_number
from PARTS,ALTERNAT
where alt_shadow_num = shadow_number
and alt_type_code < 0
and alt_type_code = -1
{$where}
order by p_l,part_number,alt_type_code,alt_sort, p_l, part_number

SQL;
$data=$db->gData($SQL);

$pdf=new PDF_LabelCode39($lbl);
//$pdf=new PDF_LabelCode39('5165');
$pdf->AddPage();

// Print labels
if (count($data) > 0)
{
foreach ($data as $val)
 {
  $pre="{$val["p_l"]} {$val["part_number"]}";
//Cell($w, $h=0, $txt='', $border=0, $ln=0, $align='', $fill=false, $link='') 
  $text=$val["alt_part_number"];
  $pdf->Add_BarCode($text,1,10);
  $pdf->Add_Label($pre,1,10);

  // to just add label use; $pdf->Add_Label($text);
  // to just add a barcode, Code39(X,Y,$text,barwidth, barheight)
  // $pdf->Code39(80,40,'CODE 39',1,10);

 } // end for i loop
} // end count data > 0
$pdf->Output();
?>
