<?php

//cl_inv.php -- Update Qty, book history, update bin records
/*
 * cl_inv.php
 * 02/28/22 Dave Erlenbach initial
 * 03/02/22 dse add option opt_qoh - if on, set primary bin if not qoh
 * 06/22/22 dse change paud_bin to paud_floc and add paud_tloc
 * 06/24/22 dse change updQty to be able to just add PARTHIST if adjInv is true
 * 06/14/24 dse don't set primary bin if it stats with !
 * 06/18/24 dse add po on moveQty to allow putaway 
 * 07/25/24 dse add read of opt 399 - 402 to support cust returns

*/

if (get_cfg_var('wmsdir')) $wmsDir = get_cfg_var('wmsdir');
else {
    echo "<h1>WMS System is not Configured on this System</h1>";
    exit;
}
$top = str_replace("/var/www", "", $wmsDir);

$wmsInclude = "{$wmsDir}/include"; // main incude for this system
require_once("{$wmsInclude}/db_main.php");
require_once("{$wmsInclude}/wr_log.php");
require_once("{$wmsInclude}/cl_TOTES.php");

$rev_pos = strpos(strrev(__FILE__), strrev("/"));
$incDir = substr(__FILE__, 0, strlen(__FILE__) - $rev_pos - strlen("/"));
require_once("{$incDir}/get_option.php");
unset($rev_pos);
unset($incDir);

class invUpdate
{
    public $numRows;
    public $opt_qoh;
    private $db;
    private $logfile;
    private $debug;

    public function __construct()
    {
        $this->db = new WMS_DB;
        $this->logfile = "/tmp/cl_invUpd.log";
        $this->debug = false;
        $this->numRows = 0;
        $this->opt_qoh = false;
    } // end contruct

    function updQty($tran, $adjInv = true)
    {
        /* tran is an array of the following;
      (
                                  IN wms_trans_id   int,
                                  IN shadow     int,
                                  IN company    smallint,
                                  IN psource      char(  10 ),
                                  IN user_id     int,
                                  IN host_id   char(  20 ),
                                  IN ext_ref  char( 20 ),
                                  IN trans_type char(  3 ),
                                  IN in_qty   int,
                                  IN uom char(  3 ),
                                  IN floc  varchar(18),
                                  IN tloc  varchar(18),
                                  IN inv_code  char(  1 ),
                                  IN mdse_price numeric (10,3),
                                  IN core_price numeric (10,3),
                                  IN in_qty_core   smallint,
                                  IN in_qty_def smallint,
                                  IN bin_type char(1) )

        */
        // Update or insert WHSEQTY, WHSELOC and PARTHIST
        // bin types P=Primary Bin,S=Secondary,O=Overstock, M=Moveable, ...

        $old_qoh = 0;
        $whsQty = 0;
        $today = $this->db->dbDate();

        //check variables
        if (!is_array($tran)) return false;
        foreach (array_keys($tran) as $w) {
            $$w = $tran[$w];
        }
        if (isset($company) and $company < 1) return false;
        if (isset($shadow) and $shadow < 1) return false;
        //finish validation and make sure vaiable is set
        $primary_bin = $floc;
        $SQL = <<<SQL
    select qty_avail + qty_alloc as old_qoh,
    primary_bin
    from WHSEQTY
    where ms_company = {$company}
    and ms_shadow = {$shadow}
SQL;
        $w = $this->gData($SQL);
        if (isset($w["old_qoh"])) $old_qoh = $w["old_qoh"];
        if (isset($w["primary_bin"])) $primary_bin = $w["primary_bin"];
        $pbinSQL = "";
        $opt = get_option($this->db, $company, 21);
        $bin_type = substr($opt, 1, 1);
        if ($old_qoh == 0 and $this->opt_qoh) $primary_bin = "";
        if (trim($primary_bin) == "" and trim($floc) <> "" and substr($floc, 0, 1) <> "!") {
            $primary_bin = $floc;
            $bin_type = substr($opt, 0, 1);
            $pbinSQL = <<<SQL
, primary_bin = "{$primary_bin}"
SQL;
        }

        $SQLS = array();
        $si = 0;

        // if adjInv is false, only insert into PARTHIST
        if ($adjInv) {
            $SQLS[$si] = <<<SQL
    insert into WHSELOC ( whs_company, whs_location, whs_shadow, whs_code, whs_qty, whs_uom,whs_alloc)
     values ({$company},"{$floc}",{$shadow},"{$bin_type}", {$in_qty}, "{$uom}",0)
    ON DUPLICATE KEY UPDATE whs_qty = whs_qty + {$in_qty}

SQL;
            $si++;
            $SQLS[$si] = <<<SQL

   insert into WHSEQTY ( ms_shadow, ms_company, primary_bin, qty_avail,qty_core,qty_defect)
   values ({$shadow},{$company},"{$floc}",{$in_qty},{$in_qty_core},{$in_qty_def})
    ON DUPLICATE KEY UPDATE qty_avail = qty_avail + {$in_qty},
                            qty_core = qty_core + {$in_qty_core},
                            qty_defect = qty_defect + {$in_qty_def}{$pbinSQL}

SQL;
            $si++;
        } // end adjInv is true

        // Insert PARTHIST
        $fbin = $floc;
        $tbin = $tloc;
        if ($tbin == "Received" or $trans_type == "PUT") // flip old and new bin
        {
            $tbin = $floc;
            $fbin = $tloc;
        }
        if (substr($tbin, 0, 1) == "!") $tbin = substr($tbin, 1);

        // set not inventory to negative number
        if ($adjInv == false) $inv_code = "-";
        $SQLS[$si] = <<<SQL
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
         ( "{$wms_trans_id}",
           {$shadow},
           {$company},
           "{$today}",
           "{$psource}",
           {$user_id},
           "{$host_id}",
           "{$ext_ref}",
           "{$trans_type}",
           {$in_qty},
           "{$uom}",
           "{$fbin}",
           "{$tbin}",
           {$old_qoh},
           "{$inv_code}",
           {$mdse_price},
           {$core_price},
           {$in_qty_core},
           {$in_qty_def} );

SQL;

        $rc = array();
        //Start Transaction
        $rc["start"] = $this->db->startTrans();
        //run each SQL in Transaction
//echo "<pre>";
        foreach ($SQLS as $k => $SQL) {
//print "{$SQL}\n";
            $rc[$k] = $this->db->updTrans($SQL);
        } // end foreach SQLS
        // commit or Rollback Transaction
        $rc["end"] = $this->db->endTrans($rc[$k]);
//echo "<pre>RC=";
//print_r($rc);
//exit;
        return $rc["end"];
    } // end updQty

    private function gData($SQL)
    {
        $tmp = array();
        $ret = array();
        $this->numRows = 0;
        $rc = $this->db->query($SQL);
        $numrows = $this->db->num_rows();
        $i = 1;
        while ($i <= $numrows) {
            $this->db->next_record();
            if ($numrows and $this->db->Record) {
                foreach ($this->db->Record as $key => $data) {
                    if (!is_numeric($key)) {
                        $tmp[$i]["$key"] = $data;
                    }
                }
            }
            $i++;
        } // while i < numrows
        if ($numrows == 1) $ret = $tmp[1]; else $ret = $tmp;
        $this->numRows = $numrows;
        return $ret;
    } // end moveQty

    function moveQty($tran)
    {
        /* tran is an array of;
         [fromTo] => Array
              (
                  [comp] => 1
                  [shadow] => 88136
                  [qty] => 1
                  [userId] => 1
                  [updWhseQty] => 0 or 1  Add Qty to WHSEQTY too , Optional
                  [po] => ""  // optional, putaway (not move) if set
                  [from] => Array
                      (
                          [type] => B
                          [Bin] => A-02-01-C
                      )

                  [to] => Array
                      (
                          [type] => T
                          [Bin] => 158
                      )

              )

        */
        $jsn = json_encode($tran);
        file_put_contents("/tmp/dave.txt", "{$jsn}\n", FILE_APPEND);
        $old_qoh = 0;
        $whsQty = 0;
        $inv_code = " "; // later change to support core and defect
        $coreQty = 0;
        $defQty = 0;
        $wms_trans_id = 0; // perhaps get from control
        $today = $this->db->dbDate();
        if (!is_array($tran)) return false;
        foreach (array_keys($tran) as $w) {
            if (!is_array($w)) $$w = $tran[$w];
        }
        $ok = false;
        if (isset($tran["from"])) {
            $source = $tran["from"]["Bin"];
            $srcType = $tran["from"]["type"];
            $phSrc = $source;
            if ($srcType == "T") $phSrc = "!" . $source;
            $ok = true;
        }
        if (!$ok) return false;
        $ok = false;
        if (isset($tran["to"])) {
            $dest = $tran["to"]["Bin"];
            $destType = $tran["to"]["type"];
            $phDest = $dest;
            if ($destType == "T") $phDest = "!" . $dest;
            $ok = true;
        }
        if (!$ok) return false;
        if ($comp < 1) return false;
        if ($shadow < 1) return false;
        $ttype = "MOV";
        $tref = "MOVE";
        $o399 = get_option($this->db, $comp, 399);
        $o400 = get_option($this->db, $comp, 400);
        $o401 = get_option($this->db, $comp, 401);
        $o402 = get_option($this->db, $comp, 402);
        $floc = $source;
        if ($o399 > 0) { // it is a cust return
            if ($source == $o400) $tref = "Stock";
            if ($source == $o401) $tref = "Core";
            if ($source == $o402) $tref = "Defect";
            $ttype = "PUT";
            $floc = "Return";
        } // it is a cust return
        $tloc = $dest;
        if (isset($po) and $po <> "") {
            $ttype = "PUT";
            $tref = $po;
            if (isset($qty_avail)) $old_qoh = $qty_avail;
        }

        $SQLS = array();
        $si = 0;
        $primary_bin = "";
        $locBinType = "O";
        $locBinUpd = "";
        $SQL = <<<SQL
    select primary_bin from WHSEQTY
    where ms_company = {$comp}
    and ms_shadow = {$shadow}
SQL;

        $w = $this->gData($SQL);
        if (isset($w[1]["primary_bin"])) $primart_bin = $w[1]["primary_bin"];
        if ($primary_bin == "" and $destType <> "T") {
            $locBinType = "P";
            $primary_bin = $phDest;
            $SQLS[$si] = <<<SQL
update WHSEQTY set primary_bin = "{$phDest}" 
    where ms_company = {$comp}
    and ms_shadow = {$shadow}
SQL;
            $si++;
            $locBinUpd = <<<SQL
, whs_code = "P"
SQL;
        }

        if (isset($updWhseQty) and $updWhseQty > 0) {
            $SQLS[$si] = <<<SQL
update WHSEQTY set qty_avail = qty_avail + {$qty}
    where ms_company = {$comp}
    and ms_shadow = {$shadow}
SQL;
            $si++;
        }
        $SQL = <<<SQL
    select whs_qty as old_qoh,
    whs_uom
    from WHSELOC
    where whs_company = {$comp}
    and whs_shadow = {$shadow}
    and whs_location = "{$source}"

SQL;
        $w = $this->gData($SQL);
        $uom = "EA";
        if (isset($w["old_qoh"])) $old_qoh = $w["old_qoh"];
        if (isset($w["wms_uom"])) $uom = $w["wms_uom"];

        // trans 1, move inventory out of old bin
        $SQLS[$si] = <<<SQL
    update WHSELOC
    set whs_qty = whs_qty - $qty
    where whs_company = {$comp}
    and whs_shadow = {$shadow}
    and whs_location = "{$phSrc}"

SQL;
        // trans 2, move inventory into new bin
        $si++;
        $SQLS[$si] = <<<SQL
    insert into WHSELOC ( whs_company, whs_location, whs_shadow, whs_code, whs_qty, whs_uom,whs_alloc)
     values ({$comp},"{$phDest}",{$shadow},"{$locBinType}", {$qty}, "{$uom}",0)
    ON DUPLICATE KEY UPDATE whs_qty = whs_qty + {$qty} {$locBinUpd}

SQL;
        $si++;
        // trans 3, book parthist

        $SQLS[$si] = <<<SQL
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
         ( "{$wms_trans_id}",
           {$shadow},
           {$comp},
           "{$today}",
           " ",
           {$userId},
           "{$tref}",
           " ",
           "{$ttype}",
           {$qty},
           "{$uom}",
           "{$floc}",
           "{$tloc}",
           {$old_qoh},
           "{$inv_code}",
           0.00,
           0.00,
           {$coreQty},
           {$defQty} );

SQL;
        // have to update tote Hdr if source or dest is a tote
        //if source is T and dest <> T update totehdr with dest bin
        //if dest is T and source <> T update totehdr with source bin
        //if niether is T dont worry about it

        if ($srcType == "T" or $destType == "T") {
            $theTote = new TOTE;
            $toteStat = 1; // set tote status to used
            //tote Ref= "" at this time, make have to make a move record later
            $lastLoc = $source;
            if ($ttype == "PUT") $ttype = "RCS";
            if ($srcType == "T") {
                $lastLoc = $source;
                $rc = $theTote->updToteHdr($source, $comp, $toteStat, "{$ttype}", $lastLoc, "");
                // remove the part from the tote
                $q = -$qty;
                $rc1 = $theTote->addItemToTote($source, $shadow, $q, $uom);
                // check is tote is empty, if so, free it
                $rc2 = $theTote->cdShadow($source, $shadow);
                if ($rc2 > 0) { // delete WHSELOC record if empty for this part
                    $si++;
                    $SQLS[$si] = <<<SQL
 delete from WHSELOC 
where whs_company = {$comp}
    and whs_shadow = {$shadow}
    and whs_location = "{$phSrc}"
SQL;
                } // end  delete WHSELOC record if empty for this part
                $rc3 = $theTote->freeTote($source);
            } // remove item from source tote

            if ($destType == "T") {
                $lastLoc = $source; // set to source so be know what bin is was
                $rc = $theTote->updToteHdr($desc, $comp, $toteStat, "{$ttype}", $lastLoc, "");
                // add the part to the tote
                $q = $qty;
                $rc1 = $theTote->addItemToTote($dest, $shadow, $q, $uom);
            }

        } // end source or dest is a T


        $rc = array();
        //Start Transaction
        $this->db->DBDBG = "/tmp/moveQty_SQL.log";
        $rc["start"] = $this->db->startTrans();
        //run each SQL in Transaction
        foreach ($SQLS as $k => $SQL) {
            $rc[$k] = $this->db->updTrans($SQL);
            wr_log("/tmp/cl_inv.log", "SQL={$SQL}");
        } // end foreach SQLS
        // commit or Rollback Transaction
        $rc["end"] = $this->db->endTrans($rc[$k]);
        //$this->db->DBDBG="";
        //delete any un-used bins for this part if not the primary and there empty
        $SQL = <<<SQL
 delete from WHSELOC
where whs_company = {$comp}
    and whs_shadow = {$shadow}
    and whs_code <> "P"
    and whs_qty = 0
    and whs_alloc = 0

SQL;

        wr_log("/tmp/cl_inv.log", "SQL={$SQL}");
        $rc["extra"] = $this->db->Update($SQL);

        return $rc;
    } // end gData
} // end class invUpd
?>
