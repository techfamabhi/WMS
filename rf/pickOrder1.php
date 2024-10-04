<?php

// pickOrder.php -- Discrete Order Picking, 1 Order at a time
// 03/15/22 dse initial
// 01/09/24 dse add no Barcode buttons and new skip lines, correct invaling bin
// 02/02/24 dse Log No UPC Button to NOUPC table

/*TODO
 ) need to pass myZones when geting orders and items

 ) need to add button for new tote when an orders tote is full

 ) Need to fix NFP not found part

*/

session_start();

//echo "<pre>";
//echo $toteId;
//print_r($_REQUEST);
//print_r($_SESSION);
//echo "</pre>";
//exit;
foreach (array_keys($_REQUEST) as $w) {
    $$w = $_REQUEST[$w];
}
//error_reporting(0);

if (get_cfg_var('wmsdir')) $wmsDir = get_cfg_var('wmsdir');
else {
    echo "<h1>WMS System is not Configured on this System</h1>";
    exit;
}
$top = str_replace("/var/www", "", $wmsDir);


echo "<pre>";
print_r($_REQUEST);
echo "</pre>";

$thisprogram = basename($_SERVER["PHP_SELF"]);
require("{$wmsDir}/config.php");
require_once("{$wmsInclude}/chklogin.php");
require_once("{$wmsInclude}/cl_rf1.php");
require_once("{$wmsInclude}/cl_template.php");
require_once("{$wmsInclude}/cl_PARTS2.php");
require_once("{$wmsInclude}/db_main.php");
require_once("{$wmsInclude}/get_option.php");
require_once("{$wmsInclude}/get_option.php");
require_once("{$wmsInclude}/restSrv.php");
require_once("shipIt.php");
require_once("collapse.php");


$RESTSRV = "http://{$wmsIp}{$wmsServer}/PICK_srv.php";
$W2ErpSRV = "http://{$wmsIp}{$wmsServer}/WMS2ERP.php";

$comp = $wmsDefComp;
$db = new WMS_DB;
$pg = new displayRF;
$opt = array();

$opt[25] = get_option($db, $comp, 25);
$opt[26] = get_option($db, $comp, 26);
$opt[102] = get_option($db, $comp, 102);
$opt[103] = get_option($db, $comp, 103);

// Other Init
if (!isset($B1)) $B1 = "";
if (!isset($toteId)) $toteId = "";
if (!isset($curLine)) $curLine = 0;
if (!isset($scaninput)) $scaninput = "";
if (!isset($func)) $func = "enterOrd";

// Application Specific Variables -------------------------------------

$temPlate = "generic1";
$title = "Picking";
$panelTitle = "Order #";

// end application specific variables ---------------------------------


$pickingComplete = false;

if (isset($_SESSION["wms"]["zones"])) $zones = $_SESSION["wms"]["zones"];
else $zones = array();
$myZones = "";
$myZones1 = "";
if (count($zones) > 0 and $opt[26] == "Y") {
    $comma = "";
    foreach ($zones as $key => $z) {
        $myZones .= "{$comma}{$z}";
        $myZones1 .= "{$z}";
    } // end foreach zones
} else $myZones = "%";

$myOrders = "";
if (isset($order)) { // bld hidden array of all orders and load em
    if (is_array($order) and count($order) > 0) { // order is array
        foreach ($order as $key => $v) {
            $myOrders .= <<<HTML
  <input type="hidden" name="order[]" value="{$v}"> 

HTML;
        }
    } // order is array
    else { // order is not array
        $myOrders .= <<<HTML
  <input type="hidden" name="order" value="{$order}"> 

HTML;
    } // order is not array

// Zero Pick
    if ($B1 == "NFP" and $func == "pickScanPart") { // zero pick the item
        if (!isset($tote_id)) $tote_id = 0;
        $req = array("action" => "setZeroPick",
            "company" => $comp,
            "user_id" => $UserID,
            "host_order_num" => $hostordernum,
            "zone" => "",
            "whseLoc" => $bintoScan,
            "order_num" => $orderNumber,
            "line_num" => $lineNumber,
            "pull_num" => $pullnum,
            "shadow" => $shadow,
            "qtyPicked" => 0,
            "zeroed" => $qtytopick,
            "uom" => $uom,
            "p_l" => $p_l,
            "part_number" => $part_number,
            "tote_id" => $tote_id
        );
//echo "<pre>";
//print_r($req);
//echo "</pre>";
//exit;
        $rc = restSrv($RESTSRV, $req);
        $response = (json_decode($rc, true));
        if (isset($_SESSION["wms"]["Pick"])) $j = count($_SESSION["wms"]["Pick"]);
        else $j = 0;
        $j++;
        $notFound = true;
        if ($j > 1) {
            foreach ($_SESSION["wms"]["Pick"] as $jj => $pk) {
                if ($pk["line_num"] == $req["line_num"]) $notFound = false;
            }
        }
        if ($notFound) $_SESSION["wms"]["Pick"][$j] = $req;

        $partnumber = "";
    } // zero pick the item
    //load the order
    $ord = ordToPick($comp, $UserID, $order);
    if ($ord == null) { // done picking the orders, load the orders anyway and set func to complete
        $pickingComplete = true;
        $ord = ordToPick($comp, $UserID, $order, true);
    } // done picking the orders, load the orders anyway and set func to complete
    $orders = array();
    $oidx = array();
    $tmp = orderSlots($ord);
    if (isset($tmp["orders"])) $orders = $tmp["orders"];
    if (isset($tmp["oidx"])) $oidx = $tmp["oidx"];
    if (isset($tmp["o"])) $o = $tmp["o"];
    $items = array();
    if (count($orders) > 0 and isset($o)) {
        foreach ($orders as $key => $w) {
            if (is_numeric($key)) $ord[$key]["slot"] = $key;
        }

        $req = array("action" => "getItems",
            "order_num" => $o,
            "zones" => $zones
        );
        $rc = restSrv($RESTSRV, $req);
        $items = (json_decode($rc, true));
        $items = lineSlots($items, $oidx);
        // set current line to pick, check skipTo, make sure that line is set
        if (isset($skipTo) and $skipTo > 0) $ln = $skipTo; else $ln = 0;
        $numLinesToPick = 0;
        $nextLine = 0;
    } // end count orders > 0
    if (count($items) > 0) foreach ($items as $key => $i) {
        $qty = $i["qtytopick"] - $i["qty_picked"];
        $zero = $i["zero_picked"];
        if ($i["zpuser"] <> $UserID) $zero = 0;
        $qty = $qty - $zero;
        if ($qty > 0) $numLinesToPick++;
        if ($qty > 0 and $ln == 0) $ln = $key;
        if ($B1 == "NFP" and $func == "pickScanPart" and $curLine == $ln and $qty > 0) {
            $numLinesToPick--;
            $ln = 0;
        }
    }
    if (count($items) > 0) {
        $ord["items"] = $items;
        $ord["linesToPick"] = $numLinesToPick;
        if (isset($ord["items"][$ln])) {
            $ord["curLine"] = $ln;
            $ord["pickLine"] = $ord["items"][$ln];
        }
        if ($ln == 0) $pickingComplete = true;
        $CURLINE = $ln;
        $ord["curLine"] = $ln;
        if ($numLinesToPick == 0) $pickingComplete = true;
        $ord["pickingComplete"] = $pickingComplete;
    } // count items > 0
    else $pickingComplete = true;

    if ($pickingComplete) {
        $func = "Drop";
    }
    if (isset($ord["pickLine"]["ord_num"])) $orderNumber = $ord["pickLine"]["ord_num"];
    else $orderNumber = 0;
//echo "<pre>ln={$ln}";
//print_r($ord);
//echo "</pre>";
//exit;
    $ORDER = $ord;
} // bld hidden array of all orders and load em

//echo "<pre>";
//if (isset($func)) echo "func={$func}\n";
//print_r($ORDER);
//echo "</pre>";


if (isset($fPQ) and $fPQ > 0 and isset($B2)) {
    // redirect to Pick Queue screen
    $htm = <<<HTML
 <html>
 <head>
 <script>
 window.location.href="pickQue1.php?nh={$nh}";
 </script>
 </head>
 <body>
 </body>
</html>

HTML;
    echo $htm;
    exit;
}
if (!isset($fPQ)) $fPQ = "0";
if (!isset($msg)) $origMsg = "";
else $origMsg = $msg;
$msg = "";
$msgcolor = "";
$js = "";
if (isset($func) and $func == "pickScanPart" and isset($NoUPC) and $NoUPC == "1") { // log this part has no UPC
//echo "Source: PICK\n";
//echo "Problem: NoUPC\n";
//echo "User: {$UserID}\n";
//echo "Order: {$hostordernum}\n";
//echo "Shadow: {$shadow}\n";
//echo "Bin: {$bintoScan}\n";
//echo "Qty: {$qtytopick}\n";
    $SQL = <<<SQL
insert into NOUPC 
(source,problem,userId,refnum,shadow,bin,qty)
values ("PIC","NoUPC",$UserID,"{$hostordernum}",$shadow,"{$bintoScan}",{$qtytopick})

SQL;
    $rc = $db->Update($SQL);
} // log this part has no UPC

// Cancel funtion
if (isset($func) and $func == "sOrder") {
    unset($func);
    if (isset($scaninput)) unset($scaninput);
    if (isset($hostordernum)) unset($hostordernum);
    if (isset($orderFound)) unset($orderFound);
    // redirect to Pick Queue screen
    $htm = <<<HTML
 <html>
 <head>
 <script>
 window.location.href="pickQue1.php?nh={$nh}";
 </script>
 </head>
 <body>
 </body>
</html>

HTML;
    echo $htm;
    exit;

}
// end cancel


// Part not entered, re-enter it
if ($func == "pickScanPart" and trim($partnumber) == "") {
    $func = "pickGoToBin";
}

// Not Found Part
if ($func == "pickGoToBin" and $B1 == "NFP") { // check if any items have been picked, it not free the tote
    $req = array("action" => "checkAnyPicked",
        "company" => $comp,
        "user_id" => $UserID,
        "host_order_num" => $hostordernum,
        "zone" => "",
        "order_num" => $orderNumber,
    );
    $rc = restSrv($RESTSRV, $req);
    $response = (json_decode($rc, true));
//echo"<pre>257";
//var_dump(debug_backtrace());
//print_r($response);
//exit;

    if (isset($response["zeroed"]) and $response["zeroed"] > 0) { // ask if they want to attempt to pull the zeroed items

        $func = "letsPick";
        $binLocation = "";
        $msg = "Part Zero Picked";
//echo "<pre>Need to figure out zero picked strategy";
//print_r($response);
//exit;
    } // ask if they want to attempt to pull the zeroed items
    else if (isset($response["picked"]) and $response["picked"] < 1) { // nothing has been picked, clear the tote
// TODO
// need a version of release OrdTote in WMS2ERP to unset all the
// the tote reference on this order and free the tote
    } // nothing has been picked, clear the tote
} // check if any items have been picked, it not free the tote

// Make sure the scanned bin has the part we need in it
if ($func == "pickGoToBin" and isset($binLocation)) {
    $binLocation = strtoupper($binLocation);
    // check for valid bin entered
    $req = array("action" => "chkPartBin",
        "company" => $comp,
        "shadow" => $shadow,
        "whseLoc" => $binLocation
    );
    $ret = restSrv($RESTSRV, $req);
    $w = (json_decode($ret, true));
    $rc = 0;
    if (isset($w["rc"])) $rc = $w["rc"];
    unset($w);
    if ($rc < 1) $binLocation = "";
    if (trim($binLocation) == "") {
        $msg = "Invalid Bin";
        if ($scaninput == "") $msg = "";
        if (isset($skipTo) and $skipTo > 0) $msg = "";
        $func = "letsPick";
    }

    //echo "<pre>";
    //print_r($_REQUEST);
    //print_r($ret);
    //print_r($req);
    //exit;
} // end func = pickGoToBin

// check if we are using totes
if ($opt[102] == "1" and $func == "letsPick" and isset($orderFound)) {
    $msg = "";
    $order_num = $orderFound;
    $func = "enterTote";
//echo "<pre>";
//print_r($_REQUEST);
//exit;
} // end opt 102, letspick and order found

// make the review screen to use later
$reviewScreen = makeReview();


//exit;

if ($func == "enterOrd" and isset($order) and $scaninput == "") $scaninput = $order;

switch ($func) {
    case "enterOrd":
    {
        $ord1 = $ORDER;

        // set order status for all orders in ord

        if (isset($ord1["errCode"])) {
            $msg = $ord1["errText"];
            $msgcolor = "red";
            $extra_js = "";
            $mainSection = enterOrder($msg, $msgcolor);
            $mainSection .= setPlaySound($playsound);
            $mainSection .= $extra_js;
        }  // end order not found
        else { // order found
            $title = "Items to Pick";
            $color = "green";
            $mainSection = letsPick($ord1, "Pick It");
            $pg->addMenuLink("javascript:do_reset();", "Cancel");

        } // order found


        if (1 == 2) { // code to ask order # if need to later
            $extra_js = "";
            $mainSection = enterOrder("", "blue");
            $mainSection .= $extra_js;
        } // code to ask order # if need to later

        break;
    } // end case enterOrd

    case "enterTote":
    {
        $pg->addMenuLink("javascript:history.back();", "Cancel");
        //get Order by host Order num
        $ord1 = $ORDER;
        $ord = $ord[$CURLINE];

        if (!isset($toteId)) $toteId = "";
        $msg = "";
        if ($toteId <> "") {
            $req = array("action" => "chkTote",
                "company" => $comp,
                "tote_code" => $toteId
            );
            $rc = restSrv($RESTSRV, $req);
            $tc = (json_decode($rc, true));
            if (isset($tc["numRows"]) and $tc["numRows"] < 1) { // invalid tote
                if (isset($tc["errText"])) $msg = $tc["errText"];
                $toteId = "";
            } // invalid tote
        }
        if ($toteId == "") {
            $gotTote = checkTote($ord, $msg);
//echo "<pre>";
//print_r($gotTote);
//exit;
            if ($gotTote <> "false" and intval($gotTote) < 1) { // asking
                $mainSection = $gotTote;
                break;
            } // asking
            else if ($gotTote <> "false") $toteId = $gotTote;
        } // ask tote initially
        //if ($toteId <> "" and intval($toteId) > 0)
        if ($toteId <> "") { // check and update tote

            $req = array("action" => "updTote",
                "company" => $comp,
                "order_num" => $order_num,
                "host_order_num" => $hostordernum,
                "zone" => $myZones,
                "tote_id" => $toteId
            );
            $rc = restSrv($RESTSRV, $req);
            $toteInfo = (json_decode($rc, true));
            if (isset($toteInfo["errCode"])) { // error Code is Set
                $gotTote = checkTote($ord, $toteInfo["errText"]);
//echo "<pre>";
//print_r($gotTote);
//exit;
                if ($gotTote !== false) {
                    $mainSection = $gotTote;
                    break;
                } // end ask tote
            } // error Code is set
        } // end enterTote
    } // check and update tote

    case "letsPick":
    {
        $ord1 = $ORDER;
        $activeOrder = 1;
        $j = 1;
        if (isset($ORDER["curLine"])) {
            $j = $ORDER["curLine"];
            $activeOrder = $ORDER["items"][$j]["slot"];
            $status = abs($ORDER[$activeOrder]["order_stat"]);
        } else $status = 9;
        if ($status == 2 and $B1 == "Help Pick") $status = 1;
        switch ($status) {
            case 0:
            case 1:
            case 2:
            { // good to go
                if (isset($skipTo) and $skipTo > 0) $ln = $skipTo; else $ln = 0;

                $allLines = "";

                $curline = $ORDER["curLine"];
                $j = $curline;
                $line1 = $ORDER["items"];
                if (isset($skipTo)) {
                    if ($skipTo > 0 and $skipTo == $curline) $j = 0;
                }
                if ($j == $curline) $j = 0;
                //if (count($line1) > 0) $allLines=displayItems($line1,$j);
                //if (count($ord1) > 0) $allLines=displayItems($ord1,$j);
                $allLines = displayItems($ord1, $j);

                $lln = 1;
                if (isset($skipTo)) $lln = $skipTo;
                if (isset($_SESSION["wms"]["Pick"])
                    and count($_SESSION["wms"]["Pick"]) > 0
                    and isset($line1[$lln]["zero_picked"])) { // begin zero Pick
                    $found = false;
                    foreach ($_SESSION["wms"]["Pick"] as $jj => $pk) {
                        //echo " zero={$pk["zeroed"]} ?= {$line1[1]["zero_picked"]}\n";
                        if ($pk["line_num"] == $line1[$lln]["line_num"]) {
                            if ($pk["zeroed"] > 0) $found = "true";
                        }
                    }
                    if ($found) {
                        //unset($line1);

                        if (!isset($cur_loc) and isset($binToScan)) $cur_loc = $binToScan;
                        else $cur_loc = "";
                        $tmp1 = chkIfMore($orderNumber, $ord, $zones, $toteId, $cur_loc);
//Temp
//$tmp1=true;
//if ($pickingComplete) $tmp1=false;
// end Temp
                        if ($tmp1 == false) $tmp = checkDrop($opt[103], $thisprogram, $nh, $ord);
                        if (count($tmp) > 0) { // ned 2 drop
                            foreach ($tmp as $w => $val) {
                                $$w = $val;
                            }
                        } // ned 2 drop
                        //$msg="Order {$hostordernum} Complete";
                        // set order stat to -2
                        $req = array("action" => "setZeroStat",
                            "company" => 1,
                            "order_num" => $orderNumber);
                        $rc = restSrv($RESTSRV, $req);
                        break;
                    }
                } // End zero pick

                $lln = $curline;
                //if (isset($skipTo)) $lln=$skipTo;
                $lineCount = count($line1);
                if (isset($ORDER["pickLine"])) {
                    $line = $ORDER["pickLine"];
                    //flag order as being picked
                    $req = array("action" => "flagOrder",
                        "company" => 1,
                        "user_id" => $UserID,
                        "order_num" => $line["ord_num"],
                        "line_num" => $line["line_num"],
                        "pull_num" => $line["pull_num"],
                        "zone" => $line["whse_loc"]
                    );
                    $rc = restSrv($RESTSRV, $req);
                    $updrc = (json_decode($rc, true));
                    $partInfo = chkPart(".{$line["shadow"]}", $comp);
                    $otherLoc = "";
                    if (isset($partInfo["WhseLoc"])) {
                        foreach ($partInfo["WhseLoc"] as $rec => $loc) {
                            if ($loc["whs_location"] <> $line["whse_loc"]) {
                                $otherLoc .= <<<HTML
  <input type="hidden" name="otherLoc[]" id="othLoc[]" value="{$loc["whs_location"]}|{$loc["whs_qty"]}">

HTML;
                            }
                        } // end foreach whseloc
                    } // end isset WhseLoc
                    if ($otherLoc == "") {
                        $otherLoc .= <<<HTML
  <input type="hidden" name="otherLoc[]" id="othLoc[]" value="">

HTML;
                    }
                    if (!isset($msg)) $msg = "";
                    $mainSection = pickBin($msg, $ORDER[$activeOrder], $line);
                    $title = "Picking";
                } // end count line1 < 1
                break;
            } // good to go
            case 200: // should be 2
            { // uh ooh, someone else may have picked up the order or the order is deleted
                echo "<pre> someone else picked up this order";
                print_r($ord1);
                exit;
                break;
            } // uh ooh, someone else may have picked up the order or the order is deleted
            case 3: // in packing aleady
            {
                $msg = "Picking complete for Order {$hostordernum}, currently in Packing";
                $msg .= ", Status:{$status}";
                $mainSection = reDirect($thisprogram, $nh, $msg);
                break;
            }
            case 4: // in shipping aleady
            {
                $msg = "Picking complete for Order {$hostordernum}, currently in Packing";
                $msg .= ", Status:{$status}";
                $mainSection = reDirect($thisprogram, $nh, $msg);
                break;
            }
            case 5:
            case 6:
            case 7:
            { // order is complete
                $msg = "Picking complete for Order {$hostordernum}";
                $msg .= ", Status:{$status}";
                $mainSection = reDirect($thisprogram, $nh, $msg);
                break;
            } // order is complete
            default:
            { // uh ooh, order is no longer on file
                $msg = "Can't find Order {$hostordernum}";
                $mainSection = reDirect($thisprogram, $nh, $msg);
                break;
            } // uh ooh, order is no longer on file
        } // end switch status
        break;
    } // end case letsPick

    case "pickGoToBin":
    {
//get pick line item, display screen for user to pick the part
// if qty > 1, let em enter it, but return submits 1, they may have to scan more parts
        //get Order by host Order num
        $ord1 = $ORDER;
        $activeOrder = 1;
        $j = 1;
        if (isset($ORDER["curLine"])) {
            $j = $ORDER["curLine"];
            $activeOrder = $ORDER["items"][$j]["slot"];
        }

        if (isset($ord1[$activeOrder])) {
            $ord = $ord1[$activeOrder];
        } else {
            if (isset($ord1["errCode"]) and $ord1["errCode"] == 1) { // there are no more items in this zone to pick for this order
                $tmp = checkDrop($opt[103], $thisprogram, $nh, $ord);
                if (count($tmp) > 0) { // ned 2 drop
                    foreach ($tmp as $w => $val) {
                        $$w = $val;
                    }
                } // ned 2 drop
            } // there are no more items in this zone to pick for this order
            else {
//echo "<pre>";
//print_r($_REQUEST);
//print_r($req);
//print_r($ord1);
                die("Error: order number {$hostordernum} not found at line:" . __LINE__);
            }
        }

        if (!isset($lineNumber)) $lineNumber = 0;
        if (!isset($pullnum)) $pullnum = 0;
        $curline = $ORDER["curLine"];
        $j = $curline;
        $line1 = $ORDER["items"];

        if (isset($line1[$curline])) {
            $line = $line1[$curline];
            //check if bin entered is the same as itempull bin
            if (!isset($binLocation)) $binLocation = "";
            if ($binLocation <> "" and $bintoScan <> $binLocation) { // user is changing bin, update Itempull and allocation
                $req = array("action" => "chgPickBin",
                    "company" => $comp,
                    "user_id" => $UserID,
                    "order_num" => $orderNumber,
                    "line_num" => $lineNumber,
                    "pull_num" => $pullnum,
                    "origBin" => $bintoScan,
                    "newBin" => $binLocation,
                    "shadow" => $shadow,
                    "uom" => $uom,
                    "qty" => $qtytopick
                );
                $rc = restSrv($RESTSRV, $req);
                // check rc here, make sure it worked, 1 = OK
                $line["whse_loc"] = $binLocation;
            } // user is changing bin, update Itempull and allocation
            if (!isset($msg)) $msg = "";
            $title = "Picking";
            $color = "blue";
            $part = "{$line["p_l"]} {$line["part_number"]}";
            $hiddens = <<<HTML
  <input type="hidden" name="func" id="func" value="pickScanPart">
  <input type="hidden" name="nh" value="{$nh}">
{$myOrders}
  <input type="hidden" name="hostordernum" id="hostordernum" value="{$hostordernum}">
  <input type="hidden" name="toteId" id="toteId" value="{$toteId}">
  <input type="hidden" name="bintoScan" id="bintoScan" value="{$line["whse_loc"]}">
  <input type="hidden" name="orderNumber" id="orderNumber" value="{$line["ord_num"]}">
  <input type="hidden" name="lineNumber" id="lineNumber" value="{$line["line_num"]}">
  <input type="hidden" name="skipTo" id="skipTo" value="0">
  <input type="hidden" name="curLine" id="curLine" value="{$CURLINE}">
  <input type="hidden" name="pullnum" id="pullnum" value="{$line["pull_num"]}">
  <input type="hidden" name="shadow" id="shadow" value="{$line["shadow"]}">
  <input type="hidden" name="p_l" id="p_l" value="{$line["p_l"]}">
  <input type="hidden" name="part_number" id="part_number" value="{$line["part_number"]}">
  <input type="hidden" name="part_desc" id="part_desc" value="{$line["part_desc"]}">
  <input type="hidden" name="uom" id="uom" value="{$line["uom"]}">
  <input type="hidden" name="qtytopick" id="qtytopick" value="{$line["qtytopick"]}">
  <input type="hidden" name="qtypicked" id="qtypicked" value="{$line["qty_picked"]}">
HTML;
            $color = "blue";
            $qty = $line["qtytopick"] - $line["qty_picked"];
            $fieldPrompt = "Scan Part";
            $fieldPlaceHolder = "Scan Part {$part}";
            $fieldId = " id=\"part_number\"";
            $fieldTitle = " title=\"at Bin: {$line["whse_loc"]}, Scan {$part}\"";
            $msg = <<<HTML
at Bin {$line["whse_loc"]}, Scan {$part}&nbsp;&nbsp;&nbsp; (qty {$qty} {$line["uom"]})
HTML;
            $msg2 = "";
            if ($qty > 1) {
                $msg2 = "<span class=\"w3-red\">Total Quantity is <strong>{$qty}</strong></span>";
            }
            if ($origMsg <> "") {
                $a = $msg2;
                $msg2 = $origMsg;
                $origMsg = $a;
                unset($a);
            }
            $data = array(
                "formName" => "form1",
                "formAction" => $thisprogram,
                "hiddens" => $hiddens,
                "color" => "w3-{$color}",
                "focusField" => "partnumber",
                "msg" => $msg,
                "msg2" => $msg2,
                "partOnChange" => "do_submit();",
                "partPrompt" => $fieldPrompt,
                "partType" => "text",
                "partField" => "partnumber",
                "partValue" => "",
                "partPlaceHolder" => $fieldPlaceHolder,
                "partId" => $fieldId,
                "partTitle" => $fieldTitle,
                "qtyOnChange" => "",
                "qtyPrompt" => "Qty",
                "qtyField" => "qty",
                "qtyType" => "number",
                "qtyValue" => 1,
                "qtyPlaceHolder" => "",
                "qtyFieldId" => "qtyid",
                "qtyuom" => "&nbsp;{$line["uom"]}",
                "qtyTitle" => "Adjust Quantity",
                "bottomLeft" => "at Bin: {$line["whse_loc"]}",
                "bottomCenter" => "Scan {$part} (qty {$qty} {$line["uom"]})",
                "bottomRight" => "",
                "toteId" => $toteId,
                "origMsg" => $origMsg,
                "function" => ""
            );
            $mainSection = frmtScreen($data, $thisprogram, "pickPart1");
            $msg = "";
        } // end line[1] is set
        else {
// Here is the problem, need to check if more parts are on the order
// if so, go pick those parts
// TODO , should just redirect to itself, to set the picking complete flag

            $tmp = array();
//echo "<pre>";
//print_r(get_defined_vars());
//exit;
            $cur_loc = $bintoScan;
            $tmp1 = chkIfMore($orderNumber, $ord, $zones, $toteId, $cur_loc);
//Temp
//$tmp1=true;
//if ($pickingComplete) $tmp1=false;
// end Temp
            if ($tmp1 == false) $tmp = checkDrop($opt[103], $thisprogram, $nh, $ord);
//echo "<pre>at 601";
//print_r($tmp);
//exit;
            if (count($tmp) > 0) { // ned 2 drop
                foreach ($tmp as $w => $val) {
                    $$w = $val;
                }
            } // ned 2 drop
            else { // pick is done
                $msg = "Order {$hostordernum} Complete";
                $mainSection = reDirect($thisprogram, $nh, $msg);
            } // pick is done
        }

        break;
    } // end case pickGoToBin
    case "pickScanPart":
    {
//get pick line item, display screen for user to pick the part
// if qty > 1, let em enter it, but return submits 1, they may have to scan more parts
        //get Order by host Order num
        $ord1 = $ORDER;
        $activeOrder = 1;
        $j = 1;
        if (isset($ORDER["curLine"])) {
            $j = $ORDER["curLine"];
            $activeOrder = $ORDER["items"][$j]["slot"];
        }

        if (isset($ord1[$activeOrder])) {
            $ord = $ord1[$activeOrder];
        }

        $curline = $ORDER["curLine"];
        $j = $curline;
        $line1 = $ORDER["items"];

        if (isset($line1[$curline])) {
            $line = $line1[$curline];
            $part = chkPart($partnumber, $comp);
            if ($part["numRows"] == 1) { // got the part check to make sure it's correct
                $partOK = false;
                $binOK = false;
                $qtyOk = false;
                $lineDone = false;
                if (!isset($part["Result"]["shadow_number"])) $entered_shadow = 0;
                else $entered_shadow = $part["Result"]["shadow_number"];
//echo "<pre> {$entered_shadow} -- {$shadow}";
//print_r($part);
//exit;
                if ($entered_shadow <> $shadow) { // oops, wrong part
                    $part = "{$line["p_l"]} {$line["part_number"]}";
                    $hiddens = <<<HTML
  <input type="hidden" name="func" id="func" value="pickScanPart">
  <input type="hidden" name="nh" value="{$nh}">
{$myOrders}
  <input type="hidden" name="hostordernum" id="hostordernum" value="{$hostordernum}">
  <input type="hidden" name="toteId" id="toteId" value="{$toteId}">
  <input type="hidden" name="bintoScan" id="bintoScan" value="{$bintoScan}">
  <input type="hidden" name="orderNumber" id="orderNumber" value="{$orderNumber}">
  <input type="hidden" name="lineNumber" id="lineNumber" value="{$lineNumber}">
  <input type="hidden" name="skipTo" id="skipTo" value="0">
  <input type="hidden" name="curLine" id="curLine" value="{$CURLINE}">
  <input type="hidden" name="pullnum" id="pullnum" value="{$pullnum}">
  <input type="hidden" name="shadow" id="shadow" value="{$shadow}">
  <input type="hidden" name="p_l" id="p_l" value="{$p_l}">
  <input type="hidden" name="part_number" id="part_number" value="{$part_number}">
  <input type="hidden" name="part_desc" id="part_desc" value="{$part_desc}">
  <input type="hidden" name="uom" id="uom" value="{$uom}">
  <input type="hidden" name="qtytopick" id="qtytopick" value="{$qtytopick}">
  <input type="hidden" name="qtypicked" id="qtypicked" value="{$qtypicked}">

HTML;
                    $color = "red";
                    $qty = $qtytopick - $qtypicked;
                    $fieldPrompt = "Scan Part";
                    $fieldPlaceHolder = "Scan Part {$part}";
                    $fieldId = " id=\"part_number\"";
                    $fieldTitle = " title=\"at Bin: {$bintoScan}, Scan {$part}\"";
                    $msg = <<<HTML
at Bin {$line["whse_loc"]}, Scan {$part}&nbsp;&nbsp;&nbsp; (qty {$qty} {$uom})
HTML;
                    $msg2 = "Wrong Part, you Entered {$partnumber}, Need {$part_number} ";
                    if ($entered_shadow == 0) $msg2 = "Invalid Part, Need {$part_number} ";

                    if ($qty > 1) {
                        $msg2 .= "<span class=\"w3-red\">Total Quantity is <strong>{$qty}</strong></span>";
                    }
                    if ($origMsg <> "") {
                        $a = $msg2;
                        $msg2 = $origMsg;
                        $origMsg = $a;
                        unset($a);
                    }
                    $data = array(
                        "formName" => "form1",
                        "formAction" => $thisprogram,
                        "hiddens" => $hiddens,
                        "color" => "w3-{$color}",
                        "focusField" => "partnumber",
                        "msg" => $msg,
                        "msg2" => $msg2,
                        "partOnChange" => "do_submit();",
                        "partPrompt" => $fieldPrompt,
                        "partType" => "text",
                        "partField" => "partnumber",
                        "partValue" => "",
                        "partPlaceHolder" => $fieldPlaceHolder,
                        "partId" => $fieldId,
                        "partTitle" => $fieldTitle,
                        "qtyOnChange" => "",
                        "qtyPrompt" => "Qty",
                        "qtyField" => "qty",
                        "qtyType" => "number",
                        "qtyValue" => 1,
                        "qtyPlaceHolder" => "",
                        "qtyFieldId" => "qtyid",
                        "qtyuom" => "&nbsp;{$line["uom"]}",
                        "qtyTitle" => "Adjust Quantity",
                        "bottomLeft" => "at Bin: {$line["whse_loc"]}",
                        "bottomCenter" => "Scan {$part} (qty {$qty} {$line["uom"]})",
                        "bottomRight" => "",
                        "toteId" => $toteId,
                        "origMsg" => $origMsg,
                        "function" => ""
                    );
                    $mainSection = frmtScreen($data, $thisprogram, "pickPart1");
                    $msg = "";
                    break;
                } // oops, wrong part
                if ($part["Result"]["shadow_number"] == $line["shadow"]) { // we have the correct part
                    //echo "part is correct";
                    $partOK = true;
                    //check that this part is in this bin
                    $binNum = 0;
                    foreach ($part["WhseLoc"] as $rec => $wl) {
                        if ($wl["whs_location"] == $line["whse_loc"]) {
                            $binOK = true;
                            $binNum = $rec;
                            break;
                        } //good bin
                    } // end for each whseloc
//       if ($binNum > 0) echo ", good Bin"; else echo ", Incorrect Bin";
                    //check that we have not more that the correct qty
                    if ($qty > $line["qtytopick"]) { // they picked to many
                        //echo " OVERAGE, you picked to many, please re-scan and enter the correct qty";
                        $msg = "OVERAGE, only {$line["qtytopick"]} needed, picked {$qty},<br> please re-scan and enter the correct qty";

                        $htm = <<<HTML
<!DOCTYPE html>
<html>
<!--reDirect1 -->
<body onload="document.form1.submit()">
<form name="form1" action="{$thisprogram}" method="get">
 <input type="hidden" name="func" id="func" value="pickGoToBin">
  <input type="hidden" name="nh" value="{$nh}">
{$myOrders}
  <input type="hidden" name="hostordernum" id="hostordernum" value="{$hostordernum}">
  <input type="hidden" name="toteId" id="toteId" value="{$toteId}">
  <input type="hidden" name="bintoScan" id="bintoScan" value="{$line["whse_loc"]}">
  <input type="hidden" name="orderNumber" id="orderNumber" value="{$line["ord_num"]}">
  <input type="hidden" name="lineNumber" id="lineNumber" value="{$line["line_num"]}">
  <input type="hidden" name="skipTo" id="skipTo" value="0">
  <input type="hidden" name="curLine" id="curLine" value="{$CURLINE}">
  <input type="hidden" name="pullnum" id="pullnum" value="{$line["pull_num"]}">
  <input type="hidden" name="shadow" id="shadow" value="{$line["shadow"]}">
  <input type="hidden" name="p_l" id="p_l" value="{$line["p_l"]}">
  <input type="hidden" name="part_number" id="part_number" value="{$line["part_number"]}">
  <input type="hidden" name="part_desc" id="part_desc" value="{$line["part_desc"]}">
  <input type="hidden" name="uom" id="uom" value="{$line["uom"]}">
  <input type="hidden" name="qtytopick" id="qtytopick" value="{$line["qtytopick"]}">
  <input type="hidden" name="qtypicked" id="qtypicked" value="{$line["qty_picked"]}">
  <input type="hidden" name="msg" id="msg" value="{$msg2}">
</form>
</body>
</html>

HTML;
                        $fp = fopen("/tmp/reDirect.log", "a");
                        fwrite($fp, "$htm\n");
                        fwrite($fp, "-----------------------------------------------------------\n");
                        fclose($fp);

                        echo $htm;
                        exit;
                    } // they picked to many
                    else $qtyOK = true;
                    //if ($qtyOK) echo ", Qty is Correct";
                    $msg = "";
                    if ($qty < $line["qtytopick"]) {
                        $msg = "Need " . ($line["qtytopick"] - ($line["qty_picked"] + $qty)) . " more from this Bin";
                    } else $lineDone = true;
                    //Update what we picked
                    if (!isset($toteId)) $toteId = 0;
                    $req = array("action" => "updPickQty",
                        "company" => $comp,
                        "user_id" => $UserID,
                        "host_order_num" => $hostordernum,
                        "zone" => "",
                        "whseLoc" => $bintoScan,
                        "order_num" => $orderNumber,
                        "line_num" => $lineNumber,
                        "pull_num" => $pullnum,
                        "shadow" => $shadow,
                        "qtyPicked" => $qty,
                        "zeroed" => 0,
                        "tote_id" => $toteId,
                        "uom" => $uom,
                        "p_l" => $p_l,
                        "part_number" => $part_number
                    );
                    $rc = restSrv($RESTSRV, $req);
                    $response = (json_decode($rc, true));
                    // save line item in session for review
                    if (isset($_SESSION["wms"]["Pick"])) $j = count($_SESSION["wms"]["Pick"]);
                    else $j = 0;
                    $j++;
                    $_SESSION["wms"]["Pick"][$j] = $req;
                    // end save line item in session for review
                    if (!$lineDone) { // more to pick in this bin
                        $htm = <<<HTML
<!DOCTYPE html>
<html>
<!--reDirect2 -->
<body onload="document.form1.submit()">
<form name="form1" action="{$thisprogram}" method="get">
 <input type="hidden" name="func" id="func" value="pickGoToBin">
  <input type="hidden" name="nh" value="{$nh}">
{$myOrders}
  <input type="hidden" name="hostordernum" id="hostordernum" value="{$hostordernum}">
  <input type="hidden" name="toteId" id="toteId" value="{$toteId}">
  <input type="hidden" name="bintoScan" id="bintoScan" value="{$line["whse_loc"]}">
  <input type="hidden" name="orderNumber" id="orderNumber" value="{$line["ord_num"]}">
  <input type="hidden" name="lineNumber" id="lineNumber" value="{$line["line_num"]}">
  <input type="hidden" name="skipTo" id="skipTo" value="0">
  <input type="hidden" name="curLine" id="curLine" value="{$CURLINE}">
  <input type="hidden" name="pullnum" id="pullnum" value="{$line["pull_num"]}">
  <input type="hidden" name="shadow" id="shadow" value="{$line["shadow"]}">
  <input type="hidden" name="p_l" id="p_l" value="{$line["p_l"]}">
  <input type="hidden" name="part_number" id="part_number" value="{$line["part_number"]}">
  <input type="hidden" name="part_desc" id="part_desc" value="{$line["part_desc"]}">
  <input type="hidden" name="uom" id="uom" value="{$line["uom"]}">
  <input type="hidden" name="qtytopick" id="qtytopick" value="{$line["qtytopick"]}">
  <input type="hidden" name="qtypicked" id="qtypicked" value="{$line["qty_picked"]}">
  <input type="hidden" name="msg" id="msg" value="{$msg}">
</form>
</body>
</html>

HTML;
                        $fp = fopen("/tmp/reDirect.log", "a");
                        fwrite($fp, "$htm\n");
                        fwrite($fp, "-----------------------------------------------------------\n");
                        fclose($fp);

                        echo $htm;
                        exit;
                    } // more to pick in this bin
                } // we have the correct part
                else { // not the correct part
//Add code to abandon line and redo the picking of this part, and log the error
//echo "not the correct part";
//exit;
                } // not the correct part
            } // got the part, check to make sure it's correct
            else if ($part["numRows"] > 1) { // uh ohh, the have more than 1 part
// whoah, looks like a dupe in ALTERNAT, and log the error
                $mainSection = frmtChoosePart($part, $thisprogram);
//echo "<pre>uh ooh, the have more than 1 part";
////print_r($part);
//$vars=get_defined_vars();
//print_r($vars);
//exit;
                break;
            }  // uh ohh, the have more than 1 part
            else { // nof part
// invalid part do the same as not the correct part, and log the error
                echo "Invalid part";
                print_r($part);
                exit;
            } // nof part
//echo "<pre> request";
//print_r($_REQUEST);
//echo "line";
//print_r($line1);
//echo "part";
//print_r($part);
//exit;
            //then check if more parts to pick on this order
            if (isset($binToScan)) $cur_loc = $binToScan;
            else $cur_loc = "";

            $next_line = $ORDER["items"][$curline];

            $next_loc = $next_line["whse_loc"];
            if ($next_loc == $cur_loc) { //next part is in this bin goto pickGoToBin
                $htm = <<<HTML
<!DOCTYPE html>
<html>
<!--reDirect3 -->
<body onload="document.form1.submit()">
 <form name="form1" action="{$thisprogram}" method="get">
  <input type="hidden" name="func" id="func" value="pickGoToBin">
  <input type="hidden" name="nh" value="{$nh}">
{$myOrders}
  <input type="hidden" name="hostordernum" id="hostordernum" value="{$hostordernum}">
  <input type="hidden" name="toteId" id="toteId" value="{$toteId}">
  <input type="hidden" name="bintoScan" id="bintoScan" value="{$next_line["whse_loc"]}">
  <input type="hidden" name="orderNumber" id="orderNumber" value="{$next_line["ord_num"]}">
  <input type="hidden" name="lineNumber" id="lineNumber" value="{$next_line["line_num"]}">
  <input type="hidden" name="skipTo" id="skipTo" value="0">
  <input type="hidden" name="curLine" id="curLine" value="{$CURLINE}">
  <input type="hidden" name="pullnum" id="pullnum" value="{$next_line["pull_num"]}">
  <input type="hidden" name="shadow" id="shadow" value="{$next_line["shadow"]}">
  <input type="hidden" name="p_l" id="p_l" value="{$next_line["p_l"]}">
  <input type="hidden" name="part_number" id="part_number" value="{$next_line["part_number"]}">
  <input type="hidden" name="part_desc" id="part_desc" value="{$next_line["part_desc"]}">
  <input type="hidden" name="uom" id="uom" value="{$next_line["uom"]}">
  <input type="hidden" name="qtytopick" id="qtytopick" value="{$next_line["qtytopick"]}">
  <input type="hidden" name="qtypicked" id="qtypicked" value="{$next_line["qty_picked"]}">
 </form>
HTML;
                $fp = fopen("/tmp/reDirect.log", "a");
                fwrite($fp, "$htm\n");
                fwrite($fp, "-----------------------------------------------------------\n");
                fclose($fp);

                echo $htm;
                exit;
            } //next part is in this bin goto pickGoToBin
            else { // goto letsPick different bin
                // if not, display complete screen, if option is on to drop, show drop screen
//echo "<pre>1164";
//var_dump(debug_backtrace());
//print_r($_REQUEST);
//exit;
                $htm = <<<HTML
<!DOCTYPE html>
<html>
<!--reDirect4 -->
<body onload="document.form1.submit()">
<form name="form1" action="{$thisprogram}" method="get">
 <input type="hidden" name="func" value="letsPick">
 <input type="hidden" name="nh" value="{$nh}">
{$myOrders}
 <input type="hidden" name="orderNumber" id="orderNumber" value="{$line["ord_num"]}">
 <input type="hidden" name="hostordernum" id="hostordernum" value="{$hostordernum}">
  <input type="hidden" name="toteId" id="toteId" value="{$toteId}">
</form>

HTML;
                $fp = fopen("/tmp/reDirect.log", "a");
                fwrite($fp, "$htm\n");
                fwrite($fp, "-----------------------------------------------------------\n");
                fclose($fp);

                echo $htm;
                exit;
            } // goto letsPick different bin
        } // there is more to pick next_line[1] isset
        else { // order is complete, go to pickComplete
            if ($pickingComplete) $mainSection = pickComplete($thisprogram, $nh, $ord, $scaninput);
            if (1 == 2) {
                $htm = <<<HTML
<!DOCTYPE html>
<html>
<!--reDirect5 -->
<body onload="document.form1.submit()">
<form name="form1" action="{$thisprogram}" method="get">
 <input type="hidden" name="func" value="pickComplete">
 <input type="hidden" name="nh" value="{$nh}">
{$myOrders}
 <input type="hidden" name="orderNumber" id="orderNumber" value="{$line["ord_num"]}">
 <input type="hidden" name="hostordernum" id="hostordernum" value="{$hostordernum}">
  <input type="hidden" name="toteId" id="toteId" value="{$toteId}">
</form>

HTML;
                $fp = fopen("/tmp/reDirect.log", "a");
                fwrite($fp, "$htm\n");
                fwrite($fp, "-----------------------------------------------------------\n");
                fclose($fp);


                echo $htm;
                exit;
            } // end 1 == 2
        } // order is complete, go to pickComplete
        break;
    } // end pickScanPart

    case "pickComplete":
    {
        //this is where you land after picking is complete
        $tmp = checkDrop($opt[103], $thisprogram, $nh, $ord);
//echo "<pre>";
//print_r($tmp);
//exit;
        if (count($tmp) > 0) {
            foreach ($tmp as $w => $val) {
                $$w = $val;
            }
        }
        break;
    } // end pickComplete
    case "Drop":
    {
        //update drop zone info and reDirect, scaninput contains drop zone
        if (!isset($scaninput)) $scaninput = "";
// TODO loop here on all orders to drop or release
        if ($pickingComplete) $mainSection = pickComplete($thisprogram, $nh, $ord, $scaninput);
        break;
    } // end Drop
} // end switch func

//******************************************************

//Display Header

if (trim($reviewScreen) <> "") {
    $pg->addMenuLink("javascript:showReview();", "Review");
}
if (isset($lineCount)) {
    $pg->infoLine = "Zones: {$myZones} - Lines left to pick: {$lineCount}";
} // end lineCount is set

$pg->viewport = "1.0";
$pg->dispLogo = false;
if (isset($ord["host_order_num"])) $title = "Picking";
if (isset($title)) $pg->title = $title;
if (isset($color)) $pg->color = $color; else $color = "blue";
$ejs = "";
if (isset($nh) and $nh > 0) {
    $pg->noHeader = true;
    $ejs = <<<HTML
<script>
 if ( window !== window.parent ) 
 {
  parent.document.getElementById('pageTitle').innerHTML="{$pg->title}";
 }
</script>

HTML;
} else $nh = 0;
$reshtm = <<<HTML
  document.form1.func.value=arg;
  document.form1.submit();

HTML;
if ($fPQ > 0) $reshtm = "history.back();";

$pg->title = $title;
if ($color == "green") $color = "#47d147";
if (!isset($fPQ)) $fPQ = "0";
$js .= <<<HTML
{$ejs}
<script>
function do_reset(arg)
{
{$reshtm}
}

</script>

<script>
 function plusMinus(fld,flag)
 {
  var qty=document.getElementById(fld);
  if (flag > 0) qty.value++;
  else qty.value--;
  return true;
 }
</script>
<style>
input,
textarea {
  border: 1px solid #eeeeee;
  box-sizing: border-box;
  margin: 0;
  outline: none;
  padding: 10px;
}

input[type="button"] {
  -webkit-appearance: button;
  cursor: pointer;
}

input::-webkit-outer-spin-button,
input::-webkit-inner-spin-button {
  -webkit-appearance: none;
}

.input-group {
  clear: both;
  margin: 5px 0;
  position: relative;
}

.input-group input[type='button'] {
  background-color: #eeeeee;
  min-width: 38px;
  width: auto;
  transition: all 300ms ease;
}

.input-group .button-minus,
.input-group .button-plus {
  font-weight: bold;
  height: 38px;
  padding: 0;
  width: 38px;
  position: relative;
}

.input-group .quantity-field {
  position: relative;
  color: black;
  height: 38px;
  left: -6px;
  text-align: center;
  width: 22px;
  display: inline-block;
  font-size: 13px;
  margin: 0 0 5px;
  resize: vertical;
}

.button-plus {
  left: -13px;
}

input[type="number"] {
  -moz-appearance: textfield;
  -webkit-appearance: none;
}
</style>
<script src="/jq/shortcut.js" type="text/javascript"></script>
<script>
shortcut.add("return",function() {
  document.form1.submit();
});
</script>

<style>
input[type=text], input[type=number], select, textarea {
width: 100%;
padding: 12px;
border: 1px solid #ccc;
border-radius: 4px;
resize: vertical;
color: black;
}

.toteDiv
{
 position: relative;
 top: 0px;
 float: right;
 right: 10px;
}

.myRed
{
color:#fff!important;
background-color:#ff7777!important
}
html,body,h1,h2,h3,h4,h5 {font-family: "Raleway", sans-serif}
.binbutton {
    background-color: #2196F3;
    border: none;
    border-radius: 8px;
    color: white;
    padding: 15px 32px;
    text-align: center;
    text-decoration: none;
    display: inline-block;
    font-size: 20px;
    margin: 4px 2px;
    cursor: pointer;
}
.binbutton:disabled {
    background-color: #dddddd;
}
.binbutton:enabled {
    background-color: {$color};
}
</style>
HTML;
$pg->jsh = $js;
if ($msg <> "") $pg->msg = $msg;
if (!isset($otherScripts)) $otherScripts = "";
if (!isset($mainSection)) {
    $msg = "{$func} MainSection not set";
    $mainSection = reDirect($thisprogram, $nh, $msg);
//temp
    echo $msg;
    exit;
//temp
} // end mainSection not set
if ($msgcolor <> "") {
    $pg->color = $msgcolor;
}

$js = collapseCss();
$js .= collapseJs();
$pg->js = $js;
$pg->Display();
//echo "<textarea>{$mainSection}</textarea>";
//exit;
//Rest of page
if (!isset($allLines)) $allLines = "";
$htm = <<<HTML
  {$mainSection}
  {$otherScripts}
  {$allLines}
  {$reviewScreen}
 </body>
</html>

HTML;
echo $htm;
//echo "<pre>";
//print_r($_REQUEST);


function frmtScreen($data, $thisprogram, $temPlate = "generic1", $incFunction = true)
{
    $ret = "";
    $parser = new parser;
    $parser->theme("en");
    $parser->config->show = false;
    $ret = $parser->parse($temPlate, $data);
    if ($incFunction) {
        $ret .= <<<HTML
<script>
function do_submit()
{
 document.{$data["formName"]}.submit();
}
</script>
HTML;
    }
    return $ret;

} // end frmtScreen

function setPlaySound($playsound)
{
    $htm = "";
    if ($playsound) {
        $htm = <<<HTML
<audio controls autoplay hidden>
  <source src="/Bluejay/sounds/psycho.wav" type="audio/wav">
</audio>
HTML;
    }
    return $htm;
} // end setPlaySound

function enterOrder($msg, $color)
{
    global $thisprogram;
    global $nh;
    global $toteId;
    global $myOrders;
    $msghtm = "";
    if (trim($msg) <> "") $msghtm = <<<HTML
  <input type="hidden" name="msg" id="msg" value="{$msg}">

HTML;
    $hiddens = <<<HTML
  {$msghtm}<input type="hidden" name="nh" value="{$nh}">
{$myOrders}
  <input type="hidden" name="toteId" id="toteId" value="{$toteId}">
HTML;
    $data = array("formName" => "form1",
        "formAction" => $thisprogram,
        "hiddens" => $hiddens,
        "color" => "w3-{$color}",
        "fieldValue" => "",
        "msg" => $msg,
        "msg2" => "",
    );
    $htm = frmtScreen($data, $thisprogram, "enterOrd");
    return $htm;

} // end enterOrder
function letsPick($ord, $B1Prompt)
{
    global $thisprogram;
    global $title;
    global $nh;
    global $fPQ;
    global $toteId;
    global $RESTSRV;
    global $myOrders;

    $orders = array();
    foreach ($ord as $key => $o) {
        if (is_numeric($key)) {
            $orders[$key] = $o;
            //$orders[$key]["slot"]=$key;
        }

    } // end foreach ord
    $items = $ord["items"];
    if (count($items) > 0)
        foreach ($items as $key => $i) {
            $qty = $i["qtytopick"] - $i["qty_picked"];
            $items[$key]["qcls"] = "";
            if ($qty < 0) {
                $qty = 0;
                $items[$key]["qcls"] = " class=\"Alt3DataTD\"";
            }
            $items[$key]["qty2pick"] = $qty;
        }

    /* got the data for al orders, now I need to revamp the display
 1st show items to pick and the order# as 1,2,3, etc...
 then show details of the order after,
 just host order#, customer, ship via
*/

//$lineItems=array();
//if (is_array($items) and count($items) > 0)
//{ // add place holder to items
    //foreach($items as $key=>$item)
    //{
    //$o=$item["ord_num"];
    //if (isset($oidx[$o])) $items[$key]["slot"]=$oidx[$o];
    //else $items[$key]["slot"]=0;
    //}
//} // add place holder to items

//echo "<pre>here";
//print_r($orders);
//print_r($items);
//exit;


    //$odate=date("m/d/Y",strtotime($ord["date_required"]));
    $odate = date("m/d/Y");
    $hiddens = <<<HTML
      <input type="hidden" name="fPQ" value="{$fPQ}">
      <input type="hidden" name="toteId" id="toteId" value="{$toteId}">

HTML;
    $color = "w3-blue";
    $data = array("formAction" => $thisprogram,
        "hiddens" => $hiddens,
        "color" => $color,
        "nh" => $nh,
        "orders" => $orders,
        "items" => $items,
        "B1Prompt" => $B1Prompt
    );
    $htm = frmtScreen($data, $thisprogram, $temPlate = "dispOrder1", $incFunction = false);
    return $htm;
} // end letsPick
function chkPart($pnum, $comp)
{
    global $main_ms;
    $ret = array();
    $ret["upc"] = $pnum;
    $ret["comp"] = $comp;
    $pr = new PARTS;
    $pnum = trim($pnum);
    $ret = $pr->chkPart($pnum, $comp);
    return $ret;
} // end chkPart

function reDirect($thisprogram, $nh, $msg)
{
    global $toteId;
    global $myOrders;
    $htm = <<<HTML
<!DOCTYPE html>
<html>
<!--reDirect6 -->
<body onload="document.form1.submit()">
<form name="form1" action="{$thisprogram}" method="get">
 <input type="hidden" name="func" id="func" value="">
  <input type="hidden" name="nh" value="{$nh}">
{$myOrders}
  <input type="hidden" name="msg" id="msg" value="{$msg}">
  <input type="hidden" name="toteId" id="toteId" value="{$toteId}">
</form>
</body>
</html>

HTML;
    return $htm;
} // end reDirect

function checkDrop($opt, $thisprogram, $nh, $ord, $msg = "")
{
    global $toteId;
    global $myOrders;
    $ret = array("msg" => "", "mainSection" => "");
    if ($opt == "1") { //we need to drop
//Need to get all totes for the order to make sure the user drops all totes
        $hiddens = <<<HTML
  <input type="hidden" name="func" id="func" value="Drop">
  <input type="hidden" name="nh" value="{$nh}">
{$myOrders}
  <input type="hidden" name="orderNumber" value="{$orderNumber}">
  <input type="hidden" name="hostordernum" value="{$hostordernum}">
  <input type="hidden" name="toteId" id="toteId" value="{$toteId}">
HTML;
        $fieldPrompt = "Dropped at";
        $fieldPlaceHolder = "Scan or Drop Zone";
        $fieldId = " id=\"dropzone\"";
        $fieldTitle = " title=\"Scan or Enter the Drop Zone\"";
        $extra_js = "";
        $color = "blue";

        $data = array("formName" => "form1",
            "formAction" => $thisprogram,
            "hiddens" => $hiddens,
            "color" => "w3-{$color}",
            "onChange" => "do_submit();",
            "fieldType" => "text",
            "fieldValue" => "",
            "fieldPrompt" => $fieldPrompt,
            "fieldPlaceHolder" => $fieldPlaceHolder,
            "fieldName" => "scaninput",
            "fieldId" => $fieldId,
            "fieldTitle" => $fieldTitle,
            "msg" => "Drop Order {$hostordernum} in Drop Zone",
            "msg2" => "Order is Complete",
            "function" => ""
        );
        $ret["mainSection"] = frmtScreen($data, $thisprogram);
        $ret["msg"] = "Completed Picking for {$hostordernum}";
    }//we need to drop
    else { // redirect for next pick
        $ret["msg"] = "Completed Picking for {$hostordernum}";
        $ret["mainSection"] = pickComplete($thisprogram, $nh, $ord);
    } // redirect for next pick
    return $ret;
} // end checkDrop

function pickComplete($thisprogram, $nh, $ord, $dropZone = "")
{
//function pickComplete($thisprogram,$nh,$hostOrder,$order_num, $dropZone="")
    global $wmsAssets;
    global $toteId;
    global $comp;
    global $opt;
    global $RESTSRV;
    global $W2ErpSRV;
    global $myZones;
    global $UserID;
    global $db;
    $orders = array();
    $dmsg = "";
    if (is_array($ord) and count($ord) > 0) { // ord is array
        foreach ($ord as $key => $o) {
            if (is_numeric($key)) {
                $onum = $o["order_num"];
                $orders[$onum] = $o;
            }
//echo "{$key} = {$key}\n";
            if ($key == "items" and count($o) > 0) {
                foreach ($o as $key1 => $item) {
                    $onum = $item["ord_num"];
                    $i = $item["line_num"];
                    $orders[$onum]["items"][$i] = $item;
                }
            } // end count items > 0
        } // end foreach ord
    } // ord is array
    else { // ord is not array
        $num = 1;
        $orders[$num] = $ord;
    } // ord is not array

    // update zone if set
    $panelTitle = "";
    if (count($orders) > 0) {
        $msg = "";
        foreach ($orders as $key => $o) {
            $order_num = $o["order_num"];
            $hostOrder = $o["host_order_num"];
            if ($panelTitle <> "") $panelTitle .= "<br>";
            $j = count($o["items"]);
            $s = "";
            if ($j > 1) $s = "s";
            $panelTitle .= "Order {$hostOrder} ({$j} Item{$s}) is Complete";
            $SQL = <<<SQL
select count(*) as cnt from ITEMPULL
where ord_num = {$order_num}
and ( zero_picked > 0 and zpuser = {$UserID})

SQL;
            $w1 = $db->gData($SQL);
            if (isset($w1[1])) $w = $w1[1]; else $w["cnt"] = 0;
            $j = $w["cnt"];
            $s = "";
            if ($j > 1) $s = "s";
            if ($w["cnt"] > 0) $panelTitle .= " ({$j} Item{$s} Not Found)";

            if (isset($toteId) and intval($toteId) > 0 and $dropZone <> "") {
                $rc = updToteLoc($toteId, $order_num, $myZones, $dropZone);
            } // end update drop
            $rdata = relOrder($comp, $order_num, $hostOrder, $myZones, $dropZone);
            if (isset($rdata["openLines"]) and $rdata["openLines"] > 0) {
                if ($w["cnt"] == $rdata["openLines"]) { // set stat to zero picked for this user
                    $SQL = <<<SQL
 update ORDERS set order_stat = -2 where order_num = {$order_num}

SQL;
                    $rc = $db->Update($SQL);
                } // set stat to zero picked for this user
            } // end openlines
            if (isset($ret["status"])) {
                $stat = $ret["status"];
                if (strlen($msg) > 0) $msg .= "<br>";
                $msg .= $ret["msg"];
            } // end ret status is set
        } // end foreach orders
    } // end count order > 0

    $dmsg = "";
    if (isset($msg) and $msg <> "") {
        $dmsg = <<<HTML
 <div class="row">
   <div class="col-75">
       <span style="word-wrap: normal; font-weight: bold; font-size: large; text-align: cput;">{$msg}</span>
   </div>
  </div>
HTML;

    } // end msg is set
    //$panelTitle=$msg;
    $htm = <<<HTML
<!DOCTYPE html>
<html>
<head>
 <title>{$panelTitle}</title>
 <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
 <meta name="viewport" content="initial-scale=1.0, width=device-width, user-scalable=yes" />
 <script src="/jq/shortcut.js" type="text/javascript"></script>
 <script>
shortcut.add("return",function() {
  var rc=do_reset();
  return(rc);
});
 </script>


 <link rel="stylesheet" href="../Themes/Multipads/Style.css">

 <link rel="stylesheet" href="../assets/css/wms.css">
</head>
<body>
<!--Pick Complete -->
 <h3 class="FormHeaderFont" align="left">{$panelTitle}</h3>
{$dmsg}
 <br>
 <button class="binbutton" style="background-color: #A9F5BC; color: black;" id="B1" name="B1" onclick="do_reset();">Done</button>
<script>
function do_reset()
{
 document.location.href="pickQue1.php?nh={$nh}";
}
</script>
</body>
</html>

HTML;
    echo $htm;
    exit;

} // end pickComplete


function checkTote($ord, $msg = "")
{ // check/enter totes
    global $RESTSRV;
    global $nh;
    global $thisprogram;
    global $toteId;
    if (!isset($toteId)) $toteId = "";
    $color = "blue";
    if ($msg <> "") $color = "red";
    // have to scan a tote ID if option is on to use totes
    if (isset($ord)) {
        $order_num = $ord["order_num"];
        $comp = $ord["company"];
        //check if order has assigned tote
        $req = array("action" => "chkOrdTote",
            "company" => $comp,
            "order_num" => $order_num
        );
        $rc = restSrv($RESTSRV, $req);
        $toteInfo = (json_decode($rc, true));
        if (isset($toteInfo[1])) { // check all totes for my zones, if 1 found for my zone, use it
            $z = $_SESSION["wms"]["zones"];
            foreach ($toteInfo as $key => $t1) {
                if ($key <> "numRows") {
                    if (in_array($t1["last_zone"], $z) === false) {
                        unset($toteInfo[$key]);
                        $toteInfo["numRows"]--;
                    }
                } // no numRows
            } // end foreach toteinfo
        } // check all totes for my zones, if 1 found for my zone, use it
        if ($toteInfo["numRows"] < 1) { // ask for tote
            $hiddens = <<<HTML
  <input type="hidden" name="nh" value="{$nh}">
{$myOrders}
  <input type="hidden" name="hostordernum" id="hostordernum" value="{$ord["host_order_num"]}">
  <input type="hidden" name="order_num" id="order_num" value="{$ord["order_num"]}">
  <input type="hidden" name="orderNumber" id="orderNumber" value="{$ord["order_num"]}">
  <input type="hidden" name="orderFound" id="orderFound" value="{$ord["order_num"]}">
  <input type="hidden" name="scaninput" value="{$ord["order_num"]}">
  <input type="hidden" name="B1" value="">

HTML;
            $data = array("formName" => "form1",
                "formAction" => $thisprogram,
                "hiddens" => $hiddens,
                "color" => "w3-{$color}",
                "fieldValue" => $toteId,
                "msg" => "Picking Order {$ord["host_order_num"]}",
                "msg2" => $msg
            );
            $mainSection = frmtScreen($data, $thisprogram, "enterTote");
        } // ask for tote
    } // end if isset ord
    $ret = "";
    if (isset($mainSection)) $ret = $mainSection;
    if (isset($toteInfo["numRows"]) and $toteInfo["numRows"] > 0) {
        if (isset($toteInfo))
            foreach ($toteInfo as $key => $t) {
                if ($key <> "numRows")
                    $ret = $t["tote_id"];
            }
    } // end numRows isset and > 0
    if ($ret == "") $ret = "false";
    return $ret;
} // end check/enter tote
function pickBin($msg, $ord, $line)
{
    global $nh;
    global $thisprogram;
    global $otherLoc;
    global $toteId;
    global $myOrders;
    global $CURLINE;
    if (!isset($msg)) $msg = "";
    $hostordernum = $ord["host_order_num"];
    $title = "Picking";
    $color = "blue";
    $hiddens = <<<HTML
  <input type="hidden" name="func" id="func" value="pickGoToBin">
  <input type="hidden" name="nh" value="{$nh}">
{$myOrders}
  <input type="hidden" name="toteId" id="toteId" value="{$toteId}">
  <input type="hidden" name="hostordernum" id="hostordernum" value="{$hostordernum}">
  <input type="hidden" name="bintoScan" id="bintoScan" value="{$line["whse_loc"]}">
  <input type="hidden" name="orderNumber" id="orderNumber" value="{$line["ord_num"]}">
  <input type="hidden" name="lineNumber" id="lineNumber" value="{$line["line_num"]}">
  <input type="hidden" name="skipTo" id="skipTo" value="0">
  <input type="hidden" name="curLine" id="curLine" value="{$CURLINE}">
  <input type="hidden" name="pullnum" id="pullnum" value="{$line["pull_num"]}">
  <input type="hidden" name="shadow" id="shadow" value="{$line["shadow"]}">
  <input type="hidden" name="p_l" id="p_l" value="{$line["p_l"]}">
  <input type="hidden" name="part_number" id="part_number" value="{$line["part_number"]}">
  <input type="hidden" name="part_desc" id="part_desc" value="{$line["part_desc"]}">
  <input type="hidden" name="qtytopick" id="qtytopick" value="{$line["qtytopick"]}">
  <input type="hidden" name="qtypicked" id="qtypicked" value="{$line["qty_picked"]}">
{$otherLoc}
HTML;

    $color = "blue";
    $qty = $line["qtytopick"] - $line["qty_picked"];
    $fieldPrompt = "Scan Bin";
    $fieldPlaceHolder = "Scan Bin {$line["whse_loc"]} Label";
    $fieldId = " id=\"whse_loc\"";
    $fieldTitle = "Go to Bin {$line["whse_loc"]} and Scan Bin Label";
    $msg = <<<HTML
{$line["p_l"]} {$line["part_number"]} {$line["part_desc"]} (qty {$qty})
<br>
Go to Bin {$line["whse_loc"]} and Scan Bin Label
HTML;

    $data = array("formName" => "form1",
        "formAction" => $thisprogram,
        "hiddens" => $hiddens,
        "color" => "w3-{$color}",
        "onChange" => "",
        "fieldType" => "text",
        "fieldValue" => "",
        "fieldPrompt" => $fieldPrompt,
        "fieldPlaceHolder" => $fieldPlaceHolder,
        "fieldName" => "binLocation",
        "fieldId" => $fieldId,
        "fieldTitle" => $fieldTitle,
        "Qty" => $qty,
        "toteId" => $toteId,
        "msg" => $msg,
        "msg2" => "",
        "function" => ""
    );
    $msg = "";
    $ret = frmtScreen($data, $thisprogram, "pickBin");
    return $ret;
} // end pickBin

function makeReview()
{
    global $toteId;
    if (!isset($_SESSION["wms"]["Pick"])) {
        return "";
    } // end nothing to review
    $x = $_SESSION["wms"]["Pick"];
    $htm = <<<HTML
<style>
.modal {
  display: none; /* Hidden by default */
  position: fixed; /* Stay in place */
  z-index: 1; /* Sit on top */
  padding-top: 10px; /* Location of the box */
  padding-bottom: 10px; /* Location of the box */
  left: 10px;
  top: 10px;
  border-radius: 8px;
  border-color: black;
  border-style: solid;
  width: auto; /* Full width */
  height: auto; /* Full height */
  overflow: auto; /* Enable scroll if needed */
  background-color: white;
  color: black;
}

/* Modal Content */
.modal-content {
  background-color: #fefefe;
  overflow: scroll;
  margin: auto;
  padding: 20px;
  border: 1px solid #888;
  width: 80%;
}

</style>

 <div id="reviewScreen" class="modal">
  <table class="table table-bordered table-striped w3-margin">
   <tr>
    <th class"FormSubHeaderFont" colspan="6">Review</th>
   </tr>
   <tr>
    <th class="FieldCaptionTD">Tote</th>
    <th class="FieldCaptionTD">P/L</th>
    <th class="FieldCaptionTD">Part #</th>
    <th class="FieldCaptionTD">Qty</th>
    <th class="FieldCaptionTD">ZP</th>
    <th class="FieldCaptionTD">UOM</th>
    <th class="FieldCaptionTD">Bin</th>
   </tr>

HTML;
    if (count($x)) foreach ($x as $ord => $data) {
        if (!isset($data["tote_id"])) $data["tote_id"] = $toteId;
        if ($data["tote_id"] == 0) $data["tote_id"] = " ";
        if (!isset($data["zeroed"])) $data["zeroed"] = 0;
        $htm .= <<<HTML
   <tr>
    <td>{$data["tote_id"]}</th>
    <td>{$data["p_l"]}</th>
    <td>{$data["part_number"]}</th>
    <td>{$data["qtyPicked"]}</th>
    <td>{$data["zeroed"]}</th>
    <td>{$data["uom"]}</th>
    <td>{$data["whseLoc"]}</th>
   </tr>

HTML;
    } // end count and foreach x
    $htm .= <<<HTML
  </table>
 <button class="w3-margin" onclick="document.getElementById('reviewScreen').style.display='none';">Close</button>
 </div>
<script>
document.getElementById('reviewScreen').style.display="none";
function showReview()
 {
  document.getElementById('reviewScreen').style.display="block";
 }

</script>
  
HTML;
    return $htm;
}

function chkIfMore($orderNumber, $ord, $zones, $toteId, $cur_loc)
{
    global $thisprogram;
    global $nh;
    global $RESTSRV;
    global $UserID;
    global $order;
    global $myOrders;
    global $pickingComplete;

    if ($pickingComplete) return false;
    else
        if (count($ord["items"]) > 0) {
            $foundOne = false;
            foreach ($ord["items"] as $key => $i) {
                $qty = $i["qtytopick"] - $i["qty_picked"];
                $zero = $i["zero_picked"];
                if ($qty - $zero > 0) {
                    $next_line1 = $i;
                    $foundOne = true;
                }
            } // end foreach items
            if (!$foundOne) {
                $pickingComplete = true;
                return false;
            }
        } // end count items > 0

    if (1 == 2) {
        $req = array("action" => "fetchPickOrder",
            "company" => 1,
            "user_id" => $UserID,
            "order_num" => $order,
            "line_num" => 0,
            "zline" => 1710,
            "zones" => $zones
        );
        $rc = restSrv($RESTSRV, $req);
        $next_line1 = (json_decode($rc, true));
//echo "<pre> line 2015 {$skipTo}";
//print_r($req);
//print_r($line1);
//exit;
    } // end 1 == 2

    $lln = 1;
    if (isset($skipTo)) $lln = $skipTo;
    if (isset($next_line1[$lln])) { // there is more to pick next_line[1] isset
        $next_line = $next_line1[$lln];
        $next_loc = $next_line["whse_loc"];
        if ($next_loc == $cur_loc) { //next part is in this bin goto pickGoToBin
            $htm = <<<HTML
<!DOCTYPE html>
<html>
<!--reDirect3 -->
<body onload="document.form1.submit()">
 <form name="form1" action="{$thisprogram}" method="get">
  <input type="hidden" name="func" id="func" value="pickGoToBin">
  <input type="hidden" name="nh" value="{$nh}">
{$myOrders}
  <input type="hidden" name="hostordernum" id="hostordernum" value="{$hostordernum}">
  <input type="hidden" name="toteId" id="toteId" value="{$toteId}">
  <input type="hidden" name="bintoScan" id="bintoScan" value="{$next_line["whse_loc"]}">
  <input type="hidden" name="orderNumber" id="orderNumber" value="{$next_line["ord_num"]}">
  <input type="hidden" name="lineNumber" id="lineNumber" value="{$next_line["line_num"]}">
  <input type="hidden" name="skipTo" id="skipTo" value="0">
  <input type="hidden" name="curLine" id="curLine" value="{$CURLINE}">
  <input type="hidden" name="pullnum" id="pullnum" value="{$next_line["pull_num"]}">
  <input type="hidden" name="shadow" id="shadow" value="{$next_line["shadow"]}">
  <input type="hidden" name="p_l" id="p_l" value="{$next_line["p_l"]}">
  <input type="hidden" name="part_number" id="part_number" value="{$next_line["part_number"]}">
  <input type="hidden" name="part_desc" id="part_desc" value="{$next_line["part_desc"]}">
  <input type="hidden" name="uom" id="uom" value="{$next_line["uom"]}">
  <input type="hidden" name="qtytopick" id="qtytopick" value="{$next_line["qtytopick"]}">
  <input type="hidden" name="qtypicked" id="qtypicked" value="{$next_line["qty_picked"]}">
 </form>
HTML;
            $fp = fopen("/tmp/reDirect.log", "a");
            fwrite($fp, "$htm\n");
            fwrite($fp, "-----------------------------------------------------------\n");
            fclose($fp);

            echo $htm;
            exit;
        } //next part is in this bin goto pickGoToBin
        else { // goto letsPick different bin
            // if not, display complete screen, if option is on to drop, show drop screen
            $htm = <<<HTML
<!DOCTYPE html>
<html>
<!--reDirect5a -->
<body onload="document.form1.submit()">
<form name="form1" action="{$thisprogram}" method="get">
 <input type="hidden" name="func" value="letsPick">
 <input type="hidden" name="nh" value="{$nh}">
{$myOrders}
 <input type="hidden" name="orderNumber" id="orderNumber" value="{$orderNumber}">
 <input type="hidden" name="hostordernum" id="hostordernum" value="{$hostordernum}">
  <input type="hidden" name="toteId" id="toteId" value="{$toteId}">
</form>

HTML;
            $fp = fopen("/tmp/reDirect.log", "a");
            fwrite($fp, "$htm\n");
            fwrite($fp, "-----------------------------------------------------------\n");
            fclose($fp);

            echo $htm;
            exit;
        } // goto letsPick different bin
    } // there is more to pick next_line[1] isset
    else return false;

} // end chkIfMore

function validateDrop($dropLoc)
{
    global $ZONESRV;
    global $comp;
    $ZONESRV = "{$wmsIp}/{$wmsServer}/WHSEZONES_srv.php";

    $f = array("action" => "fetchSingle", "zone_company" => $comp, "zone" => $dropLoc);
    $rc = restSrv($ZONESRV, $f);
    $zones = json_decode($rc, true);
    if (isset($zones["zone_type"]) and $zones["zone_type"] == "PKG") return true;
    else return false;
} // end validateDrop


function frmtChoosePart($part, $thisprogram)
{
    global $nh;
    global $hostordernum;
    global $toteId;
    global $bintoScan;
    global $orderNumber;
    global $lineNumber;
    global $pullnum;
    global $shadow;
    global $p_l;
    global $part_number;
    global $part_desc;
    global $uom;
    global $qtytopick;
    global $qtypicked;
    global $qty;
    global $MyOrder;
    $hiddens = "";
    $cnt = $part["numRows"];
    $upc = $part["upc"];
    $detail = "";
    $htm = <<<HTML
 <div class="w3-clear"></div>
  <div class="w3-half">
   <div class="panel panel-default">
    <div class="panel-heading">
     <div class="row">
      <div class="col-md-6">
       <h3 class="panel-title">Found {$cnt} Parts matching "{$upc}"</h3>
      </div>
     </div>
    </div>
    <div class="panel-body">
     <div class="table-responsive">
      <form name="form2" action="{$thisprogram}" method="get">
      <input type="hidden" name="func" id="func" value="pickScanPart">
      <input type="hidden" name="nh" value="0">
{$myOrders}
      <input type="hidden" name="hostordernum" id="hostordernum" value="{$hostordernum}">
      <input type="hidden" name="toteId" id="toteId" value="{$toteId}">
      <input type="hidden" name="bintoScan" id="bintoScan" value="{$bintoScan}">
      <input type="hidden" name="orderNumber" id="orderNumber" value="{$orderNumber}">
      <input type="hidden" name="lineNumber" id="lineNumber" value="{$lineNumber}">
  <input type="hidden" name="skipTo" id="skipTo" value="0">
  <input type="hidden" name="curLine" id="curLine" value="{$CURLINE}">
      <input type="hidden" name="pullnum" id="pullnum" value="{$pullnum}">
      <input type="hidden" name="shadow" id="shadow" value="{$shadow}">
      <input type="hidden" name="p_l" id="p_l" value="{$p_l}">
      <input type="hidden" name="part_number" id="part_number" value="{$part_number}">
      <input type="hidden" name="part_desc" id="part_desc" value="{$part_desc}">
      <input type="hidden" name="uom" id="uom" value="{$uom}">
      <input type="hidden" name="qtytopick" id="qtytopick" value="{$qtytopick}">
      <input type="hidden" name="qtypicked" id="qtypicked" value="{$qtypicked}">
      <input type="hidden" name="qty" id="qty" value="{$qty}">
      <input type="hidden" name="partnumber" id="partnumber" value="">
      <table class="table table-bordered table-striped">
       <tr>
        <td width="1%" class="FieldCaptionTD">&nbsp;</td>
        <td width="3%" class="FieldCaptionTD">P/L</td>
        <td width="10%" class="FieldCaptionTD">Part Number</td>
        <td width="25%" class="FieldCaptionTD">Part Desc</td>
        <td align="right" width="10%" class="FieldCaptionTD">Avail</td>
       </tr>
_DETAIL_
      </table>
      </form>
     </div>
    </div>
   </div>
  </div>
 </div>
<br />
<script>
function do_sel(shadow)
{
 document.form2.partnumber.value='.' + shadow;
 document.form2.submit();
}
</script>
HTML;
    if (count($part["choose"]) > 0) {
        foreach ($part["choose"] as $rec => $l) {
            $cls = "";
            $t = "Click here to select Part Number: {$l["p_l"]} {$l["part_number"]}";
            if ($l["qty_avail"] > 0) $cls = " class=\"bg-success\"";
            $detail .= <<<HTML
       <tr onclick="do_sel({$l["shadow_number"]});">
        <td title="{$t}"><input type="checkbox" name="UPCs[]" value="{$l["shadow_number"]}"></td>
        <td>{$l["p_l"]}</td>
        <td>{$l["part_number"]}</td>
        <td>{$l["part_desc"]}</td>
        <td align="right"{$cls}>{$l["qty_avail"]}</td>
       </tr>

HTML;
        } // end foreach part
    } // end choose count > 0
    $htm = str_replace("_DETAIL_", $detail, $htm);
    return $htm;
} // end frmtChoosePart

function displayItems($ord, $allowSkip = 0)
{
    global $RESTSRV;
    global $line1;
    global $curline;
    global $CURLINE;
    global $skipTo;
    $orders = array();
    foreach ($ord as $key => $o) {
        if (is_numeric($key)) {
            $orders[$key] = $o;
            //$orders[$key]["slot"]=$key;
        }

    } // end foreach ord
    $items = $ord["items"];

//echo "<pre> line 2227 {$skipTo}";
//print_r($req);
//print_r($line1);
//exit;

    //$lln=1;
    //if (isset($skipTo)) $lln=$skipTo;
    $lln = $ord["curLine"];
    if (count($items) < 1) return "";
    $t = "Items To Pick";
    if ($allowSkip == 2) {
        $i = $items[$lln];
        $t = "Skipped To Pick {$i["p_l"]} {$i["part_number"]}";
    }
//table table-bordered table-striped w3-margin">
    $htm = <<<HTML
  <div class="w3-row-padding w3-margin-bottom">
    <div class="w3-half">
      <div class="container">
       <table class="table table-bordered table-striped overflow-auto">
        <tr>
         <th class"FormSubHeaderFont" align="left" colspan="6">{$t}</th>
        </tr>
        <tr>
         <th class="FieldCaptionTD">&nbsp;</th>
         <th class="FieldCaptionTD">Bin</th>
         <th class="FieldCaptionTD">Part #</th>
         <th class="FieldCaptionTD">ToPick</th>
         <th class="FieldCaptionTD">Slot</th>
         <th class="FieldCaptionTD">QtyOrd</th>
         <th class="FieldCaptionTD">QtyPickd</th>
         <th class="FieldCaptionTD">Avail</th>
         <th class="FieldCaptionTD">Desc</th>
        </tr>

HTML;
    $ocs = "";
    if ($allowSkip > 0) {
        $ocs = <<<HTML
 <script>
 function skipToLine(lineNum)
 {
  document.form1.skipTo.value=lineNum;
  document.form1.submit();
 }
 </script>
HTML;
    }
    $first = true;
    $selected = " class=\"Alt4DataTD\"";
    $ext = "ing";
    foreach ($items as $o => $i) {
        $qty = $i["qtytopick"] - $i["qty_picked"];
        $oc = "";
        if ($allowSkip == 1) $oc = " onclick=\"skipToLine({$o});\"";
        if ($allowSkip == 2) $oc = " onclick=\"skipToLine(0);\"";
        if ($o == $curline) $ext = "ing";
        $btn = <<<HTML
<button class="binbutton-tinyer" title="Pick this Item {$o}" name="Bs" onclick="{$o}">Pick{$ext}</button>
HTML;
        if ($allowSkip == 2) {
            $btn = <<<HTML
<button class="binbutton-tinyer" name="Bs" onclick="{$oc}">Cancel Skip</button>
HTML;
        } // end allowSkip=2
        if ($qty < 1) $btn = <<<HTML
<button class="binbutton-tinyer" name="na" disabled>---</button>
HTML;
        if ($qty > 0 and $i["zero_picked"] >= $qty) $btn = <<<HTML
<button class="binbutton-tinyer" name="na" disabled>Not Found</button>
HTML;
        $htm .= <<<HTML
        <tr{$selected}{$oc}>
         <td>{$btn}</td>
         <td nowrap>{$i["whse_loc"]}</td>
         <td nowrap>{$i["p_l"]} {$i["part_number"]}</td>
         <td align="center">{$qty}</td>
         <td align="center">{$i["slot"]}</td>
         <td align="center">{$i["qty_ord"]}</td>
         <td align="center">{$i["qty_picked"]}</td>
         <td align="center">{$i["qty_avail"]}</td>
         <td nowrap>{$i["part_desc"]}</td>
        </tr>

HTML;
        $selected = "";
        $ext = "";
    } // end foreach items
    $btn = "";
    $htm .= <<<HTML
       </table>
{$btn}
      </div>
    </div>
  </div>
 {$ocs}

HTML;

    return $htm;

} // end displayItems

function ordToPick($comp, $UserID, $order, $showall = false)
{
    global $RESTSRV;
    $sa = "";
    if ($showall) $sa = "showAll";
    //get Order by host Order num
    $req = array("action" => "orderToPick",
        "company" => $comp,
        "user_id" => $UserID,
        "host_order_num" => $order,
        "case" => $sa
    );
    $rc = restSrv($RESTSRV, $req);
    $ord1 = (json_decode($rc, true));
    return $ord1;
} // end ordToPick

function orderSlots($ord)
{
    $orders = array();
    $oidx = array();
    if (is_array($ord) and count($ord) > 0) { // ord is array
        $o = array();
        foreach ($ord as $key => $x) {
            $on = $x["order_num"];
            $o[$key] = $on; // for req
            $k = $key;
            $oidx[$on] = $k;
            $orders[$k]["slot"] = $k;
            $orders[$k]["order_num"] = $on;
            $orders[$k]["host_order_num"] = $x["host_order_num"];
            $orders[$k]["customer_id"] = $x["customer_id"];
            $orders[$k]["name"] = $x["name"];
            $orders[$k]["ship_via"] = $x["ship_via"];
            $orders[$k]["zones"] = $x["zones"];
            $orders[$k]["num_lines"] = $x["num_lines"];
            $orders[$k]["enter_date"] = date("m/d/Y", strtotime($x["enter_date"]));
        }
    } // ord is array
    else { // ord is not array
        $o = $ord["order_num"];
        $k = 1;
        $oidx[$k] = $o;
        $orders[$k]["slot"] = $k;
        $orders[$k]["order_num"] = $ord["order_num"];
        $orders[$k]["host_order_num"] = $ord["host_order_num"];
        $orders[$k]["customer_id"] = $ord["customer_id"];
        $orders[$k]["name"] = $ord["name"];
        $orders[$k]["ship_via"] = $ord["ship_via"];
        $orders[$k]["zones"] = $ord["zones"];
        $orders[$k]["num_lines"] = $ord["num_lines"];
        $orders[$k]["enter_date"] = date("m/d/Y", strtotime($ord["enter_date"]));

    } // ord is not array
    $ret = array("orders" => $orders,
        "oidx" => $oidx,
        "o" => $o
    );
    return $ret;
} // end orderSlots

function lineSlots($items, $oidx)
{
    if (is_array($items) and count($items) > 0) { // add place holder to items
        foreach ($items as $key => $item) {
            $o = $item["ord_num"];
            if (isset($oidx[$o])) $items[$key]["slot"] = $oidx[$o];
            else $items[$key]["slot"] = 0;
        }
        return $items;
    } // add place holder to items

} // end lineSlots

function updToteLoc($toteId, $order_num, $myZones, $dropZone)
{
    global $RESTSRV;
    if (isset($toteId) and intval($toteId) > 0 and $dropZone <> "") {
//Need to loop thru all totes for the order
        $req = array("action" => "updToteLoc",
            "company" => 1,
            "order_num" => $order_num,
            "tote_id" => $toteId,
            "zone" => $myZones,
            "whseLoc" => $dropZone
        );
        $rc = restSrv($RESTSRV, $req);
    } // end update drop
    return $rc;
} // end updToteLoc

function relOrder($comp, $order_num, $hostOrder, $myZones, $dropZone)
{
    global $RESTSRV;
    global $W2ErpSRV;
    global $opt;
    // check if all line items are picked and all lines were in this zone
    // if yes, release comm to host system, otherwise, use packing to finalize
    // to release, move all the totes for this order to container,
    // or free the totes, then Ship with  WMS2ERP.php server

    $req = array("action" => "checkRel2Host",
        "company" => 1,
        "order_num" => $order_num,
        "zone" => $myZones,
        "whseLoc" => $dropZone
    );
    $ret = restSrv($RESTSRV, $req);
    $rdata = (json_decode($ret, true));

    $fp = fopen("/tmp/shipIt.log", "a");
    fwrite($fp, "Order: {$order_num} HostId: {$hostOrder} openLines: {$rdata["openLines"]} opt25: {$opt[25]}\n");
    fclose($fp);
    if (isset($rdata["openLines"]) and $rdata["openLines"] < 1 and $opt[25] > 0) { // all lines picked, ship it
        if ($rdata["openLines"] == 0) { // no lines left anywhere to pick
            $fp = fopen("/tmp/shipIt.log", "a");
            fwrite($fp, "Running shipIt, Order: {$order_num} HostId: {$hostOrder} \n");
            fclose($fp);
            $rdata = shipIt($comp, $hostOrder, $RESTSRV, $W2ErpSRV);
//echo "<pre>";
//print_r($rdata);
//exit;
            //$ret=restSrv($W2ErpSRV,$req);
            $rdata = (json_decode($ret, true));
        } // no lines left anywhere to pick
    } // end all lines picked, ship it
    return $rdata;
} // end relOrder
?>

