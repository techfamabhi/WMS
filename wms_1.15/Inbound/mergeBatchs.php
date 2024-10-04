<?php
/*  mergeBatchs.php 

	merge an array of batches of RCPT_SCAN to one batch
	Find the largest batch, then adds all other batches to it

 TODO;
  update RCPT_BATCH status of merged batchs to 9
 
  possible enhance to only move parts from a specific PO number
  the also move RCPT_INWORK to new batch if needed

*/
function mergeBatchs($db,$batches)
{ 
 if (!is_array($batches)) return false;
 $nBatch=0;
 $b="";
 $newbatchs=array();
 foreach ($batches as $k=>$batch)
 {
  if (strlen($b) > 0) $b.=",";
  $b.=$batch;
  $SQL=<<<SQL
select max(line_num) as ml, count(*) as cnt from RCPT_SCAN where batch_num = {$batch}

SQL;
  $rc=loadBCnt($db,$SQL);
  $newbatchs[$batch]=$rc;
 } // end foreach batches

$nBatch=first_key($newbatchs);
$nxtLine=$newbatchs[$nBatch]["ml"] + 1;
//echo "<pre> next={$nxtLine}\n";
//print_r($newbatchs);
//exit;

 $SQL=<<<SQL
select * from RCPT_SCAN
where batch_num in ({$b})

SQL;
 
$bdata=array();
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
        if (!is_numeric($key)) { $bdata[$i]["$key"]=$data; }
       }
     }
    $i++;
  } // while i < numrows

 if (isset($rc)) unset($rc);
 if ($nBatch > 0 and count($bdata) > 0)
 {
  foreach ($bdata as $k=>$d)
  {
   if ($d["batch_num"] == $nBatch) continue;
   else
   { // update record to new batch;
    $SQL=<<<SQL
update RCPT_SCAN set batch_num = {$nBatch}, line_num = {$nxtLine}
where batch_num = {$d["batch_num"]} and line_num = {$d["line_num"]}

SQL;
    $rc=$db->Update($SQL);
    $nxtLine++;
   } // update record to new batch;
  } // end foreach $bdata
 } // end nBatch > 0 count bdata > 0
 return $nBatch;
} // end mergeBatches

if (!function_exists('loadBCnt'))
{
function loadBCnt($db,$SQL) // modified for max line as ml
{
  $cnt=array("cnt"=>0,"ml"=>0);
  $rc=$db->query($SQL);
  $numrows=$db->num_rows();
  $i=1;
  while ($i <= $numrows)
  {
   $db->next_record();
     if ($numrows)
     {
        $cnt["cnt"]=$db->f("cnt");
        $cnt["ml"]=$db->f("ml");
     }
     $i++;
   } // while i < numrows
  return $cnt;
} // end loadBCnt
}

if (!function_exists('firstKey'))
{
    function first_key(array $arr) {
        foreach($arr as $key => $unused) {
            return $key;
        }
        return NULL;
    }

}
