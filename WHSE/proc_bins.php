<?php
//proc_bins.php -- process uploaded bins
// 12/23/21 dse adapted from proc_parts.php

foreach (array_keys($_REQUEST) as $w) {
    $$w = $_REQUEST[$w];
}
//error_reporting(0);
session_start();
require($_SESSION["wms"]["wmsConfig"]);
$debug = 0;
$return = "bin_upl.php";


$htm = <<<HTML
<html>
<head>
</head>
    <body marginwidth="0" marginheight="0" topmargin="0" leftmargin="2">
HTML;
echo $htm;
//change tabs and commas to pipes
$ok = 1;
$fname = "bin_upd.xls";
//$uploadfile = $uploaddir. $_FILES['FileUpload1_File']['name'];
$orig_file = $_FILES["FileUpload1_File"]["name"];
echo "<pre>";
//print_r($_FILES);
//phpinfo(INFO_VARIABLES);
$uploaddir = '/usr1/wms/tmp/';
$uploadfile = $uploaddir . $fname;
print "<p align=\"center\">";
if (move_uploaded_file($_FILES['FileUpload1_File']['tmp_name'], $uploadfile)) {
    print "<h4>File $fname is valid, and was successfully uploaded.</h4> ";
//   print "Here's some more debugging info:\n";
//   print_r($_FILES);
} else {
    if ($_FILES['FileUpload1_File']['size'] == 0) {
        print "<h4>Please select a file to upload.</h4>";
        $ok = 0;
    } else {
        print "<h4>Possible file upload attack!  Here's some debugging info:</h4>";
        print "<pre>";
        print_r($_FILES);
        $ok = 0;
        print "</pre>";
    }
}
require_once("{$wmsInclude}/db_main.php");
require_once("{$wmsInclude}/get_companys.php");
require_once("{$wmsInclude}/onlyascii.php");
require_once("{$wmsInclude}/cl_addupdel.php");
$db = new DB_MySQL;
$upd = new AddUpdDEL;

$j = strpos($fname, ".xls");
$j1 = strpos($orig_file, ".xlsx");
//echo "<pre>uploaddir={$uploaddir} fname={$fname} j={$j}\n";
if ($j > 0) { // convert from Excel to csv
    $sfile = "{$uploaddir}/{$fname}";
    $fname = str_replace(".xls", ".csv", $fname);

    $dfile = "{$uploaddir}/{$fname}";
    $cmd = "xls2csv -x {$sfile} -s cp1253 -d 8859-1 >{$dfile}";
    if ($j1 > 0) $cmd = "xlsx2csv {$sfile} {$dfile}";
    $output = exec($cmd);
    echo $output;
}  // convert from Excel to csv

echo "<pre>";
echo "<h2>File=:{$uploaddir}/{$fname}</h2>\n";
$fullname = "{$uploaddir}/{$fname}";
$fields = "";
$types = "";
$select_fields = "wb_company,wb_location";
//extra fields
if (!isset($pfields)) $pfields = array();
if (count($pfields) > 0) {
    foreach ($pfields as $key => $fld) {
        $fields[$key + 2] = $fld;
        //case "wb_section":
        //case "wb_aisle":
        //case "wb_subin":
        switch ($fld) {
            case "wb_width":
            case "wb_depth":
            case "wb_height":
            case "wb_volume":
            case "wb_pick":
            case "wb_recv":
                $types[$fld] = "1";
                break;
            default:
                $types[$fld] = "0";
                break;
        } // end switch fld
        if ($select_fields <> "") $select_fields .= ",";
        $select_fields .= " {$fld}";
    } // end foreach pfields
} //pfields is set
echo "<pre>";
$bins = array();
$nofbins = array();
$comp = 1;
$COMPS = get_companys($db, 0);
$rec_read = 0;
$rec_found = 0;
$rec_add = 0;
$rec_upd = 0;
$rec_nupd = 0;
$row = 1;

if (($handle = fopen($fullname, "r")) !== FALSE) {
    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        $num = count($data);
        if ($row > 1) {
            $rec_read++;
            $comp = $data[0];
            $bins[$row]["wb_company"] = onlyascii($data[0]);
            if (!isset($COMPS[$comp])) {
                echo "<pre>";
                echo "Invalid Wharehouse #: {$comp}\n";
                echo "Data record is row:{$row}\n";
                print_r($data);
                exit;
            }
            if (!isset($data[1])) {
                $msg = "There is not enough columns in this spreadsheet, the values (column B) is empty";
                echo "<pre>";
                print_r($data);
                exit;
                disp_err($row, $data, $msg);
            }
            $theBin = onlyascii($data[1]);
            $bins[$row]["wb_location"] = $theBin;
            $onFile = get_bininfo($db, $comp, $theBin);
            if (count($onFile)) $rec_found++;
            $j = count($pfields);
            if ($j > 0) {
                for ($i = 0; $i < $j; $i++) {
                    if (isset($data[$i + 2])) {
                        $k = $i + 2;
                        $bins[$row][$pfields[$i]] = onlyascii($data[$k]);
                        if (trim($data[$k]) == "") {
                            if ($types[$pfields[$i]] > 0) $bins[$row][$pfields[$i]] = 0;
                            else $bins[$row][$pfields[$i]] = " ";
                        } // data is empty
                    } else {
                        if ($types[$pfields[$i]] > 0) $bins[$row][$pfields[$i]] = 0;
                        else $bins[$row][$pfields[$i]] = " ";
                    }
                }
            }
            if (count($onFile) < 1) { // bin not found add it
                $rec_add++;
                $comma = "";
                $dataset = "";
                foreach ($bins[$row] as $key => $Fld) {
                    if (strlen($dataset) > 0) $comma = ",";
                    $val = trim($Fld);
                    if ($key == "wb_company") $val = trim($Fld);
                    else if ($key == "wb_location") $val = '"' . trim($Fld) . '"';
                    else
                        if ($types[$key] < 1) $val = '"' . trim($Fld) . '"';
                    $dataset .= "{$comma}{$val}";
                } // end foreach bins[row]

                $uSQL = <<<SQL
insert into WHSEBINS ({$select_fields})
values ( {$dataset} );

SQL;
            } // bin not found add it
            else { // bin is there, update it
                $comma = "";
                $set = "set";
                $dataset = "";
                foreach ($bins[$row] as $key => $Fld) {
                    if ($key <> "wb_company" and $key <> "wb_location") {
                        if (strlen($dataset) > 0) {
                            $comma = ",";
                            $set = "";
                        }
                        $val = trim($Fld);
                        if ($val <> trim($onFile[$key])) { // field is changed
                            if ($types[$key] < 1) $val = '"' . trim($Fld) . '"';
                            $dataset .= "{$comma}\n{$set} {$key}={$val}";
                        } // field is changed
                    } // not comp or bin
                } // end foreach bins[row]
                if (strlen($dataset) > 0) {
                    $uSQL = <<<SQL
update WHSEBINS
{$dataset}
where wb_company = {$comp}
  and wb_location = "{$theBin}"

SQL;
                    $rec_upd++;
                } else {
                    $uSQL = "";
                    $rec_nupd++;
                }
            } // bin is there, update it
            if (strlen($uSQL) > 0) $rc = $db->Update($uSQL);

        } // row > 1
        if (($row % 100) == 0) {
            echo "Record# {$row} {$bins[$row]["wb_location"]}\n";
            ob_flush();
            flush();
            usleep(50000);

        }
        $row++;
    }
    fclose($handle);
}
//print_r($COMPS);
$ins = 0;
//print_r($bins);
displayStats:
$dpl = "";
echo "All Done\n Records Read: {$rec_read}\n Bins Found: {$rec_found}\n Added: {$rec_add}\n Updated: {$rec_upd}\nNot Updated {$rec_nupd}{$dpl}";
//print_r($bins);
if (sizeof($nofbins) > 0) {
    $htm = <<<HTML
<table>
 <tr>
  <td colspan="3"><h2>Parts Not on File</h2></td>
 </tr>
 <tr>
  <th>P/L</th>
  <th>Part Number</th>
 </tr>

HTML;
    foreach ($nofbins as $key => $dat) {
        $htm .= <<<HTML
 <tr>
  <td>{$dat["pl"]}</td>
  <td>{$dat["part"]}</td>
 </tr>
HTML;
    } // end foreach nofpart
    $htm .= <<<HTML
</table>
HTML;
    echo $htm;
} // end nof

$htm = <<<HTML
<p>&nbsp;</p>
<a href="{$return}">Return</a>
 </body>
</html>
HTML;
echo $htm;

exit;
function get_bininfo($db, $comp, $bin)
{
    $ret = array();
    $SQL = <<<SQL
 select * from WHSEBINS
 where wb_company = {$comp}
   and wb_location = "{$bin}"

SQL;

    $rc = $db->query($SQL);
    $numrows = $db->num_rows();
    $i = 1;
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
    return ($ret);
} // end get_bin

function disp_err($row, $data, $msg)
{
    $e = "";
    $alph = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
    for ($i = 0; $i <= count($data); $i++) {
        $j = $i + 1;
        $a = substr($alph, $j, 1);
        $e .= "Column {$a}=\"{$data[$i]}\" ";
    }
    $htm = <<<HTML
 <h2>{$msg}<h2><br><br>
This Data record (Row#: {$row});<br>
{$e}<br>
 Quiting...
HTML;
    echo $htm;
    exit;
} // end disp_error
?>
