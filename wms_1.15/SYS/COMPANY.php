<?php

// COMPANY.php -- Company Maintenance
// 12/09/21 dse initial
// 02/09/22 dse add host_company
// 03/01/22 dse change to use external templates and js scripts
//TODO

// get the WMS home directory and set top ------------------------------
session_start();
if (get_cfg_var('wmsdir')) $wmsDir=get_cfg_var('wmsdir');
else { echo "<h1>WMS System is not Configured on this System</h1>"; exit; }
$top=str_replace("/var/www","",$wmsDir);

// set self and includes -----------------------------------------------
$thisprogram=basename($_SERVER["PHP_SELF"]);
require("{$wmsDir}/config.php");
require_once("{$wmsInclude}/chklogin.php");
require_once("{$wmsInclude}/cl_Bluejay.php");
require_once("{$wmsInclude}/cl_template.php");
$Bluejay=$top;

// Application Specific Variables -------------------------------------
$temPlate="COMPANY";
$title="Warehouse Maintenance";
$panelTitle="Warehouses";
$SRVPHP="{$wmsServer}/COMPANY_srv.php";
$DRPSRV="{$wmsServer}/dropdowns.php";
// end application specific variables ---------------------------------

//Load html template from the English Directory *******
$parser = new parser;
$parser->theme("en");
$parser->config->show=false;
$data=array("title"=>$title,"panelTitle"=>$panelTitle);
$theVueHtm=$parser->parse($temPlate,$data);
//******************************************************

//Read the vue app script from the js directory ************
$conf=array( "extension"=>'js', "theme"=>'js');
$data=array("SRVPHP"=>"{$SRVPHP}","DRPSRV"=>"{$DRPSRV}");
$vueAppScript=$parser->parse($temPlate,$data,$conf);
//******************************************************

//load the vue header script needed for html head section
$js=$parser->parse("vueheader",$data,$conf);

//Display Header
$pg=new Bluejay;
$pg->title=$title;
$pg->js=$js;
$pg->Display();

// Display Rest of page
$htm=<<<HTML
  {$theVueHtm}
<script>
  {$vueAppScript}
</script>
 </body>
</html>

HTML;
echo $htm;
?>
