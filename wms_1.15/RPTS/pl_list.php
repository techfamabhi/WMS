<?php
// pl_list.php - report of P/L on file
// 02/16/23 dse initial

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
isset($_REQUEST["vend"]) ? $vend=strtoupper($_REQUEST["vend"]) : $vend="%";
isset($_REQUEST["pld"]) ? $pld=$_REQUEST["pld"] : $pld="";
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

$report->title="Prodline Line List";
// Custom Report Settings
// Fields [name]="align,mask,title,class (if not 0)"
$report->fields["PL"]="L,";
$report->fields["PLDesc"]="L,";
$report->fields["Vendor"]="L,";
$report->fields["PrefZone"]="L,";
$report->fields["PrefAisle"]="R,";
$report->fields["DateAdded"]="L,";

$prevPart="";

//for computed fields, use BeforeShowRecord else comment it out
function BeforeShowRecord($record,$fields)
{
//Calculate all the fields needed here
global $report;

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
order by pl_code
SQL;

$report->sort[1]["SQL"]=<<<SQL
order by vendor,pl_code
SQL;
$report->sort[0]["Desc"]="P/L";
$report->sort[1]["Desc"]="Vendor, P/L";

$order_by=$report->sort[0]["SQL"];
if (isset($SORT)) $order_by=$report->sort[$SORT]["SQL"];


//set criteria
$report->criteria=$criteria;
$report->criteria["types"]["vend"]="text";
$report->criteria["vend"]["prompt"]="Vendor";
$report->criteria["vend"]["value"]="{$vend}";
$report->criteria["types"]["pld"]="utext";
$report->criteria["pld"]["prompt"]="Description";
$report->criteria["pld"]["value"]="{$pld}";
$report->criteria["types"]["loc"]="utext";
$report->criteria["loc"]["prompt"]="Zone";
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
$where="where pl_company = {$comp}";
$extra="";
if (trim($vend) <> "" and $vend <> "%")
{
  $v=str_replace("!","",$vend);
  if (strpos($vend,'!') !== false) $nl="not "; else $nl="";
  $extra=" and pl_vend_code {$nl}like \"{$v}%\"";
}
if (trim($loc) <> "") $extra=" and pl_perfered_zone like \"{$loc}%\"";
if (trim($pld) <> "") $extra=" and pl_desc like \"{$pld}%\"";

$report->SQL=<<<SQL
select 
pl_code as PL,
pl_desc as PLDesc,
pl_vend_code as Vendor,
pl_perfered_zone as PrefZone,
pl_perfered_aisle as PrefAisle,
DATE_FORMAT( pl_date_added, "%m/%d/%y") as DateAdded
from PRODLINE
{$where}
{$extra}
{$order_by}
SQL;
// End Custom Report Settings

//Run it

//Add any extra html required here if needed
$dhtm="";
$report->dhtm=$dhtm;

if ($need_input_before_find and $loc == "" and $vend == "")
{
  unset($B1);
  unset($report->B1);
} // prompt user
else
$report->B1="Run Report";
 $report->Run();
?>
