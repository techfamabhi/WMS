<?php
// picking.php - list open reelased orders and select 1 to pick
// 01/05/24 dse Don't allow sort on stat_desc or lines
/*
TODO
1) include header and STD routines
2) set zone
3) get open picks
4) allow selection of a pick
5) re-invoke pick.php so it can open a new tab with the selected pick
6) decide if each pick should have it's own tab, or have pick.php tell them next bin

future SQL to sort by type and prio, then filter out status and type and more
select
distinct
order_type,
priority,
zone,
user_id,
ord_num,
count(*)
 as num_lines
from ITEMPULL,ORDERS
where order_num = ord_num
group by order_type,priority, zone, ord_num, user_id

*/
foreach (array_keys($_REQUEST) as $w) { $$w=$_REQUEST[$w]; }
//error_reporting(0);
if (!isset($nh)) $nh=0;
if (!isset($orderType)) $orderType="%";
session_start();
$title="";
if (get_cfg_var('wmsdir')) $wmsDir=get_cfg_var('wmsdir');
else { echo "<h1>WMS System is not Configured on this System</h1>"; exit; }
$top=str_replace("/var/www","",$wmsDir);



$thisprogram=basename($_SERVER["PHP_SELF"]);
require("{$wmsDir}/config.php");
require_once("{$wmsInclude}/chklogin.php");
require_once("{$wmsInclude}/cl_Bluejay.php");
require_once("{$wmsInclude}/date_functions.php");
require_once("{$wmsInclude}/cl_modal.php");


require_once("../include/restSrv.php");

$Server="http://{$wmsIp}/{$wmsServer}/PICK_srv.php";
$UserId= $_SESSION["wms"]["UserID"];
if (isset($_SESSION["wms"]["wmsDefComp"])) $comp= $_SESSION["wms"]["wmsDefComp"];
else $comp=1;
if (isset($_SESSION["wms"]["zones"])) $zones= $_SESSION["wms"]["zones"];
else $zones=array();
$zones=array();

if (isset($_REQUEST["noSelect"])) $noSelect=$_REQUEST["noSelect"]; else $noSelect=0;
if (isset($_REQUEST["sorter"])) $sorter=$_REQUEST["sorter"]; else $sorter="";
if (isset($_REQUEST["sortDir"])) $sortDir=$_REQUEST["sortDir"]; else $sortDir="";

//create modal
$Modal=new cl_Modal;
$Modal->mwidth="78%";
$Modal->cwidth="88%";
$Modal->cheight="90%";
$Modal->init("myModal","modalFrame");

//Set array of fields as "fieldName"=>"Human Readable Heading"
$fields=array(
"wms_date"=>"Time",
"elapsed"=>"Elapsed",
"host_order_num"=>"Order Id",
"stat_desc"=>"Status",
"ship_via"=>"ShipVia",
"priority"=>"Prio",
"chili"=>"&nbsp;",
"zones"=>"Zones",
"lines"=>"Lines",
"order_type"=>"Type",
"cust_po_num"=>"PO Number",
"customer_id"=>"Customer",
"name"=>"Name"
 );
$colums=count($fields);
//Set up & down arrow images
$sortasc=<<<HTML
<img src="{$wmsImages}/sort_asc.png" width="16" height="16" border="0" title="Sort Ascending"/>
HTML;
$sortdesc=<<<HTML
<img src="{$wmsImages}/sort_desc.png" width="16" height="16" border="0" title="Sort Descending"/>
HTML;
$si=array();
foreach($fields as $fld=>$heading) { $si[$fld]="&nbsp;"; }

if ($sorter <> "")
{
 if ($sortDir == "") $sortDir="asc";
 $dir="sort{$sortDir}";
 $si[$sorter]=$$dir;
}


$i=0;
$stat="-1|3";
if ($orderType <> "%" and $orderType <> "O") $stat="{$orderType}|{$orderType}";
if ($orderType == "%") $stat="-1|3";
if ($orderType == "O") $stat="4|9";
if ($orderType == "3") $stat="3|3";
if ($orderType == "4") $stat="4|4";
if ($orderType == "P") $stat="1|2";

//get open orders
$f=array("action"=>"fetchall",
"numRows"=>9999,
"startRec"=>1,
"company"=>$comp,
"custname"=>"",
"statRange"=>$stat
);
if (isset($sorter) and trim($sorter) <> "")
{
 $f["sortby"]=array($sorter,$sortDir);
}
$myZones="";
if (!empty($zones))
{
 $comma="";
 $myZones=" Zones: ";
 if (count($zones) == 1) $myZones=" Zone:";
 $f["zones"]=$zones;
 foreach($zones as $z)
 {
  $myZones.="{$comma}{$z}";
  $comma=",";
 }
} // end zones not empty

//get open picks
$f1=array("action"=>"fetchOpenPicks",
"company"=>$comp,
"zones"=>$zones
);

$htm="";
$oty="";
$options=array(
"-1"=>"Awaiting Product",
"0"=>"Not Released",
"1"=>"Awaiting Pick",
"2"=>"Being Picked",
"3"=>"In Packing",
"4"=>"In Shipping",
"O"=>"Other");

if ($orderType == "%") $oty=" selected"; else $oty="";
$op_htm=<<<HTML
 <option value="%"{$oty}>All{$myZones}</option>

HTML;
$mTitle="";
foreach ($options as $key=>$o)
{
 if (strcmp($orderType,$key) == 0) $oty=" selected"; else $oty="";
 if ($orderType == "P" and ($key == 1 or $key == 2))
 {
  $oty=" selected";
  $mTitle="Awaiting or Being Picked";
 }
 if ($mTitle == "" and $oty <> "") $mTitle=$o;
 $op_htm.=<<<HTML
 <option value="{$key}"{$oty}>{$o}{$myZones}</option>

HTML;
} // end foreach option
if ($orderType == "%" and $mTitle == "") $mTitle="Open Orders";

$msg="";
$rc=restSrv($Server,$f);
$data=(json_decode($rc,true)); // dumps array
if (isset($data["rowData"])) $rowData=$data["rowData"];
else $rowData=array();
$js="";
 $needprod="";
 $notrel="";
 $open="";
 $inproc="";
 $packing="";
 $shipping="";
 $other="";
 $js=<<<HTML
<script>
function setSort(fld)
{
 var ele=document.getElementById('sorter');
 var sdir=document.getElementById('sortDir');
 var sortArrow=document.getElementById('si_' + fld);
 ele.value=fld;
 sortArrow.innerHTML="";
 if (sdir.value === 'asc')
  {
   sortArrow.innerHTML='{$sortdesc}';
   document.getElementById('sortDir').value="desc";
  }
 else
  {
   ele.value=fld;
   sortArrow.innerHTML='{$sortasc}';
   document.getElementById('sortDir').value="asc";
  }

 document.form1.submit();
}
</script>

HTML;

$queType=<<<HTML
    <div class="col-25">
     <label class="FormSubHeaderFont" for="orderType">Queue Type</label>
     <select name="orderType" id="orderType" onchange="document.form1.submit();">
   {$op_htm}
    </select>
    </div>

HTML;

if ($noSelect > 0) $queType="";

$formHTML=<<<HTML
  <div class="container">
   <div class="row">
 <form name="form1" action="{$thisprogram}" method="GET">
  <input type="hidden" name="sorter" id="sorter" value="{$sorter}">
  <input type="hidden" name="sortDir" id="sortDir" value="$sortDir">
  <input type="hidden" name="nh" id="nh" value="{$nh}">
  <input type="hidden" name="ret" id="ret" value="{$thisprogram}">
   <div style="text-align: center;"><span class="FormHeaderFont" align="center">_TITLE_</span></div>
    {$queType}
    <div class="col-25">
     <span class="FormSubHeaderFont">{$myZones}</span>
    </div>
    <div class="col-10">
   </div>
  </div>
  {$msg}
  <table width="100%" class="RPT table table-bordered table-striped overflow-auto">
   <thead>
    <tr>

HTML;
if (count($rowData))
{
 //Add Header row to table
 foreach($fields as $fld=>$heading)
 {
  if ($fld == "elapsed" or $fld == "chili" or $fld == "stat_desc" or $fld == "lines")
  $formHTML.=<<<HTML
         <th nowrap class="FieldCaptionTD">{$heading}</th>

HTML;
  else
  $formHTML.=<<<HTML
         <th onclick="setSort('{$fld}');" nowrap class="FieldCaptionTD">{$heading}<span id="si_{$fld}">{$si[$fld]}</span></th>

HTML;
 } // end foreach fields
$formHTML.=<<<HTML
        </tr>
       </thead>
_DETAIL_ROWS_
       </table>
      </form>

HTML;


$detail="";
  foreach($rowData as $rec=>$l)
  {
   $cls="";
   switch($l["order_stat"])
   {
     case "-1": // awaiting product
       	$w="needprod";
     case "0": // not released
 	$w="notrel";
	break;
     case "1": // awaiting Pick
 	$w="open";
        $cls=" class=\"Alt2DataTD\"";
	break;
     case "2": // being Picked
        $cls=" class=\"Alt4DataTD\"";
     	$w="inproc";
	break;
     case "3": // being packed
 	$w="packing";
        $cls=" class=\"Alt6DataTD\"";
	break;
     case "4": // being shipped
 	$w="shipping";
        $cls=" class=\"Alt7DataTD\"";
	break;
     default:  // done
 	$w="other";
	break;
   } // end switch order_stat
 $lnk="";
 $lnke="";
 //get users on an order
if (1 == 1)
{
 $f2=array("action"=>"fetchUsers",
  "order_num"=>$l["order_num"]);
 $rc=restSrv("http://localhost/wms/servers/PICK_srv.php",$f2);
 $d=(json_decode($rc,true)); // dumps array
 $addLink=true;
 if ($l["order_stat"] == "1") $addLink=true;
 if (count($d) > 0 and !$addLink)
 {
  foreach ($d as $pd)
  {
   if ($pd["user_id"] == $UserId and $l["order_stat"] < 3) $addLink=true;
  }
 } // we have d check 4 add link
 if ($addLink)
 { // awaiting pick, add href to pickit
  $lnk=<<<HTML
<a href="javascript:void(0);" onclick="pickit({$l["host_order_num"]});">
HTML;
  $lnke="</a>";
 //Redo link to popup modal
$lnk=<<<HTML
<span onclick="setframe('/wms/rf/orddtl.php?orderNum={$l["host_order_num"]}&nobtn=1');">

HTML;
  $lnke="</span>";
} // end 1 = 2

  // check if priority
            //<td>{$l["priority"]}</td>
  // end check if priority
 } // awaiting pick, add href to pickit
 $time=gDate($l["wms_date"]) . " " . gTime($l["wms_date"]);
 $elapsed=mdiff($l["wms_date"]);
 $e=mdiff($l["wms_date"],true);
 $chili="";
 if ($l["order_type"] == "O" and $l["order_stat"] < 5)
 {
 if ($l["priority"] < 4) $chili.=<<<HTML
<img border="0" src="{$wmsImages}/chilipepper.gif" width="12" height="8">
HTML;
 if ($l["priority"] < 3) $chili.=<<<HTML
<img border="0" src="{$wmsImages}/chilipepper.gif" width="18" height="10">
HTML;
 if ($l["priority"] < 2) $chili.=<<<HTML
<img border="0" src="{$wmsImages}/chilipepper.gif" width="23" height="16">
HTML;
if ($e > 1440 and $l["order_stat"] < 3) $chili.=<<<HTML
<img border="0" src="{$wmsImages}/dancingChile.gif" width="20" height="22">

HTML;
 } // end order_type = O
 
 $$w.=<<<HTML
           <tr>
            <td>{$time}</td>
            <td align="right">{$elapsed}</td>
            <td align="right">{$lnk}{$l["host_order_num"]}{$lnke}&nbsp;</td>
            <td{$cls}>{$l["stat_desc"]}</td>
            <td align="center">{$l["ship_via"]}</td>
            <td align="right">{$l["priority"]}&nbsp;</td>
            <td align="center">{$chili}</td>
            <td align="center">{$l["zones"]}</td>
            <td align="right">{$l["lines"]}</td>
            <td align="center">{$l["order_type"]}</td>
            <td>{$l["cust_po_num"]}</td>
            <td align="right">{$l["customer_id"]}&nbsp;</td>
            <td>{$l["name"]}</td>
           </tr>

HTML;
  } // end foreach rowData

if ($open == "") $h1="";
else $h1=<<<HTML
           <tr>
            <td colspan="{$colums}" class="FormSubHeaderFont">Awaiting Pick</td>
           </tr>

HTML;
if ($inproc == "") $h2="";
else $h2=<<<HTML
           <tr>
            <td colspan="{$colums}" class="FormSubHeaderFont">Being Picked</td>
           </tr>

HTML;
$h3=<<<HTML
           <tr>
            <td colspan="{$colums}" class="FormSubHeaderFont">In Packing</td>
           </tr>

HTML;
if ($packing == "") $h3="";
$h4=<<<HTML
           <tr>
            <td colspan="{$colums}" class="FormSubHeaderFont">In Shipping</td>
           </tr>

HTML;
if ($shipping == "") $h4="";
$h5=<<<HTML
           <tr>
            <td colspan="{$colums}" class="FormSubHeaderFont">Awaiting Product/Other</td>
           </tr>

HTML;
if ($other == "" and $notrel== "" and $needprod == "") $h5="";

$hiddens=<<<HTML
  <input type="hidden" name="nh" id="nh" value="{$nh}">
HTML;
} // end we have rowdata
else
{ // no rowData
  $msg=<<<HTML
<div class="row">
   <div class="col-75">
       <span style="word-wrap: normal; font-weight: bold; font-size: large; text-align: center;">No Records Found</span>
   </div>
  </div>

HTML;
 $h1="";
 $open="";
 $h2="";
 $inproc="";
 $h3="";
 $packing="";
 $h4="";
 $shipping="";
 $h5="";
 $needprod="";
 $notrel="";
 $other="";
}  // no rowData

$detail=<<<HTML
 {$h1}{$open}
 {$h2}{$inproc}
 {$h3}{$packing}
 {$h4}{$shipping}
 {$h5}{$needprod}
 {$notrel}
 {$other}

HTML;
 $body=str_replace("_DETAIL_ROWS_",$detail,$formHTML);

$htm.=<<<HTML
{$body}
</table>
<script>
function pickit(ordNum)
{
 if ( window !== window.parent )
 {
 parent.loadPage("pickOrder.php?nh={$nh}&fPQ=1&func=enterOrd&scaninput=" + ordNum);
 return(false);
 }
 else
 window.location.href="pickOrder.php?nh={$nh}&fPQ=1&func=enterOrd&scaninput=" + ordNum;
}
function changeZone()
{
 var url="selectZone.php";
 document.form1.action=url;
 document.form1.submit();
 //window.location.href=url;
}
</script>
</body>
</html>
HTML;

//<button class="zoneButton" onclick="changeZone();">Change Zone</button>
//Display Header
$pg=new BlueJay;
$pg->nh=$nh;
$pg->dispLogo=false;
if (isset($ord["host_order_num"])) $title="Order {$ord["host_order_num"]}";
else $title="Picking Queue";
if (isset($title)) $pg->title=$title;
if (isset($color)) $pg->color=$color; else $color="blue";
$pg->js=<<<HTML
<script>
function openalt(url,nlns) {
        hgt=210 + (nlns * 25);
        var popup=window.open(url,"popup", "toolbar=no,left=0,top=125,status=yes,resizable=yes,scrollbars=yes,width=600,height=" + hgt );
 return(false);
     }

function showItems(ordnum)
{
 var url="orddtl.php?orderNum=" + ordnum;
 openalt(url,10);
 return false;
}

</script>
<style>
.zoneButton {
  background-color: #4db8ff;
  border: none;
  color: white;
  padding: 2px;
  text-align: center;
  text-decoration: none;
  display: inline-block;
  font-size: 12px;
  font-weight: bold;
  margin: 0px 2px;
  border-radius: 10px;
}

</style>
{$js}
{$Modal->StyleSheet}
{$Modal->Modal}
{$Modal->javaScript}

HTML;
$ejs="";
if (isset($nh) and $nh > 0)
{
 $pg->noHeader=true;
}
$pg->Display();
$x="";
if (isset($mTitle)) $x=$mTitle;
$htm=str_replace("_TITLE_",$x,$htm);
echo $htm;
//echo "<pre>";
//print_r($Modal);

