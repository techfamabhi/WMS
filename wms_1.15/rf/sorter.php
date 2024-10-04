<?php
//Sorter routines
//get wmsDir  and get config.php

//Use cl_Bluejay or cl_rf to insure correct stylesheet is included

//temp to get this sample to work
require_once("../include/db_main.php");
$db=new WMS_DB;
$wmsImages="../images";
$thisprogram="/wms/rf/sorter.php";
echo <<<HTML
<link href="/jq/bootstrap.min.css" rel="stylesheet">
<link href="/wms/Themes/Multipads/Style.css?=time()" type="text/css" rel="stylesheet">

HTML;
// end temp to get this sample to work

//add these to top of program

if (isset($_REQUEST["sorter"])) $sorter=$_REQUEST["sorter"]; else $sorter="";
if (isset($_REQUEST["sortDir"])) $sortDir=$_REQUEST["sortDir"]; else $sortDir="";
//Setup section

//Set array of fields as "fieldName"=>"Human Readable Heading"
$fields=array(
"host_po_num"=>"PO#",
"p_l"=>"P/L",
"part_number"=>"Part Number",
"part_desc"=>"Description",
"partUOM"=>"UOM",
"totalQty"=>"Total Recvd",
"pack_id"=>"Pack Id"
 );

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



$formHTML=<<<HTML
 <form name="form1" action="{$thisprogram}" method="GET">
      <input type="hidden" name="sorter" id="sorter" value="{$sorter}">
      <input type="hidden" name="sortDir" id="sortDir" value="$sortDir">
      <table class="table table-bordered table-striped overflow-auto">
       <thead>
        <tr>

HTML;
 //Add Header row to table
 foreach($fields as $fld=>$heading) 
 { 
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

//set your other html vars here
$detail="<tr><td>...</td></tr>";

//Heres a sample
 $rcpt=108;
 $receipt=get_rcpt($db,$rcpt,$sorter,$sortDir);
 $detail="";
  if (count($receipt) > 0)
 {
  foreach($receipt as $line=>$item)
  {
   $detail.=<<<HTML
        <tr onclick="editItem({$item["batch_num"]},{$item["line_num"]});">
         <td align="right">{$item["host_po_num"]}</td>
         <td>{$item["p_l"]}</td>
         <td>{$item["part_number"]}</td>
         <td>{$item["part_desc"] }</td>
         <td>{$item["partUOM"] }</td>
         <td align="right">{$item["totalQty"]}</td>
         <td nowrap>{$item["pack_id"]}</td>
        </tr>
HTML;
  } // end foreach receipt
 } // count receipt > 0


//Put it all together
$body=str_replace("_DETAIL_ROWS_",$detail,$formHTML);

$htm=<<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta http-equiv="Content-Language" content="en-us">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.5">

<link href="/jq/bootstrap.min.css" rel="stylesheet">
<link href="/wms/Themes/Multipads/Style.css?=time()" type="text/css" rel="stylesheet">

{$js}
</head>
<body>
{$body}
</body>
</html>

HTML;

echo $htm;
//echo "<pre> si ";
//print_r($si);

function get_rcpt($db,$rcpt,$sort,$sortDir)
{
 $ret=array();
 $ret["numRows"]=0;
 $orderby="";
 if ($sortDir == "desc")
  {
   $sort.=" desc";
  }
 if (trim($sort) <> "") $orderby="order by {$sort},line_num";
 $SQL=<<<SQL
 select 
RCPT_SCAN.batch_num, 
host_po_num,
 po_number,
line_num,
p_l,
part_number,
part_desc,
 pkgUOM,
 scan_upc,
 part_desc,
 po_line_num,
 scan_status,
 scan_user,
 pack_id,
 shadow,
 partUOM,
 line_type,
 pkgQty,
 scanQty,
 totalQty,
 timesScanned,
 recv_to
from RCPT_INWORK,RCPT_SCAN, PARTS, POHEADER
where RCPT_INWORK.batch_num = {$rcpt}
and  RCPT_INWORK.batch_num = RCPT_SCAN.batch_num
and scan_status < 1
and shadow_number = shadow
and POHEADER.wms_po_num = po_number
{$orderby}

SQL;

$rc=$db->query($SQL);
  $numrows=$db->num_rows();
  $i=1;
  while ($i <= $numrows)
  {
   $db->next_record();
     if ($numrows and $db->Record)
     {
      foreach ($db->Record as $key=>$data)
       {
        if (!is_numeric($key)) { $ret[$i]["$key"]=$data; }
       }
     }
    $i++;
  } // while i < numrows
 $ret["numRows"]=$numrows;
 return($ret);
} // end get_rcpt
?>
