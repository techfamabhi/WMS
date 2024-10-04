<?php
// config.php -- set config vars for Inbound and Outboud Operations

if (get_cfg_var('wmsdir')) $wmsDir=get_cfg_var('wmsdir');
else { echo "<h1>WMS System is not Configured on this System</h1>"; exit; }
$top=str_replace("/var/www","",$wmsDir);
require("{$wmsDir}/config.php");

//extra settings
//"inType"=>"text", // Text (pipe delimited) or json
$globalSettings=array(
"inType"=>"text", // Text (pipe delimited) or json
"inDir"=>"/usr1/wms/Inbound", // Host to WMS directory
"xinDir"=>"/usr1/schema/WMS/data/PARTS", // temp test directory
"inNotice"=>"edavenbach@gmail.com",
//"outType"=>"json", // Text (pipe delimited) or json
"outType"=>"Text", // Text (pipe delimited) or json
"outDir"=>"/usr1/wms/Outbound", // WMS to Host Directory
"outNotice"=>"edavenbach@gmail.com",
"doneDir"=>"/usr1/wms/Loaded", // loaded directory
"sentDir"=>"/usr1/wms/Sent", // Sent directory
"errDir"=>"/usr1/wms/Errors", // error directory
"ServiceIn"=>"/usr1/wms/Service/In", // Service In
"ServiceOut"=>"/usr1/wms/Service/Out" // Service Out

);

if (count($globalSettings))
 foreach ($globalSettings as $var=>$val)
 {
  if (isset($_SESSION)) $_SESSION[$var]=$val;
  $$var=$val;
 } // end foreach global setting

if (isset($_REQUEST["dis"]) and isset($_SESSION)  and $_REQUEST["dis"] == "Y")
{
 echo "<pre>";
 print_r($_SESSION);
} // end dis = Y
?>
