<?php
// cl_orders.php -- Retreive Order Info
//
// 03/08/23 dse add loadWhseOrder to load items in whse sequence 
// 03/08/23 dse add get Bill and ship to info

require_once("../include/quoteit.php");
require_once("../include/db_main.php");
require_once("../include/wr_log.php");

class ORDERS
{
    public $ordNumber;
    public $Order;
    public $Items;
    public $ItemPull;
    public $OrdTotes;
    public $BillTo;
    public $ShipTo;
    public $whseSeq;
    public $Messg;
    private $logfile;
    private $debug;
    private $Defs;
    private $db;
    private $ORDERS;
    private $ITEMS;
    private $ITEMPULL;

    public function __construct()
    {
        $this->db = new WMS_DB;
        $t = "ORDERS";
        $this->Defs = array();
        $this->Defs[$t] = $this->setDef($this->db, $t);
        $t = "ITEMS";
        $this->Defs[$t] = $this->setDef($this->db, $t);
        $t = "ITEMPULL";
        $this->Defs[$t] = $this->setDef($this->db, $t);
        $t = "ORDTOTE";
        $this->Defs[$t] = $this->setDef($this->db, $t);
        $this->logfile = "/tmp/clorders.log";
        $this->debug = false;
        $this->Messg = "";
    } // end contruct

    private function setDef($db, $table)
    {
        $ret = array();
        // set table def and select and update fields
        $ret["Defs"] = $this->setFldDef($db, $table);
        $ret["Select"] = $this->setFlds($db, $ret["Defs"]);
        return $ret;
    } // end load

    private function setFldDef($db, $table)
    {
        //Get Update table definition
        $u = $db->MetaData($table);
        $ret = array();
        foreach ($u as $key => $v) {
            $qote = 0;
            if (preg_match('(CHAR|DATE|TIME)', strtoupper($v["Type"])) === 1) {
                $qote = 1;
            }
            $ret[$v["Field"]] = $qote;
        } // end foreach u
        return $ret;
    } // end load

    private function setFlds($db, $Flds)
    {
        //set update fields and types to find which fields need quotes
        $ret = "";
        $comma = "";
        foreach ($Flds as $f => $val) {
            if (strlen($ret) > 0) $comma = ",";
            $ret .= "{$comma}{$f}";
        }
        return $ret;
    } // end doSelect

    public function load($order)
    {
        $this->ordNumber = $order;
        foreach (array_keys($this->Defs) as $d) {
            $this->{$d} = $this->doSelect($d);
        } // end foreach array_keys
        // load cust info if found
        if (count($this->Order) > 0) $this->getShipTo();
    } // end getShipTo

    private function doSelect($table)
    {
        if (isset($this->Defs[$table])) {
            $where = "";
            $orderby = "";
            $SQL = "";
            $m = true;
            switch ($table) {
                case "ORDERS":
                    $where = "where order_num = {$this->ordNumber} ";
                    $m = false;
                    break;
                case "ITEMS":
                    $where = "where ord_num = {$this->ordNumber} ";
                    $m = true;
                    $orderby = "order by ord_num,line_num";
                    break;
                case "ITEMPULL":
                    $where = "where ord_num = {$this->ordNumber} ";
                    $m = true;
                    $orderby = "order by ord_num, whse_loc, line_num";
                    break;
                case "ORDTOTE":
                    $where = "where order_num = {$this->ordNumber} ";
                    $m = true;
                    $orderby = "order by order_num, tote_id";
                    break;
            } // end switch table
            if ($where <> "") {
                $SQL = <<<SQL
 select {$this->Defs[$table]["Select"]}
 from {$table}
 {$where}
 {$orderby}

SQL;
            } // $where is set
            if ($SQL <> "") {
                $ret = $this->gData($SQL, $m);
                if ($table == "ORDERS") $this->Order = $ret;
                if ($table == "ITEMS") $this->Items = $ret;
                if ($table == "ITEMPULL") $this->ItemPull = $ret;
                if ($table == "ORDTOTE") {
                    $this->OrdTotes = $ret;
                    if (count($ret) > 0) {
                        foreach ($ret as $key => $d) {
                            $tote = $d["tote_id"];
                            $SQL = <<<SQL
select * from TOTEDTL where tote_id = {$tote}

SQL;
                            $tmp = $this->gData($SQL, $m);
                            $this->OrdTotes[$key]["toteDtl"] = $tmp;

                        }
                    }
                }
            } // SQL<> ""
        } // table defs set
    } // end setDef

    private function gData($SQL, $multi = true)
    {
        $db = $this->db;
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
    } // end getFldDef

    public function getShipTo()
    {
        $et = "C";
        if ($this->Order["order_type"] == "D") $et = "V";
        $SQL = <<<SQL
 select 
host_id,
name,
addr1,
addr2,
city,
state,
zip,
ctry,
phone,
email
 from ENTITY 
 where  entity_type = "{$et}"
 and host_id = "{$this->Order["customer_id"]}"

SQL;
        $tmp = $this->gData($SQL, false);
        $this->BillTo = $tmp;
        if ($this->Order["drop_ship_flag"] > 0) { // it's a dropship
            $SQL = <<<SQL
  select 
name,
addr1,
addr2,
city,
state,
zip,
ctry,
phone,
email
from DROPSHIP
where order_num = {$this->ordNumber}

SQL;
            $tmp1 = $this->gData($SQL, false);
            if (count($tmp1) > 0) $this->ShipTo = $tmp1;
            else $this->ShipTo = $tmp;
        } // it's a dropship
        else { // it's not a dropship
            $this->ShipTo = $tmp;
        } // it's not a dropship
        unset($tmp);
        unset($tmp1);
    } // end setFlds

    public function loadWhseOrder($order)
    {
        $this->ordNumber = $order;
        foreach (array_keys($this->Defs) as $d) {
            $this->{$d} = $this->doSelect($d);
            if ($d == "ITEMS") {
                if (count($this->Items)) {
                    $whseSeq = array();
                    foreach ($this->Items as $key => $item) {
                        $loc = $item["whse_loc"];
                        $nkey = "{$loc}!{$key}";
                        $whseSeq[$nkey] = $item;
                    } // end foreach items
                    ksort($whseSeq);
                    $this->whseSeq = $whseSeq;
                } // end count items > 0
            } // end ITEMS
        } // end foreach array_keys
        if (count($this->Order) > 0) $this->getShipTo();
    } // end gData

} // end class Orders
?>
