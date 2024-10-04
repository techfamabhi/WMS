<?php
// PRINTERS.php -- Whse Zone Maintenance
// 12/09/21 dse initial
// 03/02/22 dse change to use external templates and js scripts

/*
TODO
 table needs to be modified to include is pickable
 maybe also put a wayable

| Field       | Type        | Null | Key | Default | Extra |
| lpt_number      | smallint(6) | NO   | PRI | NULL    |       |
| lpt_description | char(30)    | YES  |     | NULL    |       |
| lpt_company     | smallint(6) | YES  |     | NULL    |       |
| lpt_pathname    | char(128)   | YES  |     | NULL    |       |
| lpt_type        | char(20)    | YES  |     | NULL    |       |
| lpt_copy_code   | char(4)     | YES  |     | NULL    |       |
| lpt_prompt      | char(15)    | YES  |     | NULL    |      

*/

session_start();
if (get_cfg_var('wmsdir')) $wmsDir=get_cfg_var('wmsdir');
else { echo "<h1>WMS System is not Configured on this System</h1>"; exit; }
$top=str_replace("/var/www","",$wmsDir);

$thisprogram=basename($_SERVER["PHP_SELF"]);
require("{$wmsDir}/config.php");
require_once("{$wmsInclude}/chklogin.php");
require_once("{$wmsInclude}/cl_Bluejay.php");
require_once("{$wmsInclude}/cl_template.php");
$Bluejay=$top;

// Application Specific Variables -------------------------------------
if (isset($company_num)) $operComp=$company_num;
else $operComp=0;
$temPlate="PRINTERS";
$title="Printer Maintenance";
$panelTitle="Printers";
$SRVPHP="{$wmsServer}/PRINTERS_srv.php";
$DRPSRV="{$wmsServer}/dropdowns.php";
// end application specific variables ---------------------------------

//Load html template **********************************
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
$w=$parser->parse("vueheader",$data,$conf);
$js=$w;

//Display Bluejay Header
$pg=new Bluejay;
$pg->title=$title;
$pg->js=$js;
$pg->Display();

//Rest of page
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
