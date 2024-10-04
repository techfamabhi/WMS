<?php

function get_option($db,$comp,$option)
{
$ret="";
$SQL=<<<SQL
SELECT cop_option,cop_flag 
FROM COPTIONS
WHERE cop_company = {$comp}
  AND cop_option = {$option}

SQL;
$rc=$db->query($SQL);
$numrows=$db->num_rows();
$i=1;
 while ($i <= $numrows)
 {
  $db->next_record();
     if ($numrows)
     {
      $ret=$db->f("cop_flag");
     }
  $i++;
 } // wjile i < numrows

return($ret);
} //end of get_option
?>
