<?php

// inv1.php -- SCan Bin, then all the parts in it
// 12/02/22 dse initial
/*TODO
screen 1, ask;
	Scheduled counts
		Shows existing cycle/ phys counts, user then selects,
		scans a bin for starting point and system guides them thru
		for parts going forward from first bin. 

	New Count by Part
                User scans or enters part, system directs them to each
		bin containing that part

	New Count by Bin
		User scans bin, then all parts in that bin
 		Also used for initial inventory
		if primary bin is blank, assigns first bin instance of
		that part as primary
*/
session_start();

//echo "<pre> REQUEST=";
//print_r($_REQUEST);
//echo "</pre>";
//PHPINFO(INFO_VARIABLES);
if (isset($_REQUEST["B2"]) and $_REQUEST["B2"] == "cancel") {
} // end b2 is set

$dispBin = "";
$lastPart = "";
$lastScan = "";
$lastQty = "";
foreach (array_keys($_REQUEST) as $w) {
    $$w = $_REQUEST[$w];
}
//error_reporting(0);

if (get_cfg_var('wmsdir') !== false) $wmsDir = get_cfg_var('wmsdir');
else {
    echo "<h1>WMS System is not Configured on this System</h1>";
    exit;
}
$top = str_replace("/var/www", "", $wmsDir);

if (!isset($nh)) $nh = 0;
$thisprogram = basename($_SERVER["PHP_SELF"]);
require("{$wmsDir}/config.php");
require_once("{$wmsInclude}/chklogin.php");
require_once("{$wmsInclude}/cl_rf1.php");
require_once("{$wmsInclude}/cl_template.php");
require_once("{$wmsInclude}/cl_PARTS2.php");
require_once("{$wmsInclude}/db_main.php");
require_once("{$wmsInclude}/date_functions.php");
require_once("../include/restSrv.php");
require_once("pb_utils.php");
require_once("collapse.php");


$RESTSRV = "http://{$wmsIp}{$wmsServer}/RcptLine.php";
$PARTSRV = "http://{$wmsIp}{$wmsServer}/whse_srv.php";
$UPDSRV = "http://{$wmsIp}{$wmsServer}/PO_srv.php";
$comp = $wmsDefComp;
$db = new WMS_DB;

// Application Specific Variables -------------------------------------
$temPlate = "generic1";
$title = "Assign Bins";
if (isset($lastPart) and $lastPart <> "") $title = "Last Part # {$lastPart}";
$panelTitle = "Assign Bins";
//$SRVPHP="{$wmsServer}/COMPANY_srv.php";
//$DRPSRV="{$wmsServer}/dropdowns.php";
// end application specific variables ---------------------------------

if (!isset($func)) $func = "startInv";
if (!isset($msg)) $msg = "";
if ($func == "chooseType" and isset($R1)) {
    if ($R1 == "-3") $func = "scanBin";
    if ($R1 == "-2") $func = "scanPart";
    if ($R1 > 0) {
        $ctrlNum = $R1;
        $func = "scanBin";
    }
} // end choosetype

if (!isset($ctrlNum)) $ctrlNum = 0;
if (isset($R1)) {
    if ($R1 > 0) $ctrlNum = $R1; else $ctrlNum = 0;
} // end R1 is set

$qtyOverride = false;
if ($func == "enterQty" and isset($entQty)) {
    $partNumber = $lastPart;
    $qtyOverride = true;
    $func = "scanPart";
}

if ($ctrlNum < 1 and isset($R1)) {
    $ctrlNum = getCtrl($comp);
    $userId = $_SESSION["wms"]["UserID"];
    $t = -$R1;
    $rc = addBatch($db, $ctrlNum, $comp, $userId, 0, $t);
    unset($t);
//echo "ctrlNum={$ctrlNum}";
}


if ($func == "palletToMove" and $toteId == "" and $B1 == "submit") $func = "scanScreen";
if ($func == "movingPallet" and $newLoc == "" and $B1 == "submit") $func = "palletToMove";
if ($func == "whatToDo") {
    if (isset($R1)) { // user answered what to do
        if ($R1 == 3) $func = "movePallet";
        else if ($R1 == 2) $func = "directedPutaway";
        else $func = "askPart";
    } // user answered what to do
} // end func == whatToDo


//Record noUPC part found in this bin
// Future, add new screen to record Part number and qty of part with no UPC
if ($func == "scanPart" and isset($B4) and $B4 == "noUPC") {
    if (isset($theBin) and $theBin <> "") {
        $lastPart = "";
        $rc = recordError($db, $ctrlNum, 0, $theBin, $theBin, "noUPC", "", "NUPC", 0);
    } // end NF and valid bin
}

if ($func == "donePressed" and isset($B2) and $B2 == "cancel") {
    $toteId = "";
    $title = "Assign Bins";
    $func = "startInv";
} // end donePressed

if ($func == "donePressed" and isset($B2) and $B2 == "done") {
    require("{$wmsInclude}/backToMenu.php");
}
if ($func == "movePallet" and isset($toteId)) {
    $redirect = "palletmove.php?func=palletToMove&nh=0&toteId={$toteId}&from=putaway";
    $htm = <<<HTML
 <html>
 <head>
 <script>
window.location.href="{$redirect}";
 </script>
 </head>
 <body>
 </body>
 </html>

HTML;
    echo $htm;
    exit;
}
if (isset($theBin) and $func == "scanBin") {
    $result = procScan($theBin, $comp);
    $lastScan = $theBin;
//echo "<pre> from scanBin";
//print_r($result);
//echo "</pre>";

    if ($result["infoType"] == "NF") $msg = "Invalid Bin";
    if (isset($result["infoType"])) {
        if ($result["infoType"] == "B") { // its a bin
            $binInfo = $result;
            $func = "scanPart";
            $dispBin = $result["wb_location"];
            //$dispBin=$result["wb_zone"]
            //. "-" . sprintf("%02d",$result["wb_aisle"])
            //. "-" . sprintf("%02d",$result["wb_section"])
            //. "-" . $result["wb_level"];

            //if ($result["wb_subin"] > 0)
            //$dispBin . "-" .sprintf("%02d",$result["wb_subin"]);
        } // its a bin
    } // end infoType is set

} // end isset theBin

if (isset($partNumber) and $partNumber <> "") {
    $result = procScan($partNumber, $comp);
    $lastScan = $partNumber;
    if (isset($result["infoType"])) {
        if ($result["infoType"] == "B") { // its a bin
            $binInfo = $result;
            $func = "scanPart";
            // $dispBin=$result["wb_zone"]
            //. "-" . sprintf("%02d",$result["wb_aisle"])
            //. "-" . sprintf("%02d",$result["wb_section"])
            //. "-" . $result["wb_level"];

            //if ($result["wb_subin"] > 0)
            //$dispBin . "-" .sprintf("%02d",$result["wb_subin"]);
            $theBin = $result["wb_location"];
            $dispBin = $theBin;
        } // its a bin
        if ($result["infoType"] == "P") { // itsa part
            $func = "scanPart";
            $lastPart = $partNumber;
            $lastScan = $partNumber;
            $lastQty = 1;
            $tc = $result["Result"]["alt_type_code"];
            if ($tc < 0) $lastQty = -$tc;
            if (isset($entQty) and $entQty <> $lastQty) $lastQty = $entQty;
            if (isset($result["Result"]["shadow_number"])) $shadow = $result["Result"]["shadow_number"]; else $shadow = 0;
            if ($shadow > 0) { // record to store

                $tqavail = 0; // total qty avail
                $tqalloc = 0; // total qty alloc
                $bavail = 0; // bin avail
                $balloc = 0; // bin alloc
                if (isset($result["WhseQty"][$comp])) {
                    $tqavail = $result["WhseQty"][$comp]["qty_avail"];
                    $tqalloc = $result["WhseQty"][$comp]["qty_alloc"];
                }
                if (isset($result["whseLoc"][$comp]) and count($result["whseLoc"][$comp]) > 0) { // whseloc isset
                    foreach ($result["whseLoc"][$comp] as $wl) {
                        if ($wl["whs_location"] == $theBin) {
                            $bavail = $wl["whs_qty"];
                            $balloc = $wl["whs_alloc"];
                        }
                    } // end foreach whseloc
                } // whseloc isset
                $tmp = getLine($db, $ctrlNum, $shadow, $theBin);
                $btype = "O";
                if ($result["WhseQty"][$comp]["primary_bin"] == $theBin) $btype = "P";
                if ($result["WhseQty"][$comp]["primary_bin"] == "") $btype = "P";
                $rec = array(
                    "count_num" => $ctrlNum,
                    "count_line" => $tmp["count_line"],
                    "userId" => $_SESSION["wms"]["UserID"],
                    "whse_loc" => $theBin,
                    "bin_type" => $btype,
                    "shadow" => $shadow,
                    "qty" => $lastQty,
                    "uom" => $result["Part"]["unit_of_measure"],
                    "bin_avail" => $bavail,
                    "bin_alloc" => $balloc,
                    "qty_avail" => $tqavail,
                    "qty_alloc" => $tqalloc,
                    "line_status" => 0,
                    "exists" => $tmp["exists"]
                );
                /*
                count_num       int not null,
                count_line      int not null,
                userId      int not null default 0,
                whse_loc        varchar(18) not null default " ",
                bin_type        char(1) not null, -- Primary, Overstock, etc
                shadow  int not null,
                qty     int not null,
                uom char(3) not null,
                bin_avail       int not null,
                bin_alloc       int not null,
                qty_avail       int not null,
                qty_alloc       int not null,
                line_status     smallint not null
                */
                $rc = updInv($db, $rec);

            } // record to store
//echo "<pre> from partNumber not empty";
//print_r($result);
//print_r($rec);
//exit;
        } // if infotype = P
    } // end infoType is set

    if ($result["infoType"] == "NF") {
        $msg = "Invalid Part Number or UPC";
        if (isset($theBin) and $theBin <> "") {
            $lastPart = "";
            $lastScan = $partNumber;
            $rc = recordError($db, $ctrlNum, 2, $theBin, $theBin, $partNumber, "", "NOF", 0);
            if (strlen($partNumber) > 11) {

                echo "<pre>";
                echo "Part {$partNumber}\n";
                print_r($_REQUEST);
                echo "</pre>";
            } // end part is > 11
        } // end NF and valid Part
    } // end infotype = NF
} // end isset partNumber

if (isset($B3) and $B3 == "ViewQty") $func = "enterQty";

switch ($func) {
    case "startInv":
    {
        if (isset($msg)) $msg = "";
        if (isset($msgCancel)) $msg = $msgCancel;
        $color = "light-blue";
        if ($msg <> "") $color = "green";
        $mainSection = startScreen($msg, $color);
        break;
    } // end startInv
    case "scanBin":
    { // Display Scan Bin screen
        if (!isset($msg)) $msg = "";
        $color = "light-blue";
        if ($msg <> "") $color = "green";
        if ($msg <> "" and substr($msg, 0, 7) == "Invalid") $color = "red";
        if (isset($msgCancel)) $msg = $msgCancel;
        $mainSection = scanBin($msg, $color);
        break;
    } // End Display Scan screen

    case "scanPart":
    { // ask scan Part
        $color = "light-blue";
        if ($dispBin <> "") $color = "green";
        if ($msg <> "" and substr($msg, 0, 7) == "Invalid") $color = "red";
        // add invalid part color if invalid
        if ($msg == "" and $dispBin <> "") $msg = "Bin: {$dispBin}";
        if ($lastPart <> "") $msg .= " Last Part#: {$lastPart}";
        $mainSection = askPart($theBin, $color, $msg);
        break;
    } // ask scan Part

    case "askPart":
    case "chkPart":
    {
        if (isset($toteId) and $toteId <> "") { // tote not empty
            $w = getToteInfo($toteId);
            if (isset($w[1])) {
                if ($func == "chkPart") {
                    $ok = true;
                    $req = array("action" => "getPart",
                        "company" => $comp,
                        "partNumber" => $partNumber
                    );
                    $ret = restSrv($PARTSRV, $req);
                    $part = (json_decode($ret, true));
                    if (!isset($part["status"])
                        or (isset($part["status"]) and $part["status"] == -35)) { //part not found
                        $ok = false;
                        $msg = "Invalid Part!";
                        $mainSection = askPart($toteId, "red", $msg);
                        break;
                    } //part not found
                    if (isset($part["Result"])) { // part is good, see if it's in this tote
                        $req = array("action" => "getPartInTote",
                            "company" => $comp,
                            "tote_id" => $toteId,
                            "shadow" => $part["Result"]["shadow_number"]
                        );
                        $ret1 = restSrv($RESTSRV, $req);
                        $toteDtl = (json_decode($ret1, true));
                        if (isset($toteDtl["errCode"])) {
                            $ok = false;
                            $msg = $toteDtl["errText"];
                            $mainSection = askPart($toteId, "red", $msg);
                            break;
                        } // end errCode is set
                    } // part is good, see if it's in this tote
                    if (count($toteDtl) > 0 and $ok) { // good part and it is in this tote, send them to the bin
                        $req["action"] = "getPoForPart";
                        $ret1 = restSrv($RESTSRV, $req);
                        $poInfo = (json_decode($ret1, true));
                        $mainSection = sendToBin($toteDtl, $part, $poInfo, $color = "green", $msg = "");
                        //$mainSection.="\n" . collapseCss();
                        //$mainSection.="\n" . collapseJs();
//echo "<pre>$ret";
//print_r($toteDtl);
//print_r($poInfo);
//print_r($part);
//exit;
                    } // good part and it is in this tote, send them to the bin
                } // end chkPart
                else { // ask scan part
                    $mainSection = askPart($toteId);
                } // ask scan part
            } // w[1] is set
        } // tote not empty
        break;
    } // askPart

    case "enterQty":
    {
        //if ($lastPart <> "") $msg="Part#: {$lastPart}";
        $color = "blue";
        $mainSection = enterQty($lastPart, $color, $msg);
        break;
    } // end enterQty

    case "putBin":
    {
        // bin is entered, check if valid for this part
        // If so,  update the part
        $validbins = array();
        if (isset($primaryBin)) array_push($validbins, $primaryBin);
        if (isset($obins) and count($obins) > 0) {
            foreach ($obins as $key => $b) {
                array_push($validbins, $primaryBin);
            } // end foreach obins
        } // push the obins
        $ok = false;
        if (count($validbins)) { // validate the bins, else let it fall thru means there is no bins yet
            foreach ($validbins as $b) {
                if ($bin == $b) $ok = true;
            }
            if (!$ok) { // not a valid bin for this part, redirect them to a proper bin
                $msg = "Invalid Bin For this Part";
            } // not a valid bin for this part, redirect them to a proper bin
        } // validate the bins, else let it fall thru means there is no bins yet
        // validate bin entry
        $req = array("action" => "validateBin",
            "company" => $comp,
            "bin" => $bin
        );
//Should be PARTSRV, but the update fails
        $ret = restSrv($PARTSRV, $req);
        $w = (json_decode($ret, true));
        if (isset($w["numRows"]) and $w["numRows"] < 1) { // invalid bin
            //redisplay putbin screen
            $msg = "Invalid Bin 2";
            $ok = false;
        }  // invalid bin
        else { // check if primary bin is set or blank, if so, set it to this bin
            $setPrim = false;
            if (!isset($primaryBin) and trim($bin) <> "") $setPrim = true;
            if (isset($primaryBin) and trim($primaryBin) == "") $setPrim = true;
            if ($setPrim) {
                $primaryBin = $bin;
                $ok = true;
            }
        } // check if primary bin is set or blank, if so, set it to this bin
        if (isset($ok) and $ok) { // its ok, update it
            $userId = $_SESSION["wms"]["UserID"];
            $req = array("action" => "putAway",
                "company" => $comp,
                "userId" => $userId,
                "wms_po_num" => $wmspo,
                "host_po_num" => $hostpo,
                "batch" => $batch_num,
                "toteId" => $toteId,
                "shadow" => $shadow,
                "primaryBin" => $primaryBin,
                "qtyStockd" => $Qty,
                "BinTote" => $bin,
                "partUOM" => $partUOM,
                "pkgQty" => $pkgQty
            );
            $ret = restSrv($UPDSRV, $req);
            $y = (json_decode($ret, true));
            if ($y["status"] == 1) {
                if ($y["toteItems"] > 0) $mainSection = askPart($toteId);
                else {
                    $color = "blue";
                    $msg = "There are no more Items in Tote {$toteId}";
                    $mainSection = entOrderTote($msg, $color, true);
                }
            } // end status = 1
            else { // and error occured
                echo "<pre>Error Occurred";
                echo $ret;
                exit;
            } // and error occured
        } // its ok, update it
        else {
            if (isset($save_sendToBin)) {
                $data = json_decode(base64_decode($save_sendToBin), true);
                if ($msg <> "") $data["msg"] = $msg;
                else $data["msg"] = "Invalid Bin";
                $data["color"] = "w3-red";
                if (count($data["obins"]) > 0 and $data["obins"][0]["obin"] == "") unset($data["obins"]);
//echo "<pre>";
//print_r($data);
//echo "</pre>";

                $save_sendToBin = base64_encode(json_encode($data));
                $data["hiddens"] .= <<<HTML
  <input type="hidden" name="save_sendToBin" value="{$save_sendToBin}">

HTML;

                $mainSection = frmtScreen($data, $thisprogram, "putBin");
                break;
            } // end save_sendToBin
        }
        break;
    } // end putBin

    case "palletToMove":
    {
        if (isset($toteId) and $toteId <> "") { // a tote or pallet was scanned , diplay tote info
            $title = $panelTitle . $toteId;
            $w = getToteInfo($toteId);
//print_r($req);
            $ww = "";
            $notGoodTote = "";
            if (array_key_exists("tote_type", $w[1])) $ww = $w[1]["tote_type"];
            if ($ww <> "RCV" and $ww <> "PUT" and $ww <> "MOV") {
                $notGoodTote = $ww;
                unset($w);
                $w = array();
            }
            if (isset($w[1]) and isset($w[1]["totalQty"]) and intval($w[1]["totalQty"]) > 0) { // display Order and Tote Info
                $task = chkTask($toteId);
                if (count($task) > 0) {
                    //check if tote is moving by the same user
                    //echo "<pre> task=";
                    //print_r($task);
                    //echo "</pre>";
                } // end count task > 0

                $target_zone = "";
                $target_aisle = "";
                $req = array("action" => "getToteLoc",
                    "company" => $comp,
                    "tote_id" => $toteId
                );
                $ret = restSrv($RESTSRV, $req);
                $y = (json_decode($ret, true));
                if (isset($y[1])) foreach ($y as $idx => $target) {
                    $last_zone = "";
                    $last_loc = "";
                    if (isset($target["target_zone"])) $target_zone = att($target_zone, $target["target_zone"]);
                    if (isset($target["target_aisle"])) $target_aisle = att($target_aisle, $target["target_aisle"]);
                    if (isset($target["last_zone"])) $last_zone = att($last_zone, $target["last_zone"]);
                    if (isset($target["last_location"])) $last_loc = att($last_location, $target["last_location"]);
                } // end foreach y
                $msg = "";
                $mmsg = "";
                $templte = "palletMove";
                if (!isset($last_zone)) {
                    $templte = "generic2";
                    $last_zone =
                    $last_loc = "{$task[1]["tote_type"]} {$task[1]["tote_ref"]}";
                    $target_zone = "";
                    $target_aisle = "";
                    $mmsg = "<br>Warning, this is not a Receiving Tote";
                    // ***** Putaway Mode ***************************

// temp code to try putaway
                    /*
                        $color="blue";
                        $msg="Scan Part";
                        $vendor="";
                        $title="Putaway Tote " . $toteId;
                        $work=frmtPartScan($vendor,$msg,$color);
                        $buttons=setStdButtons("C",true);
                        $mainSection=str_replace("_BUTTONS_",$buttons,$work);
                        unset($work);

                        break;  // **** End Putaway Mode *******
                    */
// temp code to try putaway
                }
                // ask Method Ad-Hoc Mode or directed mode
                $mainSection = askWhatToDo($mmsg, $toteId, $color = "light-blue");

            } // display Order and Tote Info
            else { // tote not found
                $color = "red";
                $msg = "Pallet/Tote Not Found";
                if (isset($w[1])) {
                    if (intval($w[1]["totalQty"]) < 1) echo "\nit is less than 1\n";
                    if (array_key_exists("totalQty", $w[1]) and intval($w[1]["totalQty"]) < 1) {
                        $color = "yellow";
                        $msg = "Pallet/Tote {$toteId} does not have any Parts in it";
                    }
                } // w[1] is set
                if ($notGoodTote <> "") {
                    $msg = "Tote is not a Receiving Tote, Please choose another Tote";
                }

                $mainSection = entOrderTote($msg, $color);

            } // end tote not found
        } // end Display Tote
        break;
    } // end palletToMove

    case "movePallet":
    {
        $num_items = "";
        $totalQty = "";
        if (isset($w[1]["num_items"])) $num_items = $w[1]["num_items"];
        if (isset($w[1]["totalQty"])) $totalQty = $w[1]["totalQty"];
        $req = array("action" => "addTask",
            "company" => $comp,
            "tote_id" => $toteId,
            "operation" => "MOV"
        );

        $hiddens = <<<HTML
<form name="form1" action="palletmove.php" method="get">
  <input type="hidden" name="func" id="func" value="movingPallet">
  <input type="hidden" name="nh" value="0">
  <input type="hidden" name="toteId" value="{$toteId}">
  <input type="hidden" name="target_zone" value="{$target_zone}">
  <input type="hidden" name="target_aisle" value="{$target_aisle}">
  <input type="hidden" name="num_items" value="{$num_items}">
  <input type="hidden" name="totalQty" value="{$totalQty}">

HTML;
        $color = "green";
        $fieldPrompt = "Scan New Location";
        $fieldPlaceHolder = "Scan New Pallet/Tote Location";
        $fieldId = "new_Loc";
        $fieldTitle = " title=\"Scan New Pallet/Tote Location\"";
        if ($last_zone <> "") $msg = "Last Zone: {$last_zone}{$mmsg}";
        if ($last_loc <> "") $msg = "   Last Location: {$last_loc}{$mmsg}";
        $msg2 = "Move Pallet/Tote {$toteId} -";
        $msg2 .= " Items: {$num_items}, Units: {$totalQty}";

        $buttons = setStdButtons("C");
        $data = array("formName" => "form1",
            "formAction" => $thisprogram,
            "hiddens" => $hiddens,
            "color" => "w3-{$color}",
            "onChange" => "do_submit();",
            "fieldType" => "text",
            "fieldValue" => "",
            "fieldPrompt" => $fieldPrompt,
            "fieldPlaceHolder" => $fieldPlaceHolder,
            "fieldName" => "newLoc",
            "fieldId" => $fieldId,
            "fieldTitle" => $fieldTitle,
            "msg" => $msg,
            "msg2" => $msg2,
            "buttons" => $buttons,
            "target_zone" => $target_zone,
            "target_aisle" => $target_aisle,
            "function" => ""
        );
        $mainSection = frmtScreen($data, $thisprogram, $templte);
        break;

    } // end movePallet

    case "movingPallet":
    {
        $title = $panelTitle . $toteId;
        $req = array("action" => "getNewLoc",
            "company" => $comp,
            "tote_id" => $toteId,
            "newLoc" => $newLoc
        );
        $ret = restSrv($RESTSRV, $req);
        $w = (json_decode($ret, true));
        if (count($w)) {
            $j = $w[1];
            $req = array(
                "action" => "updToteLoc",
                "company" => $comp,
                "tote_id" => $toteId,
                "operation" => $j["zone_type"],
                "zone" => $j["zone_type"],
                "newBin" => $j["zone"]
            );
            $ret = restSrv($RESTSRV, $req);
            $w1 = (json_decode($ret, true));
//  echo "<pre>getNewLoc Results=";
//print_r($w);
//print_r($w1);
        } // end count w
        break;
    } // end moveingPallet

} // end switch func

$pg = new displayRF;
$pg->viewport = "1.0";
$pg->dispLogo = false;
$pg->Bootstrap = true;
if (isset($title)) $pg->title = $title;
if (isset($color)) $pg->color = $color; else $color = "light-blue";
$ejs = "";
if (isset($nh) and $nh > 0) {
    $pg->noHeader = true;
}

if (!isset($otherScripts)) $otherScripts = "";
$pg->jsh = <<<HTML
<script>
function openalt(url,nlns) {
        hgt=210 + (nlns * 25);
        var popup=window.open(url,"popup", "toolbar=no,left=0,top=125,status=yes,resizable=yes,scrollbars=yes,width=600,height=" + hgt );
 return(false);
     }
function doView(tote)
{
 var url="tcont.php?toteId=" + tote;
 openalt(url,10);
 return false;
}
</script>

HTML;
if (isset($js)) $pg->jsh .= $js;
$pg->Display();
//Rest of page
$htm = <<<HTML
  {$mainSection}
  {$otherScripts}
 </body>
</html>

HTML;
echo $htm;
echo "<pre>";
//print_r($w);


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

function entOrderTote($msg, $color = "blue", $override = false)
{
    global $thisprogram;
    global $nh;
    global $ctrlNum;
    if ($msg <> "" and !$override) $color = "red";

    $hiddens = <<<HTML
  <input type="hidden" name="func" id="func" value="palletToMove">
  <input type="hidden" name="nh" value="{$nh}">
  <input type="hidden" name="scanTote" value="">
  <input type="hidden" name="ctrlNum" value="{$ctrlNum}">
HTML;
    $fieldPrompt = "Tote or Pallet";
    $fieldPlaceHolder = "Scan Tote/Pallet Id to Move";
    $fieldId = " id=\"toteid\"";
    $msg2 = "Scan Tote/Pallet (Tote, Pallet, Cart, etc) to Move";
    $fieldTitle = " title=\"{$msg2}\"";
    $extra_js = "";
    $buttons = setStdButtons("D");

    $data = array("formName" => "form1",
        "formAction" => $thisprogram,
        "hiddens" => $hiddens,
        "color" => "w3-{$color}",
        "onChange" => "do_submit();",
        "fieldType" => "text",
        "fieldValue" => "",
        "fieldPrompt" => $fieldPrompt,
        "fieldPlaceHolder" => $fieldPlaceHolder,
        "fieldName" => "toteId",
        "fieldId" => $fieldId,
        "fieldTitle" => $fieldTitle,
        "msg" => $msg,
        "msg2" => $msg2,
        "buttons" => $buttons,
        "function" => ""
    );
    $ret = frmtScreen($data, $thisprogram, "generic2");
    return $ret;
} // end entOrderTote

function att($in, $add)
{ // att - add to target
    $comma = "";
    if (strlen($in) > 0) {
        $comma = ",";
        if (trim($in) == trim($add)) return $in;
        if (strpos($in, "{$add}{$comma}") !== false) return $in;
    }
    return "{$in}{$comma}{$add}";
} // end att

function setCustomButtons($flag = "D")
{
    global $lastPart;
    // args D=Done, C=Cancel
    $w = "done";
    $w1 = "Done";
    if ($flag == "C") {
        $w = "cancel";
        $w1 = "Cancel";
    }

    $buttons = array(
        0 => array(
            "btn_id" => "b1",
            "btn_name" => "B1",
            "btn_value" => "submit",
            "btn_onclick" => "document.form1.submit();",
            "btn_prompt" => "Submit"
        ),
        1 => array(
            "btn_id" => "b3",
            "btn_name" => "B3",
            "btn_value" => "ViewQty",
            "btn_onclick" => "doQty({$lastPart}); return false;",
            "btn_prompt" => "Chg Qty"
        ),
        2 => array(
            "btn_id" => "b2",
            "btn_name" => "B2",
            "btn_value" => $w,
            "btn_onclick" => "do_done();",
            "btn_prompt" => $w1
        )
    );
    if ($lastPart == "") unset($buttons[1]);
    return $buttons;

} // end setCustomButtons
function setStdButtons($DorC = "D", $tc = false)
{
    // args D=Done, C=Cancel
    $w = "done";
    $w1 = "Done";
    if ($DorC == "C") {
        $w = "cancel";
        $w1 = "Cancel";
    }
    $buttons = array(
        0 => array(
            "btn_id" => "b1",
            "btn_name" => "B1",
            "btn_value" => "submit",
            "btn_onclick" => "do_submit();",
            "btn_prompt" => "Submit"
        ),
        1 => array(
            "btn_id" => "b2",
            "btn_name" => "B2",
            "btn_value" => $w,
            "btn_onclick" => "do_done();",
            "btn_prompt" => $w1
        )
    );
    if ($tc) {
        global $toteId;
        $b = array(
            0 => $buttons[0],
            1 => array(
                "btn_id" => "b1",
                "btn_name" => "B1",
                "btn_value" => "View",
                "btn_onclick" => "doView({$toteId}); return false;",
                "btn_prompt" => "View Contents"
            ),
            2 => $buttons[1]
        );
        unset($buttons);
        $buttons = "";
        foreach ($b as $b1) {
            $buttons .= <<<HTML
<button class="binbutton-small" id="{b1["btn_id"]}" name="{$b1["btn_name"]}" value="{$b1["btn_value"]}" onclick="{$b1["btn_onclick"]}">{$b1["btn_prompt"]}</button>

HTML;

        } // end foreach b
    } // end tc is true
    return $buttons;
} // end setStdButtons

function askWhatToDo($msg, $toteId, $color = "light-blue")
{
    global $thisprogram;
    global $nh;
    //if ($msg <> "") $color="red";

    $hiddens = <<<HTML
  <input type="hidden" name="func" id="func" value="whatToDo">
  <input type="hidden" name="nh" value="{$nh}">
  <input type="hidden" name="toteId" value="{$toteId}">
HTML;
    $fieldPrompt = "Choose Action";
    $fieldName = "R1";
    $msg2 = "";
    $fieldTitle = " title=\"{$msg2}\"";
    $extra_js = "";
    $buttons = setStdButtons("C");

    $data = array("formName" => "form1",
        "heading" => "Putaway/Move Tote # {$toteId}",
        "hiddens" => $hiddens,
        "fieldPrompt" => "Choose Action",
        "fieldName" => "R1",
        "msg" => $msg,
        "cols" => 4,
        "color" => "w3-{$color}",
        "buttons" => $buttons
    );

    $ret = frmtScreen($data, $thisprogram, "radio1");
    return $ret;
} // end askWhatToDo

function getToteInfo($toteId)
{
    global $comp;
    global $RESTSRV;
    $w = array();
    if (isset($toteId) and $toteId <> "") { // a tote or pallet was scanned , diplay tote info
        $req = array("action" => "getTote",
            "company" => $comp,
            "tote_id" => $toteId
        );
        $ret = restSrv($RESTSRV, $req);
        $w = (json_decode($ret, true));
    }
    return $w;
} // end getToteInfo

function chkTask($toteId)
{
    global $comp;
    global $RESTSRV;
    if (isset($toteId) and $toteId <> "") { // a tote or pallet was scanned , diplay tote info
        $req = array("action" => "chkTask",
            "company" => $comp,
            "tote_id" => $toteId
        );
        $ret1 = restSrv($RESTSRV, $req);
        $task = (json_decode($ret1, true));
    }
    return $task;
} // end chkTask

function askPart($theBin, $color = "light-blue", $msg = "")
{
    global $thisprogram;
    global $lastPart;
    global $lastScan;
    global $lastQty;
    global $savedQty;
    global $shadow;
    global $dispBin;
    global $comp;
    global $ctrlNum;
    global $nh;

    //if ($msg <> "") $color="red";

    $hiddens = <<<HTML
  <input type="hidden" name="func" id="func" value="scanPart">
  <input type="hidden" name="nh" value="{$nh}">
  <input type="hidden" name="theBin" value="{$theBin}">
  <input type="hidden" name="lastPart" value="{$lastPart}">
  <input type="hidden" name="lastScan" value="{$lastScan}">
  <input type="hidden" name="lastQty" value="{$lastQty}">
  <input type="hidden" name="dispBin" value="{$dispBin}">
  <input type="hidden" name="ctrlNum" value="{$ctrlNum}">
  <input type="hidden" name="comp" value="{$comp}">
HTML;
    $fieldPrompt = "Scan Part";
    if (trim($theBin) <> "") $fieldPrompt = "Scan Part or New Bin";
    $fieldPlaceHolder = "Scan";
    $fieldId = "";
    $msg2 = "Scan a Part from Bin {$theBin}";
    $fieldTitle = " title=\"{$msg2}\"";
    $extra_js = "";
    $buttons = setCustomButtons("C");
    if ($lastPart <> "") $buttons = setCustomButtons("Q");
    $buttons[3] = $buttons[2];
    $buttons[2] = array(
        "btn_id" => "b4",
        "btn_name" => "B4",
        "btn_value" => "noUPC",
        "btn_onclick" => "document.form1.submit();",
        "btn_prompt" => "No UPC"
    );
    $data = array("formName" => "form1",
        "formAction" => $thisprogram,
        "hiddens" => $hiddens,
        "color" => "w3-{$color}",
        "onChange" => "do_submit();",
        "fieldType" => "text",
        "fieldValue" => "",
        "fieldPrompt" => $fieldPrompt,
        "fieldPlaceHolder" => $fieldPlaceHolder,
        "fieldName" => "partNumber",
        "fieldId" => $fieldId,
        "fieldTitle" => $fieldTitle,
        "lastScan" => "Last Scan: {$lastScan}",
        "msg" => $msg,
        "msg2" => $msg2,
        "buttons" => $buttons,
        "function" => ""
    );
    $ret = frmtScreen($data, $thisprogram, "generic3");
    return $ret;


} // end askPart
function sendToBin($tote, $part, $po, $color = "light-blue", $msg = "")
{
    global $thisprogram;
    global $nh;
    global $comp;
    global $toteId;
    global $ctrlNum;
    $pkgQty = 1;
    if ($part["Result"]["alt_type_code"] < 0) $pkgQty = intval(-$part["Result"]["alt_type_code"]);
    if ($msg <> "") $color = "red";

    $hiddens = <<<HTML
  <input type="hidden" name="func" id="func" value="putBin">
  <input type="hidden" name="nh" value="{$nh}">
  <input type="hidden" name="toteId" value="{$tote[1]["tote_id"]}">
  <input type="hidden" name="wmspo" value="{$po["po_number"]}">
  <input type="hidden" name="ctrlNum" value="{$ctrlNum}">
  <input type="hidden" name="hostpo" value="{$po["host_po_num"]}">
  <input type="hidden" name="batch_num" value="{$po["batch_num"]}">
  <input type="hidden" name="comp" value="{$comp}">
  <input type="hidden" name="shadow" value="{$part["Part"]["shadow_number"]}">
  <input type="hidden" name="partUOM" value="{$part["Part"]["unit_of_measure"]}">
  <input type="hidden" name="pkgQty" value="{$pkgQty}">

HTML;
    $bin = "";
    $bin2 = "";
    $obins = array();
    $binPrompt = "Primary Bin";
    $binPrompt2 = "Other Bins";
    $msg2 = "Scan the Bin to put this item into";

    $obins = array();
    if (count($part["WhseLoc"]) > 0) { // fill in primary bin and other bins array
        foreach ($part["WhseLoc"] as $key => $w) {
            if ($w["whs_code"] == "P") {
                $bin = $w["whs_location"];
                $hiddens .= <<<HTML
  <input type="hidden" name="primaryBin" value="{$bin}">

HTML;
            } else array_push($obins, array("obin" => $w["whs_location"]));
        } // end for each whseloc
    } // fill in primary bin and other bins array

    if (trim($part["WhseQty"][$comp]["primary_bin"]) == "") { // set prefered zone and aisle because primary is not set
        $color = "yellow";
        $msg = "No Primary Bin is Set";
        $binPrompt = "Pref Zone/Aisle";
        $binPrompt2 = "Zone: {$part["ProdLine"]["pl_perfered_zone"]} Aisle: {$part["ProdLine"]["pl_perfered_aisle"]}";
        $msg2 = "Preferred {$binPrompt2}";
        $bin = "";
        $bin2 = "";
        array_push($obins, array("obin" => $binPrompt2));
    } // set prefered zone and aisle because primary is not set

    $binPrompt2 = "otherBins";
    if (count($obins) > 0) {
        foreach ($obins as $key => $b) {
            $hiddens .= <<<HTML
  <input type="hidden" name="obin[{$key}]" value="{$b["obin"]}">

HTML;

        } // end foreach obins
    } // end obins count > 0
    $fieldPrompt = "Scan Bin {$bin}";
    $fieldPlaceHolder = "Scan Bin";
    $fieldId = "";
    $fieldTitle = " title=\"{$msg2}\"";
    $extra_js = "";
    $buttons = setCustomButtons("C");
    $Qty = 1;
    if ($part["Result"]["alt_type_code"] < 0) $Qty = -$part["Result"]["alt_type_code"];
    $tQty = 1;
    if ($tote[1]["tote_qty"] <> 1) $tQty = $tote[1]["tote_qty"];
    $tqClass = "";
    if ($tQty > $Qty) {
        $tqClass = "class=\"Alt7DataTD\"";
        $msg2 = "<span class=\"Alt7DataTD\" style=\"word-wrap: normal;font-weight: bold; font-size: large; margin-left: 0px; text-align: cput;\">Total {$tQty} of this Part are in this Tote</span><br>Scan the Bin to put this item into";
    }
    $data = array("formName" => "form1",
        "formAction" => $thisprogram,
        "hiddens" => $hiddens,
        "color" => "w3-{$color}",
        "onChange" => "document.form1.submit();",
        "fieldType" => "text",
        "fieldValue" => "",
        "fieldPrompt" => $fieldPrompt,
        "fieldPlaceHolder" => $fieldPlaceHolder,
        "fieldName" => "bin",
        "fieldId" => $fieldId,
        "fieldTitle" => $fieldTitle,
        "pl" => $part["Part"]["p_l"],
        "partNumber" => $part["Part"]["part_number"],
        "pdesc" => $part["Part"]["part_desc"],
        "Qty" => $Qty,
        "toteQty" => $tQty,
        "tqClass" => $tqClass,
        "binPrompt" => $binPrompt,
        "binPrompt2" => $binPrompt2,
        "bin" => $bin,
        "bin2" => $bin2,
        "obins" => $obins,
        "msg" => $msg,
        "msg2" => $msg2,
        "buttons" => $buttons,
        "function" => ""
    );
//echo "<pre>Here";
//print_r($data);
//echo "</pre>";
    $save_sendToBin = base64_encode(json_encode($data));
    $data["hiddens"] .= <<<HTML
  <input type="hidden" name="save_sendToBin" value="{$save_sendToBin}">

HTML;
//echo "<pre>";
//print_r($data);
//echo "</pre>";
    $ret = frmtScreen($data, $thisprogram, "putBin");
    return $ret;
} // end sendToBin

function startScreen($msg, $color = "blue", $override = false)
{
    global $thisprogram;
    global $nh;
    global $db;
    global $ctrlNum;
    global $PARTSRV;
    global $comp;

    $req = array("action" => "getOpenInvBatches",
        "company" => $comp
    );
    $ret = restSrv($PARTSRV, $req);
    $invBatches = (json_decode($ret, true));

    if ($msg <> "" and !$override) $color = "red";

    $hiddens = <<<HTML
  <input type="hidden" name="func" id="func" value="chooseType">
  <input type="hidden" name="nh" value="{$nh}">
  <input type="hidden" name="ctrlNum" value="{$ctrlNum}">
  <input type="hidden" name="scanBin" value="">
HTML;
    $fieldPrompt = "Please Choose";
    $fieldPlaceHolder = "";
    $fieldId = "";
    $msg2 = "";
    $fieldTitle = "";
    $extra_js = "";
    $buttons = setStdButtons("D");
    $data = array("formName" => "form1",
        "formAction" => $thisprogram,
        "hiddens" => $hiddens,
        "color" => "w3-{$color}",
        "onChange" => "do_submit();",
        "fieldType" => "text",
        "fieldValue" => "",
        "fieldPrompt" => $fieldPrompt,
        "fieldPlaceHolder" => $fieldPlaceHolder,
        "fieldName" => "invType",
        "fieldId" => $fieldId,
        "fieldTitle" => $fieldTitle,
        "curcounts" => $invBatches,
        "msg" => $msg,
        "msg2" => $msg2,
        "buttons" => $buttons,
        "function" => ""
    );
    $ret = frmtScreen($data, $thisprogram, "invStart");
    return $ret;


} // end startScreen

function scanBin($msg, $color = "blue", $override = false)
{
    global $thisprogram;
    global $nh;
    global $ctrlNum;
    if ($msg <> "" and !$override) $color = "red";

    $hiddens = <<<HTML
  <input type="hidden" name="func" id="func" value="scanBin">
  <input type="hidden" name="nh" value="{$nh}">
  <input type="hidden" name="ctrlNum" value="{$ctrlNum}">
  <input type="hidden" name="scanBin" value="">
HTML;
    $fieldPrompt = "Scan Bin";
    $fieldPlaceHolder = "Scan Bin to Assign";
    $fieldId = " id=\"bin\"";
    $msg2 = "Scan Bin to Assign";
    $fieldTitle = " title=\"{$msg2}\"";
    $extra_js = "";
    $buttons = setStdButtons("D");

    $data = array("formName" => "form1",
        "formAction" => $thisprogram,
        "hiddens" => $hiddens,
        "color" => "w3-{$color}",
        "onChange" => "do_submit();",
        "fieldType" => "text",
        "fieldValue" => "",
        "fieldPrompt" => $fieldPrompt,
        "fieldPlaceHolder" => $fieldPlaceHolder,
        "fieldName" => "theBin",
        "fieldId" => $fieldId,
        "fieldTitle" => $fieldTitle,
        "msg" => $msg,
        "msg2" => $msg2,
        "buttons" => $buttons,
        "function" => ""
    );
    $ret = frmtScreen($data, $thisprogram, "generic2");
    return $ret;
} // end scanBin

function procScan($searchStr, $comp)
{
    global $PARTSRV;

    $req = array("action" => "findIt",
        "comp" => $comp,
        "Search" => $searchStr
    );
    $ret = restSrv($PARTSRV, $req);
    $result = (json_decode($ret, true));
    return $result;
} // end procScan

function enterQty($lastPart, $msg, $color = "blue", $override = false)
{
    global $thisprogram;
    global $lastPart;
    global $lastQty;
    global $theBin;
    global $dispBin;
    global $ctrlNum;
    global $comp;
    global $nh;

    //if ($msg <> "") $color="red";

    //if ($msg <> "" and !$override) $color="red";
    $color = "yellow";
    $hiddens = <<<HTML
  <input type="hidden" name="func" id="func" value="enterQty">
  <input type="hidden" name="nh" value="{$nh}">
  <input type="hidden" name="theBin" value="{$theBin}">
  <input type="hidden" name="ctrlNum" value="{$ctrlNum}">
  <input type="hidden" name="lastPart" value="{$lastPart}">
  <input type="hidden" name="lastQty" value="{$lastQty}">
  <input type="hidden" name="dispBin" value="{$dispBin}">
  <input type="hidden" name="comp" value="{$comp}">
HTML;


    $msg = "Part # {$lastPart} ___ Bin: {$theBin}";
    $fieldPrompt = "Enter Total Qty in this Bin";
    $fieldPlaceHolder = "Enter Inventory Qty";
    $fieldId = " id=\"entQty\"";
    $msg2 = "Enter Inventory Qty";
    $fieldTitle = " title=\"{$msg2}\"";
    $extra_js = "";
    $buttons = setStdButtons("C");

    $data = array("formName" => "form1",
        "formAction" => $thisprogram,
        "hiddens" => $hiddens,
        "color" => "w3-{$color}",
        "onChange" => "",
        "fieldType" => "number",
        "fieldValue" => "{$lastQty}",
        "fieldPrompt" => $fieldPrompt,
        "fieldPlaceHolder" => $fieldPlaceHolder,
        "fieldName" => "entQty",
        "fieldId" => $fieldId,
        "fieldTitle" => $fieldTitle,
        "msg" => $msg,
        "msg2" => $msg2,
        "buttons" => $buttons,
        "function" => ""
    );
    $ret = frmtScreen($data, $thisprogram, "generic2");
    return $ret;
} // end enterQty

function getCtrl($comp)
{
    global $PARTSRV;
    $req = array("action" => "getControlNum",
        "ctrlComp" => $comp,
        "ctrlKey" => "PHYSINV"
    );
    $ret = restSrv($PARTSRV, $req);
    $ctrl = (json_decode($ret, true));
    unset($ret);
    $ret = 0;
    if (isset($ctrl["controlNum"])) $ret = $ctrl["controlNum"];

    return $ret;
} // end getCtrl

function getLine($db, $ctrlNum, $shadow, $bin)
{
    $ret = array("count_line" => 0, "exists" => 0);

    $SQL = <<<SQL
select count_line from INV_SCAN
where count_num = {$ctrlNum}
and shadow = {$shadow}
and whse_loc = "{$bin}"

SQL;
    $rc = getOneField($db, $SQL, "count_line");
    if ($rc == "") { // shadow not found
        $SQL = <<<SQL
  select (IFNULL(max(count_line),0) + 1) as count_line
  from INV_SCAN
  where count_num = {$ctrlNum}

SQL;
        $rc = getOneField($db, $SQL, "count_line");
    } // shadow not found
    else $ret["exists"] = 1;
    if ($rc <> "") $ret["count_line"] = $rc;
    return $ret;
} // end getLine

function getOneField($db, $SQL, $fname)
{
    $ret = "";
    $rc = $db->query($SQL);
    $numrows = $db->num_rows();
    $i = 1;
    while ($i <= $numrows) {
        $db->next_record();
        if ($numrows) {
            $ret = $db->f($fname);
        }
        $i++;
    } // while i < numrows
    return $ret;

} // end getOneField
function updInv($db, $rec)
{
    global $qtyOverride;
    /*
   count_num       int not null,
   count_line      int not null,
   userId      int not null default 0,
   whse_loc        varchar(18) not null default " ",
   bin_type        char(1) not null, -- Primary, Overstock, etc
   shadow  int not null,
   qty     int not null,
   uom char(3) not null,
   bin_avail       int not null,
   bin_alloc       int not null,
   qty_avail       int not null,
   qty_alloc       int not null,
   line_status     smallint not null
   */
    $setQty = "set qty = qty + {$rec["qty"]}";
    if ($qtyOverride) $setQty = "set qty = {$rec["qty"]}";
    if ($rec["exists"] > 0) { //update
        $SQL = <<<SQL
update INV_SCAN
{$setQty}
where count_num = {$rec["count_num"]}
and count_line = {$rec["count_line"]}
SQL;
    } //update
    else { // add
        $SQL = <<<SQL
insert into INV_SCAN
(count_num,
count_line,
userId,
whse_loc,
bin_type,
shadow,
qty,
uom,
bin_avail,
bin_alloc,
qty_avail,
qty_alloc,
line_status)
values (
{$rec["count_num"]},
{$rec["count_line"]},
{$rec["userId"]},
"{$rec["whse_loc"]}",
"{$rec["bin_type"]}",
{$rec["shadow"]},
{$rec["qty"]},
"{$rec["uom"]}",
{$rec["bin_avail"]},
{$rec["bin_alloc"]},
{$rec["qty_avail"]},
{$rec["qty_alloc"]},
{$rec["line_status"]}
)

SQL;
    } // add
//echo "<pre>{$SQL}</pre>";
    $ret = $db->Update($SQL);
    return $ret;
} // end updInv

function addBatch($db, $countNum, $comp, $user, $stat, $type)
{
    $SQL = <<<SQL
 insert into INV_BATCH
 ( count_num, company, create_by, create_date, due_date, count_status, count_type)
 values (
{$countNum},
{$comp},
{$user},
NOW(),
NOW() + INTERVAL 1 DAY,
{$stat},
{$type}
)

SQL;

    $ret = $db->Update($SQL);
    return $ret;

} // end addBatch
function recordError($db, $ctrlNum, $typ, $theBin, $partNumber, $pl, $pn, $qty)
{
    $SQL = <<<SQL
insert into INV_ERROR
(count_num, ex_type, last_bin, this_bin, upc, p_l, part_number, qty)
values ( {$ctrlNum},{$typ},"{$theBin}","{$theBin}","{$partNumber}","{$pl}","{$pn}",{$qty})

SQL;
    $ret = $db->Update($SQL);
    return $ret;
} // end recordError
?>
