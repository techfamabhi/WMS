<?php
// putawayList.php - report of items in inventory
// 11/14/22 dse initial
// 07/09/24 dse add tote dtl if opt 27 is on

foreach (array_keys($_REQUEST) as $w) { $$w=$_REQUEST[$w]; }
error_reporting(0);

session_start();

if (get_cfg_var('wmsdir')) $wmsDir=get_cfg_var('wmsdir');
else { echo "<h1>WMS System is not Configured on this System</h1>"; exit; }
$top=str_replace("/var/www","",$wmsDir);

if (!isset($nh)) $nh=0;
if (isset($BP) and $BP=="All Putaway")
{
 $htm=<<<HTML
 <html>
 <head>
 <script>
window.location.href="putawayList1.php?nh={$nh}";
 </script>
 </head>
 <body>
 </body>
</html>

HTML;
echo $htm;
exit;

}

if (isset($_SESSION["wms"])) require($_SESSION["wms"]["wmsConfig"]);
else require("{$wmsDir}/config.php");

$thisprogram="putawayList.php";
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
isset($_REQUEST["TOTE"]) ? $TOTE=strtoupper($_REQUEST["TOTE"]) : $TOTE="";
isset($_REQUEST["comp"]) ? $comp=$_REQUEST["comp"] : $comp=1;

require_once("{$wmsInclude}/db_main.php");
require_once("{$wmsInclude}/cl_rpt.php");
require_once("{$wmsInclude}/get_option.php");

$db=new WMS_DB;
$opt[27]=get_option($db,$comp,27);


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
if (isset($nh) and $nh > 0) $report->noHeader=true; else $nh=0;
$B1="Run Report";

$x=<<<HTML
&nbsp;
&nbsp;
&nbsp;
&nbsp;
<button class="binbutton-tiny" onclick="window.location.href='putawayList1.php'" name="BP" value="All Putaway">All Putaway</button>
HTML;
$report->title="Received Parts to be Putaway {$x}";
// Custom Report Settings
// Fields [name]="align,mask,title,class (if not 0)"
$report->fields["Tote"]="L,";
$report->fields["PONum"]="L,";
$report->fields["POLine"]="R,";
$report->fields["Batch"]="R,";
$report->fields["MainBin"]="L,";
$report->fields["PL"]="L,";
$report->fields["PartNumber"]="L,";
$report->fields["PartDesc"]="L,";
$report->fields["PartUOM"]="L,";
$report->fields["shadow"]="H,";
$report->fields["MT"]="L,,Mdse Type";
$report->fields["Scanned"]="R,";
$report->fields["ExtQty"]="R,";
$report->fields["QtyOrd"]="R,";
$report->fields["Stocked"]="R,";

$prevPart="";

//for computed fields, use BeforeShowRecord else comment it out
function BeforeShowRecord($record,$fields)
{
//Calculate all the fields needed here
global $db;
global $comp;
global $prevPart;
global $report;

 // ditto certain fields when part has multiple bins unless exporting
 if ($report->pipe > 0 or $report->excel > 0) return $record;
 if (trim($record["MainBin"]) == "")
 {
  $SQL=<<<SQL
select
pl_perfered_zone,
pl_perfered_aisle
from PRODLINE
where pl_code = "{$record["PL"]}"
and pl_company = {$comp}
SQL;
  
  $rc=$db->query($SQL);
  $numrows=$db->num_rows();
  $i=1;
  while ($i <= $numrows)
  {
   $db->next_record();
     if ($numrows)
     {
        // $record["MainBin"]=$db->f("pl_perfered_zone") . " " .  sprintf("%02d",$db->f("pl_perfered_aisle"));
        $record["MainBin"]=$db->f("pl_perfered_zone") . " " .  $db->f("pl_perfered_aisle");
     }
     $i++;
   } // while i < numrows
 }

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
//$report->xtra["_REPORT"][0]="form,rform1,putawayList1.php";

//Set up Sort
$report->SORT=$SORT;
$report->sort[0]["SQL"]=<<<SQL
order by pack_id,po_number,po_line_num,batch_num

SQL;

$report->sort[1]["SQL"]=<<<SQL
order by primary_bin,pack_id,po_number,po_line_num,batch_num
SQL;
$report->sort[0]["Desc"]="Tote,PO, batch";
$report->sort[1]["Desc"]="Bin, Tote, PO, Batch";

$order_by=$report->sort[0]["SQL"];
if (isset($SORT)) $order_by=$report->sort[$SORT]["SQL"];


//set criteria
$report->criteria=$criteria;
$report->criteria["types"]["TOTE"]="text";
$report->criteria["TOTE"]["prompt"]="Tote";
$report->criteria["TOTE"]["value"]="{$TOTE}";
$report->criteria["types"]["PL"]="text";
$report->criteria["PL"]["prompt"]="P/L";
$report->criteria["PL"]["value"]="{$PL}";
$report->criteria["types"]["loc"]="utext";
$report->criteria["loc"]["prompt"]="Bin Location";
$report->criteria["loc"]["value"]="{$loc}";

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
$where="and p_l like \"{$PL}%\"";
$extra="";
if (trim($loc) <> "") $extra=" and primary_bin like \"{$loc}%\"";
if (trim($TOTE) <> "") $extra.=" and pack_id like \"{$TOTE}%\"";

$stocked="and qty_stockd < totalQty";
if ($opt[27] > 0)
{
$stocked=<<<SQL
and shadow in (select tote_shadow 
 from TOTEHDR TH, TOTEDTL TD
where tote_code = RCPT_SCAN.pack_id
and TD.tote_id = TH.tote_id
and tote_shadow = RCPT_SCAN.shadow )
SQL;
 
}

$report->SQL=<<<SQL
select pack_id as Tote,
host_po_num as PONum,
po_line_num  as POLine ,
batch_num  as Batch   ,
p_l as PL,
part_number as PartNumber,
part_desc as PartDesc,
primary_bin as MainBin,
shadow,
partUOM      ,
line_type as MT   ,
scanQty as Scanned,
totalOrd as ExtQty    ,
totalQty as QtyOrd     ,
qty_stockd as Stocked
from RCPT_SCAN, PARTS,WHSEQTY, POHEADER
where scan_status < 2
and recv_to <> "b"
{$stocked}
and shadow_number = shadow
and ms_shadow = shadow
and ms_company = {$comp}
and wms_po_num = po_number
{$where}
{$extra}
{$order_by}
SQL;
// End Custom Report Settings

//Run it

//Add any extra html required here if needed
$dhtm="";
$report->dhtm=$dhtm;

//if ($need_input_before_find and $loc == "" and $PL == "")
//{
  //unset($B1);
  //unset($report->B1);
//} // prompt user
//else
$report->B1="Run Report";
 $report->Run();
$x=<<<HTML
&nbsp;
&nbsp;
&nbsp;
&nbsp;
<button class="binbutton-tiny" onclick="window.location.href='putawayList1.php'" name="BP" value="All Putaway">All Putaway</button>
HTML;
echo $x;
?>
