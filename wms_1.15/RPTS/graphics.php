<?php
// emptyBin1.php - report of Bins with no parts
// 10/27/23 Dse initial

foreach (array_keys($_REQUEST) as $w) { $$w=$_REQUEST[$w]; }
//error_reporting(0);
error_reporting(E_ALL);

session_start();

if (get_cfg_var('wmsdir')) $wmsDir=get_cfg_var('wmsdir');
else { echo "<h1>WMS System is not Configured on this System</h1>"; exit; }
$top=str_replace("/var/www","",$wmsDir);

if (isset($_SESSION["wms"])) require($_SESSION["wms"]["wmsConfig"]);
else require("{$wmsDir}/config.php");

$thisprogram="cust_list.php";
if (!isset($wmsInclude)) $wmsInclude="{$wmsDir}/include";
require_once("{$wmsInclude}/chklogin.php");
require_once("{$wmsInclude}/cl_Bluejay.php");
require_once("{$wmsInclude}/db_main.php");

$db=new WMS_DB;

echo "<pre>;";

$SQL=<<<SQL
select image_url from WEB_GRAPHICS

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

$htm=<<<HTML
 <table>
 <tr>
  <td colspan="4">Available Images</td>
 </tr>
 
HTML;
if (count($ret) > 0)
{
 $i=0;
  $addtr=true;
 foreach ($ret as $key=>$r)
 {
  $img=$r["image_url"];
  $i++;
  if ($i > 4)
  {
  $htm.=<<<HTML
   </tr>

HTML;
   $addtr=true;
   $i=1;
  }
  if ($addtr) 
  {
  $htm.=<<<HTML
   <tr>

HTML;
   $addtr=false;
  }
  $htm.=<<<HTML
   <td><img tabindex="0"  alt="" src="/wms/{$img}"  border="0"><br>{$img}</td>

HTML;
 } // end foreach ret
 if ($i < 4)   $htm.=<<<HTML
   </tr>

HTML;

 $htm.=<<<HTML
  </table>
HTML;
echo "</pre>";
 echo $htm;
} // end count ret > 0

