<?php
foreach (array_keys($_REQUEST) as $w) {
    $$w = $_REQUEST[$w];
}
//error_reporting(0);

session_start();

if (get_cfg_var('wmsdir')) $wmsDir = get_cfg_var('wmsdir');
else {
    echo "<h1>WMS System is not Configured on this System</h1>";
    exit;
}
$top = str_replace("/var/www", "", $wmsDir);

require($_SESSION["wms"]["wmsConfig"]);
$thisprogram = "cust_list.php";
require_once("{$wmsInclude}/chklogin.php");
require_once("{$wmsInclude}/cl_Bluejay.php");

$excel = "";
if (isset($_REQUEST["excel"])) $excel = $_REQUEST["excel"]; else $excel = "";
if (isset($_REQUEST["criteria"])) $criteria = $_REQUEST["criteria"]; else $criteria = "";
if (isset($_REQUEST["referer"])) $REFER = $_REQUEST["referer"]; else $REFER = "{$top}/webmenu.php";
if ($REFER == "0" or $REFER == "") $REFER = "{$top}/webmenu.php";
if (isset($_REQUEST["SORT"])) $SORT = $_REQUEST["SORT"]; else $SORT = 0;
//echo "<pre>";
//echo "SORT={$SORT}\n";
//print_r($_REQUEST);
//exit;
if (isset($_REQUEST["floathdr"])) $floathdr = $_REQUEST["floathdr"]; else $floathdr = "";

if (isset($_REQUEST["B1"])) $B1 = $_REQUEST["B1"]; else $B1 = "";

//Search Criteria and passed variables
isset($_REQUEST["vname"]) ? $vname = strtoupper($_REQUEST["vname"]) : $vname = "";
isset($_REQUEST["vendor"]) ? $vendor = strtoupper($_REQUEST["vendor"]) : $vendor = "";
isset($_REQUEST["comp"]) ? $comp = $_REQUEST["comp"] : $comp = 1;

require_once("{$wmsInclude}/db_main.php");
require_once("{$wmsInclude}/cl_rpt.php");


//Start report
$need_input_before_find = false;
$report = new Report;
$report->init();
$report->B1 = $B1;
$report->B1 = "Run Report";
$report->excel = $excel;
$report->REFER = $REFER;
$report->showpipe = true;
$report->fieldDelimiter = "       ";
$B1 = "Run Report";

$report->title = "Vendor List";
// Custom Report Settings
// Fields [name]="align,mask,title,class (if not 0)"
$report->fields["Vendor_Id"] = "L,";
$report->fields["Name"] = "L,";
$report->fields["Address1"] = "L,";
$report->fields["Address2"] = "L,";
$report->fields["City"] = "L,";
$report->fields["St"] = "L,";
$report->fields["Zip"] = "L,";
$report->fields["Country"] = "L,";
$report->fields["Contact"] = "L,";
$report->fields["Phone"] = "L,";
$report->fields["Email"] = "L,";
$report->fields["B_O"] = "L,";
$report->fields["ToBin"] = "L,,Recv to bin allowed";
$report->fields["InPlace"] = "L,,Recv on original pallet allowed";

//for computed fields, use BeforeShowRecord else comment it out
function BeforeShowRecord($record, $fields)
{
//Calculate all the fields needed here

    return ($record);
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
$report->other_hidden = "";

//Set up Sort
$report->SORT = $SORT;
$report->sort[0]["SQL"] = <<<SQL
order by vendor
SQL;

$report->sort[1]["SQL"] = <<<SQL
order by name,vendor
SQL;
$report->sort[0]["Desc"] = "Vendor Id";
$report->sort[1]["Desc"] = "Vendor Name";

$order_by = $report->sort[0]["SQL"];
if (isset($SORT)) $order_by = $report->sort[$SORT]["SQL"];


//set criteria
$report->criteria = empty($criteria) ? [] : $criteria;
$report->criteria["types"]["vname"] = "utext";
$report->criteria["vname"]["prompt"] = "Vendor Name";
$report->criteria["vname"]["value"] = "{$vname}";
$report->criteria["types"]["vendor"] = "text";
$report->criteria["vendor"]["prompt"] = "Vendor Id";
$report->criteria["vendor"]["value"] = "{$vendor}";

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
$where = "where vendor like \"{$vendor}%\"";
$extra = "";
if (trim($vname) <> "") $extra = " and name like \"{$vname}%\"";

$report->SQL = <<<SQL
select
vendor as Vendor_Id,
name as Name,
addr1 as Address1,
addr2 as Address2,
city as City,
state as St,
zip as Zip,
ctry as Country,
contact as Contact,
phone as Phone,
email as Email,
allow_bo as B_O,
allow_to_bin as ToBin,
allow_inplace as InPlace

from VENDORS
{$where}
{$extra}
{$order_by}
SQL;
// End Custom Report Settings

//Run it

//Add any extra html required here if needed
$dhtm = "";
$report->dhtm = $dhtm;

if ($need_input_before_find and $vname == "" and $vendor == "") {
    unset($B1);
    unset($report->B1);
} // prompt user
else
    $report->B1 = "Run Report";
$report->Run();
?>
