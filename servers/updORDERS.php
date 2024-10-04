<?php

// updORDERS.php -- Server for Updating and sending Picked Orders to Host
//05/12/22 dse initial
// 06/22/22 dse change paud_bin to paud_floc , NEED TO ADD paud_tloc

/*
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


$DEBUG = true;
require("srv_hdr.php");
require_once("cl_ORDERS.php");
require("{$wmsDir}/config_comm.php");

if (isset($_REQUEST["searcH"])) $srch = $_REQUEST["searcH"]; else $srch = "";
$comp = 0;
$logfile = "/tmp/updOrd.log";
if ($srch <> "") $comp = intval($srch);
if ($DEBUG) wr_log($logfile, "Program={$_SERVER["PHP_SELF"]}");
if ($DEBUG) wr_log($logfile, "inputData:\n{$inputdata}");
$action = $reqdata["action"];

if (isset($reqdata["order_num"])) $order_num = $reqdata["order_num"]; else $order_num = 0;
if (isset($reqdata["company"])) $comp = $reqdata["company"]; else $comp = "";

if ($DEBUG) wr_log($logfile, "Function: {$action}");
$rc = array();
switch ($action) {
    case "Ship":
    {
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
                //header('HTTP/1.1 200 Order is still is process');
                $rdata = array('status' => 200, "message" => "Order is still is process");
                $x = json_encode($rdata);
                if ($DEBUG) wr_log($logfile, "Response:\n{$x}");
                echo $x;
                exit;
            } // end order stat < 4
            if ($ord->Order["order_stat"] == 4) { // stat = 4, set ship qty, adjust alloc, book parthist and set status to 5
                // step 1, set status to 5 (being updated)
                $SQL = <<<SQL
update ORDERS
set order_stat = 5
where order_num = {$ord->Order["order_num"]}

SQL;
                $ord->Order["order_stat"] = 5;
                if ($DEBUG) echo "{$SQL}\n";
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
                                    if ($DEBUG) echo "{$SQL}\n";
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
                                    if ($DEBUG) echo "{$SQL}\n";
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
                                if ($DEBUG) echo "{$SQL}\n";
                                $rc["Items"] = $db->Update($SQL);

                            } // set line stat to 1
                            $SQL = <<<SQL
update WHSEQTY
set qty_alloc = qty_alloc - {$pulled[$l]["qty"]}
where ms_company = {$item["inv_comp"]}
and ms_shadow = {$item["shadow"]}

SQL;

                            if ($DEBUG) echo "{$SQL}\n";
                            $rc["WhseQty"] = $db->Update($SQL);
                            $otype = "PIC";
                            if ($ord->Order["order_type"] == "D") $otype = "DM";
                            if ($ord->Order["order_type"] == "T") $otype = "OTR";
                            $rc["PartHist"] = writeParthist($db, $item, $ord->Order["company"], $ord->Order["host_order_num"], $ord->Order["cust_po_num"], $otype, $ord->Order["customer_id"], $pulled[$l]["user"]);
                        } // end foreach items
                    } // end count items > 0
                } // end there is itempulls
            } // stat = 4, set ship qty and set status to 5

// check if expost is done, if not, generate it
        } // got an order
        else exit; // probably need to log order not found

        if ($ord->Order["order_stat"] == 5) { // export it and set status to 6
            $output = format_output($db, $ord);
            $rc["fileWrite"] = write_output($ord->Order["host_order_num"], $output);
            $SQL = <<<SQL
update ORDERS
set order_stat = 6
where order_num = {$ord->Order["order_num"]}

SQL;
            $ord->Order["order_stat"] = 6;
            if ($DEBUG) echo "{$SQL}\n";
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
    } // end cancel and delete
} // end switch reqdata action

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
    $qty = $item["qty_ship"];
    $qty_core = 0;
    $qty_def = 0;
    if (intval($inv_code) == 1) { // core
        $qty_code = $item["qty_ship"];
        $qty = 0;
    }  // core
    if (intval($inv_code) == 2) { // defect
        $qty_def = $item["qty_ship"];
        $qty = 0;
    }  // defect

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


    if ($DEBUG) echo "{$SQL}\n";
    //Start Transaction
    $rc["start"] = $db->startTrans();
    // do transaction
    $rc["trans"] = $db->updTrans($SQL);
    // commit or Rollback Transaction
    $rc["end"] = $db->endTrans($rc["trans"]);
    return $rc["trans"];

} // end writeParthist

function format_output($db, $ord)
{
    /*

   Field          Format     Description
   SOH                       Header
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
        foreach ($ord->ItemPull as $key => $item) {
            $l = $item["line_num"];
            $out[$rCode][$i]["OrderId"] = $ho;
            $out[$rCode][$i]["line_num"] = $l;
            $out[$rCode][$i]["user_id"] = $item["user_id"];
            $out[$rCode][$i]["p_l"] = $ord->Items[$l]["p_l"];
            $out[$rCode][$i]["part_number"] = $ord->Items[$l]["part_number"];
            $out[$rCode][$i]["qty_ship"] = $item["qty_picked"];
            $out[$rCode][$i]["uom"] = $item["uom_picked"];
            $out[$rCode][$i]["location"] = $item["whse_loc"];
            $out[$rCode][$i]["inv_code"] = $ord->Items[$l]["inv_code"];
            $i++;
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
    if (strtoupper($outType) == "JSON") $ext = "json";
    $order = trim($hostOrder);
    $fname = "{$outDir}/ship{$order}.{$ext}";
    $ok = false;
    $i = 0;
    while (!$ok) {
        $i++;
        if (file_exists($fname)) $fname = "{$outDir}/ship{$order}_{$i}.{$ext}";
        else $ok = true;
    } // end while !ok

    if (strtoupper($outType) == "JSON") $data = json_encode($output);
    else { // text output
        $data = "";
        $o = $output["SOH"];
        $data .= "SOH|{$o["OrderId"]}|{$o["Company"]}|{$o["Date"]}|{$o["shipVia"]}\n";
        $o = $output["SOD"];
        $j = count($o);
        for ($i = 0; $i <= $j; $i++) {
            foreach ($o as $fld => $val) {
                $data .= "SOD|{$val["OrderId"]}|{$val["line_num"]}|{$val["user_id"]}|{$val["p_l"]}|{$val["part_number"]}|{$val["qty_ship"]}|{$val["uom"]}|{$val["location"]}|{$val["inv_code"]}\n";
            } // end foreach SOS
        } // end for i loop
        $o = $output["SOE"];
        $data .= "SOE|{$o["OrderId"]}|{$o["Sent"]}\n";
    }  // text output
    $rc = file_put_contents($fname, $data, LOCK_EX);
    return ($rc);
} // end write_output
?>
