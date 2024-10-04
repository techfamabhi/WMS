<?php
// inv_list.php - report of items in inventory
// 8/8/22 dse initial

foreach (array_keys($_REQUEST) as $w) { $$w=$_REQUEST[$w]; }
error_reporting(0);

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
isset($_REQUEST["PL"]) ? $PL=strtoupper($_REQUEST["PL"]) : $PL="";
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

$report->title="Part with Negative Inventory or Allocation";
// Custom Report Settings
// Fields [name]="align,mask,title,class (if not 0)"
$report->fields["PL"]="L,";
$report->fields["PartNumber"]="L,";
$report->fields["PartDesc"]="L,";
$report->fields["UOM"]="L,";
$report->fields["Avail"]="R,";
$report->fields["OnPick"]="R,";
$report->fields["Putaway"]="R,";
$report->fields["Defect"]="R,";
$report->fields["QtyCore"]="R,";
$report->fields["BT"]="R,,Bin Type";
$report->fields["Bin"]="L,";
$report->fields["BinQty"]="R,";
$report->fields["BinOnPick"]="R,";
$report->fields["BinUOM"]="L,";

$prevPart="";

//for computed fields, use BeforeShowRecord else comment it out
function BeforeShowRecord($record,$fields)
{
//Calculate all the fields needed here
global $prevPart;
global $report;

 // ditto certain fields when part has multiple bins unless exporting
 if ($report->pipe > 0 or $report->excel > 0) return $record;
 if ($record["PartNumber"] == $prevPart)
 {
  $record["PartNumber"]="\"";
  $record["Avail"]="\"";
  $record["PartDesc"]="\"";
  $record["UOM"]="\"";
  $record["OnPick"]="\"";
  $record["Putaway"]="\"";
  $record["Defect"]="\"";
  $record["QtyCore"]="\"";
 }
 if ($record["PartNumber"] <> "\"") $prevPart=$record["PartNumber"];
 if ($record["BT"]=="O") $record["BT"]=" ";

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
order by p_l,part_seq_num,part_number
SQL;

$report->sort[1]["SQL"]=<<<SQL
order by whs_location,p_l,part_seq_num,part_number
SQL;
$report->sort[0]["Desc"]="P/L Part Number";
$report->sort[1]["Desc"]="Bin, P/L, Part Number";

$order_by=$report->sort[0]["SQL"];
if (isset($SORT)) $order_by=$report->sort[$SORT]["SQL"];


//set criteria
$report->criteria=$criteria;
$report->criteria["types"]["PL"]="text";
$report->criteria["PL"]["prompt"]="P/L";
$report->criteria["PL"]["value"]="{$PL}";
$report->criteria["types"]["loc"]="utext";
$report->criteria["loc"]["prompt"]="Bin Location";
$report->criteria["loc"]["value"]="{$loc}";

/* Don't have company yet, rem it out ************************************
$db=new WMS_DB;
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

//$report->Totals["PL"]="S,";
//$report->Totals["Date"]="D,";
//$report->Totals["Amount"]="R,2";


//Set SQL
$where="where p_l like \"{$PL}%\"";
$extra="";
if (trim($loc) <> "") $extra=" and whs_location like \"{$loc}%\"";
else $extra='and (whs_location = "" or whs_location = "NONE")';

$report->SQL=<<<SQL
select
p_l as PL,
part_number as PartNumber,
part_desc as PartDesc,
unit_of_measure as UOM,
qty_avail as Avail,
qty_alloc as OnPick,
qty_putaway as Putaway,
qty_defect as Defect,
qty_core as QtyCore,
whs_code as BT ,
whs_location as Bin,
whs_qty as BinQty ,
whs_alloc as BinOnPick,
whs_uom as BinUOM

from PARTS, WHSEQTY, WHSELOC
{$where}
and ms_shadow = shadow_number
and ms_company = {$comp}
and (qty_avail < 0
  or qty_alloc < 0
  or whs_qty < 0
  or whs_alloc < 0
  or qty_putaway < 0
  or qty_defect < 0
  or qty_core < 0)
and whs_shadow = shadow_number
and whs_company = {$comp}
{$extra}
{$order_by}
SQL;
// End Custom Report Settings

//Run it

//Add any extra html required here if needed
$dhtm="";
$report->dhtm=$dhtm;

if ($need_input_before_find and $loc == "" and $PL == "")
{
  unset($B1);
  unset($report->B1);
} // prompt user
else
$report->B1="Run Report";
 $report->Run();
?>
