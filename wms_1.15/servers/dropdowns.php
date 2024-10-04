<?php

// dropdowns.php -- Server for easy validation dropdowns
//12/07/21 dse initial

/*
input json;  inputData={"action":"getGroups"} 

always returns opt_val and opt_desc in return json

so a generic vue script can create the dropdown list box

*/

$DEBUG=true;

require("../include/wr_log.php");
require("../include/db_main.php");
$inputdata = file_get_contents("php://input");
$reqdata=json_decode($inputdata,true);
$db=new WMS_DB;

$rdata = array();

if (isset($_REQUEST["searcH"])) $srch=$_REQUEST["searcH"]; else $srch="";

if ($DEBUG) wr_log("/tmp/dropdown.log","inputData={$inputdata}");
$action=$reqdata["action"];
if (isset($reqdata["id"])) $id=$reqdata["id"]; else $id=0;
if (isset($reqdata["region"])) $region=$reqdata["region"]; else $region="";
if (isset($reqdata["company"])) $company=$reqdata["company"]; else $company="";


if ($DEBUG) wr_log("/tmp/dropdown.log","Switching={$action}");
$SQL="";

switch ($action)
{
 case "getGroups":
 {
  $where="";
  $SQL=<<<SQL
select
group_id as opt_val,
group_desc as opt_desc
from WEB_GROUPS
{$where}
order by group_id

SQL;
  break;
 } // end getGroups
 case "getGraphics":
 {
  $SQL=<<<SQL
 select image_url as opt_val,
        image_url as opt_desc
 from WEB_GRAPHICS
 order by image_url

SQL;
  break;
 } // end getGrapics
 case "getComps":
 {
 $where="where company_number > 0\n";
 //wr_log("/tmp/dropdown.log","company = {$company}");
 if ($company <> "") $where="where company_number > -1\n";
 //wr_log("/tmp/dropdown.log","where = {$where}");
 if ($region <> "") $where.="and company_region like \"{$region}%\"\n";
 if ($db->DBType == "SYBASE")
 {
  $SQL=<<<SQL
 select company_number as opt_val,
        company_abbr + " " + TRIM(company_city) + "-" + company_city as opt_desc
 from COMPANY
 {$where}
 order by company_number

SQL;
 }
 else
 {
 $SQL=<<<SQL
  select company_number as opt_val,
        CONCAT(company_abbr,"-",company_city," ",company_state) as opt_desc
 from COMPANY
 {$where}
 order by company_number

SQL;
 }
  break;
 } // end getGrapics
} // end switch reqdata action

if ($SQL <> "")
{
 $rc=$db->query($SQL);
  $numrows=$db->num_rows();
  $i=0;
  while ($i <= $numrows)
  {
   $db->next_record();
     if ($numrows and $db->Record)
     {
      foreach ($db->Record as $key=>$data)
       {
        if (!is_numeric($key)) { $rdata[$i]["$key"]=$data; }
       }
     }
    $i++;
  } // while i < numrows
} // end SQL <> ""
if (count($rdata)) $x=json_encode($rdata);
else $x="[]";
if ($DEBUG)
{
 wr_log("/tmp/dropdown.log","dropdowns.php in getGroups");
 wr_log("/tmp/dropdown.log",$SQL);
 wr_log("/tmp/dropdown.log",$x);
 wr_log("/tmp/dropdown.log","end dropdowns.php getGroups");
} // end DEBUG
header('Content-type: application/json');
echo $x;


?>
