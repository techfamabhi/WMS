<?php
// inv_list.php - report of items in inventory
// 8/8/22 dse initial

foreach (array_keys($_REQUEST) as $w) { $$w=$_REQUEST[$w]; }
error_reporting(0);

session_start();
//echo "<pre>";
//print_r($_REQUEST);
//echo "</pre>";

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
isset($_REQUEST["noUPC"]) ? $noUPC=$_REQUEST["noUPC"] : $noUPC=0;
isset($_REQUEST["PL"]) ? $PL=strtoupper($_REQUEST["PL"]) : $PL="";
isset($_REQUEST["SUPC"]) ? $SUPC=strtoupper($_REQUEST["SUPC"]) : $SUPC="";
isset($_REQUEST["comp"]) ? $comp=$_REQUEST["comp"] : $comp=1;

require_once("{$wmsInclude}/db_main.php");
require_once("{$wmsInclude}/cl_rpt.php");

$showBin=false;
if ($showBin) $db=new WMS_DB;

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

$report->title="Parts with Inventory UPC List";
// Custom Report Settings
// Fields [name]="align,mask,title,class (if not 0)"
if ($showBin) $ba="L"; else $ba="H";
$report->fields["shadow"]="H,";
$report->fields["PL"]="L,";
$report->fields["PartNumber"]="L,";
$report->fields["PartDesc"]="L,";
$report->fields["QOH"]="R,,Quantity on Hand";
$report->fields["UPC"]="L,";
$report->fields["UPCQty"]="R,";
$report->fields["Bin"]="{$ba},";

$prevPart="";

//for computed fields, use BeforeShowRecord else comment it out
function BeforeShowRecord($record,$fields)
{
//Calculate all the fields needed here
global $prevPart;
global $report;
global $showBin;
if ($showBin)
{
global $comp;
global $db;
//global $SUPC;

$SQL=<<<SQL
select whs_location from WHSELOC
where whs_company = {$comp}
and whs_shadow = {$record["shadow"]}

SQL;

  $rc=$db->query($SQL);
  $numrows=$db->num_rows();
  $i=1;
  while ($i <= $numrows)
  {
   $db->next_record();
     if ($numrows)
     {
        $record["Bin"]=$db->f("whs_location");
     }
     $i++;
   } // while i < numrows
} // end showBin

 // ditto certain fields when part has multiple bins unless exporting
 if ($report->pipe > 0 or $report->excel > 0) return $record;
 //if ($SUPC <> "")
 //{
  //$j=strlen($SUPC);
  //if (substr(trim($record["UPC"]),0,$j) <> $SUPC)
  //unset($record);
  //return $record;
 //}
 if ($record["PartNumber"] == $prevPart)
 {
  $record["PartNumber"]="\"";
  $record["PartDesc"]="\"";
  $record["QOH"]=" ";
 }
 if ($record["PartNumber"] <> "\"") $prevPart=$record["PartNumber"];

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

$report->sort[0]["Desc"]="P/L Part Number";

$order_by=$report->sort[0]["SQL"];
if (isset($SORT)) $order_by=$report->sort[$SORT]["SQL"];


//set criteria
$report->criteria=$criteria;
$report->criteria["types"]["PL"]="text";
$report->criteria["PL"]["prompt"]="P/L";
$report->criteria["PL"]["value"]="{$PL}";
//$report->criteria["types"]["SUPC"]="text";
//$report->criteria["SUPC"]["prompt"]="UPCs Starting With";
//$report->criteria["SUPC"]["value"]="{$SUPC}";

$report->criteria["types"]["noUPC"]="select";
$report->criteria["noUPC"]["prompt"]="No UPC Parts Only";
$report->criteria["noUPC"][1]["Desc"]="Yes";
$report->criteria["noUPC"][1]["selected"]="";
$report->criteria["noUPC"][0]["Desc"]="No";
$report->criteria["noUPC"][1]["selected"]="";
$report->criteria["noUPC"][$noUPC]["selected"]=" selected";

//$report->criteria["types"]["justNo"]="checkbox";
//$report->criteria["justNo"]["prompt"]="Parts with No UPC";
//$report->criteria["justNo"]["value"]="{$justNo}";

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

//$report->Totals["PL"]="S,";
//$report->Totals["Date"]="D,";
//$report->Totals["Amount"]="R,2";


//Set SQL
$where="where p_l like \"{$PL}%\"";
$extra="";
//if ($SUPC <> "") $extra="and alt_part_number like \"{$SUPC}%\"";
if (isset($noUPC) and $noUPC > 0)
{ // in inventory with no UPC

$report->SQL=<<<SQL
select
shadow_number as shadow,
p_l as PL,
part_number as PartNumber,
part_desc as PartDesc,
(qty_avail + qty_alloc) as QOH,
" " as UPC,
"" as UPCQty,
"" as Bin
from PARTS A, WHSEQTY B

{$where}
and B.ms_shadow = A.shadow_number
and ms_company = {$comp}
 and (B.qty_avail <> 0
   or B.qty_alloc <> 0
   or B.qty_putaway <> 0
   or B.qty_defect <> 0
   or B.qty_core <> 0)

 and shadow_number not in (select alt_shadow_num from  ALTERNAT
                            where alt_shadow_num = A.shadow_number
                            AND (alt_type_code < 1))

{$order_by}

SQL;

} // in inventory with no UPC
else
{ // all parts with inventory


$report->SQL=<<<SQL
select
shadow_number as shadow,
p_l as PL,
part_number as PartNumber,
part_desc as PartDesc,
(qty_avail + qty_alloc) as QOH,
IFNULL(alt_part_number," ") as UPC,
IFNULL(ABS(alt_type_code),"") as UPCQty,
" " as Bin
from PARTS A
LEFT JOIN ALTERNAT C
ON C.alt_shadow_num = A.shadow_number
AND C.alt_type_code < 1
{$extra}
JOIN WHSEQTY B ON B.ms_shadow = A.shadow_number
and ms_company = {$comp}
and (B.qty_avail <> 0
  or B.qty_alloc <> 0
  or B.qty_putaway <> 0
  or B.qty_defect <> 0
  or B.qty_core <> 0)

{$where}
{$order_by}

SQL;

} // all parts with inventory
//echo "<pre>{$report->SQL}";
//exit;
//echo "</pre>";
// End Custom Report Settings

//Run it

//Add any extra html required here if needed
$dhtm="";
$report->dhtm=$dhtm;

if ($need_input_before_find and $PL == "")
{
  unset($B1);
  unset($report->B1);
} // prompt user
else
$report->B1="Run Report";
 $report->Run();
?>
