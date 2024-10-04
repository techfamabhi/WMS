<?php
foreach (array_keys($_REQUEST) as $w) { $$w=$_REQUEST[$w]; }
//error_reporting(0);

session_start();

if (get_cfg_var('wmsdir')) $wmsDir=get_cfg_var('wmsdir');
else { echo "<h1>WMS System is not Configured on this System</h1>"; exit; }
$top=str_replace("/var/www","",$wmsDir);

require($_SESSION["wms"]["wmsConfig"]);
$thisprogram="cust_list.php";
require_once("{$wmsInclude}/chklogin.php");
require_once("{$wmsInclude}/cl_Bluejay.php");
require_once("{$wmsInclude}/get_companys.php");

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
isset($_REQUEST["descript"]) ? $descript=strtoupper($_REQUEST["descript"]) : $descript="";
isset($_REQUEST["sysTem"]) ? $sysTem=strtoupper($_REQUEST["sysTem"]) : $sysTem="";
isset($_REQUEST["comp"]) ? $comp=$_REQUEST["comp"] : $comp=1;

require_once("{$wmsInclude}/db_main.php");
require_once("{$wmsInclude}/cl_rpt.php");

$db1=new WMS_DB;
$cmp=get_companys($db1,0);
//Start report
$report=new Report;
$report->init();
$report->B1=$B1;
$report->B1="Run Report";
$report->excel=$excel;
$report->REFER=$REFER;
$report->showpipe=true;
$report->fieldDelimiter="       ";
$B1="Run Report";

$report->title="Available Option List";
// Custom Report Settings
// Fields [name]="align,mask,title,class (if not 0)"
$report->fields["OptionId"]="R,";
$report->fields["Description"]="L,";
$report->fields["Description2"]="L,";
$report->fields["Category"]="L,";
$report->fields["Setting"]="L,";
$report->fields["Comments"]="L,";

//for computed fields, use BeforeShowRecord else comment it out
function BeforeShowRecord($record,$fields)
{
 global $db1;
//Calculate all the fields needed here
 $ret="Not Set";
$comp=1; // later loop thru all comps and setup field for each, then loop to read
 $SQL=<<<SQL
select cop_flag
from COPTIONS 
where cop_company = {$comp}
and cop_option = {$record["OptionId"]}
SQL;

  $rc=$db1->query($SQL);
  $numrows=$db1->num_rows();
  $i=1;
  while ($i <= $numrows)
  {
   $db1->next_record();
     if ($numrows)
     {
        $ret=$db1->f("cop_flag");
     }
     $i++;
   } // while i < numrows
 $record["Setting"]=$ret;
return($record);
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
order by copt_number
SQL;

$report->sort[1]["SQL"]=<<<SQL
order by copt_desc,copt_number
SQL;
$report->sort[0]["Desc"]="Option Id";
$report->sort[1]["Desc"]="Option Description";

$order_by=$report->sort[0]["SQL"];
if (isset($SORT)) $order_by=$report->sort[$SORT]["SQL"];


//set criteria
$report->criteria=$criteria;
$report->criteria["types"]["descript"]="utext";
$report->criteria["descript"]["prompt"]="Description";
$report->criteria["descript"]["value"]="{$descript}";
$report->criteria["types"]["sysTem"]="utext";
$report->criteria["sysTem"]["prompt"]="Category";
$report->criteria["sysTem"]["value"]="{$sysTem}";

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
$where="where copt_desc like \"{$descript}%\"";
$extra="";
if (trim($sysTem) <> "") $extra=" and copt_cat like \"{$sysTem}%\"";

$report->SQL=<<<SQL
select 
copt_number as OptionId,
copt_desc as Description,
copt_desc1 as Description2,
copt_cat as Category,
"Not Set" as Setting,
copt_text as Comments
from COPTDESC
{$where}
{$extra}
{$order_by}
SQL;
// End Custom Report Settings

//Run it

$dhtm="";
$report->dhtm=$dhtm;
if ($descript == "" and $sysTem == "" and 1 == 2)
{
  unset($B1);
  unset($report->B1);
} // prompt user
else
$report->B1="Run Report";
 $report->Run();
?>
