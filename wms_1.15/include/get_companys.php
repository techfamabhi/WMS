<?php

function get_companys($db,$scomp=0)
{
 $where="where company_number > 0";
 if ($scomp > 0) $where="where company_number = {$scomp}";
 $comp_info=array();
 $SQL=<<<SQL
select * from COMPANY
{$where}

SQL;

$parts=array();
$rc=$db->query($SQL);
  $numrows=$db->num_rows();
  $i=1;
  while ($i <= $numrows)
  {
   $db->next_record();
     if ($numrows and $db->Record)
     {
      $comp=$db->f("company_number");
      foreach ($db->Record as $key=>$data)
       {
        if (!is_numeric($key)) { $comp_info[$comp]["$key"]=$data; }
       }
     }
    $i++;
  } // while i < numrows
 return($comp_info);
} // end get_companys
