<?php
$setConfig = true;
require("config.php");

require_once("{$wmsInclude}/db_main.php");
require_once("{$wmsInclude}/quoteit.php");
require_once("{$wmsInclude}/escQuotes.php");
echo "<pre>";

$pl = "WIX";
$partNumber = "55555";
$company = 1;
$rowcnt = 10;
$d = "TES|112101|6|WIX|52057";
//$msg= "Part Number: {$pl} {$partNumber} Company: {$company} Not Found. Record: {$rowcnt}\n";
//$msg= "The Message";
$msg = array();
array_push($msg, "Invalid Company: 1");
array_push($msg, "Invalid Vendor: WIX");

$rc = logError("TES", "TEST.dat", 10, $msg, $d, 0);

function sendError($errInfo)
{
    // sendError send and error to outDir and mv filename to errDir

    /* errInfo is an array of the details of the error
     errInfo
       recordType	(POH, POD, ...)
        filename	The filename the error occurred in
        rowNum		The row number where the error occurred
           message		an array of messages of whats wrong
    */
    // outType=A = Ascii Pipe Delimted, J=Json
    global $outType;
    global $outDir;
    global $outNotice;
    global $doneDir;
    global $sentDir;
    global $errDir;

    $ext = "txt";
    $mode = "w";
    if (strtoupper($outType) == "JSON") $ext = "json";
    $t = $errInfo["fileName"];
    $j = strpos($t, ".");
    if ($j) $t = substr($t, 0, $j);
    $fname = "{$outDir}/Error_{$t}.{$ext}";
    $output = "";
    foreach ($errInfo as $r => $d) {
        if (is_array($d)) {
            foreach ($d as $n => $k) {
                $output .= "{$r}:{$k}\n";
            } // end foreach d
        } // end if array
        else $output .= "{$r}:{$d}\n";
    } // end foreach errInfo
    $rc = file_put_contents($fname, $output, LOCK_EX);
    return $rc;
} // end sendError

function logError($rtype, $filenm, $row, $msg, $rowData, $flag = 0)
{
    /* Flag settings
     0=Add to db
     1=Add to db and send err file
     2=Same as #1 but also exits
    */
    $edb = new WMS_DB;
    $errorInfo = array();
    $errorInfo["utcDate"] = gmdate("Y/m/d H:i:s");
    $errorInfo["recordType"] = $rtype;
    $errorInfo["fileName"] = $filenm;
    $errorInfo["rowNum"] = $row;
    $errorInfo["message"] = array();
    $errorInfo["rowData"] = $rowData;
    if (is_array($msg) and count($msg) > 0) {
        foreach ($msg as $m) {
            array_push($errorInfo["message"], $m);
        } // end foreach msg
    } // end msg is an array
    else {
        array_push($errorInfo["message"], $msg);
    } // msg is not an array

    //Save to db
    $FLDS = "";
    $VALS = "";
    $comma = "";
    foreach ($errorInfo as $r => $d) {
        $FLDS .= "{$comma}{$r}";
        if (is_array($d)) {
            $VALS .= "{$comma}\"";
            $semicolon = "";
            foreach ($d as $n => $k) {
                $v = escQuotes($k);
                $VALS .= "{$semicolon}{$v}";
                $semicolon = ";";
            } // end foreach d
            $VALS .= "\"";
        } // end if array
        else { // not an array
            $v = quoteit(escQuotes($d));
            if ($r == "rowNum") $v = $d;
            $VALS .= "{$comma}{$v}";
        } // not an array
        $comma = ",";
    } // end foreach errInfo
    $SQL = <<<SQL
 insert into WMSERROR
 ({$FLDS})
 values ({$VALS})

SQL;
    $rc = 0;
    $rc = $edb->Update($SQL);
    echo "SQL={$SQL}\nrc={$rc}\n";
    exit;
    // end Save to db

    $ret = sendError($errorInfo);
    return $ret;

} // end frmtError

function mvFile($f, $doneDir)
{
    $w1 = basename($f);
    rename($f, "{$doneDir}/{$w1}");
} // end mvFile
?>
