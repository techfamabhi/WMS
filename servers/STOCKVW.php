<?php

// PARTHIST_srv.php -- Server for PARTHIST.php
//04/11/22 dse initial
// 06/22/22 dse change paud_bin to paud_floc and add paud_tloc


$update_table = "PARTHIST";
$query_table = "PARTHIST";
$DEBUG = true;
require("srv_hdr.php");
$db1 = new WMS_DB;

if (isset($_REQUEST["searcH"])) $srch = $_REQUEST["searcH"]; else $srch = "";
$comp = 1;
if ($srch <> "") $comp = intval($srch);
if ($DEBUG) wr_log("/tmp/STOCKVW.log", "inputData={$inputdata}");
$action = $reqdata["action"];
if (isset($reqdata["pl"])) $p_l = $reqdata["pl"]; else $p_l = "";
if (isset($reqdata["p_l"])) $p_l = $reqdata["p_l"]; else $p_l = "";
if (isset($reqdata["bin"])) $BIN = $reqdata["bin"]; else $BIN = "";
if (isset($reqdata["src"])) $src = $reqdata["src"]; else $src = "%";
if (isset($reqdata["comp"])) $comp = $reqdata["comp"]; else $comp = 1;
if (isset($reqdata["avail"])) $avail = $reqdata["avail"]; else $avail = "";
if (isset($reqdata["custnum"])) $custnum = $reqdata["custnum"]; else $custnum = "";
if (isset($reqdata["vendor"])) $vendor = $reqdata["vendor"]; else $vendor = "";
if (isset($reqdata["oper"])) $oper = $reqdata["oper"]; else $oper = "";
if (isset($reqdata["transId"])) $transId = $reqdata["transId"]; else $transId = "";
if (isset($reqdata["extref"])) $extref = $reqdata["extref"]; else $extref = "";
if (isset($reqdata["paudType"])) $paudType = $reqdata["paudType"]; else $paudType = "";
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

if ($DEBUG) wr_log("/tmp/STOCKVW.log", "Switching={$action}");
$rdata = array();
switch ($action) {
    case "fetchall":
    case "fetchAll":
    {
        $args = array();
        $PL = $p_l;
        $company = $comp;
        $where = "";
        $j = count($args);
        if ($PL <> "" and $PL <> "%") array_push($args, array("fld" => "p_l", "operand" => "=", "val" => $PL));
        if ($shadow_number <> "" and $shadow_number <> "NULL") array_push($args, array("fld" => "shadow_number", "operand" => "=", "val" => $shadow_number));
        if ($src <> "%") array_push($args, array("fld" => "paud_source", "operand" => "=", "val" => $src));
        if ($custnum <> "") array_push($args, array("fld" => "paud_source", "operand" => "=", "val" => $custnum));
        if ($vendor <> "") array_push($args, array("fld" => "paud_source", "operand" => "=", "val" => $vendor));
        if ($oper <> "") array_push($args, array("fld" => "paud_source", "operand" => "=", "val" => $oper));
        if ($transId <> "" and $transId <> "%") array_push($args, array("fld" => "paud_ref", "operand" => "=", "val" => $transId));
        if ($extref <> "" and $extref <> "%") array_push($args, array("fld" => "paud_ext_ref", "operand" => "=", "val" => $extref));
        if ($paudType <> "" and $paudType <> "%") array_push($args, array("fld" => "paud_type", "operand" => "=", "val" => $paudType));
        $op = "and";
        if (count($args)) {
            $j = 0;
            foreach ($args as $key => $a) {
                if ($j > 0) $op = "and";
                if ($a["fld"] == "shadow_number") $comma = ""; else $comma = '"';
                $where .= <<<SQL
{$op} {$a["fld"]} {$a["operand"]} {$comma}{$a["val"]}{$comma}

SQL;
                $j++;
            } // end foreach args
        } // end count($args)
        if ($BIN <> "" and $BIN <> "%") {
            $where .= <<<SQL
    {$op} (paud_floc like "{$BIN}%" or paud_tloc like "{$BIN}%")
SQL;
        }

        $SQL = <<<SQL
    select count(*) as cnt 
    from PARTS,PARTHIST
    where paud_shadow = shadow_number
    {$where}
    and paud_company = {$comp}
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

        $order_by = "order by paud_date desc,paud_type, p_l,part_seq_num,part_number";

//create a Cursor
        //order by p_l,part_seq_num,part_number
        $SQL = <<<SQL
 select shadow_number,p_l,part_number,part_desc,part_class,
  paud_id,
  paud_date,
  paud_source,
  paud_user,
  paud_ref,
  paud_ext_ref,
  paud_type,
  paud_qty,
  paud_uom,
  paud_floc,
  paud_tloc,
  paud_prev_qty,
  paud_inv_code,
  paud_qty_core,
  paud_qty_def
   
 from PARTS,PARTHIST
   where paud_shadow = shadow_number
 {$where}
   and paud_company = {$comp}
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
//$params=array(":PL" => "{$PL}");
        $params = array();
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
                    $rdata["rowData"][$rowsReturned]["userName"] = getUser($db1, $results["paud_user"]);
                    $rowsReturned++;
                    $numrows = $i;
                    if (($rowsReturned) >= ($numRows)) break;
                }
                $i++;
            }
            $rdata["rowThru"] = $rec_count;

            // $rdata["rows"]=$rowsReturned;
        } //SQL ok

        if (isset($x)) unset($x);
        $x = json_encode($rdata);
        if ($DEBUG) wr_log("/tmp/STOCKVW.log", "PL:{$PL} startRec={$startRec} numRows={$numRows} rowsReturned={$rowsReturned}");
        if ($DEBUG) wr_log("/tmp/STOCKVW.log", $SQL);
        if ($DEBUG) wr_log("/tmp/STOCKVW.log", $x);
        echo $x;
        break;
    } // end fetchs

    case "fetchSingle":
    {
        $company = 1;
        $SQL = <<<SQL
  select shadow_number,p_l,part_number,part_desc,part_class,qty_avail,primary_bin
 from PARTHIST,WHSEQTY
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
        if ($DEBUG) wr_log("/tmp/STOCKVW.log", "shadow:{$shadow_number}");
        if ($DEBUG) wr_log("/tmp/STOCKVW.log", $SQL);
        if ($DEBUG) wr_log("/tmp/STOCKVW.log", $x);
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

        if ($DEBUG) wr_log("/tmp/STOCKVW.log", "In Update Mode SQL=");
        if ($DEBUG) wr_log("/tmp/STOCKVW.log", $SQL);
        $currec = array();
        $rc = $db->query($SQL);
        $numrows = $db->num_rows();
        if ($DEBUG) wr_log("/tmp/STOCKVW.log", "{$numrows} records");
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
        if ($DEBUG) wr_log("/tmp/STOCKVW.log", "{$numrows} records");
        $j = count($currec);
        if ($DEBUG) wr_log("/tmp/STOCKVW.log", "found {$j} records");
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
                if ($DEBUG) wr_log("/tmp/STOCKVW.log", $SQL);
                $rc = $db->Update($SQL);
                $msg = "({$rc}) Records Saved";
                if ($DEBUG) wr_log("/tmp/STOCKVW.log", $msg);
                $rdata = '{"message":"' . $msg . '"}';
            } else {
                if ($DEBUG) wr_log("/tmp/STOCKVW.log", "No fields to Update");
                $rdata = '{"message":"No Changes, Record Not Updated!"}';
            }
            $x = $rdata;
            //header('Content-type: application/json');
            //$x=json_encode($rdata);
            if ($DEBUG) wr_log("/tmp/STOCKVW.log", $x);
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
        if ($DEBUG) wr_log("/tmp/STOCKVW.log", $SQL);
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
            if ($DEBUG) wr_log("/tmp/STOCKVW.log", $msg);
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
        if ($DEBUG) wr_log("/tmp/STOCKVW.log", "upd_flds={$upd_flds}");
        if ($DEBUG) wr_log("/tmp/STOCKVW.log", "updVals={$updVals}");
        $SQL = <<<SQL
 insert into {$update_table} ({$upd_flds})
 values ( {$updVals})

SQL;
        if ($DEBUG) wr_log("/tmp/STOCKVW.log", $SQL);
        $rc = $db->Update($SQL);
        $msg = "({$rc}) Records Added";
        if ($rc < 1) $msg = "An Error Accourred attempting to add the record!";
        if ($DEBUG) wr_log("/tmp/STOCKVW.log", $msg);
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
        if ($DEBUG) wr_log("/tmp/STOCKVW.log", $SQL);
        $rc = $db->Update($SQL);
        $msg = "({$rc}) Records Deleted";
        if ($rc < 1) $msg = "An Error Accourred attempting to Delete the record!";
        if ($DEBUG) wr_log("/tmp/STOCKVW.log", $msg);
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
} // end  getQTY

function getUser($db, $userId)
{
    $ret = "User not found";
    if ($userId < 1) return $ret;
    $SQL = <<<SQL
select username, first_name, last_name
from WEB_USERS
where user_id = {$userId}

SQL;
    $rc = $db->query($SQL);
    $numrows = $db->num_rows();
    $i = 1;
    while ($i <= $numrows) {
        $db->next_record();
        if ($numrows) {
            $uname = trim($db->f("username"));
            $first = trim($db->f("first_name"));
            $last = trim($db->f("last_name"));
        }
        $i++;
    } // while i < numrows
    if (isset($uname)) { // build display name (shortest as possible)
        //$ret="{$userId} {$first} " . substr($last,0,1);
        $ret = "{$first} " . substr($last, 0, 1);
    } // build display name (shortest as possible)
    return $ret;
} // end getUser
/*
PARTHIST Fields
paud_num 
paud_id 
paud_shadow 
paud_company 
paud_date           
paud_source 
paud_user 
paud_ref 
paud_ext_ref  
paud_type // PCK, RCV, PUT, ADJ, CNT, MOV
paud_qty 
paud_uom 
paud_floc  
paud_tloc  
paud_prev_qty 
paud_inv_code 
paud_price 
paud_core_price 
paud_qty_core 
paud_qty_def
*/

?>
