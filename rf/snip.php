<?php

function chkPart($pnum, $comp)
{
    global $main_ms;
    $ret = array();
    $ret["upc"] = $pnum;
    $ret["comp"] = $comp;
    $pr = new PARTS;
    $pnum = trim($pnum);
    $a = $pr->lookup($pnum);
    if (count($a) == 1) $ret = $pr->Load($a[1]["shadow_number"], $main_ms);
    $ret["status"] = $pr->status;
    $ret["numRows"] = count($pr->status);
    if ($pr->status > 1) {
        $ret = $a;
        $ret["numRows"] = $pr->status;
        $ret["status"] = $pr->status;
    } else {
        $ret["Result"] = $a[1];
        $ret["Part"] = $pr->Data;
        $ret["ProdLine"] = $pr->ProdLine;
        $ret["WhseQty"] = $pr->WHSEQTY;
        $ret["Alternates"] = $pr->Alternates;
    }
    unset($pr);
    return ($ret);
}

function get_part($db, $pnum_in)
{
    $ret = array();
    $ret["status"] = 0;
    $ret["num_rows"] = 0;
    $i = 0;
    $SQL = <<<SQL
SELECT alt_part_number,alt_type_code, alt_uom,
 shadow_number,
 p_l,
 part_number,
 unit_of_measure,
 shadow_number
 part_desc,
 part_long_desc, 
 part_seq_num,
 part_category,
 part_class
 part_subline,
 part_group,
 part_returnable, 
 serial_num_flag,
 special_instr,
 hazard_id,
 kit_flag,
 cost,
 core,
 core_group 
 FROM ALTERNAT,PARTS
 WHERE alt_part_number like "{$pnum_in}"
 AND  shadow_number = alt_shadow_num
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
    $ret["num_rows"] = $numrows;
    if ($ret["num_rows"] == 0) {
        $ret["status"] = -35;
    }
    return ($ret);
} // end get_part

function get_whsqty($db, $comp, $shadow)
{
    $ret = array();
    $SQL = <<<SQL
 select 
 primary_bin as whse_location,
 qty_avail,
 qty_alloc,
 qty_on_order,
 qty_on_vendbo,
 qty_on_custbo,
 qty_defect,
 qty_core
 from WHSEQTY
 where ms_shadow = $shadow
 and ms_company = $comp

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
} // end get_whsqty


$title = "Template";
$inc = "../assets/css";
$viewport = "0.75";


function chkPartOnPO($db, $shadow, $POs, $qty_scanned = 1)
{
    $poitems = array();
    $poitems["numRows"] = 0;
    $poitems["inRecv"] = 0;
    if (count($POs) < 1 or $shadow < 1) return ($poitems);
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
and scan_status < 1
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
    return ($poitems);
} // end chkPartOnPO

function count_batch($db, $batch)
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
    return ($ret);
} // end count_batch
function get_batch($db, $batch)
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
    return ($ret);
} // end get batch
function get_batchDetail($db, $batch, $shadow, $user)
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
         recv_to
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
    return ($ret);
} // end get_batchDetail
?>
