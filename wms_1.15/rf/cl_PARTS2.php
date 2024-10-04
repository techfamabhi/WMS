<?php
//requires /usr1/include/get_table.php
// 01/19/18 add Intchg, supercede, notes, alternates, qtybrk
// 02/05/21 dse add support for type T supercedes
// Add Lookup by part number 
// 01/12/22 dse change to support WMS
class PARTS  
{
 var $status;
 var $shadow_number;
 var $p_l;
 var $part_number;
 var $Info;
 var $Select;
 var $Data;
 var $WHSEQTY;
 var $ProdLine;
 var $Notes;
 var $Alternates;

function Load($shadow_number,$company=-1) {
 global $db;
 $this->shadow_number=$shadow_number;
 $where="where shadow_number = $shadow_number";
 $this->Info=get_table($db,"PARTS",$where);
 $this->Data=$this->Select($shadow_number);
 $this->ProdLine=$this->PLSelect($this->Data,$company);
 $tmp=$this->WHSEQTYSelect($shadow_number,$company);
 $this->WHSEQTY=$tmp;
 $tmp=$this->Load_Alternates($shadow_number);
 $this->Alternates=$tmp;
 //if ($this->Data["part_num_notes"] > 0)
  //{
   //$tmp=$this->Load_NOTES($shadow_number);
   //$this->Notes=$tmp;
  //}
}

 function Select($shadow_number)
 {
  global $db;
  $Data=array();
  $this->shadow_number=$shadow_number;
  $SQL=$this->Info["Select"];
  $rc=$db->query($SQL);
  $numrows=$db->num_rows();
  $i=1;
  while ($i <= $numrows)
  {
   $db->next_record();
     if ($numrows and $db->Record) 
     {
      foreach ($db->Record as $key=>$data)
       {
        if (!is_numeric($key))
        {
         $Data["$key"]=$data;
         if ($key == "p_l") $this->p_l=$data;
         if ($key == "part_number") $this->part_number=$data;
        }
       }
     }
    $i++;
  } // while i < numrows
 if ($numrows > 0) $this->status=$numrows;
 else $this->status=-35;
return($Data);
 } // end Select 

function AddUpd($num)
{
  global $db;
//Need to Write full Update and Insert 
} // end AddUpd

function UpdatePART()
{
//Limited Update for A/R Inquiry
  global $db;
  return($rc);
} // end of Update

 function WHSEQTYSelect($shadow_number,$company=-1)
 {
 global $db;
 $Data=array();
 $tmp=array();
 $where_add="WHERE ms_shadow = " . $shadow_number;
 if ($company <> -1) $where_add.=" AND ms_company = {$company}";
  else $where_add.=" AND ms_company > 0";
 $tmp=get_table($db,"WHSEQTY",$where_add);
 $SQL=$tmp["Select"];
 $SQL.=" ORDER BY ms_company";
 $rc=$db->query($SQL);
 $numrows=$db->num_rows();
  $i=1;
  while ($i <= $numrows)
  {
   $db->next_record();
     if ($numrows and $db->Record)
     {
      $num=$db->f("ms_company");
      foreach ($db->Record as $key=>$data)
       {
        if (!is_numeric($key))
        {
         $Data[$num]["$key"]=$data;
        }
       }
     }
    $i++;
  } // while i < numrows
//$Data["Info"]=$tmp;
return($Data);
}
 function PLSelect($part,$comp)
 {
 global $db;
 $Data=array();
 if ($comp < 1) $comp=1;
 $where_add=<<<SQL
WHERE pl_code = "{$part["p_l"]}"
and pl_company = {$comp}

SQL;
  $tmp=get_table($db,"PRODLINE",$where_add);
 $SQL=$tmp["Select"];

 $rc=$db->query($SQL);
 $numrows=$db->num_rows();
  $i=1;
  while ($i <= $numrows)
  {
   $db->next_record();
     if ($numrows and $db->Record)
     {
      foreach ($db->Record as $key=>$data)
       {
        if (!is_numeric($key))
        {
         $Data["$key"]=$data;
        }
       }
     }
    $i++;
  } // while i < numrows
return($Data);

 } // end PLSelect

function Load_Alternates($shadow_number)
 {
 global $db;
 $Data=array();
 $tmp=array();
 $where_add=<<<SQL
WHERE alt_shadow_num = {$shadow_number} and alt_type_code < 9997
SQL;
 $tmp=get_table($db,"ALTERNAT",$where_add);
 $SQL=$tmp["Select"];
 $SQL.=" ORDER BY alt_shadow_num, alt_type_code, alt_part_number,alt_uom";
 $rc=$db->query($SQL);
 $numrows=$db->num_rows();
  $i=1;
  while ($i <= $numrows)
  {
   $db->next_record();
     if ($numrows and $db->Record)
     {
      foreach ($db->Record as $key=>$data)
       {
        if (!is_numeric($key))
        {
         $Data[$i]["$key"]=$data;
        }
       }
     }
    $i++;
  } // while i < numrows
//$Data["Info"]=$tmp;
return($Data);
 } // end Load_Alternates
function Load_MSUPER($shadow_number,$type="S")
 {
 global $db;
 $Data=array();
 $tmp=array();
 $where_add=<<<SQL
WHERE si_from_shadow = {$shadow_number}
and si_code = "{$type}"
SQL;
if ($type == "I")
{ //include type T too
 $where_add=<<<SQL
WHERE si_from_shadow = {$shadow_number}
and si_code in ("I","T")
SQL;
} //include type T too

 $tmp=get_table($db,"MSUPER",$where_add);
 $SQL=$tmp["Select"];
 $rc=$db->query($SQL);
 $numrows=$db->num_rows();
  $i=1;
  while ($i <= $numrows)
  {
   $db->next_record();
     if ($numrows and $db->Record)
     {
      foreach ($db->Record as $key=>$data)
       {
        if (!is_numeric($key))
        {
         $Data[$i]["$key"]=$data;
        }
       }
     }
    $i++;
  } // while i < numrows
//$Data["Info"]=$tmp;
return($Data);
 } // end Load_MSUPER
function Load_NOTES($shadow_number)
 {
 global $db;
 $Data=array();
 $tmp=array();
 $where_add=<<<SQL
WHERE pnote_shadow = {$shadow_number}
SQL;
 $tmp=get_table($db,"PARTNOTE",$where_add);
 $SQL=$tmp["Select"];
 $SQL.="order by pnote_shadow, pnote_line";
 $rc=$db->query($SQL);
 $numrows=$db->num_rows();
  $i=1;
  while ($i <= $numrows)
  {
   $db->next_record();
     if ($numrows and $db->Record)
     {
      foreach ($db->Record as $key=>$data)
       {
        if (!is_numeric($key))
        {
         $Data[$i]["$key"]=$data;
        }
       }
     }
    $i++;
  } // while i < numrows
//$Data["Info"]=$tmp;
return($Data);
 } // end Load_NOTES

function lookup($pnum_in)
 {
  global $db;
  $SQL=<<<SQL
SELECT 
 shadow_number,
 p_l,
 part_number,
 part_desc,
 unit_of_measure,
 alt_part_number,
 alt_type_code,
 alt_uom
 from ALTERNAT,PARTS
 WHERE alt_part_number like "{$pnum_in}"
 AND  shadow_number = alt_shadow_num

SQL;
 $Data=array();
 $rc=$db->query($SQL);
 $numrows=$db->num_rows();
  $i=1;
  while ($i <= $numrows)
  {
   $db->next_record();
     if ($numrows and $db->Record)
     {
      foreach ($db->Record as $key=>$data)
       {
        if (!is_numeric($key))
        {
         $Data[$i]["$key"]=$data;
        }
       }
     }
    $i++;
  } // while i < numrows
 if ($numrows > 0) $this->status=$numrows;
 else $this->status=-35;
 return($Data);
 } // end lookup
} // end class
?>
