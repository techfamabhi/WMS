<?php

// WMS_srv.php -- Master Server for all servers
//07/21/22 dse initial


$DEBUG = true;
require("srv_hdr.php");
require_once("{$wmsDir}/include/restSrv.php");
$serverDir = "http://localhost/{$top}/servers";


if ($DEBUG) wr_log("/tmp/WMS_srv.log", "inputData={$inputdata}");
$action = $reqdata["action"];
if (isset($reqdata["serverName"])) $sname = $reqdata["serverName"];
else $sname = "";
$srv = getCall($db, $action, $sname);
$RESTSRV = "{$serverDir}/{$srv}";
if ($DEBUG) wr_log("/tmp/WMS_srv.log", "RESTSRV={$RESTSRV}");
$rdata = restSrv($RESTSRV, $reqdata);
echo $rdata;

function getCall($db, $action, $sname = "")
{
    $ret = "";
    if (trim($action) == "") return $ret;
    $ewhere = "";
    if (trim($sname) <> "") $ewhere = " and serverName = \"{$sname}\"";

    $SQL = <<<SQL
select
actionName,
serverName
from DATA_SERVERS
where actionName="{$action}"
{$ewhere}

SQL;
    $rc = $db->query($SQL);
    $numrows = $db->num_rows();
    $i = 1;
    while ($i <= $numrows) {
        $db->next_record();
        if ($numrows) {
            //$a=$db->f("actionName");
            $ret = $db->f("serverName");
        }
        $i++;
    } // while i < numrows
    return $ret;
} // end getAllCalls
