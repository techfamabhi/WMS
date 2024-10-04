<?php
// procError.php -- a collection of functions to process errors
// version 1.0
// 

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

function logError($rtype, $filenm, $row, $docNum, $msg, $rowData, $flag = 0)
{
    /* Flag settings
     0=Add to db and output to screen
     1=Add to db and send err file and output to screen
     2=Same as #1 but also exits (not implemeted)
    */
    $rc = 0;
    $rc1 = 0;
    $edb = new WMS_DB;
    $errorInfo = array();
    $errorInfo["utcDate"] = gmdate("Y/m/d H:i:s");
    $errorInfo["recordType"] = $rtype;
    $errorInfo["fileName"] = $filenm;
    $errorInfo["rowNum"] = $row;
    $errorInfo["docNum"] = $docNum;
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
    $rc = $edb->Update($SQL);
    // end Save to db

    if ($flag > 0) $rc1 = sendError($errorInfo);
    $ret = "{$rc}:{$rc1}";

    echo "{$msg}\n";
    return $ret;
} // end logError

?>
