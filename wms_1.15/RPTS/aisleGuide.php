<?php
// emptyBin1.php - report of Bins with no parts
// 10/27/23 Dse initial

foreach (array_keys($_REQUEST) as $w) { $$w=$_REQUEST[$w]; }
//error_reporting(0);
error_reporting(E_ALL);

session_start();

if (get_cfg_var('wmsdir')) $wmsDir=get_cfg_var('wmsdir');
else { echo "<h1>WMS System is not Configured on this System</h1>"; exit; }
$top=str_replace("/var/www","",$wmsDir);

if (isset($_SESSION["wms"])) require($_SESSION["wms"]["wmsConfig"]);
else require("{$wmsDir}/config.php");

$thisprogram="cust_list.php";
if (!isset($wmsInclude)) $wmsInclude="{$wmsDir}/include";
require_once("{$wmsInclude}/chklogin.php");
require_once("{$wmsInclude}/cl_Bluejay.php");

$excel="";
if (isset($_REQUEST["excel"])) $excel=$_REQUEST["excel"]; else $excel="";
if (isset($_REQUEST["criteria"])) $criteria=$_REQUEST["criteria"]; else $criteria="";
if (isset($_REQUEST["referer"])) $REFER=$_REQUEST["referer"]; else $REFER="{$top}/webmenu.php";
if ($REFER == "0" or $REFER=="") $REFER="{$top}/webmenu.php";
if (isset($_REQUEST["SORT"])) $SORT=$_REQUEST["SORT"]; else $SORT=0;
//echo "<pre>";
//echo "SORT={$SORT}\n";
//print_r($_REQUEST);
//exit;
if (isset($_REQUEST["floathdr"])) $floathdr=$_REQUEST["floathdr"]; else $floathdr="";

if (isset($_REQUEST["B1"])) $B1=$_REQUEST["B1"]; else $B1="";

//Search Criteria and passed variables
isset($_REQUEST["loc"]) ? $loc=strtoupper($_REQUEST["loc"]) : $loc="";
isset($_REQUEST["Z"]) ? $Z=strtoupper($_REQUEST["Z"]) : $Z="";
isset($_REQUEST["comp"]) ? $comp=$_REQUEST["comp"] : $comp=1;

require_once("{$wmsInclude}/db_main.php");
require_once("{$wmsInclude}/cl_rpt.php");


//Start report
$need_input_before_find=true;
$report=new Report;
$report->init();
$report->B1=$B1;
$report->B1="Run Report";
$report->excel=$excel;
$report->REFER=$REFER;
$report->showpipe=true;
$report->fieldDelimiter="       ";
$B1="Run Report";

$report->title="Aisle Guide - Show Bin Usage Report";
// Custom Report Settings
// Fields [name]="align,mask,title,class (if not 0)"
$report->fields["Bin"]="L,";
$report->fields["P_L"]="L,";
$report->fields["PartNumber"]="L,";
$report->fields["PartDesc"]="L,";
$report->fields["Qty"]="R,";
$report->fields["UOM"]="L,";
$report->fields["BT"]="R,,Bin Type";
$report->fields["Width"]="R,";
$report->fields["Depth"]="R,";
$report->fields["Height"]="R,";
$report->fields["Volume"]="R,%1.2f";

$prevPart="";

//for computed fields, use BeforeShowRecord else comment it out
function BeforeShowRecord($record,$fields)
{
global $report;
 if ( $record["Qty"] <> " " and intval($record["Qty"]) < 1)
  $report->fields["Qty"]="R,,,Alt5DataTD"; 
 else $report->fields["Qty"]="R,";

if (1 == 2)
{
//Calculate all the fields needed here
global $prevPart;
global $db1;
global $comp;

 // ditto certain fields when part has multiple bins unless exporting
 if ($report->pipe > 0 or $report->excel > 0) return $record;
 if ($record["PartNumber"] == $prevPart)
 {
  $record["P_L"]="\"";
  $record["PartNumber"]="\"";
  $record["PartDesc"]="\"";
  $record["Qty"]="\"";
  $record["UOM"]="\"";
 }
 if ($record["PartNumber"] <> "\"") $prevPart=$record["PartNumber"];
 $SQL=<<<SQL
 select 
p_l as P_L,
part_number as PartNumber,
part_desc as PartDesc,
whs_qty as Qty,
whs_uom as UOM,
whs_code as BT

from WHSELOC, PARTS
where whs_company = {$comp}
and whs_location = "{$record["Bin"]}"
and shadow_number = whs_shadow
SQL;
  
 $rc=$db1->query($SQL);
  $numrows=$db1->num_rows();
  $i=1;
  while ($i <= $numrows)
  {
   $db1->next_record();
     if ($numrows and $db1->Record)
     {
      foreach ($db1->Record as $key=>$data)
       {
        if (!is_numeric($key)) { $record["$key"]=$data; }
       }
     }
    $i++;
  } // while i < numrows
 
 if ($record["BT"]=="O") $record["BT"]=" ";
} // end 1 == 2

return $record ;
} // end function BeforeShowRecord

//xtra - Special considerations for fields
// first char is when, B=before,A=After
// if more than 1 action, add array for field name
// when,form,action
// when,link,href,target
// when,popup,href
// when,checkbox,cb_name,checked
// when,hidden,
// when submit, button value, button name
$report->other_hidden="";

//Set up Sort
$report->SORT=$SORT;
$report->sort[0]["SQL"]=<<<SQL
order by wb_location,p_l,part_seq_num,part_number
SQL;
$report->sort[1]["SQL"]=<<<SQL
order by p_l,part_seq_num,part_number
SQL;

$report->sort[0]["Desc"]="Bin, P/L, Part Number";
$report->sort[1]["Desc"]="P/L Part Number";

$order_by=$report->sort[0]["SQL"];
if (isset($SORT)) $order_by=$report->sort[$SORT]["SQL"];



//set criteria
$report->criteria=$criteria;
$report->criteria["types"]["Z"]="text";
$report->criteria["Z"]["prompt"]="Zone";
$report->criteria["Z"]["value"]="{$Z}";
$report->criteria["types"]["loc"]="utext";
$report->criteria["loc"]["prompt"]="Bin Location";
$report->criteria["loc"]["value"]="{$loc}";
$db1=new WMS_DB;

/* Don't have company yet, rem it out ************************************
$cmp=get_companys($db,0);
$report->criteria["types"]["comp"]="select";
$report->criteria["comp"]["prompt"]="Company";
foreach ($cmp as $c=>$cdata)
 {
  $j=strpos($cdata["company_city"],",");
  $d=$c . " - " . substr($cdata["company_city"],0,$j) . " " . $cdata["company_abbr"];
  $report->criteria["comp"][$c]["Desc"]=$d;
  $report->criteria["comp"][$c]["selected"]="";
  if ($comp == $c) $report->criteria["comp"][$c]["selected"]=" selected";
 } // end foreach cmp
// end company ************************************************************ */

/* sample drop down *********************************************************
$report->criteria["types"]["stko"]="select";
$report->criteria["stko"]["prompt"]="Stocking Parts Only";
$report->criteria["stko"][1]["Desc"]="Yes";
$report->criteria["stko"][1]["selected"]="";
$report->criteria["stko"][2]["Desc"]="No";
$report->criteria["stko"][1]["selected"]="";
$report->criteria["stko"][$stko]["selected"]=" selected";

// end sample dropdown **************************************************** */

//Totals section
// Totals [name]="align,#of Dec"
// In Align field, S=SubTotal D=Total Prompt Display


//Set SQL
$where="where wb_location like \"{$Z}%\"";
$extra="";
if (trim($loc) <> "") $extra=" and wb_location like \"{$loc}%\"";

$report->SQL=<<<SQL
select
A.wb_location as Bin,
IFNULL(P.p_l,"-") as P_L,
IFNULL(P.part_number," ") as PartNumber,
IFNULL(P.part_desc," ") as PartDesc,
IFNULL(B.whs_qty," ") as Qty,
IFNULL(B.whs_uom," ") as UOM,
IFNULL(B.whs_code," ") as BT,
wb_width as Width,
wb_depth as Depth,
wb_height as Height,
wb_volume as Volume
from WHSEBINS A
LEFT JOIN WHSELOC B ON B.whs_location = A.wb_location AND B.whs_company = A.wb_company
 LEFT JOIN  PARTS P ON P.shadow_number = B.whs_shadow
{$where}
and wb_company = {$comp}
{$extra}
{$order_by}
SQL;
// End Custom Report Settings

//Run it

//Add any extra html required here if needed
$dhtm="";
$report->dhtm=$dhtm;

if ($need_input_before_find and $loc == "" and $Z == "")
{
  unset($B1);
  unset($report->B1);
} // prompt user
else
{
$report->B1="Run Report";
}
 $report->Run();
?>
