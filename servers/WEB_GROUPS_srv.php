<?php

// WEB_GROUPS_srv.php -- Server for WEB_GROUPS.php
//12/09/21 dse initial
//02/09/22 dse move field defs to function in srv_hdr.php


$update_table = "WEB_GROUPS";
$query_table = "WEB_GROUPS";
$DEBUG = true;
require("srv_hdr.php");

if (isset($_REQUEST["searcH"])) $srch = $_REQUEST["searcH"]; else $srch = "";
$comp = 0;
if ($srch <> "") $comp = intval($srch);
if ($DEBUG) wr_log("/tmp/WEB_GROUPS.log", "inputData={$inputdata}");
$action = $reqdata["action"];
if (isset($reqdata["group_id"])) $group_id = $reqdata["group_id"]; else $group_id = 0;
if (isset($reqdata["orig_group"])) $orig_group = $reqdata["orig_group"]; else $orig_group = -1;

// set table def and select and update fields
$uFlds = setFldDef($db, $update_table);
if ($query_table == $update_table) {
    $qFlds = $uFlds;
} else {
    $qFlds = setFldDef($db, $query_table);
}

$upd_flds = setFlds($db, $uFlds);
$sel_flds = setFlds($db, $qFlds);

if ($DEBUG) wr_log("/tmp/WEB_GROUPS.log", "Switching={$action}");
switch ($action) {
    case "fetchall":
    case "fetchSingle":
    {
        $where = "";
        $order_by = "order by group_id";
        if ($group_id > -1) $where = "where group_id = {$group_id}\n";

        $awhere = "";
        $SQL = <<<SQL
select
{$sel_flds}
from {$query_table}
{$where}
{$order_by}

SQL;
        if ($DEBUG) wr_log("/tmp/WEB_GROUPS.log", "SQL=" . $SQL);

        $rc = $db->query($SQL);
        $numrows = $db->num_rows();
        $i = 0;
        while ($i <= $numrows) {
            $db->next_record();
            if ($numrows and $db->Record) {
                foreach ($db->Record as $key => $data) {
                    if ($action == "fetchSingle") {
                        $rdata[$key] = $data;
                    } else {
                        if (!is_numeric($key)) {
                            $rdata[$i]["$key"] = $data;
                        }
                    }
                }
            }
            $i++;
        } // while i < numrows
        $x = json_encode($rdata);
        if ($DEBUG) wr_log("/tmp/WEB_GROUPS.log", $SQL);
        if ($DEBUG) wr_log("/tmp/WEB_GROUPS.log", $x);
        echo $x;
        break;
    } // end fetchs
    case "update":
    {
        $where = "where group_id = {$group_id}\n";


        $SQL = <<<SQL
select
{$upd_flds}
from {$update_table}
{$where}

SQL;

        if ($DEBUG) wr_log("/tmp/WEB_GROUPS.log", "In Update Mode SQL=");
        if ($DEBUG) wr_log("/tmp/WEB_GROUPS.log", $SQL);
        $currec = array();
        $rc = $db->query($SQL);
        $numrows = $db->num_rows();
        if ($DEBUG) wr_log("/tmp/WEB_GROUPS.log", "{$numrows} records");
        $i = 0;
        while ($i <= $numrows) {
            $db->next_record();
            if ($numrows and $db->Record) {
                foreach ($db->Record as $key => $data) {
                    if (!is_numeric($key)) {
                        $currec["$key"] = $data;
                    }
                }
            }
            $i++;
        } // while i < numrows
        if ($DEBUG) wr_log("/tmp/WEB_GROUPS.log", "{$numrows} records");
        $j = count($currec);
        if ($DEBUG) wr_log("/tmp/WEB_GROUPS.log", "found {$j} records");
        if ($j > 0) { // got a record, update it if needed
            $SQL = <<<SQL
update {$update_table} set
SQL;
            $flds = array();
            $found_diff = 0;
            foreach ($currec as $f => $val) {
                if (isset($reqdata[$f]) and $val <> $reqdata[$f]) {
                    $val = trim($val);
                    $comma = "";
                    if ($found_diff > 0) $comma = ",";
                    $found_diff++;
                    $q = "";
                    if ($uFlds[$f] > 0) {
                        $q = '"';
                        $reqdata[$f] = str_replace("'", "", $reqdata[$f]);
                        $reqdata[$f] = str_replace('"', "", $reqdata[$f]);
                    }
                    $SQL .= "{$comma} {$f} = {$q}{$reqdata[$f]}{$q}";
                }
            } // end foreach currec
            $SQL .= "\n{$where}";

            if ($found_diff > 0) {
                if ($DEBUG) wr_log("/tmp/WEB_GROUPS.log", $SQL);
                $rc = $db->Update($SQL);
                $msg = "({$rc}) Records Saved";
                if ($DEBUG) wr_log("/tmp/WEB_GROUPS.log", $msg);
                $rdata = '{"message":"' . $msg . '"}';
            } else {
                if ($DEBUG) wr_log("/tmp/WEB_GROUPS.log", "No fields to Update");
                $rdata = '{"message":"No Changes, Record Not Updated!"}';

            }
            $x = $rdata;
//header('Content-type: application/json');
//$x=json_encode($rdata);
            if ($DEBUG) wr_log("/tmp/WEB_GROUPS.log", $x);
            echo $x;
        } // got a record, update it if needed


        break;
    } // end update
    case "insert":
    {
        $SQL = <<<SQL
select count(*) as Cnt
from {$update_table}
where group_id = "{$group_id}"

SQL;
        if ($DEBUG) wr_log("/tmp/WEB_GROUPS.log", $SQL);
        $Cnt = 0;
        $rc = $db->query($SQL);
        $numrows = $db->num_rows();
        $i = 1;
        while ($i <= $numrows) {
            $db->next_record();
            if ($numrows) {
                $Cnt = $db->f("Cnt");
            }
            $i++;
        } // while i < numrows
        if ($Cnt > 0) {
            $msg = "Group # {$group_id} is Already is use, Record not Added.";
            if ($DEBUG) wr_log("/tmp/WEB_GROUPS.log", $msg);
            $rdata = '{"message":"' . $msg . '"}';
            echo $rdata;
            break;
        } // end if username or password already exists

        $updVals = "";
        $comma = "";
        foreach ($uFlds as $key => $v) {
            if (strlen($updVals) > 0) $comma = ",";
            if (isset($reqdata[$key])) $w = $reqdata[$key]; else $w = "";
            if ($key == "user_id") $w = "NULL";
            else {
                if ($v > 0) { // quote it
                    //need to properly escape embedded quotes at some point instead of
                    //removing them
                    $w = str_replace("'", "", $w);
                    $w = str_replace('"', "", $w);
                    $w = quoteit($w);
                } // quote it
                else if ($w == "") $w = 0;
            } // fld is not user id
            $val = $w;
            $updVals .= "{$comma}{$val}";
        } // end foreach uFlds
        if ($DEBUG) wr_log("/tmp/WEB_GROUPS.log", "upd_flds={$upd_flds}");
        if ($DEBUG) wr_log("/tmp/WEB_GROUPS.log", "updVals={$updVals}");
        $SQL = <<<SQL
 insert into {$update_table} ({$upd_flds})
 values ( {$updVals})

SQL;
        if ($DEBUG) wr_log("/tmp/WEB_GROUPS.log", $SQL);
        $rc = $db->Update($SQL);
        $msg = "({$rc}) Records Added";
        if ($rc < 1) $msg = "An Error Accourred attempting to add the record!";
        if ($DEBUG) wr_log("/tmp/WEB_GROUPS.log", $msg);
        $rdata = '{"message":"' . $msg . '"}';
        echo $rdata;
        break;
    } // end insert
    case "delete":
    {
        if (1 == 2) { // rem out, delete not allowed
            $SQL = <<<SQL
 delete from {$update_table}
 where group_id = {$group_id}

SQL;
            if ($DEBUG) wr_log("/tmp/WEB_GROUPS.log", $SQL);
            $rc = $db->Update($SQL);
            $msg = "({$rc}) Records Deleted";
            if ($rc < 1) $msg = "An Error Accourred attempting to Delete the record!";
        } // rem out, delete not allowed
        $msg = "Deleting a Company Record is not allowed";
        if ($DEBUG) wr_log("/tmp/WEB_GROUPS.log", $msg);
        $rdata = '{"message":"' . $msg . '"}';
        echo $rdata;
        break;
    } // end delete

} // end switch reqdata action

?>
