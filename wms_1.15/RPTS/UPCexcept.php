<?php
// toteList.php - report of Totes
// 03/25/24 dse initial

foreach (array_keys($_REQUEST) as $w) { $$w=$_REQUEST[$w]; }
error_reporting(E_ALL);

session_start();

if (get_cfg_var('wmsdir')) $wmsDir=get_cfg_var('wmsdir');
else { echo "<h1>WMS System is not Configured on this System</h1>"; exit; }
$top=str_replace("/var/www","",$wmsDir);

if (isset($_SESSION["wms"])) require($_SESSION["wms"]["wmsConfig"]);
else require("{$wmsDir}/config.php");

$thisprogram="toteList.php";
if (!isset($wmsInclude)) $wmsInclude="{$wmsDir}/include";
require_once("{$wmsInclude}/chklogin.php");
require_once("{$wmsInclude}/cl_Bluejay.php");
require_once("{$wmsInclude}/db_main.php");
$db=new WMS_DB;

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
isset($_REQUEST["rptOn"]) ? $rptOn=strtoupper($_REQUEST["rptOn"]) : $rptOn="0";
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

$report->title="UPC Assignment Exceptions";
// Custom Report Settings
// Fields [name]="align,mask,title,class (if not 0)"
$report->fields["shadow"]="H,";
$report->fields["PL"]="L,";
$report->fields["PartNumber"]="L,";
$report->fields["Description"]="L,";
$report->fields["UPC"]="L,";
$report->fields["Qty"]="R,";
$report->fields["UOM"]="L,";
$report->fields["Source"]="L,";
$report->fields["UserId"]="L,";
$report->fields["DT"]="L,";


$prevPart="";

//for computed fields, use BeforeShowRecord else comment it out
function BeforeShowRecord($record,$fields)
{
//Calculate all the fields needed here
global $prevPart;
global $report;
global $db;

 if ($record["UPC"] == "NOUPC") $record["UPC"]="No UPC";
 else
 { // not a no upc
  if ($record["UPC"] <> "")
  {
   $SQL=<<<SQL
 select source, userId , occurred 
 from UPCLOG
 where shadow   = {$record["shadow"]}
 and upc = "{$record["UPC"]}"
 
SQL;
  $d=$db->gData($SQL);
  $record["Source"]=$d[1]["source"];
  $record["UserId"]=$d[1]["userId"];
  $record["DT"]=$d[1]["occurred"];
 
  }
 } // not a no upc
 if ($record["Qty"] <> 1) $report->fields["Qty"]="R,,,Alt5DataTD";
 else $report->fields["Qty"]="R,";



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
order by p_l,part_number
SQL;

if (isset($SORT)) $order_by=$report->sort[$SORT]["SQL"];


//set criteria
$report->criteria=$criteria;
$report->criteria["types"]["rptOn"]="select";
$report->criteria["rptOn"]["prompt"]="Report On";
$report->criteria["rptOn"][0]["Desc"]="Both 1 &amp; 2";
$report->criteria["rptOn"][0]["selected"]="";
$report->criteria["rptOn"][1]["Desc"]="(1) Qty Not One";
$report->criteria["rptOn"][1]["selected"]="";
$report->criteria["rptOn"][2]["Desc"]="(2) No UPC";
$report->criteria["rptOn"][2]["selected"]="";
$report->criteria["rptOn"][3]["Desc"]="All UPC's";
$report->criteria["rptOn"][3]["selected"]="";
if (isset($rptOn))
$report->criteria["rptOn"][$rptOn]["selected"]=" selected";

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
$where=<<<SQL
where (
upc_scanned = "NOUPC"
or upc_qty > 1)

SQL;
if (isset($rptOn) and $rptOn == 1) $where="where upc_qty > 1\n";
if (isset($rptOn) and $rptOn == 2) $where='where upc_scanned = "NOUPC"' ."\n";
if (isset($rptOn) and $rptOn == 3) $where='where upc_scanned not in ("NOUPC","")' ."\n";
$extra="";

$report->SQL=<<<SQL
select 
shadow,
p_l as PL,
part_number as PartNumber,
part_desc as Description,
upc_scanned as UPC,
upc_qty as Qty,
unit_of_measure as UOM,
" " as Source,
0 as UserId,
" " as DT

from NEEDUPC,PARTS
{$where}
and shadow_number = shadow
{$order_by}
SQL;
// End Custom Report Settings

//Run it

//Add any extra html required here if needed
$dhtm="";
$report->dhtm=$dhtm;

/*
if ($need_input_before_find and $TotePrx == "")
{
  unset($B1);
  unset($report->B1);
} // prompt user
else
*/
$report->B1="Run Report";
 $report->Run();
?>
