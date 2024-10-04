<?php
//loadPWinv.php -- load PW inventory
/*
TODO
 add 
 filter out non ascii
 change bin to upper case
*/

if (get_cfg_var('wmsdir')) $wmsDir = get_cfg_var('wmsdir');
else {
    echo '{"errCode":500,"errText":"WMS System is not Configured on this System"}';
    exit;
}
$top = str_replace("/var/www", "", $wmsDir);


require_once("{$wmsDir}/config.php");
require_once("{$wmsDir}/include/quoteit.php");
require_once("{$wmsDir}/include/escQuotes.php");
require_once("{$wmsDir}/include/wr_log.php");
require_once("{$wmsDir}/include/db_main.php");
require_once("{$wmsInclude}/onlyascii.php");

$db = new WMS_DB;

foreach (array_keys($_REQUEST) as $w) {
    $$w = $_REQUEST[$w];
}


$baseDir = "/usr1/wms/Stage/";
$fname = "RWMS_INVENTORY_QOH_20230907.txt";
$fname = "XRWMS_INVENTORY_QOH_20230907.txt";
//$fname="qoh1.";

set_time_limit(0);
echo "<pre>";
$row = 1;
$rec = 1;
if (($handle = fopen("{$baseDir}/{$fname}", "r")) !== FALSE) {
    // setting field sep to ctrl-v (ascii 22)
    while (($rdata = fgetcsv($handle, 1000, "|")) !== FALSE) {
        $num = count($rdata);
        //if ($row > 4699 and $row < 4800) print_r($rdata);
        //if ($row < 11 ) print_r($rdata);
        if ($row > 10) exit;
        if ($row > 0)  // 1st row is headers in this file
        {
            //$data=explode("|",$rdata[0]);
            $data = $rdata;
            $bin = "";
            $grp = "";
            if (isset($data[10])) $bin = $data[10];
            $grp = $data[9];
            if (strlen($bin) > 14 and $grp <> "" and strpos($bin, $grp) !== false) $bin = $grp;
            $data["bin"] = strtoupper(onlyascii($bin));
            $pl = onlyascii($data[1]);
            $part = onlyascii($data[2]);
            $SQL = <<<SQL
select alt_shadow_num as shadow,
unit_of_measure as uom
from ALTERNAT, PARTS
where alt_part_number = "{$pl}{$part}"
and shadow_number = alt_shadow_num
SQL;


            $data["shadow"] = 0;
            $data["uom"] = "EA";
            $rc = $db->query($SQL);
            $numrows = $db->num_rows();
            $i = 1;
            while ($i <= $numrows) {
                $db->next_record();
                if ($numrows) {
                    $data["shadow"] = $db->f("shadow");
                    $data["uom"] = $db->f("uom");
                }
                $i++;
            } // while i < numrows
            print_r($data);
            if ($data["shadow"] > 0) { // got a shadow, add records
                $rc = addData($db, $data);
                //echo "{$rc}\n";
            } // got a shadow, add records
            if (($row < 50 or $row % 100) == 0) {
                echo "Record# {$row} {$data[1]} {$data[2]} $rc\n";
                ob_flush();
                flush();
                usleep(50000);

            }
        } // end row > 1

        $row++;

    } // end while data
} // end handle  <> false

function addData($db, $data)
{
    $ret = false;
    if ($data["shadow"] > 0) {
        $qavail = $data["4"];
        $min = $data["6"];
        $max = $data["7"];
        if (trim($qavail) == "") $qavail = 0;
        if (trim($min) == "") $min = 0;
        if (trim($max) == "") $max = 0;
        $SQL0 = <<<SQL
insert ignore into WHSEBINS
( wb_company, wb_location, wb_zone, wb_aisle, wb_section, wb_level, wb_subin,
wb_width, wb_depth, wb_height, wb_volume, wb_pick, wb_recv, wb_status)
values ({$data[0]}, "{$data["bin"]}", " ", " ", " ", " "," ",
0, 0, 0, 0.00, 1, 1, "T")

SQL;

        $SQL1 = <<<SQL
insert into WHSEQTY
( ms_shadow, ms_company, primary_bin, qty_avail, minimum, maximum)
values ({$data["shadow"]}, {$data["0"]}, " ", {$qavail}, {$min}, {$max} )
on duplicate key update qty_avail = qty_avail

SQL;
        $SQL2 = <<<SQL
insert into WHSELOC
( whs_company, whs_location, whs_shadow, whs_code, whs_qty, whs_uom, whs_alloc)
values ( {$data["0"]}, "{$data["bin"]}", {$data["shadow"]}, "O",{$qavail}, "{$data["uom"]}", 0)
on duplicate key update whs_qty = whs_qty

SQL;

        echo "{$SQL0}\n{$SQL1}\n{$SQL2}\n";
        //$rc0=$db->Update($SQL0);
        //$rc1=$db->Update($SQL1);
        //$rc2=$db->Update($SQL2);
        //$ret="{$rc0}|{$rc1}|{$rc2}";
    } // end shadow > 0
    return $ret;
} // end addData
