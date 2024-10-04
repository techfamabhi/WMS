<?php
// Function to write to logfile 
function wr_log($logfile, $logentry)
{
    $cdate = date("m/d/Y H:i:s");
    $fp = fopen("$logfile", "a");
    fwrite($fp, "{$_SERVER["SCRIPT_FILENAME"]}|{$cdate}|");
    fwrite($fp, "$logentry\n");
    fclose($fp);
}

?>
