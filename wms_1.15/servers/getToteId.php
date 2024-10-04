<?php
 function getToteId($tote_code,$comp=0)
 {
  global $db;
  $ret=-1;
  $awhere="";
  $where=<<<SQL
where tote_code = "{$tote_code}"
SQL;
  if (is_numeric($tote_code)) $where=<<<SQL
where tote_id = {$tote_code}
SQL;
  
  if ($comp > 0) $awhere = "and tote_company = {$comp}";
  $SQL=<<<SQL
select tote_id from TOTEHDR
{$where}
{$awhere}

SQL;
  $rc4=$db->query($SQL);
  $numrows=$db->num_rows();
  $i=1;
  while ($i <= $numrows)
  {
   $db->next_record();
     if ($numrows)
     {
        $ret=$db->f("tote_id");
     }
     $i++;
   } // while i < numrows
  return $ret;
 } // end getToteId
?>
