<?php
session_start();
if (get_cfg_var('wmsdir')) $wmsDir=get_cfg_var('wmsdir');
else { echo "<h1>WMS System is not Configured on this System</h1>"; exit; }
$top=str_replace("/var/www","",$wmsDir);

$thisprogram=basename($_SERVER["PHP_SELF"]);
require("{$wmsDir}/config.php");

require_once("{$wmsInclude}/db_main.php");
require_once("{$wmsInclude}/wr_log.php");

if (isset($_REQUEST["po"])) $po=$_REQUEST["po"]; else $po=0;
if (isset($_REQUEST["vend"])) $vend=$_REQUEST["vend"]; else $vend="";

 wr_log("/tmp/checkPO.log","in po={$po} in Vendor={$vend}");
$comp=$wmsDefComp;
$db=new WMS_DB;

$SQL=<<<SQL
select vendor
from POHEADER
where host_po_num = "{$po}"

SQL;
$vendor="NOTFOUND";
 wr_log("/tmp/checkPO.log","{$SQL}");

  $rc=$db->query($SQL);
  $numrows=$db->num_rows();
  $i=1;
  while ($i <= $numrows)
  {
   $db->next_record();
     if ($numrows)
     {
        $vendor=$db->f("vendor");
     }
     $i++;
   } // while i < numrows

//header('Content-type: application/json');
 wr_log("/tmp/checkPO.log","po={$po} Vendor={$vendor}");
echo $vendor;
?>
