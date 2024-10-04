<?php
function ftp_getfiles($delete_flag, $OtherDir = "")
{
// set up basic connection

    require("config.php");

//echo "<pre><h3>Server={$ftpserver}</h3>\n";
    $conn_id = ftp_connect($ftpserver, 21, 30);

// login with username and password
    if ($conn_id) {
        $login_result = ftp_login($conn_id, $ftpuser, $ftpwd);
    }

// check connection
    if ((!$conn_id) || (!$login_result)) {
        echo "<h3>FTP connection has failed!</h3>";
        echo "<h3>Attempted to connect to $ftpserver for user $ftpuser</h3>";
        return ("");
    } else {
        //echo "<h3>Connected to $ftpserver, for user $ftpuser</h3>";
        $a = 0;
    }

//set pasv mode
    ftp_pasv($conn_id, true);

//Override ftpIn with passed directory
    if (trim($OtherDir) <> "") $ftpIn = $OtherDir;

    if (ftp_chdir($conn_id, $ftpIn)) {
        echo "Current directory is now: " . ftp_pwd($conn_id) . "\n";
    } else {
        echo "Couldn't change directory\n";
        return ("");
    }

    $dir = str_replace("/", "", $directory);

// check for lockfile
    $ok = false;
    while (!$ok) {
        $file_size = ftp_size($conn_id, $lockfile);
        if ($file_size != -1) {
            echo "waiting for lock\n";
            ob_flush();
            flush();
            sleep(2);
        } else $ok = true;
    } // end ok loop

//get files
    $contents = ftp_nlist($conn_id, "");
    rsort($contents);

    foreach ($contents as $key => $sourcefile) {
        //echo "<h3> Directory " . $sourcefile . "</h3>\n";
        $destination_file = "{$inDir}/{$sourcefile}";
        //echo "<h3>" . $sourcefile . "</h3>\n";
        $suffix = substr($sourcefile, -4);
        unset ($download);
        if (substr($sourcefile, 0, 3) == "old"
            or substr($sourcefile, 0, 4) == "save") {
            $download = false;
            unset($contents[$key]);
        } else {
            // echo "<h3>Down $sourcefile from $ftpserver as $destination_file</h3>";
            $download = ftp_get($conn_id, $destination_file, $sourcefile, FTP_BINARY);
        } // sourcefile <> old

// check download status
        if ($download) {
            /*
                if (substr($sourcefile,0,13)=="ClosedReceipt")
                {
                  echo "ClosedReceipt\n";
                } // file is ClosedReceipt file
                if (substr($sourcefile,0,7)=="wAdjust")
                {
                  echo "wAdjust\n";
                } // file is wAdjust
                if (substr($sourcefile,0,11)=="wInvExtract")
                {
                  echo "wInvExtract\n";
                } // file is wInvExtract
                if (substr($sourcefile,0,8)=="wReceipt")
                {
                  echo "wReceipt\n";
                } // file is wReceipt
                if (substr($sourcefile,0,5)=="wShip")
                {
                  echo "wShip\n";
                } // file is wShip
                if (substr($sourcefile,0,6)=="wCount")
                {
                  echo "wCount\n";
                } // file is wCount
            */

            if ($delete_flag == "Y") {
                if (ftp_delete($conn_id, $sourcefile)) {
                    echo "<h3>successfully deleted $sourcefile</h3>";
                } else {
                    echo "<h3>There was a problem while deleting $sourcefile</h3>";
                }
            }
        } // !download
    } // end foreach contents
    $rdir = ftp_chdir($conn_id, "..");
//
// close the FTP stream
    ftp_close($conn_id);
    return ($contents);
} //end function ftp_it
?>
