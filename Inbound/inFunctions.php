<?php
//inFunctions.php -- Finctions for incoming file processing
//05/24/23 dse initial

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

function loadFields($fields, $data)
{
    $Rec = array();
    $maxFlds = count($fields) - 1;
    for ($i = 0; $i <= $maxFlds; $i++) {
        if (isset($data[$i + 1])) $Rec[$fields[$i]] = $data[$i + 1];
    } // end for i
// if (!isset($Rec[$fields[$maxFlds]])) $Rec[$fields[$maxFlds]]=2;
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
    $pl = escQuotes($pl);
    $sl = escQuotes($sl);

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

    $cnt=0;
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

function getPoComp($db, $wmsPO)
{
    // needed to read company options properly
    $SQL = <<<SQL
 select company
 from POHEADER
 where wms_po_num = {$wmsPO}
SQL;
    $ret = 1; // default to comp 1 not 0
    $rc = $db->query($SQL);
    $numrows = $db->num_rows();
    $i = 1;
    while ($i <= $numrows) {
        $db->next_record();
        if ($numrows) {
            $ret = $db->f("company");
        }
        $i++;
    } // while i < numrows
    return ($ret);

} // end getPoComp
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
    if (file_exists("{$doneDir}/{$w1}")) rename($f, "{$doneDir}/{$w1}");
} // end mvFile

function toCSV($array)
{
    $csv = '';

    $header = false;
    foreach ($array as $line) {
        if (empty($header)) {
            $header = array_keys($line);
            $csv .= implode(',', $header);
            $header = array_flip($header);
        }

        $line_array = array();

        foreach ($line as $value) {
            array_push($line_array, $value);
        }

        $csv .= "\n" . implode(',', $line_array);
    }

//output as CSV string
    return $csv;
} // end toCSV

function check_payload($in)
{
    $return = "";

    $result = json_decode($in, true);
    if (json_last_error() === JSON_ERROR_NONE) { // format json as pipe delimited
        if (isset($result["ORDER"]["POH"])) {
            $comp = $result["ORDER"]["POH"]["company"];
            $poType = $result["ORDER"]["POH"]["po_type"];
            $po = $result["ORDER"]["POH"]["host_po_num"];
            if ($poType == "R") { // its a return
                global $db;
                $r = $result["ORDER"]["POD"];
                $opt399 = get_option($db, $comp, 399);
                $opt400 = get_option($db, $comp, 400);
                if (intval($opt399) > 0 and trim($opt400) <> "") { // send part direct to tote
                    if (isset($r) and count($r) > 0) {
                        foreach ($r as $d) {
                            $return .= "RET|{$comp}|{$po}|{$d["p_l"]}|{$d["part_number"]}|{$d["uom"]}|{$d["qty_ord"]}|{$d["line_type"]}\n";
                        } // end foreach r
                    }
                    return $return;
                }  // send part direct to tote
            }  // its a return
        } // end order->POH isset
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

?>
