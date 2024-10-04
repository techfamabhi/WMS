<?php
function get_part($db,$pnum_in)
{
  $ret=array();
  $ret["partno"]=$pnum_in;
  $ret["status"]=0;
  $ret["num_rows"]=0;
  $i=0;
  $qstring=<<<SQL
SELECT alt_part_number,alt_type_code, part_desc, p_l,
 part_number, unit_of_measure, shadow_number, num_supercedes,
 num_interchanges, ord_hdr_bucket, part_seq_num, part_category,
 part_long_desc, part_class, part_weight,
 convert(char(10),sale_date_from,101) as sale_on_date,
 convert(char(10),sale_date_thru, 101)as sale_off_date,
 sale_price_code, part_returnable, qty_per_car,
 broken_pack_chrg, restocking_fee, part_kit_type, part_min_gp,
 part_cf_flag, qty_break_flag, 
price00,
price01,
price02,
price03,
price04,
price05,
price06,
price07,
price08,
price09,
price10,
price11,
price12,
price13,
price14,
price15,
recycle_fee
 FROM ALTERNAT,PARTS
 WHERE alt_part_number like "{$pnum_in}"
 AND  shadow_number = alt_shadow_num
SQL;

  $rc=$db->query($qstring);
  $numrows=$db->num_rows();
  $ret["num_rows"]=$numrows;
$i=1;
 while ($i <= $numrows)
 {
    $db->next_record();

     if ($numrows and $db->Record)
     {
      foreach ($db->Record as $key=>$data)
       {
        if (!is_numeric($key)) { $ret[$i]["$key"]=$data; }
       }
     }
    $i++;
   }
  if ($ret["num_rows"] == 0) { $ret["status"]=-35; }
  return($ret);
} // end get_part
?>
