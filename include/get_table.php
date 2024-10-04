<?php
/* $Id: get_table.php,v 1.2 2013/03/25 21:10:16 root Exp root $
   $Source: /usr1/include/RCS/get_table.php,v $ */

/* Mon Mar 25 2013
Changed to use function get_metachar so it will work on PHP 5 

Wed Jan 12 2022 dse converted to MYSQL DATE_FORMAT 
                    and new Metadata function in db class

*/


function get_table($db1, $table, $where)
{
    $table_info = array();
    $table_info = get_metadata($db1, $table);
    $select = "select ";
    $i = 1;
    foreach ($table_info as $key => $pvalue) {
        $fld_name = $pvalue["name"];
        $dbvalue = $fld_name;
        if ($pvalue["type"] == "datetime") {
            $itsatime = strpos($fld_name, "time");
            if ($itsatime == true) {
                $dbvalue = "DATE_FORMAT({$fld_name},'%H:%i') as {$fld_name}";
            } else {
                $dbvalue = "DATE_FORMAT({$fld_name},'%m/%d/%Y') as {$fld_name}";
            }
        }
        $comma = ",\n";
        if ($i == 1) {
            $comma = "";
        }
        if ($fld_name) {
            $select .= $comma . $dbvalue;
        }
        $i++;
    }
    $select .= " \nfrom $table\n $where \n";
    $table_info["Select"] = $select;
    return ($table_info);
} // end get_table

function get_metadata($db, $table)
{
    $res = array();
    $w = $db->metadata($table);
    $i = 1;
    foreach ($w as $key => $f) {
        $res[$i]["table"] = $table;
        $res[$i]["name"] = $f["Field"];
        $res[$i]["type"] = $f["Type"];
        $res[$i]["len"] = 0;
        $res[$i]["position"] = $i;
        $res[$i]["flags"] = "";
        $i++;
    }
    return ($res);
} // end get_metadata
?>
