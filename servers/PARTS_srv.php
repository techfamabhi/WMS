<?php

// PARTS_srv.php -- Server for PARTS.php
//12/10/21 dse initial
//02/09/22 dse move field defs to function in srv_hdr.php


$update_table = "PARTS";
$query_table = "PARTS";
$DEBUG = true;
require("srv_hdr.php");
$db1 = new WMS_DB;

if (isset($_REQUEST["searcH"])) $srch = $_REQUEST["searcH"]; else $srch = "";
$comp = 1;
if ($srch <> "") $comp = intval($srch);
if ($DEBUG) wr_log("/tmp/PARTS.log", "inputData={$inputdata}");
$action = $reqdata["action"];
if (isset($reqdata["p_l"])) $p_l = $reqdata["p_l"]; else $p_l = "";
if (isset($reqdata["comp"])) $comp = $reqdata["comp"]; else $comp = 1;
if (isset($reqdata["avail"])) $avail = $reqdata["avail"]; else $avail = "";
if (isset($reqdata["shadow_number"])) $shadow_number = $reqdata["shadow_number"]; else $shadow_number = 0;
if (isset($reqdata["numRows"])) $numRows = $reqdata["numRows"]; else $numRows = 10;
if (isset($reqdata["startRec"])) $startRec = $reqdata["startRec"]; else $startRec = 0;

// set table def and select and update fields
$uFlds = setFldDef($db, $update_table);
if ($query_table == $update_table) {
    $qFlds = $uFlds;
} else {
    $qFlds = setFldDef($db, $query_table);
}

$upd_flds = setFlds($db, $uFlds);
$sel_flds = setFlds($db, $qFlds);

if ($DEBUG) wr_log("/tmp/PARTS.log", "Switching={$action}");
$rdata = array();
switch ($action) {
    case "fetchall":
    case "fetchAll":
    {
        $PL = $p_l;
        $company = $comp;
        $where = "";
        if (trim($avail) <> "") {
            $op = "<>";
            if (intval($avail) < 0) $op = "<";
            if (intval($avail) > -1) $op = ">";
            $where = <<<SQL
 and qty_avail {$op} {$avail} 
SQL;
//or maximum > 0)
        } // avail <> ""

        $SQL = <<<SQL
    select count(*) as cnt from PARTS,WHSEQTY
    where p_l = "{$PL}"
    and ms_shadow = shadow_number
    and ms_company = {$comp}
    {$where}
SQL;

        $rc = $db->query($SQL);
        $numrows = $db->num_rows();
        $i = 1;
        while ($i <= $numrows) {
            $db->next_record();
            if ($numrows) {
                $rowCount = $db->f("cnt");
            }
            $i++;
        } // while i < numrows

        $order_by = "order by p_l,part_seq_num,part_number";

//create a Cursor
        //order by p_l,part_seq_num,part_number
        $SQL = <<<SQL
 select shadow_number,p_l,part_number,part_desc,part_class,
   ms_company as whse, qty_avail,maximum,primary_bin
 from PARTS,WHSEQTY
 where p_l = :PL
   and ms_shadow = shadow_number
   and ms_company = {$comp}
 {$where}
 {$order_by}

SQL;
        /*
         and ms_shadow = shadow_number
         and ms_company = 1
         and (qty_avail <> 0 or maximum > 0)
        */

        $rdata["rowFrom"] = 0;
        $rdata["rowThru"] = 0;
        $rdata["rowCount"] = $rowCount;
//Ony can get 1 param to bind on the create cursor function
        $params = array(":PL" => "{$PL}");
        if ($startRec == 0) $startRec = 1;

        $sth = $db->create_cursor($SQL, $params);
        if ($sth == true) { //SQL ok
            $numrows = 0;
            $rec_count = 0;
            $rowsReturned = 0;
            $i = 0;
            while ($results = $db->curfetch()) {
                $rec_count++;
                if ($rec_count >= $startRec) {
                    if ($rdata["rowFrom"] == 0) $rdata["rowFrom"] = $rec_count;
                    $rdata["rowData"][$rowsReturned] = $results;
                    $tmp = getQTY($db1, $results["shadow_number"], $company);
                    $rdata["rowData"][$rowsReturned]["qty_avail"] = $tmp["qty_avail"];
                    $rdata["rowData"][$rowsReturned]["primary_bin"] = $tmp["primary_bin"];
                    $rowsReturned++;
                    $numrows = $i;
                    if ($rowsReturned >= $numRows + 1) break;
                }
                $i++;
            }
            $rdata["rowThru"] = $rec_count;

            // $rdata["rows"]=$rowsReturned;
        } //SQL ok

        if (isset($x)) unset($x);
        $x = json_encode($rdata);
        if ($DEBUG) wr_log("/tmp/PARTS.log", "PL:{$PL} startRec={$startRec} numRows={$numRows} rowsReturned={$rowsReturned}");
        if ($DEBUG) wr_log("/tmp/PARTS.log", $SQL);
        if ($DEBUG) wr_log("/tmp/PARTS.log", $x);
        echo $x;
        break;
    } // end fetchs

    case "fetchSingle":
    {
        $company = 1;
        $SQL = <<<SQL
  select shadow_number,p_l,part_number,part_desc,part_class,qty_avail,primary_bin
 from PARTS,WHSEQTY
 where shadow_number = {$shadow_number}
 and ms_shadow = shadow_number
 and ms_company = {$comp}
SQL;

        $rc = $db->query($SQL);
        $numrows = $db->num_rows();
        $i = 1;
        while ($i <= $numrows) {
            $db->next_record();
            if ($numrows and $db->Record) {
                foreach ($db->Record as $key => $data) {
                    if (!is_numeric($key)) {
                        $rdata["$key"] = $data;
                    }
                }
            }
            $i++;
        } // while i < numrows
        if (isset($x)) unset($x);
        $x = json_encode($rdata);
        if ($DEBUG) wr_log("/tmp/PARTS.log", "shadow:{$shadow_number}");
        if ($DEBUG) wr_log("/tmp/PARTS.log", $SQL);
        if ($DEBUG) wr_log("/tmp/PARTS.log", $x);
        echo $x;
        break;
    } // end fetch Single
    case "update":
    {
        $where = <<<SQL
 where shadow_number = {$shadow_number}

SQL;


        $SQL = <<<SQL
select
{$upd_flds}
from {$update_table}
{$where}

SQL;

        if ($DEBUG) wr_log("/tmp/PARTS.log", "In Update Mode SQL=");
        if ($DEBUG) wr_log("/tmp/PARTS.log", $SQL);
        $currec = array();
        $rc = $db->query($SQL);
        $numrows = $db->num_rows();
        if ($DEBUG) wr_log("/tmp/PARTS.log", "{$numrows} records");
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
        if ($DEBUG) wr_log("/tmp/PARTS.log", "{$numrows} records");
        $j = count($currec);
        if ($DEBUG) wr_log("/tmp/PARTS.log", "found {$j} records");
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
                if ($DEBUG) wr_log("/tmp/PARTS.log", $SQL);
                $rc = $db->Update($SQL);
                $msg = "({$rc}) Records Saved";
                if ($DEBUG) wr_log("/tmp/PARTS.log", $msg);
                $rdata = '{"message":"' . $msg . '"}';
            } else {
                if ($DEBUG) wr_log("/tmp/PARTS.log", "No fields to Update");
                $rdata = '{"message":"No Changes, Record Not Updated!"}';
            }
            $x = $rdata;
            //header('Content-type: application/json');
            //$x=json_encode($rdata);
            if ($DEBUG) wr_log("/tmp/PARTS.log", $x);
            echo $x;
        } // got a record, update it if needed
        break;
    } // end update

    case "insert":
    {
        exit;
        $where = <<<SQL
 where shadow_number = {$shadow_number}

SQL;

        $SQL = <<<SQL
select count(*) as Cnt
from {$update_table}
{$where}

SQL;
        if ($DEBUG) wr_log("/tmp/PARTS.log", $SQL);
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
            $msg = "Ship Via {$via_code} is Already is use, Record not Added.";
            if ($DEBUG) wr_log("/tmp/PARTS.log", $msg);
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
        if ($DEBUG) wr_log("/tmp/PARTS.log", "upd_flds={$upd_flds}");
        if ($DEBUG) wr_log("/tmp/PARTS.log", "updVals={$updVals}");
        $SQL = <<<SQL
 insert into {$update_table} ({$upd_flds})
 values ( {$updVals})

SQL;
        if ($DEBUG) wr_log("/tmp/PARTS.log", $SQL);
        $rc = $db->Update($SQL);
        $msg = "({$rc}) Records Added";
        if ($rc < 1) $msg = "An Error Accourred attempting to add the record!";
        if ($DEBUG) wr_log("/tmp/PARTS.log", $msg);
        $rdata = '{"message":"' . $msg . '"}';
        echo $rdata;
        break;
    } // end insert
    case "delete":
    {
        exit;
        $where = <<<SQL
 where shadow_number = {$shadow_number}

SQL;

        $SQL = <<<SQL
 delete from {$update_table}
 {$where}

SQL;
        if ($DEBUG) wr_log("/tmp/PARTS.log", $SQL);
        $rc = $db->Update($SQL);
        $msg = "({$rc}) Records Deleted";
        if ($rc < 1) $msg = "An Error Accourred attempting to Delete the record!";
        if ($DEBUG) wr_log("/tmp/PARTS.log", $msg);
        $rdata = '{"message":"' . $msg . '"}';
        echo $rdata;
        break;
    } // end delete
} // end switch reqdata action

function getQTY($db1, $shadow, $comp)
{
    $SQL = <<<SQL
 select qty_avail,primary_bin
 from WHSEQTY
 where ms_shadow = {$shadow}
 and ms_company = {$comp}
SQL;

    $ret = array();
    $ret["qty_avail"] = 0;
    $ret["primary_bin"] = "";
    $rc = $db1->query($SQL);
    $numrows = $db1->num_rows();
    $i = 1;
    while ($i <= $numrows) {
        $db1->next_record();
        if ($numrows and $db1->Record) {
            foreach ($db1->Record as $key => $data) {
                if (!is_numeric($key)) {
                    $ret["$key"] = $data;
                }
            }
        }
        $i++;
    } // while i < numrows
    return ($ret);
}

?>
