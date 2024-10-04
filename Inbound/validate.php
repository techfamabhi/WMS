<?php
$partValFields = array(
    "unit_of_measure" => "UOM_CODES|uom_code",
    "part_subline" => "SUBLINES|subline",
    "part_category" => "CATEGORYS|cat_id",
    "part_group" => "PARTGROUPS|pgroup_id",
    "part_class" => "PARTCLASS|class_id",
    "hazard_id" => "HAZARD_CODES|haz_code"
);

function validateIt($db, $table, $keyFld, $val, $str = 1)
{
    // if str=0, dont quote the key value
    $comma = ",";
    if ($str < 1) $comma = "";
    $ret = false;
    if (trim($val) == "") return (true);
    $SQL = <<<SQL
select count(*) as cnt from {$table}
where {$keyFld} = {$comma}{$val}{$comma}

SQL;

    $rc = $db->query($SQL);
    $numrows = $db->num_rows();
    $i = 1;
    while ($i <= $numrows) {
        $db->next_record();
        if ($numrows) {
            $cnt = $db->f("cnt");
        }
        $i++;
    } // while i < numrows
    if ($cnt > 0) $ret = true;
    return ($ret);
}

?>
