<?php

function getPart($db, $pnum_in)
{
    $ret = array();
    $ret["status"] = 0;
    $ret["num_rows"] = 0;
    $SQL = <<<SQL
SELECT 
alt_part_number,alt_type_code,alt_uom
p_l,
part_number,
part_desc,
part_long_desc,
unit_of_measure,
part_seq_num,
part_subline,
part_category,
part_group,
part_class,
serial_num_flag,
special_instr,
hazard_id,
kit_flag,
cost,
core,
core_group,
part_returnable,
shadow_number
 FROM ALTERNAT,PARTS
 WHERE alt_part_number like "{$pnum_in}"
 AND  shadow_number = alt_shadow_num
order by alt_part_number,alt_sort,alt_type_code, alt_shadow_num
SQL;

    $i = 0;
    $rc = $db->query($SQL);
    $numrows = $db->num_rows();
    while ($i <= $numrows) {
        $db->next_record();
        if ($numrows and $db->Record) {
            foreach ($db->Record as $key => $data) {
                if (!is_numeric($key)) {
                    $ret[$i]["$key"] = $data;
                }
            }
        }
        $i++;
    } // while i < numrows
    $ret["num_rows"] = $numrows;
    if ($ret["num_rows"] == 0) {
        $ret["status"] = -35;
    }
    return ($ret);
} // end getPart
?>
