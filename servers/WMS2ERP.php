<?php

// WMS2ERP.php -- Server for Updating and sending Data back to Host
// 05/12/22 dse initial
// 06/22/22 dse change paud_bin to paud_floc , NEED TO ADD paud_tloc
// 07/22/22 dse rename to WMS2ERP and add Receipts
// 01/26/23 dse correct loop in write_output
// 07/28/23 dse add send to PW service
// 10/12/23 dse correct zero picked items so they are updated
// 10/12/23 dse correct error when line items data when host lines are missing
// 07/26/24 dse correct undefined var ic on text output

/*
TODO
 for reveiving
  if stat is done don't allow reprocess unless it's set to re-export
  if re-export, don't update PO qtys (status = 8)
  update PO status to partially received
  add insert into new HRECPTS and DRECPTS 

 add function after exporting to see if PO is completly received,
 if so, update the po status to complete

 *) update Line Items with qty picked
 *) update WHSEQTY, de-allocate all shipped items
 *) insert PARTHIST
 *) update statistics 
 *) update order header status and date times
 *) export host order file to export directory
 *) remove from order que
 future
 *) track shipped container by customer for charging later is not returned
 *) ...

left off, 
Probably should move un-allocation from main section to function unAllocate
*/


require("srv_hdr.php");
require_once("cl_ORDERS.php");
require("{$wmsDir}/config_comm.php");
require_once("getUser.php");
require_once("updPoStat.php");
require_once("{$wmsDir}/include/get_contrl.php");
require_once("{$wmsDir}/include/cl_pwpost.php");
require_once("{$wmsDir}/include/cl_inv.php");

$DEBUG = true;
$comp = 1;
$db1 = new WMS_DB;
$logfile = "/tmp/wms2erp.log";
if ($DEBUG) {
    wr_log($logfile, "Program={$_SERVER["PHP_SELF"]}");
    wr_log($logfile, "inputData:\n{$inputdata}");
}

$action = $reqdata["action"];
if (isset($reqdata["company"])) $comp = $reqdata["company"]; else $comp = 1;

// Parse Arguments from array
// Add new arguments to initialize to the posArgs array
$posArgs = array(
    "order_num" => 0,
    "batch_num" => 0,
    "toteId" => 0,
    "container" => 0
);

//parse args in posArgs
foreach ($posArgs as $var => $typ) {
    $defVal = 0;
    if ($typ == 1) $defVal = "";
    if ($typ == 2) $defVal = date("Y/m/d h:i:s");
    if ($typ == 3) $defVal = null;
    if (isset($reqdata[$var])) $$var = $reqdata[$var]; else $$var = $defVal;
} // end foreach posArgs
// End Parse Arguments from array
if (isset($reqdata["override"])) $override = $reqdata["override"]; else $override = 0;

// payload for invAdj
if (isset($reqdata["userId"])) $userId = $reqdata["userId"]; else $userId = 0;
if (isset($reqdata["shadow"])) $shadow = $reqdata["shadow"]; else $shadow = 0;
if (isset($reqdata["comp"])) $comp = $reqdata["comp"]; else $comp = 1;
if (isset($reqdata["Qty"])) $Qty = $reqdata["Qty"]; else $Qty = 0;
if (isset($reqdata["Bin"])) $Bin = $reqdata["Bin"]; else $Bin = "";
if (isset($reqdata["pl"])) $pl = $reqdata["pl"]; else $pl = "";
if (isset($reqdata["partNumber"])) $partNumber = $reqdata["partNumber"]; else $partNumber = "";
if (isset($reqdata["reasonCode"])) $reasonCode = $reqdata["reasonCode"]; else $reasonCode = "";
if (isset($reqdata["reasonText"])) $reasonText = $reqdata["reasonText"]; else $reasonText = "";
if (isset($reqdata["uom"])) $uom = $reqdata["uom"]; else $uom = "";
if (isset($reqdata["binType"])) $binType = $reqdata["binType"]; else $binType = "";
if (isset($reqdata["primaryBin"])) $primaryBin = $reqdata["primaryBin"]; else $primaryBin = "";
if (isset($reqdata["qtyCore"])) $qtyCore = $reqdata["qtyCore"]; else $qtyCore = 0;
if (isset($reqdata["qtyDef"])) $qtyDef = $reqdata["qtyDef"]; else $qtyDef = 0;
if (isset($reqdata["invCode"])) $invCode = $reqdata["invCode"]; else $invCode = 0;
if (isset($reqdata["mdse"])) $mdse = $reqdata["mdse"]; else $mdse = 0;
if (isset($reqdata["core"])) $core = $reqdata["core"]; else $core = 0;
if (isset($reqdata["oldQty"])) $oldQty = $reqdata["oldQty"]; else $oldQty = 0;
if (isset($reqdata["newQty"])) $newQty = $reqdata["newQty"]; else $newQty = 0;

//if (isset($reqdata["order_num"])) $order_num=$reqdata["order_num"]; else $order_num=0;

if ($DEBUG) wr_log($logfile, "Function: {$action}");
$rc = array();
$goodaction = false;
switch ($action) {
    case "releaseTote":
    {
        /* Args
         comp,
         order_num,
         tote_id,
         container 0=none else container to use

        */
        $okToRelease = false;
        $goodaction = true;
        if (!isset($container)) $container = 0;
        if ($order_num < 1) exit;
        $where = "";
        $ord = new ORDERS;
        $ord->load($order_num);
        if (isset($ord->Order["order_num"]) and $ord->Order["order_stat"] < 4) {
            $order_num = $ord->Order["order_num"];
            // check if tote is valid for this order
            $SQL = <<<SQL
select tote_status from ORDTOTE
where order_num = {$ord->Order["order_num"]}
  and tote_id = {$toteId}

SQL;
            $tstat = -1;
            $rc = $db->query($SQL);
            $numrows = $db->num_rows();
            $i = 1;
            while ($i <= $numrows) {
                $db->next_record();
                if ($numrows) {
                    $tstat = $db->f("tote_status");
                }
                $i++;
            } // while i < numrows
            $msg = "";
            if ($tstat < 0) {
                $msg = "Tote is not for Order {$order_num}";
            } // end tstat < 0
            // check if tote has parts
            $SQL = <<<SQL
 select count(*) as cnt from TOTEDTL
 where tote_id = {$toteId}

SQL;
            $cnt = loadCnt($db, $SQL);
            if ($cnt < 1) $msg = "Tote has no Items in it";

            if ($msg <> "") {
                $rdata = array('status' => 200, "message" => $msg);
                $x = json_encode($rdata);
                if ($DEBUG) wr_log($logfile, "Response:\n{$x}");
                echo $x;
                exit;
            } // end msg <> ""
            // Release Tote from Order put into container
            // get container# if not set
            if ($container < 1) $container = get_contrl($db, 0, "CONTAINR");
            $rdata["Container"] = $container;
            // move the parts from TOTEDTL to the container and delte and free the tote
            $SQL = <<<SQL
 select 
tote_item,
tote_shadow,
tote_qty,
tote_uom
from TOTEDTL
where  tote_id = {$toteId}

SQL;
            $toteDtl = $db->gData($SQL);
            if (count($toteDtl) > 0) {
                $nextline = 0;
                foreach ($toteDtl as $l => $t) {
                    $nextline++;
                    $uSQL = <<<SQL
 insert into ORDPACK
 (order_num, carton_num, line_num, shadow, qty, uom )
 values (
 {$order_num}, $container, $nextline, {$t["tote_shadow"]}, {$t["tote_qty"]}, "{$t["tote_uom"]}" 
 )

SQL;
                    $rc = $db->Update($uSQL);
                    $dSQL = "";
                    if ($rc > 0) $dSQL = <<<SQL
delete from TOTEDTL 
where tote_id = {$toteId}
and tote_item = {$t["tote_item"]}
and tote_shadow = {$t["tote_shadow"]}

SQL;
                    if ($dSQL <> "") $rcd = $db->Update($dSQL);
                } // end foreach toteDtl
                $SQL = <<<SQL
update ORDTOTE set tote_status = 1
where order_num = {$order_num}
  and tote_id = {$toteId}

SQL;
                $rct = $db->Update($SQL);
                $rct = resetTote($db, $toteId);
            } // end toteDtl > 0

            // check rest of totes, if all released, Check ITEMPULL,
            $SQL = <<<SQL
select count(*) as cnt from ORDTOTE 
where order_num = {$order_num}
and tote_status < 1
SQL;
            if (loadCnt($db, $SQL) < 1) $okToRelease = true;

            // if all pickable items are picked, set order status to ready to ship (4)
            // see if all the items are picked
            $stillStuffToPick = true;
            //check Itempull, see if order still has pickable items
            $SQL = <<<SQL
select count(*) as cnt
from ITEMPULL
where ord_num = {$order_num}
and qty_picked < qtytopick

SQL;
            if (loadCnt($db, $SQL) < 1) $stillStuffToPick = false;

            if ($okToRelease and !$stillStuffToPick) {
                $SQL = <<<SQL
update ORDERS
set order_stat = 4
where order_num = {$ord->Order["order_num"]}

SQL;
                $ord->Order["order_stat"] = 4;
                if ($DEBUG) wr_log($logfile, "SQL:\n{$SQL}");
                $rdata["OrdStat"] = $db->Update($SQL);
                // delete the totes from ORDTOTE since they all got moved to containers
                $SQL = <<<SQL
 delete from ORDTOTE
where order_num = {$ord->Order["order_num"]}
SQL;
                $rcot = $db->Update($SQL);
            } // end okToRelease
        } // end order_num isset

        if (isset($x)) unset($x);
        $x = json_encode($rdata);
        if ($DEBUG) wr_log($logfile, "Response:\n{$x}");
        echo $x;
        break;

    } // end releaseTote

    case "Ship":
    {
        $goodaction = true;
        if ($order_num < 1) exit;
        $where = "";
        $ord = new ORDERS;
        $ord->load($order_num);


//| ord_num | line_num | shadow | p_l | part_number | part_desc        | uom | qty_ord | qty_ship | qty_bo | qty_avail | min_ship_qty | case_qty | inv_code | line_status | hazard_id | zone | whse_loc  | qty_in_primary | num_messg | part_weight | part_subline | part_category | part_group | part_class | item_pulls | specord_num | inv_comp |
        if (isset($ord->Order["order_num"])) { // got an order
// check order status, make sure it's not finaliazed yet
            /* -1 awaiting product,
                0=Open,
                1=scheduling,
                2=in proc,
                3=Pack,
                4=ready to ship,
                5=reserved,
                6=sent,
                7=compl,
                9=done/delete
            */

            if ($ord->Order["order_stat"] < 4) {
                if ($override > 0) { // override, set status to 4
                    $ord->Order["order_stat"] = 4;
                } // end override > 0
                else { // override = 0
                    //header('HTTP/1.1 200 Order is still is process');
                    $rdata = array('status' => 200, "message" => "Order is still is process");
                    $x = json_encode($rdata);
                    if ($DEBUG) wr_log($logfile, "Response:\n{$x}");
                    echo $x;
                    exit;
                } // override = 0
            } // end order stat < 4
            if ($ord->Order["order_stat"] == 4) { // stat = 4, set ship qty, adjust alloc, book parthist and set status to 5
                // step 1, set status to 5 (being updated)
                $SQL = <<<SQL
update ORDERS
set order_stat = 5,
wms_complete = NOW()
where order_num = {$ord->Order["order_num"]}

SQL;
                $ord->Order["order_stat"] = 5;
                if ($DEBUG) wr_log($logfile, "SQL:\n{$SQL}");
                $rc["OrdStat"] = $db->Update($SQL);
                // step 2, get all itempull records in an array by Item line #
                $pulled = array();
                if (count($ord->ItemPull) > 0) {
                    foreach ($ord->ItemPull as $key => $item) {
                        $l = $item["line_num"];
                        if (isset($pulled[$l])) { // already set add qty to existing entry
                            $pulled[$l]["qty"] = $pulled[$l]["qty"] + $item["qty_picked"];
                        } // already set add qty to existing entry
                        else { // not set, set pulled array
                            $pulled[$l]["shadow"] = $item["shadow"];
                            $pulled[$l]["qty2Pick"] = $item["qtytopick"];
                            $pulled[$l]["qty"] = $item["qty_picked"];
                            $pulled[$l]["uom"] = $item["uom_picked"];
                            $pulled[$l]["bin"] = $item["whse_loc"];
                            $pulled[$l]["user"] = $item["user_id"];
                            $pulled[$l]["zero_picked"] = $item["zero_picked"];
                        } // not set, set pulled array
                    } // end foreach itempull
                    //update Items
                    if (count($ord->Items)) {
                        foreach ($ord->Items as $key => $item) {
                            $l = $item["line_num"];
                            if (isset($pulled[$l])) { // item has been pulled
                                $updI = false;
                                // check if qty matched
                                if ($item["qty_ship"] <> $pulled[$l]["qty"]) $updI = true;
                                // check if bin matches
                                if ($item["whse_loc"] <> $pulled[$l]["bin"]) $updI = true;
                                $ord->Items[$key]["qty_ship"] = $pulled[$l]["qty"];
                                $items["qty_ship"] = $pulled[$l]["qty"];
                                if (intval($pulled[$l]["qty"]) < $item["qty_ship"]) {
                                    // remmed out, cuasing zero picked items to not be updated
                                    //$updI=false;
                                    $pulled[$l]["qty"] = 0;
                                    if ($pulled[$l]["zero_picked"] > 0) { // zero picked, update ITEMPULL
                                        // TODO Add to zero or picking exception log somewhere
                                        $SQL = <<<SQL
 update ITEMPULL
set qtytopick = {$pulled[$l]["qty"]}
where ord_num = {$item["ord_num"]}
and line_num = {$item["line_num"]}

SQL;
                                        if ($DEBUG) wr_log($logfile, "SQL:\n{$SQL}");
                                        $rc = $db->Update($SQL);
                                    } // zero picked, update ITEMPULL
                                } // pulled qty < qty_ship
                                if ($updI) {

// Note: changing qty will change allocation, 
// however, changing allocation will not, if we change allocation
// (because we shipped it), we must add PARThist record
// indicating we shipped it
                                    $SQL = <<<SQL
update ITEMS
set qty_ship = {$pulled[$l]["qty"]},
    whse_loc = "{$pulled[$l]["bin"]}",
    line_status = line_status + 1
where ord_num = {$item["ord_num"]}
and line_num = {$item["line_num"]}

SQL;
                                    if ($DEBUG) wr_log($logfile, "SQL:\n{$SQL}");
                                    $rc["Items"] = $db->Update($SQL);
                                } // end updI
                            } // item has been pulled
                            else { // item hasnt been pulled
                                if ($item["qty_ship"] <> 0) // set it to 0
                                {
                                    $SQL = <<<SQL
update ITEMS
set qty_ship = 0
where ord_num = {$item["ord_num"]}
and line_num = {$item["line_num"]}

SQL;
                                    if ($DEBUG) wr_log($logfile, "SQL:\n{$SQL}");
                                    $rc["Items"] = $db->Update($SQL);
                                } // end set it to 0
                            } // item hasnt been pulled
                            if (intval($item["line_status"]) == 0) { // set line stat to 1
                                $SQL = <<<SQL
update ITEMS
set line_status = 1
where ord_num = {$item["ord_num"]}
and line_num = {$item["line_num"]}

SQL;
                                if ($DEBUG) wr_log($logfile, "SQL:\n{$SQL}");
                                $rc["Items"] = $db->Update($SQL);

                            } // set line stat to 1
                            $SQL = <<<SQL
update WHSEQTY
set qty_alloc = qty_alloc - {$pulled[$l]["qty"]}
where ms_company = {$item["inv_comp"]}
and ms_shadow = {$item["shadow"]}

SQL;

                            if ($DEBUG) wr_log($logfile, "SQL:\n{$SQL}");
                            $rc["WhseQty"] = $db->Update($SQL);
                            $otype = "PIC";
                            if ($ord->Order["order_type"] == "D") $otype = "DM";
                            if ($ord->Order["order_type"] == "T") $otype = "OTR";
                            $rc["PartHist"] = writeParthist($db, $item, $ord->Order["company"], $ord->Order["host_order_num"], $ord->Order["cust_po_num"], $otype, $ord->Order["customer_id"], $pulled[$l]["user"]);
                        } // end foreach items
                    } // end count items > 0
                } // end there is itempulls
            } // stat = 4, set ship qty and set status to 5

// check if export is done, if not, generate it
        } // got an order
        else exit; // probably need to log order not found

        if ($DEBUG) wr_log($logfile, "Export and set stat to 6, status={$ord->Order["order_stat"]}:\n");
        if ($ord->Order["order_stat"] == 5) { // export it and set status to 6
            $output = format_output($db, $ord);
            $rc["fileWrite"] = write_output($ord->Order["host_order_num"], $output);
            $SQL = <<<SQL
update ORDERS
set order_stat = 6,
wms_complete = NOW()
where order_num = {$ord->Order["order_num"]}

SQL;
            $ord->Order["order_stat"] = 6;
            if ($DEBUG) wr_log($logfile, "SQL:\n{$SQL}");
            $rc["OrdStat6"] = $db->Update($SQL);


        } // export it and set status to 6
        $rdata = $rc;
        if (isset($x)) unset($x);
        $x = json_encode($rdata);
        if ($DEBUG) wr_log($logfile, "Response:\n{$x}");
        echo $x;
        break;
    } // end ship

    case "Cancel":
    case "Delete":
    {
        $goodaction = true;
    } // end cancel and delete
    case "flagRcptDone":
    {
        $goodaction = true;
        $brc = -1;
        $rdata = array();
        if ($batch_num > 0) {
            $brc = chkBatchStatus($db, $batch_num);
            $upd = false;
            $msg = "";
            switch ($brc) {
                case 0: // ok
                    $upd = true;
                    break;
                case 1: // already flagged
                    $msg = "Already flagged as done";
                    break;
                case 6: // export is currently running
                    $msg = "Export is currently running";
                    break;
                case 7: // export is done
                    $msg = "Export has already been done";
                    break;
                case 8: // re-run export but dont update PO
                    $upd = false;
                    break;
                case 9: //flagged for delete
                    $msg = "Batch is already complete";
                    break;
                case -2: // Received but not putaway
                    $msg = "Putaway has not been Completed";
                    break;
                default: // < 0, batch not found
                    $msg = "Batch not found";
                    break;
            } // end switch brc
            if ($upd) { // update record
                $brc = updBatchStatus($db, $batch_num, 1); // set status to complete
                $msg = "Batch Updated";
            } // update record
            else $rdata["error"] = $brc;

            $rdata["message"] = $msg;
            if (isset($x)) unset($x);
            $x = json_encode($rdata);
            if ($DEBUG) wr_log($logfile, "Response:\n{$x}");
            echo $x;
            break;
        } // end batch # > 0
    } // end flagRcptDone

    case "invAdj":
    {
        /*
         request payload to here
         Array
      (
          [action] => invAdj
          [userId] => 1
          [comp] => 1
          [shadow] => 87630
          [pl] => WIX
          [partNumber] => 24006
          [Bin] => A-02-03-C
          [Qty] => 1
          [reasonCode] => A
          [reasonText] => Inventory Correction
          [uom] => EA
          [invCode] => 0
          [mdse] => 6.970
          [core] => 0.000
          [qtyCore] => 0
          [qtyDef] => 0
          [binType] => P
          [oldQty] => 3
          [newQty] => 4
          [primaryBin] => A-02-03-C

      )

      JSON for inventory adjustment
         {"ADJ": [{
              "taskId": 100,
              "DateTime": "06\/09\/2023 03:13:48",
              "userId": "Dave Pike", "company": 1,
              "PL": "WIX",
              "PartNumber":"51515",
              "PartUOM": "EA",
              "Bin": "A-02-03C", "Qty": 1,
              "Lot": "",
              "MdseType": " ",
              "PrevMdseType": " ",
              "Reason": "Cycle Count"
             }]
         }

         get users name
         Check to make sure everything makes sense, then update Inventory,
         adjust WHSELOC, WHSEQTY and insert parthist

        */

        $rdata = array();
        $userIdee = getUser($db, $userId, true);

        // set up a task
        $task_id = get_contrl($db, 0, "TASK");
        $task_date = date("Y/m/d h:i:s");
        $rowData = array();
        $SQL = <<<SQL
    insert into TASKS
    (task_id, task_type, task_date, task_status, id_num, user_id, tote_id,
     last_zone, last_loc, target_zone, target_aisle, start_time, end_time)
 values ({$task_id}, "ADJ", "{$task_date}", 9, 0, {$userId}, "{$Bin}",
         " "," "," ", 0, "{$task_date}", NOW())

SQL;
        if ($DEBUG) wr_log($logfile, "SQL:\n{$SQL}");
        $rc_db = $db->Update($SQL);
        // end task
        // use cl_inv class
        $sparams1 = array(
            "wms_trans_id" => 0,
            "shadow" => $shadow,
            "company" => $comp,
            "psource" => "",
            "user_id" => $userId,
            "host_id" => "Adjust",
            "ext_ref" => $reasonText,
            "trans_type" => "ADJ",
            "in_qty" => $Qty,
            "uom" => $uom,
            "floc" => $Bin,
            "tloc" => "Adjustment",
            "inv_code" => $invCode,
            "mdse_price" => $mdse,
            "core_price" => $core,
            "in_qty_core" => $qtyCore,
            "in_qty_def" => $qtyDef,
            "bin_type" => $binType,
            "old_qoh" => $oldQty,
            "primary_bin" => $primaryBin
        );

        $trans = new invUpdate;
        $rc = $trans->updQty($sparams1); // 1=success, 0=failed
        if ($rc > 0) { // update inv was successful
            $rdata["status"] = "success";
            $rdata["invUpd"] = $rc;
            $rdata["task"] = $rc_db;
        } // update inv was successful
        else { // update failed
            $rdata["status"] = "fail";
            $rdata["invUpd"] = $rc;
            $rdata["task"] = $rc_db;
        }  // update failed

        $ic = " ";
        if ($invCode == 1) $ic = "C";
        if ($invCode == 2) $ic = "D";
        $hostReason = getHostReason($db, $reasonCode);
        if (strtoupper($outType) == "JSON") {
            // loop this if more than 1 adjustment
            $i = 0;
            $jsonData = array("ADJ" => array(
                $i => array(
                    "taskId" => $task_id,
                    "DateTime" => $task_date,
                    "userId" => $userIdee,
                    "company" => $comp,
                    "PL" => $pl,
                    "PartNumber" => $partNumber,
                    "PartUOM" => $uom,
                    "Bin" => $Bin,
                    "Qty" => $Qty,
                    "Lot" => "",
                    "MdseType" => $ic,
                    "PrevMdseType" => $ic,
                    "Reason" => $hostReason
                )
            )
            );
            $textData = json_encode($jsonData);
            // send data here
            $pw = new TPOST;
            if ($DEBUG) wr_log($logfile, "Sending Adjust:\n{$textData}");
            $rcc = $pw->Send("Adjust", $textData);
            $rccc = json_encode($rcc);
            if ($DEBUG) wr_log($logfile, "Adjust Response:\n{$rccc}");
            //append response to textData
            $oo = "{$textData}\nResult\n{$rccc}";
            // later write file with both out and result
            unset($pw);
        } else { // not json
            $textData = "ADJ|{$taskId}|{$task_date}|{$userIdee}|{$comp}|{$pl}|{$partNumber}|{$uom}|{$bin}|{$Qty}| |{$ic}|{$ic}|{$hostReason}\n";
        } // not json

        $totalRecords = 0;
        $totalQty = 0;
        $fname = buildFilename("Adjust", $task_id);
        $bfname = $fname;
        $ok = false;
        $k = 1;
        while (!$ok) {
            if (file_exists($fname)) {
                $fname = "{$bfname}_{$k}";
                $k++;
            } else $ok = true;
        } // end not ok
        $rc = file_put_contents($fname, $textData, LOCK_EX);


        $rdata["filename"] = basename($fname);
        $rdata["bytes"] = $rc;
        $x = json_encode($rdata);
        if ($DEBUG) wr_log($logfile, "Response:\n{$x}");
        echo $x;
    } // end invAdj

    case "Receive":
    {
        $goodaction = true;
        if ($batch_num > 0) {
            $brc = chkBatchStatus($db, $batch_num);
            if ($brc < 1) // batch is not flagged as done receiving
            {
                $j = '{"status": -1, "text":"Batch is still being received"}';
                if ($brc == -2) $j = '{"status": -2, "text":"Batch is still being put away"}';
                echo $j;
                exit;
            } // brc
            $SQL = <<<SQL
select 
A.batch_num,
line_num,
po_line_num,
DATE_FORMAT(batch_date,"%m/%d/%y") as batch_date,
batch_status,
scan_status,
po_number,
company,
po_type,
vendor,
bo_flag,
host_po_num,
scan_user,
scan_upc,
pkgUOM,
B.shadow,
p_l,
part_number,
scanQty,
totalQty,
qty_stockd,
pack_id,
partUOM,
line_type,
timesScanned,
recv_to

from 
RCPT_BATCH A,
RCPT_SCAN B,
POHEADER D,
PARTS 
where A.batch_num = {$batch_num}
  and B.batch_num = A.batch_num
  and D.wms_po_num = B.po_number
  and shadow_number = B.shadow
SQL;
//Need to check the rcpt_status, then update it to updating
// at end set it to sent to ERP

            $rdata = $db->gData($SQL);
            if (count($rdata) > 0) {
                // break batch by host_po_num, then process each po by itself
                $output = "";
                $textData = array();
                $jsonData = array();
                $i = 1; // line item counter // need to set later
                $trd = array();
                foreach ($rdata as $rec => $data) {
                    $PONumber = $data["host_po_num"];
                    $blFlag = $data["bo_flag"];
                    $trd[$PONumber][$i] = $data;
                    $i++;
                }
                // end break batch by host_po_num
                unset($rdata);
                unset($rec);
                unset($data);
                // end break batch by host_po_num

                $i = 0;
                foreach ($trd as $PO => $rdata) {
                    $mn = min(array_keys($rdata));
                    // set po status to 4 -- Updating
                    $po = $rdata[$mn]["po_number"];
                    $comp = $rdata[$mn]["company"];
                    $boFlag = $rdata[$mn]["bo_flag"];
                    $rrc = updPoStat($db, $comp, $po, 4);
                    $potype = poTypeName($rdata[$mn]["po_type"]);
                    if (strtoupper($outType) == "JSON") {
                        $jsonData[$PO]["RcptBegin"] = array(
                            "TransType" => "{$potype}",
                            "RcptNum" => "{$PO}",
                            "Company" => $rdata[$mn]["company"],
                            "Vendor" => $rdata[$mn]["vendor"]
                        );
                    } else { // not json
                        $textData[$PO] = "RcptBegin|{$potype}|{$PO}|{$rdata[$mn]["company"]}|{$rdata[$mn]["vendor"]}\n";
                    } // not json
                    $totalRecords = 0;
                    $totalQty = 0;
                    $fname = buildFilename("Receipt", $PO);
                    $bfname = $fname;
                    $ok = false;
                    $k = 1;
                    while (!$ok) {
                        if (file_exists($fname)) {
                            $fname = "{$bfname}_{$k}";
                            $k++;
                        } else $ok = true;
                    } // end not ok
                    foreach ($rdata as $rec => $data) {
                        $recType = "RcptDtl";
                        //if ($data["batch_status"] > 6) $recType="RcptResend";
                        //check detail scan status, update to exporting(1), then exported later(2)
                        $lstat = $data["scan_status"];
                        if ($data["scan_status"] == 0) $lstat = 1;
                        if ($lstat <> $data["scan_status"]) {
                            $rc = updScanStatus($db, $data["batch_num"], $data["line_num"], $lstat);
                            $rc = 1;
                        }
                        //if ($lstat > 1) $recType="RcptResend";
                        $rc = updPOQty($db, $data);
                        $userIdee = getUser($db, $data["scan_user"], true);
                        $mdseType = mdseType($data["line_type"]);
                        $rcvTo = "TOTE";
                        if ($data["recv_to"] == "b") $rcvTo = "Bin";
                        $totalRecords++;
                        $totalQty = intval($totalQty) + intval($data["totalQty"]);
                        $tPO = $data["host_po_num"];
                        if ($potype == "ASN") $tPO = getTracking($db1, $data);
                        if ($tPO == "") $tPO = $data["host_po_num"];
                        if (strtoupper($outType) == "JSON") {
                            $jsonData[$PO][$recType][$i] = array(
                                "PONumber" => $tPO,
                                // "LineNum"=>$data["line_num"],
                                "User" => $userIdee,
                                "ScannedUPC" => $data["scan_upc"],
                                "UPCUOM" => $data["pkgUOM"],
                                "PL" => $data["p_l"],
                                "PartNumber" => $data["part_number"],
                                "scanQty" => $data["scanQty"],
                                "totalQty" => $data["totalQty"],
                                "PartUOM" => $data["partUOM"],
                                "MdseType" => $mdseType,
                                "timesScanned" => $data["timesScanned"],
                                "RecvdTo" => $rcvTo,
                                "scannedTo" => $data["pack_id"]
                            );
                            $i++;
                        } // end json
                        else { // output text
                            $textData[$PO] .= "{$recType}|{$tPO}|{$data["line_num"]}|{$userIdee}|{$data["scan_upc"]}|{$data["pkgUOM"]}|{$data["p_l"]}|{$data["part_number"]}|{$data["scanQty"]}|{$data["totalQty"]}|{$data["partUOM"]}|{$mdseType}|{$data["timesScanned"]}|{$rcvTo}|{$data["pack_id"]}\n";
                        } // output text
                        // set dtl scan status to exported(2)
                        $rc = updScanStatus($db, $data["batch_num"], $data["line_num"], 2);
                    } // end foreach rdata

                    if (strtoupper($outType) == "JSON") {
                        $jsonData[$PO]["RcptDone"] = array(
                            "TransType" => "{$potype}",
                            "RcptNum" => "{$PO}",
                            "totalRecords" => $totalRecords,
                            "totalQty" => $totalQty
                        );
                        $textData[$PO] = json_encode($jsonData[$PO]);
                        // transmit here
                        // echo "Transmit {$fname}\n{$textData[$PO]}\n";
                        $rc = file_put_contents("/usr1/wms/Stage/work_{$PO}", $textData[$PO], LOCK_EX);
                        $pw = new TPOST;
                        $rcc = $pw->Send("Receive", $textData[$PO]);
                        $rccc = json_encode($rcc);
                        $oo = "{$textData[$PO]}\nResult\n{$rccc}";
                        // write file here with both out and result
                        $rc = file_put_contents($fname, $oo, LOCK_EX);
                        unset($pw);
                        echo $rccc;
                    } else { // not json
                        $textData[$PO] .= "RcptDone|{$potype}|{$data["batch_num"]}|{$totalRecords}|{$totalQty}\n";
                        // write file here
                        $rc = file_put_contents($fname, $textData[$PO], LOCK_EX);
                        echo $rc;
                    } // not json
                    // flag po header as Backorders exist
                    $stat = -1; // backorders
                    if ($boFlag < 1) $stat = 7; // no bo allowed
                    $tmp = poOpenLines($db, $po, 1, false);
                    if (isset($tmp["numRows"])) {
                        if ($tmp["numRows"] == 0 and $blFlag > 0) $stat = 7;
                    }
                    // this check to see if any in process is failing, I think the db is still
                    // updating
                    // Need to fix............................................
                    //$t=chkInProcess($db,$po);
                    //if ($t  > 0) $stat = 2; // in process with another batch
                    //if ($DEBUG) wr_log($logfile,"updPoStatus to {$stat} t={$t}\n");
// end check for in process

                    $rrc = updPoStat($db, $comp, $po, $stat);
                } // end foreach trd
            } // end count rdata > 0

        } // batch num > 0
        //update batch_status and PO file
        $brc = updBatchStatus($db, $batch_num, 7); // set status to Export Done
        break;
    } // end Receive

} // end switch reqdata action
if (!$goodaction) {
    $response['error'] = true;
    $response['code'] = 400;
    $response['message'] = 'Bad Request';
    echo json_encode($response);
    die();
}

function gData($db, $SQL, $multi = true)
{
    // multi=expected to return multiple records, false=just 1
    $ret = array();
    $rc = $db->query($SQL);
    $numrows = $db->num_rows();
    $i = 1;
    while ($i <= $numrows) {
        $db->next_record();
        if ($numrows and $db->Record) {
            foreach ($db->Record as $key => $data) {
                if (!is_numeric($key)) {
                    if ($multi) $ret[$i][$key] = $data;
                    else $ret[$key] = $data;
                } // key is not numeric
            }
        }
        $i++;
    } // while i < numrows
    return $ret;
} // end gData
function unAllocate($db, $comp, $shadow, $qty, $bin)
{
} // end unallocate

function writeParthist($db, $item, $comp, $host_id, $ext_ref, $type, $source, $theUser)
{
    global $DEBUG;
    global $logfile;
    /* CREATE PROCEDURE wp_addPartHist(
                                IN shadow     int,
                                IN company    smallint,
                                IN psource      char(  10 ),
                                IN host_id   char(  20 ),
                                IN ext_ref  char( 20 ),
                                IN trans_type char(  3 ),
                                IN qty_mdse   int,
                                IN uom char(  3 ),
                                IN bin  varchar(18),
                                IN inv_code  char(  1 ),
                                IN mdse_price numeric (10,3),
                                IN core_price numeric (10,3),
                                IN qty_core   smallint,
                                IN qty_def smallint )
    */
    $today = $db->dbDate();
    $inv_code = $item["inv_code"];
    $insertPH = false;
    $qty = $item["qty_ship"];
    $qty_core = 0;
    $qty_def = 0;
    if (intval($inv_code) == 1) { // core
        $qty_core = $item["qty_ship"];
        $qty = 0;
    }  // core
    if (intval($inv_code) == 2) { // defect
        $qty_def = $item["qty_ship"];
        $qty = 0;
    }  // defect
    if ($qty <> 0 or $qty_code <> 0 or $qty_def <> 0)
        $SQL = <<<SQL
   INSERT INTO PARTHIST
         ( paud_id,                     -- order#       receiver#
           paud_shadow,
           paud_company,
           paud_date,
           paud_source,                 -- cust#        vendor          oper
           paud_user,
           paud_ref,                    -- invoice#     po#
           paud_ext_ref,                -- cust po#     vendor invc#
           paud_type,
           paud_qty,
           paud_uom,
           paud_floc,
           paud_tloc,
           paud_prev_qty,
           paud_inv_code,
           paud_price,
           paud_core_price,
           paud_qty_core,
           paud_qty_def )
        VALUES
         ( {$item["ord_num"]},
           {$item["shadow"]},
           {$comp},
           "{$today}",
           "{$source}",
           {$theUser},
           "{$host_id}",
           "{$ext_ref}",
           "{$type}",
           {$qty},
           "{$item["uom"]}",
           "{$item["whse_loc"]}",
           "Shipped",
           {$item["qty_avail"]},
           "{$inv_code}",
           0.00,
           0.00,
           {$qty_core},
           {$qty_def} );

SQL;

    $rc = array();

    if (isset($SQL)) {
        if ($DEBUG) wr_log($logfile, "SQL:\n{$SQL}");
        //Start Transaction
        $rc["start"] = $db->startTrans();
        // do transaction
        $rc["trans"] = $db->updTrans($SQL);
        // commit or Rollback Transaction
        $rc["end"] = $db->endTrans($rc["trans"]);
        return $rc["trans"];
    } // sql is set
    else return "";

} // end writeParthist

function format_output($db, $ord)
{
    /*

   Field          Format     Description
   SOH                       Header
   orderType    char(1)     order_type
   OrderId      char(20)   Host Order Id
   Company	       char(6)
   Date           99/99/9999 Date
   ShipVia        char(3)

   SOD                       Detail
   OrderId      char(20)   Host Order Id
   line_num       numeric
   userId         numeric
   p_l            char(6)
   part_number    char(22)
   qty_ship       int(11)
   uom            char(3)
   inv_code       numeric   Inventory type: 0=Regular, 1=Core, 2=Defective

   SOE                      End of Order
   OrderId      char(20)  Host Order Id
   Sent           numeric   The number of lines outputed


   future add
   WMSOrder       numeric
   Weight         00000.00
   SCACCode       char(4)    ? on length
   Status         bool       0=Shipped complete, otherwise, #of lines not shipped

    */
    $out = array();
    $ho = $ord->Order["host_order_num"];
    $rCode = "SOH";
    $out[$rCode]["OrderType"] = $ord->Order["order_type"];
    $out[$rCode]["OrderId"] = $ho;
    //$out[$rCode]["WMSOrder"]=$ord->Order["order_num"];
    $out[$rCode]["Company"] = $ord->Order["company"];
    $out[$rCode]["Date"] = date("m/d/Y h:i:s");
    $out[$rCode]["shipVia"] = $ord->Order["ship_via"];
    //$out[$rCode]["Status"]=-1;
    //$out[$rCode]["SCAC"]=getSCAC($db,$ord->Order["ship_via"]);
    $rCode = "SOD";
    $i = 0;
    if (count($ord->ItemPull) > 0) {
        $oldUser = -1;
        $userIdee = "";
//echo "here";
//print_r($item);
//print_r($ord);
//exit;
        $xl = array();
        if (count($ord->Items)) foreach ($ord->Items as $l => $ii) {
            $xl[$ii["line_num"]] = $l;
        }

        foreach ($ord->ItemPull as $key => $item) {
            if (isset($item["line_num"])) { // item is set
                $l = $xl[$item["line_num"]];
                if ($oldUser <> $item["user_id"]) {
                    $oldUser = $item["user_id"];
                    $userIdee = getUser($db, $item["user_id"], true);
                }
                $out[$rCode][$i]["OrderId"] = $ho;
                $out[$rCode][$i]["line_num"] = $l;
                $out[$rCode][$i]["user_id"] = $userIdee;
                $out[$rCode][$i]["p_l"] = $ord->Items[$l]["p_l"];
                $out[$rCode][$i]["part_number"] = $ord->Items[$l]["part_number"];
                $out[$rCode][$i]["qty_ship"] = $item["qty_picked"];
                $out[$rCode][$i]["uom"] = $item["uom_picked"];
                $out[$rCode][$i]["location"] = $item["whse_loc"];
                $out[$rCode][$i]["inv_code"] = $ord->Items[$l]["inv_code"];
                $i++;
            } // item is set
        } // end foreach ItemPull
    } // end count itemPull > 0
    $rCode = "SOE";
    $out[$rCode]["OrderId"] = $ho;
    $out[$rCode]["Sent"] = $i;
    return $out;
} // end format_output

function getSCAC($db, $via)
{
    $ret = " ";
    $SQL = <<<SQL
select via_SCAC from SHIPVIA
where via_code = "{$via}"

SQL;

    $rc = $db->query($SQL);
    $numrows = $db->num_rows();
    $i = 1;
    while ($i <= $numrows) {
        $db->next_record();
        if ($numrows) {
            $ret = $db->f("via_SCAC");
        }
        $i++;
    } // while i < numrows
    return $ret;
} // end getSCAC

function write_output($hostOrder, $output)
{
    // ftype=A = Ascii Pipe Delimted, J=Json
    global $outType;
    global $outDir;
    global $outNotice;
    global $doneDir;
    global $sentDir;
    global $errDir;

    $ext = "txt";
    $mode = "w";
    $toutDir = $outDir;
    if (strtoupper($outType) == "JSON") {
        $ext = "json";
        $toutDir = "{$outDir}/../Service/";
    }
    $order = trim($hostOrder);
    $fname = "{$toutDir}/ship{$order}.{$ext}";
    $ok = false;
    $i = 0;
    while (!$ok) {
        $i++;
        if (file_exists($fname)) $fname = "{$toutDir}/ship{$order}_{$i}.{$ext}";
        else $ok = true;
    } // end while !ok

    if (strtoupper($outType) == "JSON") { // json service output
        $data = json_encode($output);
        $rc = strlen($data);
        wr_log($fname, "{$data}\n");
        $pw = new TPOST;
        $rcc = $pw->Send("Ship", $data);
        $rccc = json_encode($rcc);
        $oo = "Result\n{$rccc}";
        wr_log($fname, "{$oo}\n");
        //$rc=file_put_contents($fname,$oo, LOCK_EX);
        unset($pw);
    } // json service output
    else { // text output
        $data = "";
        $o = $output["SOH"];
        $data .= "SOH|{$o["OrderId"]}|{$o["Company"]}|{$o["Date"]}|{$o["shipVia"]}\n";
        $o = $output["SOD"];
        $j = count($o);
        //for ($i=0;$i<=$j;$i++)
        $rc = 0;
        if ($j > 0) {
            foreach ($o as $fld => $val) {
                $userIdee = getUser($db, $val["user_id"], true);
                $data .= "SOD|{$val["OrderId"]}|{$val["line_num"]}|{$userIdee}|{$val["p_l"]}|{$val["part_number"]}|{$val["qty_ship"]}|{$val["uom"]}|{$val["location"]}|{$val["inv_code"]}\n";
            } // end foreach SOS
        } // end for i loop
        $o = $output["SOE"];
        $data .= "SOE|{$o["OrderId"]}|{$o["Sent"]}\n";
        $rc = file_put_contents($fname, $data, LOCK_EX);
    }  // text output
    return $rc;
} // end write_output

function poTypeName($in)
{
    $out = "";
    switch ($in) {
        case "":
        case "P":
            $out = "PO";
            break;
        case "T":
            $out = "TRN";
            break;
        case "R":
            $out = "RMA";
            break;
        case "A":
            $out = "ASN";
            break;
        case "S":
            $out = "SPEC";
            break;
        case "U":
            $out = "UNEXPECTED";
            break;
    } // end switch in
    return $out;

} // end poTypeName

function mdseType($in)
{
    $out = $in;
    switch ($in) {
        case "0":
            $out = "NI"; // non-inentory
            break;
        case "":
        case "1":
        case "M":
            $out = "M";
            break;
        case "2":
        case "C":
            $out = "C";
            break;
        case "3":
        case "D":
            $out = "C";
            break;
    } // end switch in
    return $out;
}

function buildFilename($fType, $idNum)
{
    // ftype=A = Ascii Pipe Delimted, J=Json
    global $outType;
    global $outDir;
    global $ServiceOut;

    $toutDir = $outDir;
    $ext = "txt";
    $mode = "w";
    if (strtoupper($outType) == "JSON") {
        $ext = "json";
        $toutDir = $ServiceOut;
    }
    $id = trim($idNum);
    $fname = "{$toutDir}/{$fType}{$idNum}.{$ext}";
    $ok = false;
    $i = 0;
    while (!$ok) {
        $i++;
        if (file_exists($fname)) $fname = "{$toutDir}/{$fType}{$id}_{$i}.{$ext}";
        else $ok = true;
    } // end while !ok
    return ($fname);
} // end buildFilename

function updPOQty($db, $data)
{
    if (count($data) > 0) {
        $SQL = <<<SQL
   select poi_status,poi_line_num, 
   qty_ord, qty_recvd,vendor_ship_qty
   from POITEMS
   where poi_po_num = {$data["po_number"]}
   and shadow = {$data["shadow"]}

SQL;
        $rdata = $db->gData($SQL);
        if (count($rdata) > 0) {
            if (count($rdata) < 2) { // just 1 line, update all the qty to this line
                $d = $rdata[1];
                $qo = $d["qty_ord"];
                $qr = $d["qty_recvd"] + $data["totalQty"];
                $stat = 1;
                if ($qr >= $qo) $stat = 9;
                $SQL = <<<SQL
update POITEMS
set poi_status = {$stat},
    qty_recvd = qty_recvd + {$data["totalQty"]}
    where poi_po_num = {$data["po_number"]}
     and poi_line_num = {$d["poi_line_num"]}

SQL;
                $rc = $db->Update($SQL);
                return ($rc);
            } // just 1 line, update all the qty to this line
            else { // more than 1 line, divy it up until qty runs out
                $qtyLeft = $data["totalQty"];
                foreach ($rdata as $rec => $d) {
                    $qo = $d["qty_ord"];
                    $qr = $d["qty_recvd"];
                    $left = ($qo - $qr);
                    $stat = 1;
                    $toApply = $qtyLeft;
                    if ($toApply > $left) $toApply = $left;
                    if ($toApply < 1 and !isset($rdata[($rec + 1)])) $toApply = $qtyLeft;
                    $qtyLeft = ($qtyLeft - $toApply);
                    if (($qr + $toApply) >= $qo) $stat = 9;
                    $SQL = <<<SQL
update POITEMS
set poi_status = {$stat},
    qty_recvd = qty_recvd + {$toApply}
    where poi_po_num = {$data["po_number"]}
     and poi_line_num = {$d["poi_line_num"]}

SQL;
                    $rc = $db->Update($SQL);
                } // end foreach rdata
                return ($rc);
            } // more than 1 line, divy it up until qty runs out

        } // end count rdata > 0
    } // end count data > 0
    echo "<pre>";
    print_r($data);
    exit;
} // end updPOQty

function updBatchStatus($db, $batch, $stat)
{
    $rc = 0;
    if ($batch > 0 and $stat > -1) {
        $SQL = <<<SQL
update RCPT_BATCH
set batch_status = {$stat}
where batch_num = {$batch}

SQL;
        $rc = $db->Update($SQL);
        //echo "{$SQL}\n";
    } // batch and line > 0
    return $rc;
} // end updBatchDtl

function updScanStatus($db, $batch, $line, $stat)
{
    $rc = 0;
    if ($batch > 0 and $line > 0 and $stat > -1) {
        $SQL = <<<SQL
update RCPT_SCAN
set scan_status = {$stat}
where batch_num = {$batch}
  and line_num = {$line}

SQL;
        $rc = $db->Update($SQL);
        //echo "{$SQL}\n";
    } // batch and line > 0
    return $rc;
} // end updScanStatus


function chkBatchStatus($db, $batch_num)
{
    $SQL = <<<SQL
select batch_status 
from RCPT_BATCH
where batch_num = {$batch_num}

SQL;
    $stat = -1;
    $rc = $db->query($SQL);
    $numrows = $db->num_rows();
    $i = 1;
    while ($i <= $numrows) {
        $db->next_record();
        if ($numrows) {
            $stat = $db->f("batch_status");
        }
        $i++;
    } // while i < numrows

//check if putaway is done
    $SQL = <<<SQL
select count(*) as cnt from RCPT_SCAN
where batch_num = {$batch_num}
and totalQty <> qty_stockd

SQL;

    $cnt = 0;
    $rc = $db->query($SQL);
    $numrows = $db->num_rows();
    $i = 1;
    while ($i <= $numrows) {
        $db->next_record();
        if ($numrows) {
            $cnt = $db->f("cnt");
        }
        $i++;
    } // while i < numrows

    if ($cnt <> 0) $stat = -2;
    return $stat;
} // end chkBatchStatus

function resetTote($db, $toteId)
{
    $SQL = <<<SQL
select count(*) as cnt from TOTEDTL
where tote_id = {$toteId}

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
where tote_id = {$toteId}

SQL;
        $rd["toteReset"] = $db->Update($SQL);
    } // update tote status
    return $rd;
} // end reset Tote

function loadCnt($db, $SQL)
{
    $cnt = 0;
    $rc = $db->query($SQL);
    $numrows = $db->num_rows();
    $i = 1;
    while ($i <= $numrows) {
        $db->next_record();
        if ($numrows) {
            $cnt = $db->f("cnt");
        }
        $i++;
    } // while i < numrows
    return $cnt;
} // end loadCnt

function chkInProcess($db, $po)
{
    global $DEBUG;
    global $logfile;
    $rc = 0;
    // switched to scan_status instead of batch_status to see if
    // open recvings are there

    $SQL = <<<SQL
select count(*) as cnt
from RCPT_BATCH A, RCPT_SCAN B, RCPT_INWORK C
where po_number = {$po}
and C.batch_num = B.batch_num
and A.batch_num = B.batch_num
-- and batch_status < 2
and scan_status < 2

SQL;
    if ($DEBUG) wr_log($logfile, "SQL:\n{$SQL}");
    $rc = loadCnt($db, $SQL);
    return $rc;
} // end chkInProcess

function getHostReason($db, $reason)
{
    $SQL = <<<SQL
 select  reason_desc,host_reason
 from REASONS
 where reason_code = "{$reason}"

SQL;
    $ret = $reason;
    $rc = $db->query($SQL);
    $numrows = $db->num_rows();
    $i = 1;
    while ($i <= $numrows) {
        $db->next_record();
        if ($numrows) {
            $ret = $db->f("host_reason");
            if (trim($ret) == "") $ret = $db->f("reason_desc");
        }
        $i++;
    } // while i < numrows
    return $ret;
} // end getHostReason

function getTracking($db, $data)
{
    $po = $data["po_number"];
    $shadow = $data["shadow"];
    $line = $data["po_line_num"];
    $SQL = <<<SQL
select tracking_num
from POITEMS
where poi_po_num = {$po}
and shadow = {$shadow}
and poi_line_num = {$line}

SQL;
    $ret = "";
    $rc = $db->query($SQL);
    $numrows = $db->num_rows();
    $i = 1;
    while ($i <= $numrows) {
        $db->next_record();
        if ($numrows) {
            $ret = $db->f("tracking_num");
        }
        $i++;
    } // while i < numrows
    if ($ret == "") { // get a tracking number from the PO tracking is blank
        $SQL = <<<SQL
$SQL=<<<SQL
select distinct tracking_num
from POITEMS
where poi_po_num = {$po}
and tracking_num <> ""
LIMIT 1

SQL;
        $rc = $db->query($SQL);
        $numrows = $db->num_rows();
        $i = 1;
        while ($i <= $numrows) {
            $db->next_record();
            if ($numrows) {
                $ret = $db->f("tracking_num");
            }
            $i++;
        } // while i < numrows


    } // get a tracking number from the PO tracking is blank
    return $ret;


} // end getTracking
?>
