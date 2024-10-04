<?php
//in.php -- read erp2wms datafiles, add/upd/delete records from it.
//12/14/21 dse initial
//02/17/22 dse add allow_inplace to VENDORS
//02/24/22 dse Add Customers in CST
//12/16/22 dse sort the files by oldest date/time first
//05/18/23 dse Convert to service, still takes pipe input
//05/24/23 dse add company to ITM, DRP and ORL
//06/02/23 dse add Auto Add customer is NOF with blank name and address, set a flag that if DRP record update the name and address from the DRP
//07/24/24 dse add option 399,400 to just add returns to default cart

/*TODO


Add wr_log and Debug logging
figure out what to do when a part is missing
figure out what to do when a important validation record is missing

Delete Order or PO
json:
    "DELETE": {
        "host_document_id": "483881",
        "entity": "17260",
        "doc_type": "O",
        "company": 1
    }

If Order,
Check if released, if not, set status to CANCELED and done
if Yes,
	is it being picked?
		Yes: Send error response, "Picking already started"
                No: set status to CANCELED, then unallocate

If PO,
 check if status <> 0, if yes, error "PO (type) is already being received"

else, set status to CANCELED

*/
define('LOGFILE', "/tmp/in.log");
$LOGFILE = "/tmp/in.log";
//define('DEBUG',true);
$debug = false;

$setConfig = true;
require("config.php");

$wmsInclude = $_SESSION['wms']['wmsInclude'];

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
require_once("{$wmsInclude}/onlyascii.php");
require_once("{$wmsInclude}/get_option.php");

$db = new WMS_DB;
$upd = new AddUpdDEL;
set_time_limit(0);
//echo '<pre>' . print_r(get_defined_vars(), true) . '</pre>';
$RESTSRV = "http://{$wmsIp}{$wmsServer}/COMPANY_srv.php";

//echo "<pre>";

if (!isset($_REQUEST["rwms"])) {
    $newcode = 401;
    header('X-PHP-Response-Code: ' . $newcode, true, $newcode);
    exit;
}

require("inFldMap.php");
require("inFunctions.php");

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
$RETS = 0;
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

$inputdata = file_get_contents("php://input");
wr_log($LOGFILE, $inputdata);
$baseFile = "WMS_Service_Input";
$f = "WMSI_" . date("mdY") . "_" . microtime() . ".in";
$f = str_replace(" ", "", $f);
$f = str_replace("0.", "", $f);
$f = str_replace("\n", "", $f);
$f = str_replace("\r", "", $f);
$baseFile = $f;
file_put_Contents("{$ServiceIn}/{$f}", $inputdata);
$dataTypeIn = "text";
$inputdata = check_payload($inputdata);
$outType = $dataTypeIn;
$j = substr_count($inputdata, "|");
$output = array();
if (count($j) and strlen($inputdata) > 1) {
    if (1 == 2) { // don't need for service input
        // get uniq filename to save later
        $f = substr($inputdata, 0, 3) . "_" . date("mdY") . "_" . microtime() . ".txt";
        $f = str_replace(" ", "", $f);
        $f = str_replace("0.", "", $f);
        $f = str_replace("\n", "", $f);
        $f = str_replace("\r", "", $f);
        $baseFile = $f;
    } // don't need for service input

    $data = explode("\n", $inputdata);
    if (count($data))
        foreach ($data as $d) {
            $d = str_replace("\n", "", $d);
            $d = str_replace("\r", "", $d);
            if ($d <> "") {
                if (isset($w)) unset($w);
                $w = array();
                if ($d <> "") $w = explode("|", $d);
                if (count($w) > 1) {
                    $rowcnt++;
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
  and entity_type="V"

SQL;

                                $rc = $upd->updRecord($rowData, "ENTITY", $where);
                                $x = json_decode($rc, true);
                                $output[] = "{$w[0]}|{$x["message"]}\n";
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
  and entity_type="C"

SQL;

                                $rc = $upd->updRecord($rowData, "ENTITY", $where);
                                $x = json_decode($rc, true);
                                $output[] = "{$w[0]}|{$x["message"]}\n";
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
                                    header('X-PHP-Response-Code: 400', true, 400);
                                    $rc = logError($w[0], $baseFile, $rowcnt, "", $msg, $d, 0);
//$output[]= $msg;
                                    exit;

                                } // comp < 1
                                $vendor = trim($rowData["pl_vend_code"]);
                                if (!isset($Vendors[$vendor]) and $vendor <> "") { // vendor not found
                                    $output[] = "Row {$rowcnt} Invalid Vendor {$vendor}\n";
                                    $inValidVend++;
                                } // vendor not found
                                if (isset($Vendors[$vendor]) or $vendor == "") { // vendor found
                                    //Update, Insert or Delete the record
                                    $where = <<<SQL
where pl_code = "{$pl}"
  and pl_company = "{$comp}"

SQL;
                                    $rc = $upd->updRecord($rowData, "PRODLINE", $where);
                                    $x = json_decode($rc, true);
                                    $output[] = "{$w[0]}|{$x["message"]}\n";
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
                                $x = json_decode($rc, true);
                                $output[] = "{$w[0]}|{$x["message"]}\n";
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
                                $x = json_decode($rc, true);
                                $output[] = "{$w[0]}|{$x["message"]}\n";
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
                                $x = json_decode($rc, true);
                                $output[] = "{$w[0]}|{$x["message"]}\n";
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
                                $x = json_decode($rc, true);
                                $output[] = "{$w[0]}|{$x["message"]}\n";
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
                                $x = json_decode($rc, true);
                                $output[] = "{$w[0]}|{$x["message"]}\n";
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
                                $x = json_decode($rc, true);
                                $output[] = "{$w[0]}|{$x["message"]}\n";
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
                                $x = json_decode($rc, true);
                                $output[] = "{$w[0]}|{$x["message"]}\n";
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
                                    $x = json_decode($rc, true);
                                    $output[] = "{$w[0]}|{$x["message"]}\n";
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
                                    header('X-PHP-Response-Code: 400', true, 400);
                                    $rc = logError($w[0], $baseFile, $rowcnt, " ", $msg, $d, 0);
                                    exit;
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
                                    header('X-PHP-Response-Code: 400', true, 400);
                                    $rc = logError($w[0], $baseFile, $rowcnt, " ", $msg, $d, 0);
//$output[]= $msg;
                                    exit;
                                } // log pl error and skip to next record
                                //Update, Insert or Delete the record

                                $sl = $rowData["part_subline"];
                                $rc = val_sl($db, $pl, $sl);
                                if ($rc < 1) {
                                    $msg = "03|Error Invalid Subline: P/L: {$pl} SubLine: {$sl} Part Number: {$partNumber}\n";
                                    header('X-PHP-Response-Code: 400', true, 400);
                                    $rc = logError($w[0], $baseFile, $rowcnt, " ", $msg, $d, 0);
//$output[]= $msg;
                                    exit;
                                } // log subline error
                                foreach ($partValFields as $key => $v) {
                                    if (isset($rowData[$key]) and trim($rowData[$key]) <> "") {
                                        $j1 = explode("|", $v);
                                        $rcv = validateIt($db, $j1[0], $j1[1], $rowData[$key], 1, 1);
                                        if (!$rcv) {
                                            $msg = "04|Error validation: {$j1[0]} value: {$rowData[$key]} is not a valid code P/L: {$pl} Part Number: {$partNumber}\n";
                                            header('X-PHP-Response-Code: 400', true, 400);
                                            $rc = logError($w[0], $baseFile, $rowcnt, " ", $msg, $d, 0);
//$output[]= $msg;
                                            exit;
                                        }
                                    } // there is a value in the field
                                } // end foreach partValFields
                                $where = <<<SQL
where p_l = "{$pl}"
  and part_number = "{$partNumber}"

SQL;
                                $rc = $upd->updRecord($rowData, "PARTS", $where);
                                $x = json_decode($rc, true);
                                $output[] = "{$w[0]}|{$x["message"]}\n";
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
                                $poComp = $comp;
                                $vendor = $rowData["vendor"];
                                //$HPO=substr($HPO,0,20);
                                $validPO = chkHostPo($db, $comp, $HPO);
                                if ($validPO < 1) $rowData["wms_po_num"] = get_contrl($db, 0, "POHEADER");
                                else $rowData["wms_po_num"] = $validPO;
                                $PO = $rowData["wms_po_num"];
                                $rowData["po_status"] = 0;
                                $rowData["num_messages"] = 0;
                                if (!isset($rowData["ship_via"])) $rowData["ship_via"] = "";
                                if (!isset($rowData["comment"])) $rowData["comment"] = "";
                                if (trim($rowData["comment"]) <> "") $rowData["num_messages"] = 1;
                                $rowData["created_by"] = 0;
                                //validate comp, vendor
                                $validComp = validateIt($db, "COMPANY", "company_number", $comp, 0, 0);
                                $validVend = validateIt($db, "ENTITY", "host_id", $vendor, 1, 0);
                                if ($validComp == false or $validVend == false) {
                                    if (isset($msg)) unset($msg);
                                    $msg = array();
                                    if (!$validComp) array_push($msg, "01|Invalid Company: {$comp}");
                                    if (!$validVend) array_push($msg, "05|Invalid Vendor: {$vendor}");
                                    header('X-PHP-Response-Code: 400', true, 400);
                                    $rc = logError($w[0], $baseFile, $rowcnt, $HPO, $msg, $d, 0);
//$output[]= $msg;
                                    exit;
                                }
                                if (isset($rowData["bo_flag"])) {
                                    if ($rowData["bo_flag"] == "Y") $rowData["bo_flag"] = 1;
                                    if ($rowData["bo_flag"] == "N" or $rowData["bo_flag"] == "") $rowData["bo_flag"] = 0;
                                }
                                //validate/format dates to insert into MYSQL
                                $rowData["po_date"] = usa_to_eur($rowData["po_date"]);
                                if (!isset($rowData["est_deliv_date"])) $rowData["est_deliv_date"] = $rowData["po_date"];
                                $rowData["est_deliv_date"] = usa_to_eur($rowData["est_deliv_date"]);
                                $rowData["sched_date"] = usa_to_eur($rowData["sched_date"]);
                                //Update, Insert or Delete the record
                                $where = <<<SQL
where company = {$comp}
  and wms_po_num = {$PO}

SQL;
                                $rc = $upd->updRecord($rowData, "POHEADER", $where);
                                $x = json_decode($rc, true);
                                $output[] = "{$w[0]}|{$x["message"]}\n";
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

                                $PO = $rowData["poi_po_num"];
                                $poLine = $rowData["poi_line_num"];
                                $pl = trim($rowData["p_l"]);
                                $partNum = trim($rowData["part_number"]);
                                $pnum = $pl . $partNum;
                                //$PO=substr($PO,0,20);
                                if (!isset($comp)) $comp = 1;
                                if (isset($poComp)) $comp = $poComp;

                                $validPO = chkHostPo($db, $comp, $PO);
//make sure PO is on file
                                if ($validPO < 1) {
                                    $msg = "06|(POD) PO Number: {$PO} is Not on File\n";
                                    header('X-PHP-Response-Code: 400', true, 400);
                                    $rc = logError($w[0], $baseFile, $rowcnt, $PO, $msg, $d, 0);
//$output[]= $msg;
                                    exit;
                                } // end validPO
                                //replace host PO with WMS PO
                                $rowData["poi_po_num"] = $validPO;
                                $poline = chkPoLine($db, $validPO, $poLine);
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
                                    header('X-PHP-Response-Code: 400', true, 400);
                                    $rc = logError($w[0], $baseFile, $rowcnt, $PO, $msg, $d, 0);
//$output[]= $msg;
                                    exit;
                                } // uh ooh have more than 1 part
                                if ($part["status"] <> -35) { // part is on file
                                    $rowData["shadow"] = $part[$j]["shadow_number"];
                                    // need to get UOM info to fill in wght and volume
                                } // part is on file
                                else { // part is NOF
                                    $msg = "08|Invalid part P/L: {$pl} part number: {$partNum}";
                                    header('X-PHP-Response-Code: 400', true, 400);
                                    $rc = logError($w[0], $baseFile, $rowcnt, $PO, $msg, $d, 0);
//$output[]= $msg;
                                    $output[] = $rc;
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
                                $x = json_decode($rc, true);
                                $output[] = "{$w[0]}|{$x["message"]}\n";
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
                                $hdrComment = "";
                                if (isset($rowData["messg"])) {
                                    // save comment to add to ORDMESSG later
                                    $hdrComment = $rowData["messg"];
                                    unset($rowData["messg"]);
                                }
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
                                $tabcust = array(0 => "CUSTOMERS", 1 => "customer");
                                if ($rowData["order_type"] == "D") $tabcust = array(0 => "VENDORS", 1 => "vendor");
                                $validCust = validateIt($db, $tabcust[0], $tabcust[1], $customer, 1, 0);
                                $newCust = false;
                                if ($validCust == false) {
                                    // add new blank customer record and reload validCust
                                    $newCust = true; // flag for DRP to update name and addr
                                    $newCustNum = $customer;
                                    $newCustType = "C";
                                    if ($rowData["order_type"] == "D") $newCustType = "V";
                                    $ttmp = array("host_id" => $newCustNum,
                                        "entity_type" => $newCustType
                                    );
                                    EntityAU($upd, $ttmp);
                                    unset($ttmp);
                                    $validCust = validateIt($db, $tabcust[0], $tabcust[1], $newCustNum, 1, 0);
                                } // end add customer and set newCust flag
                                if ($validComp == false or $validCust == false) {
                                    $msg = array();
                                    if (!$validComp) array_push($msg, "01|Invalid Company: {$comp}");
                                    if (!$validCust) array_push($msg, "09|Invalid Customer: {$customer}");
                                    header('X-PHP-Response-Code: 400', true, 400);
                                    $rc = logError($w[0], $baseFile, $rowcnt, $ORD, $msg, $d, 0);
//$output[]= $msg;
                                    exit;
                                }
                                //validate/format dates to insert into MYSQL
                                $rowData["enter_date"] = usa_to_eur($rowData["enter_date"]);
                                //$rowData["date_required"]=usa_to_eur($rowData["date_required"]);
                                // set required to enter date without time
                                $rowData["date_required"] = usa_to_eur($rowData["enter_date"], false);
                                $rowData["drop_ship_flag"] = ($rowData["drop_ship_flag"] == "Y") ? 1 : 0;
                                //Update, Insert or Delete the record
                                $where = <<<SQL
where order_num = {$Order}

SQL;
                                $rc = $upd->updRecord($rowData, "ORDERS", $where);
                                $x = json_decode($rc, true);
                                $output[] = "{$w[0]}|HostOrder={$ORD},WMSOrder={$Order} {$x["message"]}\n";
                                $xx = $w[0] . "S";
                                $$xx++;
                                if ($hdrComment <> "") $rc = addOrderMsg($Order, $hdrComment, 0);
                            } // order is not empty
                            break;  // end ORD

                        case "RET":
                        {
                            if (isset($rowData)) unset($rowData);
                            $rowData = array();
                            $maxFlds = count($fields["RET"]) - 1;
                            if (trim($w[1]) <> "") { // order num is not empty
                                $rowData = loadFields($fields["RET"], $w);
                                if (isset($rowData["company"])) $comp = $rowData["company"]; else $comp = 1;
                                $o399 = get_option($db, $comp, 399);
                                $o400 = get_option($db, $comp, 400);
                                if (intval($o399) > 0 and $o400 <> "") {
                                    // get tote
                                    $SQL = <<<SQL
select A.tote_id
from TOTEHDR A,TOTEDTL B
where tote_code = "{$o400}"
and B.tote_id = A.tote_id

SQL;
                                    $SQL = <<<SQL
select A.tote_id
from TOTEHDR A
where tote_code = "{$o400}"

SQL;


                                    $toteId = 0;
                                    $maxLine = 0;
                                    $rc = $db->query($SQL);
                                    $numrows = $db->num_rows();
                                    $i = 1;
                                    while ($i <= $numrows) {
                                        $db->next_record();
                                        if ($numrows) {
                                            $toteId = $db->f("tote_id");
                                        }
                                        $i++;
                                    } // while i < numrows
                                    $pl = trim($rowData["p_l"]);
                                    $partNum = trim($rowData["part_number"]);
                                    $pnum = $pl . $partNum;

                                    $part = getPart($db, $pnum);
                                    $j = 0;
//check part count
                                    if ($part["num_rows"] > 1) { // uh ooh have more than 1 part
                                        $msg = "07|{$PO} Duplicate part number, P/L: {$pl} part number: {$partNum}\n";
                                        header('X-PHP-Response-Code: 400', true, 400);
                                        $rc = logError($w[0], $baseFile, $rowcnt, $PO, $msg, $d, 0);
//$output[]= $msg;
                                        exit;
                                    } // uh ooh have more than 1 part
                                    if ($part["status"] <> -35) { // part is on file
                                        $rowData["shadow"] = $part[$j]["shadow_number"];
                                        // need to get UOM info to fill in wght and volume
                                    } // part is on file
                                    else { // part is NOF
                                        $msg = "08|Invalid part P/L: {$pl} part number: {$partNum}";
                                        header('X-PHP-Response-Code: 400', true, 400);
                                        $rc = logError($w[0], $baseFile, $rowcnt, $PO, $msg, $d, 0);
//$output[]= $msg;
                                        $output[] = $rc;
                                        mvFile($f, $errDir);
                                        exit;
                                    } // part is NOF
                                    if ($toteId > 0) {
                                        // got 1 part, lets add it to tote
                                        $shadow = $part[$j]["shadow_number"];
                                        $item = getToteLine($db, $toteId, $shadow);
                                        $qty = $rowData["qty"];
                                        $uom = $part[$j]["unit_of_measure"];
                                        $SQL = <<<SQL
     insert into TOTEDTL ( tote_id, tote_item, tote_shadow, tote_qty, tote_uom)
     values ({$toteId},{$item},{$shadow},{$qty},"{$uom}")
     ON DUPLICATE KEY UPDATE
     tote_qty = tote_qty + $qty

SQL;
                                        $rc = $db->Update($SQL);
                                        if ($rc > 0) $m = "{$rc} {$pl} {$partNum} Qty {$qty} Updated";
                                        else $m = "Error Updating Part Number: {$pl} {$partNum}";
                                        $output[] = "{$w[0]}|{$m}\n";
                                        $xx = $w[0] . "S";
                                        $$xx++;
                                    } // end toteid > 0
                                } // end o399 > 0
                            } // company is not empty
                            break;
                        } // end RET

                        case "ITM":
                            if (isset($rowData)) unset($rowData);
                            $rowData = array();
                            $maxFlds = count($fields["ITM"]) - 1;
                            if (trim($w[1]) <> "") { // order num is not empty
                                $rowData = loadFields($fields["ITM"], $w);
                                $ORD = $rowData["ord_num"];
                                $comp = 0;
                                if (isset($rowData["company"]) and $rowData["company"] > 0) $comp = $rowData["company"];

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
                                    header('X-PHP-Response-Code: 400', true, 400);
                                    $rc = logError($w[0], $baseFile, $rowcnt, $ORD, $msg, $d, 0);
//$output[]= $msg;
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
                                    header('X-PHP-Response-Code: 400', true, 400);
                                    $rc = logError($w[0], $baseFile, $rowcnt, $ORD, $msg, $d, 0);
//$output[]= $msg;
                                    exit;
                                } // uh ooh have more than 1 part
                                if (isset($part["status"]) and $part["status"] == -35) $part["numRows"] = 0;
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
                                    header('X-PHP-Response-Code: 400', true, 400);
                                    $rc = logError($w[0], $baseFile, $rowcnt, $ORD, $msg, $d, 0);
//$output[]= $msg;
                                    exit;
                                } // part is NOF

                                //Update, Insert or Delete the record
                                $where = <<<SQL
where ord_num = "{$Order}"
  and line_num = {$lineNum}

SQL;
                                $rc = $upd->updRecord($rowData, "ITEMS", $where);
                                $rc1 = updOrderLines($db, $Order, $lineNum);
                                //$output[]= "{\"ORD\":\"{$w[1]}\",Line: {$lineNum} {$rc} }\n";
                                $x = json_decode($rc, true);
                                $output[] = "{$w[0]}|HostOrder={$ORD},Item={$lineNum} {$x["message"]}\n";
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
                                if (isset($rowData["company"]) and $rowData["company"] > 0) $comp = $rowData["company"];
                                $validOrd = chkHostOrder($db, $comp, $ORD);
                                $Order = $validOrd["order_num"];
                                if ($Order > 0) { // valid order
                                    //replace host Order# with WMS Order
                                    $rowData["order_num"] = $Order;
                                    $comp = $validOrd["company"];
                                    if (isset($newCust) and $newCust == true and trim($newCustNum) <> "") { // update customer record with DRP record name and address
                                        // make sure the fields are initialized
                                        if (!isset($rowData["name"])) $rowData["name"] = "";
                                        if (!isset($rowData["addr1"])) $rowData["addr1"] = "";
                                        if (!isset($rowData["addr2"])) $rowData["addr2"] = "";
                                        if (!isset($rowData["city"])) $rowData["city"] = "";
                                        if (!isset($rowData["state"])) $rowData["state"] = "";
                                        if (!isset($rowData["zip"])) $rowData["zip"] = "";
                                        if (!isset($rowData["ctry"])) $rowData["ctry"] = "";
                                        if (!isset($rowData["phone"])) $rowData["phone"] = "";
                                        if (!isset($rowData["email"])) $rowData["email"] = "";
                                        if (!isset($newCustType)) $newCustType = "C";
                                        $ttmp = array("host_id" => $newCustNum,
                                            "entity_type" => $newCustType,
                                            "name" => $rowData["name"],
                                            "addr1" => $rowData["addr1"],
                                            "addr2" => $rowData["addr2"],
                                            "city" => $rowData["city"],
                                            "state" => $rowData["state"],
                                            "zip" => $rowData["zip"],
                                            "ctry" => $rowData["ctry"],
                                            "phone" => $rowData["phone"],
                                            "email" => $rowData["email"]
                                        );
                                        EntityAU($upd, $ttmp);
                                        unset($ttmp);
                                    } // end update customer record with DRP record name and address

                                    //Update, Insert or Delete the record
                                    $where = <<<SQL
 where order_num = {$Order}

SQL;
                                    $rc = $upd->updRecord($rowData, "DROPSHIP", $where);
                                    $x = json_decode($rc, true);
                                    $output[] = "{$w[0]}|HostOrder={$ORD},{$x["message"]}\n";
                                    $xx = $w[0] . "S";
                                    $$xx++;
                                } // end valid order
                                else { // order is not valid
                                    $msg = "10|DropShip Record: Order Number: {$ORD} is Not on File\n";
                                    header('X-PHP-Response-Code: 400', true, 400);
                                    $rc = logError($w[0], $baseFile, $rowcnt, $ORD, $msg, $d, 0);
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
                                if (isset($rowData["company"]) and $rowData["company"] > 0) $comp = $rowData["company"];
                                $validOrd = chkHostOrder($db, $comp, $ORD);
                                $Order = $validOrd["order_num"];
                                if ($Order > 0) { // valid order
                                    $prio = getOrderPrio($db, $Order);
                                    $relCode = "WAI";
                                    if ($prio < 4) $relCode = "REL";
                                    if ($rowData["releaseCode"] == "REL") $relCode = "REL";
                                    $rc = add2Que($db, $Order, $relCode);
                                    //$output[]= "ORD: {$ORD}, ReleaseCode: {$relCode}, WMSOrder,{$Order}}\n";
                                    $output[] = "{$w[0]}|HostOrder={$ORD},ReleaseCode={$relCode}\n";
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
                                $x = json_decode($rc, true);
                                $output[] = "{$w[0]}|{$x["message"]}\n";
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
    file_put_contents("{$doneDir}/{$w1}", $inputdata);
} // end count files
if ($rowcnt > 0) $output["RecordsRead"] = "{$rowcnt}\n";;
if ($VENS > 0) $output["Vendors"] = "Processed={$VENS}\n";
if ($inValidVend > 0) $output["InvalidVend"] = "ors={$inValidVend}\n";
if ($CSTS > 0) $output["Customers"] = "Processed={$CSTS}\n";
if ($PLMS > 0) $output["ProdLines"] = "Processed={$PLMS}\n";
if ($PRTS > 0) $output["Parts"] = "Processed={$PRTS}\n";
if ($UMCS > 0) $output["UOMCodes"] = "Processed={$UMCS}\n";
if ($UOMS > 0) $output["UOMs"] = "Processed={$UOMS}\n";
if ($SUBS > 0) $output["SubLines"] = "Processed={$SUBS}\n";
if ($CATS > 0) $output["Catg"] = "Processed={$CATS}\n";
if ($PGRS > 0) $output["Groups"] = "Processed={$PGRS}\n";
if ($PCLS > 0) $output["Classes"] = "Processed={$PCLS}\n";
if ($VIAS > 0) $output["VIA"] = "Processed={$VIAS}\n";
if ($ZONS > 0) $output["Zones"] = "Zones Processed={$ZONS}\n";
if ($BTPS > 0) $output["BinTypes"] = "Processed={$BTPS}\n";

if (count($output) > 0) {
    $f = "WMSI_" . date("mdY") . "_" . microtime() . ".out";
    if ($dataTypeIn == "json") { // json input
        $output = str_replace("\n", "", $output);
        $x = json_encode($output);
        echo $x;
        file_put_Contents("{$ServiceOut}/{$f}", $x);
    } // json input
    else { // pipe input
        $x = "";
        foreach ($output as $key => $out) {
            if (is_numeric($key)) {
                echo $out;
            } else if ($debug) echo "{$key}|{$out}";
        }
        file_put_Contents("{$ServiceOut}/{$f}", $x);
    } // pipe input
} else {
    //http_response_code(404);
    $newcode = 400;
    header('X-PHP-Response-Code: ' . $newcode, true, $newcode);
}

function EntityAU($upd, $rowData)
{
    if (!isset($rowData["host_id"]) and !isset($rowData["entity_type"])) return -1;
    //Update, Insert or Delete the record
    $where = <<<SQL
where host_id = "{$rowData["host_id"]}"
and entity_type = "{$rowData["entity_type"]}"

SQL;

    $rc = $upd->updRecord($rowData, "ENTITY", $where);
    return $rc;

} // end addEntity
function xcheck_payload($in)
{
    global $dataTypeIn;
    $result = json_decode($in, true);
    if (json_last_error() === JSON_ERROR_NONE) { // format json as pipe delimited
        $dataTypeIn = "json";
        $ret = "";
        $sep = "|";
        // hate to do this but seems the only way to get around it not adding to file
        // if a new line is embeded in the messge
        if (isset($result["ORDER"]["ORD"]["messg"]))
            $result["ORDER"]["ORD"]["messg"] = str_replace("\n", "~~", $result["ORDER"]["ORD"]["messg"]);
        foreach ($result as $key => $data) {
            $j = array_depth($data);
            if ($j > 1) {
                foreach ($data as $key1 => $data1) {
                    $j1 = array_depth($data1);
                    if ($j1 > 1) {
                        foreach ($data1 as $key2 => $data2) {
                            $j2 = array_depth($data2);
                            if ($j2 < 2) $ret .= addTo($key1, $data2, $sep);
                        } // foreach data1
                    } // end is_array data1
                    else $ret .= addTo($key1, $data1, $sep);
                } // end foreach data
            } // is array data
            else $ret .= addTo($key, $data, $sep);
        } // end foreach result
        return $ret;
    }  // format json as pipe delimited
    else return $in;
} // check payload
function addTo($key, $data, $sep)
{
    return "{$key}{$sep}" . implode("{$sep}", $data) . "\n";
}

function array_depth($array)
{
    // some functions that usually return an array occasionally return false
    if (!is_array($array)) {
        return 0;
    }

    $max_indentation = 1;
    // PHP_EOL in case we're running on Windows
    $lines = explode(PHP_EOL, print_r($array, true));

    foreach ($lines as $line) {
        $indentation = (strlen($line) - strlen(ltrim($line))) / 4;
        $max_indentation = max($max_indentation, $indentation);
    }
    return ceil(($max_indentation - 1) / 2) + 1;
}

function addOrderMsg($order, $messg, $line = 0)
{
    global $db;
    if ($order < 1) return;
    if (trim($messg) == "") return;
    // if line = 0, delete any header messages then re-add them
    if ($line == 0) {
        $SQL = <<<SQL
delete from ORDMESSG
where order_num = {$order}
and line_num = {$line}

SQL;
        $rc = $db->Update($SQL);
    } // end line = 0

    $SQL = <<<SQL
select IFNULL(0,max(message_num)) as message_num
from ORDMESSG
where order_num = {$order}
and line_num = {$line}
SQL;
    $maxline = 0;
    $rc = $db->query($SQL);
    $numrows = $db->num_rows();
    $i = 1;
    while ($i <= $numrows) {
        $db->next_record();
        if ($numrows) {
            $maxline = $db->f("message_num");
        }
        $i++;
    } // while i < numrows
    $j = strlen($messg);
    $messg = str_replace("'", "", $messg);
    $messg = str_replace('"', "", $messg);
    //$messg=str_replace("\n",";",$messg);
    // replace ~~ with new line if changed in chkPayload
    $messg = str_replace("~~", "\n", $messg);
    $m = explode("\n", $messg);
    if (count($m) > 0) {
        foreach ($m as $msg) {
            $maxline++;
            $msg = onlyascii($msg);
            $SQL = <<<SQL
insert into ORDMESSG
( order_num, line_num, message_num, message )
values ({$order},{$line},{$maxline},"{$msg}")

SQL;
            $rc = $db->Update($SQL);
        }
    }
    return $maxline;

} // end addOrderMsg
function getToteLine($db, $toteId, $shadow = 0)
{
    if ($shadow > 0) {
        $SQL = <<<SQL
 select IFNULL(tote_item,0) as tote_item
from TOTEDTL
where tote_id = {$toteId}
and tote_shadow = {$shadow}
SQL;
        $ret = getData($db, $SQL, "tote_item");
        if ($ret > 0) return $ret;
    } // end shadow > 0

    $SQL = <<<SQL
select IFNULL(max(tote_item),0) + 1 as maxLine
from TOTEDTL
where tote_id = {$toteId}

SQL;
    $ret = 1;
    $ret = getData($db, $SQL, "maxLine");
    return $ret;

} // end getToteLine

function getData($db, $SQL, $fld)
{
    $ret = -1;
    $rc = $db->query($SQL);
    $numrows = $db->num_rows();
    $i = 1;
    while ($i <= $numrows) {
        $db->next_record();
        if ($numrows) {
            $ret = $db->f($fld);
        }
        $i++;
    } // while i < numrows
    return $ret;

} // end getData
?>
