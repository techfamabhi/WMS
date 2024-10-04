<?php
// inv_list.php - report of items in inventory
// 8/8/22 dse initial

foreach (array_keys($_REQUEST) as $w) { $$w=$_REQUEST[$w]; }
//error_reporting(0);

session_start();
//echo "<pre>";
//print_r($_REQUEST);
//echo "</pre>";

if (get_cfg_var('wmsdir')) $wmsDir=get_cfg_var('wmsdir');
else { echo "<h1>WMS System is not Configured on this System</h1>"; exit; }
$top=str_replace("/var/www","",$wmsDir);

if (isset($_SESSION["wms"])) require($_SESSION["wms"]["wmsConfig"]);
else require("{$wmsDir}/config.php");

$thisprogram="dupeUPC.php";
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
$db=new WMS_DB;
if ($showBin) $db1=new WMS_DB;

if (isset($shadow) and isset($upc) and isset($qty))
 {
  $SQL=<<<SQL
delete from ALTERNAT
where alt_shadow_num = {$shadow}
and alt_part_number = "{$upc}"
and alt_type_code = {$qty}
SQL;
 $rc=$db->Update($SQL);
   $htm=<<<HTML
 <html>
 <head>
 <script>
window.top.location.href="{$thisprogram}";
 </script>
 </head>
 <body>
 </body>
</html>

HTML;
echo $htm;
exit;

 } // end shadow, upc and qty are set
//Start report
$need_input_before_find=false;
$report=new Report;
$report->init();
$report->B1=$B1;
$report->B1="Run Report";
$report->excel=$excel;
$report->REFER=$REFER;
$report->showpipe=true;
$report->fieldDelimiter="       ";
$report->extra_js=<<<HTML
<script>
function delUPC(shadow,upc,qty,pn)
{
 if (confirm("Delete UPC: " + upc + " for Part#: " + pn))
 {
  var input = document.createElement("input");
  var frm=document.form1;

  input.setAttribute("type", "hidden");
  input.setAttribute("name", "shadow");
  input.setAttribute("value", shadow);
  frm.appendChild(input);
 
  var input = document.createElement("input");
  input.setAttribute("type", "hidden");
  input.setAttribute("name", "upc");
  input.setAttribute("value", upc);
  frm.appendChild(input);
  var input = document.createElement("input");
  input.setAttribute("type", "hidden");
  input.setAttribute("name", "qty");
  input.setAttribute("value", qty);
  frm.appendChild(input);

 document.form1.submit();
 } // end confirm delete
 else return false;

//append to form element that you want .

 //var ac=document.form1.action;
 //document.form1.action=ac + "?shadow=" + shadow + "&upc=" + upc + "&qty=" + qty;
//alert(document.form1.action);
 //window.location.href=document.form1.action;
}
</script>
HTML;
$B1="Run Report";

$report->title="Duplicate UPC List";
// Custom Report Settings
// Fields [name]="align,mask,title,class (if not 0)"
if ($showBin) $ba="L"; else $ba="H";
$report->fields["shadow"]="H,";
$report->fields["UPC"]="L,";
$report->fields["_"]="L,";
$report->fields["P_L"]="L,";
$report->fields["PartNumber"]="L,";
$report->fields["PartDesc"]="L,";
$report->fields["DupeCount"]="R,";
$report->fields["UPCQty"]="R,";
$report->fields["BinQty"]="R,";
$report->fields["Bin"]="L,";
//$report->fields["BinQty"]="H,";

$prevPart="";
$prevDesc="";

//for computed fields, use BeforeShowRecord else comment it out
function BeforeShowRecord($record,$fields)
{
//Calculate all the fields needed here
global $prevPart;
global $prevDesc;
global $report;
global $wmsImages;
global $showBin;
global $comp;
global $db1;

 // ditto certain fields when part has multiple bins unless exporting
 if ($report->pipe > 0 or $report->excel > 0) return $record;
 //if ($SUPC <> "")
 //{
  //$j=strlen($SUPC);
  //if (substr(trim($record["UPC"]),0,$j) <> $SUPC)
  //unset($record);
  //return $record;
 //}
 if ($record["UPC"] == $prevPart) $record["UPC"]="\"";
 if ($record["PartDesc"] == $prevDesc) $record["PartDesc"]="\"";
 if ($record["UPC"] <> "\"") $prevPart=$record["UPC"];
 if ($record["PartDesc"] <> "\"") $prevPart=$record["PartDesc"];
 if ($record["BinQty"] <> 0) $report->fields["BinQty"]="R,,,Alt5DataTD";
 else $report->fields["BinQty"]="R,";

 $s=$record["shadow"];
 $u=$record["UPC"];
 $q=-$record["UPCQty"];
 $qt="'";
 $record["_"]=<<<HTML
<img src="{$wmsImages}/trash2.png" onclick="delUPC({$s},'{$u}',{$q},'{$record["P_L"]} {$record["PartNumber"]}');">

HTML;

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
order by alt_part_number,p_l,part_number
SQL;

$report->sort[0]["Desc"]="UPC P/L Part Number";

$order_by=$report->sort[0]["SQL"];
if (isset($SORT)) $order_by=$report->sort[$SORT]["SQL"];


//set criteria
$report->criteria=$criteria;
//$report->criteria["types"]["PL"]="text";
//$report->criteria["PL"]["prompt"]="P/L";
//$report->criteria["PL"]["value"]="{$PL}";
//$report->criteria["types"]["SUPC"]="text";
//$report->criteria["SUPC"]["prompt"]="UPCs Starting With";
//$report->criteria["SUPC"]["value"]="{$SUPC}";

//$report->criteria["types"]["noUPC"]="select";
//$report->criteria["noUPC"]["prompt"]="No UPC Parts Only";
//$report->criteria["noUPC"][1]["Desc"]="Yes";
//$report->criteria["noUPC"][1]["selected"]="";
//$report->criteria["noUPC"][0]["Desc"]="No";
//$report->criteria["noUPC"][1]["selected"]="";
//$report->criteria["noUPC"][$noUPC]["selected"]=" selected";

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
$where="";
if ($PL <> "") $where="where p_l like \"{$PL}%\"";
$extra="";
//if ($SUPC <> "") $extra="and alt_part_number like \"{$SUPC}%\"";
$report->SQL=<<<SQL
select
shadow_number as shadow,
upc as UPC,
" " as _,
p_l as P_L,
part_number as PartNumber,
part_desc as PartDesc,
cnt DupeCount,
abs(alt_type_code) as UPCQty,
IFNULL(whs_qty,0) as BinQty,
IFNULL(whs_location,"") as Bin

from DUPEALT A
JOIN ALTERNAT ON alt_part_number = upc and alt_type_code < 0
JOIN PARTS ON shadow_number = alt_shadow_num
LEFT JOIN WHSELOC ON whs_shadow = shadow_number and whs_company = {$comp}

{$where}
{$order_by}

SQL;

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
