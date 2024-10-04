<?php
// 01/19/18 add Intchg, supercede, notes, alternates, qtybrk
// 02/05/21 dse add support for type T supercedes
// Add Lookup by part number
// 01/12/22 dse change to support WMS
// 03/03/22 dse add WHSELOC and chkPart
// 05/09/22 dse add TOTES and ITEMPULL Selects
// 08/23/22 dse use tote_code as tote_id

if (get_cfg_var('wmsdir')) $wmsDir = get_cfg_var('wmsdir');
else {
    echo "<h1>WMS System is not Configured on this System</h1>";
    exit;
}
require_once("{$wmsDir}/include/get_table.php");

class PARTS
{
    var $status;
    var $shadow_number;
    var $p_l;
    var $part_number;
    var $Info;
    var $Select;
    var $Data;
    var $WHSEQTY;
    var $WHSELOC;
    var $ProdLine;
    var $Notes;
    var $Alternates;

    function AddUpd($num)
    {
        global $db;
//Need to Write full Update and Insert
    }

    function UpdatePART()
    {
//Limited Update for A/R Inquiry
        global $db;
        // return($rc);
    } // end Select

    function ITEMPULLSelect($shadow, $company = -1)
    {
        global $db;
        $Data = array();
        $where = " and ITEMPULL.company = {$company}\n";
        if ($company < 1) $where = "";
        $SQL = <<<SQL
select
order_num,
host_order_num,
zone,
whse_loc  ,
qtytopick ,
qty_picked ,
qty_verified
from ITEMPULL,ORDERS
 where shadow = {$shadow}
{$where} and order_num = ord_num

SQL;
        $rc = $db->query($SQL);
        $numrows = $db->num_rows();
        $i = 1;
        while ($i <= $numrows) {
            $db->next_record();
            if ($numrows and $db->Record) {
                $num = $db->f("order_num");
                foreach ($db->Record as $key => $data) {
                    if (!is_numeric($key)) {
                        $Data[$num]["$key"] = $data;
                    }
                }
            }
            $i++;
        } // while i < numrows
        return $Data;

    } // end AddUpd

    function TOTESelect($shadow, $company = -1)
    {
        global $db;
        $Data = array();
        $where = " and tote_company = {$company}\n";
        if ($company < 1) $where = "";
        $SQL = <<<SQL
select
tote_code as tote_id,
tote_type,
tote_location,
tote_item,
tote_shadow,
tote_qty,
tote_uom,
tote_ref

from TOTEDTL A,TOTEHDR B
where tote_shadow = {$shadow}
and B.tote_id = A.tote_id
{$where}

SQL;
        $rc = $db->query($SQL);
        $numrows = $db->num_rows();
        $i = 1;
        while ($i <= $numrows) {
            $db->next_record();
            if ($numrows and $db->Record) {
                $num = $db->f("tote_id");
                foreach ($db->Record as $key => $data) {
                    if (!is_numeric($key)) {
                        $Data[$num]["$key"] = $data;
                    }
                }
            }
            $i++;
        } // while i < numrows
// get host order number for orders or host po num for POs
        if (count($Data) > 0) {
            $f["O"][0] = "ORDERS";
            $f["O"][1] = "host_order_num";
            $f["O"][2] = "order_num";
            $f["P"][0] = "POHEADER";
            $f["P"][1] = "host_po_num";
            $f["P"][2] = "wms_po_num";
            foreach ($Data as $key => $d) {
                $t = ($d["tote_type"] == "PIC") ? "O" : "P";
                $SQL = <<<SQL
select {$f[$t][1]} from {$f[$t][0]} where {$f[$t][2]} = "{$d["tote_ref"]}"

SQL;

                $fp = fopen("/tmp/cl_PARTS2.txt", "a");
                ob_start();
                var_dump($Data);
                $content = ob_get_contents();
                ob_end_clean();

                fwrite($fp, "$content\n");
                fwrite($fp, "$SQL\n");
                fclose($fp);
                $rc = $db->query($SQL);
                $numrows = $db->num_rows();
                $db->next_record();
                $Data[$key]["HostId"] = $db->f($f[$t][1]);
            } // end foreach Data
        } // end count Data
        return $Data;

    } // end of Update

    function chkPart($pnum, $comp)
    {
        global $db;
        $ret = array();
        $ret["upc"] = $pnum;
        $ret["comp"] = $comp;
        $pnum = trim($pnum);
        $a = $this->lookup($pnum);
        $ret["status"] = $this->status; // >0 = good lookup, -35= notfound
        if ($this->status == 1) { // got 1 part
            $this->Load($a[1]["shadow_number"], $comp);
            $ret["numRows"] = 1;
            $ret["Result"] = $a[1];
            $ret["Part"] = $this->Data;
            $ret["ProdLine"] = $this->ProdLine;
            $ret["WhseQty"] = $this->WHSEQTY;
            $ret["WhseLoc"] = $this->WHSELOC;
        } // end got 1 part
        $ret["numRows"] = count($this->status);
        if ($this->status > 1) { // status > 1
            $ret["numRows"] = $this->status;
            $ret["status"] = $this->status;
            $ret["choose"] = $a;
            foreach ($a as $key => $p) {
                $tmp = $this->WHSEQTYSelect($p[$key]["shadow_number"], $comp);
                if (isset($tmp[$comp])) {
                    $tmp = $tmp[$comp];
                    $ret["choose"][$key]["qty_avail"] = $tmp["qty_avail"];
                    $ret["choose"][$key]["qty_alloc"] = $tmp["qty_alloc"];
                    $ret["choose"][$key]["qty_defect"] = $tmp["qty_defect"];
                    $ret["choose"][$key]["qty_core"] = $tmp["qty_core"];
                    $ret["choose"][$key]["qty_on_order"] = $tmp["qty_on_order"];
                    $ret["choose"][$key]["qty_on_vendbo"] = $tmp["qty_on_vendbo"];
                    $ret["choose"][$key]["primary_bin"] = $tmp["primary_bin"];
                } // end isset tmp[comp]
                else { // not in WHSEQTY
                    $ret["choose"][$key]["qty_avail"] = 0;
                    $ret["choose"][$key]["qty_alloc"] = 0;
                    $ret["choose"][$key]["qty_defect"] = 0;
                    $ret["choose"][$key]["qty_core"] = 0;
                    $ret["choose"][$key]["qty_on_order"] = 0;
                    $ret["choose"][$key]["qty_on_vendbo"] = 0;
                    $ret["choose"][$key]["primary_bin"] = "NOFS";
                } // not in WHSEQTY
            } // end foreach a
        } // status > 1
        else { // status < 1
            $ret["status"] = $this->status;
        } // status < 1
        return ($ret);
    } // end WHSEQTYSelect

    function lookup($pnum_in)
    {
        global $db;
        $SQL = <<<SQL
SELECT
 shadow_number,
 p_l,
 part_number,
 part_desc,
 unit_of_measure,
 alt_part_number,
 alt_type_code,
 alt_uom
 from ALTERNAT,PARTS
 WHERE alt_part_number like "{$pnum_in}"
 AND  shadow_number = alt_shadow_num

SQL;
        $Data = array();
        $rc = $db->query($SQL);
        $numrows = $db->num_rows();
        $i = 1;
        while ($i <= $numrows) {
            $db->next_record();
            if ($numrows and $db->Record) {
                foreach ($db->Record as $key => $data) {
                    if (!is_numeric($key)) {
                        $Data[$i]["$key"] = $data;
                    }
                }
            }
            $i++;
        } // while i < numrows
        if ($numrows > 0) $this->status = $numrows;
        else $this->status = -35;
        return ($Data);
    }

    function Load($shadow_number, $company = -1)
    {
        global $db;
        $this->shadow_number = $shadow_number;
        $where = "where shadow_number = $shadow_number";
        $this->Info = get_table($db, "PARTS", $where);
        $this->Data = $this->Select($shadow_number);
        $this->ProdLine = $this->PLSelect($this->Data, $company);
        $tmp = $this->WHSEQTYSelect($shadow_number, $company);
        $this->WHSEQTY = $tmp;
        $tmp = $this->WHSELOCSelect($shadow_number, $company);
        $this->WHSELOC = $tmp;

        //$tmp=$this->Load_Alternates($shadow_number);
        //$this->Alternates=$tmp;
        //if ($this->Data["part_num_notes"] > 0)
        //{
        //$tmp=$this->Load_NOTES($shadow_number);
        //$this->Notes=$tmp;
        //}
    } // end TOTESelect

    function Select($shadow_number)
    {
        global $db;
        $Data = array();
        $this->shadow_number = $shadow_number;
        $SQL = $this->Info["Select"];
        $rc = $db->query($SQL);
        $numrows = $db->num_rows();
        $i = 1;
        while ($i <= $numrows) {
            $db->next_record();
            if ($numrows and $db->Record) {
                foreach ($db->Record as $key => $data) {
                    if (!is_numeric($key)) {
                        $Data["$key"] = $data;
                        if ($key == "p_l") $this->p_l = $data;
                        if ($key == "part_number") $this->part_number = $data;
                    }
                }
            }
            $i++;
        } // while i < numrows
        if ($numrows > 0) $this->status = $numrows;
        else $this->status = -35;
        return ($Data);
    } // end WHSELOCSelect

    function PLSelect($part, $comp)
    {
        global $db;
        $Data = array();
        if ($comp < 1) $comp = 1;
        $where_add = <<<SQL
WHERE pl_code = "{$part["p_l"]}"
and pl_company = {$comp}

SQL;
        $tmp = get_table($db, "PRODLINE", $where_add);
        $SQL = $tmp["Select"];

        $rc = $db->query($SQL);
        $numrows = $db->num_rows();
        $i = 1;
        while ($i <= $numrows) {
            $db->next_record();
            if ($numrows and $db->Record) {
                foreach ($db->Record as $key => $data) {
                    if (!is_numeric($key)) {
                        $Data["$key"] = $data;
                    }
                }
            }
            $i++;
        } // while i < numrows
        return ($Data);

    } // end chkPart

    function WHSEQTYSelect($shadow_number, $company = -1)
    {
        global $db;
        $Data = array();
        $tmp = array();
        $where_add = "WHERE ms_shadow = " . $shadow_number;
        if ($company <> -1) $where_add .= " AND ms_company = {$company}";
        else $where_add .= " AND ms_company > 0";
        $tmp = get_table($db, "WHSEQTY", $where_add);
        $SQL = $tmp["Select"];
        $SQL .= " ORDER BY ms_company";

        $rc = $db->query($SQL);
        $numrows = $db->num_rows();
        $i = 1;
        while ($i <= $numrows) {
            $db->next_record();
            if ($numrows and $db->Record) {
                $num = $db->f("ms_company");
                foreach ($db->Record as $key => $data) {
                    if (!is_numeric($key)) {
                        $Data[$num]["$key"] = $data;
                    }
                }
            }
            $i++;
        } // while i < numrows
//$Data["Info"]=$tmp;
        return ($Data);
    } // end PLSelect

    function WHSELOCSelect($shadow_number, $company = -1)
    {
        global $db;
        $Data = array();
        $tmp = array();
        $where_add = "WHERE whs_shadow = " . $shadow_number;
        if ($company <> -1) $where_add .= " AND whs_company = {$company}";
        else $where_add .= " AND whs_company > 0";
        $tmp = get_table($db, "WHSELOC", $where_add);
        $SQL = $tmp["Select"];
        $SQL .= " ORDER BY whs_company, whs_code desc, whs_location";
        $rc = $db->query($SQL);
        $numrows = $db->num_rows();
        $i = 1;
        while ($i <= $numrows) {
            $db->next_record();
            if ($numrows and $db->Record) {
                foreach ($db->Record as $key => $data) {
                    if (!is_numeric($key)) {
                        $Data[$i]["$key"] = $data;
                    }
                }
            }
            $i++;
        } // while i < numrows
        return ($Data);
    } // end Load_Alternates

    function Load_Alternates($shadow_number)
    {
        global $db;
        $Data = array();
        $tmp = array();
        $where_add = <<<SQL
WHERE alt_shadow_num = {$shadow_number} and alt_type_code < 9997
SQL;
        $tmp = get_table($db, "ALTERNAT", $where_add);
        $SQL = $tmp["Select"];
        $SQL .= " ORDER BY alt_shadow_num, alt_type_code, alt_part_number,alt_uom";
        $rc = $db->query($SQL);
        $numrows = $db->num_rows();
        $i = 1;
        while ($i <= $numrows) {
            $db->next_record();
            if ($numrows and $db->Record) {
                foreach ($db->Record as $key => $data) {
                    if (!is_numeric($key)) {
                        $Data[$i]["$key"] = $data;
                    }
                }
            }
            $i++;
        } // while i < numrows
//$Data["Info"]=$tmp;
        return ($Data);
    } // end Load_MSUPER

    function Load_MSUPER($shadow_number, $type = "S")
    {
        global $db;
        $Data = array();
        $tmp = array();
        $where_add = <<<SQL
WHERE si_from_shadow = {$shadow_number}
and si_code = "{$type}"
SQL;
        if ($type == "I") { //include type T too
            $where_add = <<<SQL
WHERE si_from_shadow = {$shadow_number}
and si_code in ("I","T")
SQL;
        } //include type T too

        $tmp = get_table($db, "MSUPER", $where_add);
        $SQL = $tmp["Select"];
        $rc = $db->query($SQL);
        $numrows = $db->num_rows();
        $i = 1;
        while ($i <= $numrows) {
            $db->next_record();
            if ($numrows and $db->Record) {
                foreach ($db->Record as $key => $data) {
                    if (!is_numeric($key)) {
                        $Data[$i]["$key"] = $data;
                    }
                }
            }
            $i++;
        } // while i < numrows
//$Data["Info"]=$tmp;
        return ($Data);
    } // end Load_NOTES

    function Load_NOTES($shadow_number)
    {
        global $db;
        $Data = array();
        $tmp = array();
        $where_add = <<<SQL
WHERE pnote_shadow = {$shadow_number}
SQL;
        $tmp = get_table($db, "PARTNOTE", $where_add);
        $SQL = $tmp["Select"];
        $SQL .= "order by pnote_shadow, pnote_line";
        $rc = $db->query($SQL);
        $numrows = $db->num_rows();
        $i = 1;
        while ($i <= $numrows) {
            $db->next_record();
            if ($numrows and $db->Record) {
                foreach ($db->Record as $key => $data) {
                    if (!is_numeric($key)) {
                        $Data[$i]["$key"] = $data;
                    }
                }
            }
            $i++;
        } // while i < numrows
//$Data["Info"]=$tmp;
        return ($Data);
    } // end lookup
} // end class
?>
