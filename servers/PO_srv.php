<?php

// PO_srv.php -- Server for PO Stuff as well as POStatus.php
//02/09/22 dse initial
//09/28/22 dse Add Putaway
//08/10/23 dse Add openOnPO
// 05/08/24/dse changed to not extend qty * pkgqty, let client do the math
// 05/21/24/dse update getBatchByPO to support either host or wms po#
// 06/14/24/dse change count of RCPT_SCAN to max(line_num)
// 06/14/24/dse add Opt 27 to book inventory when recv to tote
// 06/26/24/dse insert WHSEQTY if it doesn't exist on recvReceipt
// 07/26/24/dse correct non numeric toteid in putAway


$update_table = "POHEADER";
$query_table = "POHEADER";
$query_table1 = "POITEMS";
$DEBUG = true;
$u = true;
require("srv_hdr.php");
require_once("{$wmsDir}/include/get_table.php");
require_once("{$wmsDir}/include/cl_inv.php");
require_once("{$wmsDir}/include/cl_TOTES.php");
require_once("updPoStat.php");
require("getToteId.php");


$db1 = new WMS_DB;
require_once("{$wmsDir}/include/get_option.php");


if (isset($_REQUEST["searcH"])) $srch = $_REQUEST["searcH"]; else $srch = "";
$comp = 0;
if ($srch <> "") $comp = intval($srch);
if ($DEBUG) wr_log("/tmp/PO_srv.log", "inputData={$inputdata}");
$action = $reqdata["action"];
if (isset($reqdata["comp"])) $comp = $reqdata["comp"]; else $comp = 1;
if (isset($reqdata["host_po_num"])) $PO = $reqdata["host_po_num"]; else $PO = "";
if (isset($reqdata["wms_po_num"])) $wms_po_num = $reqdata["wms_po_num"]; else $wms_po_num = 0;
if (isset($reqdata["batch"])) $batch = $reqdata["batch"]; else $batch = 0;
if (isset($reqdata["vendor"])) $vendor = $reqdata["vendor"]; else $vendor = "";
if (isset($reqdata["rcpt"])) $rcpt = $reqdata["rcpt"]; else $rcpt = "";
if (isset($reqdata["delivDate"])) $delivDate = $reqdata["delivDate"]; else $delivDate = "";
if (isset($reqdata["typeSearch"])) $typeSearch = $reqdata["typeSearch"]; else $typeSearch = "";
if (isset($reqdata["POstatus"])) $POstatus = $reqdata["POstatus"]; else $POstatus = " < 4";
if (isset($reqdata["po_status"])) $po_status = $reqdata["po_status"]; else $po_status = "";
if (isset($reqdata["sortBy"])) $sortBy = $reqdata["sortBy"]; else $sortBy = "";
if (isset($reqdata["orderby"])) $orderby = $reqdata["orderby"];
else $orderby = "host_po_num";
if (isset($reqdata["plSearch"])) $plSearch = $reqdata["plSearch"]; else $plSearch = "";
if (isset($reqdata["pnSearch"])) $pnSearch = $reqdata["pnSearch"]; else $pnSearch = "";
$others = array(
    "RECEIPT" => 0,
    "userId" => 0,
    "recvType" => 0,
    "recvTo" => 1,
    "POs" => 2,
    "HPO" => 2,
    "shadow" => 0,
    "UPC" => 1,
    "PPL" => 1,
    "PPN" => 1,
    "PPD" => 1,
    "partUOM" => 1,
    "pkgUOM" => 1,
    "pkgQty" => 0,
    "totalQty" => 0,
    "prefZone" => 1,
    "qtyRecvd" => 0,
    "qtyStockd" => 0,
    "BinTote" => 1,
    "primaryBin" => 1,
    "totes" => 2,
    "toteId" => 0
);
foreach ($others as $var => $typ) {
    $defVal = 0;
    if ($typ == 1) $defVal = "";
    if ($typ == 2) {
        unset($defVal);
        $defVal = array();
    }
    if ($typ == 3) $defVal = date("Y/m/d h:i:s");
    if ($typ == 4) $defVal = null;
    if (isset($reqdata[$var])) $$var = $reqdata[$var]; else $$var = $defVal;
} // end foreach others
unset($others);

// set table def and select and update fields
$opt = array();
$opt[21] = get_option($db, $comp, 21);
if (trim($opt[21]) == "") $opt[21] = "PO";
$uFlds = setFldDef($db, $update_table);
if ($query_table == $update_table) {
    $qFlds = $uFlds;
} else {
    $qFlds = setFldDef($db, $query_table);
}

$upd_flds = setFlds($db, $uFlds);
$sel_flds = setFlds($db, $qFlds);
$dFlds = setFldDef($db, $query_table1);
$det_flds = setFlds($db, $dFlds);

if ($DEBUG) wr_log("/tmp/PO_srv.log", "Switching={$action}");
switch ($action) {

    case "getBatchByPO": // get open batches by host po#
    {
        // returns the count and all open batch#'s for a po
        $where = "";
        if (isset($PO) and $PO <> "") {
            $where = <<<SQL
where host_po_num = "{$PO}"
SQL;
        }
        if (isset($wms_po_num) and $wms_po_num > 0) {
            $where = <<<SQL
where wms_po_num = {$wms_po_num}
SQL;
        }

        $numrows = 0;
        $rdata = array();
        $rdata["numRows"] = $numrows;
        if ($where <> "") {
            $SQL = <<<SQL
select distinct
 B.batch_num,
 po_number,
 host_po_num,
 po_status,
 batch_status,
 po_type

  from RCPT_SCAN A, RCPT_BATCH B, POHEADER
  {$where}
  and company = {$comp}
  and batch_status < 1
  and B.batch_num = A.batch_num
  and wms_po_num = po_number

SQL;

            $rc = $db->query($SQL);
            $numrows = $db->num_rows();
            $i = 1;
            while ($i <= $numrows) {
                $db->next_record();
                if ($numrows and $db->Record) {
                    $rdata[$i]["batch"] = $db->f("batch_num");
                }
                $i++;
            } // while i < numrows

        } // PO is not empty
        $rdata["numRows"] = $numrows;
        if (isset($x)) unset($x);
        $x = json_encode($rdata);
        if ($DEBUG) wr_log("/tmp/PO_srv.log", $x);
        echo $x;
        break;
    } // end getBatchByPO

    case "setPOStatus": // set po status
    {
        $rdata = array();
//action:'update',
        //company: {$comp},
        //wms_po_num :    application.wms_po_num,
        //po_status :    postat
        if (isset($comp) and $comp > 0
            and isset($wms_po_num) and $wms_po_num > 0
            and isset($po_status) and $po_status <> "") {
            $rdata = updPoStat($db, $comp, $wms_po_num, $po_status);
        }
        if (isset($x)) unset($x);
        $x = json_encode($rdata);
        if ($DEBUG) wr_log("/tmp/PO_srv.log", $x);
        echo $x;
        break;
    } // end setPOStatus
    case "getPO": // get po num and comp from host po
    {
        if ($PO == "") exit;
        $awhere = "";
        if (isset($comp)) $awhere = " and company = {$comp}";
        $SQL = <<<SQL
select
company,
wms_po_num,
host_po_num,
po_status
from POHEADER
where host_po_num = "{$PO}"
{$awhere}

SQL;
        $rdata = array();
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
        if ($DEBUG) wr_log("/tmp/PO_srv.log", $x);
        echo $x;
        break;
    } // end getPO
    case "fetchall":
    case "fetchSingle":
    {
        $where = "";
        $order_by = "order by {$orderby}";
        if (isset($sortBy) and is_numeric(trim($sortBy))) {
            $ob = array(
                0 => "host_po_num",
                1 => "vendor, DATE_FORMAT(est_deliv_date,'%M/%d/%Y')",
                2 => "DATE_FORMAT(est_deliv_date,'%M/%d/%Y') , vendor",
                3 => "po_status, host_po_num",
                4 => "po_type, host_po_num",
            );
            if (isset($ob[$sortBy])) $order_by = "order by {$ob[$sortBy]}";
            unset($ob);
        }
        $cond = "and";
        $postat = " and po_status {$POstatus}";

        $where = "where company = {$comp} {$postat}\n";
        if ($PO <> "") $where .= " and host_po_num like \"{$PO}%\"\n";
        if ($vendor <> "") $where .= " and vendor like \"{$vendor}%\"\n";
        if ($delivDate <> "") $where .= " and est_deliv_date like \"{$delivDate}%\"\n";
        if ($typeSearch == "%") $typeSearch = "";
        if ($typeSearch <> "") $where .= " and po_type = \"{$typeSearch}\"\n";

        if ($action == "fetchSingle") {
            $where = <<<SQL
  where company = {$comp}
  and wms_po_num = {$wms_po_num}

SQL;
        }

        $SQL = <<<SQL
select
{$sel_flds}
from {$query_table}
{$where}
{$order_by}

SQL;
        if ($DEBUG) wr_log("/tmp/PO_srv.log", "SQL=" . $SQL);

        $rc = $db->query($SQL);
        $numrows = $db->num_rows();
        $i = 0;
        while ($i <= $numrows) {
            $db->next_record();
            if ($numrows and $db->Record) {
                foreach ($db->Record as $key => $data) {
                    if ($key == "po_date" or $key == "est_deliv_date" or $key == "sched_date") {
                        $rdata[$i]["$key"] = date("m/d/Y", strtotime($data));
                    } else {
                        $rdata[$i]["$key"] = $data;
                    }
                }
                $rdata[$i]["statDesc"] = "Not Received";
                $rdata[$i]["num_lines"] = countPOLines($db, $rdata[$i]["wms_po_num"]);
                $tmp = chkInProc($db1, $rdata[$i]["wms_po_num"]);
                if ($tmp !== false) $rdata[$i]["po_status"] = 2;
                if ($rdata[$i]["po_status"] == -1) $rdata[$i]["statDesc"] = "BackOrders";
                if ($rdata[$i]["po_status"] == 1) $rdata[$i]["statDesc"] = "On Dock";
                if ($rdata[$i]["po_status"] == 2) $rdata[$i]["statDesc"] = "In Process";
                if ($rdata[$i]["po_status"] == 3) $rdata[$i]["statDesc"] = "In Putaway";
                if ($rdata[$i]["po_status"] == 4) $rdata[$i]["statDesc"] = "Updating";
                if ($rdata[$i]["po_status"] > 4) $rdata[$i]["statDesc"] = "Received";
                $rdata[$i]["typeDesc"] = "Purchase Order";
                if ($rdata[$i]["po_type"] == "A") $rdata[$i]["typeDesc"] = "ASN";
                if ($rdata[$i]["po_type"] == "T") $rdata[$i]["typeDesc"] = "Transfer";
                if ($rdata[$i]["po_type"] == "R") $rdata[$i]["typeDesc"] = "RMA";
                if ($rdata[$i]["po_type"] == "S") $rdata[$i]["typeDesc"] = "Special Order";
                $tmp = sumPO($db1, $rdata[$i]["wms_po_num"]);
                $tdate = $rdata[$i]["po_date"];
                if ($rdata[$i]["est_deliv_date"] == "12/31/1969") $rdata[$i]["est_deliv_date"] = $tdate;
                if ($rdata[$i]["sched_date"] == "12/31/1969") $rdata[$i]["sched_date"] = $tdate;
                $rdata[$i]["QtyOrd"] = $tmp["qOrd"];
                $rdata[$i]["totalRecvd"] = $tmp["qRec"];
                $rdata[$i]["inProcessRecvd"] = $tmp["cRec"];
                $rdata[$i]["stocked"] = $tmp["cStk"];
                $rdata[$i]["tmp"] = $tmp;


                /*
               0 = Open
                1 = On Dock (Send ON_DOCK message to Host)
                2 = In Process (Send LOCK message to Host)
                3 = In Putaway (Send PUTAWAY message to Host)
                4 = Updating
                5 = Received (send RECEIPT message to Host)
                6 = Sent (send OFF_DOCK to Host)
               if backorders exist, set status to -1 when all done
               if not back orders exist, set status to 7
               */

            }
            $i++;
        } // while i < numrows
//$aa=print_r($rdata);
        if ($DEBUG) wr_log("/tmp/PO_srv.log", "reqdata=" . $PO);
        if ($DEBUG) wr_log("/tmp/PO_srv.log", "PO=" . $PO);
//if ($DEBUG) wr_log("/tmp/PO_srv.log","RDATA=" . $aa);
        if ($action == "fetchSingle") $rdata = $rdata[0];
        $x = json_encode($rdata);
        if ($DEBUG) wr_log("/tmp/PO_srv.log", $SQL);
        if ($DEBUG) wr_log("/tmp/PO_srv.log", $x);
        echo $x;
        break;
    } // end fetchs
    case "update":
    {
        $where = <<<SQL
  where company = {$comp}
  and wms_po_num = {$wms_po_num}

SQL;


        $SQL = <<<SQL
select
{$upd_flds}
from {$update_table}
{$where}

SQL;

        if ($DEBUG) wr_log("/tmp/PO_srv.log", "In Update Mode SQL=");
        if ($DEBUG) wr_log("/tmp/PO_srv.log", $SQL);
        $currec = array();
        $rc = $db->query($SQL);
        $numrows = $db->num_rows();
        if ($DEBUG) wr_log("/tmp/PO_srv.log", "{$numrows} records");
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
        if ($DEBUG) wr_log("/tmp/PO_srv.log", "{$numrows} records");
        $j = count($currec);
        if ($DEBUG) wr_log("/tmp/PO_srv.log", "found {$j} records");
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
                if ($DEBUG) wr_log("/tmp/PO_srv.log", $SQL);
                $rc = $db->Update($SQL);
                $msg = "({$rc}) Records Saved";
                if ($DEBUG) wr_log("/tmp/PO_srv.log", $msg);
                $rdata = '{"message":"' . $msg . '"}';
            } else {
                if ($DEBUG) wr_log("/tmp/PO_srv.log", "No fields to Update");
                $rdata = '{"message":"No Changes, Record Not Updated!"}';

            }
            $x = $rdata;
//header('Content-type: application/json');
//$x=json_encode($rdata);
            if ($DEBUG) wr_log("/tmp/PO_srv.log", $x);
            echo $x;
        } // got a record, update it if needed


        break;
    } // end update
    case "fetchDetail":
    case "fetchDetail1":
    {
        $where = <<<SQL
  where company = {$comp}
  and wms_po_num = {$wms_po_num}
  and poi_po_num = wms_po_num

SQL;
        if (isset($plSearch) and $plSearch <> "") $where .= " and p_l like \"{$plSearch}%\"\n";
        if (isset($pnSearch) and $pnSearch <> "") $where .= " and part_number like \"{$pnSearch}%\"\n";
        $orderby = "order by poi_po_num,poi_line_num";
        $SQL = <<<SQL
select host_po_num, vendor, {$det_flds}
from POHEADER,POITEMS
{$where}
{$orderby}

SQL;

        $joinClause = "   and batch_num = {$batch}";
        if ($action == "fetchDetail1") {
            if (!is_numeric($batch) and $batch <> "")
                $joinClause = <<<SQL
   and batch_num in ({$batch})
   and scan_status < 2

SQL;
        } // end fetchDetail1

        if ($batch > 0) {
            $SQL = <<<SQL
select host_po_num, vendor, {$det_flds},
IFNULL(line_num,0) as line_num,
IFNULL(qty_stockd,0) as qty_stockd,
IFNULL(totalQty,0) as totalQty,
IFNULL(pack_id,0) as pack_id,
IFNULL(recv_to,"") as recv_to,
IFNULL(batch_num,0) as batch_num

from POHEADER,POITEMS
 LEFT JOIN RCPT_SCAN
  ON po_number = poi_po_num and poi_line_num = po_line_num
{$joinClause}
   and RCPT_SCAN.shadow = POITEMS.shadow
{$where}
{$orderby}

SQL;
            $SQL = str_replace(",shadow", ",POITEMS.shadow", $SQL);
            $SQL = str_replace(",line_type", ",POITEMS.line_type", $SQL);
        } // end batch > 0
        if ($DEBUG) wr_log("/tmp/PO_srv.log", $SQL);
        $rc = $db->query($SQL);
        $numrows = $db->num_rows();
        $i = 0;
        while ($i <= $numrows) {
            $db->next_record();
            if ($numrows and $db->Record) {
                foreach ($db->Record as $key => $data) {
                    if (!is_numeric($key)) {
                        $rdata[$i]["$key"] = $data;
                    }
                }
                if (trim($rdata[$i]["uom"]) == "") $rdata[$i]["uom"] = "EA";
                $j = $rdata[$i]["line_type"];
                if (trim($j) == "0") $rdata[$i]["line_type"] = "Mdse";
                if (trim($j) == "1") $rdata[$i]["line_type"] = "Core";
                if (trim($j) == "2") $rdata[$i]["line_type"] = "Defect";
                unset($j);
            }
            $i++;
        } // while i < numrows
        if (isset($x)) unset($x);
        $rdata = chkRecving($db, $comp, $rdata);
        $x = json_encode($rdata);
        if ($DEBUG) wr_log("/tmp/PO_srv.log", $x);
        echo $x;
        break;
    }
    case "rcptInfo":
    {
        if ($rcpt <> "") $rdata = getRecpt($db, $rcpt);
        else $rdata = array();
        if (isset($x)) unset($x);
        $x = json_encode($rdata);
        if ($DEBUG) wr_log("/tmp/PO_srv.log", $x);
        echo $x;
        break;
    } // end rcptInfo
    case "insert":
    {
        $rdata = '{"message":"Insert Record Not Supported"}';
        echo $rdata;
        break;
    } // end insert
    case "delete":
    {
        $rdata = '{"message":"Delete Record Not Supported"}';
        echo $rdata;
        break;
    } // end delete
    case "recvReciept":
    {
        /* Vars in input array -- all required

           "action"=>"recvReciept",
           "batch"=>reciept#,
           "RECEIPT"=>reciept#,
           "userId"=>UserID,
           "recvType"=>recvType, // 1=PO 2=ASN, 3=Transfer, 4=Cust Return, 5=Unexpected
           "recvTo"=>"a", // a=to tote, b=to Bin
           "POs"=>$POs, // array of possible PO #s
           "HPO"=>$HPO, // matching array of host PO #s
           "vendor"=>$vendor,
           "shadow"=> $shadow,
           "comp"=> $comp,
           "UPC"=> $UPC, // the UPC scanned
           "PPL"=> $PPL, // the P/L
           "PPN"=> $PPN, // the part number
           "PPD"=> $PPD, // the part description
           "partUOM"=> $partUOM,
           "pkgUOM"=> $pkgUOM,
           "pkgQty"=> $pkgQty,
           "totalQty"=> $tqty,
           "prefZone"=> $prefZone, // prefered zone|aisle
           "qtyRecvd"=> $qtyRecvd,
           "BinTote"=>$scaninput, // the bin or tote
           "totes"=> $totes // array of totes for this recving
        */


//Start -----------------------------------------------------------------------
        $rd = array();
// good part, good PO, good bin, save it
        if (isset($batch)) {
            // read option to add inventory when recving to tote
            $thisRecvTo = $recvTo;
            $opt[27] = get_option($db, $comp, 27);
            if (trim($opt[27]) == "") $opt[27] = "0";
            if ($opt[27] > 0 and $recvTo == "a") $thisRecvTo = "b";
            // wr_log("/tmp/PO_srv.log","get_batch({$batch});");
            $w = get_batch($db, $batch);
            $upd = new AddUpdDel;
            if ($w["status"] == -35) { // batch does not exist yet
                $rqdata = array();
                $rqdata["batch_num"] = $batch;
                $rqdata["user_id"] = $userId;
                $rqdata["batch_status"] = 0;
                $rqdata["batch_date"] = date("Y/m/d H:i:s");
                $rqdata["batch_company"] = $comp;
                $rqdata["batch_type"] = $recvType;
                $rqdata["action"] = 2;
                $where = "where batch_num = {$batch}";
                $rd["RCPT_BATCH"] = $upd->updRecord($rqdata, "RCPT_BATCH", $where);
                //echo "batch return_code={$return_code}\n";

                foreach ($POs as $key => $po) {
                    unset($rqdata);
                    $rqdata = array();
                    $rqdata["wms_po_num"] = $po;
                    $rqdata["batch_num"] = $batch;
                    $rqdata["action"] = 2;
                    $where = "where wms_po_num = {$po} and batch_num = {$batch}";
                    $rd["RCPT_INWORK"] = $upd->updRecord($rqdata, "RCPT_INWORK", $where);
                    //echo "inwork return_code={$return_code}\n";
                }
            } // batch does not exist yet
            //add batch detail
            unset($rqdata);
            $rqdata = array();
            $theUser = $userId;

            if ($shadow > 0) chkWHSEQTY($db, $comp, $shadow);
            wr_log("/tmp/PO_srv.log", "get_batchDetail({$batch},{$shadow},{$theUser});");
            $w = get_batchDetail($db, $batch, $shadow, $theUser);
            if (empty($w)) { //add new record
                $whichPO = setPOforPart($db, $shadow, $POs, $qtyRecvd);
                $po = $POs[$whichPO];
                // get line # from selected PO
                $tmp = chkPartOnPO($db, $shadow, array(0 => $po), $qtyRecvd);
                $poline = 0;
                $qtyOrd = 0;
                if (isset($tmp[1]["poi_line_num"])) {
                    $poline = $tmp[1]["poi_line_num"];
                    $qtyOrd = $tmp[1]["qty_ord"];
                }
                $stockd = 0;
                //if ($recvTo == 'b') $stockd=($qtyRecvd * $pkgQty);
                if ($thisRecvTo == 'b') $stockd = ($qtyRecvd);


                $hpo = $HPO[$whichPO];
                wr_log("/tmp/PO_srv.log", "count_batch({$batch});");
                $lines = count_batch($db, $batch);
                $next_line = $lines + 1;
                $rqdata["batch_num"] = $batch;
                $rqdata["line_num"] = $next_line;
                $rqdata["pkgUOM"] = $pkgUOM;
                $rqdata["scan_upc"] = $UPC;
                $rqdata["po_number"] = $po;
                $rqdata["po_line_num"] = $poline;
                $rqdata["scan_status"] = 0;
                $rqdata["scan_user"] = $theUser;
                $rqdata["pack_id"] = $BinTote;
                $rqdata["shadow"] = $shadow;
                $rqdata["partUOM"] = $partUOM;
                $rqdata["line_type"] = "0"; // need to pass this too
                $rqdata["pkgQty"] = $pkgQty;
                $rqdata["scanQty"] = $qtyRecvd;
                //$rqdata["totalQty"]=($qtyRecvd * $pkgQty);
                $rqdata["totalQty"] = ($qtyRecvd);
                $rqdata["timesScanned"] = 1;
                $rqdata["recv_to"] = $recvTo;
                $rqdata["qty_stockd"] = $stockd;
                $rqdata["totalOrd"] = $qtyOrd;
                $rqdata["action"] = 2;
                $where = <<<SQL
 where batch_num = {$batch}
   and shadow = {$shadow}
   -- and scan_user = {$theUser}

SQL;
                $rd["RCPT_SCAN"] = $upd->updRecord($rqdata, "RCPT_SCAN", $where);
            } //add new record
            else { // update qty in scan record
                $theUser = $userId;
                $rqdata = $w;
                $where = <<<SQL
 where batch_num = {$batch}
   and shadow = {$shadow}
   -- and scan_user = {$theUser}

SQL;
                $stockd = 0;
                //if ($recvTo == 'b') $stockd=($qtyRecvd * $pkgQty);
                if ($thisRecvTo == 'b') $stockd = ($qtyRecvd);

                $rqdata["scanQty"] = $w["scanQty"] + $qtyRecvd;
                //$rqdata["totalQty"]=($rqdata["scanQty"] * $pkgQty);
                $rqdata["totalQty"] = ($rqdata["totalQty"] + $qtyRecvd);
                $rqdata["timesScanned"] = $w["timesScanned"] + 1;
                $rqdata["qty_stockd"] = $w["qty_stockd"] + $stockd;
                $rd["RCPT_SCAN"] = $upd->updRecord($rqdata, "RCPT_SCAN", $where);
            } // update qty in scan record
            $save_RCPTSAN = $rqdata;
            $save_RCPTSCAN_where = $where;

            $whichPO = setPOforPart($db, $shadow, $POs, $qtyRecvd);
            $po = $POs[$whichPO];
            $hpo = $HPO[$whichPO];
            //$qty=($qtyRecvd * $pkgQty);
            $qty = $qtyRecvd;
            $binType = substr($opt[21], 0, 1);
            if ($thisRecvTo == "b") { // recv to Bin, update WHSEQTY and add PARTHIST
                if ($recvTo == "a") $BinTote = "!" . $BinTote; // add ! to start of nin to denote tote
                $tmp = getPrice($db, $rqdata["po_number"], $rqdata["po_line_num"]);
                if (isset($mst)) {
                    if (trim($mst["primary_bin"]) <> "") $binType = substr($opt[21], 1, 1);
                } // end mst is set
                if (2 == 2) {
                    // use cl_inv class
                    $sparams1 = array(
                        "wms_trans_id" => $po,
                        "shadow" => $shadow,
                        "company" => $comp,
                        "psource" => $vendor,
                        "user_id" => $theUser,
                        "host_id" => $hpo,
                        "ext_ref" => "Direct To Bin",
                        "trans_type" => "RCV",
                        "in_qty" => $qty,
                        "uom" => $partUOM,
                        "floc" => $BinTote,
                        "tloc" => "Received",
                        "inv_code" => "0",
                        "mdse_price" => $tmp["cost"],
                        "core_price" => $tmp["core"],
                        "in_qty_core" => 0,
                        "in_qty_def" => 0,
                        "bin_type" => $binType
                    );

                    $trans = new invUpdate;
                    $rd["updQty"] = $trans->updQty($sparams1); // 1=success, 0=failed
                    if ($recvTo == "a") {
                        if (substr($BinTote, 0, 1) == "!") $tote = substr($BinTote, 1); else $tote = $BinTote;
                        $bincls = new TOTE;
                        $rd["addItemToTote"] = $bincls->addItemToTote($tote, $shadow, $qty, $partUOM);
                    }

                    //Do something on failure *********************************************
                } // end 2 == 2
                if (isset($rd["updQty"])) $rc = $rd["updQty"]; else $rc = 0;
                if ($rc > 0) { // update inv was successful
                    $rqdata["scan_status"] = 1;
                    $rd["RCPT_SCAN2"] = $upd->updRecord($rqdata, "RCPT_SCAN", $where);
                } // update inv was successful

            } // recv to Bin, update WHSEQTY and add PARTHIST
            else { // ********************* Recv to Tote *****************************
                //add tote to session
                $lasttote = 0;
                if (isset($totes)) {
                    if (!isset($totes[$BinTote]))
                        $totes[$BinTote] = $BinTote;
                } // totes are set
                else { // totes are not set
                    $totes[$BinTote] = $BinTote;
                } // totes are not set

                $prefz = "";
                $prefi = 0;
                if (isset($prefZone)) {
                    $k = explode("|", $prefZone);
                    if (isset($k[0])) $prefz = $k[0];
                    if (isset($k[1])) $prefi = intval($k[1]);
                }
                if (isset($prodline)) // if not and not prefZone, lookup PL
                {
                    $prefz = $prodline["pl_perfered_zone"];
                    //$prefi=sprintf("%02d",$prodline["pl_perfered_aisle"]);
                    $prefi = $prodline["pl_perfered_aisle"];
                }

//add tote to RCPT_TOTE with bincls->updRcptTote($req)
                $bincls = new TOTE;

                $req = array(
                    "rcpt_num" => $batch,
                    "tote_id" => $BinTote,
                    "rcpt_status" => 0,
                    "last_zone" => "RCV",
                    "last_loc" => "",
                    "target_zone" => $prefz,
                    "target_aisle" => $prefi
                );
//print_r($req);
                $rd["updRecptTote"] = $bincls->updRcptTote($req);

//add part to totedtl,
                //$qty=($qtyRecvd * $pkgQty);
                $qty = $qtyRecvd;
                $rd["addItemToTote"] = $bincls->addItemToTote($BinTote, $shadow, $qty, $partUOM);

//add to parthist, with from bin "Receiving" and to bin of tote#
                $req = array(
                    "wms_trans_id" => $po,
                    "shadow" => $shadow,
                    "company" => $comp,
                    "psource" => $vendor,
                    "user_id" => $theUser,
                    "host_id" => $hpo,
                    "ext_ref" => "To Putaway",
                    "trans_type" => "RCV",
                    "in_qty" => $qty,
                    "uom" => $partUOM,
                    "floc" => $BinTote,
                    "tloc" => "Received",
                    "inv_code" => "0",
                    "mdse_price" => 0.00,
                    "core_price" => 0.00,
                    "in_qty_core" => 0,
                    "in_qty_def" => 0,
                    "bin_type" => $binType
                );
                $trans = new invUpdate;
                $rd["updQty2"] = $trans->updQty($req, false);
            } // * END *************** Recv to Tote *****************************

            $msg = "Last: {$PPL} {$PPN} {$PPD} Qty: {$qtyRecvd} to: {$BinTote}";
        }
        if (!isset($msg)) $msg = "";
        $rd["msg"] = $msg;
        if (isset($x)) unset($x);
        $x = json_encode($rd);
        if ($DEBUG) wr_log("/tmp/PO_srv.log", $x);
        echo $x;
        // good part, good PO, good bin, save it
        break;
    } // end recvReciept
    case "putAway":
    {
        /*
         Update Inventory and book parthist
         update RCPT_SCAN stockd qty and scan_status = 1
         delete part from tote (based on qty entered)
         if the tote is empty, free tote to unused

         vars
         to figure qty I need pkgQty unless the putaway screen does it for me
         here is what I have in the putaway screen right now;
         [toteId] => 144
            [wmspo] => 1014
            [hostpo] => 99705
            [batch_num] => 125
            [comp] => 1
            [shadow] => 89286
            [partUOM] => EA
            [pkgQty] => 1
            [primaryBin] => B-04-12-B
            [obin] => Array
                (
                    [0] => B-04-02-A
                )
            [Qty] => 1
            [bin] => B-04-12-B
        */
        $rd = array();
        $rd["status"] = 0;
        // get RCPT_SCAN info for later update
        if ($batch < 1 and (isset($RECEIPT) and $RECEIPT > 0)) $batch = $RECEIPT;
        $toteNum = getToteId($toteId, $comp);
        if ($batch > 0 and $shadow > 0 and $toteNum > 0 and $qtyStockd <> 0) {
            // get human tote code for parthist
            $SQL = <<<SQL
select tote_code from TOTEHDR
where tote_id = {$toteNum}

SQL;
            $w = $db->gData($SQL);
            $numRows = $db->NumRows;
            if ($numRows > 0) $toteCode = $w[1]["tote_code"];
            else $toteCode = $toteId;

            // check RCPT_TOTE for this batch if not present add it
            chkRcptTote($db, $batch, $toteNum);

            $SQL = <<<SQL
select batch_num, po_number, po_line_num,pack_id, line_num,
scanQty,totalQty,qty_stockd,
qty_ord, qty_recvd,
tote_item, tote_qty, tote_uom
from RCPT_TOTE D, RCPT_SCAN A, POITEMS B, TOTEDTL C
where D.rcpt_num    = {$batch}
  and D.tote_id     = {$toteNum}
  and A.shadow      = {$shadow}
  and A.batch_num   = D.rcpt_num
  and poi_po_num    = po_number
  and poi_line_num  = po_line_num
  and B.shadow      = A.shadow
  and C.tote_id     = D.tote_id
  and tote_shadow   = A.shadow

SQL;
            if ($DEBUG) wr_log("/tmp/PO_srv.log", $SQL);
            $w = $db->gData($SQL);
            $numRows = $db->NumRows;
//echo $SQL;
//print_r($w);
            if ($numRows > 0) $rcpt = $w[1]; else $rcpt = array();
//need to add check for opt 27, it's ok if no PO#
            if ($numRows > 0) {
                $tmp = getPrice($db, $rcpt["po_number"], $rcpt["po_line_num"]);
                $binType = substr($opt[21], 0, 1);
                if (trim($primaryBin) <> "") $binType = substr($opt[21], 1, 1);
                $sparams1 = array(
                    "wms_trans_id" => $wms_po_num,
                    "shadow" => $shadow,
                    "company" => $comp,
                    "psource" => $tmp["vendor"],
                    "user_id" => $userId,
                    "host_id" => $PO,
                    "ext_ref" => "Put Away",
                    "trans_type" => "PUT",
                    "in_qty" => $qtyStockd,
                    "uom" => $partUOM,
                    "floc" => $BinTote,
                    "tloc" => $toteCode,
                    "inv_code" => "0",
                    "mdse_price" => $tmp["cost"],
                    "core_price" => $tmp["core"],
                    "in_qty_core" => 0,
                    "in_qty_def" => 0,
                    "bin_type" => $binType
                );
                if ($DEBUG) wr_log("/tmp/PO_srv.log", json_encode($sparams1));
                $trans = new invUpdate;
                $rd["updQty"] = $trans->updQty($sparams1); // 1=success, 0=failed
                if ($rd["updQty"] > 0) { // inv update is good

                    $updstat = "";
                    $q1 = $rcpt["scanQty"];
                    $q2 = $rcpt["qty_stockd"] + $qtyStockd;
                    if ($q2 >= $q1) $updstat = ", scan_status = 1";
                    $SQL = <<<SQL
update RCPT_SCAN
set qty_stockd = qty_stockd + {$qtyStockd}{$updstat}
where batch_num = {$rcpt["batch_num"]}
and line_num = {$rcpt["line_num"]}
SQL;
                    if ($DEBUG) wr_log("/tmp/PO_srv.log", $SQL);
                    $rd["updScan"] = $db->Update($SQL);
                    // delete from tote -- move to cl_TOTES later(but since its read already)
                    $leftInTote = $rcpt["tote_qty"] - $qtyStockd;
                    if ($leftInTote < 1)
                        $SQL = <<<SQL
delete from TOTEDTL
where tote_id = {$toteNum}
and tote_item = {$rcpt["tote_item"]}

SQL;
                    else
                        $SQL = <<<SQL
update TOTEDTL
set tote_qty = {$leftInTote}
where tote_id = {$toteNum}
and tote_item = {$rcpt["tote_item"]}

SQL;
                    $rd["updTote"] = $db->Update($SQL);
                    // check if tote is empty, if so, set status to free
                    $SQL = <<<SQL
select count(*) as cnt from TOTEDTL
where tote_id = {$toteNum}

SQL;
                    $w1 = $db->gData($SQL);
                    $rd["toteItems"] = $w1[1]["cnt"];
                    if ($rd["toteItems"] < 1) { // update tote status
                        $SQL = <<<SQL
update TOTEHDR
set tote_status = 0,
    tote_type = " ",
    tote_location = " ",
    tote_ref = 0,
    tote_lastused = NOW()
where tote_id = {$toteNum}

SQL;
                        $rd["toteReset"] = $db->Update($SQL);
                    } // update tote status
                } // inv update is good
                else { // inv update failed
                    $rd["errCode"] = -1;
                    $rd["errText"] = "Inventory Update Failed";
                } // inv update failed
            } // end numRows > 0
        } // got a batch, shadow and tote and qty <> 0
        if (!isset($rd["updQty"])) $rd["updQty"] = false;
        if ($rd["updQty"] and $rd["updScan"] and $rd["updTote"]) {
            $rd["status"] = 1;
        } // all updated ok
        if (isset($x)) unset($x);
        $x = json_encode($rd);
        if ($DEBUG) wr_log("/tmp/PO_srv.log", $x);
        echo $x;
        break;
    } // end putAway

    case "openOnPO1":
    {
        $rdata = array();
        if ($wms_po_num > 0) {
            $rdata = sumPO2($db, $wms_po_num);
        } // end wms po > 0
        if (isset($x)) unset($x);
        $x = json_encode($rdata);
        if ($DEBUG) wr_log("/tmp/PO_srv.log", $x);
        echo $x;
        break;
    } // end openOnPO

    case "inWorkPO":
    {
        $where = "";
        if ($PO <> "") $where .= " and host_po_num like \"{$PO}%\"\n";
        if ($vendor <> "") $where .= " and vendor like \"{$vendor}%\"\n";
        if ($typeSearch == "%") $typeSearch = "";
        if ($typeSearch <> "") $where .= " and po_type = \"{$typeSearch}\"\n";

        $pos = getInWorkPO($db, $comp, 1, $where);
        $k = 0;
        if (count($pos) > 0) foreach ($pos as $pkey => $po) {
            if (isset($tmp)) unset($tmp);
            $tmp = array();
            $SQL = <<<SQL
select distinct A.batch_num,
scan_status,
packing_slip,
po_number
from RCPT_SCAN A, RCPT_INWORK B
where scan_status < 2
and po_number = {$po["po_number"]}
and B.batch_num = A.batch_num
and wms_po_num = po_number

SQL;

            $rc = $db->query($SQL);
            $numrows = $db->num_rows();
            $i = 1;
            while ($i <= $numrows) {
                $db->next_record();
                if ($numrows and $db->Record) {
                    $batch = $db->f("batch_num");
                    if (!isset($tmp[$batch])) {
                        foreach ($db->Record as $key => $data) {
                            if (!is_numeric($key)) {
                                $tmp[$batch]["$key"] = $data;
                            }
                        }
                    }
                    $i++;
                } // batch is not set
                // add upd the total ordered, recveived and stocked
                $tmp1 = array();
                if (isset($tmp[$batch]["po_number"])) {
                    //$tmp1=sumPO1($db1,$tmp[$batch]["po_number"],$batch);
                    $tmp1 = sumPO3($db1, $tmp[$batch]["po_number"], $batch);
                    foreach ($tmp1 as $fld => $val) {
                        $tmp[$batch][$fld] = $val;
                        $pos[$pkey][$fld] = $pos[$pkey][$fld] + $val;
                    } // end foreach tmp1
                } // end batch and po is set
                $k++;
            } // while i < numrows
            $tmp2 = sumPO2($db1, $tmp[$batch]["po_number"]);
            $pos[$pkey]["totalQtyOrd"] = $tmp2["totalOrd"];
            $pos[$pkey]["totalPreRecvd"] = $tmp2["preRecvd"];
            $pos[$pkey]["totalLines"] = $tmp2["totalLines"];
            $pos[$pkey]["NumBatches"] = count($tmp);
            $pos[$pkey]["Batches"] = $tmp;
        } // end foreach pos
        if (isset($x)) unset($x);
        $x = json_encode($pos);
        if ($DEBUG) wr_log("/tmp/PO_srv.log", $x);
        echo $x;
        break;
    } // end inWorkPO

    case "inWorkPO1":
    { // group em by packing slip
        $where = "";
        if ($PO <> "") $where .= " and host_po_num like \"{$PO}%\"\n";
        if ($vendor <> "") $where .= " and vendor like \"{$vendor}%\"\n";
        if ($typeSearch == "%") $typeSearch = "";
        if ($typeSearch <> "") $where .= " and po_type = \"{$typeSearch}\"\n";

        $pos = getInWorkPO($db, $comp, 1, $where);
        $k = 0;
        if (count($pos) > 0) foreach ($pos as $pkey => $po) {
            if (isset($tmp)) unset($tmp);
            $tmp = array();
            if (!isset($po["packing_slip"])) $po["packing_slip"] = "";
            $pos[$pkey]["searchString"] = bldBatchSearch($db1, $po["po_number"], 0, $po["packing_slip"]);
            $SQL = <<<SQL
select distinct A.batch_num,
scan_status,
packing_slip,
po_number
from RCPT_SCAN A, RCPT_INWORK B
where scan_status < 2
and packing_slip = "{$po["packing_slip"]}"
and po_number = {$po["po_number"]}
and B.batch_num = A.batch_num
and wms_po_num = po_number

SQL;
            $rc = $db->query($SQL);
            $numrows = $db->num_rows();
            $i = 1;
            while ($i <= $numrows) {
                $db->next_record();
                if ($numrows and $db->Record) {
                    $batch = $db->f("batch_num");
                    if (!isset($tmp[$batch])) {
                        foreach ($db->Record as $key => $data) {
                            if (!is_numeric($key)) {
                                $tmp[$batch]["$key"] = $data;
                            }
                        }
                    }
                    $i++;
                } // batch is not set
                // add upd the total ordered, recveived and stocked
                $tmp1 = array();
                if (isset($tmp[$batch]["po_number"])) {
                    //$tmp1=sumPO1($db1,$tmp[$batch]["po_number"],$batch,$tmp[$batch]["packing_slip"]);
                    $tmp1 = sumPO3($db1, $tmp[$batch]["po_number"], $batch, $tmp[$batch]["packing_slip"]);
                    foreach ($tmp1 as $fld => $val) {
                        if ($fld == "searchString") $searchString = $val;
                        else {
                            $tmp[$batch][$fld] = $val;
                            $pos[$pkey][$fld] = $pos[$pkey][$fld] + $val;
                        }
                    } // end foreach tmp1
                } // end batch and po is set
                $k++;
            } // while i < numrows
            $tmp2 = sumPO2($db1, $tmp[$batch]["po_number"]);
            $pos[$pkey]["totalQtyOrd"] = $tmp2["totalOrd"];
            $pos[$pkey]["totalPreRecvd"] = $tmp2["preRecvd"];
            $pos[$pkey]["totalLines"] = $tmp2["totalLines"];
            $pos[$pkey]["NumBatches"] = count($tmp);
            $pos[$pkey]["Batches"] = $tmp;
        } // end foreach pos
        if (isset($x)) unset($x);
        $x = json_encode($pos);
        if ($DEBUG) wr_log("/tmp/PO_srv.log", $x);
        echo $x;
        break;
    } // end inWorkPO1

    case "openOnPO":
    {
        $ret = array();
        $where = "1 = 1";
        if ($batch > 0) $where = "A.batch_num = {$batch}";
        if ($PO <> "") $where = <<<SQL
host_po_num = "{$PO}"
SQL;
        $SQL = <<<SQL
select count(*) as cnt,
  IFNULL(sum(qty_ord - qty_stockd),0) as units,
  IFNULL(sum(qty_ord),0) as orderd,
  IFNULL(sum(qty_stockd),0) as stockd
from
RCPT_BATCH A,
RCPT_SCAN B,
POHEADER D,
POITEMS E
where {$where}
  and B.batch_num = A.batch_num
  and D.wms_po_num = B.po_number
  and E.poi_po_num = B.po_number
  and E.shadow = B.shadow
  -- and qty_stockd <> qty_ord

SQL;

        if ($DEBUG) wr_log("/tmp/PO_srv.log", $SQL);
        $ret = array();
        $rc = $db->query($SQL);
        $numrows = $db->num_rows();
        $i = 1;
        while ($i <= $numrows) {
            $db->next_record();
            if ($numrows and $db->Record) {
                foreach ($db->Record as $key => $data) {
                    if (!is_numeric($key)) {
                        if ($numrows < 2) $ret[$key] = $data;
                        else $ret[$i]["$key"] = $data;
                    }
                }
            }
            $i++;
        } // while i < numrows
        if (isset($x)) unset($x);
        $x = json_encode($ret);
        if ($DEBUG) wr_log("/tmp/PO_srv.log", $x);
        echo $x;
        break;
    } // end openOnPO

} // end switch reqdata action

function getInWorkPO($db, $comp, $ordBy = 0, $ewhere = "")
{
    global $DEBUG;
    $groupflds = <<<SQL
packing_slip,
host_po_num,
SQL;
    $groupBy = "group by packing_slip, host_po_num";

    if ($ordBy == 1) {
        $groupflds = <<<SQL
host_po_num,
SQL;
        $groupBy = "group by vendor,host_po_num";
    } // end ordBy = 1
    $SQL = <<<SQL
select distinct
{$groupflds}
po_number,
po_type,
vendor,
DATE_FORMAT(po_date,"%m/%d/%y") as po_date,
num_lines,
DATE_FORMAT(est_deliv_date,"%m/%d/%y") as est_deliv_date,
xdock
from RCPT_SCAN A, RCPT_INWORK B, POHEADER C
where C.company = {$comp}
and A.scan_status < 2
and C.wms_po_num = A.po_number
and B.batch_num = A.batch_num
and B.wms_po_num = A.po_number
{$ewhere}
{$groupBy}
SQL;
    if ($DEBUG) wr_log("/tmp/PO_srv.log", $SQL);

    $pos = array();
    $rc = $db->query($SQL);
    $numrows = $db->num_rows();
    $i = 1;
    while ($i <= $numrows) {
        $db->next_record();
        if ($numrows and $db->Record) {
            foreach ($db->Record as $key => $data) {
                if (!is_numeric($key)) {
                    $pos[$i]["$key"] = $data;
                }
            }
        }
        $pos[$i]["qtyOrderd"] = 0;
        $pos[$i]["qtyPrecvd"] = 0;
        $pos[$i]["qtyRecvd"] = 0;
        $pos[$i]["qtyScand"] = 0;
        $pos[$i]["qtyStockd"] = 0;
        $pos[$i]["qtyShipd"] = 0;
        $pos[$i]["lineCount"] = 0;
        $i++;
    } // while i < numrows
    if (count($pos) > 0) {
        foreach ($pos as $key => $data) {
            if ($key == "est_deliv_date" and $data == "12/31/69") $pos[$key]["est_deliv_date"] = $data["po_date"];
        }
    }
    return $pos;

} // end getInWorkPO

function chkRecving($db, $comp, $rdata)
{
    /* rdata contains array of;
   {
           "host_po_num": "99591",
           "vendor": "WIX",
           "poi_po_num": "1013",
           "poi_line_num": "1",
           "shadow": "87630",
           "p_l": "WIX",
           "part_number": "24006",
           "part_desc": "Fuel Filter",
           "uom": "EA",
           "qty_ord": "12",
           "qty_recvd": "0",
           "qty_bo": "0",
           "qty_cancel": "0",
           "mdse_price": "6.970",
           "core_price": "0.000",
           "weight": "0.000",
           "volume": "0.000",
           "case_uom": "EA",
           "case_qty": "12",
           "poi_status": "0",
           "vendor_ship_qty": "0",
           "packing_slip": "",
           "tracking_num": "",
           "bill_lading": "",
           "container_id": "",
           "carton_id": "",
           "line_type": "Mdse"
   }
   */
    if (count($rdata) > 0) {
        $batches = "";
        $comma = "";
        //get_batch nums
        $SQL = <<<SQL
 select RCPT_INWORK.batch_num
 from RCPT_INWORK,RCPT_BATCH
 where wms_po_num = {$rdata[0]["poi_po_num"]}
   and RCPT_BATCH.batch_num = RCPT_INWORK.batch_num
   and batch_status < 1

SQL;

        $rc = $db->query($SQL);
        $numrows = $db->num_rows();
        $i = 1;
        while ($i <= $numrows) {
            $db->next_record();
            if ($numrows) {
                $batches .= $comma . $db->f("batch_num");
                $comma = ",";
            }
            $i++;
        } // while i < numrows

        if ($batches <> "") {
            foreach ($rdata as $key => $data) {
                $SQL = <<<SQL
 select sum(totalQty) as totalQty,
 sum(qty_stockd) as qtyStocked
 from RCPT_SCAN
 where batch_num in ({$batches})
 and po_number = {$data["poi_po_num"]}
 and po_line_num= {$data["poi_line_num"]}
 and shadow = {$data["shadow"]}
 and scan_status < 2
SQL;
                $tq = $data["qty_recvd"];
                $stockd = 0;
                wr_log("/tmp/PO_srv.log", $SQL);
                $rc = $db->query($SQL);
                $numrows = $db->num_rows();
                $i = 1;
                while ($i <= $numrows) {
                    $db->next_record();
                    if ($numrows) {
                        $tq = $tq + $db->f("totalQty");
                        $stockd = $stockd + $db->f("qtyStocked");
                    }
                    $i++;
                } // while i < numrows
                if ($tq <> 0) $rdata[$key]["qty_recvd"] = $tq;
                $rdata[$key]["qtyStocked"] = $stockd;
            } // end foreach rdata
        } // end batches <> ""
    } // end count rdata > 0
    return ($rdata);
} // end chkRecving
function sumPO($db, $ponum)
{
    $SQL = <<<SQL
select sum(qty_ord) as qOrd,
       sum(totalQty) as qRec,
       sum(scanQty) as cRec,
       sum(qty_stockd) as cStk
from POITEMS A,RCPT_SCAN B
where po_number = {$ponum}
and poi_po_num = po_number
and A.shadow = B.shadow

SQL;

    $ret = array();
    $ret["qOrd"] = 0;
    $ret["qRec"] = 0;
    $ret["cRec"] = 0;
    $ret["cStk"] = 0;
    $rc = $db->query($SQL);
    $numrows = $db->num_rows();
    $i = 1;
    while ($i <= $numrows) {
        $db->next_record();
        if ($numrows and $db->Record) {
            foreach ($db->Record as $key => $data) {
                if (!is_numeric($key)) {
                    $ret["$key"] = $data;
                }
            }
        }
        $i++;
    } // while i < numrows
    $SQL = <<<SQL
select sum(qty_ord) as qOrd
from POITEMS 
where poi_po_num = {$ponum}

SQL;
    $rc = $db->query($SQL);
    $numrows = $db->num_rows();
    $i = 1;
    while ($i <= $numrows) {
        $db->next_record();
        if ($numrows and $db->Record) {
            foreach ($db->Record as $key => $data) {
                if (!is_numeric($key)) {
                    $ret["$key"] = $data;
                }
            }
        }
        $i++;
    } // while i < numrows

    return ($ret);
}

function getRecpt($db, $rcpt)
{
    $ret = array();
    $ret["numRows"] = 0;
    $SQL = <<<SQL
 select
RCPT_SCAN.batch_num,
host_po_num,
 po_number,
line_num,
PARTS.p_l,
PARTS.part_number,
PARTS.part_desc,
 pkgUOM,
 scan_upc,
 po_line_num,
 scan_status,
 scan_user,
 pack_id,
 D.shadow,
 partUOM,
 RCPT_SCAN.line_type,
 pkgQty,
 qty_ord,
 scanQty,
 totalQty,
 timesScanned,
 qty_recvd,
 qty_stockd,
 recv_to
from RCPT_INWORK,RCPT_SCAN, PARTS, POHEADER, POITEMS D
where RCPT_INWORK.batch_num = {$rcpt}
and  RCPT_INWORK.batch_num = RCPT_SCAN.batch_num
and scan_status < 2
and shadow_number = RCPT_SCAN.shadow
and POHEADER.wms_po_num = po_number
and poi_po_num = po_number
and D.shadow = RCPT_SCAN.shadow

SQL;
    $rc = $db->query($SQL);
    $numrows = $db->num_rows();
    $i = 1;
    while ($i <= $numrows) {
        $db->next_record();
        if ($numrows and $db->Record) {
            foreach ($db->Record as $key => $data) {
                if (!is_numeric($key)) {
                    $ret[$i]["$key"] = $data;
                }
            }
        }
        $i++;
    } // while i < numrows

    $ret["numRows"] = $numrows;
    return $ret;

} // end getRecpt

function getPrice($db, $po, $po_line) // *
{
    $ret = array("cost" => 0.00, "core" => 0.00);
    $SQL = <<<SQL
select mdse_price as cost,
       core_price as core ,
       vendor
from POHEADER, POITEMS
where wms_po_num = {$po}
  and poi_po_num = wms_po_num
  and poi_line_num = {$po_line}

SQL;
    $rc = $db->query($SQL);
    $numrows = $db->num_rows();
    $i = 1;
    while ($i <= $numrows) {
        $db->next_record();
        if ($numrows) {
            $ret["cost"] = $db->f("cost");
            $ret["core"] = $db->f("core");
            $ret["vendor"] = $db->f("vendor");
        }
        $i++;
    } // while i < numrows
    return $ret;

} // end getPrice


function chkPartOnPO($db, $shadow, $POs, $qty_scanned = 1) // *
{
    $poitems = array();
    $poitems["numRows"] = 0;
    $poitems["inRecv"] = 0;
    $poitems["totalOrd"] = 0;
    $poitems["totalPrevRecvd"] = 0;
    if (count($POs) < 1 or $shadow < 1) return $poitems;
    $P = "";
    $comma = "";
    foreach ($POs as $p) {
        $P .= "{$comma}{$p}";
        $comma = ",";
    } // end foreach POs
    $wt = "=";
    $we = "";
    if (count($POs) > 1) {
        $wt = "in (";
        $we = ")";
    }
    $where = <<<SQL
where poi_po_num {$wt}{$P}{$we}
 and shadow = {$shadow}

SQL;

    $SQL = <<<SQL
select
poi_po_num,
poi_line_num,
shadow,
p_l,
part_number,
part_desc,
uom,
qty_ord,
qty_recvd,
qty_bo,
qty_cancel,
mdse_price,
core_price,
weight,
volume,
case_uom,
case_qty,
poi_status,
vendor_ship_qty,
packing_slip,
tracking_num,
bill_lading,
container_id,
carton_id,
line_type,
{$qty_scanned} as qty_scanned
from POITEMS
{$where}
order by poi_po_num,poi_line_num

SQL;

    $rc = $db->query($SQL);
    $numrows = $db->num_rows();
    $i = 1;
    while ($i <= $numrows) {
        $db->next_record();
        if ($numrows and $db->Record) {
            foreach ($db->Record as $key => $data) {
                if (!is_numeric($key)) {
                    $poitems[$i]["$key"] = $data;
                }
                if ($key == "qty_ord") {
                    $poitems["totalOrd"] = $poitems["totalOrd"] + $data;
                }
                if ($key == "qty_recvd") {
                    $poitems["totalPrevRecvd"] = $poitems["totalPrevRecvd"] + $data;
                }
            }
        }
        $i++;
    } // while i < numrows
    $poitems["numRows"] = $numrows;
    //check current open receipts for this part
    $whr = str_replace("poi_po_num", "wms_po_num", $where);
    $SQL = <<<SQL
 select sum(totalQty) as inRecv
from RCPT_INWORK,RCPT_SCAN
{$whr}
and  RCPT_INWORK.batch_num = RCPT_SCAN.batch_num
and scan_status < 2
SQL;
    $inRecv = 0;
    $rc = $db->query($SQL);
    $numrows = $db->num_rows();
    $i = 1;
    while ($i <= $numrows) {
        $db->next_record();
        if ($numrows) {
            $inRecv = $db->f("inRecv");
        }
        $i++;
    } // while i < numrows

    $poitems["inRecv"] = $inRecv;
    return $poitems;
} // end chkPartOnPO

function count_batch($db, $batch) // *
{
    $ret = 0;
    $SQL = <<<SQL
select
IFNULL(max(line_num),0) as mline
from RCPT_SCAN
where batch_num = {$batch}
SQL;

    $rc = $db->query($SQL);
    $numrows = $db->num_rows();
    $i = 1;
    while ($i <= $numrows) {
        $db->next_record();
        if ($numrows) {
            $ret = $db->f("mline");
        }
        $i++;
    } // while i < numrows
    return $ret;
} // end count_batch
function get_batch($db, $batch)  // *
{
    // args db= connection to db, batch = batchnum
    $ret = array();
    $ret["status"] = -35;
    if ($batch > 0) {
        $SQL = <<<SQL
select * from RCPT_BATCH
where batch_num = {$batch}

SQL;
        $SQL1 = <<<SQL
 select RCPT_INWORK.wms_po_num, host_po_num
 from RCPT_INWORK,POHEADER
where batch_num = {$batch}
  and POHEADER.wms_po_num = RCPT_INWORK.wms_po_num

SQL;
        $rc = $db->query($SQL);
        $numrows = $db->num_rows();
        $i = 1;
        while ($i <= $numrows) {
            $db->next_record();
            if ($numrows and $db->Record) {
                foreach ($db->Record as $key => $data) {
                    if (!is_numeric($key)) {
                        $ret["$key"] = $data;
                    }
                }
            }
            $i++;
        } // while i < numrows
        if (count($ret) > 1) $ret["status"] = 1;
        $rc = $db->query($SQL1);
        $numrows = $db->num_rows();
        $i = 1;
        while ($i <= $numrows) {
            $db->next_record();
            if ($numrows and $db->Record) {
                foreach ($db->Record as $key => $data) {
                    if (!is_numeric($key)) {
                        $ret["POs"][$i]["$key"] = $data;
                    }
                }
            }
            $i++;
        } // while i < numrows

    } // end batch > 0
    return $ret;
} // end get batch
function get_batchDetail($db, $batch, $shadow, $user) // *
{
    $ret = array();
    $SQL = <<<SQL
 select  batch_num,
         line_num,
         pkgUOM,
         scan_upc,
         po_number,
         po_line_num,
         scan_status,
         scan_user,
         pack_id,
         shadow,
         partUOM,
         line_type,
         pkgQty,
         scanQty,
         totalQty,
         timesScanned,
         recv_to,
         totalOrd,
         qty_stockd
 from RCPT_SCAN
 where batch_num = {$batch}
   and shadow = {$shadow}
--    and scan_user = {$user}

SQL;
    $rc = $db->query($SQL);
    $numrows = $db->num_rows();
    $i = 1;
    while ($i <= $numrows) {
        $db->next_record();
        if ($numrows and $db->Record) {
            foreach ($db->Record as $key => $data) {
                if (!is_numeric($key)) {
                    $ret["$key"] = $data;
                }
            }
        }
        $i++;
    } // while i < numrows
    return $ret;
} // end get_batchDetail

function setPOforPart($db, $shadow, $POs, $qty) // *
{
    $ret = array();
    $ret["po"] = 0;
    $ret["line"] = 0;
    $poitems = chkPartOnPO($db, $shadow, $POs, $qty);
//echo "<pre>";
//print_r($poitems);
//exit;
    if ($poitems["numRows"] < 1) {
        //Item not found on any of the PO's, what to do?
        //
        echo "Help, part was not found on any PO";
        exit;
    }
    if ($poitems["numRows"] > 0) { // set to po index
        foreach ($POs as $key => $p) {
            if ($p == $poitems["1"]["poi_po_num"]) $ret = $key;
        }
    } // set to po index
    return $ret;
} // end setPOforPart
function sumPO1($db, $ponum, $batchNum = 0, $ps = "")
{
    global $DEBUG;
    $where = "";
    if ($batchNum > 0) $where = " and B.batch_num = {$batchNum}\n";
    if ($ps <> "") $where .= " and C.packing_slip = \"{$ps}\"\n";
    $SQL = <<<SQL
select sum(qty_ord) as qtyOrderd,
       sum(qty_recvd) as qtyPrecvd,
       sum(totalQty) as qtyRecvd,
       sum(scanQty) as qtyScand,
       sum(qty_stockd) as qtyStockd,
       sum(vendor_ship_qty) as qtyShipd,
       count(*) as lineCount
from POITEMS A,RCPT_SCAN B, RCPT_INWORK C
where po_number = {$ponum}
and C.batch_num = B.batch_num
{$where} and poi_po_num = po_number
and A.shadow = B.shadow

SQL;
    if ($DEBUG) wr_log("/tmp/PO_srv.log", $SQL);
    $ret = array();
    $ret["qtyOrderd"] = 0;
    $ret["qtyPrecvd"] = 0;
    $ret["qtyRecvd"] = 0;
    $ret["qtyScand"] = 0;
    $ret["qtyStockd"] = 0;
    $ret["lineCount"] = 0;
    $rc = $db->query($SQL);
    $numrows = $db->num_rows();
    $i = 1;
    while ($i <= $numrows) {
        $db->next_record();
        if ($numrows and $db->Record) {
            foreach ($db->Record as $key => $data) {
                if (!is_numeric($key)) {
                    $ret["$key"] = $data;
                }
            }
        }
        $i++;
    } // while i < numrows
    return ($ret);

} // end sumPO1

function sumPO3($db, $ponum, $batchNum = 0, $ps = "")
{
    global $DEBUG;
    $where = "";
    // if ($batchNum > 0) $where=" and B.batch_num = {$batchNum}\n";
    //if ($ps <> "") $where .=" and C.packing_slip = \"{$ps}\"\n";
    // step 1, sum up PO ordered and prev received
    $SQL = <<<SQL
select sum(qty_ord) as qtyOrderd,
       sum(qty_recvd) as qtyPrecvd,
       sum(vendor_ship_qty) as qtyShipd,
       count(*) as lineCount
from POITEMS 
where poi_po_num = {$ponum}

SQL;
    if ($DEBUG) wr_log("/tmp/PO_srv.log", $SQL);
    $rdata = loadData($db, $SQL);

    // step 2, sum up all open batches
    $SQL = <<<SQL
select 
       sum(totalQty) as qtyRecvd,
       sum(scanQty) as qtyScand,
       sum(qty_stockd) as qtyStockd
from RCPT_BATCH A, RCPT_SCAN B, RCPT_INWORK C
where po_number = {$ponum}
and C.batch_num = B.batch_num
and A.batch_num = B.batch_num
and batch_status < 2

SQL;
    if ($DEBUG) wr_log("/tmp/PO_srv.log", $SQL);
    $tmp = loadData($db, $SQL);
    if (isset($tmp["qtyRecvd"])) $rdata["qtyRecvd"] = $tmp["qtyRecvd"]; else $rdata["qtyRecvd"] = 0;
    if (isset($tmp["qtyScand"])) $rdata["qtyScand"] = $tmp["qtyScand"]; else $rdata["qtyScand"] = 0;
    if (isset($tmp["qtyStockd"])) $rdata["qtyStockd"] = $tmp["qtyStockd"]; else $rdata["qtyStockd"] = 0;

    return ($rdata);

} // end sumPO3

function sumPO2($db, $ponum)
{ // get total qty ordered from PO lines
    $SQL = <<<SQL
select sum(qty_ord) as totalOrd,
       sum(qty_recvd) as preRecvd,
       count(*) as totalLines
from POITEMS
where poi_po_num = {$ponum}

SQL;
    $ret = array();
    $ret["totalOrd"] = 0;
    $ret["preRecvd"] = 0;
    $ret["totalLines"] = 0;
    $rc = $db->query($SQL);
    $numrows = $db->num_rows();
    $i = 1;
    while ($i <= $numrows) {
        $db->next_record();
        if ($numrows and $db->Record) {
            foreach ($db->Record as $key => $data) {
                if (!is_numeric($key)) {
                    $ret["$key"] = $data;
                }
            }
        }
        $i++;
    } // while i < numrows
    return ($ret);
} // end sumPO2

function bldBatchSearch($db, $ponum, $batchNum = 0, $ps = "")
{
    global $DEBUG;
    $where = "";
    if ($batchNum > 0) $where = " and B.batch_num = {$batchNum}\n";
    if ($ps <> "") $where .= " and C.packing_slip = \"{$ps}\"\n";
    // get searchString of all batches
    $SQL = <<<SQL
 select distinct B.batch_num
 from POITEMS A,RCPT_SCAN B, RCPT_INWORK C
where po_number = {$ponum}
and C.batch_num = B.batch_num
and scan_status < 2
{$where} and poi_po_num = po_number
and A.shadow = B.shadow

SQL;
    $searchString = "";
    $rc = $db->query($SQL);
    $numrows = $db->num_rows();
    $i = 1;
    while ($i <= $numrows) {
        $db->next_record();
        if ($numrows and $db->Record) {
            foreach ($db->Record as $key => $data) {
                if (!is_numeric($key)) {
                    if ($searchString <> "") $searchString .= ",";
                    $searchString .= $data;
                }
            }
        }
        $i++;
    } // while i < numrows

    // end get searchString of all batches
    return $searchString;
} // end bldBatchSearch
function chkInProc($db, $ponum)
{
    $ret = false;
    $SQL = <<<SQL
select distinct
scan_status
from RCPT_SCAN A, RCPT_INWORK B
where scan_status < 2
and po_number = {$ponum}
and B.batch_num = A.batch_num
and wms_po_num = po_number

SQL;

    $rc = $db->query($SQL);
    $numrows = $db->num_rows();
    $i = 1;
    while ($i <= $numrows) {
        $db->next_record();
        if ($numrows) {
            $ret = $db->f("scan_status");
        }
        $i++;
    } // while i < numrows
    return $ret;

} // end chkInProc

function loadData($db, $SQL)
{
    // loads array when expecting 1 record
    $rec = array();
    $rc = $db->query($SQL);
    $numrows = $db->num_rows();
    $i = 1;
    while ($i <= $numrows) {
        $db->next_record();
        if ($numrows and $db->Record) {
            foreach ($db->Record as $key => $data) {
                if (!is_numeric($key)) {
                    $ret["$key"] = $data;
                }
            }
        }
        $i++;
    } // while i < numrows

    return ($ret);

} // end loadData

function countPOLines($db, $ponum)
{
    $ret = 0;
    // the status clause filters out closed lines
    $SQL = <<<SQL
select count(*) as cnt from POITEMS
where poi_po_num = {$ponum}
and poi_status < 9

SQL;
    $w1 = $db->gData($SQL);
    if (isset($w1[1]["cnt"])) $ret = $w1[1]["cnt"];
    return $ret;
} // end countPOLines

function chkRcptTote($db, $batch, $toteId)
{
    $SQL = <<<SQL
select count(*) as cnt
from RCPT_TOTE
where rcpt_num    = {$batch}
  and tote_id     = {$toteId}

SQL;
    $w1 = $db->gData($SQL);
    if (!isset($w1[1])) {
        $SQL = <<<SQL
  insert into RCPT_TOTE
(rcpt_num,tote_id,rcpt_status,last_zone,last_loc,target_zone,target_aisle )
values ({$batch},{$totaId},0,"RCV","","",0)
SQL;
        $db->Update($SQL);
    }
    return;
} // end chkRcptTote
function chkWHSEQTY($db, $comp, $shadow)
{
    // adds a record for the part if it doesn't exist (ie, the first receiving)
    $SQL = <<<SQL
insert ignore into WHSEQTY (ms_shadow,ms_company) values ({$shadow},{$comp});
SQL;
    $rc = $db->Update($SQL);
    return true;
} // end chkWHSEQTY
?>
