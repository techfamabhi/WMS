<?php
/*
*/

$wmsInclude = "/var/www/wms/include";
require_once("{$wmsInclude}/get_table.php");
require_once("{$wmsInclude}/cl_addupdel.php");
require_once("{$wmsInclude}/cl_inv.php");
require_once("{$wmsInclude}/wr_log.php");

/* Vars in input array -- all required

   "action"=>"recvReciept",
   "batch"=>$_SESSION["rf"]["RECEIPT"],
   "RECEIPT"=>$_SESSION["rf"]["RECEIPT"],
   "userId"=>$_SESSION["wms"]["UserID"],
   "recvType"=>$_SESSION["rf"]["recvType"],
   "recvTo"=>$_SESSION["rf"]["recvTo"],
   "POs"=>$POs,
   "HPO"=>$HPO,
   "vendor"=>$vendor,
   "shadow"=> $shadow,
   "comp"=> $comp,
   "UPC"=> $UPC,
   "PPL"=> $PPL,
   "PPN"=> $PPN,
   "PPD"=> $PPD,
   "partUOM"=> $partUOM,
   "pkgUOM"=> $pkgUOM,
   "pkgQty"=> $pkgQty,
   "totalQty"=> $tqty,
   "prefZone"=> $prefZone,
   "qtyRecvd"=> $qtyRecvd,
   "BinTote"=>$scaninput
*/


//Start -----------------------------------------------------------------------

// good part, good PO, good bin, save it
if (isset($batch)) {
    wr_log("/tmp/testfunc.txt", "get_batch({$batch});");
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
        $return_code = $upd->updRecord($rqdata, "RCPT_BATCH", $where);
        //echo "batch return_code={$return_code}\n";

        foreach ($POs as $key => $po) {
            unset($rqdata);
            $rqdata = array();
            $rqdata["wms_po_num"] = $po;
            $rqdata["batch_num"] = $batch;
            $rqdata["action"] = 2;
            $where = "where wms_po_num = {$po} and batch_num = {$batch}";
            $return_code = $upd->updRecord($rqdata, "RCPT_INWORK", $where);
            //echo "inwork return_code={$return_code}\n";
        }
    } // batch does not exist yet
    //add batch detail
    unset($rqdata);
    $rqdata = array();
    $theUser = $userId;
    wr_log("/tmp/testfunc.txt", "get_batchDetail({$batch},{$shadow},{$theUser});");
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
        if ($recvTo == 'b') $stockd = ($qtyRecvd * $pkgQty);


        $hpo = $HPO[$whichPO];
        wr_log("/tmp/testfunc.txt", "count_batch({$batch});");
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
        $rqdata["totalQty"] = ($qtyRecvd * $pkgQty);
        $rqdata["timesScanned"] = 1;
        $rqdata["recv_to"] = $recvTo;
        $rqdata["qty_stockd"] = $stockd;
        $rqdata["totalOrd"] = $qtyOrd;
        $rqdata["action"] = 2;
        $where = <<<SQL
 where batch_num = {$batch}
   and shadow = {$shadow}
   and scan_user = {$theUser}

SQL;
        $return_code = $upd->updRecord($rqdata, "RCPT_SCAN", $where);
    } //add new record
    else { // update qty in scan record
        $theUser = $userId;
        $rqdata = $w;
        $where = <<<SQL
 where batch_num = {$batch}
   and shadow = {$shadow}
   and scan_user = {$theUser}

SQL;
        $stockd = 0;
        if ($recvTo == 'b') $stockd = ($qtyRecvd * $pkgQty);

        $rqdata["scanQty"] = $w["scanQty"] + $qtyRecvd;
        $rqdata["totalQty"] = ($rqdata["scanQty"] * $pkgQty);
        $rqdata["timesScanned"] = $w["timesScanned"] + 1;
        $rqdata["qty_stockd"] = $w["qty_stockd"] + $stockd;
        $return_code = $upd->updRecord($rqdata, "RCPT_SCAN", $where);
    } // update qty in scan record
    $save_RCPTSAN = $rqdata;
    $save_RCPTSCAN_where = $where;

    $whichPO = setPOforPart($db, $shadow, $POs, $qtyRecvd);
    $po = $POs[$whichPO];
    $hpo = $HPO[$whichPO];
    $qty = ($qtyRecvd * $pkgQty);
    $binType = substr($opt[21], 0, 1);
    if ($recvTo == "b") { // recv to Bin, update WHSEQTY and add PARTHIST
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
                "floc" => $theBin,
                "tloc" => "Received",
                "inv_code" => "0",
                "mdse_price" => $tmp["cost"],
                "core_price" => $tmp["core"],
                "in_qty_core" => 0,
                "in_qty_def" => 0,
                "bin_type" => $binType
            );

            $trans = new invUpdate;
            $rc = $trans->updQty($sparams1); // 1=success, 0=failed

            //Do something on failure *********************************************
        } // end 2 == 2
        if ($rc > 0) { // update inv was successful
            $rqdata["scan_status"] = 1;
            $return_code = $upd->updRecord($rqdata, "RCPT_SCAN", $where);
        } // update inv was successful

    } // recv to Bin, update WHSEQTY and add PARTHIST
    else { // ********************* Recv to Tote *****************************
        //add tote to session
        $lasttote = 0;
        if (isset($totes)) {
            if (!isset($totes[$theBin]))
                $totes[$theBin] = $theBin;
        } // totes are set
        else { // totes are not set
            $totes[$theBin] = $theBin;
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
            $prefi = sprintf("%02d", $prodline["pl_perfered_aisle"]);
        }

//add tote to RCPT_TOTE with bincls->updRcptTote($req)
        $req = array(
            "rcpt_num" => $batch,
            "tote_id" => $theBin,
            "rcpt_status" => 0,
            "last_zone" => "RCV",
            "last_loc" => "",
            "target_zone" => $prefz,
            "target_aisle" => $prefi
        );
//print_r($req);
        $rc = $bincls->updRcptTote($req);

//add part to totedtl,
        $rc1 = $bincls->addItemToTote($theBin, $shadow, $qtyRecvd, $partUOM);

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
            "floc" => $theBin,
            "tloc" => "Received",
            "inv_code" => "0",
            "mdse_price" => 0.00,
            "core_price" => 0.00,
            "in_qty_core" => 0,
            "in_qty_def" => 0,
            "bin_type" => $binType
        );
        $trans = new invUpdate;
        $rc2 = $trans->updQty($req, false);
    } // * END *************** Recv to Tote *****************************

    $msg = "Last: {$PPL} {$PPN} {$PPD} Qty: {$qtyRecvd} to: {$BinTote}";
}
if (!isset($msg)) $msg = "";

// good part, good PO, good bin, save it

function getPrice($db, $po, $po_line) // *
{
    $ret = array("cost" => 0.00, "core" => 0.00);
    $SQL = <<<SQL
select mdse_price as cost,
       core_price as core 
from POITEMS
where poi_po_num = {$po}
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
select count(*) as cnt
from RCPT_SCAN
where batch_num = {$batch}
SQL;

    $rc = $db->query($SQL);
    $numrows = $db->num_rows();
    $i = 1;
    while ($i <= $numrows) {
        $db->next_record();
        if ($numrows) {
            $ret = $db->f("cnt");
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
 select	 batch_num,
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
   and scan_user = {$user}

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

?>
