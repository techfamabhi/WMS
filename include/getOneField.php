<?php
function getOneField($db, $SQL, $fname)
{
    $ret = "";
    $rc = $db->query($SQL);
    $numrows = $db->num_rows();
    $i = 1;
    while ($i <= $numrows) {
        $db->next_record();
        if ($numrows) {
            $ret = $db->f($fname);
        }
        $i++;
    } // while i < numrows
    return $ret;

} // end getOneField
?>
