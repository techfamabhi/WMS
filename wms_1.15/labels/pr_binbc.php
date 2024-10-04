<?php

// standard_top.php -- standard top of program
// mm/dd/yy who initial  (mm/dd/yy is the date, who is you, inital is what you did)

foreach (array_keys($_REQUEST) as $w) { $$w=$_REQUEST[$w]; }
//error_reporting(0);

if (get_cfg_var('wmsdir')) $wmsDir=get_cfg_var('wmsdir');
else { echo "<h1>WMS System is not Configured on this System</h1>"; exit; }
$top=str_replace("/var/www","",$wmsDir) . "/";

if (!isset($comp)) $comp=1;
require_once("{$wmsDir}/assets/pdf/fpdf_LabelCode39Js.php");
require_once("{$wmsDir}/include/db_main.php");
$db=new WMS_DB;

if (isset($lbl)) $label=$lbl; else $lbl="5160";
// code39 example
//$pdf=new PDF_LabelCode39('L7163');
//barcode on page
//$pdf->AddPage();
//$pdf->Code39(80,40,'CODE 39',1,10);
//$pdf->Output();


//Label example


/*------------------------------------------------
To create the object, 2 possibilities:
either pass a custom format via an array
or use a built-in AVERY name
------------------------------------------------*/

// Example of custom format
// $pdf = new PDF_Label(array('paper-size'=>'A4', 'metric'=>'mm', 'marginLeft'=>1, 'marginTop'=>1, 'NX'=>2, 'NY'=>7, 'SpaceX'=>0, 'SpaceY'=>0, 'width'=>99, 'height'=>38, 'font-size'=>14));

// Standard format

if (trim($binTo) == "") $binTo=$binFrom;
$where="";
if ($binFrom <> "") $where = "and wb_location between \"{$binFrom}\" and \"{$binTo}\"\n";
$SQL=<<<SQL
select wb_location, wb_zone, wb_aisle, wb_section, wb_level, wb_subin
from WHSEBINS
where wb_company = {$comp}
{$where}
order by wb_location

SQL;
$data=$db->gData($SQL);

$pdf=new PDF_LabelCode39($lbl);
//$pdf=new PDF_LabelCode39('5165');
$pdf->AddPage();

// Print labels
//echo "<pre>";
//print_r($data);
//exit;
if (count($data) > 0)
{
foreach ($data as $val)
 {
  $text=$val["wb_location"];
  $pdf->Add_BarCode($text,1,10);

  // to just add label use; $pdf->Add_Label($text);
  // to just add a barcode, Code39(X,Y,$text,barwidth, barheight)
  // $pdf->Code39(80,40,'CODE 39',1,10);

 } // end for i loop
} // end count data > 0
$pdf->Output();

?>
