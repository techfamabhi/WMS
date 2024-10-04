<?php

require_once("../include/db_main.php");

$db = new WMS_DB;

$func = "";
if (isset($argv[1])) $func = $argv[1];

if (get_cfg_var('wmsdir') !== false) $wmsDir = get_cfg_var('wmsdir');
else {
    echo "<h1>WMS System is not Configured on this System</h1>";
    exit;
}

if ($func == 0) { // display Inbound directory
    require_once("{$wmsDir}/config_comm.php");
    displayIt($inDir);
} // display Inbound directory

if ($func == 1) displayIt($wmsDir);
$top = str_replace("/var/www", "", $wmsDir);
if ($func == 2) displayIt($top);

$opt = 9001;
if ($func == 4) $opt = 9002;
if ($func == 5) $opt = 9004; // lock retry sleep time

$SQL = <<<SQL
select cop_flag
from COPTIONS
where cop_company = 0
and cop_option = {$opt}

SQL;
$slp = 10;
$rc = $db->query($SQL);
$numrows = $db->num_rows();
$i = 1;
while ($i <= $numrows) {
    $db->next_record();
    if ($numrows) {
        $slp = $db->f("cop_flag");
    }
    $i++;
} // while i < numrows
if ($func > 2) displayIt($slp);

function displayIt($in)
{
    echo $in;
    exit;
} // end displayIt
?>
