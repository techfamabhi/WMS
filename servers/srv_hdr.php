<?php
//Header for data servers
if (get_cfg_var('wmsdir'))
    $wmsDir = get_cfg_var('wmsdir');
else {
    echo '{"errCode":500,"errText":"WMS System is not Configured on this System"}';
    exit;
}
$top = str_replace("/var/www", "", $wmsDir);


require_once("{$wmsDir}/include/quoteit.php");
require_once("{$wmsDir}/include/db_main.php");
require_once("{$wmsDir}/include/wr_log.php");

$inputdata = file_get_contents("php://input");
$reqdata = json_decode($inputdata, true);
$db = new WMS_DB;

if (isset($u)) {
    require_once("{$wmsDir}/include/cl_addupdel.php");
    $upd = new AddUpdDel;
    unset($u);
}

$rdata = array();
function setFldDef($db, $table)
{
    //Get Update table definition
    $u = $db->MetaData($table);
    $ret = array();
    foreach ($u as $key => $v) {
        $qote = 0;
        if (preg_match('(CHAR|DATE|TIME|TEXT)', strtoupper($v["Type"])) === 1) {
            $qote = 1;
        }
        $ret[$v["Field"]] = $qote;
    } // end foreach u
    return $ret;
} // end getFldDef

function setFlds($db, $Flds)
{
    //set update fields and types to find which fields need quotes
    $ret = "";
    $comma = "";
    foreach ($Flds as $f => $val) {
        if (strlen($ret) > 0)
            $comma = ",";
        $ret .= "{$comma}{$f}";
    }
    return $ret;
} // end setFlds
?>