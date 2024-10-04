<?php
include "../include/db_main.php";
$db=new WMS_DB;
$SQL=<<<SQL
select * from SHIPVIA
where via_code <> " "

SQL;
$data=$db->gData($SQL);

$table=new rcpTable;
        /*flds          array of Field Prompts for the table
        */
        //items         array of data items to match flds
     $table->heading="My Table";
     $flds=array(
"via_code"=>"Code",
"via_desc"=>"Description",
"via_SCAC"=> "SCAC",
"pack_rescan"=>"Pack Rescan",
"drop_zone"=> "Drop Zone"
);

$d1=array();
foreach($data as $key=>$d)
{
 $i=$key - 1;
 $d1[$i]=$d;
}
unset($data);
$data=$d1;
unset($d1);

     $html=$table->frmtTable($flds,$data);
echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta http-equiv="Content-Language" content="en-us">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<meta name="robots" content="noindex">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
<style>
.FormHeaderFont{ color: #000080; font-weight: bold; font-size: 18px; }
.FormSubHeaderFont{ color: #000080; font-weight: bold; font-size: 14px; }
.FieldCaptionTD{ border-style: outset; border-width: 1px; background-color: #B0BACE; color: #000000; font-size: 13px; font-weight: bold; }
</style>
{$table->css}

</head>
<body>
{$html}
</body>
</html>
HTML;

/* class: rcpTable  -- Responsive Table
   version 1.0
   
   vars;
 	heading		Heading for the table
 	headerClass	class of Heading
 	captionClass	class of field captions
 	fieldClass	class of the field
 	css		the html link of the css file to include in your header
 	pathToCss	the full path to the responsiveTable.css file
   
    Example;
     $table=new rcpTable
     //flds		array of Field names and prompts for the data
     $flds=array(
            ["via_code"] => "Code",
            ["via_desc"] => "Description",
            ["via_SCAC"] => "SCAC",
            ["pack_rescan"] => "Pack Rescan",
            ["drop_zone"] => "Drop Zone"
        );
 	//data		array of data items of field=>value
      $data=array(
 	[1] => Array (
            [via_code] => COM
            [via_desc] => Commercial Truck Line
            [via_SCAC] => 
            [pack_rescan] => 0
            [drop_zone] => 
        ),

    	[2] => Array (
            [via_code] => FDX
            [via_desc] => Federal Express
            [via_SCAC] => 
            [pack_rescan] => 0
            [drop_zone] => 
        ),

    	[3] => Array (
            [via_code] => UPB
            [via_desc] => UPS Blue (Next Day Air)
            [via_SCAC] => 
            [pack_rescan] => 0
            [drop_zone] => 
        ),

    	[4] => Array (
            [via_code] => UPR
            [via_desc] => UPS Red (2nd Day Air)
            [via_SCAC] => 
            [pack_rescan] => 0
            [drop_zone] => 
        )
       );


);
   ***************  OR set flds and data to json arrays ********************
  $flds="{
	"via_code": "Code",
	"via_desc": "Description",
	"via_SCAC": "SCAC",
	"pack_rescan": "Pack Rescan",
	"drop_zone": "Drop Zone"
}";
  $data="[{
	"via_code": "COM",
	"via_desc": "Commercial Truck Line",
	"via_SCAC": "",
	"pack_rescan": "0",
	"drop_zone": ""
}, {
	"via_code": "FDX",
	"via_desc": "Federal Express",
	"via_SCAC": "",
	"pack_rescan": "0",
	"drop_zone": ""
}, {
	"via_code": "UPB",
	"via_desc": "UPS Blue (Next Day Air)",
	"via_SCAC": "",
	"pack_rescan": "0",
	"drop_zone": ""
}, {
	"via_code": "UPR",
	"via_desc": "UPS Red (2nd Day Air)",
	"via_SCAC": "",
	"pack_rescan": "0",
	"drop_zone": ""
}, {
	"via_code": "UPS",
	"via_desc": "UPS Regular",
	"via_SCAC": "",
	"pack_rescan": "0",
	"drop_zone": ""
}, {
	"via_code": "USP",
	"via_desc": "USPS",
	"via_SCAC": "",
	"pack_rescan": "0",
	"drop_zone": ""
}, {
	"via_code": "WC",
	"via_desc": "Will Call",
	"via_SCAC": "",
	"pack_rescan": "0",
	"drop_zone": ""
}]";
     $html=$table->frmtTable($flds,$data);
     echo $table->css;
     echo $html;
*/

class rcpTable
{
 public $heading;
 public $headerClass;
 public $captionClass;
 public $fieldClass;
 public $css;
 public $pathToCss="../assets/css";

 public function __construct()
 {
  $this->heading="";
  $this->headerClass="FormSubHeaderFont";
  $this->captionClass="FieldCaptionTD";
  $this->fieldClass="";
  $this->css=<<<HTML
<link rel="stylesheet" href="{$this->pathToCss}/responsiveTable.css">
HTML;
 } // end contruct

 public function frmtTable($fields,$data)
 {
  //check if params are arrays or json
  $flds=$this->checkSetParam($fields);
  if (!isset($flds)) return "";

  $items=$this->checkSetParam($data);
  if (!isset($items)) return "";

  $cols=count($flds);
  if ($cols < 1) // there are no fields, return empty
  {
   return "";
   break;
  } // end cols < 1
  $htm=<<<HTML
  <div class="w3-half">
   <table class="rspTable table table-bordered table-striped">
    <thead role="rowgroup">

HTML;
  if (count($this->heading) > 0)
  {
   $htm.=<<<HTML
     <tr>
      <td colspan="{$cols}" class="{$this->headerClass}" align="center">{$this->heading}</td>
     </tr>

HTML;
  } // end count heading > 0

  if ($cols > 0)
  {
   $htm.=<<<HTML
     <tr role="row">
HTML;
   foreach($flds as $idx=>$fld)
   {
    $htm.=<<<HTML
      <td role"columnheader" class="{$this->captionClass}" align="center" >{$fld}</td>

HTML;
   } // end foreach $flds 
  } // end cols > 0

  $htm.=<<<HTML
     </tr>
    </thead>

HTML;
  if (count($items) > 0)
  {
   foreach ($items as $idx=>$record)
   {
    if (is_array($record))
     {
      $cls="";
      if (trim($this->fieldClass) <> "") $cls=" class \"{$this->fieldClass}\'";
      $htm.=<<<HTML
      <tr>

HTML;
      foreach ($record as $fldKey=>$data)
      {
       $value=$data;
       if ($value == "") $value="&nbsp;"; // prevents wierd display on small screen
       $htm.=<<<HTML
       <td  data-label="{$flds[$fldKey]}" align="center"{$cls}>{$value}</td>

HTML;
      } // end foreach record
   $htm.=<<<HTML
         </tr>

HTML;
     } // end is array record
   } // end foreach items
  } // end count items > 0
  $htm.=<<<HTML
   </table>
  </div>

HTML;
  return $htm;
 } // end frmtTable

private function isJson($in)
{
 json_decode($in);
 return json_last_error() === JSON_ERROR_NONE;
} // end isJson

private function checkSetParam($in)
{
 //check if passed
  if (is_array($in)) $out=$in;
  else
  { // check if json
   if ($this->isJson($in)) $out=json_decode($in,true);
  } // check if json
  if (isset($out)) return $out; else return "";
} // end checkSetParam

} // end class rcpTable
