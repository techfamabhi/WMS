<?php
// getSlots.php -- Generate new Slots (totes,Cart id's,pack ids)
// 03/24/22 dse initial

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
require_once("{$wmsInclude}/get_contrl.php");
require_once("{$wmsInclude}/db_main.php");

$db = new WMS_DB;

if (!isset($num2Gen)) $num2Gen = 1;

echo "<pre>";
for ($i = 1; $i <= $num2Gen; $i++) {
    $slotNum = get_contrl($db, 0, "TOTES");
    $SQL = <<<SQL
insert into SLOTHDR ( slot_id, slot_status, slot_location, slot_lastused, num_items, slot_type, slot_ref )
values ( {$slotNum}, -1, " ", NOW(), 0, " ", 0 )

SQL;
    if ($i < 26 or ($i % 100) == 0) echo "{$i} => {$slotNum}\n";
    $rc = $db->Update($SQL);
} // end for i loop

?>
