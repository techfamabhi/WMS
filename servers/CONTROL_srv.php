<?php

// CONTROL_srv.php -- Server for CONTROL.php
//12/10/21 dse initial
//02/09/22 dse move field defs to function in srv_hdr.php
//11/10/22 dse add old_key to allow updating control key


$update_table = "CONTROL";
$query_table = "CONTROL";
$DEBUG = true;
require("srv_hdr.php");

$rdata = array();

if (isset($_REQUEST["searcH"])) $srch = $_REQUEST["searcH"]; else $srch = "";
$comp = 0;
if ($srch <> "") $comp = intval($srch);
if ($DEBUG) wr_log("/tmp/CONTROL.log", "Program=" . basename($_SERVER["SCRIPT_NAME"]));
if ($DEBUG) wr_log("/tmp/CONTROL.log", "inputData:\n{$inputdata}");
$action = $reqdata["action"];
if (isset($reqdata["control_company"])) $control_company = $reqdata["control_company"]; else $control_company = 0;
if (isset($reqdata["control_key"])) $control_key = $reqdata["control_key"]; else $control_key = "";
if (isset($reqdata["old_key"])) $old_key = $reqdata["old_key"]; else $old_key = "";

// set table def and select and update fields
$uFlds = setFldDef($db, $update_table);
if ($query_table == $update_table) {
    $qFlds = $uFlds;
} else {
    $qFlds = setFldDef($db, $query_table);
}

$upd_flds = setFlds($db, $uFlds);
$sel_flds = setFlds($db, $qFlds);

if ($DEBUG) wr_log("/tmp/CONTROL.log", "Function: {$action}");
switch ($action) {
    case "fetchall":
    case "fetchSingle":
    {
        $where = "";
        $order_by = "order by control_company,control_key";
        if (trim($control_key) <> "") $where = <<<SQL
where control_company = {$control_company} 
  and control_key = "{$control_key}"

SQL;

        $awhere = "";
        $SQL = <<<SQL
select
{$sel_flds}
from {$query_table}
{$where}
{$order_by}

SQL;
        //if ($DEBUG) wr_log("/tmp/CONTROL.log","SQL=" . $SQL);

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
        if (isset($x)) unset($x);
        $x = json_encode($rdata);
        //if ($DEBUG) wr_log("/tmp/CONTROL.log",$SQL);
        if ($DEBUG) wr_log("/tmp/CONTROL.log", "Response:\n{$x}");
        echo $x;
        break;
    } // end fetchs

    case "update":
    {
        $key = $control_key;
        if ($old_key <> "") $key = $old_key;
        $where = <<<SQL
where control_company = {$control_company}
  and control_key = "{$key}"

SQL;


        $SQL = <<<SQL
select
{$upd_flds}
from {$update_table}
{$where}

SQL;

        if ($DEBUG) wr_log("/tmp/CONTROL.log", "In Update Mode SQL=");
        if ($DEBUG) wr_log("/tmp/CONTROL.log", $SQL);
        $currec = array();
        $rc = $db->query($SQL);
        $numrows = $db->num_rows();
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
        if ($DEBUG) wr_log("/tmp/CONTROL.log", "{$numrows} records");
        $j = count($currec);
        if ($DEBUG) wr_log("/tmp/CONTROL.log", "found {$j} records");
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
                //if ($DEBUG) wr_log("/tmp/CONTROL.log",$SQL);
                $rc = $db->Update($SQL);
                $msg = "({$rc}) Records Saved";
                $rdata = '{"message":"' . $msg . '"}';
            } else {
                if ($DEBUG) wr_log("/tmp/CONTROL.log", "No fields to Update");
                $rdata = '{"message":"No Changes, Record Not Updated!"}';
            }
            $x = $rdata;
            //header('Content-type: application/json');
            //$x=json_encode($rdata);
            if ($DEBUG) wr_log("/tmp/CONTROL.log", $x);
            if ($DEBUG) wr_log("/tmp/CONTROL.log", "Response:\n{$x}");
            echo $x;
        } // got a record, update it if needed
        break;
    } // end update

    case "insert":
    {
        $where = <<<SQL
where control_company = {$control_company}
  and control_key = "{$control_key}"

SQL;

        $SQL = <<<SQL
select count(*) as Cnt
from {$update_table}
{$where}

SQL;
        //if ($DEBUG) wr_log("/tmp/CONTROL.log",$SQL);
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
            $msg = "Control Key {$control_key} is Already is use for Company {$control_company}, Record not Added.";
            $rdata = '{"message":"' . $msg . '"}';
            if ($DEBUG) wr_log("/tmp/CONTROL.log", "Response:\n{$rdata}");
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
        //if ($DEBUG) wr_log("/tmp/CONTROL.log","upd_flds={$upd_flds}");
        //if ($DEBUG) wr_log("/tmp/CONTROL.log","updVals={$updVals}");
        $SQL = <<<SQL
 insert into {$update_table} ({$upd_flds})
 values ( {$updVals})

SQL;
        //if ($DEBUG) wr_log("/tmp/CONTROL.log",$SQL);
        $rc = $db->Update($SQL);
        $msg = "({$rc}) Records Added";
        if ($rc < 1) $msg = "An Error Accourred attempting to add the record!";
        //if ($DEBUG) wr_log("/tmp/CONTROL.log",$msg);
        $rdata = '{"message":"' . $msg . '"}';
        if ($DEBUG) wr_log("/tmp/CONTROL.log", "Response:\n{$rdata}");
        echo $rdata;
        break;
    } // end insert
    case "delete":
    {
        $where = <<<SQL
where control_company = {$control_company}
  and control_key = "{$control_key}"

SQL;

        $SQL = <<<SQL
 delete from {$update_table}
 {$where}

SQL;
        //if ($DEBUG) wr_log("/tmp/CONTROL.log",$SQL);
        $rc = $db->Update($SQL);
        $msg = "({$rc}) Records Deleted";
        if ($rc < 1) $msg = "An Error Accourred attempting to Delete the record!";
        $rdata = '{"message":"' . $msg . '"}';
        if ($DEBUG) wr_log("/tmp/CONTROL.log", "Response:\n{$rdata}");
        echo $rdata;
        break;
    } // end delete
} // end switch reqdata action

?>
