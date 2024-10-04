<?php

foreach (array_keys($_REQUEST) as $w) { $$w=$_REQUEST[$w]; }
//error_reporting(0);


ignore_user_abort(true);

$ok=0;
if (isset($_REQUEST["vendor"])) $s_sort=strtoupper($_REQUEST["vendor"]); else $s_sort="";
if (isset($_REQUEST["stype"])) $stype=$_REQUEST["stype"]; else $stype="a";
// etype= entity type, only search customers if etype = c
if (isset($_REQUEST["etype"])) $etype=$_REQUEST["etype"]; else $etype="";

if (!empty($s_sort)) { $ok=1; }
if ($ok) {
require("../include/db_main.php");
 require("wr_log.php");
$db=new WMS_DB;

$srch="%{$s_sort}%";
if (strlen($s_sort) == 1 or $stype == "f") $srch="{$s_sort}%";


$extra="";
if ($etype <> "c") $extra = " and entity_type = 'V'";

$SQL=<<<SQL
select 
  host_id as vendor,
  name
from ENTITY
where (upper(host_id) like "{$srch}"
or upper(name) like "{$srch}")
 and host_id <> " "
{$extra}
order by name
SQL;
wr_log("/tmp/vSearch.log","SQL={$SQL}");
$vendors=array();
$rc=$db->query($SQL);
$numrows=$db->num_rows();
$i=1;
while ($i <= $numrows)
{
 $db->next_record();
     if ($numrows)
     {
 	$vendors[$i]["vendor"]=$db->f("vendor");
 	$vendors[$i]["name"]=$db->f("name");
     }
  $i++;
 } // while i < numr

 
//End Include Common Files
if ($stype == 'j')
{
 $pinfo=json_encode($vendors);
}
else
{
$pinfo="";
if (count($vendors) ) foreach ($vendors as $item)
 {
  $targ="";
  $pinfo.=<<<HTML
<option value="{$item["vendor"]}">{$item["name"]}</option>

HTML;
 } // end foreach menu
} // not stype j

echo $pinfo;
//$fp=fopen("/tmp/ajax.log", "a");
  //fwrite($fp,"$pinfo\n");
  //fclose($fp);

}
?>
