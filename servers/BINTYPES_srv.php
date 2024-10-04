<?php

// BINTYPES_srv.php -- Server for BINTYPES.php
//12/10/21 dse initial
//02/09/22 dse move field defs to function in srv_hdr.php


$update_table = "BINTYPES";
$query_table = "BINTYPES";

$DEBUG = true;
$u = true;
require("srv_hdr.php");

if (isset($_REQUEST["searcH"])) $srch = $_REQUEST["searcH"]; else $srch = "";
$comp = 0;
if ($srch <> "") $comp = intval($srch);
if ($DEBUG) wr_log("/tmp/BIN_TYPES.log", "Program={$_SERVER["PHP_SELF"]}");
if ($DEBUG) wr_log("/tmp/BIN_TYPES.log", "inputData:\n{$inputdata}");
$action = $reqdata["action"];
if (isset($reqdata["typ_company"])) $typ_company = $reqdata["typ_company"]; else $typ_company = 0;
if (isset($reqdata["typ_code"])) $typ_code = $reqdata["typ_code"]; else $typ_code = "";

// set table def and select and update fields
$uFlds = setFldDef($db, $update_table);
if ($query_table == $update_table) {
    $qFlds = $uFlds;
} else {
    $qFlds = setFldDef($db, $query_table);
}

$upd_flds = setFlds($db, $uFlds);
$sel_flds = setFlds($db, $qFlds);


if ($DEBUG) wr_log("/tmp/BIN_TYPES.log", "Function: {$action}");
switch ($action) {
    case "fetchall":
    case "fetchSingle":
    {
        $where = "";
        $order_by = "order by typ_company,typ_code";
        if ($typ_code <> "") $where = <<<SQL
where typ_company = {$typ_company} 
  and typ_code = "{$typ_code}"

SQL;

        $SQL = <<<SQL
select
{$sel_flds}
from {$query_table}
{$where}
{$order_by}

SQL;
        //if ($DEBUG) wr_log("/tmp/BIN_TYPES.log","SQL=" . $SQL);

        $rc = $db->query($SQL);
        $numrows = $db->num_rows();
        $i = 0;
        while ($i <= $numrows) {
            $db->next_record();
            if ($numrows and $db->Record) {
                foreach ($db->Record as $key => $data) {
                    if (!is_numeric($key)) {
                        if ($action == "fetchSingle") {
                            $rdata[$key] = $data;
                        } else {
                            $rdata[$i]["$key"] = $data;
                        }
                    } // key is not numeric
                }
            }
            $i++;
        } // while i < numrows
        if (isset($x)) unset($x);
        $x = json_encode($rdata);
        //if ($DEBUG) wr_log("/tmp/BIN_TYPES.log",$SQL);
        if ($DEBUG) wr_log("/tmp/BIN_TYPES.log", "Response:\n{$x}");
        echo $x;
        break;
    } // end fetchs

    case "update":
    case "insert":
    {
        $where = <<<SQL
where typ_company = {$typ_company}
  and typ_code = "{$typ_code}"

SQL;
        $rdata = $upd->updRecord($reqdata, $update_table, $where);
        //if ($DEBUG) wr_log("/tmp/BIN_TYPES.log",$rdata);
        if ($DEBUG) wr_log("/tmp/BIN_TYPES.log", "Response:\n{$x}");
        echo $rdata;
        break;
    } // end update

    case "delete":
    {
        $where = <<<SQL
where typ_company = {$typ_company}
  and typ_code = "{$typ_code}"

SQL;

        $reqdata["action"] = 1;
        //$rdata=$upd->updRecord($reqdata,$update_table,$where);
        // for some reason this does not work, even though the class calls the
        // same function, it logs the delete but noting comes back
        $rdata = $upd->delRecord($update_table, $where);
        echo $rdata;
        break;
    } // end delete
} // end switch reqdata action

?>
