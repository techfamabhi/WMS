<?php
/*
 check_part.php for stockvw
06/04/24 dse correct choose part and customer search display

*/
foreach (array_keys($_REQUEST) as $w) {
    $$w = $_REQUEST[$w];
}
//error_reporting(0);
if (get_cfg_var('wmsdir')) $wmsDir = get_cfg_var('wmsdir');
else {
    echo "<h1>WMS System is not Configured on this System</h1>";
    exit;
}
$top = str_replace("/var/www", "", $wmsDir);

require_once("{$wmsDir}/include/db_main.php");
require_once("{$wmsDir}/include/cl_PARTS2.php");
$db = new WMS_DB;

$th = "class=\"FormHeaderFont\" ";
$tc = "class=\"FieldCaptionTD\" ";
$td = "class=\"DataTD\" ";
$td = "";
$ta = "class=\"AltDataTD\" ";

$msg = "";

if (trim($pnum) <> "" and strtoupper($pnum) <> "DONE") {
//echo "Last Part#=$pnum";
    $part = get_part($db, trim(strtoupper($pnum)));
    $j = $part["num_rows"];
//echo " Parts found: $j\n";
    $bgsound = "";
    if ($part["status"] == -35) {
        echo "$pnum Not Found!\n";
        echo "</pre><script> alert(\"Part: $pnum Not Found!\");</script>";
        $j = 1;
//print_r($part);
        $part[1]["alt_part_number"] = $pnum;
        $part[1]["p_l"] = "???";
        $part[1]["part_number"] = $pnum;
        $part[1]["part_seq_num"] = 0;
        $part[1]["part_desc"] = "Not Found!";
        $part[1]["shadow_number"] = 0;
        $pnum = "";
        $bgsound = <<<HTML
<embed src="shrub.wav" width="180" height="90" loop="false" autostart="true" hidden="true" />
HTML;
        $bgsound = "";
    } // end of -35
//else
//{ // part found
//echo "<pre>";
//print_r($part);
//} // part found

    $i = 1;
//echo "Parts found: $j\n";
//print_r($part);
    if ($pnum == "") {
        $j = 1;
    }
    if ($j > 1) {
//<embed src="shrub2.wav" width="180" height="90" loop="false" autostart="true" hidden="true" />
//<form method="post" name="form2" action="{$base_prog}">
//<table width="40%" class="table-bordered table-striped">
        $padding = " style=\"padding: 15px\"";
        $htm = <<<HTML
<style>
 td { height: 30px;
      padding-left 15px;
    }
</style>
<input type="hidden" name="form_name" value="form2">
<H1 {$th}>Please Choose Line Code!</H1>
<table border="0" width="40%" style="padding: 10px" class="table-bordered table-striped overflow-auto">
<tr {$padding}>
<td {$tc} align="center">Select</th>
<td {$tc}>P/L</th>
<td nowrap {$tc}>Part#</th>
<td nowrap {$tc}>Description</th>
</tr>
HTML;

        while ($i <= $j) {
            $p_l = $part[$i]["p_l"];
            $pn = $part[$i]["part_number"];
            $pdesc = $part[$i]["part_desc"];
            $upc = $part[$i]["alt_part_number"];
            $shadow = $part[$i]["shadow_number"];
            $htm .= <<<HTML
  <tr {$padding}>
  <td {$td} align="center"><input type="checkbox" name="pnum" value="{$shadow}" onchange="setshadow({$shadow},'{$p_l}','{$pn}');"></td>
  <td {$td}>{$p_l}</td>
  <td {$td}>{$pn}</td>
  <td {$td}>{$pdesc}</td>
</tr>

HTML;
            $i++;
        }
        $htm .= <<<HTML
</table>
<script>
//alert("Multple Parts Found: {$pnum} Please Choose!");
</script>

HTML;
//</form>
        echo $htm;

    } // if j > 1
//print_r($part);
    if ($j == 1) {
        $shadow = $part[1]["shadow_number"];
        $p_l = $part[$i]["p_l"];
        $pn = $part[$i]["part_number"];
        $htm = <<<HTML
<script>
setshadow({$shadow},"{$p_l}","{$pn}");
</script>
HTML;
        echo $htm;
    } // end if j=1
} // end if $pnum

function get_part($db, $pnum)
{
    $PM = new PARTS;
    $part = array();
    $part["partno"] = $pnum;
    $a = $PM->lookup(trim(strtoupper($pnum)));
    $j = count($a);
    $part["num_rows"] = $j;
    if ($j < 1) $part["status"] = -35; else $part["status"] = 0;
    if ($j > 0) {
        foreach ($a as $key => $data) {
            $part[$key] = $data;
        } // end foreach a
        if ($j == 1 and isset($a[1])) $part["partno"] = $a[1]["p_l"] . $a[1]["part_number"];
    } // end j > 0
    return ($part);
} // end get_part
function xget_part($db, $pnum_in)
{
    $ret = array();
    $ret["status"] = 0;
    $ret["num_rows"] = 0;
    $i = 0;
    $qstring = <<<SQL
SELECT alt_part_number,alt_type_code, part_desc, p_l,
 part_number, unit_of_measure, shadow_number, num_supercedes,
 num_interchanges, ord_hdr_bucket, part_seq_num, part_category,
 part_long_desc, part_class, part_weight,
 convert(char(10),sale_date_from,101) as sale_on_date,
 convert(char(10),sale_date_thru, 101)as sale_off_date,
 sale_price_code, part_returnable, qty_per_car,
 broken_pack_chrg, restocking_fee, part_kit_type, part_min_gp,
 part_cf_flag, qty_break_flag, price00,price06
 FROM ALTERNAT,PARTS
 WHERE alt_part_number like "{$pnum_in}"
 AND  shadow_number = alt_shadow_num
SQL;

    $rc = $db->query($qstring);
    $numrows = $db->num_rows();
    $ret["num_rows"] = $numrows;
    $i = 1;
    while ($i <= $numrows) {
        $db->next_record();
        $ret[$i]["alt_part_number"] = $db->f("alt_part_number");
        $ret[$i]["alt_type_code"] = $db->f("alt_type_code");
        $ret[$i]["part_desc"] = $db->f("part_desc");
        $ret[$i]["p_l"] = $db->f("p_l");
        $ret[$i]["part_number"] = $db->f("part_number");
        $ret[$i]["unit_of_measure"] = $db->f("unit_of_measure");
        $ret[$i]["shadow"] = $db->f("shadow_number");
        $ret[$i]["shadow_number"] = $db->f("shadow_number");
        $ret[$i]["num_supercedes"] = $db->f("num_supercedes");
        $ret[$i]["num_interchanges"] = $db->f("num_interchanges");
        $ret[$i]["ord_hdr_bucket"] = $db->f("ord_hdr_bucket");
        $ret[$i]["part_seq_num"] = $db->f("part_seq_num");
        $ret[$i]["part_category"] = $db->f("part_category");
        $ret[$i]["part_long_desc"] = $db->f("part_long_desc");
        $ret[$i]["part_class"] = $db->f("part_class");
        $ret[$i]["part_weight"] = $db->f("part_weight");
        $ret[$i]["sale_on_date"] = $db->f("sale_on_date");
        $ret[$i]["sale_off_date"] = $db->f("sale_off_date");
        $ret[$i]["sale_price_code"] = $db->f("sale_price_code");
        $ret[$i]["part_returnable"] = $db->f("part_returnable");
        $ret[$i]["qty_per_car"] = $db->f("qty_per_car");
        $ret[$i]["broken_pack_chrg"] = $db->f("broken_pack_chrg");
        $ret[$i]["restocking_fee"] = $db->f("restocking_fee");
        $ret[$i]["part_kit_type"] = $db->f("part_kit_type");
        $ret[$i]["part_min_gp"] = $db->f("part_min_gp");
        $ret[$i]["part_cf_flag"] = $db->f("part_cf_flag");
        $ret[$i]["qty_break_flag"] = $db->f("qty_break_flag");
        $ret[$i]["price00"] = $db->f("price00");
        $ret[$i]["price06"] = $db->f("price06");
        $i++;
    }
    if ($ret["num_rows"] == 0) {
        $ret["status"] = -35;
    }
    return ($ret);
} // end xget_part

?>
