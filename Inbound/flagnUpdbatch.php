<?php
// flagnUpdbatch.php - Flag a bacth done and update and send to host
// 11/15/22 dse initial

foreach (array_keys($_REQUEST) as $w) {
    $$w = $_REQUEST[$w];
}
error_reporting(0);

session_start();

if (get_cfg_var('wmsdir')) $wmsDir = get_cfg_var('wmsdir');
else {
    echo "<h1>WMS System is not Configured on this System</h1>";
    exit;
}
$top = str_replace("/var/www", "", $wmsDir);

if (isset($_SESSION["wms"])) require($_SESSION["wms"]["wmsConfig"]);
else require("{$wmsDir}/config.php");

$thisprogram = "flagnUpdbatch.php";
if (!isset($wmsInclude)) $wmsInclude = "{$wmsDir}/include";

//Search Criteria and passed variables
isset($_REQUEST["comp"]) ? $comp = $_REQUEST["comp"] : $comp = 1;
isset($_REQUEST["batch"]) ? $batch = $_REQUEST["batch"] : $batch = 0;

require_once("../include/restSrv.php");
$RESTSRV = "http://{$wmsIp}{$wmsServer}/WMS2ERP.php";


if ($batch > 0) {
    $req = array("action" => "flagRcptDone",
        "batch_num" => $batch,
        "comp" => $comp
    );
    $ret = restSrv($RESTSRV, $req);
    $rdata = (json_decode($ret, true));
    echo "<pre>";
    //print_r($rdata);
    // if flaging is ok, receive the batch
    if (!isset($rdata["error"])) {
        $req = array("action" => "Receive",
            "batch_num" => $batch,
            "comp" => $comp
        );
        $ret = restSrv($RESTSRV, $req);
        $rc = (json_decode($ret, true));
        if ($rc > 0) echo "Batch Released Successfully";
    } // end no error
    else {
        echo $rdata["message"];
    } // an error occurred

} // batch > 0
else {
    echo "Batch number is not set";
}

?>
