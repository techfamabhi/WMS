<?php

$wmsInclude="../include";
require_once("{$wmsInclude}/db_main.php");

$db=new DB_MySQL;

//create a Cursor
 //order by p_l,part_seq_num,part_number
$SQL=<<<SQL
 select p_l,part_number,part_desc,part_class
 from PARTS
 where p_l = :PL
 -- and ms_shadow = shadow_number
 -- and ms_company = 1
 -- and (qty_avail <> 0 or maximum > 0)

SQL;

$PL="WIX";
$company=1;
//Ony can get 1 param to bind on the create cursor function
$params=array(":PL" => "{$PL}");

  $sth=$db->create_cursor($SQL,$params);
  if ($sth==true)
  { //SQL ok
   $numrows=0;
   $i=1;
   $out=<<<HTML
<table>
  <tr>
   <th>P/L</th>
   <th>Part_Number</th>
   <th align="left">Desc</th>
   <th align="left">Class</th>
  </tr>

HTML;
   while ( $results=$db->curfetch())
   {
    $out.=<<<HTML
  <tr>
   <td>{$results["p_l"]}</td>
   <td>{$results["part_number"]}</td>
   <td>{$results["part_desc"]}</td>
   <td>{$results["part_class"]}</td>
  </tr>

HTML;
    $numrows=$i;
    $i++;
   }
   $out.="</table>";
   echo "Numrows: {$numrows}\n";
   echo $out;
  } //SQL ok

?>
