<?php

// 02/24/2023 dse use ENTIRY instead of CUSTOMER to pick up Vendors too

foreach (array_keys($_REQUEST) as $w) {
    $$w = $_REQUEST[$w];
}
error_reporting(0);

ignore_user_abort(true);


$title = "Customer/Vendor Search";
if (get_cfg_var('wmsdir')) $wmsDir = get_cfg_var('wmsdir');
else {
    echo "<h1>WMS System is not Configured on this System</h1>";
    exit;
}
$top = str_replace("/var/www", "", $wmsDir);

require_once("{$wmsDir}/include/db_main.php");
$db = new WMS_DB;;
//End Include Common Files

$s_b_sort = trim($_REQUEST["s_b_sort"]);
$s_b_sort = str_replace("_", " ", $s_b_sort);

$hdr = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta http-equiv="Content-Language" content="en-us">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<meta name="robots" content="noindex">
<meta name="viewport" content="width=device-width, initial-scale=1.5">

<link href="/jq/bootstrap.min.css" rel="stylesheet">
<link href="/wms/Themes/Multipads/Style.css?=time()" type="text/css" rel="stylesheet">
<link rel="stylesheet" href="/wms/assets/css/wms.css">
</head>
<body>
HTML;
if (!empty($s_b_sort)) {
    $HTML = <<<HTML
{$hdr}
<table border="0" width="100%" class="RPT table table-bordered table-striped overflow-auto">
  <tr>
    <th width="5%" class="FieldCaptionTD">Type</th>
    <th width="10%" class="FieldCaptionTD">Cust/Vend</th>
    <th width="20%" class="FieldCaptionTD">Name</th>
    <th width="20%" class="FieldCaptionTD">Address</th>
    <th width="20%" class="FieldCaptionTD">City</th>
    <th width="10%" class="FieldCaptionTD">Phone</th>
    <th width="10%" class="FieldCaptionTD">Last Order</th>
    <th width="5%" class="FieldCaptionTD">Ship Via</th>
  </tr>
HTML;

    $DHTML = "";
    $link = "stockvw.php";

    $bsort = strtoupper($s_b_sort) . "%";

    $SQL = <<<SQL
select
entity_type,
host_id as b_cust_number,
name as b_name,
addr1 as b_address,
addr2 as b_attn_line,
city as b_city,
state as b_state,
zip as b_zip,
ctry as b_ctry,
contact,
phone as b_phone,
email,
ship_via,
DATE_FORMAT(last_trans,"%m/%d/%y") as last_ord_date,
allow_bo

from ENTITY 
where name like "{$bsort}"
or host_id like "{$bsort}"
order by host_id
SQL;
    //$fp=fopen("/tmp/srv.log", "a");
    //fwrite($fp,"Sort=$s_b_sort\n");
    //fwrite($fp,"SQL=$SQL\n");
    //fclose($fp);

    $db->query($SQL);
    $rc = $db->next_record();
    if ($rc) {
        do {
            $custnum = $db->f("b_cust_number");
            if (isset($custnum)) {
                $etype = $db->f("entity_type");
                $name = $db->f("b_name");
                $addr = $db->f("b_address");
                $attn = $db->f("b_attn_line");
                if (trim($addr) == "") $addr = $attn;
                $c = $db->f("b_city");
                if (trim($c) <> "" and trim($s) <> "") $c .= ",";
                $s = $db->f("b_state");
                $z = $db->f("b_zip");
                $city = "{$c} {$s} {$z}";
                $phone = $db->f("b_phone");
                $shipVia = $db->f("ship_via");
                $last_ord_date = $db->f("last_ord_date");
                if ($last_ord_date == "00/00/00") $last_ord_date = "";
                $cust_d = $custnum;
                $href = "javascript:setesrc($custnum);";
                $DHTML .= <<<HTML
     <tr>
     <td width="5%" >{$etype}</td>
     <td width="10%" style="TEXT-ALIGN: right" >
     <a href="{$href}">{$cust_d}&nbsp;</a>
     </td>
     <td width="20%" >
     <a href="{$href}">{$name}</a>
     </td>
     <td width="20%" >{$addr}</td>
     <td width="20%" >{$city}</td>
     <td width="10%" >{$phone}</td>
     <td width="10%" >{$last_ord_date}</td>
     <td width="5%" >{$ship_via}</td>
     </tr>
HTML;
            }
            $rc = $db->next_record();
        } while ($rc);
    } // if $rc

//$HT=$HTML . $DHTML . "\n</table><body></html>";
    $HT = $HTML . $DHTML . "\n</table>";
}
echo $HT;
?>
