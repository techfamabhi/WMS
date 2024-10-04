<?php
if (get_cfg_var('wmsdir')) $wmsDir=get_cfg_var('wmsdir');
else { echo "<h1>WMS System is not Configured on this System</h1>"; exit; }
$top=str_replace("/var/www","",$wmsDir);

$wmsInclude="{$wmsDir}/include"; // main include for this system
require_once("{$wmsInclude}/db_main.php");
require_once("{$wmsInclude}/cl_pwpost.php");
//require_once("cl_pwpost.php");

$db=new WMS_DB;

echo "<pre>";
$SQL=<<<SQL
select * from WMSCOMMERR
where statusCode in (423, 503)

SQL;

$ret=array();
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
        if (!is_numeric($key)) { $ret[$i]["$key"]=$data; }
       }
     }
    $i++;
  } // while i < numrows

print_r($ret);

if (count($ret) > 0)
{
 foreach ($ret as $key=>$d)
 {
  $data=$d["payload"];
  $errId=$d["errId"];
  $rtype=$d["recordType"];

  $pw=new TPOST;
  $rcc=$pw->Send($rtype,$data,$errId);
  echo "rc=";
  print_r($rcc);
  unset($pw);
 } // end foreach ret
} // end count ret > 0

?>
