<?php
//in.php -- read erp2wms datafiles, add/upd/delete records from it.
//12/14/21 dse initial
//02/17/22 dse add allow_inplace to VENDORS
//02/24/22 dse Add Customers in CST
//12/16/22 dse sort the files by oldest date/time first

/*TODO
Add wr_log and Debug logging
figure out what to do when a part is missing
figure out what to do when a important validation record is missing


*/
define('LOGFILE', "/tmp/in.log");
//define('DEBUG',true);

$setConfig = true;
require("config.php");

require_once("{$wmsInclude}/db_main.php");
require_once("{$wmsInclude}/quoteit.php");
require_once("{$wmsInclude}/escQuotes.php");
require_once("{$wmsInclude}/wr_log.php");
require_once("{$wmsInclude}/cl_addupdel.php");
require_once("{$wmsInclude}/get_contrl.php");
require_once("{$wmsInclude}/date_functions.php");
require_once("{$wmsInclude}/getPart.php");
require_once("{$wmsInclude}/restSrv.php");
require_once("{$wmsInclude}/sort_files.php");
require_once("{$wmsInclude}/cl_PARTS2.php");
require_once("{$wmsInclude}/procError.php");
require_once("{$wmsInclude}/importMap.php");

$db = new WMS_DB;
$upd = new AddUpdDEL;
set_time_limit(0);
//echo '<pre>' . print_r(get_defined_vars(), true) . '</pre>';
$RESTSRV = "http://{$wmsIp}{$wmsServer}/COMPANY_srv.php";

echo "<pre>";

//operation field is always 0=Add, 1=Delete, 2=If Exists Update, Else Add

$partValFields = array(
    "unit_of_measure" => "UOMCODES|uom_code",
    "part_category" => "CATEGORYS|cat_id",
    "part_group" => "PARTGROUPS|pgroup_id",
    "part_class" => "PARTCLASS|class_id",
    "hazard_id" => "HAZARD_CODES|haz_code"
);

$Vendors = chk_vendor($db);
$rowcnt = 0;
$VENS = 0;
$CSTS = 0;
$PLMS = 0;
$UMCS = 0;
$UOMS = 0;
$SUBS = 0;
$POHS = 0;
$PODS = 0;
$CATS = 0;
$PGRS = 0;
$PCLS = 0;
$VIAS = 0;
$ZONS = 0;
$PRTS = 0;
$ORDS = 0;
$ITMS = 0;
$DRPS = 0;
$ORLS = 0;
$BTPS = 0;

$inValidVend = 0;
$PLM = array();
$vUpdFlds = array();
//$data=file("{$inDir}/pl.dat");
//$data=file("{$inDir}/others.dat");
//$data=file("{$inDir}/WIX.dat");
//$data=file("/usr1/schema/WMS/data/ALT/UPC.dat");
//$data=file("/usr1/schema/WMS/data/PO.dat");
//$data=file("{$inDir}/WIX.dat");
//$data=file("{$inDir}/WHD.dat");
//$data=file("/usr1/schema/WMS/data/POD.dat");
//$data=file("{$inDir}/VENDORS.dat");
//$data=file("/usr1/schema/WMS/data/CUSTOMERS.dat");
//$data=file("/usr1/schema/WMS/data/Orders/Order.dat");

//temp
$aa = array();
$itms = 0;
echo "<pre>";
// end temp

$files = find_all_files($inDir, "lck", false);
if (count($files)) {
    foreach ($files as $f) {
        $baseFile = basename($f);
        $data = file($f);
        if (count($data))
            foreach ($data as $d) {
                $d = str_replace("\n", "", $d);
                $rowcnt++;
                if ($d <> "") {
                    $w = explode("|", $d);

//temp
                    $x = loadFields($fields[$w[0]], $w, true);
                    if ($w[0] <> "ITM") $aa[$w[0]] = $x;
                    else {
                        if (!isset($aa["ITM"])) $aa["ITM"] = array();
                        $aa["ITM"][$itms] = $x;
                        $itms++;
                    }
//array_push($aa, array("ITM"=>(array($w[2]=>$x))));
//print_r($aa);
                    //if (count($w))
//temp
                    if (count($w) and 1 == 2) {
                        switch ($w[0]) {
                            case "VEN":
                                if (isset($rowData)) unset($rowData);
                                $rowData = array();
                                $maxFlds = count($fields["VEN"]) - 1;
                                if (trim($w[1]) <> "") { // vendor code is not empty
                                    $rowData = loadFields($fields["VEN"], $w);
                                    $rowData["entity_type"] = "V";
                                    $vendor = $rowData["vendor"];
                                    $rowData["host_id"] = $vendor;
                                    unset($rowData["vendor"]);
                                    //Update, Insert or Delete the record
                                    $where = <<<SQL
where host_id = "{$vendor}"

SQL;

                                    $rc = $upd->updRecord($rowData, "ENTITY", $where);
                                    echo "{$w[1]} {$rc}\n";
                                    $xx = $w[0] . "S";
                                    $$xx++;
                                } // vendor code is not empty
                                break;

                            case "CST":
                                if (isset($rowData)) unset($rowData);
                                $rowData = array();
                                $maxFlds = count($fields["CST"]) - 1;
                                if (trim($w[1]) <> "") { // customer is not empty
                                    $rowData = loadFields($fields["CST"], $w);
                                    $rowData["entity_type"] = "C";
                                    $customer = $rowData["customer"];
                                    $rowData["host_id"] = $customer;
                                    if (trim($rowData["allow_bo"]) == "1") $rowData["allow_bo"] = "Y";
                                    if (trim($rowData["allow_bo"]) == "0") $rowData["allow_bo"] = "N";
                                    unset($rowData["customer"]);
                                    //Update, Insert or Delete the record
                                    $where = <<<SQL
where host_id = "{$customer}"

SQL;

                                    $rc = $upd->updRecord($rowData, "ENTITY", $where);
                                    echo "{$w[1]} {$rc}\n";
                                    $xx = $w[0] . "S";
                                    $$xx++;
                                } // customer is not empty
                                break;
                            case "PLM":
                                if (isset($rowData)) unset($rowData);
                                if (trim($w[1]) <> "") { // PL code is not empty
                                    $rowData = loadFields($fields["PLM"], $w);
                                    $pl = $rowData["pl_code"];
                                    $hostcomp = $rowData["pl_company"];
                                    $comp = convert_comp($hostcomp);
                                    if ($comp < 1) {
                                        $msg = array();
                                        array_push($msg, "01|Invalid Company: {$comp}");
                                        $rc = logError($w[0], $baseFile, $rowcnt, "", $msg, $d, 1);
                                        exit;

                                    } // comp < 1
                                    $vendor = trim($rowData["pl_vend_code"]);
                                    if (!isset($Vendors[$vendor]) and $vendor <> "") { // vendor not found
                                        echo "Row {$rowcnt} Invalid Vendor {$vendor}\n";
                                        $inValidVend++;
                                    } // vendor not found
                                    if (isset($Vendors[$vendor]) or $vendor == "") { // vendor found
                                        //Update, Insert or Delete the record
                                        $where = <<<SQL
where pl_code = "{$pl}"
  and pl_company = "{$comp}"

SQL;
                                        $rc = $upd->updRecord($rowData, "PRODLINE", $where);
                                        echo "{$w[1]} {$rc}\n";
                                        $xx = $w[0] . "S";
                                        $$xx++;
                                    } // vendor found
                                } // PL code is not empty
                                break;
                            case "UMC":
                                if (isset($rowData)) unset($rowData);
                                $rowData = array();
                                $maxFlds = count($fields["UMC"]) - 1;
                                if (trim($w[1]) <> "") { // uom code is not empty
                                    $rowData = loadFields($fields["UMC"], $w);
                                    $uom = $rowData["uom_code"];
                                    //Update, Insert or Delete the record
                                    $where = <<<SQL
where uom_code = "{$uom}"

SQL;
                                    $rc = $upd->updRecord($rowData, "UOMCODES", $where);
                                    echo "{$w[1]} {$rc}\n";
                                    $xx = $w[0] . "S";
                                    $$xx++;
                                } // uom code is not empty
                                break;
                            case "SUB":
                                if (isset($rowData)) unset($rowData);
                                $rowData = array();
                                $maxFlds = count($fields["SUB"]) - 1;
                                if (trim($w[1]) <> "") { // uom code is not empty
                                    $rowData = loadFields($fields["SUB"], $w);
                                    $pl = $rowData["p_l"];
                                    $subl = $rowData["subline"];
                                    //Update, Insert or Delete the record
                                    $where = <<<SQL
where p_l = "{$pl}"
  and subline = "{$subl}"

SQL;
                                    $rc = $upd->updRecord($rowData, "SUBLINES", $where);
                                    echo "{$w[1]} {$rc}\n";
                                    $xx = $w[0] . "S";
                                    $$xx++;
                                } // uom code is not empty
                                break;
                            case "CAT":
                                if (isset($rowData)) unset($rowData);
                                $rowData = array();
                                $maxFlds = count($fields["CAT"]) - 1;
                                if (trim($w[1]) <> "") { // uom code is not empty
                                    $rowData = loadFields($fields["CAT"], $w);
                                    $cat = $rowData["cat_id"];
                                    //Update, Insert or Delete the record
                                    $where = <<<SQL
where cat_id = "{$cat}"

SQL;
                                    $rc = $upd->updRecord($rowData, "CATEGORYS", $where);
                                    echo "{$w[1]} {$rc}\n";
                                    $xx = $w[0] . "S";
                                    $$xx++;
                                } // uom code is not empty
                                break;
                            case "PGR":
                                if (isset($rowData)) unset($rowData);
                                $rowData = array();
                                $maxFlds = count($fields["PGR"]) - 1;
                                if (trim($w[1]) <> "") { // uom code is not empty
                                    $rowData = loadFields($fields["PGR"], $w);
                                    $pg = $rowData["pgroup_id"];
                                    //Update, Insert or Delete the record
                                    $where = <<<SQL
where pgroup_id = "{$pg}"

SQL;
                                    $rc = $upd->updRecord($rowData, "PARTGROUPS", $where);
                                    echo "{$w[1]} {$rc}\n";
                                    $xx = $w[0] . "S";
                                    $$xx++;
                                } // uom code is not empty
                                break;
                            case "CLS":
                                if (isset($rowData)) unset($rowData);
                                $rowData = array();
                                $maxFlds = count($fields["CLS"]) - 1;
                                if (trim($w[1]) <> "") { // uom code is not empty
                                    $rowData = loadFields($fields["CLS"], $w);
                                    $cls = $rowData["class_id"];
                                    //Update, Insert or Delete the record
                                    $where = <<<SQL
where class_id = "{$cls}"

SQL;
                                    $rc = $upd->updRecord($rowData, "PARTCLASS", $where);
                                    echo "{$w[1]} {$rc}\n";
                                    $xx = $w[0] . "S";
                                    $$xx++;
                                } // uom code is not empty
                                break;
                            case "VIA":
                                if (isset($rowData)) unset($rowData);
                                $rowData = array();
                                $maxFlds = count($fields["VIA"]) - 1;
                                if (trim($w[1]) <> "") { // uom code is not empty
                                    $rowData = loadFields($fields["VIA"], $w);
                                    $via = $rowData["via_code"];
                                    //Update, Insert or Delete the record
                                    $where = <<<SQL
where via_code = "{$via}"

SQL;
                                    $rc = $upd->updRecord($rowData, "SHIPVIA", $where);
                                    echo "{$w[1]} {$rc}\n";
                                    $xx = $w[0] . "S";
                                    $$xx++;
                                } // uom code is not empty
                                break;
                            case "ZON":
                                if (isset($rowData)) unset($rowData);
                                $rowData = array();
                                $maxFlds = count($fields["ZON"]) - 1;
                                if (trim($w[1]) <> "") { // uom code is not empty
                                    $rowData = loadFields($fields["ZON"], $w);
                                    $hostcomp = $rowData["zone_company"];
                                    $comp = convert_comp($hostcomp);
                                    $zone = $rowData["zone"];
                                    //Update, Insert or Delete the record
                                    $where = <<<SQL
where zone = "{$zone}"
  and zone_company = {$comp}

SQL;
                                    $rc = $upd->updRecord($rowData, "WHSEZONES", $where);
                                    echo "{$w[1]} {$rc}\n";
                                    $xx = $w[0] . "S";
                                    $$xx++;
                                } // uom code is not empty
                                break;
                            case "UOM":
                                if (isset($rowData)) unset($rowData);
                                $rowData = array();
                                $maxFlds = count($fields["UOM"]) - 1;
                                if (trim($w[1]) <> "") { // uom code is not empty
                                    $rowData = loadFields($fields["UOM"], $w);
                                    $pl = $rowData["p_l"];
                                    $partNumber = $rowData["part_number"];
                                    $shadow = get_shadow($db, $pl, $partNumber);
                                    $hostcomp = $rowData["company"];
                                    $company = convert_comp($hostcomp);
                                    $uom = strtoupper($rowData["uom"]);
                                    if ($shadow > 0) {
                                        //Update, Insert or Delete the record
                                        $where = <<<SQL
where shadow = {$shadow}
and company = {$company}
and uom = "{$uom}"

SQL;
                                        $rowData["shadow"] = $shadow;
                                        $rc = $upd->updRecord($rowData, "PARTUOM", $where);
                                        echo "{$w[1]} {$rc}\n";
                                        if ($rowData["uom_qty"] > 0 and trim($rowData["upc_code"]) <> "") {
                                            $altype = -$rowData["uom_qty"];
                                            $rc1 = chkAddAlt($db, $shadow, $uom, $altype, $rowData["upc_code"]);
                                            $alt = trim($rowData["upc_code"]);
                                            $rc2 = addAlt($db, $shadow, $alt, $altype, $uom, 0, 1);
                                            //check if alt type is there
                                            //add alternate for upc code
                                        } // rc > 0
                                        $xx = $w[0] . "S";
                                        $$xx++;
                                    } // shadow > 0
                                    else { // log part not found somewhere
                                        $msg = "01|part: {$pl} {$partNumber} Not Found. Record: {$rowcnt}\n";
                                        //$rc=logError($w[0],$baseFile,$rowcnt," ",$msg,$d,1);
                                        echo $msg;
                                    } // log part not found somewhere
                                } // uom code is not empty
                                break;
                            case "PRT":
                                if (isset($rowData)) unset($rowData);
                                $rowData = array();
                                $maxFlds = count($fields["PRT"]) - 1;
                                if (trim($w[1]) <> "") { // uom code is not empty
                                    $rowData = loadFields($fields["PRT"], $w);
                                    // check if P/L and part number is set, if not error out
                                    if (isset($rowData["shadow_number"]) and $rowData["shadow_number"] < 1) { // see if part is on file and set shadow
                                        $rowData["shadow_number"] = checkPlPart($db, $rowData["p_l"], $rowData["part_number"]);

                                    } // see if part is on file and set shadow
                                    if (!isset($rowData["shadow_number"]) or $rowData["shadow_number"] < 1) {
                                        $rowData["shadow_number"] = 0;
//Add a loop around here, check to make sure no part is using returned shadow
// if so, keep getting control #'s until you find a free 1

                                        $s = get_contrl($db, 0, "PARTS");
                                        if ($s > 0) $rowData["shadow_number"] = $s;
                                        unset($s);
                                    }
                                    $shadow = $rowData["shadow_number"];
                                    $pl = $rowData["p_l"];
                                    $partNumber = $rowData["part_number"];
                                    if (!isset($rowData["unit_of_measure"])) $rowData["unit_of_measure"] = "EA";
                                    $uom = trim($rowData["unit_of_measure"]);
                                    $rc = val_pl($db, $pl, 0);
// perhaps output to 2 files, 1 for  error file and file of errored records
                                    if ($rc < 1) { // log pl error and skip to next record
                                        $msg = "02|Error Invalid Product Line: value: {$pl} Part Number: {$partNumber}\n";
                                        $rc = logError($w[0], $baseFile, $rowcnt, " ", $msg, $d, 1);
                                        exit;
                                    } // log pl error and skip to next record
                                    //Update, Insert or Delete the record

                                    $sl = $rowData["part_subline"];
                                    $rc = val_sl($db, $pl, $sl);
                                    if ($rc < 1) {
                                        $msg = "03|Error Invalid Subline: P/L: {$pl} SubLine: {$sl} Part Number: {$partNumber}\n";
                                        $rc = logError($w[0], $baseFile, $rowcnt, " ", $msg, $d, 1);
                                        exit;
                                    } // log subline error
                                    foreach ($partValFields as $key => $v) {
                                        if (isset($rowData[$key]) and trim($rowData[$key]) <> "") {
                                            $j1 = explode("|", $v);
                                            $rcv = validateIt($db, $j1[0], $j1[1], $rowData[$key], 1, 1);
                                            if (!$rcv) {
                                                $msg = "04|Error validation: {$j1[0]} value: {$rowData[$key]} is not a valid code P/L: {$pl} Part Number: {$partNumber}\n";
                                                $rc = logError($w[0], $baseFile, $rowcnt, " ", $msg, $d, 1);
                                                exit;
                                            }
                                        } // there is a value in the field
                                    } // end foreach partValFields
                                    $where = <<<SQL
where p_l = "{$pl}"
  and part_number = "{$partNumber}"

SQL;
                                    $rc = $upd->updRecord($rowData, "PARTS", $where);
                                    echo "{$w[1]} {$rc}\n";
                                    $xx = $w[0] . "S";
                                    $$xx++;
                                    // add alternates
                                    $alt = trim($pl) . trim($partNumber);
                                    $rc = addAlt($db, $shadow, $alt, 9997, $uom, 0);
                                    $alt = trim($partNumber);
                                    $rc = addAlt($db, $shadow, $alt, 9998, $uom, 0);
                                    $alt = "." . trim($shadow);
                                    $rc = addAlt($db, $shadow, $alt, 9999, $uom, 0);
                                } // pl is not empty
                                break;
                            case "POH":
                                if (isset($rowData)) unset($rowData);
                                $rowData = array();
                                $maxFlds = count($fields["POH"]) - 1;
                                if (trim($w[2]) <> "") { // po is not empty
                                    $rowData = loadFields($fields["POH"], $w);
                                    $HPO = $rowData["host_po_num"];
                                    $hostcomp = $rowData["company"];
                                    $comp = convert_comp($hostcomp);
                                    $vendor = $rowData["vendor"];
                                    $validPO = chkHostPo($db, $comp, $HPO);
                                    if ($validPO < 1) $rowData["wms_po_num"] = get_contrl($db, 0, "POHEADER");
                                    else $rowData["wms_po_num"] = $validPO;
                                    $PO = $rowData["wms_po_num"];
                                    $rowData["po_status"] = 0;
                                    $rowData["num_messages"] = 0;
                                    if (trim($rowData["comment"]) <> "") $rowData["num_messages"] = 1;
                                    $rowData["created_by"] = 0;
                                    //validate comp, vendor
                                    $validComp = validateIt($db, "COMPANY", "company_number", $comp, 0, 0);
                                    $validVend = validateIt($db, "VENDORS", "vendor", $vendor, 1, 0);
                                    if ($validComp == false or $validVend == false) {
                                        if (isset($msg)) unset($msg);
                                        $msg = array();
                                        if (!$validComp) array_push($msg, "01|Invalid Company: {$comp}");
                                        if (!$validVend) array_push($msg, "05|Invalid Vendor: {$vendor}");
                                        $rc = logError($w[0], $baseFile, $rowcnt, $HPO, $msg, $d, 1);
                                        exit;
                                    }
                                    //validate/format dates to insert into MYSQL
                                    $rowData["po_date"] = usa_to_eur($rowData["po_date"]);
                                    $rowData["est_deliv_date"] = usa_to_eur($rowData["est_deliv_date"]);
                                    $rowData["sched_date"] = usa_to_eur($rowData["sched_date"]);
                                    //Update, Insert or Delete the record
                                    $where = <<<SQL
where company = {$comp}
  and wms_po_num = {$PO}

SQL;
                                    $rc = $upd->updRecord($rowData, "POHEADER", $where);
                                    echo "{$w[1]} {$rc}\n";
                                    $xx = $w[0] . "S";
                                    $$xx++;
                                } // po is not empty
                                break;
                            case "POD":
                                if (isset($rowData)) unset($rowData);
                                $rowData = array();
                                $maxFlds = count($fields["POD"]) - 1;
                                if (trim($w[1]) <> "") { // po is not empty
                                    $rowData = loadFields($fields["POD"], $w);
                                    $rowData["shadow"] = 0;
                                    // force comp to 1 since POD doesn't have the company# in it
                                    //$hostcomp=$rowData["company"];
                                    //$comp=convert_comp($hostcomp);
                                    $comp = 1;

                                    $PO = $rowData["poi_po_num"];
                                    $poLine = $rowData["poi_line_num"];
                                    $pl = trim($rowData["p_l"]);
                                    $partNum = trim($rowData["part_number"]);
                                    $pnum = $pl . $partNum;
                                    $validPO = chkHostPo($db, $comp, $PO);
//make sure PO is on file
                                    if ($validPO < 1) {
                                        $msg = "06|(POD) PO Number: {$PO} is Not on File\n";
                                        $rc = logError($w[0], $baseFile, $rowcnt, $PO, $msg, $d, 1);
                                        exit;
                                    } // end validPO
                                    //replace host PO with WMS PO
                                    $rowData["poi_po_num"] = $validPO;
                                    $poline = chkPoLine($db, $PO, $poLine);
//check if line exists already, if not init non imported fieds
                                    if ($poline["status"] == -35) {
                                        $rowData["weight"] = 0.00;
                                        $rowData["volume"] = 0.00;
                                        $rowData["qty_recvd"] = 0;
                                        $rowData["qty_cancel"] = 0;
                                    } // end po line nof
                                    $part = getPart($db, $pnum);
                                    $j = 0;
//check part count
                                    if ($part["num_rows"] > 1) { // uh ooh have more than 1 part
                                        $msg = "07|{$PO} Duplicate part number, P/L: {$pl} part number: {$partNum}\n";
                                        $rc = logError($w[0], $baseFile, $rowcnt, $PO, $msg, $d, 1);
                                        exit;
                                    } // uh ooh have more than 1 part
                                    if ($part["status"] <> -35) { // part is on file
                                        $rowData["shadow"] = $part[$j]["shadow_number"];
                                        // need to get UOM info to fill in wght and volume
                                    } // part is on file
                                    else { // part is NOF
                                        $msg = "08|Invalid part P/L: {$pl} part number: {$partNum}";
                                        $rc = logError($w[0], $baseFile, $rowcnt, $PO, $msg, $d, 1);
                                        echo $rc;
                                        mvFile($f, $errDir);
                                        exit;
                                    } // part is NOF

                                    //Update, Insert or Delete the record
                                    $where = <<<SQL
where poi_po_num = "{$validPO}"
  and poi_line_num = {$poLine}

SQL;
                                    $rc = $upd->updRecord($rowData, "POITEMS", $where);
                                    $rc1 = updPOLines($db, $validPO, $poLine);
                                    echo "{$w[1]} {$rc}\n";
                                    $xx = $w[0] . "S";
                                    $$xx++;
                                } // po is not empty
                                break;

                            case "ORD":
                                $ORDS++;
                                if (isset($rowData)) unset($rowData);
                                $rowData = array();
                                $maxFlds = count($fields["ORD"]) - 1;
                                if (trim($w[2]) <> "") { // order is not empty
                                    $rowData = loadFields($fields["ORD"], $w);
                                    $ORD = $rowData["host_order_num"];
                                    $hostcomp = $rowData["company"];
                                    $comp = convert_comp($hostcomp);
                                    $rowData["company"] = $comp;
                                    $customer = $rowData["customer_id"];
                                    $validORD = chkHostOrder($db, $comp, $ORD);
                                    if ($validORD["order_num"] < 1) { // get new order# and init fields not in import
                                        $Order = get_contrl($db, 0, "ORDERS");
                                        $rowData["order_stat"] = 0;
                                        $rowData["wms_date"] = date("Y-m-d H:i:s");
                                        $rowData["num_lines"] = 0;
                                    } // get new order# and init fields not in import
                                    else $Order = $validORD["order_num"];
                                    $rowData["order_num"] = $Order;
                                    //validate comp, customer
                                    $validComp = validateIt($db, "COMPANY", "company_number", $comp, 0, 0);
                                    $validCust = validateIt($db, "CUSTOMERS", "customer", $customer, 1, 0);
                                    if ($validComp == false or $validCust == false) {
                                        $msg = array();
                                        if (!$validComp) array_push($msg, "01|Invalid Company: {$comp}");
                                        if (!$validCust) array_push($msg, "09|Invalid Customer: {$customer}");
                                        $rc = logError($w[0], $baseFile, $rowcnt, $ORD, $msg, $d, 1);
                                        exit;
                                    }
                                    //validate/format dates to insert into MYSQL
                                    $rowData["enter_date"] = usa_to_eur($rowData["enter_date"]);
                                    $rowData["date_required"] = usa_to_eur($rowData["date_required"]);
                                    $rowData["drop_ship_flag"] = ($rowData["drop_ship_flag"] == "Y") ? 1 : 0;
                                    //Update, Insert or Delete the record
                                    $where = <<<SQL
where order_num = {$Order}

SQL;
                                    $rc = $upd->updRecord($rowData, "ORDERS", $where);
                                    echo "{$w[1]} {$rc}\n";
                                    $xx = $w[0] . "S";
                                    $$xx++;
                                } // order is not empty
                                break;  // end ORD

                            case "ITM":
                                if (isset($rowData)) unset($rowData);
                                $rowData = array();
                                $maxFlds = count($fields["ITM"]) - 1;
                                if (trim($w[1]) <> "") { // order num is not empty
                                    $rowData = loadFields($fields["ITM"], $w);
                                    $ORD = $rowData["ord_num"];
                                    $comp = 0;
                                    $validOrd = chkHostOrder($db, $comp, $ORD);
                                    //error out if Host Order is NOF
                                    $Order = $validOrd["order_num"];
                                    //replace host Order# with WMS Order
                                    $rowData["ord_num"] = $Order;
                                    $comp = $validOrd["company"];
                                    $rowData["inv_comp"] = $comp;
                                    $rowData["shadow"] = 0;
                                    $lineNum = $rowData["line_num"];
                                    $pl = trim($rowData["p_l"]);
                                    $partNum = trim($rowData["part_number"]);
                                    $pnum = $pl . $partNum;
//make sure Order is on file
                                    if ($Order < 1) {
                                        $msg = "10|Order Number: {$Order} is Not on File\n";
                                        $rc = logError($w[0], $baseFile, $rowcnt, $ORD, $msg, $d, 1);
                                        exit;
                                    } // end valid Order
//check if line exists already, if not init non imported fieds
                                    $poline = chkOrderLine($db, $Order, $lineNum);
                                    if ($poline["status"] == -35) {
                                        $rowData["qty_ship"] = 0;
                                        $rowData["qty_bo"] = 0;
                                        $rowData["qty_avail"] = 0;
                                        $rowData["line_status"] = 0;
                                        $rowData["zone"] = " ";
                                        $rowData["whse_loc"] = " ";
                                        $rowData["qty_in_primary"] = 0;
                                        $rowData["num_messg"] = 0;
                                        $rowData["item_pulls"] = 0;
                                        $rowData["hazard_id"] = 0;
                                        $rowData["part_weight"] = 0.00;
                                        $rowData["part_subline"] = " ";
                                        $rowData["part_category"] = " ";
                                        $rowData["part_group"] = " ";
                                        $rowData["part_class"] = " ";
                                    } // end ord line nof
                                    $part = chkPart($pnum, $comp);
                                    $j = 0;
//check part count
                                    if ($part["numRows"] > 1) { // uh ooh have more than 1 part
                                        $msg = "07|Duplicate part number, P/L: {$pl} part number: {$partNum}\n";
                                        $rc = logError($w[0], $baseFile, $rowcnt, $ORD, $msg, $d, 1);
                                        exit;
                                    } // uh ooh have more than 1 part
                                    if ($part["numRows"] == 1) { // part is on file
                                        $rowData["shadow"] = $part["Result"]["shadow_number"];
                                        $rowData["qty_avail"] = $part["WhseQty"][$comp]["qty_avail"];
                                        $rowData["zone"] = substr($part["WhseQty"][$comp]["primary_bin"], 0, 1);
                                        $rowData["whse_loc"] = $part["WhseQty"][$comp]["primary_bin"];
                                        $rowData["qty_in_primary"] = 0;
                                        $rowData["hazard_id"] = $part["Part"]["hazard_id"];
                                        $rowData["part_weight"] = $part["Part"]["part_weight"];
                                        $rowData["part_subline"] = $part["Part"]["part_subline"];
                                        $rowData["part_category"] = $part["Part"]["part_category"];
                                        $rowData["part_group"] = $part["Part"]["part_group"];
                                        $rowData["part_class"] = $part["Part"]["part_class"];

                                        $pq = $rowData["qty_avail"];
                                        if (trim($rowData["whse_loc"]) == "" or $rowData["qty_in_primary"] == 0) { // no primary, get first WhseLoc if exists
                                            if (isset($part["WhseLoc"][1])) {
                                                $bin = $part["WhseLoc"][1]["whs_location"];
                                                $qty = $part["WhseLoc"][1]["whs_qty"];
                                                $code = $part["WhseLoc"][1]["whs_code"];
                                                $rowData["zone"] = substr($bin, 0, 1);
                                                $rowData["whse_loc"] = $bin;
                                                $rowData["qty_in_primary"] = $qty;
                                            } // whseloc is set
                                        } // no primary, get first WhseLoc if exists
                                        // need to get UOM info to fill in wght and volume
                                    } // part is on file
                                    else { // part is NOF
                                        $msg = "08|Invalid part P/L: {$pl} part number: {$partNum}";
                                        $rc = logError($w[0], $baseFile, $rowcnt, $ORD, $msg, $d, 1);
                                        exit;
                                    } // part is NOF

                                    //Update, Insert or Delete the record
                                    $where = <<<SQL
where ord_num = "{$Order}"
  and line_num = {$lineNum}

SQL;
                                    $rc = $upd->updRecord($rowData, "ITEMS", $where);
                                    $rc1 = updOrderLines($db, $Order, $lineNum);
                                    echo "{$w[1]} {$rc} Line: {$lineNum} {$rc1}\n";
                                    $xx = $w[0] . "S";
                                    $$xx++;
                                } // order num is not empty
                                break;
                            case "DRP":
                                if (isset($rowData)) unset($rowData);
                                $rowData = array();
                                $maxFlds = count($fields["DRP"]) - 1;
                                if (trim($w[1]) <> "") { // order num is not empty
                                    $rowData = loadFields($fields["DRP"], $w);
                                    $ORD = $rowData["order_num"];
                                    $comp = 0;
                                    $validOrd = chkHostOrder($db, $comp, $ORD);
                                    $Order = $validOrd["order_num"];
                                    if ($Order > 0) { // valid order
                                        //replace host Order# with WMS Order
                                        $rowData["ord_num"] = $Order;
                                        $comp = $validOrd["company"];

                                        //Update, Insert or Delete the record
                                        $where = <<<SQL
 where order_num = {$Order}

SQL;
                                        $rc = $upd->updRecord($rowData, "DROPSHIP", $where);
                                        echo "{$w[1]} {$rc}\n";
                                        $xx = $w[0] . "S";
                                        $$xx++;
                                    } // end valid order
                                    else { // order is not valid
                                        $msg = "10|DropShip Record: Order Number: {$ORD} is Not on File\n";
                                        $rc = logError($w[0], $baseFile, $rowcnt, $ORD, $msg, $d, 1);
                                        exit;
                                    } // order is not valid
                                } // order num is not empty
                                break; // end DRP

                            case "ORL":
                                if (isset($rowData)) unset($rowData);
                                $rowData = array();
                                $maxFlds = count($fields["ORL"]) - 1;
                                if (trim($w[1]) <> "") { // order num is not empty
                                    $rowData = loadFields($fields["ORL"], $w);
                                    $ORD = $rowData["order_num"];
                                    $comp = 0;
                                    $validOrd = chkHostOrder($db, $comp, $ORD);
                                    $Order = $validOrd["order_num"];
                                    if ($Order > 0) { // valid order
                                        $prio = getOrderPrio($db, $Order);
                                        $relCode = "WAI";
                                        if ($prio < 4) $relCode = "REL";
                                        if ($rowData["releaseCode"] == "REL") $relCode = "REL";
                                        $rc = add2Que($db, $Order, $relCode);
                                    } // valid order
                                } // order num is not empty
                                break; // end ORL
                            case "BTP":
                                if (isset($rowData)) unset($rowData);
                                $rowData = array();
                                $maxFlds = count($fields["BTP"]) - 1;
                                if (trim($w[1]) <> "") { // uom code is not empty
                                    $rowData = loadFields($fields["BTP"], $w);
                                    $hostcomp = $rowData["typ_company"];
                                    $comp = convert_comp($hostcomp);
                                    $bint = $rowData["typ_code"];
                                    //Update, Insert or Delete the record
                                    $where = <<<SQL
where typ_code = "{$bint}"
  and typ_company = {$comp}

SQL;
                                    $rc = $upd->updRecord($rowData, "BINTYPES", $where);
                                    echo "{$w[1]} {$rc}\n";
                                    $xx = $w[0] . "S";
                                    $$xx++;
                                } // uom code is not empty
                                break;
                        } // end switch w[0]
                    } // end count w
                } // d <> ""
            } // end foreach data
        $w1 = basename($f);
        //rename($f,"{$doneDir}/{$w1}");
    } // end foreach files
} // end count files

print_r($aa);
$xx = json_encode($aa);
echo $xx;
file_put_contents("/tmp/order.json", $xx);
exit;

if ($rowcnt > 0) echo "Rows Read={$rowcnt}\n";
if ($VENS > 0) echo "Vendors Processed={$VENS}\n";
if ($CSTS > 0) echo "Customers Processed={$CSTS}\n";
if ($PLMS > 0) echo "Product Lines Processed={$PLMS}\n";
if ($PRTS > 0) echo "Parts Processed={$PRTS}\n";
if ($inValidVend > 0) echo "Invalid Vendors={$inValidVend}\n";
if ($UMCS > 0) echo "UOM Codes Processed={$UMCS}\n";
if ($UOMS > 0) echo "UOMs Processed={$UOMS}\n";
if ($SUBS > 0) echo "Sub Lines Processed={$SUBS}\n";
if ($CATS > 0) echo "Categories Processed={$CATS}\n";
if ($PGRS > 0) echo "Product Groups Processed={$PGRS}\n";
if ($PCLS > 0) echo "Product Classes Processed={$PCLS}\n";
if ($VIAS > 0) echo "Ship Vias Processed={$VIAS}\n";
if ($ZONS > 0) echo "Warehouse Zones Processed={$ZONS}\n";
if ($BTPS > 0) echo "Bin Types Processed={$BTPS}\n";

if ($ORDS > 0) {
    $htm = <<<HTML
<a href="../daemons/allocate.php">Allocate</a>
HTML;
    echo $htm;
}

function chk_vendor($db)
{
    $Vendors = array();
    $SQL = <<<SQL
select vendor from VENDORS

SQL;

    $rc = $db->query($SQL);
    $numrows = $db->num_rows();
    $i = 0;
    while ($i < $numrows) {
        $db->next_record();
        if ($numrows) {
            $v = trim($db->f("vendor"));
            $Vendors[$v] = $v;
        }
        $i++;
    } // while i < numrows
    return ($Vendors);
} // end chk_vendor
function loadFields($fields, $data, $json = false)
{
    $Rec = array();
    $maxFlds = count($fields) - 1;
    for ($i = 0; $i <= $maxFlds; $i++) {
        $k = ($json) ? $i + 1 : $i + 2;
        if (isset($fields[$i]) and isset($data[$k])) $Rec[$fields[$i]] = $data[$k];
    } // end for i
    if (!isset($Rec[$fields[$maxFlds]])) $Rec[$fields[$maxFlds]] = 2;
    return ($Rec);
} // end loadFields
function val_pl($db, $pl, $comp)
{
    $ret = false;
    if (trim($pl) == "") return (true);

    $SQL = <<<SQL
select count(*) as cnt from PRODLINE
where pl_code = "{$pl}"
and pl_company >= {$comp}

SQL;

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
    if ($i > 1) $ret = true;
    return ($ret);
} // end val_pl
function val_sl($db, $pl, $sl)
{
    $ret = false;
    if (trim($pl) == "" or trim($sl) == "") return (true);

    $SQL = <<<SQL
select count(*) as cnt from SUBLINES
where p_l = "{$pl}"
and subline >= "{$sl}"

SQL;

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
    if ($i > 1) $ret = true;
    return ($ret);
} // end val_sl

function validateIt($db, $table, $keyFld, $val, $str = 1, $addNew = 0)
{
    // if str=0, dont quote the key value
    $quote = '"';
    if ($str < 1) $quote = '';
    $ret = false;
    if (trim($val) == "") return (true);
    $SQL = <<<SQL
select count(*) as cnt from {$table}
where {$keyFld} = {$quote}{$val}{$quote}

SQL;

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
    if ($cnt > 0) $ret = true;
    if (!$ret and $addNew > 0) { // add new record with key
        $SQL = <<<SQL
insert into {$table} ($keyFld)
values ({$quote}{$val}{$quote})

SQL;
        $rc1 = $db->Update($SQL);
        if ($rc1 > 0) $ret = true;
    } // add new record with key
    return ($ret);
}

function addAlt($db, $shadow, $alt, $typeCode, $uom, $sort, $chk = 1)
{
    $ret = 0;
    $numrows = 0;
    if ($shadow > 0) {
        $altdata = array();
        if ($chk > 0) {
            $numrows = 1;
            $SQL = <<<SQL
select * from ALTERNAT
where alt_part_number = "{$alt}" 
and alt_shadow_num = {$shadow}
and alt_type_code = {$typeCode}

SQL;

            $rc = $db->query($SQL);
            $numrows = $db->num_rows();
            $i = 1;
            while ($i <= $numrows) {
                $db->next_record();
                if ($numrows and $db->Record) {
                    foreach ($db->Record as $key => $data) {
                        if (!is_numeric($key)) {
                            $altdata["$key"] = $data;
                        }
                    }
                }
                $i++;
            } // while i < numrows
        } //chk > 0

        if ($numrows > 0) {
            $upd = false;
            if ($uom <> $altdata["alt_uom"]) $upd = true;
            if ($upd) {
                $SQL = <<<SQL
update ALTERNAT
set alt_uom="{$uom}"
where alt_part_number = "{$alt}" 
and alt_shadow_num = {$shadow}
and alt_type_code = {$typeCode}

SQL;
                $ret = $db->Update($SQL);
            } // end upd
        } // numrows > 0
        else { // insert
            $SQL = <<<SQL
insert into ALTERNAT 
( alt_shadow_num, alt_part_number, alt_type_code, alt_uom, alt_sort)
values ( {$shadow}, "{$alt}", {$typeCode}, "{$uom}", {$sort} )

SQL;
            $ret = $db->Update($SQL);
        }// insert
    } // end shadow > 0
    return ($ret);
} // and addAlt
function get_shadow($db, $pl, $part)
{
    //global $d;
    //global $w;
    //global $rowcnt;
    //global $baseFile;
    $ret = 0;
    $key = trim($pl) . trim($part);
    $SQL = <<<SQL
select alt_shadow_num as shadow
from ALTERNAT
where alt_part_number = "{$key}"
and alt_type_code = 9997

SQL;

    $rc = $db->query($SQL);
    $numrows = $db->num_rows();
    $i = 1;
    while ($i <= $numrows) {
        $db->next_record();
        if ($numrows) {
            $ret = $db->f("shadow");
        }
        $i++;
    } // while i < numrows
//if ($ret < 1)
//{
    //$msg="99|Internal Error: Shadow # Not found";
    //$rc=logError($w[0],$baseFile,$rowcnt," ",$msg,$d,1);
//}
    return ($ret);
} // end get_shadow
function chkAddAlt($db, $shadow, $uom, $altype, $upc)
{
    $ret = 0;
    $cnt = 0;
    $t = -$altype;
    $SQL = <<<SQL
select count(*) as cnt from ALTYPES
where al_key = {$altype}

SQL;

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
    if ($cnt < 1) { // add type
        $SQL = <<<SQL
insert into ALTYPES (al_key,al_desc)
values ({$altype},"Case of {$t}")

SQL;
        $rc1 = $db->Update($SQL);
    } // add type
    if ((isset($rc1) and $rc1 > 0) or $cnt > 0) { //add was success
        $SQL = <<<SQL
insert ignore into ALTERNAT
(alt_shadow_num,alt_part_number,alt_type_code,alt_uom,alt_sort)
values ({$shadow},"{$upc}",{$altype},"{$uom}",0)

SQL;
        $ret = $db->Update($SQL);
    } //add was success
    return ($ret);
} // end chkAddAlt
function chkHostPo($db, $comp, $PO)
{
    $ret = 0;
    $SQL = <<<SQL
 select wms_po_num
 from POHEADER
 where company = {$comp}
 and host_po_num = "{$PO}"

SQL;
    $rc = $db->query($SQL);
    $numrows = $db->num_rows();
    $i = 1;
    while ($i <= $numrows) {
        $db->next_record();
        if ($numrows) {
            $ret = $db->f("wms_po_num");
        }
        $i++;
    } // while i < numrows
    return ($ret);
} // end chkHostPo
function chkPoLine($db, $PO, $poLine)
{
    $ret = array();
    $ret["status"] = 0;
    $SQL = <<<SQL
 select * from POITEMS
 where poi_po_num = {$PO}
 and poi_line_num = {$poLine}

SQL;

    $rc = $db->query($SQL);
    $numrows = $db->num_rows();
    $i = 0;
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
    $ret["num_rows"] = $numrows;
    if ($ret["num_rows"] == 0) {
        $ret["status"] = -35;
    }
    return ($ret);
} // end chkPoLine
/*
Purchase Orders
 wms_po_num is generated
 po_status is set to 0
 num_messages is generated
 created_by is set to 0, user id if entered manually in WMS
Req     Field           DataType        Description
*       Record Code                     Always POH
*       company       smallint
*       host_po_num     char(20)        PO if type is "P" or ASN
                                        RMA# if type "R" for Credit
                                        Transfer# if type is "T"
*       po_type         char(1)         P=po, T=transfer, R=cust return(RMA)
*       vendor          char(10)
        po_date         datetime
*       num_lines       int
        bo_flag         tinyint         0=cancel b/o, 1=bo allowed [default=1]
        est_deliv_date  datetime        Default=tomorrow
        ship_via        char(6)
        sched_date      datetime        default=delivery date
        xdock           tinyint         1=cross dock
        disp_comment    tinyint         Display comment when receiving
        comment         varchar(128)
        customer_id     char(12)        if spec order or xdock
        ordernum        int             if special order, xdock or RMA
        container       varchar(15)     optional if ASN provides the container id
        carton_id       varchar(15)     optional if provided by ASN


POITEMS
  poi_po_num is looked up (this is the WMS po#)
  shadow is looked up
  qty_recvd is set to 0
  qty_bo is set to 0
  qty_cancel is set to 0
  weight is looked up
  volume is looked up
  poi_status tinyint, -- 0=open, 1=recvd part, 9=complete
Req     Field           DataType        Description
*       Record Code                     Always POD
*       poi_po_num      int
*       poi_line_num    int
*       p_l             char(3)
*       part_number     char(22)
        part_desc       char(30)
*       uom             char(3)
*       qty_ord         int
        mdse_price      numeric(10,3)
        core_price      numeric(10,3)
        line_type       char(1)         " " normal, C=core, D="Defect"
        case_uom        char(3)         if part was ordered in cases
        case_qty        int             if part was ordered in cases
        vendor_ship_qty int             if ASN
        packing_slip    char(22)        if ASN
        tracking_num    char(22)        if ASN
        bill_lading     char(22)        if ASN
        container_id    char(15)        if ASN
        carton_id       char(10)        if ASN and vendor 


poi_po_num 
poi_line_num 
p_l 
part_number 
part_desc 
uom 
qty_ord 
mdse_price 
core_price 
line_type 
case_uom
case_qty 
vendor_ship_qty int
packing_slip 
tracking_num 
bill_lading  
container_id 
carton_id 
*/

function chkHostOrder($db, $comp, $ORD)
{
    $ret = array("order_num" => 0, "company" => 0);
    $where = <<<SQL
 where company = {$comp}
 and host_order_num = "{$ORD}"
SQL;
    if ($comp == 0) {
        $where = <<<SQL
 where host_order_num = "{$ORD}"
SQL;
    } // end comp == 0
    $SQL = <<<SQL
 select order_num,company
 from ORDERS
 {$where}

SQL;
//echo "<pre>";
//echo "{$SQL}\n";
    $rc = $db->query($SQL);
    $numrows = $db->num_rows();
    $i = 1;
    while ($i <= $numrows) {
        $db->next_record();
        if ($numrows) {
            $ret["order_num"] = $db->f("order_num");
            $ret["company"] = $db->f("company");
        }
        $i++;
    } // while i < numrows
    return ($ret);
} // end chkHostOrder

function chkOrderLine($db, $ORD, $lineNum)
{
    $ret = array();
    $ret["status"] = 0;
    $SQL = <<<SQL
 select * from ITEMS
 where ord_num = {$ORD}
 and line_num = {$lineNum}

SQL;
    $rc = $db->query($SQL);
    $numrows = $db->num_rows();
    $i = 0;
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
    $ret["num_rows"] = $numrows;
    if ($ret["num_rows"] == 0) {
        $ret["status"] = -35;
    }
    return ($ret);


} // end chkOrderLine

function chkPart($pnum, $comp)
{
    $ret = array();
    $ret["upc"] = $pnum;
    $ret["comp"] = $comp;
    $pr = new PARTS;
    $pnum = trim($pnum);
    $ret = $pr->chkPart($pnum, $comp);
    return $ret;
} // end chkPart

function getOrderPrio($db, $order)
{
    $ret = 99;
    $SQL = <<<SQL
select priority from ORDERS where order_num = {$order}

SQL;
    $rc = $db->query($SQL);
    $numrows = $db->num_rows();
    $i = 1;
    while ($i <= $numrows) {
        $db->next_record();
        if ($numrows) {
            $ret = $db->f("priority");
        }
        $i++;
    } // while i < numrows
    return ($ret);
} // end getOrderPrio
function updOrderLines($db, $order, $line)
{
    $SQL = <<<SQL
update ORDERS
set num_lines = {$line}
where order_num = {$order}

SQL;
    $rc = $db->Update($SQL);
    return $rc;
} // end updOrderLines

function updPOLines($db, $po, $lines)
{
    $SQL = <<<SQL
update POHEADER
set num_lines = {$lines}
where wms_po_num = {$po}

SQL;

    $rc = $db->Update($SQL);
    return $rc;
} // end updPOLines

function convert_comp($hostcomp)
{
    global $RESTSRV;
    $r = array("action" => "convert", "host_company" => $hostcomp);
    $rc = restSrv($RESTSRV, $r);
    $w = (json_decode($rc, true));
    $ret = $w["company_number"];
    if ($ret == "") $ret = 0;
    return $ret;
} // end convert_comp

function add2Que($db, $order, $typ)
{
    $SQL = <<<SQL
  insert into ORDQUE (order_num,que_key,que_data)
  values ({$order},"{$typ}","")
 ON DUPLICATE KEY UPDATE
 que_key="{$typ}"

SQL;
    /* que_key  WAI=Waiting if priority > 2 else REL=Release to floor */
    $rc = $db->Update($SQL);
    return ($rc);
} // end add2Que

function checkPlPart($db, $pl, $part_number)
{
    $ret = 0;
    $SQL = <<<SQL
select shadow_number 
from PARTS 
where p_l = "{$pl}"
and part_number = "{$part_number}"

SQL;
    $rc = $db->query($SQL);
    $numrows = $db->num_rows();
    $i = 1;
    while ($i <= $numrows) {
        $db->next_record();
        if ($numrows) {
            $ret = $db->f("shadow_number");
        }
        $i++;
    } // while i < numrows
    return $ret;

} // end checkPlPart

function find_all_files($dir, $lock = "", $subdirs = true)
{
    $result = array();
    $root = scandir($dir);
    // sort the files by oldest file first
    $root = sortFiles($dir, $root, false);
    foreach ($root as $value) {
        //ignore . and ..
        if ($value === '.' || $value === '..') {
            continue;
        }
        // ignore lock files
        if ($lock <> "" and strpos($value, $lock)) continue;
        if ($lock <> "") { // check if lock file exists
            $w1 = basename($value);
            $w2 = basename($w1, ".dat");
            if (file_exists("{$dir}/{$w2}.{$lock}")) {
                continue;
            }
        } // check if lock file exists
        // if regular file, and no lock, add to array
        if (is_file("$dir/$value")) {
            $result[] = "$dir/$value";
            continue;
        }

        //if directory, add all the files
        if ($subdirs) foreach (find_all_files("$dir/$value", "", true) as $value) {
            $result[] = $value;
        }
    }
    return $result;
} // end find_all_files
function mvFile($f, $doneDir)
{
    $w1 = basename($f);
    rename($f, "{$doneDir}/{$w1}");
} // end mvFile


?>
