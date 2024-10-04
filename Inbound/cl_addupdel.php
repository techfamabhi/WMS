<?php
if (get_cfg_var('wmsdir')) $wmsDir = get_cfg_var('wmsdir');
else {
    echo "<h1>WMS System is not Configured on this System</h1>";
    exit;
}
$top = str_replace("/var/www", "", $wmsDir);

$wmsInclude = "{$wmsDir}/include"; // main incude for this system
require_once("{$wmsInclude}/db_main.php");
require_once("{$wmsInclude}/quoteit.php");
require_once("{$wmsInclude}/escQuotes.php");
require_once("{$wmsInclude}/wr_log.php");

//db_addupdel -- Generic Add/Update/Delete database class
/*
 * db_addupdel.php
 * 12/15/21 Dave Erlenbach initial
 *
 * Class to handle db update if the record exists
 *       or add the record if not
 * also handles deleting the record
 *
 * since the "if exists" syntax differs from database to database
 * this class reads the record first, then compares all the fields
 * in the table to what was passed in and if the record exists, it
 * updats only the fields that changed.
 * if not, it adds the record with 0's or spaces for empty fields
 Example;
  create an instance;
  $upd=new AddUpdel;

  setup an array of each record for reqdata Fieldname=>Value ;
  [vendor] => ABC
  [name] => ABC VENDOR
  [addr1] => 123 MAIN STREET
...

 set the table to update.
 set the where clause to find the unique record for each data set 
 then call
 $return_code=$upd->updRecord($reqdata,$update_table,$where);

 */

class AddUpdDel
{
    public $updateTable;
    private $db;
    private $uFlds;

    public function __construct()
    {
        $this->db = new DB_MySQL;
        $this->updateTable = "";
        $this->uFlds = array();
    } // end contruct

    public function updRecord($reqdata, $upd_table, $where)
    {
        if (trim($where) == "") {
            return (false);
        }
        $this->updateTable = $upd_table;
        $this->uFlds = $this->setupd_flds();
        if (!isset($reqdata["action"])) $reqdata["action"] = 2;
        $rdata = "";
        $upd_flds = "";
        $comma = "";
        foreach ($this->uFlds as $f => $val) {
            if (strlen($upd_flds) > 0) $comma = ",";
            $upd_flds .= "{$comma}{$f}";
        }

        $SQL = <<<SQL
 select
 {$upd_flds}
 from {$this->updateTable}
 {$where}
 
SQL;

        $currec = array();
        $rc = $this->db->query($SQL);
        $numrows = $this->db->num_rows();
        $i = 0;
        while ($i <= $numrows) {
            $this->db->next_record();
            if ($numrows and $this->db->Record) {
                foreach ($this->db->Record as $key => $data) {
                    if (!is_numeric($key)) {
                        $currec["$key"] = $data;
                    }
                }
            }
            $i++;
        } // while i < numrows
        $foundCount = count($currec);
        switch ($reqdata["action"]) {
            case 1: // delete
            {
                if ($foundCount > 0) { // fond record, delete it
                    $SQL = <<<SQL
  delete from {$this->updateTable}
  {$where}
 
SQL;
                    if (defined('DEBUG')) wr_log(LOGFILE, $SQL);
                    $rc = $this->db->Update($SQL);
                    $msg = "({$rc}) Records Deleted";
                    if ($rc < 1) {
                        $msg = "An Error Accourred attempting to Delete the {$this->updateTable} record!";
                        if (defined('DEBUG')) wr_log(LOGFILE, $msg);
                    }
                    $rdata = '{"message":"' . $msg . '"}';
                } // fond record, delete it
                else { // record not found, nothing to delete
                    $rdata = '{"message":"Record Not Found, Nothing to Delete."}';
                } // record not found, nothing to delete
                return ($rdata);
                break;
            } // end delete
            case 0:
            case 2:
            { // add/update
                if ($foundCount < 1) {
                    $rdata = $this->addRecord($reqdata);
                    return ($rdata);
                    break;
                } // foundCount < 1
                else { // got a record, update it if needed
                    $SQL = <<<SQL
update {$this->updateTable} set
SQL;
                    $flds = array();
                    $found_diff = 0;
                    foreach ($currec as $f => $val) {
                        if (isset($reqdata[$f]) and trim($val) <> trim($reqdata[$f])) {
                            $val = trim($val);
                            $comma = "";
                            if ($found_diff > 0) $comma = ",";
                            $found_diff++;
                            $q = "";
                            if ($this->uFlds[$f] > 0) {
                                $q = '"';
                                $reqdata[$f] = escQuotes($reqdata[$f]);
                            }
                            // 1 last check now that quotes are removed
                            $SQL .= "{$comma} {$f} = {$q}{$reqdata[$f]}{$q}";
                        }
                    } // end foreach currec
                    $SQL .= "\n{$where}";

                    if ($found_diff > 0) {
                        if (defined('DEBUG')) wr_log(LOGFILE, $SQL);
                        $rc = $this->db->Update($SQL);
                        $msg = "({$rc}) Records Saved";
                        if ($rc < 1) {
                            if (defined('DEBUG')) wr_log(LOGFILE, "{$this->updateTable} - {$msg}");
                        }
                        $rdata = '{"message":"' . $msg . '"}';
                    } else {
                        $rdata = '{"message":"No Changes, Record Not Updated!"}';
                    }
                } // got a record, update it if needed
            } // add /update
        } // end switch
        return ($rdata);

    } // end updRecord

    private function setupd_flds()
    {
        $u = $this->db->MetaData($this->updateTable);
        unset($this->uFlds);
        $this->uFlds = array();
        foreach ($u as $key => $v) {
            $qote = 0;
            if (preg_match('(CHAR|DATE|TIME)', strtoupper($v["Type"])) === 1) {
                $qote = 1;
            }
            $this->uFlds[$v["Field"]] = $qote;
        } // end foreach u
        return ($this->uFlds);
    } // end addRecord

    private function addRecord($reqdata)
    {
        $rdata = "";
        //set update fields and types to find which fields need quotes
        $upd_flds = "";
        $comma = "";
        foreach ($this->uFlds as $f => $val) {
            if (strlen($upd_flds) > 0) $comma = ",";
            $upd_flds .= "{$comma}{$f}";
        }

        $updVals = "";
        $comma = "";
        foreach ($this->uFlds as $key => $v) {
            if (strlen($updVals) > 0) $comma = ",";
            if (isset($reqdata[$key])) $w = $reqdata[$key]; else $w = "";
            if ($key == "user_id") $w = "NULL";
            else {
                if ($v > 0) { // quote it
                    //need to properly escape embedded quotes at some point instead of
                    //removing them
                    $w = escQuotes($w);
                    //$w=str_replace("'","\'",$w);
                    //$w=str_replace('"','\"',$w);
                    $w = quoteit($w);
                } // quote it
                else if ($w == "") $w = 0;
            } // fld is not user id
            $val = $w;
            $updVals .= "{$comma}{$val}";
        } // end foreach uFlds
        $SQL = <<<SQL
 insert into {$this->updateTable} ({$upd_flds})
 values ( {$updVals})

SQL;
        if (defined('DEBUG')) wr_log(LOGFILE, $SQL);
        $rc = $this->db->Update($SQL);
        $msg = "({$rc}) Records Added";
        if ($rc < 1) {
            $msg = "An Error Accourred attempting to add the {$this->updateTable} record!";
            if (defined('DEBUG')) wr_log(LOGFILE, $msg);
        }
        $rdata = '{"message":"' . $msg . '"}';
        return ($rdata);
    } // end setupd_flds
} // end class AddUpdDel
?>
